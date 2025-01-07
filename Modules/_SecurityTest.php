<?php
/*
*
* SecurityTest.php - Module template.
*	This module contains commands and functions
*	for testing and debugging Security.php
* Author: Andrew Zbikowski <andyzib@gmail.com> (AKA Glarawyn RK1)
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
$securitytest = new SecurityTest($bot);
/*
The Class itself...
*/
class SecurityTest extends BaseActiveModule
{ // Start Class

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command("all", "securitytest", "OWNER");
    }


    /*
    This function handles all the inputs and returns FALSE if the
    handler should not send output, otherwise returns a string
    sutible for output via send_tell, send_pgroup, and send_gc.
    */
    function command_handler($name, $msg, $source)
    { // Start function handler()
        $vars = explode(' ', strtolower($msg));
        $command = $vars[0];
        switch ($command) {
            case "securitytest":
                switch ($vars[1]) {
                    case "cache":
                        if (isset($vars[2])) {
                            return $this->show_cache($vars[2]);
                        } else {
                            return $this->show_cache();
                        }
                        break;
                    case "whoami":
                        return $this->whoami($name);
                        break;
                    case "whois":
                        return $this->whois($vars[2]);
                    default:
                        return "Pick a test: cache, whoami, whois";
                }
                break;
            default:
                $this->bot->send_tell($name, "Broken plugin, received unhandled command: $command");
        }
    } // End function handler()

    /*
    Shows the security cache on the bot console.
    */
    function show_cache($what = "all")
    { // Start function show_cache()
        $what = strtolower($what);
        if ($what == "member" || $what == "members") {
            print_r("Members Cache:\n");
            print_r($this->bot->core("security")->cache['members']);
            return "Security Members Cache Array dumped to console.";
        } elseif ($what == "guest" || $what == "guests") {
            print_r("Guests Cache:\n");
            print_r($this->bot->core("security")->cache['guests']);
            return "Security Guests Cache Array dumped to console.";
        } elseif ($what == "banned" || $what == "ban") {
            print_r("Banned Cache:\n");
            print_r($this->bot->core("security")->cache['banned']);
            return "Security Banned Cache Array dumped to console.";
        } elseif ($what == "org" || $what == "ranks" || $what == "orgranks") {
            print_r("OrgRanks Cache:\n");
            print_r($this->bot->core("security")->cache['orgranks']);
            return "Security OrgRanks Cache Array dumped to console.";
        } elseif ($what == "group" || $what == "groups") {
            print_r("Groups Cache:\n");
            print_r($this->bot->core("security")->cache['groups']);
            return "Security Groups Cache Array dumped to console.";
        } else // Entire cache
        {
            print_r("Security Cache:\n");
            print_r($this->bot->core("security")->cache);
            return "Security Cache Array dumped to console.";
        }
    } // End function show_cache()

    /*
    Returns highest access level.
    */
    function whoami($name)
    { // Start function whoami
        $groups = $this->bot->core("security")->get_groups($name);
        $access = $this->bot->core("security")->get_access_level($name);
        $access = $this->get_access_name($access);
        $message = "Your access level is " . $access;
        if ($groups != -1) {
            $groupmsg = " You are a member of the following security groups: ";
            foreach ($groups as $group) {
                $groupmsg .= $group['name'] . " ";
            }
        }
        return $message . $groupmsg;
    } // End function whoami

    function whois($name)
    { // Start function whois()
        $name = $this->bot->core('tools')->sanitize_player($name);
        $groups = $this->bot->core("security")->get_groups($name);
        $access = $this->bot->core("security")->get_access_level($name);
        $access = $this->get_access_name($access);
        $message = $name . "'s highest access level is " . $access;
        if ($groups != -1) {
            $groupmsg = $name . " is a member of the following security groups: ";
            foreach ($groups as $group) {
                $groupmsg .= $group['name'] . " ";
            }
        }
        return $message . $groupmsg;
    } // End function whois()

    function get_access_name($access)
    { // Start function get_access_name()
        switch ($access) { // Start switch
            case 256:
                $access = "Owner";
                break;
            case 255:
                $access = "SuperAdmin";
                break;
            case 192:
                $access = "Admin";
                break;
            case 128:
                $access = "Leader";
                break;
            case 2:
                $access = "Member";
                break;
            case 1:
                $access = "Guest";
                break;
            case 0:
                $access = "Anonymous";
                break;
            case -1:
                $access = "Banned";
                break;
            default:
                $access = "Unknown (" . $access . ")";
                break;
        } // End switch
        return $access;
    } // End function get_access_name()
} // End of Class
?>
