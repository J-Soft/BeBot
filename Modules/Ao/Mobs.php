<?php
/*
* Mobs.php - Handle various mobs states
* With linkage to Mobs states API courtesy of The Nadybot Team
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
* - Bitnykk (RK2)
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

$mobs = new Mobs($bot);

/*
The Class itself...
*/
class Mobs extends BaseActiveModule
{
	var $mlist;
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->register_module("mobs");
		$this -> register_command("all", "mobs", "GUEST");
		$this -> register_alias('mobs', 'mob');
		$this -> register_command("all", "pris", "GUEST");
		$this -> register_alias("pris", "priso");
		$this -> register_alias("pris", "prisos");
		$this -> register_alias("pris", "prisoner");
		$this -> register_alias("pris", "prisoners");
		$this -> register_command("all", "hags", "GUEST");		
		$this -> register_alias("hags", "hag");
		$this -> register_command("all", "dreads", "GUEST");
		$this -> register_alias("dreads", "dread");
		$this -> register_alias("dreads", "dreadloch");
		$this -> register_alias("dreads", "dreadlochs");
		$this -> register_command("all", "his", "GUEST");
		$this -> register_alias("his", "hi");
		$this -> register_alias("his", "sapling");
		$this -> register_command("all", "uniques", "GUEST");		
		$this -> register_alias("uniques", "unique");
		$this -> register_alias("uniques", "otacustes");
		$this -> register_alias("uniques", "otacuste");
		$this -> register_alias("uniques", "ota");
		$this -> register_alias("uniques", "ljotur");
		$this -> register_alias("uniques", "ljo");
		$this -> register_command("all", "jacks", "GUEST");
		$this -> register_alias("jacks", "legchopper");
		$this -> register_alias("jacks", "legchop");
		$this -> register_command("all", "recks", "GUEST");
		$this -> register_alias("recks", "reck");
		$this -> help['description'] = 'Allows to have specific mobs states (updated every 2 minutes).';
		$this -> help['command']['mobs']="Shows list of all supported mobs.";
		$this -> help['command']['pris']="Shows latest states of all prisoners.";
		$this -> help['command']['hags']="Shows latest states of all hags.";
		$this -> help['command']['dreads']="Shows latest states of all dreads.";
		$this -> help['command']['his']="Shows latest states of Hollow Island's Sapling.";
		$this -> help['command']['uniques']="Shows latest states of uniques (Otacustes, Ljotur, ...).";
		$this -> help['command']['jacks']="Shows latest states of Jack Legchopper & his clones.";
		$this -> help['command']['recks']="Shows latest states of mobs in The Reck.";		
        $this->bot->core("settings")
            ->create("Mobs", "ApiUrl", "https://mobs.aobots.org/api/", "What's the Mobs API URL we should use to auto-update (Nadybot's by default, leave empty to disable automation) ?");			
		$this->register_event("cron", "2min");
		$this->mlist= array();
	}

	function command_handler($name, $msg, $channel)
	{
		if (preg_match("/^mobs$/i", $msg))
			return $this -> show_mobs("list");
		elseif (preg_match("/^pris$/i", $msg))
			return $this -> show_mobs("prisoner");
		elseif (preg_match("/^hags$/i", $msg))
			return $this -> show_mobs("hag");
		elseif (preg_match("/^dreads$/i", $msg))
			return $this -> show_mobs("dreadloch");
		elseif (preg_match("/^his$/i", $msg))
			return $this -> show_mobs("hi");
		elseif (preg_match("/^uniques$/i", $msg))
			return $this -> show_mobs("unique");
		elseif (preg_match("/^jacks$/i", $msg))
			return $this -> show_mobs("legchopper");
		elseif (preg_match("/^recks$/i", $msg))
			return $this -> show_mobs("reck");			
	}

    function cron($cron)
    {
		if ($cron == 120) {
			if($this->bot->core("settings")->get("Mobs", "ApiUrl")!='') {				
				$url = $this->bot->core("settings")->get("Mobs", "ApiUrl");
				$content = $this->bot->core("tools")->get_site($url);				
				if (!($content instanceof BotError)) {
					if (strpos($content, '"name":') !== false) {
						$folders = json_decode($content);
						$this->mlist= array();							
						foreach($folders as $folder => $mobs) {
							foreach($mobs as $mob) {
								$status = $mob->status->status;
								$color = "<font color=#0000ff>";
								if($status=='up') $color = "<font color=#00ff00>";
								if($status=='under_attack') { $color = "<font color=#ffa500>"; $status = 'attacked'; }
								if($status=='down') $color = "<font color=#ff0000>";
								$rally = $this -> bot -> core("tools") -> chatcmd($mob->coordinates->x." ".$mob->coordinates->y." ".$mob->playfield, ucfirst($mob->key), "waypoint");
								$this -> mlist [$folder] [$mob->name] = $rally." is ".$color.$status."</font>";
							}
						}											
					}
				}
			}
		}
	}
	
	function show_mobs($which)
	{
		if($which=='') $which = 'list';
		$inside = '';
		if($which=='list') {
			$inside .= $this -> bot -> core("tools") -> chatcmd("pris", "Prisoners")." \n";
			$inside .= $this -> bot -> core("tools") -> chatcmd("hags", "Hags")." \n";
			$inside .= $this -> bot -> core("tools") -> chatcmd("dreads", "Dreadlochs")." \n";
			$inside .= $this -> bot -> core("tools") -> chatcmd("his", "Hollow Island")." \n";
			$inside .= $this -> bot -> core("tools") -> chatcmd("uniques", "Uniques")." \n";
			$inside .= $this -> bot -> core("tools") -> chatcmd("jacks", "Jack Legchopper")." \n";
			$inside .= $this -> bot -> core("tools") -> chatcmd("recks", "The Reck")." \n";
		} else {
			if(isset($this -> mlist [$which])) {
				foreach($this -> mlist [$which] as $name => $status) {
					$inside .= $name." : ".$status."\n";
				}
			}
		}
		return "Mob(s) found : ".$this->bot->core("tools")->make_blob("click to view", $inside);	
	}

}
?>
