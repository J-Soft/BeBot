<?php
/*
* Security.php - Provide security to all of BeBot.
*
* See http://wiki.bebot.link/index.php/Security for full on
* usage of BeBots Security System.
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
* - Bitnykk (RK5)
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

// Define Access Levels as Constants, these are available globally...
// There is no way to change these during runtime.
define("OWNER", 256); // Outside range of MySQL TINYINT UNSIGNED
define("SUPERADMIN", 255);
define("ADMIN", 192);
define("LEADER", 128);
define("MEMBER", 2);
define("GUEST", 1);
define("ANONYMOUS", 0);
define("BANNED", -1); // Outside range of MySQL TINYINT UNSIGNED

$security = new Security_Core($bot);

/*
The Class itself...
*/
class Security_Core extends BaseActiveModule
{ // Start Class
    var $enabled; // Set to true when the security subsystem is ready.
    /*
    The $firstcon and $gocron variables make an end run around cron...
    The idea is to do cron on bot startup, but then only do cron actions
    when the roster update is not happening.
    */
    var $firstcron; // Cron Control Hack.
    var $gocron; // Cron Crontrol Hack part 2. :)
    var $_super_admin; // SuperAdmins from Bot.conf
    var $_owner; // Owner from Bot.conf
    var $_cache; // Security Cache.
    var $last_alts_status; // Check status of setting UseAlts, if it changes clear main cache.
	var $owner, $super_admin, $cache, $last_leader;
    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));

        $this->register_module("security");
        $this->register_event("cron", "12hour");
        $this->register_event("connect");

        // Create security_groups table.
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("security_groups", "true") . "
					(gid INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(35) UNIQUE,
					description VARCHAR(80),
					access_level TINYINT UNSIGNED NOT NULL DEFAULT 0)"
        );

        // Create default Security Groups (superadmin, admin, leader)
        $sql = "INSERT IGNORE INTO #___security_groups (name, description, access_level) VALUES ";
        $sql .= "('superadmin', 'Super Administrators', 255),";
        $sql .= "('admin', 'Administrators', 192),";
        $sql .= "('leader', 'Raid Leaders', 128)";
        $this->bot->db->query($sql);
        unset($sql);

        // Create security_members table.
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("security_members", "true") . "
						(id INT UNIQUE NOT NULL AUTO_INCREMENT,
						name VARCHAR(50),
						gid INT,
						PRIMARY KEY (name, gid),
						KEY (id)
						)"
        );

        // All org members will be bot members so give org ranks a default access_level of 2.
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("security_org", "true") . "
					(org_gov VARCHAR(25) NOT NULL,
					org_rank VARCHAR(25) NOT NULL,
					org_rank_id TINYINT UNSIGNED NOT NULL,
					access_level TINYINT UNSIGNED NOT NULL DEFAULT 2,
					PRIMARY KEY (org_gov, org_rank, org_rank_id))
					"
        );

        // Insert Ranks into table.
        $sql = "INSERT IGNORE INTO #___security_org (org_gov, org_rank, org_rank_id) VALUES ";
        $sql .= "('Department', 'President', 0), ";
        $sql .= "('Department', 'General', 1), ";
        $sql .= "('Department', 'Squad Commander', 2), ";
        $sql .= "('Department', 'Unit Commander', 3), ";
        $sql .= "('Department', 'Unit Leader', 4), ";
        $sql .= "('Department', 'Unit Member', 5), ";
        $sql .= "('Department', 'Applicant', 6), ";
        $sql .= "('Faction', 'Director', 0), ";
        $sql .= "('Faction', 'Board Member', 1), ";
        $sql .= "('Faction', 'Executive', 2), ";
        $sql .= "('Faction', 'Member', 3), ";
        $sql .= "('Faction', 'Applicant', 4), ";
        $sql .= "('Republic', 'President', 0), ";
        $sql .= "('Republic', 'Advisor', 1), ";
        $sql .= "('Republic', 'Veteran', 2), ";
        $sql .= "('Republic', 'Member', 3), ";
        $sql .= "('Republic', 'Applicant', 4), ";
        $sql .= "('Monarchy', 'Monarch', 0), ";
        $sql .= "('Monarchy', 'Consil', 1), ";
        $sql .= "('Monarchy', 'Follower', 2), ";
        $sql .= "('Feudalism', 'Lord', 0), ";
        $sql .= "('Feudalism', 'Knight', 1), ";
        $sql .= "('Feudalism', 'Vassal', 2), ";
        $sql .= "('Feudalism', 'Peasant', 3), ";
        $sql .= "('Anarchism', 'Anarchist', 1)";
        $this->bot->db->query($sql);
        unset($sql);

        $this->enabled = false;

        $this->owner = ucfirst(strtolower($bot->owner));
        $this->super_admin = array();
        if (!empty($bot->super_admin) && is_array($bot->super_admin) && count($bot->super_admin)>0) {
            foreach ($bot->super_admin as $user => $value) {
                $this->super_admin[ucfirst(strtolower($user))] = $value;
            }
        }
        $this->firstcron = true;
        $this->gocron = true;

        $this->help['description'] = "Handles the security groups, their rights and their members.";
        $this->help['command']['admin groups'] = "Shows all security groups and their members.";
        $this->help['command']['admin group add <groupname>'] = "Adds the new group <groupname> with ANONYMOUS rights.";
        $this->help['command']['admin group del <groupname>'] = "Removes the group <groupname>.";
        $this->help['command']['admin add <group> <name>'] = "Adds <name> as member to the group <group>.";
        $this->help['command']['admin del <group> <name>'] = "Removes <name> as member from the group <group>.";
        $this->help['command']['admin del <name>'] = "Removes <name> from the bot and all security groups.";
        $this->help['command']['addgroup <group> <desc>'] = "Adds a new group named <group> with description <desc>.";
        $this->help['command']['adduser <name>'] = "Adds <name> as GUEST to guild bots or as MEMBER to raid bots.";
        $this->help['command']['adduser <name> <group>'] = "Adds <name> as member to the security group <group>.";
        $this->help['command']['delgroup <group>'] = "Deletes the security group <group>";
        $this->help['command']['deluser <name>'] = "Removes <name> from the bot and all security groups.";
        $this->help['command']['deluser <name> <group>'] = "Removes <name> from the security group <group>.";
        $this->help['command']['security'] = "Display security system main menu.";
        $this->help['command']['security groups'] = "Display security groups.";
        $this->help['command']['security levels'] = "Display security access levels.";
        $this->help['notes'] = "The owner and superadmins defined in the config file cannot be modified in any way.";
    }


    /*
    This gets called when bot connects
    */
    function connect()
    { // Start function connect()
        // Bind the command to the bot
        // Can't be done earlier as otherwise we'd end in a requirement loop with access control
        $this->register_command("all", "security", "SUPERADMIN");
        $this->register_command("all", "adduser", "SUPERADMIN");
        $this->register_command("all", "deluser", "SUPERADMIN");
		$this -> register_alias("deluser", "remuser");
        $this->register_command("all", "addgroup", "SUPERADMIN");
        $this->register_command("all", "delgroup", "SUPERADMIN");
		$this -> register_alias("delgroup", "remgroup");
        $this->register_command("all", "admin", "SUPERADMIN");

        $this->enable();

        $this->bot->core("settings")
            ->create(
                "Security",
                "orggov",
                "Unknown",
                "Orginization Government Form",
                "Anarchism;Department;Faction;Feudalism;Monarchy;Republic;Unknown",
                true,
                99
            );
    } // End function connect()

    /*
    This gets called on cron
    */
    function cron()
    { // Start function cron()
        if (!$this->enabled) {
            $this->enable();
        }
        if ($this->gocron) {
            // Do cron stuff
            $this->cache_security();
            $this->set_government();
            // End cron stuff with this.
            if ($this->firstcron) {
                $this->firstcron = false;
            } else {
                $this->gocron = false;
            }
        } else {
            $this->gocron = true; // Don't do cron stuff, but do it next time.
        }
    } // End function cron()

    /*
    As this module depends on settings module, and Security.php will be loaded before
    Settings.php, this function will be called by the first cron job. This function
    initilized the security cache then enables security.
    */
    function enable()
    { // Start function enable()
        $this->cache = array();
        $this->cache_security(); // Populate the security cache.
        // Customizable Security Settings.
        $longdesc = "Should be run over all alts to get the highest access level for the queried characters?";
        $this->bot->core("settings")
            ->create("Security", "UseAlts", false, $longdesc);
        $longdesc = "Should all characters in the chat group of the bot be considered GUESTs for security reasons?";
        $this->bot->core("settings")
            ->create("Security", "GuestInChannel", true, $longdesc);
        $longdesc = "Security Access Level required to add members and guests.";
        $this->bot->core("settings")
            ->create("Security", "adduser", "SUPERADMIN", $longdesc, "OWNER;SUPERADMIN;ADMIN;LEADER", false, 1);
        $longdesc = "Security Access Level required to remove members and guests.";
        $this->bot->core("settings")
            ->create("Security", "deluser", "SUPERADMIN", $longdesc, "OWNER;SUPERADMIN;ADMIN;LEADER", false, 2);
        $longdesc = "Security Access Level required to add security groups.";
        $this->bot->core("settings")
            ->create("Security", "addgroup", "SUPERADMIN", $longdesc, "OWNER;SUPERADMIN;ADMIN;LEADER", false, 3);
        $longdesc = "Security Access Level required to remove security groups.";
        $this->bot->core("settings")
            ->create("Security", "delgroup", "SUPERADMIN", $longdesc, "OWNER;SUPERADMIN;ADMIN;LEADER", false, 4);
        $longdesc = "Security Access Level required to add users to security groups.";
        $this->bot->core("settings")
            ->create("Security", "addgroupmember", "SUPERADMIN", $longdesc, "OWNER;SUPERADMIN;ADMIN;LEADER", false, 5);
        $longdesc = "Security Access Level required to remove users from security groups.";
        $this->bot->core("settings")
            ->create("Security", "remgroupmember", "SUPERADMIN", $longdesc, "OWNER;SUPERADMIN;ADMIN;LEADER", false, 6);
        $longdesc = "Security Access Level required to use <pre>security whois name.";
        $this->bot->core("settings")
            ->create("Security", "whois", "leader", $longdesc, "OWNER;SUPERADMIN;ADMIN;LEADER", false, 10);
        $longdesc = "Security Access Level required to change settings for all modules.";
        $this->bot->core("settings")
            ->create("Security", "settings", "SUPERADMIN", $longdesc, "OWNER;SUPERADMIN;ADMIN;LEADER", false, 99);

        $this->enabled = true;
        $this->last_alts_status = $this->bot->core("settings")
            ->get("Security", "UseAlts");
        $this->last_leader = "";
    } // End function enable()

    /*
    Unified message handler
    */
    function command_handler($source, $msg, $msgtype)
    { // Start funciton handler
        $vars = explode(' ', strtolower($msg));

        $command = $vars[0];
		if(!isset($vars[1])) { $vars[1]=""; }
        switch ($command) {
            case 'security':
                switch ($vars[1]) {
                    case "changelevel":
                        if ($this->check_access($source, "SUPERADMIN")) {
                            //The following is in URGENT NEED of comments!
                            if (preg_match(
                                "/^security changelevel (Department) (Squad|Unit|Board) (Commander|Leader|Member) (.+)$/i",
                                $msg,
                                $info
                            )
                            ) {
                                return $this->change_level($info[2] . " " . $info[3], $info[4], $info[1]); // SA
                            } elseif (preg_match(
                                "/^security changelevel (Faction) (Board Member) (.+)$/i",
                                $msg,
                                $info
                            )
                            ) {
                                return $this->change_level($info[2], $info[3], $info[1]); // SA
                            } elseif (preg_match("/^security changelevel (.+?) (.+?) (.+)$/i", $msg, $info)) {
                                return $this->change_level($info[2], $info[3], $info[1]); // SA
                            } elseif (preg_match("/^security changelevel (.+?) (.+)$/i", $msg, $info)) {
                                return $this->change_level($info[1], $info[2]); // SA
                            }
                        } else {
                            $this->error->set("Only SUPERADMINs can access the changelevel command.");
                            return $this->error;
                        }
                        break;
                    case "levels":
                        if ($this->check_access($source, "SUPERADMIN")) {
                            return $this->show_security_levels($msgtype); // A
                        } else {
                            $this->error->set("Only SUPERADMINs can modify the access levels.");
                            return $this->error;
                        }
                    case "whois":
                        if ($this->check_access(
                            $source,
                            $this->bot
                                ->core("settings")->get('Security', 'Whois')
                        )
                        ) {
                            return $this->whois($vars[2]);
                        } else {
                            $this->error->set(
                                "You must have " . strtoupper(
                                    $this->bot
                                        ->core("settings")
                                        ->get('Security', 'Whois')
                                ) . " access or higher to use <pre>security whois"
                            );
                            return $this->error;
                        }
                        break;
                    case "whoami":
                        if ($this->check_access($source, "GUEST")) {
                            return $this->whoami($source);
                        } else {
                            $this->bot->log(
                                "SECURITY",
                                "DENIED",
                                "Player " . $source . " was denied access to <pre>security whoami."
                            );
                            return false;
                        }
                        break;
                    case "groups":
                        if ($this->check_access($source, "GUEST")) {
                            return $this->show_groups();
                        } else {
                            $this->error->set("You need to be GUEST or higher to access 'groups'");
                            return $this->error;
                        }
                        break;
                    default:
                        if ($this->check_access($source, "GUEST")) {
                            return $this->show_security_menu($source);
                        } else {
                            $this->error->set("You need to be GUEST or higher to access 'check_access'");
                            return $this->error;
                        }
                        break;
                }
                break;
            case 'adduser': // adduser username group
                if (isset($vars[2])) {
                    if ($this->check_access(
                        $source,
                        $this->bot
                            ->core("settings")->get('Security', 'Addgroupmember')
                    )
                    ) {
                        return $this->add_group_member($vars[1], $vars[2], $source);
                    } else {
                        $this->error->set(
                            "Only " . strtoupper(
                                $this->bot
                                    ->core("settings")
                                    ->get('Security', 'Addgroupmember')
                            ) . "s and above can add group members."
                        );
                        return $this->error;
                    }
                } else {
                    if ($this->check_access(
                        $source,
                        $this->bot
                            ->core("settings")->get('Security', 'Adduser')
                    )
                    ) {
                        return $this->add_user($source, $vars[1]);
                    } else {
                        $this->error->set(
                            "Only " . strtoupper(
                                $this->bot
                                    ->core("settings")
                                    ->get('Security', 'Adduser')
                            ) . "s and above can add users."
                        );
                        return $this->error;
                    }
                }
                break;
            case 'deluser':
                if (isset($vars[2])) {
                    if ($this->check_access(
                        $source,
                        $this->bot
                            ->core("settings")->get('Security', 'Remgroupmember')
                    )
                    ) {
                        return $this->rem_group_member($vars[1], $vars[2], $source);
                    } else {
                        $this->error->set(
                            "Only " . strtoupper(
                                $this->bot
                                    ->core("settings")
                                    ->get('Security', 'Remgroupmember')
                            ) . "s and above can remove group members."
                        );
                        return $this->error;
                    }
                } else {
                    if ($this->check_access(
                        $source,
                        $this->bot
                            ->core("settings")->get('Security', 'Deluser')
                    )
                    ) {
                        return $this->del_user($source, $vars[1]);
                    } else {
                        $this->error->set(
                            "Only " . strtoupper(
                                $this->bot
                                    ->core("settings")
                                    ->get('Security', 'Deluser')
                            ) . "s and above can remove users."
                        );
                        return $this->error;
                    }
                }
                break;
            case 'addgroup':
                if ($this->check_access(
                    $source,
                    $this->bot->core("settings")
                        ->get('Security', 'Addgroup')
                )
                ) {
                    if (preg_match("/^addgroup (.+?) (.+)$/i", $msg, $info)) {
                        return $this->add_group($info[1], $info[2], $source);
                    } else {
                        $this->error->set(
                            "Not enough paramaters given. Try /tell <botname> <pre>addgroup groupname description."
                        );
                    }
                } else {
                    $this->error->set(
                        "Only " . strtoupper(
                            $this->bot
                                ->core("settings")
                                ->get('Security', 'Addgroup')
                        ) . "s and above can add groups."
                    );
                    return $this->error;
                }
                break;
            case 'delgroup':
                if ($this->check_access(
                    $source,
                    $this->bot->core("settings")
                        ->get('Security', 'Delgroup')
                )
                ) {
                    if (isset($vars[1])) {
                        return $this->del_group($vars[1], $source);
                    } else {
                        $this->error->set("Not enough paramaters given. Try /tell <botname> <pre>delgroup groupname.");
                        return $this->error;
                    }
                } else {
                    $this->error->set(
                        "Only " . strtoupper(
                            $this->bot
                                ->core("settings")
                                ->get('Security', 'Delgroup')
                        ) . "s and above can delete groups."
                    );
                    return $this->error;
                }
                break;
            case 'admin':
                if (preg_match("/^admin group(s){0,1}$/i", $msg)) {
                    if ($this->check_access($source, "guest")) {
                        return $this->show_groups();
                    } else {
                        return false; // FIXME: Nothing returned?
                    }
                } else {
                    if (preg_match("/^admin group add (.+?) (.+)$/i", $msg, $info)) {
                        if ($this->check_access(
                            $source,
                            $this->bot
                                ->core("settings")->get('Security', 'Addgroupmember')
                        )
                        ) {
                            return $this->add_group($info[1], $info[2], $source);
                        } else {
                            $this->error->set(
                                "Only " . strtoupper(
                                    $this->bot
                                        ->core("settings")
                                        ->get('Security', 'Addgroup')
                                ) . "s and above can add groups."
                            );
                            return $this->error;
                        }
                    } else {
                        if (preg_match("/^admin group add ([a-zA-Z0-9]+)$/i", $msg, $info)) {
                            if ($this->check_access(
                                $source,
                                $this->bot
                                    ->core("settings")->get('Security', 'Addgroupmember')
                            )
                            ) {
                                return $this->add_group($info[1], " ", $source); // No group description
                            } else {
                                $this->error->set(
                                    "Only " . strtoupper(
                                        $this->bot
                                            ->core("settings")
                                            ->get('Security', 'Addgroup')
                                    ) . "s and above can add groups."
                                );
                                return $this->error;
                            }
                        } else {
                            if (preg_match("/^admin group (remove|rem|del) ([a-zA-Z0-9]+)$/i", $msg, $info)) {
                                if ($this->check_access(
                                    $source,
                                    $this->bot
                                        ->core("settings")->get('Security', 'Delgroup')
                                )
                                ) {
                                    return $this->del_group($info[2], $source);
                                } else {
                                    $this->error->set(
                                        "Only " . strtoupper(
                                            $this->bot
                                                ->core("settings")
                                                ->get('Security', 'Delgroup')
                                        ) . "s and above can delete groups."
                                    );
                                    return $this->error;
                                }
                            } else {
                                if (preg_match("/^admin add ([a-zA-Z0-9]+) ([a-zA-Z0-9]+)$/i", $msg, $info)) {
                                    if ($this->check_access(
                                        $source,
                                        $this->bot
                                            ->core("settings")->get('Security', 'Addgroupmember')
                                    )
                                    ) {
                                        return $this->add_group_member($info[2], $info[1], $source);
                                    } else {
                                        $this->error->set(
                                            "Only " . strtoupper(
                                                $this->bot
                                                    ->core("settings")
                                                    ->get('Security', 'Addgroupmember')
                                            ) . "s and above and add group members."
                                        );
                                        return ($this->error);
                                    }
                                } else {
                                    if (preg_match(
                                        "/^admin (remove|rem|del) ([a-zA-Z0-9]+) ([a-zA-Z0-9]+)$/i",
                                        $msg,
                                        $info
                                    )
                                    ) {
                                        if ($this->check_access(
                                            $source,
                                            $this->bot
                                                ->core("settings")->get('Security', 'Remgroupmember')
                                        )
                                        ) {
                                            return $this->rem_group_member($info[3], $info[2], $source);
                                        } else {
                                            $this->error->set(
                                                "Only " . strtoupper(
                                                    $this->bot
                                                        ->core("settings")
                                                        ->get('Security', 'Remgroupmember')
                                                ) . "s and above can remove group members."
                                            );
                                            return $this->error;
                                        }
                                    } else {
                                        if (preg_match("/^admin (remove|rem|del) ([a-zA-Z0-9]+)$/i", $msg, $info)) {
                                            if ($this->check_access(
                                                $source,
                                                $this->bot
                                                    ->core("settings")->get('Security', 'Deluser')
                                            )
                                            ) {
                                                return $this->del_user($source, $info[2]);
                                            } else {
                                                $this->error->set(
                                                    "Only " . strtoupper(
                                                        $this->bot
                                                            ->core("settings")
                                                            ->get('Security', 'Deluser')
                                                    ) . "s and above can delete users."
                                                );
                                                return $this->error;
                                            }
                                        } else {
                                            return $this->bot->send_help($source);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            default:
                $this->bot->send_tell($source, "Broken plugin, received unhandled command: $command");
        }
    } // End funciton handler

    /*
    Adds a group.
    */
    function add_group($groupname, $description, $caller = "Internal Process")
    { // Start function add_group()
        $groupname = strtolower($groupname);
        if ($groupname == "leader" || $groupname == "admin" || $groupname == "superadmin" || $groupname == "member"
            || $groupname == "guest"
            || $groupname == "anonymous"
            || $groupname == "owner"
        ) {
            $this->error->set(ucfirst($groupname) . " is a default group and cannot be created as a custom group.");
            return $this->error;
        }
        // Check for bad input.
        if (is_numeric($groupname)) {
            $this->error->set("Group Names should not be all numbers.");
            return $this->error;
        }
        if (strlen($groupname) < 5) {
            if (is_numeric($groupname)) {
                $this->error->set("Group Names should be five or more characters.");
                return $this->error;
            }
        }
        $groupname = str_replace(" ", "_", $groupname); // Replace Spaces with underscores.
        $groupname = $this->bot->db->real_escape_string($groupname); // If any slashes are added, it's an invalid group.
        if (strpos($groupname, "\\")) {
            $this->error->set(
                "Single quotes, double quotes, backslash and other special characters are not allowed in group names."
            );
            return $this->error;
        }
        // Input should be good now...
        if (isset($this->cache['groups'][$groupname]['gid'])) {
            $this->error->set("Group " . $groupname . " already exisits");
            return $this->error;
        }
        $sql = "INSERT INTO #___security_groups (name, description) ";
        $sql .= "VALUES ('" . $groupname . "', '" . $this->bot->db->real_escape_string($description) . "')";
        $this->bot->db->query($sql);
        $sql = "SELECT gid FROM #___security_groups WHERE name = '" . $groupname . "'";
        $result = $this->bot->db->select($sql);
        $gid = $result[0][0];
        unset($result);
        $tmp = array(
            "gid" => $gid,
            "name" => $groupname,
            "description" => $description,
            "access_level" => 0
        );
        $tmp['members'] = array();
        $this->cache_mgr("add", "groups", $tmp);
        $this->bot->log(
            "SECURITY",
            "ADDGROUP",
            $caller . " Created group " . $groupname . " with anonymous level privileges."
        );
        return "Created group " . $groupname . " with anonymous level privileges.";
    } // End function add_group()

    /*
    Deletes a group.
    */
    function del_group($target, $caller = "Internal Process")
    { // Start function del_group()
        $target = strtolower($target);
        if ($target == "leader" || $target == "admin" || $target == "superadmin") {
            $this->error->set($target . " cannot be deleted.");
            return $this->error;
        }
        $target = $this->bot->db->real_escape_string($target);
        if (is_numeric($target)) {
            $sql = "SELECT name FROM #___security_groups WHERE gid = '" . $target . "'"; // FIXME: Could use the cache here.
            $result = $this->bot->db->select($sql);
            if (!isset($this->cache['groups'][$target])) {
                $this->error->set("Group ID " . $target . " not found.");
                return $this->error;
            }
            $target = $this->cache['groups'][$target]['name'];
        }
        if (!isset($this->cache['groups'][$target]['gid'])) {
            $this->error->set("Group " . $target . " does not exisit");
            return $this->error;
        }
        $sql = "DELETE FROM #___security_members WHERE gid = '" . $this->cache['groups'][$target]['gid'] . "'";
        $this->bot->db->query($sql);
        $sql = "DELETE FROM #___security_groups WHERE gid = '" . $this->cache['groups'][$target]['gid'] . "'";
        $this->bot->db->query($sql);
        $this->bot->log(
            "SECURITY",
            "DELGROUP",
            $caller . " Deleted group ID " . $this->cache['groups'][$target]['gid'] . " Name: " . $target . "."
        );
        $this->cache_mgr("rem", "groups", $target);

        // Clear the flexible security cache if it exists:
        if ($this->bot->core("flexible_security") != null) {
            $this->bot->core("flexible_security")->clear_cache();
        }
        return "Deleted group "; //ID " . $this->cache['groups'][$target]['gid'] . " Name: " . $target . ".";
    } // End function del_group()

    /*
    Adds $target to $group
    */
    function add_group_member($target, $group, $caller = "Internal Process")
    { // Start function add_group_member()
		$target = $this->bot->core('tools')->sanitize_player($target);
        $group = strtolower($group);
        $uid = $this->bot->core('player')->id($target);
        if ($uid instanceof BotError) {
            $this->error->set($target . " is not a valid character.");
            return $this->error;
        }
        $gid = $this->get_gid($group);
        if ($gid == -1) {
            $this->error->set(
                "Unable to find group ID for " . $group . " " . $group . " may not exist. Check your spelling and try again."
            );
            return $this->error;
        }

        if (strtolower($caller) != "internal process" && $this->get_access_level(
                $caller
            ) < $this->cache['groups'][$gid]['access_level']
        ) {
            $this->error->set(
                "Your Access Level is less than the Access Level of " . $group . ". You cannot add members to " . $group . "."
            );
            return $this->error;
        }

        if (!isset($this->cache['groups'][$gid]['members'][$target])) {
            $sql = "INSERT INTO #___security_members (name,gid) VALUES ('" . $target . "', " . $gid . ")";
            $this->bot->db->query($sql);
            $this->cache_mgr("add", "groupmem", $group, $target);
            $this->bot->log("SECURITY", "GRPMBR", $caller . " Added " . $target . " to group " . $group . ".");
            return ("Added " . $target . " to group " . $group . ".");
        } else {
            $this->error->set($target . " is already a member of " . $group);
            return $this->error;
        }
    } // End function add_group_member()

    /*
    Removes $target from $group if $name is an admin.
    */
    function rem_group_member($target, $group, $caller)
    { // Start function rem_group_member()
        $target = $this->bot->core('tools')->sanitize_player($target);
        $group = strtolower($group);
        $uid = $this->bot->core('player')->id($target);
        if (!$uid) {
            $this->error->set($target . " is not a valid character.");
            return $this->error;
        }

        $gid = $this->get_gid($group);
        if ($gid == -1) {
            $this->error->set(
                "Unable to find group ID for " . $group . " " . $group . " may not exisit. Check your spelling and try again."
            );
            return $this->error;
        }


        if (!preg_match("/^Internal Process: (.*)$/i", $caller)) {
            if ($this->get_access_level($caller) < $this->cache['groups'][$group]['access_level']) {
                $this->error->set(
                    "Your Access Level is lower than " . $group . "'s Access Level. You cannot remove members from " . $group . "."
                );
                return $this->error;
            }
        }

        if (isset($this->cache['groups'][$group]['members'][$target])) {
            $sql = "DELETE FROM #___security_members WHERE name = '" . $target . "' AND gid = " . $this->cache['groups'][$group]['gid'];
            $this->bot->db->query($sql);
            $this->cache_mgr("rem", "groupmem", $group, $target);
            $this->bot->log("SECURITY", "GRPMBR", $caller . " Removed " . $target . " from " . $group);
            return "Removed " . $target . " from " . $group;
        } else {
            $this->error->set($target . " is not a member of " . $group);
            return $this->error;
        }
    } // End function rem_group_member()

    /*
    Adds a user as a guest or member.
    $admin = person setting the ban.
    $target = person being banned.
    */
    function add_user($admin, $target)
    { // Start function add_user()
		$admin = $this->bot->core('tools')->sanitize_player($admin);
        $target = $this->bot->core('tools')->sanitize_player($target);
        //$level = strtoupper($level);
        $uid = $this->bot->core('player')->id($target);
        // Check to see if user is banned.
        if ($this->is_banned($target)) {
            $this->error->set($target . " is banned.");
            return $this->error;
        }

        // Get whois data & check for errors.
        if (strtolower($this->bot->game) == 'ao') {
            $who = $this->bot->core("whois")->lookup($target);
            if ($who instanceof BotError) {
                return $who;
            }
        }

        if ($this->bot->guildbot) // If this is a guildbot, we can only add guests.
        {
            $level = "GUEST";
            $lvlnum = GUEST;
            $cache = "guests";
        } else // If it's a raid bot, we should only add members...
        {
            $level = "MEMBER";
            $lvlnum = MEMBER;
            $cache = "members";
        }

        // Check to see if they are already a member
        if (isset($this->cache[$cache][$target])) {
            $this->error->set(ucfirst($target) . " is already a " . $level . ".");
            return $this->error;
        } else {
            $this->cache_mgr("add", $cache, $target);
            $sql = "INSERT INTO #___users (char_id, nickname, added_by, added_at, user_level, updated_at) ";
            $sql .= "VALUES (" . $uid . ", '" . $target . "', '" . $this->bot->db->real_escape_string(
                    $admin
                ) . "', " . time() . ", " . $lvlnum . ", " . time() . ") ";
            $sql .= "ON DUPLICATE KEY UPDATE added_by = VALUES(added_by), added_at = VALUES(added_at), user_level=VALUES(user_level), updated_at = VALUES(updated_at)";
            $this->bot->db->query($sql);
            $this->bot->log("SECURITY", "ADDUSER", $admin . " Added " . $target . " as a " . $level);
            return "Added " . $target . " as a " . $level;
        }
    } // End function add_user()

    /*
    Removes a user
    */
    function del_user($admin, $target)
    { // Start function del_user()
		$admin = $this->bot->core('tools')->sanitize_player($admin);
        $target = $this->bot->core('tools')->sanitize_player($target);
        if (!isset($this->cache["members"][$target]) && !isset($this->cache["guests"][$target])) {
            $this->error->set($target . " is not a member of <botname>.");
            return $this->error;
        } else {
            $this->cache_mgr("rem", "members", $target);
            $this->cache_mgr("rem", "guests", $target);
            $groups = $this->get_groups($target);
            if ($groups <> -1) {
                foreach ($groups as $gid) {
                    $this->rem_group_member($target, $this->cache['groups'][$gid]['name'], $admin);
                }
            }
            $this->bot->core("notify")->del($target);
            $sql
                =
                "UPDATE #___users SET user_level = 0, deleted_by = '" . $this->bot->db->real_escape_string(
                    $admin
                ) . "', deleted_at = " . time() . ", notify = 0 WHERE nickname = '" . $target
                . "'";
            $this->bot->db->query($sql);
            $this->bot->log("SECURITY", "DELUSER", $admin . " " . $target . " has been removed from <botname>.");
            return $target . " has been removed from <botname>.";
        }
    } // End function del_user()

    /*
    $admin = person setting the ban.
    $target = person being banned.
    */
    function set_ban(
        $admin,
        $target,
        $caller = "Internal Process",
        $reason = "None given.",
        $endtime = 0
    ) { // Start function set_ban()
        $admin = ucfirst(strtolower($admin));
        $target = ucfirst(strtolower($target));

        if (!$this->bot->core('player')->id($target)) {
            $this->error->set($target . " is not a valid character!");
            return $this->error;
        }

        if ($this->check_access($target, "OWNER")) {
            $this->error->set($target . " is the bot owner and cannot be banned.");
            return $this->error;
        }

        if (isset($this->cache['banned'][$target])) {
            $this->error->set($target . " is already banned.");
            return $this->error;
        } elseif (isset($this->cache['guests'][$target])) {
            $this->cache_mgr("rem", "guests", $target);
            $this->cache_mgr("add", "banned", $target);
            $sql = "UPDATE #___users SET user_level = -1, banned_by = '" . $this->bot->db->real_escape_string(
                    $admin
                ) . "', banned_at = " . time() . ", banned_for = '"
                . $this->bot->db->real_escape_string(
                    $reason
                ) . "', banned_until = " . $endtime . " WHERE nickname = '" . $target . "'";
        } elseif (isset($this->cache['members'][$target])) {
            $this->cache_mgr("rem", "members", $target);
            $this->cache_mgr("add", "banned", $target);
            $sql = "UPDATE #___users SET user_level = -1, banned_by = '" . $this->bot->db->real_escape_string(
                    $admin
                ) . "', banned_at = " . time() . ", updated_at = " . time()
                . ", banned_for = '" . $this->bot->db->real_escape_string(
                    $reason
                ) . "', banned_until = " . $endtime . " WHERE nickname = '" . $target . "'";
        } else // They are not in the member table at all.
        {
            $who = $this->bot->core("whois")->lookup($target);
            if ($who instanceof BotError) {
                return $who;
            }
            $this->cache_mgr("add", "banned", $target);
            $sql = "INSERT INTO #___users (char_id,nickname,added_by,added_at,banned_by,banned_at,banned_for,banned_until,notify,user_level,updated_at) ";
            $sql
                .=
                "VALUES ('" . $who['id'] . "', '" . $who['nickname'] . "', '" . $this->bot->db->real_escape_string(
                    $admin
                ) . "', " . time() . ", '" . $this->bot->db->real_escape_string($admin) . "', "
                . time() . ", '" . $this->bot->db->real_escape_string($reason) . "', " . $endtime . ", 0, -1, " . time(
                ) . ") ";
            $sql .= " ON DUPLICATE KEY UPDATE banned_by = VALUES(banned_by), banned_at = VALUES(banned_at), user_level = VALUES(user_level), updated_at = VALUES(updated_at), banned_for = VALUES(banned_for), banned_until = VALUES(banned_until)";
        }
        $this->bot->db->query($sql);
        $this->bot->core("player_notes")->add($target, $admin, $reason, 1);
        $this->bot->core("notify")->del($target);
        $this->bot->log("SECURITY", "BAN", $caller . " Banned " . $target . " from " . $this->bot->botname . ".");
        return "Banned " . $target . " from " . $this->bot->botname . ".";
    } // End function set_ban()

    /*
    Removes a ban.
    */
    function rem_ban($admin, $target, $caller = "Internal Process")
    { // Start function rem_ban()
		$admin = $this->bot->core('tools')->sanitize_player($admin);
        $target = $this->bot->core('tools')->sanitize_player($target);
        if (!isset($this->cache['banned'][$target])) {
            $this->error->set($target . " is not banned.");
            return $this->error;
        } else {
            $this->cache_mgr("rem", "banned", $target);
            $sql = "UPDATE #___users SET user_level = 0 WHERE nickname = '" . $target . "'";
            $this->bot->db->query($sql);
            $return = "Unbanned " . $target . " from " . $this->bot->botname . ". " . $target . " is now anonymous.";
            $this->bot->log("SECURITY", "BAN", $caller . " " . $return);
            return $return;
        }
    } // End function rem_ban()

    /*
    Returns the group id for $groupname.
    Returns -1 if the group doesn't exisit.
    */
    function get_gid($groupname)
    { // Start function get_gid()
        if (isset($this->cache['groups'][$groupname]['gid'])) {
            return $this->cache['groups'][$groupname]['gid'];
        } else {
            return -1;
        }
    } // End function get_gid()

    // Shows the security commands.
    function show_security_menu($source)
    { // Start function show_security_menu
        $inside = "Security System Main Menu\n\n";
        $inside .= "[" . $this->bot->core("tools")
                ->chatcmd("security groups", "Security Groups") . "]\n";
        $inside .= "[" . $this->bot->core("tools")
                ->chatcmd("security levels", "Security Levels") . "]\n";
        $inside .= "[" . $this->bot->core("tools")
                ->chatcmd("settings security", "Security Settings") . "]\n";
        $inside .= "[" . $this->bot->core("tools")
                ->chatcmd("security whoami", "Your Security Level and Group Membership") . "]\n";
        $inside .= "\n";
        $inside .= "To see someone elses Security Level and Group Membership type\n /tell <botname> <pre>security whois &lt;playername&gt;\n";
        return $this->bot->core("tools")->make_blob("Security System", $inside);
    } // End function show_security_menu

    /*
    Shows the groups, their ID numbers, and access levels.
    If called by a superadmin, allows changing access levels.
    */
    function show_security_levels($msgtype)
    { // Start function show_security_levels()
        $sql = "SELECT gid,name,description,access_level FROM #___security_groups ";
        $sql .= "WHERE name != 'superadmin' AND name != 'admin' AND name != 'leader' ";
        $sql .= "ORDER BY access_level DESC, name ASC";
        $result = $this->bot->db->select($sql, MYSQLI_ASSOC);
		$superadmin = ""; $admin = ""; $leader = ""; $member = ""; $guest = ""; $anonymous = "";
        if (!empty($result)) {
            foreach ($result as $group) {
                if ($group['access_level'] == SUPERADMIN && $group['name'] <> "superadmin") {
                    $superadmin .= "+ Security Group " . $group['name'] . " (" . stripslashes(
                            $group['description']
                        ) . "):\n    Change " . $group['name'] . " Access Level To: "
                        . $this->change_links(SUPERADMIN, $group['gid'], $msgtype) . "\n";
                }
                if ($group['access_level'] == ADMIN && $group['name'] <> "admin") {
                    $admin .= "+ Security Group " . $group['name'] . " (" . stripslashes(
                            $group['description']
                        ) . "):\n    Change " . $group['name'] . " Access Level To: "
                        . $this->change_links(ADMIN, $group['gid'], $msgtype) . "\n";
                }
                if ($group['access_level'] == LEADER && $group['name'] <> "leader") {
                    $leader .= "+ Security Group " . $group['name'] . " (" . stripslashes(
                            $group['description']
                        ) . "):\n    Change " . $group['name'] . " Access Level To: "
                        . $this->change_links(LEADER, $group['gid'], $msgtype) . "\n";
                }
                if ($group['access_level'] == MEMBER && $group['name'] <> "member") {
                    $member .= "+ Security Group " . $group['name'] . " (" . stripslashes(
                            $group['description']
                        ) . "):\n    Change " . $group['name'] . " Access Level To: "
                        . $this->change_links(MEMBER, $group['gid'], $msgtype) . "\n";
                }
                if ($group['access_level'] == GUEST && $group['name'] <> "guest") {
                    $guest .= "+ Security Group " . $group['name'] . " (" . stripslashes(
                            $group['description']
                        ) . "):\n    Change " . $group['name'] . " Access Level To: "
                        . $this->change_links(GUEST, $group['gid'], $msgtype) . "\n";
                }
                if ($group['access_level'] == ANONYMOUS && $group['name'] <> "anonymous") {
                    $anonymous .= "+ Security Group " . $group['name'] . " (" . stripslashes(
                            $group['description']
                        ) . "):\n    Change " . $group['name'] . " Access Level To: "
                        . $this->change_links(ANONYMOUS, $group['gid'], $msgtype) . "\n";
                }
            }
        }
        unset($result);
        if ($this->bot->guildbot) {
            $sql = "SELECT org_rank, org_rank_id, access_level FROM #___security_org ";
            $sql .= "WHERE org_gov = '" . $this->bot->core("settings")
                    ->get('Security', 'Orggov') . "' ";
            $sql .= "ORDER BY org_rank_id ASC, access_level DESC";
            $result = $this->bot->db->select($sql, MYSQLI_ASSOC);
            if (!empty($result)) // This really should never be empty as this module automaticaly inserts data here.
            {
                foreach ($result as $rank) {
                    if ($rank['access_level'] == SUPERADMIN) {
                        $superadmin
                            .= "+ Org Rank " . $rank['org_rank'] . ":\n    Change " . $rank['org_rank'] . " Access Level To: " . $this->change_links(
                                SUPERADMIN,
                                $rank,
                                $msgtype
                            )
                            . "\n";
                    }
                    if ($rank['access_level'] == ADMIN) {
                        $admin
                            .= "+ Org Rank " . $rank['org_rank'] . ":\n    Change " . $rank['org_rank'] . " Access Level To: " . $this->change_links(
                                ADMIN,
                                $rank,
                                $msgtype
                            ) . "\n";
                    }
                    if ($rank['access_level'] == LEADER) {
                        $leader
                            .=
                            "+ Org Rank " . $rank['org_rank'] . ":\n    Change " . $rank['org_rank'] . " Access Level To: " . $this->change_links(
                                LEADER,
                                $rank,
                                $msgtype
                            ) . "\n";
                    }
                    if ($rank['access_level'] == MEMBER) {
                        $member
                            .=
                            "+ Org Rank " . $rank['org_rank'] . ":\n    Change " . $rank['org_rank'] . " Access Level To: " . $this->change_links(
                                MEMBER,
                                $rank,
                                $msgtype
                            ) . "\n";
                    }
                    if ($rank['access_level'] == GUEST) {
                        $guest
                            .= "+ Org Rank " . $rank['org_rank'] . ":\n    Change " . $rank['org_rank'] . " Access Level To: " . $this->change_links(
                                GUEST,
                                $rank,
                                $msgtype
                            ) . "\n";
                    }
                    if ($rank['access_level'] == ANONYMOUS) {
                        $anonymous
                            .= "+ Org Rank " . $rank['org_rank'] . ":\n    Change " . $rank['org_rank'] . " Access Level To: " . $this->change_links(
                                ANONYMOUS,
                                $rank,
                                $msgtype
                            )
                            . "\n";
                    }
                }
            }
        }
        if ($this->bot->guildbot) {
            $blurb = "Org Ranks and Security Groups with ";
        } else {
            $blurb = "Security Groups with ";
        }
        $superadmin = $blurb . "Access Level SUPERADMIN\n" . $superadmin;
        $admin = $blurb . "Access Level ADMIN\n" . $admin;
        $leader = $blurb . "Access Level LEADER\n" . $leader;
        $member = $blurb . "Access Level MEMBER\n" . $member;
        $guest = $blurb . "Access Level GUEST\n" . $guest;
        $anonymous = $blurb . "Access Level ANONYMOUS\n" . $anonymous;
        $return = "Security: Access Levels\n\n";
        $return .= "[" . $this->bot->core("tools")
                ->chatcmd("security", "Security: Main Menu") . "] ";
        $return .= "[" . $this->bot->core("tools")
                ->chatcmd("security groups", "Security: Security Groups") . "] ";
        $return .= "\n\n";
        $return .= $superadmin . "\n";
        $return .= $admin . "\n";
        $return .= $leader . "\n";
        $return .= $member . "\n";
        $return .= $guest . "\n";
        $return .= $anonymous . "\n\n";
        $return .= "[" . $this->bot->core("tools")
                ->chatcmd("security", "Security: Main Menu") . "] ";
        $return .= "[" . $this->bot->core("tools")
                ->chatcmd("security groups", "Security: Security Groups") . "] ";
        return $this->bot->core("tools")
            ->make_blob("Security Access Levels", $return);
    } // End function show_security_levels()

    // Displays security groups, the group's access level, and the members of the group.
    function show_groups()
    { // Start function show_groups()
        $sql = "SELECT gid, name, description, access_level FROM #___security_groups ";
        $sql .= "ORDER BY access_level DESC, gid ASC, name";
        $result = $this->bot->db->select($sql, MYSQLI_ASSOC);
        $superadmins = "Access Level SUPERADMIN:\n";
        $admins = "Access Level ADMIN:\n";
        $leaders = "Access Level LEADER:\n";
        $members = "Access Level MEMBER:\n";
        $guests = "Access Level GUEST:\n";
        $anon = "Access Level ANONYMOUS:\n";
        foreach ($result as $group) {
            if ($group['access_level'] == SUPERADMIN) {
                $superadmins .= " + " . $group['name'] . " (" . stripslashes($group['description']) . ") ";
                $superadmins .= "\n";
                $superadmins .= $this->make_group_member_list($group['gid']);
                $superadmins .= "\n";
            } elseif ($group['access_level'] == ADMIN) {
                $admins .= " + " . $group['name'] . " (" . stripslashes($group['description']) . ") ";
                $admins .= "\n";
                $admins .= $this->make_group_member_list($group['gid']);
                $admins .= "\n";
            } elseif ($group['access_level'] == LEADER) {
                $leaders .= " + " . $group['name'] . " (" . stripslashes($group['description']) . ") ";
                $leaders .= "\n";
                $leaders .= $this->make_group_member_list($group['gid']);
                $leaders .= "\n";
            } elseif ($group['access_level'] == MEMBER) {
                $members .= " + " . $group['name'] . " (" . stripslashes($group['description']) . ") ";
                $members .= "\n";
                $members .= $this->make_group_member_list($group['gid']);
                $members .= "\n";
            } elseif ($group['access_level'] == GUEST) {
                $guests .= " + " . $group['name'] . " (" . stripslashes($group['description']) . ") ";
                $guests .= "\n";
                $guests .= $this->make_group_member_list($group['gid']);
                $guests .= "\n";
            } elseif ($group['access_level'] == ANONYMOUS) {
                $anon .= " + " . $group['name'] . " (" . stripslashes($group['description']) . ") ";
                $anon .= "\n";
                $anon .= $this->make_group_member_list($group['gid']);
                $anon .= "\n";
            }
        }
        $return = "Security: Groups\n\n";
        $return .= "[" . $this->bot->core("tools")
                ->chatcmd("security", "Security: Main Menu") . "] ";
        $return .= "[" . $this->bot->core("tools")
                ->chatcmd("security levels", "Security: Access Levels") . "] ";
        $return .= "\n\n";
        $return .= $superadmins . "\n";
        $return .= $admins . "\n";
        $return .= $leaders . "\n";
        $return .= $members . "\n";
        $return .= $guests . "\n";
        $return .= $anon . "\n";
        $return .= "\n";
        $return .= "[" . $this->bot->core("tools")
                ->chatcmd("security", "Security: Main Menu") . "] ";
        $return .= "[" . $this->bot->core("tools")
                ->chatcmd("security levels", "Security: Access Levels") . "] ";
        return $this->bot->core("tools")->make_blob("Security Groups", $return);
    } // End function show_groups()

    // Makes the list of group members for show_groups()
    function make_group_member_list($gid)
    { // Start function make_group_member_list()
        $tmp = "";
        if (empty($this->cache['groups'][$gid]['members'])) {
            return "        - No members.";
        }
        $users = $this->cache['groups'][$gid]['members'];
        sort($users);
        foreach ($users as $member) {
            $tmp .= "        - " . $member . "\n";
        }
        return rtrim($tmp);
    } // End function make_group_member_list()

    // Displays all admins.
    function show_admins($source)
    { // Start function show_admins()
    } // End function show_admins()

    /*
    Creates the proper change links for a group, called by show_groups.
    $levelid = numeric access level
    $groupid = group id number or name/shortname
    $source = source of command (pgmsg, gc, tell)
    */
    function change_links($levelid, $groupid, $msgtype)
    { // Start function change_links
		$return = "";
        if (!is_numeric($levelid)) {
            return null;
        }
        if (is_array($groupid)) {
            $vars = $this->bot->core("settings")
                    ->get('Security', 'Orggov') . " " . $groupid['org_rank'];
        } else {
            $vars = $groupid;
        }
        $chatcmd = "security changelevel " . $vars . " ";
        if ($levelid <> SUPERADMIN) {
            $return .= "[" . $this->bot->core("tools")
                    ->chatcmd($chatcmd . SUPERADMIN, "SUPERADMIN", $msgtype) . "] ";
        }

        if ($levelid <> ADMIN) {
            $return .= "[" . $this->bot->core("tools")
                    ->chatcmd($chatcmd . ADMIN, "ADMIN", $msgtype) . "] ";
        }

        if ($levelid <> LEADER) {
            $return .= "[" . $this->bot->core("tools")
                    ->chatcmd($chatcmd . LEADER, "LEADER", $msgtype) . "] ";
        }

        if ($levelid <> MEMBER) {
            $return .= "[" . $this->bot->core("tools")
                    ->chatcmd($chatcmd . MEMBER, "MEMBER", $msgtype) . "] ";
        }

        if ($levelid <> GUEST) {
            $return .= "[" . $this->bot->core("tools")
                    ->chatcmd($chatcmd . GUEST, "GUEST", $msgtype) . "] ";
        }

        if ($levelid <> ANONYMOUS) {
            $return .= "[" . $this->bot->core("tools")
                    ->chatcmd($chatcmd . ANONYMOUS, "ANONYMOUS", $msgtype) . "] ";
        }
        return $return;
    } // End function change_links

    /*
    Changes the access level of a security group or org rank.
    */
    function change_level($groupid, $newacl, ?string $government = null)
    { // Start function change_level()
        if (!is_numeric($newacl)) {
            return "Access Levels should be an integer.";
        }
        if ($newacl > SUPERADMIN || $newacl < ANONYMOUS) {
            return "Error: Access level should be between " . ANONYMOUS . " and " . SUPERADMIN . ".";
        }
        if (is_numeric($groupid) && is_null($government)) {
            $orgrank = false;
            $sql = "UPDATE #___security_groups SET access_level = " . $newacl . " WHERE gid = " . $groupid;
            $return = "Group ID " . $groupid . " changed to access level " . $this->get_access_name($newacl);
        } elseif (strtolower($government) == strtolower(
                $this->bot
                    ->core("settings")->get('Security', 'Orggov')
            )
        ) {
            $orgrank = true;
            $sql = "UPDATE #___security_org SET access_level = " . $newacl . " WHERE org_gov = '" . $this->bot->db->real_escape_string(
                    $government
                ) . "' AND org_rank = '"
                . $this->bot->db->real_escape_string($groupid) . "'";
            $return = "Org Rank " . $groupid . " changed to access level " . $this->get_access_name($newacl);
        } else {
            $sql = false;
            return "Invalid input for changelevel command.";
        }

        if ($this->bot->db->returnQuery($sql)) // Success
        {
            if ($orgrank) {
                $this->cache_mgr("add", "orgranks", $groupid, $newacl);
            } else {
                $tmp = array(
                    'gid' => $groupid,
                    'name' => $this->cache['groups'][$groupid]['name']
                );
                $this->cache_mgr("add", "groups", $tmp, $newacl);
            }
            $this->bot->log(
                "SECURITY",
                "UPDATE",
                "Access level for $groupid changed to " . $this->get_access_name($newacl) . "."
            );

            // Clear the flexible security cache if it exists:
            if ($this->bot->core("flexible_security") != null) {
                $this->bot->core("flexible_security")->clear_cache();
            }

            return $return;
        } else {
            $this->bot->log("SECURITY", "ERROR", "MySQL Error: " . $sql);
            return "Error updating database. Check logfile for details.";
        }
    } // End function change_level()


    /*
    Return an array contining the group ids $name is a member of.
    */
    function get_groups($name)
    { // Start function get_groups()
        $name = $this->bot->core('tools')->sanitize_player($name);
        $tmp = array();
        if (!isset($this->cache['membership'][$name]) || empty($this->cache['membership'][$name])) {
            return -1;
        }
        foreach ($this->cache['membership'][$name] AS $gid) {
            $tmp[] = $gid;
        }
        sort($tmp);
        return $tmp;
    } // End function get_groups()

    /*
    Get group name from a gid
    */
    function get_group_info($gid)
    { // Start function get_group_name()
        $sql = "SELECT * FROM #___security_groups WHERE gid = " . $gid;
        $result = $this->bot->db->select($sql, MYSQLI_ASSOC);
        if (empty($result)) {
            return false;
        } else {
            return $result[0];
        } // Should return an array with elements named id, name, description, and access_level
    } // End function get_group_name()

    /*
    Returns the access level of the passed player
    If the player is in mutiple groups, the highest
    access level will be returned.
    This function only checks the specified character,
    no alts.
    */
    function get_access_level_player($player)
    { // Start function get_access_level()
        $player = $this->bot->core('tools')->sanitize_player($player);
        $uid = $this->bot->core("player")->id($player);
        // If user does not exist return ANONYMOUS access right away
        if (!$uid) {
            return 0;
        }

        $dbuid = $this->bot->core("user")->get_db_uid($player);
        if ($uid && $dbuid && ($uid != $dbuid)) {
            // Danger rodger wilco. We have a rerolled player which have not yet been deleted from users table.
            //$this -> bot -> core("user") -> erase("Security", $player, FALSE, $uid);
            //echo "Debug1: $uid does not match $dbuid \n";
            return 0;
        }

        $player = ucfirst(strtolower($player));
        // Check #1: Check Owner and SuperAdmin from Bot.conf.
        if ($player == $this->owner) {
            return 256;
        }
        if (isset($this->super_admin[$player])) {
            return 255;
        }
        // Check to see if the user is banned.
        if (isset($this->cache['banned'][$player])) {
            return -1;
        }
        // Check user's table status. users_table: anonymous (0), guest (1), member (2)
        $highestlevel = 0;
        if (isset($this->cache['guests'][$player])) {
            $highestlevel = 1;
        }
        if ($this->bot->core("settings")
                ->get("Security", "GuestInChannel")
            && $this->bot
                ->core("online")->in_chat($player)
        ) {
            $highestlevel = 1;
            $this->cache['online'][$player] = true;
        }
        if (isset($this->cache['members'][$player])) {
            $highestlevel = 2;
        }

        // Check Org Rank Access.
        if ($this->bot->guildbot && isset($this->cache['members'][$player])) {
            $highestlevel = $this->org_rank_access($player, $highestlevel);
        }
        // Check default and custom groups.
        $highestlevel = $this->group_access($player, $highestlevel);

        // Check if the flexible security module is enabled, if yes check there:
        if ($this->bot->core("flexible_security") != null) {
            $highestlevel = $this->bot->core("flexible_security")
                ->flexible_group_access($player, $highestlevel);
        }

        // !leader handling
        if ($this->bot->core("settings")
                ->exists("Leader", "Name")
            && $highestlevel < LEADER
        ) {
            if ($this->bot->core("settings")->get("Leader", "Leaderaccess")
                && strtolower($player) == strtolower(
                    $this->bot
                        ->core("settings")->get("Leader", "Name")
                )
            ) {
                $highestlevel = LEADER;
            }
        }

        // All checks done, return the result.
        return $highestlevel;
    } // End function get_access_level()

    /*
    Returns the access level of the passed player
    If the player is in mutiple groups, the highest
    access level will be returned.
    This function checks all alts for the highest
    access level of any registered alt.
    If one alt is banned the user as a total will
    be considered banned!
    */
    function get_access_level($player)
    {
		$player = $this->bot->core('tools')->sanitize_player($player);
        // If setting UseAlts got changed since last round whipe mains cache:
        if ($this->last_alts_status != $this->bot->core("settings")
                ->get("Security", "UseAlts")
        ) {
            unset($this->cache['mains']);
            $this->cache['mains'] = array();
            $this->last_alts_status = $this->bot->core("settings")
                ->get("Security", "UseAlts");
        }

        $player = ucfirst(strtolower($player));

        // Check if leader exists and is set, make sure to unset outdated cache entries on leader changes:
        if ($this->bot->core("settings")->exists("Leader", "Name")) {
            if ($this->bot->core("settings")->get("Leader", "Leaderaccess")
                && strtolower($this->last_leader) != strtolower(
                    $this->bot
                        ->core("settings")->get("Leader", "Name")
                )
            ) {
                if (!$this->bot->core("settings")->get("Security", "Usealts")) {
                    $leadername = $this->last_leader;
                } else {
                    $leadername = $this->bot->core("alts")
                        ->main($this->last_leader);
                }
                unset($this->cache['mains'][$leadername]);
                $this->last_leader = $this->bot->core("settings")
                    ->get("Leader", "Name");
                if (!$this->bot->core("settings")->get("Security", "Usealts")) {
                    $leadername = $this->last_leader;
                } else {
                    $leadername = $this->bot->core("alts")
                        ->main($this->last_leader);
                }
                unset($this->cache['mains'][$leadername]);
            }
        }

        // If characters in private chatgroup are counted as guests make sure that status is deleted
        // when they leave chat again:
        if ($this->bot->core("settings")->get("Security", "GuestInChannel")
            && !$this->bot->core("online")->in_chat($player)
            && (isset($this->cache['online'][$player]))
        ) {
            // Unset online cache:
            unset($this->cache['online'][$player]);
            // Get mains cache entry to check (depends on UseAlts):
            if (!$this->bot->core("settings")->get("Security", "Usealts")) {
                $onlinename = $player;
            } else {
                $onlinename = $this->bot->core("alts")->main($player);
            }
            // Check if there is a cache entry for $onlinename, and highest level is GUEST.
            // If both are true unset that cached entry:
            if (isset($this->cache['mains'][$onlinename])
                && $this->cache['mains'][$onlinename] == GUEST
            ) {
                unset($this->cache['mains'][$onlinename]);
            }
        }

        $uid = $this->bot->core("player")->id($player);
        // If user does not exist return ANONYMOUS access right away
        if (!$uid) {
            return 0;
        }

        $dbuid = $this->bot->core("user")->get_db_uid($player);
        if ($uid && $dbuid && ($uid != $dbuid)) {
            // Danger rodger wilco. We have a rerolled player which have not yet been deleted from users table.
            //$this -> bot -> core("user") -> erase("Security", $player, FALSE, $uid);
            //echo "Debug: $uid does not match $dbuid \n";
            return 0;
        }

        // If alts should not be queried just return the access level for $player
        if (!$this->bot->core("settings")->get("Security", "Usealts")) {
            // If we got a cached entry, return that:
            if (isset($this->cache['mains'][$player])) {
                return $this->cache['mains'][$player];
            }
            // Otherwise get highest access level, cache it, and then return it.
            $highest = $this->get_access_level_player($player);
            $this->cache['mains'][$player] = $highest;
            return $highest;
        }

        // Check mains cache
        if (isset($this->cache['mains'][$this->bot->core("alts")
            ->main($player)])
        ) {
            return $this->cache['mains'][$this->bot->core("alts")
                ->main($player)];
        }

        // Get main and alts
        $main = $this->bot->core("alts")->main($player);
        $alts = $this->bot->core("alts")->get_alts($main);

        // Check main and alts for owner or config file defined superadmins
        $foundSA = false;
        if ($main == $this->owner) {
            $this->cache['mains'][$main] = 256;
            return 256;
        }
        if (isset($this->super_admin[$main])) {
            $this->cache['mains'][$main] = 255;
            $foundSA = true;
        }
        if (!empty($alts)) {
            foreach ($alts as $alt) {
                if ($alt == $this->owner) {
                    $this->cache['mains'][$main] = 256;
                    return 256;
                }
                if (isset($this->super_admin[$main])) {
                    $this->cache['mains'][$main] = 255;
                    $foundSA = true;
                }
            }
        }
        if ($foundSA) {
            return 255;
        }

        // Get access rights of main
        $access = $this->get_access_level_player($main);

        // if main is banned user is considered banned
        if ($access == -1) {
            $this->cache['mains'][$main] = -1;
            return -1;
        }

        // if user got alts check all their access levels
        // if nobody is banned return highest access level
        // over all alts.
        // if banned return banned.
        if (!empty($alts)) {
            foreach ($alts as $alt) {
                $newaccess = $this->get_access_level_player($alt);
                if ($newaccess == -1) {
                    $this->cache['mains'][$main] = -1;
                    return -1;
                }
                if ($newaccess > $access) {
                    $access = $newaccess;
                }
            }
        }

        $this->cache['mains'][$main] = $access;
        return $access;
    }


    /*
    Figures out the access level based on org rank.
    */
    function org_rank_access($player, $highest)
    { // Start function org_rank_access()
		$player = $this->bot->core('tools')->sanitize_player($player);
        $who = $this->bot->core("whois")
            ->lookup($player, true); // Do whois with no XML lookup, guild members should be cached...
        if ($who instanceof BotError) {
            return $highest;
        }
        if ($who['org'] <> $this->bot->guildname) {
            return $highest;
        }
        if ($this->cache['orgranks'][$who["rank"]] > $highest) {
            return $this->cache['orgranks'][$who["rank"]];
        } else {
            return $highest;
        }
    } // End function org_rank_access()

    function org_rank_id($player, $highest)
    { // Start function org_rank_access()
		$player = $this->bot->core('tools')->sanitize_player($player);
        $who = $this->bot->core("whois")
            ->lookup($player, true); // Do whois with no XML lookup, guild members should be cached...
        if ($who instanceof BotError) {
            return $highest;
        }
        if ($who['org'] <> $this->bot->guildname) {
            return $highest;
        }
        if ($this->cache['orgrank_ids'][$who["rank"]] < $highest) {
            return $this->cache['orgrank_ids'][$who["rank"]];
        } else {
            return $highest;
        }
    } // End function org_rank_access()

    /*
    Figure out the access level based on group membership
    Should only be called by get_access_level()
    */
    function group_access($player, $highest)
    { // Start function group_access()
		$player = $this->bot->core('tools')->sanitize_player($player);
        $groups = $this->get_groups($player);
        if ($groups == -1) {
            return $highest; // $player is not a member of any groups.
        }
        foreach ($groups as $gid) {
            if ($this->cache['groups'][$gid]['access_level'] > $highest) {
                $highest = $this->cache['groups'][$gid]['access_level'];
            }
        }
        return $highest;
    } // End function group_access()

    /*
    Checks $name's access agnist $level.
    Returns TRUE if they meet (or exceede) the specified level, otherwise false.
    Replacment for is_admin(), check_security().

    Reminder: If you do check_access($name, "banned"), this function will return TRUE if they are NOT banned!
    IF the user is banned, this function will return FALSE.
    */
    function check_access($name, $level)
    { // Start function check_access()
        if (!$this->enabled) {
            return false;
        } // No access is granted until the secuirty subsystems are ready.
        $name = $this->bot->core('tools')->sanitize_player($name);
        $level = strtoupper($level);
        if ($level == "RAIDLEADER") {
            $this->bot->log("SECURITY", "WARNING", "Deprecated level raidleader passed to check_access().");
            $level = "LEADER";
        }
        $access = $this->get_access_level($name);
        if (is_numeric($level)) // Just check numbers.
        {
            if ($access >= $level) {
                return true;
            } else {
                return false;
            }
        } else {
            switch ($level) { // Start switch
                case "ANONYMOUS":
                    if ($access >= ANONYMOUS) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case "GUEST":
                    if ($access >= GUEST) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case "MEMBER":
                    if ($access >= MEMBER) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case "LEADER":
                    if ($access >= LEADER) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case "ADMIN":
                    if ($access >= ADMIN) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case "SUPERADMIN":
                    if ($access >= SUPERADMIN) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case "OWNER":
                    if ($access >= OWNER) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case "BANNED":
                    $this->bot->log(
                        "SECURITY",
                        "WARNING",
                        "Consider using the is_banned(\$name) function instead of check_access(\$name, \$level) for ban checking."
                    );
                    if ($access > BANNED) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                default:
                    return false; // Unknown Access Level.
                    break;
            } // End switch
        }
    } // End function check_access()

    // Returns true if the user is banned, otherwise false
    function is_banned($name)
    { // Start function is_banned()
		$name = $this->bot->core('tools')->sanitize_player($name);
        if ($this->enabled) {
            return (isset($this->cache['banned'][ucfirst(strtolower($name))]));
        } else {
            return false;
        }
    } // End function is_banned()

    /*
    Sets the org governing form, resets org rank access if the governing form changes.
    */
    function set_government()
    { // Start set_government()
        if (!$this->bot->guildbot) {
            return false;
        } // Raidbot.
        //$guild = $this -> bot -> db -> select("SELECT org_name FROM #___whois WHERE nickname = '".$this -> bot -> botname."'");
        $whois = $this->bot->core("whois")->lookup($this->bot->botname);
        if ($whois && !($whois instanceof BotError)) {
            $guild = $whois['org'];
        }
        //if(!empty($guild) && $guild[0][0] != "")
        if (empty($guild) || $guild != "") {
            $guild = $this->bot->guildname;
        }
        $sql = "SELECT DISTINCT org_rank_id,org_rank FROM #___whois WHERE org_name = '" . $this->bot->db->real_escape_string(
                $guild
            ) . "' ORDER BY org_rank_id ASC"; // Gets the org ranks.
        $result = $this->bot->db->select($sql);
        if (empty($result)) {
            //$this -> bot -> core("settings") -> save("Security", "orggov", "Unknown");
            return false; // Org roster hans't updated yet. FIXME: Need to try again later.
        }

        if ($result[0][1] == "Director") // Faction
        {
            $orggov = "Faction";
        } elseif ($result[0][1] == "Monarch") // Monarchy
        {
            $orggov = "Monarchy";
        } elseif ($result[0][1] == "Lord") // Feudalism
        {
            $orggov = "Feudalism";
        } elseif ($result[0][1] == "Anarchist") // Anarchism
        {
            $orggov = "Anarchism";
        } elseif ($result[0][1] == "President") // Republic or Department
        {
            if ($result[1][1] == "General") // Department
            {
                $orggov = "Department";
            } elseif ($result[1][1] == "Advisor") // Republic
            {
                $orggov = "Republic";
            } else // Unknown?!?
            {
                $orggov = "Unknown";
            }
        } else // Unknown?!?
        {
            $orggov = "Unknown";
        }
        if ($this->bot->core("settings")->get('Security', 'Orggov') <> $orggov
        ) // Change detected, reset access levels.
        {
            if ($orggov == "Unknown") {
                return $this->bot->core("settings")->get('Security', 'Orggov');
            } else {
                $this->bot->core("settings")
                    ->save("Security", "orggov", $orggov);
                $sql = "UPDATE #___security_org SET access_level = 2";
                $this->bot->db->query($sql);
                $this->cache_org_ranks();
            }
        }
        return $this->bot->core("settings")->get('Security', 'Orggov');
    } // End set_government()

    /*
    Admin.php Functions:

    To Do:
    function add_admin($source, $group, $name, $type) // 0.3
    function list_admin($source, $type) // 0.3
    function member_del($name, $group, $member) // 0.2
    function member_add($name, $group, $member) // 0.2
    function group_add($name, $group) // 0.2
    function group_del($name, $group) // 0.2
    function group_show() // 0.2

    Done:
    function in_group($name, $group) // 0.2
    - in_group() is a wrapper for check_access() in 0.4
    */

    // --------------------------------------------------
    // Functions to setup and manage the security cache.
    // --------------------------------------------------

    /*
    Adds and removes information from the cache.
    $action: add or rem
    $cache: Which cache to modify (groups, guests, members, banned, groupmem, orgranks, main, maincache)
    $info: The information to add (or remove)
    $more: Extra informaion needed for some actions.
    */
    function cache_mgr($action, $cache, $info, ?string $more = null)
    { // Start function cache_mgr()
        $action = strtolower($action);
        if ($action == "add") {
            $action = true;
        } else {
            $action = false;
        }
        $cache = strtolower($cache);
        switch ($cache) {
            case "guests":
                if ($action) {
                    $this->cache['guests'][$info] = $info;
                } else {
                    unset($this->cache['guests'][$info]);
                }
                unset($this->cache['mains'][$this->bot->core("alts")
                    ->main($info)]);
                break;
            case "members":
                if ($action) {
                    $this->cache['members'][$info] = $info;
                } else {
                    unset($this->cache['members'][$info]);
                }
                unset($this->cache['mains'][$this->bot->core("alts")
                    ->main($info)]);
                break;
            case "banned":
                if ($action) {
                    $this->cache['banned'][$info] = $info;
                } else {
                    unset($this->cache['banned'][$info]);
                }
                unset($this->cache['mains'][$this->bot->core("alts")
                    ->main($info)]);
                break;
            case "groups":
                if ($action) {
                    if (is_null($more)) // Adding a new group.
                    {
                        $this->cache['groups'][$info['gid']] = $info;
                        $this->cache['groups'][$info['name']] = $info;
                    } else // Updating a groups access level.
                    {
                        $this->cache['groups'][$info['gid']]['access_level'] = $more;
                        $this->cache['groups'][$info['name']]['access_level'] = $more;
                        foreach (
                            $this->cache['groups'][$info['gid']]['members']
                            as $member
                        ) {
                            unset($this->cache['mains'][$this->bot->core("alts")
                                ->main($member)]);
                        }
                    }
                } else {
                    $gid = $this->cache['groups'][$info]['gid'];
                    $gname = $info;
                    foreach ($this->cache['groups'][$gid]['members'] as $member) {
                        unset($this->cache['membership'][$member][$gid]);
                        unset($this->cache['mains'][$this->bot->core("alts")
                            ->main($member)]);
                    }
                    unset ($this->cache['groups'][$gid]);
                    unset ($this->cache['groups'][$gname]);
                }
                break;
            case "groupmem":
                $group = strtolower($info);
                $member = ucfirst(strtolower($more));
                $gid = $this->get_gid($group);
                if ($action) {
                    $this->cache['groups'][$group]['members'][$member] = $member;
                    $this->cache['groups'][$gid]['members'][$member] = $member;
                    $this->cache['membership'][$member][$gid] = $gid;
                } else {
                    unset($this->cache['membership'][$member][$gid]);
                    unset($this->cache['groups'][$group]['members'][$member]);
                    unset($this->cache['groups'][$gid]['members'][$member]);
                }
                unset($this->cache['mains'][$this->bot->core("alts")
                    ->main($member)]);
                break;
            case "orgranks":
                if ($action) {
                    $this->cache['orgranks'][$info] = $more;
                } else {
                    unset($this->cache['orgranks'][$info]);
                }
                unset($this->cache['mains']);
                $this->cache['mains'] = array();
                break;
            case "main":
                unset($this->cache['mains'][$this->bot->core("alts")
                    ->main($info)]);
                break;
            case "maincache":
                unset($this->cache['mains']);
                $this->cache['mains'] = array();
                break;
        }
    } // End function cache_mgr()

    /*
    Loads security information into the cache.
    */
    function cache_security()
    { // Start function cache_security()
        $this->cache_users(); // Cache users security.
        $this->cache_groups(); // Admin groups and members.
        if ($this->bot->guildbot) {
            $this->cache_org_ranks(); // Cache org rank security if this is a guildbot.
        }
    } // End function cache_security()

    // Adds information from the users table to the security cache.
    function cache_users()
    { // Start function cache_users()
        $this->cache['members'] = array();
        $this->cache['guests'] = array();
        $this->cache['banned'] = array();
        $this->cache['mains'] = array();
        $this->cache['online'] = array();
        $sql = "SELECT nickname,user_level FROM #___users WHERE user_level != 0";
        $result = $this->bot->db->select($sql);
        if (empty($result)) {
            return false;
        } // No users...huh. ;-)
        foreach ($result as $user) {
            if ($user[1] == 2) {
                $this->cache['members'][$user[0]] = $user[0];
            } elseif ($user[1] == 1) {
                $this->cache['guests'][$user[0]] = $user[0];
            } /*** FIXME ***/
            // This is to keep 0.3.x BeBot admins from being treated as banned outright.
            elseif ($user[1] == 3) {
                $this->cache['members'][$user[0]] = $user[0];
            } else {
                $this->cache['banned'][$user[0]] = $user[0];
            }
        }
        unset($result);
    } // End function cache_users()

    // Adds the org_rank access levels to the cache.
    function cache_org_ranks()
    { // Start function cache_org_ranks()
        $this->cache['orgranks'] = array();
        if (!($this->bot->core("settings")->exists('Security', 'Orggov'))) {
            $this->set_government(); // Won't work until the org governing form is identified.
        }
        if ($this->bot->core("settings")
                ->get('Security', 'Orggov') instanceof BotError
        ) {
            return false; // If the setting is still missing, we can't do anything about it.
        }
        if ($this->bot->core("settings")
                ->get('Security', 'Orggov') == "Unknown"
        ) {
            $this->set_government(); // Won't work until the org governing form is identified.
        }
        if ($this->bot->core("settings")
                ->get('Security', 'Orggov') == "Unknown"
        ) {
            return false; // Tried to ID the org government and failed. Try again in 12 hours.
        }
        $sql = "SELECT org_rank, access_level, org_rank_id FROM #___security_org ";
        $sql .= "WHERE org_gov = '" . $this->bot->core("settings")
                ->get('Security', 'Orggov') . "' ";
        $sql .= "ORDER BY org_rank_id ASC";
        $result = $this->bot->db->select($sql, MYSQLI_ASSOC);
        if (empty($result)) {
            return false;
        } // Nothing to cache.
        // Now cache them...
        foreach ($result as $orgrank) {
            $this->cache['orgranks'][$orgrank['org_rank']] = $orgrank['access_level'];
            $this->cache['orgrank_ids'][$orgrank['org_rank']] = $orgrank['org_rank_id'];
        }
        return true;
    } // End function cache_org_ranks()

    // Adds the groups and their members to the cache.
    function cache_groups()
    { // Start function cache_groups()
        $this->cache['groups'] = array();
        $this->cache['membership'] = array();
        $sql = "SELECT * FROM #___security_groups";
        $result = $this->bot->db->select($sql, MYSQLI_ASSOC);
        if (empty($result)) {
            $this->bot->log(
                "SECURITY",
                "ERROR",
                "No groups exisit, not even the groups created by default. Something is very wrong."
            );
            exit();
        }
        foreach ($result as $group) { //gid, name, description, access_level
            $this->cache['groups'][$group['gid']] = $group;
            $this->cache['groups'][$group['name']] = $group;
            $gid = $this->get_gid($group['name']);
            $sql = "SELECT name FROM #___security_members WHERE gid = " . $gid;
            $members = $this->bot->db->select($sql, MYSQLI_ASSOC);
            $this->cache['groups'][$group['gid']]['members'] = array();
            $this->cache['groups'][$group['name']]['members'] = array();
            // Cache members of the group, no big deal if there are no members.
            if (!empty($members)) {
                foreach ($members as $member) {
                    $this->cache['groups'][$group['gid']]['members'][$member['name']] = $member['name'];
                    $this->cache['groups'][$group['name']]['members'][$member['name']] = $member['name'];

                    if (!isset($this->cache['membership'][$member['name']])) {
                        $this->cache['membership'][$member['name']] = array();
                    }
                    $this->cache['membership'][$member['name']][$group['gid']] = $group['gid'];
                }
            }

        }
    } // End function cache_groups()

    /*
    Returns highest access level.
    */
    function whoami($name)
    { // Start function whoami
		$name = $this->bot->core('tools')->sanitize_player($name);
		$groupmsg = "";
        $groups = $this->bot->core("security")->get_groups($name);
        $access = $this->bot->core("security")->get_access_level($name);
        $access = $this->get_access_name($access);
        $message = "Your access level is " . strtoupper($access) . ".";
        if ($groups <> -1) {
            $groupmsg = " You are a member of the following security groups: ";
            foreach ($groups as $gid) {
                $groupmsg .= strtolower($this->cache['groups'][$gid]['name']) . ", ";
            }
            $groupmsg = rtrim($groupmsg);
            $groupmsg = rtrim($groupmsg, ",");
        }
        return $message . $groupmsg;
    } // End function whoami

    function whois($name)
    { // Start function whois()
        $player = $this->bot->core('tools')->sanitize_player($name);
        $groups = $this->bot->core("security")->get_groups($player);
        $access = $this->bot->core("security")->get_access_level($player);
        $access = $this->get_access_name($access);
        $message = $player . "'s highest access level is " . strtoupper($access) . ".";
		$groupmsg = "";
        if ($groups <> -1) {
            $groupmsg = " " . $player . " is a member of the following security groups: ";
            foreach ($groups as $gid) {
                $groupmsg .= strtolower($this->cache['groups'][$gid]['name']) . ", ";
            }
            $groupmsg = rtrim($groupmsg);
            $groupmsg = rtrim($groupmsg, ",");
        }
        return $message . $groupmsg;
    } // End function whois()

    function get_access_name($access)
    { // Start function get_access_name()
        switch ($access) { // Start switch
            case OWNER:
                $access = "OWNER";
                break;
            case SUPERADMIN:
                $access = "SUPERADMIN";
                break;
            case ADMIN:
                $access = "ADMIN";
                break;
            case LEADER:
                $access = "LEADER";
                break;
            case MEMBER:
                $access = "MEMBER";
                break;
            case GUEST:
                $access = "GUEST";
                break;
            case ANONYMOUS:
                $access = "ANONYMOUS";
                break;
            case BANNED:
                $access = "BANNED";
                break;
            default:
                $access = "UNKNOWN (" . STRTOUPPER($access) . ")";
                break;
        } // End switch
        return $access;
    } // End function get_access_name()

} // End of Class
?>
