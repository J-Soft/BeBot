<?php
/*
* Countdown.php - A simple countdown plugin
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
$countdown = new Countdown($bot);
/*
The Class itself...
*/
class Countdown extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command('all', 'countdown', 'MEMBER');
        $this->register_alias("countdown", "cd");
        $this->register_event("timer", "countdown");
        $classid = $this->bot->core("timer")
            ->create_timer_class("Countdown", "Notify class used for countdowns, only last 5secs are shown.");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, -2, 0, "", "[##lightgreen##--&gt; GO GO GO &lt;--##end##]");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 1, "", "[##orange##--------&gt; 1 &lt;-------##end##]");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 2, "", "[##orange##--------&gt; 2 &lt;-------##end##]");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 3, "", "[##orange##--------&gt; 3 &lt;-------##end##]");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 4, "", "[##red##--------&gt; 4 &lt;-------##end##]");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 5, "", "[##red##--------&gt; 5 &lt;-------##end##]");
        $this->bot->core("settings")
            ->create("Countdown", "Channel", "both", "In which channel should a countdown be shown? In the channel of origin, or in both gc and pgmsg?", "both;gc;pgmsg;origin");
        $this->help['description'] = "A simple countdown plugin.";
        $this->help['command']['countdown'] = "Counts down to zero.";
        $this->help['notes'] = "<pre>cd is a synonym for <pre>countdown.";
    }


    function timer($name, $prefix, $suffix, $delay)
    {
        $parts = explode(" ", $name);
        $user = $parts[0];
        $origin = $parts[1];
        $out = $this->bot->core("settings")->get("Countdown", "Channel");
        if (strtolower($out) == 'origin') {
            $this->bot->send_output($user, $prefix . $suffix, $origin);
        }
        else {
            $this->bot->send_output($user, $prefix . $suffix, $out);
        }
    }


    /*
    This gets called on a msg in the privgroup with the command
    */
    function command_handler($name, $msg, $origin)
    {
        $ret = $this->bot->core("timer")
            ->add_timer(false, "countdown", 6, $name . " " . $origin, "internal", 0, "Countdown");
        return "Countdown started!";
    }
}

?>
