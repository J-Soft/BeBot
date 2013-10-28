<?php
/*
* GUI to set the levels for the access control of the bot.
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
$flexiblesecurity_gui = new FlexibleSecurityGUI($bot);
/*
The Class itself...
*/
class FlexibleSecurityGUI extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command("all", "flexible", "SUPERADMIN");
        $this->register_command("all", "faction", "ADMIN");
        $this->register_command("all", "minlevel", "ADMIN");
        // The name of the group to use in the faction and minlevel commands to define guest access to the bot.
        // The group needs to be added and set to the wished access level (GUEST would be the best for the purpose).
        // Every level and faction conditions will always be replaced on some change done with minlevel or faction.
        $this->bot->core("settings")
            ->create(
                "Flexible_Security",
                "Guest_Group",
                "",
                "What's the name of the group that should be used as guest group in the flexible rules for the faction and minlevel commands?"
            );
        $this->help['description'] = 'Handles flexible security rules for the bot.';
        $this->help['command']['minlevel'] = "Shows the currently set minimal level for guest access.";
        $this->help['command']['minlevel <level>'] = "Sets the mininmal level for guest access to level.";
        $this->help['command']['faction'] = "Shows the current faction settings for guest access.";
        $this->help['command']['faction exclude <omni|clan|neutral|all>']
            = "Sets the faction setting for guest access to anyone not in the selected faction. Using exclude all will exclude all from meeting this requirement.";
        $this->help['command']['faction <omni|clan|neutral|all>'] = "Sets the faction setting for guest access to anyone meeting the selected faction requirement.";
        $this->help['command']['flexible'] = "Shows the GUI to create and delete flexible security groups";
        $this->help['command']['flexible condition add <groupname> <level|rank_id|at_id> <<|<=|>|>=|=|!=> <number>']
            = "Adds a new condition to the flexible security group <groupname> for level, rank_id or alien level (at_id), meeting the condition defined by the compare operator and the number to compare too.";
        $this->help['command']['flexible condition add <groupname> profession <=|!=> <prof>']
            = "Adds a new condition to the flexible security group <groupname> for profession requirement, with <prof> being either a shortcut or the full profession name.";
        $this->help['command']['flexible condition add <groupname> faction <=|!=> <omni|clan|neutral|all>']
            = "Adds a new condition to the flexible security group <groupname> for faction requirement, where ALL matches all factions.";
        $this->help['command']['flexible condition add <groupname> org <=|!=> <player>']
            = "Adds a new condition to the flexible security group <groupname> for organization requirements, the organization compared to is the org <player> is in.";
        $this->help['notes']
            = "For the <pre>minlevel and <pre>faction commands to work you have to config a flexible group for guest access and enter the name into the setting Guest_Group of the Flexible_Security module.";
    }


    function command_handler($name, $msg, $origin)
    {
        // Make sure < and > are there instead of the placeholder:
        $msg = str_replace("&lt;", "<", $msg);
        $msg = str_replace("&gt;", ">", $msg);
        if (preg_match("/^flexible$/i", $msg)) {
            return $this->show_groups();
        } elseif (preg_match("/^flexible create ([a-z_]+) (and|or)$/i", $msg, $info)) {
            return $this->create_group($info[1], $info[2]);
        } elseif (preg_match("/^flexible delete ([a-z_]+)$/i", $msg, $info)) {
            return $this->delete_group($info[1]);
        } elseif (preg_match(
            "/^flexible condition add ([a-z_]+) (level|rank_id|at_id) (<|<=|>|>=|=|!=) ([01-9]+)$/i",
            $msg,
            $info
        )
        ) {
            return $this->add_number_condition($info[1], $info[2], $info[3], $info[4]);
        } elseif (preg_match("/^flexible condition add ([a-z_]+) profession (=|!=) ([a-z]+)$/i", $msg, $info)) {
            return $this->add_prof_condition($info[1], $info[2], $info[3]);
        } elseif (preg_match(
            "/^flexible condition add ([a-z_]+) faction (=|!=) (omni|clan|neutral|all)$/i",
            $msg,
            $info
        )
        ) {
            return $this->add_faction_condition($info[1], $info[2], $info[3]);
        } elseif (preg_match("/^flexible condition add ([a-z_]+) org (=|!=) ([a-z][a-z01-9]+)$/i", $msg, $info)) {
            return $this->add_org_condition($info[1], $info[2], $info[3]);
        } elseif (preg_match(
            "/^flexible condition del ([a-z_]+) (level|rank_id|at_id|profession|faction|org_id) (<|<=|>|>=|=|!=) (.+)/i",
            $msg,
            $info
        )
        ) {
            return $this->del_condition($info[1], $info[2], $info[3], $info[4]);
        } elseif (preg_match("/^faction$/i", $msg)) {
            return $this->show_faction();
        } elseif (preg_match("/^faction exclude (all|omni|clan|neutral)$/i", $msg, $info)) {
            return $this->change_faction("!=", $info[1]);
        } elseif (preg_match("/^faction (all|omni|clan|neutral)$/i", $msg, $info)) {
            return $this->change_faction("=", $info[1]);
        } elseif (preg_match("/^minlevel$/i", $msg)) {
            return $this->show_minlevel();
        } elseif (preg_match("/^minlevel ([1-9][01-9]*)$/i", $msg, $info)) {
            return $this->change_minlevel($info[1]);
        }
        return false;
    }


    function show_groups()
    {
        $grps = $this->bot->db->select(
            "SELECT t2.gid, name, description, access_level, op FROM #___security_flexible"
            . " AS t1, #___security_groups AS t2 WHERE t1.field = 'join' AND t1.gid = t2.gid ORDER BY access_level DESC, name ASC"
        );
        $blob = '';
        if (!empty($grps)) {
            $blob .= " ##yellow## ::: ##end## ##ao_infoheader##Currently defined flexible security groups for " . $this->bot->botname;
            $blob .= "##end## ##yellow## ::: ##end##";
            foreach ($grps as $grp) {
                $blob .= "\n\n##ao_infotext##Group:##end## " . $grp[1] . " (" . $grp[2] . ") ";
                $blob .= $this->bot->core("tools")
                        ->chatcmd("flexible delete " . $grp[1], "[DELETE]") . "\n";
                $blob .= "##ao_infotext##Access level:##end## " . $this->bot
                        ->core("security")->get_access_name($grp[3]) . "\n";
                $blob .= "##ao_infotext##Conditions:##end## (";
                if ($grp[4] == '&&') {
                    $blob .= "AND";
                } else {
                    $blob .= "OR";
                }
                $blob .= "-combined)";
                $conds = $this->bot->db->select(
                    "SELECT field, op, compareto FROM #___security_flexible" . " WHERE field != 'join' AND gid = " . $grp[0] . " ORDER BY field ASC"
                );
                if (empty($conds)) {
                    $blob .= "\nNo conditions, nobody can be member of this group based on the conditions.";
                } else {
                    foreach ($conds as $cond) {
                        $blob .= "\n - " . $cond[0] . " " . htmlentities($cond[1]) . " " . $cond[2];
                        if ($cond[0] == 'org_id') {
                            $org_name = $this->bot->db->select(
                                "SELECT DISTINCT(org_name) FROM #___whois WHERE " . "org_id = " . $cond[2]
                            );
                            if (!empty($org_name)) {
                                $blob .= " (Organization: " . stripslashes($org_name[0][0]) . ")";
                            } else {
                                $blob .= " (Organization name not found)";
                            }
                        }
                        $blob .= " ";
                        $blob .= $this->bot->core("tools")
                            ->chatcmd(
                                "flexible condition del " . $grp[1] . " " . $cond[0] . " " . htmlentities(
                                    $cond[1]
                                ) . " " . $cond[2],
                                "[DELETE]"
                            );
                    }
                }
            }
            $blob .= "\n\n";
        }
        unset($grps);
        $grps = $this->bot->db->select(
            "SELECT name, description, access_level FROM #___security_groups WHERE gid NOT IN (" . "SELECT DISTINCT(gid) FROM #___security_flexible WHERE field = 'join') "
            . "ORDER BY access_level DESC, name ASC"
        );
        if (!empty($grps)) {
            $blob .= " ##yellow## ::: ##end## ##ao_infoheader##Non-extended security groups for " . $this->bot->botname;
            $blob .= "##end## ##yellow## ::: ##end##";
            foreach ($grps as $grp) {
                $blob .= "\n\n##ao_infotext##Group:##end## " . $grp[0] . " (" . $grp[1] . ")\n";
                $blob .= "##ao_infotext##Access level:##end## " . $this->bot
                        ->core("security")->get_access_name($grp[2]);
                $blob .= "\n##ao_infotext##Create flexible extension:##end## ";
                $blob .= $this->bot->core("tools")
                    ->chatcmd("flexible create " . $grp[0] . " AND", "[AND-combined]");
                $blob .= " " . $this->bot->core("tools")
                        ->chatcmd("flexible create " . $grp[0] . " OR", "[OR-combined]");
            }
        }
        return $this->bot->core("tools")
            ->make_blob("Flexible groups of " . $this->bot->botname, $blob);
    }


    function create_group($grpname, $combine)
    {
        $combine = strtolower($combine);
        $grpname = strtolower($grpname);
        $gid = $this->bot->core("security")->get_gid($grpname);
        if ($gid == -1) {
            return "No group with the name##highlight## " . $grpname . "##end## existing!";
        }
        $comp = '';
        if ($combine == 'and') {
            $comp = '&&';
        }
        if ($combine == 'or') {
            $comp = '||';
        }
        if ($comp == '') {
            return "##highlight##" . $comp . " ##end##is an illegal combine type for flexible groups!";
        }
        $cond = $this->bot->db->select(
            "SELECT op FROM #___security_flexible WHERE gid = " . $gid . " AND field = 'join'"
        );
        if (!empty($cond)) {
            $rstr = "There is already flexible group extension for the group##highlight## " . $grpname;
            $rstr .= "##end##, the conditions are##highlight## ";
            if ($cond[0][0] == '&&') {
                $rstr .= "AND";
            } else {
                $rstr .= "OR";
            }
            $rstr .= "##end##-combined!";
            return $rstr;
        }
        $this->bot->db->query(
            "INSERT INTO #___security_flexible (gid, field, op) VALUES (" . $gid . ", 'join', '" . $comp . "')"
        );
        // Clear the flexible security cache
        $this->bot->core("flexible_security")->clear_cache();
        return "Flexible extension for group##highlight## " . $grpname . "##end## created, conditions will be##highlight## " . strtoupper(
            $combine
        ) . "##end##-combined!";
    }


    function exists_group($gid)
    {
        $ret = $this->bot->db->select(
            "SELECT * FROM #___security_flexible WHERE gid = " . $gid . " AND field = 'join'"
        );
        if (empty($ret)) {
            return false;
        }
        return true;
    }


    function delete_group($grpname)
    {
        $grpname = strtolower($grpname);
        $gid = $this->bot->core("security")->get_gid($grpname);
        if ($gid == -1) {
            return "No group with the name##highlight## " . $grpname . "##end## existing!";
        }
        $cond = $this->bot->db->select("SELECT op FROM #___security_flexible WHERE gid = " . $gid);
        if (empty($cond)) {
            return "There is no flexible group extension for the group##highlight## " . $grpname . "##end##!";
        }
        $this->bot->db->query("DELETE FROM #___security_flexible WHERE gid = " . $gid);
        // Clear the flexible security cache
        $this->bot->core("flexible_security")->clear_cache();
        return "The flexible group extension for group##highlight## " . $grpname . "##end## has been deleted!";
    }


    // level|rank_id|at_id
    function add_number_condition($grpname, $field, $condition, $compare)
    {
        $field = strtolower($field);
        $grpname = strtolower($grpname);
        $gid = $this->bot->core("security")->get_gid($grpname);
        if ($gid == -1) {
            return "No group with the name##highlight## " . $grpname . "##end## existing!";
        }
        if (!($this->exists_group($gid))) {
            return "##error##You cannot add conditions to non-extended security groups!##end##";
        }
        // Check number ranges depending on field
        if ($field == "level") {
            if ($compare < 1 || $compare > 220) {
                return "The level has to be between##highlight## 1##end## and##highlight## 220##end##!";
            }
        } elseif ($field == "rank_id") {
            if ($compare < 0 || $compare > 10) {
                return "The rank ID has to be between##highlight## 0##end## and##highlight## 10##end##!";
            }
        } elseif ($field == "at_id") {
            if ($compare < 0 || $compare > 30) {
                return "The alien level has to be between##highlight## 0##end## and##highlight## 30##end##!";
            }
        } else // shouldn't happen, but save is save
        {
            return "##error##Illegal fieldname!##end##";
        }
        // make sure condition is in legal range:
        switch ($condition) {
            case ">":
            case ">=":
            case "<":
            case "<=":
            case "=":
            case "!=":
                break;
            default:
                return "##error##Comparator illegal!##emd##";
        }
        // make sure the exact entry doesn't exist yet in the db:
        $entry = $this->bot->db->select(
            "SELECT * FROM #___security_flexible WHERE gid = " . $gid . " AND field = '" . $field . "' AND op = '" . $condition . "' AND compareto = '" . $compare . "'"
        );
        if (!empty($entry)) {
            return "##error##Duplicate entry in DB, you cannot enter the exactly same condition twice!##end##";
        }
        // enter the condition, all checks done
        $this->bot->db->query(
            "INSERT INTO #___security_flexible (gid, field, op, compareto) VALUES (" . $gid . ", '" . $field . "', '" . $condition . "', '" . $compare . "')"
        );
        if ($condition == '<') {
            $condition = '&lt;';
        }
        if ($condition == '<=') {
            $condition = '&lt;=';
        }
        // Clear the flexible security cache:
        $this->bot->core("flexible_security")->clear_cache();
        return
            "New condition##highlight## " . $field . " " . htmlentities(
                $condition
            ) . " " . $compare . "##end## added successfully to group##highlight## " . $grpname . "##end##!";
    }


    function add_prof_condition($grpname, $condition, $compare)
    {
        $grpname = strtolower($grpname);
        $gid = $this->bot->core("security")->get_gid($grpname);
        if ($gid == -1) {
            return "No group with the name##highlight## " . $grpname . "##end## existing!";
        }
        if (!($this->exists_group($gid))) {
            return "##error##You cannot add conditions to non-extended security groups!##end##";
        }
        if (($prof = $this->bot->core("professions")
                ->full_name($compare)) instanceof BotError
        ) {
            return $prof;
        }
        if ($condition != '=' && $condition != '!=') {
            return "##error##" . $condition . " is illegal for professions!##end##";
        }
        // make sure the exact entry doesn't exist yet in the db:
        $entry = $this->bot->db->select(
            "SELECT * FROM #___security_flexible WHERE gid = " . $gid . " AND field = 'profession'" . " AND op = '" . $condition . "' AND compareto = '" . $prof . "'"
        );
        if (!empty($entry)) {
            return "##error##Duplicate entry in DB, you cannot enter the exactly same condition twice!##end##";
        }
        // enter the condition, all checks done
        $this->bot->db->query(
            "INSERT INTO #___security_flexible (gid, field, op, compareto) VALUES (" . $gid . ", 'profession', '" . $condition . "', '" . $prof . "')"
        );
        // Clear the flexible security cache
        $this->bot->core("flexible_security")->clear_cache();
        return "New condition##highlight## profession " . $condition . " " . $prof . "##end## added successfully to group" . "##highlight## " . $grpname . "##end##!";
    }


    function add_faction_condition($grpname, $condition, $compare)
    {
        $grpname = strtolower($grpname);
        $gid = $this->bot->core("security")->get_gid($grpname);
        if ($gid == -1) {
            return "No group with the name##highlight## " . $grpname . "##end## existing!";
        }
        if (!($this->exists_group($gid))) {
            return "##error##You cannot add conditions to non-extended security groups!##end##";
        }
        $faction = strtolower($compare);
        if ($faction != 'omni' && $faction != 'clan' && $faction != 'neutral' && $faction != 'all') {
            return "##error##Illegal faction!##end##";
        }
        if ($condition != '=' && $condition != '!=') {
            return "##error##" . $condition . " is illegal for factions!##end##";
        }
        // make sure the exact entry doesn't exist yet in the db:
        $entry = $this->bot->db->select(
            "SELECT * FROM #___security_flexible WHERE gid = " . $gid . " AND field = 'faction'" . " AND op = '" . $condition . "' AND compareto = '" . $faction . "'"
        );
        if (!empty($entry)) {
            return "##error##Duplicate entry in DB, you cannot enter the exactly same condition twice!##end##";
        }
        // enter the condition, all checks done
        $this->bot->db->query(
            "INSERT INTO #___security_flexible (gid, field, op, compareto) VALUES (" . $gid . ", 'faction', '" . $condition . "', '" . $faction . "')"
        );
        // Clear the flexible security cache
        $this->bot->core("flexible_security")->clear_cache();
        return "New condition##highlight## faction " . $condition . " " . $faction . "##end## added successfully to group" . "##highlight## " . $grpname . "##end##!";
    }


    function add_org_condition($grpname, $condition, $compare)
    {
        $grpname = strtolower($grpname);
        $gid = $this->bot->core("security")->get_gid($grpname);
        if ($gid == -1) {
            return "No group with the name##highlight## " . $grpname . "##end## existing!";
        }
        if (!($this->exists_group($gid))) {
            return "##error##You cannot add conditions to non-extended security groups!##end##";
        }
        if ($condition != '=' && $condition != '!=') {
            return "##error##" . $condition . " is illegal for factions!##end##";
        }
        // Lookup player info (errors are catched in lookup())
        $info = $this->bot->core("whois")->lookup($compare);
        if ($info instanceof BotError) {
            return $info;
        }
        $org_id = $info['org_id'];
        $org_name = $info['org'];
        if ($org_id == 0) {
            return "##error##" . $compare . " is not in any org!##end##";
        }
        // make sure the exact entry doesn't exist yet in the db:
        $entry = $this->bot->db->select(
            "SELECT * FROM #___security_flexible WHERE gid = " . $gid . " AND field = 'org_id'" . " AND op = '" . $condition . "' AND compareto = '" . $org_id . "'"
        );
        if (!empty($entry)) {
            return "##error##Duplicate entry in DB, you cannot enter the exactly same condition twice!##end##";
        }
        // enter the condition, all checks done
        $this->bot->db->query(
            "INSERT INTO #___security_flexible (gid, field, op, compareto) VALUES (" . $gid . ", 'org_id', '" . $condition . "', '" . $org_id . "')"
        );
        // Clear the flexible security cache
        $this->bot->core("flexible_security")->clear_cache();
        return "New condition##highlight## org " . $condition . " " . $org_id . " (" . $org_name . ")##end## added successfully to group##highlight## " . $grpname . "##end##!";
    }


    function del_condition($grpname, $field, $condition, $compare)
    {
        $grpname = strtolower($grpname);
        // Valid values for $compare won't need escaping, but it's free user input, so better be save:
        $compare = mysql_real_escape_string($compare);
        $gid = $this->bot->core("security")->get_gid($grpname);
        if ($gid == -1) {
            return "No group with the name##highlight## " . $grpname . "##end## existing!";
        }
        // make sure the exact entry exist in the db:
        $entry = $this->bot->db->select(
            "SELECT * FROM #___security_flexible WHERE gid = " . $gid . " AND field = '" . $field . "' AND op = '" . $condition . "' AND compareto = '" . $compare . "'"
        );
        if (empty($entry)) {
            return "##error##Condition not found, can't delete it!##end##";
        }
        // Delete the entry
        $this->bot->db->query(
            "DELETE FROM #___security_flexible WHERE gid = " . $gid . " AND field = '" . $field . "' AND op = '" . $condition . "' AND compareto = '" . $compare . "'"
        );
        // Clear the flexible security cache
        $this->bot->core("flexible_security")->clear_cache();
        return "Condition ##highlight##" . $field . " " . htmlentities(
            $condition
        ) . " " . $compare . "##end## removed from group##highlight## " . $grpname . "##end##!";
    }


    // Make sure the GUEST group has an AND-combined extension.
    function check_guest_group($gid)
    {
        $cond = $this->bot->db->select(
            "SELECT op FROM #___security_flexible WHERE gid = " . $gid . " AND field = 'join'"
        );
        if (empty($cond)) {
            // Create extension:
            $this->bot->db->query(
                "INSERT INTO #___security_flexible (gid, field, op) VALUES (" . $gid . ", 'join', '&&')"
            );
        } else {
            // Make sure extension is AND-combined:
            if ($cond[0][0] != '&&') {
                // OR-combined or wrong definition, delete and create new:
                $this->bot->db->query("DELETE FROM #___security_flexible WHERE gid = " . $gid);
                $this->bot->db->query(
                    "INSERT INTO #___security_flexible (gid, field, op) VALUES (" . $gid . ", 'join', '&&')"
                );
            }
        }
    }


    function show_faction()
    {
        $grpname = strtolower(
            $this->bot->core("settings")
                ->get("Flexible_security", "Guest_group")
        );
        $gid = $this->bot->core("security")->get_gid($grpname);
        if ($gid == -1) {
            return "The setting ##highlight##Guest_Group##end## for the module ##highlight##Flexible_Security##end## isn't set " . "to an existing security group!";
        }
        $this->check_guest_group($gid);
        $fact = $this->bot->db->select(
            "SELECT op, compareto FROM #___security_flexible WHERE gid = " . $gid . " AND field = 'faction'"
        );
        if (empty($fact)) {
            $mode = "exclude";
            $faction = "all";
            $this->bot->db->query(
                "INSERT INTO #___security_flexible (gid, field, op, compareto) VALUES " . "(" . $gid . ", 'faction', '!=', 'all')"
            );
        } else {
            if ($fact[0][0] == '=') {
                $mode = "";
            } else {
                $mode = "exclude";
            }
            $faction = $fact[0][1];
        }
        // Clear the flexible security cache
        $this->bot->core("flexible_security")->clear_cache();
        return "Faction is currently set to ##highlight##" . $mode . " " . $faction . "##end##.";
    }


    function change_faction($comp, $faction)
    {
        $grpname = strtolower(
            $this->bot->core("settings")
                ->get("Flexible_security", "Guest_group")
        );
        $gid = $this->bot->core("security")->get_gid($grpname);
        if ($gid == -1) {
            return "The setting ##highlight##Guest_Group##end## for the module ##highlight##Flexible_Security##end## isn't set " . "to an existing security group!";
        }
        $this->check_guest_group($gid);
        $this->bot->db->query("DELETE FROM #___security_flexible WHERE gid = " . $gid . " AND field = 'faction'");
        $this->bot->db->query(
            "INSERT INTO #___security_flexible (gid, field, op, compareto) VALUES " . "(" . $gid . ", 'faction', '" . $comp . "', '" . $faction . "')"
        );
        if ($comp == '=') {
            $mode = "";
        } else {
            $mode = "exclude";
        }
        // Clear the flexible security cache
        $this->bot->core("flexible_security")->clear_cache();
        return "Faction is now set to ##highlight##" . $mode . " " . $faction . "##end##.";
    }


    function show_minlevel()
    {
        $grpname = strtolower(
            $this->bot->core("settings")
                ->get("Flexible_security", "Guest_group")
        );
        $gid = $this->bot->core("security")->get_gid($grpname);
        if ($gid == -1) {
            return "The setting ##highlight##Guest_Group##end## for the module ##highlight##Flexible_Security##end## isn't set " . "to an existing security group!";
        }
        $this->check_guest_group($gid);
        $mlvl = $this->bot->db->select(
            "SELECT compareto FROM #___security_flexible WHERE gid = " . $gid . " AND field = " . "'level' AND op = '>='"
        );
        if (empty($mlvl)) {
            $this->bot->db->query("DELETE FROM #___security_flexible WHERE gid = " . $gid . " AND field = 'level'");
            $this->bot->db->query(
                "INSERT INTO #___security_flexible (gid, field, op, compareto) VALUES " . "(" . $gid . ", 'level', '>=', '220')"
            );
            $minlevel = 220;
        } else {
            $minlevel = $mlvl[0][0];
        }
        // Clear the fexible security cache
        $this->bot->core("flexible_security")->clear_cache();
        return "Minimum level is currently set to##highlight## " . $minlevel . "##end##.";
    }


    function change_minlevel($newlevel)
    {
        $grpname = strtolower(
            $this->bot->core("settings")
                ->get("Flexible_security", "Guest_group")
        );
        $gid = $this->bot->core("security")->get_gid($grpname);
        if ($gid == -1) {
            return "The setting ##highlight##Guest_Group##end## for the module ##highlight##Flexible_Security##end## isn't set " . "to an existing security group!";
        }
        $this->check_guest_group($gid);
        if ($newlevel < 1 || $newlevel > 220) {
            return "##error##Level must be between 1 and 220!##end##";
        }
        $this->bot->db->query("DELETE FROM #___security_flexible WHERE gid = " . $gid . " AND field = 'level'");
        $this->bot->db->query(
            "INSERT INTO #___security_flexible (gid, field, op, compareto) VALUES " . "(" . $gid . ", 'level', '>=', '" . $newlevel . "')"
        );
        // Clear the flexible security cache
        $this->bot->core("flexible_security")->clear_cache();
        return "Minimum level is now set to ##highlight##" . $newlevel . "##end##.";
    }
}

?>
