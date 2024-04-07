<?php
/*
* Whois.php - Finds info on a player
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
$whois = new Whois($bot);

class Whois extends BaseActiveModule
{
    var $name;
    var $origin;


    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->help['description'] = 'Shows information about a player';
        $this->help['command']['whois <name>'] = "Shows information about player <name>.";
        $this->register_command("all", "whois", "GUEST");
		$this -> register_alias("whois", "w");
        $this->bot->core("colors")
            ->define_scheme("whois", "alienlevel", "lightgreen");
        $this->bot->core("colors")
            ->define_scheme("whois", "level", "lightbeige");
        $this->bot->core("colors")->define_scheme("whois", "name", "yellow");
        $this->bot->core("colors")->define_scheme("whois", "info", "normal");
        $this->bot->core("colors")
            ->define_scheme("whois", "profession", "lightbeige");
        $this->bot->core("colors")->define_scheme("whois", "orginfo", "normal");
        if ($this->bot->guildbot) {
            $altstat = true;
        } else {
            $altstat = false;
        }
        $this->bot->core("settings")
            ->create("Whois", "Details", true, "Should we display a detailed view window with the whois?");
        $this->bot->core("settings")
            ->create("Whois", "Alts", $altstat, "Should we display known alts window when showing whois info?");
        $this->bot->core("settings")
            ->create("Whois", "Online", true, "Should we display if the player is online?");
        $this->bot->core("settings")
            ->create(
                "Whois",
                "ShowMain",
                true,
                "Should we display the name of a characters main if they are on a registered alt? This only makes sense if Details are enabled and/or Alts is disabled "
            );
        $this->bot->core("settings")
            ->create("Whois", "Banned", true, "Should Banned Status be returned on a whois?");
        $this->bot->core("settings")
            ->create(
                "Whois",
                "ShowOptions",
                true,
                "Should we display options and link to other information commands in details window?"
            );
        $this->bot->core("settings")
            ->create(
                "Whois",
                "ShowLinks",
                true,
                "Should we display links to out of game websites containing character info?"
            );
        $this->bot->core("settings")
            ->create("Whois", "LastSeen", true, "Show the time we last saw the user in detailed view if applicable?");
        $this->bot->core("settings")
            ->create("Whois", "Notes", true, "Show notes if any exists?");
    }


    function command_handler($name, $msg, $type)
    {
        preg_match("/^whois (.+)$/i", $msg, $info);
        $id = $this->bot->core("player")->id($info[1]);
        if ($id && !($id instanceof BotError)) {
            return $this->whois_player($name, $info[1], $type);
        } else {
            return "Player ##highlight##" . $info[1] . " ##end##does not exist.";
        }
    }


    /*
    Returns info about the player
    */
    function whois_player($source, $name, $origin)
    {
        $name = ucfirst(strtolower($name));
        if (strtolower($this->bot->game) == 'aoc') {
            $this->name[$name] = $source;
            $this->origin[$name] = $origin;
        }
        $who = $this->bot->core("whois")->lookup($name);
        if (!$who || ($who instanceof BotError)) {
            return false;
        }
        if (strtolower($this->bot->game) == 'aoc') {
            unset($this->name[$name]);
            unset($this->origin[$name]);
        }
        if (strtolower($this->bot->game) == 'ao') {
			if($who["lastname"]!="") {
				$result = "##whois_name##" . $who["nickname"] . "##end## '".$who["lastname"]."' is a level ";
			} else {
				$result = "##whois_name##" . $who["nickname"] . "##end## is a level ";
			}
			if($who["firstname"]!="") {
				$result = "'".$who["firstname"]."' ".$result;
			}			
        } else {		
			$result = "##whois_name##" . $who["nickname"] . "##end## is a level ";
		}
        $result .= "##whois_level##" . $who["level"] . "##end##";
        if (strtolower($this->bot->game) == 'ao') {
            $result .= "/##whois_alienlevel##" . $who["at_id"] . "##end## " . $who["breed"];
        }
        $result .= " ##whois_profession##";
        if ($this->bot->core("settings")->get("Online", "Useshortcuts")) {
            $result .= $this->bot->core("shortcuts")
                ->get_short($who["profession"]);
        } else {
            $result .= $who["profession"];
        }
        if (strtolower($this->bot->game) == 'ao') {
            $result .= "##end##, ";
        }
        if (!empty($who["rank"])) {
            $result .= "##whois_orginfo##";
            if ($this->bot->core("settings")->get("Online", "Useshortcuts")) {
                $result .= $this->bot->core("shortcuts")
                    ->get_short($who["rank"]);
            } else {
                $result .= $who["rank"];
            }
            $result .= " of ";
            if ($this->bot->core("settings")->get("Online", "Useshortcuts")) {
                $result .= $this->bot->core("shortcuts")
                    ->get_short($who["org"]);
            } else {
                $result .= $who["org"];
            }
            $result .= "##end##, ";
        }
        if (strtolower($this->bot->game) == 'ao') {
            $result .= "##" . $who["faction"] . "##" . $who["faction"] . "##end##";
        }
        if ($this->bot->core("settings")->get("whois", "banned")) {
            $banned = $this->bot->core("security")
                ->get_access_level_player($name);
            if ($banned == -1) {
                $result .= ":: ##red## Banned!##end##";
            }
        }
        if ($this->bot->core("settings")->get("Whois", "Online") == true) {
            $online = $this->bot->core("online")->get_online_state($name);
            if ($online['status'] != -1) {
                $result .= " :: " . $online['content'];
            }
        }
        if ($this->bot->core("settings")->get("Whois", "Notes") == true) {
            $notes = $this->bot->core("player_notes")
                ->get_notes($source, $name, "all", "DESC");
            if (!($notes instanceof BotError)) {
                $notesin = "Notes for " . $name . ":\n\n";
                foreach ($notes as $note) {
                    if ($note['class'] == 1) {
                        $notesin .= "Ban Reason #";
                    } elseif ($note['class'] == 2) {
                        $notesin .= "Admin Note #";
                    } else {
                        $notesin .= "Note #";
                    }
                    $notesin .= $note['pnid'] . " added by " . $note['author'] . " on " . gmdate(
                            $this->bot
                                ->core("settings")
                                ->get("Time", "FormatString"),
                            $note['timestamp']
                        ) . ":\n";
                    $notesin .= $note['note'];
                    $notesin .= "\n\n";
                }
                $result .= " :: " . $this->bot->core("tools")
                        ->make_blob("Notes", $notesin);
            }
        }
        if ($this->bot->core("settings")->get("Whois", "Details") == true) {
            if ($this->bot->core("settings")->get("Whois", "ShowMain") == true
            ) {
                $main = $this->bot->core("alts")->main($name);
                if (strcasecmp($main, $name) != 0) {
                    $result .= " :: Alt of $main";
                }
            }
            $result .= " :: " . $this->bot->core("whois")
                    ->whois_details($source, $who);
        } elseif ($this->bot->core("settings")->get("Whois", "Alts")) {
            $alts = $this->bot->core("alts")->show_alt($name);
            if ($alts['alts']) {
                $result .= " :: " . $alts['list'];
            }
        }
        return $result;
    }
}

?>
