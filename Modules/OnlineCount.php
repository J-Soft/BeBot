<?php
/*
* Displays online numbers for each profession or online characters of a specific profession.
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
$onlinecounting = new OnlineCounting($bot);
class OnlineCounting extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->bot->core("colors")->define_scheme("counting", "text", "normal");
        $this->bot->core("colors")
            ->define_scheme("counting", "number", "lightgray");
        $this->bot->core("colors")
            ->define_scheme("counting", "name", "forestgreen");
        $this->register_command("all", "count", "GUEST");
        $this->register_command("all", "check", "GUEST");
        if (strtololower($this->bot->game) == 'aoc') {
            $this->cp = "class";
        }
        else {
            $this->cp = "profession";
        }
        $this->help['description'] = 'Lists characters in chat group';
        $this->help['command']['count all'] = "Lists all professions and the number of characters of each " . $this->cp . " in chat";
        $this->help['command']['count'] = $this->help['command']['count all'];
        $this->help['command']['count [prof]'] = "Lists all members of [prof] with level and alien level that are in chat.";
        $this->help['command']['count org'] = "Lists the number of characters per organization currently online in chat.";
        $this->help['command']['count org [orgname]'] = "Lists the number of characters online in chat that are in the organization [orgname].";
        $this->help['command']['check all'] = "Offers assist on everybody online in chat.";
        $this->help['command']['check'] = $this->help['command']['check all'];
        $this->help['command']['check [prof]'] = "Offers assist on all members of [prof] in the chat.";
        $this->help['command']['check org'] = "Offers assist on all characters in chat sorted by their organizations.";
        $this->help['command']['check org [orgname]'] = "Offers assist on all characters online in chat that are in the organization [orgname].";
    }


    function command_handler($name, $msg, $type)
    {
        if (preg_match("/^count$/i", $msg, $info)) {
            return $this->count_all();
        }
        elseif (preg_match("/^count all$/i", $msg, $info)) {
            return $this->count_all();
        }
        elseif (preg_match("/^count org$/i", $msg, $info)) {
            return $this->count_org();
        }
        elseif (preg_match("/^count org (.*)$/i", $msg, $info)) {
            return $this->count_org_members($info[1]);
        }
        elseif (preg_match("/^count (.*)$/i", $msg, $info)) {
            return $this->count($info[1]);
        }
        elseif (preg_match("/^check$/i", $msg, $info)) {
            return $this->check_all();
        }
        elseif (preg_match("/^check all$/i", $msg, $info)) {
            return $this->check_all();
        }
        elseif (preg_match("/^check org$/i", $msg, $info)) {
            return $this->check_org();
        }
        elseif (preg_match("/^check org (.*)$/i", $msg, $info)) {
            return $this->check_org_members($info[1]);
        }
        elseif (preg_match("/^check (.*)$/i", $msg, $info)) {
            return $this->check($info[1]);
        }
    }


    function make_assist($assist, $title)
    {
        return "<a href='chatcmd://" . implode(" \\n ", $assist) . "'>" . $title . "</a>";
    }


    function get_org_members($orgname)
    {
        return $this->bot->db->select(
            "SELECT DISTINCT(t1.nickname), t2.level, t2.defender_rank_id" . " FROM " . $this->bot
                ->core("online")
                ->full_tablename() . " WHERE t2.org_name = '" . $orgname . "' ORDER BY t1.nickname ASC"
        );
    }


    function get_prof($prof)
    {
        if ($prof == "") {
            $profsearch = "!= ''";
        }
        else {
            $profsearch = "= '" . $prof . "'";
        }
        return $this->bot->db->select(
            "SELECT DISTINCT(t1.nickname), t2.level, t2.defender_rank_id" . " FROM " . $this->bot
                ->core("online")
                ->full_tablename() . " WHERE t2." . $this->cp . " " . $profsearch . " ORDER BY t1.nickname ASC"
        );
    }


    function get_orgs()
    {
        $innersql = "SELECT t2.org_name as org, COUNT(DISTINCT t1.nickname) AS count " . " FROM " . $this->bot
            ->core("online")
            ->full_tablename() . " WHERE t2.org_name != '' GROUP BY t2.org_name ORDER BY count DESC, org_name ASC";
        $sql = "SELECT t1.org AS org, t1.count AS count, SUM(t2.level) / t1.count AS avg_level FROM (" . $innersql
            . ") AS t1, #___whois AS t2, #___online AS t3 WHERE t1.org = t2.org_name AND " . "t2.nickname = t3.nickname AND " . $this->bot
            ->core("online")->otherbots("t3.") . " AND " . $this->bot
            ->core("online")
            ->channels("t3.") . " GROUP BY org ORDER BY t1.count DESC, t1.org ASC";
        return $this->bot->db->select($sql, MYSQL_ASSOC);
    }


    function make_org_assist($orgname)
    {
        $org = $this->get_org_members($orgname);
        if (empty($org)) {
            return "";
        }
        $assist = array();
        foreach ($org as $mem) {
            $assist[] = "/assist " . $mem[0];
        }
        return $this->make_assist($assist, "Check " . $orgname);
    }


    function count_all()
    {
        $profession_list = "'" . $this->bot->core('professions')
            ->get_professions("', '") . "'";
        $shortcut_array = array_combine(
            $this->bot->core('professions')
                ->get_profession_array(), $this->bot->core('professions')
                ->get_shortcut_array()
        );
        foreach ($shortcut_array as $prof) {
            $profession_count[$prof] = 0;
        }
        $query = "SELECT t2." . $this->cp . " as profession, COUNT(DISTINCT t1.nickname) as count" . " FROM " . $this->bot
            ->core("online")
            ->full_tablename() . " WHERE t2." . $this->cp . " IN (" . $profession_list . ") GROUP BY " . $this->cp . "";
        $online_count = $this->bot->db->select($query, MYSQL_ASSOC);
        $total_online = 0;
        if (!empty($online_count)) {
            foreach ($online_count as $profession) {
                $profession_count[$shortcut_array[$profession['profession']]] += $profession['count'];
                $total_online += $profession['count'];
            }
        }
        $output = "Total: ##counting_number##$total_online##end##";
        foreach ($profession_count as $shortcut => $count) {
            $output .= ", $shortcut: ##counting_number##$count##end##";
        }
        return $this->bot->core("colors")->colorize("counting_text", $output);
    }


    function count($shortcut)
    {
        if (($prof = $this->bot->core("professions")
            ->full_name($shortcut)) instanceof BotError
        ) {
            return $prof;
        }
        $pcount = $this->bot->db->select(
            "SELECT COUNT(DISTINCT t1.nickname) FROM " . $this->bot
                ->core("online")
                ->full_tablename() . " WHERE t2." . $this->cp . " = '" . $prof . "'"
        );
        if ($pcount[0][0] == 0) {
            return $this->bot->core("colors")
                ->colorize("counting_text", "No " . $prof . " in chat!");
        }
        $profchars = $this->get_prof($prof);
        $first = 1;
        $retstr = $pcount[0][0] . " " . $prof . "s in chat: ";
        $strings = array();
        foreach ($profchars as $curchar) {
            $helpstr = $this->bot->core("colors")
                ->colorize("counting_name", $curchar[0]) . " [";
            $helpstr .= $this->bot->core("colors")
                ->colorize("counting_number", $curchar[1]) . "/";
            $helpstr .= $this->bot->core("colors")
                ->colorize("counting_number", $curchar[2]) . "]";
            $strings[] = $helpstr;
        }
        $retstr .= implode(", ", $strings);
        return $this->bot->core("colors")->colorize("counting_text", $retstr);
    }


    function count_org()
    {
        $counts = $this->get_orgs();
        if (empty($counts)) {
            return $this->bot->core("colors")
                ->colorize("counting_text", "Nobody online!");
        }
        $tcount = $this->bot->db->select(
            "SELECT count(DISTINCT nickname) as count FROM #___online WHERE " . $this->bot
                ->core("online")->otherbots("") . " AND " . $this->bot
                ->core("online")->channels(""), MYSQL_ASSOC
        );
        $totalcount = $tcount[0]['count'];
        $orgs = array();
        foreach ($counts as $org) {
            $perc = (100 * $org['count']) / $totalcount;
            $orgcmd = $this->bot->core("tools")
                ->chatcmd("count org " . $org['org'], $org['org']);
            $orgstr = round($perc, 1) . "% " . $orgcmd . ": " . $org['count'] . " with an average level of " . round($org['avg_level'], 1);
            $orgs[] = $orgstr;
        }
        return $this->bot->core("tools")
            ->make_blob("Online organizations", "##blob_title##Online organisations:##end##<br><br>" . implode("<br>", $orgs));
    }


    function count_org_members($orgname)
    {
        $pcount = $this->bot->db->select(
            "SELECT COUNT(DISTINCT t1.nickname) FROM " . $this->bot
                ->core("online")
                ->full_tablename() . " WHERE t2.org_name = '" . $orgname . "'"
        );
        if ($pcount[0][0] == 0) {
            return $this->bot->core("colors")
                ->colorize("counting_text", "No member of " . $prof . " in chat!");
        }
        $profchars = $this->get_org_members($orgname);
        $first = 1;
        $retstr = $pcount[0][0] . " member of " . $orgname . " in chat: ";
        $strings = array();
        foreach ($profchars as $curchar) {
            $helpstr = $this->bot->core("colors")
                ->colorize("counting_name", $curchar[0]) . " [";
            $helpstr .= $this->bot->core("colors")
                ->colorize("counting_number", $curchar[1]) . "/";
            $helpstr .= $this->bot->core("colors")
                ->colorize("counting_number", $curchar[2]) . "]";
            $strings[] = $helpstr;
        }
        $retstr .= implode(", ", $strings);
        return $this->bot->core("colors")->colorize("counting_text", $retstr);
    }


    function check_all()
    {
        $online = $this->get_prof("");
        if (empty($online)) {
            return "Nobody online!";
        }
        $assist = array();
        foreach ($online as $mem) {
            $assist[] = "/assist " . $mem[0];
        }
        return $this->bot->core("tools")
            ->make_blob("Check all online", $this->make_assist($assist, "Check all online"));
    }


    function check($shortcut)
    {
        if (($prof = $this->bot->core("professions")
            ->full_name($shortcut)) instanceof BotError
        ) {
            return $prof;
        }
        $profchars = $this->get_prof($prof);
        if (empty($profchars)) {
            return "No " . $prof . " in chat!";
        }
        $assist = array();
        foreach ($profchars as $mem) {
            $assist[] = "/assist " . $mem[0];
        }
        return $this->bot->core("tools")
            ->make_blob("Check " . $prof, $this->make_assist($assist, "Check " . $prof));
    }


    function check_org()
    {
        $orgs = $this->get_orgs();
        if (empty($orgs)) {
            return "Nobody online!";
        }
        $orgassist = array();
        foreach ($orgs as $org) {
            $orgblob = $this->make_org_assist($org['org']);
            if ($orgblob != "") {
                $orgassist[] = $orgblob;
            }
        }
        return $this->bot->core("tools")
            ->make_blob("Check organizations", implode("\n", $orgassist));
    }


    function check_org_members($orgname)
    {
        $blob = $this->make_org_assist($orgname);
        if ($blob == "") {
            return "Nobody of " . $orgname . " online!";
        }
        return $this->bot->core("tools")->make_blob("Check " . $orgname, $blob);
    }
}

?>
