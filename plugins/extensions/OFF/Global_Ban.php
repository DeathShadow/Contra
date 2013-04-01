<?php
class Global_Ban extends extension {
	public $name = 'Global Ban';
	public $version = 1;
	public $about = 'Globally bans people from chatrooms.';
	public $status = TRUE;
	public $author = 'lwln';
	public $type = EXT_SYSTEM;

	protected $gban = array();

	function init() {
		$this->addCmd('gban', 'c_gban', 100);
		$this->load_data();
		if (!is_array($this->gban['rooms'])) {
			$this->gban['rooms'] = array();
			$this->save_data();
		}
	}

	function c_gban($ns, $from, $message, $target) {
		$crap = args($message, 2);
		switch (strtolower(args($message, 1))) {
			case 'list':
				$say = count($this->gban['rooms']) == 0 ? '<sub>There are no rooms in your Global Ban list!</sub>' : '<sub>Global Ban List:<br />';
				$list = $this->gban['rooms'];
				foreach ($list as $key => $room) {
					$list[$key] = $this->dAmn->deform_chat($room);
				}
				$say.= implode(' &#183; ', $list);
				$this->say($ns, $from, $say);
				break;

			case 'new':
			case 'add':
				if (empty($crap)) {
					$this->say($ns, $from, 'Syntax: <code>'.$this->Bot->trigger.'gban add (room)</code>');
					break;
				}
				$under = strtolower(args($message, 2));
				if (in_array($this->dAmn->format_chat($under), $this->array_lowercase($this->gban['rooms']))) {
					$this->say($ns, $from, '<sub>'.args($message, 2).' is already in the Global Ban list.</sub>');
					break;
				}
				$this->gban['rooms'][] = $this->dAmn->format_chat(args($message, 2));
				$this->say($ns, $from, '<sub>'.args($message, 2).' has been added to the Global Ban list.</sub>');
				$this->save_data();
				break;

			case 'del':
			case 'rem':
				if (empty($crap)) {
					$this->say($ns, $from, 'Syntax: <code>'.$this->Bot->trigger.'gban rem (room)</code>');
					break;
				}
				$under = strtolower(args($message, 2));
				if (!in_array($this->dAmn->format_chat($under), $this->array_lowercase($this->gban['rooms']))) {
					$this->say($ns, $from, '<sub>'.args($message, 2).' is not in the Global Ban list.</sub>');
					break;
				}
				$key = array_search($this->dAmn->format_chat($under), $this->array_lowercase($this->gban['rooms']));
				unset($this->gban['rooms'][$key]);
				$this->gban['rooms'] = array_filter($this->gban['rooms']);
				$this->say($ns, $from, '<sub>'.args($message, 2).' has been removed from the Global Ban list.</sub>');
				$this->save_data();
				break;

			case '':
			case '?':
				$say = '<sub>Global Ban Help<ul>';
				$say.= '<li><b>'.$this->Bot->trigger.'gban list</b> - lists all the rooms on the Global Ban list.</li>';
				$say.= '<li><b>'.$this->Bot->trigger.'gban add (channel)</b> - adds <i>(channel)</i> to the Global Ban list.</li>';
				$say.= '<li><b>'.$this->Bot->trigger.'gban rem (channel)</b> - removes <i>(channel)</i> from the Global Ban list.</li>';
				$say.= '<li><b>'.$this->Bot->trigger.'gban (username)</b> - bans <i>(username)</i> from all the rooms on the Global Ban list.</li>';
				$say.= '<li><b>'.$this->Bot->trigger.'gban ?</b> - shows this help message.</li></ul></sub>';
				$this->say($ns, $from, $say);
				break;

			default:
				foreach ($this->gban['rooms'] as $room) {
					if (in_array(strtolower($room), array_keys(array_change_key_case($this->dAmn->chat)))) {
						$this->dAmn->ban($room, args($message, 1));
					}
				}
				break;
		}
	}

	function say($ns, $from, $message) {
		$this->dAmn->say($ns, '<abbr title="'.$from.' -away"></abbr>'.$message);
	}

	function array_lowercase($array) {
		foreach ($array as $key => $val) {
			$array[$key] = strtolower($val);
		}
		return $array;
	}

	function load_data() {
		$this->gban = $this->Read('gban', 2);
		$this->gban = $this->gban === false ? array() : $this->gban;
	}

	function save_data() {
		if (empty($this->gban)) {
			$this->Unlink('gban');
		} else {
			$this->Write('gban', $this->gban, 2);
		}
	}
}

new Global_Ban($core);
?>