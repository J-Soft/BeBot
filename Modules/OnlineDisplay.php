<?php
/*
* Online.php - Online plugin to display online users
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
$onlinedisplay = new OnlineDisplay($bot);
/*
The Class itself...
*/
class OnlineDisplay extends BaseActiveModule
{

    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command("all", "online", "GUEST");
        $this->register_command("all", "sm", "GUEST");
        // Register for logon notifies
        $this->register_event("logon_notify");
        $this->register_event("pgjoin");
        if (strtolower(AOCHAT_GAME) == "ao") {
            $cp = "profession";
            $this->cp = "profession";
            $mode = "Fancy";
        }
        else {
            $cp = "classes";
            $this->cp = "class";
            $mode = "Basic";
        }
        $this->help['description'] = 'Shows who is online.';
        $this->help['command']['online'] = 'Shows who is online in org or chatgroup.';
        $this->help['command']['online <prof>'] = "Shows all characters of " . $cp . " <prof> online in org or chatgroup.";
        $this->help['command']['sm'] = "Lists all characters online sorted alphabetical by name.";
        $this->bot->core("settings")
            ->create("Online", "Mode", $mode, "Which mode should be used in the online display?", "Basic;Fancy");
        $this->bot->core("settings")
            ->create(
            "Online", "Showall", "LEADER", "Security Access Level required to See all Online (" . $this->bot->commpre . "online all).", "OWNER;SUPERADMIN;ADMIN;LEADER;MEMBER"
        );
        if ($this->bot->guildbot) {
            $altmode = true;
            $charinfo = "rank";
            if ($this->bot->core("settings")->get("Online", "Otherbots") != ""
            ) {
                $guildtext = "members online in Alliance.";
            }
            else {
                $guildtext = "members online in Guild.";
            }
        }
        else {
            $altmode = false;
            $charinfo = "org";
            $guildtext = "members online";
        }
        $this->bot->core("settings")
            ->create("Online", "ShowAlts", $altmode, "Whould mains and alts be shown in the online display?");
        $this->bot->core("settings")
            ->create("Online", "CharInfo", $charinfo, "Which information should be shown besides level and alien level?", "none;rank;org;both");
        $this->bot->core("settings")
            ->create("Online", "UseShortcuts", FALSE, "Should the shortcut database be used to transform the info shown about characters?");
        $this->bot->core("settings")
            ->create("Online", "ShowAccessLevel", FALSE, "Should the access level of each player be displayed?");
        $this->bot->core("settings")
            ->create("Online", "GuildText", $guildtext, "What title should be displayed when online buddies are listed?");
        $this->bot->core("settings")
            ->create("Online", "GroupText", "characters in privategroup", "What title should be displayed when online characters in the private group are listed?");
        $this->bot->core("settings")
            ->create("Online", "SortBy", "nickname", "Should the characters of each " . $this->cp . " be sorted by nickname or level?", "nickname;level");
        $this->bot->core("settings")
            ->create("Online", "LogonSpam", FALSE, "Should buddies that log on be spammed with the current online list?");
        $this->bot->core("settings")
            ->create("Online", "PgjoinSpam", FALSE, "Should users who join private group get spammed with current online list?");
        $this->bot->core("settings")
            ->create("Online", "IRCText", "Users on IRC", "What title should be displayed when IRC members are listed?");
        $this->bot->core("settings")
            ->create("Online", "IRCbot", $this->bot->botname, "What is the name of the bot used for IRC?");
        $this->bot->core("settings")
            ->create("Online", "irc", FALSE, "Should IRC be included in the Online List");
        $this->bot->core("settings")
            ->create(
            "Online", "whois_alts_cmd", TRUE, "Should <pre>whois be used Instead of <pre>alts for link inside window (default to <pre>alts if alt list isnt shown in whois)"
        );
        $this->bot->core("settings")
            ->create("Online", "RaidStatus", TRUE, "Should Raid Status Be shown");
        $this->bot->core("colors")
            ->define_scheme("online", "title", "blob_title");
        $this->bot->core("colors")
            ->define_scheme("online", $this->cp, "seagreen");
        $this->bot->core("colors")
            ->define_scheme("online", "characters", "blob_text");
        $this->bot->core("colors")->define_scheme("online", "afk", "white");
    }


    function notify($user, $startup = false)
    {
        if (!$startup
            && $this->bot->core("settings")
                ->get("Online", "Logonspam")
        ) {
            $this->bot->send_tell(
                $user, $this->online_msg(
                    "", $this->bot
                        ->core("settings")->get("Online", "Channel")
                )
            );
        }
    }


    function pgjoin($user)
    {
        if ($this->bot->core("settings")->get("Online", "PgjoinSpam")) {
            $this->bot->send_tell(
                $user, $this->online_msg(
                    "", $this->bot
                        ->core("settings")->get("Online", "Channel")
                )
            );
        }
    }


    function command_handler($name, $msg, $origin)
    {
        return $this->handler(
            $msg, $this->bot->core("settings")
                ->get("Online", "Channel"), $name
        );
    }


    function handler($msg, $what, $name = FALSE)
    {
        if (preg_match("/^online$/i", $msg)) {
            return $this->online_msg("", $what);
        }
        else {
            if (preg_match("/^online (.+)$/i", $msg, $info)) {
                return $this->online_msg($info[1], $what, $name);
            }
            else {
                if (preg_match("/^sm$/i", $msg)) {
                    return $this->sm_msg($what);
                }
            }
        }
    }


    /*
    Makes the message.
    */
    function online_msg($param, $what, $name = False)
    {
        if ($param == "all") {
            if ($this->bot->core("security")->check_access(
                $name, $this->bot
                    ->core("settings")->get('Online', 'Showall')
            )
            ) {
                $what = "guild";
            }
            $param = "";
        }
        // If any search parameter is added try to get the profession name
        $profstring = "";
        if ($param != "") {
            if (($profname = $this->bot->core("professions")
                ->full_name($param)) instanceof BotError
            ) {
                return $profname;
            }
            $profstring = " AND t2." . $this->cp . " = '" . $profname . "' ";
        }
        $guild = $this->online_list("gc", $profstring, 2);
        $guests = $this->online_list("gc", $profstring, 1);
        $other = $this->online_list("gc", $profstring, 0);
        $pgroup = $this->online_list("pg", $profstring);
        unset($this->listed);
        $online = "";
        $msg = "";
        if (($what == "both") || ($what == "guild")) {
            $online .= $this->bot->core("colors")
                ->colorize(
                "online_title", "::: " . $guild[0] . " " . $this->bot
                ->core("settings")
                ->get("Online", "Guildtext") . " :::"
            ) . "\n" . $guild[1];
            $online .= "\n" . $this->bot->core("colors")
                ->colorize("lightbeige", "--------------------------------------------------------------\n");
            $msg .= $this->bot->core("colors")
                ->colorize("highlight", $guild[0]) . " " . $this->bot
                ->core("settings")->get("Online", "Guildtext") . " ";
            if ($guests[0] > 0) {
                $online .= "##online_title##::: " . $guests[0] . " Guests Online :::##end##\n" . $guests[1];
                $online .= "\n##lightbeige##--------------------------------------------------------------##end##\n";
                $msg .= "##highlight##" . $guests[0] . "##end## Guests Online ";
            }
            if ($other[0] > 0) {
                $online .= "##online_title##::: " . $other[0] . " Other Online :::##end##\n" . $other[1];
                $online .= "\n##lightbeige##--------------------------------------------------------------##end##\n";
                $msg .= "##highlight##" . $other[0] . "##end## Other Online ";
            }
        }
        if (($what == "both")
            || ($what == "pgroup") && strtolower(AOCHAT_GAME) == "ao"
        ) {
            $online .= $this->bot->core("colors")
                ->colorize(
                "online_title", "::: " . $pgroup[0] . " " . $this->bot
                ->core("settings")
                ->get("Online", "GroupText") . " :::"
            ) . "\n" . $pgroup[1];
            $msg .= $this->bot->core("colors")
                ->colorize("highlight", $pgroup[0]) . " " . $this->bot
                ->core("settings")->get("Online", "GroupText");
        }
        if ($this->bot->core("settings")->get("Online", "irc")
            && ($this->bot
                ->core("settings")
                ->get("Online", "IRCbot") !== $this->bot->botname
                || ($this->bot
                    ->core("settings")
                    ->exists("irc", "connected")
                    && $this->bot
                        ->core("settings")
                        ->get("irc", "connected")))
        ) {
            $irclist = $this->irc_online_list();
            $online .= "\n" . $this->bot->core("colors")
                ->colorize("lightbeige", "--------------------------------------------------------------\n\n");
            $online .= $this->bot->core("colors")
                ->colorize(
                "online_title", "::: " . $irclist[0] . " " . $this->bot
                ->core("settings")
                ->get("Online", "IRCText") . " :::"
            ) . "\n" . $irclist[1];
            $msg .= ". " . $this->bot->core("colors")
                ->colorize("highlight", $irclist[0]) . " " . $this->bot
                ->core("settings")->get("Online", "IRCText");
        }
        $msg .= ":: " . $this->bot->core("tools")
            ->make_blob("click to view", $online);
        return $msg;
    }


    /*
    make the list of online players
    */
    function online_list($channel, $like, $lvl = FALSE)
    {
        $andlvl = "";
        if (strtolower(AOCHAT_GAME) == "ao") {
            $ex1 = "defender_rank_id DESC, ";
            $ex2 = ", defender_rank_id";
        }
        $botstring = $this->bot->core("online")->otherbots();
        if (strtolower(
            $this->bot->core("settings")
                ->get("Online", "Sortby")
        ) == "level"
        ) {
            $sortstring = " ORDER BY " . $this->cp . " ASC, t2.level DESC, " . $ex1 . "t1.nickname ASC";
        }
        else {
            $sortstring = " ORDER BY " . $this->cp . " ASC, t1.nickname ASC";
        }
        if ($lvl !== FALSE) {
            $andlvl = " AND t1.level = " . $lvl;
        }
        $online = $this->bot->db->select(
            "SELECT t1.nickname, t2.level, org_rank, org_name, " . $this->cp . $ex2 . ", t1.level FROM "
                . "#___online AS t1 LEFT JOIN #___whois AS t2 ON t1.nickname = t2.nickname WHERE status_" . $channel . "=1" . $andlvl . " AND " . $botstring . $like . $sortstring
        );
        if (strtolower(
            $this->bot->core("settings")
                ->get("Online", "Mode")
        ) == "fancy"
        ) {
            if (strtolower(AOCHAT_GAME) == "aoc") {
                $profgfx["Barbarian"] = "16308";
                $profgfx["Guardian"] = "84203";
                $profgfx["Conqueror"] = "16252";
                $profgfx["Priest of Mitra"] = "16237";
                $profgfx["Tempest of Set"] = "84197";
                $profgfx["Bear Shaman"] = "39290";
                $profgfx["Dark Templar"] = "16300";
                $profgfx["Assassin"] = "16186";
                $profgfx["Ranger"] = "117993";
                $profgfx["Doctor"] = "44235";
                $profgfx["Necromancer"] = "100998";
                $profgfx["Herald of Xotli"] = "16341";
                $profgfx["Demonologist"] = "16196";
            }
            else {
                $profgfx["Meta-Physicist"] = "16308";
                $profgfx["Adventurer"] = "84203";
                $profgfx["Engineer"] = "16252";
                $profgfx["Soldier"] = "16237";
                $profgfx["Keeper"] = "84197";
                $profgfx["Shade"] = "39290";
                $profgfx["Fixer"] = "16300";
                $profgfx["Agent"] = "16186";
                $profgfx["Trader"] = "117993";
                $profgfx["Doctor"] = "44235";
                $profgfx["Enforcer"] = "100998";
                $profgfx["Bureaucrat"] = "16341";
                $profgfx["Martial Artist"] = "16196";
                $profgfx["Nano-Technician"] = "16283";
            }
        }
        $prof_based = "";
        $online_list = "";
        $online_num = 0;
        if (!empty($online)) {
            $currentprof = "";
            foreach ($online as $player) {
                if (isset($this->listed[$channel][$player[0]])) {
                    Continue;
                }
                $this->listed[$channel][$player[0]] = TRUE;
                if ($currentprof != $player[4]) {
                    $currentprof = $player[4];
                    if (strtolower(
                        $this->bot->core("settings")
                            ->get("Online", "Mode")
                    ) == "fancy"
                    ) {
                        $online_list .= "\n<img src=tdb://id:GFX_GUI_FRIENDLIST_SPLITTER>\n";
                        $online_list .= "<img src=rdb://" . $profgfx[$player[4]] . ">";
                    }
                    else {
                        $online_list .= "\n";
                    }
                    $online_list .= $this->bot->core("colors")
                        ->colorize("online_" . $this->cp, $player[4]) . "\n";
                    if (strtolower(
                        $this->bot->core("settings")
                            ->get("Online", "Mode")
                    ) == "fancy"
                    ) {
                        $online_list .= "<img src=tdb://id:GFX_GUI_FRIENDLIST_SPLITTER>\n";
                    }
                }
                $admin = "";
                $raid = "";
                $online_num++;
                $main = $this->bot->core("alts")->main($player[0]);
                $alts = $this->bot->core("alts")->get_alts($main);
                if ($this->bot->exists_module("raid") && $this->bot->core("raid")->raid
                    && $this->bot
                        ->core("settings")->get("Online", "RaidStatus")
                ) {
                    if (isset($this->bot->core("raid")->user[$player[0]])) {
                        $raid = " :: ##green##In Raid##end## ";
                    }
                    elseif (isset($this->bot->core("raid")->user2[$player[0]])) {
                        $raid = " :: ##red##" . $this->bot->core("raid")->user2[$player[0]] . "##end## ";
                    }
                    else {
                        $raid = " :: ##red##Not in Raid##end## ";
                    }
                }
                if ($this->bot->core("settings")
                    ->get("Online", "Showaccesslevel")
                    && $this->bot
                        ->core("security")->check_access($player[0], "LEADER")
                ) {
                    $level = $this->bot->core("security")
                        ->get_access_name(
                        $this->bot->core("security")
                            ->get_access_level($player[0])
                    );
                    $admin = " :: " . $this->bot->core("colors")
                        ->colorize("online_title", ucfirst(strtolower($level))) . " ";
                }
                if ($this->bot->core("settings")
                    ->get("Online", "whois_alts_cmd")
                    && $this->bot
                        ->core("settings")->get("Whois", "Alts")
                ) {
                    $altcmd = "whois";
                }
                else {
                    $altcmd = "alts";
                }
                if (empty($alts)
                    || !$this->bot->core("settings")
                        ->get("Online", "Showalts")
                ) {
                    $alts = "";
                }
                else {
                    if ($main == $player[0]) {
                        $alts = ":: " . $this->bot->core("tools")
                            ->chatcmd($altcmd . " " . $player[0], "Details") . " ::";
                    }
                    else {
                        $alts = ":: " . $this->bot->core("tools")
                            ->chatcmd($altcmd . " " . $player[0], $main . "'s Alt") . " ";
                    }
                }
                $charinfo = "";
                if ($this->bot->core("settings")->get("Online", "Useshortcuts")
                ) {
                    $player[2] = $this->bot->core("shortcuts")
                        ->get_short($player[2]);
                    $player[3] = $this->bot->core("shortcuts")
                        ->get_short(stripslashes($player[3]));
                }
                else {
                    $player[3] = stripslashes($player[3]);
                }
                if (strtolower(AOCHAT_GAME) == "ao") {
                    if (strtolower(
                        $this->bot->core("settings")
                            ->get("Online", "Charinfo")
                    ) == "both"
                    ) {
                        if ($player[3] != '') {
                            $charinfo = "(" . $player[2] . ", " . $player[3] . ") ";
                        }
                    }
                    elseif (strtolower(
                        $this->bot->core("settings")
                            ->get("Online", "Charinfo")
                    ) == "rank"
                    ) {
                        if ($player[2] != '') {
                            $charinfo = "(" . $player[2] . ") ";
                        }
                    }
                    elseif (strtolower(
                        $this->bot->core("settings")
                            ->get("Online", "Charinfo")
                    ) == "org"
                    ) {
                        if ($player[3] != '') {
                            $charinfo = "(" . $player[3] . ") ";
                        }
                    }
                }
                $online_list .= $this->bot->core("colors")
                    ->colorize("online_characters", " - Lvl " . $player[1]);
                if (strtolower(AOCHAT_GAME) == "ao") {
                    $online_list .= "/" . $player[5];
                }
                $online_list .= $this->bot->core("colors")
                    ->colorize("online_characters", " " . $player[0] . " " . $charinfo . $raid . $admin . $alts);
                if (isset($this->bot->commands["tell"]["afk"]->afk[$player[0]])) {
                    $online_list .= ":: " . $this->bot->core("colors")
                        ->colorize("online_afk", "( AFK )") . "\n";
                }
                else {
                    $online_list .= "\n";
                }
            }
        }
        return array(
            $online_num,
            $online_list
        );
    }


    function irc_online_list()
    {
        $online = $this->bot->db->select(
            "SELECT nickname FROM #___online WHERE botname = '" . $this->bot
                ->core("settings")
                ->get("Online", "IRCbot") . " - IRC' AND status_gc = 1"
        );
        $online_list = "";
        $online_num = 0;
        if (!empty($online)) {
            foreach ($online as $user) {
                $online_list .= "##forestgreen## - " . $user[0] . "##end##\n";
                $online_num++;
            }
        }
        return array(
            $online_num,
            $online_list
        );
    }


    /*
    Makes the message.
    */
    function sm_msg($what)
    {
        if ($param == "all") {
            $param = "";
        }
        // If any search parameter is added try to get the profession name
        $profstring = "";
        if ($param != "") {
            if (($profname = $this->bot->core("professions")
                ->full_name($param)) instanceof BotError
            ) {
                return $profname;
            }
            $profstring = " AND t2." . $this->cp . " = '" . $profname . "' ";
        }
        $countonline = $this->bot->db->select(
            "SELECT count(DISTINCT t1.nickname) FROM " . $this->bot
                ->core("online")->full_tablename() . " WHERE t2.level >= 1"
        );
        $count1 = $this->bot->db->select(
            "SELECT count(DISTINCT t1.nickname) FROM " . $this->bot
                ->core("online")->full_tablename() . " WHERE t2.level < 100"
        );
        $count2 = $this->bot->db->select(
            "SELECT count(DISTINCT t1.nickname) FROM " . $this->bot
                ->core("online")
                ->full_tablename() . " WHERE t2.level < 190 AND t2.level > 99"
        );
        $count3 = $this->bot->db->select(
            "SELECT count(DISTINCT t1.nickname) FROM " . $this->bot
                ->core("online")
                ->full_tablename() . " WHERE t2.level < 205 AND t2.level > 189"
        );
        $count4 = $this->bot->db->select(
            "SELECT count(DISTINCT t1.nickname) FROM " . $this->bot
                ->core("online")
                ->full_tablename() . " WHERE t2.level < 220 AND t2.level > 204"
        );
        $count5 = $this->bot->db->select(
            "SELECT count(DISTINCT t1.nickname) FROM " . $this->bot
                ->core("online")->full_tablename() . " WHERE t2.level = 220"
        );
        if (strtolower(AOCHAT_GAME) == "ao") {
            $ex1 = ", defender_rank_id";
        }
        $online = $this->bot->db->select(
            "SELECT DISTINCT(t1.nickname), t2.level, " . $this->cp . $ex1 . ", org_name FROM " . $this->bot
                ->core("online")
                ->full_tablename() . " WHERE t2.level >= 1" . " ORDER BY t1.nickname ASC, " . $this->cp . " ASC, t2.level DESC"
        );
        $count = 0;
        $msg = $this->bot->core("colors")
            ->colorize("highlight", "Chatlist\n\n");
        $msg .= $this->bot->core("colors")
            ->colorize("online_characters", "Players (1-99): ") . $count1[0][0] . "\n";
        $msg .= $this->bot->core("colors")
            ->colorize("online_characters", "Players (100-189): ") . $count2[0][0] . "\n";
        $msg .= $this->bot->core("colors")
            ->colorize("online_characters", "Players (190-204): ") . $count3[0][0] . "\n";
        $msg .= $this->bot->core("colors")
            ->colorize("online_characters", "Players (205-219): ") . $count4[0][0] . "\n";
        $msg .= $this->bot->core("colors")
            ->colorize("online_characters", "Players (220): ") . $count5[0][0] . "\n\n";
        if (!empty($online)) {
            foreach ($online as $player) {
                if (strtolower(AOCHAT_GAME) == "ao") {
                    $ex2 = " (" . $player[4] . ")";
                }
                $msg .= $this->bot->core("colors")
                    ->colorize("online_title", $player[0] . "\n");
                $msg .= "    " . $player[1] . "/" . $player[2] . " " . $player[3] . $ex2 . "\n";
                $count++;
            }
        }
        if (empty($countonline[0][0])) {
            $countonline[0][0] = 0;
        }
        return $countonline[0][0] . " Members Online :: " . $this->bot
            ->core("tools")->make_blob("click to view", $msg);
    }
}

?>
