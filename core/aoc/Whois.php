<?php
/*
* Whois.php - Whois lookup with database caching.
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
$whois_core = new Whois_Core($bot);
class Whois_Core extends BasePassiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        /*
          Create tables for our whois cache if it does not already exsist.
          */
        $this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("whois", "false") . " (
					ID bigint NOT NULL default '0',
					nickname varchar(15) NOT NULL default '',
					level tinyint(3) unsigned NOT NULL default '1',
					class enum('Commoner','Conqueror','Dark Templar','Guardian','Bear Shaman','Priest of Mitra','Scoin of Set','Tempest of Set',
						'Assassin','Barbarian','Ranger','Demonologist','Herald of Xotli','Lich','Necromancer') NOT NULL default 'Commoner',
					craft1 enum('','Alchemist','Architect','Armorsmith','Gemcutter','Weaponsmith','None'),
					craft2 enum('','Alchemist','Architect','Armorsmith','Gemcutter','Weaponsmith','None'),
					location int(15) NOT NULL default '0',
					online tinyint(1) NOT NULL default '0',
					used bigint(25) NOT NULL default '0',
					org_rank_id int(15) NOT NULL default '0',
					org_rank int(15) NOT NULL default '0',
					org_name varchar(15) NULL default '',
					updated int(10) NOT NULL default '0',
					PRIMARY KEY  (nickname),
					KEY ID (ID),
					KEY Class (class),
					KEY updated (updated),
					KEY used (used)
				)");
        $this->register_module("whois");
        $this->register_event("cron", "1hour");
        $this->cache = array();
        $this->class_name = array();
        $this->create_name_cache();
        $this->register_event("buddy");
        $this->bot->core("settings")->create("Whois", "MaxCacheSize", 100, "What is the maximum number of whois entries that should be cached internally in the bot at the same time to reduce load on the SQL server?", "10;25;50;75;100;200;300;500;1000");
        $this->bot->core("settings")->create("Whois", "MaxTimeInCache", 3, "After how many hours in the internal query cache should an entry be removed because it could be outdated or free memory should be freed? This setting does not affect database entries in any way.", "1;2;3;4;5;6;7;8;9;10;11;12");
        $this->bot->core("settings")->create("Whois", "TimeValid", 1, "After how many days should the whois information in a database entry be considered outdated and looked for an update? NOTICE: Only use values higher then one day if you have trouble connecting to the FC website or are running the external updating script.", "1;2;3;4;5");
        $this->bot->core("settings")->create('Whois', "Debug", False, "Show debug information (such as Character ID, Org ID, etc)");
        $this->update_table();
    }

    function update_table()
    {
        if ($this->bot->db->get_version("whois") == 5) {
            return;
        }
        switch ($this->bot->db->get_version("whois"))
        {
            case 1: // Update Table version to prevent repeat update calls
                //was an update for a setting which isnt used in AoC
            case 2:
                $this->bot->db->update_table("whois", "class1", "modify", "ALTER IGNORE TABLE #___whois modify `class1` enum('','Alchemist','Architect','Armorsmith','Gemcutter','Weaponsmith','None') NOT NULL");
                $this->bot->db->update_table("whois", "class2", "modify", "ALTER IGNORE TABLE #___whois modify `class2` enum('','Alchemist','Architect','Armorsmith','Gemcutter','Weaponsmith','None') NOT NULL");
            case 3:
                $this->bot->db->update_table("whois", "id", "alter", "ALTER TABLE #___whois CHANGE `id` BIGINT NOT NULL");
                $this->bot->db->set_version("whois", 4);
                $this->update_table();
                return;
            case 4:
                $this->bot->db->update_table("whois", "ID", "alter", "ALTER TABLE #___whois MODIFY ID BIGINT NOT NULL");
                $this->bot->db->set_version("whois", 5);
                $this->update_table();
                break;
            default:
        }
    }

    /*
     This gets called if a buddy logs on/off
     */
    function buddy($name, $online, $level, $location, $class)
    {
        $user = $this->bot->core("player")->id($name);
        $who = array();
        $who["id"] = $user;
        $who["nickname"] = $name;
        if (!array_key_exists($name, $this->bot->buddy_status))
            $who["online"] = 0;
        else
        {
            if (4 == ($this->bot->buddy_status[$name] & 4))
                $who["online"] = 3;
            else if (2 == ($this->bot->buddy_status[$name] & 2))
                $who["online"] = 2;
            else if (1 == ($this->bot->buddy_status[$name] & 1))
                $who["online"] = 1;
            else
                $who["online"] = 0;
        }
        $who["level"] = $level;
        $who["location"] = $location;
        $class_name = $this->class_name[$class];
        if ($class_name == "") {
            $class_name = "Commoner";
        }
        $who["class"] = $class_name;
        $lookup = $this->bot->db->select("SELECT * FROM #___craftingclass WHERE name = '" . $name . "'", MYSQL_ASSOC);
        if (!empty($lookup)) {
            $who["craft1"] = $lookup[0]['class1'];
            $who["craft2"] = $lookup[0]['class2'];
        }
        $this->update($who);
    }

    function create_name_cache()
    {
        $this->class_name[0] = "Commoner";
        $this->class_name[18] = "Barbarian";
        $this->class_name[20] = "Guardian";
        $this->class_name[22] = "Conqueror";
        $this->class_name[24] = "Priest of Mitra";
        $this->class_name[26] = "Scoin of Set";
        $this->class_name[28] = "Tempest of Set";
        $this->class_name[29] = "Bear Shaman";
        $this->class_name[31] = "Dark Templar";
        $this->class_name[34] = "Assassin";
        $this->class_name[39] = "Ranger";
        $this->class_name[41] = "Necromancer";
        $this->class_name[42] = "Lich";
        $this->class_name[43] = "Herald of Xotli";
        $this->class_name[44] = "Demonologist";
    }

    function cron()
    {
        $this->cleanup_cache();
    }

    // Removes old entries from cache to make room for new ones
    // All entries older then MaxTimeInCache get removed
    // If none meets this requirement the oldest entry gets removed to free one spot
    function cleanup_cache()
    {
        $oldesttime = -1;
        $oldestname = "";
        $thistime = time();
        foreach ($this->cache as $nick => $who)
        {
            if ($who["timestamp"] < $thistime - 60 * 60 * $this->bot->core("settings")->get("Whois", "MaxTimeInCache")) {
                unset($this->cache[$nick]);
            }
            else if ($oldesttime == -1) {
                $oldesttime = $who["timestamp"];
                $oldestname = $nick;
            }
            else if ($oldesttime > $who["timestamp"]) {
                $oldesttime = $who["timestamp"];
                $oldestname = $nick;
            }
        }
        if (count($this->cache) >= $this->bot->core("settings")->get("Whois", "MaxCacheSize")) {
            unset($this->cache[$oldestname]);
        }
    }

    // Remove $who from cache
    function remove_from_cache($who)
    {
        if (isset($this->cache[$who])) {
            unset($this->cache[$who]);
        }
    }

    // Add $who to cache, make sure cache doesn't grow too large
    function add_to_cache($who)
    {
        // If cache has grown to maximum size clean it up
        if (count($this->cache) >= $this->bot->core("settings")->get("Whois", "MaxCacheSize")) {
            $this->cleanup_cache();
        }
        // We got room now, just add the new entry to cache
        $who["timestamp"] = time();
        $this->cache[ucfirst(strtolower($who["nickname"]))] = $who;
    }

    /**
     * Internal whois function with caching. Heavily modified to work with AoC.
     *
     * @param $name      User name of the player to lookup whois information
     * @param $noupdate  If no stale data is cached, get whois information by adding the user as a buddy temporarily.
     *                   You should set $noupdate to true, if you are sure the user is already a buddy.
     * @param $nowait    If buddy added, wait until server sends whois information. Process other packets meanwhile.
     *                   You should set $nowait to true, if you are about to update whois information for many users.
     * @return The WHO array, or false, or BotError
     */
    function lookup($name, $noupdate = false, $nowait = false)
    {
        if ($this->bot->core("settings")->get("Statistics", "Enabled"))
            $this->bot->core("statistics")->capture_statistic("Whois", "Lookup");
        $name = ucfirst(strtolower($name));
        $uid = $this->bot->core("player")->id($name);
        /*
          Make sure we havent been passed a bogus name.
          */
        if (!$uid || ($uid instanceof BotError)) {
            $this->error->set("$name appears to be a non exsistant character.");
            return $this->error;
        }

        // Check cache for entry first.
        if (isset($this->cache[$name])) {
            // return entry if cached one isn't outdated yet
            if ($this->cache[$name]["timestamp"] >= time() - 60 * 60 * $this->bot->core("settings")->get("Whois", "MaxTimeInCache")) {
                return $this->cache[$name];
            }
            // entry outdated, remove it and get it from db again
            unset($this->cache[$name]);
        }
        $lookup = $this->bot->db->select("SELECT * FROM #___whois WHERE nickname = '" . $name . "'", MYSQL_ASSOC);

        // If we have a result, we check it and return it, if it's still up-to-date.
        if (!empty($lookup)) {
            $who["id"] = $lookup[0]['ID'];
            $who["nickname"] = $lookup[0]['nickname'];
            $who["level"] = $lookup[0]['level'];
            $who["class"] = $lookup[0]['class'];
            $who["profession"] = $lookup[0]['class'];
            $who["online"] = $lookup[0]['online'];
            $who["location"] = $lookup[0]['location'];
            $who["craft1"] = $lookup[0]['craft1'];
            $who["craft2"] = $lookup[0]['craft2'];
            // Check if user id needs to be updated, only done if entry in DB has 0 as UID:
            if ($lookup[0]['ID'] == 0 || $lookup[0]['ID'] == -1) {
                $this->bot->db->query("UPDATE #___whois SET ID = '" . $uid . "' WHERE nickname = '" . $name . "'");
                $lookup[0]['ID'] = $uid;
                $who["id"] = $uid;
            }
            /*
               If the result isn't stale yet and the userid's match, use it.
               */
            if ($lookup[0]['updated'] >= (time() - ($this->bot->core("settings")->get("whois", "timevalid") * 24 * 3600)) && $lookup[0]['ID'] == $uid) {
                $this->add_to_cache($who);
                return $who;
            }
        }
        /*
          If noupdate = true and old data exists, return the old data.
          If noupdate = true but no old data, return error.
          */
        if ($noupdate) {
            if (empty($lookup)) {
                return false;
            }
            else
            {
                // return outdated info because the caller didn't want us to update it
                return $who;
            }
        }
        // We have no info about the user yet. The only possible way to get the info
        // is to add the user as a buddy (gets removed by roster update sometime)
        // and retrieve the info when the buddy() function gets called.
        $this->bot->core("chat")->buddy_add($uid);
        if ($nowait) {
            return false;
        }
        else
        {
            $this->bot->aoc->wait_for_buddy_add($uid);
            return $this->lookup($name, true);
        }
    }

    /*
     Performs a quick check to make sure XML data is parsable.
     */
    function check_xml($xml)
    {
        if ($xml instanceof BotError) {
            $this->bot->log("WHOIS", "CHECK_XML", "For some reason I was passed a BotError. This shouldn't happen!");
            return $xml; // The XML is bad to start with, no more checking needed. Should not have made it this far!
        }
        $nickname = $this->bot->core("tools")->xmlparse($xml, "nick");
        /*
          We have an empty nick despite having gotten a valid responce from Funcom XML? Bail!
          This should __never__ happen, but you can never rule out errors on Funcom's end.
          */
        if ($nickname == '') {
            $this->error->set("Could not parse XML data.");
            return $this->error;
        }
        else
            return $xml; // If we get here, all should be well.
    }

    /*
     Updates whois cache info with passed array.
     */
    function update($who)
    {
        // Adding in some validation and error handling due to an unknown bug (work around).
        // If ID stops being 0, then remove this code.
        if (!$who["id"]) {
            $this->bot->log('Whois', 'Update', $who["nickname"] . " had an invalid user ID! UID: " . $who["id"]);
            $who["id"] = $this->bot->core("player")->id($who["nickname"]);
        }
        if ($who["id"]) {
            // Update our database cache
            $this->bot->db->query("INSERT INTO #___whois (id, nickname, level," . " class, craft1, craft2, location, online, updated)" . " VALUES ('" . $who["id"] . "', '" . $who["nickname"] . "', '" . $who["level"] . "', '" . $who["class"] . "', '" . $who["craft1"] . "', '" . $who["craft2"] . "', " . $who["location"] . ", " . $who["online"] . ", " . "'" . time() . "') ON DUPLICATE KEY UPDATE id = VALUES(id), " . "level = VALUES(level), class = VALUES(class), craft1 = VALUES(craft1), craft2 = VALUES(craft2), online = VALUES(online), location = VALUES(location), " . " updated = VALUES(updated) ");
            // Clear from memory cache
            $this->remove_from_cache($who["nickname"]);
            return TRUE;
        }
        else
            return FALSE;
    }

    function whois_details($source, $whois)
    {
        $seen = "";
        $alts = "";
        $window = "\n##normal##Name:##end## ##highlight##{$whois['nickname']}##end##\n";
        $window .= " ##normal##Level:##end## ##highlight##{$whois['level']}##end##\n";
        $window .= " ##normal##Class:##end## ##highlight##{$whois['class']}##end##\n";
        if (!empty($whois['craft1'])) {
            $window .= " ##normal##Craft Class 1:##end## ##highlight##{$whois['craft1']}##end##\n";
        }
        if (!empty($whois['craft2'])) {
            $window .= " ##normal##Craft Class 2:##end## ##highlight##{$whois['craft2']}##end##\n";
        }
        if (0 == $whois['online']) {
            // For offline users 'location' contains the last online time in milliseconds since 1970!
            $window .= " ##normal##Last Online: ##highlight##" . gmdate($this->bot->core("settings")->get("time", "formatstring"), $whois['location']) . "##end##\n";
        }
        if ($this->bot->core("settings")->get('Whois', 'Debug')) {
            $window .= " ##normal##Character ID: ##highlight##" . $this->bot->core("tools")->int_to_string($whois['id']) . "##end####end##\n\n";
        }
        if ($this->bot->core("security")->check_access($source, $this->bot->core("settings")->get('Security', 'Whois'))) {
            $access = $this->bot->core("security")->get_access_level($whois['nickname']);
            $this->bot->core("security")->get_access_name($access);
            $window .= " ##normal##Bot access: ##highlight##" . ucfirst(strtolower($this->bot->core("security")->get_access_name($access)));
            if ($this->bot->core("settings")->get('Whois', 'Debug'))
                $window .= " ($access)";
            $window .= " ##end####end##\n\n";
        }
        $online = $this->bot->core("online")->get_online_state($whois['nickname']);
        $window .= "##normal## Status: " . $online['content'] . $seen . "##end##\n";
        if ($online['status'] <= 0) {
            if ($this->bot->core("settings")->get("Whois", "LastSeen")) {
                $lastseen = $this->bot->core("online")->get_last_seen($whois['nickname']);
                if ($lastseen) {
                    $window .= "##normal## Last Seen: ##highlight##" . gmdate($this->bot->core("settings")->get("time", "formatstring"), $lastseen) . "##end####end##\n";
                }
            }
        }
        if ($this->bot->core("settings")->get('Whois', 'Debug')) {
            $whois_debug = $this->bot->db->select("SELECT updated FROM #___whois WHERE nickname = '" . $whois['nickname'] . "'", MYSQL_ASSOC);
            $user_debug = $this->bot->db->select("SELECT id,notify,user_level,added_by,added_at,deleted_by,deleted_at,updated_at FROM #___users WHERE nickname = '" . $whois['nickname'] . "'", MYSQL_ASSOC);
            $window .= "\n##red## Debug Information:##end##\n";
            if (!empty($whois_debug[0]['updated']))
                $window .= " ##normal##Whois Updated Time: ##highlight## " . gmdate($this->bot->core("settings")->get("time", "formatstring"), $whois_debug[0]['updated']) . "##end##\n";
            if (!empty($user_debug[0]['id'])) {
                if (!empty($user_debug[0]['added_by'])) {
                    $window .= " ##normal##User Added By: ##highlight## " . $user_debug[0]['added_by'] . "##end##\n";
                    $window .= " ##normal##User Added At: ##highlight## " . gmdate($this->bot->core("settings")->get("time", "formatstring"), $user_debug[0]['added_at']) . "##end##\n";
                }
                if (!empty($user_debug[0]['deleted_by'])) {
                    $window .= " ##normal##User Deleted By: ##highlight## " . $user_debug[0]['deleted_by'] . "##end##\n";
                    $window .= " ##normal##User Deleted At: ##highlight## " . gmdate($this->bot->core("settings")->get("time", "formatstring"), $user_debug[0]['deleted_at']) . "##end##\n";
                }
                $window .= " ##normal##User Updated At: ##highlight## " . gmdate($this->bot->core("settings")->get("time", "formatstring"), $user_debug[0]['updated_at']) . "##end##\n";
                $flag_count = 0;
                if ($user_debug[0]['notify'] == 1) {
                    if ($flag_count >= 1)
                        $flag .= ", ";
                    $flag .= "Notify";
                    $flag_count++;
                }
                if ($user_debug[0]['user_level'] == 2) {
                    if ($flag_count >= 1)
                        $flag .= ", ";
                    $flag .= "Member";
                    $flag_count++;
                }
                if ($user_debug[0]['user_level'] == 1) {
                    if ($flag_count >= 1)
                        $flag .= ", ";
                    $flag .= "Guest";
                    $flag_count++;
                }
                if ($user_debug[0]['user_level'] == 0) {
                    if ($flag_count >= 1)
                        $flag .= ", ";
                    $flag .= "Not a Member";
                    $flag_count++;
                }
                if ($flag_count >= 1) {
                    $window .= " ##normal##Flags: ##highlight##\n";
                    $window .= $flag . "\n##end####end##";
                }
            }
        }
        if ($this->bot->core("settings")->get("Whois", "Alts") == TRUE) {
            $alts = $this->bot->core("alts")->show_alt($whois['nickname'], 1);
            if ($alts['alts']) {
                $window .= "\n" . $alts['list'];
            }
        }
        if ($this->bot->core("settings")->get("Whois", "ShowOptions") == TRUE) {
            $window .= "\n##normal##::: Options :::##end##\n";
            $window .= $this->bot->core("tools")->chatcmd('addbuddy ' . $whois['nickname'], 'Add to buddylist', 'cc') . "\n";
            $window .= $this->bot->core("tools")->chatcmd('rembuddy ' . $whois['nickname'], 'Remove from buddylist', 'cc') . "\n";
            //$window .= $this -> bot -> core("tools") -> chatcmd('history ' . $whois['nickname'], 'Character history') . "\n";
        }
        return ($this->bot->core("tools")->make_blob("Details", $window));
    }
}

?>