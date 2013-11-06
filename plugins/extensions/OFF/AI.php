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

	private $enabled = array();

	function init() {
		$this->addCmd('ai', 'c_ai', 99);
		$this->hook('e_msg', 'recv_msg');
		$this->enabled = $this->Read('ai');
		$this->enabled = ($this->enabled === false ? array() : $this->enabled);
	}

	function c_ai($ns, $from, $message, $target) {
		$dAmn = $this->dAmn;
		$user = $this->user;
		$com  = strtolower(args($message, 1));
		$chan = $dAmn->format_chat(strtolower(args($message, 2)));
		if ($chan == 'chat:') {
			$chan = strtolower($ns);
		}
		switch($com) {
			case 'on':
			case 'off':
				$kw = $com == 'on' ? 'hook' : 'unhook';
				if (in_array($chan, array('chat:botdom', 'chat:dsgateway','chat:datashare'))) {
					$dAmn->say($ns, $from.': AI can\'t be turned on in #'.substr($chan, 5).'.');
					break;
				}
				if ($com == 'on') {
					if (array_key_exists($chan, $this->enabled)) {
						$dAmn->say($ns, $from.': AI is already on in #'.substr($chan, 5).'.');
					} else {
						$dAmn->say($ns, $from.': AI turned on in #'.substr($chan, 5).'!');
						$this->enabled[$chan] = true;
					}
				} else {
					if (!array_key_exists($chan, $this->enabled)) {
						$dAmn->say($ns, $from.': AI is already off in #'.substr($chan, 5).'.');
					} else {
						$dAmn->say($ns, $from.': AI turned off in #'.substr($chan, 5).'!');
						unset($this->enabled[$chan]);
					}
				}
				$this->Write('ai', $this->enabled);
				break;
			default:
				$dAmn->say($ns, $from.': This is Contra\'s AI module!');
				break;
		}
	}

	function e_msg($c, $from, $msg) {
		$dAmn = $this->dAmn;
		$name = $this->Bot->username;
		$chan = strtolower($c);
		if (!array_key_exists($chan, $this->enabled) || $this->enabled[$chan] !== true) return;
		if (strtolower(substr($msg, 0, strlen($name))) == strtolower($name)) {
			if ($c == 'chat:Botdom' || strtolower($from) == strtolower($name)) {
				return;
			}
			$msg = substr($msg, strlen($name.': '));
			$awayStr = 'I am currently away. Reason:';
			if (strtolower($msg)=='trigcheck' || strtolower($msg)=='trigger' || strtolower(substr($msg, 0, strlen($awayStr))) == strtolower($awayStr)) {
				return;
			}
			$response = file_get_contents('http://kato.botdom.com/respond/'.$from.'/'.base64_encode(html_entity_decode($msg)));
			$dAmn->say($c, $from.': '.$response);
		}
	}
}

new Brain($core);

?>
