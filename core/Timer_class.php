<?php

	/*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*	TIMER CLASS
	*	By photofroggy
	*	Do not edit!
	*/
	
class Timer {
	protected $events = array(); // Array to store timed events.
	protected $timers = array(); // Array to store timers...
	protected $rands = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r');
	protected $Bot;
	
	function __get($var) {
		return $this->$var;
	}
	function __construct($bot) {
		$this->Bot = $bot;
	}
	
	function addEvt($module, $time, $data = false, $evts = false) {			// This method should be used to add a timed event.	
		if ($module == false) {
			return false;
		}
		if (!array_key_exists($module, $this->Bot->mod)) {
			return false;
		}
		if ($this->Bot->mod[$module]->status == true) {	
			if (is_numeric($time)) {
				$ctime = time();					// This is used instead of time() to make sure everything uses the exact same time.
				$a = $this->rands[array_rand($this->rands)]; $b = $this->rands[array_rand($this->rands)];
				$id = substr(sha1($ctime.$a.$b), -9); // Timer ID is simply the last 9 digits of the encrypted time.
				
				if ($evts==false) {
					$evts = 'timer';
				}
				
				$this->events[$id] = array(					// Event [id] as array containing;
					'Mod'  	=> $module,						// ----- Mod   as $module
					'exec' 	=> $ctime + $time,				// ----- exec  as time() + $time
					'args' 	=> $data,						// ----- args   as $data.
					'event' => $evts,						// ----- event as $evts.
				);											// End array.
				
				return $id;		// Send the event id back to whoever requested it!
			}
		}
		return false;		// This should only happen if the timer can not be applied.
	}
	
	function delEvt($ID) {
		/*		DELETE EVENT
		*
		*		This method should be used to delete
		*		events stored on the timer. You must
		*		provide the ID of the event to be
		*		deleted. Event data for the timer class
		*		can not be edited outside of this class.
		*/
	
		if (array_key_exists($ID, $this->events)) {
			unset($this->events[$ID]);
			return true;						// Return true if the event has been deleted.
		}
		return false;							// Return false otherwise.
	}
	
	function triggerEvents() {
		/*		TRIGGER EVENTS
		*
		*		This method runs through all of the events which have
		*		been stored, and checks whether it is time to run the
		*		event yet. If so, it runs the event and then deletes the
		*		event from the array of events stored in this class.
		*
		*		This is not something to be used by modules and module
		*		developers! If you want to trigger this event through
		*		a module for some unknown reason then use event::loop();.
		*		That said, I advise against it greatly, because you'll
		*		trigger every module that uses event::loop() as well as
		*		this method.
		*/
		
		if (!empty($this->events)) {
			$time = time();
			foreach ($this->events as $eid => $ed) {
				$module = $this->events[$eid]['Mod'];
				if ($time >= $this->events[$eid]['exec']) {
					if (array_key_exists($module, $this->Bot->mod)) {
						if ($this->Bot->mod[$module]->status == true) {
							$data  = $this->events[$eid]['args'];
							$event = $this->events[$eid]['event'];
							$done = $this->Bot->Events->trigger_mod($module, $event, $data);
						}
					}
					unset($this->events[$eid]);
				}
			}
		}
	}
	
	function Start() {
		/*
		*		This method is used to start a timer. Not like
		*		the timers previously defined in the class,
		*		but more like a stop watch. The other timers
		*		can be seen as "egg timers".
		*		
		*		This method should return the id of the timer
		*		that has been started. This id should be used
		*		to stop the timer with the appropriate method.
		*/

		$start = time();
		$id = substr(sha1($start), -9);
		$this->timers[$id] = array(
			'start' => $start
		);
		return $id;
	
	}
	
	function Stop($id) {
		/*
		*		This is the method to stop the stopwatch
		*		style timers! Use the id given and the name
		*		of the module stopping the timer if you
		*		want to do thing properly.
		*/
	
		$end = time();
		if (isset($this->timers[$id])) {
			$start = $this->timers[$id]['start'];
			$time  = $end - $start;
			unset($this->timers[$id]);
			return $time;
		} else {
			return 'Timer not set';
		}
	}
}

?>