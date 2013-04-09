<?php

	/*
	*	AI module version 3.
	*	Made for Contra v3.
	*	Created by photofroggy.
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*/

class Brain extends extension {
	public $name = 'AI';
	public $version = 3;
	public $about = 'Artificial Intelligence.';
	public $status = true;
	public $author = 'photofroggy';

	function init() {
		$this->addCmd('ai', 'c_ai', 99);
		$this->switch_board();
	}

	function c_ai($ns, $from, $message, $target) {
		$dAmn = $this->dAmn;
		$user = $this->user;
		$com = strtolower(args($message, 1));
		switch($com) {
			case 'on':
			case 'off':
				$kw = $com == 'on' ? 'hook' : 'unhook';
				if ($ns == "chat:Botdom" && $com == 'on') {
					$dAmn->say($ns, $from.': AI can\'t be turned on in #Botdom.');
					break;
				}
				if ($this->$kw('e_msg', 'recv_msg')) {
					$dAmn->say($ns, $from.': AI turned '.$com.'!');
				}
				$this->switch_board($com);
				break;
			default:
				$dAmn->say($ns, $from.': This is Contra\'s AI module!');
				break;
		}
	}

	function e_msg($c, $from, $msg) {
		$dAmn = $this->dAmn;
		$name = $this->Bot->username;
		if (strtolower(substr($msg, 0, strlen($name))) == strtolower($name)) {
			if ($c == "chat:Botdom" || strtolower($from) == strtolower($name)) {
				return;
			}
			$msg = substr($msg, strlen($name.': '));
			$awayStr = 'I am currently away. Reason:';
			if (strtolower($msg)=="trigcheck" || strtolower($msg)=="trigger" || strtolower(substr($msg, 0, strlen($awayStr))) == strtolower($awayStr)) {
				return;
			}
			$response = file_get_contents('http://kato.botdom.com/respond/'.$from.'/'.base64_encode(html_entity_decode($msg)));
			$dAmn->say($c, $from.': '.$response);
		}
	}

	function switch_board($switch = false) {
		if ($switch !== false) {
			if ($switch == 'on') {
				$this->Write('switch', 'true', 1);
			} else {
				if (file_exists('./storage/mod/'.$this->name.'/switch.bsv')) {
					$this->Unlink('switch');
				}
			}
		}
		if (file_exists('./storage/mod/'.$this->name.'/switch.bsv')) {
			$this->hook('e_msg', 'recv_msg');
		}
	}
}

new Brain($core);

?>
