<?php
/*
* Raid_Loot.php - Module to handle Raid Loot.
*  by Shelly - Updated by Bitnykk for 1.1
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
*
* Written for use with BeBot by: Shelly Targe
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg StensÃ¥ ShadowRealm Creations and the BeBot development team.
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
*/

/*
Add a "_" at the beginning of the file (Raid_Loot.php) if you do not want it to be loaded.
*/

require_once("_Raid_Load.php"); // DB part creator/updater

$raidloot = new raidloot($bot);


/*
The Class itself...
*/
class raidloot extends BaseActiveModule
{
        function __construct(&$bot)
        {
                parent::__construct($bot, get_class($this));
				$this->register_module("raidloot");
                $this -> count = 0;
				$this -> register_command("all", "RLTEST", "LEADER");
                $this -> register_command("all", "raidloot", "GUEST");
				$this -> register_command("all", "rloot", "LEADER");
                $this -> register_command("all", "ALAPPAA", "LEADER");
                $this -> register_command("all", "ALBTRAUM", "LEADER");
                $this -> register_command("all", "APF", "LEADER");
                $this -> register_command("all", "13", "LEADER");				
                $this -> register_command("all", "28", "LEADER");				
                $this -> register_command("all", "35", "LEADER");
				$this -> register_command("all", "42", "LEADER");
				$this -> register_alias("13", "north");
				$this -> register_alias("28", "west");
				$this -> register_alias("35", "east");				
                $this -> register_command("all", "BIODOME", "LEADER");
                $this -> register_command("all", "COLLECTOR", "LEADER");
                $this -> register_command("all", "DB", "LEADER");
                $this -> register_command("all", "DREADLOCH", "LEADER");
                $this -> register_command("all", "GAUNTLOOT", "LEADER");				
                $this -> register_command("all", "RHI", "LEADER");
                $this -> register_command("all", "RIS", "LEADER");
                $this -> register_command("all", "JACK", "LEADER");
                $this -> register_command("all", "LOX", "LEADER");
                $this -> register_command("all", "ZOD", "LEADER");
                $this -> register_command("all", "PRISONERS", "LEADER");
                $this -> register_command("all", "POH", "LEADER");
                $this -> register_command("all", "TARASQUE", "LEADER");
				$this -> register_command("all", "HLTOTW", "LEADER");
				$this -> register_command("all", "HLSUBWAY", "LEADER");

                $this -> bot -> core("settings") -> create('raidloot', 'New', 'LEADER', 'Who should be able to start new loot roll', 'GUEST;MEMBER;LEADER;ADMIN;SUPERADMIN;OWNER');
                $this -> bot -> core("settings") -> create ("Loot", "Roll", "SINGLE", "Should you be allowed to be added to the roll of more than one slot?", "SINGLE;MULTI");

                $this -> bot -> core("colors") -> define_scheme("loot", "highlight", "yellow");


//Helpfiles
                $this -> help['description'] = "Loot manager for the Raid loot module";
				$this -> help['command']['ALAPPA']="Shows Possible Items from Alappaa.";
                $this -> help['command']['ALBTRAUM albmemories']="Shows Possible Memories from Albtraum.";
                $this -> help['command']['ALBTRAUM albrings']="Shows Possible Rings from Albtraum.";
                $this -> help['command']['ALBTRAUM albmisc']="Shows Possible Miscs from Albtraum.";	
 				$this -> help['command']['APF apf 7']="Shows Possible APF Sector 7 Loot.";
                $this -> help['command']['APF apf 10']="Shows Possible APF Sector 10 Loot.";
                $this -> help['command']['APF apf 13']="Shows Possible APF Sector 13 Loot.";
                $this -> help['command']['APF apf 28']="Shows Possible APF Sector 28 Loot.";
                $this -> help['command']['APF apf 35']="Shows Possible APF Sector 35 Loot.";
                $this -> help['command']['13']="Adds know loots for sector 13/North boss.";
                $this -> help['command']['28']="Adds know loots for sector 28/West boss.";
                $this -> help['command']['35']="Adds know loots for sector 35/East bosses.";
                $this -> help['command']['40']="Adds know loots for sector 42 final boss.";				
                $this -> help['command']['North']="Adds know loots for sector 13/North boss.";
                $this -> help['command']['West']="Adds know loots for sector 28/West boss.";
                $this -> help['command']['East']="Adds know loots for sector 35/East bosses.";
                $this -> help['command']['APF apf 42']="Shows Possible APF Sector 42 Loot.";
                $this -> help['command']['BIODOME biodome']="Shows Possible Items from Biodome.";
                $this -> help['command']['COLLECTOR collechest']="Shows Possible Chests for Collector.";
				$this -> help['command']['DB db1']="Shows Possible Items from DB1.";
				$this -> help['command']['DB db2']="Shows Possible Items from DB2.";
				$this -> help['command']['DB db3']="Shows Possible Items from DB3.";				
				$this -> help['command']['DREADLOCH dreadweapons']="Shows Possible Weapons from Dreadloch.";
				$this -> help['command']['DREADLOCH dreadarmor']="Shows Possible Armors from Dreadloch.";
				$this -> help['command']['DREADLOCH dreadutil']="Shows Possible Utils from Dreadloch.";
				$this -> help['command']['GAUNTLOOT gauntarmor']="Shows Possible Armors from Gauntlet.";
				$this -> help['command']['GAUNTLOOT gauntitem']="Shows Possible Items from Gauntlet.";
				$this -> help['command']['GAUNTLOOT gauntrare']="Shows Possible Rares from Gauntlet.";				
				$this -> help['command']['RHI hilion']="Shows Possible Lion Armor Items from Hollow Island.";
				$this -> help['command']['RHI hifirstbrood']="Shows Possible Items of 1st Brood from Hollow Island.";
				$this -> help['command']['RHI hisecondbrood']="Shows Possible Items of 2nd Brood from Hollow Island.";
				$this -> help['command']['RHI hithirdbrood']="Shows Possible Items of 3rd Brood from Hollow Island.";
				$this -> help['command']['RHI hifourthbrood']="Shows Possible Items of 4th Brood from Hollow Island.";
				$this -> help['command']['RHI hififthbrood']="Shows Possible Items of 5th Brood from Hollow Island.";
				$this -> help['command']['RHI hisixthbrood']="Shows Possible Items of 6th Brood from Hollow Island.";
				$this -> help['command']['RHI hiseventhbrood']="Shows Possible Items of 7th Brood from Hollow Island.";
				$this -> help['command']['RHI hieighthbrood']="Shows Possible Items of 8th Brood from Hollow Island.";
				$this -> help['command']['RHI hininthbrood']="Shows Possible Items of 9th Brood from Hollow Island.";
				$this -> help['command']['RHI hisuzerain']="Shows Possible Items of Suzerain from Hollow Island.";
				$this -> help['command']['RHI hibroodmother']="Shows Possible Items of Mother from Hollow Island.";
				$this -> help['command']['RHI hiweed']="Shows Possible Items of Weed from Hollow Island.";
				$this -> help['command']['RIS iskey']="Shows Possible Keys from Inner Sanctum.";
				$this -> help['command']['RIS isweapons']="Shows Possible Weapons from Inner Sanctum.";
				$this -> help['command']['RIS isrings']="Shows Possible Rings from Inner Sanctum.";
				$this -> help['command']['RIS isabaddon']="Shows Possible Abaddons from Inner Sanctum.";
				$this -> help['command']['RIS iskarmic']="Shows Possible Books from Inner Sanctum.";
				$this -> help['command']['RIS isarmor']="Shows Possible Armors from Inner Sanctum.";
				$this -> help['command']['RIS ismisc']="Shows Possible Miscs from Inner Sanctum.";			
				$this -> help['command']['JACK legchopper']="Shows Possible Items from Jack Legchopper.";
				$this -> help['command']['LOX 12-man']="Shows Possible Items from 12m.";
				$this -> help['command']['LOX alienthreat']="Shows Possible Items from Sinuh.";
				$this -> help['command']['LOX vortexx']="Shows Possible Items from Vortexx.";
                $this -> help['command']['ZOD beast beastarmor']="Shows Possible beastarmor Loot.";
                $this -> help['command']['ZOD beast beastweaps']="Shows Possible beastweapons Loot.";
                $this -> help['command']['ZOD beast beaststars']="Shows Possible beaststars Loot.";
                $this -> help['command']['ZOD tnh tnh']="Shows Possible The Night Heart Loot.";
                $this -> help['command']['ZOD beast sb']="Shows Possible sbs Loot.";
                $this -> help['command']['ZOD westzods aries']="Shows Possible Aries Zodiac Loot.";
                $this -> help['command']['ZOD westzods leo']="Shows Possible Leo Zodiac Loot.";
                $this -> help['command']['ZOD westzods virgo']="Shows Possible Virgo Zodiac Loot.";
                $this -> help['command']['ZOD eastzods aquarius']="Shows Possible Aquarius Zodiac Loot.";
                $this -> help['command']['ZOD eastzods cancer']="Shows Possible Cancer Zodiac Loot.";
                $this -> help['command']['ZOD eastzods gemini']="Shows Possible Gemini Zodiac Loot.";
                $this -> help['command']['ZOD middlezods libra']="Shows Possible Libra Zodiac Loot.";
                $this -> help['command']['ZOD middlezods pisces']="Shows Possible Pisces Zodiac Loot.";
                $this -> help['command']['ZOD middlezods taurus']="Shows Possible Taurus Zodiac Loot.";
                $this -> help['command']['ZOD northzods capricorn']="Shows Possible Capricorn Zodiac Loot.";
                $this -> help['command']['ZOD northzods sagittarius']="Shows Possible Sagittarius Zodiac Loot.";
                $this -> help['command']['ZOD northzods scorpio']="Shows Possible Scorpio Zodiac Loot.";
				$this -> help['command']['PRISONERS prisoners']="Shows Possible Items from Prisoners.";
				$this -> help['command']['POH poh']="Shows Possible Items from Pyramid of Home.";				
				$this -> help['command']['TARASQUE taracommong']="Shows Possible Common Loots from Tarasque Castle.";				
				$this -> help['command']['TARASQUE taraboss']="Shows Possible Boss Loots from Tarasque Castle.";				
				$this -> help['command']['HLTOTW hltotwarmors']="Shows Possible Armors from Temple of 3 Winds (High Level).";
				$this -> help['command']['HLTOTW hltotwrings']="Shows Possible Rings from Temple of 3 Winds (High Level).";
				$this -> help['command']['HLTOTW hltotwsymbs']="Shows Possible Symbs from Temple of 3 Winds (High Level).";
				$this -> help['command']['HLTOTW hltotwweaps']="Shows Possible Weapons from Temple of 3 Winds (High Level).";
				$this -> help['command']['HLTOTW hltotwbooks']="Shows Possible Books from Temple of 3 Winds (High Level).";
				$this -> help['command']['HLTOTW hltotwmiscs']="Shows Possible Miscs from Temple of 3 Winds (High Level).";						
				$this -> help['command']['HLSUBWAY hlsubarmors']="Shows Possible Armors from Subway (High Level).";		
				$this -> help['command']['HLSUBWAY hlsubmiscs']="Shows Possible Miscs from Subway (High Level).";		
				$this -> help['command']['HLSUBWAY hlsubweapons']="Shows Possible Weapons from Subway (High Level).";		
				
                $this -> help['notes'] = "This module is a work in progress ...<br>";
        }


        /*
        Unified message handler
        $source: The originating player
        $msg: The actual message, including command prefix and all
        $type: The channel the message arrived from. 1 Being tells, 2 being private group 3 being guildchat
        */
        function command_handler($source, $msg, $type)
        {
                $vars = explode(' ', strtolower($msg));

                $command = (strtoupper($vars[0]));
                if(isset($vars[1])) { $subcom = (strtolower($vars[1])); } else { $subcom = ""; }
				if(isset($vars[2])) { $subcom2 = (strtolower($vars[2])); } else { $subcom2 = ""; }
                unset($vars[0], $vars[1], $vars[2]);
// Debugger :
//echo "\nCommand [$command], SubCom [$subcom]\n\n";
                switch($command)
                {			
               //$this -> register_command("all", "rloot", "LEADER");
						case 'RLOOT':
								return $this -> rloot($source, $msg);
								break;
               //$this -> register_command("all", "rloot", "LEADER");
						case '13':
								return $this -> apf13();
								break;
               //$this -> register_command("all", "rloot", "LEADER");
						case '28':
								return $this -> apf28();
								break;
               //$this -> register_command("all", "rloot", "LEADER");
						case '35':
								return $this -> apf35();
								break;								
               //$this -> register_command("all", "rloot", "LEADER");
						case '42':
								return $this -> apf42();
								break;
               //$this -> register_command("all", "RLTEST", "LEADER");
                        case 'RLTEST':
                                return $this -> rltest($subcom);
                                break;				
                //$this -> register_command("all", "ALAPPAA", "LEADER");
                        case 'ALAPPAA':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> show_aloot($source, 'alappaa', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'ALAPPAA');
                                                break;
                                }
                                break;
                //$this -> register_command("all", "ALBTRAUM", "LEADER");
                        case 'ALBTRAUM':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'ALBTRAUM');
                                                break;
                                        case 'albmemories':
                                                return $this -> show_aloot($source, 'albmemories', $command);
                                                break;
                                        case 'albrings':
                                                return $this -> show_aloot($source, 'albrings', $command);
                                                break;
                                        case 'albmisc':
                                                return $this -> show_aloot($source, 'albmisc', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'ALBTRAUM');
                                                break;
                                }
                                break;
                //$this -> register_command("all", "APF", "LEADER");
                        case 'APF':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'APF');
                                                break;
                                        case '7':
                                                return $this -> show_aloot($source, '7', $command);
                                                break;												
                                        case '10':
                                                return $this -> show_aloot($source, '10', $command);
                                                break;
                                        case '13':
                                                return $this -> show_aloot($source, '13', $command);
                                                break;
                                        case '28':
                                                return $this -> show_aloot($source, '28', $command);
                                                break;
                                        case '35':
                                                return $this -> show_aloot($source, '35', $command);
                                                break;
                                        case '42':
                                                return $this -> show_aloot($source, '42', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'APF');
                                                break;
                                }
                                break;
                //$this -> register_command("all", "BIODOME", "LEADER");
                        case 'BIODOME':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'BIODOME');
                                                break;
                                        case 'biodome':
                                                return $this -> show_aloot($source, 'biodome', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'BIODOME');
                                                break;
                                }
                                break;	
                //$this -> register_command("all", "COLLECTOR", "LEADER");
                        case 'COLLECTOR':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'COLLECTOR');
                                                break;
                                        case 'collechest':
                                                return $this -> show_aloot($source, 'collechest', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'COLLECTOR');
                                                break;
                                }
                                break;										
                //$this -> register_command("all", "DB", "LEADER");
                        case 'DB':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'DB');
                                                break;
                                        case 'db1':
                                                return $this -> show_aloot($source, 'db1', $command);
                                                break;
                                        case 'db2':
                                                return $this -> show_aloot($source, 'db2', $command);
                                                break;
                                        case 'db3':
                                                return $this -> show_aloot($source, 'db3', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'DB');
                                                break;
                                }
                                break;
                //$this -> register_command("all", "DREADLOCH" "LEADER");
                        case 'DREADLOCH':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'DREADLOCH');
                                                break;
                                        case 'dreadweapons':
                                                return $this -> show_aloot($source, 'dreadweapons', $command);
                                                break;
                                        case 'dreadarmor':
                                                return $this -> show_aloot($source, 'dreadarmor', $command);
                                                break;
                                        case 'dreadutil':
                                                return $this -> show_aloot($source, 'dreadutil', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'DREADLOCH');
                                                break;
                                }
                                break;
                //$this -> register_command("all", "GAUNTLOOT" "LEADER");
                        case 'GAUNTLOOT':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'GAUNTLOOT');
                                                break;
                                        case 'gauntarmor':
                                                return $this -> show_aloot($source, 'gauntarmor', $command);
                                                break;
                                        case 'gauntitem':
                                                return $this -> show_aloot($source, 'gauntitem', $command);
                                                break;
                                        case 'gauntrare':
                                                return $this -> show_aloot($source, 'gauntrare', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'GAUNTLOOT');
                                                break;
                                }
                                break;								
                //$this -> register_command("all", "RHI", "LEADER");
                        case 'RHI':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'RHI');
                                                break;
                                        case 'hilion':
                                                return $this -> show_aloot($source, 'hilion', $command);
                                                break;
                                        case 'hifirstbrood':
                                                return $this -> show_aloot($source, 'hifirstbrood', $command);
                                                break;
                                        case 'hisecondbrood':
                                                return $this -> show_aloot($source, 'hisecondbrood', $command);
                                                break;
                                        case 'hithirdbrood':
                                                return $this -> show_aloot($source, 'hithirdbrood', $command);
                                                break;
                                        case 'hifourthbrood':
                                                return $this -> show_aloot($source, 'hifourthbrood', $command);
                                                break;
                                        case 'hififthbrood':
                                                return $this -> show_aloot($source, 'hififthbrood', $command);
                                                break;
                                        case 'hisixthbrood':
                                                return $this -> show_aloot($source, 'hisixthbrood', $command);
                                                break;
                                        case 'hiseventhbrood':
                                                return $this -> show_aloot($source, 'hiseventhbrood', $command);
                                                break;
                                        case 'hieighthbrood':
                                                return $this -> show_aloot($source, 'hieighthbrood', $command);
                                                break;
                                        case 'hininthbrood':
                                                return $this -> show_aloot($source, 'hininthbrood', $command);
                                                break;
                                        case 'hisuzerain':
                                                return $this -> show_aloot($source, 'hisuzerain', $command);
                                                break;
                                        case 'hibroodmother':
                                                return $this -> show_aloot($source, 'hibroodmother', $command);
                                                break;
                                        case 'hiweed':
                                                return $this -> show_aloot($source, 'hiweed', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'RHI');
                                                break;
                                }
                                break;
                //$this -> register_command("all", "RIS", "LEADER");
                        case 'RIS':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'RIS');
                                                break;
                                        case 'iskey':
                                                return $this -> show_aloot($source, 'iskey', $command);
                                                break;
                                        case 'isweapons':
                                                return $this -> show_aloot($source, 'isweapons', $command);
                                                break;
                                        case 'isrings':
                                                return $this -> show_aloot($source, 'isrings', $command);
                                                break;
                                        case 'isabaddon':
                                                return $this -> show_aloot($source, 'isabaddon', $command);
                                                break;
                                        case 'iskarmic':
                                                return $this -> show_aloot($source, 'iskarmic', $command);
                                                break;
                                        case 'isarmor':
                                                return $this -> show_aloot($source, 'isarmor', $command);
                                                break;
                                        case 'ismisc':
                                                return $this -> show_aloot($source, 'ismisc', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'RIS');
                                                break;
                                }
                                break;
                //$this -> register_command("all", "JACK", "LEADER");
                        case 'JACK':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'JACK');
                                                break;
                                        case 'legchopper':
                                                return $this -> show_aloot($source, 'legchopper', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'JACK');
                                                break;
                                }
                                break;
                //$this -> register_command("all", "LOX", "LEADER");
                        case 'LOX':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'LOX');
                                                break;
                                        case '12-man':
                                                return $this -> show_aloot($source, '12-man', $command);
                                                break;
                                        case 'alienthreat':
                                                return $this -> show_aloot($source, 'alienthreat', $command);
                                                break;
                                        case 'vortexx':
                                                return $this -> show_aloot($source, 'vortexx', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'LOX');
                                                break;
                                }
                                break;
                //$this -> register_command("all", "ZOD", "LEADER");
                        case 'ZOD':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'Pandemonium');
                                                break;
                                        case 'beast':
                                                switch($subcom2)
                                                {
                                                        case 'beastarmor':
                                                                return $this -> show_aloot($source, 'BeastArmor', $command);
                                                                break;
                                                        case 'beastweapons':
                                                                return $this -> show_aloot($source, 'BeastWeapons', $command);
                                                                break;
                                                        case 'beaststars':
                                                                return $this -> show_aloot($source, 'Stars', $command);
                                                                break;
                                                        case 'sb':
                                                                return $this -> show_aloot($source, 'ShadowBreeds', $command);
                                                                break;
                                                        default:
                                                                return $this -> rloot_interface($source, 'BEAST');
                                                                break;
                                                }
                                                break;
                                        case 'tnh':
                                                return $this -> show_aloot($source, 'TNH', $command);
                                                break;
                                        case 'westzods':
                                                switch($subcom2)
                                                {
                                                        case 'aries':
                                                                return $this -> show_bloot($source, 'Aries', $command);
                                                                break;
                                                        case 'leo':
                                                                return $this -> show_bloot($source, 'Leo', $command);
                                                                break;
                                                        case 'virgo':
                                                                return $this -> show_bloot($source, 'Virgo', $command);
                                                                break;
                                                        default:
                                                                return $this -> rloot_interface($source, 'WestZods');
                                                                break;
                                                }
                                                break;
                                        case 'eastzods':
                                                switch($subcom2)
                                                {
                                                        case 'aquarius':
                                                                return $this -> show_bloot($source, 'Aquarius', $command);
                                                                break;
                                                        case 'cancer':
                                                                return $this -> show_bloot($source, 'Cancer', $command);
                                                                break;
                                                        case 'gemini':
                                                                return $this -> show_bloot($source, 'Gemini', $command);
                                                                break;
                                                        default:
                                                                return $this -> rloot_interface($source, 'EastZods');
                                                                break;
                                                }
                                                break;
                                        case 'middlezods':
                                                switch($subcom2)
                                                {
                                                        case 'libra':
                                                                return $this -> show_bloot($source, 'Libra', $command);
                                                                break;
                                                        case 'pisces':
                                                                return $this -> show_bloot($source, 'Pisces', $command);
                                                                break;
                                                        case 'taurus':
                                                                return $this -> show_bloot($source, 'Taurus', $command);
                                                                break;
                                                        default:
                                                                return $this -> rloot_interface($source, 'MiddleZods');
                                                                break;
                                                }
                                                break;												
                                        case 'northzods':
                                                switch($subcom2)
                                                {
                                                        case 'capricorn':
                                                                return $this -> show_bloot($source, 'Capricorn', $command);
                                                                break;
                                                        case 'sagittarius':
                                                                return $this -> show_bloot($source, 'Sagittarius', $command);
                                                                break;
                                                        case 'scorpio':
                                                                return $this -> show_bloot($source, 'Scorpio', $command);
                                                                break;
                                                        default:
                                                                return $this -> rloot_interface($source, 'NorthZods');
                                                                break;
                                                }
                                                break;
                                        case 'all':
                                                return $this -> show_aloot($source, 'all', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'pande');
                                                break;
                                }
                                break;
                //$this -> register_command("all", "PRISONERS", "LEADER");
                        case 'PRISONERS':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'PRISONERS');
                                                break;
                                        case 'prisoners':
                                                return $this -> show_aloot($source, 'prisoners', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'PRISONERS');
                                                break;
                                }
                                break;									
                //$this -> register_command("all", "POH", "LEADER");
                        case 'POH':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'POH');
                                                break;
                                        case 'poh':
                                                return $this -> show_aloot($source, 'poh', $command);
                                                break;
                                        case 'test':
                                                return $this -> rltest($subcom);
                                                break;												
                                        default:
                                                return $this -> bot -> send_help($source, 'POH');
                                                break;
                                }
                                break;		
                //$this -> register_command("all", "TARASQUE", "LEADER");
                        case 'TARASQUE':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'TARASQUE');
                                                break;
                                        case 'taracommon':
                                                return $this -> show_aloot($source, 'taracommon', $command);
                                                break;
                                        case 'taraboss':
                                                return $this -> show_aloot($source, 'taraboss', $command);
                                                break;
										default:
                                                return $this -> bot -> send_help($source, 'TARASQUE');
                                                break;
                                }
                                break;								
               //$this -> register_command("all", "HLTOTW", "LEADER");
                        case 'HLTOTW':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'HLTOTW');
                                                break;
                                        case 'hltotwarmors':
                                                return $this -> show_aloot($source, 'hltotwarmors', $command);
                                                break;
                                        case 'hltotwrings':
                                                return $this -> show_aloot($source, 'hltotwrings', $command);
                                                break;
                                        case 'hltotwsymbs':
                                                return $this -> show_aloot($source, 'hltotwsymbs', $command);
                                                break;
                                        case 'hltotwweaps':
                                                return $this -> show_aloot($source, 'hltotwweaps', $command);
                                                break;
                                        case 'hltotwbooks':
                                                return $this -> show_aloot($source, 'hltotwbooks', $command);
                                                break;
                                        case 'hltotwmiscs':
                                                return $this -> show_aloot($source, 'hltotwmiscs', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'HLTOTW');
                                                break;
                                }
                                break;		
               //$this -> register_command("all", "HLSUBWAY", "LEADER");
                        case 'HLSUBWAY':
                                switch($subcom)
                                {
                                        case '':
                                                return $this -> rloot_interface($source, 'HLSUBWAY');
                                                break;
                                        case 'hlsubarmors':
                                                return $this -> show_aloot($source, 'hlsubarmors', $command);
                                                break;
                                        case 'hlsubmiscs':
                                                return $this -> show_aloot($source, 'hlsubmiscs', $command);
                                                break;
                                        case 'hlsubweapons':
                                                return $this -> show_aloot($source, 'hlsubweapons', $command);
                                                break;
                                        default:
                                                return $this -> bot -> send_help($source, 'HLSUBWAY');
                                                break;
                                }
                                break;									
                        case 'ADD_RLOOT':
                                return $this -> add_loot($source, $subcom, $command);
                                break;
                        case 'RAIDLOOT':
                                return $this -> rloot_interface($source, 'raidloot');
                                break;
                        default:
                                {
                                // Just a safety net to allow you to catch errors where a module has registered  a command, but fails to actually do anything about it
                                        $this -> bot -> send_output($source, "Broken plugin, received unhandled command: $command", $type);
                                }
                }
        }

        /*
        Custom functions go below here
        */

        function rltest($subcom)
        {
			echo " \n RL Test : ".$subcom." \n ";
		}

        function apf13()
        {
			unset($this->bot->core("loots")->loot); $this->bot->core("loots")->loot=array();
			unset($this->bot->core("loots")->leftovers); $this->bot->core("loots")->leftovers=array();
			$this->bot->core("loots")->count = 0;

			$this->bot->core("loots")->loot[1]['item'] = "<a href='itemref://275909/275909/1'>Gelatinous Lump</a>";
			$this->bot->core("loots")->loot[1]['num'] = 3;
			$this->bot->core("loots")->loot[2]['item'] = "<a href='itemref://275916/275916/1'>Biotech Matrix</a>";
			$this->bot->core("loots")->loot[2]['num'] = 3;
			$this->bot->core("loots")->loot[3]['item'] = "<a href='itemref://257960/257960/250'>Action Probability Estimator</a>";
			$this->bot->core("loots")->loot[3]['num'] = 1;
			$this->bot->core("loots")->loot[4]['item'] = "<a href='itemref://257962/257962/250'>Dynamic Gas Redistribution Valves</a>";
			$this->bot->core("loots")->loot[4]['num'] = 1;
			$this->bot->core("loots")->loot[5]['item'] = "<a href='itemref://257533/257533/1'>All Bounties</a>";
			$this->bot->core("loots")->loot[5]['num'] = 1;
			$this->bot->core("loots")->loot[6]['item'] = "<a href='itemref://257968/257968/1'>All ICE</a>";
			$this->bot->core("loots")->loot[6]['num'] = 1;
			$this->bot->core("loots")->loot[7]['item'] = "<a href='itemref://257706/257706/1'>Kyr&#039;Ozch Helmet (2500 Token board)</a>";
			$this->bot->core("loots")->loot[7]['num'] = 1;

			$this->bot->core("loots")->rlist();
        }

        function apf28()
        {
			unset($this->bot->core("loots")->loot); $this->bot->core("loots")->loot=array();
			unset($this->bot->core("loots")->leftovers); $this->bot->core("loots")->leftovers=array();
			$this->bot->core("loots")->count = 0;

			$this->bot->core("loots")->loot[1]['item'] = "<a href='itemref://275912/275912/1'>Crystaline Matrix</a>";
			$this->bot->core("loots")->loot[1]['num'] = 3;
			$this->bot->core("loots")->loot[2]['item'] = "<a href='itemref://275914/275914/1'>Kyr&#039;Ozch Circuitry</a>";
			$this->bot->core("loots")->loot[2]['num'] = 3;
			$this->bot->core("loots")->loot[3]['item'] = "<a href='itemref://257959/257959/250'>Inertial Adjustment Processing Unit</a>";
			$this->bot->core("loots")->loot[3]['num'] = 1;
			$this->bot->core("loots")->loot[4]['item'] = "<a href='itemref://257963/257963/250'>Notum Amplification Coil</a>";
			$this->bot->core("loots")->loot[4]['num'] = 1;
			$this->bot->core("loots")->loot[5]['item'] = "<a href='itemref://257533/257533/1'>All Bounties</a>";
			$this->bot->core("loots")->loot[5]['num'] = 1;
			$this->bot->core("loots")->loot[6]['item'] = "<a href='itemref://257968/257968/1'>All ICE</a>";
			$this->bot->core("loots")->loot[6]['num'] = 1;
			$this->bot->core("loots")->loot[7]['item'] = "<a href='itemref://257706/257706/1'>Kyr&#039;Ozch Helmet (2500 Token board)</a>";
			$this->bot->core("loots")->loot[7]['num'] = 1;

			$this->bot->core("loots")->rlist();
        }

        function apf35()
        {
			unset($this->bot->core("loots")->loot); $this->bot->core("loots")->loot=array();
			unset($this->bot->core("loots")->leftovers); $this->bot->core("loots")->leftovers=array();
			$this->bot->core("loots")->count = 0;

			$this->bot->core("loots")->loot[1]['item'] = "<a href='itemref://275918/275918/1'>Alpha Program Chip</a>";
			$this->bot->core("loots")->loot[1]['num'] = 3;
			$this->bot->core("loots")->loot[2]['item'] = "<a href='itemref://275919/275919/1'>Beta Program Chip</a>";
			$this->bot->core("loots")->loot[2]['num'] = 3;
			$this->bot->core("loots")->loot[3]['item'] = "<a href='itemref://275906/275906/1'>Odd Kyr&#039;Ozch Nanobots</a>";
			$this->bot->core("loots")->loot[3]['num'] = 3;
			$this->bot->core("loots")->loot[4]['item'] = "<a href='itemref://275907/275907/1'>Kyr&#039;Ozch Processing Unit</a>";
			$this->bot->core("loots")->loot[4]['num'] = 3;
			$this->bot->core("loots")->loot[5]['item'] = "<a href='itemref://257961/257961/250'>Energy Redistribution Unit</a>";
			$this->bot->core("loots")->loot[5]['num'] = 1;
			$this->bot->core("loots")->loot[6]['item'] = "<a href='itemref://257964/257964/250'>Visible Light Remodulation Device</a>";
			$this->bot->core("loots")->loot[6]['num'] = 1;
			$this->bot->core("loots")->loot[7]['item'] = "<a href='itemref://257533/257533/1'>All Bounties</a>";
			$this->bot->core("loots")->loot[7]['num'] = 1;
			$this->bot->core("loots")->loot[8]['item'] = "<a href='itemref://257968/257968/1'>All ICE</a>";
			$this->bot->core("loots")->loot[8]['num'] = 1;
			$this->bot->core("loots")->loot[9]['item'] = "<a href='itemref://257706/257706/1'>Kyr&#039;Ozch Helmet (2500 Token board)</a>";
			$this->bot->core("loots")->loot[9]['num'] = 1;

			$this->bot->core("loots")->rlist();
        }
		
        function apf42()
        {
			unset($this->bot->core("loots")->loot); $this->bot->core("loots")->loot=array();
			unset($this->bot->core("loots")->leftovers); $this->bot->core("loots")->leftovers=array();
			$this->bot->core("loots")->count = 0;

			$this->bot->core("loots")->loot[1]['item'] = "<a href='itemref://262656/262656/1'>Kyr'Ozch Invasion Plan (ACDC)</a>";
			$this->bot->core("loots")->loot[1]['num'] = 1;
			$this->bot->core("loots")->loot[2]['item'] = "<a href='itemref://260422/260422/1'>Unlearning Device (AI Reset)</a>";
			$this->bot->core("loots")->loot[2]['num'] = 4;

			$this->bot->core("loots")->rlist();
        }		
		
        function rloot($source, $msg)
        {
                $vars = explode(' ', strtolower($msg));

                if(isset($vars[1])) { $subcom = (strtolower($vars[1])); } else { $subcom = ""; }
                unset($vars[0], $vars[1], $vars[2]);

                $notyet = true;
                $query = "SELECT id, name, ref, img FROM #___RaidLoot WHERE ref = '$subcom'";
                $var = $this -> bot -> db -> select($query, MYSQLI_ASSOC);
                if(!empty($var))
                {
                        $rid = $var[0]["id"];
                        $ref = $var[0]["ref"];
                        $img = $var[0]["img"];
                        $pname = $var[0]["name"];
                        $msg = "<a href='itemref://{$ref}/{$ref}/300'>".$pname."</a>";

                        for ($i=1;$i<= $this->bot->core("loots")->count; $i++)
                        {
                                if ($msg == $this->bot->core("loots")->loot[$i]['item'])
                                {
                                        $this->bot->core("loots")->loot[$i]['num']++;
                                        $num = $this->bot->core("loots")->loot[$i]['num'];
                                        $notyet = false;
                                        $numslot = $i;
                                }
                        }

                        if ($notyet)
                        {
                                $this->bot->core("loots")->count ++;
                                $num = 1;
                                $numslot = $this->bot->core("loots")->count;
                                $this->bot->core("loots")->loot[$numslot]['item'] = $msg;
                                $this->bot->core("loots")->loot[$numslot]['num'] = 1;
                        }

						$this -> bot -> send_pgroup("<highlight>" . $num . "x " . $msg . "<end> being rolled in Slot <highlight>#".$numslot);

                        if ($this->count == 1)
                        {
                                unset($this -> leftovers); $this->bot->core("loots")->leftovers = array();
                        }
                }
                else
                {
						$this -> bot -> send_pgroup("<highlight>" . $num . "x " . $msg . "<end> Not Found!<highlight> #");
                }
        }		
		
        function add_loot($source, $riid, $command)
        {
                if(!empty($riid))
                {
                        $dontadd = 0;

                        $query = "SELECT * FROM #___RaidLoot WHERE ref = '$riid'";
                        $item = $this -> bot -> db -> select($query, MYSQLI_ASSOC);
                        if(!empty($item))
                        {
                                $rid = $item[0]["id"];
                                $ref = $item[0]["ref"];
                                $img = $item[0]["img"];
                                $pname = $item[0]["name"];

                                foreach($item as $key => $loot) {
                                        if($item[0]["ref"] == $loot["ref"]){
                                        $loot[$key]["multiloot"] = $item[0]["multiloot"]+1;
                                        $total = $item[0]["multiloot"]+1;
                                        $dontadd = 1;
                                        $slot = $key;
                                        }
                                }

                                if($dontadd == 0){
                                        if(is_array($loot)){
                                                if(count($loot) < 31)
                                                        $nextloot = count($loot) + 1;
                                                else{
                                                        $this -> bot -> send_tell("You can only roll 30 items max at one time!");
                                                        return;
                                                }
                                        }
                                        else
                                        $nextloot = 1;
                                        $loot[$nextloot]["name"] = $item[0]["name"];
                                        $loot[$nextloot]["linky"] = "<a href='itemref://{$ref}/{$ref}/300'>{$item[0]["name"]}</a>";
                                        $loot[$nextloot]["icon"] = $item[0]["img"];
                                        $loot[$nextloot]["multiloot"] = 1;
                                        $this -> bot -> send_pgroup("<highlight>" . $item[0]["name"] . "<end> will be rolled in Slot <highlight>#".$nextloot);
                                }
                                else{
                                        $this -> bot -> send_pgroup("<highlight>" . $item[0]["name"] . "<end> will be rolled in Slot <highlight>#".$slot."<end> as multiloot. Total: <yellow>".$total."<end>");
                                }
                                $this -> bot -> send_pgroup("To add use !raidadd ".$nextloot.", or !raidadd 0 to remove yourself");
                        }

                }
        } //end function

        function show_aloot($name, $section, $command)
        {
                if(!empty($section))
                {
                        //List all loot for a given section.
                        $query = "SELECT id, name, ref, img FROM #___RaidLoot WHERE area = '$section'";
                        $rloots = $this -> bot -> db -> select($query, MYSQLI_ASSOC);
                        if(!empty($rloots))
                        {
                                $usection = strtoupper($section);
                                $ucmd = ucfirst($command);

                                $window = "<header><center>##highlight##::::: $ucmd AREA Loot :::::##end##<end>\n\n";
                                $window .= "##highlight##for $usection##end##\n\n</center>";
                                $window .= "##highlight##    Raid Admin Commands##end##\n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("list ", "Display the Loot List")." \n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("result ", "Roll the Loot")." \n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("reroll ", "Reroll the leftovers")." \n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("clear ", "Clear the Loot List")." \n\n";
                                //echo "found loot";
                                foreach($rloots as $var)
                                {
                                        $id  = $var['id'];
                                        $ref = $var['ref'];
                                        $img = $var['img'];
                                        $pname = $var['name'];
                                        $lref = "<a href='itemref://{$ref}/{$ref}/300'></a>";

                                $window .= "<a href='itemref://{$ref}/{$ref}/300'><img src=rdb://{$img}></a>\nItem: <font color=#ffff66>".$pname."</font> [ ";
                                $window .= $this -> bot -> core("tools") -> chatcmd("rloot ".$ref, "Add to list")." ]\n\n";
                                }
                                return $this -> bot -> core("tools") -> make_blob("Available $ucmd loot for $usection", $window);
                        }
                        elseif($section == 'all')
                        {
                                $window = "<header><center>##highlight##::::: ALL BOSS Loot :::::##end##<end>\n\n\n</center>";
                                $window .= "##highlight##    Raid Admin Commands##end##\n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("list ", "Display the Loot List")." \n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("result ", "Roll the Loot")." \n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("reroll ", "Reroll the leftovers")." \n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("clear ", "Clear the Loot List")." \n\n";
                                //List all loots for all bosses.
                                $query = "SELECT id, name, ref, img FROM #___RaidLoot";
                                $rloots = $this -> bot -> db -> select($query, MYSQLI_ASSOC);
                                if(!empty($rloots))
                                {
                                        //echo "found loot";
                                        foreach($rloots as $var)
                                        {
                                                $id  = $var['id'];
                                                $ref = $var['ref'];
                                                $img = $var['img'];
                                                $pname = $var['name'];

                                        $window .= "<a href='itemref://{$ref}/{$ref}/300'><img src=rdb://{$img}></a>\nItem: <font color=#ffff66>".$pname."</font> [ ";
                                        $window .= $this -> bot -> core("tools") -> chatcmd("rloot ".$ref, "Add to list")." ]\n\n";
                                        }
                                        return $this -> bot -> core("tools") -> make_blob("Available $command loot for $section", $window);
                                }
                        }
                }
                else
                {
                        echo " *****NO***** section loot found ";
                        return ("rloots = Empty");
                }
        }

        function show_bloot($name, $boss, $command)
        {
                if(!empty($boss))
                {
                        //List all loot for a given boss section.
                        $query = "SELECT id, name, ref, img FROM #___RaidLoot WHERE boss = '$boss'";
                        $rloots = $this -> bot -> db -> select($query, MYSQLI_ASSOC);
                        if(!empty($rloots))
                        {
                                $uboss = strtoupper($boss);
                                $ucmd = ucfirst($command);

                                $window = "<header><center>##highlight##::::: $ucmd BOSS Loot :::::##end##<end>\n\n\n";
                                $window .= "##highlight##for $uboss##end##\n\n</center>";
                                $window .= "##highlight##    Raid Admin Commands##end##\n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("list ", "Display the Loot List")." \n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("result ", "Roll the Loot")." \n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("reroll ", "Reroll the leftovers")." \n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("clear ", "Clear the Loot List")." \n\n";
                                //echo "found loot";
                                foreach($rloots as $var)
                                {
                                        $id  = $var['id'];
                                        $ref = $var['ref'];
                                        $img = $var['img'];
                                        $pname = $var['name'];

                                        $window .= "<a href='itemref://{$ref}/{$ref}/300'><img src=rdb://{$img}></a>\nItem: <font color=#ffff66>".$pname."</font> [ ";
                                        $window .= $this -> bot -> core("tools") -> chatcmd("rloot ".$ref, "Add to list")." ]\n\n";
                                }
                                return $this -> bot -> core("tools") -> make_blob("Available $ucmd loot for $uboss", $window);
                        }
                        elseif($boss == 'all')
                        {
                                $window = "<header><center>##highlight##::::: ALL BOSS Loot :::::##end##<end>\n\n\n</center>";
                                $window .= "##highlight##    Raid Admin Commands##end##\n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("list ", "Display the Loot List")." \n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("result ", "Roll the Loot")." \n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("reroll ", "Reroll the leftovers")." \n";
                                $window .= $this -> bot -> core("tools") -> chatcmd("clear ", "Clear the Loot List")." \n\n";
                                //List all loots for all bosses.
                                $query = "SELECT id, name, ref, img FROM #___RaidLoot";
                                $rloots = $this -> bot -> db -> select($query, MYSQLI_ASSOC);
                                if(!empty($rloots))
                                {
                                        //echo "found loot";
                                        foreach($rloots as $var)
                                        {
                                                $id  = $var['id'];
                                                $ref = $var['ref'];
                                                $img = $var['img'];
                                                $pname = $var['name'];

                                        $window .= "<a href='itemref://{$ref}/{$ref}/300'><img src=rdb://{$img}></a>\nItem: <font color=#ffff66>".$pname."</font> [ ";
                                        $window .= $this -> bot -> core("tools") -> chatcmd("rloot ".$ref, "Add to list")." ]\n\n";
                                        }
                                        return $this -> bot -> core("tools") -> make_blob("Available $command loot for $boss", $window);
                                }
                        }
                }
                else
                {
                        echo " *****NO***** boss loot found ";
                        return ("rloots = Empty");
                }
        }

        function rloot_interface($source, $area)
        {
                if($area === 'Pandemonium')
                {
                        $list = "<header>##highlight##::::: Pandemonium Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##The Beast##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD Beast beastarmor", "Beast Armor")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD Beast beastweapons", "Beast Weapons")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD Beast beaststars", "Beast Stars")."\n";
                        $list .= "\n##highlight##The Night Heart##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD TNH", "The Night Heart")."\n";
                        $list .= "\n##highlight##West Zodiacs##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD WestZods aries", "Aries Items")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD WestZods leo", "Leo Items")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD WestZods virgo", "Virgo Items")."\n";
                        $list .= "\n##highlight##East Zodiacs##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD EastZods aquarius", "Aquarius Items")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD EastZods cancer", "Cancer Items")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD EastZods gemini", "Gemini Items")."\n";
                        $list .= "\n##highlight##Middle Zodiacs##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD MiddleZods libra", "Libra Items")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD MiddleZods pisces", "Pisces Items")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD MiddleZods taurus", "Taurus Items")."\n";
                        $list .= "\n##highlight##North Zodiacs##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD NorthZods capricorn", "Capricorn Items")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD NorthZods sagittarius", "Sagittarius Items")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD NorthZods scorpio", "Scorpio Items")."\n";
                        $list .= "\n##highlight##Other##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD Beast sb", "Shadowbreed items")."\n";
                }
                else if($area === 'APF')
                {
                        $list = "<header>##highlight##::::: Alien Play Field Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tSector Loot##end##\n";
						$list .= $this -> bot -> core("tools") -> chatcmd("APF 7", "Sector 7")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("APF 10", "Sector 10")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("APF 13", "Sector 13")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("APF 28", "Sector 28")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("APF 35", "Sector 35")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("APF 42", "Sector 42")."\n";
                }
                else if($area === 'ALAPPAA')
                {
                        $list = "<header>##highlight##::::: Alappaa Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ALAPPAA alappaa", "Alappaa")."\n";
                }
                else if($area === 'ALBTRAUM')
                {
                        $list = "<header>##highlight##::::: Albatrum Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ALBTRAUM albmemories", "Albatrum Memories")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ALBTRAUM albrings", "Albatrum Rings")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ALBTRAUM albmisc", "Albatrum Misc")."\n";
                }
                else if($area === 'BIODOME')
                {
                        $list = "<header>##highlight##::::: Biodome Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("BIODOME biodome", "Biodome Items")."\n";
                }
                else if($area === 'COLLECTOR')
                {
                        $list = "<header>##highlight##::::: Collector Chests :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tBoss Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("COLLECTOR collechest", "Collector Chests")."\n";
                }
                else if($area === 'DREADLOCH')
                {
                        $list = "<header>##highlight##::::: Dreadloch Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("DREADLOCH dreadweapons", "Dreadloch Weapons")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("DREADLOCH dreadarmor", "Dreadloch Armor")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("DREADLOCH dreadutil", "Dreadloch Tools")."\n";
                }
                else if($area === 'GAUNTLOOT')
                {
                        $list = "<header>##highlight##::::: Gauntlet Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("GAUNTLOOT gauntarmor", "Gauntlet Armors")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("GAUNTLOOT gauntitem", "Gauntlet  Items")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("GAUNTLOOT gauntrare", "Gauntlet Rares")."\n";
                }						
                else if($area === 'DB')
                {
                        $list = "<header>##highlight##::::: Dust Brigade Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("DB db1", "db1 Ground Chief Mikkelsen")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("DB db2", "db2 Ground Chief Aune")."\n";
			   $list .= $this -> bot -> core("tools") -> chatcmd("DB db3", "db3 Dust Brigade 3")."\n";
                }
                else if($area === 'LOX')
                {
                        $list = "<header>##highlight##::::: Legacy Of the XAN Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("LOX 12-man", "12 Man")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("LOX alienthreat", "Sinuh")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("LOX vortexx", "Vortexx")."\n";
                }
                else if($area === 'RHI')
                {
                        $list = "<header>##highlight##::::: Hollow Island Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hilion", "Lion Armor")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hifirstbrood", "First Brood")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hisecondbrood", "Second Brood")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hithirdbrood", "Third Brood")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hifourthbrood", "Fourth Brood")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hififthbrood", "Fifth Brood")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hisixthbrood", "Sixth Brood")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hiseventhbrood", "Seventh Brood")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hieighthbrood", "Eighth Brood")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hininthbrood", "Ninth Brood")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hisuzerain", "Suzerain")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hibroodmother", "Brood Mother")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI hiweed", "Hollow Island Weed")."\n";
                }
                else if($area === 'RIS')
                {
                        $list = "<header>##highlight##::::: Inner Sanctum Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RIS iskey", "IS Permanant Key")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RIS isweapons", "IS Weapons")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RIS isrings", "IS Rings")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RIS isabaddon", "Abaddon Armor parts")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RIS iskarmic", "Karmic Books")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RIS isarmor", "Armor")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RIS ismisc", "IS Misc")."\n";
                }
                else if($area === 'JACK')
                {
                        $list = "<header>##highlight##::::: Jack &#39;Legchopper&#39; Menendez Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tBoss Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("JACK legchopper", "Jack Legchopper")."\n";
                }
                else if($area === 'PRISONERS')
                {
                        $list = "<header>##highlight##::::: Prisoners Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tBoss Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("PRISONERS prisoners", "Prisoner Items")."\n";
                }
                else if($area === 'POH')
                {
                        $list = "<header>##highlight##::::: POH Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("POH poh", "POH Items")."\n";
						$list .= $this -> bot -> core("tools") -> chatcmd("ZOD Beast beastweapons", "Beast Weapons")."\n";
                }
                else if($area === 'TARASQUE')
                {
                        $list = "<header>##highlight##::::: Tarasque Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("TARASQUE taracommon", "Tarasque common loots")."\n";
						$list .= $this -> bot -> core("tools") -> chatcmd("TARASQUE taraboss", "Tarasque boss loots")."\n";
                }				
                else if($area === 'HLTOTW')
                {
                        $list = "<header>##highlight##::::: High Level Temple of 3 Winds Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("HLTOTW hltotwarmors", "HL Totw Armors")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("HLTOTW hltotwrings", "HL Totw Rings")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("HLTOTW hltotwsymbs", "HL Totw Symbs")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("HLTOTW hltotwweaps", "HL Totw Weapons")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("HLTOTW hltotwbooks", "HL Totw Books")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("HLTOTW hltotwmiscs", "HL Totw Miscs")."\n";
                }	
                else if($area === 'HLSUBWAY')
                {
                        $list = "<header>##highlight##::::: High Level Subway Loot :::::##end##<end>\n\n\n";
                        $list .= "##highlight##\t\tPF Loot##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("HLSUBWAY hlsubarmors", "HL Subway Armors")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("HLSUBWAY hlsubmiscs", "HL Subway Miscs")."\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("HLSUBWAY hlsubweapons", "HL Subway Weapons")."\n";
                }					
                else
                {
                        $list = "<center><header>##highlight##::::: Raid Manager 1.11 :::::##end##<end></center>\n\n\n";
                        $list .= "##highlight##    Raid Admin Commands##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("list ", "Display the Loot List")." \n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("result ", "Roll the Loot")." \n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("reroll ", "Reroll the leftovers")." \n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("clear ", "Clear the Loot List")." \n\n";
                        $list .= "##highlight##::::: Alappaa Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ALAPPAA", "Alappaa")."\n\n";
                        $list .= "##highlight##::::: Albtraum Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ALBTRAUM", "Albtraum")."\n\n";
                        $list .= "##highlight##::::: Alien Play Field Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("APF", "Alien Play Fields")."\n\n";
                        $list .= "##highlight##::::: Biodome Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("BIODOME", "Biodome")."\n\n";
                        $list .= "##highlight##::::: Collector Chests :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("COLLECTOR", "Collector")."\n\n";					
                        $list .= "##highlight##::::: Dreadloch Loot :::::##end##\n";					
                        $list .= $this -> bot -> core("tools") -> chatcmd("DREADLOCH", "Dreadloch")."\n\n";
                        $list .= "##highlight##::::: Dustbrigade Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("DB", "Dustbrigade")."\n\n";
                        $list .= "##highlight##::::: Gauntlet Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("GAUNTLOOT", "Gauntlet")."\n\n";						
                        $list .= "##highlight##::::: Hollow Island Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RHI", "Hollow Island")."\n\n";
                        $list .= "##highlight##::::: Inner Sanctum Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("RIS", "Inner Sanctum")."\n\n";
                        $list .= "##highlight##::::: Jack &#39;Legchopper&#39; Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("JACK", "Jack Legchopper")."\n\n";
                        $list .= "##highlight##::::: Legacy of the Xan Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("LOX", "Legacy of the Xan")."\n\n";
                        $list .= "##highlight##::::: Pandemonium Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("ZOD", "Pandemonium")."\n\n";
                        $list .= "##highlight##::::: Prisoners Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("PRISONERS", "Prisoners")."\n\n";
                        $list .= "##highlight##::::: POH Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("POH", "Pyramid of Home")."\n\n";
                        $list .= "##highlight##::::: (HL) Subway Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("HLSUBWAY", "(HL) Subway")."\n\n";
                        $list .= "##highlight##::::: TARASQUE Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("TARASQUE", "Tarasque Castle")."\n\n";
                        $list .= "##highlight##::::: (HL) TOTW Loot :::::##end##\n";
                        $list .= $this -> bot -> core("tools") -> chatcmd("HLTOTW", "(HL) Temple of 3 Winds")."\n\n";
                }
                return $this -> bot -> core("tools") -> make_blob("$area Loot", $list);
        }

}
?>
