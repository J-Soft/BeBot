<?php
/*
* dyna.php - Module dyna
* Adapted from Budabots's work Tyrence, Neksus & Mdkdoc420
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

$dyna = new Dyna($bot);

class Dyna extends BaseActiveModule
{
	function __construct (&$bot)
	{
		parent::__construct($bot, get_class($this));

		$this->register_command('all', 'dyna', 'GUEST');
		$this -> register_alias("dyna", "dynas");

		$this -> help['description'] = "Search and show various dynacamps and their details.";
		$this -> help['command']['dyna <level>'] = "Search for existing dyna by level.";
	}

	function command_handler($source, $msg, $origin)
	{
		$this->error->reset();
		
		$vars = explode(' ', strtolower($msg));
		$command = $vars[0];
		
		switch($command)
		{
			case 'dyna':
				if(count($vars)==2&&is_numeric($vars[1])&&$vars[1]>0) {
					return $this -> send_dyna($source, $origin, $vars[1]);
				} else {
					$this->bot->send_help($source);
				}
				break;		
			default:			
				$this -> error -> set("Broken plugin, received unhandled command: $command");
				return($this->error->message());
		}
	}
	
	function send_dyna($name, $origin, $lvl=0)
	{	
		// pf name type min max x y
		$dynas[] = array(585,'Dynacamp1','Rhinomen',60,65,460,340);
		$dynas[] = array(585,'Dynacamp3','Hounds',55,60,820,980);
		$dynas[] = array(585,'Dynacamp4','Androids',55,60,380,2300);
		$dynas[] = array(585,'Dynacamp5','Androids',50,55,860,2340);
		$dynas[] = array(585,'Dynacamp6','Mechdogs',45,50,1100,1700);
		$dynas[] = array(585,'Dynacamp7','Androids',40,45,980,380);
		$dynas[] = array(585,'Dynacamp8','Mantezes',45,50,1260,700);
		$dynas[] = array(585,'Dynacamp9','Mantezes',45,50,1180,940);
		$dynas[] = array(585,'Dynacamp10','Anuns',60,65,1380,1380);
		$dynas[] = array(585,'Dynacamp11','Leets',30,35,1340,1900);
		$dynas[] = array(585,'Dynacamp12','Fleas',25,30,1420,2620);
		$dynas[] = array(585,'Dynacamp13','Fleas',25,30,1580,2780);
		$dynas[] = array(585,'Dynacamp14','Rollerrats',40,45,1860,2780);
		$dynas[] = array(585,'Dynacamp15','Hounds',40,45,1620,2060);
		$dynas[] = array(585,'Dynacamp16','Blubbags',15,20,1740,1940);
		$dynas[] = array(585,'Dynacamp17','Blubbags',25,30,1700,1700);
		$dynas[] = array(585,'Dynacamp18','Fleas',25,30,2100,2340);
		$dynas[] = array(585,'Dynacamp19','Hounds',45,50,2140,980);
		$dynas[] = array(585,'Dynacamp20','Mantezes',60,65,2060,740);
		$dynas[] = array(716,'Dynacamp1','Igruana',5,6,380,3180);
		$dynas[] = array(716,'Dynacamp2','Tentacle Mutants',11,13,860,3260);
		$dynas[] = array(716,'Dynacamp3','Blubbags',6,8,540,2860);
		$dynas[] = array(716,'Dynacamp5','Malle',3,5,380,1500);
		$dynas[] = array(716,'Dynacamp6','Biofreak',5,7,380,1300);
		$dynas[] = array(716,'Dynacamp7','Fleas',17,19,540,1020);
		$dynas[] = array(716,'Dynacamp8','Shadowmutants',8,10,300,1060);
		$dynas[] = array(716,'Dynacamp9','Leets',7,8,380,820);
		$dynas[] = array(716,'Dynacamp10','Pareets',11,13,300,620);
		$dynas[] = array(716,'Dynacamp11','Rhinomen',15,17,620,340);
		$dynas[] = array(716,'Dynacamp12','Aquaans',12,14,540,660);
		$dynas[] = array(716,'Dynacamp13','Nighthowler',12,14,660,1340);
		$dynas[] = array(716,'Dynacamp14','Reets',3,4,660,2540);
		$dynas[] = array(590,'Dynacamp1','Snakes',130,135,380,420);
		$dynas[] = array(590,'Dynacamp2','Anuns',115,120,420,1060);
		$dynas[] = array(590,'Dynacamp3','Spiders',120,125,500,1700);
		$dynas[] = array(590,'Dynacamp4','Spiders',110,115,460,2380);
		$dynas[] = array(590,'Dynacamp5','Spiders',105,110,860,2380);
		$dynas[] = array(590,'Dynacamp6','Spiders',115,120,940,1860);
		$dynas[] = array(590,'Dynacamp7','Enigmas',140,145,940,1540);
		$dynas[] = array(590,'Dynacamp8','Enigmas',135,140,1340,860);
		$dynas[] = array(590,'Dynacamp9','Enigmas',100,105,1700,2500);
		$dynas[] = array(590,'Dynacamp10','Cyborgs',95,100,1380,2740);
		$dynas[] = array(590,'Dynacamp11','Enigmas',130,135,2620,2820);
		$dynas[] = array(590,'Dynacamp12','Enigmas',130,135,2380,2340);
		$dynas[] = array(590,'Dynacamp13','Spiders',120,125,2340,340);
		$dynas[] = array(590,'Dynacamp14','Nanofreaks',165,170,2660,300);
		$dynas[] = array(590,'Dynacamp15','Spiders',120,125,3380,1380);
		$dynas[] = array(590,'Dynacamp16','Snakes',175,180,3540,1420);
		$dynas[] = array(590,'Dynacamp17','Snakes',125,130,3220,1620);
		$dynas[] = array(590,'Dynacamp18','Anuns',130,135,3580,2940);
		$dynas[] = array(590,'Dynacamp20','Snakes',200,205,3500,1900);
		$dynas[] = array(655,'Dynacamp01','Leets',30,35,1380,2700); 
		$dynas[] = array(655,'Dynacamp02','Shadowmutants',80,85,4500,1140);
		$dynas[] = array(655,'Dynacamp03','Snakes',60,65,1180,1420);
		$dynas[] = array(655,'Dynacamp04','Skin Spiders',75,80,2220,1500);
		$dynas[] = array(655,'Dynacamp05','Scavenger Dogs',55,60,460,580);
		$dynas[] = array(655,'Dynacamp06','Scorpiods',40,45,1220,2100);
		$dynas[] = array(655,'Dynacamp0','Hammerbeasts',35,40,1700,2740);
		$dynas[] = array(655,'Dynacamp08','Snakes',85,90,3020,300);
		$dynas[] = array(655,'Dynacamp09','Skin Spiders',75,80,2940,2020);
		$dynas[] = array(655,'Dynacamp10','Shadowmutants',50,55,3020,2220);
		$dynas[] = array(655,'Dynacamp11','Hammerbeasts',70,75,4180,420);
		$dynas[] = array(600,'Dynacamp1','Manteze',60,65,700,2180);
		$dynas[] = array(600,'Dynacamp2','Manteze',60,65,580,2540);
		$dynas[] = array(600,'Dynacamp3','Rhinomen',55,60,1140,980);
		$dynas[] = array(600,'Dynacamp4','Rhinomen',45,50,1020,300);
		$dynas[] = array(600,'Dynacamp5','Rhinomen',50,55,1220,340);
		$dynas[] = array(600,'Dynacamp6','Rhinomen',65,70,1380,540);
		$dynas[] = array(600,'Dynacamp7','Rhinomen',60,65,1340,900);
		$dynas[] = array(600,'Dynacamp8','Lizards',20,25,1020,2740);
		$dynas[] = array(600,'Dynacamp9','Blubbags',51,56,1300,2340);
		$dynas[] = array(600,'Dynacamp10','Spiders',50,55,1420,1540);
		$dynas[] = array(600,'Dynacamp11','Lizards',15,20,1740,2660);
		$dynas[] = array(600,'Dynacamp12','Rhinomen',60,65,1940,1860);
		$dynas[] = array(600,'Dynacamp13','Rhinomen',80,85,1860,1100);
		$dynas[] = array(600,'Dynacamp14','Bileswarms',105,110,1700,820);
		$dynas[] = array(600,'Dynacamp15','Unfinished Breed',60,65,2060,420);
		$dynas[] = array(600,'Dynacamp16','Blubbags',50,55,3060,2220);
		$dynas[] = array(600,'Dynacamp17','Leets',30,35,3340,2860);
		$dynas[] = array(600,'Dynacamp18','Manteze',55,60,4020,1780);
		$dynas[] = array(600,'Dynacamp19','Manteze',60,65,4060,1340);
		$dynas[] = array(600,'Dynacamp20','Manteze',65,70,4220,380);
		$dynas[] = array(605,'Dynacamp1','Snakes',140,145,460,780);
		$dynas[] = array(605,'Dynacamp2','Enigmas',135,140,580,1820);
		$dynas[] = array(605,'Dynacamp3','Ninjadroids',125,130,380,2260);
		$dynas[] = array(605,'Dynacamp4','Quake Lizards',135,140,820,1940);
		$dynas[] = array(605,'Dynacamp5','Pit Lizards',155,160,820,1340);
		$dynas[] = array(605,'Dynacamp6','Quake Lizards',135,140,900,740);
		$dynas[] = array(605,'Dynacamp7','Pit Lizards',160,165,1500,660);
		$dynas[] = array(605,'Dynacamp8','Snakes',155,160,1500,2100);
		$dynas[] = array(605,'Dynacamp9','Enigmas',110,115,1660,3100);
		$dynas[] = array(605,'Dynacamp10','Bileswarms',130,135,1700,2580);
		$dynas[] = array(605,'Dynacamp11','Nanofreaks',151,155,1660,1780);
		$dynas[] = array(605,'Dynacamp12','Snakes',150,155,1700,1380);
		$dynas[] = array(605,'Dynacamp13','Snakes',155,160,1780,1140);
		$dynas[] = array(605,'Dynacamp14','Swampghouls',170,175,1700,540);
		$dynas[] = array(605,'Dynacamp15','Nanofreaks',165,170,2380,740);
		$dynas[] = array(605,'Dynacamp16','Snakes',160,165,2380,1460);
		$dynas[] = array(605,'Dynacamp17','Swampghouls',170,175,2300,1260);
		$dynas[] = array(605,'Dynacamp18','Ninjadroids',135,140,2500,2140);
		$dynas[] = array(605,'Dynacamp19','Ninjadroids',135,140,2180,2540);
		$dynas[] = array(605,'Dynacamp20','Ottous',110,115,2140,2980);
		$dynas[] = array(565,'Dynacamp1','Lizards',20,25,340,1740);
		$dynas[] = array(565,'Dynacamp3','Leets',30,35,460,2380);
		$dynas[] = array(565,'Dynacamp4','Leets',30,35,740,2900);
		$dynas[] = array(565,'Dynacamp5','Leets',30,35,1220,2900);
		$dynas[] = array(565,'Dynacamp6','Rhinomen',10,15,1260,2540);
		$dynas[] = array(565,'Dynacamp7','Rhinomen',10,15,1340,2340);
		$dynas[] = array(565,'Dynacamp8','Rhinomen',30,35,1460,2140);
		$dynas[] = array(565,'Dynacamp9','Rhinomen',30,35,1700,1580);
		$dynas[] = array(565,'Dynacamp10','Eyemutants',15,20,2180,2740);
		$dynas[] = array(565,'Dynacamp11','Snakes',30,35,2100,2220);
		$dynas[] = array(565,'Dynacamp12','Brontos',20,25,2340,1060);
		$dynas[] = array(565,'Dynacamp13','Scorpiods',30,35,2340,660);
		$dynas[] = array(565,'Dynacamp14','Scorpiods',30,35,2620,300);
		$dynas[] = array(565,'Dynacamp15','Buzzsaws',20,25,2820,700);
		$dynas[] = array(565,'Dynacamp16','Rhinomen',45,50,2820,1220);
		$dynas[] = array(565,'Dynacamp17','Rhinomen',50,55,2660,1460);
		$dynas[] = array(565,'Dynacamp18','Salamanders',30,35,2740,2460);
		$dynas[] = array(565,'Dynacamp19','Fleas',25,30,3260,2900);
		$dynas[] = array(565,'Dynacamp20','Minibulls',40,45,3420,2100);
		$dynas[] = array(565,'Dynacamp21','Rhinomen',40,45,3500,1300);
		$dynas[] = array(570,'Dynacamp1','Anuns',145,150,380,500);
		$dynas[] = array(570,'Dynacamp2','Anuns',145,150,380,900);
		$dynas[] = array(570,'Dynacamp3','Sandworms',145,150,420,1500);
		$dynas[] = array(570,'Dynacamp4','Anuns',150,155,700,2460);
		$dynas[] = array(570,'Dynacamp5','Anuns',150,155,460,3140);
		$dynas[] = array(570,'Dynacamp6','Anuns',150,155,1340,3060);
		$dynas[] = array(570,'Dynacamp7','Anuns',150,155,1260,2860);
		$dynas[] = array(570,'Dynacamp8','Mantis',76,90,1140,1140);
		$dynas[] = array(570,'Dynacamp9','Mantis',135,140,1460,860);
		$dynas[] = array(570,'Dynacamp10','Cyborgs',85,90,1940,1380);
		$dynas[] = array(570,'Dynacamp11','Mantis',165,170,2220,3340);
		$dynas[] = array(570,'Dynacamp12','Mantis',165,170,2460,2660);
		$dynas[] = array(570,'Dynacamp13','Mantis',135,140,2660,2300);
		$dynas[] = array(570,'Dynacamp14','Mantis',165,170,2740,2460);
		$dynas[] = array(570,'Dynacamp15','Mantis',165,170,2980,2940);
		$dynas[] = array(570,'Dynacamp16','Mantis',165,170,3100,3260);
		$dynas[] = array(570,'Dynacamp18','Anuns',165,170,3500,2020);
		$dynas[] = array(570,'Dynacamp20','Anuns',105,110,3060,900);
		$dynas[] = array(570,'Dynacamp21','Unknown',171,190,3460,294);
		$dynas[] = array(570,'Dynacamp22','Unknown',121,141,3980,1780);
		$dynas[] = array(551,'Dynacamp','Template',70,75,140,2700);
		$dynas[] = array(551,'Dynacamp1','Biofreaks',35,40,340,1060);
		$dynas[] = array(551,'Dynacamp2','Scorpiods',45,50,540,1460);
		$dynas[] = array(551,'Dynacamp3','Skin Spiders',50,55,620,3060);
		$dynas[] = array(551,'Dynacamp4','Hammerbeasts',70,75,980,3300);
		$dynas[] = array(551,'Dynacamp5','Skin Spider',55,60,1260,2540);
		$dynas[] = array(551,'Dynacamp6','Clawfingers',40,45,1140,1700);
		$dynas[] = array(551,'Dynacamp7','Hounds',40,45,1580,1140);
		$dynas[] = array(551,'Dynacamp8','Hounds',40,45,1420,1140);
		$dynas[] = array(551,'Dynacamp9','Hounds',40,45,1380,1380);
		$dynas[] = array(551,'Dynacamp10','Blubbags',35,40,1100,2020);
		$dynas[] = array(551,'Dynacamp11','Skin Spiders',55,60,1340,2500);
		$dynas[] = array(551,'Dynacamp13','Rollerrats',35,40,1740,1220);
		$dynas[] = array(551,'Dynacamp12','Hammerbeast',70,75,1540,3340);
		$dynas[] = array(551,'Dynacamp14','Blubbags',40,45,1700,1740);
		$dynas[] = array(551,'Dynacamp15','Hammerbeasts',70,75,1700,3380);
		$dynas[] = array(551,'Dynacamp16','Skin Spiders',45,50,1980,2580);
		$dynas[] = array(551,'Dynacamp17','Blubbags',40,45,2300,1340);
		$dynas[] = array(551,'Dynacamp18','Blubbags',45,50,2420,1340);
		$dynas[] = array(551,'Dynacamp19','Blubbags',50,55,2340,1500);
		$dynas[] = array(551,'Dynacamp20','Blubbags',55,60,2340,1420);	

		$all = 0; $blob = "";
		if($lvl==0) {
			return $this->bot->send_output($name, "No level given ...", $origin);
		}				
		foreach($dynas as $dyna) {
			if($lvl>=$dyna[3] && $lvl<=$dyna[4]) {
					$waypoint = $this->bot->core("tools")->chatcmd($dyna[5]." ".$dyna[6]." ".$dyna[0], "Click for waypoint", "waypoint");
					$blob .= $dyna[1]." : ".$dyna[3]."-".$dyna[4]." ".$dyna[2]." ".$waypoint."\n";
					$all++;
			}
		}		
		return $this->bot->send_output($name, $all." element(s) found: ".$this->bot->core("tools")->make_blob("click to view", $blob), $origin);
	}
}
?>
