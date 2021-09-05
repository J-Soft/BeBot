<?php
/*
* Attribute.php - An Age of Conan attribute calculator Module
* For BeBot - An Anarchy Online & Age of Conan Chat Automaton Developed by Blondengy (RK1)
* Copyright (C) 2009 Daniel Holmen - adapted by Bitnykk for 0.7.x serie
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 Thomas Juberg Stensås, ShadowRealm Creations and the BeBot development team.
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

$aocattribute = new Aocattribute($bot);

class Aocattribute extends BaseActiveModule
{
  var $bot;
  var $attribute;
  var $returnstr;

  function __construct (&$bot)
  {
   parent::__construct($bot, get_class($this));

   $this -> register_command("all", "attribute", "MEMBER");
   $this -> register_alias('attribute', 'attributes');   

   $this -> help['description'] = 'Attribute trickle-down calculator';;
   
   $this -> help['command']['attribute <name> <value>'] = "Shows bonuses gained with given attribute name and value (anything over 1)."; 
   $this -> help['notes'] = "Intelligence = int / intel\n\nConstitution = con / const\n\nStrength = str / streng\n\nDexterity = dex / dext\n\nWisdom = wis / wisd\n\nCombat Rating = cr or comb\n\nHeal Rating = hr / heal.";   
   
  }

  function command_handler($name, $msg, $origin)
  {
   $output = "";
   if (strtolower($msg) == "attribute")
   {
    $this -> bot -> send_help($name, 'attribute');
   }
   else if (preg_match("/^attribute.*$/i", $msg))
   {
    $cmdparam = explode(" ", $msg);

    $attr = $this->get_attribute_name($cmdparam[1]);
    if ($attr == "") return "No such attribute name";
    if (isset($cmdparam[2]) && is_numeric($cmdparam[2]) && $cmdparam[2] > 0) {
		$output = $this->generate_list($attr, $cmdparam[2]);
	} else {
		$output = $this->generate_list($attr, 1);		
	}
   }
   else $output = "Invalid attribute arguments";
   
   return $output;
  }

  function get_attribute_name($name)
  {
   $aliases = array(
    "int" => "Intelligence",
    "intel" => "Intelligence",
    "con" => "Constitution",
    "const" => "Constitution",
    "str" => "Strength",
    "streng" => "Strength",
    "dex" => "Dexterity",
    "dext" => "Dexterity",
    "wis" => "Wisdom",
    "wisd" => "Wisdom",
    "cr" => "CombatRating",
    "comb" => "CombatRating",
    "hr" => "HealRating",
    "heal" => "HealRating"
   );

   $lowercasename = trim(strtolower($name));
   foreach($aliases as $key => $value)
   {
    if ($lowercasename == $key) return $value;
    if ($lowercasename == strtolower($value)) return $value;
   } 

   return "";
  }

  function generate_list($attr, $value=1)
  {
   $output = "<font face=&#39;HEADLINE&#39; color=#c0c0c0>" . $value . " " . $attr . "</font><br>";
   $linktitle = "Calculated values for ".$value." ".$attr." (Click for info)";

   $infolist = $this->get_attribute($attr);

   foreach ($infolist as $info) {
    $output .= $info["Prefix"] . ($info["Factor"] * $value) . " " . $info["Title"] ."<br>";
   }
   return "<a href=\"text://<div align=left>".$output."</div>\">".$linktitle."</a>";
  }

  function get_attribute($attr)
  {
   $attributelist = array(
      "Strength" => array(
       array("Factor" => 3, "Prefix" => "+", "Title" => "Combat rating (except Daggers)", "Description" => "Increases Combat rating when using melee weapons (except Daggers)"),
       array("Factor" => 2, "Prefix" => "+", "Title" => "Armor", "Description" => "Increases Armor"),
       array("Factor" => 0.05, "Prefix" => "+", "Title" => "Natural Stamina regeneration", "Description" => "Increases Natural Stamina regeneration"),
       array("Factor" => 0.15, "Prefix" => "+", "Title" => "OOC Stamina regeneration", "Description" => "Increases Out of Combat Stamina regeneration"),
       array("Factor" => 2, "Prefix" => "+", "Title" => "Stamina", "Description" => "Increases Stamina"),
       array("Factor" => 0.15, "Prefix" => "+", "Title" => "OOC Health regeneration", "Description" => "Increases Out of Combat Health regeneration")),
      "Intelligence" => array(
       array("Factor" => 0.6, "Prefix" => "+", "Title" => "Spell damage (Mage)", "Description" => "Increases Spell damage for spells with Intelligence attribute (Mage spells)"),
       array("Factor" => 3, "Prefix" => "+", "Title" => "Mana", "Description" => "Increase Mana points"),
       array("Factor" => 0.07, "Prefix" => "+", "Title" => "Natural Mana regeneration", "Description" => "Increases Natural Mana regeneration"),
       array("Factor" => 0.5, "Prefix" => "+", "Title" => "Protection from Electrical/Fire/Cold", "Description" => "Increases protection from Electrical/Fire/Cold magic"),
       array("Factor" => 0.38, "Prefix" => "+", "Title" => "OOC Mana regeneration", "Description" => "Increases Out of Combat Mana regeneration")),
      "Constitution" => array(
       array("Factor" => 5, "Prefix" => "+", "Title" => "Health (Minimally, depends on your Class)", "Description" => "Increases Health points Minimum"),
       array("Factor" => 8, "Prefix" => "+", "Title" => "Health (Maximally, depends on your Class)", "Description" => "Increases Health points Maximum"),
       array("Factor" => 2, "Prefix" => "+", "Title" => "Stamina", "Description" => "Increase Stamina points"),
       array("Factor" => 0.05, "Prefix" => "+", "Title" => "Natural Stamina regeneration", "Description" => "Increases Natural Stamina regeneration"),
       array("Factor" => 0.15, "Prefix" => "+", "Title" => "OOC Stamina regeneration", "Description" => "Increase Out of Combat Stamina regeneration"),
       array("Factor" => 0.15, "Prefix" => "+", "Title" => "OOC Health regeneration", "Description" => "Increases Out of Combat Health regeneration")),
      "Dexterity" => array(
       array("Factor" => 0.5, "Prefix" => "+", "Title" => "Evade rating", "Description" => "Increases Evade rating"),
       array("Factor" => 2, "Prefix" => "+", "Title" => "Stamina", "Description" => "Increases Stamina points"),
       array("Factor" => 3, "Prefix" => "+", "Title" => "Combat rating (ranged, daggers)", "Description" => "Increases Combat rating with ranged weapons and daggers"),
       array("Factor" => 0.05, "Prefix" => "+", "Title" => "Natural Stamina regeneration", "Description" => "Increases Natural Stamina regeneration"),
       array("Factor" => 0.15, "Prefix" => "+", "Title" => "OOC Stamina regeneration", "Description" => "Increases Out of Combat Stamina regeneration")),
      "Wisdom" => array(
       array("Factor" => 0.6, "Prefix" => "+", "Title" => "Spell damage (Priest/DT)", "Description" => "Increases Spell damage for spells with Wisdom attribute (Priest/DT spells)"),
       array("Factor" => 3, "Prefix" => "+", "Title" => "Mana", "Description" => "Increases Mana points"),
       array("Factor" => 0.07, "Prefix" => "+", "Title" => "Natural Mana regeneration", "Description" => "Increases Natural Mana regeneration"),
       array("Factor" => 0.38, "Prefix" => "+", "Title" => "OOC Mana regeneration", "Description" => "Increase Out of Combat Mana regeneration"),
       array("Factor" => 0.5, "Prefix" => "+", "Title" => "Protection (Holy/Unholy)", "Description" => "Increases Protection from Holy/Unholy magic")),
      "CombatRating" => array(
       array("Factor" => 0.0278, "Prefix" => "+", "Title" => "DPS", "Description" => "Increases Damage Per Second")),
      "HealRating" => array(
       array("Factor" => 0.135, "Prefix" => "+", "Title" => "Self Healing", "Description" => "Increases Self Healing"),
       array("Factor" => 0.235, "Prefix" => "+", "Title" => "Cone Effect Healing", "Description" => "Increases Cone Effect Healing"))
      ); 

   return $attributelist[$attr];
  }
}

?>