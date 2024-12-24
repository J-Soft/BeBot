<?php
/*
* Database of the land control zones, based on code by Wolfbiter, modified by Pharexys.
* Improved by Bitnykk first using Tyrence's API courtesy of Unk & Draex, then Nady's API
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
$landcontrol = new LandControlZones($bot);
class LandControlZones extends BaseActiveModule
{
	var $towers;
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("land_control_zones", "true") . " (
			`id` int(11) default 0,
			`lrange` int(10) default NULL,
			`hrange` int(10) default NULL,
			`area` varchar(50) default NULL,
			`short` varchar(5) default NULL,
			`huge` varchar(10) default NULL,
			`zoneid` int(11) default NULL,
			`x` varchar(10) default NULL,
			`y` varchar(10) default NULL,
			`name` varchar(250) default NULL,
			PRIMARY KEY (id),
			UNIQUE (area, name),
			INDEX (lrange),
			INDEX (hrange),
			INDEX (area)
			)"
        );
        $this->help['description'] = 'Land Control Areas + Hot/Cold';
        $this->help['command']['lc'] = "Shows a link to each available area.";
        $this->help['command']['lc [area]'] = "Shows all towersites in given area.";
        $this->help['command']['lc [QL]'] = "Shows all towersites around given QL.";
        $this->help['command']['hot'] = "Shows all hot towersites.";
        $this->help['command']['hot [QL]'] = "Shows hot towersites around given QL.";
        $this->help['command']['plant'] = "Shows all towersites currently available to be planted.";		
		$this->help['notes'] = "The external Hot/Cold API accepts a cache time NO lower than 5 min (otherwise you might be banned) which is the default.";
        if ($this->bot->core("settings")
            ->exists("LandControl", "SchemaVersion")
        ) {
            $this->bot->db->set_version(
                "land_control_zones",
                $this->bot
                    ->core("settings")->get("LandControl", "SchemaVersion")
            );
            $this->bot->core("settings")->del("LandControl", "SchemaVersion");
        }
		$zones = $this->bot->db->select(
			"SELECT COUNT(*) FROM " . $this->bot->db->define_tablename("land_control_zones", "true")
		);
		if ($zones[0][0]==0) { $this->bot->db->set_version("land_control_zones", 5); }
        switch ($this->bot->db->get_version("land_control_zones")) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
			case 6:
				$col = $this->bot->db->select("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '#___land_control_zones' AND COLUMN_NAME = 'short'");
				if(count($col)==0) {
					$this->bot->db->update_table(
						"land_control_zones",
						"short",
						"add",
						"ALTER TABLE #___land_control_zones ADD short VARCHAR(5) DEFAULT NULL"
					);
				}
				$col = $this->bot->db->select("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '#___land_control_zones' AND COLUMN_NAME = 'zoneid'");
				if(count($col)==0) {				
					$this->bot->db->update_table(
						"land_control_zones",
						"zoneid",
						"add",
						"ALTER TABLE #___land_control_zones ADD zoneid INT(11) DEFAULT NULL"
					);
				}
                $this->bot->db->query("truncate table #___land_control_zones");
                $filename = "./Extras/TableData/LcZones.sql";
                $handle = fopen($filename, "r");
                $query = fread($handle, filesize($filename));
                fclose($handle);
                if (!empty($query)) {
                    $this->bot->db->query($query);
                }
            default:
        }
        $this->bot->db->set_version("land_control_zones", 7);
        $this->register_command("all", "lc", "MEMBER");
		$this -> register_alias('lc', 'lca');
		$this->bot->core("settings")->create("LandControl", "ApiUrl", "https://towers.aobots.org/api/sites/", "What is HTTP(s) JSON Tower API URL (Nady's by default) ?");
		if($this->bot->core("settings")->get("LandControl", "ApiUrl")!="https://towers.aobots.org/api/sites/") {
			$this->bot->core("settings")->save("LandControl", "ApiUrl", "https://towers.aobots.org/api/sites/");
		}
        $this->register_command("all", "hot", "MEMBER");		
        $this->register_command("all", "plant", "MEMBER");
		$this->towers= array();
		$this->register_event("cron", "5min");
    }


    function command_handler($name, $msg, $channel)
    {
       if (preg_match("/^lc (\d+)$/i", $msg, $info)) {
            return $this->show_lc(null, $info[1]);
        } else if (preg_match("/^lc (.+)$/i", $msg, $info)) {
            return $this->show_lc($info[1], null);
        } elseif (preg_match("/^lc$/i", $msg, $info)) {
            return $this->show_lc("--all--", null);
        } elseif (preg_match("/^hot (\d+)$/i", $msg, $info)) {
            return $this->show_hot($info[1]);
        } elseif (preg_match("/^hot$/i", $msg, $info)) {
            return $this->show_hot();
        } elseif (preg_match("/^plant$/i", $msg, $info)) {
            return $this->show_plant();
        } 
        return false;
    }
	
    function cron($cron)
    {
		if ($cron == 300) {
			$content = $this->bot->core("tools")->get_site($this->bot->core("settings")->get("LandControl", "ApiUrl"));
			if (!($content instanceof BotError)) {				
				if (strpos($content, '{"name":') !== false) {
					$towers = json_decode($content);				
					$this->towers= array();
					$this->towers= $towers;
				}
			}
		}
	}
	
    function show_hot(?int $ql = null)
    {
		$datas = array();
		$temp = array();
		$return = "";
		$count = 0;
		if($ql==NULL || !is_numeric($ql) || $ql<0 || $ql>250) {
			$return .= "Any QL currently hot sites :";
			foreach ($this->towers as $tower) {
				if($tower->gas!=75&&$tower->enabled==true&&$tower->plant_time!=null) {
					$count++;
					$temp = $this->format($tower);
					$datas[$temp[0]][$temp[1]] = $temp[2];
				}
			}
		} else {
			$return .= "Around QL ".$ql." currently hot sites :";
			foreach ($this->towers as $tower) {
				if($ql>=$tower->min_ql&&$ql<=$tower->max_ql&&$tower->gas!=75&&$tower->enabled==true&&$tower->plant_time!=null) {
					$count++;
					$temp = $this->format($tower);
					$datas[$temp[0]][$temp[1]] = $temp[2];
				}
			}
		}
		ksort($datas);
		foreach($datas AS $data => $val) {
			ksort($datas[$data]);
		}
		foreach($datas AS $data) {
			foreach($data AS $val => $ret) {
				$return .= $ret;
			}
		}
		return $this->bot->core("tools")
                ->make_blob($count." Hot Notum Field(s)", $return."<br><br>CT QL/Org, Gas state/timer & Def Conductor+Turret provided by Nady's API");		
	}

	
    function show_lc(?string $iarea = null, ?int $ql = null)
    {
		$datas = array();
		$temp = array();
		$return = "";
		$count = 0;
        if ($iarea == "--all--") {
            $areas = $this->bot->db->select(
                "select distinct(area),count(area) from #___land_control_zones where id < 264 group by area"
            );
            if (!empty($areas)) {
                $return .= "<div align=center><u><font color=#10a5e5>Land Control Areas</font></u></div>";
                foreach ($areas as $area) {
					$count++;
                    $return .= $this->bot->core("tools")
                            ->chatcmd("lc " . $area[0], $area[0]) . " (" . $area[1] . ")<br>";
                }
                return $this->bot->core("tools")
                    ->make_blob("Land Control Areas", $return);
            } else {
                return "No matches";
            }
		} else {
			if($iarea!=NULL) {
				$area = $this->bot->db->select(
					"select zoneid from #___land_control_zones where area = '".$iarea."' LIMIT 1"
				);				
				if(isset($area[0][0])) {
					foreach ($this->towers as $tower) {
						if($tower->playfield_id==$area[0][0]) {
							$count++;
							$temp = $this->format($tower);
							$datas[$temp[0]][$temp[1]] = $temp[2];
						}
					}
				} else {
					$return = "Wrong area, please retry with a valid name in the list of !lc.";
				}
			} elseif(is_numeric($ql)) {
				foreach ($this->towers as $tower) {
					if($ql>=$tower->min_ql&&$ql<=$tower->max_ql) {
						$count++;
						$temp = $this->format($tower);
						$datas[$temp[0]][$temp[1]] = $temp[2];
					}
				}
			}
		}
		ksort($datas);
		foreach($datas AS $data => $val) {
			ksort($datas[$data]);
		}
		foreach($datas AS $data) {
			foreach($data AS $val => $ret) {
				$return .= $ret;
			}
		}
		return $this->bot->core("tools")
                ->make_blob($count." Land Control Areas", $return."<br><br>CT QL/Org, Gas state/timer & Def Conductor+Turret provided by Nady's API");			
    }
	

    function show_plant()
    {
		$datas = array();
		$temp = array();
		$return = "";
		$count = 0;
		foreach ($this->towers as $tower) {
			if($tower->enabled==true&&$tower->plant_time==null) {
				$count++;
				$temp = $this->format($tower);
				$datas[$temp[0]][$temp[1]] = $temp[2];
			}
		}
		ksort($datas);
		foreach($datas AS $data => $val) {
			ksort($datas[$data]);
		}
		foreach($datas AS $data) {
			foreach($data AS $val => $ret) {
				$return .= $ret;
			}
		}	
		return $this->bot->core("tools")
                ->make_blob($count." Unplanted Field(s)", $return."<br><br>CT QL/Org, Gas state/timer & Def Conductor+Turret provided by Nady's API");
    }	

	
	function format($result)
	{
		$datas = array();
		$return = "";
		$msg = "";
		if ($result->org_faction == "Omni") { $color = "aqua"; }
		elseif ($result->org_faction == "Clan") { $color = "orange"; }
		else { $color = "gray"; }		
		$state = "##gray##Disabled ...##end##";
		$def = "##gray##?D##end##(?C+?T)";
		if($result->enabled==true) {
			if ($result->plant_time!=null) {
				$times = $this->times($result->plant_time,$result->timing);
				if ($result->gas==5) {
					$state = "##yellow##CLOSING##end##(5%) / Closes in ".$this->clean($times[0]);
				} elseif ($result->gas==25) {
					if($result->gas==$times[2]) {
						$state = "##green##OPENED##end##(25%) / Closes in ".$this->clean($times[0]);
					} else {				
						$msg = "Closes in few minutes.";
						if ($this->bot->exists_module("towerattack")) {
							$check = $this->bot->db->select(
								"select time from #___tower_attack where off_guild = '".addslashes($result->org_name)."' ORDER BY time DESC LIMIT 1"
							);
							if(isset($check[0][0])&&is_numeric($check[0][0])&&$check[0][0]>0) {
								$now = time();
								$ms = date('i:s', $result->plant_time);
								$min = $check[0][0]+3600;
								$minh = date('H', $min);
								$max = $check[0][0]+7200;
								$maxh = date('H', $max);
								$ended = new DateTime('today '.$minh.':'.$ms);
								$endet = $ended->getTimestamp();
								if($endet<$min) {
									$ended = new DateTime('today '.$maxh.':'.$ms);
									$endet = $ended->getTimestamp();
								}
								if($now<$endet) {
									$msg = "Closes in about ".$this->clean($endet);
								}
							}
						}						
						$state = "##orange##PENALTIED##end##(25%) / ".$msg;
					}
				} else {
					$state = "##red##CLOSED##end##(75%) / Opens in ".$this->clean($times[1]);
				}
				$c = $result->num_conductors;
				$t = $result->num_turrets;
				$d = $t+$c;
				$l = "##green##";
				if($d>20) $l = "##yellow##";
				if($d>40) $l = "##red##";
				$def = $l.$d."D##end##(".$c."C+".$t."T)";
			} else {
				$state = "##white##Unplanted !!!##end##";
			}
		}
		if($result->ql==null) { $rql="?"; } else { $rql=$result->ql; }
		if($result->org_faction==null) { $rf="?"; } else { $rf=$result->org_faction; }
		if($result->org_name==null) { $ron="?"; } else { $ron=$result->org_name; }
		$infos = $this->bot->db->select(
			"select short from #___land_control_zones where name = '".addslashes($result->name)."' LIMIT 1"
		);		
		$return .= "<br><br>".$infos[0][0]." ".$result->site_id."x"
				. "<br> Range: " . $result->min_ql . "-" . $result->max_ql." / Def=".$def
				. "<br> Coord: " . $this->coords($result->center->x,$result->center->y,$result->playfield_id,$result->name)
				. "<br> Infos: " . "QL ".$rql." CT of ".$rf." ##".$color."##".$ron."##end##"
				. "<br> State: Gas=" . $state;
		$datas[] = $result->playfield_id;
		$datas[] = $result->site_id;
		$datas[] = $return;
		return $datas;
	}

	function clean($next)
	{
        $now = time();
		$left = $next-$now;
        $hour = floor($left/3600);
        $left = $left - ($hour*3600);
        $min = floor($left/60);
        $sec = $left - ($min*60);
		if ($sec < 10) { $sec = "0".$sec; }
        if ($hour < 10) { $hour = "0".$hour; }
        if ($min < 10) { $min = "0".$min; }
		$msg = $hour."h".$min."m".$sec."s";
		return $msg;
    }	
	
    function times($plant,$type)
    {
		$times = array();
		$now = time();
		$ms = date('i:s', $plant);
		$hms = date('H:i:s', $plant);
		if($type=="StaticEurope") {
			$closed = new DateTime('today 20:'.$ms);
			$closet = $closed->getTimestamp();
			$opened = new DateTime('today 14:'.$ms);
			$openet = $opened->getTimestamp();			
			if($now<$closet&&$now>$openet) {
				$curgas = 25;
				$fived = new DateTime('today 19:'.$ms);
				$fivet = $fived->getTimestamp();
				if($now<$closet&&$now>$fivet) {
					$curgas = 5;
				}
			} else {
				$curgas = 75;
			}
			if($closet<$now) {
				$closed = new DateTime('tomorrow 20:'.$ms);
				$closet = $closed->getTimestamp();
			}		
			if($openet<$now) {
				$opened = new DateTime('tomorrow 14:'.$ms);
				$openet = $opened->getTimestamp();
			}				
		} elseif($type=="StaticUS") {
			$closed = new DateTime('today 04:'.$ms);
			$closet = $closed->getTimestamp();
			$opened = new DateTime('today 22:'.$ms);
			$openet = $opened->getTimestamp();					
			if($now<$closet||$now>$openet) {
				$curgas = 25;
				$fived = new DateTime('today 03:'.$ms);
				$fivet = $fived->getTimestamp();
				if($now<$closet&&$now>$fivet) {
					$curgas = 5;
				}
			} else {
				$curgas = 75;
			}
			if($closet<$now) {
				$closed = new DateTime('tomorrow 04:'.$ms);
				$closet = $closed->getTimestamp();
			}		
			if($openet<$now) {
				$opened = new DateTime('tomorrow 22:'.$ms);
				$openet = $opened->getTimestamp();
			}			
		} else { // "Dynamic"
			$closed = new DateTime('today '.$hms);
			$closet = $closed->getTimestamp();
			$dynop = $plant+64800;
			$dynhms = date('H:i:s', $dynop);
			$opened = new DateTime('today '.$dynhms);
			$openet = $opened->getTimestamp();
			$dynfiv = $dynop+18000;
			$fivhms = date('H:i:s', $dynfiv);					
			if($closet>$openet) {	
				if($now<$closet&&$now>$openet) {
					$curgas = 25;
					$fived = new DateTime('today '.$fivhms);
					$fivet = $fived->getTimestamp();
					if($now<$closet&&$now>$fivet) {
						$curgas = 5;
					}
				} else {
					$curgas = 75;
				}				
			} else {
				if($now<$closet||$now>$openet) {
					$curgas = 25;
					$fived = new DateTime('today '.$fivhms);
					$fivet = $fived->getTimestamp();
					if($now<$closet&&$now>$fivet) {
						$curgas = 5;
					}
				} else {
					$curgas = 75;
				}				
			}
			if($closet<$now) {
				$closed = new DateTime('tomorrow '.$hms);
				$closet = $closed->getTimestamp();
			}
			if($openet<$now) {
				$opened = new DateTime('tomorrow '.$dynhms);
				$openet = $opened->getTimestamp();
			}				
		}
		$times[] = $closet;
		$times[] = $openet;
		$times[] = $curgas;
        return $times;
    }
	
    function coords($x,$y,$zid,$name)
    {
        return $this->bot->core("tools")->chatcmd($x . " " . $y . " " . $zid, $name, "waypoint");
    }
	
}

?>