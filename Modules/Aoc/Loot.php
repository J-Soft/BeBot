<?php
/*
* Loot Module - allows you to flatroll items
* Module originally coded by Craized <http://www.craized.net>
* Module heavily updated and rewritten by Ebag333
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 J-Soft and the BeBot development team.
*
* Developed by:
* - Alreadythere (RK2)
* - Blondengy (RK1)
* - Blueeagl3 (RK1)
* - Glarawyn (RK1)
* - Khalem (RK1)
* - Naturalistic (RK1)
* - Temar (RK1)
* - Bitnykk (RK5)
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
/*
Add a "_" at the beginning of the file (_Loot.php) if you do not want it to be loaded.
*/
$loot = new Rolls($bot);
/*
The Class itself...
*/
class Rolls extends BaseActiveModule
{
	
	var $leftovers = array();
	var $loot = array();
	var $count, $addmsg;

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->count = 0;
        $this->register_command(
            "gc",
            "loot",
            "ANONYMOUS",
            array(
                 "add" => "ANONYMOUS",
                 "rem" => "ANONYMOUS",
                 "list" => "ANONYMOUS",
                 "clear" => "ADMIN",
                 "result" => "ADMIN",
                 "reroll" => "ADMIN"
            )
        );
        $this->register_command(
            "tell",
            "loot",
            "ANONYMOUS",
            array(
                 "add" => "ANONYMOUS",
                 "rem" => "ANONYMOUS",
                 "list" => "ANONYMOUS",
                 "clear" => "ADMIN",
                 "result" => "ADMIN",
                 "reroll" => "ADMIN"
            )
        );
        $this->bot->core("settings")
            ->create(
                "Loot",
                "Roll",
                "SINGLE",
                "Should you be allowed to be added to the roll of more than one slot?",
                "SINGLE;MULTI"
            );
        $this->bot->core("colors")
            ->define_scheme("loot", "highlight", "yellow");
        $this->help['description'] = 'Module to flat roll on items.';
        $this->help['command']['loot <item>'] = "Adds an item to the roll list.";
        $this->help['command']['loot add <slot>'] = "Adds your name to the slot number.  Add 0 removes you from all slots.";
        $this->help['command']['loot rem <slot>'] = "Removes your name from the slot number.";
        $this->help['command']['loot list'] = "Lists all items and who is rolling for them.";
        $this->help['command']['loot clear'] = "Clears all rolls.";
        $this->help['command']['loot result'] = "Rolls for all the items and announces winners.";
        $this->help['command']['loot reroll'] = "Adds any unwon items from the last roll to a new roll.";
    }


    /*
    This function handles all the inputs and returns FALSE if the
    handler should not send output, otherwise returns a string
    sutible for output via send_tell, send_gc, and send_pgroup.
    */
    function command_handler($name, $msg, $source)
    { // Start function handler()
        $this->error->reset(); //Reset the error message so we don't trigger the handler by old error messages.
        $com = $this->parse_com(
            $msg,
            array(
                 'com',
                 'sub',
                 'args'
            )
        );
        switch (strtolower($com['sub'])) {
            case 'clear':
                unset($this->loot); $this->loot = array();
                unset($this->leftovers); $this->leftovers = array();
                $this->count = 0;
                $this->bot->send_gc("##loot_highlight##" . $name . "##end## cancelled the loot rolls in progress");
                break;
            case 'result':
                $this->roll($name);
                break;
            case 'list':
                $this->rlist();
                break;
            case 'add':
                $this->add($name, $com['args'], false);
                break;
            case 'reroll':
                $this->reroll($name);
                break;
            case 'rem':
                if (is_numeric($com['args']) && (int)$com['args'] == (float)$com['args'] && array_key_exists(
                        $name,
                        $this->loot[$com['args']]
                    )
                ) {
                    unset($this->loot[$com['args']][$name]);
                    $this->bot->send_gc(
                        "##loot_highlight##" . $name . "##end## removed from rolls in slot##loot_highlight## #" . $com['args']
                    );
                } else {
                    $this->bot->send_help($name);
                }
                break;
            default:
                if (!empty($com['sub']) || !empty($com['args'])) {
                    $this->loot($com['sub'] . ' ' . $com['args'], $name);
                } else {
                    $this->bot->send_help($name);
                }
        }
    } // End function handler()

    /***********************************************************************************************************/
    function add($name, $slot)
    {
        if ($slot == 0) {
            $slots = array_keys($this->loot);
            foreach ($slots as $key => $sslot) {
                $list = array_keys($this->loot[$sslot]);
                foreach ($list as $playerslot => $player) {
                    if ($player == $name) {
                        unset($this->loot[$sslot][$player]);
                    }
                }
            }
            $this->addmsg = "##loot_highlight##" . $name . "##end## removed from all slots.";
        } else {
            $present = false;
            if ($this->loot[$slot]) {
                if ($this->bot->core("settings")
                        ->get('Loot', 'Roll') == "SINGLE"
                ) {
                    $slots = array_keys($this->loot);
                    foreach ($slots as $key => $sslot) {
                        $list = array_keys($this->loot[$sslot]);
                        foreach ($list as $playerslot => $player) {
                            if ($player == $name) {
                                unset($this->loot[$sslot][$player]);
                                $present = true;
                            }
                        }
                    }
                    if ($present == true) {
                        $this->addmsg = "##loot_highlight##" . $name . "##end## changed to slot##loot_highlight## #" . $slot . "##end##";
                    } else {
                        $this->addmsg = "##loot_highlight##" . $name . "##end## assigned to slot##loot_highlight## #" . $slot . "##end##";
                    }
                    $this->loot[$slot][$name] = 1;
                } else {
                    $this->loot[$slot][$name] = 1;
                    $this->addmsg = "##loot_highlight##" . $name . "##end## assiged to slot##loot_highlight## #" . $slot . "##end##";
                }
            } else {
                $this->addmsg = "There is currently no roll in slot $slot";
            }
        }
        $this->bot->send_gc($this->addmsg);
    }


    function loot($msg, $name)
    {
        $notyet = true;
        for ($i = 1; $i <= $this->count; $i++) {
            if ($msg == $this->loot[$i]['item']) {
                $this->loot[$i]['num']++;
                $num = $this->loot[$i]['num'];
                $notyet = false;
                $numslot = $i;
            }
        }
        if ($notyet) {
            $this->count++;
            $num = 1;
            $numslot = $this->count;
            $this->loot[$numslot]['item'] = $msg;
            $this->loot[$numslot]['num'] = 1;
        }
        $this->bot->send_gc(
            "##loot_highlight##" . $num . "x " . $msg . "##end## being rolled in slot##loot_highlight## #" . $numslot
        );
        if ($this->count == 1) {
            unset($this->leftovers); $this->leftovers = array();
        }
    }


    function roll($name)
    {
        $num = 1;
        $lcount = 0;
		$msg = "";
        foreach ($this->loot as $slot) {
            $item = $slot['item'];
            unset($slot['item']);
            $numitems = $slot['num'];
            unset($slot['num']);
            for ($k = 0; $k < $numitems; $k++) {
                $users = array();
                $list = $slot;
                $users = array_keys($list);
                $count = count($list) - 1;
				
				if($count>0) {
                for ($i = 1; $i <= 10000; $i++) {
                    $list[$users[$this->bot->core("tools")
                        ->my_rand(0, $count)]]
                        += 1;
                }}
                natsort($list);
				$winner = "";
                foreach ($list as $name => $points) {
                    $winner = $name;
                }
                if (!$winner) {
                    $winner = "Nobody";
                    $lcount = count($this->leftovers) + 1;
                    $this->leftovers[$lcount] = $item;
                } else {
                    unset($slot[$winner]);
                }
                $msg .= "##loot_highlight##Item: ##end##" . $item . "  (Slot##loot_highlight## #" . $num . "##end##)\n";
                $msg .= "##loot_highlight##Winner: ##end##" . $winner . "\n\n";
                unset($users);
                unset($winner);
                unset($list);
            }
            unset($this->loot[$num]);
            $num++;
        }
        $blob = "Item Winners List :: " . $this->bot->core("tools")
                ->make_blob("click to view", $msg);
        $this->bot->send_gc($blob);
        $this->count = 0;
    }


    function reroll($name)
    {
        $lcount = count($this->leftovers);
        if ($lcount == 0) {
            $this->bot->send_gc("##loot_highlight##No leftovers from last roll.##end##");
        } else {
            $this->count = 0;
            foreach ($this->leftovers as $item) {
                $notyet = true;
                for ($i = 1; $i <= $this->count; $i++) {
                    if ($item == $this->loot[$i]['item']) {
                        $this->loot[$i]['num']++;
                        $num = $this->loot[$i]['num'];
                        $notyet = false;
                        $numslot = $i;
                    }
                }
                if ($notyet) {
                    $this->count++;
                    $num = 1;
                    $numslot = $this->count;
                    $this->loot[$numslot]['item'] = $item;
                    $this->loot[$numslot]['num'] = 1;
                }
                $msg .= "##loot_highlight##" . $num . "x " . $item . "##end## being rolled in slot##loot_highlight## #" . $numslot . "##end##.\n";
            }
            $blob = "Item Roll List :: " . $this->bot->core("tools")
                    ->make_blob("click to view", $msg);
            $this->bot->send_gc($blob);
            unset($this->leftovers); $this->leftovers = array();
        }
    }


    function rlist()
    {
        $num = 0; $msg = "";
        foreach ($this->loot as $slot) {
            $num++;
            $msg .= "Slot ##loot_highlight###" . $num . "##end##: (" . $this->bot
                    ->core("tools")
                    ->chatcmd("loot add " . $num, "Add") . "/" . $this->bot
                    ->core("tools")->chatcmd("loot rem " . $num, "Remove") . ")\n";
            $msg .= "Item: ##loot_highlight##" . $slot['item'] . "##end## (##loot_highlight##" . $slot['num'] . "x##end##)\n";
            if (count($slot) == 1) {
                $msg .= "";
            } else {
                $list = array_keys($slot);
                foreach ($list as $key => $player) {
                    if (($player != "item") && ($player != "num") && ($slot[$player] == 2)) {
                        $msg .= " [##loot_highlight##$player##end##]";
                    }
                }
                foreach ($list as $key => $player) {
                    if (($player != "item") && ($player != "num") && ($slot[$player] == 1)) {
                        $msg .= " [##loot_highlight##$player##end##]";
                    }
                }
            }
            $msg .= "\n\n";
        }
        $blob = "Item Roll List :: " . $this->bot->core("tools")
                ->make_blob("click to view", $msg);
        $this->bot->send_gc($blob);
    }
}

?>
