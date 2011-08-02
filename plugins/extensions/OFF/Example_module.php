<?php

	/*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*	
	*	
	*	Here we have an example of a Contra extension!
	*	Every extension is a class, and the class has to be
	*	an extension of the extension class, or it won't work
	*	properly.
	*/

class Example extends extension {
	/*
	*	The first thing you need to do in your extension's class
	*	is define the extension information. I guess you could
	*	call this the meta data of the extension.
	*	
	*	The information of the extension is contained inside 
	*	class variables. These class variables have to be public,
	*	because this is what they are predefined as.
	*/
	
	public $name = 'Example';				// name - This is the name of the extension!
	public $version = 1;					// version - The version number! You can have this as a string.
	public $about = 'Example extension.';	// about - Brief information about your extension.
	public $status = false;					// status - Whether the extension if on or off as default. true = on, false = off. Default is true.
	public $author = 'photofroggy';			// author - The person who made the extension. Usually you.

	/// type - This defines the type. This is optional and defaults to the option here, and you can choose from 3 types.
	public $type = EXT_CUSTOM;
	
	/*
	*	EXTENSION TYPES.
	*	
	*	The three constants are
	*	
	*		EXT_CUSTOM - Custom extension, and the default type.
	*		EXT_SYSTEM - System extension. These can't be turned off.
	*		EXT_LIBRARY - Library. Libraries can't be turned off and they are hidden.
	*/
	
	function init() {
		/*
		*	Initialise! Here we have the init method! You don't need this but
		*	you should use it to configure commands for the extension and to
		*	hook events to methods. Here I'm only going to show how to hook
		*	a command.
		*/
	
		$this->addCmd('example', 'c_example'); // This adds the command "example", and it will run the method "c_example".
		$this->cmdHelp('example', 'Just an example of how commands work.'); // Just add a bit of information about the command
	}
	
	function c_example($ns, $from, $message, $target) {
		/*
		*	This is the method used by the command "example". Command
		*	methods always have the same parameters, as shown here.
		*	$ns refers to the channel the command was received from. $from
		*	is the person who triggered the command. $message is the message
		*	received, with the bot's trigger character removed. $target is the
		*	target channel.
		*	
		*	For the example command we are just going to display a message on dAmn.
		*/
		$this->dAmn->say($ns, $from.': This is an example!');
		/* 
		*	The syntax might seem odd, but the dAmn object is stored inside this class as
		*	a class variable, so it all makes sense really. Contra does use dAmnPHP, but saying
		*	that is probably pointless as there is currently no documentation for dAmnPHP
		*	available.
		*/
	}
}

	/*
	*	Now that we have constructed our very simple example extension, we
	*	need to load it into the bot. All you have to do to load the extension is
	*	instantiate it. Don't worry about storing it anywhere, this will be done
	*	automatically if everything has been done correctly.
	*/
	new Example($core);
	/*
	*	So there you have it! This is how Contra extensions work for the most
	*	part. I might wright a bit on hooking events later.
	*/

?>