<?php

    class Devinfo extends extension {
        public $name = 'Devinfo';
        public $version = 1;
        public $about = 'Deviant info extension.';
        public $status = true;
        public $author = 'DivinityArcane';
        public $type = EXT_CUSTOM;

        function init() {
            $this->addCmd('devinfo', 'c_devinfo');
            $this->cmdHelp('devinfo', 'Deviant info command.');
        }
        
        function c_devinfo($ns, $from, $message, $target) {
			$args = explode(' ', args($message, 1, true));

			if (count($args) < 1 || strlen($args[0]) < 1) {
				$this->dAmn->say($ns, $from.': usage: devinfo <i>username</i>');
				
			} else {
				$data = deviant_info($args[0]);

				if ($data == null) {
					$this->dAmn->say($ns, $from.': Unable to communicate with deviantART at this time.');
					
				} else {
					$former = (strtolower($args[0]) == strtolower($data['Username']) ? '' : ' <sup>(Formerly '.$args[0].')</sup>');
					$output = '<b>:icon'.$data['Username'].': Information on :dev'.$data['Username'].':</b>'.$former.'<br/>';

					$doublebreak = false;
					
					if (array_key_exists('Tagline', $data) && strlen($data['Tagline']) > 0) {
						$output .= '<br/><i>'.$data['Tagline'].'</i>';
						$doublebreak = true;
					}
						
					if (array_key_exists('Name', $data) && strlen($data['Name']) > 0) {
						$output .= '<br/><i>'.$data['Name'].'</i>';
						$doublebreak = true;
					}
						
					if (array_key_exists('ASL', $data) && strlen($data['ASL']) > 0) {
						$output .= '<br/><i>'.$data['ASL'].'</i>';
						$doublebreak = true;
					}

					if ($doublebreak)
						$output .= '<br/>';

					$doublebreak = false;
						
					if (array_key_exists('Type', $data) && strlen($data['Type']) > 0) {
						$output .= '<br/><b><i>'.$data['Type'].'</i></b>';
						$doublebreak = true;
					}
						
					if (array_key_exists('Joined', $data) && strlen($data['Joined']) > 0) {
						$output .= '<br/><b>'.$data['Joined'].'</b>';
						$doublebreak = true;
					}
						
					if (array_key_exists('Member', $data) && strlen($data['Member']) > 0) {
						$output .= '<br/><b>'.$data['Member'].'</b>';
						$doublebreak = true;
					}

					if ($doublebreak)
						$output .= '<br/>';

					foreach ($data['info'] as $k => $v) {
						$output .= '<br/><b>'.$k.':</b> '.$v;
					}
					
					$this->dAmn->say($ns, $output);

				}
			}
        }
    }
    
    new Devinfo($core);

?>