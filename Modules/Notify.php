<?php
/*
* Adding and removing toons from notify list - notify is unrelated to guest or memberstatus.
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
$notify = new Notify($bot);
/*
The Class itself...
*/
class Notify extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command("all", "notify", "ADMIN");
        $this->help['description'] = "Handling of notify list.";
        $this->help['command']['notify'] = "Shows the current notify list.";
        $this->help['command']['notify on <player>'] = "Adds <player> to the notify list.";
        $this->help['command']['notify off <player>'] = "Removes <player> of the notify list.";
        $this->help['command']['notify cache'] = "Lists all players on the notify list.";
        $this->help['command']['notify cache clear'] = "Removes all players on the notify list.";
        $this->help['command']['notify cache update'] = "Updates the notify cache with the latest players on the notify list.";
    }


    function command_handler($name, $msg, $origin)
    {
        $com = $this->parse_com(
            $msg, array(
                'com',
                'sub',
                'arg'
            )
        );
        Switch ($com['sub']) {
        case 'on':
            return $this->add_notify($name, $com['arg']);
        case 'off':
            return $this->del_notify($com['arg']);
        case 'cache':
            Switch (strtolower($com['arg'])) {
            case 'clear':
                return $this->bot->core("notify")->clear_cache();
            case 'update':
                $this->bot->core("notify")->update_cache();
                return "Updating notify cache.";
            Default:
                return $this->bot->core("notify")->list_cache();
            }
        case 'list':
        case '':
            return $this->show_notify_list();
        Default:
            if (strtolower($com['arg']) == "on" || strtolower($com['arg']) == "off") // asume they want to turn notify on or off but did wrong order
            {
                Return $this->command_handler($name, $com['com'] . " " . $com['arg'] . " " . $com['sub'], $origin);
            }
            else {
                Return ("##error##Error: Unknown Sub Command ##highlight##" . $com['sub'] . "##end####end##");
            }
        }
    }


    function show_notify_list()
    {
        $notlist = $this->bot->db->select("SELECT nickname, user_level FROM #___users WHERE notify = 1 ORDER BY nickname");
        if (empty($notlist)) {
            return "Nobody on notify!";
        }
        $guestcount = 0;
        $membercount = 0;
        $othercount = 0;
        $total = 0;
        $guest = "##blob_title## ::: All guests on notify for " . $this->bot->botname . " :::##end##\n";
        $member = "##blob_title## ::: All members on notify for " . $this->bot->botname . " :::##end##\n";
        $other = "##blob_title## ::: All others on notify for " . $this->bot->botname . " ::: ##end##\n";
        foreach ($notlist as $notuser) {
            $blob = "\n&#8226; " . $notuser[0] . " " . $this->bot->core("tools")
                ->chatcmd("notify off " . $notuser[0], "[Remove]");
            $blob = $this->bot->core("colors")->colorize("blob_text", $blob);
            if ($notuser[1] >= 2) {
                $member .= $blob;
                $membercount++;
            }
            elseif ($notuser[1] == 1) {
                $guest .= $blob;
                $guestcount++;
            }
            else {
                $other .= $blob;
                $othercount++;
            }
            $total++;
        }
        return $total . " Characters on notify: " . $this->bot->core("tools")
            ->make_blob($membercount . " Member", $member) . ", " . $this->bot
            ->core("tools")
            ->make_blob($guestcount . " Guests", $guest) . ", " . $this->bot
            ->core("tools")->make_blob($othercount . " Others", $other);
    }


    function add_notify($source, $user)
    {
        return $this->bot->core("notify")->add($source, $user);
    }


    function del_notify($user)
    {
        return $this->bot->core("notify")->del($user);
    }
}

?>
