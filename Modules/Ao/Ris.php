<?php
/*
* Written by Zacix for BeBot & modified by Bitnykk
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2010 Thomas Juberg, ShadowRealm Creations and the BeBot development ri.
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
* See Credits file for all aknowledgements.
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
* File last changed at $LastChangedDate:  nov 2017 $
* Revision: $Id: Ris.php 1835 $
*/
require_once("Ris.inc");

$newris = new NewRis($bot);

/*
The Class itself...
*/
class NewRis extends BaseActiveModule
{
	var $pgroup;
	var $numris;
	var $ris;
	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));

		$this -> ris = array();
		$this -> numris = 0;
		$this -> pgroup = array(array());

        $this -> register_command("all", "showris", "GUEST");
        $this -> register_command("all", "startri", "LEADER");
        $this -> register_command("all", "clearris", "LEADER");
	$this -> register_alias('clearris', 'clearri');
	$this -> register_alias('clearris', 'riclear');
        $this -> register_command("all", "addri", "LEADER");
        $this -> register_command("all", "remri", "LEADER");
        $this -> register_command("all", "delri", "LEADER");
        $this -> register_command("all", "rileader", "LEADER");
        $this -> register_command("all", "riadmin", "LEADER");
        $this -> register_command("all", "riname", "LEADER");
        $this -> register_command("all", "risend", "LEADER");
	$this -> register_alias('risend', 'sendri');		
        $this -> register_command("all", "ritake", "GUEST");
		$this -> register_command("all", "riauto", "LEADER");
		$this -> register_command("all", "rialts", "LEADER");
	$this -> register_alias('rialts', 'rialt');		
		$this -> register_command("all", "richeck", "LEADER");
        $this -> register_command("all", "ritest", "OWNER");

        $this -> bot -> core("settings") -> create("Ris", "sendrate", 100000, "Microsecond (usleep) pause between each member manipulation (kick/invite)");
        $this -> bot -> core("settings") -> create("Ris", "sendbot", "", "Name of preparation bot allowed to send RI player list to be invited");
		$this -> bot -> core("settings") -> create("Ris", "ignlvl", "0", "Level under which toons are ignored in the module (0 by default)");

        $this -> register_event("pgjoin");
        $this -> register_event("pgleave");
	}
	
	function command_handler($name,$msg, $origin)
	{
		$msg = explode(" ",$msg);
		$msg[0] = strtolower($msg[0]);

		if($msg[0] == "ritake")
		{
			$this->TakeRi($name,$msg[1]);
		}
		elseif(strtolower($msg[0]) == "startri")
		{
			$this->StartRi($name,ucfirst(strtolower($msg[1])),implode(" ",array_slice($msg,2)));
		}
		else if($msg[0] == "showris")
		{
			$this->PrintRis();
		}
		else if($msg[0] == "addri")
		{
			$this->AddRiMember(ucfirst(strtolower($msg[2])),$msg[1],$name);
		}
		else if($msg[0] == "remri")
		{
			$this->DelRiMember(ucfirst(strtolower($msg[1])),$name,false);
		}
		else if($msg[0] == "delri")
		{
			$this->DelRi($msg[1],$name);
		}
		else if($msg[0] == "rileader")
		{
			$this->SetRiLeader(ucfirst(strtolower($msg[2])),$msg[1],$name);
		}
		else if($msg[0] == "riname")
		{
			$this->SetRiName(implode(" ",array_slice($msg,2)),$msg[1],$name);
		}
		else if($msg[0] == "clearris")
		{
			$this->ClearRis($name);
		}
		else if($msg[0] == "riadmin")
		{
			$this->GetAdminConsole($name);
		}
		else if($msg[0] == "risend")
		{
			$this->SendRi($msg[1],strtolower($msg[2]),$name);
		}
		else if($msg[0] == "riauto")
		{
			$this->AutoRi($name);
		}
		else if($msg[0] == "rialts")
		{
			$this->RiAlts($msg[1],$name);
		}		
		else if($msg[0] == "richeck")
		{
			$this->RiCheck($name);
		}			
		else if($msg[0] == "ritest")
		{
			echo " (1-DEBUG-1) "; print_r($this->ris); echo " (0-DEBUG-0) ";
			$this -> bot -> core("chat") -> pgroup_kick($msg[1]);
			$this->bot->send_tell($msg[2], "!invite ".$msg[1], 1, false, TRUE);
		}

		return false;
	}

	function SetRiLeader($name, $num, $executer)
	{
		if(preg_match("#^[1-9]#", $num))
		{
			if($num <= count($this -> ris) && $num > 0)
			{
				$ri = $this -> GetRi($num);
				$member = $ri -> GetMember($name);
				if($member)
				{
					$ri -> ClearLeader();
					$member -> SetLeader(true);
				$this->bot->send_tell($executer,"[##highlight##".$name ."##end##] is new leader of ri ##highlight##".$num."##end##");
				}
				else
				{
					$this -> bot -> send_tell($executer,"[##highlight##".$name."##end##] is not in ri ##highlight##".$num."##end##");
				}
			}
			else
			{
				$this -> bot -> send_tell($executer,"Ri ##highlight##".$num . "##end## does not exist");
			}
		}
		else
		{
			$this -> bot -> send_tell($executer,"Usage: !rileader &lt;number&gt; &lt;name&gt;");
		}
	}

	function RiAlts($prof, $name)
	{
		$return = ""; $inside = ""; $checklist = array(); $prof = strtolower(substr($prof,0,3));
		$profs = array("enf","doc","cra","bur","age","fix","eng","sol","kee","met","mp","nan","nt","tra","adv","mar","ma","sha");
		if(!in_array($prof,$profs)) {
			$return = "Wrong profession given.";
		} else {
			switch ($prof) {
				case "enf":
					$prof = "Enforcer";
					break;				
				case "doc":
					$prof = "Doctor";
					break;			
				case "cra":
				case "bur":
					$prof = "Bureaucrat";
					break;				
				case "age":
					$prof = "Agent";
					break;			
				case "fix":
					$prof = "Fixer";
					break;			
				case "eng":
					$prof = "Engineer";
					break;			
				case "sol":
					$prof = "Soldier";
					break;			
				case "kee":
					$prof = "Keeper";
					break;			
				case "met":
				case "mp":
					$prof = "Meta-Physicist";
					break;					
				case "nan":
				case "nt":
					$prof = "Nano-Technician";
					break;					
				case "tra":
					$prof = "Trader";
					break;			
				case "adv":
					$prof = "Adventurer";
					break;				
				case "mar":
				case "ma":
					$prof = "Martial Artist";
					break;					
				case "sha":
					$prof = "Shade";
					break;					
			}
			if(count($this->pgroup)>1) {
				foreach($this->pgroup as $index=>$array) {
					if(count($array)>0) {
						$toon = $array[0];
						$result = $this -> bot -> core("whois") -> lookup($toon);
						if($toon!=$name&&$prof!=$result["profession"]) $checklist[$toon] = array();
					}
				}
				foreach($checklist as $toon=>$state) {
					$found = false;
					$main = $this->bot->core("alts")->main($toon);
					if($main!=$toon&&$main!=$name) {
						$result = $this -> bot -> core("whois") -> lookup($main);
						if(!($result instanceof BotError)&&$prof==$result["profession"]) {
							$checklist[$toon][] = array($main=>$result["level"]);
							$found = true;
						}
					}
					$alts = $this->bot->core("alts")->get_alts($main);
					foreach ($alts as $alt) {	 
						if($alt!=$toon&&$alt!=$name) {
							$result = $this -> bot -> core("whois") -> lookup($alt);
							if(!($result instanceof BotError)&&$prof==$result["profession"]) {
								$checklist[$toon][] = array($alt=>$result["level"]);
								$found = true;
							}
						}
					}
					if(!$found) unset($checklist[$toon]);
				}
				if(count($checklist)>0) {
					$all = "";
					foreach($checklist as $player=>$alts) {
						$all .= $player." ";
						$inside .= "\n".$player." has ".count($alts)." ".$prof."(s)"; $list = "";
						foreach($alts as $id=>$values) {				
							foreach($values as $thealt=>$lvl) { 
								$list .= " ".$lvl;
							}
						}
						$inside .= " [lvl".$list."] ";
						$inside .= "<a href='chatcmd:///tell ".$player." could you log ".$prof." please ?'>Tell this player</a>";
					}
					if(count($checklist)>1) $inside .= "\n\n<a href='chatcmd:///tell ".$this->bot->botname." !s ".$all." => could any of you log ".$prof." please ?'>Warn all players</a>";
					$return = count($checklist)." player(s) have some alt(s) of given profession ".$this->bot->core("tools")->make_blob("click to view", $inside);
				} else {
					$return = "No alt of given profession found.";
				}
			} else {
				$return = "Nobody in raid channel yet.";
			}
		}		
		$this -> bot -> send_tell($name,$return);
	}
	
	function RiCheck($name)
	{
		if(count($this->ris)==0) { 
			$this -> bot -> send_tell($name,"No RI created so far ... please do this first!");
			return false;
		}
		$msg = ""; $check = array(); $count = 0;
		foreach($this->ris as $index=>$ri) {			
			$members = $ri->GetRiMembers();
			$num = bcadd($index,1);
			foreach($members as $index=>$rimember) {
				$main = $this->bot->core("alts")->main($rimember->GetName());
				$check[$main][$num][] = $rimember->GetName();
			}			
		}
		foreach($check as $main=>$ris) {
			if(count($ris)>1) {
				$msg .= "\n".$main.": ";
				foreach($ris as $ir=>$alts) {
					foreach($alts as $alt) {
						$count++;
						$msg .= " ".$alt."(";
						for($i=1;$i<=count($this -> ris);$i++) {
							if($i==$ir) $msg .= $i;
							else $msg .= $this -> bot -> core("tools") -> chatcmd("addri " . $i . " " . $alt, $i);
						}
						$msg .= ") ";					
					}
				}
			}
		}
		$this -> bot -> send_tell($name, $count." separated alt(s) : ".$this->bot->core("tools")->make_blob("click to view", $msg));
	} 
	
	function AutoRi($name)
	{
		if(count($this->ris)==0) { 
			$this -> bot -> send_tell($name,"No RI was yet created ... please do this first!");
			return false;
		}

		$sorted = array();
		foreach($this->pgroup as $toon=>$array) {
			if($toon) {
				$free = true;
				foreach($this->ris as $index=>$ri)
				{
					$members = $ri->GetRiMembers();
					foreach($members as $index=>$rimember)
					{
						if($rimember->GetName()==$toon) {
							$free = false;
						}
					}
					
				}
				if($free) $sorted[] = $array;
			}
		}
		$j=0;
		$flag = true;
		$temp=0;
		while ( $flag )
		{
		  $flag = false;
		  for( $j=0;  $j < count($sorted)-1; $j++)
		  {
			if ( isset($sorted[$j][1]) && isset($sorted[$j+1][1]) && $sorted[$j][1] < $sorted[$j+1][1] )
			{
			  $temp = $sorted[$j];
			  $sorted[$j] = $sorted[$j+1];
			  $sorted[$j+1]=$temp;
			  $flag = true;
			}
		  }
		}
	
		$check = array();
		foreach($this->ris as $index=>$ri)
		{
			$check['tank'][bcadd($index,1)] = 0;
			$check['heal'][bcadd($index,1)] = 0;
			$check['refl'][bcadd($index,1)] = 0;
			$check['snar'][bcadd($index,1)] = 0;
			$check['aura'][bcadd($index,1)] = 0;
			$check['buff'][bcadd($index,1)] = 0;
			$check['back'][bcadd($index,1)] = 0;
			$check['dmgd'][bcadd($index,1)] = 0;
		}
		foreach($this->ris as $index=>$ri)
		{
			$members = $ri->GetRiMembers();
			foreach($members as $indexri=>$rimember)
			{
				$prof = $rimember->GetProfession();
				if($prof=="Enforcer") {
					$check['tank'][bcadd($index,1)] = $check['tank'][bcadd($index,1)]+1;
				}
				if($prof=="Doctor") {
					$check['heal'][bcadd($index,1)] = $check['heal'][bcadd($index,1)]+1;
				}
				if($prof=="Engineer"||$prof=="Soldier") {
					$check['refl'][bcadd($index,1)] = $check['refl'][bcadd($index,1)]+1;
				}
				if($prof=="Bureaucrat"||$prof=="Fixer") {
					$check['snar'][bcadd($index,1)] = $check['snar'][bcadd($index,1)]+1;
				}
				if($prof=="Keeper") {
					$check['aura'][bcadd($index,1)] = $check['aura'][bcadd($index,1)]+1;
				}
				if($prof=="Trader"||$prof=="Meta-Physicist") {
					$check['buff'][bcadd($index,1)] = $check['buff'][bcadd($index,1)]+1;
				}
				if($prof=="Adventurer"||$prof=="Martial Artist"||$prof=="Agent") {
					$check['back'][bcadd($index,1)] = $check['back'][bcadd($index,1)]+1;
				}
				if($prof=="Shade"||$prof=="Nano-Technician") {
					$check['dmgd'][bcadd($index,1)] = $check['dmgd'][bcadd($index,1)]+1;
				}
			}
		}
		
		foreach($sorted as $index=>$array)
		{
			if(isset($array[4])&&$array[4])
			{
				if($array[2]=="Enforcer") {
					$count = 0; $sent = false;
					while(!$sent) {
						foreach($this->ris as $index=>$ri)
						{
							if(!$sent&&$check['tank'][bcadd($index,1)] == $count) {
								$member = new RiMember($array[0],$array[1],$array[2],$array[3]);
								$dest = $this->GetRi(bcadd($index,1));
								$dest->AddMember($member);
								$check['tank'][bcadd($index,1)] = $check['tank'][bcadd($index,1)]+1;
								$sent = true;
							}
						}
						$count++;						
					}
				}
				if($array[2]=="Doctor") {
					$count = 0; $sent = false;
					while(!$sent) {
						foreach($this->ris as $index=>$ri)
						{
							if(!$sent&&$check['heal'][bcadd($index,1)] == $count) {
								$member = new RiMember($array[0],$array[1],$array[2],$array[3]);
								$dest = $this->GetRi(bcadd($index,1));
								$dest->AddMember($member);
								$check['heal'][bcadd($index,1)] = $check['heal'][bcadd($index,1)]+1;
								$sent = true;
							}
						}
						$count++;						
					}
				}
				if($array[2]=="Engineer"||$array[2]=="Soldier") {
					$count = 0; $sent = false;
					while(!$sent) {
						foreach($this->ris as $index=>$ri)
						{
							if(!$sent&&$check['refl'][bcadd($index,1)] == $count) {
								$member = new RiMember($array[0],$array[1],$array[2],$array[3]);
								$dest = $this->GetRi(bcadd($index,1));
								$dest->AddMember($member);
								$check['refl'][bcadd($index,1)] = $check['refl'][bcadd($index,1)]+1;
								$sent = true;
							}
						}
						$count++;						
					}
				}
				if($array[2]=="Bureaucrat"||$array[2]=="Fixer") {
					$count = 0; $sent = false;
					while(!$sent) {
						foreach($this->ris as $index=>$ri)
						{
							if(!$sent&&$check['snar'][bcadd($index,1)] == $count) {
								$member = new RiMember($array[0],$array[1],$array[2],$array[3]);
								$dest = $this->GetRi(bcadd($index,1));
								$dest->AddMember($member);
								$check['snar'][bcadd($index,1)] = $check['snar'][bcadd($index,1)]+1;
								$sent = true;
							}
						}
						$count++;						
					}
				}
				if($array[2]=="Keeper") {
					$count = 0; $sent = false;
					while(!$sent) {
						foreach($this->ris as $index=>$ri)
						{
							if(!$sent&&$check['aura'][bcadd($index,1)] == $count) {
								$member = new RiMember($array[0],$array[1],$array[2],$array[3]);
								$dest = $this->GetRi(bcadd($index,1));
								$dest->AddMember($member);
								$check['aura'][bcadd($index,1)] = $check['aura'][bcadd($index,1)]+1;
								$sent = true;
							}
						}
						$count++;						
					}
				}
				if($array[2]=="Trader"||$array[2]=="Meta-Physicist") {
					$count = 0; $sent = false;
					while(!$sent) {
						foreach($this->ris as $index=>$ri)
						{
							if(!$sent&&$check['buff'][bcadd($index,1)] == $count) {
								$member = new RiMember($array[0],$array[1],$array[2],$array[3]);
								$dest = $this->GetRi(bcadd($index,1));
								$dest->AddMember($member);
								$check['buff'][bcadd($index,1)] = $check['buff'][bcadd($index,1)]+1;
								$sent = true;
							}
						}
						$count++;						
					}
				}
				if($array[2]=="Adventurer"||$array[2]=="Martial Artist"||$array[2]=="Agent") {
					$count = 0; $sent = false;
					while(!$sent) {
						foreach($this->ris as $index=>$ri)
						{
							if(!$sent&&$check['back'][bcadd($index,1)] == $count) {
								$member = new RiMember($array[0],$array[1],$array[2],$array[3]);
								$dest = $this->GetRi(bcadd($index,1));
								$dest->AddMember($member);
								$check['back'][bcadd($index,1)] = $check['back'][bcadd($index,1)]+1;
								$sent = true;
							}
						}
						$count++;						
					}
				}
				if($array[2]=="Shade"||$array[2]=="Nano-Technician") {
					$count = 0; $sent = false;
					while(!$sent) {
						foreach($this->ris as $index=>$ri)
						{
							if(!$sent&&$check['dmgd'][bcadd($index,1)] == $count) {
								$member = new RiMember($array[0],$array[1],$array[2],$array[3]);
								$dest = $this->GetRi(bcadd($index,1));
								$dest->AddMember($member);
								$check['dmgd'][bcadd($index,1)] = $check['dmgd'][bcadd($index,1)]+1;
								$sent = true;
							}
						}
						$count++;						
					}
				}				
			}
		}
		
		$this -> bot -> send_tell($name,"Auto distributed ".count($sorted)." character(s) among ".count($this->ris)." RI(s).");
	}
	
	function PrintRis()
	{
		foreach($this->ris as $index=>$ri)
		{
			if($ri -> GetName() == "")
			{
				$msg = "Ri ##highlight##".bcadd($index,1)."##end## (##highlight##".$ri -> Count()."##end##) :: ";
			}
			else
			{
				$msg = "Ri ##highlight##".bcadd($index,1)."##end## :: \"".$ri -> GetName()."\" (##highlight##".$ri->Count() . "##end##) :: ";
			}

			$members = $ri->GetRiMembers();
			foreach($members as $index=>$rimember)
			{
				if($rimember->IsLeader())
				{
					$msg .= "[##blob_title##".$rimember->GetName() . " :: Leader##end##]";
				}
				else
				{
					$msg .= "[##highlight##".$rimember->GetName() . "##end##]";
				}
			}
			$msg .= "##end##";
			$this->bot->send_pgroup($msg);
		}
	}

	function SendRi($num,$bot,$executer)
	{
		if(preg_match("#^[1-9]#", $num))
		{
			if($num <= count($this->ris) && $num > 0)
			{       $listing = ""; $pause = $this -> bot -> core("settings") -> get("Ris", "sendrate");
				$ri = $this->ris[$num-1]; if ($ri->GetName() == "") { $name = ""; } else { $name = " (".$ri->GetName().") "; }
				$this->bot->send_pgroup("Ri ##highlight##" . $num . "##end## ".$name."being exported to [##highlight##" . $bot . "##end##]");
				foreach($ri->GetRiMembers() as $index=>$member)
				{
					if(isset($this->pgroup[$member->GetName()]) && !$member->IsLeader())
					{       $listing .= $member->GetName().",";
						$this -> bot -> core("chat") -> pgroup_kick($member->GetName());
						usleep($pause);
					} elseif (isset($this->pgroup[$member->GetName()]) && $member->IsLeader()) {
                                                $listing .= $member->GetName()."," ;
                                                $this->pgroup[$member->GetName()][4] = true;
						usleep($pause);
                                        }
				}
                         	$this->bot->send_tell($bot, "!ritake ".substr($listing, 0, -1), 1, false, TRUE);
				unset($this->ris[$num-1]);
				$this->numris--;
				$this->ris = array_values($this->ris);
			}
			else
			{
				$this->bot->send_tell($executer, "Ri ##highlight##" . $num . "##end## does not exist");
			}
		}
		else
		{
			$this->bot->send_tell($executer,"Usage: <pre>risend &lt;RINumber&gt; &lt;TargetBotName&gt;##end##");
		}
	}

	function TakeRi($bot,$blob)
	{       $sendbot = $this -> bot -> core("settings") -> get("Ris", "sendbot");
	        $pause = $this -> bot -> core("settings") -> get("Ris", "sendrate");
		if (strtolower($sendbot) == strtolower($bot) && $sendbot != "") {
                   $listing = explode(",",$blob); $count = count($listing);
                   for ($i=0 ; $i<$count ; $i++) { //echo $listing[$i];
                       $this -> bot -> core("chat") -> pgroup_invite($listing[$i]);
                       usleep($pause);
                   }
                }
	}

	function AddRi($ri)
	{
		array_push($this->ris,$ri);
	}

	function ClearRis($name)
	{
		foreach($this->ris as $index=>$ris)
		{
			$this->DelRi(1,$name);
		}
		$this->bot->send_pgroup("All Raid Interfaces cleared by [##highlight##".$name . "##end##]");
	}

	function DelRi($num,$executer)
	{
		if(preg_match("#^[1-9]#", $num))
		{
			if($num <= count($this->ris) && $num > 0)
			{
				$ri = $this->ris[$num-1];
				foreach($ri->GetRiMembers() as $index=>$member)
				{
					if(isset($this->pgroup[$member->GetName()]))
					{
						$this->pgroup[$member->GetName()][4] = true;
					}
				}
				unset($this->ris[$num-1]);
				$this->numris--;
				$this->ris = array_values($this->ris);
			}
			else
			{
				$this->bot->send_tell($executer, "Ri ##highlight##" . $num . "##end## does not exist");
			}
		}
		else
		{
			$this->bot->send_tell($executer,"Usage: <pre>delri &lt;number&gt;##end##");
		}
	}

	function GetRi($num)
	{
		return $this->ris[$num-1];
	}

	function DelRiMember($name,$executer,$suppress=true,$silent=false)
	{
		$return = false;

		if(! ($this->bot->core('player')->id($name) instanceof BotError) )
		{
			for($i=1;$i<=count($this->ris);$i++)
			{
				$ri = $this->GetRi($i);
				if($ri->DelMember($name))
				{
					if(isset($this->pgroup[$name]))
					{
						$this->pgroup[$name][4] = true;
					}
					$return = true;
					if(!$suppress&&$executer!="")
					{
					$this->bot->send_tell($executer,"[##highlight##".$name ."##end##] has been removed from ri ##highlight##". $i . "##end##");
					}
				}
				else
				{
					$return = $return | false;
				}
			}
			return $return;
		}
		else
		{
			if (!$silent&&$executer!="")
			{
				$this->bot->send_tell($executer,"Usage: <pre>remri &lt;name&gt;");
			}
			return false;
		}
	}

	function AddRiMember($name,$num,$executer)
	{
		if(preg_match("#^[1-9]#", $num))
		{
			if($num <= count($this->ris) && $num > 0)
			{
				if($name != "")
				{
					if(isset($this->pgroup[$name]))
					{
						$member = new RiMember($this->pgroup[$name][0],$this->pgroup[$name][1],$this->pgroup[$name][2],$this->pgroup[$name][3]);
						$ri = $this->GetRi($num);
						$rimembers = $ri->GetRiMembers();
						$isri = false;
						foreach($rimembers as $id=>$object) {
							if($object->name==$name) { $isri = true; }
						}											
						if($isri)
						{
						$this->bot->send_tell($executer,"##highlight##".$name . "##end## is already in ri ##highlight##".$num . "##end##");
							return;
						}
						else
						{
							$deleted = $this->DelRiMember($name,$executer);
						}


						if($ri->AddMember($member))
						{
							$this->pgroup[$name][4] = false;
							if($deleted)
							{
							$this->bot->send_tell($executer,"[##highlight##" . $name . "##end##] changed to ri ##highlight##" . $num . "##end##");
							}
							else
							{
							$this->bot->send_tell($executer,"[##highlight##" . $name . "##end##] has been added to ri ##highlight##" . $num . "##end####end##");
							}
						}
						else
						{
						$this->bot->send_tell($executer,"Ri ##highlight##".$num . "##end## is full");
						}
					}
					else
					{
						$this->bot->send_tell($executer,"[##highlight##" . $name . "##end##] is not in the group");
					}
				}
			}
			else
			{
				$this->bot->send_tell($executer,"Ri ##highlight##".$num . "##end## does not exist");
			}
		}
		else
		{
			$this->bot->send_tell($executer,"Usage: <pre>riadd &lt;number&gt; &lt;name&gt;");
		}
	}

	function StartRi($executer,$name,$riname)
	{
		if($name != "")
		{
			if(isset($this->pgroup[$name]))
			{
				$this->DelRiMember($name,$executer);
				$member = new RiMember($this->pgroup[$name][0],$this->pgroup[$name][1],$this->pgroup[$name][2],$this->pgroup[$name][3],true);
				$ri = new Ri($member,$riname);
				++$this->numris;
				$this->pgroup[$name][4] = false;
				$this->AddRi($ri);
				$this->bot->send_pgroup("Ri ##highlight##" . $this->numris . "##end## has been started. Leader is [##highlight##" . $name . "##end##]");
			}
			else
			{
			$this->bot->send_tell($executer,"[##highlight##" . $name . "##end##] is not in the group");
			}
		}
	}

	function SetRiName($riname,$num,$executer)
	{
		if(preg_match("#^[1-9]#", $num))
		{
			if($num <= count($this->ris) && $num > 0)
			{
				$ri = $this->GetRi($num);
				$ri->SetName($riname);
			}
			else
			{
				$this->bot->send_tell($executer,"Ri ##highlight##" . $num . "##end## does not exist");
			}
		}
		else
		{
			$this->bot->send_tell($executer, "Usage: <pre>riname &lt;number&gt; &lt;name&gt;");
		}

	}

	function GetPlayerInfo($name)
	{
		$uid = $this->bot->core('player')->id($name);
		$result = $this -> bot -> core("whois") -> lookup($name);
		//print_r($result);
		if ( !($result instanceof BotError) && !empty($result) && count($result)>0 && !isset($result["error"]) 
		   && isset($result["level"]) && is_numeric($result["level"]) && $result["level"]>0 
		   && isset($result["profession"]) && $result["profession"]!="" )
		{
			$member[0] = $name;
			$member[1] = $result["level"];
			$member[2] = $result["profession"];
			if(isset($result["org"]) && $result["org"]!="") $member[3] = $result["org"];
			else $member[3] = "Orgless";
			$member[4] = true;
			return $member;
		}
		else
		{
			$member[0] = $name;
			$member[1] = "Unknown";
			$member[2] = "Unknown";
			$member[3] = "Unknown";
			$member[4] = true;
			return $member;
		}
	}

	function pgjoin($name)
	{
		$member = $this->GetPlayerInfo($name);
		if(is_numeric($member[1])&&$member[1]>$this->bot->core("settings")->get("Ris","ignlvl")) $this->pgroup[$name] = $member;
	}

	function pgleave($name)
	{
		unset($this->pgroup[$name]);
		$this -> DelRiMember($name,"", 0, 1);
	}

	function GetAdminConsole($name)
	{
		$msg  = "##highlight##:::: Multiple RI Admin ::::##end##\n\n";
		$msg .= "##highlight##Commands:##end##\n";
		$msg .= $this -> bot -> core("tools") -> chatcmd("riadmin", "Refresh this admin.")."\n";
		$msg .= $this -> bot -> core("tools") -> chatcmd("riauto", "Auto distrib profs among RIs.")."\n";
		$msg .= "Alt: "
			.$this -> bot -> core("tools") -> chatcmd("richeck", "Check")." "
			.$this -> bot -> core("tools") -> chatcmd("rialts enf", "Enf")." "
			.$this -> bot -> core("tools") -> chatcmd("rialts doc", "Doc")." "
			.$this -> bot -> core("tools") -> chatcmd("rialts bur", "Bu")." "
			.$this -> bot -> core("tools") -> chatcmd("rialts age", "Ag")." "			
			.$this -> bot -> core("tools") -> chatcmd("rialts fix", "Fx")." "
			.$this -> bot -> core("tools") -> chatcmd("rialts eng", "Eng")." "
			.$this -> bot -> core("tools") -> chatcmd("rialts sol", "So")." "
			.$this -> bot -> core("tools") -> chatcmd("rialts kee", "Ke")." "
			.$this -> bot -> core("tools") -> chatcmd("rialts met", "Mp")." "
			.$this -> bot -> core("tools") -> chatcmd("rialts nan", "Nt")." "
			.$this -> bot -> core("tools") -> chatcmd("rialts tra", "Tr")." "			
			.$this -> bot -> core("tools") -> chatcmd("rialts adv", "Ad")." "
			.$this -> bot -> core("tools") -> chatcmd("rialts mar", "Ma")." "
			.$this -> bot -> core("tools") -> chatcmd("rialts sha", "Sh")." "
		."\n";
		$msg .= $this -> bot -> core("tools") -> chatcmd("showris", "Show RIs on channel.")."\n";
		$msg .= $this -> bot -> core("tools") -> chatcmd("clearris", "Reset all RIs !")."\n";
		$msg .= $this -> bot -> core("tools") -> chatcmd("risend", "Howto export RI ?")."\n\n";
		foreach($this->ris as $index=>$ri)
		{
			if($ri->GetName() == "")
			{
				$msg .= ":: Ri ##highlight##" . bcadd($index,1) . "##end## (##highlight##" . $ri->Count() . "##end##) :: ";
			}
			else
			{
				$msg .= ":: Ri ##highlight##" . bcadd($index,1) . "##end## :: \"" . $ri->GetName() . "\" (##highlight##" . $ri->Count() . "##end##) :: ";
			}
				$msg .= $this -> bot -> core("tools") -> chatcmd("riname " . bcadd($index,1) . " North", "N")."/";
				$msg .= $this -> bot -> core("tools") -> chatcmd("riname " . bcadd($index,1) . " East", "E")."/";
				$msg .= $this -> bot -> core("tools") -> chatcmd("riname " . bcadd($index,1) . " West", "W")." :: ";
				$msg .= $this -> bot -> core("tools") -> chatcmd("delri " . bcadd($index,1), "Del")."\n";

			$members = $ri->GetRiMembers();
			foreach($members as $indexri=>$rimember)
			{
				$sid = ":";
				if (strtolower($this->bot->game) == 'ao') {
					$who = $this->bot->core("whois")->lookup($rimember->GetName());
					if (!($who instanceof BotError)) {
						if ($who["faction"] == 'Clan') $sid = "C";
						elseif ($who["faction"] == 'Omni') $sid = "O";						
						elseif ($who["faction"] == 'Neutral') $sid = "N";
						else $sid = "?";
					} else {
						$sid = "!";
					}
				}
				$color = "highlight";
				if ($this->bot->exists_module("loots")&&isset($this->bot->core("loots")->loot[1])) {
					$list = array_keys($this->bot->core("loots")->loot[1]);
					foreach($list as $player) {
						if (($player != "item") && ($player != "num") && ($this->bot->core("loots")->loot[1][$player] == 1) && ($player == $rimember->GetName())) {
							$color = "blob_title";
						}
					}
				}
				if($rimember->IsLeader())
				{
					$msg .= "\t[##".$color."##" . $rimember->GetName() . " :: Leader##end##] (" . $rimember->GetLevel() . " " . $rimember->GetProfession() . ") ".$sid." ";
					$msg .= $this->GetExtraInfo($rimember->GetName(),$index+1);
				}
				else
				{
					$msg .= "\t[##".$color."##" . $rimember->GetName() . "##end##] (" . $rimember->GetLevel() . " " . $rimember->GetProfession() . ") ".$sid." ";
					$msg .= $this->GetExtraInfo($rimember->GetName(),$index+1);
				}
				foreach($this->ris as $indexri=>$ris)
				{
					$msg .= "/";
					$msg .= $this -> bot -> core("tools") -> chatcmd("addri " . bcadd($indexri,1) . " " . $rimember->GetName(), bcadd($indexri,1));
				}
				$msg .= "\n";
			}
			$msg .= "\n";
		}
		$msg .= "\n";
		$msg .= "##blob_title##::Looking For Ri::##end##\n";
		foreach($this->pgroup as $index=>$array)
		{
			$test = $array;							
			if(isset($array[4]))
			{
				$hasri = false;
				foreach($this->ris as $index=>$ri)
				{
					$checkri = $ri->GetRiMembers();
					foreach($checkri as $id=>$object) {
						if($object->name==$array[0]) { $hasri = true; }
					}
				}
				if(!$hasri) {
					$sid = ":";
					if (strtolower($this->bot->game) == 'ao') {
						$who = $this->bot->core("whois")->lookup($array[0]);
						if (!($who instanceof BotError)) {
							if ($who["faction"] == 'Clan') $sid = "C";
							elseif ($who["faction"] == 'Omni') $sid = "O";						
							elseif ($who["faction"] == 'Neutral') $sid = "N";
							else $sid = "?";
						} else {
							$sid = "!";
						}
					}				
					$color = "highlight";
					if ($this->bot->exists_module("loots")&&isset($this->bot->core("loots")->loot[1])) {
						$list = array_keys($this->bot->core("loots")->loot[1]);
						foreach($list as $player) {
							if (($player != "item") && ($player != "num") && ($this->bot->core("loots")->loot[1][$player] == 1) && ($player == $array[0])) {
								$color = "blob_title";
							}
						}
					}					
					$msg .= "\t[##".$color."##" . $array[0] . "##end##] (" . $array[1] . " " . $array[2] . ") ".$sid." ";
					$msg .= $this -> bot -> core("tools") -> chatcmd("startri ".$array[0], "Start Ri");
					foreach($this->ris as $indexri=>$ris)
					{
						$msg .= "/";
						$msg .= $this -> bot -> core("tools") -> chatcmd("addri " . bcadd($indexri,1) . " " . $test[0], bcadd($indexri,1));
					}
					$msg .= "\n";
				}
			}
		}
		$msg .= "##end##";
		$this -> bot -> send_tell($name, $this -> bot -> core("tools") -> make_blob("Ris Administration", $msg));
	}

	function GetExtraInfo($name,$index)
	{
		$msg = "";
		$msg .= $this -> bot -> core("tools") -> chatcmd("remri ".$name, "Rem")."/";
		$msg .= $this -> bot -> core("tools") -> chatcmd("startri ".$name, "Sta")."/";
		$msg .= $this -> bot -> core("tools") -> chatcmd("rileader $index $name", "Lead");
		return $msg;
	}
}
?>
