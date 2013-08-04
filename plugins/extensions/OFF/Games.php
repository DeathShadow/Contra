<?php

	/*
	*	Games for Contra!
	*	Created for Contra v3 by photofroggy!
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*	To install this as a module, drop the file in ~/plugins/extensions in your bot!
	*/

class Games extends extension {
	public $name = 'Games';
	public $version = 4;
	public $about = 'Small games for Contra v2';
	public $status = true;
	public $author = 'photofroggy';

	protected $ball = array();
	protected $drinks = array();
	protected $cookies = array();
	protected $rpc = array('rock', 'paper', 'scissors');
	protected $vender = 1;

	function init() {
		$this->addCmd('8ball', 'c_8ball');
		$this->addCmd('fortune','c_fortune');
		$this->addCmd('rr', 'c_rr');
		$this->addCmd('shoot', 'c_shoot');
		$this->addCmd('vend', 'c_vend');
		$this->addCmd('vender', 'c_vender');
		$this->addCmd('stock', 'c_stock', 75);
		$this->addCmd('games', 'c_games');

		$this->cmdHelp('8ball','Ask the 8ball a question!');
		$this->cmdHelp('fortune','Get a fortune cookie!');
		$this->cmdHelp('rr','Play Russian Roulette, if you dare!');
		$this->cmdHelp('shoot', 'Play rock, paper, scissors against me!');
		$this->cmdHelp('vend', 'Grab a refreshment from the vending machine!');
		$this->cmdHelp('vender', 'View the items available in the vending machine!');
		$this->cmdHelp('stock', 'Manage and view the vending machine\'s stock!');
		$this->cmdHelp('games', 'Get info about my games!');

		$this->load_8ball();
		$this->load_drinks();
		$this->load_fortunes();
	}

	function c_8ball($ns, $from, $message, $target) {
		$question = args($message, 1, true);
		if (empty($question)) {
			$say = $from.': Give a question to the 8ball!';
		} else {
			$say = $from.': You said \'<i>'.$question.'?</i>\' I say \'<b>'.$this->ball[array_rand($this->ball)].'</b>\'';
		}
		if (!empty($say)) {
			$this->dAmn->say($ns, $say);
		}
	}
	function c_fortune($ns, $from, $message, $target) {
		$this->dAmn->say($ns, $from.': Confucious say: <b>'.$this->cookies[array_rand($this->cookies)].'</b>');
	}
	function c_rr($ns, $from, $message, $target) {
		$spin = rand(1,6);
		$bullet = rand(1,6);
		$this->dAmn->say($ns, $from.': You place a bullet in the :gun: and spin it around. Then you put it to your head, and pull...');
		if ($spin===$bullet) {
			$say = $from.': BLAM! You lose.';
		} else {
			$say = $from.': :phew: You\'re alright.';
		}
		$this->dAmn->say($ns, $say);
	}
	function c_shoot($ns, $from, $message, $target) {
		$motion = $this->rpc[rand(0,2)];
		$say = $from.': ';
		switch (strtolower(args($message, 1))) {
			case 'rock':
				if ($motion == 'rock') {
					$say.= 'Rock ties rock.';
				}
				if ($motion == 'paper') {
					$say.= 'Paper covers rock. You lose.';
				}
				if ($motion == 'scissors') {
					$say.= 'Rock smashes scissors. You win.';
				}
				break;
			case 'paper':
				if ($motion == 'rock') {
					$say.= 'Paper covers rock. You win.';
				}
				if ($motion == 'paper') {
					$say.= 'Paper ties paper.';
				}
				if ($motion == 'scissors') {
					$say.= 'Scissors cut paper. You lose.';
				}
				break;
			case 'scissors':
			case 'scissor':
				if ($motion == 'rock') {
					$say.= 'Rock smashes scissors. You lose.';
				}
				if ($motion == 'paper') {
					$say.= 'Scissors cut paper. You win.';
				}
				if ($motion == 'scissors') {
					$say.= 'Scissors tie scissors.';
				}
				break;
			default:
				$say.= 'Usage: '.$this->Bot->trigger.'shoot [rock|paper|scissors]';
				break;
		}
		$this->dAmn->say($ns, $say);
	}
	function c_vend($ns, $from, $message, $target) {
		$order = args($message, 1, true);
		if (empty($order)) {
			$this->dAmn->say($ns, $from.': Order something from the vending machine.');
			return;
		}
		if ($this->stocks($order) === false) {
			$this->dAmn->say($ns, $from.': The machine doesn\'t stock "'.$order.'".');
			return;
		}
		$this->dAmn->say($ns, $from.': '.$this->vend($order));
	}
	function c_games($ns, $from, $message, $target) {
		$arg = args($message, 1);
		if (strtolower($arg)!='list') {
			$this->dAmn->say($ns, $from.': This module just contains a few small games for you to mess around with!');
			return;
		}
		$this->dAmn->say($ns, $from.': Available games;<br/><sub>- 8ball, fortune, rr, shoot, vend</sub>');
	}

	function stocks($req) {
		foreach ($this->drinks as $id => $drink) {
			if (strtolower($req)==strtolower($drink)) {
				return $id;
			}
		}
		return false;
	}
	function vend($req) {
		$max = (count($this->drinks)*$this->vender)+1;
		$get = rand(1, $max);
		$cur = 2;
		$vend = '';
		foreach ($this->drinks as $name){
			if ($cur == $get){
				$vend = $name;
				if ($name == $req) {
					$vended='success';
				}
			}
		}
		$cur++;
		if (!$vend){
			if ($get==1) {
				$vended='jam';
			} elseif ($get>$cur) {
				$vended='success';
			}
		}
		switch ($vended) {
			case 'success':
				return 'Here is your '.$req.'.';
				break;
			case 'jam':
				return 'It\'s jammed. :thumb14796735:';
				break;
			default:
				return 'You got a '.$vend.' :shakefist:';
				break;
		}
	}
	function c_vender($ns, $from, $message, $target) {
		$this->c_stock($ns, $from, 'stock list', $target);
	}
	function c_stock($ns, $from, $message, $target) {
		$subcom = strtolower(args($message, 1));
		$item = args($message, 2, true);
		$say = $from.': ';
		switch ($subcom) {
			case 'add':
				if (empty($item)) {
					$say.= 'You need to give an item to be stocked.';
					break;
				}
				if ($this->stocks($item) !== false) {
					$say.= 'The vending machine already stocks '.$item.'.';
					break;
				}
				$this->drinks[] = $item;
				$say.= $item.' has been added to the vending machine.';
				break;
			case 'rem':
			case 'remove':
				if (empty($item)) {
					$say.= 'You need to give an item to remove from stock.';
					break;
				}
				$key = $this->stocks($item);
				if ($key === false) {
					$say.= 'The vending machine doesn\'t stock '.$item.'.';
					break;
				}
				unset($this->drinks[$key]);
				$say.= 'Removed '.$item.' from the vending machine.';
				break;
			case 'list':
				if (empty($this->drinks)) {
					$say.= 'The vending machine is empty.';
					break;
				}
				$say.= 'The vending machine contains these items:<br/><sup>-> ';
				foreach($this->drinks as $drink)
					$say.= $drink.', ';
				$say = rtrim($say, ', ').'</sup>';
				break;
			default:
				$cmd = args($message, 0);
				$pre = str_replace('&', '&amp;', $this->Bot->trigger).$cmd.' ';
				$say.= $cmd.' has the following commands:<br/><sup>';
				$say.= '<b>'.$pre.'add (item)</b> - Add an item to the vender\'s stock.<br/>';
				$say.= '<b>'.$pre.'rem(ove) (item)</b> - Remove an item from the vender\'s stock.<br/>';
				$say.= '<b>'.$pre.'list</b> - View the vender\'s stock list.<br/></sup>';
				return $this->dAmn->say($ns, $say);
				break;
		}
		$this->save_drinks();
		$this->load_drinks();
		$this->dAmn->say($ns, $say);
	}
	function save_drinks() {
		$this->Write('drinks', $this->drinks, 2);
	}
	function load_drinks() {
		$this->drinks = $this->Read('drinks', 2);
		if ($this->drinks !== false) {
			return;
		}
		$this->drinks = $this->vender_defaults();
		$this->save_drinks();
		$this->load_drinks();
	}
	function save_fortunes() {
		$this->Write('fortune cookies', $this->cookies, 2);
	}
	function load_fortunes() {
		$this->cookies = $this->Read('fortune cookies', 2);
		if ($this->cookies !== false) {
			return;
		}
		$this->cookies = $this->fortune_defaults();
		$this->save_fortunes();
		$this->load_fortunes();
	}
	function save_8ball() {
		$this->Write('8ball', $this->ball, 2);
	}
	function load_8ball() {
		$this->ball = $this->Read('8ball', 2);
		if ($this->ball !== false) {
			return;
		}
		$this->ball = $this->eightBall_defaults();
		$this->save_8ball();
		$this->load_8ball();
	}
	function vender_defaults() {
		return array(
			'coke',
			'diet coke',
			'pepsi',
			'diet pepsi',
			'lemonade',
			'wine',
			'beer',
			'mountain dew',
			'sprite',
			'coffee',
			'root beer',
			'hot chocolate',
			'fuzzy dice',
		);
	}
	function fortune_defaults() {
		return array(
			'You will meet someone special in your next life.',
			'Your future would have been sucessful had you gotten a different cookie.',
			'EMPTY COOKIE.',
			'If at first you do not succeed, sky diving is not for you.',
			'It is true, your days are numbered.',
			'Your car will run out of gas soon, but your significant other will be full. :slyfart:',
			'Success is just around the corner.  To bad your here.',
			'Tomorrow does not look good, but it is probably better then today.',
			'You should not have flushed the toilet.',
			'Your fly is open but do not worry, the barn is empty.',
			'EMPTY COOKIE.',
			'You are realy the orphan of a great king who is searching for you.',
			'05  12  22  24  31  40.',
			'Time could have been better spent by not reading this fortune cookie.',
			'Sell a man one fish, and he can eat for one day.  Teach the man to fish, and you can go broke.',
			'Corn dogs are not grown... they are nurtured.',
			'Life teaches many lessons and yes there is a test in the end.',
			'The collective IQ of the world dropped 10 points because you read this.',
			'Love will come to you when you least expect it, now that your expecting to not expect it, love probably is not coming.',
			'HELP ME!!! I have been kidnapped!',
			'If you are looking for sexual satisfaction, then you will need to keep looking.',
			'There is a BMW parked outside with someone elses name on it.',
			'I am all out of fortune cookies.'
		);
	}
	function eightBall_defaults() {
		return array(
			'Affirmative.',
			'Negatory.',
			'Outlook not so good.',
			'Signs point to Yes.',
			'Yes.',
			'You may rely on it.',
			'Ask again later.',
			'Concentrate and ask again.',
			'Outlook good.',
			'My sources say so.',
			'Better not tell you now.',
			'Without a doubt.',
			'It is decidedly so.',
			'My reply is no.',
			'As I see it, Yes.',
			'It is certain.',
			// Real 8 ball responses end here, the rest is random crap
			'Yes, definetely.',
			'Don\'t count on it.',
			'Most likely.',
			'My llama says yes!',
			'Are you serious, man? That question is SICK!',
			'I say :fart: you.',
			'Infidel! You know nothing! Oh, and your answer is hell yes thats one fine potato.',
			'HELL YES!',
			'HELL NO!',
			'Don\'t touch my barbeque you bastard!',
			'Yes that will happen, right when you have that hot date with Santa Clause on the Moon!',
			'You betcha ass that\'s an affirmative!',
			'Yes, then you will fly to Uranus and eat chili with Elton John.',
			'How appropriate, you fight like a cow.',
			'Oh look a bird.... what was it you said?',
			'Your mom said yes, aaaaall niiiight loooong.',
			'Nu--uh!! Ain\'t tellin yaa!',
			'Your fly is open :rofl:',
			'I\'ll tell you if you lemme have a go at your mom again.',
			'Just in case you were curious, the world is being corrupted by flying monkeys and NO, you will not get laid tonight.',
			'My answer has to be no, but would you say no if I said your clothes would look great on my floor?',
			'Stop abusing me! :cries:',
			'Signs point to shut the hell up.',
			':nod: NoodleMan has video proof.',
			'Mmmmmm.... :donut:. Oh. Your question. Yes.',
			'Shut up and do the friggin pencil!',
			'I forsee that this will happen on the 30th of February.',
			'No you son of a window dresser.',
			'If it is sooooo important why are you asking a chatroom 8ball? Answer that and get back to me.'
		);
	}
}

new Games($core);

?>