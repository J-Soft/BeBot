<?php
/*
* Spirits.php - Module Spirits
* Adapted from Budabots's work by Jaqueme & Wolfbiter
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

$Spirits = new Spirits($bot);

class Spirits extends BaseActiveModule
{
	function __construct (&$bot)
	{
		parent::__construct($bot, get_class($this));

		$this->register_command('all', 'spirits', 'GUEST');
		$this -> register_alias("spirits", "spirit");

		$this -> help['description'] = "Search and show Spirits and their details.";
		$this -> help['command']['Spirits <param(s)>'] = "Search for existing Spirits by QL and/or slot or name.";
	}

	function command_handler($source, $msg, $origin)
	{
		$this->error->reset();
		
		$vars = explode(' ', strtolower($msg));
		$command = $vars[0];
		
		$ql = 0; $slot = ""; $words = "";
		$q = false; $s = false;
		for($i=1;$i<count($vars);$i++) {
			if(!$s&&preg_match("/(chest|ear|eye|feet|head|larm|legs|lhand|lwrist|rarm|rhand|rwrist|waist)/i", strtolower($vars[$i]))) {
				$slot = ucfirst(strtolower($vars[$i]));
				$s = true;
			} elseif (!$q&&is_numeric($vars[$i])) {
				$ql = $vars[$i];
				$q = true;
			} else {
				$words .= $vars[$i];
			}			
		}

		switch($command)
		{
			case 'spirits':
				if($ql>0||$slot!=""||$words!="") {
					return $this -> send_spirits($source, $origin, $ql, $slot, $words);
				} else {
					$this->bot->send_help($source);
				}
				break;		
			default:				
				$this -> error -> set("Broken plugin, received unhandled command: $command");
				return($this->error->message());
		}
	}
	
	function send_spirits($name, $origin, $ql=0, $slot="", $words="")
	{
		// aoid name ql slot lvl agi sen
		$sptab[218576]=array('Whispering Spirit',1,'Rarm',2,5,5);
		$sptab[224645]=array('Bitter Spirit of True Seeing',1,'Eye',1,3,3);
		$sptab[224646]=array('Blue Spirit of True Seeing',5,'Eye',4,12,12);
		$sptab[224647]=array('Discouraged Spirit of True Seeing',25,'Eye',19,57,57);
		$sptab[224648]=array('Dismal Spirit of True Seeing',30,'Eye',22,69,69);
		$sptab[224649]=array('Grave Spirit of True Seeing',60,'Eye',44,136,136);
		$sptab[224650]=array('Heartsick Spirit of True Seeing',80,'Eye',59,181,181);
		$sptab[224651]=array('Melancholic Spirit of True Seeing',110,'Eye',81,249,249);
		$sptab[224652]=array('Mourning Spirit of True Seeing',130,'Eye',95,293,293);
		$sptab[224653]=array('Pathetic Spirit of True Seeing',160,'Eye',117,361,361);
		$sptab[224654]=array('Poor Spirit of True Seeing',190,'Eye',139,429,429);
		$sptab[224655]=array('Rejected Spirit of True Seeing',200,'Eye',146,451,451);
		$sptab[224656]=array('Dispirited Spirit of True Seeing',210,'Eye',154,683,683);
		$sptab[224657]=array('Sorrowful Spirit of True Seeing',230,'Eye',168,749,749);
		$sptab[224658]=array('Tragic Spirit of True Seeing',250,'Eye',183,1063,1063);
		$sptab[224659]=array('Unhappy Spirit of True Seeing',270,'Eye',198,1149,1149);
		$sptab[224661]=array('Bitter Spirit of Discerning Weakness',1,'Eye',1,3,3);
		$sptab[224662]=array('Blue Spirit of Discerning Weakness',5,'Eye',4,12,12);
		$sptab[224663]=array('Comfortless Spirit of Discerning Weakness',10,'Eye',8,23,23);
		$sptab[224664]=array('Grave Spirit of Discerning Weakness',60,'Eye',44,136,136);
		$sptab[224665]=array('Heartsick Spirit of Discerning Weakness',80,'Eye',59,181,181);
		$sptab[224666]=array('Joyless Spirit of Discerning Weakness',90,'Eye',66,203,203);
		$sptab[224667]=array('Lonely Spirit of Discerning Weakness',100,'Eye',73,226,226);
		$sptab[224668]=array('Pessimistic Spirit of Discerning Weakness',170,'Eye',125,383,383);
		$sptab[224669]=array('Pitiable Spirit of Discerning Weakness',180,'Eye',132,406,406);
		$sptab[224670]=array('Rejected Spirit of Discerning Weakness',200,'Eye',146,451,451);
		$sptab[224671]=array('Somber Spirit of Discerning Weakness',220,'Eye',161,716,716);
		$sptab[224672]=array('Sorrowful Spirit of Discerning Weakness',230,'Eye',168,749,749);
		$sptab[224673]=array('Sorry Spirit of Discerning Weakness',240,'Eye',176,781,781);
		$sptab[224674]=array('Unhappy Spirit of Discerning Weakness',270,'Eye',198,1149,1149);
		$sptab[224675]=array('Weeping Spirit of Discerning Weakness',290,'Eye',212,1233,1233);
		$sptab[224677]=array('Bitter Spirit of Essence',1,'Legs',1,3,3);
		$sptab[224678]=array('Blue Spirit of Essence',5,'Legs',4,12,12);
		$sptab[224679]=array('Despairing Spirit of Essence',20,'Eye',15,46,46);
		$sptab[224680]=array('Discouraged Spirit of Essence',25,'Legs',19,57,57);
		$sptab[224681]=array('Dismal Spirit of Essence',30,'Legs',22,69,69);
		$sptab[224682]=array('Heartsick Spirit of Essence',80,'Legs',59,181,181);
		$sptab[224683]=array('Miserable Spirit of Essence',120,'Legs',88,271,271);
		$sptab[224684]=array('Mourning Spirit of Essence',130,'Eye',95,293,293);
		$sptab[224685]=array('Oppressed Spirit of Essence',150,'Eye',110,339,339);
		$sptab[224686]=array('Pathetic Spirit of Essence',160,'Legs',117,361,361);
		$sptab[224687]=array('Dispirited Spirit of Essence',210,'Eye',154,683,683);
		$sptab[224688]=array('Sorry Spirit of Essence',240,'Legs',176,781,781);
		$sptab[224689]=array('Tragic Spirit of Essence',250,'Eye',183,1063,1063);
		$sptab[224690]=array('Unhappy Spirit of Essence',270,'Legs',198,1149,1149);
		$sptab[224691]=array('Upset Spirit of Essence',280,'Legs',205,1191,1191);
		$sptab[224692]=array('Weeping Spirit of Essence',290,'Eye',212,1233,1233);
		$sptab[224693]=array('Wistful Spirit of Essence',300,'Legs',219,1276,1276);
		$sptab[224694]=array('Bitter Spirit of Clear Thought',1,'Head',1,3,3);
		$sptab[224695]=array('Blue Spirit of Clear Thought',5,'Head',4,12,12);
		$sptab[224696]=array('Depressed Spirit of Clear Thought',15,'Head',11,35,35);
		$sptab[224697]=array('Dreary Spirit of Clear Thought',40,'Head',30,91,91);
		$sptab[224698]=array('Heartsick Spirit of Clear Thought',80,'Head',59,181,181);
		$sptab[224699]=array('Joyless Spirit of Clear Thought',90,'Head',66,203,203);
		$sptab[224700]=array('Lonely Spirit of Clear Thought',100,'Head',73,226,226);
		$sptab[224701]=array('Miserable Spirit of Clear Thought',120,'Head',88,271,271);
		$sptab[224702]=array('Nostalgic Spirit of Clear Thought',140,'Head',103,316,316);
		$sptab[224703]=array('Oppressed Spirit of Clear Thought',150,'Head',110,339,339);
		$sptab[224704]=array('Rejected Spirit of Clear Thought',200,'Head',146,451,451);
		$sptab[224705]=array('Sorrowful Spirit of Clear Thought',230,'Head',168,749,749);
		$sptab[224706]=array('Tragic Spirit of Clear Thought',250,'Head',183,1063,1063);
		$sptab[224707]=array('Weeping Spirit of Clear Thought',290,'Head',212,1233,1233);
		$sptab[224708]=array('Wistful Spirit of Clear Thought',300,'Head',219,1276,1276);
		$sptab[224709]=array('Bitter Brain Spirit of Offence',1,'Head',1,3,3);
		$sptab[224710]=array('Blue Brain Spirit of Offence',5,'Head',4,12,12);
		$sptab[224711]=array('Comfortless Brain Spirit of Offence',10,'Head',8,23,23);
		$sptab[224712]=array('Dismal Brain Spirit of Offence',30,'Head',22,69,69);
		$sptab[224713]=array('Heartsick Brain Spirit of Offence',80,'Head',59,181,181);
		$sptab[224714]=array('Lonely Brain Spirit of Offence',100,'Head',73,226,226);
		$sptab[224715]=array('Melancholic Brain Spirit of Offence',110,'Head',81,249,249);
		$sptab[224716]=array('Nostalgic Brain Spirit of Offence',140,'Head',103,316,316);
		$sptab[224717]=array('Oppressed Brain Spirit of Offence',150,'Head',110,339,339);
		$sptab[224718]=array('Rejected Brain Spirit of Offence',200,'Head',146,451,451);
		$sptab[224719]=array('Dispirited Brain Spirit of Offence',210,'Head',154,683,683);
		$sptab[224720]=array('Somber Brain Spirit of Offence',220,'Head',161,716,716);
		$sptab[224721]=array('Sorry Brain Spirit of Offence',240,'Head',176,781,781);
		$sptab[224722]=array('Tragic Brain Spirit of Offence',250,'Head',183,1063,1063);
		$sptab[224723]=array('Upset Brain Spirit of Offence',280,'Head',205,1191,1191);
		$sptab[224724]=array('Weeping Brain Spirit of Offence',290,'Head',212,1233,1233);
		$sptab[224725]=array('Wistful Brain Spirit of Offence',300,'Head',219,1276,1276);
		$sptab[224726]=array('Bitter Essence Brain Spirit',1,'Head',1,3,3);
		$sptab[224727]=array('Blue Essence Brain Spirit',5,'Head',4,12,12);
		$sptab[224728]=array('Comfortless Essence Brain Spirit',10,'Head',8,23,23);
		$sptab[224729]=array('Discouraged Essence Brain Spirit',25,'Head',19,57,57);
		$sptab[224730]=array('Dismal Essence Brain Spirit',30,'Head',22,69,69);
		$sptab[224731]=array('Gloomy Essence Brain Spirit',50,'Head',37,113,113);
		$sptab[224732]=array('Grave Essence Brain Spirit',60,'Head',44,136,136);
		$sptab[224733]=array('Heartsick Essence Brain Spirit',80,'Head',59,181,181);
		$sptab[224734]=array('Joyless Essence Brain Spirit',90,'Head',66,203,203);
		$sptab[224735]=array('Miserable Essence Brain Spirit',120,'Head',88,271,271);
		$sptab[224736]=array('Pathetic Essence Brain Spirit',160,'Head',117,361,361);
		$sptab[224737]=array('Pessimistic Essence Brain Spirit',170,'Head',125,383,383);
		$sptab[224738]=array('Pitiable Essence Brain Spirit',180,'Head',132,406,406);
		$sptab[224739]=array('Poor Essence Brain Spirit',190,'Head',139,429,429);
		$sptab[224740]=array('Somber Essence Brain Spirit',220,'Head',161,716,716);
		$sptab[224741]=array('Weeping Essence Brain Spirit',290,'Head',212,1233,1233);
		$sptab[224742]=array('Wistful Essence Brain Spirit',300,'Head',219,1276,1276);
		$sptab[224743]=array('Bitter Spirit of Knowledge Whispered',1,'Ear',1,3,3);
		$sptab[224744]=array('Blue Spirit of Knowledge Whispered',5,'Ear',4,12,12);
		$sptab[224745]=array('Comfortless Spirit of Knowledge Whispered',10,'Ear',8,23,23);
		$sptab[224746]=array('Depressed Spirit of Knowledge Whispered',15,'Ear',11,35,35);
		$sptab[224747]=array('Dreary Spirit of Knowledge Whispered',40,'Ear',30,91,91);
		$sptab[224748]=array('Gloomy Spirit of Knowledge Whispered',50,'Ear',37,113,113);
		$sptab[224749]=array('Grieving Spirit of Knowledge Whispered',70,'Ear',52,159,159);
		$sptab[224750]=array('Heartsick Spirit of Knowledge Whispered',80,'Ear',59,181,181);
		$sptab[224751]=array('Joyless Spirit of Knowledge Whispered',90,'Ear',66,203,203);
		$sptab[224752]=array('Melancholic Spirit of Knowledge Whispered',110,'Ear',81,249,249);
		$sptab[224753]=array('Pitiable Spirit of Knowledge Whispered',180,'Ear',132,406,406);
		$sptab[224754]=array('Poor Spirit of Knowledge Whispered',190,'Ear',139,429,429);
		$sptab[224755]=array('Dispirited Spirit of Knowledge Whispered',210,'Ear',154,683,683);
		$sptab[224756]=array('Somber Spirit of Knowledge Whispered',220,'Ear',161,716,716);
		$sptab[224757]=array('Sorrowful Spirit of Knowledge Whispered',230,'Ear',168,749,749);
		$sptab[224758]=array('Upset Spirit of Knowledge Whispered',280,'Ear',205,1191,1191);
		$sptab[224759]=array('Wistful Spirit of Knowledge Whispered',300,'Ear',219,1276,1276);
		$sptab[224760]=array('Bitter Spirit of Strength Whispered',1,'Ear',1,3,3);
		$sptab[224761]=array('Blue Spirit of Strength Whispered',5,'Ear',4,12,12);
		$sptab[224762]=array('Depressed Spirit of Strength Whispered',15,'Ear',11,35,35);
		$sptab[224763]=array('Dismal Spirit of Strength Whispered',30,'Ear',22,69,69);
		$sptab[224764]=array('Grave Spirit of Strength Whispered',60,'Ear',44,136,136);
		$sptab[224765]=array('Grieving Spirit of Strength Whispered',70,'Ear',52,159,159);
		$sptab[224766]=array('Heartsick Spirit of Strength Whispered',80,'Ear',59,181,181);
		$sptab[224767]=array('Lonely Spirit of Strength Whispered',100,'Ear',73,226,226);
		$sptab[224768]=array('Melancholic Spirit of Strength Whispered',110,'Ear',81,249,249);
		$sptab[224769]=array('Pathetic Spirit of Strength Whispered',160,'Ear',117,361,361);
		$sptab[224770]=array('Pessimistic Spirit of Strength Whispered',170,'Ear',125,383,383);
		$sptab[224771]=array('Poor Spirit of Strength Whispered',190,'Ear',139,429,429);
		$sptab[224772]=array('Rejected Spirit of Strength Whispered',200,'Ear',146,451,451);
		$sptab[224773]=array('Sorrowful Spirit of Strength Whispered',230,'Ear',168,749,749);
		$sptab[224774]=array('Troubled Spirit of Strength Whispered',260,'Ear',190,1106,1106);
		$sptab[224775]=array('Upset Spirit of Strength Whispered',280,'Ear',205,1191,1191);
		$sptab[224776]=array('Weeping Spirit of Strength Whispered',290,'Ear',212,1233,1233);
		$sptab[224777]=array('Wistful Spirit of Strength Whispered',300,'Ear',219,1276,1276);
		$sptab[224778]=array('Bitter Spirit of Essence Whispered',1,'Ear',1,3,3);
		$sptab[224779]=array('Blue Spirit of Essence Whispered',5,'Ear',4,12,12);
		$sptab[224780]=array('Depressed Spirit of Essence Whispered',15,'Ear',11,35,35);
		$sptab[224781]=array('Despairing Spirit of Essence Whispered',20,'Ear',15,46,46);
		$sptab[224782]=array('Discouraged Spirit of Essence Whispered',25,'Ear',19,57,57);
		$sptab[224783]=array('Gloomy Spirit of Essence Whispered',50,'Ear',37,113,113);
		$sptab[224784]=array('Lonely Spirit of Essence Whispered',100,'Ear',73,226,226);
		$sptab[224785]=array('Pathetic Spirit of Essence Whispered',160,'Ear',117,361,361);
		$sptab[224786]=array('Pitiable Spirit of Essence Whispered',180,'Ear',132,406,406);
		$sptab[224787]=array('Poor Spirit of Essence Whispered',190,'Ear',139,429,429);
		$sptab[224788]=array('Rejected Spirit of Essence Whispered',200,'Ear',146,451,451);
		$sptab[224789]=array('Somber Spirit of Essence Whispered',220,'Ear',161,716,716);
		$sptab[224790]=array('Sorrowful Spirit of Essence Whispered',230,'Ear',168,749,749);
		$sptab[224791]=array('Sorry Spirit of Essence Whispered',240,'Ear',176,781,781);
		$sptab[224792]=array('Tragic Spirit of Essence Whispered',250,'Ear',183,1063,1063);
		$sptab[224793]=array('Unhappy Spirit of Essence Whispered',270,'Ear',198,1149,1149);
		$sptab[224794]=array('Upset Spirit of Essence Whispered',280,'Ear',205,1191,1191);
		$sptab[224795]=array('Wistful Spirit of Essence Whispered',300,'Ear',219,1276,1276);
		$sptab[224796]=array('Bitter Right Limb Spirit of Strength',1,'Rarm',1,3,3);
		$sptab[224797]=array('Blue Right Limb Spirit of Strength',5,'Rarm',4,12,12);
		$sptab[224798]=array('Depressed Right Limb Spirit of Strength',15,'Rarm',11,35,35);
		$sptab[224799]=array('Dismal Right Limb Spirit of Strength',30,'Rarm',22,69,69);
		$sptab[224800]=array('Dreary Right Limb Spirit of Strength',40,'Rarm',30,91,91);
		$sptab[224801]=array('Grave Right Limb Spirit of Strength',60,'Rarm',44,136,136);
		$sptab[224802]=array('Heartsick Right Limb Spirit of Strength',80,'Rarm',59,181,181);
		$sptab[224803]=array('Lonely Right Limb Spirit of Strength',100,'Rarm',73,226,226);
		$sptab[224804]=array('Melancholic Right Limb Spirit of Strength',110,'Rarm',81,249,249);
		$sptab[224805]=array('Pessimistic Right Limb Spirit of Strength',170,'Rarm',125,383,383);
		$sptab[224806]=array('Pitiable Right Limb Spirit of Strength',180,'Rarm',132,406,406);
		$sptab[224807]=array('Poor Right Limb Spirit of Strength',190,'Rarm',139,429,429);
		$sptab[224808]=array('Rejected Right Limb Spirit of Strength',200,'Rarm',146,451,451);
		$sptab[224809]=array('Tragic Right Limb Spirit of Strength',250,'Rarm',183,1063,1063);
		$sptab[224810]=array('Unhappy Right Limb Spirit of Strength',270,'Rarm',198,1149,1149);
		$sptab[224811]=array('Upset Right Limb Spirit of Strength',280,'Rarm',205,1191,1191);
		$sptab[224812]=array('Wistful Right Limb Spirit of Strength',300,'Rarm',219,1276,1276);
		$sptab[224813]=array('Bitter Right Limb Spirit of Weakness',1,'Rarm',1,3,3);
		$sptab[224814]=array('Blue Right Limb Spirit of Weakness',5,'Rarm',4,12,12);
		$sptab[224815]=array('Comfortless Right Limb Spirit of Weakness',10,'Rarm',8,23,23);
		$sptab[224816]=array('Despairing Right Limb Spirit of Weakness',20,'Rarm',15,46,46);
		$sptab[224817]=array('Gloomy Right Limb Spirit of Weakness',50,'Rarm',37,113,113);
		$sptab[224818]=array('Heartsick Right Limb Spirit of Weakness',80,'Rarm',59,181,181);
		$sptab[224819]=array('Lonely Right Limb Spirit of Weakness',100,'Rarm',73,226,226);
		$sptab[224820]=array('Melancholic Right Limb Spirit of Weakness',110,'Rarm',81,249,249);
		$sptab[224821]=array('Mourning Right Limb Spirit of Weakness',130,'Rarm',95,293,293);
		$sptab[224822]=array('Nostalgic Right Limb Spirit of Weakness',140,'Rarm',103,316,316);
		$sptab[224823]=array('Oppressed Right Limb Spirit of Weakness',150,'Rarm',110,339,339);
		$sptab[224824]=array('Poor Right Limb Spirit of Weakness',190,'Rarm',139,429,429);
		$sptab[224825]=array('Rejected Right Limb Spirit of Weakness',200,'Rarm',146,451,451);
		$sptab[224826]=array('Somber Right Limb Spirit of Weakness',220,'Rarm',161,716,716);
		$sptab[224827]=array('Upset Right Limb Spirit of Weakness',280,'Rarm',205,1191,1191);
		$sptab[224828]=array('Wistful Right Limb Spirit of Weakness',300,'Rarm',219,1276,1276);
		$sptab[224829]=array('Bitter Right Limb Spirit of Essence',1,'Rarm',1,3,3);
		$sptab[224830]=array('Blue Right Limb Spirit of Essence',5,'Rarm',4,12,12);
		$sptab[224831]=array('Comfortless Right Limb Spirit of Essence',10,'Rarm',8,23,23);
		$sptab[224832]=array('Depressed Right Limb Spirit of Essence',15,'Rarm',11,35,35);
		$sptab[224833]=array('Despairing Right Limb Spirit of Essence',20,'Rarm',15,46,46);
		$sptab[224834]=array('Gloomy Right Limb Spirit of Essence',50,'Rarm',37,113,113);
		$sptab[224835]=array('Grave Right Limb Spirit of Essence',60,'Rarm',44,136,136);
		$sptab[224836]=array('Pathetic Right Limb Spirit of Essence',160,'Rarm',117,361,361);
		$sptab[224837]=array('Pitiable Right Limb Spirit of Essence',180,'Rarm',132,406,406);
		$sptab[224838]=array('Poor Right Limb Spirit of Essence',190,'Rarm',139,429,429);
		$sptab[224839]=array('Sorrowful Right Limb Spirit of Essence',230,'Rarm',168,749,749);
		$sptab[224840]=array('Sorry Right Limb Spirit of Essence',240,'Rarm',176,781,781);
		$sptab[224841]=array('Troubled Right Limb Spirit of Essence',260,'Rarm',190,1106,1106);
		$sptab[224842]=array('Unhappy Right Limb Spirit of Essence',270,'Rarm',198,1149,1149);
		$sptab[224843]=array('Weeping Right Limb Spirit of Essence',290,'Rarm',212,1233,1233);
		$sptab[224844]=array('Wistful Right Limb Spirit of Essence',300,'Rarm',219,1276,1276);
		$sptab[224845]=array('Bitter Midriff Spirit of Essence',1,'Waist',1,3,3);
		$sptab[224847]=array('Comfortless Midriff Spirit of Essence',10,'Waist',8,23,23);
		$sptab[224848]=array('Despairing Midriff Spirit of Essence',20,'Waist',15,46,46);
		$sptab[224849]=array('Dreary Midriff Spirit of Essence',40,'Waist',30,91,91);
		$sptab[224850]=array('Grave Midriff Spirit of Essence',60,'Waist',44,136,136);
		$sptab[224851]=array('Heartsick Midriff Spirit of Essence',80,'Waist',59,181,181);
		$sptab[224852]=array('Joyless Midriff Spirit of Essence',90,'Waist',66,203,203);
		$sptab[224853]=array('Melancholic Midriff Spirit of Essence',110,'Waist',81,249,249);
		$sptab[224854]=array('Miserable Midriff Spirit of Essence',120,'Waist',88,271,271);
		$sptab[224855]=array('Pitiable Midriff Spirit of Essence',180,'Waist',132,406,406);
		$sptab[224856]=array('Dispirited Midriff Spirit of Essence',210,'Waist',154,683,683);
		$sptab[224857]=array('Sorry Midriff Spirit of Essence',240,'Waist',176,781,781);
		$sptab[224858]=array('Tragic Midriff Spirit of Essence',250,'Waist',183,1063,1063);
		$sptab[224859]=array('Troubled Midriff Spirit of Essence',260,'Waist',190,1106,1106);
		$sptab[224860]=array('Upset Midriff Spirit of Essence',280,'Waist',205,1191,1191);
		$sptab[224861]=array('Weeping Midriff Spirit of Essence',290,'Waist',212,1233,1233);
		$sptab[224862]=array('Wistful Midriff Spirit of Essence',300,'Waist',219,1276,1276);
		$sptab[224863]=array('Bitter Midriff Spirit of Knowledge',1,'Waist',1,3,3);
		$sptab[224864]=array('Blue Midriff Spirit of Knowledge',5,'Waist',4,12,12);
		$sptab[224865]=array('Comfortless Midriff Spirit of Knowledge',10,'Waist',8,23,23);
		$sptab[224866]=array('Dreary Midriff Spirit of Knowledge',40,'Waist',30,91,91);
		$sptab[224867]=array('Gloomy Midriff Spirit of Knowledge',50,'Waist',37,113,113);
		$sptab[224868]=array('Grieving Midriff Spirit of Knowledge',70,'Waist',52,159,159);
		$sptab[224869]=array('Heartsick Midriff Spirit of Knowledge',80,'Waist',59,181,181);
		$sptab[224870]=array('Miserable Midriff Spirit of Knowledge',120,'Waist',88,271,271);
		$sptab[224871]=array('Oppressed Midriff Spirit of Knowledge',150,'Waist',110,339,339);
		$sptab[224872]=array('Pathetic Midriff Spirit of Knowledge',160,'Waist',117,361,361);
		$sptab[224873]=array('Pitiable Midriff Spirit of Knowledge',180,'Waist',132,406,406);
		$sptab[224874]=array('Poor Midriff Spirit of Knowledge',190,'Waist',139,429,429);
		$sptab[224875]=array('Sorrowful Midriff Spirit of Knowledge',230,'Waist',168,749,749);
		$sptab[224876]=array('Sorry Midriff Spirit of Knowledge',240,'Waist',176,781,781);
		$sptab[224877]=array('Tragic Midriff Spirit of Knowledge',250,'Waist',183,1063,1063);
		$sptab[224878]=array('Troubled Midriff Spirit of Knowledge',260,'Waist',190,1106,1106);
		$sptab[224879]=array('Unhappy Midriff Spirit of Knowledge',270,'Waist',198,1149,1149);
		$sptab[224880]=array('Wistful Midriff Spirit of Knowledge',300,'Waist',219,1276,1276);
		$sptab[224881]=array('Bitter Midriff Spirit of Weakness',1,'Waist',1,3,3);
		$sptab[224882]=array('Blue Midriff Spirit of Weakness',5,'Waist',4,12,12);
		$sptab[224883]=array('Depressed Midriff Spirit of Weakness',15,'Waist',11,35,35);
		$sptab[224884]=array('Dreary Midriff Spirit of Weakness',40,'Waist',30,91,91);
		$sptab[224885]=array('Gloomy Midriff Spirit of Weakness',50,'Waist',37,113,113);
		$sptab[224886]=array('Grave Midriff Spirit of Weakness',60,'Waist',44,136,136);
		$sptab[224887]=array('Grieving Midriff Spirit of Weakness',70,'Waist',52,159,159);
		$sptab[224888]=array('Melancholic Midriff Spirit of Weakness',110,'Waist',81,249,249);
		$sptab[224889]=array('Nostalgic Midriff Spirit of Weakness',140,'Waist',103,316,316);
		$sptab[224890]=array('Pathetic Midriff Spirit of Weakness',160,'Waist',117,361,361);
		$sptab[224891]=array('Pessimistic Midriff Spirit of Weakness',170,'Waist',125,383,383);
		$sptab[224892]=array('Poor Midriff Spirit of Weakness',190,'Waist',139,429,429);
		$sptab[224893]=array('Rejected Midriff Spirit of Weakness',200,'Waist',146,451,451);
		$sptab[224894]=array('Somber Midriff Spirit of Weakness',220,'Waist',161,716,716);
		$sptab[224895]=array('Sorrowful Midriff Spirit of Weakness',230,'Waist',168,749,749);
		$sptab[224896]=array('Sorry Midriff Spirit of Weakness',240,'Waist',176,781,781);
		$sptab[224897]=array('Weeping Midriff Spirit of Weakness',290,'Waist',212,1233,1233);
		$sptab[224898]=array('Wistful Midriff Spirit of Weakness',300,'Waist',219,1276,1276);
		$sptab[224899]=array('Bitter Midriff Spirit of Strength',1,'Waist',1,3,3);
		$sptab[224900]=array('Blue Midriff Spirit of Strength',5,'Waist',4,12,12);
		$sptab[224901]=array('Comfortless Midriff Spirit of Strength',10,'Waist',8,23,23);
		$sptab[224902]=array('Depressed Midriff Spirit of Strength',15,'Waist',11,35,35);
		$sptab[224903]=array('Despairing Midriff Spirit of Strength',20,'Waist',15,46,46);
		$sptab[224904]=array('Discouraged Midriff Spirit of Strength',25,'Waist',19,57,57);
		$sptab[224905]=array('Grieving Midriff Spirit of Strength',70,'Waist',52,159,159);
		$sptab[224906]=array('Oppressed Midriff Spirit of Strength',150,'Waist',110,339,339);
		$sptab[224907]=array('Pessimistic Midriff Spirit of Strength',170,'Waist',125,383,383);
		$sptab[224908]=array('Pitiable Midriff Spirit of Strength',180,'Waist',132,406,406);
		$sptab[224909]=array('Rejected Midriff Spirit of Strength',200,'Waist',146,451,451);
		$sptab[224910]=array('Dispirited Midriff Spirit of Strength',210,'Waist',154,683,683);
		$sptab[224911]=array('Sorrowful Midriff Spirit of Strength',230,'Waist',168,749,749);
		$sptab[224912]=array('Tragic Midriff Spirit of Strength',250,'Waist',183,1063,1063);
		$sptab[224913]=array('Troubled Midriff Spirit of Strength',260,'Waist',190,1106,1106);
		$sptab[224914]=array('Weeping Midriff Spirit of Strength',290,'Waist',212,1233,1233);
		$sptab[224915]=array('Wistful Midriff Spirit of Strength',300,'Waist',219,1276,1276);
		$sptab[224916]=array('Bitter Heart Spirit of Knowledge',1,'Chest',1,3,3);
		$sptab[224917]=array('Blue Heart Spirit of Knowledge',5,'Chest',4,12,12);
		$sptab[224918]=array('Comfortless Heart Spirit of Knowledge',10,'Chest',8,23,23);
		$sptab[224919]=array('Depressed Heart Spirit of Knowledge',15,'Chest',11,35,35);
		$sptab[224920]=array('Despairing Heart Spirit of Knowledge',20,'Chest',15,46,46);
		$sptab[224921]=array('Joyless Heart Spirit of Knowledge',90,'Chest',66,203,203);
		$sptab[224922]=array('Melancholic Heart Spirit of Knowledge',110,'Chest',81,249,249);
		$sptab[224923]=array('Oppressed Heart Spirit of Knowledge',150,'Chest',110,339,339);
		$sptab[224924]=array('Pathetic Heart Spirit of Knowledge',160,'Chest',117,361,361);
		$sptab[224925]=array('Dispirited Heart Spirit of Knowledge',210,'Chest',154,683,683);
		$sptab[224926]=array('Sorry Heart Spirit of Knowledge',240,'Chest',176,781,781);
		$sptab[224927]=array('Unhappy Heart Spirit of Knowledge',270,'Chest',198,1149,1149);
		$sptab[224928]=array('Weeping Heart Spirit of Knowledge',290,'Chest',212,1233,1233);
		$sptab[224929]=array('Wistful Heart Spirit of Knowledge',300,'Chest',219,1276,1276);
		$sptab[224930]=array('Bitter Heart Spirit of Essence',1,'Chest',1,3,3);
		$sptab[224931]=array('Blue Heart Spirit of Essence',5,'Chest',4,12,12);
		$sptab[224932]=array('Comfortless Heart Spirit of Essence',10,'Chest',8,23,23);
		$sptab[224933]=array('Grieving Heart Spirit of Essence',70,'Chest',52,159,159);
		$sptab[224934]=array('Joyless Heart Spirit of Essence',90,'Chest',66,203,203);
		$sptab[224935]=array('Upset Heart Spirit of Essence',280,'Chest',205,1191,1191);
		$sptab[224936]=array('Wistful Heart Spirit of Essence',300,'Chest',219,1276,1276);
		$sptab[224937]=array('Depressed Heart Spirit of Essence',15,'Chest',11,35,35);
		$sptab[224938]=array('Despairing Heart Spirit of Essence',20,'Chest',15,46,46);
		$sptab[224939]=array('Gloomy Heart Spirit of Essence',50,'Chest',37,113,113);
		$sptab[224940]=array('Heartsick Heart Spirit of Essence',80,'Chest',59,181,181);
		$sptab[224941]=array('Mourning Heart Spirit of Essence',130,'Chest',95,293,293);
		$sptab[224942]=array('Nostalgic Heart Spirit of Essence',140,'Chest',103,316,316);
		$sptab[224943]=array('Pitiable Heart Spirit of Essence',180,'Chest',132,406,406);
		$sptab[224944]=array('Dispirited Heart Spirit of Essence',210,'Chest',154,683,683);
		$sptab[224945]=array('Tragic Heart Spirit of Essence',250,'Chest',183,1063,1063);
		$sptab[224946]=array('Unhappy Heart Spirit of Essence',270,'Chest',198,1149,1149);
		$sptab[224947]=array('Bitter Heart Spirit of Strength',1,'Chest',1,3,3);
		$sptab[224948]=array('Blue Heart Spirit of Strength',5,'Chest',4,12,12);
		$sptab[224949]=array('Comfortless Heart Spirit of Strength',10,'Chest',8,23,23);
		$sptab[224950]=array('Depressed Heart Spirit of Strength',15,'Chest',11,35,35);
		$sptab[224951]=array('Despairing Heart Spirit of Strength',20,'Chest',15,46,46);
		$sptab[224952]=array('Discouraged Heart Spirit of Strength',25,'Chest',19,57,57);
		$sptab[224953]=array('Gloomy Heart Spirit of Strength',50,'Chest',37,113,113);
		$sptab[224954]=array('Miserable Heart Spirit of Strength',120,'Chest',88,271,271);
		$sptab[224955]=array('Oppressed Heart Spirit of Strength',150,'Chest',110,339,339);
		$sptab[224956]=array('Pessimistic Heart Spirit of Strength',170,'Chest',125,383,383);
		$sptab[224957]=array('Pitiable Heart Spirit of Strength',180,'Chest',132,406,406);
		$sptab[224958]=array('Poor Heart Spirit of Strength',190,'Chest',139,429,429);
		$sptab[224959]=array('Sorrowful Heart Spirit of Strength',230,'Chest',168,749,749);
		$sptab[224960]=array('Sorry Heart Spirit of Strength',240,'Chest',176,781,781);
		$sptab[224961]=array('Troubled Heart Spirit of Strength',260,'Chest',190,1106,1106);
		$sptab[224962]=array('Upset Heart Spirit of Strength',280,'Chest',205,1191,1191);
		$sptab[224963]=array('Wistful Heart Spirit of Strength',300,'Chest',219,1276,1276);
		$sptab[224964]=array('Bitter Heart Spirit of Weakness',1,'Chest',1,3,3);
		$sptab[224965]=array('Blue Heart Spirit of Weakness',5,'Chest',4,12,12);
		$sptab[224966]=array('Discouraged Heart Spirit of Weakness',25,'Chest',19,57,57);
		$sptab[224967]=array('Gloomy Heart Spirit of Weakness',50,'Chest',37,113,113);
		$sptab[224968]=array('Grave Heart Spirit of Weakness',60,'Chest',44,136,136);
		$sptab[224969]=array('Joyless Heart Spirit of Weakness',90,'Chest',66,203,203);
		$sptab[224970]=array('Mourning Heart Spirit of Weakness',130,'Chest',95,293,293);
		$sptab[224971]=array('Nostalgic Heart Spirit of Weakness',140,'Chest',103,316,316);
		$sptab[224972]=array('Pathetic Heart Spirit of Weakness',160,'Chest',117,361,361);
		$sptab[224973]=array('Pessimistic Heart Spirit of Weakness',170,'Chest',125,383,383);
		$sptab[224974]=array('Poor Heart Spirit of Weakness',190,'Chest',139,429,429);
		$sptab[224975]=array('Rejected Heart Spirit of Weakness',200,'Chest',146,451,451);
		$sptab[224976]=array('Dispirited Heart Spirit of Weakness',210,'Chest',154,683,683);
		$sptab[224977]=array('Sorry Heart Spirit of Weakness',240,'Chest',176,781,781);
		$sptab[224978]=array('Tragic Heart Spirit of Weakness',250,'Chest',183,1063,1063);
		$sptab[224979]=array('Troubled Heart Spirit of Weakness',260,'Chest',190,1106,1106);
		$sptab[224980]=array('Wistful Heart Spirit of Weakness',300,'Chest',219,1276,1276);
		$sptab[224981]=array('Bitter Left Limb Spirit of Understanding',1,'Larm',1,3,3);
		$sptab[224982]=array('Blue Left Limb Spirit of Understanding',5,'Larm',4,12,12);
		$sptab[224983]=array('Discouraged Left Limb Spirit of Understanding',25,'Larm',19,57,57);
		$sptab[224984]=array('Dreary Left Limb Spirit of Understanding',40,'Larm',30,91,91);
		$sptab[224985]=array('Grieving Left Limb Spirit of Understanding',70,'Larm',52,159,159);
		$sptab[224986]=array('Lonely Left Limb Spirit of Understanding',100,'Larm',73,226,226);
		$sptab[224987]=array('Melancholic Left Limb Spirit of Understanding',110,'Larm',81,249,249);
		$sptab[224988]=array('Mourning Left Limb Spirit of Understanding',130,'Larm',95,293,293);
		$sptab[224989]=array('Oppressed Left Limb Spirit of Understanding',150,'Larm',110,339,339);
		$sptab[224990]=array('Pessimistic Left Limb Spirit of Understanding',170,'Larm',125,383,383);
		$sptab[224991]=array('Rejected Left Limb Spirit of Understanding',200,'Larm',146,451,451);
		$sptab[224992]=array('Dispirited Left Limb Spirit of Understanding',210,'Larm',154,683,683);
		$sptab[224993]=array('Somber Left Limb Spirit of Understanding',220,'Larm',161,716,716);
		$sptab[224994]=array('Tragic Left Limb Spirit of Understanding',250,'Larm',183,1063,1063);
		$sptab[224995]=array('Troubled Left Limb Spirit of Understanding',260,'Larm',190,1106,1106);
		$sptab[224996]=array('Wistful Left Limb Spirit of Understanding',300,'Larm',219,1276,1276);
		$sptab[224997]=array('Bitter Left Limb Spirit of Weakness',1,'Larm',1,3,3);
		$sptab[224998]=array('Blue Left Limb Spirit of Weakness',5,'Larm',4,12,12);
		$sptab[224999]=array('Dreary Left Limb Spirit of Weakness',40,'Larm',30,91,91);
		$sptab[225000]=array('Grave Left Limb Spirit of Weakness',60,'Larm',44,136,136);
		$sptab[225001]=array('Heartsick Left Limb Spirit of Weakness',80,'Larm',59,181,181);
		$sptab[225002]=array('Joyless Left Limb Spirit of Weakness',90,'Larm',66,203,203);
		$sptab[225003]=array('Lonely Left Limb Spirit of Weakness',100,'Larm',73,226,226);
		$sptab[225004]=array('Melancholic Left Limb Spirit of Weakness',110,'Larm',81,249,249);
		$sptab[225005]=array('Miserable Left Limb Spirit of Weakness',120,'Larm',88,271,271);
		$sptab[225006]=array('Mourning Left Limb Spirit of Weakness',130,'Larm',95,293,293);
		$sptab[225007]=array('Pathetic Left Limb Spirit of Weakness',160,'Larm',117,361,361);
		$sptab[225008]=array('Poor Left Limb Spirit of Weakness',190,'Larm',139,429,429);
		$sptab[225009]=array('Rejected Left Limb Spirit of Weakness',200,'Larm',146,451,451);
		$sptab[225010]=array('Somber Left Limb Spirit of Weakness',220,'Larm',161,716,716);
		$sptab[225011]=array('Sorry Left Limb Spirit of Weakness',240,'Larm',176,781,781);
		$sptab[225012]=array('Unhappy Left Limb Spirit of Weakness',270,'Larm',198,1149,1149);
		$sptab[225013]=array('Weeping Left Limb Spirit of Weakness',290,'Larm',212,1233,1233);
		$sptab[225014]=array('Wistful Left Limb Spirit of Weakness',300,'Larm',219,1276,1276);
		$sptab[225015]=array('Bitter Left Limb Spirit of Strength',1,'Larm',1,3,3);
		$sptab[225016]=array('Blue Left Limb Spirit of Strength',5,'Larm',4,12,12);
		$sptab[225017]=array('Depressed Left Limb Spirit of Strength',15,'Larm',11,35,35);
		$sptab[225018]=array('Despairing Left Limb Spirit of Strength',20,'Larm',15,46,46);
		$sptab[225019]=array('Dismal Left Limb Spirit of Strength',30,'Larm',22,69,69);
		$sptab[225020]=array('Grave Left Limb Spirit of Strength',60,'Larm',44,136,136);
		$sptab[225021]=array('Heartsick Left Limb Spirit of Strength',80,'Larm',59,181,181);
		$sptab[225022]=array('Melancholic Left Limb Spirit of Strength',110,'Larm',81,249,249);
		$sptab[225023]=array('Miserable Left Limb Spirit of Strength',120,'Larm',88,271,271);
		$sptab[225024]=array('Oppressed Left Limb Spirit of Strength',150,'Larm',110,339,339);
		$sptab[225025]=array('Pathetic Left Limb Spirit of Strength',160,'Larm',117,361,361);
		$sptab[225026]=array('Pitiable Left Limb Spirit of Strength',180,'Larm',132,406,406);
		$sptab[225027]=array('Somber Left Limb Spirit of Strength',220,'Larm',161,716,716);
		$sptab[225028]=array('Sorrowful Left Limb Spirit of Strength',230,'Larm',168,749,749);
		$sptab[225029]=array('Tragic Left Limb Spirit of Strength',250,'Larm',183,1063,1063);
		$sptab[225030]=array('Troubled Left Limb Spirit of Strength',260,'Larm',190,1106,1106);
		$sptab[225031]=array('Unhappy Left Limb Spirit of Strength',270,'Larm',198,1149,1149);
		$sptab[225032]=array('Wistful Left Limb Spirit of Strength',300,'Larm',219,1276,1276);
		$sptab[225033]=array('Bitter Left Limb Spirit of Essence',1,'Larm',1,3,3);
		$sptab[225034]=array('Blue Left Limb Spirit of Essence',5,'Larm',4,12,12);
		$sptab[225035]=array('Comfortless Left Limb Spirit of Essence',10,'Larm',8,23,23);
		$sptab[225036]=array('Depressed Left Limb Spirit of Essence',15,'Larm',11,35,35);
		$sptab[225037]=array('Despairing Left Limb Spirit of Essence',20,'Larm',15,46,46);
		$sptab[225038]=array('Dreary Left Limb Spirit of Essence',40,'Larm',30,91,91);
		$sptab[225039]=array('Gloomy Left Limb Spirit of Essence',50,'Larm',37,113,113);
		$sptab[225040]=array('Grieving Left Limb Spirit of Essence',70,'Larm',52,159,159);
		$sptab[225041]=array('Melancholic Left Limb Spirit of Essence',110,'Larm',81,249,249);
		$sptab[225042]=array('Mourning Left Limb Spirit of Essence',130,'Larm',95,293,293);
		$sptab[225043]=array('Pitiable Left Limb Spirit of Essence',180,'Larm',132,406,406);
		$sptab[225044]=array('Poor Left Limb Spirit of Essence',190,'Larm',139,429,429);
		$sptab[225045]=array('Dispirited Left Limb Spirit of Essence',210,'Larm',154,683,683);
		$sptab[225046]=array('Sorrowful Left Limb Spirit of Essence',230,'Larm',168,749,749);
		$sptab[225047]=array('Tragic Left Limb Spirit of Essence',250,'Larm',183,1063,1063);
		$sptab[225048]=array('Troubled Left Limb Spirit of Essence',260,'Larm',190,1106,1106);
		$sptab[225049]=array('Upset Left Limb Spirit of Essence',280,'Larm',205,1191,1191);
		$sptab[225050]=array('Wistful Left Limb Spirit of Essence',300,'Larm',219,1276,1276);
		$sptab[225051]=array('Bitter Spirit of Right Wrist Offence',1,'Rwrist',1,3,3);
		$sptab[225052]=array('Blue Spirit of Right Wrist Offence',5,'Rwrist',4,12,12);
		$sptab[225053]=array('Comfortless Spirit of Right Wrist Offence',10,'Rwrist',8,23,23);
		$sptab[225054]=array('Despairing Spirit of Right Wrist Offence',20,'Rwrist',15,46,46);
		$sptab[225055]=array('Dismal Spirit of Right Wrist Offence',30,'Rwrist',22,69,69);
		$sptab[225056]=array('Gloomy Spirit of Right Wrist Offence',50,'Rwrist',37,113,113);
		$sptab[225057]=array('Grave Spirit of Right Wrist Offence',60,'Rwrist',44,136,136);
		$sptab[225058]=array('Heartsick Spirit of Right Wrist Offence',80,'Rwrist',59,181,181);
		$sptab[225059]=array('Melancholic Spirit of Right Wrist Offence',110,'Rwrist',81,249,249);
		$sptab[225060]=array('Miserable Spirit of Right Wrist Offence',120,'Rwrist',88,271,271);
		$sptab[225061]=array('Mourning Spirit of Right Wrist Offence',130,'Rwrist',95,293,293);
		$sptab[225062]=array('Nostalgic Spirit of Right Wrist Offence',140,'Rwrist',103,316,316);
		$sptab[225063]=array('Dispirited Spirit of Right Wrist Offence',210,'Rwrist',154,683,683);
		$sptab[225064]=array('Unhappy Spirit of Right Wrist Offence',270,'Rwrist',198,1149,1149);
		$sptab[225065]=array('Upset Spirit of Right Wrist Offence',280,'Rwrist',205,1191,1191);
		$sptab[225066]=array('Weeping Spirit of Right Wrist Offence',290,'Rwrist',212,1233,1233);
		$sptab[225067]=array('Wistful Spirit of Right Wrist Offence',300,'Rwrist',219,1276,1276);
		$sptab[225068]=array('Bitter Spirit of Right Wrist Weakness',1,'Rwrist',1,3,3);
		$sptab[225069]=array('Blue Spirit of Right Wrist Weakness',5,'Rwrist',4,12,12);
		$sptab[225070]=array('Depressed Spirit of Right Wrist Weakness',15,'Rwrist',11,35,35);
		$sptab[225071]=array('Despairing Spirit of Right Wrist Weakness',20,'Rwrist',15,46,46);
		$sptab[225072]=array('Discouraged Spirit of Right Wrist Weakness',25,'Rwrist',19,57,57);
		$sptab[225073]=array('Dreary Spirit of Right Wrist Weakness',40,'Rwrist',30,91,91);
		$sptab[225074]=array('Gloomy Spirit of Right Wrist Weakness',50,'Rwrist',37,113,113);
		$sptab[225075]=array('Grave Spirit of Right Wrist Weakness',60,'Rwrist',44,136,136);
		$sptab[225076]=array('Joyless Spirit of Right Wrist Weakness',90,'Rwrist',66,203,203);
		$sptab[225077]=array('Miserable Spirit of Right Wrist Weakness',120,'Rwrist',88,271,271);
		$sptab[225078]=array('Mourning Spirit of Right Wrist Weakness',130,'Rwrist',95,293,293);
		$sptab[225079]=array('Pessimistic Spirit of Right Wrist Weakness',170,'Rwrist',125,383,383);
		$sptab[225080]=array('Pitiable Spirit of Right Wrist Weakness',180,'Rwrist',132,406,406);
		$sptab[225081]=array('Poor Spirit of Right Wrist Weakness',190,'Rwrist',139,429,429);
		$sptab[225082]=array('Dispirited Spirit of Right Wrist Weakness',210,'Rwrist',154,683,683);
		$sptab[225083]=array('Troubled Spirit of Right Wrist Weakness',260,'Rwrist',190,1106,1106);
		$sptab[225084]=array('Wistful Spirit of Right Wrist Weakness',300,'Rwrist',219,1276,1276);
		$sptab[225085]=array('Bitter Spirit of Left Wrist Defense',1,'Lwrist',1,3,3);
		$sptab[225086]=array('Blue Spirit of Left Wrist Defense',5,'Lwrist',4,12,12);
		$sptab[225087]=array('Comfortless Spirit of Left Wrist Defense',10,'Lwrist',8,23,23);
		$sptab[225088]=array('Dismal Spirit of Left Wrist Defense',30,'Lwrist',22,69,69);
		$sptab[225089]=array('Grave Spirit of Left Wrist Defense',60,'Lwrist',44,136,136);
		$sptab[225090]=array('Grieving Spirit of Left Wrist Defense',70,'Lwrist',52,159,159);
		$sptab[225091]=array('Heartsick Spirit of Left Wrist Defense',80,'Lwrist',59,181,181);
		$sptab[225092]=array('Lonely Spirit of Left Wrist Defense',100,'Lwrist',73,226,226);
		$sptab[225093]=array('Pathetic Spirit of Left Wrist Defense',160,'Lwrist',117,361,361);
		$sptab[225094]=array('Pessimistic Spirit of Left Wrist Defense',170,'Lwrist',125,383,383);
		$sptab[225095]=array('Pitiable Spirit of Left Wrist Defense',180,'Lwrist',132,406,406);
		$sptab[225096]=array('Rejected Spirit of Left Wrist Defense',200,'Lwrist',146,451,451);
		$sptab[225097]=array('Somber Spirit of Left Wrist Defense',220,'Lwrist',161,716,716);
		$sptab[225098]=array('Sorrowful Spirit of Left Wrist Defense',230,'Lwrist',168,749,749);
		$sptab[225099]=array('Sorry Spirit of Left Wrist Defense',240,'Lwrist',176,781,781);
		$sptab[225100]=array('Tragic Spirit of Left Wrist Defense',250,'Lwrist',183,1063,1063);
		$sptab[225101]=array('Unhappy Spirit of Left Wrist Defense',270,'Lwrist',198,1149,1149);
		$sptab[225102]=array('Wistful Spirit of Left Wrist Defense',300,'Lwrist',219,1276,1276);
		$sptab[225103]=array('Bitter Spirit of Left Wrist Strength',1,'Lwrist',1,3,3);
		$sptab[225104]=array('Blue Spirit of Left Wrist Strength',5,'Lwrist',4,12,12);
		$sptab[225105]=array('Comfortless Spirit of Left Wrist Strength',10,'Lwrist',8,23,23);
		$sptab[225106]=array('Dismal Spirit of Left Wrist Strength',30,'Lwrist',22,69,69);
		$sptab[225107]=array('Grave Spirit of Left Wrist Strength',60,'Lwrist',44,136,136);
		$sptab[225108]=array('Grieving Spirit of Left Wrist Strength',70,'Lwrist',52,159,159);
		$sptab[225109]=array('Heartsick Spirit of Left Wrist Strength',80,'Lwrist',59,181,181);
		$sptab[225110]=array('Melancholic Spirit of Left Wrist Strength',110,'Lwrist',81,249,249);
		$sptab[225111]=array('Mourning Spirit of Left Wrist Strength',130,'Lwrist',95,293,293);
		$sptab[225112]=array('Pathetic Spirit of Left Wrist Strength',160,'Lwrist',117,361,361);
		$sptab[225113]=array('Pitiable Spirit of Left Wrist Strength',180,'Lwrist',132,406,406);
		$sptab[225114]=array('Poor Spirit of Left Wrist Strength',190,'Lwrist',139,429,429);
		$sptab[225115]=array('Rejected Spirit of Left Wrist Strength',200,'Lwrist',146,451,451);
		$sptab[225116]=array('Dispirited Spirit of Left Wrist Strength',210,'Lwrist',154,683,683);
		$sptab[225117]=array('Sorrowful Spirit of Left Wrist Strength',230,'Lwrist',168,749,749);
		$sptab[225118]=array('Sorry Spirit of Left Wrist Strength',240,'Lwrist',176,781,781);
		$sptab[225119]=array('Upset Spirit of Left Wrist Strength',280,'Lwrist',205,1191,1191);
		$sptab[225120]=array('Wistful Spirit of Left Wrist Strength',300,'Lwrist',219,1276,1276);
		$sptab[225121]=array('Bitter Right Hand Strength Spirit',1,'Rhand',1,3,3);
		$sptab[225122]=array('Blue Right Hand Strength Spirit',5,'Rhand',4,12,12);
		$sptab[225123]=array('Despairing Right Hand Strength Spirit',20,'Rhand',15,46,46);
		$sptab[225124]=array('Discouraged Right Hand Strength Spirit',25,'Rhand',19,57,57);
		$sptab[225125]=array('Grave Right Hand Strength Spirit',60,'Rhand',44,136,136);
		$sptab[225126]=array('Joyless Right Hand Strength Spirit',90,'Rhand',66,203,203);
		$sptab[225127]=array('Lonely Right Hand Strength Spirit',100,'Rhand',73,226,226);
		$sptab[225128]=array('Melancholic Right Hand Strength Spirit',110,'Rhand',81,249,249);
		$sptab[225129]=array('Mourning Right Hand Strength Spirit',130,'Rhand',95,293,293);
		$sptab[225130]=array('Nostalgic Right Hand Strength Spirit',140,'Rhand',103,316,316);
		$sptab[225131]=array('Oppressed Right Hand Strength Spirit',150,'Rhand',110,339,339);
		$sptab[225132]=array('Pitiable Right Hand Strength Spirit',180,'Rhand',132,406,406);
		$sptab[225133]=array('Somber Right Hand Strength Spirit',220,'Rhand',161,716,716);
		$sptab[225134]=array('Sorrowful Right Hand Strength Spirit',230,'Rhand',168,749,749);
		$sptab[225135]=array('Sorry Right Hand Strength Spirit',240,'Rhand',176,781,781);
		$sptab[225136]=array('Weeping Right Hand Strength Spirit',290,'Rhand',212,1233,1233);
		$sptab[225137]=array('Wistful Right Hand Strength Spirit',300,'Rhand',219,1276,1276);
		$sptab[225138]=array('Bitter Right Hand Defencive Spirit',1,'Rhand',1,3,3);
		$sptab[225139]=array('Blue Right Hand Defencive Spirit',5,'Rhand',4,12,12);
		$sptab[225140]=array('Despairing Right Hand Defencive Spirit',20,'Rhand',15,46,46);
		$sptab[225141]=array('Dismal Right Hand Defencive Spirit',30,'Rhand',22,69,69);
		$sptab[225142]=array('Grave Right Hand Defencive Spirit',60,'Rhand',44,136,136);
		$sptab[225143]=array('Heartsick Right Hand Defencive Spirit',80,'Rhand',59,181,181);
		$sptab[225144]=array('Lonely Right Hand Defencive Spirit',100,'Rhand',73,226,226);
		$sptab[225145]=array('Somber Right Hand Defencive Spirit',220,'Rhand',161,716,716);
		$sptab[225146]=array('Sorrowful Right Hand Defencive Spirit',230,'Rhand',168,749,749);
		$sptab[225147]=array('Tragic Right Hand Defencive Spirit',250,'Rhand',183,1063,1063);
		$sptab[225148]=array('Troubled Right Hand Defencive Spirit',260,'Rhand',190,1106,1106);
		$sptab[225149]=array('Upset Right Hand Defencive Spirit',280,'Rhand',205,1191,1191);
		$sptab[225150]=array('Wistful Right Hand Defencive Spirit',300,'Rhand',219,1276,1276);
		$sptab[225151]=array('Miserable Right Hand Defencive Spirit',120,'Rhand',88,271,271);
		$sptab[225152]=array('Oppressed Right Hand Defencive Spirit',150,'Rhand',110,339,339);
		$sptab[225153]=array('Pitiable Right Hand Defencive Spirit',180,'Rhand',132,406,406);
		$sptab[225154]=array('Rejected Right Hand Defencive Spirit',200,'Rhand',146,451,451);
		$sptab[225155]=array('Bitter Spirit of Insight - Right Hand',1,'Rhand',1,3,3);
		$sptab[225156]=array('Blue Spirit of Insight - Right Hand',5,'Rhand',4,12,12);
		$sptab[225157]=array('Despairing Spirit of Insight - Right Hand',20,'Rhand',15,46,46);
		$sptab[225158]=array('Dreary Spirit of Insight - Right Hand',40,'Rhand',30,91,91);
		$sptab[225159]=array('Heartsick Spirit of Insight - Right Hand',80,'Rhand',59,181,181);
		$sptab[225160]=array('Miserable Spirit of Insight - Right Hand',120,'Rhand',88,271,271);
		$sptab[225161]=array('Mourning Spirit of Insight - Right Hand',130,'Rhand',95,293,293);
		$sptab[225162]=array('Oppressed Spirit of Insight - Right Hand',150,'Rhand',110,339,339);
		$sptab[225163]=array('Pathetic Spirit of Insight - Right Hand',160,'Rhand',117,361,361);
		$sptab[225164]=array('Pitiable Spirit of Insight - Right Hand',180,'Rhand',132,406,406);
		$sptab[225165]=array('Dispirited Spirit of Insight - Right Hand',210,'Rhand',154,683,683);
		$sptab[225166]=array('Somber Spirit of Insight - Right Hand',220,'Rhand',161,716,716);
		$sptab[225167]=array('Sorry Spirit of Insight - Right Hand',240,'Rhand',176,781,781);
		$sptab[225168]=array('Troubled Spirit of Insight - Right Hand',260,'Rhand',190,1106,1106);
		$sptab[225169]=array('Unhappy Spirit of Insight - Right Hand',270,'Rhand',198,1149,1149);
		$sptab[225170]=array('Wistful Spirit of Insight - Right Hand',300,'Rhand',219,1276,1276);
		$sptab[225171]=array('Bitter Spirit of Defense',1,'Legs',1,3,3);
		$sptab[225172]=array('Blue Spirit of Defense',5,'Legs',4,12,12);
		$sptab[225173]=array('Comfortless Spirit of Defense',10,'Legs',8,23,23);
		$sptab[225174]=array('Dreary Spirit of Defense',40,'Legs',30,91,91);
		$sptab[225175]=array('Gloomy Spirit of Defense',50,'Legs',37,113,113);
		$sptab[225176]=array('Grieving Spirit of Defense',70,'Legs',52,159,159);
		$sptab[225177]=array('Heartsick Spirit of Defense',80,'Legs',59,181,181);
		$sptab[225178]=array('Joyless Spirit of Defense',90,'Legs',66,203,203);
		$sptab[225179]=array('Lonely Spirit of Defense',100,'Legs',73,226,226);
		$sptab[225180]=array('Miserable Spirit of Defense',120,'Legs',88,271,271);
		$sptab[225181]=array('Mourning Spirit of Defense',130,'Legs',95,293,293);
		$sptab[225182]=array('Oppressed Spirit of Defense',150,'Legs',110,339,339);
		$sptab[225183]=array('Pathetic Spirit of Defense',160,'Legs',117,361,361);
		$sptab[225184]=array('Pitiable Spirit of Defense',180,'Legs',132,406,406);
		$sptab[225185]=array('Poor Spirit of Defense',190,'Legs',139,429,429);
		$sptab[225186]=array('Sorrowful Spirit of Defense',230,'Legs',168,749,749);
		$sptab[225187]=array('Unhappy Spirit of Defense',270,'Legs',198,1149,1149);
		$sptab[225188]=array('Wistful Spirit of Defense',300,'Legs',219,1276,1276);
		$sptab[225189]=array('Comfortless Spirit of Essence',10,'Legs',8,23,23);
		$sptab[225190]=array('Depressed Spirit of Essence',15,'Legs',11,35,35);
		$sptab[225191]=array('Grave Spirit of Essence',60,'Legs',44,136,136);
		$sptab[225192]=array('Joyless Spirit of Essence',90,'Legs',66,203,203);
		$sptab[225193]=array('Somber Spirit of Essence',220,'Legs',161,716,716);
		$sptab[225194]=array('Troubled Spirit of Essence',260,'Legs',190,1106,1106);
		$sptab[225195]=array('Bitter Left Hand Spirit of Defence',1,'Lhand',1,3,3);
		$sptab[225196]=array('Blue Left Hand Spirit of Defence',5,'Lhand',4,12,12);
		$sptab[225197]=array('Comfortless Left Hand Spirit of Defence',10,'Lhand',8,23,23);
		$sptab[225198]=array('Discouraged Left Hand Spirit of Defence',25,'Lhand',19,57,57);
		$sptab[225199]=array('Dismal Left Hand Spirit of Defence',30,'Lhand',22,69,69);
		$sptab[225200]=array('Grave Left Hand Spirit of Defence',60,'Lhand',44,136,136);
		$sptab[225201]=array('Joyless Left Hand Spirit of Defence',90,'Lhand',66,203,203);
		$sptab[225202]=array('Lonely Left Hand Spirit of Defence',100,'Lhand',73,226,226);
		$sptab[225203]=array('Melancholic Left Hand Spirit of Defence',110,'Lhand',81,249,249);
		$sptab[225204]=array('Miserable Left Hand Spirit of Defence',120,'Lhand',88,271,271);
		$sptab[225205]=array('Pitiable Left Hand Spirit of Defence',180,'Lhand',132,406,406);
		$sptab[225206]=array('Poor Left Hand Spirit of Defence',190,'Lhand',139,429,429);
		$sptab[225207]=array('Rejected Left Hand Spirit of Defence',200,'Lhand',146,451,451);
		$sptab[225208]=array('Dispirited Left Hand Spirit of Defence',210,'Lhand',154,683,683);
		$sptab[225209]=array('Tragic Left Hand Spirit of Defence',250,'Lhand',183,1063,1063);
		$sptab[225210]=array('Troubled Left Hand Spirit of Defence',260,'Lhand',190,1106,1106);
		$sptab[225211]=array('Unhappy Left Hand Spirit of Defence',270,'Lhand',198,1149,1149);
		$sptab[225212]=array('Wistful Left Hand Spirit of Defence',300,'Lhand',219,1276,1276);
		$sptab[225213]=array('Bitter Left Hand Spirit of Strength',1,'Lhand',1,3,3);
		$sptab[225214]=array('Blue Left Hand Spirit of Strength',5,'Lhand',4,12,12);
		$sptab[225215]=array('Comfortless Left Hand Spirit of Strength',10,'Lhand',8,23,23);
		$sptab[225216]=array('Depressed Left Hand Spirit of Strength',15,'Lhand',11,35,35);
		$sptab[225217]=array('Discouraged Left Hand Spirit of Strength',25,'Lhand',19,57,57);
		$sptab[225218]=array('Grave Left Hand Spirit of Strength',60,'Lhand',44,136,136);
		$sptab[225219]=array('Heartsick Left Hand Spirit of Strength',80,'Lhand',59,181,181);
		$sptab[225220]=array('Joyless Left Hand Spirit of Strength',90,'Lhand',66,203,203);
		$sptab[225221]=array('Nostalgic Left Hand Spirit of Strength',140,'Lhand',103,316,316);
		$sptab[225222]=array('Oppressed Left Hand Spirit of Strength',150,'Lhand',110,339,339);
		$sptab[225223]=array('Pitiable Left Hand Spirit of Strength',180,'Lhand',132,406,406);
		$sptab[225224]=array('Dispirited Left Hand Spirit of Strength',210,'Lhand',154,683,683);
		$sptab[225225]=array('Somber Left Hand Spirit of Strength',220,'Lhand',161,716,716);
		$sptab[225226]=array('Tragic Left Hand Spirit of Strength',250,'Lhand',183,1063,1063);
		$sptab[225227]=array('Weeping Left Hand Spirit of Strength',290,'Lhand',212,1233,1233);
		$sptab[225228]=array('Wistful Left Hand Spirit of Strength',300,'Lhand',219,1276,1276);
		$sptab[225229]=array('Bitter Spirit of Feet Strength',1,'Feet',1,3,3);
		$sptab[225230]=array('Blue Spirit of Feet Strength',5,'Feet',4,12,12);
		$sptab[225231]=array('Depressed Spirit of Feet Strength',15,'Feet',11,35,35);
		$sptab[225232]=array('Discouraged Spirit of Feet Strength',25,'Feet',19,57,57);
		$sptab[225233]=array('Gloomy Spirit of Feet Strength',50,'Feet',37,113,113);
		$sptab[225234]=array('Grave Spirit of Feet Strength',60,'Feet',44,136,136);
		$sptab[225235]=array('Joyless Spirit of Feet Strength',90,'Feet',66,203,203);
		$sptab[225236]=array('Nostalgic Spirit of Feet Strength',140,'Feet',103,316,316);
		$sptab[225237]=array('Oppressed Spirit of Feet Strength',150,'Feet',110,339,339);
		$sptab[225238]=array('Pathetic Spirit of Feet Strength',160,'Feet',117,361,361);
		$sptab[225239]=array('Pessimistic Spirit of Feet Strength',170,'Feet',125,383,383);
		$sptab[225240]=array('Pitiable Spirit of Feet Strength',180,'Feet',132,406,406);
		$sptab[225241]=array('Rejected Spirit of Feet Strength',200,'Feet',146,451,451);
		$sptab[225242]=array('Dispirited Spirit of Feet Strength',210,'Feet',154,683,683);
		$sptab[225243]=array('Sorrowful Spirit of Feet Strength',230,'Feet',168,749,749);
		$sptab[225244]=array('Tragic Spirit of Feet Strength',250,'Feet',183,1063,1063);
		$sptab[225245]=array('Troubled Spirit of Feet Strength',260,'Feet',190,1106,1106);
		$sptab[225246]=array('Wistful Spirit of Feet Strength',300,'Feet',219,1276,1276);
		$sptab[225247]=array('Bitter Spirit of Feet Defense',1,'Feet',1,3,3);
		$sptab[225248]=array('Blue Spirit of Feet Defense',5,'Feet',4,12,12);
		$sptab[225249]=array('Comfortless Spirit of Feet Defense',10,'Feet',8,23,23);
		$sptab[225250]=array('Dreary Spirit of Feet Defense',40,'Feet',30,91,91);
		$sptab[225251]=array('Grave Spirit of Feet Defense',60,'Feet',44,136,136);
		$sptab[225252]=array('Heartsick Spirit of Feet Defense',80,'Feet',59,181,181);
		$sptab[225253]=array('Joyless Spirit of Feet Defense',90,'Feet',66,203,203);
		$sptab[225254]=array('Oppressed Spirit of Feet Defense',150,'Feet',110,339,339);
		$sptab[225255]=array('Pathetic Spirit of Feet Defense',160,'Feet',117,361,361);
		$sptab[225256]=array('Pessimistic Spirit of Feet Defense',170,'Feet',125,383,383);
		$sptab[225257]=array('Poor Spirit of Feet Defense',190,'Feet',139,429,429);
		$sptab[225258]=array('Rejected Spirit of Feet Defense',200,'Feet',146,451,451);
		$sptab[225259]=array('Dispirited Spirit of Feet Defense',210,'Feet',154,683,683);
		$sptab[225260]=array('Sorrowful Spirit of Feet Defense',230,'Feet',168,749,749);
		$sptab[225261]=array('Sorry Spirit of Feet Defense',240,'Feet',176,781,781);
		$sptab[225262]=array('Troubled Spirit of Feet Defense',260,'Feet',190,1106,1106);
		$sptab[225263]=array('Unhappy Spirit of Feet Defense',270,'Feet',198,1149,1149);
		$sptab[225264]=array('Wistful Spirit of Feet Defense',300,'Feet',219,1276,1276);
		$sptab[260763]=array('Ethereal Spirit of Berserker Rage',205,'Larm',190,634,634);
		$sptab[268357]=array('Syndicate Brain Spirit of Guile',180,'Head',132,376,376);
		$sptab[270246]=array('Rejected Brain Spirit of Computer Skill',200,'Head',146,451,451);
		$sptab[275026]=array('Wistful Brain Spirit of Computer Skill',300,'Head',1,1276,1276);
		$sptab[279068]=array('Xan Brain Spirit of Offence - Beta',250,'Head',201,1020,1020);
		$sptab[279069]=array('Xan Essence Brain Spirit - Beta',250,'Head',201,1020,1020);
		$sptab[279070]=array('Xan Spirit of Clear Thought - Beta',250,'Head',201,1020,1020);
		$sptab[279071]=array('Xan Spirit of Discerning Weakness - Beta',250,'Eye',201,1020,1020);
		$sptab[279072]=array('Xan Spirit of Essence Whispered - Beta',250,'Ear',201,1020,1020);
		$sptab[279073]=array('Xan Spirit of Knowledge Whispered - Beta',250,'Ear',201,1020,1020);
		$sptab[279074]=array('Xan Spirit of Strength Whispered - Beta',250,'Ear',201,1020,1020);
		$sptab[279075]=array('Xan Heart Spirit of Essence - Beta',250,'Chest',201,1020,1020);
		$sptab[279076]=array('Xan Heart Spirit of Knowledge - Beta',250,'Chest',201,1020,1020);
		$sptab[279077]=array('Xan Heart Spirit of Strength - Beta',250,'Chest',201,1020,1020);
		$sptab[279078]=array('Xan Heart Spirit of Weakness - Beta',250,'Chest',201,1020,1020);
		$sptab[279079]=array('Xan Left Limb Spirit of Essence - Beta',250,'Larm',201,1020,1020);
		$sptab[279080]=array('Xan Left Limb Spirit of Strength - Beta',250,'Larm',201,1020,1020);
		$sptab[279081]=array('Xan Left Limb Spirit of Understanding - Beta',250,'Larm',201,1020,1020);
		$sptab[279082]=array('Xan Left Limb Spirit of Weakness - Beta',250,'Larm',201,1020,1020);
		$sptab[279083]=array('Xan Left Hand Spirit of Defence - Beta',250,'Lhand',201,1020,1020);
		$sptab[279084]=array('Xan Left Hand Spirit of Strength - Beta',250,'Lhand',201,1020,1020);
		$sptab[279085]=array('Xan Spirit of Left Wrist Defense - Beta',250,'Lwrist',201,1020,1020);
		$sptab[279086]=array('Xan Spirit of Left Wrist Strength - Beta',250,'Lwrist',201,1020,1020);
		$sptab[279087]=array('Xan Right Limb Spirit of Essence - Beta',250,'Rarm',201,1020,1020);
		$sptab[279088]=array('Xan Right Limb Spirit of Strength - Beta',250,'Rarm',201,1020,1020);
		$sptab[279089]=array('Xan Right Limb Spirit of Weakness - Beta',250,'Rarm',201,1020,1020);
		$sptab[279090]=array('Xan Right Hand Defensive Spirit - Beta',250,'Rhand',201,1020,1020);
		$sptab[279091]=array('Xan Right Hand Strength Spirit - Beta',250,'Rhand',201,1020,1020);
		$sptab[279092]=array('Xan Spirit of Insight - Right Hand - Beta',250,'Rhand',201,1020,1020);
		$sptab[279093]=array('Xan Spirit of Right Wrist Offence - Beta',250,'Rwrist',201,1020,1020);
		$sptab[279094]=array('Xan Spirit of Right Wrist Weakness - Beta',250,'Rwrist',201,1020,1020);
		$sptab[279095]=array('Xan Midriff Spirit of Essence - Beta',250,'Waist',201,1020,1020);
		$sptab[279096]=array('Xan Midriff Spirit of Knowledge - Beta',250,'Waist',201,1020,1020);
		$sptab[279097]=array('Xan Midriff Spirit of Strength - Beta',250,'Waist',201,1020,1020);
		$sptab[279098]=array('Xan Midriff Spirit of Weakness - Beta',250,'Waist',201,1020,1020);
		$sptab[279099]=array('Xan Spirit of Defense - Beta',250,'Legs',201,1020,1020);
		$sptab[279100]=array('Xan Spirit of Essence - Beta',250,'Legs',201,1020,1020);
		$sptab[279101]=array('Xan Spirit of Feet Defense - Beta',250,'Feet',201,1020,1020);
		$sptab[279102]=array('Xan Spirit of Feet Strength - Beta',250,'Feet',201,1020,1020);
		$sptab[279103]=array('Xan Brain Spirit of Offence - Alpha',250,'Head',201,1063,1063);
		$sptab[279104]=array('Xan Brain Spirit of Offence - Alpha',300,'Head',219,1276,1276);
		$sptab[279105]=array('Xan Essence Brain Spirit - Alpha',250,'Head',201,1063,1063);
		$sptab[279106]=array('Xan Essence Brain Spirit - Alpha',300,'Head',219,1276,1276);
		$sptab[279107]=array('Xan Spirit of Clear Thought - Alpha',250,'Head',201,1063,1063);
		$sptab[279108]=array('Xan Spirit of Clear Thought - Alpha',300,'Head',219,1276,1276);
		$sptab[279109]=array('Xan Spirit of Discerning Weakness - Alpha',250,'Eye',201,1063,1063);
		$sptab[279110]=array('Xan Spirit of Discerning Weakness - Alpha',300,'Eye',219,1276,1276);
		$sptab[279111]=array('Xan Spirit of Essence Whispered - Alpha',250,'Ear',201,1063,1063);
		$sptab[279112]=array('Xan Spirit of Essence Whispered - Alpha',300,'Ear',219,1276,1276);
		$sptab[279113]=array('Xan Spirit of Knowledge Whispered - Alpha',250,'Ear',201,1063,1063);
		$sptab[279114]=array('Xan Spirit of Knowledge Whispered - Alpha',300,'Ear',219,1276,1276);
		$sptab[279115]=array('Xan Spirit of Strength Whispered - Alpha',250,'Ear',201,1063,1063);
		$sptab[279116]=array('Xan Spirit of Strength Whispered - Alpha',300,'Ear',219,1276,1276);
		$sptab[279117]=array('Xan Heart Spirit of Essence - Alpha',250,'Chest',201,1063,1063);
		$sptab[279118]=array('Xan Heart Spirit of Essence - Alpha',300,'Chest',219,1276,1276);
		$sptab[279119]=array('Xan Heart Spirit of Knowledge - Alpha',250,'Chest',201,1063,1063);
		$sptab[279120]=array('Xan Heart Spirit of Knowledge - Alpha',300,'Chest',219,1276,1276);
		$sptab[279121]=array('Xan Heart Spirit of Strength - Alpha',250,'Chest',201,1063,1063);
		$sptab[279122]=array('Xan Heart Spirit of Strength - Alpha',300,'Chest',219,1276,1276);
		$sptab[279123]=array('Xan Heart Spirit of Weakness - Alpha',250,'Chest',201,1063,1063);
		$sptab[279124]=array('Xan Heart Spirit of Weakness - Alpha',300,'Chest',219,1276,1276);
		$sptab[279125]=array('Xan Left Limb Spirit of Essence - Alpha',250,'Larm',201,1063,1063);
		$sptab[279126]=array('Xan Left Limb Spirit of Essence - Alpha',300,'Larm',219,1276,1276);
		$sptab[279127]=array('Xan Left Limb Spirit of Strength - Alpha',250,'Larm',201,1063,1063);
		$sptab[279128]=array('Xan Left Limb Spirit of Strength - Alpha',300,'Larm',219,1276,1276);
		$sptab[279129]=array('Xan Left Limb Spirit of Understanding - Alpha',250,'Larm',201,1063,1063);
		$sptab[279130]=array('Xan Left Limb Spirit of Understanding - Alpha',300,'Larm',219,1276,1276);
		$sptab[279131]=array('Xan Left Limb Spirit of Weakness - Alpha',250,'Larm',201,1063,1063);
		$sptab[279132]=array('Xan Left Limb Spirit of Weakness - Alpha',300,'Larm',219,1276,1276);
		$sptab[279133]=array('Xan Left Hand Spirit of Defence - Alpha',250,'Lhand',201,1063,1063);
		$sptab[279134]=array('Xan Left Hand Spirit of Defence - Alpha',300,'Lhand',219,1276,1276);
		$sptab[279135]=array('Xan Left Hand Spirit of Strength - Alpha',250,'Lhand',201,1063,1063);
		$sptab[279136]=array('Xan Left Hand Spirit of Strength - Alpha',300,'Lhand',219,1276,1276);
		$sptab[279137]=array('Xan Spirit of Left Wrist Defense - Alpha',250,'Lwrist',201,1063,1063);
		$sptab[279138]=array('Xan Spirit of Left Wrist Defense - Alpha',300,'Lwrist',219,1276,1276);
		$sptab[279139]=array('Xan Spirit of Left Wrist Strength - Alpha',250,'Lwrist',201,1063,1063);
		$sptab[279140]=array('Xan Spirit of Left Wrist Strength - Alpha',300,'Lwrist',219,1276,1276);
		$sptab[279141]=array('Xan Right Limb Spirit of Essence - Alpha',250,'Rarm',201,1063,1063);
		$sptab[279142]=array('Xan Right Limb Spirit of Essence - Alpha',300,'Rarm',219,1276,1276);
		$sptab[279143]=array('Xan Right Limb Spirit of Strength - Alpha',250,'Rarm',201,1063,1063);
		$sptab[279144]=array('Xan Right Limb Spirit of Strength - Alpha',300,'Rarm',219,1276,1276);
		$sptab[279145]=array('Xan Right Limb Spirit of Weakness - Alpha',250,'Rarm',201,1063,1063);
		$sptab[279146]=array('Xan Right Limb Spirit of Weakness - Alpha',300,'Rarm',219,1276,1276);
		$sptab[279147]=array('Xan Right Hand Defensive Spirit - Alpha',250,'Rhand',201,1063,1063);
		$sptab[279148]=array('Xan Right Hand Defensive Spirit - Alpha',300,'Rhand',219,1276,1276);
		$sptab[279149]=array('Xan Right Hand Strength Spirit - Alpha',250,'Rhand',201,1063,1063);
		$sptab[279150]=array('Xan Right Hand Strength Spirit - Alpha',300,'Rhand',219,1276,1276);
		$sptab[279151]=array('Xan Spirit of Insight - Right Hand - Alpha',250,'Rhand',201,1063,1063);
		$sptab[279152]=array('Xan Spirit of Insight - Right Hand - Alpha',300,'Rhand',219,1276,1276);
		$sptab[279153]=array('Xan Spirit of Right Wrist Offence - Alpha',250,'Rwrist',201,1063,1063);
		$sptab[279154]=array('Xan Spirit of Right Wrist Offence - Alpha',300,'Rwrist',219,1276,1276);
		$sptab[279155]=array('Xan Spirit of Right Wrist Weakness - Alpha',250,'Rwrist',201,1063,1063);
		$sptab[279156]=array('Xan Spirit of Right Wrist Weakness - Alpha',300,'Rwrist',219,1276,1276);
		$sptab[279157]=array('Xan Midriff Spirit of Essence - Alpha',250,'Waist',201,1063,1063);
		$sptab[279158]=array('Xan Midriff Spirit of Essence - Alpha',300,'Waist',219,1276,1276);
		$sptab[279159]=array('Xan Midriff Spirit of Knowledge - Alpha',250,'Waist',201,1063,1063);
		$sptab[279160]=array('Xan Midriff Spirit of Knowledge - Alpha',300,'Waist',219,1276,1276);
		$sptab[279161]=array('Xan Midriff Spirit of Strength - Alpha',250,'Waist',201,1063,1063);
		$sptab[279162]=array('Xan Midriff Spirit of Strength - Alpha',300,'Waist',219,1276,1276);
		$sptab[279163]=array('Xan Midriff Spirit of Weakness - Alpha',250,'Waist',201,1063,1063);
		$sptab[279164]=array('Xan Midriff Spirit of Weakness - Alpha',300,'Waist',219,1276,1276);
		$sptab[279165]=array('Xan Spirit of Defense - Alpha',250,'Legs',201,1063,1063);
		$sptab[279166]=array('Xan Spirit of Defense - Alpha',300,'Legs',219,1276,1276);
		$sptab[279167]=array('Xan Spirit of Essence - Alpha',250,'Legs',201,1063,1063);
		$sptab[279168]=array('Xan Spirit of Essence - Alpha',300,'Legs',219,1276,1276);
		$sptab[279169]=array('Xan Spirit of Feet Defense - Alpha',250,'Feet',201,1063,1063);
		$sptab[279170]=array('Xan Spirit of Feet Defense - Alpha',300,'Feet',219,1276,1276);
		$sptab[279171]=array('Xan Spirit of Feet Strength - Alpha',250,'Feet',201,1063,1063);
		$sptab[279172]=array('Xan Spirit of Feet Strength - Alpha',300,'Feet',219,1276,1276);
		$sptab[279173]=array('Xan Spirit of Essence - Beta',250,'Eye',201,1020,1020);
		$sptab[279174]=array('Xan Spirit of Essence - Alpha',250,'Eye',201,1063,1063);
		$sptab[279175]=array('Xan Spirit of Essence - Alpha',300,'Eye',219,1276,1276);
		$sptab[279189]=array('Xan Brain Spirit of Power - Alpha',250,'Head',201,1063,1063);
		$sptab[279190]=array('Xan Brain Spirit of Power - Alpha',300,'Head',219,1276,1276);
		$sptab[279191]=array('Xan Brain Spirit of Will - Alpha',250,'Head',201,1063,1063);
		$sptab[279192]=array('Xan Brain Spirit of Will - Alpha',300,'Head',219,1276,1276);
		$sptab[279193]=array('Xan Spirit of Stealth - Alpha',250,'Eye',201,1063,1063);
		$sptab[279194]=array('Xan Spirit of Stealth - Alpha',300,'Eye',219,1276,1276);
		$sptab[279195]=array('Xan Spirit of Secrecy Whispered - Alpha',250,'Ear',201,1063,1063);
		$sptab[279196]=array('Xan Spirit of Secrecy Whispered - Alpha',300,'Ear',219,1276,1276);
		$sptab[279199]=array('Xan Spirit of Stealth Whispered - Alpha',250,'Ear',201,1063,1063);
		$sptab[279200]=array('Xan Spirit of Stealth Whispered - Alpha',300,'Ear',219,1276,1276);
		$sptab[279204]=array('Xan Heart Spirit of Will - Alpha',250,'Chest',201,1063,1063);
		$sptab[279205]=array('Xan Heart Spirit of Will - Alpha',300,'Chest',219,1276,1276);
		$sptab[279206]=array('Xan Heart Spirit of Power - Alpha',250,'Chest',201,1063,1063);
		$sptab[279207]=array('Xan Heart Spirit of Power - Alpha',300,'Chest',219,1276,1276);
		$sptab[279208]=array('Xan Left Limb Spirit of Power - Alpha',250,'Larm',201,1063,1063);
		$sptab[279209]=array('Xan Left Limb Spirit of Power - Alpha',300,'Larm',219,1276,1276);
		$sptab[279210]=array('Xan Left Limb Spirit of Defense - Alpha',250,'Larm',201,1063,1063);
		$sptab[279211]=array('Xan Left Limb Spirit of Defense - Alpha',300,'Larm',219,1276,1276);
		$sptab[279212]=array('Xan Left Hand Spirit of Power - Alpha',250,'Lhand',201,1063,1063);
		$sptab[279213]=array('Xan Left Hand Spirit of Power - Alpha',300,'Lhand',219,1276,1276);
		$sptab[279214]=array('Xan Spirit of Left Wrist  Power - Alpha',250,'Lwrist',201,1063,1063);
		$sptab[279215]=array('Xan Spirit of Left Wrist  Power - Alpha',300,'Lwrist',219,1276,1276);
		$sptab[279216]=array('Xan Right Limb Spirit of Power - Alpha',250,'Rarm',201,1063,1063);
		$sptab[279217]=array('Xan Right Limb Spirit of Power - Alpha',300,'Rarm',219,1276,1276);
		$sptab[279219]=array('Xan Right Limb Spirit of Defense - Alpha',250,'Rarm',201,1063,1063);
		$sptab[279220]=array('Xan Right Limb Spirit of Defense - Alpha',300,'Rarm',219,1276,1276);
		$sptab[279221]=array('Xan Right hand Spirit of Power - Alpha',250,'Rhand',201,1063,1063);
		$sptab[279222]=array('Xan Right hand Spirit of Power - Alpha',300,'Rhand',219,1276,1276);
		$sptab[279223]=array('Xan Right hand Spirit of Will - Alpha',250,'Rhand',201,1063,1063);
		$sptab[279224]=array('Xan Right hand Spirit of Will - Alpha',300,'Rhand',219,1276,1276);
		$sptab[279225]=array('Xan Spirit of Right Wrist  Power - Alpha',250,'Rwrist',201,1063,1063);
		$sptab[279226]=array('Xan Spirit of Right Wrist  Power - Alpha',300,'Rwrist',219,1276,1276);
		$sptab[279227]=array('Xan Midriff Spirit of Power - Alpha',250,'Waist',201,1063,1063);
		$sptab[279228]=array('Xan Midriff Spirit of Power - Alpha',300,'Waist',219,1276,1276);
		$sptab[279229]=array('Xan Midriff Spirit of Defense - Alpha',250,'Waist',201,1063,1063);
		$sptab[279230]=array('Xan Midriff Spirit of Defense - Alpha',300,'Waist',219,1276,1276);
		$sptab[279231]=array('Xan Spirit of Power - Alpha',250,'Legs',201,1063,1063);
		$sptab[279232]=array('Xan Spirit of Power - Alpha',300,'Legs',219,1276,1276);
		$sptab[279321]=array('Xan Brain Spirit of Computer Skill - Beta',250,'Head',201,1020,1020);
		$sptab[279322]=array('Xan Brain Spirit of Computer Skill - Alpha',250,'Head',201,1063,1063);
		$sptab[279323]=array('Xan Brain Spirit of Computer Skill - Alpha',300,'Head',219,1276,1276);
		$sptab[279324]=array('Xan Brain Spirit of Intellect - Alpha',250,'Head',201,1063,1063);
		$sptab[279325]=array('Xan Brain Spirit of Intellect - Alpha',300,'Head',219,1276,1276);
		$sptab[295715]=array('Comfortless Spirit of Defense',10,'Legs',1,24,24);
		$sptab[303743]=array('Fabricated Essence Brain Spirit',1,'Head',1,4,4);
		$sptab[303744]=array('Fabricated Essence Brain Spirit',250,'Head',200,1050,1050);
		$sptab[303745]=array('Fabricated Spirit of Clear Thought',1,'Head',1,4,4);
		$sptab[303746]=array('Fabricated Spirit of Clear Thought',250,'Head',200,1050,1050);
		$sptab[303747]=array('Fabricated Spirit of Knowledge Whispered',1,'Ear',1,4,4);
		$sptab[303748]=array('Fabricated Spirit of Knowledge Whispered',250,'Ear',200,1050,1050);
		$sptab[303749]=array('Fabricated Heart Spirit of Essence',1,'Chest',1,4,4);
		$sptab[303750]=array('Fabricated Heart Spirit of Essence',250,'Chest',200,1050,1050);
		$sptab[303751]=array('Fabricated Heart Spirit of Knowledge',1,'Chest',1,4,4);
		$sptab[303752]=array('Fabricated Heart Spirit of Knowledge',250,'Chest',200,1050,1050);
		$sptab[303753]=array('Fabricated Heart Spirit of Weakness',1,'Chest',1,4,4);
		$sptab[303754]=array('Fabricated Heart Spirit of Weakness',250,'Chest',200,1050,1050);
		$sptab[303755]=array('Fabricated Left Limb Spirit of Strength',1,'Larm',1,4,4);
		$sptab[303756]=array('Fabricated Left Limb Spirit of Strength',250,'Larm',200,1050,1050);
		$sptab[303757]=array('Fabricated Left Limb Spirit of Weakness',1,'Larm',1,4,4);
		$sptab[303758]=array('Fabricated Left Limb Spirit of Weakness',250,'Larm',200,1050,1050);
		$sptab[303759]=array('Fabricated Left Hand Spirit of Strength',1,'Lhand',1,4,4);
		$sptab[303760]=array('Fabricated Left Hand Spirit of Strength',250,'Lhand',200,1050,1050);
		$sptab[303761]=array('Fabricated Spirit of Left Wrist Strength',1,'Lwrist',1,4,4);
		$sptab[303762]=array('Fabricated Spirit of Left Wrist Strength',250,'Lwrist',200,1050,1050);
		$sptab[303763]=array('Fabricated Right Limb Spirit of Strength',1,'Rarm',1,4,4);
		$sptab[303764]=array('Fabricated Right Limb Spirit of Strength',250,'Rarm',200,1050,1050);
		$sptab[303765]=array('Fabricated Right Hand Strength Spirit',1,'Rhand',1,4,4);
		$sptab[303766]=array('Fabricated Right Hand Strength Spirit',250,'Rhand',200,1050,1050);
		$sptab[303767]=array('Fabricated Spirit of Right Wrist Offence',1,'Rwrist',1,4,4);
		$sptab[303768]=array('Fabricated Spirit of Right Wrist Offence',250,'Rwrist',200,1050,1050);
		$sptab[303769]=array('Fabricated Midriff Spirit of Essence',1,'Waist',1,4,4);
		$sptab[303770]=array('Fabricated Midriff Spirit of Essence',250,'Waist',200,1050,1050);
		$sptab[303771]=array('Fabricated Midriff Spirit of Weakness',1,'Waist',1,4,4);
		$sptab[303772]=array('Fabricated Midriff Spirit of Weakness',250,'Waist',200,1050,1050);
		$sptab[303773]=array('Fabricated Spirit of Defense',1,'Legs',1,4,4);
		$sptab[303774]=array('Fabricated Spirit of Defense',250,'Legs',200,1050,1050);
		$sptab[303775]=array('Fabricated Spirit of Feet Defense',1,'Feet',1,4,4);
		$sptab[303776]=array('Fabricated Spirit of Feet Defense',250,'Feet',200,1050,1050);
		$sptab[305071]=array('Prototype Essence Brain Spirit',1,'Head',1,4,4);
		$sptab[305072]=array('Prototype Essence Brain Spirit',250,'Head',200,1050,1050);
		$sptab[305073]=array('Prototype Spirit of Clear Thought',1,'Head',1,4,4);
		$sptab[305074]=array('Prototype Spirit of Clear Thought',250,'Head',200,1050,1050);
		$sptab[305075]=array('Prototype Spirit of Knowledge Whispered',1,'Ear',1,4,4);
		$sptab[305076]=array('Prototype Spirit of Knowledge Whispered',250,'Ear',200,1050,1050);
		$sptab[305077]=array('Prototype Heart Spirit of Essence',1,'Chest',1,4,4);
		$sptab[305078]=array('Prototype Heart Spirit of Essence',250,'Chest',200,1050,1050);
		$sptab[305079]=array('Prototype Heart Spirit of Knowledge',1,'Chest',1,4,4);
		$sptab[305080]=array('Prototype Heart Spirit of Knowledge',250,'Chest',200,1050,1050);
		$sptab[305081]=array('Prototype Heart Spirit of Weakness',1,'Chest',1,4,4);
		$sptab[305082]=array('Prototype Heart Spirit of Weakness',250,'Chest',200,1050,1050);
		$sptab[305083]=array('Prototype Left Limb Spirit of Strength',1,'Larm',1,4,4);
		$sptab[305084]=array('Prototype Left Limb Spirit of Strength',250,'Larm',200,1050,1050);
		$sptab[305085]=array('Prototype Left Limb Spirit of Weakness',1,'Larm',1,4,4);
		$sptab[305086]=array('Prototype Left Limb Spirit of Weakness',250,'Larm',200,1050,1050);
		$sptab[305087]=array('Prototype Left Hand Spirit of Strength',1,'Lhand',1,4,4);
		$sptab[305088]=array('Prototype Left Hand Spirit of Strength',250,'Lhand',200,1050,1050);
		$sptab[305089]=array('Prototype Spirit of Left Wrist Strength',1,'Lwrist',1,4,4);
		$sptab[305090]=array('Prototype Spirit of Left Wrist Strength',250,'Lwrist',200,1050,1050);
		$sptab[305091]=array('Prototype Right Limb Spirit of Strength',1,'Rarm',1,4,4);
		$sptab[305092]=array('Prototype Right Limb Spirit of Strength',250,'Rarm',200,1050,1050);
		$sptab[305093]=array('Prototype Right Hand Strength Spirit',1,'Rhand',1,4,4);
		$sptab[305094]=array('Prototype Right Hand Strength Spirit',250,'Rhand',200,1050,1050);
		$sptab[305095]=array('Prototype Spirit of Right Wrist Offence',1,'Rwrist',1,4,4);
		$sptab[305096]=array('Prototype Spirit of Right Wrist Offence',250,'Rwrist',200,1050,1050);
		$sptab[305097]=array('Prototype Midriff Spirit of Essence',1,'Waist',1,4,4);
		$sptab[305098]=array('Prototype Midriff Spirit of Essence',250,'Waist',200,1050,1050);
		$sptab[305099]=array('Prototype Midriff Spirit of Weakness',1,'Waist',1,4,4);
		$sptab[305100]=array('Prototype Midriff Spirit of Weakness',250,'Waist',200,1050,1050);
		$sptab[305101]=array('Prototype Spirit of Defense',1,'Legs',1,4,4);
		$sptab[305102]=array('Prototype Spirit of Defense',250,'Legs',200,1050,1050);
		$sptab[305103]=array('Prototype Spirit of Feet Defense',1,'Feet',1,4,4);
		$sptab[305104]=array('Prototype Spirit of Feet Defense',250,'Feet',200,1050,1050);
		$sptab[305506]=array('Ethereal Embrace',300,'Rhand',210,1300,1300);
		
		$result = array();
		if($words!="") {
			$words = explode(" ",$words);
			foreach($sptab AS $aoid => $infos) {
				foreach($words as $word) {
					if(preg_match("/".addslashes(strtolower($word))."/i", strtolower($infos[0]))) {						
						$result[$aoid] = $infos;
					}
				}
			}			
		} else {
			$result = $sptab;
		}
		if($ql>0) {
			foreach($result AS $aoid => $infos) {
				if($infos[1]!=$ql) {
					unset($result[$aoid]);
				}
			}
		}
		if($slot!="") {
			foreach($result AS $aoid => $infos) {
				if($infos[2]!=$slot) {
					unset($result[$aoid]);
				}
			}
		}				
		
		$blob = ""; $all = 0;
		foreach($result AS $aoid => $infos) {
			if(is_numeric($aoid)&&$aoid>0&&!empty($infos)&&isset($infos[1])&&is_numeric($infos[1])&&isset($infos[0])&&$infos[0]!="") {
				$blob .= '<a href="itemref://'.$aoid.'/'.$aoid.'/'.$infos[1].'">'.$infos[0].'</a> ';
				$all++;
			}
		}
		return $this->bot->send_output($name, $all." spirit(s) found: ".$this->bot->core("tools")->make_blob("click to view", $blob), $origin);
	}
}
?>
