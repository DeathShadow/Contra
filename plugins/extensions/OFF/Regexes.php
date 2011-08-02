<?php

	/*
	*	Regexes module version 1.
	*	Made for Contra v3.
	*	Created by photofroggy.
	*	
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*/

class Regexes extends extension {
	public $name = 'Regexes';
	public $version = 1;
	public $about = 'Tests regular expressions.';
	public $status = true;
	public $author = 'photofroggy';
	
	function init() {
		$this->addCmd('re', 'c_re', 25);
	}
	
	function c_re($ns, $from, $message, $target) {
		if(strtolower($ns) != 'chat:webdevelopment') return;
		$dAmn = $this->dAmn;
		$user = $this->user;
		if(strtolower(args($message, 1)) == 'all') {
			$sub = args($message, 2, True);
			$func = 'preg_match_all';
		} else {
			$sub = args($message, 1, True);
			$func = 'preg_match';
		}
		if(empty($sub))
			return $this->dAmn->say($ns, $from.': You must give an expression and subject to search.');
		$split = explode('////', $sub);
		if(!isset($split[0]) || !isset($split[1]))
			return $this->dAmn->say($ns, $from.': You must give an expression and subject to search.');
		$func($split[0], $split[1], $match);
		$this->dAmn->say($ns, '<bcode>'.var_export($match, true));
	}
}

new Regexes($core);

?>