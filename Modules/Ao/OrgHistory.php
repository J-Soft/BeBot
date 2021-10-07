<?php
/*
* OrgHistory.php - Handle Org History. Formatted for full compatibility with Nadyita's module.
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
$orgHistory = new OrgHistory($bot);
/*
The Class itself...
*/
class OrgHistory extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("org_history", "true") . "
				(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				actor VARCHAR(50),
				action VARCHAR(10),
				actee VARCHAR(50),
				organization INT,
				time INT)"
        );
        $this->register_command("all", "orghistory", "GUEST");
		//if ($bot->guildbot) {
			$this->register_event("gmsg", "org");
			$this->register_event("gmsg", "Org Msg");
		//}
        $this->bot->core("settings")
            ->create("OrgHistory", "AlertDisc", false, "Do we alert Discord of Org Events ?");
        $this->bot->core("settings")
            ->create("OrgHistory", "DiscChanId", "", "What Discord ChannelId in case we separate Org Events from main Discord channel (leave empty for all in main channel) ?");
        $this->bot->core("settings")
            ->create("OrgHistory", "AlertIrc", false, "Do we alert Irc of Org Events ?");			
        $this->help['description'] = 'Handle org events history.';
        $this->help['command']['orghistory'] = "Shows all org events history with pagination.";
        $this->help['command']['orghistory [keyword]'] = "Searches for org events history.";
    }

    function command_handler($name, $msg, $channel)
    {
        if (preg_match("/^orghistory (\d+)$/i", $msg, $info)) {
                return $this->history_blob($info[1], null);
        } elseif (preg_match("/^orghistory (\d+) (.+)$/i", $msg, $info)) {
                return $this->history_blob($info[1], $info[2]);
        } elseif (preg_match("/^orghistory (.+)$/i", $msg, $info)) {
                return $this->history_blob(0, $info[1]);
        } else {
			return $this->history_blob(0, null);
		}
    }


    /*
    Makes the history results
    */
    function history_blob($skip = 0, $string = null)
    {
        $blob = "##blob_title##:::: Org Event History Results ::::##end##\n\n";
		if ( $skip == '' || !is_numeric($skip) ) { $skip = 0; }
		$pager = 20; $range = $skip+$pager;		
		$total = $this->bot->db->select("SELECT COUNT(*) FROM #___org_history");
		if($range>$total[0][0]) { $range = $total[0][0]; }
		$where = "";
		$kword = "";
		$link = "";
		if($string) {
			$kword = $string;
			$link = " ".$kword;
			$where = " WHERE (actor='".$kword."' OR action='".$kword."' OR actee='".$kword."')";
		}
        $result = $this->bot->db->select(
            "SELECT * FROM #___org_history".$where." ORDER BY time DESC LIMIT ".$skip.", ".$pager , MYSQLI_ASSOC
        );
        if (empty($result)) {
            return "No Org Event History found!";
        }
        foreach ($result as $res) {
			if($res['actee']==$res['actor']) { $by = ""; } else { $by = " by ".$res['actor']; }
            $blob .= date("Y-m-d H:i", $timestamp)." : ".$res['actee']." ".$res['action'].$by." (OrgId=".$res['organization'].")\n";
        }
		$back = $skip-$pager;
		if($back>=0) {
			$blob .= " ".$this->bot->core("tools")->chatcmd("orghistory ".$back.$link, "Back")." ";
		}		
		if($range<$total[0][0]) {
			$blob .= " ".$this->bot->core("tools")->chatcmd("orghistory ".$range.$link, "Next")." ";
		}
		$first = $skip+1;		
        return "Org Event History: ".$first."-".$range." / ".$total[0][0] ." ". $this->bot->core("tools")
            ->make_blob("click to view", $blob);
    }

    /*
    This gets called to relay spotted messages below
    */
    function relay_msg($msg)
	{
		if ($this->bot->exists_module("discord")&&$this->bot->core("settings")->get("OrgHistory", "AlertDisc")) {
			if($this->bot->core("settings")->get("OrgHistory", "DiscChanId")) { $chan = $this->bot->core("settings")->get("OrgHistory", "DiscChanId"); } else { $chan = ""; }
			$this->bot->core("discord")->disc_alert($msg, $chan);
		}
		if ($this->bot->exists_module("irc")&&$this->bot->core("settings")->get("OrgHistory", "AlertIrc")) {
			$this->bot->core("irc")->send_irc("", "", $msg);
		}		
	}

    /*
    This gets called on a msg in the group
    */
    function gmsg($name, $group, $msg)
    {
echo " // DEBUG: ".$group.":".$msg." // ";		
		$record = false;
        if (preg_match(
            "/(.+) has left the organization./i",
            $msg,
            $info
        )
        ) {
			$this->relay_msg($msg);
            $infos["actor"] = $info[1];
            $infos["action"] = "left";
            $infos["actee"] = $info[1];
            $record = true;
        } else {
            if (preg_match(
                "/(.+) kicked (.+) from the organization./i",
                $msg,
                $info
            )
            ) {
				$this->relay_msg($msg);
				$infos["actor"] = $info[1];
				$infos["action"] = "kicked";
				$infos["actee"] = $info[2];
                $record = true;
            } else {
                if (preg_match(
                    "/(.+) invited (.+) to your organization./i",
                    $msg,
                    $info
                )
                ) {
					$this->relay_msg($msg);
					$infos["actor"] = $info[1];
					$infos["action"] = "invited";
					$infos["actee"] = $info[2];
					$record = true;
                } else {
                    if (preg_match(
                        "/(.+) removed inactive character (.+) from your organization./i",
                        $msg,
                        $info
                    )
                    ) {
						$this->relay_msg($msg);
						$infos["actor"] = $info[1];
						$infos["action"] = "removed";
						$infos["actee"] = $info[2];
						$record = true;
                    }
                }
            }
        }
        if ($record) {
            $infos["time"] = time();
            $infos["organization"] = $this->bot->guild_id;
			$this->bot->db->query(
				"INSERT INTO #___org_history (actor, action, actee, organization, time) VALUES (
				'" . mysqli_real_escape_string($this->bot->db->CONN,
					$infos["actor"]
				) . "', '" . $infos["action"] . "',
				'" . mysqli_real_escape_string($this->bot->db->CONN,
					$infos["actee"]
				) . "', " . $infos["organization"] . ", '"
				. $infos["time"] . "')"
			);
        }
    }

}

?>
