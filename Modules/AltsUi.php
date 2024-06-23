<?php
/*
* Alts.php - Manage alternative characters.
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
$alts = new Alts($bot);
/*
The Class itself...
*/
class Alts extends BaseActiveModule
{

    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command("all", "alts", "GUEST", array("confirm" => "ANONYMOUS"));
        $this->register_command("all", "altadmin", "ADMIN");
        $this->help['description'] = "Shows information about alternative characters.";
        $this->help['command']['alts [player]'] = "Shows information about [player]. If no player is given it shows information about your alts";
        $this->help['command']['alts add <player>'] = "Adds <player> as your alt (can add more than 1 coma-separated eg: Toon1,Toon2,etc).";
        $this->help['command']['alts del <player>'] = "Removes <player> from your alt list.";
        $this->help['command']['alts confirm <main>'] = "Confirms you as alt of <main>.";
		$this->help['command']['alts newmain <alt>'] = "Makes declared <alt> the new main of all declared alts.";
        $this->help['command']['altadmin add <main> <alt>'] = "Adds <alt> as alt to <main> (can add more than 1 coma-separated eg: Toon1,Toon2,etc).";
		$this->help['command']['altadmin list <main>'] = "Lists all alts unconfirmed included.";
        $this->help['command']['altadmin del <main> <alt>'] = "Removes <alt> as alt from <main>.";
        $this->help['command']['altadmin confirm <main> <alt>'] = "Confirms <alt> as alt of <main>.";
		$this->help['command']['altadmin newmain <alt>'] = "Makes declared <alt> the new main of all declared alts.";
		$this->help['command']['altadmin recache'] = "Refreshes mains/alts cache of bot fully (after manual altadmin add/del, e.g.).";
        $this->bot->core("settings")
            ->create(
                "Alts",
                "Security",
                true,
                "Should security restrictions be enabled to prevent users from gaining higher access levels by adding alts with higher access level when Usealts for the security module is enabled?"
            );
    }


    function command_handler($name, $msg, $origin)
    {
        $security = false;
        $vars = explode(' ', strtolower($msg));
		if(!isset($vars[1])) $vars[1] = "";
        $command = $vars[0];
        if (($this->bot->core("settings")
                    ->get("Alts", "Security") == true)
            && ($this->bot
                    ->core("settings")
                    ->get("Security", "UseAlts") == true)
        ) {
            $security = true;
        }
        switch ($command) {
            case 'alts':
                switch ($vars[1]) {
                    case 'add':
						if(strpos($vars[2], ',') !== false) {
							$toons = explode(',', $vars[2]);
							foreach($toons as $toon) {
								$this->add_alt($name, $toon);
							}
							return "Alts have been sorted out.";
						} else {							
							return $this->add_alt($name, $vars[2]);
						}
                    case 'del':
                    case 'rem':
                        return $this->del_alt($name, $vars[2]);
                    case '':
                        return $this->display_alts($name);
                    case 'confirm':
                        return $this->confirm($name, $vars[2]);
                    case 'newmain':
                        return $this->newmain($name, $vars[2], 0);						
                    default:
                        return $this->display_alts($vars[1]);
                }
            case 'altadmin':
                switch ($vars[1]) {
                    case 'add':
                        if ($security) {
                            if ($this->bot->core("security")
                                    ->get_access_level($name) < $this->bot
                                    ->core("security")->get_access_level($vars[2])
                            ) {
                                return "##error##Character ##highlight##$vars[2]##end## has a higher security level then you, so you cannot add ##highlight##$vars[3]##end## to ##highlight##$vars[2]##end##'s alts.##end##";
                            } elseif ($this->bot->core("security")
                                    ->get_access_level($name) < $this->bot
                                    ->core("security")->get_access_level($vars[3])
                            ) {
                                return "##error##Character ##highlight##$vars[3]##end## has a higher security level then you, so you cannot add ##highlight##$vars[3]##end## to ##highlight##$vars[2]##end##'s alts.##end##";
                            } else {
								if(strpos($vars[3], ',') !== false) {
									$toons = explode(',', $vars[3]);
									foreach($toons as $toon) {
										$this->add_alt($vars[2], $toon, 1);
									}
									return "Alts have been all sorted.";
								} else {
									return $this->add_alt($vars[2], $vars[3], 1);
								}	
                            }
                        } else {
							if(strpos($vars[3], ',') !== false) {
								$toons = explode(',', $vars[3]);
								foreach($toons as $toon) {
									$this->add_alt($vars[2], $toon, 1);
								}
								return "Alts have all been sorted.";
							} else {
								return $this->add_alt($vars[2], $vars[3], 1);
							}	
                        }
                    case 'rem':
                    case 'del':
                        return $this->del_alt($vars[2], $vars[3]);
                    case 'confirm':
                        return $this->confirm($vars[3], $vars[2]);
                    case 'newmain':
                        return $this->newmain($name, $vars[2], 1);	
                    case 'recache':
                        return $this->recache();
                    case 'list':
                        return $this->display_all($vars[2]);						
                    default:
                        return "Unknown Subcommand: ##highlight##" . $vars[1] . "##end##";
                }
            default:
                return "Broken plugin, received unhandled command: $command";
        }
        return false;
    }

	function recache() {
		$this->bot->core("alts")->create_caches();
		return "Bot mains/alts cache has been fully updated";
	}
	
    function display_alts($name)
    {
        if ($this->bot->core("player")->id($name) instanceof BotError) {
            return "##error##Character ##highlight##$name##end## does not exist.##end##";
        }

        $whois = $this->bot->core("whois")->lookup($name);

        if ($whois instanceof BotError) {
            $whois = array('nickname' => $name);
        }

        $alts = $this->bot->core("alts")->show_alt($name);

        if (strtolower($this->bot->game) == 'aoc') {
            $retstr = "{$whois['nickname']} ({$whois['level']} / {$whois['class']}) - ";
        } else {
            $retstr
                = "{$whois['firstname']}' ##{$whois['faction']}##{$whois['nickname']}##end##' {$whois['lastname']} ({$whois['level']} / ##lime## {$whois['at_id']}##end## {$whois['profession']}) - ";
        }

        if ($alts['alts']) {
            $retstr .= $alts['list'];
        } else {
            $retstr .= "has no alts defined!";
        }
        return $retstr;
    }
	
    function display_all($main)
    {
        $return = '';
		$total = 0;
		$main = ucfirst(strtolower($main));
        //Check that that $main is a valid character
        if ($this->bot->core('player')->id($main) instanceof BotError) {
            return "##error##Character ##highlight##$main##end## does not exist.##end##";
        }
        $confirmed = $this->bot->db->select("SELECT alt FROM #___alts WHERE confirmed = 1 AND main = '$main'");
        if (!empty($confirmed) && count($confirmed)>0) {
			$return .= "\n\n##normal##Confirmed##end##: ";
            foreach($confirmed AS $key => $value) {
				$return .= $value[0]." ";
				$total ++;
			}
		}
        $unconfirmed = $this->bot->db->select("SELECT alt FROM #___alts WHERE confirmed = 0 AND main = '$main'");
        if (!empty($unconfirmed) && count($unconfirmed)>0) {
			$return .= "\n\n##normal##Unconfirmed##end##: ";
            foreach($unconfirmed AS $key => $value) {
				$return .= "<a href='chatcmd:///tell ".$this->bot->botname.
						" altadmin confirm ".$main." ".$value[0]."'>".$value[0]."</a> ";
				$total ++;
			}
		}
		// generate response from lists
		if ($return == '') return "No alt (even unconfirmed) yet declared from ".$main;
		else return $this->bot->core("tools")->make_blob($total." alt(s) found (confirmed or not).", $return);
	}

    /*
    Changes given alt to new main
    */
    function newmain($name, $alt, $admin = 0)
    {
		$security = false;
        $name = ucfirst(strtolower($name));
        $alt = ucfirst(strtolower($alt));
        if (($this->bot->core("settings")
                    ->get("Alts", "Security") == true)
            && ($this->bot
                    ->core("settings")
                    ->get("Security", "UseAlts") == true)
            && ($admin == 0)
        ) {
            $security = true;
        }
        //Check that $name is a valid character
        if ($this->bot->core('player')->id($name) instanceof BotError) {
            return "##error##Character ##highlight##$name##end## does not exist.##end##";
        }
        //Check that that $alt is a valid character
        if ($this->bot->core('player')->id($alt) instanceof BotError) {
            return "##error##Character ##highlight##$alt##end## does not exist.##end##";
        }	
        //Establish the $main of the $alt
        $main = $this->bot->core("alts")->main($alt);
        //Check that $alt is already its own $main
        if ($alt == $main) {
            return "##error##Character ##highlight##$alt##end## is already a main or has no alt.##end##";
        }
        //Check if user levelled sender is the main of the alt being mained
        if ($name != $main && $admin == 0) {
            return "##error##User ##highlight##$name##end## must be Admin or $alt's main to make $alt new main.##end##";
        }		
        if ($security) {
            // Check if the alt being mained has lower security
            if ($this->bot->core("security")
                    ->get_access_level($main) > $this->bot->core("security")
                    ->get_access_level($alt)
            ) {
                return "##error##Character ##highlight##$alt##end## is of lower user level than ##highlight##$main##end## and cannot be changed to main.##end##";
            }
        }		
        $alt = ucfirst(strtolower($alt));
        $main = ucfirst(strtolower($main));
		$this->bot->db->query("UPDATE #___alts SET alt = '$main', main = '$alt' WHERE alt = '$alt' AND main = '$main'");
		$this->bot->db->query("UPDATE #___alts SET main = '$alt' WHERE main = '$main'");
		$this->bot->core("alts")->create_caches();
        if ($this->bot->exists_module("points")) {
            $this->bot->core("points")->check_alts($alt);
        }
        return "##highlight##$alt##end## has been changed to new main.";		
	}

    /*
    Adds an alt to your alt list
    */
    function add_alt($name, $alt, $admin = 0)
    {
        $security = false;
        $name = ucfirst(strtolower($name));
        $alt = ucfirst(strtolower($alt));
        if (($this->bot->core("settings")
                    ->get("Alts", "Security") == true)
            && ($this->bot
                    ->core("settings")
                    ->get("Security", "UseAlts") == true)
            && ($admin == 0)
        ) {
            $security = true;
        }
        //Check that we're not trying to register ourself as an alt
        if ($name == $alt) {
            return "##error##You cannot register yourself as your own alt.##end##";
        }
        //Check that $name is a valid character
        if ($this->bot->core('player')->id($name) instanceof BotError) {
            return "##error##Character ##highlight##$name##end## does not exist.##end##";
        }
        //Check that the alt is a valid character
        if ($this->bot->core('player')->id($alt) instanceof BotError) {
            return "##error##Character ##highlight##$alt##end## does not exist.##end##";
        }
        //Establish the main of the caller
        $main = $this->bot->core("alts")->main($name);
        //Check that the alt is not already registered
        $query = "SELECT main, confirmed FROM #___alts WHERE main='$alt' OR alt='$alt'";
        $result = $this->bot->db->select($query);
        if (!empty($result)) {
            if ($result[0][1] == 1) {
                return ("##highlight##$alt##end## is already registered as an alt of ##highlight##" . $result[0][0] . "##end##.");
            } else {
                return ("##highlight##$alt##end## is already an unconfirmed alt of ##highlight##" . $result[0][0] . "##end##.");
            }
        }

        //Check that the main is not already registered but unconfirmed, this is only needed if main is not different
        if ($name == $main) {
            $query = "SELECT main, confirmed FROM #___alts WHERE alt='$name'";
            $result = $this->bot->db->select($query);
            if (!empty($result)) {
                if ($result[0][1] == 0) {
                    return ("##highlight##$name##end## is already an unconfirmed alt of ##highlight##" . $result[0][0] . "##end##.");
                }
            }
        }
        if ($security) {
            // Check if the alt being added has higher security
            if ($this->bot->core("security")
                    ->get_access_level($name) < $this->bot->core("security")
                    ->get_access_level($alt)
            ) {
                return "##error##Character ##highlight##$alt##end## is of higher user level and cannot be added as your alt.##end##";
            }
        }
        $alt = ucfirst(strtolower($alt));
        $main = ucfirst(strtolower($main));
        if ($this->bot->core("settings")
                ->get("Alts", "Confirmation")
            && ($admin == 0)
        ) {
            $this->bot->db->query("INSERT INTO #___alts (alt, main, confirmed) VALUES ('$alt', '$main', 0)");
            $inside = "##blob_title##  :::  Alt Confirmation Request :::##end##\n\n";
            $inside .= "##blob_text## $main has added you as an Alt\n\n " . $this->bot
                    ->core("tools")
                    ->chatcmd("alts confirm " . $main, "Click here") . " to Confirm.";
            $this->bot->send_tell(
                $alt,
                "Alt Confirmation :: " . $this->bot
                    ->core("tools")->make_blob("Click to view", $inside)
            );
            return "##highlight##$alt##end## has been registered but now requires confirmation, to confirm do ##highlight##<pre>alts confirm $main##end## from $alt";
        }
        $this->bot->db->query("INSERT INTO #___alts (alt, main) VALUES ('$alt', '$main')");
        $this->bot->core("alts")->add_alt($main, $alt);
		$this->bot->core("alts")->create_caches();
        if ($this->bot->exists_module("points")) {
            $this->bot->core("points")->check_alts($main);
        }
        return "##highlight##$alt##end## has been registered as a new alt of ##highlight##$main##end##.";
    }


    /*
    Removes an alt form your alt list
    */
    function del_alt($name, $alt)
    {
        $name = ucfirst(strtolower($name));
        $alt = ucfirst(strtolower($alt));
        //Establish the main of the caller
        $main = $this->bot->core("alts")->main($name);
        //Check that we're not trying to register ourself as an alt
        if ($name == $alt && $name == $main) {
            return "##error##You cannot remove yourself as not being your own alt.##end##";
        }
        // Make sure $name and $alt match legal pattern for character names (only letters or numbers)
        if (!preg_match("/^[a-z0-9]+$/i", $name)) {
            return "##error##Illegal character name ##highlight##$name##end##!##end##";
        }
        if (!preg_match("/^[a-z0-9]+$/i", $name)) {
            return "##error##Illegal character name ##highlight##$alt##end##!##end##";
        }
        //Chech that alt is indeed an alt of the caller
        $alt = ucfirst(strtolower($alt));
        $main = ucfirst(strtolower($main));
        $result = $this->bot->db->select("SELECT main FROM #___alts WHERE alt = '$alt' AND main = '$main'");
        if (empty($result)) {
            return "##highlight##$alt##end## is not registered as an alt of ##highlight##$main##end##.";
        } else {
            $this->bot->db->query("DELETE FROM #___alts WHERE alt = '" . ucfirst(strtolower($alt)) . "'");
            $this->bot->core("alts")->del_alt($main, $alt);
			$this->bot->core("alts")->create_caches();
            return "##highlight##$alt##end## has been removed from ##highlight##$main##end##s alt-list.";
        }
    }


    function confirm($alt, $main)
    {
        $result = $this->bot->db->select("SELECT confirmed FROM #___alts WHERE alt = '$alt' AND main = '$main'");
        if (!empty($result)) {
            if ($result[0][0] == 0) {
                $this->bot->db->query("UPDATE #___alts SET confirmed = 1 WHERE main = '$main' AND alt = '$alt'");
                $this->bot->core("alts")->add_alt($main, $alt);
                if ($this->bot->exists_module("points")) {
                    $this->bot->core("points")->check_alts($main);
                }
                return "##highlight##$alt##end## has been confirmed as a new alt of ##highlight##$main##end##.";
            } else {
                return ("##highlight##$alt##end## is already a confirmed alt of ##highlight##$main##end##.");
            }
        } else {
            return "##error####highlight##$alt##end## is not registered as an alt of ##highlight##$main##end##.##end##";
        }
    }
}

?>
