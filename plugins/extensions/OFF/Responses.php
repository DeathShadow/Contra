<?php

	/*
	*	Responses module version 1!!!
	*	Made for Contra v3.
	*	Created by photofroggy.
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*	Responses! This really is pointless,
	*	but people always seem to want this...
	*/

class Responses extends extension {
	public $name = 'Responses';
	public $version = 1;
	public $about = 'This is a bot response module for Contra!';
	public $status = true;
	public $author = 'photofroggy';

	private $response = array();
	private $rooms = array();
	private $banned = array(
		'chat:botdom',
		'chat:datashare',
		'chat:dsgateway'
	);

	function init() {
		$ar = array('responses', 'response', 're');
		$this->addCmd($ar, 'c_response', 75);

		$this->cmdHelp($ar, 'Use this command to manage your bot\'s responses.');

		$this->load_data();
	}

	function c_response($ns, $from, $message, $target) {
		$subcom = strtolower(args($message, 1));
		$ar = args($message, 2);
		$say = $from.': ';
		$channel = strtolower($target);
		switch ($subcom) {
			case 'add':
				if (isset($this->response[$ar])) {
					$say.= $ar.' is already stored as an autoresponse.';
					break;
				}
				$ara = explode(' | ', $message, 3);
				$ar = $ara[1];
				$re = $ara[2];
				if (empty($re)) {
					$say.= 'You need to give a response to be used!';
					break;
				}
				if (in_array(strtolower($channel), $this->banned)) {
					$say.='Responses are not allowed in #'.substr($chan, 5).'.';
					break;
				}
				$this->response[$ar] = $re;
				$say.= 'Added response "'.$re.'" for "'.$ar.'".';
				break;
			case 'rem':
			case 'remove':
				$ara = explode(' | ', $message, 2);
				$ar = $ara[1];
				if (!isset($this->response[$ar])) {
					$say.= $ar.' is not an autoresponse.';
					break;
				}
				unset($this->response[$ar]);
				$say.= 'Removed autoresponse for '.$ar.'.';
				break;
			case 'on':
				$key = array_search($channel, $this->rooms);
				if ($key !== false) {
					$say.='Responses are already enabled in '.$this->dAmn->deform_chat($target, $this->Bot->username).'.';
					break;
				}
				if (in_array(strtolower($channel), $this->banned)) {
					$say.='Responses are not allowed in #'.substr($chan, 5).'.';
					break;
				}
				$this->rooms[] = $channel;
				unset($this->rooms[$key]);
				$say.= 'Responses enabled for '.$this->dAmn->deform_chat($target, $this->Bot->username).'!';
				break;
			case 'off':
				$key = array_search($channel, $this->rooms);
				if ($key !== false) {
					$say.= 'Responses are already disabled in '.$this->dAmn->deform_chat($target, $this->Bot->username).'.';
					break;
				}
				$this->rooms[] = $channel;
				$say.= 'Responses disabled in '.$this->dAmn->deform_chat($target, $this->Bot->username).'!';
				break;
			case 'list':
				if (empty($this->response)) {
					$say.= 'There are currently no responses stored.';
					break;
				}
				$say.= 'Stored responses:<sup>';
				foreach ($this->response as $ar => $re)
					$say.= '<br/><b>'.$ar.'</b> - '.$re;
				$say.= '</sup>';
				break;
			case 'channels':
			case 'rooms':
				if (empty($this->rooms)) {
					$say.= 'Responses are not disabled in any rooms.';
					break;
				}
				$say.= 'Responses are disabled in the following rooms:<br/><sup>';
				foreach ($this->rooms as $chan)
					$say.= $this->dAmn->deform_chat($chan, $this->Bot->username).', ';
				$say = rtrim($say, ', ').'</sup>';
				break;
			case 'clear':
				$cc = args($message, 3);
				switch (strtolower($ar)) {
					case 'channels':
					case 'rooms':
						if (empty($this->rooms)) {
							$say.= 'Responses are not disabled in any rooms.';
							break;
						}
						if (empty($cc) || strtolower($cc) != 'yes') {
							$say.= 'This will re-enable responses in all rooms. Type "<code>'.$this->Bot->trigger.args($message, 0).' '.$subcom.' '.$ar.' yes</code>" to confirm.';
							break;
						}
						$this->rooms = array();
						$say.= 'Responses re-enabled for all rooms!';
						break;
					case 'responses':
						if (empty($this->response)) {
							$say.= 'There are no responses stored.';
							break;
						}
						if (empty($cc) || strtolower($cc) != 'yes') {
							$say.= 'This will delete all stored responses. Type "<code>'.$this->Bot->trigger.args($message, 0).' '.$subcom.' '.$ar.' yes</code>" to confirm.';
							break;
						}
						$this->response = array();
						$say.= 'Responses have been deleted!';
						break;
					case 'all':
						if (empty($this->response) && empty($this->rooms)) {
							$say.= 'There is no data being stored for this module.';
							break;
						}
						if (empty($cc) || strtolower($cc) != 'yes') {
							$say.= 'This will delete all data stored for this module. Type "<code>'.$this->Bot->trigger.args($message, 0).' '.$subcom.' '.$ar.' yes</code>" to confirm.';
							break;
						}
						$this->response = $this->rooms = array();
						$say.= 'All data for this module has been deleted!';
                                                break;
					default:
						$say.= $this->help_msg(args($message, 0), str_replace('&', '&amp;', $this->Bot->trigger).args($message, 0), false);
						break;
				}
				break;
			default:
				$say.= $this->help_msg(args($message, 0), str_replace('&', '&amp;', $this->Bot->trigger).args($message, 0));
				break;
		}
		$this->save_data();
		$this->dAmn->say($ns, $say);
	}

	function help_msg($cmd, $pre, $full = true) {
		$say = $cmd.' has the following commands:<br/><sup>';
		if ($full) {
			$say.= '<b>'.$pre.' add | (trigger) | (response)</b> - Adds a response for (trigger).<br/>';
			$say.= '<b>'.$pre.' rem(ove) | (trigger)</b> - Removes the response stored for (trigger).<br/>';
			$say.= '<b>'.$pre.' [channel] (on/off)</b> - Blocks and unblocks responses in [channel].<br/>';
			$say.= '<b>'.$pre.' list</b> - Shows a list of all responses stored.<br/>';
			$say.= '<b>'.$pre.' (channels/rooms)</b> - Lists the channels in which responses are blocked.<br/>';
		}
		$say.= '<b>'.$pre.' clear (channel/rooms)</b> - Unblocks blocked channels.<br/>';
		$say.= '<b>'.$pre.' clear responses</b> - Deletes all responses.<br/>';
		$say.= '<b>'.$pre.' clear all</b> - Deletes all data stored in the module.<br/></sup>';
		return $say.'<i>Optional parameter "channel" always defaults to the channel you are in.</i>';
	}

	function e_respond($ns, $from, $message) {
		if (array_search($ns, $this->rooms) !== false || in_array(strtolower($ns), $this->banned)) {
			return;
		}
		$trig = args($message, 0, true);
                if ($from != $this->Bot->username && isset($this->response[$trig])) {
			$re = $this->response[$trig];
			$this->respond($ns, $from, args($message, 1, true), $re);
		}
	}

	function respond($ns, $from, $args, $msg) {
		$msg = str_replace('{from}', $from, $msg);
		$msg = str_replace('{channel}', $this->dAmn->deform_chat($ns, $this->Bot->username), $msg);
		$msg = str_replace('{ns}', $ns, $msg);
		$msg = str_replace('{args}', $args, $msg);
		$msg = str_replace('{args|from}', (empty($args) ? $from : $args), $msg);
		$this->dAmn->say($ns, $msg);
	}

	function load_data() {
		$this->response = $this->Read('rdata', 2);
		$this->response = $this->response === false ? array() : $this->response;
		$this->rooms = $this->Read('rooms', 2);
		$this->rooms = $this->rooms === false ? array() : $this->rooms;
		$this->rooms = array_change_key_case($this->rooms, CASE_LOWER);
		if (!empty($this->response)) {
			$this->hook('e_respond', 'recv_msg');
		} else {
			$this->unhook('e_respond', 'recv_msg');
		}
	}

	function save_data() {
		if (empty($this->response)) {
			$this->Unlink('rdata');
		} else {
			$this->Write('rdata', $this->response, 2);
		}
		if (empty($this->rooms)) {
			$this->Unlink('rooms');
		} else {
			$this->Write('rooms', $this->rooms, 2);
		}
		if (!empty($this->response)) {
			$this->hook('e_respond', 'recv_msg');
		} else {
			$this->unhook('e_respond', 'recv_msg');
		}
	}
}

new Responses($core);

?>
