<?php
/*
* Online.php - Keep track of who is online.
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
* See Credits file for all aknowledgements.
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
* This module offers the following functions making queries over online characters easier:
* pgroup_tablename()
* - returns the FROM part for a query over all users in private group.
*
* gc_tablename()
* - returns the FROM part for a query over all users in guild chat.
*
* full_tablename()
* - returns the FROM part for a query over all bots, including those defined in otherbots.
*   It uses the channels defined by the Channel settings.
*
* These functions set aliases for the table names:
* - #___online is aliased as t1
* - #___whois is aliased as t2
*/
$OnlineDB_Core = new OnlineDB_Core($bot);
/*
The Class itself...
*/
class OnlineDB_Core extends BasePassiveModule
{ // Start Class
    var $last_seen; // Caches all known last seen infos for faster access
    var $guest_cache; // Caches all character in the guest channel for optional security handling

    /*
     Constructor:
     Hands over a referance to the "Bot" class.
     */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        // Create Table
        $this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("online", "false") . "
		            (nickname varchar(25) NOT NULL,
		             botname VARCHAR(25) NOT NULL,
		             status_gc INT(1) DEFAULT '0',
		             status_gc_changetime INT(11) DEFAULT '0',
		             status_pg INT(1) DEFAULT '0',
		             status_pg_changetime INT(11) DEFAULT '0',
		             reinvite INT(1) DEFAULT '0',
					 level INT(1) DEFAULT '0',
		             PRIMARY KEY (nickname, botname))");
        $this->register_module("online");
        $this->register_event("pgjoin");
        $this->register_event("pgleave");
        $this->register_event("buddy");
        $this->register_event("connect");
        $this->register_event("disconnect");
        $this->update_online_table();
        if ($this->bot->guildbot) {
            $chan = "both";
        }
        else
        {
            $chan = "pgroup";
        }
        $this->bot->core("settings")->create("Online", "Channel", $chan, "For which channels should the online status be shown? In pure raidbots Guild channel means display online status for all buddies.", "both;pgroup;guild");
        $this->bot->core("settings")->create("Online", "OtherBots", "", "Which other bots should be included in the online listings? This has to be a comma-seperated list.");
        // Settings for the reinvite ability:
        $this->bot->core("settings")->create("Reinvite", "Enabled", TRUE, "Should reinviting of users in the chat group after a restart be on or off?");
        $this->bot->core("settings")->create("Reinvite", "Silent", TRUE, "Should the reinvite be silent without any output, or not? On means silent, Off means notifies are sent.");
        if ($this->bot->guildbot) {
            $reinvnot = "You are reinvited to the guest channel of " . $this->bot->guildname . "!";
        }
        else
        {
            $reinvnot = "You are reinvited to " . $this->bot->botname . "!";
        }
        $this->bot->core("settings")->create("Reinvite", "Notify", $reinvnot, "The notify sent on reinvites of silent is disabled.");
        $this->last_seen = array();
        $this->guest_cache = array();
        $list = $this->bot->db->select("SELECT nickname, last_seen FROM #___users WHERE last_seen > 0");
        if (!empty($list)) {
            foreach ($list as $user)
            {
                $this->last_seen[ucfirst(strtolower($user[0]))] = $user[1];
            }
        }
    } // End function Online()

    function update_online_table()
    {
        if ($this->bot->core("settings")->exists("Online", "Schemaversion")) {
            $this->bot->db->set_version("online", $this->bot->core("settings")->get("Online", "Schemaversion"));
            $this->bot->core("settings")->del("Online", "Schemaversion");
        }
        if ($this->bot->db->get_version("online") == 5) {
            return;
        }
        switch ($this->bot->db->get_version("online"))
        {
            case 1:
                $this->bot->db->update_table("online", "profession", "drop", "ALTER IGNORE TABLE #___online DROP `profession`, DROP `level`, DROP `ailevel`");
            case 2:
                $this->bot->db->update_table("online", array("status_irc", "status_irc_changetime"), "drop", "ALTER IGNORE TABLE #___online DROP status_irc, DROP status_irc_changetime");
            case 3:
                $this->bot->db->update_table("online", "reinvite", "add", "ALTER IGNORE TABLE #___online ADD reinvite INT(1) DEFAULT '0'");
            case 4:
                $this->bot->db->update_table("online", "level", "add", "ALTER IGNORE TABLE #___online ADD level INT(1) DEFAULT '0'");
            default:
        }
        $this->bot->db->set_version("online", 5);
    }

    /*
     This gets called if someone joins the privgroup
     */
    function pgjoin($name)
    { // Start function pgjoin()
        $this->status_change($name, "pg", 1);
        $this->guest_cache[ucfirst(strtolower($name))] = ucfirst(strtolower($name));
        // Mark $name for reinvite to chat group (UPDATE works as status_change() creates any needed entries
        $this->bot->db->query("UPDATE #___online SET reinvite = '1' WHERE nickname = '" . $name . "' AND botname = '" . $this->bot->botname . "'");
    } // End function pgjoin()

    /*
     This gets called if someone leaves the privgroup
     */
    function pgleave($name)
    { // Start function pgleave()
        $this->status_change($name, "pg", 0);
        unset($this->guest_cache[ucfirst(strtolower($name))]);
        // Unmark $name for reinvite to chat group (UPDATE works as status_change() creates any needed entries
        $this->bot->db->query("UPDATE #___online SET reinvite = '0' WHERE nickname = '" . $name . "' AND botname = '" . $this->bot->botname . "'");
    } // End function pgleave(

    /*
     This gets called if a buddy logs on/off
     */
    function buddy($name, $msg)
    { // Start function buddy()
        if ($msg == 1 || $msg == 0) {
            if ($this->bot->core("notify")->check($name)) {
                if (!isset($this->bot->other_bots[$name])) {
                    if ($msg == 1) {
                        $this->status_change($name, "gc", 1);
                    }
                    else
                    {
                        $this->status_change($name, "gc", 0);
                    }
                }
            }
        }
    } // End function buddy()

    /*
     This gets called when bot connects
     */
    function connect()
    { // Start function connect()
        $this->everyone_offline();
        // Grab all users for reinvite - if reinvite is enabled:
        $inpg = $this->bot->db->select("SELECT nickname FROM #___online WHERE botname = '" . $this->bot->botname . "' AND reinvite = '1'");
        // Unset all reinvite flags for users not yet in pgroup (safety, some users may be faster then this function):
        $this->bot->db->query("UPDATE #___online SET reinvite = '0' WHERE botname = '" . $this->bot->botname . "' AND status_pg = '0'");
        if (!empty($inpg) && $this->bot->core("settings")->get("Reinvite", "Enabled")) {
            foreach ($inpg as $user)
            {
                // We cannot do any online checks here, as at this point the buddy list most likely hasn't been
                // checked yet. Besides, invites sent to offline characters are ignored by the chatserver.
                $this->bot->core("chat")->pgroup_invite($user[0]);
                if (!$this->bot->core("settings")->get("Reinvite", "Silent")) {
                    $this->bot->send_tell($user[0], $this->bot->core("settings")->get("Reinvite", "Notify"));
                }
            }
        }
    } // End function connect()

    /*
     This gets called when bot disconnects
     */
    function disconnect()
    { // Start function disconnect()
        $this->everyone_offline(); // FIXME: If doing a proper disconnect, should everyone go offline?
    } // End function disconnect()

    /* --------------------------------------------------
     *
     * Custom Functions Below
     *
     * --------------------------------------------------
     */
    function status_change($name, $where, $newstatus)
    { // Start function status_change()
        $name = ucfirst(strtolower($name));
        $where = strtolower($where);
        switch ($where)
        {
            case "gc":
                $column = "status_gc";
                break;
            case "pg":
                $column = "status_pg";
                break;
            default:
                return FALSE;
                break;
        }
        $level = $this->bot->db->select("SELECT user_level FROM #___users WHERE nickname = '$name'");
        if (!empty($level))
            $level = $level[0][0];
        else
            $level = 0;
        $sql = "INSERT INTO #___online (nickname, botname, " . $column . ", " . $column . "_changetime, level) ";
        $sql .= "VALUES ('" . $name . "', '" . $this->bot->botname . "', '" . $newstatus . "', '" . time() . "', " . $level . ") ";
        $sql .= "ON DUPLICATE KEY UPDATE " . $column . " = '" . $newstatus . "', " . $column . "_changetime = '" . time() . "', level = " . $level;
        $this->bot->db->query($sql);
        // Update last seen field, doesn't matter if logon or logoff, this is last time we saw any change
        $this->bot->db->query("UPDATE #___users SET last_seen = " . time() . " WHERE nickname = '$name'");
        $this->last_seen[$name] = time();
    } // End function status_change()

    /*
     Sets the status of everyone to offline.
     */
    function everyone_offline()
    { // Start function everyone_offline()
        $sql = "UPDATE #___online SET status_gc = '0', status_pg = '0' WHERE botname = '" . $this->bot->botname . "'";
        $this->bot->db->query($sql);
    } // End function everyone_offline()

    /*
     Remove a player from the online list
     */
    function logoff($name)
    {
        $name = ucfirst(strtolower($name));
        // Remove from online list
        $this->bot->db->query("UPDATE #___online SET status_gc = '0' WHERE nickname = '" . $name . "' AND botname = '" . $this->bot->botname . "'");
        // Remove from internal logon glob
        if (isset($this->bot->glob["online"][$name])) {
            unset($this->bot->glob["online"][$name]);
        }
    }

    // Returns the FROM part for a query over all users in private group.
    function pgroup_tablename()
    {
        $str = " #___online AS t1 LEFT JOIN #___whois AS t2 ON t1.nickname = t2.nickname AND t1.botname = '";
        $str .= $this->bot->botname . "' AND t1.status_pg = 1 ";
        return $str;
    }

    // Returns the FROM part for a query over all users in guild chat.
    function gc_tablename()
    {
        $str = " #___online AS t1 LEFT JOIN #___whois AS t2 ON t1.nickname = t2.nickname AND t1.botname = '";
        $str .= $this->bot->botname . "' AND t1.status_gc = 1 ";
        return $str;
    }

    // Returns the FROM part for a query over all bots, including those defined in otherbots.
    // It uses the channels defined by the Channel settings.
    function full_tablename()
    {
        $str = " #___online AS t1 LEFT JOIN #___whois AS t2 ON t1.nickname = t2.nickname AND " . $this->otherbots("t1.");
        $str .= " AND " . $this->channels("t1.") . " ";
        return $str;
    }

    function get_last_seen($name, $checkalts = FALSE)
    {
        $lastseen = FALSE;
        if ($checkalts) {
            $main = $this->bot->core("alts")->main($name);
            $alts = $this->bot->core("alts")->get_alts($main);
            if (isset($this->last_seen[ucfirst(strtolower($main))]))
                $lastseen = array($this->last_seen[ucfirst(strtolower($main))], $main);
            if (!empty($alts)) {
                foreach ($alts as $alt)
                {
                    if (isset($this->last_seen[ucfirst(strtolower($alt))]))
                        if ($this->last_seen[ucfirst(strtolower($alt))] > $lastseen[0])
                            $lastseen = array($this->last_seen[ucfirst(strtolower($alt))], $alt);
                }
            }
        }
        else
        {
            if (isset($this->last_seen[ucfirst(strtolower($name))]))
                $lastseen = $this->last_seen[ucfirst(strtolower($name))];
        }
        return ($lastseen);
    }

    /*
     * Check if $name is currently online.
     * Returns text output in $return['content'] and integer output in $return['status']
     * Return status is one of:
     * -1 Unknown (User not tracked)
     * 0 User is offline
     * 1 User is online
     */
    function get_online_state($name)
    {
        if (!$this->bot->core("chat")->buddy_exists($name)) {
            $return['content'] = "##white##Unknown##end##";
            $return['status'] = -1;
            return $return;
        }
        elseif ($this->bot->core("chat")->buddy_online($name))
        {
            $return['content'] = "##green##Online##end##";
            $return['status'] = 1;
            return $return;
        }
        else
        {
            $return['content'] = "##red##Offline##end##";
            $return['status'] = 0;
            return $return;
        }
    }

    // Checks if $name is in chat group of the bot.
    function in_chat($name)
    {
        return isset($this->guest_cache[ucfirst(strtolower($name))]);
    }

    // Returns the WHERE clause to get all botnames to show in online displays.
    // The specific channels which should be included have to be defined in addition to this.
    function otherbots($prefix = "")
    {
        if ($this->bot->core("settings")->get("Online", "Otherbots") != "") {
            $bots = explode(",", $this->bot->core("settings")->get("Online", "Otherbots"));
            $botnames = array();
            foreach ($bots as $bot)
            {
                // Only use valid botnames
                if ($this->bot->core('player')->id(trim($bot))) {
                    $botnames[] = $prefix . "botname = '" . trim($bot) . "'";
                }
            }
            $botnames[] = $prefix . "botname = '" . $this->bot->botname . "'";
            $botstring = "(" . implode(" OR ", $botnames) . ")";
        }
        else
        {
            $botstring = $prefix . "botname = '" . $this->bot->botname . "'";
        }
        return $botstring;
    }

    // Retuns the WHERE clause used to define which channel should be used for an online display, based on the setting Channel.
    function channels($prefix = "")
    {
        switch (strtolower($this->bot->core("settings")->get("Online", "Channel")))
        {
            default:
            case 'both':
                $channel = "(" . $prefix . "status_gc = 1 OR " . $prefix . "status_pg = 1)";
                break;
            case 'guild':
                $channel = $prefix . "status_gc = 1";
                break;
            case 'pgroup':
                $channel = $prefix . "status_pg = 1";
                break;
        }
        return $channel;
    }

    //Returns an array of people currently online in $channel
    //Valid channels are ('gc', 'pg', 'both')
    function list_users($channel)
    {
        $channel = strtolower($channel);
        switch ($channel)
        {
            case 'gc':
            case 'guild':
                $where_clause = 'status_gc = 1';
                break;
            case 'pg':
            case 'pgroup':
            case 'private':
                $where_clause = 'status_pg = 1';
                break;
            case 'both':
            case 'any':
            case 'all':
                $where_clause = 'status_gc = 1 OR status_pg = 1';
                break;
            default:
                $this->error->set("Unknown channel '$channel' in online->list()");
                return ($this->error);
        }
        $query = "SELECT nickname FROM #___online WHERE ($where_clause) AND botname = '{$this->bot->botname}' ORDER BY nickname";
        $users = $this->bot->db->select($query, MYSQL_ASSOC);
        if (empty($users)) {
            $this->error->set("No users found in $channel");
            return ($this->error);
        }
        foreach ($users as $user)
        {
            $user_list[] = $user['nickname'];
        }
        return ($user_list);
    }
} // End of Class
?>