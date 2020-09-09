<?php
/*
* Raffle.php - Module that handles raffles.
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
/*
Add a "_" at the beginning of the file (_ClassName.php) if you do not want it to be loaded.
*/
$raffle = new Raffle($bot);
/*
The Class itself...
*/
class Raffle extends BaseActiveModule
{
    var $item;
    var $item_blank;
    var $users = array();
    var $output;
    var $admin;
    var $result;


    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->output = "group";
        $this->result = "";
        $this->register_command("all", "raffle", "GUEST");
        $this->help['description'] = 'Module to handle item lotteries';
        $this->help['command']['raffle start <item>'] = "Starts a raffle for item <item>.";
        $this->help['command']['raffle reannounce'] = "Announces the current raffle again.";
        $this->help['command']['raffle cancel'] = "Cancels the current raffle.";
        $this->help['command']['raffle closing'] = "Close the current raffle.";
        $this->help['command']['raffle result'] = "Announce the winner(s) of the last raffle.";
        $this->help['command']['raffle output <(group|guild|both)>'] = "Chooses which channel raffles should be announced to.";
        $this->help['command']['raffle join'] = "Joins the current raffle.";
        $this->help['command']['raffle leave'] = "Leaves the current raffle.";
        $this->help['notes'] = "Notes for the help goes in here.";
        $this->bot->core("settings")
            ->create("Raffle", "timer", 0, "How Long shold a Raffle Last? 0 = disabled");
        $this->bot->core("colors")
            ->define_scheme("raffle", "highlight", "yellow");
    }


    function command_handler($name, $msg, $origin)
    {
        if (preg_match("/^raffle start (.+)/i", $msg, $info)) {
            $this->raffle_start($name, $info[1]);
        } else {
            if (preg_match("/^raffle join/i", $msg, $info)) {
                $this->raffle_join($name);
            } else {
                if (preg_match("/^raffle leave/i", $msg, $info)) {
                    $this->raffle_leave($name);
                } else {
                    if (preg_match("/^raffle output (group|guild|both)$/i", $msg, $info)) {
                        $this->raffle_output($name, $info[1]);
                    } else {
                        if (preg_match("/^raffle cancel$/i", $msg, $info)) {
                            $this->raffle_cancel($name);
                        } else {
                            if (preg_match("/^raffle reannounce$/i", $msg, $info)) {
                                $this->raffle_reannounce($name);
                            } else {
                                if (preg_match("/^raffle closing$/i", $msg, $info)) {
                                    $this->raffle_closing($name);
                                } else {
                                    if (preg_match("/^raffle result$/i", $msg, $info)) {
                                        $this->raffle_result($name);
                                    } else {
                                        if (preg_match("/^raffle admin$/i", $msg, $info)) {
                                            $this->bot->send_tell($name, $this->make_admin($name));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    /*
    End raffle
    */
    function raffle_result($name)
    {
        if (!empty($this->item) && !empty($this->users)
            && (($this->admin == $name)
                || $this->bot
                    ->core("security")->check_access($name, "admin"))
        ) {
            $users = array_keys($this->users);
            for ($i = 0; $i < 5; $i++) {
                shuffle($users);
            }

            $usr_num = count($users);
            $max = 1000 * $usr_num - $usr_num;
            if ($max > 29000) {
                $max = 30000 - $usr_num;
            }

            for ($i = 0; $i < $max; $i++) {
                $this->users[$users[$this->bot->core("tools")
                    ->my_rand(0, ($usr_num - 1))]]
                    += 1;
            }

            natsort($this->users);
            $results = "##ao_ccheader##::::: Raffle Results :::::##end####lightyellow##\n\n";
            $results .= "##highlight##" . $this->admin . "##end## raffled ##highlight##" . $this->item_blank;
            $results .= "##end##. I rolled $i times using " . $this->bot->core(
                    "tools"
                )->randomsource . " for random numbers. The results where:\n\n";
            $winner = "";
            $res = "";
            $count = count($this->users);
            foreach ($this->users as $key => $points) {
                if ($count == 1) {
                    $winner = $key;
                }
                $res = "##highlight##" . $count . ".##end## $key ##highlight##" . $points . " points##end##\n" . $res;
                $count--;
            }
            $results .= $res;
            $this->output(
                "\n##raffle_highlight##--------------------------------------------------------##end##\n" . "  ##raffle_highlight##" . $winner
                . "##end## has won the raffle for ##raffle_highlight##" . $this->item . "##end##! :: " . $this->bot
                    ->core("tools")
                    ->make_blob(
                        "view results",
                        $results
                    ) . "\n" . "##raffle_highlight##----------------------------------------------------------##end##"
            );
            $this->users = array();
            $this->item = "";
            $this->admin = "";
            $this->item_blank = "";
            $this->result = "Results from the last raffle: " . $this->bot
                    ->core("tools")->make_blob("view results", $results);
        } else {
            if (empty($this->item)) {
                $this->bot->send_tell($name, "There is no raffle currently running.\n" . $this->result);
            } else {
                if (empty($this->users)) {
                    $this->bot->send_tell($name, "Noone is in the raffle yet.");
                } else {
                    $this->bot->send_tell($name, "You did not start the raffle nore are you bot administrator.");
                }
            }
        }
    }


    /*
    Cancel raffle
    */
    function raffle_cancel($name)
    {
        if (!empty($this->item)
            && (($this->admin == $name)
                || $this->bot
                    ->core("security")->check_access($name, "admin"))
        ) {
            $this->output("##raffle_highlight##$name##end## has canceled the raffle.");
            $this->users = array();
            $this->item = "";
            $this->admin = "";
            $this->item_blank = "";
        } else {
            if (empty($this->item)) {
                $this->bot->send_tell($name, "There is no raffle currently running.\n" . $this->result);
            } else {
                $this->bot->send_tell($name, "You did not start the raffle nore are you bot administrator.");
            }
        }
    }


    /*
    Reannounces raffle
    */
    function raffle_reannounce($name, $secs = false)
    {
        if (isset($this->item)
            && (($this->admin == $name)
                || $this->bot
                    ->core("security")->check_access($name, "admin"))
        ) {
            $output
                = "\n##raffle_highlight##--------------------------------------------------------##end##\n" . "  The raffle for ##raffle_highlight##" . $this->item . "##end## ";
            if ($secs) {
                $output .= "has $secs Seconds Remaining :: ";
            } else {
                $output .= "is still running :: ";
            }
            $output .= $this->click_join(
                    "join"
                ) . "\n" . "##raffle_highlight##----------------------------------------------------------##end##";
            $this->output($output);
        } else {
            if (!isset($this->item)) {
                $this->bot->send_tell($name, "There is no raffle currently running.");
            } else {
                $this->bot->send_tell($name, "You did not start the raffle nore are you bot administrator.");
            }
        }
    }


    /*
    Reannounces raffle
    */
    function raffle_closing($name)
    {
        if (isset($this->item)
            && (($this->admin == $name)
                || $this->bot
                    ->core("security")->check_access($name, "admin"))
        ) {
            $this->output(
                "\n##raffle_highlight##----------------------------------------------------------##end##\n" . "  The raffle for ##raffle_highlight##" . $this->item
                . "##end## wil be " . "##raffle_highlight##closing soon##end## :: " . $this->click_join("join") . "\n"
                . "##raffle_highlight##----------------------------------------------------------##end##"
            );
        } else {
            if (!isset($this->item)) {
                $this->bot->send_tell($name, "There is no raffle currently running.");
            } else {
                $this->bot->send_tell($name, "You did not start the raffle nore are you bot administrator.");
            }
        }
    }


    /*
    Change output chan
    */
    function raffle_output($name, $chan)
    {
        if (($this->admin == $name)
            || $this->bot->core("security")
                ->check_access($name, "admin")
        ) {
            $this->output = $chan;
            $this->output("Raffle output set to ##raffle_highlight##" . $chan . ".");
        } else {
            $this->bot->send_tell($name, "You did not start the raffle nore are you bot administrator.");
        }
    }


    /*
    Join the raffle
    */
    function raffle_join($name)
    {
        if (empty($this->item)) {
            $this->bot->send_tell($name, "There is no raffle running at the moment.");
        } else {
            if (isset($this->users[$name])) {
                $this->bot->send_tell($name, "You are already in the raffle.");
            } else {
                $this->users[$name] = 1;
                $this->bot->send_tell($name, "You have joined the raffle. " . $this->click_join("leave"), 1);
                $this->output(
                    "##raffle_highlight##" . $name . "##end## has ##raffle_highlight##joined##end##" . " the raffle ::" . $this->click_join(
                        "join"
                    ),
                    1
                );
            }
        }
    }


    /*
    Leave the raffle
    */
    function raffle_leave($name)
    {
        if (!isset($this->item)) {
            $this->bot->send_tell($name, "There is no raffle running at the moment.");
        } else {
            if (!isset($this->users[$name])) {
                $this->bot->send_tell($name, "You are not in the raffle.");
            } else {
                unset($this->users[$name]);
                $this->bot->send_tell($name, "You have left the raffle. " . $this->click_join("join"), 1);
                $this->output(
                    "##raffle_highlight##" . $name . "##end## has ##raffle_highlight##left##end##" . " the raffle :: " . $this->click_join(
                        "join"
                    ),
                    1
                );
            }
        }
    }


    /*
    Starts the raffle
    */
    function raffle_start($name, $item)
    {
        if (empty($this->item)) {
            $itemref = explode(" ", $item, 5);
            if (strtolower($itemref[0]) == "&item&") {
                $item = $this->bot->core("tools")
                    ->make_item($itemref[1], $itemref[2], $itemref[3], $itemref[4], true);
            }
            $this->item = $item;
            $this->item_blank = preg_replace("/<\/a>/U", "", preg_replace("/<a href(.+)>/sU", "", $item));
            $this->users = array();
            $this->admin = $name;
            $timer = $this->bot->core("settings")
                ->get("Raffle", "timer");
            if ($timer > 0) {
                $this->register_event("cron", "2sec");
                $this->end = time() + $timer;
                if ($timer > 20) {
                    $this->announce = 2;
                } elseif ($timer > 10) {
                    $this->announce = 1;
                } else {
                    $this->announce = 0;
                }
            }
            $output = "\n##raffle_highlight##----------------------------------------------------------##end##\n";
            $output .= "  ##raffle_highlight##" . $name . "##end## has started a raffle for ##raffle_highlight##" . $item . "##end## :: " . $this->click_join(
                    "join"
                );
            $output .= "\n##raffle_highlight##----------------------------------------------------------##end##";
            $this->output($output);
            $this->bot->send_tell($name, $this->make_admin($name));
        } else {
            $this->bot->send_tell($name, "A raffle is already running.");
        }
    }


    /*
    Raffle Admin Menu
    */
    function make_admin($name)
    {
        if (empty($this->item)) {
            return "There is no raffle running.";
        } else {
            if (!$this->bot->core("security")
                    ->check_access($name, "admin")
                && !($this->admin == $name)
            ) {
                return "You did not start the raffle and are not a bot admin.";
            } else {
                $inside = "##ao_ccheader##:::: Raffle Administration ::::##end##<font color=CCInfoHeader>\n\n";
                $inside .= "Output channel: \n";
                $inside .= $this->bot->core("tools")
                        ->chatcmd("raffle output guild", "Guild") . " ";
                $inside .= $this->bot->core("tools")
                        ->chatcmd("raffle output group", "Group") . " ";
                $inside .= $this->bot->core("tools")
                        ->chatcmd("raffle output both", "Both") . "\n\n";
                $inside .= "Item: " . $this->item_blank . "\n\n";
                $inside .= "- " . $this->bot->core("tools")
                        ->chatcmd("raffle join", "Join the raffle") . "\n";
                $inside .= "- " . $this->bot->core("tools")
                        ->chatcmd("raffle leave", "Leave the raffle") . "\n\n";
                $inside .= "Cancel raffle: " . $this->bot->core("tools")
                        ->chatcmd("raffle cancel", "click") . "\n\n";
                $inside .= "Announce raffle still open: " . $this->bot
                        ->core("tools")->chatcmd("raffle reannounce", "click") . "\n\n";
                $inside .= "Announce raffle closing soon: " . $this->bot
                        ->core("tools")->chatcmd("raffle closing", "click") . "\n\n";
                $inside .= ":: " . $this->bot->core("tools")
                        ->chatcmd("raffle result", "Show winner!") . " ::\n";
                return "Raffle ##raffle_highlight##Admin##end## menu: " . $this->bot
                    ->core("tools")->make_blob("click to view", $inside);
            }
        }
    }


    /*
    Makes the "click to join" tag...
    */
    function click_join($val)
    {
        $inside = "##ao_ccheader##:::: Join/Leave Raffle ::::##end####lightyellow##\n\n";
        $inside .= "Raffle for: ##highlight##" . $this->item_blank . "##end##\n\n";
        $inside .= "- " . $this->bot->core("tools")
                ->chatcmd("raffle join", "Join the raffle") . "\n";
        $inside .= "- " . $this->bot->core("tools")
                ->chatcmd("raffle leave", "Leave the raffle") . "\n";
        return $this->bot->core("tools")
            ->make_blob("click to " . $val, $inside);
    }


    /*
    Outputs to the right chan
    */
    function output($msg, $low = 0)
    {
        if (($this->output == "guild") || ($this->output == "both")) {
            $this->bot->send_gc($msg, $low);
        }
        if (($this->output == "group") || ($this->output == "both")) {
            $this->bot->send_pgroup($msg);
        }
    }


    function cron()
    {
        if ($this->announce == 2 && ($this->end - time()) < 20) {
            $this->raffle_reannounce($this->admin, 20);
            $this->announce = 1;
        }
        if ($this->announce == 1 && ($this->end - time()) < 10) {
            $this->raffle_reannounce($this->admin, 10);
            $this->announce = 0;
        }
        if ($this->end < time()) {
            $this->unregister_event("cron", "2sec");
            if (!empty($this->users)) {
                $this->raffle_result($this->admin);
            } else {
                $this->output("Raffle for " . $this->item . " ended with no users.");
                $this->users = array();
                $this->item = "";
                $this->admin = "";
                $this->item_blank = "";
            }
        }
    }
}

?>
