<?php

	/*
	*	System commands. Version 3.
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*	This module provides a set of
	*	commands which can be used
	*	to maintain your bot. It also
	*	provides information about the
	*	bot and the system the bot is
	*	running on.
	*/

class System_commands extends extension {
	public $name = 'System';
	public $version = 3;
	public $about = 'System commands.';
	public $status = true;
	public $author = 'photofroggy';
	public $type = EXT_SYSTEM;

	protected $trigc1;
	protected $trigc2;
	protected $trigc3;
	protected $trigc4;
	protected $switches = array();

	function init() {
		$e = array('e', 'eval');
		$cmds = array('commands', 'cmds');
		$cmd = array('command', 'cmd');
		$mods = array('modules', 'mods');
		$mod = array('module', 'mod');
		$aj = array('autojoin', 'aj');

		$this->addCmd('about', 'c_about');
		$this->addCmd('system', 'c_about');
		$this->addCmd('uptime', 'c_about');
		$this->addCmd($cmds, 'c_commands');
		$this->addCmd($cmd, 'c_command', 99);
		$this->addCmd($mods, 'c_modules');
		$this->addCmd($mod, 'c_module', 99);
		$this->addCmd('users', 'c_users');
		$this->addCmd('user', 'c_user', 75);
		$this->addCmd($aj, 'c_autojoin', 99);
		$this->addCmd('ctrig', 'c_trigger', 100);
		$this->addCmd($e, 'c_eval',100);
		$this->addCmd('restart', 'c_restart', 99);
		$this->addCmd('quit', 'c_quit', 99);
		$this->addCmd('credits', 'c_credits');

		$this->addCmd('sudo', 'c_sudo', 100); // Lololololololololol.

		$this->cmdHelp('about', 'Displays information about the bot.');
		$this->cmdHelp('system', 'Displays information about the system.');
		$this->cmdHelp('uptime', 'Shows how long the bot has been running for.');
		$this->cmdHelp($cmds, 'Displays the commands available to you.');
		$this->cmdHelp($cmd, 'Manage and view your commands.');
		$this->cmdHelp($mods, 'Displays the loaded modules.');
		$this->cmdHelp($mod, 'Manage and view your modules.');
		$this->cmdHelp($aj, 'Manage and view the bot\'s autojoin channels.');
		$this->cmdHelp('ctrig', 'Change your bot\'s trigger character.');
		$this->cmdHelp($e, 'Executes given php code! Use at your own risk!');
		$this->cmdHelp('users', 'Displays the users of the bot.');
		$this->cmdHelp('user', 'Manage and view your bot\'s user list.');
		$this->cmdHelp('restart', 'Restarts the bot.');
		$this->cmdHelp('quit', 'Shuts down the bot.');
		$this->cmdHelp('credits', 'Here lies the persons whom helped in the creation of Contra.');

		$this->cmdHelp(
			'sudo',
			'Idea stolen from i<b></b>nfinity0. '
				.$this->Bot->trigger.'sudo [user] [command] [params]'
		);

		$this->hook('e_trigcheck', 'recv_msg');
		$this->hook('bdsmain', 'recv_msg');
		$this->hook('codsmain', 'recv_msg');
		$this->hook('load_switches', 'startup');

		$this->trigc1 = strtolower($this->Bot->username.': trigcheck');
		$this->trigc2 = strtolower($this->Bot->username.': trigger');
		$this->trigc3 = strtolower($this->Bot->username.', trigcheck');
		$this->trigc4 = strtolower($this->Bot->username.', trigger');
	}

	function c_about($ns, $from, $message, $target) {
		$part = args($message, 1);
		$cmd = args($message, 0);
		if(strtolower($cmd)!='about') $part = $cmd;
		switch(strtolower($part)) {
			case 'system': $about = '/npmsg '.$from.': Running PHP '.PHP_VERSION.' on '.$this->Bot->sysString.'.';
				break;
			case 'uptime': $about = '<abbr title="'.$from.'"></abbr>Uptime: '.time_length(time()-$this->Bot->start).'.';
				break;
                        case 'pcuptime': $about = '<abbr title="'.$from.'"></abbr>'.`uptime`;
                                break;
			case 'about':
			case '':
			default:
				$about = str_replace('%N%', $this->Bot->info['name'], '<abbr title="'.$from.'"></abbr>'.$this->Bot->aboutStr);
				$about = str_replace('%V%', $this->Bot->info['version'], $about);
				$about = str_replace('%S%', $this->Bot->info['status'], $about);
				$about = str_replace('%R%', $this->Bot->info['release'], $about);
				$about = str_replace('%O%', $this->Bot->owner, $about);
				$about = str_replace('%A%', $this->Bot->info['author'], $about);
				$about = str_replace('%D%', (DEBUG===true?'Running in debug mode.':''), $about);
				break;
		}
		$this->dAmn->say($target, $about);
	}

	function c_commands($ns, $from, $message, $target) { $this->c_command($ns, $from, 'command list '.args($message, 1, true), $target); }
	function c_command($ns, $from, $message, $target) {
		$subby = strtolower(args($message, 1));
		switch($subby) {
			case 'ban':
			case 'allow':
			case 'reset':
				$func = (strtolower(args($message,1)) == 'allow' ? 'add' : (strtolower(args($message,1)) == 'ban' ? 'ban' : 'rem')).'Cmd';
				$cmd = strtolower(args($message, 2));
				$user = strtolower(args($message, 3));
				$user = strlen($user)>=1?$user:$from;
				$say = $from.': ';
				if(strlen($cmd)>=1) {
					if($this->user->$func($user,$cmd)) {
						if($func == 'addCmd') $say.= $user.' has been given access to '.$cmd.'.';
						if($func == 'banCmd') $say.= $user.' has been disallowed access to '.$cmd.'.';
						if($func == 'remCmd') $say.= $user.'\'s access to '.$cmd.' has been reset.';
					} else $say.= 'Could not edit '.$user.'\'s access to '.$cmd.'.';
				} else $say.= 'Use this command to edit a users access to a command.';
				break;
			case 'on':
			case 'off':
				$s = (strtolower(args($message, 1)) == 'on' ? true : false);
				$cmd = args($message, 2);
				$r = $this->switchCmd($cmd,$s);
				if($r===true) {
					$say = $from.': command '.$cmd.' switched '.($s == true ? 'on' : 'off').'.';
					if(!isset($this->switches['cmds'])) $this->switches['cmds'] = array();
					if(!isset($this->switches['cmds'][$cmd]))
						$this->switches['cmds'][$cmd]['orig'] = ($s === true ? false : true);
					$this->switches['cmds'][$cmd]['cur'] = $s;
					if($this->switches['cmds'][$cmd]['cur'] === $this->switches['cmds'][$cmd]['orig'])
						unset($this->switches['cmds'][$cmd]);
					if(empty($this->switches['cmds'])) unset($this->switches['cmds']);
					$this->save_switches();
				} elseif($r===false) $say = $from.': Could not turn '.$cmd.' '.($s==true?'on':'off').'.';
				break;
			case 'switches':
				if(!isset($this->switches['cmds']))
					return $this->dAmn->say($ns, $from.': No switches for commands are currently stored.');
				switch(strtolower(args($message, 2))) {
					case 'reset':
						foreach($this->switches['cmds'] as $cmd => $data)
							$this->switchCmd($cmd, $data['orig']);
						unset($this->switches['cmds']);
						$this->save_switches();
						return $this->dAmn->say($ns, $from.': Command switches have been reset!');
						break;
					default:
						$say = '<abbr title="'.$from.'"></abbr><b>There are switches stored for the following commands:</b>';
						$cmds = '<br/><sub>- ';
						foreach($this->switches['cmds'] as $cmd => $data)
							$cmds.= $cmd.', ';
						$say.= rtrim($cmds, ', ').'</sub><br/>To get rid of all switches, type "<code>';
						$say.= str_replace('&', '&amp;', $this->Bot->trigger).args($message, 0).' switches reset</code>".';
						break;
				}
				break;
			case 'list':
			default:
				$all = ($subby == 'list' ? strtolower(args($message, 2)) : $subby) == 'all' ? true : false;
				$say = '<abbr title="'.$from.'"></abbr><b>'.($all ? 'All' : 'Available').' commands:</b><sub>';
				foreach($this->user->list['pc'] as $num => $name) {
					$modline = '<br/>&nbsp;-<b> '.$name.':</b> ';
					$cmds = '';
					foreach($this->Bot->Events->events['cmd'] as $cmd => $cmda) {
						if($cmda['p'] == $num) {
							if($this->user->hasCmd($from,$cmd) || $all) {
								if($cmd != 'mod' && $cmd != 'mods' && $cmd != 'aj' && $cmd != 'e' && $cmd != 'cmd' && $cmd != 'cmds') {
									$off = (($cmda['s']===false||$this->Bot->mod[$cmda['m']]->status===false)?true:false);
									$cmds.= '<abbr title="Privs: '.$cmda['p'].';">'.($off?'<i><code>':'').$cmd.
									($off?'</code></i>':'').'</abbr>, ';
								}
							}
						}
					}
					if(!empty($cmds)) $say.= $modline.rtrim($cmds, ', ');
				}
				$say.= '</sub><br/>Italic commands are off.';
				break;
		}
		$this->dAmn->say($target, $say);
	}
	function c_modules($ns, $from, $message, $target) { $this->c_module($ns, $from, 'module list', $target); }
	function c_module($ns, $from, $message, $target) {
		switch(strtolower(args($message, 1))) {
			case 'on':
			case 'off':
				$s = (strtolower(args($message, 1))=='on'?true:false);
				$st = ($s==true?'on':'off');
				$ext = strtolower(args($message,2,true));
				if(strlen($ext) >= 1) { $exn=false;
					foreach($this->Bot->mod as $ex => $i) {
						if(strtolower($i->name)==$ext)
							$exn = $i->name; $exi = $i;
					}
					if(!$exn) $say = $from.': No such module.';
					else {
						if($this->Bot->mod[$exn]->type === EXT_LIBRARY)
							return $this->dAmn->say($ns, $from.': No such module.');
						if($this->Bot->mod[$exn]->type === EXT_SYSTEM)
							return $this->dAmn->say($ns, $from.': System modules can\'t be turned off!');
						if($this->Bot->mod[$exn]->status == ($s === true?false:true)) {
							$this->Bot->mod[$exn]->status = $s;
							if(!isset($this->switches['mods'])) $this->switches['mods'] = array();
							if(!isset($this->switches['mods'][$exn]))
								$this->switches['mods'][$exn]['orig'] = ($s === true ? false : true);
							$this->switches['mods'][$exn]['cur'] = $s;
							if($this->switches['mods'][$exn]['cur'] === $this->switches['mods'][$exn]['orig'])
								unset($this->switches['mods'][$exn]);
							if(empty($this->switches['mods'])) unset($this->switches['mods']);
							$this->save_switches();
							$say = $from.': Module '.$exn.' has been turned '.$st.'!';
						} else $say = $from.': Module '.$exn.' was already '.$st.'.';
					}
				} else $say = $from.': No such module.';
				break;
			case 'switches':
				if(!isset($this->switches['mods']))
					return $this->dAmn->say($ns, $from.': No switches for modules are currently stored.');
				switch(strtolower(args($message, 2))) {
					case 'reset':
						foreach($this->switches['mods'] as $mod => $data)
							$this->Bot->mod[$mod]->status = $data['orig'];
						unset($this->switches['mods']);
						$this->save_switches();
						return $this->dAmn->say($ns, $from.': Module switches have been reset!');
						break;
					default:
						$say = '<abbr title="'.$from.'"></abbr><b>There are switches stored for the following modules:</b>';
						$mods = '<br/><sub>';
						foreach($this->switches['mods'] as $mod => $data)
							$mods.= $mod.', ';
						$say.= rtrim($mods, ', ').'</sub><br/>To get rid of all switches, type "<code>';
						$say.= str_replace('&', '&amp;', $this->Bot->trigger).args($message, 0).' switches reset</code>".';
						break;
				}
				break;
			case 'info':
				$ext = args($message, 2,true);
				if(strlen($ext) >= 1) {
					foreach($this->Bot->mod as $mod => $info)
						if(strtolower($ext)==strtolower($info->name)&&
							$info->type!=EXT_LIBRARY)
								$exi = $info;
					if(!isset($exi)) $say = $from.': no such module.';
					else {
						$exn = $exi->name;
						if($exi->type==EXT_LIBRARY) return $this->dAmn->say($ns, $from.': No such module.');
						$head = '<abbr title="'.$from.'"></abbr><b><u>';
						$head.= $exi->type == EXT_SYSTEM ? 'System module' : 'Module';
						$head.= ' '.$exn.'</u></b><br/>';
						$head.= '<b>Status: </b>'.($exi->status?'On':'Off').'<br/>'.
						($exi->about!==Null?'<b>About: </b>'.$exi->about.'<br/>':'').(
						$exi->version!==Null?'<b>Version:</b> '.$exi->version.'<br/>':'')
						.'<b>Author:</b> '.$exi->author;
						$cmds = '';
						foreach($this->Bot->Events->events['cmd'] as $cmd => $cmda) {
							if($cmda['m']==$exi->name) {
								if(!$cmda['s']) $cmds.= '<b>';
								$cmds.= '<abbr title="Privs: '.$cmda['p'].';">';
								$cmds.= ''.$cmd.'</abbr>, ';
								if(!$cmda['s']) $cmds.= '</b>';
							}
						}
						$cmds = empty($cmds) ? '' : '<br/><b>Commands:</b><br/><sub>- '.rtrim($cmds,', ').'</sub>';
						$evts = '';
						foreach($this->Bot->Events->events['evt'] as $evt => $evta) {
							$tagging = '<abbr title="%meths%">'.$evt.'</abbr>, '; $captcha = '';
							foreach($evta as $id => $data)
								if($data['m'] == $exi->name) $captcha.= $data['f'].'; ';
							$evts.= empty($captcha) ? '' : str_replace('%meths%', rtrim($captcha, '; '), $tagging);
						}
						if(!empty($evts)) $evts = '<br/><b>Active events:</b><br/><sub>- '.rtrim($evts,', ').'</sub>';
						$say = $head.$cmds.$evts;
					}
				} else $say = $from.': Use this command to view information on a '.$ty.'.';
				break;
			case 'list':
			default:
				$say = '<abbr title="'.$from.'"></abbr>Loaded Modules:<br/>';$mods ='';
				foreach($this->Bot->mod as $module => $info) {
					if($info->type != EXT_LIBRARY) {
						if(!$info->status) $mods.= '<i><code>';
						$mods.= $info->name;
						if(!$info->status) $mods.= '</code></i>';
						$mods.= ', ';
					}
				}
				$say.= rtrim($mods, ', ').'<br/><sup>Italic '.($ty=='library'?'libraries':'modules').' are deactivated.</sup>';
				break;
		}
		$this->dAmn->say($target, $say);
	}

	function c_users($ns, $from, $message, $target) { $this->c_user($ns, $from, 'user list', $target); }
	function c_user($ns, $from, $message, $target) {
		$act = strtolower(args($message, 1));
		$usrx = args($message,2);
		$priv = args($message,3);

		switch($act) {
			case 'add':
                                if($usrx == $this->Bot->username) return $this->dAmn->say($ns, $from.': Failed to add '.$usrx.' to user list.');
				$r = $this->user->add($usrx,$priv);
				$t = $this->user->class_name($priv);
				if($r=='added') $say = $from.': Added '.$usrx.' to privilege class '.$t.'.';
				else $say = $from.': Failed to add '.$usrx.' to privilege class '.$priv.' ('.$r.')';
				break;
			case 'rem':
			case 'remove':
				$r = $this->user->rem($usrx);
				if($r=='removed') $say = $from.': Removed '.$usrx.' from the user list.';
				else $say = $from.': Failed to remove '.$usrx.' from the user list ('.$r.')';
				break;
			case 'class':
				$suba = strtolower(args($message, 2));
				$p1 = args($message,3);$p2 = args($message,4);
				switch($suba) {
					case 'add':
						if($p1==''||$p2=='') $say = $from.': Usage: '.$this->Bot->trigger.'user class add [privclass] [order]';
						else {
							if($this->user->add_class($p1,$p2)===true) $say = $from.': Added user class '.$p1.' with order='.$p2.'.';
							else $say = $from.': Failed to add user class '.$p1.' with order '.$p2.'.';
						}
						break;
					case 'rem':
					case 'remove':
						if($p1=='') $say = $from.': Usage: '.$this->Bot->trigger.'user '.$suba.' [class]';
						else {
							if($this->user->rem_class($p1)===true) $say = $from.': Removed user class '.$p1.'.';
							else $say = $from.': Failed to remove user class '.$p1.'.';
						}
						break;
					case 'rename':
						if($p1==''||$p2=='') $say = $from.': Usage: '.$this->Bot->trigger.'user class rename [name] [new_name]';
						else {
							if($this->user->rename_class($p1,$p2)===true) $say = $from.': Renamed user class '.$p1.' to '.$p2.'.';
							else $say = $from.': Failed renaming user class '.$p1.' to '.$p2.'.';
						}
						break;
					default: $say = $from.': Use this command to add, remove and rename access levels for your bot.';
						break;
				}
				break;
			case 'classes':
				$say = '<abbr title="'.$from.'"></abbr><b>I have the following privclasses loaded</b><br/><sub>';
				foreach($this->user->list['pc'] as $ord => $name) $say.= $name.'('.$ord.') &middot; ';
				$say = substr($say,0,-10).'</sub>';
				break;
			case 'list':
			default:
				$say = '<abbr title="'.$from.'"></abbr><b><u>Users</u></b>';
				foreach($this->user->list as $order => $usrs) {
					if(!empty($usrs)) {
						if(is_numeric($order)) { $users = '';
							$say.= '<br/><sub><b>'.$this->user->list['pc'][$order].'</b><code>('.$order.')</code><br/>';
							foreach($usrs as $id => $user)
								$users.= substr($user, 0, 1).'<b></b>'.substr($user, 1).', ';
							$say.= '-> '.rtrim($users,', ').'</sub>';
						}
					}
				}
				break;
		}
		$this->dAmn->say($target, $say);
	}

	function bdsmain($ns, $from, $message) {
		if($ns == 'chat:DataShare' && substr($message, 0, 4) == 'BDS:') {
			$command = explode(':', $message, 4);
			switch($command[1]) {
				case 'BOTCHECK':
				switch($command[2]) {
					case 'ALL':
					$this->dAmn->npmsg('chat:datashare', 'BDS:BOTCHECK:RESPONSE:'.$from.','.$this->Bot->owner.','.$this->Bot->info['name'].','.$this->Bot->info['version'].'/'.$this->Bot->info['bdsversion'].','.md5(strtolower(trim(str_replace(' ', '', $this->Bot->trigger)).$from.$this->Bot->username)).','.$this->Bot->trigger, TRUE);
					break;
					case 'DIRECT':
					if(stristr($command[3], $this->Bot->username))
						$this->dAmn->npmsg('chat:datashare', 'BDS:BOTCHECK:RESPONSE:'.$from.','.$this->Bot->owner.','.$this->Bot->info['name'].','.$this->Bot->info['version'].'/'.$this->Bot->info['bdsversion'].','.md5(strtolower(trim(str_replace(' ', '', $this->Bot->trigger)).$from.$this->Bot->username)).','.$this->Bot->trigger, TRUE);
					break;
				}
			}
		}
	}

	function c_autojoin($ns, $from, $message, $target) {
		$act = strtolower(args($message, 1));
		switch($act) {
			case 'add':
				$ans = args($message,2);
				if($ans=='') $ans = $target;
				$ansid = false;
				foreach($this->Bot->autojoin as $id => $channel)
					if(strtolower($this->dAmn->deform_chat($channel,$this->Bot->username)) == strtolower($this->dAmn->deform_chat($ans,$this->Bot->username)))
						$ansid = $id;
				if($ansid!==false) $say = $from.': '.$ans.' is already on your autojoin list.';
				else {
					$this->Bot->autojoin[] = $ans;
					$say = $from.': Added '.$ans.' to your autojoin list.';
				}
				$this->Bot->save_config();
				break;
			case 'rem':
			case 'remove':
				$rns = args($message,2);
				if($rns=='') $rns = $target;
				$rnsid = false;
				foreach($this->Bot->autojoin as $id => $channel) {
					if(strtolower($this->dAmn->deform_chat($channel,$this->Bot->username))
					==strtolower($this->dAmn->deform_chat($rns,$this->Bot->username)))
						$rnsid = $id;
				}
				if($rnsid===false) $say = $from.': '.$rns.' is not on your autojoin list.';
				else {
					$this->Bot->autojoin = array_del_key($this->Bot->autojoin, $rnsid);
					$say = $from.': Removed '.$rns.' from your autojoin list.';
				}
				$this->Bot->save_config();
				break;
			case 'list':
			default:
				$say = $from.', the following channels are on my autojoin list:<br/><sub>';
				foreach($this->Bot->autojoin as $id => $channel)
					$say.= $channel.' &middot; ';
				$say = substr($say,0,-10).'</sub>';
				break;
		}
		$this->dAmn->say($target, $say);
	}

	function c_trigger($ns, $from, $message, $target) {
		$trig = args($message,1);
		$conf = strtolower(args($message,2));
		if($trig!='') {
			if($conf!=''&&$conf=='yes') {
				$this->Bot->trigger = $trig;
				$this->Bot->save_config();
				$say = $from.': Trigger changed to <code>'.$trig.'</code>!';
			} else $say = $from.': Are you sure you want to change your trigger? (Type '.$this->Bot->trigger.'ctrig '.$trig.' yes)';
		} else $say = $from.': Use this command to change your trigger.';
		$this->dAmn->say($target, $say);
	}

	function c_eval($ns, $from, $message, $target) {
                if(strtolower($from) != strtolower($this->Bot->owner))
                        return $this->dAmn->say($ns, $from.': Sorry, only the actual owner can mess with the eval command.');
                if (preg_match('/\b(escapeshellarg|escapeshellcmd|exec|passthru|proc_close|proc_get_status|proc_nice|proc_open|proc_terminate|shell_exec|system|rm|mv|cp|shutdown|kill|killall|cat|ls|dir)\b/i',args($message, 1, true)))
                        return $this->dAmn->say($ns, $from.': Sorry, the eval command contains a function that have been disabled.');
		$code = args($message, 1, true);
		$e = eval($code);
		if(!empty($e) && $e !== false)
			return $this->dAmn->say($ns, 'Code returned:<bcode>'.var_export($e,true));
		if($e === false)
			return $this->dAmn->say($ns, 'Code returned false! Make sure your input is correct!');
		$this->dAmn->say($ns, 'Code executed.');
	}

	function c_restart($ns, $from, $message, $target) {
		if(RESTARTABLE) {
			file_put_contents('./storage/bat/restart.bcd', 'lolwot?');
			$this->dAmn->say($target, $from.': Bot restarting. (uptime: '.time_length(time()-$this->Bot->start).')');
			$this->Bot->shutdownStr[0] = 'Bot restarting on request by '.$from.'!';
			$this->Bot->shutdownStr[1] = 'Reloading in a second.';
			$this->dAmn->close = true;
			$this->dAmn->disconnect();
		} else $this->dAmn->say($target, $from.': The bot cannot be restarted when run from the main menu!');
	}
	function c_quit($ns, $from, $message, $target) {
		if(strtolower(args($message, 1))=='hard')
			file_put_contents('./storage/bat/quit.bcd', 'lolwot?');
		$this->dAmn->say($target, $from.': Bot shutting down. (uptime: '.time_length(time()-$this->Bot->start).')');
		$this->Bot->shutdownStr[0] = 'Bot has quit on request by '.$from.'!';
		$this->dAmn->close=true;
		$this->dAmn->disconnect();
	}
	function e_trigcheck($ns, $from, $message) {
		$checks=strtolower($message);
		if($checks == $this->trigc1||$checks==$this->trigc2
		|| $checks == $this->trigc3||$checks==$this->trigc4) $this->dAmn->say($ns, $from.': My trigger is <code>'.str_replace('&','&amp;',$this->Bot->trigger).'</code>');
	}

	function c_credits($ns,$from,$message,$target) {
		/*
		*		Please DO NOT edit this method in any way, shape,
		*		or form. This command was created to pay respect
		*		to those who gave me help when creating this bot.
		*		Changing the message here would be disrespectful.
		*/

		$say = '<b>Credits</b><br/>';
		$say.= 'The following people have been a great help in the creation of Contra:<br/><sub>';
		$say.= ' - :develectricnet: - Tested the bot on a Unix system and has given valuable feedback.<br/>';
		$say.= ' - :devhakumiogin: - Provided inspiration for a much more flexible events system.<br/>';
		$say.= ' - :devManjyomeThunder: - Provides inspiration for ideas, directly and indirectly.<br/>';
		$say.= ' - :devinfinity0: - Provided help on using streams rather than sockets, and provided code for .sh files.<br/>';
		$say.= ' - :devplaguethenet: - Giving advice on how to do certain things, and providing ideas.<br/>';
		$say.= ' - :devDeathShadow--666: - Continuing to maintain Contra after :devphotofroggy: discontinued it.<br/>';
		$say.= ' - Users of #Botdom - They manage to put up with my testing (sometimes).<br/>';
		$say.= '</sub>These people are awesome! Some are often hard to find, though =P';
		$this->dAmn->say($target, $say);
	}

	function c_sudo($ns, $from, $message, $target) {
		$who = args($message, 1);
		$what = args($message, 2);
		$msg = args($message, 2, true);
		$msg = empty($msg) ? false : $msg;
		if(empty($who))
			return $this->dAmn->say($ns, $from.': You need to determine who to execute the command as.');
		if(empty($what))
			return $this->dAmn->say($ns, $from.': You need to determine the command to execute.');
		if($what == 'eval')
			return $this->dAmn->say($ns, $from.': Eval can not be used with the "sudo" command.');
		if($who == $this->Bot->owner)
			return $this->dAmn->say($ns, $from.': Cannot execute commands as bot owner.');
		if($who == $this->Bot->username)
			return $this->dAmn->say($ns, $from.': Cannot execute commands as bot.');
		$this->Bot->Events->command($what, $ns, $who, $msg);
	}

	function codsmain($ns, $from, $message) {
		if($ns == 'chat:DataShare' && substr($message, 0, 5) == 'CODS:') {
			$command = explode(':', $message, 5);
			switch($command[1]) {
				case 'BOTCHECK':
				switch($command[2]) {
					case 'ALL':
					$this->dAmn->npmsg('chat:datashare', 'CODS:BOTCHECK:RESPONSE:'.$from.','.$this->Bot->owner.','.$this->Bot->info['name'].','.$this->Bot->info['version'].'/'.$this->Bot->info['bdsversion'].','.md5(strtolower(trim(str_replace(' ', '', $this->Bot->trigger)).$from.$this->Bot->username)).','.$this->Bot->trigger, TRUE);
					break;
				}
				case 'VERSION':
				switch($command[2]) {
					case 'NOTIFY':
					$command2 = explode(',', $message, 5);
					$user = $command[3];
					$version = $command2[1];
					$released = $command2[2];
					$reason = $command2[3];
					if(stristr($user, $this->Bot->username)) {
						if(empty($user) || empty($version) || empty($released)) return;
						if($version > $this->Bot->info['version'] && $from == 'Asuos') {
							$this->Console->Alert("Contra {$version} has been released on {$released}. Get it at http://botdom.com/wiki/Contra#Latest");
							if(!empty($reason)) $this->Console->Alert($reason);
						}
					}
					break;
				}
			}
		}
	}

	function save_switches() {
		if(file_exists('./storage/mod/'.$this->name.'/switches.bsv'))
			if(empty($this->switches))
				return $this->Unlink('switches');
		$this->Write('switches', $this->switches, 2);
	}
	function load_switches() {
		$this->switches = $this->Read('switches', 2);
		$this->switches = $this->switches == false ? array() : $this->switches;
		if(empty($this->switches)) return;
		foreach($this->switches['mods'] as $mod => $s) {
			if($s['orig'] === $this->Bot->mod[$mod]->status && $s['orig'] !== $s['cur'])
				break $this->Bot->mod[$mod]->status = $s['cur'];
			unset($this->switches['mods'][$mod]);
		}
		if(empty($this->switches['mods'])) unset($this->switches['mods']);
		foreach($this->switches['cmds'] as $cmd => $s) {
			if($s['orig'] === $this->Bot->Events->events['cmd'][$cmd]['s'] && $s['orig'] !== $s['cur'])
				break $this->switchCmd($cmd, $s['cur']);
			unset($this->switches['cmds'][$cmd]);
		}
		if(empty($this->switches['cmds'])) unset($this->switches['cmds']);
		$this->save_switches();
	}
}

new System_commands($core);

?>