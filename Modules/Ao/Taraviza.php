<?php
/*
* Tara.php - Handle Tara timer.
*
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
* - Bitnykk (RK2) for Gauntlet addition
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

$taraviza = new Taraviza($bot);

/*
The Class itself...
*/
class Taraviza extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->register_module("taraviza");
		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS tara (time int NOT NULL default '0')");
		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS viza (time int NOT NULL default '0')");
		$this -> register_command("all", "tara", "GUEST");
		$this -> register_command("all", "gauntlet", "GUEST");
		$this -> register_command("all", "settara", "GUEST");
		$this -> register_command("all", "setviza", "GUEST");
		$this -> register_alias('tara', 'timers');
		$this -> register_alias("gauntlet", "gaunt");
		$this -> register_alias("gauntlet", "vizaresh");
		$this -> register_alias("gauntlet", "viza");
		$this -> register_alias("tara", "spawntime");
		$this -> register_alias("tara", "spawn");
		$this -> register_alias("tara", "pop");
		$this -> register_alias("tara", "poptime");
		$this -> help['description'] = 'Allows to have a specific Tarasque/Gauntlet timer.';
		$this -> help['command']['tara']="Shows time left to next Tarasque.";
		$this -> help['command']['viza']="Shows time left to next Gauntlet.";
		$this -> help['command']['settara'] = "Sets the timer from now. Add -/+ number for minute adjusting.";
		$this -> help['command']['setviza'] = "Sets the timer from now. Add -/+ number for minute adjusting.";
	}

	function command_handler($name, $msg, $channel)
	{
		if (preg_match("/^tara$/i", $msg))
			return $this -> show_tara($name, $channel);
		else if (preg_match("/^gauntlet$/i", $msg))
			return $this -> show_viza($name, $channel);
		else if (preg_match("/^settara$/i", $msg))
			return $this -> set_now($name, $channel);
		else if (preg_match("/^settara [+]([0-9]+)$/i", $msg, $info))
			return $this -> set_tara($name, "add", $info, $channel);
		else if (preg_match("/^settara [-]([0-9]+)$/i", $msg, $info))
			return $this -> set_tara($name, "rem", $info, $channel);
		else if (preg_match("/^setviza$/i", $msg))
			return $this -> set_now2($name, $channel);
		else if (preg_match("/^setviza [+]([0-9]+)$/i", $msg, $info))
			return $this -> set_viza($name, "add", $info, $channel);
		else if (preg_match("/^setviza [-]([0-9]+)$/i", $msg, $info))
			return $this -> set_viza($name, "rem", $info, $channel);
	}

	function verif_tara()
	{
        $take = $this -> bot -> db -> select("SELECT * FROM tara");
        if (empty($take))
                $this -> bot -> db -> query("INSERT INTO tara (time) VALUES ('".time()."')");
        }

	function verif_viza()
	{
        $take = $this -> bot -> db -> select("SELECT * FROM viza");
        if (empty($take))
                $this -> bot -> db -> query("INSERT INTO viza (time) VALUES ('".time()."')");
        }

	function show_tara($name, $channel)
	{
		$timer = 0;		
        $this -> verif_tara();
        $take = $this -> bot -> db -> select("SELECT * FROM tara");
        foreach ($take as $line){ $timer = $line[0]; }
        $now = time();
        while ($timer <= $now) { $timer = $timer + 32400; } // 9H cycle
        $left = $timer - $now;
        $hour = floor($left/3600);
        $left = $left - ($hour*3600);
        $min = floor($left/60);
        $sec = $left - ($min*60);
        if ($sec < 10) { $sec = "0".$sec; }
        if ($hour < 10) { $hour = "0".$hour; }
        if ($min < 10) { $min = "0".$min; }
        $msg = "Tarasque should pop in about ".$hour."h".$min."m"; //.":".$sec;
		return $msg;
        }

	function show_viza($name, $channel)
	{
		$timer = 0;
        $this -> verif_tara();
        $take = $this -> bot -> db -> select("SELECT * FROM viza");
        foreach ($take as $line){ $timer = $line[0]; }
        $now = time();
        while ($timer <= $now) { $timer = $timer + 61200; } // 17H cycle
        $left = $timer - $now;
        $hour = floor($left/3600);
        $left = $left - ($hour*3600);
        $min = floor($left/60);
        $sec = $left - ($min*60);
        if ($sec < 10) { $sec = "0".$sec; }
        if ($hour < 10) { $hour = "0".$hour; }
        if ($min < 10) { $min = "0".$min; }
        $msg = "Gauntlet should start in about ".$hour."h".$min."m"; //.":".$sec;
		return $msg;
        }

	function set_now($name, $channel)
	{
        $this -> bot -> db -> query("TRUNCATE TABLE tara");
        $this -> verif_tara();
        $this -> bot -> send_output($name, "Tarasque timer set in 9H", $channel);
//        $this -> bot -> core("raid") -> pause($name, TRUE);
        }

	function set_now2($name, $channel)
	{
        $this -> bot -> db -> query("TRUNCATE TABLE viza");
        $this -> verif_viza();
        $this -> bot -> send_output($name, "Gauntlet timer set in 19H", $channel);
//        $this -> bot -> core("raid") -> pause($name, TRUE);
        }

 	function set_tara($name, $oper, $msg, $channel)
	{
        $this -> verif_tara();
        $take = $this -> bot -> db -> select("SELECT * FROM tara");
        foreach ($take as $line){ $timer = $line[0]; }
        $orig = $timer;
        if ($oper == "add") {
        $timer = $timer + ($msg[1]*60);
        $this -> bot -> db -> query("UPDATE tara SET time='".$timer."' WHERE time='".$orig."'");
        $send = "+".$msg[1]." minute(s) added to Tarasque timer";
        } elseif ($oper == "rem") {
        $timer = $timer - ($msg[1]*60);
        $this -> bot -> db -> query("UPDATE tara SET time='".$timer."' WHERE time='".$orig."'");
        $send = "-".$msg[1]." minute(s) removed to Tarasque timer";
        } else {
        $send = "Tarasque Timer unchanged";
        }
        $this -> bot -> send_output($name, $send, $channel);
        }

 	function set_viza($name, $oper, $msg, $channel)
	{
        $this -> verif_tara();
        $take = $this -> bot -> db -> select("SELECT * FROM viza");
        foreach ($take as $line){ $timer = $line[0]; }
        $orig = $timer;
        if ($oper == "add") {
        $timer = $timer + ($msg[1]*60);
        $this -> bot -> db -> query("UPDATE viza SET time='".$timer."' WHERE time='".$orig."'");
        $send = "+".$msg[1]." minute(s) added to Gauntlet timer";
        } elseif ($oper == "rem") {
        $timer = $timer - ($msg[1]*60);
        $this -> bot -> db -> query("UPDATE viza SET time='".$timer."' WHERE time='".$orig."'");
        $send = "-".$msg[1]." minute(s) removed to Gauntlet timer";
        } else {
        $send = "Gauntlet Timer unchanged";
        }
        $this -> bot -> send_output($name, $send, $channel);
        }

}
?>
