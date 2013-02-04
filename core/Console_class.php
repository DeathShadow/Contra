<?php

	/*
	*	Console class for Contra by photofroggy.
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*	This is the stuff used to send messages to
	*	the console output. It mainly just adds a
	*	clock and certain characters, depending on
	*	the type of message.
	*
	*	You can use $Console->Clock(); to return the
	*	internal clock which is used on the messages.
	*/

class Console {
	public $format = 'H:i:s';

	function Time($ts=false) {
		if(PHP_OS == 'Darwin') {
			$config = include './storage/config.cf';
			ini_set('date.timezone', $config['timezone']);
			unset($config);
		}
		return date($this->format, ($ts==false?time():$ts));
	}
	function Clock($ts=false) {	return '['.$this->Time($ts).']'; }
	function Message($str = '', $ts = false) {
		foreach (array(chr(0), chr(7)) as $chr)
			$str = str_replace($chr, '', $str);
		if(DEBUG) $this->log($this->Clock($ts).' '.$str.chr(10));
		echo $this->Clock($ts),' ',$str,chr(10);
	}
	function Notice($str = '', $ts = false)  { $this->Message('** '.$str,$ts); }
	function Warning($str = '', $ts = false) { $this->Message('>> '.$str,$ts); }
	function Alert($str = '', $ts = false) { $this->Message('!! >> '.$str,$ts); }
	function Write($data = '') {
		if(is_array($data)) $data = var_export($data, true);
		$data = str_replace("\n", "\n>>", $data);
		foreach (array(chr(0), chr(7)) as $chr)
			$str = str_replace($chr, '', $str);
		echo '>>',$data,chr(10);
		if(DEBUG) $this->log('>>'.$data.chr(10));
	}
	function log($str) {
		if(!is_dir('./storage')) mkdir('./storage', 0755);
		if(!is_dir('./storage/logs')) mkdir('./storage/logs', 0755);
		if(!is_dir('./storage/logs/~debug')) mkdir('./storage/logs/~debug', 0755);
		$ol = @file_get_contents('./storage/logs/~debug/'.$this->session.'.txt');
		file_put_contents('./storage/logs/~debug/'.$this->session.'.txt', $ol.$str);
	}
}

?>