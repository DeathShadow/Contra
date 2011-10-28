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

	protected $botdata = array();
	protected $botBanTimers = array();
	protected $kicks = array();
	protected $validBDS = "^([A-Z0-9]+:[A-Z0-9]+:[A-Z0-9]+)|([A-Z0-9]+:[A-Z0-9]+:[A-Z0-9]+:.*?)$^";

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
		$this->addCmd('user', 'c_user', 100);
		$this->addCmd($aj, 'c_autojoin', 99);
		$this->addCmd('ctrig', 'c_trigger', 100);
		$this->addCmd($e, 'c_eval',100);
		$this->addCmd('restart', 'c_restart', 99);
		$this->addCmd('quit', 'c_quit', 99);
		$this->addCmd('credits', 'c_credits');
		$this->addCmd('botinfo', 'c_botinfo');
		$this->addCmd('update', 'c_update', 100);

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
		$this->cmdHelp('botinfo', 'Lists information on a specific bot.');
		$this->cmdHelp('update', 'Updates Contra to latest version. Only works if the bot\'s current version is below the released version');

		$this->cmdHelp(
			'sudo',
			'Idea stolen from i<b></b>nfinity0. '
				.$this->Bot->trigger.'sudo [user] [command] [params]'
		);

		$this->hook('e_trigcheck', 'recv_msg');
		$this->hook('bds_join', 'recv_join');
		$this->hook('bds_recv', 'recv_msg');
		$this->hook('e_banned', 'recv_privchg');
		$this->hook('bdsmain', 'recv_msg');
		$this->hook('codsmain', 'recv_msg');
		$this->hook('load_switches', 'startup');

		$this->load_botdata();

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
			case 'system':
				$about = '/npmsg '.$from.': Running PHP '.PHP_VERSION.' on '.$this->Bot->sysString.'.';
				break;
			case 'uptime':
				$about = '<abbr title="'.$from.'"></abbr>Uptime: '.time_length(time()-$this->Bot->start).'.';
				break;
                        case 'pcuptime':
				if(PHP_OS == 'Linux')
					$about = '<abbr title="'.$from.'"></abbr>'.`uptime`;
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
									$cmds.= ($off?'<i><code>':'').$cmd.
									($off?'</code></i>':'').', ';
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

	function c_botinfo($ns, $from, $message, $target) {
		$param = strtolower(args($message, 1));
		$ownerz = args($message, 2);
		$this->botdata = array_change_key_case($this->botdata, CASE_LOWER);
		if($param !== '') {
			if(!array_key_exists($param, $this->botdata)) {
				$this->dAmn->say($ns, "Sorry, {$from}, I don't have any data on <b>{$param}</b>. Sending request for botinfo, check back again.");
				$this->dAmn->npmsg('chat:DataShare', "BDS:BOTCHECK:REQUEST:{$param}", TRUE);
			}elseif(array_key_exists($param, $this->botdata) && empty($this->botdata[$param]['bannedBy'])) {
				$work = $this->botdata[$param];
				$ass = explode(';', $work['owner']);
				foreach($ass as $poo => $pooz) {
					$satan[$pooz] = array(true);
				}
				$asshole = '[<b>:dev' . implode(array_keys($satan), ':</b>], [<b>:dev') . ':</b>]';
				$sb  = '<sub>';
				$sb .= "Bot Username: [<b>:dev{$work['actualname']}:</b>]<br>";
				$sb .= "Bot Owner: {$asshole}<br>";
				$sb .= "Bot Version: <b>{$work['bottype']} <i>{$work['version']}</i></b><br>";
				$sb .= "BDS Version: <b>{$work['bdsversion']}</b><br>";
				$sb .= "Bot Trigger: <b>" . implode('</b><b>', str_split($work["trigger"])) . "</b><br>";
				$sb .= "Signature: <b>{$work['lasthash']}</b><br>";
				$sb .= 'Last update on <i>'.date('n/j/Y g:i:s A', $work['lastupdate'])." UTC</i> by [<b><i>:dev{$work['requestedBy']}:</i></b>]";
				$sb .= "</sub><abbr title=\"{$from}\"> </abbr>";
				$this->dAmn->say($ns, $sb);
			}else{
				$work = $this->botdata[$param];
				$sb  = '<sub>';
				$sb .= "Bot Username: [<b>:dev{$work['actualname']}:</b>]<br>";
				$sb .= "Bot Owner: [<b>:dev{$work['owner']}:</b>]<br>";
				$sb .= "Bot Status: <b>{$work['status']}</b><br>";
				$sb .= 'Last update on <i>'.date('n/j/Y g:i:s A', $work['lastupdate'])." UTC</i> by [<b><i>:dev{$work['bannedBy']}:</i></b>]";
				$sb .= "</sub><abbr title=\"{$from}\"> </abbr>";
				$this->dAmn->say($ns, $sb);
			}
		}else{
			$sb = "";
			$this->dAmn->say($ns, "<abbr title=\"{$from}\"></abbr> You must specify the name of a bot you wish to get information for.<br /><sup>[There are ".count($this->botdata)." bots in database.]</sup>", TRUE);
		}
	}
	function BDSBotCheck($ns, $sender, $payload) {
		$splitted = explode(',', $payload, 6);
		if(count($splitted) !== 6) return;
		$store = $this->store($splitted, $sender);
	}
	function bds_recv($ns, $from, $message) {
		if(strtolower($ns) == 'chat:datashare') {
			$parts = explode(':', $message, 4);
			if($parts[0] == 'BDS' && $parts[1] == 'BOTCHECK' && $parts[2] == 'RESPONSE')
				$this->BDSBotCheck($ns, $from, $parts[3]);
		}

	}
	function is_bot($from) {
		$fromz = strtolower($from);
		return $this->botdata[$fromz]['bot'];
	}
	function store($data, $from) {
		if(!$this->verify($data, $from)) return;
		// ADD SAVING SHIT UP HERE

		$strig = trim(htmlspecialchars_decode($data[5], ENT_NOQUOTES));

		$user = $from;
		$fromz = strtolower($from);
		$versions = explode('/', $data[3]);
		if(empty($versions[1])) $versions[1] = '0.1';
		if(empty($fromz)) return;
		$this->botdata[$fromz] = array(
			'requestedBy'	=> $data[0],
			'owner'		=> $data[1],
			'trigger'	=> $strig,
			'bottype'	=> $data[2],
			'version'	=> $versions[0],
			'bdsversion'	=> $versions[1],
			'actualname'	=> $user,
			'bot'		=> true,
			'lasthash'	=> $data[4],
			'lastupdate'	=> time() - (int)substr(date('O'),0,3)*60*60,
		);
		ksort($this->botdata, SORT_STRING);
		$this->save_botdata();
		return true;
	}
	function verify($data, $from) {
		if(count($data) < 6) return false;

		$versions = explode('/', $data[3]);
		if($data[2] == ('Indigo'|'Dante') && $data[5] == '&amp;')
			$strig = trim(htmlspecialchars_decode($data[5], ENT_NOQUOTES));
		else $strig = trim($data[5]);

		// Now, we have to recreate the hash
		$sig = md5(strtolower($strig.$data[0].$from));

		if($sig !== $data[4]) return false;

		// Hash check passed.
		unset($this->botKickTimers[strtolower($from)]);
		return true;
	}
	function e_banned($ns, $user, $by, $npc) {
		$bot = strtolower($user);
		if($ns == 'chat:DataShare' && !empty($this->botdata[$bot]) && $npc == 'Banned') {
			if(empty($reason)) $reason = 'Suspicious activity';
			$this->botdata[$bot] = array(
				'bannedBy'	=> $by,
				'owner'		=> $this->botdata[$bot]['owner'],
				'trigger'	=> $this->botdata[$bot]['trigger'],
				'bottype'	=> $this->botdata[$bot]['bottype'],
				'version'	=> $this->botdata[$bot]['version'],
				'status'	=> 'BANNED '.date('n/j/Y g:i:s A', time() - (int)substr(date('O'),0,3)*60*60)." - {$reason}",
				'actualname'	=> $user,
				'bot'		=> true,
				'lastupdate'	=> time() - (int)substr(date('O'),0,3)*60*60,
			);
			ksort($this->botdata, SORT_STRING);
			$this->save_botdata();
		}
	}
	function bdsmain($ns, $from, $message) {
		if($ns == 'chat:DataShare' && substr($message, 0, 4) == 'BDS:') {
			$command = explode(':', $message, 4);
			switch($command[1]) {
				case 'BOTCHECK':
				switch($command[2]) {
					case 'ALL':
					if($this->dAmn->chat[$ns]['member'][$from]['pc'] == 'PoliceBot')
						$this->dAmn->npmsg('chat:datashare', 'BDS:BOTCHECK:RESPONSE:'.$from.','.$this->Bot->owner.','.$this->Bot->info['name'].','.$this->Bot->info['version'].'/'.$this->Bot->info['bdsversion'].','.md5(strtolower(str_replace(' ', '', $this->Bot->trigger).$from.$this->Bot->username)).','.$this->Bot->trigger, TRUE);
					break;
					case 'DIRECT':
						if(strtolower($command[3]) == strtolower($this->Bot->username))
						$this->dAmn->npmsg('chat:datashare', 'BDS:BOTCHECK:RESPONSE:'.$from.','.$this->Bot->owner.','.$this->Bot->info['name'].','.$this->Bot->info['version'].'/'.$this->Bot->info['bdsversion'].','.md5(strtolower(str_replace(' ', '', $this->Bot->trigger).$from.$this->Bot->username)).','.$this->Bot->trigger, TRUE);
					break;
					case 'INFO':
						$info = explode(',', $message);
						$info2 = explode(':', $info[0]);
						$user = $info2[3];
						$userz = strtolower($user);
						if($this->dAmn->chat[$ns]['member'][$from]['pc'] != 'PoliceBot') return;
						elseif(strtolower($from) != strtolower($this->Bot->username) && is_array($this->botdata[$userz]) && !array_key_exists('bannedBy', $this->botdata[$userz])){
							$bottype = $info[1];
							$versions = explode('/', $info[2]);
							$botowner = $info[3];
							$trigger = $info[4];

							$this->botdata[$userz] = array(
								'requestedBy'	=> $from,
								'owner'		=> $botowner,
								'trigger'	=> $trigger,
								'bottype'	=> $bottype,
								'version'	=> $versions[0],
								'bdsversion'	=> $versions[1],
								'actualname'	=> $user,
								'bot'		=> true,
								'lasthash'	=> 'Updated by a police bot.',
								'lastupdate'	=> time() - (int)substr(date('O'),0,3)*60*60,
							);
							ksort($this->botdata, SORT_STRING);
							$this->save_botdata();
						}
					break;
					case 'NODATA':
						if($this->dAmn->chat[$ns]['member'][$from]['pc'] != 'PoliceBot') return;
						elseif(strtolower($command[3]) == strtolower($this->Bot->username) && strtolower($from) != strtolower($this->Bot->username))
							$this->dAmn->npmsg('chat:datashare', 'BDS:BOTCHECK:RESPONSE:'.$from.','.$this->Bot->owner.','.$this->Bot->info['name'].','.$this->Bot->info['version'].'/'.$this->Bot->info['bdsversion'].','.md5(strtolower(str_replace(' ', '', $this->Bot->trigger).$from.$this->Bot->username)).','.$this->Bot->trigger, TRUE);
					break;
					case 'BADBOT':
						if($this->dAmn->chat[$ns]['member'][$from]['pc'] != 'PoliceBot') return;
						elseif(strtolower($from) != strtolower($this->Bot->username)) {
							$info = explode(',', $message);
							$info2 = explode(':', $info[0]);
							$user = $info2[3];
							$userz = strtolower($user);
							$bottype = $info[2];
							$version = $info[3];
							$status = $info[4];
							$botowner = $info[1];
							$bannedby = $info[5];
							$lastupdate = $info[6];
							$trigger = $info[7];

							$this->botdata[$userz] = array(
								'bannedBy'	=> $bannedby,
								'owner'		=> $botowner,
								'trigger'	=> $trigger,
								'bottype'	=> $bottype,
								'version'	=> $version,
								'status'	=> $status,
								'actualname'	=> $user,
								'bot'		=> true,
								'lastupdate'	=> intval($lastupdate),
							);
							ksort($this->botdata, SORT_STRING);
							$this->save_botdata();
						}
					break;
				}
				break;
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
		if($trig!=''&&$trig!=$this->Bot->trigger) {
			if($conf!=''&&$conf=='yes') {
				$this->Bot->trigger = $trig;
				$this->Bot->save_config();
				$say = $from.': Trigger changed to <code>'.$trig.'</code>!';
			} else $say = $from.': Are you sure you want to change your trigger? (Type '.$this->Bot->trigger.'ctrig '.$trig.' yes)';
		} elseif($trig==$this->Bot->trigger) $say = $from.': Why change the bot\'s trigger to the same as current?';
		else $say = $from.': Use this command to change your trigger.';
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

	function c_update($ns, $from, $message) {
		if(strtolower($from) == strtolower($this->Bot->owner)) {
			$confirm = args($message, 1);
			if($confirm == 'yes') {
				$this->dAmn->npmsg('chat:DataShare', "CODS:VERSION:UPDATEME:{$this->Bot->username},{$this->Bot->info['version']}", TRUE);
				$this->dAmn->say($ns, "{$from}: Now updating. Bot will be shutdown after update is complete.");
			}else $this->dAmn->say($ns, "{$from}: <b>Updating Contra</b>:<br />Are you sure? using {$this->Bot->trigger}update will overwrite your bot's files.<br /><sub>Type <code>{$this->Bot->trigger}update yes</code> to confirm update.</sub>");
		}
	}

	function codsmain($ns, $from, $message) {
		if($ns == 'chat:DataShare' && substr($message, 0, 5) == 'CODS:') {
			$command = explode(':', $message, 5);
			switch($command[1]) {
				case 'BOTCHECK':
				switch($command[2]) {
					case 'ALL':
					$this->dAmn->npmsg('chat:datashare', 'CODS:BOTCHECK:RESPONSE:'.$from.','.$this->Bot->owner.','.$this->Bot->info['name'].','.$this->Bot->info['version'].'/'.$this->Bot->info['bdsversion'].','.md5(strtolower(str_replace(' ', '', $this->Bot->trigger).$from.$this->Bot->username)).','.$this->Bot->trigger, TRUE);
					break;
				}
				case 'VERSION':
				switch($command[2]) {
					case 'NOTIFY':
					$command2 = explode(',', $message, 5);
					$version = $command2[1];
					$released = $command2[2];
					if(stristr($command[3], $this->Bot->username)) {
						if(empty($version) || empty($released)) return;
						if($version > $this->Bot->info['version'] && $from == 'Asuos') {
							$this->sendnote($this->Bot->owner, 'Update Service', "A new version of Contra is available. (version: {$version}; released on {$released}) You can download it from http://botdom.com/wiki/Contra#Latest or type {$this->Bot->trigger}update to update your bot.<br />(<b>NOTE: using {$this->Bot->trigger}update will overwrite all your changes to your bot.</b>)");
							$this->Console->Alert("Contra {$version} has been released on {$released}. Get it at http://botdom.com/wiki/Contra#Latest");
						}
					}
					break;
					case 'UPDATE':
					$command2 = explode(',', $message, 5);
					$version = $command2[1];
					$downloadlink = $command2[2];
					if(stristr($command[3], $this->Bot->username)) {
						if(empty($version) || empty($downloadlink)) return;
						if($version > $this->Bot->info['version'] && $from == 'Asuos') {
							$file = file_get_contents($downloadlink);
							$link = explode('/', $downloadlink);
							$moo = fopen($link[4], 'w+');
							$moo2 = fwrite($moo, $file);
							fclose($moo);
							$zip = new ZipArchive;
							if($zip->open($link[4]) === TRUE) {
								$zip->extractTo('./');
								$zip->close();
							}
							unlink($link[4]);
							$this->Bot->shutdownStr[0] = 'Bot has been updated.';
							$this->dAmn->close=true;
							$this->dAmn->disconnect();
						}
					}
					break;
				}
			}
		}
	}

	function save_botdata() { $this->Write('botdata', $this->botdata, 2); }
	function load_botdata() {
		$this->botdata = $this->Read('botdata', 2);
		if($this->botdata !== false) return array();
		$this->botdata = '';
		$this->save_botdata();
		$this->load_botdata();
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

	function sendnote($to, $from, $content) {
		if(empty($to)) return false;
		$user = strtolower($to);
		if(!isset($this->notes[$user]))
			$this->notes[$user] = array();
		if(!isset($this->receivers[$user]))
			$this->receivers[$user] = 1;
		else $this->receivers[$user]++;
		$this->notewrite('receive', $this->receivers);
		$i = count($this->notes[$user]);
		$this->notes[$user][$i]['content'] = $content;
		$this->notes[$user][$i]['from'	 ] = 	$from;
		$this->notes[$user][$i]['ts'	 ] =   time();
		$this->notewrite('notes', $this->notes);
		$this->loadnotes();
		return true;
	}
	function loadnotes() {
		$notes = $this->Read('notes');
		$this->notes = ($notes === false ? array() : $notes);
		$rec = $this->Read('receive');
		$this->receivers = ($rec === false ? array() : $rec);
	}

	final protected function notewrite($file, $data, $format = 0) {
		if(!is_dir('./storage')) mkdir('./storage', 0755);
		if(!is_dir('./storage/mod')) mkdir('./storage/mod', 0755);
		if(!is_dir('./storage/mod/Notes')) mkdir('./storage/mod/Notes', 0755);
		$file = strtolower($file);
		switch($format) {
			case 2: save_config('./storage/mod/Notes/'.$file.'.bsv', $data);
				break;
			case 1: file_put_contents('./storage/mod/Notes/'.$file.'.bsv', $data);
				break;
			case 0: default:
				file_put_contents('./storage/mod/Notes/'.$file.'.bsv', serialize($data));
				break;
		}
	}
}

new System_commands($core);

?>