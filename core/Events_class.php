<?php

	/*
	*	Contra Events system.
	*	Version 3
	*	Made by photofroggy!
	*	
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*	
	*	This pretty much handles all
	*	module events and commands
	*	for Contra! Editing this is
	*	really not recommended...
	*/

class Event_System {
	protected $core;
	protected $events = array(
		'evt' => array(),
		'cmd' => array(),
		'BDS' => array()
	);
	
	public function __get($var) { return $this->$var; }
	public function __construct($core) { $this->core = $core; }
	public function load_mods() {
		inc_files('./plugins/extensions', '.php', array('core'=>$this->core));
		foreach($this->events['evt'] as $event => $mods)
			foreach($mods as $mod => $meths)
				if(!array_key_exists($mod, $this->core->mod)) unset($this->events['evt'][$mod]);
		foreach($this->events['cmd'] as $cmd => $i)
			if(!array_key_exists($i['m'], $this->core->mod)) unset($this->events['cmd'][$cmd]);
	}
	
	public function trigger($event,
		$p0 = false,
		$p1 = false,
		$p2 = false,
		$p3 = false,
		$p4 = false,
		$p5 = false,
		$p6 = false,
		$p7 = false,
		$p8 = false,
		$p9 = false) {
		// There currently aren't any events which have this many args, but 10 spaces are available just in case.
		if(array_key_exists($event, $this->events['evt'])) {
			foreach($this->events['evt'][$event] as $id => $data) {
				if($this->core->mod[$data['m']]->status == true){
					if (is_callable($data['f'])) {
						// it's a callback function
						$data['f']($p0, $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9);
					} else {
						$this->core->mod[$data['m']]->$data['f']($p0, $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9);
					}
				}
			}
		}
	}
	
	public function trigger_mod($mod, $event,
		$p0 = false,
		$p1 = false,
		$p2 = false,
		$p3 = false,
		$p4 = false,
		$p5 = false,
		$p6 = false,
		$p7 = false,
		$p8 = false,
		$p9 = false) {
	
		if(!array_key_exists($event, $this->events['evt'])) return false;
		foreach($this->events['evt'][$event] as $id => $data) {
			if($data['m'] == $mod && $this->core->mod[$data['m']]->status == true) {
				if (is_callable($data['f'])) {
					// it's a callback function
					$data['f']($p0, $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9);
				} else {
					$this->core->mod[$data['m']]->$data['f']($p0, $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9);
				}
			}
		}
		return true;
	}
	
	public function triggerBDS($message, $from) {
		foreach ($this->events['BDS'] as $regex => $arr) {
			if (preg_match($regex, $message) === 1) {
				foreach ($arr as $i => $data) {
					if ($this->core->mod[$data['m']]->status == true) {
						$parts = explode(':', $message, 4);
						if (is_callable($data['f'])) {
							// it's a callback function
							$data['f']($parts, $from, $message);
						} else {
							$this->core->mod[$data['m']]->$data['f']($parts, $from, $message);
						}
					}
				}
			}
		}
	}

	public function command($command, $ns, $from, $message) {
		/*
		*		This is where the magic happens!
		*		Commands are triggered if we don't need
		*		to give the help for it. We also find out
		*		what channel to use as the target namespace!
		*		Also pay close attention to switches, to
		*		make sure we don't launch commands when
		*		they or the module they belong to are switched
		*		off.
		*/
		if(!array_key_exists(strtolower($command), $this->events['cmd']))
			return $this->core->Console->Notice('Received unknown command "'.$command.'" from '.$from.'.');
		if (array_key_exists($from, $this->core->dAmn->last_command) && microtime(true) - $this->core->dAmn->last_command[$from] < 1) return;
		$cmda = $this->events['cmd'][strtolower($command)];
		if($cmda['s'] === false) return;
		if($this->core->mod[$cmda['m']]->status === false) return;
		if(substr(args($message,1),0,1)=='#') {
			$tns = $this->core->dAmn->format_chat(args($message,1));
			$message = args($message,0).' '.args($message,2,true);
		} else {$tns = $ns; }
		if(args($message,1)=='?'&& !empty($cmda['h'])) {
			$this->core->dAmn->say($tns, $from.': '.$cmda['h']);
			return;
		}
		if($this->core->user->hasCmd($from, $command)) {
			$this->core->dAmn->last_command[$from] = microtime(true);
			return $this->core->mod[$cmda['m']]->$cmda['e']($ns, $from, rtrim($message), $tns);
		}
		$this->core->Console->Notice(
			'User "'.$from.'" was denied access to "'.$command.'" in '.$this->core->dAmn->deform_chat($ns,$this->core->username).'.'
		);
	}
	
	public function is_hooked($mod, $meth, $event) {
		// This returns the event hook number on success, False on failure. Use === when comparing.
		if(!array_key_exists($event, $this->events['evt'])) return false;
		foreach($this->events['evt'][$event] as $id => $info)
			if($info['m'] == $mod && $info['f'] == $meth) return $id;
		return false;
	}
	
	public function hook($mod, $meth, $event) {
		if(!array_key_exists($event, $this->events['evt'])) $this->events['evt'][$event] = array();
		if($this->is_hooked($mod, $meth, $event) !== false) return true;
		$this->events['evt'][$event][] = array(
			'm' => $mod,
			'f' => $meth,
		);
		return true;
	}

	public function hookOnce($mod, $meth, $event) {
		$that = $this;

		$cb = function($p0, $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9) use (&$cb, $mod, $meth, $event, $that) {
			if($that->core->mod[$mod]->status == true){
				if (is_callable($meth)) {
					// it's a callback function
					$meth($p0, $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9);
				} else {
					$that->core->mod[$mod]->$meth($p0, $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9);
				}
			}
			$that->unhook($mod, $cb, $event);
		};
		$this->hook($mod, $cb, $event);
		return true;
	}
	
	public function unhook($mod, $meth, $event) {
		if(!array_key_exists($event, $this->events['evt'])) return false;
		$hook = $this->is_hooked($mod, $meth, $event);
		if($hook === false) return true;
		array_splice($this->events['evt'][$event], $hook, 1);
		if(empty($this->events['evt'][$event])) unset($this->events['evt'][$event]);
		return true;
	}
	
	private function regexify ($path) {
		$parts = explode(':', $path, 4);
		$count = count($parts);
		if ($count < 3)
			for ($i = 3 - $count; $i > 0; $i--)
				$path .= ':*';

		$path = str_replace('*', '.*', $path);
		$path = '/' . $path . '/i';
		return $path;
	}
	
	public function is_hookedBDS($mod, $meth, $path) {
		// This returns the event hook number on success, False on failure. Use === when comparing.
		$regex = $this->regexify($path);
		if(!array_key_exists($regex, $this->events['evt'])) return false;
		foreach($this->events['evt'][$regex] as $id => $info)
			if($info['m'] == $mod && $info['f'] == $meth) return $id;
		return false;
	}
	
	public function hookBDS ($mod, $meth, $path) {
		$regex = $this->regexify($path);
		if(!array_key_exists($regex, $this->events['BDS'])) $this->events['BDS'][$regex] = array();
		$this->events['BDS'][$regex][] = array(
			'm' => $mod,
			'f' => $meth,
		);
		return true;
	}
	
	public function hookOnceBDS($mod, $meth, $path) {
		$that = $this;

		$cb = function($parts, $from, $message) use (&$cb, $mod, $meth, $path, $that) {
			if($that->core->mod[$mod]->status == true){
				if (is_callable($meth)) {
					// it's a callback function
					$meth($parts, $from, $message);
				} else {
					$that->core->mod[$mod]->$meth($parts, $from, $message);
				}
			}
			$that->unhook($mod, $cb, $path);
		};
		$this->hookBDS($mod, $cb, $path);
		return true;
	}

	public function unhookBDS ($mod, $meth, $path) {
		$regex = $this->regexify($path);
		$hook = $this->is_hookedBDS($mod, $meth, $regex);
		if($hook === false) return true;
		array_splice($this->events['BDS'][$regex], $hook, 1);
		if(empty($this->events['BDS'][$regex])) unset($this->events['BDS'][$regex]);
		return true;
	}

	public function add_command($mod, $cmd, $meth, $p = 25, $s = true) {
		if(array_key_exists(strtolower($cmd), $this->events['cmd'])) return 'command in use';
		$this->events['cmd'][strtolower($cmd)] = array(
			'm' => $mod,
			's' => $s,
			'p' => $p,
			'e' => $meth,
			'h' => Null,
		);
		return 'added';
	}
	
	public function cmdHelp($mod, $cmd, $helpStr) {
		if(!array_key_exists(strtolower($cmd), $this->events['cmd'])) return;
		if($this->events['cmd'][strtolower($cmd)]['m'] == $mod)
			$this->events['cmd'][strtolower($cmd)]['h'] = $helpStr;
	}
	
	public function delCmd($mod, $cmd) {
		if(!array_key_exists(strtolower($cmd), $this->events['cmd'])) return;
		if($this->events['cmd'][strtolower($cmd)]['m'] == $mod)
			unset($this->events['cmd'][strtolower($cmd)]);
	}
	
	public function switchCmd($cmd, $s = true) {
		if(is_string($s)) $s = ($s === 'on' ? true : false);
		if(!array_key_exists(strtolower($cmd), $this->events['cmd'])) return 'no such command';
		if($this->core->mod[$this->events['cmd'][strtolower($cmd)]['m']]->status === false) return false;
		if($this->events['cmd'][strtolower($cmd)]['s'] === $s) return true;
		if($this->core->mod[$this->events['cmd'][strtolower($cmd)]['m']]->type !== EXT_CUSTOM) return false;
		$this->events['cmd'][strtolower($cmd)]['s'] = ($s===true ? true : false);
		return true;
	}
}

?>
