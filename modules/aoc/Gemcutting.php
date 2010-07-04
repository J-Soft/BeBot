<?php
/*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2010 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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
*/
/*
* Gemcutting helper module v1.0, By Noer
* This module helps people with identifying gems and how to cut gems.
* Further developed by Varka.
*
*
*/
$Gemcut = new Gemcut($bot);
class Gemcut extends BaseActiveModule
{
	var $server = 'http://aocdb.lunevo.net/';
	var $gem_types = array();
	var $gem_array = array();
	var $tier_info = array();

	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->register_command('all', 'gem', 'GUEST');
		$this->register_command('all', 'gems', 'GUEST');
		$this->register_command('all', 'gemcut', 'GUEST');
		$this->register_command('all', 'geminfo', 'GUEST');
		$this->bot->core("colors")->define_scheme("gemcut", "highlight", "yellow");
		$this->bot->core("colors")->define_scheme("gemcut", "normal", "white");
		$this->bot->core("colors")->define_scheme("gemcut", "info", "lightgreen");
		$this->help['description'] = 'This module helps people with identifying gems and how to cut gems.';
		$this->help['command']['gem <itemref>'] = "Displays what bonuses an uncut gem will give and how to cut it.";
		$this->help['command']['gemcut <tier>'] = "Displays a list of gems for the specified tier.";
		// Do this in the constructor so run-time speed is improved.
		// This array is the most easy to edit. It is then used to build another structure for lookups
		//         Colour                        // Tier 1       // Tier 2       // Tier 3     // Tier 4       // Tier 5     // Tier 6
		$this->gem_types["black"] = array(array("Obsidian" , "Onyx" , "Jet" , "Black Jasper" , "Nightstar" , "Black Diamond"));
		$this->gem_types["blue"] = array(array("Azurite" , "Lapis Lazuli" , "Turqoise" , "Aquamarine" , "Sapphire" , "Star Saphire"));
		$this->gem_types["green"] = array(array("Chrysoprase" , "Malachite" , "Peridot" , "Sphene" , "Jade" , "Emerald"));
		$this->gem_types["orange"] = array(array("Carnelian" , "Tiger Eye" , "Chalcedony" , "Sunstone" , "Fire Agate" , "Padpaasahsa"));
		$this->gem_types["purple"] = array(array("Rose Quartz" , "Iolite" , "Amethyst" , "Duskstone" , "Royal Azel" , "Tyrian Sapphire"));
		$this->gem_types["red"] = array(array("Spinel" , "Jasper" , "Garnet" , "Blood Opal" , "Ruby" , "Star Ruby"));
		$this->gem_types["white"] = array(array("Quartz" , "Zircon" , "Moonstone" , "Achroite" , "White Opal" , "Diamond"));
		$this->gem_types["yellow"] = array(array("Citrine" , "Chrysoberyl" , "Sagenite" , "Topaz" , "Heliodor" , "Golden Beryl"));
		// Tier info
		$this->tier_info[] = array("40-49" , "Field of the Dead, Noble District");
		$this->tier_info[] = array("50-59" , "Eiglophian Mountains");
		$this->tier_info[] = array("60-69" , "Thunder River, Atzel's Approach");
		$this->tier_info[] = array("70-74" , "Atzel's Approach, Keshatta");
		$this->tier_info[] = array("75-79" , "Keshatta");
		$this->tier_info[] = array("80+" , "Keshatta (listed as lvl 75)");
		// Now we want a list of Prefixes and effects: 1H = Rhombic/Trillion
		// We need the name, the three cuts (1H/2H/Arm), the rarity (Common, Rare, Both), Min/Max Tiers and the effect
		//   <name>           :          0/1 : 0/1 : 0/1       :         0/1/2         : 1-6 : 1-6  =>  <effect>
		$this->gem_types["black"][] = array("Baneful:" . "1:1:1:" . "2:" . "1:6" => "+X Unholy Damage (Magic)" , "Corruptive:" . "0:0:1:" . "1:" . "1:6" => "On-hit Unholy Damage (Proc)" , "Defiling:" . "1:1:0:" . "2:" . "1:6" => "Unholy Damage (Proc)" , "Envenoming:" . "0:0:1:" . "1:" . "1:6" => "On-hit Poison Damage (Proc)" , "Malefic:" . "1:1:0:" . "2:" . "1:6" => "+X Unholy Damage (Melee)" , "Noxious:" . "1:1:0:" . "2:" . "1:6" => "+X Poison Damage (Melee)" , "Umbral:" . "0:0:1:" . "2:" . "1:6" => "+X Hiding" , "Unhallowed:" . "0:0:1:" . "2:" . "1:6" => "+X% Holy Invulnerability" , "Venemous:" . "1:1:0:" . "2:" . "1:6" => "Poison Damage (Proc)");
		$this->gem_types["blue"][] = array("Draining:" . "1:1:0:" . "2:" . "1:6" => "+X% Tap Mana % (Magic)" , "Enlightening:" . "1:1:1:" . "2:" . "1:6" => "+X Intelligence" , "Fatiguing:" . "1:1:0:" . "2:" . "1:6" => "+X% Tap Stamina % (Magic)" , "Gelid:" . "1:1:0:" . "2:" . "1:6" => "Cold Damage (Proc)" , "Glacial:" . "1:1:1:" . "2:" . "1:6" => "+X Cold Damage (Magic)" , "Icy:" . "0:0:1:" . "1:" . "1:6" => "On-hit Cold Damage (Proc)" , "Imbued:" . "0:0:1:" . "2:" . "1:6" => "+X Max Mana" , "Leeching:" . "1:1:0:" . "2:" . "1:6" => "+X% Tap Health % (Magic)" , "Negating:" . "0:0:1:" . "2:" . "1:6" => "+X% Immunity" , "Quenching:" . "0:0:1:" . "2:" . "1:6" => "+X% Fire Invulnerability" , "Replenishing:" . "0:0:1:" . "2:" . "1:6" => "+X Natural Mana Region" , "Repletive:" . "0:0:1:" . "2:" . "1:6" => "+X Non-Combat Mana Regen" , "Sagacious:" . "1:1:1:" . "2:" . "1:6" => "+X Wisdom" , "Stupefying:" . "1:1:0:" . "2:" . "1:6" => "+X% Tap Mana");
		$this->gem_types["green"][] = array("Brutal:" . "1:1:0:" . "2:" . "1:6" => "+X% Fatality" , "Deft:" . "1:1:1:" . "2:" . "1:6" => "+X Dexterity" , "Dense:" . "0:0:1:" . "0:" . "1:6" => "+X% Piercing Invulnerability" , "Enfeebling:" . "1:1:0:" . "2:" . "1:6" => "+X% Tap Stamina" , "Evasive:" . "0:0:1:" . "2:" . "1:6" => "+X% Evade" , "Frenzied:" . "1:0:1:" . "2:" . "1:6" => "+X% Offhand Chance" , "Invigorating:" . "0:0:1:" . "2:" . "1:6" => "+X Max Stamina" , "Panoptic:" . "1:1:0:" . "2:" . "1:6" => "+X Damage (Melee)" , "Poised:" . "0:0:1:" . "2:" . "1:6" => "+X Bow Damage" , "Puncturing:" . "1:1:0:" . "2:" . "1:6" => "+X Piercing Damage (Melee)" , "Rejuvenating:" . "0:0:1:" . "2:" . "1:6" => "+X Natural Stamina Regen" , "Retortive:" . "0:0:1:" . "1:" . "1:6" => "On-hit Slashing Damage (Proc)" , "Ripping:" . "1:1:1:" . "2:" . "1:6" => "On-hit Crushing Damage (Proc)" , "Sacrosanct:" . "0:0:1:" . "2:" . "1:6" => "+X% Unholy Invulnerability" , "Salubrious:" . "0:0:1:" . "2:" . "1:6" => "+X Non-Combat Stamina Regen" , "Steady:" . "0:0:1:" . "2:" . "1:6" => "+X Crossbow Damage" , "Tenacious:" . "0:0:1:" . "0:" . "1:6" => "+X% Slashing Invulnerability" , "Thrusting:" . "0:0:1:" . "2:" . "1:6" => "+X Dagger Damage (Melee)" , "Unyielding:" . "0:0:1:" . "0:" . "1:6" => "+X% Crushing Invulnerability");
		$this->gem_types["orange"][] = array("Algid:" . "0:0:1:" . "2:" . "1:6" => "+X% Cold Invulnerability" , "Flaring:" . "1:1:0:" . "2:" . "1:6" => "Fire Damage (Proc)" , "Igneous:" . "1:1:1:" . "2:" . "1:6" => "+X Fire Damage (Magic)" , "Scalding:" . "0:0:1:" . "2:" . "1:6" => "On-hit Fire Damage (Proc)" , "Searing:" . "1:1:0:" . "2:" . "1:6" => "+X Fire Damage (Melee)");
		$this->gem_types["purple"][] = array("Immutable:" . "0:0:1:" . "1:" . "1:6" => "+X% Invul. to all Magic Types" , "Mocking:" . "1:1:1:" . "2:" . "1:6" => "+X% Hate Modifier" , "Sacrarial:" . "0:0:1:" . "1:" . "1:6" => "On-hit +X% Invul. to all Melee (Proc)" , "Vexing:" . "1:1:1:" . "2:" . "1:6" => "+X Taunt");
		$this->gem_types["red"][] = array("Concussant:" . "0:0:1:" . "2:" . "1:6" => "+X 2H Blunt Damage (Melee)" , "Destructive:" . "1:1:0:" . "2:" . "1:6" => "+X Crushing Damage (Melee)" , "Destructive:" . "1:1:0:" . "2:" . "1:6" => "Crushing Damage (Proc)" , "Eviscerating:" . "1:1:0:" . "2:" . "1:6" => "+X Slashing Damage (Melee)" , "Exsanguinating:" . "1:1:0:" . "2:" . "1:6" => "+X% Tap Health" , "Fortifying:" . "0:0:1:" . "2:" . "1:6" => "+X Constitution" , "Inviolate:" . "0:0:1:" . "1:" . "1:6" => "+X% Invulerability to all Melee" , "Mighty:" . "1:1:1:" . "2:" . "1:6" => "+X Stength" , "Pulsing:" . "0:0:1:" . "1:" . "1:6" => "On-hit Heal + HoT (Proc)" , "Rending:" . "1:1:0:" . "2:" . "1:6" => "Slashing Damage + DoT (Proc)" , "Revitalising:" . "0:0:1:" . "2:" . "1:6" => "+X Non-Combat Health Regen" , "Rupturing:" . "1:1:0:" . "2:" . "1:6" => "Piercing DoT (Proc)" , "Scything:" . "0:0:1:" . "2:" . "1:6" => "+X 2H Edged Damage (Melee)" , "Secular:" . "0:0:1:" . "1:" . "1:6" => "On-hit +X% Invul. to all Melee (Proc)" , "Thrashing:" . "0:0:1:" . "2:" . "1:6" => "+X 1H Blunt Damage (Melee)" , "Vicious:" . "0:0:1:" . "2:" . "1:6" => "+X 1H Edged Damage (Melee)" , "Violent:" . "0:0:1:" . "2:" . "1:6" => "+X Polearm Damage (Melee)");
		$this->gem_types["white"][] = array("Arcing:" . "1:1:0:" . "1:" . "1:6" => "Electrical Damage (Proc)" , "Grounding:" . "0:0:1:" . "2:" . "1:6" => "+X% Electrical Unvulnerability" , "Observers:" . "0:0:1:" . "2:" . "1:6" => "+X Perception" , "Shocking:" . "0:0:1:" . "1:" . "1:6" => "On-hit Electrical Damage (Proc)" , "Stormforged:" . "1:1:0:" . "2:" . "1:6" => "+X Electrical Damage (Melee)" , "Tempestuous:" . "1:1:1:" . "2:" . "1:6" => "+X Electrical Damage (Magic)");
		$this->gem_types["yellow"][] = array("Focusing:" . "1:1:1:" . "2:" . "1:6" => "+X Casting Concentration" , "Merciful:" . "0:0:1:" . "2:" . "1:6" => "+X% Hate Modifier" , "Omnific:" . "1:1:0:" . "2:" . "1:6" => "+X Damage (Magic)" , "Purifying:" . "0:0:1:" . "2:" . "1:6" => "+X% Poison Invulnerability" , "Retributive:" . "1:1:0:" . "2:" . "1:6" => "+X Holy Damage (Melee)" , "Sacred:" . "1:1:0:" . "2:" . "1:6" => "+X Holy Damage (Magic)" , "Sancrosanct:" . "0:0:1:" . "2:" . "1:6" => "+X% Unholy Invulnerability" , "Vengeful:" . "0:0:1:" . "1:" . "1:6" => "On-hit Holy Damage (Proc)" , "Wrathful:" . "1:0:0:" . "2:" . "1:6" => "Holy Damage (Proc)");
		// Lookup Structure
		// eg. $gem_array["Obsidian"]  = array(1, "black");
		//     $gem_array["Moonstone"] = array(3, "white");
		foreach ($this->gem_types as $colour => $gems)
		{
			for ($i = 0; $i < count($gems[0]); $i ++)
			{
				// Add +1 to turn the index in the gem tier
				$this->gem_array[$gems[0][$i]] = array($i + 1 , $colour);
			}
		}
	}

	function command_handler($name, $msg, $origin)
	{
		if (preg_match('/^gemcut/i', $msg, $info))
		{
			$words = trim(substr($msg, strlen('gemcut')));
			if (! empty($words))
			{
				return $this->gemtiers($words);
			}
			else
			{
				return "Usage: gemcut [tier]";
			}
		}
		elseif (preg_match('/^geminfo/i', $msg, $info))
		{
			$words = trim(substr($msg, strlen('geminfo')));
			if (! empty($words))
			{
				return $this->gem_info($words);
			}
			else
			{
				return "Usage: geminfo [Gem Name]";
			}
		}
		elseif (preg_match('/^gems/i', $msg, $info))
		{
			$words = trim(substr($msg, strlen('gems')));
			return $this->gems($words);
		}
		elseif (preg_match('/^gem/i', $msg, $info))
		{
			$words = trim(substr($msg, strlen('item')));
			if (! empty($words))
			{
				return $this->identify($words);
			}
			else
			{
				return "Usage: gem [itemref]";
			}
		}
		else
		{
			$this->bot->send_help($name);
		}
	}

	/* Identifies a gem. */
	function identify($msg)
	{
		$items = $this->bot->core('items')->parse_items($msg);
		if (empty($items))
			return false;
		$txt = '';
		foreach ($items as $item)
		{
			$result = $this->bot->core('items')->submit_item($item, $name);
			preg_match("/(Flawless|Uncut)\s(.*)/", $item['name'], $matches);
			if ($matches[1] == "Flawless")
				$rare = true;
			else
				$rare = false;
			$gem_info = $this->gem_array[$matches[2]];
			if (count($gem_info) == 2)
			{
				$tier = $gem_info[0];
				$type = $gem_info[1];
			}
			else
			{
				$tier = 0;
				$type = 'unknown';
			}
			if (($type != "unknown") && ($tier != 0))
			{
				$txt .= $item['name'] . "\n\n";
				$txt .= "##gemcut_normal##";
				if ($rare)
					$txt .= "Quality: ##gemcut_highlight##Rare : Trillion/Teardrop/Cabochon (fits in size 2 sockets - shape 10)##end##\n";
				else
					$txt .= "Quality: ##gemcut_highlight##Common : Rhombic/Oval/Oblique (fits in size 1 sockets - shape 5)##end##\n";
				$txt .= "Tier: ##gemcut_highlight##" . $tier . " (lvl " . (($tier + 3) * 10) . ")##end##\n";
				$txt .= "Effects: ##gemcut_highlight##";
				switch ($type)
				{
					case "black":
						$txt .= "Unholy dmg / Unholy dd / Poison dmg / Poison dd";
						break;
					case "blue":
						$txt .= "Tap mana / Tap stamina / Tap health / Cold dmg / Cold dd / Wisdom / Intelligence";
						break;
					case "green":
						$txt .= "Fatality / Dexterity / Melee dmg / Pierce dmg / Crushing immune / Pierce immune / Slashing immune / Tap stamina / Bow dmg";
						break;
					case "orange":
						$txt .= "Fire dmg / Fire DD / Cold Immunity";
						break;
					case "purple":
						$txt .= "Hate / Taunt / Magic immunities";
						break;
					case "red":
						$txt .= "Crushing dmg / Slashing dmg / Tap health / Strength / Stamina + melee immunities / Holy dmg";
						break;
					case "white":
						$txt .= "Electrical dmg / Electrical dd / Electrical Immunity / Perception";
						break;
					case "yellow":
						$txt .= "Magic dmg / Holy dmg / Holy dd / Poison immunity / Unholy immunity";
						break;
				}
				$txt .= "##end##\n\n##gemcut_info##The gem effect is randomly determined when the gem is cut.##end##\n\n";
				if ($rare)
					$txt .= "Shapes: ##gemcut_highlight##Trillion(1 handed), Teardrop(2 handed) and Cabochon(Armor). Can be used in blue crafted armor and weapons .##end##\n\n";
				else
					$txt .= "Shapes: ##gemcut_highlight##Rhombic(1 handed), Oval(2 handed) and Oblique(Armor). Can be used in green crafted armor and weapons.##end##\n\n";
				$txt .= "\n\n";
			}
		}
		if (empty($txt))
			return "The gem could not be identified.";
		else
		{
			$txt .= "##gemcut_info##Socketing:\n";
			$txt .= "- Make sure both the item and the gem are in your inventory and NOT equipped.\n";
			$txt .= "- Select the gem, the items that the gem can be added to will be surrounded with a green border.\n";
			$txt .= "- Drag and drop the gem onto the selected item.##end##";
			$txt .= "##end##";
			return "Result: " . $this->bot->core("tools")->make_blob("identification", $txt);
		}
	}

	function gemtiers($msg)
	{
		switch ($msg)
		{
			case 1:
				$txt = "Tier 1 gems are (level 40-49): Obsidian, Azurite, Chrysoprase, Carnelian, Rose Quartz, Spinel, Quartz and Citrine. Drops in: Field of the Dead or Noble District.";
				break;
			case 2:
				$txt = "Tier 2 gems are (level 50-59): Onyx, Lapis Lazuli, Malachite, Tiger Eye, Iolite, Jasper, Zircon and Chrysoberyl. Drops in: Eiglophian Mountains.";
				break;
			case 3:
				$txt = "Tier 3 gems are (level 60-69): Jet, Turquoise, Peridot, Chalcedony, Amethyst, Garnet, Moonstone and Sagenite. Drops in: Thunder River, Atzel's Approach";
				break;
			case 4:
				$txt = "Tier 4 gems are (level 70-74): Black Jasper, Aquamarine, Sphene, Sunstone, Duskstone, Blood Opal, Achronite and Topaz. Drops in: Atzel's Approach, Keshatta";
				break;
			case 5:
				$txt = "Tier 5 gems are (level 75-79): Nightstar, Sapphire, Jade, Fire Agate, Royal Azel, Ruby, White Opal and Heliodor. Drops in: Keshatta";
				break;
			case 6:
				$txt = "Tier 6 gems are (level 80+): Black Diamond, Star Saphire, Emerald, Padpaasahsa, Tyrian Sapphire, Star Ruby, Diamond and Golden Beryl. Drops in: Keshatta (listed as lvl 75)";
				break;
			default:
				$txt = "Valid tiers are: 1-6";
				break;
		}
		return $txt;
	}

	function gem_info($msg)
	{
		$gem_info = $this->gem_array[$msg];
		if (count($gem_info) == 2)
		{
			$tier = $gem_info[0];
			$type = $gem_info[1];
			$txt = "##gemcut_highlight##Information about Tier $tier gem '$msg'##end##\n\n";
			$txt .= "##gemcut_info##Click on the cut types (##end##1H##gemcut_info##/##end##2H##gemcut_info##/##end##Arm##gemcut_info##) to try searching for example items of that type.##end##\n\n";
			$rare = array();
			$common = array();
			foreach ($this->gem_types[$type][1] as $info => $effect)
			{
				list ($prefix, $cut_1h, $cut_2h, $cut_arm, $rarity, $min, $max) = split(":", $info);
				if ($min <= $tier && $tier <= $max)
				{
					switch ($rarity)
					{
						case 0:
							$common[] = array($msg , $info , $effect);
							break;
						case 1:
							$rare[] = array($msg , $info , $effect);
							break;
						case 2:
							$common[] = array($msg , $info , $effect);
							$rare[] = array($msg , $info , $effect);
							break;
					}
				}
			}
			$txt .= "##gemcut_highlight##Rare Cuts##end##\n";
			$txt .= $this->renderBlock($rare, true);
			$txt .= "\n##gemcut_highlight##Common Cuts##end##\n";
			$txt .= $this->renderBlock($common, false);
			return "Gem Information : " . $this->bot->core("tools")->make_blob("$msg", $txt) . ".";
		}
		else
		{
			return "Sorry! I've never heard of that Gem.";
		}
	}

	function gems($msg)
	{
		if (preg_match("/[1-6]/", $msg))
		{
			$txt = "##gemcut_highlight##Tier $msg gems##end## : Level " . $this->tier_info[$msg - 1][0] . " - Drops in: " . $this->tier_info[$msg - 1][1] . ".\n\n<hr/>\n\n";
			$txt .= $this->displayGems($msg);
			$output = "Tier $msg gems : " . $this->bot->core("tools")->make_blob("click here", $txt) . ".";
		}
		else
		{
			$txt = "";
			for ($i = 0; $i < count($this->tier_info); $i ++)
			{
				$txt .= $this->bot->core("tools")->chatcmd("gems " . ($i + 1), "Tier " . ($i + 1) . " gems") . " : Level " . $this->tier_info[$i][0] . " - Drops in: " . $this->tier_info[$i][1] . ".\n";
			}
			$output = "Gemcutting info : " . $this->bot->core("tools")->make_blob("click here", $txt) . ".";
		}
		return $output;
	}

	function displayGems($tier)
	{
		$txt = "";
		foreach ($this->gem_types as $colour => $gems)
		{
			$txt .= ucfirst($colour) . " Gem : " . $this->bot->core("tools")->chatcmd("geminfo " . $gems[0][$tier - 1], $gems[0][$tier - 1]) . "\n";
		}
		return $txt;
	}

	function renderBlock($lines, $is_rare)
	{
		$text = "";
		foreach ($lines as $line)
		{
			$gem = $line[0];
			$info = $line[1];
			$effect = $line[2];
			list ($prefix, $cut_1h, $cut_2h, $cut_arm, $rarity, $min, $max) = split(":", $info);
			if ($is_rare)
			{
				if ($rarity == 2 || $rarity == 1)
				{
					$text .= "##gemcut_info##$prefix##end## ##gemcut_highlight##:##end## ";
					$text .= $this->renderLink($prefix, "Trillion", $gem, "1H", $cut_1h) . "##gemcut_info##/##end##";
					$text .= $this->renderLink($prefix, "Teardrop", $gem, "2H", $cut_2h) . "##gemcut_info##/##end##";
					$text .= $this->renderLink($prefix, "Cabochon", $gem, "Arm", $cut_arm) . " ##gemcut_highlight##:##end## ";
					$text .= "##gemcut_info##(##end##$effect##gemcut_info##)##end##\n";
				}
			}
			else
			{
				if ($rarity == 2 || $rarity == 0)
				{
					$text .= "##gemcut_info##$prefix##end## ##gemcut_highlight##:##end## ";
					$text .= $this->renderLink($prefix, "Rhombic", $gem, "1H", $cut_1h) . "##gemcut_info##/##end##";
					$text .= $this->renderLink($prefix, "Oval", $gem, "2H", $cut_2h) . "##gemcut_info##/##end##";
					$text .= $this->renderLink($prefix, "Oblique", $gem, "Arm", $cut_arm) . " ##gemcut_highlight##:##end## ";
					$text .= "##gemcut_info##(##end##$effect##gemcut_info##)##end##\n";
				}
			}
		}
		if ($text == "")
			$text = "No gem cuts found!";
		return $text;
	}

	function renderLink($prefix, $rarity, $gem, $text, $flag)
	{
		if ($flag == 1)
		{
			return $this->bot->core("tools")->chatcmd("items +$prefix +$rarity +$gem", $text);
		}
		else
		{
			return "-";
		}
	}
}
?>