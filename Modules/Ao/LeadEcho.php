<?php
/*
* Leader Echo Module for BE Bot <http://bebot.fieses.net>
* Based on LeadEcho module by Craized
* Extended by Alreadythere
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
$leadecho = new LeadEcho($bot);
/*
The Class itself...
*/
class LeadEcho extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->bot->core("settings")
            ->create("Leader", "Name", "", "The name of the character that took lead.", "", true);
        $this->bot->core("settings")
            ->create("Leader", "Echo", false, "Is the chat echo for the leader activated or nor?");
        $this->bot->core("settings")
            ->create("Leader", "LeaderAccess", true, "Does being leader give you at least LEADER access in the bot?");
        $this->register_command("pgmsg", "leader", "GUEST");
        $this->register_command("pgmsg", "repeat", "LEADER");
        $this->register_event("privgroup");
        $this->register_event("pgleave");
        $this->bot->core("colors")
            ->define_scheme("leader_echo", "spam", "yellow");
        $this->help['description'] = 'Gives some LEADER access and highlights his chat';
        $this->help['command']['leader [<name>]'] = "Toggles leader status for yourself if no name is given, otherwise makes <name> new leader.";
        $this->help['command']['repeat <on|off>'] = "Toggles chat repeat for leader on or off.";
    }


    /*
    This gets called on a msg in the privgroup with a command
    */
    function command_handler($name, $msg, $origin)
    {
        $highlight = $this->bot->core("colors")->get("highlight");
        $repeatstring = "<br>Repeat is ";
        if ($this->bot->core("settings")->get("Leader", "Echo")) {
            $repeatstring .= "##green##activated##end##!";
        } else {
            $repeatstring .= "##red##deactivated##end##!";
        }
        $repeatstring .= " Use !repeat on|off to toggle it.";
        if (preg_match("/^leader$/i", $msg)) {
            if ($this->bot->core("settings")->get("Leader", "Name") != $name) {
                return $this->set_leader($name, $name, $repeatstring);
            } else {
                return $this->set_leader($name, "", $repeatstring);
            }
        } elseif (preg_match("/^leader ?(.*)/i", $msg, $info)) {
            return $this->set_leader($name, ucfirst(strtolower($info[1])), $repeatstring);
        } elseif (preg_match("/^repeat on$/i", $msg)) {
            $this->bot->core("settings")->save("leader", "echo", true);
            return "Repeat is ##green##activated##end##!";
        } elseif (preg_match("/^repeat off$/i", $msg)) {
            $this->bot->core("settings")->save("leader", "echo", false);
            return "Repeat is ##red##deactivated##end##!";
        }
    }


    function set_leader($caller, $leadername, $repeatstring)
    {
        // Check if lead is just cleared, can only happen as current leader due to regexp:
        if ($leadername == "") {
            $this->bot->core("settings")->save("leader", "name", "");
            return "Leader cleared.";
        }
        // Check if new leader exists:
        $id = $this->bot->core('player')->id($leadername);
        if ($id == 0) {
            return "##error##" . $leadername . " does not exist!##end##";
        }
        // Now make sure new leader is in chatgroup:
        if (!$this->bot->core("online")->in_chat($leadername)) {
            return "##error##" . $leadername . " is not in chatgroup!##end##";
        }
        // You can only claim or set leader if it's free or your access level is equal or higher then that of the current leader
        // IMPORTANT: if the setting LeaderAccess is On then the current leader is always at least LEADER!
        if ($this->bot->core("settings")
                ->get("Leader", "Name") != ''
            && ($this->bot->core("security")
                    ->get_access_level($caller) < $this->bot
                    ->core("security")->get_access_level(
                        $this->bot->core("settings")
                            ->get("Leader", "Name")
                    ))
        ) {
            return "##error##" . $caller . ", you can't take lead from " . $this->bot
                ->core("settings")->get("Leader", "Name") . "##end##";
        }
        // All checks done, now we can set the new leader:
        $this->bot->core("settings")->save("leader", "name", $leadername);
        return "##highlight##" . $leadername . " ##end##has lead now!" . $repeatstring;
    }


    /*
    This gets called on a msg in the privgroup without a command
    */
    function privgroup($name, $msg)
    {
        if ($name == $this->bot->core("settings")
                ->get("Leader", "Name")
            && $this->bot->core("settings")
                ->get("Leader", "Echo")
        ) {
            $txt = "##leader_echo_spam##" . $msg . "##end##";
            $this->bot->send_pgroup($txt);
        }
    }


    /*
     This gets called if someone leaves the privgroup
    */
    function pgleave($name)
    {
        // clear leader if leader leaves the channel:
        if ($name == $this->bot->core("settings")->get("Leader", "Name")) {
            $this->bot->core("settings")->save("leader", "name", "");
            $this->bot->send_pgroup("Raidleader cleared");
        }
    }
}

?>
