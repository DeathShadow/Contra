<?php
	/*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*/

class BDS extends extension {
	public $name = 'BDS';
	public $version = 1;
	public $about = 'Optional BDS Stuff';
	public $status = true;
	public $author = 'DeathShadow--666';

	public $type = EXT_CUSTOM;

	function init() {
		$this->hook('bdsoptional', 'recv_msg');
	}
	function bdsoptional($ns, $from, $message) {
		if($ns == "chat:DataShare" && substr($message, 0, 4) == "BDS:") {
			$command = explode(":", $message, 4);
			switch($command[1]) {
				case 'BOTCHECK':
				switch($command[2]) {
					case 'NODATA':
					if(stristr($command[3], $this->Bot->username) && $this->dAmn->chat[$ns]['member'][$from]['pc'] == "PoliceBot")
						$this->dAmn->npmsg('chat:datashare', 'BDS:BOTCHECK:RESPONSE:'.$from.','.$this->Bot->owner.','.$this->Bot->info['name'].','.$this->Bot->info['version'].'/'.$this->Bot->info['bdsversion'].','.md5(strtolower(trim(str_replace(' ', '', $this->Bot->trigger)).$from.$this->Bot->username)).','.$this->Bot->trigger, TRUE);
					break;
				}
			}
		}
		return;
	}
}
	new BDS($core);
?>