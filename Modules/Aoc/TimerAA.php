<?php
/*
* TimerAA.php - TimerAA.
* <Copyright Getrix @ Fury AoC>
* <Credits>
*   Yite@BeBot.Forum
*   heljeere@BeBot.Forum/Crom
* </Credits>
*
*	changelog:
*  2021-03-12  b1.0.8  Adapted for the Bebot 0.7.x serie
*  2020-03-22  b1.0.7  Added option to set the timer to 'finished' for those who have filled the AA tree.
*                      Logon notify will not spam you when you are set to 'finished' or 'done'.
*	2019-09-14	b1.0.6	Added logon notify if you dont have a timer set for characters above lvl 20. (Credits: Ruskebusk / Crom)
* 				b1.0.5	Added optional name of AA being trained (Credits: Kyr)
* 				b1.0.4	Rewrote "showall" to sort the timers by when they run out. Also cleaned it up a bit so it now does all the work in a single query instead of doing one per toon. (Credits: heljeere@BeBot.Forum/Crom)
*				b1.0.3	Added "timeraa showall <nick>" to include alts of a chars. (Thanks to Yite@BeBot.Forum)
* 				b1.0.2	Recoded diff calculation and some minor text changes
* 				b1.0.1	Command access fixed and change in database. Clean of old structur needed (DROP #__timer_aa)
* 				b1.0.0	First release.
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

$timeraa = new TimerAA($bot);

class TimerAA extends BaseActiveModule
{
    var $version;

    function __construct ($bot)
    {

		parent::__construct($bot, get_class($this));

        $this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("timer_aa", "true") . " (
            `timeraa_id` int(11) NOT NULL AUTO_INCREMENT,
            `timeraa_username` varchar(255) DEFAULT NULL,
            `timeraa_start` varchar(255) DEFAULT NULL,
            `timeraa_end` varchar(255) DEFAULT NULL,
            `timeraa_cooldown` int(11) DEFAULT NULL,
			`timeraa_name` varchar(100) DEFAULT NULL,
            `finished` int(1) DEFAULT 0,
            PRIMARY KEY (`timeraa_id`))");

		$this -> register_event("logon_notify");
		$this -> register_command('all', 'timeraa', 'MEMBER');
		$this -> bot -> core("settings")-> create("TimerAA", "Remind", TRUE, "Characters above lvl 20 who dont have a timer set, should they be reminded on logon?");

		$this -> version = "b1.0.8";
        $this -> help['description'] = "This module helps you keep track of Offline AA time training.";
        $this -> help['command']['timeraa show <nick>'] = "Show how long time left over running AA training for given char only.";
        $this -> help['command']['timeraa showall <nick>'] = "Show how long time left on running AA trainings for all user chars.";
        $this -> help['command']['timeraa set 18'] = "Set the training time for current AA. Only use hours.";
        $this -> help['command']['timeraa set 18 <name of AA>'] = "Set the training time for current AA. Only use hours, with optional name of AA.";
        $this -> help['command']['timeraa set done|finished'] = "Finishes your current AA timer, or disables if none existed.";
        $this -> help['notes'] = sprintf("(C) Module By Getrix@Fury<br />Version: ##lightbeige##%s##end##", $this->version);
    }
	
    function command_handler($name, $msg, $origin)
    {
        $com = $this->parse_com($msg);
        switch($com['com'])
        {
            case 'timeraa':
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
            case 'set':
                return($this -> timer_set($name, $com['args']));
            break;
            case 'show':
                return($this -> timer_show($name, $com['args']));
            break;
            case 'showall':
                return($this -> timer_show_all($name, $com['args']));
            break;
            default:
                return("Not added");
            break;
        }
    }

    function timer_set($name, $args) {
        $arr  =  explode(" ", $args);
        $firstspace = strpos($args, ' ');

        $timeraa_username = mysqli_real_escape_string($this->bot->db->CONN,$name);
        $timeraa_cooldown = strtolower(mysqli_real_escape_string($this->bot->db->CONN,$arr[0]));
        $timeraa_start    = time();
        $timeraa_name     = "";

        if ($firstspace != 0)
            $timeraa_name = substr($args, $firstspace + 1);

		$chk_time = $this -> bot -> db -> select("SELECT * FROM #___timer_aa WHERE timeraa_username='$name'");
		
        if (!is_numeric($timeraa_cooldown)) {
          if ($timeraa_cooldown === "done" OR $timeraa_cooldown === "finished") {
			if (empty($chk_time)) {
				$sql1 = "INSERT INTO #___timer_aa (timeraa_username, timeraa_start, timeraa_end, , timeraa_cooldown, timeraa_name, finished) VALUES('$timeraa_username', '$timeraa_start', '$timeraa_start', '0', 'Disabled', '1')";
			} else {
				$sql1 = "UPDATE #___timer_aa SET timeraa_username='$timeraa_username', timeraa_start='$timeraa_start', timeraa_cooldown='0', timeraa_name='Finished', finished='1' WHERE timeraa_username='$timeraa_username'";
			}
			$query = $this->bot->db->query($sql1);				
            return "##lime##$timeraa_username##end## is done training AA timers and will not be notified upon logon!";
          }else {
            return $timeraa_cooldown." is not a number";
          }
        }

        $timeraa_end      = $this->utime_add($timeraa_start, $timeraa_cooldown);

        if (empty($chk_time)) {
           $sql = "INSERT INTO #___timer_aa (timeraa_username, timeraa_cooldown, timeraa_start, timeraa_end, timeraa_name) VALUES('$timeraa_username', '$timeraa_cooldown', '$timeraa_start', '$timeraa_end', '$timeraa_name')";
        }
        else {
           $sql = "UPDATE #___timer_aa SET timeraa_username='$timeraa_username', timeraa_cooldown='$timeraa_cooldown', timeraa_start='$timeraa_start', timeraa_end='$timeraa_end', timeraa_name='$timeraa_name', finished='0' WHERE timeraa_username='$name'";
        }

        $this -> bot -> db -> query($sql);
        return "Timer AA set for ##lime##$timeraa_username##end## with Cooldown ##fuchsia##$timeraa_cooldown##end## ##aqua##$timeraa_name##end##";
    }


    function timer_show($name, $args) {
        if (!empty($args)) {
          $name = mysqli_real_escape_string($this->bot->db->CONN,$args);
        }

        $chk_time = $this -> bot -> db -> select("SELECT * FROM #___timer_aa WHERE timeraa_username='$name'");
        if (!empty($chk_time)) {
            foreach ($chk_time as $aa) {
                $timeraa_start      = $aa[2];
                $timeraa_end        = $aa[3];
                $timeraa_ends       = date("M d H:i:s",$timeraa_end);
                $timeraa_cooldown   = $aa[4];
                $timeraa_name       = $aa[5];
                $today  = time();
                $diff = $this->get_time_difference($today, $timeraa_end);

                return "Offline AA Training Timer ##lime##$name##end## ends $timeraa_ends (##fuchsia##$diff left of $timeraa_cooldown hrs##end##) ##aqua##$timeraa_name##end##";
            }
        }
        else { return "No timer set"; }
    }

    function timer_show_all($name, $args) {
        if (!empty($args)) {
          $name = mysqli_real_escape_string($this->bot->db->CONN,$args);
        }

        $findmain = $this -> bot -> db -> select("SELECT main FROM alts WHERE alts.alt='$name'");
        if (empty($findmain)) {$main=$name;}
        else {$main = current($findmain[0]);}
        $output = "<Center>##ao_infoheadline##:::: Offline timer info for $main and alts ::::##end##</Center>".$this->brfix(2);

      $notsetoutput = $this->brfix()."<Center>##ao_infoheadline##:::: No timers ::::##end##</Center>".$this->brfix(2);
      // timeraa_username, timeraa_start, timeraa_end, timeraa_cooldown, timeraa_name
      $chk_time = $this -> bot -> db -> select("SELECT #___timer_aa.*, alts.alt FROM alts LEFT OUTER JOIN #___timer_aa ON alts.alt = timeraa_username WHERE  alts.main = '$main' UNION SELECT #___timer_aa.*, '$main' AS alt FROM #___timer_aa WHERE timeraa_username = '$main' ORDER BY timeraa_end ASC");

      if (!empty($chk_time)) {
         foreach ($chk_time as $aa) {
           $timeraa_start      = $aa[2];
           $timeraa_end        = $aa[3];
           $timeraa_ends       = date("d.m.Y H:i:s", $timeraa_end);
           $timeraa_cooldown   = $aa[4];
           $timeraa_name        = $aa[5];
           $today  = time();
           $diff = $this->get_time_difference($today, $timeraa_end);
           $finished = $aa[6];

           if ($finished != 1) {
             if (!empty($aa[1])) {
				$output .= "##lime##".$aa[1]."##end## ends $timeraa_ends (##fuchsia##$diff left of ".$timeraa_cooldown."hrs##end##) ##aqua##$timeraa_name##end##" . $this->brfix();
            }else {
				$notsetoutput .= $aa[6].$this->brfix();
            }
          }
        }
      }

        $output = $this -> bot -> core("tools") -> make_blob("AA timers for ##lime##$main##end##", $output.$notsetoutput);
        return $output;
    }

    function utime_add($unixtime, $hr=0, $min=0, $sec=0, $mon=0, $day=0, $yr=0) {
        $dt = localtime($unixtime, true);
        $unixnewtime = mktime(
        $dt['tm_hour']+$hr, $dt['tm_min']+$min, $dt['tm_sec']+$sec,
        $dt['tm_mon']+1+$mon, $dt['tm_mday']+$day, $dt['tm_year']+1900+$yr
        );
        return $unixnewtime;
    }

    function get_time_difference( $start, $end ) {
	$diff_text = "";
    $uts['start']      =    $start ;
    $uts['end']        =    $end ;
        if( $uts['start']!==-1 && $uts['end']!==-1 ) {
            if( $uts['end'] >= $uts['start'] ) {
                $diff    =    $uts['end'] - $uts['start'];
				$days=intval((floor($diff/86400)));
                if($days)
                    $diff = $diff % 86400;
				$hours=intval((floor($diff/3600)));
                if($hours)
                    $diff = $diff % 3600;
				$minutes=intval((floor($diff/60)));
                if($minutes)
                    $diff = $diff % 60;
                $seconds=intval($diff);
                $diffs = array();
                if ($days    != "0") { $diffs['days']    = $days; }
                if ($hours   != "0") { $diffs['hours']   = $hours; }
                if ($minutes != "0") { $diffs['minutes'] = $minutes; }
                if ($seconds != "0") { $diffs['seconds'] = $seconds; }
                foreach ($diffs as $key=>$df) {
                    $diff_text .= $df." ".$key." ";
                }
                return $diff_text;

            } else {
				return "0";
			}
		} else {
			return "Invalid date/time data detected";
		}
        return( false );
    }

    function brfix($count=1) {
        if ($count == 2) { $br = "<b></b><br><b></b><br>";}
        elseif ($count == 3) { $br = "<b></b><br><b></b><br><b></b><br>"; }
        else { $br = "<b></b><br>"; }
        return $br;
    }

    function notify($name, $startup = false) {

		$user = mysqli_real_escape_string($this->bot->db->CONN,$name);
		$timeraa_end = 0; $timeraa_finished = 0; $output = "Nothing found.";
		$time = time();
		$endtime = $this -> bot -> db -> select("SELECT timeraa_end, finished FROM #___timer_aa WHERE timeraa_username='$user' LIMIT 1");
		
		if(!empty($endtime)) {
			foreach($endtime as $end) {
				$timeraa_end = $end[0];
				$timeraa_finished = $end[1];
			}
		}

		$result = $this->bot->core("whois")->lookup($user);

		// send a tell on logon if time() is more than timeraa_end.. which should not be the case if it is set/active
		if ($this->bot->core("settings")->get("TimerAA", "Remind")) {
			if ($time > $timeraa_end && $timeraa_finished != 1) {
				if ($result["level"] > 20) {
					$msg = "##red##TimerAA##end##: You have no timer set! You can disable this message by replying: !timeraa set done";
					$output = $this->bot->send_tell($user, $msg);
				}
			}
		}
		// $msg2 = "Logon notify debugg endtime: ".$time. " og aaend: ".$aaend[0]." og result: ".$result["level"]; // for debugging
		// $this->bot->send_tell($user, $msg2); // for debugging
		return $output;
    }
	
}
?>