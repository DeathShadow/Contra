<?php
class BDS extends extension {
	public $name = 'BDS';
	public $version = 2;
	public $about = 'Optional BDS Stuff';
	public $status = true;
	public $author = 'DeathShadow--666';

	public $type = EXT_CUSTOM;

	protected $botdata = array();
	protected $botBanTimers = array();
	protected $kicks = array();
	protected $validBDS = "^([A-Z0-9]+:[A-Z0-9]+:[A-Z0-9]+)|([A-Z0-9]+:[A-Z0-9]+:[A-Z0-9]+:.*?)$^";

	function init() {
		$this->addCmd('botinfo', 'c_botinfo');
		$this->cmdHelp('botinfo','Lists information on a specific bot.');

		$this->hook('bds_join', 'recv_join');
		$this->hook('bds_recv', 'recv_msg');
		$this->hook('e_botKickTimer', 'botKickTimer');
		$this->hook('e_banned', 'recv_privchg');
		$this->hook('mainbds', 'recv_msg');
		$this->hook('pchatbds', 'recv_msg');

		$this->load_botdata();
	}
	function c_botinfo($ns, $from, $message, $target) {
		$param = strtolower(args($message, 1));
		$ownerz = args($message, 2);
		$this->botdata = array_change_key_case($this->botdata, CASE_LOWER);
		if($param !== '') {
			if(!array_key_exists($param, $this->botdata))
				$this->dAmn->say($ns, "Sorry, {$from}, I don't have any data on <b>{$param}</b>.");
			elseif(array_key_exists($param, $this->botdata) && empty($this->botdata[$param]['bannedBy'])) {
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
			$this->dAmn->say($ns, "<abbr title=\"{$from}\"></abbr> You must specify the name of a bot you wish to get information for. List of Registered Bots is currently disabled.<br /><sup>[There are ".count($this->botdata)." bots registered in database.]</sup>", TRUE);
		}
	}
	function BDSBotCheck($ns, $sender, $payload) {
		echo "Got botcheck info from {$sender}; checking to see if it's valid.\n";
		$splitted = explode(',', $payload, 6);
		if(count($splitted) !== 6) {
			echo "Warning: Malformed BDS Botcheck Response from {$sender}\n";
			return;
		}
		$store = $this->store($splitted, $sender);
		if($store) {
			echo "Got bot information from {$sender}\n";
			if($this->dAmn->chat[$ns]['member'][$this->Bot->username]['pc'] == 'PoliceBot') {
				if(array_key_exists(strtolower($sender), $this->botBanTimers)) {
					$this->Timer->delEvent($this->botBanTimers[$sender]);
					unset($this->botBanTimers[$sender]);
				}elseif($this->dAmn->chat[$ns]['member'][$sender]['pc'] == 'Clients')
					$this->dAmn->promote($ns, $sender, 'Bots');
			}
		}else echo "Bot information from {$sender} failed MD5 check\n";
	}
	function BDSClientCheck($ns, $sender, $payload) {
		echo "Got client info from {$sender}; checking to see if it's valid.\n";
		$splitted = explode(',', $payload, 4);
		if(count($splitted) != 4) {
			echo "Warning: Malformed BDS Client Response from {$sender}\n";
			return;
		}
		if($this->verifyclient($splitted, $sender)) {
			echo "Got client information from {$sender}\n";
			if($this->dAmn->chat[$ns]['member'][$this->Bot->username]['pc'] == 'PoliceBot') {
				if(array_key_exists(strtolower($sender), $this->botBanTimers)) {
					$this->Timer->delEvent($this->botBanTimers[$sender]);
					unset($this->botBanTimers[$sender]);
				}elseif($this->dAmn->chat[$ns]['member'][$sender]['pc'] == 'Bots')
					$this->dAmn->promote($ns, $sender, 'Clients');
			}
		}else echo "Client information from {$sender} failed MD5 check\n";
	}
	function bds_recv($ns, $from, $message) {
		if(strtolower($ns) == 'chat:datashare') {
			if($this->dAmn->chat[$ns]['member'][$this->Bot->username]['pc'] == 'PoliceBot') {
				if($this->dAmn->chat[$ns]['member'][$from]['pc'] == 'Bots' || $this->dAmn->chat[$ns]['member'][$from]['pc'] == 'Clients') {
					if(preg_match($this->validBDS, $message) == 0 && stristr(args($message, 0), 'part')) {
						$this->dAmn->kick($ns, $from, 'FAILURE TO READ THE TOPIC. Banned. <abbr title="(autokicked)"></abbr>');
						$this->dAmn->ban($ns, $from);
					}elseif(preg_match($this->validBDS, $message) == 0 && !stristr(args($message, 0), 'part')) {
						$this->dAmn->kick($ns, $from, 'Malformed BDS protocol message.  If you are not a bot, please do not join this room. Thanks. <abbr title="(autokicked)"></abbr>');
						$ts = time() - $this->kicks[$from];
						if ($ts < 1*60) $this->dAmn->demote($ns, $from, 'Silenced');
						$this->kicks[$from] = time();
					}
				}
			}
			$parts = explode(':', $message, 4);
			if($parts[0] == 'BDS' && $parts[1] == 'BOTCHECK' && $parts[2] == 'RESPONSE')
				$this->BDSBotCheck($ns, $from, $parts[3]);
			if($parts[0] == 'BDS' && $parts[1] == 'BOTCHECK' && $parts[2] == 'CLIENT')
				$this->BDSClientCheck($ns, $from, $parts[3]);
		}

	}
	function is_bot($from) {
		$fromz = strtolower($from);
		return $this->botdata[$fromz]['bot'];
	}
	function store($data, $from) {
		if(!$this->verify($data, $from)) return;
		// ADD SAVING SHIT UP HERE

		$strig = str_replace(' ', '', trim(htmlspecialchars_decode($data[5])));

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
		echo implode($data, ',') . "\n";

		if(count($data) < 6) {
			echo "Botcheck data invalid: not enough parameters.\n";
			return false;
		}

		$versions = explode('/', $data[3]);
		$strig = str_replace(' ', '', trim($data[5]));

		// Now, we have to recreate the hash
		$sig = md5(strtolower($strig.$data[0].$from));

		if($sig !== $data[4]) {
			echo "Signature is: {$data[4]}\nShould Be: {$sig}\n";
			return false;
		}

		// Hash check passed.
		$validbot = true;
		return true;
	}
	function verifyclient($data, $from) {
		echo implode($data, ',') . "\n";

		if(count($data) < 4) {
			echo "Client Botcheck data invalid: not enough parameters.\n";
			return false;
		}

		// Now, we have to recreate the hash
		$sig = md5(strtolower($data[1].$data[2].$from.$data[0]));

		if($sig !== $data[3]) {
			echo "Signature is: {$data[3]}\nShould Be: {$sig}\n";
			return false;
		}

		// Hash check passed.
		$validclient = true;
		return true;
	}
	function bds_join($ns, $from, $validclient, $message) {
		if(strtolower($ns) == 'chat:datashare') {
			if($this->dAmn->chat[$ns]['member'][$this->Bot->username]['pc'] == 'PoliceBot')
				if($this->dAmn->chat[$ns]['member'][$from]['pc'] == 'Bots' || $this->dAmn->chat[$ns]['member'][$from]['pc'] == 'Clients') {
					$fromz = strtolower($from);
					if(!$this->is_bot($fromz) && $validclient == false)
						// Timed ban
						$this->botKickTimers[$from] = $this->Timer->addEvt($this->name, 30, $from, 'botKickTimer', false);
				}
				if($this->dAmn->chat[$ns]['member'][$from]['pc'] == 'Bots' || $this->dAmn->chat[$ns]['member'][$from]['pc'] == 'Clients' || $this->dAmn->chat[$ns]['member'][$from]['pc'] == 'PoliceBot')
					$this->dAmn->npmsg($ns, "BDS:BOTCHECK:DIRECT:{$from}", true);
		}
	}
	function e_botKickTimer($from) {
		unset($this->botKickTimers[$from]);
		if($from == $this->Bot->username) return;
		$this->dAmn->kick('chat:datashare', $from, 'No response to or invaild BDS:BOTCHECK. If you are not a bot, please do not join this room. Thanks.');
		echo "{$from} hasn't responded after 30 seconds.  Kickin'.\n";
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
			if($this->dAmn->chat[$ns]['member'][$this->Bot->username]['pc'] == 'PoliceBot')
				$this->dAmn->npmsg($ns, "BDS:BOTCHECK:BADBOT:{$user},{$this->botdata[$bot]['owner']},{$this->botdata[$bot]['bottype']},{$this->botdata[$bot]['version']},{$this->botdata[$bot]['status']},{$this->botdata[$bot]['bannedBy']},{$this->botdata[$bot]['lastupdate']},{$this->botdata[$bot]['trigger']}", TRUE);
		}
	}
	function mainbds($ns, $from, $message) {
		if($ns == 'chat:DataShare' && substr($message, 0, 4) == 'BDS:' && $from != $this->Bot->username) {
			$command = explode(':', $message, 4);
			switch($command[1]) {
				case 'BOTCHECK':
				switch($command[2]) {
					case 'REQUEST':
						$user = $command[3];
						$userz = strtolower($user);
						if(empty($user)) return;
						if($this->dAmn->chat[$ns]['member'][$this->Bot->username]['pc'] != 'PoliceBot') return;
						elseif(array_key_exists($userz, $this->botdata) && !array_key_exists('bannedBy', $this->botdata[$userz]))
							$this->dAmn->npmsg($ns, "BDS:BOTCHECK:INFO:{$user},{$this->botdata[$userz]['bottype']},{$this->botdata[$userz]['version']}/{$this->botdata[$userz]['bdsversion']},{$this->botdata[$userz]['owner']},{$this->botdata[$userz]['trigger']}", TRUE);
						elseif(array_key_exists($userz, $this->botdata) && array_key_exists('bannedBy', $this->botdata[$userz]))
							$this->dAmn->npmsg($ns, "BDS:BOTCHECK:BADBOT:{$user},{$this->botdata[$userz]['owner']},{$this->botdata[$userz]['bottype']},{$this->botdata[$userz]['version']},{$this->botdata[$userz]['status']},{$this->botdata[$userz]['bannedBy']},{$this->botdata[$userz]['lastupdate']},{$this->botdata[$userz]['trigger']}", TRUE);
						else $this->dAmn->npmsg($ns, "BDS:BOTCHECK:NODATA:{$user}", TRUE);
					break;
					case 'INFO':
						$info = explode(',', $message);
						$info2 = explode(':', $info[0]);
						$user = $info2[3];
						$userz = strtolower($user);
						if($this->dAmn->chat[$ns]['member'][$from]['pc'] != 'PoliceBot') return;
						elseif($from != $this->Bot->username && !array_key_exists('bannedBy', $this->botdata[$userz])){
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
						$user = $command[3];
						$userz = strtolower($user);

						if(stristr($command[3], $this->Bot->username) && $this->dAmn->chat[$ns]['member'][$from]['pc'] == "PoliceBot")
							$this->dAmn->npmsg('chat:datashare', 'BDS:BOTCHECK:RESPONSE:'.$from.','.$this->Bot->owner.','.$this->Bot->info['name'].','.$this->Bot->info['version'].'/'.$this->Bot->info['bdsversion'].','.md5(strtolower(trim(str_replace(' ', '', $this->Bot->trigger)).$from.$this->Bot->username)).','.$this->Bot->trigger, TRUE);
						elseif($this->dAmn->chat[$ns]['member'][$this->Bot->username]['pc'] == 'PoliceBot' && array_key_exists($userz, $this->botdata) && !array_key_exists('bannedBy', $this->botdata[$userz]))
							$this->dAmn->npmsg($ns, "BDS:BOTCHECK:INFO:{$user},{$this->botdata[$userz]['bottype']},{$this->botdata[$userz]['version']}/{$this->botdata[$userz]['bdsversion']},{$this->botdata[$userz]['owner']},{$this->botdata[$userz]['trigger']}", TRUE);
						elseif($this->dAmn->chat[$ns]['member'][$this->Bot->username]['pc'] == 'PoliceBot' && array_key_exists($userz, $this->botdata) && array_key_exists('bannedBy', $this->botdata[$userz]))
							$this->dAmn->npmsg($ns, "BDS:BOTCHECK:BADBOT:{$user},{$this->botdata[$userz]['owner']},{$this->botdata[$userz]['bottype']},{$this->botdata[$userz]['version']},{$this->botdata[$userz]['status']},{$this->botdata[$userz]['bannedBy']},{$this->botdata[$userz]['lastupdate']},{$this->botdata[$userz]['trigger']}", TRUE);
					break;
					case 'BADBOT':
						if($this->dAmn->chat[$ns]['member'][$from]['pc'] != 'PoliceBot') return;
						else{
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
				case 'SYNC':
				switch($command[2]) {
					case 'REQUEST':
						$info = explode(',', $message);
						$info2 = explode(':', $info[0]);
						$user = strtolower($info2[3]);
						$death = implode(array_keys($this->botdata));
						$hash = md5(strtolower(trim($death)));
						$count = count($this->botdata);
						if($this->dAmn->chat[$ns]['member'][$this->Bot->username]['pc'] == 'PoliceBot' && $user == strtolower($this->Bot->username))
							$this->dAmn->npmsg($ns, "BDS:SYNC:RESPONSE:{$from},{$hash},{$count}", TRUE);
					break;
					case 'RESPONSE':
						$info = explode(',', $message);
						$info2 = explode(':', $info[0]);
						$user = strtolower($info2[3]);
						$death = implode(array_keys($this->botdata));
						$hash = md5(strtolower(trim($death)));
						$count = count($this->botdata);
						if($this->dAmn->chat[$ns]['member'][$this->Bot->username]['pc'] == 'PoliceBot') {
							if($user == strtolower($this->Bot->username) && $info[1] != $hash && $info[2] > $count) $this->dAmn->npmsg($ns, "BDS:LINK:REQUEST:{$from}", TRUE);
							elseif($user == strtolower($this->Bot->username) && $info[1] != $hash && $info[2] < $count) $this->dAmn->npmsg($ns, "BDS:BOTCHECK:DIRECT:{$from}", TRUE);
							elseif($user == strtolower($this->Bot->username) && $info[1] == $hash && $info[2] == $count) $this->dAmn->npmsg($ns, "BDS:SYNC:OKAY:{$from}", TRUE);
						}
					break;
					case 'OKAY':
						return;
					break;
				}
				break;
				case 'LINK':
				switch($command[2]) {
					case 'REQUEST':
						$info = explode(',', $message);
						$info2 = explode(':', $info[0]);
						$user = strtolower($info2[3]);
						if($user == strtolower($this->Bot->username) && $this->dAmn->chat[$ns]['member'][$from]['pc'] == 'PoliceBot' && $from != $this->Bot->username) {
							$bot=$this->Bot->username;
							$paa=$this->dAmn->format_chat('@'.$bot, $from);
							$this->dAmn->join($paa);
							$this->dAmn->npmsg($ns, "BDS:LINK:ACCEPT:{$from}", TRUE);
						}elseif($user == strtolower($this->Bot->username) && $this->dAmn->chat[$ns]['member'][$from]['pc'] != 'PoliceBot')
							$this->dAmn->npmsg($ns, "BDS:LINK:REJECT:{$from}", TRUE);
					break;
					case 'ACCEPT':
						$info = explode(',', $message);
						$info2 = explode(':', $info[0]);
						$user = strtolower($info2[3]);
						if($user == strtolower($this->Bot->username) && $this->dAmn->chat[$ns]['member'][$from]['pc'] == 'PoliceBot' && $from != $this->Bot->username) {
							$bot=$this->Bot->username;
							$paa=$this->dAmn->format_chat('@'.$bot, $from);
							$this->dAmn->join($paa);
							sleep(1);
							$this->dAmn->npmsg($paa, 'BDS:SYNC:BEGIN');
						}
					break;
					case 'REJECT':
						$info = explode(',', $message);
						$info2 = explode(':', $info[0]);
						$user = strtolower($info2[3]);
						if($user == strtolower($this->Bot->username) && $from != $this->Bot->username) {
							$bot=$this->Bot->username;
							$paa=$this->dAmn->format_chat('@'.$bot, $from);
							$this->dAmn->part($paa);
						}
					break;
				}
				break;
			}
		}
	}
	function pchatbds($ns, $from, $message) {
		if(strstr($ns, 'pchat:') && substr($message, 0, 4) == 'BDS:' && $from != $this->Bot->username) {
			$command = explode(':', $message, 4);
			switch($command[1]) {
				case 'SYNC':
				switch($command[2]) {
					case 'BEGIN':
						$bot=$this->Bot->username;
						$paa=$this->dAmn->format_chat('@'.$bot, $from);
						foreach($this->botdata as $bot => $botz) {
							$i = count($bot);
							while($i > 0) {
								if(empty($botz['bannedBy']))
									$this->dAmn->npmsg($paa, "BDS:SYNC:INFO:{$botz['actualname']},{$botz['owner']},{$botz['bottype']},{$botz['version']}/{$botz['bdsversion']},{$botz['lastupdate']},{$botz['trigger']}");
								if(!empty($botz['bannedBy']))
									$this->dAmn->npmsg($paa, "BDS:SYNC:BADBOT:{$botz['actualname']},{$botz['owner']},{$botz['bottype']},{$botz['version']},{$botz['status']},{$botz['bannedBy']},{$botz['lastupdate']},{$botz['trigger']}");
								$i--;
								flush();
								usleep(2000);
							}
						}
						$this->dAmn->npmsg($paa, 'BDS:SYNC:FINISHED');
						$this->dAmn->part($paa);
					break;
					case 'INFO':
						if($from != $this->Bot->username) {
							$info = explode(',', $message);
							$info2 = explode(':', $info[0]);
							$user = strtolower($info2[3]);
							$userz = strtolower($user);
							$botowner = $info[1];
							$bottype = $info[2];
							$version = explode('/', $info[3]);
							$lastupdate = $info[4];
							$trigger = $info[5];

							$this->botdata[$userz] = array(
								'requestedBy'	=> $from,
								'owner'		=> $botowner,
								'trigger'	=> $trigger,
								'bottype'	=> $bottype,
								'version'	=> $version[0],
								'bdsversion'	=> $version[1],
								'actualname'	=> $user,
								'bot'		=> true,
								'lasthash'	=> 'Updated by a police bot.',
								'lastupdate'	=> intval($lastupdate),
							);
							ksort($this->botdata, SORT_STRING);
							$this->save_botdata();
						}
					break;
					case 'BADBOT':
						if($from != $this->Bot->username) {
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
					case 'FINISHED':
						$bot=$this->Bot->username;
						$paa=$this->dAmn->format_chat('@'.$bot, $from);
						$this->dAmn->part($paa);
					break;
				}
				break;
				case 'LINK':
				switch($command[2]) {
					case 'CLOSE':
						$bot=$this->Bot->username;
						$paa=$this->dAmn->format_chat('@'.$bot, $from);
						$this->dAmn->part($paa);
					break;
				}
				break;
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
}

new BDS($core);

?>