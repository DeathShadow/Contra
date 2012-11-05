<?php

	/*
	*	dAmn commands. Version 3.
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*	This handles default dAmn commands
	*	and some extra ones.
	*/

class dAmn_commands extends extension {
	public $name = 'dAmn';
	public $version = 3;
	public $about = 'dAmn commands.';
	public $status = true;
	public $author = 'photofroggy';
	public $type = EXT_SYSTEM;

	protected $ping_who;
	protected $ping_ts;
	protected $ping_ns;

	protected $whois = false;
	protected $whois_from;
	protected $whois_user;
	protected $whois_chan;

	function init() {
		$this->addCmd('chat', 'c_chat');
		$this->addCmd('join', 'c_joinpart', 99);
		$this->addCmd('part', 'c_joinpart', 99);
		$this->addCmd('say', 'c_say', 99);
		$this->addCmd('promote', 'c_promote',99);
		$this->addCmd('demote', 'c_demote',99);
		$this->addCmd('kick', 'c_kick', 99);
		$this->addCmd('ban', 'c_ban', 99);
		$this->addCmd('unban', 'c_unban', 99);
		$this->addCmd('get', 'c_get',99);
		$this->addCmd('set', 'c_set',99);
		$this->addCmd('admin', 'c_admin', 99);
		$this->addCmd('raw', 'c_raw', 100);
		$this->addCmd('ping', 'c_ping', 99);
		$this->addCmd('members', 'c_members');
		$this->addCmd('whois', 'c_whois');
		$this->addCmd('channels', 'c_channels', 100);
		$this->addCmd('disconnects', 'c_disconnects', 99);

		$this->addCmd('dsay', 'c_dsay', 99); // Lolololololololol.

		$this->cmdHelp('chat', 'Make your bot open a private chat.');
		$this->cmdHelp('join', 'Make your bot join dAmn channels.');
		$this->cmdHelp('part', 'Make your bot leave dAmn channels.');
		$this->cmdHelp('say', 'Make your bot say something.');
		$this->cmdHelp('promote', 'Promote someone in a channel.');
		$this->cmdHelp('demote', 'Demote someone in a channel.');
		$this->cmdHelp('kick', 'Kick someone from a channel.');
		$this->cmdHelp('ban', 'Ban someone from a channel.');
		$this->cmdHelp('unban', 'Uban someone from a channel.');
		$this->cmdHelp('get', 'Get properties of a channel.');
		$this->cmdHelp('set', 'Set properties of a channel.');
		$this->cmdHelp('admin', 'Send an admin command to a channel.');
		$this->cmdHelp('raw', 'Send a raw dAmn packet!');
		$this->cmdHelp('ping', 'Test the speed of your network connection!');
		$this->cmdHelp('members', 'View the members of a channel');
		$this->cmdHelp('channels', 'View the channels that the bot is currently connected to.');
		$this->cmdHelp('disconnects', 'View the number of times the bot has been disconnected from dAmn.');

		$this->cmdHelp(
			'dsay',
			'Send a delayed message! Example: '
				.$this->Bot->trigger.'dsay 10 hello thar!'
		);

		$this->hook('e_ping', 'recv_msg');
		$this->hook('e_provider', 'join');
		$this->hook('e_kicked', 'kicked');
		$this->hook('e_respond', 'recv_msg');
		$this->hook('e_whois', 'whois');
	}

	function c_chat($ns, $from, $message, $target) {
		$bot=strtolower($this->Bot->username);
		$moo=strtolower($from);
		$paa=$this->dAmn->format_chat('@'.$bot, $moo);
		if(args($message, 1)) {
			$moo=strtolower(args($message, 1));
			$paa=$this->dAmn->format_chat('@'.$bot, $moo);
			$this->dAmn->say($ns, 'Opened pchat with :dev'.$moo.':');
		}else $this->dAmn->say($ns, 'Opened pchat with :dev'.$moo.':');
		$this->dAmn->join($paa);
	}

	function c_joinpart($ns, $from, $message, $target) {
		$func = strtolower(args($message, 0)) == 'join' ? 'join' : 'part';
		$chans = str_replace('#', ' ', args($message, 1, true));
		$chans = explode(' ', $chans);
		$chans = array_filter($chans);
		$njck = $this->dAmn->format_chat(args($message, 1));
		if($njck == 'chat:') unset($njck);
		if(in_array(strtolower($target), $this->dAmn->njc) || in_array(strtolower($njck), $this->dAmn->njc))
			if(isset($njck))
				return $this->dAmn->say($ns, $from.': Cannot join '.$this->dAmn->deform_chat($njck).'.');
			else return $this->dAmn->say($ns, $from.': Cannot join '.$this->dAmn->deform_chat($target).'.');
		if(empty($chans[0]) && $func != 'join' && $target == $ns && count($chans) <= 1)
			$this->dAmn->$func($ns);
		elseif(empty($chans[0]) && count($chans) <= 1)
			$this->dAmn->$func($this->dAmn->format_chat($target));
		else {
			if($target != $ns)
				$this->dAmn->$func($target);
			foreach($chans as $chan)
				$this->dAmn->$func($this->dAmn->format_chat($chan));
		}
	}
	function c_say($ns, $from, $message, $target) { $this->dAmn->say($target,args($message,1,true)); }
	function c_promote($ns, $from, $message, $target) {
		$user = args($message,1);
		if(empty($user))
			return $this->dAmn->say($ns, $from.': Usage: '.$this->Bot->trigger.'promote (user) [privclass]');
		$pc = args($message, 2); $pc = $pc==''?false:$pc;
		$this->dAmn->promote($target, $user, $pc);
	}
	function c_demote($ns, $from, $message, $target) {
		$user = args($message,1);
		if(empty($user))
			return $this->dAmn->say($ns, $from.': Usage: '.$this->Bot->trigger.'demote (user) [privclass]');
		$pc = args($message, 2); $pc = $pc==''?false:$pc;
		$this->dAmn->demote($target, $user, $pc);
	}
	function c_kick($ns, $from, $message, $target) {
		$user = args($message,1);
		if(empty($user))
			return $this->dAmn->say($ns, $from.': Usage: '.$this->Bot->trigger.'kick (user) [reason]');
		$r = args($message, 2, true); $r = $r==''?false:$r;
		$this->dAmn->kick($target, $user, $r);
	}
	function c_ban($ns, $from, $message, $target) {
		$user = args($message,1);
		if(empty($user))
			return $this->dAmn->say($ns, $from.': Usage: '.$this->Bot->trigger.'ban (user)');
		$this->dAmn->ban($target, $user);
	}
	function c_unban($ns, $from, $message, $target) {
		$user = args($message,1);
		if(empty($user))
			return $this->dAmn->say($ns, $from.': Usage: '.$this->Bot->trigger.'unban (user)');
		$this->dAmn->unban($target, $user);
	}
	function c_get($ns, $from, $message, $target) {
		$prop = args($message,1);
		if(empty($prop))
			return $this->dAmn->say($ns, $from.': Usage: '.$this->Bot->trigger.'get (property)');
		$this->dAmn->get($target, $prop);
	}
	function c_set($ns, $from, $message, $target) {
		$prop = args($message,1);
		$val = args($message,2,true);
		if(empty($prop) || empty($val))
			return $this->dAmn->say($ns, $from.': Usage: '.$this->Bot->trigger.'set (property) (value)');
		$this->dAmn->set($target, $prop, $val);
	}
	function c_admin($ns, $from, $message, $target) {
		$com = args($message,1,true);
		if(empty($com))
			return $this->dAmn->say($ns, $from.': Usage: '.$this->Bot->trigger.'admin (command)');
		$this->dAmn->admin($target,$com);
	}
	function c_raw($ns, $from, $message, $target) { $this->dAmn->send(str_replace('\n', LBR, args($message, 1, true))); }
	function c_ping($ns, $from, $message, $target) {
		$this->ping_who = $from;
		$this->ping_ns = $ns;
		$this->ping_ts = microtime(true);
		$this->dAmn->say($ns,$from.': Ping?');
	}

	function e_ping($ns, $from, $message) {
		if($ns != $this->ping_ns) return;
		if(strtolower($from) != strtolower($this->Bot->username)) return;
		if($message != $this->ping_who.': Ping?') return;
		$this->dAmn->say($ns, $this->ping_who.': Pong! ('.round((microtime(true) - $this->ping_ts),5).')');
		$this->ping_ns = $this->ping_who = $this->ping_ts = false;
	}

	function e_kicked($ns, $from, $r = false) {
		if($r!==false)
			if(strpos(strtolower($r),'autokicked')!==false || strpos(strtolower($r),'not privileged')!==false) return;
		$this->Console->Notice('Attempting to rejoin '.$this->dAmn->deform_chat($ns,$this->Bot->username).'.');
		$this->dAmn->join($ns);
	}

	function e_respond($ns, $from, $message, $target) {
		if($message == $this->Bot->username.': botcheck' && $ns == 'chat:Botdom' || stristr($message, '<abbr title="'.$this->Bot->username.': botcheck"></abbr>') && stristr($message, $this->Bot->username) && $ns == 'chat:Botdom')
			$this->dAmn->say($ns, '<abbr title="away"></abbr>I\'m a bot. <abbr title="botresponse: '.$from.' '.$this->Bot->owner.' '.$this->Bot->info['name'].' '.$this->Bot->info['version'].'/'.$this->Bot->info['bdsversion'].' '.md5(strtolower(str_replace(' ', '', htmlspecialchars_decode($this->Bot->trigger, ENT_NOQUOTES)).$from.$this->Bot->username)).' '.$this->Bot->trigger.'"></abbr>');
	}

	function c_members($ns, $from, $message, $target) {
		$chan = $this->dAmn->is_channel($target);
		if($chan===false||$chan=='chat:DataShare')
			return $this->dAmn->say($ns, $from.': I have not joined '.$this->dAmn->deform_chat($target, $this->Bot->username).'.');
		$type = strtolower(args($message,1));
		switch($type) {
			case 'inline':
				$say = $from.': ';
				foreach($this->dAmn->chat[$chan]['member'] as $name => $data)
					$say.= substr($name,0,1).'<b></b>'.substr($name,1).', ';
				$say = rtrim($say, ', ');
				break;
			case 'list':
			default:
				$say = '<abbr title="'.$from.'"></abbr>';
				$total = count($this->dAmn->chat[$chan]['member']); $body = '';
				foreach($this->dAmn->chat[$chan]['pc'] as $ord => $pc) {
					$list = ''; $usrs = 0;
					foreach($this->dAmn->chat[$chan]['member'] as $name => $data) {
						if($pc==$data['pc']) {
							++$usrs; $con = $data['con'];
							$list.= $data['symbol'].'<b></b>'.
							substr($name,0,1).'<b></b>'.substr($name,1)
							.($con==1?'':'['.$con.']').', ';
						}
					}
					if($usrs!==0) {
						$body.= '<br/><b>'.$pc.'</b>('.$usrs.')<br/><sub>-> '
						.rtrim($list,', ').'</sub>';
					}
				}
				$say.= '<b>'.$total.' '.($total==1?'user':'users').' in '.
				$this->dAmn->deform_chat($chan, $this->Bot->username).'</b><sub>'.$body.'</sub>';
				break;
		}
		$this->dAmn->say($ns,$say);
	}

	function c_whois($ns, $from, $message, $target) {
		$user = args($message, 1);
		if(!empty($user)) {
			$this->whois = true; $this->whois_from = $from;
			$this->whois_user = $user; $this->whois_chan = $target;
			$this->dAmn->get('login:'.$user, 'info');
		} else $this->dAmn->say($ns, $from.': Use this command to get whois info on a user.');
	}

	function e_whois($data){
		if(!$this->whois) return;
		$pack = parse_dAmn_packet($data);
		if($pack['cmd']=='get') {
			$this->dAmn->say($this->whois_chan,
			$this->whois_from.': Couldn\'t get info for '.
			$this->whois_user.' ('.$pack['args']['e'].')');
		} else {
			$Whois = handleWhois($data);
			$user = $Whois['user'];
			$Whois['rn'] = $user == 'ManjyomeThunder' ? 'The additional pylon\'s bitch!' : $Whois['rn'];
			$Whois['tn'] = $user == 'ManjyomeThunder' ? 'Inert Balls' : $Whois['tn'];
			$say = '<abbr title="'.$this->whois_from.'"></abbr><b>:icon'.$user.'::dev'.$user.":</b>\n";
			$say.= "<b>&nbsp;&middot;</b>&nbsp;&nbsp;{$Whois['rn']}\n&nbsp;<b>&middot;</b>&nbsp;&nbsp;{$Whois['tn']}\n";
			foreach($Whois['cons'] as $con => $cont) {
				if(isset($Whois['cons'][2])) $say.= "\n<i>Connection $con:</i>";
				$it = time_length($Whois['cons'][$con]['main']['idle']);
				if(!$it) $it = '0 seconds';
				$jt = $Whois['cons'][$con]['main']['online'];
				$say.= "\n&nbsp;&nbsp;<b>&middot;&nbsp;&nbsp;Online:</b> ";
				$say.= "<abbr title=\"Connected for ".time_length($jt)."\">".gmdate("D M j Y [H:i:s]",time() - $jt)."</abbr>\n";
				$say.= "&nbsp;&nbsp;<b>&middot;&nbsp;&nbsp;Idle:</b> $it\n";
				$say.= "&nbsp;&nbsp;<b>&middot;&nbsp;&nbsp;Chatrooms:</b> ";
				$crc = $Whois['cons'][$con]['c'];
				$clow = array_map('strtolower', $crc);
				array_multisort($clow, SORT_ASC, SORT_STRING, $crc);
				$chans = '';
				foreach($crc as $id => $cl) {
					$chans.= $this->dAmn->deform_chat($cl, $this->Bot->username).', ';
					$chans = str_ireplace('#DataShare,', '', $chans);
				}
				$say.= rtrim($chans,', ');
			}
			$this->dAmn->say($this->whois_chan, $say);
		}
		$this->whois = false;
	}

	function e_provider($ns) {
		if($ns == 'chat:DataShare') {
			$this->dAmn->npmsg('chat:datashare', 'BDS:PROVIDER:CAPS:CONTRA-UPDATE,BOTCHECK,BOTCHECK-EXT', TRUE);
			$this->dAmn->npmsg('chat:datashare', 'CODS:VERSION:CHECK:'.$this->Bot->username.','.$this->Bot->info['version'], TRUE);
		}
	}

	function c_channels($ns, $from, $message, $target) {
		$msg = '<abbr title="'.$from.'"></abbr><b>Currently joined channels:</b><br/><sup>';
		$chans = '';
		foreach($this->dAmn->chat as $name => $data) {
			$chans.= $this->dAmn->deform_chat($name, $this->Bot->username).', ';
			$chans = str_ireplace('#DataShare,', '', $chans);
		}
		$this->dAmn->say($ns, $msg.rtrim($chans,', ').'</sup>');
	}

	function c_disconnects($ns, $from, $message, $target) {
		$this->dAmn->say($ns, $from.': I have been disconnected '
		.$this->dAmn->disconnects.' '.($this->dAmn->disconnects==1?'time':'times').'.');
	}

	function c_dsay($ns, $from, $message, $target) {
		$time = args($message, 1);
		$msg = args($message, 2, true);
		if(!is_numeric($time))
			return $this->dAmn->say($ns, $from.': First argument must be a number!');
		$time = $time > -1 ? $time : $time*-1;
		if(empty($msg))
			return $this->dAmn->say($ns, $from.': You need to provide a message...');
		$args = array($ns, $from, $this->Bot->trigger.'say '.$msg);
		echo $this->Timer->addEvt($this->name, $time, $args, 'dsay'),chr(10);
		$this->hook('e_dsay', 'dsay');
	}
	function e_dsay($data) {
		$this->unhook('e_dsay', 'dsay');
		$this->Bot->Events->command('say', $data[0], $data[1], $data[2]);
	}

}

function handleWhois($packet) {
	$packet = parse_dAmn_packet($packet);
	$conPack = parse_dAmn_packet($packet['body']);
	$info = array(
		'user' => substr($packet['param'], 6),
		'rn' => $conPack['args']['realname'],
		'tn' => $conPack['args']['typename'],
	);
	$loop = true;
	$conNum = 0;
	while($loop == true) {
		$conPack = parse_dAmn_packet($conPack['body']);
		if($conPack['cmd'] == 'conn') {
			++$conNum;
			$info['cons'][$conNum]['main'] = array(
				'online' => $conPack['args']['online'],
				'idle' => $conPack['args']['idle'],
			);
		} elseif($conPack['cmd'] == 'ns') {
			$info['cons'][$conNum]['c'][] = $conPack['param'];
		} elseif(empty($conPack['cmd'])) $loop = Null;
	}
	return $info;
}

new dAmn_commands($core);

?>