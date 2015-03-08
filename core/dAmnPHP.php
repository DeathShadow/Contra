<?php

	/*
	*       dAmnPHP version 5 by photofroggy.
	*
	*       Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*       and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*       This class handles dAmn sockets and reads data from a dAmn connection. Can be
	*       used with any PHP client. I guess this is almost an unintended implementation of the
	*       dAmnSock specification proposed by Zeros-Elipticus, or as close as I will willingly
	*       come to it.
	*
	*       To create a new instance of the class simply use $variable = new dAmnPHP;
	*
	*       To get a cookie you need to use $dAmn->getCookie($username, $password);
	*       and store the cookie in $dAmn->cookie;. If you don't do this then you won't
	*       be able to get connected to dAmn or any chat network.
	*
	*       To be able to actually get logged into deviantART and connected to dAmn you
	*       need to set some variables from outside the class.
	*
	*       EXAMPLE:
	*               $dAmn->Client = 'dAmnPHP/public/3';
	*               $dAmn->owner = 'photofroggy';
	*               $dAmn->trigger = '!';
	*
	*       Now when you use $dAmn->connect();, that info will be sent in the handshake!
	*
	*       Use $dAmn->read(); to read data from the socket. If packets are received
	*       then the packets are returned in an array. If nothing is really happening
	*       on the socket then false is returned.
	*/

	// Before anything happens, we need to make sure OpenSSL is loaded. If not, kill the program!
	if (!extension_loaded('OpenSSL')) {
		echo '>> WARNING: You don\'t have OpenSSL loaded!',chr(10);
		if (PHP_OS == 'WIN32' || PHP_OS == 'WINNT' || PHP_OS == 'Windows') {
			echo '>> Re-read the Install PHP guide @ http://botdom.com/documentation/Install_PHP_on_Windows',chr(10),'>> ';
		}
		if (PHP_OS == 'Linux') {
			echo '>> Re-read the Install PHP guide @ http://botdom.com/documentation/Install_PHP_on_Linux',chr(10),'>> ';
		}
		if (PHP_OS == 'Darwin') {
			echo '>> Re-read the Install PHP guide @ http://botdom.com/documentation/Install_PHP_on_Mac_OS_X',chr(10),'>> ';
		}
		for ($i = 0;$i < 3; ++$i) {
			sleep(1);
			echo '.';
		}
		echo chr(10);
		sleep(1);
		exit();
	}
	// Also make sure date.timezone is set. If not, kill the program, unless it's OSX, then skip the check.
	if (!ini_get('date.timezone') && PHP_OS != 'Darwin') {
		echo '>> WARNING: You didn\'t setup php properly. (date.timezone is not set)',chr(10);
		if (PHP_OS == 'WIN32' || PHP_OS == 'WINNT' || PHP_OS == 'Windows') {
			echo '>> Re-read the Install PHP guide @ http://botdom.com/documentation/Install_PHP_on_Windows#Part_2 (Do/redo step 7-9)',chr(10),'>> ';
		}
		if (PHP_OS == 'Linux') {
			echo '>> Re-read the Install PHP guide @ http://botdom.com/documentation/Install_PHP_on_Linux#PHP_configure_setup (Do/redo step 2-4)',chr(10),'>> ';
		}
		for ($i = 0;$i < 3; ++$i) {
			sleep(1);
			echo '.';
		}
		echo chr(10);
		sleep(1);
		exit();
	}
	// Do a PHP version check. PHP under 5.4.x is no longer supported, kill the program if php is under 5.4.x.
	if (version_compare(phpversion(), '5.4.0', '<')) {
		echo '>> WARNING: PHP versions under 5.4.x is no longer supported. You MUST to upgrade your PHP to continue.',chr(10);
		if (PHP_OS == 'WIN32' || PHP_OS == 'WINNT' || PHP_OS == 'Windows') {
			echo '>> See install guide for latest PHP version. http://botdom.com/documentation/Install_PHP_on_Windows',chr(10),'>> ';
		}
		if (PHP_OS == 'Linux') {
			echo '>> If you\'re using distro\'s php and it\'s under 5.4.x, you must compile php from source yourself.',chr(10),'>> ';
		}
		sleep(1);
		exit();
	}
	// This is just a constant...
	define('LBR', chr(10)); // LineBReak

class dAmnPHP {
	public $Ver = 5;
	public $server = array(
		'chat' => array(
			'host' => 'chat.deviantart.com',
			'version' => '0.3',
			'port' => 3900,
		),
	);
	public $Client = 'dAmnPHP';
	public $Agent = 'dAmnPHP/5';
	public $owner = 'photofroggy';
	public $trigger = '!';
	public $socket = Null;
	public $connecting = Null;
	public $login = Null;
	public $connected = Null;
	public $close = Null;
	public $buffer = Null;
	public $chat = array();
	public $disconnects = 0;
	public $bytes_sent = 0;
	public $bytes_recv = 0;
	public $last_command = array();
	public $plc_enabled = false;
	private $plc_time = 0;		// Time since last reset.
	private $plc_time_limit = 1;	// Seconds between resets.
	private $plc_data_limit = 5;    // Packets allowed in the time limit.
	private $plc_count = array(
		'send'	=> 0,
		'join'	=> 0,
		'part'	=> 0); 		// Packets since last reset.

	static $tablumps = array(                       // Regex stuff for removing tablumps.
		'a1' => array(
			"&b\t",  "&/b\t",    "&i\t",    "&/i\t", "&u\t",   "&/u\t", "&s\t",   "&/s\t",    "&sup\t",    "&/sup\t", "&sub\t", "&/sub\t", "&code\t", "&/code\t",
			"&br\t", "&ul\t",    "&/ul\t",  "&ol\t", "&/ol\t", "&li\t", "&/li\t", "&bcode\t", "&/bcode\t",
			"&/a\t", "&/acro\t", "&/abbr\t", "&p\t", "&/p\t"
		),
		'a2' => array(
			"<b>",  "</b>",       "<i>",     "</i>", "<u>",   "</u>", "<s>",   "</s>",    "<sup>",    "</sup>", "<sub>", "</sub>", "<code>", "</code>",
			"\n",   "<ul>",       "</ul>",   "<ol>", "</ol>", "<li>", "</li>", "<bcode>", "</bcode>",
			"</a>", "</acronym>", "</abbr>", "<p>",  "</p>\n"
		),
		'b1' => array(
			"/&emote\t([^\t]+)\t([^\t]+)\t([^\t]+)\t([^\t]+)\t([^\t]+)\t/",
			"/&a\t([^\t]+)\t([^\t]*)\t/",
			"/&link\t([^\t]+)\t&\t/",
			"/&link\t([^\t]+)\t([^\t]+)\t&\t/",
			"/&dev\t[^\t]\t([^\t]+)\t/",
			"/&avatar\t(.*?)\t(.*?)\t/",
			"/&thumb\t([0-9]+)\t([^\t]+)\t([^\t]+)\t([^\t]+)\t([^\t]+)\t([^\t]+)\t/",
			"/&img\t([^\t]+)\t([^\t]*)\t([^\t]*)\t/",
			"/&iframe\t([^\t]+)\t([0-9%]*)\t([0-9%]*)\t&\/iframe\t/",
			"/&acro\t([^\t]+)\t/",
			"/&abbr\t([^\t]+)\t/"
		),
		'b2' => array(
			"\\1",
			"<a href=\"\\1\" title=\"\\2\">",
			"\\1",
			"\\1 (\\2)",
			":dev\\1:",
			":icon\\1:",
			":thumb\\1:",
			"<img src=\"\\1\" alt=\"\\2\" title=\"\\3\" />",
			"<iframe src=\"\\1\" width=\"\\2\" height=\"\\3\" />",
			"<acronym title=\"\\1\">",
			"<abbr title=\"\\1\">"
		),
	);
	public $njc = array(
		'chat:devart',
		'chat:devious',
		'chat:fella',
		'chat:help',
		'chat:mnadmin',
		'chat:idlerpg',
		'chat:irpg',
		'chat:trivia',
		'chat:photographers',
		'chat:daunderworldrpg',
		'chat:seniors',
		'chat:odysseyproject',
		'chat:communityrelations',
	);

	function Time($ts=false) {
		return date('H:i:s', ($ts===false?time():$ts));
	}
	function Clock($ts=false) {
		return '['.$this->Time($ts).']';
	}
	function Message($str = '', $ts = false) {
		echo $this->Clock($ts),' '.$str,chr(10);
	}
	function Notice($str = '', $ts = false)  {
		$this->Message('** '.$str,$ts);
	}
	function Warning($str = '', $ts = false) {
		$this->Message('>> '.$str,$ts);
	}

	// oAuth function, Modes are 0 = Silent, 1 = Echo
	public function oauth($mode, $refresh = false) {
		$this->client_id     = '24';
		$this->client_secret = 'b6c81c08563888f0da7ea3f7f763c426';
		$oauth_file          = './storage/oauth.json';

		// First off, check if the oAuth file exists and is available for reading.
		if (is_readable($oauth_file)) {

			// If we're not in silent mode.
			if ($mode == 0) {
				echo 'Grabbing existing oAuth tokens...' . LBR; // Turn off if silent
			}

			// Reading oauth file
			if (filesize($oauth_file) != 0) {
				$fh = fopen($oauth_file, 'r') or die('Failed to open oAuth file for reading.');

				// If we're not in silent mode.
				if ($mode == 0) {
					echo 'Tokens grabbed from file...' . LBR . LBR;
				}

				// Take the token(s) from the file and store them.
				$this->oauth_tokens = json_decode(fread($fh, filesize($oauth_file)));

				// Do we need a new token?
				if ($refresh) {

					// If we're not in silent mode.
					if ($mode == 0) {
						echo 'Refreshing Token' . LBR;
					}

					// Grab the JSON data from the server.
					$tokens = $this->socket('/oauth2/token?client_id='.$this->client_id.'&redirect_uri=https://damn.shadowkitsune.net/apicode/&grant_type=refresh_token&client_secret='.$this->client_secret.'&refresh_token='.$this->oauth_tokens->refresh_token);

					// Decode it and store it.
					$this->oauth_tokens = json_decode($tokens);

					// Check if the request was considered a success
					if ($this->oauth_tokens->status != 'success') {
						// Nope, something went wrong.
						if ($mode == 0) {
							echo $this->error('Something went wrong while trying to grab a token! Error: ' . $this->oauth_tokens->error_description) . LBR;
							echo 'Let\'s try and grab a new token...' . LBR;
						}
						if (file_exists($oauth_file)) {
							unlink($oauth_file);
						}
						$this->oauth(0, true);
					} else {
						// It was OK, let's store it.
						$fh = fopen($oauth_file, 'w') or die('Failed to open oAuth file for writing.');
						fwrite($fh, $tokens);
						fclose($fh);

						// If not in silent mode.
						if ($mode == 0) {
							echo 'We got a new token!' . LBR;
						}
					}
				} else {
					// If not in silent mode.
					if ($mode == 0) {
						echo 'Checking if tokens have expired...' . LBR;
					}

					// Place a placebo call to check if the token has expired.
					$placebo = json_decode($this->socket('/api/v1/oauth2/placebo?access_token='.$this->oauth_tokens->access_token));

					// Is the token OK?
					if ($placebo->status != 'success') {
						// Nope, it expired.
						if ($mode == 0) {
							echo $this->error('It appears that your token has expired! Let\'s grab a new one.') . LBR;
						}
						$this->oauth(0, true);
					} else {
						// We're done!
						if ($mode == 0) {
							echo 'We got a new token!' . LBR;
						}
						fclose($fh);
					}
				}
			} else {
				// We need a new token!
				if ($mode == 0) {
					echo $this->error('Your token file is empty, grabbing new tokens...') . LBR;
				}
				if (file_exists($oauth_file)) {
					unlink($oauth_file);
				}
				$this->oauth(0);
			}
		} else {
			// We need a token!
			if ($mode == 0) {
				echo 'Grabbing the oAuth Tokens from deviantART...' . LBR;
			}

			// Request that the user authorize the request.
			echo 'We need to authorize a new token. Log into your bot\'s account and then open this link in your web browser:' . LBR;
			echo 'https://bit.ly/1taqn2C' . LBR;

			// Retreiving the code
			echo 'Enter the code given by the above link:' . LBR;
			$code = trim(str_replace(' ', '', fgets(STDIN))); // STDIN for reading input

			// Getting the access token.
			$tokens = $this->socket('/oauth2/token?client_id='.$this->client_id.'&redirect_uri=https://damn.shadowkitsune.net/apicode/&grant_type=authorization_code&client_secret='.$this->client_secret.'&code='.$code);

			// Store the token(s)
			$this->oauth_tokens = json_decode($tokens);

			// Was it a success?
			if ($this->oauth_tokens->status != 'success') {
				echo $this->error('Something went wrong while trying to grab a token! Error: ' . $this->oauth_tokens->error_description) . LBR;
				echo 'Did you log into your bot\'s account and go to the link above?' . LBR;
				if (file_exists($oauth_file)) {
					unlink($oauth_file);
				}
				$this->oauth(0);
			} else {
				// Woo, got a token!
				$fh = fopen($oauth_file, 'w') or die('Failed to open the oAuth file for writing.');
				fwrite($fh, $tokens);
				fclose($fh);

				// If we're not in silent mode.
				if ($mode == 0) {
					echo 'We got a token!' . LBR;
				}
			}
		}
	}

	// dAmntoken function
	public function damntoken() {
		// Check if the oauth_tokens variable is set, if not set it.
		if (!isset($this->oauth_tokens)) {
			$this->oauth(0);
		}

		// Grab the damntoken and set it to damntoken variable
		$this->damntoken = json_decode($this->socket('/api/v1/oauth2/user/damntoken?access_token='.$this->oauth_tokens->access_token));
	}

	// Function to reuse the curl code.
	private function socket($url) {
		$fp = fsockopen('ssl://deviantart.com', 443, $errno, $errstr, 30);
		if (!$fp) {
		    echo "$errstr ($errno)<br />\n";
		} else {
		    $out = "GET ".$url." HTTP/1.1\r\n";
		    $out .= "Host: www.deviantart.com\r\n";
		    $out .= "Connection: Close\r\n\r\n";
		    fwrite($fp, $out);
		    while (!feof($fp)) {
		        $buffer = fgets($fp, 512);
		    }
		    fclose($fp);
		    return $buffer;
		}
	}

	private function error($text) {
		if (PHP_OS == 'Linux' || PHP_OS == 'Darwin') {
			echo " \033[1;33m" . $text . "\033[0m";
		} else {
			echo $text;
		}
	}

	function send_headers($socket, $host, $url, $referer, $post=null, $cookies=array())
	{
	    try
	    {
		$headers = '';
		if (isset($post)) {
			$headers .= "POST $url HTTP/1.1\r\n";
		} else {
			$headers .= "GET $url HTTP/1.1\r\n";
		}
		$headers .= "Host: $host\r\n";
		$headers .= 'User-Agent: '.$this->Agent."\r\n";
		$headers .= "Referer: $referer\r\n";
		if ($cookies != array()) {
			$headers .= 'Cookie: '.implode("; ", $cookies)."\r\n";
		}
		$headers .= "Connection: close\r\n";
		$headers .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*\/*;q=0.8\r\n";
		$headers .= "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n";
		$headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
		if (isset($post)) {
			$headers .= 'Content-Length: '.strlen($post)."\r\n\r\n$post";
		} else {
			$headers .= "\r\n";
		}
		$response = '';
		if (!$socket) return '';
		fputs($socket, $headers);
		while (!@feof ($socket)) {
			$response .= @fgets($socket, 8192);
		}
		return $response;
	    }
	    catch (Exception $e)
	    {
		echo 'Exception occured: '.$e->getMessage()."\n";
		return '';
	    }
	}

	public function connect() {                     // This method creates our dAmn connection!
		// First thing we do is create a socket stream using the server config data.
		$this->socket = @stream_socket_client('tcp://'.$this->server['chat']['host'].
		':'.$this->server['chat']['port']);
		// If we managed to open a connection, we need to do one or two things.
		if ($this->socket !== false) {
			// First we set the stream to non-blocking, so the bot doesn't pause when reading data.
			stream_set_blocking($this->socket, 0);
			// Now we make our handshake packet. Here we send information about the bot/client to the dAmn server.
			$data = 'dAmnClient '.$this->server['chat']['version'].LBR;
			$data.= 'agent='.$this->Agent.LBR;
			$data.= 'bot='.$this->Client.LBR;
			$data.= 'owner='.$this->owner.LBR;
			$data.= 'trigger='.$this->trigger.LBR;
			$data.= 'creator=photofroggy/froggywillneverdie@msn.com'.LBR.chr(0);
			// This is were we actually send the packet! Quite simple really.
			@stream_socket_sendto($this->socket, $data);
			// Now we have to raise a flag! This tells everything that we are currently trying to connect through a handshake!
			$this->connecting = true;

			// Finally, exit before this if case exits, so we can do the stuff that happens when the socket stream fails.
			return true;
		}
		// All we do here is display an error message and return false dawg.
		$this->Warning('Could not open connection with '.$this->server['chat']['host'].'.');
		return false;
	}

	function login($username, $authtoken) {         // Need to send a login packet? I'm your man!
		$this->login = ( $this->send("login $username\npk=$authtoken\n\0") ? true : true );
	}

	function deform_chat($chat, $discard=false) {
		if (substr($chat, 0, 5)=='chat:') {
			return '#'.str_replace('chat:','',$chat);
		}
		if (substr($chat, 0, 6)=='pchat:') {
			if ($discard===false) {
				return $chat;
			}
			$chat = str_replace('pchat:','',$chat);
			$chat1=substr($chat,0,strpos($chat,':'));
			$chat2=substr($chat,strpos($chat,':')+1);
			$mod = true;
			if (strtolower($chat1)==strtolower($discard)) {
				return '@'.$chat1;
			} else {
				return '@'.$chat2;
			}
		}
		return (substr($chat,0,1)=='#') ? $chat : (substr($chat, 0, 1)=='@' ? $chat : '#'.$chat);
	}

	function format_chat($chat, $chat2=false) {
		$chat = str_replace('#','',$chat);
		if ($chat2!=false) {
			$chat = str_replace('@','',$chat);
			$chat2 = str_replace('@','',$chat2);
			if (strtolower($chat)!=strtolower($chat2)) {
				$channel = 'pchat:';
				$users = array($chat, $chat2);
				sort($users);
				return $channel.$users[0].':'.$users[1];
			}
		}
		return (substr($chat, 0, 5)=='chat:') ? $chat : (substr($chat, 0, 6)=='pchat:' ? $chat : 'chat:'.$chat);
	}

	/*
	*               Lol, dAmn commands are below. Where you
	*               see the constant "LBR" is a LineBReak, which
	*               is the same as "\n", so if you're having trouble
	*               understanding the packets, just imagine that the
	*               "LBR"s are actually "\n"s, because they are...
	*/
	function join($channel) {
		if (in_array(strtolower($channel), $this->njc)) {
			return;
		}
		$this->send('join '.$channel.LBR);
	}
	function part($channel) {
		if (strtolower($channel) == 'chat:datashare') {
			return;
		}
		$this->send('part '.$channel.LBR);
	}
	function say($ns, $message, $DATASHARE = FALSE) {
		if (strtolower($ns) == 'chat:irpg' || strtolower($ns) == 'chat:dAmnIdlers') {
			return;
		}
		if (is_array($ns)) {
			// WE CAN SEND MESSAGES TO A PLETHORA OF CHANNELS!
			foreach ($ns as $var1 => $var2) {
				$this->say(((is_string($var1)) ? $var1 : $var2), $message);
			}
			return;
		}
		// The type of message is easily changeable.
		$type = (substr($message, 0, 4) == '/me ') ? 'action' : ((substr($message, 0, 7) == '/npmsg ') ? 'npmsg' : 'msg');
		$message = ($type == 'action') ? substr($message, 4) : (($type == 'npmsg') ? substr($message, 7) : $message );
		$message = is_array($message) ? $message = '<bcode>'.print_r($message, true) : $message;
		$message = str_replace('&lt;','<',$message);
		$message = str_replace('&gt;','>',$message);
		if (strtolower($ns) != 'chat:datashare' && strtolower($ns) != 'chat:dsgateway' || $DATASHARE == TRUE) {
			$this->send('send '.$ns.LBR.LBR.$type.' main'.LBR.LBR.$message);
		}
	}
	function action($ns, $message, $DATASHARE = FALSE) {
		if (strtolower($ns) == 'chat:irpg' || strtolower($ns) == 'chat:dAmnIdlers') {
			return;
		}
		if (strtolower($ns) != 'chat:datashare' && strtolower($ns) != 'chat:dsgateway') {
			$this->say($ns, '/me '.$message);
		} elseif ($DATASHARE == TRUE) {
			$this->say($ns, '/me '.$message, TRUE);
		}
	}
	function npmsg($ns, $message, $DATASHARE = FALSE) {
		if (strtolower($ns) == 'chat:irpg' || strtolower($ns) == 'chat:dAmnIdlers') {
			return;
		}
		if (strtolower($ns) != 'chat:datashare' && strtolower($ns) != 'chat:dsgateway') {
			$this->say($ns, '/npmsg '.$message);
		} elseif ($DATASHARE == TRUE) {
			$this->say($ns, '/npmsg '.$message, TRUE);
		}
	}
	function promote($ns, $user, $pc=false) {
		$this->send('send '.$ns.LBR.LBR.'promote '.$user.LBR.LBR.($pc!=false?$pc:''));
	}
	function demote($ns, $user, $pc=false) {
		$this->send('send '.$ns.LBR.LBR.'demote '.$user.LBR.LBR.($pc!=false?$pc:''));
	}
	function kick($ns, $user, $r=false) {
		$this->send('kick '.$ns.LBR.'u='.$user.LBR.($r!=false?"\n$r\n":''));
	}
	function ban($ns, $user) {
		$this->send('send '.$ns.LBR.LBR.'ban '.$user.LBR);
	}
	function unban($ns, $user) {
		$this->send('send '.$ns.LBR.LBR.'unban '.$user.LBR);
	}
	function get($ns, $property) {
		$this->send('get '.$ns.LBR.'p='.$property.LBR);
	}
	function set($ns, $property, $value) {
		$this->send('set '.$ns.LBR.'p='.$property.LBR.LBR.$value.LBR);
	}
	function admin($ns, $command) {
		$this->send('send '.$ns.LBR.LBR.'admin'.LBR.LBR.$command);
	}
	function disconnect() {
		$this->send('disconnect'.LBR);
	}
	// Here's the actual send function which sends the packets.
	function send($data) {
		if ($this->plc_enabled && strlen($data) > 4) {
			$ph = substr($data, 0, 4);
			if (array_key_exists($ph, $this->plc_count)) {
				$this->plc_count[$ph]++;
				if (microtime(true) - $this->plc_time >= $this->plc_time_limit) {
					foreach ($this->plc_count as $k => $v) {
						$this->plc_count[$k] = 0;
					}
					$this->plc_time = microtime(true);
				} elseif ($this->plc_count[$ph] >= $this->plc_data_limit) {
					return;
				}
			}
		}
		@stream_socket_sendto($this->socket, $data.chr(0));
		$this->bytes_sent += strlen($data) + 1;
	}
	// This is the important one. It reads packets off of the stream and returns them in an array! Numerically indexed.
	function read() {
		$s = array($this->socket); $w=Null;
		if (($s = @stream_select($s,$w,$w,0)) !== false) {
			if ($s === 0) {
				return false;
			}
			$data = @stream_socket_recvfrom($this->socket, 8192);
			if ($data !== false && $data !== '') {
				$this->buffer .= $data;
				$this->bytes_recv += strlen($data);
				$parts = explode(chr(0), $this->buffer);
				$this->buffer = ($parts[count($parts)-1] != '' ? $parts[count($parts)-1] : '');
				unset($parts[count($parts)-1]);
				if ($parts!==Null) {
					return $parts;
				}
				return false;
			} else {
				return array("disconnect\ne=socket closed\n\n");
			}
		} else {
			return array("disconnect\ne=socket error\n\n");
		}
	}

	function is_channel($ns) {
		foreach ($this->chat as $namespace => $data) {
			if (strtolower($namespace)==strtolower($ns)) {
				return $namespace;
			}
		}
		return false;
	}
}

function parse_tablumps($data) {
	$data = str_replace('chr(7)', '', $data);
	$data = str_replace(dAmnPHP::$tablumps['a1'], dAmnPHP::$tablumps['a2'], $data);
	$data = preg_replace(dAmnPHP::$tablumps['b1'], dAmnPHP::$tablumps['b2'], $data);
	$data = preg_replace('/<abbr title="colors:[A-F0-9]{6}:[A-F0-9]{6}"><\/abbr>/','', $data);
	return preg_replace('/<([^>]+) (width|height|title|alt)=""([^>]*?)>/', "<\\1\\3>", $data);
}

	/*
	*       Oh look! A packet parser! This may
	*       come in handy at a later point.
	*/
function parse_dAmn_packet($data, $sep = '=') {
	$data = parse_tablumps($data);

	$packet = array(
		'cmd' => Null,
		'param' => Null,
		'args' => array(),
		'body' => Null,
		'raw' => $data
	);
	if (stristr($data, "\n\n")) {
		$packet['body'] = trim(stristr($data, "\n\n"));
		$data = substr($data, 0, strpos($data, "\n\n"));
	}
	$data = explode("\n", $data);
	foreach ($data as $id => $str) {
		if (strpos($str, $sep) != 0) {
			$packet['args'][substr($str, 0, strpos($str, $sep))] = substr($str, strpos($str, $sep)+1);
		} elseif (isset($str[1])) {
			if (!stristr($str, ' ')) {
				$packet['cmd'] = $str;
			} else {
				$packet['cmd'] = substr($str, 0, strpos($str, ' '));
				$packet['param'] = trim(stristr($str, ' '));
			}
		}
	}
	return $packet;
}

function sort_dAmn_packet($packet) {
	$packet = parse_dAmn_packet($packet); // Told ya so...
	$data = array(
		'event' => 'packet',
		'p' => array($packet['param'], False, False, False, False, False),
		'packet' => $packet,
	);
	if (substr($packet['param'], 0, 6)=='login:') {
		$data['event'] = 'whois';
		$data['p'][0] = $packet['raw'];
		return $data;
	}
	switch ($packet['cmd']) {
		case 'dAmnServer':
			$data['event'] = 'connected';
			break;
		case 'login':
			$data['event'] = 'login';
			$data['p'][0] = $packet['args']['e'];
			$data['p'][1] = $packet['param'];
			break;
		case 'join':
		case 'part':
			$data['event'] = $packet['cmd'];
			$data['p'][1] = $packet['args']['e'];
			if (array_key_exists('r', $packet['args'])) {
				$data['p'][2] = $packet['args']['r'];
			}
			break;
		case 'property':
			$data['event'] = 'property';
			$data['p'][1] = $packet['args']['p'];
			$data['p'][2] = $packet['raw'];
			break;
		case 'recv':
			$sub = parse_dAmn_packet($packet['body']);
			$data['event'] = 'recv_'.$sub['cmd'];
			switch ($sub['cmd']) {
				case 'msg':
				case 'action':
					$data['p'][1] = $sub['args']['from'];
					$data['p'][2] = $sub['body'];
					break;
				case 'join':
				case 'part':
					$data['p'][1] = $sub['param'];
					if (array_key_exists('r', $sub['args'])) {
						$data['p'][2] = $sub['args']['r'];
					}
					if ($sub['cmd']=='join') {
						$data['p'][2] = $sub['body'];
					}
					break;
				case 'privchg':
				case 'kicked':
					$data['p'][1] = $sub['param'];
					$data['p'][2] = $sub['args']['by'];
					if ($sub['cmd']=='privchg') {
						$data['p'][3] = $sub['args']['pc'];
					}
					if ($sub['body'] !== Null) {
						$data['p'][3] = $sub['body'];
					}
					break;
				case 'admin':
					$data['event'].= '_'.$sub['param'];
					$data['p'] = array($packet['param'],$sub['args']['p'],false,false,false,false);
					if (array_key_exists('by', $sub['args'])) {
						$data['p'][2] = $sub['args']['by'];
					}
					switch ($sub['param']) {
						case 'create':
						case 'update':
							$data['p'][3] = $sub['args']['name'];
							$data['p'][4] = $sub['args']['privs'];
							break;
						case 'rename':
						case 'move':
							$data['p'][3] = $sub['args']['prev'];
							$data['p'][4] = $sub['args']['name'];
							if (array_key_exists('n', $sub['args'])) {
								$data['p'][5] = $sub['args']['n'];
							}
							break;
						case 'remove':
							$data['p'][3] = $sub['args']['name'];
							$data['p'][4] = $sub['args']['n'];
							break;
						case 'show':
							$data['p'][2] = $sub['body'];
							break;
						case 'privclass':
							$data['p'][2] = $sub['args']['e'];
							if ($sub['body']!==Null) {
								$data['p'][3] = $sub['body'];
							}
							break;
					}
					break;
			}
			break;
		case 'kicked':
			$data['event'] = 'kicked';
			$data['p'][1] = $packet['args']['by'];
			if ($packet['body'] !== Null) {
				$data['p'][2] = $packet['body'];
			}
			break;
		case 'ping':
			$data['event'] = 'ping';
			$data['p'][0] = false;
			break;
		case 'disconnect':
			$data['event'] = 'disconnect';
			$data['p'][0] = $packet['args']['e'];
			break;
		case 'send':
		case 'kick':
		case 'get':
		case 'set':
			$data['event'] = $packet['cmd'];
			$data['p'][1] = (array_key_exists('u',$packet['args'])?$packet['args']['u']:(isset($packet['args']['p'])?$packet['args']['p']:false));
			$id = $data['p'][1] == false ? 1 : 2;
			$data['p'][$id] = $packet['args']['e'];
			break;
		case 'kill':
			$data['event'] = 'kill';
			$data['p'][1] = $packet['args']['e'];
			$data['p'][2] = $packet['cmd'].' '.$packet['param'];
		case '': break;
		default:
			$data['event'] = 'unknown';
			$data['p'][0] = $packet;
			break;
	}
	return $data;
}

?>
