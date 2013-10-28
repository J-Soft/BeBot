<?php
/*
* Managing the ban list:
* - banning characters
* - unbanning characters
* - lifting timed bans
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
$banmanager = new BanManager($bot);
/*
The Class itself...
*/
class BanManager extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command(
            "all",
            "ban",
            "GUEST",
            array(
                 "add" => "ADMIN",
                 "del" => "ADMIN"
            )
        );
        $this->help['description'] = "Handling the bans for <botname>.";
        $this->help['command']['banlist'] = "Shows the list of all currently banned characters.";
        $this->help['command']['ban'] = $this->help['command']['banlist'];
        $this->help['command']['ban list'] = $this->help['command']['banlist'];
        $this->help['command']['ban add <name> <reason>'] = "Bans <name> for <reason> from the bot forever - or until manually unbanned.";
        $this->help['command']['ban add <name> <time> <reason>']
            = "Bans <name> for <reason> from the bot for <time>. <time> has a base unit of days. Using 'm' for minutes, 'h' for hours and 'd' for days directly behind the number you can change the time unit. '6h' as time would ban the character for 6h, after which the ban will be automatically deleted. The bot checks every minute for bans that have run out.";
        $this->help['command']['ban del <name>'] = "Unbans <name>.";
        $this->help['command']['ban rem <name>'] = "Unbans <name>.";
        $this->bot->core("command_alias")->register("ban list", "banlist");
        $this->bot->core("command_alias")->register("ban", "blacklist");
        $this->register_event("cron", "5min");
        $this->bot->core("settings")
            ->create("Ban", "ReqReason", false, "is a Reason Required?");
    }


    function cron()
    {
        $unbans = $this->bot->db->select(
            "SELECT nickname FROM #___users WHERE user_level = -1 AND banned_until > 0 AND banned_until <= " . time()
        );
        if (!empty($unbans)) {
            foreach ($unbans as $unban) {
                $this->bot->core("security")
                    ->rem_ban("Cron", $unban[0], "Temporary ban ran out,");
            }
        }
    }


    function command_handler($name, $msg, $origin)
    {
        if (preg_match("/^ban$/i", $msg) || preg_match("/^ban list$/i", $msg)) {
            return $this->show_ban_list();
        } elseif (preg_match("/^ban add ([a-z0-9]+) ([0-9]+[mhd]?)$/i", $msg, $info)) {
            return $this->add_ban($name, $info[1], $info[2], "");
        } elseif (preg_match("/^ban add ([a-z0-9]+)$/i", $msg, $info)) {
            return $this->add_ban($name, $info[1], "0", "");
        } elseif (preg_match("/^ban add ([a-z0-9]+) ([0-9]+[mhd]?) (.+)$/i", $msg, $info)) {
            return $this->add_ban($name, $info[1], $info[2], $info[3]);
        } elseif (preg_match("/^ban add ([a-z0-9]+) (.+)$/i", $msg, $info)) {
            return $this->add_ban($name, $info[1], "0", $info[2]);
        } elseif (preg_match("/^ban del ([a-z0-9]+)$/i", $msg, $info)) {
            return $this->del_ban($name, $info[1]);
        } elseif (preg_match("/^ban rem ([a-z0-9]+)$/i", $msg, $info)) {
            return $this->del_ban($name, $info[1]);
        }
        return $this->bot->send_help($name, "ban");
    }


    function show_ban_list()
    {
        $banned = $this->bot->db->select(
            "SELECT nickname, banned_by, banned_at, banned_for, banned_until FROM #___users WHERE user_level = -1 ORDER BY nickname"
        );
        if (empty($banned)) {
            return "Nobody is banned!";
        }
        $total = 0;
        $banlist = "##blob_title## ::: All banned characters for " . $this->bot->botname . " :::##end##\n";
        foreach ($banned as $ban) {
            $blob = "\n" . $ban[0] . " " . $this->bot->core("tools")
                    ->chatcmd("whois " . $ban[0], "[WHOIS]");
            $blob .= " " . $this->bot->core("tools")
                    ->chatcmd("ban del " . $ban[0], "[UNBAN]") . "\n";
            $blob .= $this->bot->core("colors")
                    ->colorize("blob_text", "Banned by: ") . stripslashes($ban[1]) . "\n";
            $blob .= $this->bot->core("colors")
                    ->colorize("blob_text", "Banned at: ") . gmdate(
                    $this->bot
                        ->core("settings")
                        ->get("Time", "FormatString"),
                    $ban[2]
                ) . "\n";
            $blob .= $this->bot->core("colors")
                    ->colorize("blob_text", "Reason: ") . stripslashes($ban[3]) . "\n";
            if ($ban[4] > 0) {
                $blob .= $this->bot->core("colors")
                    ->colorize(
                        "blob_text",
                        "Temporary ban until " . gmdate(
                            $this->bot
                                ->core("settings")
                                ->get("Time", "FormatString"),
                            $ban[4]
                        ) . ".\n"
                    );
            } else {
                $blob .= $this->bot->core("colors")
                    ->colorize("blob_text", "Permanent ban.\n");
            }
            $banlist .= $blob;
            $total++;
        }
        return ("##highlight##" . $total . "##end## Characters Banned ::: " . $this->bot
                ->core("tools")->make_blob("click to view", $banlist));
    }


    function add_ban($source, $user, $duration, $reason)
    {
        $id = $this->bot->core('player')->id($user);
        $user = ucfirst(strtolower($user));
        if ($id == 0) {
            return "##highlight##" . $user . " ##end##is no valid character name!";
        }
        if ($reason == "") {
            if ($this->bot->core("settings")->get("Ban", "ReqReason")) {
                Return ("Reason Required for adding Bans");
            }
            $reason = "None given.";
        }
        if ($duration == "0") {
            $endtime = 0;
        } else {
            $timesize = 60 * 60 * 24;
            if (stristr($duration, 'm')) {
                $timesize = 60;
            } elseif (stristr($duration, 'h')) {
                $timesize = 60 * 60;
            } elseif (stristr($duration, 'd')) {
                $timesize = 60 * 60 * 24;
            }
            settype($duration, "integer");
            $endtime = time() + $duration * $timesize;
        }
        $ban = $this->bot->core("security")
            ->set_ban($source, $user, $source, $reason, $endtime);
        if (!($ban instanceof BotError)) {
            if ($this->bot->core("online")->in_chat($user)) {
                $this->bot->core("chat")->pgroup_kick($user);
            }
        }
        return $ban;
    }


    function del_ban($source, $user)
    {
        $id = $this->bot->core('player')->id($user);
        $user = ucfirst(strtolower($user));
        if ($id == 0) {
            return "##highlight##" . $user . " ##end##is no valid character name!";
        }
        $ban = $this->bot->core("security")->rem_ban($source, $user, $source);
        return $ban;
    }
}

?>
