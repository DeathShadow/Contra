<?php

    class dAmnIdlers extends extension {
    
        public $name    = 'dAmnIdlers';
        public $version = 1;
        public $about   = '#dAmnIdlers player information extension';
        public $status  = true;
        public $author  = 'DivinityArcane';
        public $type    = EXT_CUSTOM;

        
        function init() {
            $this->addCmd('irpg', 'c_irpg');
            $this->cmdHelp('irpg', 'Show #dAmnIdlers user information for the specified user.<br/><b>Usage:</b> '.$this->Bot->trigger.'irpg <i>username</i>');
        }
        
        function c_irpg($ns, $from, $message, $target) {
            
            $user = strip_tags(args($message, 1));
            
            if (!strstr($message, ' ') || $user == null || empty($user))
                $this->dAmn->say($ns, $from.': Please specify which user to look up!');
            
            else {
                $json = file_get_contents('http://damnidlers.shadowkitsune.net/database.php?fmt=json&user='.$user) or null;
                
                if ($json == null || $json == 'Server busy')
                    $this->dAmn->say($ns, $from.': Server is busy. Try again later.');
                    
                else {
                    $data = json_decode($json, true);
                    
                    if ($data['.status'] != 'ok')
                        $this->dAmn->say($ns, $from.': It doesn\'t look like that user plays #dAmnIdlers');
                        
                    else {
                        $output = '<b> :bulletorange: Player information for <a href="http://damnidlers.shadowkitsune.net/?q='.$user.'">'.$user.'</a>:</b><br/><br/>';
                        $output .= '<b> Level:</b> '.$data['Level'].'<br/>';
                        $output .= '<b> Class:</b> '.$data['Class'].'<br/>';
                        $output .= '<b> Alignment:</b> '.$data['Alignment'].'<br/>';
                        $output .= '<b> Time registered:</b> '.FormatTime(time()-$data['Joined']).'<br/>';
                        $output .= '<b> Time idled:</b> '.FormatTime($data['Idled']).'<br/>';
                        $output .= '<b> Time penalized:</b> '.FormatTime($data['Penalized']).'<br/>';
                        $output .= '<b> Time to next level:</b> '.FormatTime($data['ToLevel']).'<br/>';
                        $output .= '<b> Information last updated:</b> '.$data['.last_updated'].'<br/>';
                        $output .= '<br/><i> For rankings, <a href="http://damnidlers.shadowkitsune.net/">click here.</a></i>';
                        
                        $this->dAmn->say($ns, $output);
                    }
                }
            }
        }
    }

    new dAmnIdlers($core);

?>