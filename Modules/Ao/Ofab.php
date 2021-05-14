<?php
/*
* Ofab.php - Module Ofab content
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

$ofab = new Ofab($bot);

class Ofab extends BaseActiveModule
{

	function __construct (&$bot)
	{
		parent::__construct($bot, get_class($this));
	
		$this -> register_command("all", "ofabweapon", "MEMBER");
		$this -> register_alias("ofabweapon", "ofabweapons");
		$this -> register_command("all", "ofabarmor", "MEMBER");
		$this -> register_alias("ofabarmor", "ofabarmors");
		$this -> help['description'] = "Gives information about Ofab content.";
		$this -> help['command']['ofabweapon <QL>'] = "Show Ofab weapons of given QL (must be a multiple of 25).";
		$this -> help['command']['ofabarmor <QL> <profession>'] = "Show Ofab armor of given QL (must be a multiple of 25) and profession.";
	}
	
	function command_handler($source, $msg, $origin)
	{
		$this->error->reset();
		
		$com = $this->parse_com($msg, array('com', 'sub'));
		$com['sub'] = strtolower($com['sub']);	
		switch($com['com'])
		{
			case 'ofabarmor':
				$args = explode(" ",$com['sub']);
				if ( count($args)==2 && ( $args[0]!="" && is_numeric($args[0]) && $args[0]>=25 && $args[0]<=300 && ($args[0]==25||$args[0]==50||$args[0]==75||$args[0]==100||$args[0]==125||$args[0]==150||$args[0]==175||$args[0]==200||$args[0]==225||$args[0]==250||$args[0]==275||$args[0]==300) ) )
				{
					if(($profname = $this -> bot -> core("professions") -> full_name($args[1])) instanceof BotError) return $profname;
					return($this -> ofab_armor($source, $origin, $profname, $args[0]));
					break;
				}
				else
				{
					$this -> error -> set("You have to submit an available QL (multiple of 25)and an existing profession.");
					return($this->error->message());
					break;
				}
			case 'ofabweapon':
				if ( $com['sub']!="" && is_numeric($com['sub']) && $com['sub']>=25 && $com['sub']<=300 && ($com['sub']==25||$com['sub']==50||$com['sub']==75||$com['sub']==100||$com['sub']==125||$com['sub']==150||$com['sub']==175||$com['sub']==200||$com['sub']==225||$com['sub']==250||$com['sub']==275||$com['sub']==300) )
				{
					return($this -> ofab_weapon($source, $origin, $com['sub']));
					break;
				}
				else
				{
					$this -> error -> set("You have to submit an available weapon QL. Must be a multiple of 25.");
					return($this->error->message());
					break;
				}				
			default:
				$this -> error -> set("Broken plugin, recieved unhandled command: $command");
				return($this->error->message());
		}
	}

	function ofab_armor($name, $origin, $profname, $ql)
	{
		// prof name slot low high upgraded
		$armors[] = array("Adventurer", "Ofab Adventurer Body Armor", "body", 264202, 264203, 0);
		$armors[] = array("Adventurer", "Ofab Adventurer Boots", "boots", 264200, 264201, 0);
		$armors[] = array("Adventurer", "Ofab Adventurer Pants", "pants", 264198, 264199, 0);
		$armors[] = array("Adventurer", "Ofab Adventurer Sleeves", "sleeves", 264204, 264205, 0);
		$armors[] = array("Adventurer", "Ofab Adventurer Gloves", "gloves", 264206, 264207, 0);
		$armors[] = array("Adventurer", "Ofab Adventurer Helmet", "helmet", 264208, 264209, 0);
		$armors[] = array("Adventurer", "Jayde's Odyssey ring", "ring", 267560, 267561, 0);
		$armors[] = array("Agent", "Ofab Agent Body Armor", "body", 264305, 264306, 0);
		$armors[] = array("Agent", "Ofab Agent Boots", "boots", 264311, 264312, 0);
		$armors[] = array("Agent", "Ofab Agent Pants", "pants", 264317, 264318, 0);
		$armors[] = array("Agent", "Ofab Agent Sleeves", "sleeves", 264299, 264300, 0);
		$armors[] = array("Agent", "Ofab Agent Gloves", "gloves", 264293, 264294, 0);
		$armors[] = array("Agent", "Ofab Agent Helmet", "helmet", 264287, 264288, 0);
		$armors[] = array("Agent", "Agents' Ring of Aim", "ring", 267582, 267583, 0);
		$armors[] = array("Bureaucrat", "Ofab Bureaucrat Vest", "body", 264521, 264522, 0);
		$armors[] = array("Bureaucrat", "Ofab Bureaucrat Boots", "boots", 264527, 264528, 0);
		$armors[] = array("Bureaucrat", "Ofab Bureaucrat Pants", "pants", 264533, 264534, 0);
		$armors[] = array("Bureaucrat", "Ofab Bureaucrat Sleeves", "sleeves", 264515, 264516, 0);
		$armors[] = array("Bureaucrat", "Ofab Bureaucrat Gloves", "gloves", 264509, 264510, 0);
		$armors[] = array("Bureaucrat", "Ofab Bureaucrat Headgear", "helmet", 264503, 264504, 0);
		$armors[] = array("Bureaucrat", "Bureaucrats' Ring of Order", "ring", 268307, 268308, 0);
		$armors[] = array("Doctor", "Ofab Doctor Body", "body", 264668, 264669, 0);
		$armors[] = array("Doctor", "Ofab Doctor Boots", "boots", 264674, 264675, 0);
		$armors[] = array("Doctor", "Ofab Doctor Pants", "pants", 264680, 264681, 0);
		$armors[] = array("Doctor", "Ofab Doctor Sleeves", "sleeves", 264662, 264663, 0);
		$armors[] = array("Doctor", "Ofab Doctor Gloves", "gloves", 264656, 264657, 0);
		$armors[] = array("Doctor", "Ofab Doctor Helmet", "helmet", 264650, 264651, 0);
		$armors[] = array("Doctor", "Sheffy's Micro Coil", "ring", 267562, 267563, 0);
		$armors[] = array("Enforcer", "Ofab Enforcer Breastplate", "body", 264223, 264224, 0);
		$armors[] = array("Enforcer", "Ofab Enforcer Boots", "boots", 264217, 264218, 0);
		$armors[] = array("Enforcer", "Ofab Enforcer Pants", "pants", 264211, 264212, 0);
		$armors[] = array("Enforcer", "Ofab Enforcer Sleeves", "sleeves", 264229, 264230, 0);
		$armors[] = array("Enforcer", "Ofab Enforcer Gauntlets", "gloves", 264235, 264236, 0);
		$armors[] = array("Enforcer", "Ofab Enforcer Helmet", "helmet", 264241, 264242, 0);
		$armors[] = array("Enforcer", "Band of Bravery", "ring", 267564, 267565, 0);
		$armors[] = array("Engineer", "Ofab Engineer Body", "body", 264596, 264597, 0);
		$armors[] = array("Engineer", "Ofab Engineer Boots", "boots", 264602, 264603, 0);
		$armors[] = array("Engineer", "Ofab Engineer Pants", "pants", 264608, 264609, 0);
		$armors[] = array("Engineer", "Ofab Engineer Sleeves", "sleeves", 264590, 264591, 0);
		$armors[] = array("Engineer", "Ofab Engineer Gloves", "gloves", 264581, 264582, 0);
		$armors[] = array("Engineer", "Ofab Engineer Helmet", "helmet", 264575, 264576, 0);
		$armors[] = array("Engineer", "Rusty's Ring of Bolts", "ring", 267566, 267567, 0);
		$armors[] = array("Fixer", "Ofab Fixer Body Armor", "body", 264485, 264486, 0);
		$armors[] = array("Fixer", "Ofab Fixer Boots", "boots", 264491, 264492, 0);
		$armors[] = array("Fixer", "Ofab Fixer Pants", "pants", 264497, 264498, 0);
		$armors[] = array("Fixer", "Ofab Fixer Sleeves", "sleeves", 264479, 264480, 0);
		$armors[] = array("Fixer", "Ofab Fixer Gloves", "gloves", 264473, 264474, 0);
		$armors[] = array("Fixer", "Ofab Fixer Helmet", "helmet", 264467, 264468, 0);
		$armors[] = array("Fixer", "Fixers' Ring of Breaking", "ring", 267568, 267569, 0);
		$armors[] = array("Keeper", "Ofab Keeper Body Armor", "body", 264632, 264633, 0);
		$armors[] = array("Keeper", "Ofab Keeper Boots", "boots", 264638, 264639, 0);
		$armors[] = array("Keeper", "Ofab Keeper Pants", "pants", 264644, 264645, 0);
		$armors[] = array("Keeper", "Ofab Keeper Sleeves", "sleeves", 264626, 264627, 0);
		$armors[] = array("Keeper", "Ofab Keeper Gloves", "gloves", 264620, 264621, 0);
		$armors[] = array("Keeper", "Ofab Keeper Helmet", "helmet", 264614, 264615, 0);
		$armors[] = array("Keeper", "Knights' Ring of Honour", "ring", 267570, 267571, 0);
		$armors[] = array("Martial Artist", "Ofab Martial Artist Body Armor", "body", 264341, 264342, 0);
		$armors[] = array("Martial Artist", "Ofab Martial Artist Boots", "boots", 264347, 264348, 0);
		$armors[] = array("Martial Artist", "Ofab Martial Artist Pants", "pants", 264353, 264354, 0);
		$armors[] = array("Martial Artist", "Ofab Martial Artist Sleeves", "sleeves", 264335, 264336, 0);
		$armors[] = array("Martial Artist", "Ofab Martial Artist Gloves", "gloves", 264329, 264330, 0);
		$armors[] = array("Martial Artist", "Ofab Martial Artist Helmet", "helmet", 264323, 264324, 0);
		$armors[] = array("Martial Artist", "Engelen's Ring of Damage", "ring", 267572, 267573, 0);
		$armors[] = array("Meta-Physicist", "Ofab Metaphysicist Body Armor", "body", 264377, 264378, 0);
		$armors[] = array("Meta-Physicist", "Ofab Metaphysicist Boots", "boots", 264383, 264384, 0);
		$armors[] = array("Meta-Physicist", "Ofab Metaphysicist Pants", "pants", 264389, 264390, 0);
		$armors[] = array("Meta-Physicist", "Ofab Metaphysicist Sleeves", "sleeves", 264371, 264372, 0);
		$armors[] = array("Meta-Physicist", "Ofab Metaphysicist Gloves", "gloves", 264365, 264366, 0);
		$armors[] = array("Meta-Physicist", "Ofab Metaphysicist Headgear", "helmet", 264359, 264360, 0);
		$armors[] = array("Meta-Physicist", "XtremTech's Ring of Casting", "ring", 268305, 268306, 0);
		$armors[] = array("Nano-Technician", "Ofab Nano Technician Body Armor", "body", 264413, 264414, 0);
		$armors[] = array("Nano-Technician", "Ofab Nano Technician Boots", "boots", 264419, 264420, 0);
		$armors[] = array("Nano-Technician", "Ofab Nano Technician Pants", "pants", 264425, 264426, 0);
		$armors[] = array("Nano-Technician", "Ofab Nano Technician Sleeves", "sleeves", 264407, 264408, 0);
		$armors[] = array("Nano-Technician", "Ofab Nano Technician Gloves", "gloves", 264401, 264402, 0);
		$armors[] = array("Nano-Technician", "Ofab Nano Technician Helmet", "helmet", 264395, 264396, 0);
		$armors[] = array("Nano-Technician", "NTs' Ring of NanoTechnic", "ring", 267574, 267575, 0);
		$armors[] = array("Soldier", "Ofab Soldier Body Armor", "body", 264449, 264450, 0);
		$armors[] = array("Soldier", "Ofab Soldier Boots", "boots", 264455, 264456, 0);
		$armors[] = array("Soldier", "Ofab Soldier Pants", "pants", 264461, 264462, 0);
		$armors[] = array("Soldier", "Ofab Soldier Sleeves", "sleeves", 264443, 264444, 0);
		$armors[] = array("Soldier", "Ofab Soldier Gloves", "gloves", 264437, 264438, 0);
		$armors[] = array("Soldier", "Ofab Soldier Helmet", "helmet", 264431, 264432, 0);
		$armors[] = array("Soldier", "Soldiers' Ring of Focus", "ring", 267578, 267579, 0);
		$armors[] = array("Trader", "Ofab Trader Body Armor", "body", 264269, 264270, 0);
		$armors[] = array("Trader", "Ofab Trader Boots", "boots", 264281, 264282, 0);
		$armors[] = array("Trader", "Ofab Trader Pants", "pants", 264275, 264276, 0);
		$armors[] = array("Trader", "Ofab Trader Sleeves", "sleeves", 264263, 264264, 0);
		$armors[] = array("Trader", "Ofab Trader Gloves", "gloves", 264257, 264258, 0);
		$armors[] = array("Trader", "Ofab Trader Helmet", "helmet", 264251, 264252, 0);
		$armors[] = array("Trader", "Baffle's Ring of Fling", "ring", 267580, 267581, 0);
		$armors[] = array("Shade", "Ofab Shade Body Armor", "body", 264557, 264558, 0);
		$armors[] = array("Shade", "Ofab Shade Boots", "boots", 264563, 264564, 0);
		$armors[] = array("Shade", "Ofab Shade Pants", "pants", 264569, 264570, 0);
		$armors[] = array("Shade", "Ofab Shade Sleeves", "sleeves", 264551, 264552, 0);
		$armors[] = array("Shade", "Ofab Shade Gloves", "gloves", 264545, 264546, 0);
		$armors[] = array("Shade", "Ofab Shade Headgear", "helmet", 264539, 264540, 0);
		$armors[] = array("Shade", "Shades' Ring of Shadows", "ring", 267576, 267577, 0);
		$armors[] = array("Adventurer", "Improved Ofab Adventurer Body Armor", "body", 264190, 264191, 1);
		$armors[] = array("Adventurer", "Improved Ofab Adventurer Boots", "boots", 264188, 264189, 1);
		$armors[] = array("Adventurer", "Improved Ofab Adventurer Pants", "pants", 264186, 264187, 1);
		$armors[] = array("Adventurer", "Improved Ofab Adventurer Sleeves", "sleeves", 264192, 264193, 1);
		$armors[] = array("Adventurer", "Improved Ofab Adventurer Gloves", "gloves", 264194, 264195, 1);
		$armors[] = array("Adventurer", "Improved Ofab Adventurer Helmet", "helmet", 264196, 264197, 1);
		$armors[] = array("Agent", "Improved Ofab Agent Body Armor", "body", 264303, 264304, 1);
		$armors[] = array("Agent", "Improved Ofab Agent Boots", "boots", 264309, 264310, 1);
		$armors[] = array("Agent", "Improved Ofab Agent Pants", "pants", 264315, 264316, 1);
		$armors[] = array("Agent", "Improved Ofab Agent Sleeves", "sleeves", 264297, 264298, 1);
		$armors[] = array("Agent", "Improved Ofab Agent Gloves", "gloves", 264291, 264292, 1);
		$armors[] = array("Agent", "Improved Ofab Agent Helmet", "helmet", 264285, 264286, 1);
		$armors[] = array("Bureaucrat", "Improved Ofab Bureaucrat Vest", "body", 264519, 264520, 1);
		$armors[] = array("Bureaucrat", "Improved Ofab Bureaucrat Boots", "boots", 264525, 264526, 1);
		$armors[] = array("Bureaucrat", "Improved Ofab Bureaucrat Pants", "pants", 264531, 264532, 1);
		$armors[] = array("Bureaucrat", "Improved Ofab Bureaucrat Sleeves", "sleeves", 264513, 264514, 1);
		$armors[] = array("Bureaucrat", "Improved Ofab Bureaucrat Gloves", "gloves", 264507, 264508, 1);
		$armors[] = array("Bureaucrat", "Improved Ofab Bureaucrat Headgear", "helmet", 264501, 264502, 1);
		$armors[] = array("Doctor", "Improved Ofab Doctor Body", "body", 264666, 264667, 1);
		$armors[] = array("Doctor", "Improved Ofab Doctor Boots", "boots", 264672, 264673, 1);
		$armors[] = array("Doctor", "Improved Ofab Doctor Pants", "pants", 264678, 264679, 1);
		$armors[] = array("Doctor", "Improved Ofab Doctor Sleeves", "sleeves", 264660, 264661, 1);
		$armors[] = array("Doctor", "Improved Ofab Doctor Gloves", "gloves", 264654, 264655, 1);
		$armors[] = array("Doctor", "Improved Ofab Doctor Helmet", "helmet", 264648, 264649, 1);
		$armors[] = array("Enforcer", "Improved Ofab Enforcer Breastplate", "body", 264225, 264226, 1);
		$armors[] = array("Enforcer", "Improved Ofab Enforcer Boots", "boots", 264219, 264220, 1);
		$armors[] = array("Enforcer", "Improved Ofab Enforcer Pants", "pants", 264213, 264214, 1);
		$armors[] = array("Enforcer", "Improved Ofab Enforcer Sleeves", "sleeves", 264231, 264232, 1);
		$armors[] = array("Enforcer", "Improved Ofab Enforcer Gauntlets", "gloves", 264237, 264238, 1);
		$armors[] = array("Enforcer", "Improved Ofab Enforcer Helmet", "helmet", 264243, 264244, 1);
		$armors[] = array("Engineer", "Improved Ofab Engineer Body", "body", 264594, 264595, 1);
		$armors[] = array("Engineer", "Improved Ofab Engineer Boots", "boots", 264600, 264601, 1);
		$armors[] = array("Engineer", "Improved Ofab Engineer Pants", "pants", 264606, 264607, 1);
		$armors[] = array("Engineer", "Improved Ofab Engineer Sleeves", "sleeves", 264585, 264586, 1);
		$armors[] = array("Engineer", "Improved Ofab Engineer Gloves", "gloves", 264579, 264580, 1);
		$armors[] = array("Engineer", "Improved Ofab Engineer Helmet", "helmet", 264573, 264574, 1);
		$armors[] = array("Fixer", "Improved Ofab Fixer Body Armor", "body", 264483, 264484, 1);
		$armors[] = array("Fixer", "Improved Ofab Fixer Boots", "boots", 264489, 264490, 1);
		$armors[] = array("Fixer", "Improved Ofab Fixer Pants", "pants", 264495, 264496, 1);
		$armors[] = array("Fixer", "Improved Ofab Fixer Sleeves", "sleeves", 264477, 264478, 1);
		$armors[] = array("Fixer", "Improved Ofab Fixer Gloves", "gloves", 264471, 264472, 1);
		$armors[] = array("Fixer", "Improved Ofab Fixer Helmet", "helmet", 264465, 264466, 1);
		$armors[] = array("Keeper", "Improved Ofab Keeper Body Armor", "body", 264630, 264631, 1);
		$armors[] = array("Keeper", "Improved Ofab Keeper Boots", "boots", 264636, 264637, 1);
		$armors[] = array("Keeper", "Improved Ofab Keeper Pants", "pants", 264642, 264643, 1);
		$armors[] = array("Keeper", "Improved Ofab Keeper Sleeves", "sleeves", 264624, 264625, 1);
		$armors[] = array("Keeper", "Improved Ofab Keeper Gloves", "gloves", 264618, 264619, 1);
		$armors[] = array("Keeper", "Improved Ofab Keeper Helmet", "helmet", 264612, 264613, 1);
		$armors[] = array("Martial Artist", "Improved Ofab Martial Artist Body Armor", "body", 264339, 264340, 1);
		$armors[] = array("Martial Artist", "Improved Ofab Martial Artist Boots", "boots", 264345, 264346,1);
		$armors[] = array("Martial Artist", "Improved Ofab Martial Artist Pants", "pants", 264351, 264352,1);
		$armors[] = array("Martial Artist", "Improved Ofab Martial Artist Sleeves", "sleeves", 264333, 264334, 1);
		$armors[] = array("Martial Artist", "Improved Ofab Martial Artist Gloves", "gloves", 264327, 264328, 1);
		$armors[] = array("Martial Artist", "Improved Ofab Martial Artist Helmet", "helmet", 264321, 264322, 1);
		$armors[] = array("Meta-Physicist", "Improved Ofab Metaphysicist Body Armor", "body", 264375, 264376, 1);
		$armors[] = array("Meta-Physicist", "Improved Ofab Metaphysicist Boots", "boots", 264381, 264382, 1);
		$armors[] = array("Meta-Physicist", "Improved Ofab Metaphysicist Pants", "pants", 264387, 264388, 1);
		$armors[] = array("Meta-Physicist", "Improved Ofab Metaphysicist Sleeves", "sleeves", 264369, 264370, 1);
		$armors[] = array("Meta-Physicist", "Improved Ofab Metaphysicist Gloves", "gloves", 264363, 264364,1);
		$armors[] = array("Meta-Physicist", "Improved Ofab Metaphysicist Headgear", "helmet", 264357, 264358,1);
		$armors[] = array("Nano-Technician", "Improved Ofab Nano Technician Body Armor", "body", 264411, 264412, 1);
		$armors[] = array("Nano-Technician", "Improved Ofab Nano Technician Boots", "boots", 264417, 264418, 1);
		$armors[] = array("Nano-Technician", "Improved Ofab Nano Technician Pants", "pants", 264423, 264424, 1);
		$armors[] = array("Nano-Technician", "Improved Ofab Nano Technician Sleeves", "sleeves", 264405, 264406, 1);
		$armors[] = array("Nano-Technician", "Improved Ofab Nano Technician Gloves", "gloves", 264399, 264400, 1);
		$armors[] = array("Nano-Technician", "Improved Ofab Nano Technician Helmet", "helmet", 264393, 264394, 1);
		$armors[] = array("Soldier", "Improved Ofab Soldier Body Armor", "body", 264447, 264448, 1);
		$armors[] = array("Soldier", "Improved Ofab Soldier Boots", "boots", 264453, 264454, 1);
		$armors[] = array("Soldier", "Improved Ofab Soldier Pants", "pants", 264459, 264460, 1);
		$armors[] = array("Soldier", "Improved Ofab Soldier Sleeves", "sleeves", 264441, 264442, 1);
		$armors[] = array("Soldier", "Improved Ofab Soldier Gloves", "gloves", 264435, 264436, 1);
		$armors[] = array("Soldier", "Improved Ofab Soldier Helmet", "helmet", 264429, 264430, 1);
		$armors[] = array("Trader", "Improved Ofab Trader Body Armor", "body", 264267, 264268, 1);
		$armors[] = array("Trader", "Improved Ofab Trader Boots", "boots", 264279, 264280, 1);
		$armors[] = array("Trader", "Improved Ofab Trader Pants", "pants", 264273, 264274, 1);
		$armors[] = array("Trader", "Improved Ofab Trader Sleeves", "sleeves", 264261, 264262, 1);
		$armors[] = array("Trader", "Improved Ofab Trader Gloves", "gloves", 264255, 264256, 1);
		$armors[] = array("Trader", "Improved Ofab Trader Helmet", "helmet", 264249, 264250, 1);
		$armors[] = array("Shade", "Improved Ofab Shade Body Armor", "body", 264555, 264556, 1);
		$armors[] = array("Shade", "Improved Ofab Shade Boots", "boots", 264561, 264562, 1);
		$armors[] = array("Shade", "Improved Ofab Shade Pants", "pants", 264567, 264568, 1);
		$armors[] = array("Shade", "Improved Ofab Shade Sleeves", "sleeves", 264549, 264550, 1);
		$armors[] = array("Shade", "Improved Ofab Shade Gloves", "gloves", 264543, 264544, 1);
		$armors[] = array("Shade", "Improved Ofab Shade Headgear", "helmet", 264537, 264538, 1);
		$armors[] = array("Adventurer", "Penultimate Ofab Adventurer Body Armor", "body", 264178, 264179, 2);
		$armors[] = array("Adventurer", "Penultimate Ofab Adventurer Boots", "boots", 264176, 264177, 2);
		$armors[] = array("Adventurer", "Penultimate Ofab Adventurer Pants", "pants", 264174, 264175, 2);
		$armors[] = array("Adventurer", "Penultimate Ofab Adventurer Sleeves", "sleeves", 264180, 264181, 2);
		$armors[] = array("Adventurer", "Penultimate Ofab Adventurer Gloves", "gloves", 264182, 264183, 2);
		$armors[] = array("Adventurer", "Penultimate Ofab Adventurer Helmet", "helmet", 264184, 264185, 2);
		$armors[] = array("Agent", "Penultimate Ofab Agent Body Armor", "body", 264301, 264302, 2);
		$armors[] = array("Agent", "Penultimate Ofab Agent Boots", "boots", 264307, 264308, 2);
		$armors[] = array("Agent", "Penultimate Ofab Agent Pants", "pants", 264313, 264314, 2);
		$armors[] = array("Agent", "Penultimate Ofab Agent Sleeves", "sleeves", 264295, 264296, 2);
		$armors[] = array("Agent", "Penultimate Ofab Agent Gloves", "gloves", 264289, 264290, 2);
		$armors[] = array("Agent", "Penultimate Ofab Agent Helmet", "helmet", 264283, 264284, 2);
		$armors[] = array("Bureaucrat", "Penultimate Ofab Bureaucrat Vest", "body", 264517, 264518, 2);
		$armors[] = array("Bureaucrat", "Penultimate Ofab Bureaucrat Boots", "boots", 264523, 264524, 2);
		$armors[] = array("Bureaucrat", "Penultimate Ofab Bureaucrat Pants", "pants", 264529, 264530, 2);
		$armors[] = array("Bureaucrat", "Penultimate Ofab Bureaucrat Sleeves", "sleeves", 264511, 264512, 2);
		$armors[] = array("Bureaucrat", "Penultimate Ofab Bureaucrat Gloves", "gloves", 264505, 264506, 2);
		$armors[] = array("Bureaucrat", "Penultimate Ofab Bureaucrat Headgear", "helmet", 264499, 264500, 2);
		$armors[] = array("Doctor", "Penultimate Ofab Doctor Body", "body", 264664, 264665, 2);
		$armors[] = array("Doctor", "Penultimate Ofab Doctor Boots", "boots", 264670, 264671, 2);
		$armors[] = array("Doctor", "Penultimate Ofab Doctor Pants", "pants", 264676, 264677, 2);
		$armors[] = array("Doctor", "Penultimate Ofab Doctor Sleeves", "sleeves", 264658, 264659, 2);
		$armors[] = array("Doctor", "Penultimate Ofab Doctor Gloves", "gloves", 264652, 264653, 2);
		$armors[] = array("Doctor", "Penultimate Ofab Doctor Helmet", "helmet", 264646, 264647, 2);
		$armors[] = array("Enforcer", "Penultimate Ofab Enforcer Breastplate", "body", 264227, 264228, 2);
		$armors[] = array("Enforcer", "Penultimate Ofab Enforcer Boots", "boots", 264221, 264222, 2);
		$armors[] = array("Enforcer", "Penultimate Ofab Enforcer Pants", "pants", 264215, 264216, 2);
		$armors[] = array("Enforcer", "Penultimate Ofab Enforcer Sleeves", "sleeves", 264233, 264234, 2);
		$armors[] = array("Enforcer", "Penultimate Ofab Enforcer Gauntlets", "gloves", 264239, 264240, 2);
		$armors[] = array("Enforcer", "Penultimate Ofab Enforcer Helmet", "helmet", 264245, 264246, 2);
		$armors[] = array("Engineer", "Penultimate Ofab Engineer Body", "body", 264592, 264593, 2);
		$armors[] = array("Engineer", "Penultimate Ofab Engineer Boots", "boots", 264598, 264599, 2);
		$armors[] = array("Engineer", "Penultimate Ofab Engineer Pants", "pants", 264604, 264605, 2);
		$armors[] = array("Engineer", "Penultimate Ofab Engineer Sleeves", "sleeves", 264583, 264584, 2);
		$armors[] = array("Engineer", "Penultimate Ofab Engineer Gloves", "gloves", 264577, 264578, 2);
		$armors[] = array("Engineer", "Penultimate Ofab Engineer Helmet", "helmet", 264571, 264572, 2);
		$armors[] = array("Fixer", "Penultimate Ofab Fixer Body Armor", "body", 264481, 264482, 2);
		$armors[] = array("Fixer", "Penultimate Ofab Fixer Boots", "boots", 264487, 264488, 2);
		$armors[] = array("Fixer", "Penultimate Ofab Fixer Pants", "pants", 264493, 264494, 2);
		$armors[] = array("Fixer", "Penultimate Ofab Fixer Sleeves", "sleeves", 264475, 264476, 2);
		$armors[] = array("Fixer", "Penultimate Ofab Fixer Gloves", "gloves", 264469, 264470, 2);
		$armors[] = array("Fixer", "Penultimate Ofab Fixer Helmet", "helmet", 264463, 264464, 2);
		$armors[] = array("Keeper", "Penultimate Ofab Keeper Body Armor", "body", 264628, 264629, 2);
		$armors[] = array("Keeper", "Penultimate Ofab Keeper Boots", "boots", 264634, 264635, 2);
		$armors[] = array("Keeper", "Penultimate Ofab Keeper Pants", "pants", 264640, 264641, 2);
		$armors[] = array("Keeper", "Penultimate Ofab Keeper Sleeves", "sleeves", 264622, 264623, 2);
		$armors[] = array("Keeper", "Penultimate Ofab Keeper Gloves", "gloves", 264616, 264617, 2);
		$armors[] = array("Keeper", "Penultimate Ofab Keeper Helmet", "helmet", 264610, 264611, 2);
		$armors[] = array("Martial Artist", "Penultimate Ofab Martial Artist Body Armor", "body", 264337, 264338, 2);
		$armors[] = array("Martial Artist", "Penultimate Ofab Martial Artist Boots", "boots", 264343, 264344, 2);
		$armors[] = array("Martial Artist", "Penultimate Ofab Martial Artist Pants", "pants", 264349, 264350, 2);
		$armors[] = array("Martial Artist", "Penultimate Ofab Martial Artist Sleeves", "sleeves", 264331, 264332, 2);
		$armors[] = array("Martial Artist", "Penultimate Ofab Martial Artist Gloves", "gloves", 264325, 264326, 2);
		$armors[] = array("Martial Artist", "Penultimate Ofab Martial Artist Helmet", "helmet", 264319, 264320, 2);
		$armors[] = array("Meta-Physicist", "Penultimate Ofab Metaphysicist Body Armor", "body", 264373, 264374, 2);
		$armors[] = array("Meta-Physicist", "Penultimate Ofab Metaphysicist Boots", "boots", 264379, 264380, 2);
		$armors[] = array("Meta-Physicist", "Penultimate Ofab Metaphysicist Pants", "pants", 264385, 264386, 2);
		$armors[] = array("Meta-Physicist", "Penultimate Ofab Metaphysicist Sleeves", "sleeves", 264367, 264368, 2);
		$armors[] = array("Meta-Physicist", "Penultimate Ofab Metaphysicist Gloves", "gloves", 264361, 264362, 2);
		$armors[] = array("Meta-Physicist", "Penultimate Ofab Metaphysicist Headgear", "helmet", 264355, 264356, 2);
		$armors[] = array("Nano-Technician", "Penultimate Ofab Nano Technician Body Armor", "body", 264409, 264410, 2);
		$armors[] = array("Nano-Technician", "Penultimate Ofab Nano Technician Boots", "boots", 264415, 264416, 2);
		$armors[] = array("Nano-Technician", "Penultimate Ofab Nano Technician Pants", "pants", 264421, 264422, 2);
		$armors[] = array("Nano-Technician", "Penultimate Ofab Nano Technician Sleeves", "sleeves", 264403, 264404, 2);
		$armors[] = array("Nano-Technician", "Penultimate Ofab Nano Technician Gloves", "gloves", 264397, 264398, 2);
		$armors[] = array("Nano-Technician", "Penultimate Ofab Nano Technician Helmet", "helmet", 264391, 264392, 2);
		$armors[] = array("Soldier", "Penultimate Ofab Soldier Body Armor", "body", 264445, 264446, 2);
		$armors[] = array("Soldier", "Penultimate Ofab Soldier Boots", "boots", 264451, 264452, 2);
		$armors[] = array("Soldier", "Penultimate Ofab Soldier Pants", "pants", 264457, 264458, 2);
		$armors[] = array("Soldier", "Penultimate Ofab Soldier Sleeves", "sleeves", 264439, 264440, 2);
		$armors[] = array("Soldier", "Penultimate Ofab Soldier Gloves", "gloves", 264433, 264434, 2);
		$armors[] = array("Soldier", "Penultimate Ofab Soldier Helmet", "helmet", 264427, 264428, 2);
		$armors[] = array("Trader", "Penultimate Ofab Trader Body Armor", "body", 264265, 264266, 2);
		$armors[] = array("Trader", "Penultimate Ofab Trader Boots", "boots", 264277, 264278, 2);
		$armors[] = array("Trader", "Penultimate Ofab Trader Pants", "pants", 264271, 264272, 2);
		$armors[] = array("Trader", "Penultimate Ofab Trader Sleeves", "sleeves", 264259, 264260, 2);
		$armors[] = array("Trader", "Penultimate Ofab Trader Gloves", "gloves", 264253, 264254, 2);
		$armors[] = array("Trader", "Penultimate Ofab Trader Helmet", "helmet", 264247, 264248, 2);
		$armors[] = array("Shade", "Penultimate Ofab Shade Body Armor", "body", 264553, 264554, 2);
		$armors[] = array("Shade", "Penultimate Ofab Shade Boots", "boots", 264559, 264560, 2);
		$armors[] = array("Shade", "Penultimate Ofab Shade Pants", "pants", 264565, 264566, 2);
		$armors[] = array("Shade", "Penultimate Ofab Shade Sleeves", "sleeves", 264547, 264548, 2);
		$armors[] = array("Shade", "Penultimate Ofab Shade Gloves", "gloves", 264541, 264542, 2);
		$armors[] = array("Shade", "Penultimate Ofab Shade Headgear", "helmet", 264535, 264536, 2);
		$armors[] = array("Adventurer", "Special Edition Ofab Adventurer Helmet", "specialhelmet", 267353, 267354, 3);
		$armors[] = array("Agent", "Special Edition Ofab Agent Helmet", "specialhelmet", 267356, 267357, 3);
		$armors[] = array("Bureaucrat", "Special Edition Ofab Bureaucrat Headgear", "specialhelmet", 267363, 267364, 3);
		$armors[] = array("Doctor", "Special Edition Ofab Doctor Helmet", "specialhelmet", 267351, 267352, 3);
		$armors[] = array("Enforcer", "Special Edition Ofab Enforcer Helmet", "specialhelmet", 267365, 267366, 3);
		$armors[] = array("Engineer", "Special Edition Ofab Engineer Helmet", "specialhelmet", 267369, 267370, 3);
		$armors[] = array("Fixer", "Special Edition Ofab Fixer Helmet", "specialhelmet", 267367, 267368, 3);
		$armors[] = array("Keeper", "Special Edition Ofab Keeper Helmet", "specialhelmet", 267375, 267376, 3);
		$armors[] = array("Martial Artist", "Special Edition Ofab Martial Artist Helmet", "specialhelmet", 267371, 267372, 3);
		$armors[] = array("Meta-Physicist", "Special Edition Ofab Metaphysicist Headgear", "specialhelmet", 267373, 267374, 3);
		$armors[] = array("Nano-Technician", "Special Edition Ofab Nano Technician Helmet", "specialhelmet", 267383, 267384, 3);
		$armors[] = array("Soldier", "Special Edition Ofab Soldier Helmet", "specialhelmet", 267379, 267380, 3);
		$armors[] = array("Trader", "Special Edition Ofab Trader Helmet", "specialhelmet", 267381, 267382, 3);
		$armors[] = array("Shade", "Special Edition Ofab Shade Headgear", "specialhelmet", 267377, 267378, 3);
		$armors[] = array("Adventurer", "OFAB Adventurer Protective Gear", "back", 267931, 267931, 3);
		$armors[] = array("Agent", "OFAB Agent Protective Gear", "back", 267509, 267509, 3);
		$armors[] = array("Bureaucrat", "OFAB Bureaucrat Protective Gear", "back", 267932, 267932, 3);
		$armors[] = array("Doctor", "OFAB Doctor Protective Gear", "back", 267933, 267933, 3);
		$armors[] = array("Enforcer", "OFAB Enforcer Protective Gear", "back", 267510, 267510, 3);
		$armors[] = array("Engineer", "OFAB Engineer Protective Gear", "back", 267936, 267936, 3);
		$armors[] = array("Fixer", "OFAB Fixer Protective Gear", "back", 267512, 267512, 3);
		$armors[] = array("Keeper", "OFAB Keeper Protective Gear", "back", 267513, 267513, 3);
		$armors[] = array("Martial Artist", "OFAB Martial Artist Protective Gear", "back", 267937, 267937, 3);
		$armors[] = array("Meta-Physicist", "OFAB Meta-Physicist Protective Gear", "back", 267515, 267515, 3);
		$armors[] = array("Nano-Technician", "OFAB Nano Technician Protective Gear", "back", 267938, 267938, 3);
		$armors[] = array("Soldier", "OFAB Soldier Protective Gear", "back", 267939, 267939, 3);
		$armors[] = array("Trader", "OFAB Trader Protective Gear", "back", 267935, 267935, 3);
		$armors[] = array("Shade", "OFAB Shade Protective Gear", "back", 267934, 267934, 3);
		$armors[] = array("Adventurer", "OFAB Adventurer Shoulder Wear", "shoulder", 301693, 301693, 3);
		$armors[] = array("Agent", "OFAB Agent Shoulder Wear", "shoulder", 301698, 301698, 3);
		$armors[] = array("Bureaucrat", "OFAB Bureaucrat Shoulder Wear", "shoulder", 301696, 301696, 3);
		$armors[] = array("Doctor", "OFAB Doctor Shoulder Wear", "shoulder", 301697, 301697, 3);
		$armors[] = array("Enforcer", "OFAB Enforcer Shoulder Wear", "shoulder", 267511, 267511, 3);
		$armors[] = array("Engineer", "OFAB Engineer Shoulder Wear", "shoulder", 268003, 268003, 3);
		$armors[] = array("Fixer", "OFAB Fixer Shoulder Wear", "shoulder", 301694, 301694, 3);
		$armors[] = array("Keeper", "OFAB Keeper Shoulder Wear", "shoulder", 267514, 267514, 3);
		$armors[] = array("Martial Artist", "OFAB Martial Artist Shoulder Wear", "shoulder", 268004, 268004, 3);
		$armors[] = array("Meta-Physicist", "OFAB Metaphysicist Shoulder Wear", "shoulder", 301695, 301695, 3);
		$armors[] = array("Nano-Technician", "OFAB Nano Technician Shoulder Wear", "shoulder", 268081, 268081, 3);
		$armors[] = array("Soldier", "OFAB Soldier Shoulder Wear", "shoulder", 268186, 268186, 3);
		$armors[] = array("Trader", "OFAB Trader Shoulder Wear", "shoulder", 268006, 268006, 3);
		$armors[] = array("Shade", "OFAB Shade Shoulder Wear", "shoulder", 268005, 268005, 3);
		// slot ql price
		$costs[] = array("body", 25, 78);
		$costs[] = array("boots", 25, 73);
		$costs[] = array("pants", 25, 73);
		$costs[] = array("sleeves", 25, 49);
		$costs[] = array("gloves", 25, 49);
		$costs[] = array("helmet", 25, 78);
		$costs[] = array("ring", 25, 253);
		$costs[] = array("body", 50, 323);
		$costs[] = array("boots", 50, 302);
		$costs[] = array("pants", 50, 302);
		$costs[] = array("sleeves", 50, 202);
		$costs[] = array("gloves", 50, 202);
		$costs[] = array("helmet", 50, 323);
		$costs[] = array("ring", 50, 741);
		$costs[] = array("body", 75, 736);
		$costs[] = array("boots", 75, 690);
		$costs[] = array("pants", 75, 690);
		$costs[] = array("sleeves", 75, 460);
		$costs[] = array("gloves", 75, 460);
		$costs[] = array("helmet", 75, 736);
		$costs[] = array("ring", 75, 1563);
		$costs[] = array("body", 100, 1316);
		$costs[] = array("boots", 100, 1234);
		$costs[] = array("pants", 100, 1234);
		$costs[] = array("sleeves", 100, 823);
		$costs[] = array("gloves", 100, 823);
		$costs[] = array("helmet", 100, 1316);
		$costs[] = array("ring", 100, 2719);
		$costs[] = array("body", 125, 2064);
		$costs[] = array("boots", 125, 1935);
		$costs[] = array("pants", 125, 1935);
		$costs[] = array("sleeves", 125, 1290);
		$costs[] = array("gloves", 125, 1290);
		$costs[] = array("helmet", 125, 2064);
		$costs[] = array("ring", 125, 4210);
		$costs[] = array("body", 150, 2980);
		$costs[] = array("boots", 150, 2794);
		$costs[] = array("pants", 150, 2794);
		$costs[] = array("sleeves", 150, 1863);
		$costs[] = array("gloves", 150, 1863);
		$costs[] = array("helmet", 150, 2980);
		$costs[] = array("ring", 150, 6035);
		$costs[] = array("body", 175, 4064);
		$costs[] = array("boots", 175, 3810);
		$costs[] = array("pants", 175, 3810);
		$costs[] = array("sleeves", 175, 2540);
		$costs[] = array("gloves", 175, 2540);
		$costs[] = array("helmet", 175, 4064);
		$costs[] = array("ring", 175, 8193);
		$costs[] = array("body", 200, 5316);
		$costs[] = array("boots", 200, 4984);
		$costs[] = array("pants", 200, 4984);
		$costs[] = array("sleeves", 200, 3322);
		$costs[] = array("gloves", 200, 3322);
		$costs[] = array("helmet", 200, 5316);
		$costs[] = array("ring", 200, 10687);
		$costs[] = array("body", 225, 6735);
		$costs[] = array("boots", 225, 6313);
		$costs[] = array("pants", 225, 6313);
		$costs[] = array("sleeves", 225, 4209);
		$costs[] = array("gloves", 225, 4209);
		$costs[] = array("helmet", 225, 6735);
		$costs[] = array("ring", 225, 13513);
		$costs[] = array("body", 250, 8321);
		$costs[] = array("boots", 250, 7802);
		$costs[] = array("pants", 250, 7802);
		$costs[] = array("sleeves", 250, 5201);
		$costs[] = array("gloves", 250, 5201);
		$costs[] = array("helmet", 250, 8321);
		$costs[] = array("ring", 250, 16674);
		$costs[] = array("body", 275, 10077);
		$costs[] = array("boots", 275, 9446);
		$costs[] = array("pants", 275, 9446);
		$costs[] = array("sleeves", 275, 6298);
		$costs[] = array("gloves", 275, 6298);
		$costs[] = array("helmet", 275, 10077);
		$costs[] = array("ring", 275, 20171);
		$costs[] = array("body", 300, 12000);
		$costs[] = array("boots", 300, 11250);
		$costs[] = array("pants", 300, 11250);
		$costs[] = array("sleeves", 300, 7500);
		$costs[] = array("gloves", 300, 7500);
		$costs[] = array("helmet", 300, 12000);
		$costs[] = array("ring", 300, 24000);
		$costs[] = array("specialhelmet", 300, 30000);
		$costs[] = array("back", 300, 30000);
		$costs[] = array("shoulder", 300, 12000);
		// type prof
		$types[] = array(64, "Doctor");
		$types[] = array(64, "Engineer");
		$types[] = array(64, "Keeper");
		$types[] = array(64, "Meta-Physicist");
		$types[] = array(295, "Adventurer");
		$types[] = array(295, "Enforcer");
		$types[] = array(295, "Martial Artist");
		$types[] = array(295, "Soldier");
		$types[] = array(468, "Bureaucrat");
		$types[] = array(468, "Nano-Technician");
		$types[] = array(468, "Trader");
		$types[] = array(935, "Agent");
		$types[] = array(935, "Fixer");
		$types[] = array(935, "Shade");		
		
		$blob = "No armor of that QL was found for this profession. Must be a multiple of 25.";
		$upg = "";
		foreach($types AS $type) {
			if($type[1]==$profname) {
				$blob = "";
				$upg = "(type ".$type[0]." upgrades of QL ".round(0.8*$ql)."+)";
				foreach($armors as $armor) {
					if($armor[0]==$profname) {
						foreach($costs AS $cost) {
							if($cost[0]==$armor[2]&&$ql==$cost[1]) {
								if(strpos($armor[1],"Improved")!==false) $bonus = " + 1 upgrade";
								elseif(strpos($armor[1],"Penultimate")!==false) $bonus = " + 2 upgrades";
								else $bonus = "";
								$blob .= "<a href='itemref://".$armor[3]."/".$armor[4]."/".$ql."'>".$armor[1]."</a> = ##green##".$cost[2]."##end## VP".$bonus."\n";
							}
						}
					}
				}
			}
		}
		return $this->bot->send_output($name, "QL ".$ql." ".$profname." ".$upg." armors : ".$this->bot->core("tools")->make_blob("click to view", $blob), $origin);
	}

	function ofab_weapon($name, $origin, $ql)
	{
		// weapons + costs
		$weaps[] = array('Mongoose',18,'1HE');
		$weaps[] = array('Viper',18,'Piercing');
		$weaps[] = array('Wolf',18,'2HE');
		$weaps[] = array('Bear',34,'2BH');
		$weaps[] = array('Panther',34,'1HB');
		$weaps[] = array('Cobra',687,'Rifle');
		$weaps[] = array('Shark',687,'AssaultRifle');
		$weaps[] = array('Silverback',687,'Shotgun');
		$weaps[] = array('Hawk',812,'SMG');
		$weaps[] = array('Peregrine',812,'Pistol');
		$weaps[] = array('Tiger',812,'Bow');
		$costs[] = array(25, 117);
		$costs[] = array(50, 488);
		$costs[] = array(75, 1110);
		$costs[] = array(100, 1988);
		$costs[] = array(125, 2365);
		$costs[] = array(150, 3497);
		$costs[] = array(175, 5384);
		$costs[] = array(200, 7987);
		$costs[] = array(225, 8617);
		$costs[] = array(250, 10509);
		$costs[] = array(275, 13665);
		$costs[] = array(300, 18000);
		
		$blob = "No weapon of that QL was found. Must be a multiple of 25.";
		$price = "";
		$upg = "";
		foreach($costs AS $cost) {
			if($cost[0]==$ql) {
				$blob = "";
				$upg = "(upgrades of QL ".round(0.8*$ql)."+)";
				$price = "(".$cost[1]." VP)";
				foreach($weaps as $weap) {
					$blob .= "<i>".$weap[0]."</i> is a ##red##".$weap[2]."##end## weapon upgradable with type ##green##".$weap[1]."##end##\n\n";
				}
			}		
		}		
		return $this->bot->send_output($name, "QL ".$ql." ".$upg." weapons ".$price." : ".$this->bot->core("tools")->make_blob("click to view", $blob), $origin);
	}
	

}
?>