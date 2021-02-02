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

// WS API
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

// REST API
define("DISCORD_API", "https://discordapp.com/api/v6");
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

    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
		$this->register_module("discord");
		$this->register_command("all", "discord", "SUPERADMIN");
		$this->register_command("all", "discordonline", "GUEST");
		$this->register_event("connect");
		$this->register_event("privgroup");
        $this->register_event("gmsg", "org");
		$this->register_event("buddy");
        $this->help['description'] = "Handles the Discord relay of the bot.";
        $this->help['command']['discord connect'] = "Tries relaying from/to the Discord channel.";
        $this->help['command']['discord disconnect'] = "Stops relaying from/to the Discord channel.";
        $this->help['notes'] = "The Discord relay is configured via settings, for all options check /tell <botname> <pre>settings discord. Discord side commands available are : is, online/sm, whois, uid, level/lvl/pvp";
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
				return $this->discord_online($name);
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
    function discord_online($name = "")
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
						$sent .= $color.$member['username']."##end## ";
					}
				}
			}	
			if (($name != "") && ($name != "c")) {
				$this->bot->send_tell($name,$sent);
			} else {
				if ($name == "") {
					$this->bot->send_output("", $sent,$this->bot->core("settings")->get("discord", "WhatChat"));
				}
			}	
		} else {
			if (($name != "") && ($name != "c")) {
				$this->bot->send_tell($name,"Discord relay isn't activated");
			} else {
				if ($name == "") {
					$this->bot->send_output("", "Discord relay isn't activated",$this->bot->core("settings")->get("discord", "WhatChat"));
				}
			}			
		}
	}
	
	/*
    WS ping called at discord_connect()
    */
    function discord_ping()
    {	
		$token = $this->bot->core("settings")->get("discord", "BotToken");
		$ws = new \SimpleDiscord\SimpleDiscord([
			"token" => $token,
			"debug" => 0 // 3 for test, 0 for prod
		]);
		$ws->ping();
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
			if (($name != "") && ($name != "c")) {
				$this->bot->send_tell($name,"Relaying from/to Discord channel");
			} else {
				if ($name == "") {
					$this->bot->send_output("", "Relaying from/to Discord channel",$this->bot->core("settings")->get("discord", "WhatChat"));
				}
			}
		} else {
			if (($name != "") && ($name != "c")) {
				$this->bot->send_tell($name,"Discord relay is already activated");
			} else {
				if ($name == "") {
					$this->bot->send_output("", "Discord relay is already activated",$this->bot->core("settings")->get("discord", "WhatChat"));
				}
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
			if (($name != "") && ($name != "c")) {
				$this->bot->send_tell($name,"Discord relay has been stopped");
			} else {
				if ($name == "") {
					$this->bot->send_output("", "Discord relay has been stopped",$this->bot->core("settings")->get("discord", "WhatChat"));
				}
			}
		} else {
			if (($name != "") && ($name != "c")) {
				$this->bot->send_tell($name,"Discord relay was already stopped");
			} else {
				if ($name == "") {
					$this->bot->send_output("", "Discord relay was already stopped",$this->bot->core("settings")->get("discord", "WhatChat"));
				}
			}			
		}
	}	
	
	/*
	Replace odd letters by their lowercase basis
	*/
	function cleanString($msg) {
		$patterns[0] = '/%(E1|E2|E0|E5|E4|E3|C0|C1|C2|C3|C4|C5|C6)/'; // áâàåäãÀÁÂÃÄÅÆ
		$patterns[1] = '/%(F0|E9|EA|E8|EB|C8|C9|CA|CB)/'; // ðéêèëÈÉÊË
		$patterns[2] = '/%(ED|EE|EC|EF|CC|CD|CE|CF)/'; // íîìïÌÍÎÏ
		$patterns[3] = '/%(F3|F4|F2|F8|F0|F5|F6|D2|D3|D4|D5|D6|D8)/'; // óôòøðõöÒÓÔÕÖØ
		$patterns[4] = '/%(FA|FB|F9|FC|D9|DA|DB|DC)/'; // úûùüÙÚÛÜ
		$patterns[5] = '/%E6/'; // æ
		$patterns[6] = '/%(E7|C7)/'; // çÇ
		$patterns[7] = '/%DF/'; // ß
		$patterns[8] = '/%(FD|FF|DD)/'; // ýÿÝ
		$patterns[9] = '/%(F1|D1)/';// ñÑ
		$patterns[10] = '/%(DE|FE)/';// Þþ
		$replacements[0] = 'a';
		$replacements[1] = 'e';
		$replacements[2] = 'i';
		$replacements[3] = 'o';
		$replacements[4] = 'u';
		$replacements[5] = 'ae';
		$replacements[6] = 'c';
		$replacements[7] = 'ss';
		$replacements[8] = 'y';		
		$replacements[9] = 'n';		
		$replacements[10] = 'b';						
		//return urldecode(preg_replace($patterns, $replacements, urlencode(strip_tags($msg)))); // replaced by utf8_encode()
		$msg = str_replace("&gt;", ">", strip_tags(utf8_encode($msg)));
		$msg = str_replace("&lt;", "<", $msg);
		$msg = str_replace("&amp;", "&", $msg);		
		return $msg;
	}	
	
    /*
    This gets called on a msg in the guild channel
    */
    function gmsg($name, $group, $msg)
    {	
		if ($this->bot->core("settings")->get("discord", "DiscordRelay")) {
			if (strtolower($this->bot->core("settings")->get("discord", "WhatChat"))=="gc" || strtolower($this->bot->core("settings")->get("discord", "WhatChat"))=="both" ) {		
				$channel = $this->bot->core("settings")->get("discord", "ChannelId");
				$token = $this->bot->core("settings")->get("discord", "BotToken");
				if ($channel>0 && $token!="" && substr($msg,0,1)!=$this->bot->commpre) {
					$route = "/channels/{$channel}/messages";
					$sent = "[Orgchat] ".ucfirst($name).": ".$this->cleanString($msg);
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
    This gets called on a msg in the private group
    */
    function privgroup($name, $msg)
    {
		if ($this->bot->core("settings")->get("discord", "DiscordRelay")) {
			if (strtolower($this->bot->core("settings")->get("discord", "WhatChat"))=="pgroup" || strtolower($this->bot->core("settings")->get("discord", "WhatChat"))=="both" ) {
				$channel = $this->bot->core("settings")->get("discord", "ChannelId");
				$token = $this->bot->core("settings")->get("discord", "BotToken");
				if ($channel>0 && $token!="" && substr($msg,0,1)!=$this->bot->commpre) {
					$route = "/channels/{$channel}/messages";
					$sent = "[Privchat] ".ucfirst($name).": ".$this->cleanString($msg);
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
									$sent = "[Discord] ".ucfirst($msg['author']['username']).": ".strip_tags(utf8_decode($msg['content']));
									$this->bot->send_output("", $sent,$this->bot->core("settings")->get("discord", "WhatChat"));
								} else {
									$com = explode(" ", $msg['content'], 2);
									Switch ($com[0]) {
										case $this->bot->commpre . 'is':
											$sent = $this->discord_is($msg['content']);
											Break;
										case $this->bot->commpre . 'online':
										case $this->bot->commpre . 'sm':
											$sent = $this->discord_sm($msg['content']);
											Break;
										case $this->bot->commpre . 'whois':
											$sent = $this->discord_whois($msg['content']);
											Break;
										case $this->bot->commpre . 'uid':
											$sent = $this->discord_uid($msg['content']);
											Break;
										case $this->bot->commpre . 'level':
										case $this->bot->commpre . 'lvl':
										case $this->bot->commpre . 'pvp':
											$sent = $this->discord_lvl($msg['content']);
											Break;										
										Default:
											$sent = "";
											Break;
									}
									if($sent!="") {
										$route = "/channels/{$channel}/messages";
										$sent = "[Gamebot] ".$this->bot->botname.": ".$this->cleanString($sent);
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
            if (!$this->bot->core('player')->id($info[1])) {
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
				$sent = "[Gamebot] ".$this->bot->botname.": ".$this->cleanString($sent);
				$data = array("content" => $sent);
				$result = discord_post($route, $token, $data);
				if(isset($result['message'])&& isset($result['code'])) {
					$this->bot->log("DISCORD", "ERROR", "Odd configuration : do !settings discord to fix");
				}							
            }
        }
    }	
	
    /*
    * Gets called when someone does !uid
    */
    function discord_uid($msg)
    {
		$sent = "";
		if (preg_match("/^" . $this->bot->commpre . "uid ([a-zA-Z0-9]{4,25})$/i", $msg, $info)) {
            $info[1] = ucfirst(strtolower($info[1]));
            $sent = $info[1] . ": " . $this->bot->core('player')->id($info[1]);
		}
		return $sent;		
	}
	
    /*
    * Gets called when someone does !whois
    */
    function discord_whois($msg)
    {
		$sent = "";
		if (preg_match("/^" . $this->bot->commpre . "whois (.+)$/i", $msg, $info)) {
            $info[1] = ucfirst(strtolower($info[1]));
			if (!$this->bot->core('player')->id($info[1])) {
				$sent = "Player " . $info[1] . " does not exist.";
			} else {
				$sent = $this->whois_player($info[1]);
			}		
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
            $at = "(AT " . $who["at_id"] . " - " . $who["at"] . ") ";
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
			$sent = $this->bot->commands["tell"]["level"]->get_level($info[2]);
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
            "SELECT DISTINCT(nickname) FROM #___online WHERE " . $this->bot
                ->core("online")
                ->otherbots() . " AND " . $channels . " ORDER BY nickname ASC"
        );
        if (empty($online)) {
            $sent = "Nobody online on notify!";
        } else {
            $sent = count($online) . " online in game ... ";
            $list = array();
            foreach ($online as $name) {
                $list[] = $name[0];
            }
            $sent .= implode(" ", $list);
        }
		return $sent;		
    }	
	
}

?>