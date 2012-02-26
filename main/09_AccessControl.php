<?php
/*
* AccessControl.php - Runtime configurable rights for commands for bebot.
*
* Written by Alreadythere
* Copyright (C) 2005 Christian Plog
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
$accesscontrol_core = new AccessControl_Core($bot);
/*
  The Class itself...
*/
class AccessControl_Core extends BasePassiveModule
{
    private $access_cache;
    private $access_levels;
    private $deny_levels;
    private $security_levels;
    private $channels;
    private $startup;


    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        /*
        Create the table for access rights
        */
        $this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("access_control", "true") . " (
					command varchar(50) NOT NULL default '',
					subcommand varchar(50) NOT NULL default '*',
					channel varchar(20) NOT NULL default '',
					minlevel enum('ANONYMOUS', 'GUEST', 'MEMBER', 'LEADER', 'ADMIN', 'SUPERADMIN', 'OWNER', 'DISABLED', 'DELETED') default 'DISABLED',
					PRIMARY KEY (command, subcommand, channel)
				)");
        $this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("access_control_saves", "false") . " (
					name varchar(50) NOT NULL default '',
					description varchar(150) NULL,
					commands TEXT NOT NULL,
					PRIMARY KEY (name)
				)");
        $this->startup = TRUE;
        $this->register_module("access_control");
        $this->register_event("cron", "1hour");
        $this->bot->core("settings")
            ->create("AccessControl", "DefaultLevel", "SUPERADMIN", "Which is the minimal access level that should get access to new commands on default? Or should the default for new commands be to disabled access to them at all?", "ANONYMOUS;GUEST;MEMBER;LEADER;ADMIN;SUPERADMIN;OWNER;DISABLED");
        $this->bot->core("settings")
            ->create("AccessControl", "LockGc", FALSE, "Are all commands in the guild chat locked from use?", "On;Off", TRUE);
        $this->bot->core("settings")
            ->create("AccessControl", "LockPgroup", FALSE, "Are all commands in the private chatgroup locked from use?", "On;Off", TRUE);
        $this->update_table();
        $this->access_levels   = array('ANONYMOUS',
                                       'GUEST',
                                       'MEMBER',
                                       'LEADER',
                                       'ADMIN',
                                       'SUPERADMIN',
                                       'OWNER',
                                       'DISABLED',
                                       'DELETED');
        $this->security_levels = array('ANONYMOUS',
                                       'GUEST',
                                       'MEMBER',
                                       'LEADER',
                                       'ADMIN',
                                       'SUPERADMIN',
                                       'OWNER');
        $this->deny_levels     = array('DISABLED');
        $this->channels        = array("tell",
                                       "pgmsg",
                                       "gc",
                                       "extpgmsg",
                                       "all");
        $this->create_access_cache();
    }


    function update_table()
    {
        if ($this->bot->core("settings")
            ->exists("accesscontrol", "schemaversion")
        ) {
            $this->bot->db->set_version("access_control", $this->bot
                ->core("settings")->get("accesscontrol", "schemaversion"));
            $this->bot->core("settings")->del("accesscontrol", "schemaversion");
        }
        switch ($this->bot->db->get_version("access_control"))
        {
            case 1:
                $this->bot->db->update_table("access_control", "subcommand", "add", "ALTER IGNORE TABLE #___access_control ADD COLUMN subcommand VARCHAR(50) NOT NULL DEFAULT '*' AFTER command");
                $this->bot->db->query("ALTER IGNORE TABLE #___access_control DROP PRIMARY KEY");
                $this->bot->db->update_table("access_control", array("command",
                                                                     "subcommand",
                                                                     "channel"), "alter", "ALTER IGNORE TABLE #___access_control ADD PRIMARY KEY (command, subcommand, channel)");
                $this->bot->db->query("UPDATE #___access_control SET subcommand = '*' WHERE subcommand = ''");
            case 2:
                $this->bot->db->update_table("access_control", "minlevel", "modify", "ALTER IGNORE TABLE #___access_control MODIFY minlevel enum('ANONYMOUS', 'GUEST', 'MEMBER', 'LEADER', 'ADMIN', 'SUPERADMIN', 'OWNER', 'DISABLED', 'DELETED') NOT NULL DEFAULT 'DISABLED'");
            case 3:
            default:
        }
        $this->bot->db->set_version("access_control", 3);
    }


    function cron()
    {
        if ($this->startup) {
            $this->startup = FALSE;
            // The 2/3 channels:
            $channel[0]   = "tell";
            $channel[1]   = "pgmsg";
            $channel[2]   = "extpgmsg";
            $channelcount = 3;
            if ($this->bot->guildbot) {
                $channelcount++;
                $channel[3] = "gc";
            }
            // Set default access where needed:
            for ($i = 0; $i < $channelcount; $i++)
            {
                if (!(empty($this->bot->commands[$channel[$i]]))) {
                    ksort($this->bot->commands[$channel[$i]]);
                    foreach ($this->bot->commands[$channel[$i]] as $key => $val)
                    {
                        $rights = $this->bot->db->select("SELECT * FROM #___access_control WHERE command = '" . $key . "' AND channel = '" . $channel[$i] . "'");
                        if (empty($rights)) {
                            $this->bot->db->query("INSERT INTO #___access_control (command, subcommand, channel, " . "minlevel) VALUES ('" . $key . "', '*', '" . $channel[$i] . "', '" . $this->bot
                                ->core("settings")
                                ->get("Accesscontrol", "Defaultlevel") . "')");
                        }
                    }
                }
            }
        }
        // Re-Create the access_cache:
        $this->create_access_cache();
        $this->bot->core("help")->update_cache();
    }


    // Creates the cache for all access rights to commands:
    function create_access_cache()
    {
        $this->access_cache = array();
        $access_rights      = $this->bot->db->select("SELECT * FROM #___access_control");
        // Abort if no access rights are yet defined:
        if (empty($access_rights)) {
            return;
        }
        foreach ($access_rights as $right)
        {
            $this->access_cache[strtolower($right[0])][strtolower($right[1])][strtolower($right[2])] = strtoupper($right[3]);
        }
    }


    // Does the innermost check for access rights
    function do_check($user, $command, $subcommand, $channel)
    {
        // If disabled or delted, return false, as no access is allowed
        if (in_array($this->access_cache[strtolower($command)][strtolower($subcommand)][strtolower($channel)], $this->deny_levels)) {
            return false;
        }
        else
        {
            // Otherwise check access level of $user vs the minlevel of $command, $subcommand in $channel:
            return $this->bot->core("security")
                ->check_access($user, $this->access_cache[strtolower($command)][strtolower($subcommand)][strtolower($channel)]);
        }
    }


    // Checks if $user is allowed to use $command in the $channel, true if yes, false if no.
    function check_rights($user, $command, $msg, $channel)
    {
        // Check for locks in gc or pgmsg first:
        if (($channel == "gc" && $this->bot->core("settings")
            ->get("AccessControl", "LockGc")) || ($channel == "pgmsg" && $this->bot
            ->core("settings")->get("AccessControl", "LockPgroup"))
        ) {
            return FALSE;
        }
        $subcommand = strpos($msg, " ");
        if ($subcommand === false) {
            // No subcommand, check if special "only command" entry exists, otherwise fall back on
            // general entry for command if that exists.
            if (isset($this->access_cache[strtolower($command)]['$'][strtolower($channel)])) {
                return $this->do_check($user, $command, '$', $channel);
            }
            // Check general entry:
            if (isset($this->access_cache[strtolower($command)]['*'][strtolower($channel)])) {
                return $this->do_check($user, $command, '*', $channel);
            }
            // No entry for command at all, deny access:
            return false;
        }
        else
        {
            // Possible subcommand, extract it and check for entry:
            $parts = explode(" ", $msg, 3);
            // "$" is not allowed as subcommand, same for "*" - both are special chars in subcommand field.
            // Check only general entry then:
            if (strcmp($parts[1], "*") == 0 || strcmp($parts[1], "$") == 0) {
                if (isset($this->access_cache[strtolower($command)]['*'][strtolower($channel)])) {
                    return $this->do_check($user, $command, '*', $channel);
                }
                else
                {
                    // No access level defined, deny access on default:
                    return FALSE;
                }
            }
            // We got a true subcommand, if entry for this subcommand exists use it for access check:
            if (isset($this->access_cache[strtolower($command)][strtolower($parts[1])][strtolower($channel)])) {
                // Make sure the access control entry for this subcommand is not deleted
                if ($this->access_cache[strtolower($command)][strtolower($parts[1])][strtolower($channel)] != 'DELETED') {
                    return $this->do_check($user, $command, $parts[1], $channel);
                }
            }
            // Otherwise check if general entry for $command exists, then use that:
            if (isset($this->access_cache[strtolower($command)]['*'][strtolower($channel)])) {
                return $this->do_check($user, $command, '*', $channel);
            }
            // No fitting entry exists, deny access:
            return false;
        }
    }


    // Updates the access level for a command or sets it the first time if not yet existing.
    // If the setting exists it will ALWAYS be overwriten. To add default rights use the create() function below.
    function update_access($command, $channel, $newlevel)
    {
        $command  = strtolower($command);
        $channel  = strtolower($channel);
        $newlevel = strtoupper($newlevel);
        // Safety: if command = 'commands' and newlevel = 'DISABLED' just exit, don't change anything.
        // You don't want to do that, as it would potentially exclude everyone from using commands in the bot!
        if ($command == "commands" && $newlevel == "DISABLED") {
            return;
        }
        // Add access level if not yet existing, otherwise update existing entry:
        $this->bot->db->query("INSERT INTO #___access_control (command, subcommand, channel, minlevel) VALUES ('" . $command . "', '*', '" . $channel . "', '" . $newlevel . "') ON DUPLICATE KEY UPDATE minlevel = '" . $newlevel . "'");
        // Update cache:
        $this->access_cache[strtolower($command)]['*'][strtolower($channel)] = $newlevel;
        $this->bot->core("help")->update_cache();
    }


    // Creates default rights for a command. This function never changes any existing access levels.
    // Warning: different order of variables from update_access(), but more in line with a command definition.
    // $channel = "all" means set default rights for all channels (gc, pgmsg, tell). You always have to set explicit rights for extpgmsg
    function create($channel, $command, $defaultlevel)
    {
        $command      = strtolower($command);
        $channel      = strtolower($channel);
        $defaultlevel = strtoupper($defaultlevel);
        // Check that a correct access level is used:
        if (!(in_array($defaultlevel, $this->access_levels)) || $defaultlevel == 'DELETED') {
            $this->bot->log("ACCESS", "ERROR", "Trying to set an illegal default level of " . $defaultlevel . " for " . $command . " in " . $channel . "!");
            return;
        }
        // Make sure the channel is valid:
        if (!(in_array($channel, $this->channels))) {
            $this->bot->log("ACCESS", "ERROR", "Trying to set default access rights for an illegal channel: " . $channel . " for " . $command . "!");
            return;
        }
        if ($channel == "all") {
            $chans = array("tell",
                           "pgmsg",
                           "gc");
            foreach ($chans as $chan)
            {
                // Add the default level if no entry for the channel/command combination exists yet:
                $this->bot->db->query("INSERT IGNORE INTO #___access_control (command, subcommand, channel, minlevel) VALUES ('" . $command . "', '*', '" . $chan . "', '" . $defaultlevel . "')");
                // Add to cache if not set yet:
                if (!isset($this->access_cache[strtolower($command)]['*'][strtolower($chan)])) {
                    $this->access_cache[strtolower($command)]['*'][strtolower($chan)] = $defaultlevel;
                }
            }
        }
        else
        {
            // Add the default level if no entry for the channel/command combination exists yet:
            $this->bot->db->query("INSERT IGNORE INTO #___access_control (command, subcommand, channel, minlevel) VALUES ('" . $command . "', '*', '" . $channel . "', '" . $defaultlevel . "')");
            // Add to cache if not set yet:
            if (!isset($this->access_cache[strtolower($command)]['*'][strtolower($channel)])) {
                $this->access_cache[strtolower($command)]['*'][strtolower($channel)] = $defaultlevel;
            }
        }
    }


    // Updates access rights for $command with subcommand $sub in $channel to $newlevel
    function update($command, $sub, $channel, $newlevel)
    {
        $command  = strtolower($command);
        $sub      = strtolower($sub);
        $channel  = strtolower($channel);
        $newlevel = strtoupper($newlevel);
        // Safety: if command = 'commands' and newlevel = 'DISABLED' just exit, don't change anything.
        // You don't want to do that, as it would potentially exclude everyone from using commands in the bot!
        // This is valid for all possible subcommands of commands!
        if ($command == "commands" && $newlevel == "DISABLED") {
            return;
        }
        // Check that a correct access level is used:
        if (!(in_array($newlevel, $this->access_levels))) {
            $this->bot->log("ACCESS", "ERROR", "Trying to set an illegal default level of " . $newlevel . " for " . $command . " with subcommand " . $sub . " in " . $channel . "!");
            return;
        }
        if ($newlevel == 'DELETED' && $sub == '*') {
            $this->bot->log("ACCESS", "ERROR", "You cannot delete commands (subcommand * entries)! " . "Command: " . $command . " with subcommand " . $sub . " in " . $channel . "!");
            return;
        }
        // Add access level if not yet existing, otherwise update existing entry:
        $this->bot->db->query("INSERT INTO #___access_control (command, subcommand, channel, minlevel) VALUES ('" . $command . "', '" . $sub . "', '" . $channel . "', '" . $newlevel . "') ON DUPLICATE KEY UPDATE minlevel = '" . $newlevel . "'");
        // Update cache:
        $this->access_cache[$command][$sub][$channel] = $newlevel;
        $this->bot->core("help")->update_cache();
    }


    // Creates default rights for a command $command with subcommand $sub in channel $channel.
    // This function never changes any existing access levels. It never accepts the general subcommand *.
    // $channel = "all" means set default rights for all channels (gc, pgmsg, tell). You always have to set explicit rights for extpgmsg
    function create_subcommand($channel, $command, $sub, $defaultlevel)
    {
        $command      = strtolower($command);
        $sub          = strtolower($sub);
        $channel      = strtolower($channel);
        $defaultlevel = strtoupper($defaultlevel);
        // Check that a correct access level is used:
        if (!(in_array($defaultlevel, $this->access_levels))) {
            $this->bot->log("ACCESS", "ERROR", "Trying to set an illegal default level of " . $defaultlevel . " for " . $command . " with subcommand " . $sub . " in " . $channel . "!");
            return;
        }
        // Make sure the channel is valid:
        if (!(in_array($channel, $this->channels))) {
            $this->bot->log("ACCESS", "ERROR", "Trying to set default access rights for an illegal channel: " . $channel . " with subcommand " . $sub . " for " . $command . "!");
            return;
        }
        if ($sub == "*") {
            $this->bot->log("ACCESS", "ERROR", "You cannot set access levels for the general subcommand * " . "using the create_subcommand() function! Use create() for that! Command " . $command . "!");
            return;
        }
        if ($channel == "all") {
            $chans = array("tell",
                           "pgmsg",
                           "gc");
            foreach ($chans as $chan)
            {
                // Add the default level if no entry for the channel/command combination exists yet:
                $this->bot->db->query("INSERT IGNORE INTO #___access_control (command, subcommand, channel, minlevel) VALUES ('" . $command . "', '" . $sub . "', '" . $chan . "', '" . $defaultlevel . "')");
                // Add to cache if not set yet:
                if (!isset($this->access_cache[$command][$sub][$chan])) {
                    $this->access_cache[$command][$sub][$chan] = $defaultlevel;
                }
            }
        }
        else
        {
            // Add the default level if no entry for the channel/command combination exists yet:
            $this->bot->db->query("INSERT IGNORE INTO #___access_control (command, subcommand, channel, minlevel) VALUES ('" . $command . "', '" . $sub . "', '" . $channel . "', '" . $defaultlevel . "')");
            // Add to cache if not set yet:
            if (!isset($this->access_cache[$command][$sub][$channel])) {
                $this->access_cache[$command][$sub][$channel] = $defaultlevel;
            }
        }
    }


    // Saves the current access control settings under $name with $description into the access_control_saves table.
    function save($name, $desc, $update = FALSE)
    {
        $count    = 0;
        $countsub = 0;
        $cshort   = array("tell"     => "t",
                          "gc"       => "g",
                          "pgmsg"    => "p",
                          "extpgmsg" => "e");
        $lshort   = array("ANONYMOUS"  => "AN",
                          "GUEST"      => "G",
                          "MEMBER"     => "M",
                          "LEADER"     => "L",
                          "ADMIN"      => "A",
                          "SUPERADMIN" => "SA",
                          "OWNER"      => "O",
                          "DISABLED"   => "D");
        foreach ($this->access_cache as $command => $value)
        {
            unset($subs);
            foreach ($value as $subcom => $value2)
            {
                unset($chans, $chan);
                foreach ($value2 as $channel => $level)
                {
                    if (isset($this->bot->commands[$channel][$command])) {
                        $chans[$channel] = $level;
                        if ($subcom == "*") {
                            $count++;
                        }
                        else
                        {
                            $countsub++;
                        }
                    }
                }
                if (!empty($chans)) {
                    if ($chans["tell"] && $chans["tell"] == $chans["gc"] && $chans["tell"] == $chans["pgmsg"] && !$chans["extpgmsg"]) {
                        $chan[] = "a," . $lshort[$chans["tell"]];
                    }
                    elseif ($chans["tell"] && !$chans["gc"] && $chans["tell"] == $chans["pgmsg"] && !$chans["extpgmsg"])
                    {
                        $chan[] = "r," . $lshort[$chans["tell"]];
                    }
                    else
                    {
                        foreach ($chans as $channel => $level)
                        {
                            $chan[] = $cshort[$channel] . "," . $lshort[$level];
                        }
                    }
                    $chan = implode("|", $chan);
                }
                $subs[] = $subcom . "." . $chan;
            }
            $subs   = implode("!", $subs);
            $coms[] = $command . ":" . $subs;
            //;com:subcom.chan,lvl|chan,lvl!subcom.chan,lvl|chan,lvl;
        }
        $save = implode(";", $coms);
        if ($update) {
            $sql = " ON DUPLICATE KEY UPDATE description = '" . mysql_real_escape_string($desc) . "', commands = '" . $save . "'";
        }
        $this->bot->db->query("INSERT INTO #___access_control_saves (name, description, commands) VALUES ('" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($desc) . "', '" . $save . "')" . $sql);
        Return (array($count,
                      $countsub));
    }


    // Loads the settings saved under $name in the access_control_saves table and uses those rights
    function load($name)
    {
        $count    = 0;
        $countsub = 0;
        $clong    = array("t" => "tell",
                          "g" => "gc",
                          "p" => "pgmsg",
                          "e" => "extpgmsg");
        $llong    = array('AN' => 'ANONYMOUS',
                          'G'  => 'GUEST',
                          'M'  => 'MEMBER',
                          'L'  => 'LEADER',
                          'A'  => 'ADMIN',
                          'SA' => 'SUPERADMIN',
                          'O'  => 'OWNER',
                          'D'  => 'DISABLED');
        $results  = $this->bot->db->select("SELECT commands FROM #___access_control_saves WHERE name = '" . mysql_real_escape_string($name) . "'");
        if (!empty($results)) {
            $commands = explode(";", $results[0][0]);
            foreach ($commands as $command)
            {
                $var         = explode(":", $command, 2);
                $com         = $var[0];
                $subcommands = explode("!", $var[1]);
                foreach ($subcommands as $subcommand)
                {
                    $var      = explode(".", $subcommand, 2);
                    $subcom   = $var[0];
                    $channels = explode("|", $var[1]);
                    foreach ($channels as $channel)
                    {
                        $var  = explode(",", $channel, 2);
                        $chan = $var[0];
                        $lvl  = $var[1];
                        if ($chan == "a") {
                            $load[] = array($com,
                                            $subcom,
                                            "tell",
                                            $llong[$lvl]);
                            $load[] = array($com,
                                            $subcom,
                                            "gc",
                                            $llong[$lvl]);
                            $load[] = array($com,
                                            $subcom,
                                            "pgmsg",
                                            $llong[$lvl]);
                        }
                        elseif ($chan == "r")
                        {
                            $load[] = array($com,
                                            $subcom,
                                            "tell",
                                            $llong[$lvl]);
                            $load[] = array($com,
                                            $subcom,
                                            "pgmsg",
                                            $llong[$lvl]);
                        }
                        else
                        {
                            $load[] = array($com,
                                            $subcom,
                                            $clong[$chan],
                                            $llong[$lvl]);
                        }
                    }
                }
            }
            foreach ($load as $coms)
            {
                if (isset($this->bot->commands[$coms[2]][$coms[0]])) {
                    $this->bot->db->query("UPDATE #___access_control SET minlevel = '" . $coms[3] . "' WHERE command = '" . $coms[0] . "' AND subcommand = '" . $coms[1] . "' AND channel = '" . $coms[2] . "'");
                    $this->access_cache[$coms[0]][$coms[1]][$coms[2]] = $coms[3];
                    if ($coms[1] == "*") {
                        $count++;
                    }
                    else
                    {
                        $countsub++;
                    }
                }
            }
            $this->bot->core("help")->update_cache();
            Return (array($count,
                          $countsub));
        }
        else
        {
            Return FALSE;
        }
    }


    // Checks if $name can access $command, including all subcommands. If $name can access one of the options it returns true, otherwise false
    function check_for_access($name, $command)
    {
        $command  = strtolower($command);
        $minlevel = $this->get_min_access_level($command);
        if ($minlevel == OWNER + 1) {
            return false;
        }
        return $this->bot->core("security")->check_access($name, $minlevel);
    }


    // Returns the array of all access levels
    function get_access_levels()
    {
        return $this->access_levels;
    }


    // Does the innermost check to get the minimum access level for $command $subcommand in $channel:
    function do_min_check($command, $subcommand, $channel)
    {
        // If disabled or delted, return false, as no access is allowed
        if (in_array($this->access_cache[strtolower($command)][strtolower($subcommand)][strtolower($channel)], $this->deny_levels)) {
            return OWNER + 1;
        }
        else
        {
            // Otherwise check access level of $user vs the minlevel of $command, $subcommand in $channel:
            return constant($this->access_cache[strtolower($command)][strtolower($subcommand)][strtolower($channel)]);
        }
    }


    // Returns the minimum access level needed to access $command in $channel, with full input $msg.
    // Returns OWNER + 1 if the command isn't enabled
    function get_min_rights($command, $msg, $channel)
    {
        // Check for locks in gc or pgmsg first:
        if (($channel == "gc" && $this->bot->core("settings")
            ->get("AccessControl", "LockGc")) || ($channel == "pgmsg" && $this->bot
            ->core("settings")->get("AccessControl", "LockPgroup"))
        ) {
            return OWNER + 1;
        }
        $subcommand = strpos($msg, " ");
        if ($subcommand === false) {
            // No subcommand, check if special "only command" entry exists, otherwise fall back on
            // general entry for command if that exists.
            if (isset($this->access_cache[strtolower($command)]['$'][strtolower($channel)])) {
                return $this->do_min_check($command, '$', $channel);
            }
            // Check general entry:
            if (isset($this->access_cache[strtolower($command)]['*'][strtolower($channel)])) {
                return $this->do_min_check($command, '*', $channel);
            }
            // No entry for command at all, access denied on default:
            return OWNER + 1;
        }
        else
        {
            // Possible subcommand, extract it and check for entry:
            $parts = explode(" ", $msg, 3);
            // "$" is not allowed as subcommand, same for "*" - both are special chars in subcommand field.
            // Check only general entry then:
            if (strcmp($parts[1], "*") == 0 || strcmp($parts[1], "$") == 0) {
                if (isset($this->access_cache[strtolower($command)]['*'][strtolower($channel)])) {
                    return $this->do_min_check($command, '*', $channel);
                }
                else
                {
                    // No access level defined, access denied on default:
                    return OWNER + 1;
                }
            }
            // We got a true subcommand, if entry for this subcommand exists use it for access check:
            if (isset($this->access_cache[strtolower($command)][strtolower($parts[1])][strtolower($channel)])) {
                // Make sure the access control entry for this subcommand is not deleted
                if ($this->access_cache[strtolower($command)][strtolower($parts[1])][strtolower($channel)] != 'DELETED') {
                    return $this->do_min_check($command, $parts[1], $channel);
                }
            }
            // Otherwise check if general entry for $command exists, then use that:
            if (isset($this->access_cache[strtolower($command)]['*'][strtolower($channel)])) {
                return $this->do_min_check($command, '*', $channel);
            }
            // No fitting entry exists, access denied on default:
            return OWNER + 1;
        }
    }


    // Returns the minimum access level to access $command in $channel
    // or any channel (tell, gc, pgmsg) if $channel is set to FALSE.
    function get_min_access_level($command, $channel = FALSE)
    {
        $command = strtolower($command);
        if ($channel) {
            $channel = strtolower($channel);
        }
        if (isset($this->access_cache[$command])) {
            $min = OWNER + 1;
            foreach ($this->access_cache[$command] as $sub => $chans)
            {
                foreach ($chans as $chan => $level)
                {
                    if (in_array($level, $this->security_levels)) {
                        if ((!$channel && constant($level) < $min) || ($channel == $chan && constant($level) < $min)) {
                            $min = constant($level);
                        }
                    }
                }
            }
            return $min;
        }
        return OWNER + 1;
    }
}

?>
