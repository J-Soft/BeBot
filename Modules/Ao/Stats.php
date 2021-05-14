<?php
/*
* Stats.php - Hidden skills in AO
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
*
* Developed by:
* Bitnykk - based on Tyrence work for Budabot
*
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
$stats = new Stats($bot);
class Stats extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
                $this -> register_command('all', 'stats', 'GUEST');
				$this -> register_alias("stats", "stat");
		$this -> help['description'] = 'Display hidden skills.';
		$this -> help['command']['stats']="Display hidden skills.";
	}

        function command_handler($name, $msg, $channel)
        {
		if (preg_match("/^stats$/i", $msg))
		{
			return $this -> show_stats($name, $channel);
		} else {
			$this -> bot -> send_help($name);
		}
		return false;
        }

	function show_stats($name, $channel)
	{
	$msg = "";
	$msg .= "Offense / Defense<br>";
$msg .= "<a href=skillid://276>Offense (Addall-Off)</a><br>";
$msg .= "<a href=skillid://277>Defense (Addall-Def)</a><br>";
$msg .= "<a href=skillid://51>Aggdef-Slider</a><br>";
$msg .= "<a href=skillid://4>Attack Speed</a><br>";
$msg .= "<br>";
	$msg .= "Critical Strike<br>";
$msg .= "<a href=skillid://379>Crit increase</a><br>";
$msg .= "<a href=skillid://391>Crit decrease</a><br>";
$msg .= "<br>";
	$msg .= "Heal<br>";
$msg .= "<a href=skillid://342>Heal delta (interval)</a> (tick in secs)<br>";
$msg .= "<a href=skillid://343>Heal delta (amount)</a><br>";
$msg .= "<a href=skillid://535>Heal modifier</a><br>";
$msg .= "<br>";
	$msg .= "Nano<br>";
$msg .= "<a href=skillid://363>Nano delta (interval)</a> (tick in secs)<br>";
$msg .= "<a href=skillid://364>Nano delta (amount)</a><br>";
$msg .= "<a href=skillid://318>Nano execution cost</a><br>";
$msg .= "<a href=skillid://536>Nano modifier</a><br>";
$msg .= "<a href=skillid://383>Interrupt modifier</a><br>";
$msg .= "<a href=skillid://381>Range Increase Nanoformula</a><br>";
$msg .= "<br>";
	$msg .= "Add Damage (Amount)<br>";
$msg .= "<a href=skillid://279>+Damage - Melee</a><br>";
$msg .= "<a href=skillid://280>+Damage - Energy</a><br>";
$msg .= "<a href=skillid://281>+Damage - Chemical</a><br>";
$msg .= "<a href=skillid://282>+Damage - Radiation</a><br>";
$msg .= "<a href=skillid://278>+Damage - Projectile</a><br>";
$msg .= "<a href=skillid://311>+Damage - Cold</a><br>";
$msg .= "<a href=skillid://315>+Damage - Nano</a><br>";
$msg .= "<a href=skillid://316>+Damage - Fire</a><br>";
$msg .= "<a href=skillid://317>+Damage - Poison</a><br>";
$msg .= "<br>";
	$msg .= "Reflect Shield (Percentage)<br>";
$msg .= "<a href=skillid://205>ReflectProjectileAC</a><br>";
$msg .= "<a href=skillid://206>ReflectMeleeAC</a><br>";
$msg .= "<a href=skillid://207>ReflectEnergyAC</a><br>";
$msg .= "<a href=skillid://208>ReflectChemicalAC</a><br>";
$msg .= "<a href=skillid://216>ReflectRadiationAC</a><br>";
$msg .= "<a href=skillid://217>ReflectColdAC</a><br>";
$msg .= "<a href=skillid://218>ReflectNanoAC</a><br>";
$msg .= "<a href=skillid://219>ReflectFireAC</a><br>";
$msg .= "<a href=skillid://225>ReflectPoisonAC</a><br>";
$msg .= "<br>";
	$msg .= "Reflect Shield (Amount)<br>";
$msg .= "<a href=skillid://475>MaxReflectedProjectileDmg</a><br>";
$msg .= "<a href=skillid://476>MaxReflectedMeleeDmg</a><br>";
$msg .= "<a href=skillid://477>MaxReflectedEnergyDmg</a><br>";
$msg .= "<a href=skillid://278>MaxReflectedChemicalDmg</a><br>";
$msg .= "<a href=skillid://479>MaxReflectedRadiationDmg</a><br>";
$msg .= "<a href=skillid://480>MaxReflectedColdDmg</a><br>";
$msg .= "<a href=skillid://481>MaxReflectedNanoDmg</a><br>";
$msg .= "<a href=skillid://482>MaxReflectedFireDmg</a><br>";
$msg .= "<a href=skillid://483>MaxReflectedPoisonDmg</a><br>";
$msg .= "<br>";
	$msg .= "Damage Shield (Amount)<br>";
$msg .= "<a href=skillid://226>ShieldProjectileAC</a><br>";
$msg .= "<a href=skillid://227>ShieldMeleeAC</a><br>";
$msg .= "<a href=skillid://228>ShieldEnergyAC</a><br>";
$msg .= "<a href=skillid://229>ShieldChemicalAC</a><br>";
$msg .= "<a href=skillid://230>ShieldRadiationAC</a><br>";
$msg .= "<a href=skillid://231>ShieldColdAC</a><br>";
$msg .= "<a href=skillid://232>ShieldNanoAC</a><br>";
$msg .= "<a href=skillid://233>ShieldFireAC</a><br>";
$msg .= "<a href=skillid://234>ShieldPoisonAC</a><br>";
$msg .= "<br>";
	$msg .= "Damage Absorb (Amount)<br>";
$msg .= "<a href=skillid://238>AbsorbProjectileAC</a><br>";
$msg .= "<a href=skillid://239>AbsorbMeleeAC</a><br>";
$msg .= "<a href=skillid://240>AbsorbEnergyAC</a><br>";
$msg .= "<a href=skillid://241>AbsorbChemicalAC</a><br>";
$msg .= "<a href=skillid://242>AbsorbRadiationAC</a><br>";
$msg .= "<a href=skillid://243>AbsorbColdAC</a><br>";
$msg .= "<a href=skillid://244>AbsorbFireAC</a><br>";
$msg .= "<a href=skillid://245>AbsorbPoisonAC</a><br>";
$msg .= "<a href=skillid://246>AbsorbNanoAC</a><br>";
$msg .= "<br>";
	$msg .= "Misc<br>";
$msg .= "<a href=skillid://319>XP Bonus</a><br>";
$msg .= "<a href=skillid://382>SkillLockModifier</a><br>";
$msg .= "<a href=skillid://380>Weapon Range Increase</a><br>";
$msg .= "<a href=skillid://517>Special Attack Blockers</a><br>";
$msg .= "<a href=skillid://199>Reset Points</a><br>";
$msg .= "<a href=skillid://360>Scale</a><br>";
$msg .= "<a href=skillid://676>Profession Duel Kills</a><br>";
$msg .= "<a href=skillid://677>Profession Duel Deaths</a><br>";
$msg .= "<a href=skillid://679>Solo Deaths</a><br>";
$msg .= "<a href=skillid://681>Team Deaths</a><br>";
$msg .= "<a href=skillid://410>Number of fighting opponents</a><br>";
$msg .= "<br>";
$msg .= "Compiled by Tyrence (RK2)";
$msg .= "<br>";
$msg .= "Adapted by Bitnykk (RK5)";
        $blob = " Hidden stats : " . $this -> bot -> core("tools") -> make_blob("click", $msg);
        return $blob;
	}

}
?>