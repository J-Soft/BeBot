<?php
/*
* Database of the land control zones, based on code by Wolfbiter, modified by Pharexys.
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
$landcontrol = new LandControlZones($bot);
class LandControlZones extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("land_control_zones", "true") . " (
			`id` int(11) default NULL,
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
                    "ALTER IGNORE TABLE #___land_control_zones ADD short VARCHAR(5) DEFAULT NULL"
                );
                $this->bot->db->update_table(
                    "land_control_zones",
                    "zoneid",
                    "add",
                    "ALTER IGNORE TABLE #___land_control_zones ADD zoneid INT(11) DEFAULT NULL"
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
        }
        return false;
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
                            "select id, lrange, hrange, area, huge, x, y, name from #___land_control_zones where area='" . $area[0] . "' AND lrange<=" . $lrange . " AND hrange>="
                            . $hrange . " order by huge"
                        );
                    } else {
                        $lcs = $this->bot->db->select(
                            "select id, lrange, hrange, area, huge, x, y, name from #___land_control_zones where area='" . $area[0] . "' AND lrange>=" . $lrange . " AND hrange<="
                            . $hrange . " order by huge"
                        );
                    }
                    if (!empty($lcs)) {
                        foreach ($lcs as $lc) {
                            $temp .= " Area: " . $lc[7] . "<br> Range: " . $this->conv($lc[1]) . "-" . $this->conv(
                                    $lc[2]
                                ) . "<br> Coords: " . $this->coords($lc[5]) . "x"
                                . $this->coords($lc[6]) . "<br> Hugemap: " . $lc[4] . "<br><br>";
                        }
                        $return .= $temp;
                    }
                }
                return $this->bot->core("tools")
                    ->make_blob("Land Control Areas", $return);
            } else {
                return "No matches";
            }
        }
    }


    function conv($num)
    {
        if (strlen($num) < 2) {
            return $num;
        } elseif (strlen($num) < 3) {
            return $num;
        } else {
            return $num;
        }
    }


    function coords($num)
    {
        if (strlen($num) < 2) {
            return $num;
        } elseif (strlen($num) < 3) {
            return $num;
        } elseif (strlen($num) < 4) {
            return $num;
        } else {
            return $num;
        }
    }
}

?>
