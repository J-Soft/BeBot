<?php
/*
* Database of the land control zones, based on code by Wolfbiter, modified by Pharexys.
* Improved by Bitnykk using Tyrence's API courtesy of Unk & Draex
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
        $this->help['description'] = 'Land Control Areas';
        $this->help['command']['lc [name]'] = "Shows all towersites in [name]";
        $this->help['command']['lc 50'] = "Shows all towersites in the 50 range";
        $this->help['command']['lc 100 200'] = "Shows all towersites in the 100-200 range";
        $this->help['command']['lc 100 200 [name]'] = "Shows all towersites in the 100-200 range in [name]";
        $this->help['command']['lc'] = "Shows all Land Control Areas with a link to each area.";
        $this->help['command']['hot'] = "Shows Hot Notum Fields clickable interface.";
        $this->help['command']['hot [QL] [side]'] = "Shows Hot Notum Fields result(s).";
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
                $this->bot->db->update_table(
                    "land_control_zones",
                    "short",
                    "add",
                    "ALTER TABLE #___land_control_zones ADD short VARCHAR(5) DEFAULT NULL"
                );
                $this->bot->db->update_table(
                    "land_control_zones",
                    "zoneid",
                    "add",
                    "ALTER TABLE #___land_control_zones ADD zoneid INT(11) DEFAULT NULL"
                );
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
        $this->bot->db->set_version("land_control_zones", 6);
        $this->register_command("all", "lc", "MEMBER");
		$this -> register_alias('lc', 'lca');
		$this->bot->core("settings")->create("LandControl", "ApiUrl", "https://tower-api.jkbff.com/api/towers", "What is HTTP(s) JSON Tower API URL (Tyrence's by default) ?");
        $this->register_command("all", "hot", "MEMBER");		
    }


    function command_handler($name, $msg, $channel)
    {
        if (preg_match("/^lc  (.+)$/i", $msg, $info)) {
            return $this->show_lc($info[1]);
        } elseif (preg_match("/^lc (\d+) (\d+) (.+)$/i", $msg, $info)) {
            return $this->show_lc($info[3], $info[1], $info[2]);
        } elseif (preg_match("/^lc (\d+) ([^\d]+)$/i", $msg, $info)) {
            return $this->show_lc($info[2], $info[1], $info[1]);
        } elseif (preg_match("/^lc (\d+) (\d+)$/i", $msg, $info)) {
            return $this->show_lc(null, $info[1], $info[2]);
        } elseif (preg_match("/^lc (\d+)$/i", $msg, $info)) {
            return $this->show_lc(null, $info[1], $info[1]);
        } elseif (preg_match("/^lc ([^\d]+)$/i", $msg, $info)) {
            return $this->show_lc($info[1]);
        } elseif (preg_match("/^lc$/i", $msg, $info)) {
            return $this->show_lc("--all--");
        } elseif (preg_match("/^hot (\d+) (.+)$/i", $msg, $info)) {
            return $this->show_hot($info[1], strtolower($info[2]));
        } elseif (preg_match("/^hot$/i", $msg, $info)) {
            return $this->show_hot();
        } 
        return false;
    }

	
    function show_hot($ql = null, $side = null)
    {
		$return = "";
		if($ql==NULL || !is_numeric($ql) || $ql<0 || $ql>250 || $side==NULL || ($side!="clan"&&$side!="omni"&&$side!="neutral")) {
			$return .= "Choose a side & QL range by clicking below :";
			$return .= "<br><br>##orange##CLAN##end##";
			$return .= "<br>".$this->bot->core("tools")->chatcmd("hot 0 clan", "0-50")." ".$this->bot->core("tools")->chatcmd("hot 50 clan", "50-100")
			." ".$this->bot->core("tools")->chatcmd("hot 100 clan", "100-150")." ".$this->bot->core("tools")->chatcmd("hot 150 clan", "150-200")." ".
			$this->bot->core("tools")->chatcmd("hot 200 clan", "200-250")." ".$this->bot->core("tools")->chatcmd("hot 250 clan", "250-300");
			$return .= "<br><br>##blue##OMNI##end##";
			$return .= "<br>".$this->bot->core("tools")->chatcmd("hot 0 omni", "0-50")." ".$this->bot->core("tools")->chatcmd("hot 50 omni", "50-100")
			." ".$this->bot->core("tools")->chatcmd("hot 100 omni", "100-150")." ".$this->bot->core("tools")->chatcmd("hot 150 omni", "150-200")." ".
			$this->bot->core("tools")->chatcmd("hot 200 omni", "200-250")." ".$this->bot->core("tools")->chatcmd("hot 250 omni", "250-300");
			$return .= "<br><br>##yellow##NEUTRAL##end##";
			$return .= "<br>".$this->bot->core("tools")->chatcmd("hot 0 neutral", "0-50")." ".$this->bot->core("tools")->chatcmd("hot 50 neutral", "50-100")
			." ".$this->bot->core("tools")->chatcmd("hot 100 neutral", "100-150")." ".$this->bot->core("tools")->chatcmd("hot 150 neutral", "150-200")." ".
			$this->bot->core("tools")->chatcmd("hot 200 neutral", "200-250")." ".$this->bot->core("tools")->chatcmd("hot 250 neutral", "250-300");			
			
		} else {
			$secday = time() % 86400;
			$apiurl = $this->bot->core("settings")->get("LandControl", "ApiUrl");
			$mql = $ql+50;
			$mint = $secday+3600;
			if($mint>86400) $mint = $mint-86400;
			$maxt = $secday+23400;
			if($maxt>86400) $maxt = $maxt-86400;
			$content = $this->bot->core("tools")->get_site($apiurl."?limit=100&faction=".$side."&min_ql=".$ql."&max_ql=".$mql."&min_close_time=".$mint."&max_close_time=".$maxt);
			if (!($content instanceof BotError)) {
				if (strpos($content, '{"count":') !== false) {
					$datas = json_decode($content);
					$count = count($datas->results);
					$return .= $count." ".$side." field(s) found as hot now or soon :";
					foreach($datas->results AS $result) {						
						if ($result->faction == "Omni") { $color = "blue"; }
						elseif ($result->faction == "Clan") { $color = "orange"; }
						else { $color = "yellow"; }		
						$state = "Unknown";		
						$diff = $result->close_time - $secday;
						if ($diff<0) {
							$diff = $diff+86400;
						}
						if ($diff<=3600) {
							$min = ceil($diff/60);
							$state = "##pink##CLOSING##end## (5%) off in ".$min." m";
						} elseif ($diff<=21600) {
							$min = ceil($diff/60);
							if($min>60) {
								$hour = floor($diff/3600);
								$rest = $diff-($hour*3600);
								$min = $hour." h ".ceil($rest/60);
							}
							$state = "##green##OPENED##end## (25%) off in ".$min." m";
						} else {
							$upin = $diff-21600;
							$min = ceil($upin/60);
							if($min>60) {
								$hour = floor($upin/3600);
								$rest = $upin-($hour*3600);
								$min = $hour." h ".ceil($rest/60);
							}						
							$state = "##red##CLOSED##end## (75%) up in ".$min." m";
						}
						$return .= "<br><br>".$result->playfield_short_name." ".$result->site_number."x : ".$result->site_name
						        . "<br> Range: " . $result->min_ql . "-" . $result->max_ql
                                . "<br> Coord: " . $this->coords($result->x_coord,$result->y_coord,$result->playfield_id)
                                . "<br> State: " . "QL ".$result->ql." CT of ".$result->faction." (##".$color."##".$result->org_name."##end##) ".$state;
					}
				}
			}
		}
		return $this->bot->core("tools")
                ->make_blob("Hot Notum Fields", $return."<br><br>Provided by Tyrence's API courtesy of Unk & Draex");		
	}

	
    function show_lc($iarea = null, $lrange = 0, $hrange = 300)
    {
		$return = "";
        if ($iarea == "--all--") {
            $areas = $this->bot->db->select(
                "select distinct(area),count(area) from #___land_control_zones group by area"
            );
            if (!empty($areas)) {
                $return .= "<div align=center><u><font color=#10a5e5>Land Control Areas</font></u></div>";
                foreach ($areas as $area) {
                    $return .= $this->bot->core("tools")
                            ->chatcmd("lc  " . $area[0], $area[0]) . " (" . $area[1] . ")<br>";
                }
                return $this->bot->core("tools")
                    ->make_blob("Land Control Areas", $return);
            } else {
                return "No matches";
            }
        } else {
            if (!$iarea) {
                $areas = $this->bot->db->select(
                    "select distinct(area),count(area) from #___land_control_zones group by area"
                );
            } else {
                $areas = $this->bot->db->select(
                    "select distinct(area),count(area) from #___land_control_zones where area like '%" . $iarea . "%' group by area"
                );
            }
            if (!empty($areas)) {
                foreach ($areas as $area) {
                    unset($temp);
					$temp = "";
                    if (isset($return)) {
                        $temp = "<br><br>";
                    }
                    $temp .= "<div align=center><u><font color=#10a5e5>" . $area[0] . " (" . $area[1] . ")</font></u></div>";
                    if ($lrange == $hrange) {
                        $lcs = $this->bot->db->select(
                            "select id, lrange, hrange, area, huge, x, y, name, zoneid from #___land_control_zones where area='" . $area[0] . "' AND lrange<=" . $lrange . " AND hrange>="
                            . $hrange . " order by huge"
                        );
                    } else {
                        $lcs = $this->bot->db->select(
                            "select id, lrange, hrange, area, huge, x, y, name, zoneid from #___land_control_zones where area='" . $area[0] . "' AND lrange>=" . $lrange . " AND hrange<="
                            . $hrange . " order by huge"
                        );
                    }
                    if (!empty($lcs)) {
                        foreach ($lcs as $lc) {
                            $temp .= $lc[4] . "x : " . $lc[7]
							      . "<br> Range: " . $lc[1] . "-" . $lc[2]
                                  . "<br> Coord: " . $this->coords($lc[5],$lc[6],$lc[8])
                                  . "<br> State: " . $this->state($lc[8],$lc[4])
                                  . "<br><br>";
                        }
                        $return .= $temp;
                    }
                }
                return $this->bot->core("tools")
                    ->make_blob("Land Control Areas", $return."<br><br>Enriched w/ Tyrence's API courtesy of Unk & Draex");
            } else {
                return "No matches";
            }
        }
    }

	
    function coords($x,$y,$zid)
    {
        return $this->bot->core("tools")->chatcmd($x . " " . $y . " " . $zid, $x."x".$y."(".$zid.")", "waypoint");
    }
	
	
    function state($zid,$num)
    {
		$secday = time() % 86400;
		$return = "Unknown";
		$apiurl = $this->bot->core("settings")->get("LandControl", "ApiUrl");
		$content = $this->bot->core("tools")->get_site($apiurl."?playfield_id=".$zid."&site_number=".$num);
		if (!($content instanceof BotError)) {
			if (strpos($content, '{"count":1,') !== false) {
				$datas = json_decode($content);
				if ($datas->results[0]->ql != NULL && $datas->results[0]->org_name != NULL && $datas->results[0]->faction != NULL && $datas->results[0]->close_time != NULL) {
					if ($datas->results[0]->faction == "Omni") { $color = "blue"; }
					elseif ($datas->results[0]->faction == "Clan") { $color = "orange"; }
					else { $color = "yellow"; }
					$diff = $datas->results[0]->close_time - $secday;
					if ($diff<0) {
						$diff = $diff+86400;
					}
					if ($diff<=3600) {
						$min = ceil($diff/60);
						$state = "##pink##CLOSING##end## (5%) off in ".$min." m";
					} elseif ($diff<=21600) {
						$min = ceil($diff/60);
						if($min>60) {
							$hour = floor($diff/3600);
							$rest = $diff-($hour*3600);
							$min = $hour." h ".ceil($rest/60);
						}
						$state = "##green##OPENED##end## (25%) off in ".$min." m";
					} else {
						$upin = $diff-21600;
						$min = ceil($upin/60);
						if($min>60) {
							$hour = floor($upin/3600);
							$rest = $upin-($hour*3600);
							$min = $hour." h ".ceil($rest/60);
						}						
						$state = "##red##CLOSED##end## (75%) up in ".$min." m";
					}
					$return = "QL ".$datas->results[0]->ql." CT of ".$datas->results[0]->faction." (##".$color."##".$datas->results[0]->org_name."##end##) ".$state;
				}
			}
		}
		return $return;
    }	
	
}

?>
