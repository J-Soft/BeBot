<?php
/*
* Is.php - Check if a player is online.
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2012 J-Soft and the BeBot development team.
*
* Developed by:
* - Alreadythere (RK2)
* - Blondengy (RK1)
* - Blueeagl3 (RK1)
* - Glarawyn (RK1)
* - Khalem (RK1)
* - Naturalistic (RK1)
* - Temar (RK1)
*
* See Credits file for all acknowledgements.
*
*  This program is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; version 2 of the License only.
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with this program; if not, write to the Free Software
*  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
*  USA
*/
$is = new Is($bot);
/*
The Class itself...
*/
class Is extends BaseActiveModule
{
    /*
    $is_queue['user_looking_up']['target']
    The target being looked up is either 'Unknown', 'Waiting', 'Queued', 'Offline', 'Online' or 'Timeout'

    $is_queue['user_looking_up']['trg']
    The target that was passed to the !is command

    $is_queue['user_looking_up']['tmo']
    Timeout for people waiting in queue

    $is_queue['user_looking_up']['chn']
    The channel in which user_looking_up invoked the !is command.
    */
    private $is_queue = array();
    private $special_entries
        = array(
            'trg',
            'tmo',
            'chn'
        );
    //Counter holding how big part of the buddy queue we are currently using.
    private $queue_counter = 0;


    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command("all", "is", "GUEST");
        $this->register_event("buddy");
        $this->register_event("cron", "3sec");
        $this->bot->core("settings")
            ->create(
            "Is", "Errormsg", TRUE, "Display error message on invalid username? (Turning this off is reccomended when you are not using a command prefix.)", "On;Off", FALSE, 5
        );
        //This setting controls how big part of the buddy list is allocated to checking online status.
        $this->bot->core("settings")
            ->create("Is", "Buddy_slots", 20, "How big portion of the buddy list should be reserved for lookups?", "5;10;15;20;25;30;50");
        $this->bot->core("settings")
            ->create("Is", "Timeout", 15, "How long should we wait for lookups to complete?", "10;15;20;25;30;60");
        $this->bot->core("settings")
            ->create("Is", "CheckAlts", TRUE, "Should Alts be Checked?");
        $this->help['description'] = 'Shows online status for a player.';
        $this->help['command']['is <name>'] = "Shows if player <name> is online or offline";
    }


    function command_handler($name, $msg, $origin)
    {
        //Check if a is-request is being processed
        if (isset($this->is_queue[$name])) {
            return ('Please wait until your previous lookup is completed');
        }
        $com = $this->parse_com(
            $msg, array(
                'com',
                'player'
            )
        );
        $player = $this->bot->core('tools')->validate_player($com['player']);
        if ($player instanceof BotError) {
            unset($this->is_queue[$name]);
            return ($player);
        }
        if ($player == ucfirst(strtolower($this->bot->botname))) {
            unset($this->is_queue[$name]);
            return ("I'm online!");
        }
        if ($this->bot->core("settings")->get("Is", "Checkalts")) {
            //Get a list of all known alts of $player
            $main = $this->bot->core("alts")->main($player);
            $alts = $this->bot->core("alts")->get_alts($main);
            //Add the main to the list so we've got everybody in one list.
            $alts[] = $main;
        }
        else {
            //Not checking alts, but we use the same code to check the single player
            $alts[] = $player;
        }
        if (in_array($name, $alts)) {
            unset($this->is_queue[$name]);
            return ("Why are you asking me if you are online?!");
        }
        $this->is_queue[$name]['chn'] = $origin;
        $this->is_queue[$name]['trg'] = $player;
        $this->is_queue[$name]['tmo'] = time() + $this->bot->core('settings')
            ->get('Is', 'Timeout');
        foreach ($alts as $index => $alt) {
            //Check each of them if they are in the buddy list
            if ($this->bot->core('chat')->buddy_exists($alt)) {
                //If they are, check if they are online
                if ($this->bot->core('chat')->buddy_online($alt)) {
                    $this->is_queue[$name][$alt] = 'Online';
                }
                else {
                    $this->is_queue[$name][$alt] = 'Offline';
                }
                //Alt processed. We don't need to do anything else with it.
                unset($alts[$index]);
            }
        }
        //If the alts list is empty all alts have been processed. Return the result.
        if (empty($alts)) {
            $this->send($name);
        }
        else {
            //The names now remaining in the list of alts need to be checked further
            foreach ($alts as &$alt) {
                //And add to buddy list unless we've already used the alotted space
                if ($this->queue_counter < $this->bot->core('settings')
                    ->get('Is', 'Buddy_slots')
                ) {
                    $this->is_queue[$name][$alt] = 'Queued';
                    $this->bot->core('chat')->buddy_add($alt);
                    $this->queue_counter++;
                    unset($alt);
                }
                else {
                    //Put him on hold if the alotted space is used.
                    $this->is_queue[$name][$alt] = 'Waiting';
                }
            }
        }
    }


    /*
    This gets called if a buddy logs on/off
    */
    function buddy($name, $msg)
    {
        if ($msg == 1 || $msg == 0) {
            //If the queue is empty there's nothing to do.
            if (!empty($this->is_queue)) {
                //Check if this player is in queue to be checked.
                foreach ($this->is_queue as $source => &$targets) {
                    foreach ($targets as $player => $status) {
                        if ($name == $player) {
                            if ($msg == 1) {
                                $this->is_queue[$source][$name] = 'Online';
                            }
                            else {
                                $this->is_queue[$source][$name] = 'Offline';
                            }
                            // This toon is removed by the inc_buddy() function of the buddy list already.
                            // No buddy_remove() needed.
                            $this->queue_counter--;
                        }
                    }
                    //Check if all alts of this toon has been checked.
                    $complete = TRUE;
                    foreach ($targets as $player => $status) {
                        if (!in_array($player, $this->special_entries) && $status !== 'Online' && $status !== 'Offline') {
                            $complete = FALSE;
                        }
                    }
                    if ($complete) {
                        $this->send($source);
                        unset($this->is_queue[$source]);
                    }
                }
            }
        }
    }


    function cron()
    {
        if (!empty($this->is_queue)) {
            //Go trough everyone who has an is-query running
            foreach ($this->is_queue as $source => $targets) {
                //Check for timeouts
                $now = time();
                $timeout = $this->is_queue[$source]['tmo'];
                if ($timeout > $now) {
                    foreach ($targets as $player => $status) {
                        //Check if people are waiting in queue and add them if there's room
                        if ($targets[$player] == 'Waiting'
                            && $this->queue_counter < $this->bot
                                ->core('settings')->get('Is', 'Buddy_slots')
                        ) {
                            $this->is_queue[$source][$player] = 'Queued';
                            $this->bot->core('chat')->buddy_add($player);
                            $this->queue_counter++;
                        }
                    }
                }
                else {
                    //Timeout has occured!!
                    foreach ($targets as $player => $status) {
                        //Set people waiting or in queue as timed out
                        if ($status == 'Waiting' || $status == 'Queued') {
                            $this->is_queue[$source][$player] = "Timeout";
                            //and remove from buddy list if they are on it.
                            if ($this->bot->core('chat')->buddy_exists($player)
                            ) {
                                $this->bot->core('chat')->buddy_remove($player);
                                $this->queue_counter--;
                            }
                        }
                    }
                    $this->send($source);
                }
            }
        }
    }


    function send($name)
    {
        foreach ($this->is_queue[$name] as $player => $status) {
            if ($status == 'Online') {
                $online_list[] = $player;
            }
            if ($status == 'Timeout') {
                $timeout_list[] = $player;
            }
        }
        if (empty($online_list)) {
            $reply = "{$this->is_queue[$name]['trg']} is ##red##Offline##end##";
            $reply .= $this->last_seen($this->is_queue[$name]['trg']);
        }
        else {
            $online = implode(', ', $online_list);
            $reply = "{$this->is_queue[$name]['trg']} is ##lime##Online##end## with $online.";
        }
        if (!empty($timeout_list)) {
            $timeout = implode(', ', $timeout_list);
            $reply .= "\n ##red##WARNING:##end## The following entries timed out: $timeout.";
        }
        $this->bot->send_output($name, $reply, $this->is_queue[$name]['chn']);
        unset($this->is_queue[$name]);
    }


    function last_seen($name)
    {
        $seen = $this->bot->core("online")->get_last_seen(
            $name, $this->bot
                ->core("settings")->get("Is", "Checkalts")
        );
        if ($seen) {
            if ($this->bot->core("settings")->get("Is", "Checkalts")) {
                $msg = ", last seen at ##highlight##" . gmdate(
                    $this->bot
                        ->core("settings")
                        ->get("Time", "FormatString"), $seen[0]
                ) . "##end## on ##highlight##" . $seen[1] . "##end##";
            }
            else {
                $msg = ", last seen at ##highlight##" . gmdate(
                    $this->bot
                        ->core("settings")
                        ->get("Time", "FormatString"), $seen
                ) . "##end##";
            }
        }
        return $msg;
    }
}

?>
