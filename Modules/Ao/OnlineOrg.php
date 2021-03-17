<?php
/*
* OnlineOrg.php - Module to get the Online List of Another Org.
*
* Made by Temar
* - Coded for Bebot 0.5 (SVN)
* You are Free to Improve and Change as Long as My name Stays on it
*
* 2021 updated by Bitnykk for bebot 0.7.x
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
*
* Add a "_" at the beginning of the file (_ClassName.php) if you do not want it to be loaded.
*
*/

/*
* This Module will ONLY get Org Online List(names, NOT alts etc)
* It comes in complement of Orgs.php that updates/searches for org names/ids
*/

$onlineorg = new OnlineOrg($bot);

class OnlineOrg extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));

		$this -> bot = &$bot;
		$this -> version = 7.0; // Module Version
		
		$this -> bot -> core("settings") -> create("OnlineOrg", "cache", 1, "How many rosters should bot cache before being restarted?", "1;3;5;10");
		$this -> bot -> core("settings") -> create("OnlineOrg", "delay", 2, "How long should we let the check autorun (1 for fiber, 2 for average DSL, 3 for slower net)?", "1;2;3");
		$this -> bot -> core("settings") -> create("OnlineOrg", "usewhois", 10, "How many Days should whois cache be used instead of XML after last XML?", "1;3;5;7;10;20;30");
		$this -> bot -> core("settings") -> create("OnlineOrg", "SortBy", "Rank", "Which sorting mode should be used in the online display?", "Rank;Level;Profession;Name");
		$this->bot->core("settings")->create("OnlineOrg", "XmlUrl", "http://people.anarchy-online.com", "What is HTTP(s) XML interface  URL (FC official by default, or your prefered mirror) ?");

		$this -> debug = false; // true for testing purposes ; false for release lesser verbosity
		$this -> running = false;
		$this -> org_cache = array();
		$this -> limit = 0;
		$this -> delay = 0;
		$this -> online = array();
		$this -> tempbud = array();
		$this -> pending = false;
		$this -> updating = false;
		$this -> name = "";
		$this -> origin = "";
		$this -> id = "";
		$this -> register_event("buddy");
		
		$this -> help['description'] = 'Check online presence in Org. May load massive external XML especially at first run on big Org roster, slowing down the bot if used also in org/raid context. Abuse of this tool might get you limited at Funcom XML access.';
		$this->register_command('all', 'olpurge', 'SUPERADMIN');
		$this->help['command']['olpurge'] = "Purge Org list cache (for admin only).";
		$this->register_command('all', 'olcache', 'SUPERADMIN');
		$this->help['command']['olcache'] = "Print Org list cache (for admin only).";
		$this->register_command('all', 'olstop', 'SUPERADMIN');
		$this->help['command']['olstop'] = "Stop Org list check (for admin only).";		
		$this -> register_command('all', 'orglist', 'LEADER');
		$this -> register_alias("orglist", "orgcheck");
		$this -> register_alias("orglist", "onlineorg");
		$this->help['command']['orglist <id>'] = "Check online presence on org of given id.";
	}

	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^orglist ([0-9]+)$/i", $msg, $match))
			return $this -> main($name, $match[1], $origin);
		elseif (preg_match("/olpurge/i", $msg))	
			return $this -> purge_cache();
		elseif (preg_match("/olcache/i", $msg))	
			return $this -> show_cache($name,$origin);
		elseif (preg_match("/olstop/i", $msg))	
			return $this -> close($name,$origin);				
		elseif (preg_match("/orglist/i", $msg))	
			$this -> bot -> send_help($name);
		else
			return ("##error##Error : Broken Orgs plugin, received unhandled command: ".$msg."##end##");
	}	
	
	function main($name,$id,$origin)
	{
		if(isset($this->running) && $this->running)
		{
			Return "Please wait : Orglist is already running!";
		}
		if ($this -> debug) echo " ".$name." : ".$id." -> ".$origin." ";
		$this->running = true;
		$this -> id = $id;
		$this -> name = $name;
		$this -> origin = $origin;
		$this->clean_cache();
		$this->bot->send_output($name, "First loading Org list member(s) ...", $origin);
		$list = $this -> get_list();
		if($list) {
			$this->bot->send_output($name, "Now checking Org list known on/off ...", $origin);
			$check = $this->check_list();
			if(!$check) {
				Return "Error, couldn't check for online presence among generated list!";
				$this->running = false;
			}
		} else {
			$this->running = false;
			Return "Error, couldn't generate Orglist from neither local cache, nor local db, nor distant XML!";
		}
	}
	
	function show_cache($name,$origin)
	{
		print_r($this -> org_cache);
		print_r($this -> online);
		$this -> online_list($name,$origin);
	}	
	
	function purge_cache()
	{
		$this -> org_cache = array();
		$this -> online = array();
		return "Orglist cache(s) purged (as if bot had restarted).";
	}
	
	function clean_cache()
	{
		if ($this -> debug) echo "check cache id -> ".$this -> id."\n";
		$maxcache = $this -> bot -> core("settings") -> get("OnlineOrg", "cache");
		if(!empty($this -> org_cache))
		{
			$cachecount = count($this -> org_cache);
			if (isset($this -> org_cache[$this -> id])) $cachecount--;
			if ($this -> debug) echo "count0: ".$cachecount."\n";
			$oldestt = time();
			while($cachecount >= $maxcache)
			{
				foreach($this -> org_cache as $orgcache)
				{
					if (($orgcache["time"] < $oldestt) && ($orgcache["id"] !== $this -> id))
					{
						$oldestt = $orgcache["time"];
						$oldestid = $orgcache["id"];
						if ($this -> debug) echo "oldestt = ".$oldestt." and oldestid = ".$oldestid."\n";
					}
				}
				if ($this -> debug) echo "unset: ".$oldestid."\n";
				unset($this -> org_cache[$oldestid]);
				$cachecount--;
				if ($this -> debug) echo "count: ".$cachecount."\n";
			}
		}
		$this->bot->log("ONLINEORG", "NOTICE", "Orglist cache cleaned.");
	}

	function get_list()
	{
		if ($this -> debug) echo "verif orgcache\n";
		$cachemembers = array();
		if(isset($this -> org_cache[$this -> id]["data"])) $cachemembers = $this -> org_cache[$this -> id]["data"];
		if (count($cachemembers) > 0)
		{
			$this->bot->log("ONLINEORG", "NOTICE", "Getting list from Org cache.");
			return $cachemembers;
		}
		else
		{
			return $this->parse_org();
		}
	}
	
	function parse_org()
	{
		$dim = $this->bot->dimension; $faction = ""; $orgname = ""; $members = array(); $verified = array(); $org = array(); $membercount = 0;
		if (empty($this -> id))
			Return ("Error ID Blank");
		$result = $this -> bot -> db -> select("SELECT whois_update FROM #___orgs WHERE dim = ".$dim." AND org_id = ".$this -> id);
		if (!empty($result))
		{
			if ($this -> debug) echo "last time org was whois cached : ".$result[0][0]."\n";
			$expire = $result[0][0] + (60 * 60 * 24 * $this -> bot -> core("settings") -> get("OnlineOrg", "usewhois"));
			if ($this -> debug) echo "expiration if ".$expire." <= ".time()."\n";
			if ($expire <= time())
			{
				$this->bot->log("ONLINEORG", "NOTICE", "Updating list from distant XML.");
				$usexml = true;
				$update = true;
			}
			else 
			{
				$usexml = false;
				$this->bot->log("ONLINEORG", "NOTICE", "Extracting list from Whois cache.");
			}
		} else
		{
			$this->bot->log("ONLINEORG", "NOTICE", "Obtaining list from distant XML.");
			$update = false;
			$usexml = true;
		}

		if ($usexml == true)
		{
			if (substr($this->bot->core("settings")->get("OnlineOrg", "XmlUrl"),0,4) == "http") {
				if ($this -> debug) echo $this->bot->core("settings")->get("OnlineOrg","XmlUrl")."/org/stats/d/$dim/name/".$this -> id."/basicstats.xml"."\n";
				$orgxml = $this -> bot -> core("tools") -> get_site($this->bot->core("settings")->get("OnlineOrg","XmlUrl")."/org/stats/d/$dim/name/".$this -> id."/basicstats.xml");
				if (strpos($orgxml, '<organization>') !== false) {
					$faction = $this -> bot -> core("tools") -> xmlparse($orgxml, "side");
					$orgname = $this -> bot -> core("tools") -> xmlparse($orgxml, "name");
					$org = explode("<member>", $this -> bot -> core("tools") -> xmlparse($orgxml, "members"));
				} else {
					$this->bot->log("ONLINEORG", "NOTICE", "Distant XML holds bad content.");
				}

				if (!empty($org))
				{
					if ($this -> debug) echo "Processing org XML!\n";
					for ($i = 1; $i < count($org); $i++)
					{
						$members[($i - 1)]["nickname"] = $this -> bot -> core("tools") -> xmlparse($org[$i], "nickname");
						if ($this -> debug) echo " XML ".$this -> bot -> core("tools") -> xmlparse($org[$i], "nickname")." /XML ";
						$members[($i - 1)]["firstname"] = $this -> bot -> core("tools") -> xmlparse($org[$i], "firstname");
						$members[($i - 1)]["lastname"] = $this -> bot -> core("tools") -> xmlparse($org[$i], "lastname");
						$members[($i - 1)]["level"] = $this -> bot -> core("tools") -> xmlparse($org[$i], "level");
						$members[($i - 1)]["gender"] = $this -> bot -> core("tools") -> xmlparse($org[$i], "gender");
						$members[($i - 1)]["breed"] = $this -> bot -> core("tools") -> xmlparse($org[$i], "breed");
						$members[($i - 1)]["faction"] = $faction;
						$members[($i - 1)]["profession"] = $this -> bot -> core("tools") -> xmlparse($org[$i], "profession");
						$members[($i - 1)]["at"] = $this -> bot -> core("tools") -> xmlparse($org[$i], "defender_rank");
						$members[($i - 1)]["at_id"] = $this -> bot -> core("tools") -> xmlparse($org[$i], "defender_rank_id");
						$members[($i - 1)]["org_id"] = $this -> id;
						$members[($i - 1)]["org"] = $orgname;
						$members[($i - 1)]["rank_id"] = $this -> bot -> core("tools") -> xmlparse($org[$i], "rank");
						$members[($i - 1)]["rank"] = $this -> bot -> core("tools") -> xmlparse($org[$i], "rank_name");
						$members[($i - 1)]["pictureurl"] = $this -> bot -> core("tools") -> xmlparse($org[$i], "photo_url");
						$members[($i - 1)]["id"] = $this -> bot -> core('player') -> id($members[($i - 1)]["nickname"]);
						$membercount++;
					}
					if ($this -> debug) echo "XML members => ".$membercount."!\n";
					if ($membercount >= 1)
					{
						foreach ($members as $member) {
							if($member["id"] instanceof BotError) {
								$membercount--;
							} else {
								$verified[] = $member;
								$this -> bot -> core("whois") -> update($member);
							}
						}
						if ($update)
							$this -> bot -> db -> query("UPDATE #___orgs SET members = ".$membercount.", org = '".mysqli_real_escape_string($this->bot->db->CONN,$orgname)."', whois_update = ".time()." WHERE dim = ".$dim." AND org_id = ".$this -> id);
						else
							$this -> bot -> db -> query("INSERT INTO #___orgs (dim, org_id, org, members, faction, whois_update) VALUES ('".$dim."', '".$this -> id."', '".mysqli_real_escape_string($this->bot->db->CONN,$orgname)."', '".$membercount."', '".$faction."', ".time().")");
						$this -> org_cache[$this -> id] = array("id" => $this -> id, "time" => time(), "orgname" => $orgname, "faction" => $faction, "membercount" => $membercount, "data" => $verified);
						return true;
					} else {
						$this->bot->log("ONLINEORG", "NOTICE", "Distant XML extraction is void.");
					}
				} else {
					$this->bot->log("ONLINEORG", "NOTICE", "Distant XML roster arrived empty.");
				}
			} else {
					$this->bot->log("ONLINEORG", "ERROR", "Wrong distant XML URL given.");
			}
		}
		
		if ($usexml == true) {
			$this->bot->log("ONLINEORG", "NOTICE", "Falling back on whois cache.");
			$members = array();
		}

		$db_members = $this -> bot -> db -> select("SELECT id, nickname, firstname,lastname,level,gender,breed,faction,profession,defender_rank,defender_rank_id,org_id,org_name,org_rank,org_rank_id,pictureurl FROM #___whois WHERE org_id = '" . $this -> id . "'");
		if (!empty($db_members))
		{
			foreach ($db_members as $key => $db_member)
			{
				$members[$key]["nickname"] = $db_member[1];
				if ($this -> debug) echo " DB ".$members[$key]["nickname"]." /DB ";
				$members[$key]["firstname"] = $db_member[2];
				$members[$key]["lastname"] = $db_member[3];
				$members[$key]["level"] = $db_member[4];
				$members[$key]["gender"] = $db_member[5];
				$members[$key]["breed"] = $db_member[6];
				$members[$key]["faction"] = $db_member[7];
				$members[$key]["profession"] = $db_member[8];
				$members[$key]["at"] = $db_member[9];
				$members[$key]["at_id"] = $db_member[10];
				$members[$key]["org_id"] = $db_member[11];
				$members[$key]["org"] = $db_member[12];
				$members[$key]["rank"] = $db_member[13];
				$members[$key]["rank_id"] = $db_member[14];
				$members[$key]["pictureurl"] = $db_member[15];
				$members[$key]["id"] = $this -> bot -> core('player') -> id($db_member[1]);
				if (empty($orgname)) $orgname = $db_member[12];
				if (empty($faction)) $faction = $db_member[7];
				$membercount++;
			}
			if ($this -> debug) echo "DB members => ".$membercount."!\n";
			if ($membercount >= 1) {
				$this -> org_cache[$this -> id] = array("id" => $this -> id, "time" => time(), "orgname" => $orgname, "faction" => $faction, "membercount" => $membercount, "data" => $members);
				return true;
			} else {
				$this->bot->log("ONLINEORG", "ERROR", "Empty extraction from whois cache.");
			}
		} else $this->bot->log("ONLINEORG", "NOTICE", "Nothing found neither in whois cache.");
	
		return false;
	}
	
	function check_list()
	{
		$this -> online = array(); $this -> tempbud = array(); $this -> pending = false;
		if ($this -> debug) echo "check orgcache\n";
		$cachemembers = array();
		if(isset($this -> org_cache[$this -> id]["data"])) $cachemembers = $this -> org_cache[$this -> id]["data"];
		$users = $this -> bot -> db -> select("SELECT * FROM #___users WHERE notify = 1");
		$buddies = count($users);
		if ($this -> debug) $this -> limit = 10; // 10 for testing purposes
		else $this -> limit = 950 - $buddies; // 950 for limit safety on production
		if ($this -> debug) echo "limit: ".$this -> limit."\n";
		if ($this -> debug) echo "cachemembers: ".count($cachemembers)."\n";		
		if ($this -> limit < 10) { // At least 10 slots free in buddylist or it's to slow/risky
			$this->bot->log("ONLINEORG", "ERROR", "Not enough space left in friendlist to perform check. Please free some & retry.");
			$this->close();
			return false;			
		} else {
			$this->bot->log("ONLINEORG", "NOTICE", "Starting online check of ".count($cachemembers)." member(s) over ".$buddies." buddies hence limit of ".$this->limit." ...");
		}
		if (count($cachemembers) > 0)
		{
			foreach ($cachemembers as $member)
			{
				if ($this -> debug) echo " cachemember ".$member["nickname"];
				if ($member["nickname"] == ucfirst(strtolower($this -> bot -> botname)))
				{
					if ($this -> debug) echo "= thisbot\n";
					$this -> online[$member["nickname"]] = "on";
				}
				else if ($this -> bot -> aoc -> buddy_exists($member["nickname"]))
				{
					if ($this -> debug) echo "= buddy";
					if ($this -> bot -> aoc -> buddy_online($member["nickname"]))
					{
						if ($this -> debug) echo " on";
						$this -> online[$member["nickname"]] = "on";
					} else {
						if ($this -> debug) echo " off";
						$this -> online[$member["nickname"]] = "off";
					}
					if ($this -> debug) echo "\n";
				} else {
					if ($this -> debug) echo "= tempbud ...\n";
					$this -> pending = true;
					$this -> online[$member["nickname"]] = "???";
					$this -> tempbud[$member["nickname"]] = $member["nickname"];
				}
			}
			if ($this -> pending) {
				$pref = $this->bot->core("settings")->get("OnlineOrg","delay");
				if($pref>2) { $delta = count($cachemembers)*2; }
				elseif($pref==2) { $delta = count($cachemembers); }
				elseif($pref==1) { $delta = ceil(count($cachemembers)/2); }
				else { $delta = 0; }
				$this -> delay = time() + $delta;
				$this->bot->log("ONLINEORG", "NOTICE", "Online check will now run for ".$delta." sec max ...");
				$this->bot->send_output($this->name, "Updating Org list unknown on/off ...", $this->origin);
				$this -> update_list();
			} else {
				$this->bot->log("ONLINEORG", "NOTICE", "Online check is already over!");
				$this -> close();
				$this -> online_list($this->name,$this->origin);
			}
			return true;
		}
		else
		{
			$this->bot->log("ONLINEORG", "ERROR", "Empty Org cache at check time.");
			$this -> close();
			return false;
		}
	}
	
	function update_list()
	{
		if ($this -> pending && count($this -> tempbud) > 0) {
			foreach($this -> tempbud as $key => $val) {
				if(count($this -> tempbud) < $this -> limit) {
					$this -> updating = true;
					unset($this -> tempbud[$key]);
					$this -> bot -> aoc -> buddy_add($key);
				}
			}
			$this -> updating = false;
		} elseif ($this -> pending) {
				$missing = false;
				foreach($this -> online as $key => $val) {
					if ($val == "???" && time() < $this -> delay) {
						$missing = true;
					}
				}
				if(!$missing) {
					$this -> close();
					$this -> online_list($this->name,$this->origin);
				}
		} else {
			$this -> close();
		}
	}
	
	function buddy($name, $msg)
	{
		if (isset($this -> online[$name]))
		{
			if ($msg == 1)
			{
				if ($this -> debug) echo "Onlined: ".$name."\n";
				$this -> online[$name] = "on";
			} else {
				if ($this -> debug) echo "Offlined: ".$name."\n";
				$this -> online[$name] = "off";
			}
			$this -> bot -> aoc -> buddy_remove($name);
			if($this -> pending && !$this -> updating) {
				$this -> update_list();
			}
		} else {
			if ($this -> debug) echo "External Buddy: ".$name." (".$msg.")\n";
		}
	}

	function online_list($name,$origin)
	{
		$this->bot->send_output($name, "Generating Org list on/off results ...", $origin);
		$sortby = $this->bot->core("settings")->get("OnlineOrg","SortBy");
		if ($this -> debug) echo "online_list (s.by: ".$sortby.")\n";
		
		if (!empty($this -> online) && !empty($this -> org_cache[$this -> id]))
		{
			$orgcache = $this -> org_cache[$this -> id]["data"];
			$orgname = $this -> org_cache[$this -> id]["orgname"];
			$faction = $this -> org_cache[$this -> id]["faction"];
			$orgcount = count($orgcache);
			$oncount = 0;
			$sortedorg = array();
			foreach($orgcache as $key => $player) {
				$array = array("nickname"=>$player["nickname"],"level"=>$player["level"],"rank_id"=>$player["rank_id"],"rank"=>$player["rank"],"profession"=>$player["profession"],"state"=>$this->online[$player["nickname"]]);
				switch ($sortby) {
					case "Level":
						$sortedorg[$player["level"]][] = $array;
						break;
					case "Profession":
						$sortedorg[$player["profession"]][] = $array;
						break;
					case "Name":
						$sortedorg[$player["nickname"]][] = $array;
						break;				
					default:
						$sortedorg[$player["rank_id"]][] = $array;
						break;
				}				
			}			
			switch ($sortby) {
				case "Level":
					krsort($sortedorg);
					break;
				case "Profession":
				case "Name":	
				default:
					ksort($sortedorg);
					break;
			}										
			if ($this -> debug) print_r($sortedorg);

			$blob = ""; $current = "";
			foreach ($sortedorg as $sorted)
			{
				foreach($sorted as $player) {
					if($player["state"]=="on") { $color = "##green##"; $oncount++; } elseif($player["state"]=="off") { $color = "##red##"; } else { $color = "##orange##"; }
					if($player["profession"]=="Adventurer") { $prof="Advy"; }
					elseif($player["profession"]=="Agent") { $prof="Agt"; }
					elseif($player["profession"]=="Bureaucrat") { $prof="Bur"; }
					elseif($player["profession"]=="Doctor") { $prof="Doc"; }
					elseif($player["profession"]=="Enforcer") { $prof="Enf"; }
					elseif($player["profession"]=="Engineer") { $prof="Eng"; }
					elseif($player["profession"]=="Fixer") { $prof="Fix"; }
					elseif($player["profession"]=="Keeper") { $prof="Kep"; }
					elseif($player["profession"]=="Martial Artist") { $prof="MA"; }
					elseif($player["profession"]=="Meta-Physicist") { $prof="MP"; }
					elseif($player["profession"]=="Nano-Technician") { $prof="NT"; }
					elseif($player["profession"]=="Shade") { $prof="Sha"; }
					elseif($player["profession"]=="Soldier") { $prof="Sol"; }
					elseif($player["profession"]=="Trader") { $prof="Tra"; }
					else { $prof="??"; }
					switch ($sortby) {
						case "Level":
							if($current != $player["level"])
							{
								$current = $player["level"];
								$blob .= "\n\n".$player["level"].":\n";
							}
							$blob .= $color.$player["nickname"]."##end## (".$this->bot->core("shortcuts")->get_short($player["rank"])." ".$prof.") | ";
							break;
						case "Profession":
							if($current != $player["profession"])
							{
								$current = $player["profession"];
								$blob .= "\n\n".$player["profession"].":\n";
							}
							$blob .= $color.$player["nickname"]."##end## (".$player["level"]." ".$this->bot->core("shortcuts")->get_short($player["rank"]).") | ";
							break;
						case "Name":
							$blob .= $color.$player["nickname"]."##end## (".$player["level"]." ".$this->bot->core("shortcuts")->get_short($player["rank"])." ".$prof.") | ";
							break;				
						default:
							if($current != $player["rank"])
							{
								$current = $player["rank"];
								$blob .= "\n\n".$player["rank"].":\n";
							}
							$blob .= $color.$player["nickname"]."##end## (".$player["level"]." ".$prof.") | ";
							break;
					}				
				}
			}
			
			$sent = $oncount." online among ".$faction." '".$orgname."' ".$orgcount." member(s) sorted by ".$sortby.": ".$this->bot->core("tools")->make_blob("Click to view", $blob);
			$this->bot->send_output($name, $sent, $origin);
			
		} else {
			$this->bot->log("ONLINEORG", "ERROR", "Missing informations to show online list!");
		}
	}
	
	function close($name="",$origin="")
	{
		if($this -> running || $this -> pending) {
			$this -> tempbud = array();
			$this -> updating = false;
			$this -> pending = false;
			$this -> running = false;
			$this -> limit = 0;
			$this -> delay = 0;
			if($name!=""&&$origin!="") {
				$this->bot->send_output($name, "Online check has been manually stopped.", $origin);
			} else {
				$this->bot->log("ONLINEORG", "NOTICE", "Online check has closed itself.");
			}
		}
	}		
	
}
?>