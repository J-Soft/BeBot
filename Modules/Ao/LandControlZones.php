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
        $this->help['description'] = 'Land Control Areas + Hot/Cold';
        $this->help['command']['lc'] = "Shows a link to each available area.";
        $this->help['command']['lc [name]'] = "Shows all towersites in named area.";
        $this->help['command']['lc [QL]'] = "Shows all towersites of given QL (in range of +10 to limit results).";
        $this->help['command']['hot'] = "Shows hot towersites clickable interface.";
        $this->help['command']['hot [QL] [side]'] = "Shows hot towersites of given side and QL (in range of +50 to limit results).";
		$this->help['notes'] = "The external Hot/Cold API requires cautions of limited use ; abusing it (pull every qls/zones, eg) may get your bot/server banned and unable to read it.";
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
		$this->bot->core("settings")->create("LandControl", "ApiUrl", "https://tower-api.jkbff.com/v1/api/towers", "What is HTTP(s) JSON Tower API URL (Tyrence's by default) ?");
		if($this->bot->core("settings")->get("LandControl", "ApiUrl")=="https://tower-api.jkbff.com/api/towers") {
			$this->bot->core("settings")->save("LandControl", "ApiUrl", "https://tower-api.jkbff.com/v1/api/towers");
		}
        $this->register_command("all", "hot", "MEMBER");		
    }


    function command_handler($name, $msg, $channel)
    {
       if (preg_match("/^lc (\d+)$/i", $msg, $info)) {
            return $this->show_lc(null, $info[1]);
        } else if (preg_match("/^lc (.+)$/i", $msg, $info)) {
            return $this->show_lc($info[1], null);
        } elseif (preg_match("/^lc$/i", $msg, $info)) {
            return $this->show_lc("--all--", null);
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
			$apiurl = $this->bot->core("settings")->get("LandControl", "ApiUrl");
			$mql = $ql+50;
			$secday = time() % 86400;
			$mint = $secday+3600;
			if($mint>86400) $mint = $mint-86400;
			$maxt = $secday+23400;
			if($maxt>86400) $maxt = $maxt-86400;
			$content = $this->bot->core("tools")->get_site($apiurl."?limit=50&faction=".$side."&min_ql=".$ql."&max_ql=".$mql."&min_close_time=".$mint."&max_close_time=".$maxt);
			if (!($content instanceof BotError)) {
				if (strpos($content, '{"count":') !== false) {
					$datas = json_decode($content);
					$count = count($datas->results);
					$return .= $count." ".$side." field(s) found as hot now or soon :";
					$return .= $this->format($content);
				}
			}
		}
		return $this->bot->core("tools")
                ->make_blob("Hot Notum Fields", $return."<br><br>*: Hot/Cold states provided by Tyrence's API courtesy of Unk & Draex");		
	}

	
    function show_lc($iarea = null, $ql = null)
    {
		$return = "";
        if ($iarea == "--all--") {
            $areas = $this->bot->db->select(
                "select distinct(area),count(area) from #___land_control_zones where id < 264 group by area"
            );
            if (!empty($areas)) {
                $return .= "<div align=center><u><font color=#10a5e5>Land Control Areas</font></u></div>";
                foreach ($areas as $area) {
                    $return .= $this->bot->core("tools")
                            ->chatcmd("lc " . $area[0], $area[0]) . " (" . $area[1] . ")<br>";
                }
                return $this->bot->core("tools")
                    ->make_blob("Land Control Areas", $return);
            } else {
                return "No matches";
            }
		} else {
			$search = "?limit=50";
			if($iarea!=NULL) {
                $area = $this->bot->db->select(
                    "select zoneid from #___land_control_zones where area like '%" . $iarea . "%' LIMIT 1"
                );			
				if(isset($area[0][0])&&is_numeric($area[0][0])&&$area[0][0]>0) {
					$search .= "&playfield_id=".$area[0][0];
				}
			} elseif(is_numeric($ql)) {
				if($ql==0) $ql = 1;
				$mql = $ql+10;
				$search .= "&min_ql=".$ql."&max_ql=".$mql;
			}
			$apiurl = $this->bot->core("settings")->get("LandControl", "ApiUrl");
			$content = $this->bot->core("tools")->get_site($apiurl.$search);
			if (!($content instanceof BotError)) {
				if (strpos($content, '{"count":') !== false) {
					$datas = json_decode($content);
					$count = count($datas->results);
					$return .= $count." all-side field(s) found :";
					$return .= $this->format($content);
				}
			}
		}
		return $this->bot->core("tools")
                ->make_blob("Land Control Areas", $return."<br><br>*: Hot/Cold states provided by Tyrence's API courtesy of Unk & Draex");			
    }

	
	function format($content)
	{
		$datas = json_decode($content);
		$return = "";
		$secday = time() % 86400;
		foreach($datas->results AS $result) {				
			if ($result->faction == "Omni") { $color = "aqua"; }
			elseif ($result->faction == "Clan") { $color = "orange"; }
			else { $color = "gray"; }		
			$state = "Unknown (?) current state ...";		
			if($result->penalty_duration>0) $penal = $result->penalty_until - time();
			else $penal = 0;
			if($penal<0) $penal = 0;
			$diff = $result->close_time - $secday;
			if ($diff<0) $diff = $diff+86400;
			if ($diff<=3600&&$diff>$penal) {
				$state = "##yellow##CLOSING##end## (5%) off in ".$this->times($diff)." m";
			} elseif ($diff<=21600&&$diff>$penal) {
				$state = "##green##OPENED##end## (25%) off in ".$this->times($diff)." m";
			} elseif ($penal>0) {
				$state = "##yellow##PENALIZED##end## (25%) off in ".$this->times($penal)." m";
			} else {
				$upin = $diff-21600;				
				$state = "##red##CLOSED##end## (75%) up in ".$this->times($upin)." m";
			}
			$return .= "<br><br>".$result->playfield_short_name." ".$result->site_number."x"
					. "<br> Range: " . $result->min_ql . "-" . $result->max_ql
					. "<br> Coord: " . $this->coords($result->x_coord,$result->y_coord,$result->playfield_id,$result->site_name)
					. "<br> Infos: " . "QL ".$result->ql." CT of ".$result->faction." ##".$color."##".$result->org_name."##end##"
					. "<br> State*: " . $state;
		}	
		return $return;
	}

	
    function times($sec)
    {
		$min = ceil($sec/60);
		if($min>60) {
			$hour = floor($sec/3600);
			$rest = $sec-($hour*3600);
			$min = $hour." h ".ceil($rest/60);
		}
		return $min;
    }	

	
    function coords($x,$y,$zid,$name)
    {
        return $this->bot->core("tools")->chatcmd($x . " " . $y . " " . $zid, $name, "waypoint");
    }
	
}

?>