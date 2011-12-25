<?php

	/*
	*	Bot class v3
	*	Made for Contra by photofroggy
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*	This class acts as a platform for the
	*	whole bot to launch itself from. It loads
	*	the required objects and the configurations.
	*	Editing this file is not recommended, and
	*	should never be required. If you know what
	*	you're doing, however, then go ahead.
	*/

class Bot {
	public $start;
	public $info = array(
		'name' => 'Contra',
		'version' => '5.4.3',
		'status' => '',
		'release' => 'public',
		'author' => 'photofroggy',
		'bdsversion' => '0.3',
	);
	public $username;
	protected $_password;
	public $owner;
	public $trigger;
	public $aboutStr;
	public $autojoin;
        public $session;
	public $cookie;
	public $usingStored = false;
	public $Console;
	public $sysString;
	public $dAmn;
	public $Events;
	public $Timer;
	public $running = false;
	public $user;
	public $mod = array();
	public $shutdownStr = array(
		'Bot has quit.',
		'Bye bye!'
	);

	function __construct() {

		// Store the core class as a global variable during startup so modules can hook to it.
		global $Bot;
		$Bot = $this;
		// Generate a session ID code.
		if(DEBUG) $this->session = sha1(microtime());
		// Our start time is here.
		$this->start = time();
		// System information string.
		$this->sysString = php_uname('s').' '.php_uname('r').' '.php_uname('v');

		if(strstr($this->sysString, 'NT 6.1')) $this->sysString = 'Windows 7';
		elseif(strstr($this->sysString, 'NT 6.0')) $this->sysString = 'Windows Vista';
		elseif(strstr($this->sysString, 'NT 5.2')) $this->sysString = 'Windows 2003';
		elseif(strstr($this->sysString, 'NT 5.1')) $this->sysString = 'Windows XP';
		elseif(strstr($this->sysString, 'NT 5.0')) $this->sysString = 'Windows 2000';
		elseif(strstr($this->sysString, 'NT 4.9')) $this->sysString = 'Windows ME';

		// Get a new console interface.
		$this->Console = new Console();
		// Tell the console the session code for logging purposes.
		if(DEBUG) $this->Console->session = $this->session;
		// Get a new timer class.
		$this->Timer = new Timer($this);
		// Some introduction messages! We've already done quite a bit but only introduce things here...
		$this->Console->Notice('Hey thar!');
		$this->Console->Notice('Loading '.$this->info['name'].' '.$this->info['version'].' '.$this->info['status'].' by '.$this->info['author']);
		if(DEBUG) {
			// This is for when we're running in debug mode.
			$this->Console->Notice('Running in debug mode!');
			$this->Console->Notice('Session ID: '.$this->session);
		}
		if(DEBUG) $this->Console->Notice('Loading bot config file.');
		// Loading config file...
		$this->load_config();
		if(DEBUG) $this->Console->Notice('Config loaded, loading events system.');
		// Now we load our events system.
		$this->Events = new Event_System($this);
		if(DEBUG) $this->Console->Notice('Events system loaded.');
		$client =  $this->info['name'].' '.$this->info['version'].$this->info['status'].'/'.$this->info['release'];
		if(DEBUG) $this->Console->Notice('Loading dAmnPHP, client string is "'.$client.'"');
		// Load our dAmn interface.
		$this->dAmn = new dAmnPHP;
		// Give the interface a client string.
		$this->dAmn->Client = $client;
		// And an Agent string.
		$this->dAmn->Agent = 'PHP/'.PHP_VERSION.' ('.(substr(PHP_OS,-2)=='NT'?'Windows': PHP_OS).'; U; ';
		$this->dAmn->Agent.= php_uname('s').' '.php_uname('r').'; en-GB; '.$this->owner.') ';
		$this->dAmn->Agent.= 'dAmnPHP/'.$this->dAmn->Ver;
		$this->dAmn->Agent.= ' Contra/'.$this->info['version'].'/'.$this->info['release'];
		if(DEBUG) $this->Console->Notice('Loaded dAmnPHP, loading startup scripts.');
		// Include our startup scripts.
		inc_files('./plugins/startup', 'php', array('core'=>$this));
		if(DEBUG) $this->Console->Notice('Loaded startup scripts, loading internal user access levels.');
		// Load our user level system! It's a bit late to load it here...
		$this->user = new User_System($this->owner, $this);
		if(DEBUG) $this->Console->Notice('Loaded internal user access levels, loading modules.');
		// Now we load our modules.
		$this->Events->load_mods();
		if(DEBUG) $this->Console->Notice('Loaded '.count($this->mod).' modules with '.count($this->Events->events['cmd']).' commands.');
		// Because all modules have been loaded, we don't need the core class stored in globals, so we delete it.
		unset($GLOBALS['Bot']);
		// So, now we're ready to get some work done!
		$this->Console->Notice('Ready!');
		$this->Events->trigger('startup');
		$this->network();
		if($this->running===true) { $this->run(); }
		else {
			// Looks like we failed lads.
			$this->Console->Warning('Failed to start properly.');
			$this->Console->Warning('Exiting...');
		}
	}

	function load_config() {
		$config = include './storage/config.cf';
		$this->username = $config['info']['username'];
		$this->_password = $config['info']['password'];
		$this->owner = $config['info']['owner'];
		$this->trigger = $config['info']['trigger'];
		$this->aboutStr = $config['about'];
		$this->autojoin = $config['autojoin'];
		$this->cookie = empty($config['cookie']) ? '' : unserialize($config['cookie']);
	}

	function save_config() {
		$config = array(
			'info' => array(
				'username' => $this->username,
				'password' => $this->_password,
				'trigger' => $this->trigger,
				'owner' => $this->owner,
			),
			'about' => $this->aboutStr,
			'autojoin' => $this->autojoin,
			'cookie' => empty($this->cookie) ? '' : serialize($this->cookie),
		);
		save_config('./storage/config.cf', $config);
	}

	function network($sec = false) {
		if(empty($this->username) || empty($this->_password)) $this->load_config();
		$this->Console->Notice(($sec === false ? 'Starting' : 'Restarting').' dAmn.');
		if(!$this->cookie) {
			$this->Console->Notice('Retrieving cookie. This may take a while...');
                        $this->session = $this->dAmn->getCookie($this->username, $this->_password);
		} else {
			$this->Console->Notice('Using stored cookie first...');
			$this->usingStored = true;
			$this->session = array('status' => 1, 'cookie' => $this->cookie);
		}
		$this->Events->trigger('cookie', $this->session);
	}

	function run() {
		while($this->running === true) {
			$this->Events->trigger('loop');
			$this->Timer->triggerEvents();
			usleep(10000);
		}
		foreach($this->shutdownStr as $id => $string)
			$this->Console->Notice($string);
	}
}

?>