<?php
/*
* Perks.php - Module Perks
* Adapted from Tyrbot's work by Tyrence ; enriched by Bitnykk
* Thanks to Auno tools & https://github.com/Budabot/Tyrbot/blob/master/docs/mmdb.txt
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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

/* FIXME : would be nice to update and possibly show also AI/LE perks ... */

$perks = new Perks($bot);

class Perks extends BaseActiveModule
{
	function __construct (&$bot)
	{
		// Initialize the base module
		parent::__construct($bot, get_class($this));

		// Register command
		$this->register_command('all', 'slperks', 'GUEST');
		$this -> register_alias("slperks", "slperk");
		$this->register_command('all', 'aiperks', 'GUEST');
		$this -> register_alias("aiperks", "aiperk");
		$this->register_command('all', 'leperks', 'GUEST');
		$this -> register_alias("leperks", "leperk");

		// Add description/help
		$this -> help['description'] = "Show perks and their buffs per level/profession";
		$this -> help['command']['slperks <level> <profession>'] = "Display SL perks available at level/profession given";
		$this -> help['command']['aiperks <level> <profession> <breed>'] = "Display AI perks available at level/profession/breed given";
		$this -> help['command']['leperks <level> <profession>'] = "Display LE perks available at level/profession given";
	}

	function command_handler($source, $msg, $origin)
	{
		$this->error->reset();
		
		$vars = explode(' ', strtolower($msg));
		$command = $vars[0];

		switch($command)
		{
			case 'slperks':
				if(isset($vars[1])&&is_numeric($vars[1])&&$vars[1]>0&&isset($vars[2])&&$vars[2]!="") {
					return $this -> all_perks("sl", $source, $origin, $vars[1], $vars[2], "");
				} else {
					$this->bot->send_help($source);
				}
				break;
			case 'aiperks':
				if(isset($vars[1])&&is_numeric($vars[1])&&$vars[1]>0&&isset($vars[2])&&$vars[2]!=""&&isset($vars[3])&&$vars[3]!="") {
					return $this -> all_perks("ai", $source, $origin, $vars[1], $vars[2], $vars[3]);
				} else {
					$this->bot->send_help($source);
				}
				break;
			case 'leperks':
				if(isset($vars[1])&&is_numeric($vars[1])&&$vars[1]>0&&isset($vars[2])&&$vars[2]!="") {
					return $this -> all_perks("le", $source, $origin, $vars[1], $vars[2], "");
				} else {
					$this->bot->send_help($source);
				}
				break;				
			default:				
				$this -> error -> set("Broken plugin, received unhandled command: $command");
				return($this->error->message());
		}
	}
	
	function all_perks($exten, $source, $origin, $level, $prof, $breed)
	{
		if($level<1||$level>220) {
			return $this->bot->send_output($source, "Wrong level given, must be between 1 upto 220.", $origin);
		}
		if($exten=="ai") {
			$breed = strtolower($breed);
			switch($breed) {
				case 'atrox':
				case 'trox':
					$breed = 'Atrox';
					$bid = 4;
					break;
				case 'opifex':
				case 'opi':
					$breed = 'Opifex';
					$bid = 2;
					break;
				case 'solitus':
				case 'soli':
					$breed = 'Solitus';
					$bid = 1;
					break;
				case 'nanomage':
				case 'nmage':
				case 'nano':				
				case 'nm':				
					$breed = 'Nanomage';
					$bid = 3;
					break;					
				default:
					return $this->bot->send_output($source, "Wrong breed given, nothing found.", $origin);
			}					
		}
		$prof = strtolower($prof);
		switch($prof) {
			case 'adventurer':
			case 'advy':
			case 'adv':
				$prof = 'Adventurer';
				$pid = 6;
				break;
			case 'agent':
			case 'agt':
			case 'ag':
				$prof = 'Agent';
				$pid = 5;
				break;			
			case 'bureaucrat':
			case 'crat':
			case 'bur':
				$prof = 'Bureaucrat';
				$pid = 8;
				break;
			case 'doctor':
			case 'doc':
				$prof = 'Doctor';
				$pid = 10;
				break;
			case 'enforcer':
			case 'enfo':
			case 'enf':
				$prof = 'Enforcer';	
				$pid = 9;
				break;
			case 'engineer':
			case 'engi':
			case 'eng':
				$prof = 'Engineer';
				$pid = 3;
				break;
			case 'fixer':
			case 'fix':
				$prof = 'Fixer';
				$pid = 4;
				break;
			case 'keeper':
			case 'keep':
			case 'kee':
				$prof = 'Keeper';
				$pid = 14;
				break;
			case 'martial Artist':
			case 'martial':
			case 'ma':
				$prof = 'Martial Artist';
				$pid = 2;
				break;
			case 'meta-Physicist':
			case 'meta':
			case 'mp':
				$prof = 'Meta-Physicist';
				$pid = 12;
				break;
			case 'nano-Technician':
			case 'nano':
			case 'nt':
				$prof = 'Nano-Technician';
				$pid = 11;
				break;
			case 'shade':
			case 'shad':
			case 'sha':
				$prof = 'Shade';
				$pid = 15;
				break;
			case 'soldier':
			case 'sold':
			case 'sol':
				$prof = 'Soldier';
				$pid = 1;
				break;
			case 'trader':
			case 'trad':
			case 'tra':
				$prof = 'Trader';
				$pid = 7;
				break;
			default:
				return $this->bot->send_output($source, "Wrong profession given, nothing found.", $origin);
		}
		
		$path = "./Extras/Perks/";
		$skill = array();
		if (($handle = fopen($path."skill.csv", "r")) !== FALSE) {
			while (($line = fgetcsv($handle, 1000, ";")) !== FALSE) {
				$skill[$line[0]] = $line[1];
			}
			fclose($handle);
		}
		$abil = array();
		if (($handle = fopen($path."abil.csv", "r")) !== FALSE) {
			while (($line = fgetcsv($handle, 1000, ";")) !== FALSE) {
				if(isset($line[1])) $abil[$line[0]] = $line[1];
			}
			fclose($handle);
		}
		$perk = array(); $slxclud=array(94055,94056,94057,94058,94059,94060,94203,94204,94205,94206,94207,94208,94209,94210,94211,94212,94394,94395,129456,129457,129458,129459,129460,94085,94086,94087,94088,94089,94090,94091,94092,94093,94097);
		if (($handle = fopen($path."perk.csv", "r")) !== FALSE) {
			while (($line = fgetcsv($handle, 1000, ";")) !== FALSE) {
				if( ($exten=="sl"&&($line[0]<=108173||($line[0]>=125093&&$line[0]<=129460))&&!in_array($line[0],$slxclud)) // rebalance & mistaken
				 || ($exten=="ai"&&$line[0]>=110277&&$line[0]<=114645)
				 || ($exten=="le"&&($line[0]>=130019||($line[0]>=115778&&$line[0]<=119537))&&substr($line[1],0,19)!="Global Advantage - ") ) { // rebalance & globals
					$perk[$line[0]] = $line[1];
				}
			}
			fclose($handle);
		}
		$req = array();
		if (($handle = fopen($path."req.csv", "r")) !== FALSE) {
			while (($line = fgetcsv($handle, 1000, ";")) !== FALSE) {
				foreach($perk AS $id => $name) {
					if($id==$line[0]&&($line[3]==2||$line[3]==0)) {
						$req[$line[0]][] = array($line[1],$line[2],$line[3]);
					}
				}
			}
			fclose($handle);
		}
		$eff = array();
		if (($handle = fopen($path."eff.csv", "r")) !== FALSE) {
			while (($line = fgetcsv($handle, 1000, ";")) !== FALSE) {
				foreach($perk AS $id => $name) {
					if($id==$line[0]) {
						$eff[$line[0]][] = array($line[1],$line[2],$line[3],$line[4]);
					}
				}
			}
			fclose($handle);
		}
		$ext = array();
		if (($handle = fopen($path."ext.csv", "r")) !== FALSE) {
			while (($line = fgetcsv($handle, 1000, ";")) !== FALSE) {
				foreach($eff AS $id => $effs) {
					foreach($effs AS $group) {
						if($group[0]==190) {
							if($group[3]==$line[0]) {
								$ext[$line[0]] = $line[1];
							}
						}
					}
				}
			}
			fclose($handle);
		}

		$sel = array(); $startloc['Focus Ferocity']=1;
		foreach($perk AS $id => $name) {
			if(isset($req[$id])) {
				$lvlloc=0; $profloc=array(); $bredloc=array();
				foreach($req[$id] AS $bloc) {
					//echo " [".$bloc[0]." ".$bloc[1]." ".$bloc[2]."] ";
					if($bloc[2]==2) {
						$lvlloc=$bloc[1];
					}
					if($bloc[2]==0) {
						if($bloc[0]==60) {
							$profloc[]=$bloc[1];
						}
						if($bloc[0]==4) {
							$bredloc[]=$bloc[1];
						}
					}
				}
				if(count($profloc)==0) $folder = 'GENERAL';
				elseif(count($profloc)==1) $folder = 'PERSONAL';
				else $folder = 'GROUP';
				$access = true;
				if(isset($startloc[$name])) {
					$access = false;
				}
				if(count($profloc)>0 && !in_array($pid,$profloc)) {
					$access = false;
					if(!isset($sel[$folder][$name])&&!isset($startloc[$name])) {
						$startloc[$name]=1;
					}
				}
				if(count($bredloc)>0 && !in_array($bid,$bredloc)) {
					$access = false;
					if(!isset($sel[$folder][$name])&&!isset($startloc[$name])) {
						$startloc[$name]=1;
					}			
				}
				if($lvlloc>0 && $level<=$lvlloc) $access = false;
				if($access) {
					if(isset($sel[$folder][$name])) {
						$up = $sel[$folder][$name]['PerkLevel']+1;
						$sel[$folder][$name]['PerkLevel'] = $up;
					} else {
						$sel[$folder][$name]['PerkLevel'] = 1;
					}
					if(isset($eff[$id])) {
						foreach($eff[$id] AS $buff) {
							if($buff[0]==190) {						
								if(isset($ext[$buff[3]])) {
									$sel[$folder][$name]['Actions'][]=array($buff[3],$ext[$buff[3]]);
								}
							}
							if($buff[0]==170) {
								$abiln = $abil[$buff[1]];
								if(isset($sel[$folder][$name][$abiln])) {
									$addus = $sel[$folder][$name][$abiln]+$buff[2];
								} else {
									$addus=$buff[2];
								}
								$sel[$folder][$name][$abiln]=$addus;
							}			
							if($buff[0]==53) {
								$skiln = $skill[$buff[1]];
								if(isset($sel[$folder][$name][$skiln])) {
									$bonus = $sel[$folder][$name][$skiln]+$buff[2];
								} else {
									$bonus=$buff[2];
								}
								$sel[$folder][$name][$skiln]=$bonus;
							}
						}				
					}
				}
			}
		}
		
		$count = 0; $current=""; $blob=strtoupper($exten)." perks for ".$level." ".$breed." ".$prof.":\n";
		if(isset($sel["PERSONAL"])&&count($sel["PERSONAL"])>0) {
			$blob .= "\n\nPERSONAL\n\n";
			foreach($sel["PERSONAL"] AS $name => $sels) {
				$count++;		
				$desc = "";
				if($name=='Commanding Presence') $desc = 'This perk line will offer the Bureaucrat an attack and defense modifier pulsing team buff. The more perks the Bureaucrat has in this line, the more powerful it will be.';
				if($name=='Aura of Revival') $desc = 'Will give the Keeper a pulsing team heal aura. The more perks the Keeper has in this line, the more powerful the heal gets.';
				if($name=='Channeling of Notum') $desc = 'This perk will allow for faster regeneration of nano points. The more Perks the NT has in this line, the more powerful the regen rate will be.';
				$blob .= $name." ".$sels['PerkLevel']." : ".$desc." ";
				$actions="\n";
				foreach($sels AS $titre => $content) {
					if($titre!='PerkLevel'&&$titre!='Actions') {
						$blob .= " ".$titre.":".$content." | ";
					} elseif($titre=='Actions') {
						foreach($content AS $action) {
							$actions .= ' <a href="itemref://'.$action[0].'/'.$action[0].'/1"><img src=rdb://'.$action[1].'></a> ';
						}
					}
				}
				$blob .= $actions."\n\n";
			}
		}
		if(isset($sel["GROUP"])&&count($sel["GROUP"])>0) {
			$blob .= "\n\nGROUP\n\n";
			foreach($sel["GROUP"] AS $name => $sels) {
				$count++;
				$blob .= $name." ".$sels['PerkLevel']." : ";
				$actions="\n";
				foreach($sels AS $titre => $content) {
					if($titre!='PerkLevel'&&$titre!='Actions') {
						$blob .= " ".$titre.":".$content." | ";
					} elseif($titre=='Actions') {
						foreach($content AS $action) {
							$actions .= ' <a href="itemref://'.$action[0].'/'.$action[0].'/1"><img src=rdb://'.$action[1].'></a> ';
						}
					}
				}
				$blob .= $actions."\n\n";
			}
		}
		if(isset($sel["GENERAL"])&&count($sel["GENERAL"])>0) {
			$blob .= "\n\nGENERAL\n\n";	
			foreach($sel["GENERAL"] AS $name => $sels) {
				$count++;
				$desc = "";
				$blob .= $name." ".$sels['PerkLevel']." : ";
				$actions="\n";
				foreach($sels AS $titre => $content) {
					if($titre!='PerkLevel'&&$titre!='Actions') {
						$blob .= " ".$titre.":".$content." | ";
					} elseif($titre=='Actions') {
						foreach($content AS $action) {
							$actions .= ' <a href="itemref://'.$action[0].'/'.$action[0].'/1"><img src=rdb://'.$action[1].'></a> ';
						}
					}
				}
				$blob .= $actions."\n\n";
			}
		}

		return $this->bot->send_output($source, $count." perk(s) found: ".$this->bot->core("tools")->make_blob("click to view", $blob), $origin);
	}
}
?>
