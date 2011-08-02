<?php

	/*
	*	Contra's Extra Functions.
	*	Created by photofroggy
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*	This file stores most of the functions which
	*	are not contained within a class, and are quite
	*	generic.
	*/

	function array_del_key($array, $key) {
		if(array_key_exists($key, $array)) array_splice($array, $key, 1);
		return $array;
	}

	function cmd_in($pre = '', $accept_empty = false) {
	/*
	*		This is a very simple function which is just used
	*		to get input from the command line. The input
	*		has to be at least 1 character long, otherwise
	*		you will be stuck in this loop forever!
	*/

		while(1) {
			echo $pre;
			$str = trim(fgets(STDIN));
			if(strlen($str) > 0) return $str;
			if($accept_empty === true && strlen($str) === 0) return false;
		}
	}

	function cmd_get_args($disp, $args, $hide = false) {
	/*
	*		Here we have the stuff which gets specific arguments
	*		returned according to the input given via the command
	*		line. It's quite simple really.
	*/

		while(1) {
			$str = cmd_in('> '.($disp !== 0 ? $disp.': ' : ''));
			if($args === Null && strlen($str) > 0) return $str;
			$str = strtolower($str);
			if(is_array($args) && array_key_exists($str, $args))
				return $args[$str];
		}
	}

	function main_menu($opt = false) {
		// This is the main menu! The menu is actually skipped if $opt is not false...
		if($opt === false) {
			echo '=====> Main Menu <====='.chr(10);
			echo '>>> Select an option:'.chr(10);
			echo '>> Bot - run the bot.'.chr(10);
			echo '>> Debug - run the bot in debug mode.'.chr(10);
			echo '>> Config - edit the bot config.'.chr(10);
			echo '>> Exit - Exit the program.'.chr(10);
			echo '>> PHP - Builds the php.ini file.'.chr(10);
			$select = trim(fgets(STDIN));
		} else { $select = str_replace('--','',$opt); }
		switch(strtolower(trim($select))) {
			case 'php':
				if(!defined('RESTARTABLE')) define('RESTARTABLE', true);
				build_ini();
				break;
			case 'bot':
			case 'debug':
				define('DEBUG', (strtolower(trim($select)) == 'debug' ? true : false));
				if(!defined('RESTARTABLE')) define('RESTARTABLE', true);
				new Bot;
				break;
			case 'config':
				if(!defined('RESTARTABLE')) define('RESTARTABLE', true);
				config();
				break;
			case 'menu':
				if(!defined('RESTARTABLE')) define('RESTARTABLE', false);
				main_menu(false);
				break;
			case 'exit': default:
				break;
		}
	}

	/*
	*	Function humanList takes
	*	a list (stored as an array)
	*	and produces a grammatically
	*	accurate string for the list!
	*/

	function humanList($data) {
		if(!is_array($data)) return $data;
		if(empty($data)) return false;
		$len = count($data);
		if($len == 1) return $data[0];
		if($len == 2) return implode(' and ', $data);
		$last = array_pop($data);
		return implode(', ', $data).' and '.$last;
	}

        // This is a simple function to return a time length as a properly formatted string! Grammar Nazi ftw!
        function time_length($time) {
	        // We need to reverse the number if it is a negative value.
	        $time = ($time < 0 ? $time * -1 : $time);
	        // The array below stores the formats that will be used. Apparently these are the right values...
	        $formats=array(
		        'year' => 31556926,
		        'week' => 604800,
		        'day' => 86400,
		        'hour' => 3600,
		        'minute' => 60,
		        'second' => 1
	        );
	        $ntimes	= array(); // This array will hold the formatted time!
	        // The loop below sorts everything out.
	        foreach($formats as $name => $format){
		        if($time>=$format){
			        $num = floor($time / $format); // Ok, this actually formats the seconds.
			        $time = $time % $format; // This takes away the seconds we have used/don't need
			        $ntimes[$name] = $num >= 1 ? intVal($num) : false; // Save the value! Yeah!
		        }
	        }
	        // The below array stores the times with the measurements. (1 year etc.)
	        $times = array();
	        // Ok, so we need to loop through the new times and determine which ones to store, and whether it's plural or not.
        	foreach($ntimes as $name => $len)
		        if($len > 0) $times[] = $len.' '.$name.($len == 1 ? '' : 's');
	        // Finally is the simple task of returning a correctly formatted string!
	        return humanList($times);
        }

	function config() {
		echo '> Please enter the following information.'.chr(10);
		$config = array(
			'about' => '<b>%N% %V%%S%</b> (%R% release)<br/><sub><b>Author:</b> :dev%A%:<b>; Owner:</b> :dev%O%:<b>;</b><br/><b>%D%</b></sub>',
			'autojoin' => array()
		);
		foreach(array('username' => Null, 'password' => Null, 'trigger' => Null, 'owner' => Null) as $part => $s)
			$config['info'][$part] = cmd_get_args('Bot '.$part, $s, ($part=='password'?true:false));
		echo '> Which channels would you like your bot to join? Separate with commas.'.chr(10);
		$rooms = explode(',',cmd_in('> ', true));
		foreach($rooms as $id => $room)
			if(strlen(trim($room)) > 0) $config['autojoin'][] = trim($room);
		if(empty($config['autojoin']))
			$config['autojoin'] = array('#Botdom');
		$config = array(
			'info' => $config['info'],
			'about' => $config['about'],
			'autojoin' => $config['autojoin'],
			'cookie' => '',
		);
		save_config('./storage/config.cf', $config);
		echo '> Configuration saved!'.chr(10);
	}

	function save_config($file, $data) { file_put_contents($file, "<?php\n\nreturn ".var_export($data, true).";\n\n?>"); }
	function args($string, $index, $cont = false, $sep = ' ') {
		if(empty($string)) return '';
		if($index != 0 ) {
			for($i=1;$i<=$index;$i++) {
				if(strpos($string,$sep)!==false) $string = substr($string,strpos($string,$sep)+1);
				else return '';
			}
		}
		if($cont==true) return $string;
		if(strpos($string, $sep)) return substr($string, 0, strpos($string, $sep));
		return $string;
	}

	// Make sure we have the needed directories!
	if(!is_dir('./plugins'       	 ))   mkdir('./plugins'           , 0755);		// Need plugins directory.
	if(!is_dir('./plugins/extensions'))   mkdir('./plugins/extensions', 0755);		// 	Extensions directory.
	if(!is_dir('./plugins/startup'	 ))   mkdir('./plugins/startup'   , 0755);		// 	Startup directory.
	if(!is_dir('./storage'        	 ))   mkdir('./storage'           , 0755);		// Storage directory.
	if(!is_dir('./storage/logs'   	 ))   mkdir('./storage/logs'      , 0755);		// 	Logs directory.
	if(!is_dir('./storage/bat'    	 ))   mkdir('./storage/bat'       , 0755);		// 	Storage for bat files.
	// Own dis shit!
	chmod('.'					, 0755);
	chmod('./core'				, 0755);
	chmod('./plugins'			, 0755);
	chmod('./plugins/extensions', 0755);
	chmod('./plugins/startup'	, 0755);
	chmod('./storage'			, 0755);
	chmod('./storage/logs'		, 0755);
	chmod('./storage/bat'		, 0755);

	function build_ini(){
		//  set the path, if it isn't already.
		echo '** Please enter the path to your php folder. If it is C:/php/ then you can leave it blank.',chr(10);
		$path = trim(fgets(STDIN));
		if(PHP_OS == 'WINNT')
			if(empty($path)) $path = '/php/';

		// copy the php.ini-production file
		if(!file_exists($path.'php.ini')){
			echo '** Creating the php.ini file...',chr(10);
			$file = file_get_contents($path.'php.ini-production');

			// create the php.ini file
			$handle = fopen($path.'php.ini', 'w+');

			// now write what we copied to php.ini
			fwrite($handle, $file);

			// we don't need to write anything else, so lets close that file
			fclose($handle);
			echo 'The php.ini file has been created!', chr(10);
		} else echo '** php.ini already exists!',chr(10);

		if(PHP_OS == 'WINNT'){
			echo 'Setting extension path...', chr(10);
			$ext = set_ext_dir($path);
			if($ext) echo '** Extension path set!', chr(10);
			else echo '** Extension path could not be set! :c', chr(10);

			// first is the openssl extension
			echo '** Loading openssl..',chr(10);
			if(!extension_loaded('openssl')){
				load_ext('openssl',  $path);
				echo '** Openssl has been loaded!', chr(10);
			} else echo '** Openssl is already loaded!', chr(10);

			// then its the sockets extension
			echo '** Loading sockets...',chr(10);
			if(!extension_loaded('sockets')){
				load_ext('sockets', $path);
				echo 'sockets has been loaded!', chr(10);
			} else echo '** Sockets is already loaded!',chr(10);
		}

		//setting timezone
		echo '** Do you live in the New York timezone? yes/no (if you leave it blank, or type anything other than yes,its an assumed no)',chr(10);
		$ans = trim(fgets(STDIN));
		if($ans == strtolower('yes')){
			echo '** the php.ini file has been constructed!',chr(10);
			timezone("America/New_York", $path);
		} else {
			echo '** Please enter your timezone, or press enter if you wish to leave it as is. Example: America/New_York',chr(10);
			$tz = trim(fgets(STDIN));
			if(empty($tz))
				echo '** the php.ini file has been constructed!',chr(10);
			else {
				timezone($tz, $path);
				echo 'the php.ini file has been constructed!',chr(10);
			}
		}
	}

	function load_ext($ext, $path){
		if(empty($path)) $path = '/php/';
		if(!extension_loaded($ext)){
			$file = file_get_contents($path.'php.ini');
			$file = str_replace(';extension=php_'.$ext.'.dll', 'extension=php_'.$ext.'.dll', $file);
			file_put_contents($path.'php.ini', $file);
		}
	}
	function timezone($tz, $path){
		if(empty($path)) $path = '/php/';
		if(empty($tz)) $tz = "America/New_York";
		$file = file_get_contents($path.'php.ini');
		$look = strpos($file, ';date.timezone');
		$look2 = strpos($file, 'date.timezone');
		if($look !== FALSE){
			$file = str_replace(';date.timezone =', 'date.timezone = "'.$tz.'"', $file);
			file_put_contents($path.'php.ini', $file);
		}
		if(preg_match('/date.timezone = "(.*)"/', $file) && $look2 !== FALSE) {
			$file = preg_replace('/date.timezone = "(.*)"/', 'date.timezone = "'.$tz.'"', $file);
			file_put_contents($path.'php.ini', $file);
		}
	}
	function set_ext_dir($path){
		if(empty($path)) $path = '/php/';
		$file = file_get_contents($path.'php.ini');
		if(PHP_OS == 'WINNT'){
			$look = strpos($file, '; extension_dir = "ext"');
			if($look !== FALSE){
				$file = str_replace('; extension_dir = "ext"', 'extension_dir = "ext"', $file);
				file_put_contents($path.'php.ini', $file);
				return true;
			} else return false;
		} else {
			$look = strpos($file, '; extension_dir = "./"');
			if($look !== FALSE){
				$file = str_replace('; extension_dir = "./"', 'extension_dir = "./"', $file);
				file_put_contents($path.'php.ini', $file);
				return true;
			} else return false;
		}
	}

	/*
	*	BOTDOM SPROCKET TITLE CODE
	*
	*	This is here as a tribute to the sprocket :(
	*
	*	:shakefist: <b>#BOTDOM</b>&nbsp;<sup><sup>HOME</sup> <b>OF</b> <sub>THE</sub><sup>SPRO</sup><b>C</b><sup><b>K</b><sup><b>E</b><sup><b>T</b></sup></sup></sup></sup> :shakefist:
	*/

?>