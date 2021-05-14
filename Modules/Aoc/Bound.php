<?php
/*
* Bound.php - Instance bound tracker.
* Copyright (C) 2009-2011 Getrix @ Fury AoC
* Co-dev: Kyr (Version b1.x.x)
* Version b2.x.x is rewritten from b1.x.x to support more types of lockouts (PvP,Khitai dungeons etc)
* Further Updates: Ruskebusk (Version b3.0) & Bitnykk (for Bebot 0.7.x)
* Version history@end of file
*
* For BeBot - An Anarchy Online & Age of Conan Chat Automaton Developed by Blondengy (RK1)
* Copyright (C) 2009 Daniel Holmen
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

$Bound = new Bound($bot);

class Bound extends BaseActiveModule
{
    var $version;

    function __construct (&$bot)
    {
        /* Constructor: Hands over a referance to the "Bot" class. */
        //Initialize the base module
        parent::__construct($bot, get_class($this));
		
		if($this->bot->db->get_version("bound")<4)  $this->bot->db->query("DROP TABLE IF EXISTS #___bound");
        $this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("bound", "true") . " (
            `bound_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `bound_instance_id` int(4) NOT NULL,
            `bound_charname` varchar(25) DEFAULT NULL,
            `bound_start` datetime DEFAULT NULL,
            `bound_finish` datetime DEFAULT NULL,
            PRIMARY KEY  (`bound_id`))");
		if($this->bot->db->get_version("bound")<4) $this -> bot -> db -> set_version("bound", 4);		

		if($this->bot->db->get_version("bound_instance")<4)  $this->bot->db->query("DROP TABLE IF EXISTS #___bound_instance");
        $this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("bound_instance", "true") . " (
            `instance_id` int(10) unsigned DEFAULT NULL AUTO_INCREMENT,
            `instance_shortname` varchar(25) DEFAULT NULL,
            `instance_fullname` varchar(40) DEFAULT NULL,
            `instance_type` enum('Raid','RF','PvP','Solo','Group','WB') DEFAULT NULL,
            `instance_cd` int(11) DEFAULT '0',
            PRIMARY KEY  (`instance_id`))");
		$this -> update_table();
		if($this->bot->db->get_version("bound_instance")<4) $this -> bot -> db -> set_version("bound_instance", 4);
		
		$this -> bot -> core("settings") -> create("Bound", "ServerResetTime", 5, "How many hours after midnight (GMT) Monday night does your server reset the raid instances   ?", "1;2;3;4;5;6;7;8;9;10;11;12");
        $this -> register_command("all", "Bound", "MEMBER", array('raid' => 'LEADER', 'adminadd' => 'LEADER', 'adminrem' => 'LEADER'));

		// help and description
		$this -> version = "3.0.3";
        $this -> help['description'] = "This module helps you keep track of what instances your characters are bound.";
        $this -> help['command']['bound <nick>'] = "Give you a list of bindings for a character.";
        $this -> help['command']['bound list [instance_id]'] = "Shows list of instances and optionally what characters are bound to them.";
        $this -> help['command']['bound raid [instance_id]'] = "Will bound every bot member inside game instance to the choosen instance (LEADER only).";
        $this -> help['command']['bound adminadd <nick> <instance_id>'] = "Bind character to instance (LEADER only).";
        $this -> help['command']['bound adminrem <nick> <instance_id>'] = "Unbind character from instance (LEADER only).";
        $this -> help['notes'] = sprintf("(C) Module By Getrix@Fury & Ruskebusk+Bitnykk\n\n Version: ##lightbeige##%s##end##", $this->version);

    }


    function command_handler($name, $msg, $origin)
    {
        $com = $this->parse_com($msg, array('com', 'sub', 'arg1', 'arg2', 'args'));

        switch($com['com'])
        {
            case 'bound':
                return($this -> sub_handler($name, $com, 1));
            break;
            default:
                return "Error 1";
            break;
        }
    }

    function sub_handler($name, $com, $type)
    {
        switch($com['sub'])
        {
            case 'raid':
                if ($this->bot->core("security")->check_access($name, 'LEADER'))
                {
                    return($this -> bound_raid($name, $com['arg1']));
                }
                else
                {
                    $this -> error -> set("Only LEADER and above can add bound data for others.");
                    return($this->error->message());
                }			   			   
            break;
            case 'add':
                return($this -> bound_add($com['arg1'], $name));
            break;
            case 'rem':
                return($this -> bound_rem($com['arg1'], $name));
            break;
            case 'list':
                return($this -> bound_list($com['arg1'], $name));
            break;
            case 'adminadd':
                if ($this->bot->core("security")->check_access($name, 'LEADER'))
                {
                    return($this -> bound_add($com['arg2'], $com['arg1']));
                }
                else
                {
                    $this -> error -> set("Only LEADER and above can add bound data for others.");
                    return($this->error->message());
                }
            break;
            case 'admindel':
            case 'adminrem':
                if ($this->bot->core("security")->check_access($name, 'LEADER'))
                {
                    return($this -> bound_rem($com['arg2'], $com['arg1']));
                }
                else
                {
                    $this -> error -> set("Only LEADER and above can remove bound data for others.");
                    return($this->error->message());
                }
                break;
            default:
                return($this -> bound($name, $com['sub']));
            break;
        }
    }

    function bound_rem($instance_id, $name) {
        $this -> bot -> db -> query("DELETE FROM #___bound WHERE bound_charname='$name' AND bound_instance_id='$instance_id' LIMIT 1");
        $instance_info = $this->instance_info($instance_id);
        return "Removed the bind for '".$instance_info['fullname']."'. ".$this->bound($name);
    }

    function bound_add($instance_id, $name, $silent=FALSE) {
        $bound_add = TRUE;
        $instance_info = $this->instance_info($instance_id);
        if (is_array($instance_info)) {
            $now = date('Y-m-d H:i:s');
            if (!is_numeric($instance_id)) { $instance_id = $instance_info['id']; }

            $chk_bound = $this -> bot -> db -> select("SELECT bound_finish FROM #___bound WHERE bound_charname='$name' AND bound_instance_id='$instance_id' LIMIT 1");

            $chk_instance = $this -> bot -> db -> select("SELECT instance_id FROM #___bound_instance WHERE instance_id='$instance_id' LIMIT 1");

            if ($instance_info['type'] == "Raid") {
                $raid_reset = $this -> bot -> core("settings") -> get("Bound", "ServerResetTime");
                $bound_finish = date('Y-m-d H:i:s', strtotime('+'.$raid_reset.' hour next tuesday'));
            }
            else { $bound_finish = date('Y-m-d H:i:s', strtotime('+'.$instance_info['cd'].' min')); }

            if($chk_bound) {
                if ($chk_bound[0][0] >= $now) { if ($silent) { return TRUE; } else { return $name." is already bound to ".$instance_info['fullname']."!"; } }
                else { $sql = "UPDATE #___bound  SET bound_start='$now', bound_finish='$bound_finish' WHERE bound_charname='$name' AND bound_instance_id='$instance_id' LIMIT 1"; }
            }
            else { $sql = "INSERT INTO #___bound (bound_charname, bound_instance_id, bound_start, bound_finish) VALUES ('$name', '$instance_id', '$now', '$bound_finish')"; }

            $this -> bot -> db -> query($sql);

            $output = $name." is now bound to ".$instance_info['fullname']."!";

            $output .= " ".$this->bound($name);
        }
        else { $output = "Bind failed, instance doesn't exist!"; $bound_add = FALSE; }

        if ($silent) { return $bound_add; }
        else { return $output; }
    }

    function bound_raid($name, $args="") {
        if (!empty($args)) {
        preg_match("/([0-9]+)$/i", $args, $info);
        $instance_id = $info[1];

        $bound_count = "0";
        $output = "<center>##ao_infoheadline##:::: Binding the raid to instance  ::::##end##</center>\n";

        $chk_instance = $this -> bot -> db -> select("SELECT instance_id FROM #___bound_instance WHERE instance_id='$instance_id' LIMIT 1");
        if ($chk_instance) {
        $me_sql = "SELECT location FROM whois WHERE nickname='".$name."'";
        $me_result = $this -> bot -> db -> select($me_sql);
        if (!empty($me_result)) {
        $instanceID = $me_result[0][0];
        $sql  = "SELECT t1.nickname,level,class, ";
        $sql .= "t2.notify,user_level ";
        $sql .= "FROM whois AS t1 ";
        $sql .= "LEFT JOIN #___users AS t2 ON t1.nickname = t2.nickname ";
        $sql .= "WHERE location='".$instanceID."' AND notify='1' AND user_level='2' ";
        $sql .= "ORDER BY nickname";
        $result = $this -> bot -> db -> select($sql);
        if($result) {
          foreach ($result as $val) {
            $bound_nick = $val[0];

            $bound_add = $this->bound_add($instance_id, $bound_nick, TRUE);
            if ($bound_add) {
            $output .= $bound_nick . $this->brfix();
            $bound_count = $bound_count + 1;
            }
            else {
            $output .= $bound_nick ." (FAILED)".$this->brfix();
            }
          }
          $output .= $this->brfix(2)."In total: $bound_count in raid was bound.";
        } else { $output = "Didnt find any at your location"; }
        } else { $output = "Didnt find your location $instanceID ($instance_id)."; }

        } else { $output = "Bind failed, instance doesn't exist!";  }

    } else {
        $chk_inst = $this -> bot -> db -> select("SELECT instance_id, instance_fullname FROM #___bound_instance");
        if (!empty($chk_inst)) {
        $output = "<center>##ao_infoheadline##::::  Bound raid to instance  ::::##end##</center>\n";
        foreach ($chk_inst as $ir) {
        $instance_id   = $ir[0];
        $instance_name = $ir[1];
        $output .= "<a href='chatcmd:///tell ".$this -> bot -> botname." !bound raid $instance_id'>$instance_name</a>".$this->brfix();
        }
        $output = $this -> bot -> core("tools") -> make_blob("Instance list", $output);
        return $output;
        }
        else { return "Instances not found $args"; }
    }

    return $this -> bot -> core("tools") -> make_blob("Raid bound info", $output);
    }

    function bound($name, $args="") {
    $now = date('Y-m-d H:i:s');
        if (!empty($args)) {
          $bound_name = ($args);
        } else {
          $bound_name = $name;
        }

        $output = "<center>##ao_infoheadline##:::: Bound info for: $bound_name @ $now ::::##end##</center>\n";

        $select  = "SELECT i.instance_id, i.instance_shortname, i.instance_fullname, b.bound_finish, i.instance_type ";
        $select .= "FROM #___bound_instance AS i ";
        $select .= "LEFT JOIN #___bound AS b ON ";
        $select .= "i.instance_id=b.bound_instance_id AND b.bound_charname='$bound_name' ";
		$select .= "ORDER BY i.instance_id,i.instance_fullname";
        $chk_char = $this -> bot -> db -> select($select);
        if (!empty($chk_char)) {
          foreach ($chk_char as $ar) {
			$finish          = "";
            $instance_id         = $ar[0];
            $instance_shortname  = $ar[1];
            $instance_fullname   = $ar[2];
            $bound_finish        = $ar[3];
			$instance_type     = $ar[4];
			$bound_link = "";
            if ($bound_finish >= $now) {
              $bound_color = "red";
              if ($bound_name == $name) { $bound_link  = "- <a href='chatcmd:///tell ".$this -> bot -> botname." !bound rem $instance_id'>Unbind</a>"; }
			  if ($bound_finish != "") {  $finish = "(CD: $bound_finish)"; }
            }
            else {
              $bound_color = "green";
              if ($bound_name == $name) { $bound_link  = "- <a href='chatcmd:///tell ".$this -> bot -> botname." !bound add $instance_id'>Bind</a>"; }
            }
            $output .= $instance_type ." - <font color=$bound_color>$instance_fullname $bound_link $finish</font>".$this->brfix();
          }
        }
        else {
          $output .= "You are not bound to any instances!";
        }
        $output = $this -> bot -> core("tools") -> make_blob("Bound info for $bound_name", $output);
        return $output;
    }

    function bound_list($args, $name) {
    $now = date('Y-m-d H:i:s');
    $output = "<center>##ao_infoheadline##:::: Bounded chars ::::##end##</center>\n";
    if (is_numeric($args)) {
        $select = "SELECT instance_id,instance_fullname FROM #___bound_instance WHERE instance_id='$args' LIMIT 1";
    }
    else {
      $select = "SELECT instance_id,instance_fullname FROM #___bound_instance WHERE instance_shortname='$args' LIMIT 1";
    }

	$i = 1;
    $chk_inst = $this -> bot -> db -> select($select);
    if (!empty($chk_inst)) {
      foreach ($chk_inst as $ir) {
        $instance_id   = $ir[0];
        $instance_name = $ir[1];

        $output = "<center>##ao_infoheadline##:::: $instance_name ::::##end##</center>\n";

        $chk_char = $this -> bot -> db -> select("SELECT bound_charname,bound_finish FROM #___bound WHERE bound_instance_id='$instance_id' AND bound_finish >= '$now' ORDER BY bound_charname");
        if (!empty($chk_char)) {
          foreach ($chk_char as $ar) {
			$output .= $i.": ".$ar[0] . " (CD: ".$ar[1].") ".$this->brfix();
			$i++;
          }
        }
        else { $output .= "No chars bound."; }
      }
    }
    else {
      $chk_inst = $this -> bot -> db -> select("SELECT instance_id, instance_fullname, instance_type FROM #___bound_instance ORDER BY instance_id,instance_fullname");
      if (!empty($chk_inst)) {
        $output = "<center>##ao_infoheadline##::::  Instance list  ::::##end##</center>\n";
        foreach ($chk_inst as $ir) {
          $instance_id   = $ir[0];
          $instance_name = $ir[1];
          $instance_type = $ir[2];
          $output .= $instance_type." - <a href='chatcmd:///tell ".$this -> bot -> botname." !bound list $instance_id'>$instance_name</a>".$this->brfix();
        }
        $output = $this -> bot -> core("tools") -> make_blob("Instance list", $output);
        return $output;
      }
      else { return "Instances not found $args"; }
    }

    $output = $this -> bot -> core("tools") -> make_blob("Bound list for instance: $instance_name", $output);
    return $output;
    }

    function brfix($count=1) {
        if ($count == 2) { $br = "<b></b><br><b></b><br>";}
        elseif ($count == 3) { $br = "<b></b><br><b></b><br><b></b><br>"; }
        else { $br = "<b></b><br>"; }
        return $br;
    }

    function update_table() {
        $chk_inst = $this -> bot -> db -> select("SELECT COUNT(*) FROM #___bound_instance");
        if($chk_inst[0][0]<48) {
            $this -> bot -> db -> query("INSERT INTO `#___bound_instance` (`instance_shortname`, `instance_fullname`, `instance_type`, `instance_cd`) VALUES
            ('Vistrix', 'Tier 1 - The Dragon''s Lair', 'Raid', 0),
            ('Yakhmar', 'Tier 1 - Yakhmar''s Cave', 'Raid', 0),
            ('Kyllikki', 'Tier 1 - Kyllikki''s Crypt', 'Raid', 0),
            ('W1', 'Tier 2 - Black Ring Citadel: Wing 1', 'Raid', 0),
            ('W2', 'Tier 2 - Black Ring Citadel: Wing 2', 'Raid', 0),
            ('W3', 'Tier 2 - Black Ring Citadel: Wing 3', 'Raid', 0),
            ('T3lower', 'Tier 3 - Thoth-Amons Tower - Lower Floor', 'Raid', 0),
            ('T3upper', 'Tier 3 - Thoth-Amons Tower - Upper Floor', 'Raid', 0),
            ('T3.5', 'Tier 3.5 - Temple of Erlik', 'Raid', 0),
            ('Courtyard', 'Tier 4 - TJC - Courtyard', 'Raid', 0),
            ('Citadel', 'Tier 4 - TJC - Jade Citadel', 'Raid', 0),
            ('Entity', 'Tier 4 - TJC - Chamber of Lost Dreams', 'Raid', 0),
            ('T5-Yakhmar', 'Tier 5 - Yakhmar', 'Raid', 0),
            ('T5-Honorguard', 'Tier 5 - Champion of the Honorguard', 'Raid', 0),
            ('T5-Kyllikki', 'Tier 5 - Kyllikki', 'Raid', 0),
            ('T5-Vistrix', 'Tier 5 - Vistrix', 'Raid', 0),
            ('T6', 'Tier 6 - The Palace of Cetriss', 'Raid', 0),
            ('RFT2', 'RF - Black Ring Citadel', 'RF', 10080),
            ('RFlower', 'RF - Thoth-Amon''s Tower - Lower Floor', 'RF', 10080),
            ('RFupper', 'RF - Thoth-Amons Tower - Upper Floor', 'RF', 10080),
            ('RFtemple', 'RF - The Temple of Erlik', 'RF', 10080),
            ('RFcourtyard', 'RF - The Jade Citadel Courtyard', 'RF', 10080),
            ('RFcitadel', 'RF - The Jade Citadel', 'RF', 10080),
            ('RFentity', 'RF - Chamber of Lost Dreams', 'RF', 10080),
            ('RFfirst', 'RF - PoC: The First Seal', 'RF', 10080),
            ('RFlotus', 'RF - PoC: The Lady and the Lotus', 'RF', 10080),
            ('RFemerald', 'RF - PoC: The Emberald Wizard', 'RF', 10080),
            ('January', 'World Boss - January', 'WB', 515500),
            ('February', 'World Boss - February', 'WB', 515500),
            ('March', 'World Boss - March', 'WB', 515500),
            ('April', 'World Boss - April', 'WB', 515500),
            ('May', 'World Boss - May', 'WB', 515500),
            ('June', 'World Boss - June', 'WB', 515500),
            ('July', 'World Boss - July', 'WB', 515500),
            ('August', 'World Boss - August', 'WB', 515500),
            ('September', 'World Boss - September', 'WB', 515500),
            ('October', 'World Boss - October', 'WB', 515500),
            ('November', 'World Boss - November', 'WB', 515500),
            ('December', 'World Boss - December', 'WB', 515500),
            ('BR', 'The Battle for Blood Ravine', 'PvP', 360),
            ('HV', 'The Battle for Hallowed Vaults', 'PvP', 360),
            ('LT', 'The Battle for Lost Temple', 'PvP', 360),
            ('TT', 'Totem Destruction', 'PvP', 360),
            ('CoJS', 'Blessings of an Ancient God', 'PvP', 360),
            ('CoJS', 'Blessings of Jhebbal Sag', 'PvP', 360),
            ('CoJS', 'Diving Calling', 'PvP', 360),
            ('RoKW', 'Refuge Of the Apostate', 'Solo', 1200),
            ('IoIS', 'Isle of Iron Statues', 'Solo', 1200)");
            $this->bot->log("Bound", "DB", "Updated all instances");
        }
    }


    function instance_info($instance_id) {
    if (is_numeric($instance_id)) {
        $sql = $this -> bot -> db -> select("SELECT instance_type,instance_cd,instance_shortname,instance_fullname,instance_id FROM #___bound_instance WHERE instance_id='$instance_id' LIMIT 1");
    }
    else {
        $instance_shortname = ($instance_id);
        $sql = $this -> bot -> db -> select("SELECT instance_type,instance_cd,instance_shortname,instance_fullname,instance_id FROM #___bound_instance WHERE instance_shortname='$instance_shortname' LIMIT 1");
    }

    if($sql) {
        $inst['type']       = $sql[0][0];
        $inst['cd']            = $sql[0][1];
            $inst['shortname']  = $sql[0][2];
            $inst['fullname']   = $sql[0][3];
        $inst['id']       = $sql[0][4];
        return $inst;
    }
    else {
        return FALSE;
    }

    }
}

/*
* Changelog History
* b3.0.3  - 2021-04-13 Bitnykk   - Integrated into the 0.7.x serie of Bebot with schema updates
* b3.0.2  - 2020-03-14 Ruskebusk - Changed some instance_shortnames. Can be used: !bound add t6
* b3.0.1  -	2019-09-09 Ruskebusk - Added World Boss to the bound table with 515500 minutes / 1 year CD.
* b3.0.0  - 2019-02-09 Ruskebusk - Added all raids up to T6. Also added raid finder main quest CD with a CD of 10080 minutes / 7 days.
* b2.0.2  - 2010-08-16 Getrix	 - Fixed bounding raids bug when bot runs on Windows machine.
* b2.0.1  - 2010-05-02 Getrix	 - Getting it ready for public test. Old tables need to be deleted.
* b2.0.0  - 2010-05-01 Getrix	 - Rewritten script to handle more lockout types: Raid, Group (6mans), Solo, Minigame
* b1.0.10 - 2010-02-04 Getrix	 - fixed typos that caused it to crash
* b1.0.9  - 2010-02-04 Getrix 	 - added update_table to add instances when they arrives. T3 is being added to tblv2
* b1.0.8  - 2009-12-14 Kyr  	 - added admin ability to add/remove binds for others
* b1.0.7  - 2009-12-13 Getrix 	 - Added "!bound raid" for LEADER(default) to bound whole raid within a instance.
* b1.0.6  - 2009-11-21 Kyr    	 - added setting for ServerResetTime
* b1.0.5  - 2009-11-21 Getrix 	 - Fixed typos
* b1.0.4  - 2009-11-19 Getrix 	 - Fixed bug in function bound()
* b1.0.3 Added !bound <nick>
* First release. Added "add interfaces"
*/

?>
