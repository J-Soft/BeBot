<?php
/*
* BuddyQueue.php - Queue plugin to prevent flooding
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
$buddy_queue_core = new Buddy_Queue_Core($bot);
/*
The Class itself...
*/
class Buddy_Queue_Core extends BasePassiveModule
{

    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_module("buddy_queue");

        $this->bot->core("settings")
            ->create("Buddy_Queue", "Enabled", TRUE, "Should buddies be queued or added as requested? (Queueing buddies may slow down the bot.)");
        $this->bot->core("settings")
            ->create("Buddy_Queue", "Rate", 1, "How many buddy add and removes should be done per second?", "1;2;3;4;5;6;7;8;9;10");
        $this->bot->core("settings")
            ->register_callback("Buddy_Queue", "Rate", $this);
        $this->settings(FALSE, FALSE, FALSE, $this->bot->core("settings")
            ->get("Buddy_Queue", "Rate"), FALSE);
    }


    function settings($user, $module, $setting, $new, $old)
    {
        $rate = 1 / $new;
        $max  = $new * 2;
        $this->bot->core("queue")->register($this, "buddy", $rate, $max);
    }


    function do_add($uid)
    {
        if (!empty($uid) && $uid != 0 && $uid != -1) {
            if (!($this->bot->core("chat")->buddy_exists($uid))) {
                $this->bot->aoc->buddy_add($uid);
                $this->bot->log("BUDDY QUEUE", "BUDDY-ADD", $this->bot
                    ->core("player")->name($uid));
            }
        }
        else
        {
            $this->bot->log("BUDDY QUEUE", "BUDDY-ERROR", "Tried to add " . $this->bot
                ->core("player")
                ->name($uid) . " as a buddy when they already are one.");
        }
    }


    function do_delete($uid)
    {
        if (!empty($uid) && $uid != 0 && $uid != -1) {
            if (($this->bot->core("chat")->buddy_exists($uid))) {
                $this->bot->aoc->buddy_remove($uid);
                $this->bot->core("online")->logoff($this->bot->core("player")
                    ->name($uid));
                $this->bot->log("BUDDY QUEUE", "BUDDY-DEL", $this->bot
                    ->core("player")->name($uid));
            }
            else
            {
                $this->bot->log("BUDDY QUEUE", "BUDDY-ERROR", "Tried to remove " . $this->bot
                    ->core("player")
                    ->name($uid) . " as a buddy when they are not one.");
            }
        }
    }


    /*
    This gets called on cron
    */
    function queue($module, $info)
    {
        if ($info[1]) {
            $this->do_add($info[0]);
        }
        else
        {
            $this->do_delete($info[0]);
        }
    }


    /*
    Checks if buddy can be added or removed. true if yes, false it has to be put to queue
    */
    function check_queue()
    {
        if (!$this->bot->core("settings")->get("Buddy_Queue", "Enabled")) {
            Return TRUE;
        }
        else
        {
            return $this->bot->core("queue")->check_queue("buddy");
        }
    }


    /*
    Puts a buddy into the queue
    $type is a boolean, true means add the uid, false means delete it
    */
    function into_queue($uid, $type)
    {
        $info = array($uid,
                      $type);
        return $this->bot->core("queue")->into_queue("buddy", $info);
    }
}

?>
