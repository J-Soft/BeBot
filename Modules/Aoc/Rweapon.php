<?php
/*
* Rweapon.php - An Age of Conan raid weapon Module
* For BeBot - An Anarchy Online & Age of Conan Chat Automaton Developed by Blondengy (RK1)
* Copyright (C) 2009 Daniel Holmen
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 J-Soft and the BeBot development team.
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

$rweapon = new rweapon($bot);

class rweapon Extends BaseActiveModule
{
  var $bot;
  var $rweapon;
  var $returnstr;

  function __construct (&$bot)
  {
	parent::__construct($bot, get_class($this));

	$this -> register_command("all", "rweapon", "MEMBER");

  $this -> help['description'] = 'Raid weapon information';
  $this -> help['command']['rweapon'] = 'List all raid weapons';
  $this -> help['command']['rweapon <class>'] = 'List all raid weapons for a class';
  $this -> help['command']['rweapon search <itemname>'] = 'Search for a specific item';
  $this -> help['notes'] = 'Created by Daniel Holmen<br /> If searching by class please use the following classes:<br />sin or Assassin<br />barb or Barbarian<br />bs or shammy or bear or Bear Shaman<br />conq or Conqueror<br />dt or Dark Templar<br />demo or Demonologist<br />guard or Guardian<br />hox or Herald of Xotli<br />necro or Necromancer<br />pom or Priest of Mitra<br />ranger or Ranger<br />tos or Tempest of Set<br />Generic or Ibis or Void';
  }

  function command_handler($name, $msg, $origin)
  {
	 $output = "";
	 if (strtolower($msg) == "rweapon")
	 {
		$output = $this->generate_list();
	 }
	 else if (preg_match("/^rweapon.*$/i", $msg))
	 {
		$cmdparam = explode(" ", $msg);

		if (preg_match("/^search$/i", $cmdparam[1]))
		{
		   $searchstring = preg_replace("/^rweapon.*search(.*)/i", "$1", $msg);
		   $output = $this->search($searchstring);
		}
		else if (preg_match("/^class$/i", $cmdparam[1]))
		{
		   $classname = $this->get_class_name(preg_replace("/^rweapon.*class(.*)/i", "$1", $msg));
		   if ($classname != "")
		   {
			  $output .= $this->generate_list($classname);
		   }
		}
		else
		{
		   $classname = $this->get_class_name(preg_replace("/^rweapon(.*)/i", "$1", $msg));
		   if ($classname != "")
		   {
			  $output .= $this->generate_list($classname);
		   }
		   else
		   {
			  $output = "Unknown command/parameter";
		   }
		}
	 }

	 return $output;
  }

  function get_class_name($name)
  {
	 $aliases = array(
	 "guard" => "Guardian",
	 "dt" => "Dark Templar",
	 "conq" => "Conqueror",
	 "pom" => "Priest of Mitra",
	 "tos" => "Tempest of Set",
	 "bs" => "Bear Shaman",
		"shammy" => "Bear Shaman",
		"bear" => "Bear Shamen",
	 "barb" => "Barbarian",
		"sin" => "Assassin",
	 "ranger" => "Ranger",
		"demo" => "Demonologist",
		"hox" => "Herald of Xotli",
		"necro" => "Necromancer",
	 "ibis" => "Generic",
	 "generic" => "Generic",
	 "void" => "Generic"
	 );

	 $lowercasename = trim(strtolower($name));
	 foreach($aliases as $key => $value)
	 {
		if ($lowercasename == $key) return $value;
		if ($lowercasename == strtolower($value)) return $value;
	 }

	 return "";
  }

  function generate_list($class = "")
  {
	 $output = "";
	 $weaponslist = $this->get_weapons();

	 $lastclass = "";
	 foreach($weaponslist as $key => $value)
	 {
		if ($class == "" || $class == $value[0])
		{
		   if ($lastclass != $value[0])
		   {
			  if ($lastclass != "") $output .= "<br>";
			  $lastclass = $value[0];
			  $output .= "<font face='HEADLINE' color=#c0c0c0>".$lastclass."</font><br>";
		   }
		   if ($value[4] != "")
			  $output .= " <a style=text-decoration:none href='chatcmd:///tell ".$this->bot->botname." items ".$value[4]."'><font color=#8d11be>[".$value[3]."]</font></a> (".$value[1]." ".$value[2].")<br>";
		   else
			  $output .= " ".$value[3]." (".$value[1]." ".$value[2].")<br>";
		}
	 }

	 if ($class == "") $linktitle = "Raid weapon list (Click for info)";
	 else $linktitle = "Raid weapon list for ".$class." (Click for info)";

	 return "<a href=\"text://<div align=left>".$output."</div>\">".$linktitle."</a>";
  }

  function search($searchstring) {

	 $output = "";
	 $singlematchoutput = "";
	 $multimatchoutput = "";
	 $matches = 0;

	 $weaponslist = $this->get_weapons();

	 foreach($weaponslist as $key => $value)
	 {
		if (preg_match("/^.*".$searchstring.".*$/i", " ".$value[3])) {
		   $singlematchoutput   = $value[3].' ('.$value[1].' '.$value[2].') - '.$value[0]." <a href=\"text://<a href='chatcmd:///tell ".$this->bot->botname." items ".$value[4]."'>[".$value[3]."]</a> (".$value[1]." ".$value[2].") - ".$value[0]."<br>\">(Click for info)</a>";
		   $multimatchoutput   .= " <a href='chatcmd:///tell ".$this->bot->botname." items ".$value[4]."'>[".$value[3]."]</a> (".$value[1]." ".$value[2].") - ".$value[0]."<br>";
		   $matches++;
		}
	 }

	 if ($matches == 0) $output = "No results.";
	 else if ($matches == 1) $output = "<font color=#4444bb>1 result: ".$singlematchoutput."</font>";
	 else $output = "<a href=\"text://<div align=left>".$multimatchoutput."</div>\">".$matches." results (Click for info)</a>";

	 return $output;
  }

  function get_weapons()
  {
	 $weaponslist = array( // Class [0], Tier[1], Item Type[2], Name [3], ID [4]
		   array("Guardian", "T1", "One-Handed Edged", "Broadsword of Okeanos", "3770675"),
		   array("Guardian", "T1", "Polearm", "Trident of Okeanos", "3770712"),
		   array("Guardian", "T1", "Shield", "Aegis of the Warlord", "3770703"),
		   array("Guardian", "T2", "One-Handed Edged", "Edge of the Golden Age", "3770701"),
		   array("Guardian", "T2", "Polearm", "Teeth of the Drowned", "3770713"),
		   array("Guardian", "T2", "Shield", "Aegis of the Jackal", "3770704"),
		   array("Guardian", "T3", "One-Handed Edged", "Nilus the Blood Wake", "3770702"),
		   array("Guardian", "T3", "Polearm", "Triton's Depths", "3770716"),
		   array("Guardian", "T3", "Shield", "Aegis of Dhurkan Blackblade", "3770711"),
		   array("Guardian", "CRAFT", "Shield", "Gloomshield", "4309030"),
		   array("Guardian", "CRAFT", "Polearm", "Whispering Touch", "4308507"),
		   array("Dark Templar", "T1", "One-Handed Edged", "The Baleful Blade", "3770801"),
		   array("Dark Templar", "T1", "Shield", "Gorulga's Shield", "3770817"),
		   array("Dark Templar", "T1", "Talisman", "Gorulga's Mark", "3771955"),
		   array("Dark Templar", "T2", "One-Handed Edged", "Blade of the Fallen King", "3770812"),
		   array("Dark Templar", "T2", "Shield", "The Shield of Alkmeenon", "3770828"),
		   array("Dark Templar", "T2", "Talisman", "The Mark of Gwahlur", "3771968"),
		   array("Dark Templar", "T3", "One-Handed Edged", "Shard-Blade of Wraal", "3770816"),
		   array("Dark Templar", "T3", "Shield", "Shield of the Soul Eater", "3770841"),
		   array("Dark Templar", "T3", "Talisman", "The Eye of the Bretheren", "3771969"),
		   array("Dark Templar", "CRAFT", "Shield", "Mirror of Shadow", "4309118"),
		   array("Dark Templar", "CRAFT", "Talisman", "Traumatic Reflection", "4309006"),
		   array("Dark Templar", "CRAFT", "One-Handed Blunt", "Maniacal Harm", "4308973"),
		   array("Conqueror", "T1", "One-Handed Edged", "Bloodseeker Blade", "3770717"),
		   array("Conqueror", "T1", "Two-Handed Edged", "Bloodseeker Claymore", "3770774"),
		   array("Conqueror", "T2", "One-Handed Edged", "Sovereign Battleblade", "3770719"),
		   array("Conqueror", "T2", "Two-Handed Edged", "Bloodregent Claymore", "3770775"),
		   array("Conqueror", "T3", "One-Handed Edged", "Blade of the Crowned Serpent", "3770720"),
		   array("Conqueror", "T3", "Two-Handed Edged", "Claymore of the Crowned Serpent", "3770776"),
		   array("Priest of Mitra", "T1", "One-Handed Blunt", "Vise of the Anointed", "3773832"),
		   array("Priest of Mitra", "T1", "Shield", "Pavis of Fealty", "3773873"),
		   array("Priest of Mitra", "T1", "Staff", "Staff of Devout Piety", "3773907"),
		   array("Priest of Mitra", "T1", "Talisman", "Tome of Holy Obeisance", "3773913"),
		   array("Priest of Mitra", "T2", "One-Handed Blunt", "Hammer of Solemn Vows", "3773842"),
		   array("Priest of Mitra", "T2", "Shield", "The Haven of War", "3773874"),
		   array("Priest of Mitra", "T2", "Staff", "Staff of Eternal Confluence", "3773909"),
		   array("Priest of Mitra", "T2", "Talisman", "Tome of Deific Reverence", "3773914"),
		   array("Priest of Mitra", "T3", "Shield", "Feretrum of Mitra", "3773897"),
		   array("Priest of Mitra", "T3", "One-Handed Blunt", "Hammer of Lambent Fervor", "3773846"),
		   array("Priest of Mitra", "T3", "Staff", "Samoore's Staff of Divinity", "3773910"),
		   array("Tempest of Set", "T1", "One-Handed Blunt", "Mace of the Cloudseer", "3774386"),
		   array("Tempest of Set", "T1", "Polearm", "The Cloudscar", "3774384"),
		   array("Tempest of Set", "T1", "Shield", "The Storm Harbor", "3774406"),
		   array("Tempest of Set", "T1", "Talisman", "The Tome of Storms", "3774410"),
		   array("Tempest of Set", "T2", "One-Handed Blunt", "Heaven's Torment", "3774392"),
		   array("Tempest of Set", "T2", "Polearm", "Spear of Ishti", "3774387"),
		   array("Tempest of Set", "T2", "Shield", "Shedu of Arcing Skies", "3774408"),
		   array("Tempest of Set", "T2", "Talisman", "Scripture of Coronal Skies", "3774411"),
		   array("Tempest of Set", "T3", "One-Handed Blunt", "Maahes' Crackling Spite", "3774394"),
		   array("Tempest of Set", "T3", "Talisman", "Tome of the Black Gale", "3774412"),
		   array("Tempest of Set", "T3", "Polearm", "Spire of the Firmament", "3774388"),
		   array("Bear Shaman", "T1", "Two-Handed Blunt", "Hammer of Primal Wrath", "3773797"),
		   array("Bear Shaman", "T2", "Two-Handed Blunt", "Arm of the Great Bear", "3773813"),
		   array("Bear Shaman", "T3", "Two-Handed Blunt", "The Might of Arctos", "3773820"),
		   array("Bear Shaman", "CRAFT", "Two-Handed Blunt", "Shillelagh of Sogoth", "4308974"),
		   array("Barbarian", "T1", "One-Handed Edged", "The Crescent of Blood", "3772397"),
		   array("Barbarian", "T1", "Two-Handed Edged", "The Twinned Moons", "3772413"),
		   array("Barbarian", "T2", "One-Handed Edged", "Axe of Red Ruin", "3772403"),
		   array("Barbarian", "T2", "Two-Handed Edged", "Greataxe of the Crimson Sea", "3772414"),
		   array("Barbarian", "T3", "One-Handed Edged", "Axe of the Red Tide", "3772411"),
		   array("Barbarian", "T3", "Two-Handed Edged", "Greataxe of the Red Tide", "3772415"),
		   array("Assassin", "T1", "Dagger", "Song of Demise", "3772330"),
		   array("Assassin", "T2", "Dagger", "The Silent Knell", "3772333"),
		   array("Assassin", "T3", "Dagger", "Requiem of Pain", "3772342"),
		   array("Assassin", "CRAFT", "Dagger", "Spine of Dagon", "4308856"),
		   array("Ranger", "T1", "Ammunition", "Bolts of Black Sorrow", "3772260"),
		   array("Ranger", "T1", "Ammunition", "Quiver of Barbed Tongues", "3772301"),
		   array("Ranger", "T1", "Bow", "The Fleeted Wing", "3772217"),
		   array("Ranger", "T1", "Crossbow", "Draw of Fangs", "3772172"),
		   array("Ranger", "T1", "Dagger", "The Gilded Grave", "3772166"),
		   array("Ranger", "T1", "One-Handed Edged", "The Scimitar of Dunes", "3771998"),
		   array("Ranger", "T1", "Shield", "Sepulcher's Ward", "3772163"),
		   array("Ranger", "T2", "Ammunition", "Spines of Repose", "3772282"),
		   array("Ranger", "T2", "Ammunition", "Quiver of Black Asps", "3772320"),
		   array("Ranger", "T2", "Bow", "Brace of Kuthchemes", "3772220"),
		   array("Ranger", "T2", "Crossbow", "The Serpent's Pull", "3772173"),
		   array("Ranger", "T2", "Dagger", "The Agony of Ages", "3772168"),
		   array("Ranger", "T2", "One-Handed Edged", "Sirocco's Lament", "3771999"),
		   array("Ranger", "T2", "Shield", "The Shield of Entropy", "3772164"),
		   array("Ranger", "T3", "Bow", "Legend of Shevatas", "3772247"),
		   array("Ranger", "T3", "Crossbow", "Greed of Shevatas", "3772174"),
		   array("Ranger", "T3", "Dagger", "The Immortal Edge", "3772171"),
		   array("Ranger", "T3", "Ammunition", "Bolts of Raked Flesh", "3772288"),
		   array("Ranger", "T3", "Ammunition", "Quiver of Raked Flesh", "3772321"),
		   array("Ranger", "T3", "Shield", "The Shield of Chaos", "3772165"),
		   array("Ranger", "CRAFT", "Shield", "Waning Boundary", "4309067"),
		   array("Ranger", "CRAFT", "Bow", "Wavebreaker", "4308892"),
		   array("Ranger", "CRAFT", "Crossbow", "Krakensbane", "4308902"),
		   array("Demonologist", "T1", "Dagger", "Blade of Marred Spirits", "3772420"),
		   array("Demonologist", "T1", "Staff", "Stave of Malefic Rites", "3773762"),
		   array("Demonologist", "T1", "Talisman", "Fetish of Caged Fiends", "3772416"),
		   array("Demonologist", "T2", "Dagger", "Zukala's Rites", "3773744"),
		   array("Demonologist", "T2", "Staff", "Clutch of Black Terrors", "3773763"),
		   array("Demonologist", "T2", "Talisman", "Zukala's Reliquary", "3772417"),
		   array("Demonologist", "T3", "Dagger", "The Death of Innocents", "3773746"),
		   array("Demonologist", "T3", "Staff", "The Helot of Sacrilege", "3773780"),
		   array("Demonologist", "T3", "Talisman", "Arca of Jaggta-Noga", "3772418"),
		   array("Herald of Xotli", "T1", "Dagger", "The Lost Soul", "3770878"),
		   array("Herald of Xotli", "T1", "Talisman", "Blood Scripture of Xotli", "3771989"),
		   array("Herald of Xotli", "T1", "Two-Handed Edged", "Rage of Xotli", "3771971"),
		   array("Herald of Xotli", "T2", "Dagger", "Pain of the Damned", "3771941"),
		   array("Herald of Xotli", "T2", "Talisman", "Elder Scripture of Xotli", "3772002"),
		   array("Herald of Xotli", "T2", "Two-Handed Edged", "Fury of Xotli", "3771975"),
		   array("Herald of Xotli", "T3", "Two-Handed Edged", "Wrath of Xotli", "3771987"),
		   array("Necromancer", "T1", "Dagger", "Edge of Tartarus", "3772283"),
		   array("Necromancer", "T1", "Staff", "Axis of Tartarus", "3772404"),
		   array("Necromancer", "T1", "Talisman", "Opus of Ash and Blood", "3772246"),
		   array("Necromancer", "T2", "Dagger", "Knife of Dessication", "3772286"),
		   array("Necromancer", "T2", "Staff", "The Narthex of Hell", "3772406"),
		   array("Necromancer", "T2", "Talisman", "Tome of the Undying", "3772248"),
		   array("Necromancer", "T3", "Dagger", "The Infernus of Dagon", "3772287"),
		   array("Necromancer", "T3", "Staff", "The Necros of Dagon", "3772412"),
		   array("Necromancer", "T3", "Talisman", "Epithet of Skelos", "3772250"),
		   array("Necromancer", "CRAFT", "Staff", "Filament of Nightmare", "4308971"),
		   array("Necromancer", "CRAFT", "Dagger", "Ell of Torment", "4308857"),
		   array("Generic", "T3", "Dagger", "Fury of the Void", "4281142"),
		   array("Generic", "T3", "One-Handed Edged", "Blade of the Void", "3772000"),
		   array("Generic", "T3", "Two-Handed Blunt", "Hammer of the Void", "4281141"),
		   array("Generic", "CRAFT", "One-Handed Edged", "Quill of Ibis", "3986915"),
		   array("Generic", "CRAFT", "Two-Handed Edged", "Feather of Ibis", "3986899"),
			);

	 return $weaponslist;
  }
}
?>
