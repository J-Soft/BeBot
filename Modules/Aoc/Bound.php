<?php
/*
* Bound.php - Instance bound tracker.
* <Copyright Getrix @ Fury AoC>
* 
* b1.0.7 - 2021-03-14 Adapted for the Bebot 0.7.x serie
* b1.0.6 - 2009-11-21 Kyr - added setting for ServerResetTime
* b1.0.5 - 2009-11-21 Fixed typos
* b1.0.4 - 2009-11-19 Fixed bug in function bound()
* b1.0.3 Added !bound <nick>
* First release. Added "add interfaces"
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 Thomas Juberg Stensås, ShadowRealm Creations and the BeBot development team.
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

        parent::__construct($bot, get_class($this));
    
        $this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("bound", "true") . " (
           `bound_id` int(10) unsigned NOT NULL auto_increment,
           `bound_instance_id` int(4) NOT NULL,
           `bound_charname` varchar(25) default NULL,
           `bound_week` int(3) default NULL,
           PRIMARY KEY  (`bound_id`)
        )");

        $this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("bound_instance", "true") . " (
           `instance_id` int(10) unsigned NOT NULL auto_increment,
           `instance_shortname` varchar(25) default NULL,
           `instance_fullname` varchar(40) default NULL,
           PRIMARY KEY  (`instance_id`)
        )");
        $this -> instance_add();
		
		$this -> version = "b1.0.7";
        $this -> register_command('all', 'bound', 'MEMBER');
        $this -> help['description'] = "This module helps you keep track of what instances your characters are bound.";
        $this -> help['command']['bound <nick>'] = "Give you a list of bindings for a character.";
        $this -> help['command']['bound list [instance_id]'] = "Shows list of instances and optionally what characters are bound to them.";
        $this -> help['notes'] = "(C) Module By Getrix@Fury\n";
        $this -> bot -> core("settings") -> create("Bound", "ServerResetTime", 5, "How many hours after midnight (GMT) Monday night does your server reset the raid instances   ?", "1;2;3;4;5;6;7;8;9;10;11;12");
    }


    function command_handler($name, $msg, $origin)
    {
        $com = $this->parse_com($msg);
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
            case 'add':
                return($this -> bound_add($com['args'], $name));
            break;
            case 'rem':
                return($this -> bound_rem($com['args'], $name));
            break;
            case 'list':
                return($this -> bound_list($com['args'], $name));
            break;
            default:
                return($this -> bound($name, $com['sub']));
            break;
        }
    }

    function bound_rem($args, $name) {
        preg_match("/([0-9]+)$/i", $args, $info);
        $instance_id = $info[1];
        $bound_week  = $this -> get_weeknr();
        $this -> bot -> db -> query("DELETE FROM #___bound WHERE bound_charname='$name' AND bound_instance_id='$instance_id' AND bound_week='$bound_week' LIMIT 1");
        return "Removed the bound. ".$this->bound($name);
    }
    
    function bound_add($args, $name) {
        preg_match("/([0-9]+)$/i", $args, $info);
        $instance_id = $info[1];
        $bound_week  = $this -> get_weeknr();
    
        $chk_bound = $this -> bot -> db -> select("SELECT bound_charname FROM #___bound WHERE bound_charname='$name' AND bound_instance_id='$instance_id' AND bound_week='$bound_week' LIMIT 1");
        if(!$chk_bound) {
          $chk_instance = $this -> bot -> db -> select("SELECT instance_id FROM #___bound_instance WHERE instance_id='$instance_id' LIMIT 1");
          if ($chk_instance) {
            $this -> bot -> db -> query("INSERT INTO #___bound (bound_charname, bound_instance_id, bound_week) VALUES ('$name', '$instance_id', '$bound_week')");
            $output = "You are now bound to this instance!";
          }
          else { $output = "Bind failed, instance doesn't exist!"; }
        }
        else {
          $output = "You are already bound to this instance.";
        }
        $output .= " ".$this->bound($name);
        return $output;
    }
    
    function bound($name, $args="") {
        if (!empty($args)) {
          $bound_name = mysql_real_escape_string($args);
        } else {
          $bound_name = $name;
        }

        $current_week = $this->get_weeknr();
        $output = "<center>##ao_infoheadline##:::: Bound info week $current_week for: $bound_name ::::##end##</center>\n";

        $select  = "SELECT i.*, b.bound_week ";
        $select .= "FROM #___bound_instance AS i ";
        $select .= "LEFT JOIN #___bound AS b ON ";
        $select .= "i.instance_id=b.bound_instance_id AND b.bound_charname='$bound_name' AND b.bound_week='$current_week'";
        $chk_char = $this -> bot -> db -> select($select);
        if (!empty($chk_char)) {
          foreach ($chk_char as $ar) {
            $instance_id         = $ar[0];
            $instance_shortname  = $ar[1];
            $instance_fullname   = $ar[2];
            $bound_week          = $ar[3];
            
            if ($current_week == $bound_week) {
              $bound_color = "red";
              if ($bound_name == $name) { $bound_link  = "- <a href='chatcmd:///tell ".$this -> bot -> botname." !bound rem $instance_id'>Unbound</a>"; }
            }
            else {
              $bound_color = "green";
              if ($bound_name == $name) { $bound_link  = "- <a href='chatcmd:///tell ".$this -> bot -> botname." !bound add $instance_id'>Bound</a>"; }
            }
            $output .= "<font color=$bound_color>$instance_fullname $bound_link</font>".$this->brfix();  
          }
        }
        else {
          $output .= "You are not bound to any instances!";
        }
        $output = $this -> bot -> core("tools") -> make_blob("Bound info for $bound_name", $output);
        return $output;
    }
    
    function bound_list($args, $name) {
      $bound_week = $this->get_weeknr();
      $output = "<center>##ao_infoheadline##:::: Bound chars week $bound_week ::::##end##</center>\n";
      if (is_numeric($args)) {
        $select = "SELECT instance_id,instance_fullname FROM #___bound_instance WHERE instance_id='$args' LIMIT 1";
      }
      else {
        $select = "SELECT instance_id,instance_fullname FROM #___bound_instance WHERE instance_shortname='$args' LIMIT 1";
      }
    
      $chk_inst = $this -> bot -> db -> select($select);
      if (!empty($chk_inst)) {
        foreach ($chk_inst as $ir) {
          $instance_id   = $ir[0];
          $instance_name = $ir[1];
        
          $output = "<center>##ao_infoheadline##:::: $instance_name ::::##end##</center>\n";

          $chk_char = $this -> bot -> db -> select("SELECT bound_charname FROM #___bound WHERE bound_instance_id='$instance_id' AND bound_week='$bound_week' ORDER BY bound_charname"); 
          if (!empty($chk_char)) {
            foreach ($chk_char as $ar) {
              $output .= $ar[0].$this->brfix();
            }
          }
          else { $output .= "No chars bound."; }
        }
      }
      else {
        $chk_inst = $this -> bot -> db -> select("SELECT instance_id, instance_fullname FROM #___bound_instance");
        if (!empty($chk_inst)) {
          $output = "<center>##ao_infoheadline##::::  Instance list  ::::##end##</center>\n";    
          foreach ($chk_inst as $ir) {
            $instance_id   = $ir[0];
            $instance_name = $ir[1];
            $output .= "<a href='chatcmd:///tell ".$this -> bot -> botname." !bound list $instance_id'>$instance_name</a>".$this->brfix();
          }
          $output = $this -> bot -> core("tools") -> make_blob("Instance list", $output);
          return $output;
        }
        else { return "Instances not found $args"; }
      }
    
      $output = $this -> bot -> core("tools") -> make_blob("Bound list for instance: $instance_name", $output);
      return $output;
    }
    
    function get_weeknr($date="") {
        if (empty($date)) { $date = time(); }
        else { $date = strtotime($date); }
        $day_number  = date("N", $date);
        $week_number = date("W", $date);
        $hour_number = (int)date("H", $date);
        $reset_number = (int)$this -> bot -> core("settings") -> get("Bound", "ServerResetTime");
        if ($day_number == "1" || ($day_number == "2" && $hour_number < $reset_number)) { $week_number = $week_number - 1; }        
        return $week_number;
    }
    
    function brfix($count=1) {
        if ($count == 2) { $br = "<b></b><br><b></b><br>";}
        elseif ($count == 3) { $br = "<b></b><br><b></b><br><b></b><br>"; }
        else { $br = "<b></b><br>"; }
        return $br;
    }
    
    function instance_add() {
      $chk_inst = $this -> bot -> db -> select("SELECT instance_id FROM #___bound_instance LIMIT 1");
      if(!$chk_inst) {    
        $this -> bot -> db -> query("INSERT INTO `#___bound_instance` (`instance_id`, `instance_shortname`, `instance_fullname`) VALUES
            (1, 'BRC-W1', 'Tier 2 - Black Ring Citadel: Wing 1'),
            (2, 'BRC-W2', 'Tier 2 - Black Ring Citadel: Wing 2'),
            (3, 'BRC-W3', 'Tier 2 - Black Ring Citadel: Wing 3'),
            (4, 'Vistrix', 'Tier 1 - The Dragon''s Lair'),
            (5, 'Yakhmar', 'Tier 1 - Yakhmar''s Cave'),
            (6, 'Kyllikki', 'Tier 1 - Kyllikki''s Crypt')");
        $this->bot->log("Bound", "DB", "Added T1+T2 instances");
      }
    }
}
?>   