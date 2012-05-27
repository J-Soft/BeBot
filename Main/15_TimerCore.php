<?php
/*
* Central timer handling class for the bot
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
* This class offers a couple of functions to all other Modules to handle timed events:
* - add_timer($relaying, $owner, $duration, $name, $channel, $repeat, $class = "")
*	$relaying is a boolean that has to be true if the timer is created over some relay, to avoid relay loops
*	$owner is the nickname of the owner of the timer
*	$duration is the duration of the timer in seconds
*	$name is a string defining the name of the timer
*	$channel is the chat channel the timer should be output to
*	$repeat is the interval in seconds in which the timer should be repeated until it is deleted
*	$class is the name of the timer class for this timer
*   The function returns the ID of the newly created timer
*
* - list_timed_events($owner)
*	$owner is the name of the process that created the timed events.
* 	This function returns an array containing all timed events (channel == 'internal').
* 	This array contains the fields 'id' and 'name'.
*
* - del_timer($deleter, $id, $silent = true)
*	$deleter is the nickname of the character trying to delete the timer
+	$id is the ID of the timer to delete
*	$silent defines whether a tell should be send to an owner if an admin deletes his timer. TRUE means no tell is send
*   This function returns a standard bebot array informing about success and status information.
*
* - create_timer_class($name, $description)
*	$name is the shortcut for the new timer class
*	$description is a string describing what this class is supposed to be used for
*   The function returns the ID of the newly created timer class. A -1 as return value signifies some error.
*
* - create_timer_class_entry($class_id, $next_id, $delay, $prefix, $suffix)
*	$class_id is the ID of the class the entry should be added to
*	$next_id is the ID of the next entry to follow after this entry has been used. -1 means use default last entry, -2 means this
*		entry should be self-referencing, signifying an end of the notify chain
*	$delay is the delay till the timer is finished when this notify should be shown
*	$prefix is the prefix before the timer name in the output of this notify
*	$suffix is the suffix after the timer name in the output of this notify
*   This function returns the ID of the newly created entry, which can be used for further entries in the chain.
*   A return of -1 signifies some error.
*
* The timer module supports timed events using callback functions. A module that wants to use this has to register itself
* first using the register_callback($name, $module) function. The $name must be a unique string identifying the module,
* &$module is a reference to the object of the module. The module has to implement a timer($name, $prefix, $suffix, $delay)
* function, where $name is the name given to the timer on creation, and $prefix and $suffix are the corresponding entries
* of the current notification of the timer class used and $delay is the current notify delay.
* To create a timed event you have to call the add_timer() function with $channel set to 'internal' and $owner set to the
* module name used to register the callback.
*/
$timer_core = new Timer_Core($bot);
class Timer_Core extends BasePassiveModule
{
    private $next_timer;
    private $class_cache;
    private $settings_cache;
    private $checking;
    private $modules;
    private $last_recovery_check;


    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("timer", "true") . " (
			id BIGINT(100) unsigned NOT NULL auto_increment,
			name VARCHAR(200) NOT NULL default '',
			timerclass INT(21) NOT NULL default -1,
			endtime INT(20) NOT NULL default '0',
			owner VARCHAR(20) NOT NULL default '',
			channel ENUM('tell', 'gc', 'pgmsg', 'both', 'global', 'internal')  NOT NULL default 'both',
			repeatinterval INT(8) NOT NULL default '0',
			PRIMARY KEY (id),
			INDEX (endtime)
		)"
        );
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("timer_class_entries", "false") . " (
			id INT(20) unsigned NOT NULL auto_increment,
			next_id INT(21) NOT NULL DEFAULT -1,
			class_id INT(10) unsigned NOT NULL,
			notify_delay INT(8) NOT NULL,
			notify_prefix VARCHAR(255) NOT NULL DEFAULT '',
			notify_suffix VARCHAR(255) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			INDEX (next_id),
			UNIQUE (class_id, notify_delay)
		)"
        );
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("timer_classes", "false") . " (
			id INT(10) unsigned NOT NULL auto_increment,
			name VARCHAR(25) NOT NULL DEFAULT '',
			description VARCHAR(255) NOT NULL DEFAULT '',
			PRIMARY KEY (name),
			UNIQUE (id)
		)"
        );
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("timer_class_settings", "true") . " (
			id INT(10) unsigned NOT NULL auto_increment,
			name VARCHAR(25) NOT NULL DEFAULT '',
			current_class INT(10) unsigned NOT NULL,
			default_class INT(10) unsigned NOT NULL,
			description VARCHAR(255) NOT NULL DEFAULT '',
			PRIMARY KEY (name),
			UNIQUE (id)
		)"
        );
        $this->register_module("timer");
        $this->register_event("cron", "1hour");
        $this->register_event("connect");
        $this->bot->core("settings")
            ->create("Timer", "DefaultClass", "Standard", "Which is the name of the default class to use for timers if no other is specified?");
        $this->bot->core("settings")
            ->create(
            "Timer", "GuestChannel", "origin", "Should an timer started in guestchannel or org-chat show up in both those channels or just in the channel of origin?", "both;origin"
        );
        $this->bot->core("settings")
            ->create(
            "Timer", "Global", FALSE,
            "Are all timers global? Notices for global timers are sent both to guild chat and private group. Global timers are listed in every timer listing too."
        );
        $this->bot->core("settings")
            ->create("Timer", "DeleteRank", "ADMIN", "Minimal access level to remove timer of other players.", "ANONYMOUS;GUEST;MEMBER;LEADER;ADMIN;SUPERADMIN;OWNER");
        $this->bot->core("settings")
            ->create(
            "Timer", "Relay", FALSE,
            "Should new timer be relayed to all relay bots using the established relay network (See module/Relay.php for more informations about relaying)?"
        );
        $this->bot->core("settings")
            ->create(
            "Timer", "MinRepeatInterval", 30, "What's the minimal repeat interval for repeating timers (in seconds)? This is used to prevent annoying spam.",
            "5;10;15;20;25;30;45;60"
        );
        $this->bot->core("settings")
            ->create("Timer", "RecoveryCheckInterval", 10, "How many minutes should pass between each check to recover after a database failures?", "5;10;15;30;45;60");
        $this->schema_version = 3;
        $this->update_timer_table();
        $this->create_class_cache();
        $this->update_next_timer();
        $this->modules = array();
        // Lock down timer checking until we are connected to the chatserver:
        $this->checking = true;
        $classid = $this->create_timer_class('Standard', 'Standard timer class with default notifications.');
        $nextid = $this->create_timer_class_entry($classid, -1, 10, 'Timer', 'has 10 seconds left');
        $nextid = $this->create_timer_class_entry($classid, $nextid, 30, 'Timer', 'has 30 seconds left');
        $nextid = $this->create_timer_class_entry($classid, $nextid, 60, 'Timer', 'has one minute left');
        $nextid = $this->create_timer_class_entry($classid, $nextid, 120, 'Timer', 'has two minutes left');
        $nextid = $this->create_timer_class_entry($classid, $nextid, 300, 'Timer', 'has 5 minutes left');
        $nextid = $this->create_timer_class_entry($classid, $nextid, 600, 'Timer', 'has 10 minutes left');
        $nextid = $this->create_timer_class_entry($classid, $nextid, 1800, 'Timer', 'has 30 minutes left');
        $nextid = $this->create_timer_class_entry($classid, $nextid, 3600, 'Timer', 'has one hour left');
        $nextid = $this->create_timer_class_entry($classid, $nextid, 7200, 'Timer', 'has two hours left');
        $nextid = $this->create_timer_class_entry($classid, $nextid, 21600, 'Timer', 'has six hours left');
        $classid = $this->create_timer_class("LowSpam", "Default timer class with less notifies then the Standard class.");
        $nextid = $this->create_timer_class_entry($classid, -1, 30, "Timer", "has 30 seconds left");
        $nextid = $this->create_timer_class_entry($classid, $nextid, 60, "Timer", "has one minute left");
        $nextid = $this->create_timer_class_entry($classid, $nextid, 300, "Timer", "has five minutes left");
        $nextid = $this->create_timer_class_entry($classid, $nextid, 900, "Timer", "has 15 minutes left");
        $nextid = $this->create_timer_class_entry($classid, $nextid, 1800, "Timer", "has 30 minutes left");
        $nextid = $this->create_timer_class_entry($classid, $nextid, 3600, "Timer", "has one hour left");
        $classid = $this->create_timer_class("None", "Default timer class containing no notifies at all, not even any added output when the timer runs out.");
        $this->create_timer_class_entry($classid, -2, 0, "", "");
    }


    function update_timer_table()
    {
        if ($this->bot->core("settings")->exists("timer", "schemaversion")) {
            $sv = $this->bot->core("settings")->get("timer", "schemaversion");
            Switch ($sv) {
            case 3:
                $this->bot->db->set_version("timer", 2);
            case 2:
                $this->bot->db->set_version("timer_class_entries", 2);
            }
            $this->bot->core("settings")->del("timer", "schemaversion");
        }
        switch ($this->bot->db->get_version("timer_class_entries")) {
        case 1:
            $res = $this->bot->db->select("SELECT notify_suffix FROM #___timer_class_entries");
            $this->bot->db->update_table(
                "timer_class_entries", "notify_prefix", "add", "ALTER IGNORE TABLE #___timer_class_entries ADD notify_prefix VARCHAR(255) NOT "
                . "NULL DEFAULT '' AFTER notify_delay, CHANGE notify_text notify_suffix VARCHAR(255) NOT NULL DEFAULT ''"
            );
            if (empty($res)) {
                $this->bot->db->query("UPDATE #___timer_class_entries SET notify_prefix = 'Timer' " . "WHERE notify_prefix = ''");
            }
        }
        switch ($this->bot->db->get_version("timer")) {
        case 1:
            $this->bot->db->update_table(
                "timer", "channel", "modify", "ALTER IGNORE TABLE #___timer MODIFY channel ENUM('tell', 'gc', 'pgmsg', 'both', 'global', 'internal') NOT NULL default 'both'"
            );
        }
        $this->bot->db->set_version("timer", 2);
        $this->bot->db->set_version("timer_class_entries", 2);
    }


    function connect()
    {
        // Unlock timers after connect:
        $this->checking = false;
    }


    function cron()
    {
        $this->create_class_cache();
    }


    function check_timers()
    {
        $thistime = time();
        // Check if a recovery after a database failure should happen
        // This recovery happens in a fixed interval, it doesn't really detect database failures:
        if ($this->last_recovery_check + 60 * $this->bot->core("settings")
            ->get("Timer", "RecoveryCheckInterval") <= $thistime
        ) {
            $this->update_next_timer();
        }
        // If no timer set or next one not yet reached abort
        if ($this->next_timer == -1 || $thistime < $this->next_timer || $this->checking) {
            return;
        }
        // Lock function:
        $this->checking = true;
        // Get all timers ending now as well as all notifications to do now
        $timers = $this->get_all_timer_notifications($thistime);
        if (!empty($timers)) {
            foreach ($timers as $timer) {
                $channel = strtolower($timer['channel']);
                // Find correct timerclass for timers that got seriously delayed, e.g. by a restart of the bot
                $timerclass = $timer['timerclass'];
                if ($thistime >= $timer['endtime'] && $this->class_cache[$timerclass]['delay'] > 0) {
                    $timerclass = $this->walk_down_class($timerclass);
                }
                // Check for timed events first:
                if ($channel == 'internal') {
                    // Handle the timed event:
                    if (isset($this->modules[strtolower($timer['owner'])])) {
                        if ($this->modules[strtolower($timer['owner'])] != NULL) {
                            // There is a registered callback for this module:
                            $this->modules[strtolower($timer['owner'])]->timer(
                                $timer['name'], $this->class_cache[$timerclass]['prefix'], $this->class_cache[$timerclass]['suffix'], $this->class_cache[$timerclass]['delay']
                            );
                        }
                    }
                }
                else {
                    $msg = "";
                    // It's a normal timer:
                    if ($this->class_cache[$timerclass]['prefix'] != "") {
                        $msg = $this->class_cache[$timerclass]['prefix'] . " ";
                    }
                    $msg .= "##highlight##" . $timer['name'] . "##end##";
                    if ($this->class_cache[$timerclass]['suffix'] != "") {
                        $msg .= " " . $this->class_cache[$timerclass]['suffix'];
                    }
                    if ($channel == "global") {
                        $channel = "both";
                    }
                    else {
                        if ($channel != "tell") {
                            $msg .= ", ##highlight##" . $timer['owner'] . "##end##";
                        }
                    }
                    $msg .= "!";
                    $this->bot->send_output($timer['owner'], $msg, $channel);
                }
                // If it was a notify update timerclass:
                if ($timer['endtime'] > $thistime) {
                    $this->bot->db->query("UPDATE #___timer SET timerclass = " . $this->class_cache[$timerclass]['next'] . " WHERE id = " . $timer['id']);
                }
                // If it's no notify check for repeating timers:
                elseif ($timer['repeatinterval'] > 0) {
                    // Get timer class entry for the repeated timer
                    if ($this->class_cache[$timerclass]['class'] == "Default") {
                        $entry = $this->get_class_entry(
                            $this->bot
                                ->core("settings")
                                ->get("Timer", "DefaultClass"), $timer['repeatinterval']
                        );
                    }
                    else {
                        $entry = $this->get_class_entry($this->class_cache[$timerclass]['class'], $timer['repeatinterval']);
                    }
                    // Now simply update the existing timer in the table
                    $this->bot->db->query("UPDATE #___timer SET timerclass = " . $entry . ", endtime = endtime + repeatinterval WHERE id = " . $timer['id']);
                }
            }
            // Now delete all handled timers:
            $this->bot->db->query("DELETE FROM #___timer WHERE endtime <= " . $thistime);
        }
        $this->update_next_timer();
        // Unlock function:
        $this->checking = false;
    }


    function get_class_entry($class, $duration)
    {
        $classret = $this->bot->db->select(
            "SELECT t1.id FROM #___timer_class_entries AS t1, #___timer_classes AS t2" . " WHERE t1.class_id = t2.id AND t2.name = '" . $class . "' AND t1.notify_delay < "
                . $duration . " ORDER BY t1.notify_delay DESC LIMIT 1"
        );
        if (empty($classret)) {
            return -1;
        }
        else {
            return $classret[0][0];
        }
    }


    function add_timer(
        $relaying, $owner, $duration, $name, $channel, $repeat,
        $class = ""
    )
    {
        $endtime = time() + $duration;
        $channel = strtolower($channel);
        $classcheck = $this->bot->db->select("SELECT id FROM #___timer_classes WHERE name = '" . $class . "'");
        if ($class == "" || empty($classcheck)) {
            $class = $this->bot->core("settings")->get("timer", "defaultclass");
        }
        $classid = $this->get_class_entry($class, $duration);
        if ($this->bot->core("settings")
            ->get("Timer", "GuestChannel") == "both"
            && ($channel == "gc" || $channel == "pgmsg")
        ) {
            $channel = "both";
        }
        if ($this->bot->core("settings")
            ->get("Timer", "Global")
            && $channel != "internal"
        ) {
            $channel = "global";
        }
        $this->bot->db->query(
            "INSERT INTO #___timer (name, timerclass, endtime, owner, channel, repeatinterval) VALUES " . "('" . mysql_real_escape_string($name) . "', " . $classid . ", "
                . $endtime . ", '" . mysql_real_escape_string($owner) . "', '" . mysql_real_escape_string($channel) . "', " . $repeat . ")"
        );
        $timerid = $this->bot->db->select("SELECT LAST_INSERT_ID() as id");
        // If relaying is wished and relay is enabled send new timer to relayed bot(s):
        if (!$relaying && $channel != "tell" && $channel != 'internal'
            && $this->bot
                ->core("settings")
                ->get("timer", "relay")
            && $this->bot->core("relay") instanceof Relay
            && $this->bot
                ->core("settings")->get('Relay', 'Status')
        ) {
            $msg = "<pre>relaytimer class:" . $class . " endtime:" . $endtime . " owner:" . $owner . " repeat:" . $repeat;
            $msg .= " channel:" . $channel . " name:" . $name;
            $this->bot->core("relay")->relay_to_bot($msg, false);
        }
        $this->update_next_timer();
        return $timerid[0][0];
    }


    function del_timer($deleter, $id, $silent = true)
    {
        $deleter = ucfirst(strtolower($deleter));
        $deltimer = $this->bot->db->select("SELECT owner, name FROM #___timer WHERE id = " . $id);
        if (empty($deltimer)) {
            $this->error->set("Invalid timer ID!");
            return $this->error;
        }
        $dodelete = false;
        $admin = false;
        if (ucfirst(strtolower($deltimer[0][0])) == $deleter) {
            $dodelete = true;
        }
        elseif ($this->bot->core("alts")->main($deleter) == $this->bot
            ->core("alts")->main($deltimer[0][0])
        ) {
            $dodelete = true;
        }
        elseif ($this->bot->core("security")->check_access(
            $deleter, $this->bot
                ->core("settings")->get("Timer", "DeleteRank")
        )
        ) {
            $dodelete = true;
            $admin = true;
        }
        if (!$dodelete) {
            $this->error->set("You are not allowed to delete this timer!");
            return $this->error;
        }
        $this->bot->db->query("DELETE FROM #___timer WHERE id = " . $id);
        if ($admin && !$silent) {
            $msg = "Your timer ##highlight##" . $deltimer[0][1] . "##end## was deleted by##highlight## ";
            $msg .= $deleter . "##end##!";
            $this->bot->send_output($deltimer[0][0], $msg, "tell");
        }
        $this->update_next_timer();
        return "The timer ##highlight##" . $deltimer[0][1] . "##end## was deleted!";
    }


    function list_timed_events($owner)
    {
        return $this->bot->db->select("SELECT id, name FROM #___timer WHERE channel = 'internal' AND owner = '" . $owner . "'", MYSQL_ASSOC);
    }


    function get_timer($id)
    {
        $ret = $this->bot->db->select("SELECT * FROM #___timer WHERE id = " . $id, MYSQL_ASSOC);
        if (empty($ret)) {
            return NULL;
        }
        else {
            return $ret[0];
        }
    }


    function update_next_timer()
    {
        $frompart = "(SELECT endtime FROM #___timer WHERE timerclass = -1 UNION ";
        $frompart .= "SELECT endtime - notify_delay AS endtime FROM #___timer AS t1, ";
        $frompart .= "#___timer_class_entries AS t2 WHERE t1.timerclass <> -1 AND t1.timerclass = t2.id) AS endtime_temp_table";
        $timercount = $this->bot->db->select("SELECT COUNT(endtime), MIN(endtime) FROM " . $frompart);
        // This variable gives the last time the check_timer() function made sure that the next_timer
        // variable is correct. This is used to recover from database downtimes during timer updates.
        $this->last_recovery_check = time();
        // If no timer exists, set $next_timer to -1
        if ($timercount[0][0] == 0) {
            $this->next_timer = -1;
            return;
        }
        $this->next_timer = $timercount[0][1];
    }


    function get_all_timer_notifications($thistime)
    {
        return $this->bot->db->select(
            "SELECT id, name, timerclass, channel, owner, endtime, repeatinterval " . " FROM #___timer WHERE endtime <= " . $thistime
                . " UNION SELECT t1.id AS id, name, timerclass, channel, owner, endtime, repeatinterval "
                . " FROM #___timer AS t1 JOIN #___timer_class_entries AS t2 ON t1.timerclass <> -1 AND t1.timerclass = t2.id " . " WHERE endtime - notify_delay <= " . $thistime,
            MYSQL_ASSOC
        );
    }


    function walk_down_class($timerclass)
    {
        $lastclass = -2;
        while ($timerclass != $lastclass && $this->class_cache[$timerclass]['delay'] > 0) {
            $lastclass = $timerclass;
            $timerclass = $this->class_cache[$timerclass]['next'];
        }
        return $timerclass;
    }


    function create_class_cache()
    {
        $this->class_cache = array();
        $this->class_cache[-1]['class'] = "Default";
        $this->class_cache[-1]['next'] = -1;
        $this->class_cache[-1]['delay'] = 0;
        $this->class_cache[-1]['prefix'] = "Timer";
        $this->class_cache[-1]['suffix'] = "has finished";
        $classentries = $this->bot->db->select("SELECT id, class_id, next_id, notify_delay, notify_prefix, notify_suffix FROM #___timer_class_entries", MYSQL_ASSOC);
        if (!empty($classentries)) {
            foreach ($classentries as $entry) {
                $this->class_cache[$entry['id']]['class'] = $entry['class_id'];
                $this->class_cache[$entry['id']]['next'] = $entry['next_id'];
                $this->class_cache[$entry['id']]['delay'] = $entry['notify_delay'];
                $this->class_cache[$entry['id']]['prefix'] = $entry['notify_prefix'];
                $this->class_cache[$entry['id']]['suffix'] = $entry['notify_suffix'];
            }
        }
        $this->settings_cache = array();
        $settings_entries = $this->bot->db->select("SELECT id, name, current_class FROM #___timer_class_settings", MYSQL_ASSOC);
        if (!empty($settings_entries)) {
            foreach ($settings_entries as $entry) {
                $this->settings_cache[strtolower($entry['name'])] = $entry['current_class'];
            }
        }
    }


    function create_timer_class($name, $description)
    {
        $this->bot->db->query(
            "INSERT IGNORE INTO #___timer_classes (name, description) VALUES ('" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($description) . "')"
        );
        $id = $this->bot->db->select("SELECT id FROM #___timer_classes WHERE name = '" . mysql_real_escape_string($name) . "'");
        if (empty($id)) {
            return -1;
        }
        return $id[0][0];
    }


    function create_timer_class_entry(
        $class_id, $next_id, $delay, $prefix,
        $suffix
    )
    {
        $this->bot->db->query(
            "INSERT IGNORE INTO #___timer_class_entries (next_id, class_id, notify_delay, notify_prefix, " . "notify_suffix) VALUES (" . $next_id . ", " . $class_id . ", " . $delay
                . ", '" . mysql_real_escape_string($prefix) . "', '" . mysql_real_escape_string($suffix) . "')"
        );
        $id = $this->bot->db->select("SELECT id FROM #___timer_class_entries WHERE class_id = " . $class_id . " AND notify_delay = " . $delay);
        if (empty($id)) {
            return -1;
        }
        // Updated self-referencing entries (or those that HAVE to be self-referencing) correctly:
        if ($delay == 0 || $next_id == -2) {
            $this->bot->db->query("UPDATE #___timer_class_entries SET next_id = " . $id[0][0] . " WHERE class_id = " . $class_id . " AND notify_delay = " . $delay);
        }
        // Update cache with new entry:
        $this->create_class_cache();
        return $id[0][0];
    }


    // Registers a module for callback timer events, the module name MUST be unique.
    function register_callback($modulename, &$module)
    {
        $this->modules[strtolower($modulename)] = &$module;
    }


    // Unregisters a module for callback timer events.
    function unregister_callback($modulename)
    {
        if (isset($this->modules[strtolower($modulename)])) {
            $this->modules[strtolower($modulename)] = NULL;
            unset($this->modules[strtolower($modulename)]);
            return false;
        }
        return "Invalid modulename $modulename or timer callback not registered!";
    }


    // Returns the class ID of the timer class named $name
    // Returns the ID of the default timer class (as defined by the setting) if $name doesn't exist.
    function get_class_id($name)
    {
        $id = $this->bot->db->select("SELECT id FROM #___timer_classes WHERE name = '" . mysql_real_escape_string($name) . "'", MYSQL_ASSOC);
        if (empty($id)) {
            $id = $this->bot->db->select(
                "SELECT id FROM #___timer_classes WHERE name = '" . mysql_real_escape_string(
                    $this->bot
                        ->core("settings")
                        ->get("Timer", "DefaultClass")
                ) . "'", MYSQL_ASSOC
            );
        }
        return $id[0]['id'];
    }


    // Creates a new timer class setting if a setting called $name doesn't exist yet.
    // If $name does exists it updates the default class entry and the description to any possible new values.
    function create_class_setting($name, $default_class_name, $description)
    {
        $default_id = $this->get_class_id($default_class_name);
        $class_setting = $this->bot->db->select("SELECT id, current_class FROM #___timer_class_settings WHERE name = '" . mysql_real_escape_string($name) . "'", MYSQL_ASSOC);
        if (empty($class_setting)) {
            // Setting doesn't exist, create new one:
            $this->bot->db->query(
                "INSERT IGNORE INTO #___timer_class_settings (name, current_class, " . "default_class, description) VALUES ('" . mysql_real_escape_string($name) . "', '"
                    . $default_id . "', '" . $default_id . "', '" . mysql_real_escape_string($description) . "')"
            );
            $this->settings_cache[strtolower(mysql_real_escape_string($name))] = $default_id;
        }
        else {
            // UPDATE entry
            $this->bot->db->query(
                "UPDATE #___timer_class_settings SET default_class = " . $default_id . ", description = '" . mysql_real_escape_string($description) . "' WHERE id = "
                    . $class_setting[0]['id']
            );
        }
    }


    // Updates the current_class entry of the timer class setting $name if it exists.
    // Returns true on success, returns false if the class doesn't exists.
    function update_class_setting($name, $new_class_name)
    {
        $class_setting = $this->bot->db->select("SELECT id FROM #___timer_class_settings WHERE name = '" . mysql_real_escape_string($name) . "'", MYSQL_ASSOC);
        if (!empty($class_setting)) {
            $class_id = $this->get_class_id($new_class_name);
            $this->bot->db->query("UPDATE #___timer_class_settings SET current_class = " . $class_id . " WHERE id = " . $class_setting[0]['id']);
            $this->settings_cache[strtolower(mysql_real_escape_string($name))] = $class_id;
            return true;
        }
        return false;
    }


    // Returns the class name of the timer class setting $name if it exists, "" otherwise.
    function get_class_setting($name)
    {
        if (!isset($this->settings_cache[strtolower($name)])) {
            return "";
        }
        $cname = $this->bot->db->select("SELECT name FROM #___timer_classes WHERE id = " . $this->settings_cache[strtolower($name)], MYSQL_ASSOC);
        if (empty($cname)) {
            return "";
        }
        return $cname[0]['name'];
    }
}

?>
