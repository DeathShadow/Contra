<?php

	/*
	*	Notes module version 3.
	*	Made for Contra v3.
	*	Created by photofroggy.
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*/

class notes_module extends extension {
	public $name = 'Notes';
	public $version = 3;
	public $about = 'This is a bot notes module for Contra!';
	public $status = true;
	public $author = 'photofroggy';

	protected $receivers = array();
	protected $notes = array();
	protected $read = array();

	function init() {
		$this->addCmd('note', 'c_note');
		$this->cmdHelp(
			'note',
			'Send notes to people via this bot!<br/><sup>'
				.$this->Bot->trigger.'note [user] [message]<br/>'
				.$this->Bot->trigger.'note list [new]<br/>'
				.$this->Bot->trigger.'note read [id]<br/>'
				.$this->Bot->trigger.'note clear [id]<br/></sup>'
		);

		$this->hook('notes_check', 'recv_join');
		$this->hook('notes_check', 'recv_msg');
		$this->loadnotes();
	}

	function notes_check($ns, $from, $msg = false) {
		$this->loadnotes();
		$trig = $this->Bot->trigger;
		if (substr(strtolower($msg), 0, strlen($trig.'note read')) == $trig.'note read') {
			$this->clearRecvs($from);
			return;
		}
		if (substr(strtolower($msg), 0, strlen($trig.'note list')) == $trig.'note list') {
			return;
		}
		if (substr(strtolower($msg), 0, strlen($trig.'note clear')) == $trig.'note clear') {
			return $this->clearRecvs($from);
		}
		if (!stristr($msg, 'away')) {
			$this->checkmsg($from, $ns);
		}
	}

	function c_note($ns, $from, $message, $target) {
		$dAmn = $this->dAmn;
		$com1 = args($message, 1);
		$com2 = args($message, 2);
		$com3 = args($message, 3);
		$com4 = args($message, 4);
		$note = args($message, 2, true);
		$trig = str_replace('&','&amp;',$this->Bot->trigger);
		$user = $this->user; $f = $from.': ';

		switch (strtolower($com1)) {
			case 'to':
			case 'send':
				$this->say($ns, $from, 'The correct command is: '.$this->Bot->trigger.'note username message.');
				break;
			case 'list':
			case 'read':
				$call = strtolower($com1) == 'list' ? 'getList' : 'getNote';
				$check = strtolower($from);
				if (strtolower($com1) == 'list' && isset($com2) && strtolower($com2)=='new') {
					foreach($this->notes as $user => $nn) {
						if ($user == $check) {
							if (count($this->notes[$user])==count($this->read[$user])) {
								$this->say($ns, $from, 'You have no notes.');
								return;
							}
							$this->say($ns, $from, $this->getNewList($user, false));
							return;
						}
					}
					$this->say($ns, $from, 'You have no new notes.');
					return;
				}
				foreach ($this->notes as $user => $nn) {
					if ($user == $check) {
						$this->say($ns, $from, $this->$call($user, ($call=='getList' ? false : $com2)));
						return;
					}
				}
				$this->say($ns, $from, 'You have no notes.');
				break;
			case 'del':
			case 'delete':
			case 'clear':
				if (strtolower($com1) == 'clear' || strtolower($com2) == 'all') {
					$clr = $this->clear($from);
					if ($clr == 'cleared') {
						$this->say($ns, $from, 'Notes cleared!');
					}
					if ($clr == 'error') {
						$this->say($ns, $from, 'Couldn\'t clear your notes...');
					}
					if ($clr == 'none') {
						$this->say($ns, $from, 'You don\'t have any notes to delete...');
					}
					return;
				}
				$check = strtolower($from);
				foreach ($this->notes as $user => $nn) {
					if ($user == $check) {
						$this->say($ns, $from, $this->delNote($user, $com2, $ns));
						return;
					}
				}
				$this->say($ns, $from, 'You don\'t have any notes to delete.');
				break;
			case 'about':
				$say = $f.'This is a basic notes extension which allows you to leave notes for people on the bot. This extension is bundled with Contra.';
				$this->say($ns, $from, $say);
				break;
			case 'admin':
				if ($user->has($from, 100)) {
					if ($com2=='list') {
						$this->say($ns, $from, $this->adminList($from));
						return;
					}
					if ($com2=='del'||$com2=='delete') {
						$this->say($ns, $from, $this->adminClr($from, $com3));
						return;
					}
				}
				break;
			default:
				if (empty($note) || empty($com1) || strtolower($com1) == strtolower($this->Bot->username)) {
					return;
				}
				if ($this->sendnote($com1, $from, $note, $ns)) {
					$this->say($ns, $from, 'Note sent!');
				}
				break;
		}
	}

	protected function getList($user) {
		$notes = $this->check($user);
		if ($notes !== false) {
			$this->clearRecvs($user);
		}
		$new = ($notes === false ? '' : '('.$notes.' new)');
		if (empty($this->notes[$user])) {
			return $user.': You don\'t have any notes.';
		}
		$head = $user.': Your notes <code>'.$new.'</code><br/>'; $list = '';
		foreach ($this->notes[$user] as $id => $data) {
			if (isset($this->read[$user][$id])) {
				$list.= '#'.$id.', ';
			} else {
				$list.= '<b>#'.$id.'</b>, ';
			}
		}
		return $head.rtrim($list,', ').'<br/><br/><sup><b>Bold</b> notes have not yet been read.<br/>Use '.$this->Bot->trigger.'note read [id] to read a note.</sup>';
	}

	protected function getNewList($user) {
		$notes = $this->check($user);
		if ($notes!==false) {
			$this->clearRecvs($user);
		}
		if (empty($this->notes[$user]) || count($this->notes[$user]) == count($this->read[$user])) {
			return $user.': You don\'t have any notes.';
		}
		$head = $user.': Your notes<br/>'; $list = '';
		foreach ($this->notes[$user] as $id => $data) {
			if (!isset($this->read[$user][$id])) {
				$list.= '#'.$id.', ';
			}
		}
		return $head.rtrim($list,', ').'<br/><br/><sup>Use '.$this->Bot->trigger.'note read [id] to read a note.</sup>';
	}

	protected function getNote($user, $id) {
		if (!isset($this->notes[$user])) {
			return $user.': You don\'t have any notes.';
		}
		$id = (substr($id, 0, 1) === '#' ? substr($id, 1) : $id);
		if ($id=='') {
			return $user.': You must provide a note ID number.';
		}
		if (!isset($this->notes[$user][$id])) {
			return $user.': Note #'.$id.' not found.';
		}
		$note = $this->notes[$user][$id];
		$this->read[$user][$id] = true;
		$this->Write('read', $this->read);
		return '<br/><b>To:</b> '.$user.'<b>; From:</b> '.$note['from'].'<b>; Date received</b>: '.gmdate('r', $note['ts']).'<b>;'.'<br/>Message:</b> '.$note['content'].'<br/><sub>Use "'.$this->Bot->trigger.'note clear" to delete all notes or "'.$this->Bot->trigger.'note delete '.$id.'" to delete this note.</sub>';
	}

	protected function delNote($user, $id, $ns) {
		if (!isset($this->notes[$user])) {
			return $user.': You don\'t have any notes.';
		}
		$id = (substr($id, 0, 1) === '#' ? substr($id, 1) : $id);
		if ($id == '') {
			return $user.': You must provide a note ID number.';
		}
		if (!isset($this->notes[$user][$id])) {
			return $user.': Note #'.$id.' not found.';
		}
		$this->notes[$user] = array_del_key($this->notes[$user], $id);
		if (isset($this->read[$user][$id])) {
			unset($this->read[$user][$id]);
		}
		$this->Write('notes', $this->notes);
		$this->Write('read', $this->read);
		return $user.': Deleted note #'.$id.'.';
	}

	protected function loadnotes() {
		$notes = $this->Read('notes');
		$this->notes = ($notes === false ? array() : $notes);
		$rec = $this->Read('receive');
		$this->receivers = ($rec === false ? array() : $rec);
		$read = $this->Read('read');
		$this->read = ($read === false ? array() : $read);
	}

	protected function sendnote($to, $from, $content, $ns) {
		if (empty($to)) {
			return false;
		}
		$user = strtolower($to);
		$socket = fsockopen('ssl://www.deviantart.com', 443);
		$response = $this->send_headers(
			$socket,
			$user.'.deviantart.com',
			'/',
			'http://'.$user.'.deviantart.com'
		);
		fclose($socket);

		if (($pos = strpos($response, 'HTTP/1.1 404 Not Found')) !== false) {
			$this->say($ns, $from, $user.' does not exist.');
			return;
		} else {
			if (!isset($this->notes[$user])) {
				$this->notes[$user] = array();
			}
			if (!isset($this->receivers[$user])) {
				$this->receivers[$user] = 1;
			} else {
				$this->receivers[$user]++;
			}
			$this->Write('receive', $this->receivers);
			$i = count($this->notes[$user]);
			$this->notes[$user][$i]['content'] = $content;
			$this->notes[$user][$i]['from'	 ] = 	$from;
			$this->notes[$user][$i]['ts'	 ] =   time();
			ksort($this->notes[$user]);
			$this->Write('notes', $this->notes);
			$this->Write('read', $this->read);
			$this->loadnotes();
			return true;
		}
	}

	protected function check($user) {
		$user = strtolower($user);
		if (isset($this->receivers[$user])) {
			return $this->receivers[$user];
		} else {
			return false;
		}
	}

	protected function clear($from) {
		$user = strtolower($from); $p = 1;
		if (isset($this->notes[$user])) {
			unset($this->notes[$user]);
		} else {
			$p = 0;
		}
		if (isset($this->receivers[$user])) {
			unset($this->receivers[$user]);
		}
		if ($p == 1) {
			if (empty($this->notes)) {
				$this->Unlink('notes');
			} else {
				$this->Write('notes', $this->notes);
			}
			if (empty($this->receivers)) {
				$this->Unlink('receive');
			} else {
				$this->Write('receive', $this->receivers);
			}
			$notes = $this->Read('notes');
			$rec   = $this->Read('receive');
			if (isset($this->read[$user])) {
				unset($this->read[$user]);
			}
			$this->Write('read', $this->read);
			$this->notes     = ($notes === false) ? array() : $notes;
			$this->receivers = (  $rec === false) ? array() :   $rec;
			if (isset($this->notes[$user])) {
				return 'error';
			} else {
				return 'cleared';
			}
		} else {
			return 'none';
		}
	}

	protected function adminList($admin) {
		$string = $admin.': People who have notes in their inbox, on this bot:<br/>';
		$i = 0;
		foreach ($this->notes as $user => $notes) {
			if ($user != '') {
				$string.= '<br/><b>&middot; :dev'.$user.':</b>';
				$i++;
			}
		}
		if ($i == 0) {
			$string = $admin.': No notes are stored on this bot.';
		}
		return $string;
	}

	protected function adminClr($admin, $dev) {
		if (isset($this->notes[strtolower($dev)])) {
			unset($this->notes[strtolower($dev)]);
			if (isset($this->receivers[strtolower($dev)])) {
				unset($this->receivers[strtolower($dev)]);
			}
			if (empty($this->notes)) {
				$this->Unlink('notes');
			} else {
				$this->Write('notes', $this->notes);
			}
			if (empty($this->receivers)) {
				$this->Unlink('receive');
			} else {
				$this->Write('receive', $this->receivers);
			}
			$notes = $this->Read('notes');
			$rec   = $this->Read('receive');
			if (isset($this->read[$dev])) {
				unset($this->read[$dev]);
			}
			$this->Write('read', $this->read);
			$this->notes     = ($notes === false) ? array() : $notes;
			$this->receivers = (  $rec === false) ? array() :   $rec;
			$say = $admin.': Deleted '.$dev.'\'s notes.';
		} else {
			$say = $admin.': '.$dev.' does not have any notes...';
		}
		return $say;
	}

	protected function checkmsg($from, $ns) {
		$dAmn = $this->dAmn;
		$notes = $this->check($from);
		$trig = str_replace('&','&amp;',$this->Bot->trigger);
		if ($notes !== false && $ns != 'chat:DataShare') {
			$am = ($notes == 1 ? 'note' : 'notes');
			$this->say($ns, $from, '<b>:thumb42731685:'.$from.': You have '.$notes.' new '.$am.'.</b><br/><code>Type "'.$trig.'note list" to view your notes.</code>');
			$this->clearRecvs($from);
		}
	}

	protected function clearRecvs($user) {
		unset($this->receivers[strtolower($user)]);
		if (empty($this->receivers)) {
			$this->Unlink('receive');
		} else {
			$this->Write('receive', $this->receivers);
		}
		$rec = $this->Read('receive');
		$this->receivers = ($rec === false) ? array() : $rec;
	}

	function send_headers($socket, $host, $url, $referer)
	{
	    try
	    {
		$headers = '';
		$headers .= "GET $url HTTP/1.1\r\n";
		$headers .= "Host: $host\r\n";
		$headers .= "User-Agent: Contra\r\n";
		$headers .= "Referer: $referer\r\n";
		$headers .= "Connection: close\r\n";
		$headers .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*\/*;q=0.8\r\n";
		$headers .= "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n";
		$headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$headers .= "\r\n";
		$response = '';
		fputs($socket, $headers);
		$response .= @fgets ($socket, 23);
		return $response;
	    }
	    catch (Exception $e)
	    {
		echo 'Exception occured: '.$e->getMessage()."\n";
		return '';
	    }
	}

	function say($ns, $from, $message, $DATASHARE = FALSE) {
		$this->dAmn->say($ns, '<abbr title="'.$from.' -away"></abbr>'.$message);
	}
}

new notes_module($core);

?>
