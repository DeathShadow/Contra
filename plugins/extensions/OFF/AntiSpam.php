<?php

/* Contra Module
 * - Anti-Spam
 * - Version 1.0
 * - Author: Justin Eittreim (DivinityArcane) > eittreim.justin@live.com
 * - Date: Wed, October 24 2012 02:57am
 */

class AntiSpam extends extension {
    // Module information
    public $name      = 'Anti-Spam';
    public $version   = 1;
    public $about     = 'Anti-Spam module.';
    public $status    = true;
    public $author    = 'DivinityArcane';
    public $type      = EXT_CUSTOM;

    // Our data store
    private $data;

    // Let's init the module!
    function init() {
        $this->addCmd('antispam', 'c_antispam', 50);
        $this->cmdHelp('antispam', 'Modify Anti-Spam options.');

        $this->load_data();
    }

    // Handle when the user calls our module from the command
    function c_antispam($ns, $from, $message, $target) {

        $option     = args($message, 1);
        $channel    = args($message, 2);
        $opt2       = args($message, 3);
        $opt3       = args($message, 4);
        $trigger    = $this->Bot->trigger;

        if (empty($channel) || '#' !== $channel[0])
            $option = null;

        // What do they want to do?
        switch ($option) {

            // User wants to enble the anti-spam module in a certain channel.
            case 'enable':
                $safe_chan = strtolower(substr($channel, 1));

                // A default configuration.
                $this->data[$safe_chan]   = array(

                    'strikes'             => array(),
                    'joins'               => array(),
                    'msgs'                => array(),
                    'bans'                => array(),
                    'silences'            => array(),
                    'max_strikes'         => 5,
                    'msg_delay'           => 5,
                    'join_delay'          => 5,
                    'max_msgs'            => 5,
                    'max_joins'           => 5,
                    'strike_expiry'       => 300, // 5 minutes in seconds.
                    'max_strikes_penalty' => 'silence',
                    'silenced_privclass'  => 'Silenced');

                $this->save_data();
                $this->dAmn->say($ns, "{$from}: Anti-Spam has been enabled for {$channel}.");
                break;

            // User wants to disable the anti-spam module in a certain channel.
            case 'disable':
                $safe_chan = strtolower(substr($channel, 1));
                unset($this->data[$safe_chan]);
                $this->save_data();
                $this->dAmn->say($ns, "{$from}: Anti-Spam has been disabled for {$channel}.");
                break;

            // User wants to change some options, sure!
            case 'options':
                // Make sure we have a more generic version of the chat namespace.
                $safe_chan = strtolower(substr($channel, 1));

                if (!array_key_exists($safe_chan, $this->data)) {
                    $this->dAmn->say($ns, $from .': Anti-Spam is not enabled for that channel!');
                    return;
                }

                switch($opt2) {

                    // Change the maximum amount of messages a user can say in the set period before a strike is given.
                    case 'max_msgs':
                        $integer_value = intval($opt3);

                        if ($integer_value > 0 && $integer_value <= 99) {
                            $this->data[$safe_chan]['max_msgs'] = $integer_value;
                            $this->dAmn->say($ns, $from .': The <b><code>max_msgs</code></b> variable for <b>'.
                                    $channel .'</b> has been set to <b>'. $integer_value .'</b>.');
                        } else {
                            $this->dAmn->say($ns, $from .': This value should ideally be between 1 and 99.');
                        }
                        break;

                    // Change the maximum amount of joins a user can perform in the set period before a strike is given.
                    case 'max_joins':
                        $integer_value = intval($opt3);

                        if ($integer_value > 0 && $integer_value <= 99) {
                            $this->data[$safe_chan]['max_joins'] = $integer_value;
                            $this->dAmn->say($ns, $from .': The <b><code>max_joins</code></b> variable for <b>'.
                                    $channel .'</b> has been set to <b>'. $integer_value .'</b>.');
                        } else {
                            $this->dAmn->say($ns, $from .': This value should ideally be between 1 and 99.');
                        }
                        break;

                    // Change the maximum amount of strikes before action is taken against a user.
                    case 'max_strikes':
                        $integer_value = intval($opt3);

                        if ($integer_value > 0 && $integer_value <= 99) {
                            $this->data[$safe_chan]['max_strikes'] = $integer_value;
                            $this->dAmn->say($ns, $from .': The <b><code>max_strikes</code></b> variable for <b>'.
                                    $channel .'</b> has been set to <b>'. $integer_value .'</b>.');
                        } else {
                            $this->dAmn->say($ns, $from .': This value should ideally be between 1 and 99.');
                        }
                        break;

                    // Change the time in which a user cannot meet or exceed the max message amount during, in seconds.
                    case 'msg_delay':
                        $integer_value = intval($opt3);

                        if ($integer_value > 0 && $integer_value <= 60) {
                            $this->data[$safe_chan]['msg_delay'] = $integer_value;
                            $this->dAmn->say($ns, $from .': The <b><code>msg_delay</code></b> variable for <b>'.
                                    $channel .'</b> has been set to <b>'. $integer_value .'</b>.');
                        } else {
                            $this->dAmn->say($ns, $from .': This value should ideally be between 1 and 60 seconds.');
                        }
                        break;

                    // Change the time in which a user cannot meet or exceed the max join amount during, in seconds.
                    case 'join_delay':
                        $integer_value = intval($opt3);

                        if ($integer_value > 0 && $integer_value <= 60) {
                            $this->data[$safe_chan]['join_delay'] = $integer_value;
                            $this->dAmn->say($ns, $from .': The <b><code>join_delay</code></b> variable for <b>'.
                                    $channel .'</b> has been set to <b>'. $integer_value .'</b>.');
                        } else {
                            $this->dAmn->say($ns, $from .': This value should ideally be between 1 and 60 seconds.');
                        }
                        break;

                    // Change the time (in seconds) it takes for a strike to be removed.
                    case 'strike_expiry':
                        $integer_value = intval($opt3);

                        if ($integer_value > 0 && $integer_value <= 86400) {
                            $this->data[$safe_chan]['strike_expiry'] = $integer_value;
                            $this->dAmn->say($ns, $from .': The <b><code>strike_expiry</code></b> variable for <b>'.
                                    $channel .'</b> has been set to <b>'. $integer_value .'</b>.');
                        } else {
                            $this->dAmn->say($ns, $from .': This value should ideally be between 1 and 86400 seconds.');
                        }
                        break;

                    // Change the penalty type.
                    case 'penalty':
                        if ($opt3 === 'ban' || $opt3 === 'silence') {
                            $this->data[$safe_chan]['max_strikes_penalty'] = $opt3;
                            $this->dAmn->say($ns, $from .': The <b><code>penalty</code></b> variable for <b>'.
                                    $channel .'</b> has been set to <b>'. $opt3 .'</b>.');
                        } else {
                            $this->dAmn->say($ns, $from .': This value should either <b>ban</b> or <b>silence</b>.');
                        }
                        break;

                    // Change the privclass silenced users will be demoted to.
                    case 'silenced_privclass':
                        if (!empty($opt3)) {
                            $this->data[$safe_chan]['silenced_privclass'] = $opt3;
                            $this->dAmn->say($ns, $from .': The <b><code>silenced_privclass</code></b> variable for <b>'.
                                    $channel .'</b> has been set to <b>'. $opt3 .'</b>.');

                        } else {
                            $this->dAmn->say($ns, $from .': This value should be the case-sensitive name of a privclass that'.
                                    ' the person will be demoted to.');
                        }
                        break;

                    // Let them know what all they can do.
                    default:
                        $this->dAmn->say($ns, '<abbr title="'. $from .'"></abbr>'.
                            '<b><code>Usage:</code></b><br/>'.
                            $trigger . 'antispam <i>options #channel <b>max_msgs</b> #</i><br/>'.
                            $trigger . 'antispam <i>options #channel <b>max_joins</b> #</i><br/>'.
                            $trigger . 'antispam <i>options #channel <b>msg_delay</b> #</i><br/>'.
                            $trigger . 'antispam <i>options #channel <b>join_delay</b> #</i><br/>'.
                            $trigger . 'antispam <i>options #channel <b>max_strikes</b> #</i><br/>'.
                            $trigger . 'antispam <i>options #channel <b>strike_expiry</b> #</i><br/>'.
                            $trigger . 'antispam <i>options #channel <b>penalty</b> silence/ban</i><br/>'.
                            $trigger . 'antispam <i>options #channel <b>silenced_privclass</b> privclassName</i><br/>');
                        break;
                }
                break;

            // They want to view or clear the logs, let's go about it.
            case 'logs':
                // Make sure we have a more generic version of the chat namespace.
                $safe_chan = strtolower(substr($channel, 1));

                if (!array_key_exists($safe_chan, $this->data)) {
                    $this->dAmn->say($ns, $from .': Anti-Spam is not enabled for that channel!');
                    return;
                }

                switch($opt2) {

                    // View the logs.
                    case 'view':
                        $silence_count = count($this->data[$safe_chan]['silences']);
                        $ban_count = count($this->data[$safe_chan]['bans']);

                        $message = '&raquo; <b>Anti-Spam logs for <i>'. $channel .'</i></b><br/><br/>';

                        if ($silence_count > 0) {
                            $message .= '<b>&nbsp;&nbsp;&nbsp;&nbsp;<code>There have been '. $silence_count .
                                    ' users silenced:</code></b><br/>';
                            foreach($this->data[$safe_chan]['silences'] as $silencee => $values) {
                                $message .= '<b>&nbsp;&nbsp;&nbsp;&nbsp;&middot; :dev'. $silencee .'::</b> <i>'.
                                    $values['reason'] .' <sup>('. date('l, F jS - h:i:sa', $values['timestamp']) .')</sup></i><br/>';
                            }
                            $message .= '<br/>';

                        } else {
                            $message .= '<b>&nbsp;&nbsp;&nbsp;&nbsp;<code>There have been no users silenced.</code></b><br/><br/>';
                        }

                        if ($ban_count > 0) {
                            $message .= '<b>&nbsp;&nbsp;&nbsp;&nbsp;<code>There have been '. $ban_count .
                                    ' users banned:</code></b><br/>';
                            foreach($this->data[$safe_chan]['bans'] as $banned => $values) {
                                $message .= '<b>&nbsp;&nbsp;&nbsp;&nbsp;&middot; :dev'. $banned .'::</b> <i>'.
                                    $values['reason'] .' <sup>('. date('l, F jS - h:i:sa', $values['timestamp']) .')</sup></i><br/>';
                            }
                            $message .= '<br/>';

                        } else {
                            $message .= '<b>&nbsp;&nbsp;&nbsp;&nbsp;<code>There have been no users banned.</code></b><br/><br/>';
                        }

                        $this->dAmn->say($ns, $message);
                        break;

                    // Clear the logs.
                    case 'clear':
                        $this->data[$safe_chan]['silences'] = array();
                        $this->data[$safe_chan]['bans'] = array();
                        $this->save_data();
                        $this->dAmn->say($ns, $from .': Logs have been cleared!');
                        break;

                    // Let them know what they can do with the logs.
                    default:
                        $this->dAmn->say($ns, '<abbr title="'. $from .'"></abbr>'.
                            '<b><code>Usage:</code></b><br/>'.
                            $trigger . 'antispam <i>logs #channel <b>view</b></i><br/>'.
                            $trigger . 'antispam <i>logs #channel <b>clear</b></i><br/>');
                        break;
                }
                break;


            // Check the settings for a channel
            case 'settings':
                $safe_ns = (strlen($channel) > 1 && $channel[0] == '#' ? strtolower(substr($channel, 1)) : null);
       		    // View settings on a channel
                if (null !== $safe_ns && array_key_exists($safe_ns, $this->data)) {
		            $data = $this->data[$safe_ns];
                    $message = '&raquo; <i>Settings for <b>'.$channel.'</b></i><br/><br/>'.
                        '&nbsp;&nbsp;&nbsp;&nbsp;&middot; <b>max_msgs</b> is set to: <b>'.$data['max_msgs'].'</b><br/>'.
                        '&nbsp;&nbsp;&nbsp;&nbsp;&middot; <b>max_joins</b> is set to: <b>'.$data['max_joins'].'</b><br/>'.
                        '&nbsp;&nbsp;&nbsp;&nbsp;&middot; <b>msg_delay</b> is set to: <b>'.$data['msg_delay'].'</b> seconds.<br/>'.
                        '&nbsp;&nbsp;&nbsp;&nbsp;&middot; <b>join_delay</b> is set to: <b>'.$data['join_delay'].'</b> seconds.<br/>'.
                        '&nbsp;&nbsp;&nbsp;&nbsp;&middot; <b>max_strikes</b> is set to: <b>'.$data['max_strikes'].'</b><br/>'.
                        '&nbsp;&nbsp;&nbsp;&nbsp;&middot; <b>strike_expiry</b> is set to: <b>'.$data['strike_expiry'].'</b> seconds.<br/>'.
                        '&nbsp;&nbsp;&nbsp;&nbsp;&middot; <b>penalty</b> is set to: <b>'.$data['max_strikes_penalty'].'</b><br/>'.
                        '&nbsp;&nbsp;&nbsp;&nbsp;&middot; <b>silenced_privclass</b> is set to: <b>'.$data['silenced_privclass'].'</b>';

                    $this->dAmn->say($ns, $message);
                }
                break;


            // Let them know what they can do.
            default:
                $this->dAmn->say($ns, '<abbr title="'. $from .'"></abbr>'.
	                '<b><code>Usage:</code></b><br/>'.
	                $trigger . 'antispam <i>enable <b>#channel</b></i><br/>'.
	                $trigger . 'antispam <i>disable <b>#channel</b></i><br/>'.
	                $trigger . 'antispam <i>options <b>#channel</b></i><br/>'.
	                $trigger . 'antispam <i>logs <b>#channel</b></i><br/><br/><sub><i>To view the settings of a channel, use</i><br/>'.
	                $trigger . 'antispam <i>settings</i> <b>#channel</b></sub>');
                break;
        }

    }


    // Check the user upon each message.
    function e_check_msgs($ns, $from, $msg = false) {
        // Convert the chat namespace to something more generic.
        $safe_ns = strtolower(substr($ns, 5));

        // Check that the module is enabled in this channel.
        if (!array_key_exists($safe_ns, $this->data))
            return;

        // Convert their name to lowercase, easily manageable.
        $user = strtolower($from);

        // Make sure we aren't recording ourselves.
        if ($user === strtolower($this->Bot->username))
            return;

        // If we don't have this user in the DB, add them.
        if (!array_key_exists($user, $this->data[$safe_ns]['msgs'])) {
            $this->data[$safe_ns]['msgs'][$user] = array (
                    'real_name' => $from,
                    'first_msg' => time(),
                    'last_msg' => time(),
                    'msg_count' => 1);

        } else {
            // Check the delays between now and the first/last message recorded.
            $delay_first = time() - $this->data[$safe_ns]['msgs'][$user]['first_msg'];
            $delay_last = time() - $this->data[$safe_ns]['msgs'][$user]['last_msg'];

            // It's been long enough that we can clear their message counts.
            if ($delay_first >= $this->data[$safe_ns]['msg_delay']) {
                $this->data[$safe_ns]['msgs'][$user]['first_msg'] = time();
                $this->data[$safe_ns]['msgs'][$user]['last_msg'] = time();
                $this->data[$safe_ns]['msgs'][$user]['msg_count'] = 1;

            } else if ($this->data[$safe_ns]['msgs'][$user]['msg_count'] >= $this->data[$safe_ns]['max_msgs']) {
                // User is message flooding

                // If they're not in the strikes DB, add them.
                if (!array_key_exists($user, $this->data[$safe_ns]['strikes']))
                    $this->data[$safe_ns]['strikes'][$user] = array(
                        'strike_count' => 0,
                        'last_strike' => 0);

                // If they've got more than zero strikes, check them out.
                if ($this->data[$safe_ns]['strikes'][$user]['last_strike'] != 0) {
                    // How long has it been since the last strike?
                    $delay = time() - $this->data[$safe_ns]['strikes'][$user]['last_strike'];

                    // It's been long enough that we can knock one off their counter.
                    if ($delay >= $this->data[$safe_ns]['strike_expiry']) {
                        if ($this->data[$safe_ns]['strikes'][$user]['strike_count'] > 1) {
                            $this->data[$safe_ns]['strikes'][$user]['strike_count']--;
                            $this->data[$safe_ns]['strikes'][$user]['last_strike'] = time();
                        } else {
                            $this->data[$safe_ns]['strikes'][$user]['strike_count'] = 0;
                            $this->data[$safe_ns]['strikes'][$user]['last_strike'] = 0;
                        }
                    }
                }

                // Since they've exceeded the count and gotten a strike, reset the count.
                $this->data[$safe_ns]['msgs'][$user]['first_msg'] = time();
                $this->data[$safe_ns]['msgs'][$user]['last_msg'] = time();
                $this->data[$safe_ns]['msgs'][$user]['msg_count'] = 0;

                // Add another strike.
                $this->data[$safe_ns]['strikes'][$user]['strike_count']++;
                $this->data[$safe_ns]['strikes'][$user]['last_strike'] = time();

                // If they've got enough strikes, penalize them.
                if ($this->data[$safe_ns]['strikes'][$user]['strike_count'] >= $this->data[$safe_ns]['max_strikes']) {
                    // What kind of action are we taking?
                    switch($this->data[$safe_ns]['max_strikes_penalty']) {

                        // The default is to silence them to the specified privclass.
                        default:
                        case 'silence':
                            $this->dAmn->demote($ns, $user, $this->data[$safe_ns]['silenced_privclass']);

                            // If they've not in the database (they shouldn't be.) add them.
                            if (!array_key_exists('silences', $this->data[$safe_ns]))
                                $this->data[$safe_ns]['silences'] = array();

                            // Make sure we know why they were silenced.
                            $this->data[$safe_ns]['silences'][$user] = array(
                                    'reason' => 'Message flooding.',
                                    'timestamp' => time());
                            break;

                        // A bit more harsh, ban them from the channel and let them know why.
                        case 'ban':
                            $this->dAmn->kick($ns, $user, 'You\'ve been banned from the channel for flooding. Inquire with an admin to be let back in.');
                            $this->dAmn->ban($ns, $user);

                            // If they've not in the database (they shouldn't be.) add them.
                            if (!array_key_exists('bans', $this->data[$safe_ns]))
                                $this->data[$safe_ns]['bans'] = array();

                            // Make sure we know why they were banned.
                            $this->data[$safe_ns]['bans'][$user] = array(
                                    'reason' => 'Message flooding.',
                                    'timestamp' => time());
                            break;
                    }

                    // We've taken action, we can now clear their strikes.
                    $this->data[$safe_ns]['strikes'][$user]['strike_count'] = 0;
                    $this->data[$safe_ns]['strikes'][$user]['last_strike'] = 0;

                } else {
                    // Let them know they've gained a strike!
                    $this->dAmn->say($ns, $from .': Slow down! You\'ve been given a strike. '.
                            '<sup>(You\'ve got '. $this->data[$safe_ns]['strikes'][$user]['strike_count'] .
                            '/'. $this->data[$safe_ns]['max_strikes'] .'. Reaching '. $this->data[$safe_ns]['max_strikes'].
                            ' will result in you being banned/silenced.)</sup>');
                }

            } else {
                // Toll up the message counter.
                $this->data[$safe_ns]['msgs'][$user]['last_msg'] = time();
                $this->data[$safe_ns]['msgs'][$user]['msg_count']++;

            }

        }

        // Make sure we save the data.
        $this->save_data();

    }


    // Check the user upon joining.
    function e_check_join($ns, $from, $msg = false) {
        // Convert the chat namespace to something more generic.
        $safe_ns = strtolower(substr($ns, 5));

        // Check that the module is enabled in this channel.
        if (!array_key_exists($safe_ns, $this->data))
            return;

        // Convert their name to lowercase, easily manageable.
        $user = strtolower($from);

        // Make sure we aren't recording ourselves.
        if ($user === strtolower($this->Bot->username))
            return;

        // If we don't have this user in the DB, add them.
        if (!array_key_exists($user, $this->data[$safe_ns]['joins'])) {
            $this->data[$safe_ns]['joins'][$user] = array(
                    'real_name' => $from,
                    'first_join' => time(),
                    'last_join' => time(),
                    'join_count' => 1);

        } else {
            // Check the delays between now and the first/last join recorded.
            $delay_first = time() - $this->data[$safe_ns]['joins'][$user]['first_join'];
            $delay_last = time() - $this->data[$safe_ns]['joins'][$user]['last_join'];

            // It's been long enough that we can clear their join counts.
            if ($delay_first >= $this->data[$safe_ns]['join_delay']) {
                $this->data[$safe_ns]['joins'][$user]['first_join'] = time();
                $this->data[$safe_ns]['joins'][$user]['last_join'] = time();
                $this->data[$safe_ns]['joins'][$user]['join_count'] = 1;

            } else if ($this->data[$safe_ns]['joins'][$user]['join_count'] >= $this->data[$safe_ns]['max_joins']) {
                // User is join flooding

                // If they're not in the strikes DB, add them.
                if (!array_key_exists($user, $this->data[$safe_ns]['strikes']))
                    $this->data[$safe_ns]['strikes'][$user] = array(
                        'strike_count' => 0,
                        'last_strike' => 0);

                // If they've got more than zero strikes, check them out.
                if ($this->data[$safe_ns]['strikes'][$user]['last_strike'] != 0) {
                    // How long has it been since the last strike?
                    $delay = time() - $this->data[$safe_ns]['strikes'][$user]['last_strike'];

                    // It's been long enough that we can knock one off their counter.
                    if ($delay >= $this->data[$safe_ns]['strike_expiry']) {
                        if ($this->data[$safe_ns]['strikes'][$user]['strike_count'] > 1) {
                            $this->data[$safe_ns]['strikes'][$user]['strike_count']--;
                            $this->data[$safe_ns]['strikes'][$user]['last_strike'] = time();
                        } else {
                            $this->data[$safe_ns]['strikes'][$user]['strike_count'] = 0;
                            $this->data[$safe_ns]['strikes'][$user]['last_strike'] = 0;
                        }
                    }
                }

                // Since they've exceeded the count and gotten a strike, reset the count.
                $this->data[$safe_ns]['joins'][$user]['first_join'] = time();
                $this->data[$safe_ns]['joins'][$user]['last_join'] = time();
                $this->data[$safe_ns]['joins'][$user]['join_count'] = 0;

                // Add another strike.
                $this->data[$safe_ns]['strikes'][$user]['strike_count']++;
                $this->data[$safe_ns]['strikes'][$user]['last_strike'] = time();

                // If they've got enough strikes, penalize them.
                if ($this->data[$safe_ns]['strikes'][$user]['strike_count'] >= $this->data[$safe_ns]['max_strikes']) {
                    // What kind of action are we taking?
                    switch($this->data[$safe_ns]['max_strikes_penalty']) {

                        // The default is to silence them to the specified privclass.
                        default:
                        case 'silence':
                            $this->dAmn->demote($ns, $user, $this->data[$safe_ns]['silenced_privclass']);

                            // If they've not in the database (they shouldn't be.) add them.
                            if (!array_key_exists('silences', $this->data[$safe_ns]))
                                $this->data[$safe_ns]['silences'] = array();

                            // Make sure we know why they were silenced.
                            $this->data[$safe_ns]['silences'][$user] = array(
                                    'reason' => 'Join flooding.',
                                    'timestamp' => time());
                            break;

                        // A bit more harsh, ban them from the channel and let them know why.
                        case 'ban':
                            $this->dAmn->kick($ns, $user, 'You\'ve been banned from the channel for flooding. Inquire with an admin to be let back in.');
                            $this->dAmn->ban($ns, $user);

                            // If they've not in the database (they shouldn't be.) add them.
                            if (!array_key_exists('bans', $this->data[$safe_ns]))
                                $this->data[$safe_ns]['bans'] = array();

                            // Make sure we know why they were banned.
                            $this->data[$safe_ns]['bans'][$user] = array(
                                    'reason' => 'Join flooding.',
                                    'timestamp' => time());
                            break;

                    }

                    // We've taken action, we can now clear their strikes.
                    $this->data[$safe_ns]['strikes'][$user]['strike_count'] = 0;
                    $this->data[$safe_ns]['strikes'][$user]['last_strike'] = 0;
                }

            } else {
                // Toll up the join counter.
                $this->data[$safe_ns]['joins'][$user]['last_join'] = time();
                $this->data[$safe_ns]['joins'][$user]['join_count']++;

            }

        }

        // Make sure we save the data.
        $this->save_data();

    }

    // Load data from the config file.
    function load_data() {
        $this->data = $this->Read('asdata', 2);

        if (false === $this->data)
            $this->data = array();

        if(!empty($this->data)) {
            $this->hook('e_check_msgs', 'recv_msg');
            $this->hook('e_check_join', 'recv_join');
        } else {
            $this->unhook('e_check_msgs', 'recv_msg');
            $this->unhook('e_check_join', 'recv_join');
        }
    }

    // Save data to the config file.
    function save_data() {
        if(empty($this->data)) {
            $this->Unlink('asdata');

            $this->unhook('e_check_msgs', 'recv_msg');
            $this->unhook('e_check_join', 'recv_join');
        } else {
            $this->Write('asdata', $this->data, 2);

            $this->hook('e_check_msgs', 'recv_msg');
            $this->hook('e_check_join', 'recv_join');
        }
    }

}

    // Make sure we init a new instance of our module into the bot.
    new AntiSpam($core);

?>
