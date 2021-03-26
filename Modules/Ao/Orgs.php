<?php
/*
* Orgs.php - Module to get and Manage Org List.
*
* Made by Temar
* - Coded for Bebot 0.5 (SVN)
* You are Free to Improve and Change as Long as My name Stays on it
*
* 2021 updated by Bitnykk for bebot 0.7.x
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
*
* Add a "_" at the beginning of the file (_ClassName.php) if you do not want it to be loaded.
*/

/*
* This Module will ONLY get Org names/ids from any source (Funcom by default)
* It comes in complement of OnlineOrg.php that checks for online presence
*/

$orgs = new Orgs($bot);

class Orgs extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->bot->core("settings")->create("Orgs", "LastGetOrgs", 0, "Mark last time !getorgs was done.", NULL, TRUE, 1);
		$this->bot->core("settings")->create("Orgs", "GetOrgs", 14, "How often in Days should bot run GetOrgs (0 to disable) ?", "0;1;3;5;7;10;14;21;28");
		$this->bot->core("settings")->create("Orgs", "OrgsUrl", "http://people.anarchy-online.com/people/lookup/orgs.html", "What is HTTP(s) Org interface  URL (FC official by default, or your prefered mirror) ?");
		$this->update_table();
		$this->letters = array ('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'others');
		$this -> help['description'] = 'Update cache & Search for Orgs. May load massive external XML especially at first run, slowing down the bot if used also in org/raid context. Abuse of this tool might get you limited at Funcom XML access.';
		$this->register_command('all', 'getorgs', 'SUPERADMIN');
		$this -> register_alias("getorgs", "orgs");
		$this->help['command']['getorgs'] = "Force manual update of the local Orgs cache.";
		$this->register_command('all', 'searchorg', 'GUEST');
		$this -> register_alias("searchorg", "orgsearch");
		$this -> register_alias("searchorg", "searchorgs");
		$this->help['command']['searchorg <keyword(s)>'] = "Search for an Org in local Orgs cache.";
		$this->debug = false; // true for beta ; false for release
		if ($this->debug)
			$this->register_event("cron", "1min");
		else
			$this->register_event("cron", "1hour");
	}
	
	function update_table()
	{
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("orgs", "false") . "
					(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					 dim INT NOT NULL,
					 org_id INT NOT NULL,
					 org VARCHAR(50),
					 members INT default 0,
					 faction VARCHAR(7) NULL,
					 whois_update INT(11) DEFAULT '0',
				     last_update INT(11) DEFAULT '0',
					 UNIQUE index (dim, org_id))"); 

		if($this->bot->core("settings")->exists("Orgs", "Schemaversion"))
		{
			$this->bot->db->set_version("orgs",
			$this->bot->core("settings")->get("Orgs", "Schemaversion"));
			$this->bot->core("settings")->del("Orgs", "Schemaversion");
		}
	
		if ($this->bot->db->get_version("orgs") == 3)
			return;
			
		switch ($this->bot->db->get_version("orgs"))
		{
			case 1:
				$this->bot->db->update_table("orgs", "whois_update", "add", "ALTER IGNORE TABLE #___orgs ADD `whois_update` INT default '0' AFTER `org`");
				$this->bot->db->update_table("orgs", "dim", "add", "ALTER IGNORE TABLE #___orgs ADD `dim` INT default '0' AFTER `id`");
				$this->bot->db->update_table("orgs", "members", "add", "ALTER IGNORE TABLE #___orgs ADD `members` INT default '0' AFTER `org`");
				$this->bot->db->update_table("orgs", "faction", "add", "ALTER IGNORE TABLE #___orgs ADD `faction` VARCHAR(7) AFTER `members`");
			case 2:
				$this->bot->db->update_table("orgs", "org_id", "alter", "alter table `#___orgs` DROP index org_id");
				$this->bot->db->update_table("orgs", array("dim", "org_id"), "alter", "alter table `#___orgs` ADD UNIQUE index (dim, org_id)");
		}
		
		$this->bot->db->set_version("orgs", 3);
	}

	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^getorgs$/i", $msg))
			return ($this->get_orgs($origin, $name));
		elseif (preg_match("/^searchorg (.+)$/i", $msg))
			return $this -> search_org($name, $msg, $origin);
		elseif (preg_match("/^searchorg$/i", $msg))	
			$this->bot->send_help($name);
		else
			return ("##error##Error : Broken Orgs plugin, received unhandled command: ".$msg."##end##");
	}
	
	function get_orgs($origin, $name)
	{ 	
		if ($this->debug) echo " orgs ";
		if(isset($this->active) && $this->active)
		{
			Return "Please wait : GetOrgs is already running!";
		}
		$this->added = 0; $this->updated = 0; $this->updatedm = 0; $this->checked = 0;
		$msg = "Getting Org names & ids from Funcom :: MAY TAKE AWHILE so please Wait ... ";
		$this->bot->send_output($name, $msg, $origin);
		$this->next = 0; $this->active = true;
		$this->info = array($name, $origin);
		if ($this->debug) echo " ... ";
		$this->get_orgs_letter();
	}

	function get_orgs_letter()
	{
		$this->geterror = false;
		if ($this->debug) echo " letter ";
		$letter = $this->letters[$this->next];
		if ($this->debug) echo " (".$letter.") ";
		if($letter == "others")
		{
			$sql = "";
			foreach($this->letters as $l)
				if($l != "others")
				{
					$sql .= "org NOT LIKE '".$l."%'";
					if($l != "z")
						$sql .= " AND ";
				}
		}
		else
			$sql = "org LIKE '".$letter."%'";
		$alreadyin = $this->bot->db->select("SELECT dim, org_id, org, members FROM #___orgs WHERE ".$sql);
		if (!empty($alreadyin))
		{
			foreach ($alreadyin as $org)
				$in[$org[0]][$org[1]] = array ($org[2], $org[3]);
		}
		$result = $this->get_orgs_funcom($letter);
		if(!is_array($result)) {
			if ($this->debug) echo " noresult ";
			$this->geterror = true;
		} elseif (!empty($result)) {
			if ($this->debug) echo " results ";
			$updatetime = time();
			foreach ($result as $ID => $org)
			{
				$this->checked++;
				if (isset($in[$org[3]][$ID]))
				{
					$update[$ID] = array ($org[0], $org[1], $in[$org[3]][$ID][0], $in[$org[3]][$ID][1], $org[3]);
					unset($in[$ID]);
				}
				else
				{
					$dcheck = $this->bot->db->select("SELECT dim, org_id, org, members FROM #___orgs WHERE dim = ".$org[3]." AND org_id = ".$ID);
					if(!empty($dcheck))
					{
						$update[$ID] = array ($org[0], $org[1], $dcheck[0][2], $dcheck[0][3], $org[3]);
					}
					else
						$insert[$ID] = $org;
				}
			}
			if ($this->debug) echo " updates ";
			if (!empty($update))
				foreach ($update as $key => $value)
				{
					if ($value[0] !== $value[2])
					{
						$this->bot->db->query("UPDATE #___orgs SET org = '".mysqli_real_escape_string($this->bot->db->CONN,$value[0])."', members = ".$value[1].", last_update = ".$updatetime." WHERE org_id = ".$key." AND dim = ".$value[4]);
						$this->updated++;
						if ($value[1] !== $value[3])
							$this->updatedm++;
					}
					elseif ($value[1] !== $value[3])
					{
						$this->bot->db->query("UPDATE #___orgs SET members = ".$value[1].", last_update = ".$updatetime." WHERE org_id = ".$key." AND dim = ".$value[4]);
						$this->updatedm++;
					}
				}
			if ($this->debug) echo " inserts ";
			if (!empty($insert))
			{
				foreach ($insert as $key => $value)
				{
					$this->bot->db->query("INSERT INTO #___orgs (dim, org_id, org, last_update, members, faction) VALUES ('".mysqli_real_escape_string($this->bot->db->CONN,$value[3])."', '".$key."', '".mysqli_real_escape_string($this->bot->db->CONN,$value[0])."', ".time().", ".mysqli_real_escape_string($this->bot->db->CONN,$value[1]).", '".mysqli_real_escape_string($this->bot->db->CONN,$value[2])."')");
					$this->added++;
				}
			}
			unset($insert, $update);
			$this->bot->log("ORGS", "NOTICE", "Finished updating letter : ".$letter);
		}
		if($this->geterror) {
			$this->bot->log("ORGS", "ERROR", "Stop on error : incorrect datas from provided Orgs Url!");
			$this->active = false;
		} elseif($this->next < 26) {
			$this->next++;
			$this->get_orgs_letter();
		} else {	
			$this->get_orgs_done();
		}
	}

	function get_orgs_done()
	{   
		if ($this->debug) echo " done ";	
		if ($this->checked > 0)
		{
			$inside = "  ::: Org list Update :::\n\n";
			$inside .=  "Orgs Checked :: ".$this->checked."\nOrgs Added :: ".$this->added."\nOrgs Updated :: ".$this->updated."\nMember Counts Updated :: ".$this->updatedm."\n";
			$msg = "Org Name Search Complete :: ".$this->bot->core("tools")->make_blob("click to view", $inside);
			$this->bot->send_output($this->info[0], $msg, $this->info[1]);
			$this->bot->core("settings")->save("Orgs", "LastGetOrgs", time());
		}
		else {
			$this->bot->send_output($this->info[0], "Org Name Search Failed.", $this->info[1]);
			$this->bot->log("ORGS", "ERROR", "Org Name Search Failed.");
		}
		unset($this->active, $this->next, $this->checked, $this->added, $this->updated, $this->updatedm);
	}
	
	function get_orgs_funcom($letter)
	{   
		if ($this->debug) echo " gofc ";
		if (substr($this->bot->core("settings")->get("Orgs", "OrgsUrl"),0,4) != "http") return "Error in the Orgs interface URL ... ";
		$dim = $this->bot->dimension;
		$getorgs = $this->bot->core("tools")->get_site($this->bot->core("settings")->get("Orgs", "OrgsUrl")."?l=".$letter."&dim=".$dim);
		if (!($getorgs instanceof BotError) && strpos($getorgs, '<tbody>') !== false) {
			$getorgs = str_replace("\n", "", str_replace("\r", "", $getorgs));
			while (strpos($getorgs, '  ') !== false) $getorgs = str_replace("  ", "", $getorgs);
			$getorgs = explode('<tbody>', $getorgs);			
			$getorgs = explode('</tbody>', $getorgs[1]);
			$getorgs = explode('<tr>', $getorgs[0]);
			$orgs = array ();
			foreach ($getorgs as $org)
			{
				if (strpos($org, '/d/') !== false) {
					if ($this->debug) echo " org ";
					$tmp = explode('/d/', $org);
					$tmp = explode('/name/', $tmp[1]);
					$d = $tmp[0];
					$tmp = explode('"> ', $tmp[1]);
					$id = $tmp[0]; 
					if ($this->debug) echo " id : ".$id." / ";
					$tmp = explode('</a>', $tmp[1]);
					$orgname = $tmp[0];
					if ($this->debug) echo " orgname : ".$orgname." / ";
					$tmp = explode('<td align="right">', $tmp[1], 2);
					$tmp = explode('</td>', $tmp[1], 2);
					$members = $tmp[0];
					if ($this->debug) echo " members : ".$members." / ";
					$tmp = explode('<td align="left">', $tmp[1]);
					$tmp = explode('</td>', $tmp[1]);
					$faction = $tmp[0];
					if ($this->debug) echo " faction : ".$faction." / ";
					if ($this->debug) usleep(5000);					
					if ($members !== "<span>-</span>")
						if ($d == $this->bot->dimension)
							if ($id !== 0)
								if (is_numeric($id))
									$orgs[$id] = array ($orgname, $members, $faction, $d);
				}					
			}
			return $orgs;
		} else {
			$this->bot->log("ORGS", "ERROR", "Content error in : ".substr($getorgs,0,255)."...");
			return "Content error in : ".substr($getorgs,0,255)."...";	
		}
	}

	function cron()
	{
	    if ($this->bot->core("settings")->get ("Orgs", "GetOrgs") !== 0)
		{
			if ($this->bot->core("settings")->get ("Orgs", "LastGetOrgs") + ($this->bot->core("settings")->get ("Orgs", "GetOrgs") * 24 * 60 * 60) < time()) {
				$this->get_orgs("both", "Automated");
			} else {
				if ($this->debug) $this->bot->log("ORGS", "DEBUG", "Orgs update stopped as cache isn't ".$this->bot->core("settings")->get ("Orgs", "GetOrgs")." day(s) old yet.");
			}
		} else {
			if ($this->debug) $this->bot->log("ORGS", "DEBUG", "Orgs update disabled (day set at 0) ; enable it under !settings orgs");
		}
	}
	
	function search_org($name, $msg, $origin)
	{   
		$msg = substr($msg,10); $list = array();
		$dim = $this->bot->dimension;		
		if ($this->debug) echo "\n".$name." search_org (RK".$dim.") : ".$msg." -> ".$origin."\n";		
		$words = explode(' ', $msg); $orglike = "";
		foreach ($words as $word) { $orglike = "org LIKE '%" . mysqli_real_escape_string($this->bot->db->CONN,$word) . "%' OR ";  }
		$results = $this->bot->db->select("SELECT org_id, org FROM #___orgs WHERE dim = ".$dim." AND (" . $orglike . "org LIKE '%" . mysqli_real_escape_string($this->bot->db->CONN,$msg) . "%') AND org_id !=0");
		$countres = count($results);
		if (!empty($results))
		{
			if ($this->debug) echo "Found ".$countres." Org(s) matching:\n";
			foreach ($results as $org)
			{
				$id = $org[0];
				$orgname = $org[1];
				$list[$id] = $orgname;
				if ($this->debug) echo "[name:".$orgname.", ";
				if ($this->debug) echo "id:".$id."] ";
			}
		}
		$countl = count($list); $sent = ""; $blob = "";
		if($countl>0) {
			foreach($list as $id => $orgname)
			{
				$blob .= $this->bot->core("tools")->chatcmd("orglist ".$id, $orgname)."\n";
			}
			$sent = $countl." result(s) found : ".$this->bot->core("tools")->make_blob("click to see", $blob);
		} else {
			$sent = "No org found with submitted keyword(s).";
		}
		$this->bot->send_output($name, $sent, $origin);
	}
	
}
?>