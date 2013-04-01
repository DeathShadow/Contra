<?php

	/*
	*	Extension class for Contra. By photofroggy.
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*	This is essentially the module API
	*	for Contra. It is made in an effort
	*	to make developing modules as simple
	*	as possible while providing useful
	*	features for command and event
	*	management. Alot of the stuff is
	*	is just an interface between this class
	*	and the events class though.
	*/

define('EXT_CUSTOM',0);
define('EXT_SYSTEM',1);
define('EXT_LIBRARY',2);

class extension {
	// These are variables to store parts of the core.
	public $Bot = Null;
	public $Console = Null;
	public $dAmn = Null;
	public $Timer = Null;
	public $user = Null;

	// Module properties!
	public $name = Null;
	public $type = EXT_CUSTOM; // Loldefault.
	public $status = false;
	public $about = Null;
	public $author = Null;
	public $version = Null;
	public $loading = Null;
	public $evts = array();
	public $bds = array();
	public $cmds = array();

	final function __construct($Bot) {
		// The core itself.
		$this->Bot = $Bot;
		// The Bot's console!
		$this->Console = $Bot->Console;
		// Our dAmn API!
		$this->dAmn = $Bot->dAmn;
		// Contra's very own timer class!
		$this->Timer = $Bot->Timer;
		// And we also have a user class for user access management.
		$this->user = $Bot->user;
		// Now we're loading the module!
		$this->loading = true;
		// Call the init method so the module can be configured properly.
		$this->init();
		// Call the load method to verify the load.
		$this->load();
		// Unset the loading because we're not loading now...
		unset($this->loading);
	}

	function init() {}
	final function hook($meth, $event) {
		if (is_callable($meth) || method_exists($this, $meth)) {
			$hook = $this->Bot->Events->hook($this->name, $meth, $event);
			if (isset($this->loading)) {
				$this->evts[$event][] = $meth;
			}
			return $hook;
		} else {
			return;
		}
	}

	final function hookOnce($meth, $event) {
		if (is_callable($meth) || method_exists($this, $meth)) {
			return $this->Bot->Events->hookOnce($this->name, $meth, $event);
		} else {
			return;
		}
	}

	final function unhook($meth, $event) {
		return $this->Bot->Events->unhook($this->name, $meth, $event);
	}

	final function hookBDS($meth, $path) {
		if (is_callable($meth) || method_exists($this, $meth)) {
			$hook = $this->Bot->Events->hookBDS($this->name, $meth, $path);
			if (isset($this->loading)) {
				$this->bds[$path][] = $meth;
			}
			return $hook;
		} else {
			return;
		}
	}
	
	final function hookOnceBDS($meth, $path) {
		if (is_callable($meth) || method_exists($this, $meth)) {
			return $this->Bot->Events->hookOnceBDS($this->name, $meth, $path);
		} else {
			return;
		}
	}

	final function unhookBDS($meth, $path) {
		return $this->Bot->Events->unhookBDS($this->name, $meth, $path);
	}

	final function addCmd($cmd, $meth, $p = 25, $s = true) {
		if (is_array($cmd)) {
			foreach ($cmd as $id => $scmd) {
				$this->addCmd($scmd, $meth, $p, $s);
			}
			return;
		}
		if (!method_exists($this, $meth)) {
			return;
		}
		$this->Bot->Events->add_command($this->name, $cmd, $meth, $p, $s);
		if (isset($this->loading)) {
			$this->cmds[] = $cmd;
		}
	}

	final function cmdHelp($cmd, $str) {
		if (is_array($cmd)) {
			foreach ($cmd as $id => $scmd) {
				$this->cmdHelp($scmd, $str);
			}
			return;
		}
		$this->Bot->Events->cmdHelp($this->name, $cmd, $str);
	}

	final function switchCmd($cmd, $s = true) {
		return $this->Bot->Events->switchCmd($cmd, $s);
	}
	final function load() {
		global $INC_FILE, $INC_DIR;
		$file = $INC_DIR.'/'.$INC_FILE;
		if ($this->name !== Null) {
			if ($this->author !== Null) {
				if ($this->type != EXT_CUSTOM && $this->type != EXT_SYSTEM && $this->type != EXT_LIBRARY) {
					$this->type = EXT_CUSTOM;
				}
				if (!array_key_exists($this->name, $this->Bot->mod)) {
					$this->Bot->mod[$this->name] = $this;
					unset($this->evts, $this->cmds);
					if (DEBUG) {
						$this->Console->Notice('Loaded Module '.$this->name.'.');
					}
					return;
					// eh... Houston, we have a problem!
				} else {
					$this->Console->Warning($file.' tried loading '.$this->name.', which is already loaded.');
				}
			} else {
				$this->Console->Warning($file.' tried loading a module without an author.');
			}
		} else {
			$this->Console->Warning('No name was provided for a loading module in '.$file.', ignoring.');
		}
		if (!empty($this->evts)) {
			foreach ($this->evts as $event => $meths) {
				foreach ($meths as $meth) {
					$this->unhook($event);
				}
			}
		}
		if(!empty($this->cmds)) {
			foreach ($this->cmds as $cmd) {
				$this->Bot->Events->delCmd($this->name, $cmd);
			}
		}
	}
	// The methods below are ugly fuckers :D
	final protected function Read($file, $format = 0) {
		if (!is_dir('./storage')) {
			mkdir('./storage', 0755);
		}
		if (!is_dir('./storage/mod')) {
			mkdir('./storage/mod', 0755);
		}
		if (!is_dir('./storage/mod/'.$this->name)) {
			mkdir('./storage/mod/'.$this->name, 0755);
		}
		$file = strtolower($file);
		if (!file_exists('./storage/mod/'.$this->name.'/'.$file.'.bsv')) {
			return false;
		}
		switch ($format) {
			case 2:
				return include './storage/mod/'.$this->name.'/'.$file.'.bsv';
				break;
			case 1:
				return file_get_contents('./storage/mod/'.$this->name.'/'.$file.'.bsv');
				break;
			case 0:
			default:
				return unserialize(file_get_contents('./storage/mod/'.$this->name.'/'.$file.'.bsv'));
				break;
		}
	}

	final protected function Write($file, $data, $format = 0) {
		if (!is_dir('./storage')) {
			mkdir('./storage', 0755);
		}
		if (!is_dir('./storage/mod')) {
			mkdir('./storage/mod', 0755);
		}
		if (!is_dir('./storage/mod/'.$this->name)) {
			mkdir('./storage/mod/'.$this->name, 0755);
		}
		$file = strtolower($file);
		switch ($format) {
			case 2:
				save_config('./storage/mod/'.$this->name.'/'.$file.'.bsv', $data);
				break;
			case 1:
				file_put_contents('./storage/mod/'.$this->name.'/'.$file.'.bsv', $data);
				break;
			case 0:
			default:
				file_put_contents('./storage/mod/'.$this->name.'/'.$file.'.bsv', serialize($data));
				break;
		}
	}

	final protected function Unlink($file) {
		if (!is_dir('./storage')) {
			mkdir('./storage', 0755);
		}
		if (!is_dir('./storage/mod')) {
			mkdir('./storage/mod', 0755);
		}
		if (!is_dir('./storage/mod/'.$this->name)) {
			mkdir('./storage/mod/'.$this->name, 0755);
		}
		$file = strtolower($file);
		if (!file_exists('./storage/mod/'.$this->name.'/'.$file.'.bsv')) {
			return;
		}
		unlink('./storage/mod/'.$this->name.'/'.$file.'.bsv');
	}
}

?>