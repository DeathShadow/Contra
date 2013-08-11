<?php

	/*
	*	Main run file for Contra. Made by photofroggy.
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*	This bot was started on the 15th July 2008
	*	and is designed to be lightweight and easy
	*	to install, configure and run!
	*
	*	Contra is also intended to be a full replacement
	*	for Amphino, as the code is much cleaner
	*	and far more efficient.
	*/

	// Make sure we're in the right dir.
	chdir(dirname(realpath(__FILE__)));
	// Get rid of this file... it helps...
	if (file_exists('./storage/bat/restart.bcd')) {
		unlink('./storage/bat/restart.bcd' );
	}
	// Move oauth.json from main folder to storage folder.
	if (file_exists('oauth.json')) {
		rename('oauth.json', './storage/oauth.json');
	}

	// This functions contains a loop to include files from a directory.
	function inc_files($dir, $ext = false, $vars = array()) {
		$files = scandir($dir);
		global $INC_FILE, $INC_DIR; // I don't like using globals, no in the slightest, but sometimes you do need them.
		$INC_DIR = $dir;
		extract($vars, EXTR_PREFIX_SAME, 'inc_');
		foreach ($files as $file) {
			$INC_FILE = $file;
			if ($file != '.' && $file != '..' && $file[strlen($file)-1] !== '~' && is_file($dir.'/'.$file)) {
				if ($ext === false || strtolower(substr($file, -(strlen($ext)))) == strtolower($ext)) {
					include $dir.'/'.$file;
				}
			}
		}
		unset($GLOBALS['INC_DIR'], $GLOBALS['INC_FILE']);
	}

	// Include the core files!
	inc_files('./core');

	// Make sure we have a config file!
	if (!file_exists('./storage/config.cf')) {
		echo '> You don\'t have a config file! Creating a new one...', chr(10);
		config();
		if (isset($argv[1])) {
			if (strtolower($argv[1])=='--config') {
				die();
			}
		}
	}

	// Now we can launch the main menu! Or whatever has been selected by the batch file...
	main_menu(strtolower(trim(isset($argv[1]) ? $argv[1] : '--bot')));

?>