<?php
/*
* DiscordRelay.php - Discord Relay.
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
/*
Add a "_" at the beginning of the file (_DiscordRelay.php) if you do not want it to be loaded.
*/

if ((float)phpversion() > 6.9) {
	// WS API working with PHP 7.0+
	$results = array();
	function autoLoader($dir, &$results, $exclude='') {
		$files = scandir($dir);
		foreach ($files as $key => $value) {
			$path = realpath($dir . DIRECTORY_SEPARATOR . $value);
			if (!is_dir($path) && substr($path, -4)=='.php' && $path!=$exclude) {
				$slash = str_replace("\\","/",$path);
				$explode = explode($dir,$slash);
				$results[] = $dir.$explode[1];
			}
		}
	}
	$results[] = 'Sources/Discord/log/Psr/Log/LoggerInterface.php';
	autoLoader('Sources/Discord/log/Psr/Log/',$results,'LoggerInterface.php');
	$results[] = 'Sources/Discord/websocket-php/lib/Message/Message.php';
	autoLoader('Sources/Discord/websocket-php/lib/Message/',$results,'Message.php');
	$results[] = 'Sources/Discord/websocket-php/lib/Exception.php';
	autoLoader('Sources/Discord/websocket-php/lib/',$results,'Exception.php');
	autoLoader('Sources/Discord/SimpleDiscord/src/RestClient/Resources/',$results);
	$results[] = 'Sources/Discord/SimpleDiscord/src/RestClient/RestClient.php';
	autoLoader('Sources/Discord/SimpleDiscord/src/DiscordSocket/',$results);
	$results[] = 'Sources/Discord/SimpleDiscord/src/SimpleDiscord.php';
	foreach ($results as $key) {
		include_once($key);
	}
}

// REST API https://discord.com/developers/docs/reference#api-reference
define("DISCORD_API", "https://discord.com/api/v10");
require_once('Sources/Discord/discord-php-kiss/discord_curl.php');

$discordrelay = new DiscordRelay($bot);
/*
The Class itself...
*/
class DiscordRelay extends BaseActiveModule
{
	
	var $lastmsg = 0;
	var $lastcheck = 0;
	var $crondelay = "2sec";
	var $is;
	var $note = "Discord side commands available are : help, tara, viza, is, online/sm, whois, alts, level/lvl/pvp";

    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
		$this->register_module("discord");
		$this->register_command("all", "discord", "SUPERADMIN");
		$this -> register_alias("discord", "dc");
		$this->register_command("all", "discordonline", "GUEST");
		$this -> register_alias("discordonline", "dco");
		$this->register_event("connect");
		$this->register_event("privgroup");
        $this->register_event("gmsg", "org");
		$this->register_event("buddy");
        $this->help['description'] = "Handles the Discord relay of the bot.";
        $this->help['command']['discord connect'] = "Tries relaying from/to the Discord channel.";
        $this->help['command']['discord disconnect'] = "Stops relaying from/to the Discord channel.";
        $this->help['notes'] = "The Discord relay is configured via settings, for all options check /tell <botname> <pre>settings discord. ".$this->note;
        $this->bot->core("settings")
            ->create("discord", "DiscordRelay", false, "Should the bot be relaying from/to Discord server ?", "On;Off", true);	
        $this->bot->core("settings")
            ->create("discord", "ServerId", "", "Discord server ID for the widget online checks ?");			
        $this->bot->core("settings")
            ->create("discord", "ChannelId", "", "Discord channel ID that will be relayed from/to ?");
        $this->bot->core("settings")
            ->create("discord", "BotToken", "", "Bot Token obtain from https://discord.com/developers/applications ?");
        $this->bot->core("settings")
            ->create("discord", "WhatChat", "both", "Which channel(s) should be relayed into Discord and vice versa ?", "gc;pgroup;both");
        $this->bot->core("settings")
            ->create(
                "discord",
                "Announce",
                true,
                "Should we announce logons and logoffs as controlled by the Logon module to Discord?"
            );	
        $this->bot->core("settings")
            ->create(
                "discord",
                "ItemRef",
                "AOItems",
                "Should AOItems or AUNO be used for links in item refs?",
                "AOItems;AUNO"
            );
        $this->bot->core("settings")
            ->create("Discord", "ignoreSyntax", "", "Is there a first letter that should make the bot ignore messages for Discord relay (leave empty if none) ?");
			
	}
	
    /*
    The command handling
    */	
    function command_handler($name, $msg, $source)
    {
        $com = $this->parse_com($msg);
        switch (strtolower($com['com'])) {
            case 'discord':
                switch (strtolower($com['sub'])) {
                    case 'connect':
                        return $this->discord_connect($name);
                        break;	
                    case 'disconnect':
                        return $this->discord_disconnect($name);
                        break;						
                    case 'test':
                        return $this->discord_test();
                        break;										
				}
			case 'discordonline':
				return $this->discord_online($name, "output");
				break;					
		}
	}

	/*
    Called at bot startup
    */
    function connect()
    {	
		if ($this->bot->core("settings")->get("discord", "DiscordRelay")) {
			$this->bot->core('settings')->save('discord', 'DiscordRelay', FALSE);
			$this->discord_connect();
		}
	}	

    /*
    Discord online check
    */	
    function discord_online($name = "", $output= "output")
    {
		if ($this->bot->core("settings")->get("discord", "DiscordRelay")) {
			$guild = $this->bot->core("settings")->get("discord", "ServerId");
			$token = $this->bot->core("settings")->get("discord", "BotToken");			
			if ($guild>0 && $token!="") {
				$route = "/guilds/{$guild}/widget.json";
				$result = discord_get($route, $token);
				if(isset($result['message'])&& isset($result['code'])) {
					$this->bot->log("DISCORD", "ERROR", "Bad configuration : check discord widget + !settings discord");
				} else {
					$sent = $result['presence_count']." online in discord ... ";
					foreach ($result['members'] as $member) {			
						switch ($member['status']) {
							case 'online':
								$color = "##green##";
								break;	
							case 'idle':
								$color = "##orange##";
								break;						
							case 'dnd':
								$color = "##red##";
								break;			
							default:
								$color = "##white##";
								break;
						}												
						$sent .= $color.mb_convert_encoding($member['username'], 'ISO-8859-1', 'UTF-8')."##end## ";
					}
				}
			}	
			if ($name != "") {
				if($output=="output") $this->bot->send_tell($name,$sent);
				else return $sent;
			} else {
				if($output=="output") $this->bot->send_output("", $sent,$this->bot->core("settings")->get("discord", "WhatChat"));
				else return $sent;
			}	
		} else {
			if ($name != "") {
				if($output=="output") $this->bot->send_tell($name,"Discord relay isn't activated");
				else return "Discord relay isn't activated";
			} else {
				if($output=="output") $this->bot->send_output("", "Discord relay ain't activated",$this->bot->core("settings")->get("discord", "WhatChat"));
				else return "Discord relay ain't activated";
			}			
		}
	}
	
	/*
    WS ping called at discord_connect() for PHP 7.0+
    */
    function discord_ping()
    {	
		if ((float)phpversion() > 6.9) {
			$token = $this->bot->core("settings")->get("discord", "BotToken");
			$ws = new \SimpleDiscord\SimpleDiscord([
				"token" => $token,
				"debug" => 0 // 3 for test, 0 for prod
			]);
			$ws->ping();
		}
	}	
	
    /*
    Manual/Auto relay activation
    */	
    function discord_connect($name = "")
    {	
		if (!$this->bot->core("settings")->get("discord", "DiscordRelay")) {
			$this->discord_ping();
			$this->bot->core('settings')->save('discord', 'DiscordRelay', TRUE);
			$this->register_event("cron", $this->crondelay);
			if ($name != "") {
				$this->bot->send_tell($name,"Relaying from/to Discord channel");
			} else {
				$this->bot->send_output("", "Relaying from/to Discord channel",$this->bot->core("settings")->get("discord", "WhatChat"));
			}
		} else {
			if ($name != "") {
				$this->bot->send_tell($name,"Discord relay is already activated");
			} else {
				$this->bot->send_output("", "Discord relay is already activated",$this->bot->core("settings")->get("discord", "WhatChat"));
			}			
		}
	}
	
    /*
    Manual relay desactivation
    */	
    function discord_disconnect($name = "")
    {	
		if ($this->bot->core("settings")->get("discord", "DiscordRelay")) {
			$this->unregister_event("cron", $this->crondelay);
			$this->bot->core('settings')->save('discord', 'DiscordRelay', FALSE);
			if ($name != "") {
				$this->bot->send_tell($name,"Discord relay has been stopped");
			} else {
				$this->bot->send_output("", "Discord relay has been stopped",$this->bot->core("settings")->get("discord", "WhatChat"));
			}
		} else {
			if ($name != "") {
				$this->bot->send_tell($name,"Discord relay was already stopped");
			} else {
				$this->bot->send_output("", "Discord relay was already stopped",$this->bot->core("settings")->get("discord", "WhatChat"));
			}			
		}
	}	
	
    /*
    This gets called on a msg in the guild channel
    */
    function gmsg($name, $group, $msg)
    {	
		$ignore = $this->bot->core("settings")->get("Discord", "ignoreSyntax");
		if($ignore!=""&&substr($msg,0,1)==$ignore) return false;	
		if ($this->bot->core("settings")->get("discord", "DiscordRelay")) {
			if (strtolower($this->bot->core("settings")->get("discord", "WhatChat"))=="gc" || strtolower($this->bot->core("settings")->get("discord", "WhatChat"))=="both" ) {		
				$channel = $this->bot->core("settings")->get("discord", "ChannelId");
				$token = $this->bot->core("settings")->get("discord", "BotToken");
				if ($channel>0 && $token!="" && substr($msg,0,1)!=$this->bot->commpre) {
					$route = "/channels/{$channel}/messages";
					$form = $this->strip_formatting($msg);			
					$sent = "[Guildchat] ".ucfirst($name).": ".$this->bot->core("tools")->cleanString($form,0);
					$data = array("content" => $sent);
					$result = discord_post($route, $token, $data);
					if(isset($result['message'])&& isset($result['code'])) {
						$this->bot->log("DISCORD", "ERROR", "Erroneous configuration : do !settings discord to fix");
					}					
				}
			}
		}
	}

    /*
    This gets called just below to clean text of msg
    */	
    function strip_formatting($msg)
    {
        if (strtolower(
                $this->bot->core("settings")
                    ->get("discord", "ItemRef")
            ) == "auno"
        ) {
            $rep = "http://auno.org/ao/db.php?id=\\1&id2=\\2&ql=\\3";
        } else {
            $rep = "http://aoitems.com/item/\\1/\\2/\\3";
        }
        $msg = preg_replace(
            "/<a href=\"itemref:\/\/([0-9]*)\/([0-9]*)\/([0-9]*)\">(.*)<\/a>/iU",
            "\\4" . " " . "(" . $rep . ")",
            $msg
        );
        $msg = preg_replace(
            "/<a style=\"text-decoration:none\" href=\"itemref:\/\/([0-9]*)\/([0-9]*)\/([0-9]*)\">(.*)<\/a>/iU",
            "\\4" . " " . "(" . $rep . ")",
            $msg
        );
		$msg = preg_replace("/<a href='user:\/\/(.+)\'>/isU", "", $msg);
		$msg = preg_replace("/<a href=\"user:\/\/(.+)\">/isU", "", $msg);
        $msg = preg_replace("/<a href=\"(.+)\">/isU", "\\1", $msg);
        $msg = preg_replace("/<a style=\"text-decoration:none\" href=\"(.+)\">/isU", "\\1", $msg);
        $msg = preg_replace("/<\/a>/iU", "", $msg);
        $msg = preg_replace("/<font(.+)>/iU", "", $msg);
        $msg = preg_replace("/<\/font>/iU", "", $msg);
        return $msg;
    }	
	
    /*
    This gets called by all various modules to send alerts
    */
    function disc_alert($msg, $chan="")
    {
		if ($this->bot->core("settings")->get("discord", "DiscordRelay")) {
			if($chan!=""&&$chan!=" ") {
				$channel = $chan;
			} else {
				$channel = $this->bot->core("settings")->get("discord", "ChannelId");				
			}
			$token = $this->bot->core("settings")->get("discord", "BotToken");
			if ($channel>0 && $token!="") {
				$route = "/channels/{$channel}/messages";
				$form = $this->strip_formatting($msg);
				$sent = $this->bot->core("tools")->cleanString($form,0);
				$data = array("content" => $sent);
				$result = discord_post($route, $token, $data);
				if(isset($result['message'])&& isset($result['code'])) {
					$this->bot->log("DISCORD", "ERROR", "Missed configuration : do !settings discord to fix");
				}				
			}
		}
	}
	
    /*
    This gets called on a msg in the private group
    */
    function privgroup($name, $msg)
    {
		$ignore = $this->bot->core("settings")->get("Discord", "ignoreSyntax");
		if($ignore!=""&&substr($msg,0,1)==$ignore) return false;		
		if ($this->bot->core("settings")->get("discord", "DiscordRelay")) {
			if (strtolower($this->bot->core("settings")->get("discord", "WhatChat"))=="pgroup" || strtolower($this->bot->core("settings")->get("discord", "WhatChat"))=="both" ) {
				$channel = $this->bot->core("settings")->get("discord", "ChannelId");
				$token = $this->bot->core("settings")->get("discord", "BotToken");
				if ($channel>0 && $token!="" && substr($msg,0,1)!=$this->bot->commpre) {
					$route = "/channels/{$channel}/messages";
					$form = $this->strip_formatting($msg);
					$sent = "[Privchat] ".ucfirst($name).": ".$this->bot->core("tools")->cleanString($form,0);
					$data = array("content" => $sent);
					$result = discord_post($route, $token, $data);
					if(isset($result['message'])&& isset($result['code'])) {
						$this->bot->log("DISCORD", "ERROR", "Misconfiguration : do !settings discord to fix");
					}
				}
				
			}
		}
	}
	
    /*
    Cron reads Discord channel 30x/min (hard limit: 1000x/min)
    */	
    function cron()
    {
		if ($this->bot->core("settings")->get("discord", "DiscordRelay")) {
			$channel = $this->bot->core("settings")->get("discord", "ChannelId");
			$token = $this->bot->core("settings")->get("discord", "BotToken");
			if ($channel>0 && $token!="") {
				$route = "/channels/{$channel}/messages";
				if ($this->lastmsg>0) { $route = "/channels/{$channel}/messages?after=".$this->lastmsg; }
				$result = discord_get($route, $token);
				if ($this->lastcheck==0) $this->lastcheck = date('Y-m-d').'T'.date("H:i:s").'.000000+00:00';
				$invert = array_reverse($result);
				if(isset($invert['message'])&& isset($invert['code'])) {
					$this->bot->log("DISCORD", "ERROR", "Wrong configuration : do !settings discord to fix");
				} else {
					foreach ($invert as $msg) {
							if ($msg['id']>$this->lastmsg) { $this->lastmsg = $msg['id']; }
							if ($msg['timestamp']>$this->lastcheck && !isset($msg['author']['bot'])) {
								if(substr($msg['content'],0,1)!=$this->bot->commpre) {
									$sent = "[Discord] ".ucfirst($msg['author']['username']).": ".strip_tags(mb_convert_encoding($msg['content'], 'ISO-8859-1', 'UTF-8'));
									$this->bot->send_output("", $sent,$this->bot->core("settings")->get("discord", "WhatChat"));
								} else {
									$com = explode(" ", $msg['content'], 2);
									Switch ($com[0]) {
										case $this->bot->commpre . 'is':
											$sent = $this->discord_is($msg['content']);
											Break;
										case $this->bot->commpre . 'alts':
											$sent = $this->discord_alts($msg['content']);
											Break;											
										case $this->bot->commpre . 'tara':
											$sent = $this->discord_tara($msg['content']);
											Break;
										case $this->bot->commpre . 'viza':
											$sent = $this->discord_viza($msg['content']);
											Break;											
										case $this->bot->commpre . 'online':
										case $this->bot->commpre . 'sm':
											$sent = $this->discord_sm($msg['content']);
											Break;
										case $this->bot->commpre . 'whois':
											$sent = $this->discord_whois($msg['content']);
											Break;
										case $this->bot->commpre . 'level':
										case $this->bot->commpre . 'lvl':
										case $this->bot->commpre . 'pvp':
											$sent = $this->discord_lvl($msg['content']);
											Break;
										Default:
											$sent = $this->note;
											Break;
									}
									if($sent!="") {
										$route = "/channels/{$channel}/messages";
										$sent = "[Gamebot] ".$this->bot->botname.": ".$this->bot->core("tools")->cleanString($sent,0);
										$data = array("content" => $sent);
										$result = discord_post($route, $token, $data);
										if(isset($result['message'])&& isset($result['code'])) {
											$this->bot->log("DISCORD", "ERROR", "False configuration : do !settings discord to fix");
										}								
									}
								}
							}
					}
				}
				$this->lastcheck = date('Y-m-d').'T'.date("H:i:s").'.000000+00:00';
			}
		}
    }
	
    /*
    * Gets called when someone does !is
    */
    function discord_is($msg)
    {
		$sent = "";
		if (preg_match("/^" . $this->bot->commpre . "is ([a-zA-Z0-9]{4,25})$/i", $msg, $info)) {
			$info[1] = ucfirst(strtolower($info[1]));
            if ($this->bot->core('player')->id($info[1]) instanceof BotError) {
                $sent = "Player " . $info[1] . " does not exist.";
            } else {
                if ($info[1] == ucfirst(strtolower($this->bot->botname))) {
                    $sent = "I'm online!";
                } else {
                    if ($this->bot->core("chat")->buddy_exists($info[1])) {
                        if ($this->bot->core("chat")->buddy_online($info[1])) {
                            $sent = $info[1] . " is online.";
                        } else {
                            $sent= $info[1] . " is offline.";
                        }
                    } else {
						$this->is[$info[1]] = "discord_is";
                        $this->bot->core("chat")->buddy_add($info[1]);
					}
                }
            }
		} else {
			$sent = "Please enter a valid name.";
		}
		return $sent;
	}
	
    /*
    * Gets called when someone does !alts
    */
    function discord_alts($msg)
    {
		$sent = "";
		if (preg_match("/^" . $this->bot->commpre . "alts ([a-zA-Z0-9]{4,25})$/i", $msg, $info)) {
			$info[1] = ucfirst(strtolower($info[1]));
            if ($this->bot->core('player')->id($info[1]) instanceof BotError) {
                $sent = "Player " . $info[1] . " does not exist.";
            } else {
				$main = $this->bot->core("alts")->main($info[1]);
				$list = $this->bot->core("alts")->get_alts($main);
				if(count($list)>0) $sent = $main."'s alts : ".implode(" ", $list);
				else $sent = $main." has no alts defined!";
            }
		} else {
			$sent = "Please enter a valid name.";
		}
		return $sent;
	}	
	
    /*
    This gets called if a buddy logs on/off
    */
    function buddy($name, $msg)
    {
        if ($msg == 1 || $msg == 0) {
            // Only handle this if connected to Discord server
            if (!$this->bot->core("settings")->get("discord", "DiscordRelay")) {
                return;
            }

            if ((!$this->bot->core("notify")
                    ->check($name))
                && isset($this->is[$name])
            ) {
                if ($msg == 1) {
                    $sent = $name . " is online.";
                } else {
                    $sent = $name . " is offline.";
                }			
                unset($this->is[$name]);				
				$channel = $this->bot->core("settings")->get("discord", "ChannelId");
				$token = $this->bot->core("settings")->get("discord", "BotToken");				
				$route = "/channels/{$channel}/messages";
				$sent = "[Gamebot] ".$this->bot->botname.": ".$this->bot->core("tools")->cleanString($sent,0);
				$data = array("content" => $sent);
				$result = discord_post($route, $token, $data);
				if(isset($result['message'])&& isset($result['code'])) {
					$this->bot->log("DISCORD", "ERROR", "Odd configuration : do !settings discord to fix");
				}							
            }
        }
    }	
	
    /*
    * Gets called when someone does !whois
    */
    function discord_whois($msg)
    {
		$sent = "";
		if (preg_match("/^" . $this->bot->commpre . "whois (.+)$/i", $msg, $info)) {
            $info[1] = ucfirst(strtolower($info[1]));
			if ($this->bot->core('player')->id($info[1]) instanceof BotError) {
				$sent = "Player " . $info[1] . " does not exist.";
			} else {
				$sent = $this->whois_player($info[1]);
			}		
		} else {
			$sent = "Please enter a valid name.";
		}
		return $sent;		
	}	
	
    /*
    * Gets called when someone does !tara
    */
    function discord_tara($msg)
    {
		if ($this->bot->exists_module("taraviza")) {
			$sent = $this->bot->core("taraviza")->show_tara("user");
		} else {
			$sent = "No Tarasque/Cameloot timer found.";
		}
		return $sent;		
	}	

    /*
    * Gets called when someone does !viza
    */
    function discord_viza($msg)
    {
		if ($this->bot->exists_module("taraviza")) {
			$sent = $this->bot->core("taraviza")->show_viza("user");
		} else {
			$sent = "No Vizaresh/Gauntlet timer found.";
		}
		return $sent;		
	}		

    /*
    * Gets called by discord_whois()
    */	
    function whois_player($name)
    {
        $who = $this->bot->core("whois")->lookup($name);
        if (!$who) {
            $result = "Couldn't find infos about " . $info[1] . " ...";
        } elseif (!($who instanceof BotError)) {
			if (strtolower($this->bot->game) == 'ao') {
				$at = "(AT " . $who["at_id"] . " - " . $who["at"] . ") ";
			}
            $result = "\"" . $who["nickname"] . "\"";
            if (!empty($who["firstname"]) && ($who["firstname"] != "Unknown")) {
                $result = $who["firstname"] . " " . $result;
            }
            if (!empty($who["lastname"]) && ($who["lastname"] != "Unknown")) {
                $result .= " " . $who["lastname"];
            }
            if (strtolower($this->bot->game) == 'ao') {
                $result .= " is a level " . $who["level"] . " " . $at . "" . $who["gender"] . " " . $who["breed"] . " ";
                $result .= $who["profession"] . ", " . $who["faction"];
            } else {
                $result .= " is a level " . $who["level"] . " ";
                $result .= $who["class"];
            }
            if (!empty($who["rank"])) {
                $result .= ", " . $who["rank"] . " of " . $who["org"] . "";
            }
            if ($this->bot->core("settings")->get("Whois", "Details") == true) {
                if ($this->bot->core("settings")
                        ->get("Whois", "ShowMain") == true
                ) {
                    $main = $this->bot->core("alts")->main($name);
                    if ($main != $name) {
                        $result .= " :: Alt of " . $main;
                    }
                }
            }
        } else {
            $result = $who;
        }
        return $result;
    }	
	
    /*
    Level command for discord
    */
    function discord_lvl($msg)
    {
		$sent = "";
        if (preg_match("/^" . $this->bot->commpre . "([a-zA-Z]{3,5}) ([0-9]{1,3})$/i", $msg, $info)) {
			if (strtolower($this->bot->game) == 'ao') $sent = $this->bot->commands["tell"]["level"]->get_level($info[2]);
		}
		return $sent;		
    }	
	
    /*
    * Gets called when someone does !online
    */
    function discord_sm()
    {
		$sent = "";
        $channels = "";
        if (strtolower(
                $this->bot->core("settings")
                    ->get("discord", "WhatChat")
            ) == "both"
        ) {
            $channels = "(status_pg = 1 OR status_gc = 1)";
        } elseif (strtolower(
                $this->bot->core("settings")
                    ->get("discord", "WhatChat")
            ) == "gc"
        ) {
            $channels = "status_gc = 1";
        } elseif (strtolower(
                $this->bot->core("settings")
                    ->get("discord", "WhatChat")
            ) == "pgroup"
        ) {
            $channels = "status_pg = 1";
        }	
        $online = $this->bot->db->select(
            "SELECT DISTINCT(nickname), botname FROM #___online WHERE " . $this->bot
                ->core("online")
                ->otherbots() . " AND " . $channels . " ORDER BY nickname ASC"
        );				
        if (empty($online)) {
            $sent = "Nobody online on notify!";
        } else {
            $orglist = array();
			$othlist = array();
            foreach ($online as $name) {
				if($name[1] == $this->bot->botname) {
					$orglist[] = $name[0];
				}
            }
			foreach ($online as $name) {
				if($name[1] != $this->bot->botname && !in_array($name[0], $orglist) ) {
					$othlist[] = $name[0];
				}				
			}		
            $sent = count($orglist) . " online in org + ". count($othlist) . " others : ";
			if(count($orglist)>0&&count($othlist)>0) { $spacer = " + "; } else { $spacer = " "; }
            $sent .= implode(" ", $orglist). $spacer . implode(" ", $othlist);
        }
		return $sent;		
    }	
	
}

?>