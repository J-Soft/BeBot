<?php
/*
* Twink.php - Twinkers helper.
*
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
*
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
* File last changed at $LastChangedDate: 2008-11-30 23:09:06 +0100 (Sun, 30 Nov 2008) $
* Revision: $Id: TowerAttack.php 1833 2008-11-30 22:09:06Z alreadythere $
*/

$twink = new Twink($bot);

/*
The Class itself...
*/
class Twink extends BaseActiveModule
{
	var $mish_and_shop, $dyna_mish, $buffitems, $aliases, $skill_list, $cl_list;
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));

		$this -> register_command("all", "buffitem", "GUEST");
		$this -> register_command("all", "clustloc", "GUEST");
		$this -> register_command("all", "impql", "GUEST");
		$this -> register_alias("impql", "implant");
		$this -> register_command("all", "impreq", "GUEST");
		$this -> help['description'] = 'Helps you to twink.';
        	$this -> help['command']['buffitem <item>']="Shows the twink items details.";
        	$this -> help['command']['clustloc <skill>']="Shows the location of a cluster.";
        	$this -> help['command']['impql <QL>']="Shows the implant requirement at given QL (RK & SL considered).";
        	$this -> help['command']['impreq <ability> <treatment>']="Shows the max QL of RK implant wearable (ability & treatment required).";

$this->mish_and_shop = "Mission reward, shop buyable at QL1-125";
$this->dyna_mish = "Dyna and mission boss loot";
$this->buffitems = array(
"White Sack"
	=> array("Armor", "BioMet", "QL 5 only", "Loot from j00 the leet", "5: +10"),
"Implant Disassembly Clinic"
	=> array("Utility", "Treatment, fixer only", "1-200", "Tradeskilled from portable surgery clinics and Implant disassembly unit from fix. shop",
			 "1: +1", "7: +2", "17: +3", "28: +4", "38: +5", "29: +8", "49: +6", "59: +7", "70: +8", "80: +9", "91: +10", "101: +11", "111: +12",
			 "122: +13", "132: +14", "143: +15", "153: +16", "164: +17", "174: +18", "185: +19", "195: +20" ),
"Treatment And Pharmacy Library"
	=> array("Utility", "Treatment, First Aid and Pharma Tech, doctor only", "1-200", "Tradeskilled from portable surgery clinics and pharma tutoring devices",
			 "1: +3 Treatm., +1 1st aid/pharm.tech.", "5: +4 Treatm., +1 1st aid/pharm.tech.", "11: +5 Treatm., +2 1st aid/pharm.tech.",
			 "17: +6 Treatm., +2 1st aid/pharm.tech.", "23: +7 Treatm., +2 1st aid/pharm.tech.", "29: +8 Treatm., +3 1st aid/pharm.tech.",
			 "35: +9 Treatm., +3 1st aid/pharm.tech.", "41: +10 Treatm., +3 1st aid/pharm.tech.", "47: +11 Treatm., +4 1st aid/pharm.tech.",
			 "53: +12 Treatm., +4 1st aid/pharm.tech.", "59: +13 Treatm., +4 1st aid/pharm.tech.", "65: +14 Treatm., +5 1st aid/pharm.tech.",
			 "71: +15 Treatm., +5 1st aid/pharm.tech.", "77: +16 Treatm., +5 1st aid/pharm.tech.", "83: +17 Treatm., +6 1st aid/pharm.tech.",
			 "89: +18 Treatm., +6 1st aid/pharm.tech.", "95: +19 Treatm., +6 1st aid/pharm.tech.", "101: +20 Treatm., +7 1st aid/pharm.tech.",
			 "107: +21 Treatm., +7 1st aid/pharm.tech.", "113: +22 Treatm., +7 1st aid/pharm.tech.", "119: +23 Treatm., +8 1st aid/pharm.tech.",
			 "125: +24 Treatm., +8 1st aid/pharm.tech.", "131: +25 Treatm., +8 1st aid/pharm.tech.", "137: +26 Treatm., +9 1st aid/pharm.tech.",
			 "143: +27 Treatm., +9 1st aid/pharm.tech.", "149: +28 Treatm., +9 1st aid/pharm.tech.", "155: +29 Treatm., +10 1st aid/pharm.tech.",
			 "161: +30 Treatm., +10 1st aid/pharm.tech.", "167: +31 Treatm., +10 1st aid/pharm.tech.", "173: +32 Treatm., +11 1st aid/pharm.tech.",
			 "179: +33 Treatm., +11 1st aid/pharm.tech.", "185: +34 Treatm., +11 1st aid/pharm.tech.", "191: +35 Treatm., +12 1st aid/pharm.tech.",
			 "197: +36 Treatm., +12 1st aid/pharm.tech."),
"(normal) Treatment Library"
	=> array("Utility", "Treatment and First Aid", "1-200", "Tradeskilled from portable surgery clinics and pharma tutoring devices",
			 "1: +2 Treatm., +1 1st aid", "8: +3 Treatm., +1 1st aid", "11: +3 Treatm., +2 1st aid", "20: +4 Treatm., +2 1st aid", "29: +4 Treatm., +3 1st aid",
			 "33: +5 Treatm., +3 1st aid", "45: +6 Treatm., +3 1st aid", "47: +6 Treatm., +4 1st aid", "57: +7 Treatm., +4 1st aid", "65: +7 Treatm., +5 1st aid",
			 "70: +8 Treatm., +5 1st aid", "82: +9 Treatm., +5 1st aid", "83: +9 Treatm., +6 1st aid", "95: +10 Treatm., +6 1st aid", "101: +10 Treatm., +7 1st aid",
			 "107: +11 Treatm., +7 1st aid", "119: +11 Treatm., +8 1st aid", "120: +12 Treatm., +8 1st aid", "132: +13 Treatm., +8 1st aid",
			 "137: +13 Treatm., +9 1st aid", "145: +14 Treatm., +9 1st aid", "155: +14 Treatm., +10 1st aid", "157: +15 Treatm., +10 1st aid",
			 "169: +16 Treatm., +10 1st aid", "173: +16 Treatm., +11 1st aid", "182: +17 Treatm., +11 1st aid", "191: +17 Treatm., +12 1st aid",
			 "194: +18 Treatm., +12 1st aid"),
"Ring Of Endurance"
	=> array("Armor", "Stamina and Strength", "1-300 in theory, practically ~80-250", "Rare loot from cyborg, mantis, drill and anvian mobs",
			 "1: +1 sta, +1 str", "8: +2 sta, +1 str", "18: +2 sta, +2 str", "25: +3 sta, +2 str", "41: +4 sta, +2 str", "51: +4 sta, +3 str", "57: +5 sta, +3 str",
			 "72: +6 sta, +3 str", "85: +6 sta, +4 str", "88: +7 sta, +4 str", "104: +8 sta, +4 str", "118: +8 sta, +5 str", "120: +9 sta, +5 str",
			 "135: +10 sta, +5 str", "151: +11 sta, +6 str", "167: +12 sta, +6 str", "182: +13 sta, +6 str", "184: +13 sta, +7 str", "198: +14 sta, +7 str",
			 "214: +15 sta, +7 str", "217: +15 sta, +8 str", "230: +16 sta, +8 str", "245: +17 sta, +8 str", "251: +17 sta, +9 str", "261: +18 sta, +9 str",
			 "277: +19 sta, +9 str", "284: +19 sta, +10 str", "293: +20 sta, +10 str"),
"Ring Of Essence"
	=> array("Armor", "Strength and Stamina", "1-300 in theory, practically ~80-250", "Rare loot from cyborg, mantis, drill and anvian mobs",
			 "1: +1 str, +1 sta", "8: +2 str, +1 sta", "18: +2 str, +2 sta", "25: +3 str, +2 sta", "41: +4 str, +2 sta", "51: +4 str, +3 sta", "57: +5 str, +3 sta",
			 "72: +6 str, +3 sta", "85: +6 str, +4 sta", "88: +7 str, +4 sta", "104: +8 str, +4 sta", "118: +8 str, +5 sta", "120: +9 str, +5 sta", 
			 "135: +10 str, +5 sta", "151: +11 str, +6 sta", "167: +12 str, +6 sta", "182: +13 str, +6 sta", "184: +13 str, +7 sta", "198: +14 str, +7 sta", 
			 "214: +15 str, +7 sta", "217: +15 str, +8 sta", "230: +16 str, +8 sta", "245: +17 str, +8 sta", "251: +17 str, +9 sta", "261: +18 str, +9 sta", 
			 "277: +19 str, +9 sta", "284: +19 str, +10 sta", "293: +20 str, +10 sta"),
"Uncle Bazzit 22mm" 
	=> array("Weapon", "Sense", "1-96", "Mission reward", "1: +0", "36: +5", "96: +20"),
"O.E.T. Co. Urban Sniper" 
	=> array("Weapon", "Various, depending on QL", "1-192", $this->mish_and_shop, "1: no boost", "38: +14 Shotgun", "80: no boost", "192: +30 Agility"),
"OT M50 Shotgun"
	=> array("Weapon", "Shotgun, for hotswapping", "1-200", $this->mish_and_shop, "1: no boost", "70: +15", "90: +20", "150: +25", "200: +30"),
"Bladestaff"
	=> array("Weapon", "2he, for hotswapping", "1-200", $this->mish_and_shop, "1: +1", "24: +2", "47: +5", "70: +10", "93: +15", "116: +20",
			 "139: +25", "162: +30", "185: +35", "200: +50"),
"Tripler"
	=> array("Weapo", "1he, later on also Piercing", "1-200", $this->mish_and_shop,
			 "1: no boost", "70: +5 1he", "93: +10 1he", "116: +15 1he", "139: +20 1he, +5 Piercing", "162: +25 1he, +10 Piercing", "200: +35 1he, +30 Piercing" ),
"Personalized Basic Robot Brain"
	=> array("Armor", "Sense", "1-200", "Tradeskilled from robot junk and some shop buyable components", 
			 "1: +3", "10: +4", "26: +5", "43: +6", "60: +7", "76: +8", "93: +9", "109: +10", "126: +11", "142: +12", "159: +13", "176: +14", "192: +15"),
"Combined Commando"
	=> array("Armor", "All ranged and melee skills\n<tab>pistol, shotgun, assault rifle, rife, bow, ranged energy\n<tab>heavy weapons, grenade, fling shot,".
			 " burst, full auto, Sharp Obj,\n<tab>aimed shot, bow special attack, multi ranged,\n<tab>1he, 2he, 1hb, 2hb, piercing, melee energy\n<tab>martial arts,"
			 ." brawl, dimach, parry, riposte\n<tab>fast attack, sneak attack, multi melee", 
			 "1-300", "Tradeskilled from lead bots dropped by Alien Generals/Admirals", "1: +1, AT 1 req", "7: +2", "17: +3",
			 "27: +4", "38: +5", "48: +6", "58: +7", "69: +8", "76: +8, AT 2 req", "79: +9", "89: +10", "99: +11", "110: +12", "120: +13", "130: +14", "141: +15", 
			 "151: +16", "161: +17", "172: +18", "182: +19", "192: +20", "203: +21", "213: +22", "223: +23", "226: +23, AT 3 req", "233: +24", "244: +25",
			 "254: +26", "264: +27", "275: +28", "285: +29", "295: +30"),
"Strong Armor"
	=> array("Armor", "All melee skills\n<tab>1he, 2he, 1hb, 2hb, piercing, melee energy\n<tab>martial arts, brawl, dimach, parry, riposte\n<tab>fast attack,".
			 " sneak attack, multi melee", 
			 "1-300", "Tradeskilled from lead bots dropped by Alien Generals/Admirals", "1: +1, AT 1 req", "7: +2", "17: +3",
			 "27: +4", "38: +5", "48: +6", "58: +7", "69: +8", "76: +8, AT 2 req", "79: +9", "89: +10", "99: +11", "110: +12", "120: +13", "130: +14", "141: +15",
			 "151: +16", "161: +17", "172: +18", "182: +19", "192: +20", "203: +21", "213: +22", "223: +23", "226: +23, AT 3 req", "233: +24", "244: +25",
			 "254: +26", "264: +27", "275: +28", "285: +29", "295: +30"),
"Supple Armor"
	=> array("Armor", "All ranged skills\n<tab>pistol, shotgun, assault rifle, rife, bow, ranged energy\n<tab>heavy weapons, grenade, fling shot, burst, full auto,".
			 "\n<tab>aimed shot, bow special attack, multi ranged, Sharp Obj", "1-300", "Tradeskilled from lead bots dropped by Alien Generals/Admirals",
			 "1: +1, AT 1 req", "7: +2", "17: +3", "27: +4", "38: +5", "48: +6", "58: +7", "69: +8", "76: +8, AT 2 req", "79: +9", "89: +10", "99: +11",
			 "110: +12", "120: +13", "130: +14", "141: +15", "151: +16", "161: +17", "172: +18", "182: +19", "192: +20", "203: +21", "213: +22", "223: +23", 
			 "226: +23, AT 3 req", "233: +24", "244: +25", "254: +26", "264: +27", "275: +28", "285: +29", "295: +30"),
"Arithmetic Armor"
	=> array("Armor", "All Nanoskills and Tradskills\n<tab>BioMet, MatMet, MatCrea, TimeSpace,\n<tab>Sensory Improvement, Psycho Modi\n<tab>".
			 "Mech Eng, Elec Eng, Quatum FT, Weap Smith,\n<tab>Pharma Tech, Nano Prog, Chemistry, Psychology",
			 "1-300, Alien Tech Perk locks", "Tradskilled from lead bots dropped by Alien Generals/Admirals", 
			 "  1:  +1 Nano Skills, +10 Tradeskills, AT 1 req", "  7:  +2 Nano Skills, +10 Tradeskills", "  9:  +2 Nano Skills, +11 Tradeskills", 
			 " 17:  +3 Nano Skills, +11 Tradeskills", " 24:  +3 Nano Skills, +12 Tradeskills", " 27:  +4 Nano Skills, +12 Tradeskills", 
			 " 38:  +5 Nano Skills, +12 Tradeskills", " 39:  +5 Nano Skills, +13 Tradeskills", " 48:  +6 Nano Skills, +13 Tradeskills", 
			 " 54:  +6 Nano Skills, +14 Tradeskills", " 58:  +7 Nano Skills, +14 Tradeskills", " 69:  +8 Nano Skills, +15 Tradeskills",
			 " 76:  +8 Nano Skills, +15 Tradeskills, AT 2 req", " 79:  +9 Nano Skills, +15 Tradeskills", " 84:  +9 Nano Skills, +16 Tradeskills", 
			 " 89: +10 Nano Skills, +16 Tradeskills", " 99: +11 Nano Skills, +17 Tradeskills", "110: +12 Nano Skills, +17 Tradeskills", 
			 "114: +12 Nano Skills, +18 Tradeskills", "120: +13 Nano Skills, +18 Tradeskills", "129: +13 Nano Skills, +19 Tradeskills",
			 "130: +14 Nano Skills, +19 Tradeskills", "141: +15 Nano Skills, +19 Tradeskills", "144: +15 Nano Skills, +20 Tradeskills",
			 "151: +16 Nano Skills, +20 Tradeskills", "158: +16 Nano Skills, +21 Tradeskills", "161: +17 Nano Skills, +21 Tradeskills", 
			 "172: +18 Nano Skills, +21 Tradeskills", "173: +18 Nano Skills, +22 Tradeskills", "182: +19 Nano Skills, +22 Tradeskills",
			 "188: +19 Nano Skills, +23 Tradeskills", "192: +20 Nano Skills, +23 Tradeskills", "203: +21 Nano Skills, +24 Tradeskills",
			 "213: +22 Nano Skills, +24 Tradeskills", "218: +22 Nano Skills, +25 Tradeskills", "223: +23 Nano Skills, +25 Tradeskills", 
			 "226: +23 Nano Skills, +25 Tradeskills, AT 3 req", "233: +24 Nano Skills, +26 Tradeskills", "244: +25 Nano Skills, +26 Tradeskills", 
			 "248: +25 Nano Skills, +27 Tradeskills", "254: +26 Nano Skills, +27 Tradeskills", "263: +26 Nano Skills, +28 Tradeskills",
			 "264: +27 Nano Skills, +28 Tradeskills", "275: +28 Nano Skills, +28 Tradeskills", "278: +28 Nano Skills, +29 Tradeskills", 
			 "285: +29 Nano Skills, +29 Tradeskills", "293: +29 Nano Skills, +30 Tradeskills", "295: +30 Nano Skills, +30 Tradeskills"),
"Galahad Inc. 055" 
	=> array("Weapon", "Various skills dependingon QL", "1-200", $this->mish_and_shop,
			 "1: nothing relevant", "151: +30 Full Auto", "182: nothing relevant"),
"Belt Component Platform"
	=> array("Belt", "Deck slots for NCU etc.", "1-200", "Mission loot/reward, shop buyable at QL1-125", "1: free 1 Deck", "11: free 2 Decks", "30: free 3 Decks",
			 "60: free 4 Decks", "100: free 5 Decks", "160: free 6 Decks"),
"MTI SW500"
	=> array("Weapon", "Pistol, later on also Multi ranged.", "1-180", $this->mish_and_shop, "1: no boost", "110: +20 Pistol", "180: +30 Pistol/Multi ranged."),
"BigBurger Inc." 
	=> array("Weapon", "Burst (for hotswapping)", "1-200", $this->mish_and_shop, "1: +0", "100: +30", "200: +40"),
"MTI B-94"
	=> array("Weapon", "% XP gain", "1-152", $this->mish_and_shop, "1: +0%","12: +1%", "22: +2%", "42: +3%", "62: +4%", "152: +5%"),
"OT-Windchaser M06 Rifle"
	=> array("Weapon", "Various, depending on QL", "1-170", $this->mish_and_shop,
			 "1: nothing", "22: +10 Treatment, +8 Sta", "26: +8 Agi, +15 Rifle", "30: nothing relevant", "38: +10 Sense", "41: +10 Int, +3 NCU", 
			 "50: +12 Aim.Shot, +10 Psychic, +4 NCU", "170: 20 AimedShot"),
"Platinum Filigree Ring Set with a perfectly cut Ruby Pearl/Almandine/Red Beryl"
	=> array("Armor", "Ruby Pearl: +Sen/Int, Almandine: +Str/Agi, Red Beryl: +Sta/Int", "1-250", "Crafted from Platinum Ingots and SL gems",
			 "1: +1", "234: +5"),
"G-Staff"
	=> array("Weapon", "Agility", "1-200", "Mission Reward, Dyna boss loot", "1: +0 ", "100: +20", "150: +40", "200: +60"),
"Eye of the Evening Star"
	=> array("Armor", "Agility and Sense", "100-300, TL locks, nodrop", "Drops from SL catacomb bosses and Lord of the Void",
			 "100: +10 Agi/5 Sen", "105: +11 Agi/5 Sen", "110: +11 Agi/6 Sen", "115: +12 Agi/6 Sen", "125: +13 Agi/6 Sen", "130: +13 Agi/7 Sen",
			 "135: +14 Agi/7 Sen", "145: +15 Agi/7 Sen", "150: +15 Agi/8 Sen", "155: +16 Agi/8 Sen", "165: +17 Agi/8 Sen", "170: +17 Agi/9 Sen",
			 "175: +18 Agi/9 Sen", "185: +19 Agi/9 Sen", "190: +19 Agi/10 Sen", "195: +20 Agi/10 Sen", "205: +21 Agi/10 Sen", "210: +21 Agi/11 Sen",
			 "215: +22 Agi/11 Sen", "225: +23 Agi/11 Sen", "230: +23 Agi/12 Sen", "235: +24 Agi/12 Sen", "245: +25 Agi/12 Sen", "250: +25 Agi/13 Sen",
			 "255: +26 Agi/13 Sen", "265: +27 Agi/13 Sen", "270: +27 Agi/14 Sen", "275: +28 Agi/14 Sen", "285: +29 Agi/14 Sen", "290: +29 Agi/15 Sen",
			 "295: +30 Agi/15 Sen"),
"Ring of Divine Teardrops"
	=> array("Armor", "Sense and Agility", "100-300, TL locks", "Drops from SL catacomb bosses and Lord of the Void",
			 "100: +20 Sen/21 Agi", "110: +21 Sen/11 Agi", "130: +22 Sen/12 Agi", "150: +23 Sen/13 Agi", "170: +24 Sen/14 Agi", "190: +25 Sen/15 Agi",
			 "210: +26 Sen/16 Agi", "230: +27 Sen/17 Agi", "250: +28 Sen/18 Agi", "270: +29 Sen/19 Agi", "290: +30 Sen/20 Agi"),
"Ring of Computing"
	=> array("Armor", "Intelligence and Psychic", "100-300 (nodrop)", "Drops from SL catacomb bosses and Lord of the Void",
			 "100: +5", "105: +6", "115: +7", "125: +8", "135: +9", "145: +10", "155: +11", "165: +12", "175: +13", "185: +14", "195: +15", "205: +16",
			 "215: +17", "225: +18", "235: +19", "245: +20", "255: +21", "265: +22", "275: +23", "285: +24", "295: +25"),
"Ring of Presence"
	=> array("Armor", "Intel, Psychic, all nano skills, treatment, first aid, CompLit, Nanoprog., Psychology, Tutoring, Adventuring, Perception", 
			 "1-200, lvl locks", $this->dyna_mish, "1: +1", "35: +2", "100: +3", "167: +4"),
"Polychromatic Explosiv Pillows"
	=> array("Weapon", "Can be switched between Agi/Sense, Str/Sta and Int/Psy", "QL300 only", "Made from applying APF nodrop onto Concrete Cushion", "QL300: +10"),
"Kirch Kevlar"
	=> array("Armor", "Agility, Psychic, Sense", "1-200", $this->dyna_mish, "1: +1", "26: +2",  "76: +3", "126: +4", "176: +5"),
"Sekutek Chilled Plasteel"
	=> array("Armor", "Agility, Intel, Sense", "1-200", $this->dyna_mish, "1: +1", "26: +2",  "76: +3", "126: +4", "176: +5"),
"Nova Dillon"
	=> array("Armor", "All base abilities",    "1-200", $this->dyna_mish, "1: +1", "35: +2", "101: +3", "167: +4"),
"Biomech"
	=> array("Armor", "Treatment, First aid",  "75-200", "Miss. reward, store buyable at QL75-125", "75: +4", "79: +5", "101: +6", "123: +7",
		     "145: +8", "167: +9", "189: +10"),
"Concrete Cushion"
	=> array("Weapon", "Strength, Stamina",	"1-160", "Mission reward", "1: +2", "10: +8", "160: +20"),
"Tsakachumi PTO-HV Counter-Sniper Rifle"
	=> array("Weapon", "Agility, later on also Rifle and AimedShot", "1-175", "Miss. reward, store buyable at QL1-125", 
			 "1: +4 Agi", "40: +20 Agi", "80: +25 Agi, +20 Rifle", "175: +30 Agi/Rifle/AS"),
"ICC arms gun bag"
	=> array("Weapon", "Sense, later on also Psychic", "1-145", "Miss. reward, store buyable at QL1-125", "1: +0/0", "30: +10/0", "80: +20/10", "145: +30/20"),
"O.E.T. pistol"
	=> array("Weapon", "Intel, later on also Psychic", "1-200", "Miss. reward, store buyable at QL1-125", "1: +5 Int", "70: +10 Int", "90: +15 Int",
			 "100: +20 Int/Psy", "160: +20 Int/+25 Psy", "200: +25 Int/Psy"),	
"Soft Pepper Pistol"
	=> array("Weapon", "Biomet, MatMet, MatCrea", "1-194", "Tradeskilled from sealed weap. receptable (mission chest loot)", "1: +2", "51: +14",
			 "101: +18", "151: +24", "194: +28"),
"Pillow with Important Stripes"
	=> array("Weapon", "Biomet, MatMet, MatCrea", "1-194", "Mission reward, store buyable in SL gardens", "1: +2", "51: +14", "101: +18", "151: +24", "194: +28"),
"Galahad Inc T70 pistol"
	=> array("Weapon", "Various, depending on QL", "1-200", "Misssion reward, store buyable till QL125", "1: no boost", "33: +14 pistol", 
			"44: +20 Comp.Lit.", "77: +20 Sense", "99: +20 BioMet/MatMet", "111: +25 Psychic/Psymod", "153: +20 MatCrea", "200: +30 MatCrea/TimeSpace/MechEngi"),
"Spirit Infused Yutto's Memory"
	=> array("NCU", "Treatment", "160-300", "Tradeskilled from Ancient Circuits and Yutto's Memories", "160: +5", "174: +6", "202: +7", "230: +8",
			 "258: +9", "286: +10"),
"Freedom Arms 3927"
	=> array("Weapon", "Various, depending on QL", "1-200", "Halloween Uncle Pumpkinhead loot", "1: no boost", "100: +20 sta/str", 
			 "108: +20 sta/str, +10 Multi Ranged", "150: +25 Treatment, +20 pistol/Multi Ranged", "158: +20 sta, +20 Multi ranged, +25 MatMet", 
			 "200: +30 str/sta, +30 Multi ranged")
);
$this->aliases =
array(
"Uncle Bazzit 22mm" => array("Uncle Bazzit Custom 22mm", "Uncle Bazzit (New) Custom 22mm", "Uncle Bazzit Rusty 22mm"),
"OT M50 Shotgun" => array("OT M50-ACX Shotgun", "OT M50adc Shotgun", "OT M50atg Shotgun", "OT M50bbk Shotgun", "OT M50bhq Shotgun", 
						  "OT M50caw Shotgun", "Rusty OT M50 Shotgun"),
"Bladestaff" => array("Refitted Polearm", "Training Bladestaff", "Long Slank Computer X-II", "Enhanced Polearm", "Omni-Tek Crowd Unit XI-Autotarget", "Long Slank", "Notum Bladestaff", "Jobe-Made Bladestaff"),
"Tripler" => array("Rusty Tripler", "Second-Hand Tripler", "Improved Tripler", "Enhanced Tripler", "Omni-Tek Manual Tripler", "Monofilament Tripler"),
"Strong Armor" => array("Combined Mercenary's"),
"Arithmetic Armor" => array("Combined Scout's", "Combined Officer's"),
"Supple Armor" => array("Combined Sharpshooter's"),
"Galahad Inc. 055" => array("Battered Galahad Inc. 055", "Galahad Inc. 055 Police Aegis", "Galahad Inc. 055 Police Big Mama", "Galahad Inc. 055 Police Chapman", 
							"Galahad Inc. 055 Police Edition", "Galahad Inc. 055 Police Lobster", "Galahad Inc. 055 Police Penny", "Galahad Inc. 055 Police Phoenix"),
"MTI SW500" => array("Rusty MTI SW500", "MTI SW500 Chapman", "MTI SW500 Bernstock", "MTI SW500 Lux", "MTI SW500 Geyser"),
"BigBurger Inc." => array("BigBurger Inc. Chapman Max", "Worn BigBurger Inc."),
"MTI B-94" => array("Rusty MTI B-94"),
"OT-Windchaser M06 Rifle" => array("OT-Windchaser M05 Rifle", "OT-Windchaser M06 Rifle", "OT-Windchaser M06 Quartz", "OT-Windchaser M06 Hematite", "OT-Windchaser M06 Onyx", "OT-Windchaser M06 Jasper", "OT-Windchaser M06 Emerald", "OT-Windchaser M06 Mother of Pearl", "OT-Windchaser M06 Ruby", "OT-Windchaser M06 Diamond"),
"G-Staff" => array("Apprentice G-Staff", "Junior G-Staff" , "Senior G-Staff" , "Master G-Staff"),
"Biomech" => array("Augmented Biomech", "Basic Biomech"),
"Concrete Cushion" => array("Creviced Concrete Cushion", "Excellent Concrete Cushion"),
"Tsakachumi PTO-HV Counter-Sniper Rifle" => array("Tsakachumi PTO-HV Counter-Sniper Rifle", "Tsakachumi PTO-HV.2 Counter-Sniper Rifle", "Tsakachumi PTO-HV3a Counter-Sniper Rifle", "Tsakachumi PTO-HV6 Counter-Sniper Rifle"),
"ICC arms gun bag" => array("ICC arms 2Q2B gun bag", "ICC arms 2Q2C gun bag", "ICC arms 2Q2C (u) gun bag", "ICC arms 2Q2N-8 gun bag"),
"O.E.T. pistol" => array("Second-Hand Old English Trading Co.", "O.E.T. Co. Pelastio V2", "O.E.T. Co. Pelastio V3", "O.E.T. Co. Jess", "O.E.T. Co. Maharanee"), 
"Soft Pepper Pistol" => array("Cheap Soft Pepper Pistol", "Worn Soft Pepper Pistol", "Shining Soft Pepper Pistol", "Majestic Soft Pepper Pistol"),
"Pillow with Important Stripes" => array("Love-Filled Pillow with Important Stripes", "Perfumed Pillow with Important Stripes", "Soft Pillow with Important Stripes", "Tear-soaked Pillow with Important Stripes"),
"Galahad Inc T70 pistol" => array("Burned-Out Galahad Inc T70", "Galahad Inc T70 Service Pistol", "Galahad Inc T70 Salamanca", "Galahad Inc T70 Myre", "Galahad Inc T70 Beyer", "Galahad Inc T70 Zig Zag", "Galahad Inc T70 Tsuyoshi", "Galahad Inc T70 Beardsley", "Galahad Inc T70 Priscilla", "Galahad Inc T70 Khan"),
"Freedom Arms 3927" => array("Sparkling Freedom Arms 3927", "Battered Freedom Arms 3927", "Freedom Arms 3927 Notum", "Freedom Arms 3927 Chapman", "Freedom Arms 3927 Guerrilla", "Freedom Arms 3927 G2")
);
$this->skill_list = array("Strength", "Stamina", "Agility", "Sense", "Psychic", "Intelligence", "Martial Arts", "Brawling", "Dimach", "Riposte", "Adventuring", "Swimming",
					"Body Dev", "Nano Pool", "1hb", "2hb", "1he", "2he", "piercing", "melee energy", "parry", "sneak attack", "multi melee", "fast attack",
					"Sharp Obj", "Grenade", "Heavy Weapons", "Bow", "Pistol", "Assault Rif", "MG/SMG", "Shotgun", "Rifle", "Ranged Energy", "Fling Shot",
					"Aimed Shot", "Burst", "Full Auto", "Bow Special Attack", "Multi Ranged", "Mech Eng", "Pharma Tech", "Nano Prog", "Chemistry", "Psychology",
					"Elec Eng", "Quantum FT", "Weap Smith", "Comp Lit", "Tutoring", "Bio Met", "Mat Met", "Psy Mod", "Mat Crea", "Time Space", "Sens Imp",
					"First Aid", "Treatment", "Map Nav");
$this->cl_list = array(
	"1h Blunt" => array("R.Arm", "R.Wrist", "R.Hand"),
	"1h Edged Weapon" => array("R.Arm", "R.Wrist", "R.Hand"),
	"2h Blunt" => array("R.Arm", "L.Arm", "Chest"),
	"2h Edged" => array("R.Arm", "L.Arm", "Waist"),
	"% Add All Def. Jobe" => array("L.Arm", "R.Arm", "Feet"),
	"% Add All Off Jobe" => array("L.Arm", "R.Arm", "Feet"),
	"% Add. Chem. Dam. Jobe" => array("R.Hand", "L.Wrist", "R.Wrist"),
	"% Add. Energy Dam. Jobe" => array("R.Hand", "L.Wrist", "R.Wrist"),
	"% Add. Fire Dam. Jobe" => array("R.Hand", "L.Wrist", "R.Wrist"),
	"% Add. Melee Dam. Jobe" => array("R.Hand", "L.Wrist", "R.Wrist"),
	"% Add. Poison Dam. Jobe" => array("R.Hand", "L.Wrist", "R.Wrist"),
	"% Add. Proj. Dam. Jobe" => array("R.Hand", "L.Wrist", "R.Wrist"),
	"% Add.Rad. Dam. Jobe" => array("R.Hand", "L.Wrist", "R.Wrist"),
	"% Add. Xp Jobe" => array("Ear", "Feet", "Leg"),
	"Adventuring" => array("Leg", "Waist", "Chest"),
	"Agility" => array("Leg", "Feet", "Waist"),
	"Aimed Shot" => array("Eye", "R.Wrist", "R.Hand"),
	"Assault Rif" => array("R.Arm", "R.Hand", "Eye"),
	"Bio.Metamor" => array("Head", "Chest", "Waist"),
	"Body Dev" => array("Chest", "Waist", "Leg"),
	"Bow" => array("R.Arm", "L.Arm", "Eye"),
	"Bow Spc Att" => array("Head", "R.Hand", "R.Wrist"),
	"Brawling" => array("L.Arm", "R.Arm", "Waist"),
	"Break & Entry" => array("R.Arm", "L.Arm", "Chest"),
	"Burst" => array("R.Arm", "R.Wrist", "R.Hand"),
	"Chemical AC" => array("Waist", "R.Arm", "L.Arm"),
	"Chemistry" => array("Head", "Eye", "R.Hand"),
	"Cold AC" => array("Waist", "R.Hand", "L.Hand"),
	"Comp. Liter" => array("Head", "Eye", "R.Hand"),
	"Concealment" => array("Feet", "Ear", "Eye"),
	"Dimach" => array("Chest", "Head", "Waist"),
	"Disease AC" => array("Head", "Leg", "Chest"),
	"Dodge-Rng" => array("Leg", "Feet", "Waist"),
	"Duck-Exp" => array("Leg", "Waist", "Feet"),
	"Elec. Engi" => array("Eye", "Head", "R.Hand"),
	"Energy AC" => array("Chest", "Leg", "Waist"),
	"Evade-ClsC" => array("Feet", "Leg", "Waist"),
	"Fast Attack" => array("L.Hand", "R.Hand", "R.Arm"),
	"Fire AC" => array("Waist", "L.Hand", "R.Hand"),
	"First Aid" => array("Head", "R.Hand", "L.Hand"),
	"Fling Shot" => array("R.Arm", "R.Hand", "R.Wrist"),
	"Full Auto" => array("R.Arm", "R.Wrist", "Waist"),
	"Grenade" => array("R.Arm", "Eye", "R.Hand"),
	"Heal Delta Jobe" => array("L.Arm", "Feet", "Leg"),
	"Heavy Weapons" => array("R.Arm", "Eye", "R.Hand"),
	"Imp/Proj AC" => array("Leg", "Chest", "Waist"),
	"Intelligence" => array("Head", "Eye", "Ear"),
	"Map Navig" => array("Eye", "Head", "Ear"),
	"Martial Arts" => array("R.Hand", "Feet", "L.Hand"),
	"Matter Crea" => array("Head", "R.Hand", "Eye"),
	"Matt.Metam" => array("Head", "Chest", "L.Arm"),
	"Max Health" => array("Chest", "Waist", "Leg"),
	"Max Nano" => array("Head", "Waist", "Chest"),
	"Max NCU Jobe" => array("Ear", "R.Wrist", "Leg"),
	"Mech. Engi" => array("Head", "Eye", "R.Arm"),
	"Melee Ener" => array("Head", "L.Wrist", "R.Wrist"),
	"Melee. Init" => array("Feet", "Leg", "Waist"),
	"Melee/Ma AC" => array("Chest", "Waist", "Leg"),
	"MG / SMG" => array("R.Arm", "R.Hand", "Chest"),
	"Multi Ranged" => array("L.Wrist", "R.Wrist", "Eye"),
	"Mult. Melee" => array("L.Wrist", "Eye", "R.Wrist"),
	"NanoC. Init" => array("Head", "Eye", "Chest"),
	"Nano Formula Interrupt Modifier Jobe" => array("Leg", "L.Hand", "Chest"),
	"Nano Point Cost Modifier Jobe" => array("Waist", "Ear", "L.Arm"),
	"Nano Pool" => array("Chest", "Head", "Waist"),
	"Nano Progra" => array("Head", "Eye", "R.Hand"),
	"Nano Resist" => array("Head", "R.Wrist", "L.Wrist"),
	"Parry" => array("R.Wrist", "L.Wrist", "R.Arm"),
	"Perception" => array("Ear", "Eye", "Head"),
	"Pharma Tech" => array("Head", "Eye", "R.Hand"),
	"Physic. Init" => array("Feet", "R.Arm", "L.Arm"),
	"Piercing" => array("R.Arm", "L.Arm", "Waist"),
	"Pistol" => array("R.Wrist", "R.Hand", "Eye"),
	"Psychic" => array("Head", "Chest", "Ear"),
	"Psychology" => array("Head", "Ear", "Eye"),
	"Psycho Modi" => array("Head", "Eye", "Ear"),
	"Quantum FT" => array("Head", "Eye", "R.Hand"),
	"Radiation AC" => array("Waist", "L.Arm", "R.Arm"),
	"Ranged Ener" => array("Head", "Eye", "L.Hand"),
	"Ranged. Init" => array("R.Wrist", "Head", "R.Hand"),
	"RangeInc. NF Jobe" => array("L.Arm", "L.Hand", "R.Arm"),
	"RangeInc. Weapon Jobe" => array("Eye", "Feet", "R.Arm"),
	"Rifle" => array("Eye", "R.Wrist", "L.Wrist"),
	"Riposte" => array("R.Wrist", "L.Wrist", "R.Arm"),
	"Run Speed" => array("R.Wrist", "L.Wrist", "Leg"),
	"Sense" => array("Chest", "Waist", "Head"),
	"Sensory Impr" => array("Head", "Eye", "Chest"),
	"Sharp Obj" => array("R.Wrist", "R.Hand", "Eye"),
	"Shield Chemical AC Jobe" => array("L.Hand", "Feet", "L.Wrist"),
	"Shield Cold AC Jobe" => array("L.Hand", "Feet", "L.Wrist"),
	"Shield Energy AC Jobe" => array("L.Wrist", "L.Hand", "Leg"),
	"Shield Fire AC Jobe" => array("L.Wrist", "L.Hand", "Leg"),
	"Shield Melee AC Jobe" => array("L.Hand", "Feet", "L.Wrist"),
	"Shield Poison AC Jobe" => array("L.Hand", "Feet", "L.Wrist"),
	"Shield Projectile AC Jobe" => array("L.Wrist", "L.Hand", "Leg"),
	"Shield Radiation AC Jobe" => array("L.Wrist", "L.Hand", "Leg"),
	"Shotgun" => array("R.Arm", "R.Hand", "Waist"),
	"Skill Time Lock Modifier Jobe" => array("Leg", "L.Hand", "Chest"),
	"Sneak Atck" => array("Feet", "R.Wrist", "Eye"),
	"Stamina" => array("Chest", "Leg", "Waist"),
	"Strength" => array("R.Arm", "L.Arm", "Chest"),
	"Swimming" => array("Leg", "R.Arm", "L.Arm"),
	"Time & Space" => array("Head", "R.Hand", "Eye"),
	"Trap Disarm" => array("R.Hand", "L.Hand", "Head"),
	"Treatment" => array("Head", "Eye", "R.Hand"),
	"Tutoring" => array("Eye", "Ear", "Head"),
	"Vehicle Air" => array("Eye", "Ear", "Head"),
	"Vehicle Grnd" => array("Head", "Eye", "Ear"),
	"Vehicle Hydr" => array("Head", "Eye", "Ear"),
	"Weapon Smt" => array("R.Hand", "Head", "Eye"),
	"NanoCluster of Nano Regeneration" => array("R.Wrist", "R.Arm", "Feet"));
	}

	function command_handler($sender, $msg, $channel)
	{
		if (preg_match("/^buffitem (.+)/i", $msg, $arr))
			return $this -> show_what($sender, $arr, $channel);
        elseif (preg_match("/^clustloc (.+)/i", $msg, $arr))
			return $this -> find_clust($sender, $arr, $channel);
        elseif (preg_match("/^impreq ([0-9]+) ([0-9]+)/i", $msg, $arr))
			return $this -> imp_req($sender, $arr, $channel);
         elseif (preg_match("/^impql ([0-9]+)/i", $msg, $arr))
			return $this -> imp_ql($sender, $arr, $channel);
	}

		function matches($probe, $comp) {
			$bits = explode(" ", $comp);
			$match = true;
			foreach ($bits as $substr) {
				if (stripos($probe, $substr) === false) {
					$match = false;
				}
			}
			return $match;
		}

		function contains($ary, $str) {
			$match = false;
			foreach ($ary as $probe) {
				if ($this -> matches($probe, $str)) {
					$match = true;
					break;
				}
			}
			return $match;
		}

		function duplicate($str, $ary) {
			$result = false;
			foreach ($ary as $value) {
				if ($value[0] == $str)
					$result = true;
			}
			return $result;
		}

		function get_alias($ary, $comp) {
			//print_r($ary);
			foreach($ary as $alias) {
				if ($this -> matches($alias, $comp)) {
					$result = $alias;
					return $result;
				}
			}

		}

		function make_info($n, $ary) {
			$result = "<u>".$n."</u>:\n\n".
			 		  "<font color=#33ff66>Category</font>: ".array_shift($ary)."\n".
					  "<font color=#33ff66>Boosts</font>: ".array_shift($ary)."\n".
				      "<font color=#33ff66>QL range</font>: ".array_shift($ary)."\n".
				      "<font color=#33ff66>Aquisition</font>:\n<tab>".array_shift($ary)."\n".
				      "<font color=#33ff66>Buff Break points</font>:\n";
			foreach ($ary as $breakpoint) {
				$result .= "<tab>QL ".$breakpoint."\n";
			}
			return $result;
		}

		function is_unique($str, $list) {
			$unique = true;
			$count = 0;
			foreach ($list as $probe) {
				if ($this -> matches($probe, $str))
					$count++;
			}
			$unique = ($count == 1 ? true : false);
			return $unique;
		}

	function show_what($sender, $arr, $channel)
	{
	$header = "<header>::::: Twink info :::::<end>\n\n";
	$footer = "by Imoutochan, RK1";
		$name = $arr[1];
		$results = array();
		$found = 0;
		// search item line database
		foreach ($this->buffitems as $key => $value) {
			unset($info);
			if ($this->matches($key, $name)) {
				$found++;
				$info =	$this->make_info($key, $value);
				array_unshift($results, array($key, $info));
			}
		}
		// search  item alias database
		foreach ($this->aliases as $key => $values) {
			unset($info);
			if ($this->contains($values, $name) && !($this->duplicate($key, $results))) {
				$found++;
				$buffitem = $this->buffitems[$key];
				$alias = $this->get_alias($values, $name);
				$info =	"Item $alias\nbelongs into the line of ";
				$info .= $this->make_info($key, $buffitem);
				array_unshift($results, array($key, $info, $alias));
			}
		}

		if ($found == 0) {
                $this -> bot -> send_output($sender, "No such item found in my database.", $channel);
		return;
		} else {
			$inside = $header;
			$inside .= "Your query of ".$name." returned the following item line(s):\n\n";
			if ($found == 1) {
				$inside .= $results[0][1]."\n\n";
			} else {
				foreach ($results as $result) {
					$inside .= "- <a href='chatcmd:///tell ".$this->bot->botname ." buffitem ".$result[0]."'>".$result[0]."</a>".
					           (sizeof($result) == 3 ? " (".$result[2].")" : "")."\n";
				}
				$inside .= "\n".sizeof($results)." results found, please pick one by clicking it\n\n";
			}
			$inside .= $footer;
			$windowlink = $this -> bot -> core("tools") -> make_blob("Your item search result", $inside);
                        $this -> bot -> send_output($sender, $windowlink, $channel);
                        return;
		}
	}

	function find_clust($sender, $arr, $channel)
	{
	$header = "<header>::::: Twink info :::::<end>\n\n";
	$footer = "by Imoutochan, RK1";
       		$name = trim($arr[1]);
		$info = "";
		$found = 0;
		foreach ($this->cl_list as $key => $value) {
			if ($found < 10 && $this->matches($key, $name)) {
				$found++;
				$info .= "<u>$key Cluster</u>: <font color=#ffcc33>Shiny</font>: ".$value[0].
						 " <font color=#ffff55>Bright</font>: ".$value[1].
						 " <font color=#FFFF99>Faded</font>: ".$value[2];
			}
		}
		if ($found == 0) {
			$windowlink = "No matches in my database, sorry.";
                        $this -> bot -> send_output($sender, $windowlink, $channel);
                        return;
		} elseif ($found == 1) {
			$windowlink = str_replace("--", "", $info);
                        $this -> bot -> send_output($sender, $windowlink, $channel);
                        return;
		} else {
			$inside = $header;
			$inside .= "Your query of ".$name." returned the following results:\n\n";
			$inside .= str_replace("--", "\n\n", $info);
			$inside .= $footer;
			$windowlink = $this -> bot -> core("tools") -> make_blob("Your cluster search result", $inside);
                        $this -> bot -> send_output($sender, $windowlink, $channel);
                        return;
		}
        }

	function imp_req($sender, $arr, $channel)
	{
	$header = "<header>::::: Twink info :::::<end>\n\n";
	$footer = "by Bitnykk, RK2";
	$abil = $arr[1];
	$treat = $arr[2];
	if (($abil < 0) || ($treat < 0)) { $msg = "Values must be positive !";
        } elseif (($abil > 2500) || ($treat > 5000)) { $msg = "Too high number ...";
        } else {
             if ($abil <= 404) {
                     $qla = floor((($abil-4)/2));
             } else {
                     $qla = floor(((($abil-426)/669)*100)+201);
             }
	     if ($treat <= 951) {
                    $qlt = floor(((($treat-11)*199)/940)+1);
             } else {
                     $qlt = floor(((($treat-1001)/1050)*100)+201);
             }
             if ($qla > 300) { $qla = 300; }
             if ($qlt > 300) { $qlt = 300; }
             if ($qla == $qlt) { $inside = "With ".$abil." abil & ".$treat." treat, you can wear exactlt QL .".$qla." RK implants";
             } elseif ($qla > $qlt) { $inside = "With ".$abil." abil you could wear QL ".$qla." RK implants, but ".$treat." treat limitates you to QL ".$qlt;
             } else { $inside = "With ".$treat." treat you could wear QL ".$qlt." RK implants, but ".$abil." abil limitates you to QL ".$qla;
             }
        $msg = $this -> bot -> core("tools") -> make_blob("Your impreq calculation result", $inside);
        }
        $this -> bot -> send_output($sender, $msg, $channel);
        }

	function imp_ql($sender, $arr, $channel)
	{
	$header = "<header>::::: Twink info :::::<end>\n\n";
	$footer = "by Bitnykk, RK2";
        $ql = $arr[1];
        if (($ql < 1) || ($ql > 300)) { $msg = "QL must be between 1 and 300 !";
                } elseif ($ql < 201) {
                if ($ql < 101) { $tl = "(TL3 for SL cluster)."; } else { $tl = "(TL4 for SL cluster)."; }
                $treat = ((940*($ql-1))/199)+11;
                $sl1 = $treat+(($ql/200)*54);
                $abil = (($ql*2)+4);
                $sl2 = $abil+(($ql/200)*50) + 10;
                $inside = "QL ".$ql." implant requires :\n".round($treat)." treat | ".round($abil)." abil (RK clusters)\n".round($sl1)." treat | upto ".round($sl2)." abil (SL clusters)\n\nDon't forget eventual lvl requirement ".$tl;
                $msg = $this -> bot -> core("tools") -> make_blob("Your impql calculation result", $inside);
         } else {
                if ($ql > 250) { $tl = "(TL6 for any cluster)."; } else { $tl = "(TL5 for any cluster)."; }
                $treat = (1050*(($ql-201)/100))+1001+((($ql-200)/100)*10);
                $abil = (((($ql-201)/100)*669)+426)+((($ql-200)/100)*7);
                $sl2 = $abil+((($ql-201)/100)*86) + 50;
                $inside = "QL ".$ql." implant requires :\n".round($treat)." treat | ".round($abil)." abil (RK clusters)\n".round($treat)." treat | upto ".round($sl2)." abil (SL clusters)\n\nDon't forget eventual lvl requirement ".$tl;
                $msg = $this -> bot -> core("tools") -> make_blob("Your impql calculation result", $inside);
               }
        $this -> bot -> send_output($sender, $msg, $channel);
        }

}
?>
