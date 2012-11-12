<?php

	/*
	*	Welcome module version 1!!!
	*	Made for Contra v3.
	*	Created by photofroggy.
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*	The last time I made a welcome module
	*	was quite a while ago, and it sucked, hard.
	*	So, forgive me if this isn't exactly on par
	*	with what you know :P
	*/

class Welcome extends extension {
	public $name = 'Welcome';
	public $version = 1;
	public $about = 'This is a bot welcome module for Contra!';
	public $status = true;
	public $author = 'photofroggy';

	private $welcome = array();

	function init() {
		$this->addCmd('welcome', 'c_welcome');
		$this->addCmd('wt', 'c_wt', 75);

		$this->cmdHelp(
			'welcome',
			'Set your own welcome message! Only if you\'re allowed to, though. Welcome messages can contain the following codes:'
				.'<br/><sup><b>{from}</b> - This is replaced with the username of the person who joined.<br/>'
				.'<b>{channel}</b> - This is replaced with the channel the user just joined.<br/>'
				.'<b>{ns}</b> - This is replaced with the raw namespace of the channel the user just joined.</sup><br/>'
				.'Typing \''.$this->Bot->trigger.'welcome off\' will delete your welcome message!'
		);
		$this->cmdHelp('wt', 'Manage your welcome message settings!');

		$this->load_data();
	}

	function c_welcome($ns, $from, $message, $target) {
		$channel = strtolower($target);
		if(!isset($this->welcome[$channel])) return $this->dAmn->say($ns, $from.': Welcomes are not being used in '.$this->dAmn->deform_chat($target, $this->Bot->username).'.');
		$welcomes = $this->welcome[$channel];
		if($welcomes['type'] != 'indv')
			return $this->dAmn->say($ns, $from.': You don\'t have the ability to set a welcome message in '.$this->dAmn->deform_chat($target, $this->Bot->username).'.');
		if(!$welcomes['switch'])
			return $this->dAmn->say($ns, $from.': Welcomes are currently deactivated in '.$this->dAmn->deform_chat($target, $this->Bot->username).'.');
		$say = $from.': ';
		$welcome_msg = args($message, 1, true);
		$welcome_arg = args($message, 1);
		$secp = args($message, 2, true);
		if(strtolower($welcome_arg) == 'off' && empty($secp)) {
			if(isset($this->welcome[$channel]['users'][$from])) {
				unset($this->welcome[$channel]['users'][$from]);
				$say.= 'Welcome message deleted!';
			} else $say.= 'You didn\'t have a welcome message stored anyway.';
		} else {
			if(empty($welcome_msg)) {
				$say.= 'You need to give a welcome message to be set.';
			} else {
				$this->welcome[$channel]['users'][$from] = $welcome_msg;
				$say.= 'Welcome set! Your welcome message is as follows:<br/>'.$welcome_msg;
			}
		}
		$this->save_data();
		$this->dAmn->say($ns, $say);
	}

	function c_wt($ns, $from, $message, $target) {
		$subcom = strtolower(args($message, 1));
		$com1 = args($message, 2);
		$com2 = args($message, 3);
		$com1s = args($message, 2, true);
		$com2s = args($message, 3, true);
		$com3s = args($message, 4, true);
		$channel = strtolower($target);
		$say = $from.': ';
		switch($subcom) {
			case 'all':
				if($channel == "chat:botdom") {
					$say.='Welcomes are not allowed in #Botdom.';
					break;
				}
				if(empty($com1s)) {
					$say.= 'You need to give a welcome message to set.';
					break;
				}
				$this->welcome[$channel] = array(
					'type' => 'all',
					'msg' => $com1s,
					'switch' => (empty($this->welcome[$channel]) ? true : $this->welcome[$channel]['switch']),
				);
				$say.= 'Welcome message for '.$this->dAmn->deform_chat($target, $this->Bot->username);
				$say.= ' set to "'.$com1s.'"';
				break;
			case 'pc':
				if($channel == "chat:botdom") {
					$say.='Welcomes are not allowed in #Botdom.';
					break;
				}
				if(empty($com1)) {
					$say.= 'You need to give a privclass name.';
				} else {
					if(array_search($com1, $this->dAmn->chat[$channel]['pc']) === false) {
						$say.= $com1.' is not a valid privclass in '.$this->dAmn->deform_chat($target, $this->Bot->username).'.';
					} else {
						if(!isset($this->welcome[$channel]))
							$this->welcome[$channel] = array(
								'type' => 'pc',
								'pc' => array(),
								'switch' => true
							);
						if($this->welcome[$channel]['type'] != 'pc')
							$this->welcome[$channel] = array(
								'type' => 'pc',
								'pc' => array(),
								'switch' => $this->welcome[$channel]['switch']
							);
						if(strtolower($com2) == 'off' && empty($com3s)) {
							if(isset($this->welcome[$channel]['pc'][$com1])) {
								$say.= 'Welcome for '.$com1.' members has been deleted.';
								unset($this->welcome[$channel]['pc'][$com1]);
							} else {
								$say.= 'No welcome message was stored for '.$com1.'.';
							}
						} else {
							$this->welcome[$channel]['pc'][$com1] = $com2s;
							$say.= 'Welcome message set.';
						}
					}
				}
				break;
			case 'indv':
				if($channel == "chat:botdom") {
					$say.='Welcomes are not allowed in #Botdom.';
					break;
				}
				$this->welcome[$channel] = array(
					'type' => 'indv',
					'users' => array(),
					'switch' => (empty($this->welcome[$channel]) ? true : $this->welcome[$channel]['switch']),
				);
				$say.= 'Welcomes set to individual.';
				break;
			case 'on':
			case 'off':
				if($channel == "chat:botdom") {
					$say.='Welcomes are not allowed in #Botdom.';
					break;
				}
				if(!isset($this->welcome[$channel])) {
					$say.= 'There are no welcome settings for '.$this->dAmn->deform_chat($target, $this->Bot->username).'.';
					break;
				}
				$sw = $subcom == 'on' ? true : false;
				if($this->welcome[$channel]['switch'] === $sw) {
					$say.= 'Welcomes in '.$this->dAmn->deform_chat($target, $this->Bot->username).' are already '.$subcom.'.';
					break;
				}
				$this->welcome[$channel]['switch'] = $sw;
				$say.= 'Welcomes in '.$this->dAmn->deform_chat($target, $this->Bot->username).' have been turned '.$subcom.'.';
				break;
			case 'clear':
				if(empty($com1)) {
					$say.= 'This will delete all welcome data! Type "<code>';
					$say.= str_replace('&', '&amp;', $this->Bot->trigger).'wt '.$subcom.' yes</code>"';
					$say.= ' to clear all welcome data.';
				} elseif(strtolower($com1) == 'yes') {
					unset($this->welcome[$channel]);
					$say.= 'Welcome data for '.$this->dAmn->deform_chat($target, $this->Bot->username).' has been deleted!';
				}
				break;
			case 'settings':
				if(!isset($this->welcome[$channel])) {
					$say.= 'Welcomes are not being used in '.$this->dAmn->deform_chat($target, $this->Bot->username).'.';
					break;
				}
				$say.= 'Welcomes set to '.$this->welcome[$channel]['type'].' in ';
				$say.= $this->dAmn->deform_chat($target, $this->Bot->username).'.';
				$say.= ' Welcomes are currently '.($this->welcome[$channel]['switch'] ? 'on' : 'off').'.';
				break;
			default:
				$trig = str_replace('&', '&amp;', $this->Bot->trigger);
				$say.= 'wt has the following commands:<br/><sup>';
				$say.= '<b>'.$trig.'wt [channel] all (message)</b> - Set the welcome message used to greet all users.<br/>';
				$say.= '<b>'.$trig.'wt [channel] pc (pc) (message)</b> - Set the welcome message used to greet users in privclass (pc).<br/>';
				$say.= '<b>'.$trig.'wt [channel] indv</b> - Allow users to set their own welcome messages.<br/>';
				$say.= '<b>'.$trig.'wt [channel] (on/off)</b> - Turn welcome messages on or off.<br/>';
				$say.= '<b>'.$trig.'wt [channel] settings</b> - View the current settings for welcome messages.<br/></sup>';
				$say.= '<i>Optional parameter "channel" always defaults to the channel you are in.</i>';
				break;
		}
		$this->save_data();
		$this->dAmn->say($ns, $say);
	}

	function e_welcome($ns, $from, $info) {
		if(!isset($this->welcome[strtolower($ns)])) return;
		if(!$this->welcome[strtolower($ns)]['switch']) return;
		switch($this->welcome[strtolower($ns)]['type']) {
			case 'pc':
				$info = parse_dAmn_packet($info);
				$pc = $info['args']['pc'];
				unset($info);
				if(!isset($this->welcome[strtolower($ns)]['pc'][$pc])) return;
				$this->send_welcome($ns, $from, $this->welcome[strtolower($ns)]['pc'][$pc]);
				break;
			case 'indv':
				if(!isset($this->welcome[strtolower($ns)]['users'][$from])) return;
				$this->send_welcome($ns, $from, $this->welcome[strtolower($ns)]['users'][$from]);
				break;
			case 'all':
				$this->send_welcome($ns, $from, $this->welcome[strtolower($ns)]['msg']);
				break;
		}
	}

	function send_welcome($ns, $from, $msg) {
                if($ns == 'chat:Botdom') return;
		if($this->dAmn->chat[$ns]['member'][$from]['con'] > 1) return;
		$msg = str_replace('{channel}', $this->dAmn->deform_chat($ns, $this->Bot->username), $msg);
		$msg = str_replace('{ns}', $ns, $msg);
		$msg = str_replace('{from}', $from, $msg);
		$msg = str_replace($this->Bot->trigger, '', $msg);
		$this->dAmn->say($ns, $msg);
	}

	function load_data() {
		$this->welcome = $this->Read('wdata', 2);
		$this->welcome = $this->welcome === false ? array() : $this->welcome;
		$this->welcome = array_change_key_case($this->welcome, CASE_LOWER);
		$this->save_data();
		if(!empty($this->welcome)) $this->hook('e_welcome', 'recv_join');
		else $this->unhook('e_welcome', 'recv_join');
	}

	function save_data() {
		if(empty($this->welcome)) $this->Unlink('wdata');
		else $this->Write('wdata', $this->welcome, 2);
		if(!empty($this->welcome)) $this->hook('e_welcome', 'recv_join');
		else $this->unhook('e_welcome', 'recv_join');
	}
}

new Welcome($core);

?>