<?php
/* Announcer.php
* From work by Doctorhyde@RK5, Alreadythere@RK2
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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

$announcer = new Announcer($bot);

class Announcer extends BaseActiveModule
{
	var $bot;

	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		
		$this->register_event("tells");
		$this->register_command('all', 'register', 'SUPERADMIN');
		$this->register_command('all', 'subscribe', 'SUPERADMIN');
		$this->register_command('all', 'unregister', 'SUPERADMIN');
		$this->register_command('all', 'unsubscribe', 'SUPERADMIN');
		
		$this -> bot -> core("colors") -> define_scheme("Announcer", "botname", "bluesilver");
		$this -> bot -> core("colors") -> define_scheme("Announcer", "sender", "bluegray");
		$this -> bot -> core("colors") -> define_scheme("Announcer", "type", "brightgreen");
		$this -> bot -> core("colors") -> define_scheme("Announcer", "text", "lightyellow");
		
		$this -> help['description'] = 'Allows to register/unregister to one or more BotNet(s) and then subscribe/unsubscribe to specific channel(s).';
		$this -> help['command']['register <botname>']="Registers your bot into given BotNet.";
		$this -> help['command']['subscribe <botname> <channel>']="Subscribes your bot from given BotNet's channel.";
		$this -> help['command']['unregister <botname>']="Unregisters your bot from given BotNet.";
		$this -> help['command']['unsubscribe <botname> <channel>']="Unsubscribes your bot from given BotNet's channel.";
		$this->help['notes'] = "Depending on Botnet(s) specificities, you may have to log into your bot character to make certain commands/subscriptions directly yourself.";
		
		$this->bot->core("settings")->create("Announcer", "OrgOn", false, "Should the bot be relaying from Botnet(s) to guild channel ?", "On;Off");
		$this->bot->core("settings")->create("Announcer", "ProfanState", false, "Should the bot filter profanity word(s) based on its filter ?", "On;Off");
		$this->bot->core("settings")->create("Announcer", "ProfanFilter", "", "What profanity word(s) should the bot filter (comma separated list of words) ?");
		$this->bot->core("settings")->create("Announcer", "IgnoreList", "", "What sender name(s) should the bot ignore (comma separated list of names) ?");
		$this->bot->core("settings")->create("Announcer", "PrivOn", false, "Should the bot be relaying from Botnet(s) to private channel ?", "On;Off");
		$this->bot->core("settings")->create("Announcer", "BotName", "", "What is/are Botnet(s) main BotName(s) (please better edit through register/unregister commands) ?");
        $this->bot->core("settings")
            ->create("Announcer", "AlertDisc", false, "Do we alert Discord of Botnet(s) spam ?");
        $this->bot->core("settings")
            ->create("Announcer", "DiscChanId", "", "What Discord ChannelId in case we separate Botnet(s) spam from main Discord channel (leave empty for all in main channel) ?");
        $this->bot->core("settings")
            ->create("Announcer", "AlertIrc", false, "Do we alert Irc of Botnet(s) spam ?");		
		
	}

	function command_handler($name, $msg, $origin) {
		if (preg_match("/^register (.+)$/i", $msg, $info))
			return $this->register(ucfirst(strtolower($info[1])));
		elseif (preg_match("/^subscribe (.+) (.+)$/i", $msg, $info))
			return $this->subscribe(ucfirst(strtolower($info[1])), strtolower($info[2]));		
		elseif (preg_match("/^unregister (.+)$/i", $msg, $info))
			return $this->unregister(ucfirst(strtolower($info[1])));
		elseif (preg_match("/^unsubscribe (.+) (.+)$/i", $msg, $info))
			return $this->unsubscribe(ucfirst(strtolower($info[1])), strtolower($info[2]));			
		else return false;
	}

	function register($botnet) {		
		if (!$this->bot->core('player')->id($botnet) instanceof BotError) {
			$mains = explode(",",$this->bot->core("settings")->get("Announcer", "BotName"));
			$found = false;
			foreach($mains as $main) {
				if($main==$botnet) $found = true;
			}
			if(!$found) {
				$this->bot->core("settings")->save("Announcer", "BotName", $this->bot->core("settings")->get("Announcer", "BotName").",".$botnet);
			}			
			$this->bot->send_tell($botnet, "register", 1, false);
			return "Register command sent to ##highlight##$botnet##end##";
		} else {
			return "##error##Character ##highlight##$botnet##end## does not exist.##end##";
		}		
	}
	
	function subscribe($botnet, $channel) {	
		if (!$this->bot->core('player')->id($botnet) instanceof BotError) {
			$mains = explode(",",$this->bot->core("settings")->get("Announcer", "BotName"));
			$found = false;
			foreach($mains as $main) {
				if($main==$botnet) $found = true;
			}
			if($found) {
				if($botnet=="Darknet") $this->bot->send_tell($botnet, "channels add ".$channel, 1, false);
				else $this->bot->send_tell($botnet, "subscribe ".$channel, 1, false);
				return "Subscribe command sent to ##highlight##$botnet##end##";
			} else {
				return "##error##We are not registered to ##highlight##$botnet##end## yet.##end##";
			}
		} else {
			return "##error##Character ##highlight##$botnet##end## does not exist.##end##";
		}
	}

	function unregister($botnet) {
		if (!$this->bot->core('player')->id($botnet) instanceof BotError) {
			$mains = explode(",",$this->bot->core("settings")->get("Announcer", "BotName"));
			$found = false;
			foreach($mains as $main) {
				if($main==$botnet) $found = true;
			}				
			if($found) {
				$new = str_replace($botnet.",", "", $this->bot->core("settings")->get("Announcer", "BotName"));
				$new = str_replace(",".$botnet, "", $new);
				$this->bot->core("settings")->save("Announcer", "BotName", str_replace($botnet, "", $new));
			}						
			$this->bot->send_tell($botnet, "unregister", 1, false);
			return "Unregister command sent to ##highlight##$main##end##";
		} else {
			return "##error##Character ##highlight##$botnet##end## does not exist.##end##";
		}
	}
	
	function unsubscribe($botnet, $channel) {		
		if (!$this->bot->core('player')->id($botnet) instanceof BotError) {
			$mains = explode(",",$this->bot->core("settings")->get("Announcer", "BotName"));
			$found = false;
			foreach($mains as $main) {
				if($main==$botnet) $found = true;
			}
			if($found) {
				if($botnet=="Darknet") $this->bot->send_tell($botnet, "channels rem ".$channel, 1, false);
				else $this->bot->send_tell($botnet, "unsubscribe ".$channel, 1, false);
				return "Subscribe command sent to ##highlight##$botnet##end##";
			} else {
				return "##error##We are not registered to ##highlight##$botnet##end## yet.##end##";
			}
		} else {
			return "##error##Character ##highlight##$botnet##end## does not exist.##end##";
		}
	}
	
    function tells($name, $relay_message) {
		
		if (!$this->bot->core("settings")->get("Announcer", "OrgOn") && !$this->bot->core("settings")->get("Announcer", "PrivOn")) {
			return false;
		}
		$profanity = $this->bot->core("settings")->get("Announcer", "ProfanState");
		$profanityfilter = explode(",",$this->bot->core("settings")->get("Announcer", "ProfanFilter"));
		$mains = explode(",",$this->bot->core("settings")->get("Announcer", "BotName"));
		$found = false;
		foreach($mains as $main) {
			if($main==substr($name,0,strlen($main))) $found = true;
		}
		if ($found)
        {
			$relay_Sender = "";
			$font_pattern = '/\<([\/]*)font([^\>]*)\>/';
			$relay_message = preg_replace($font_pattern,'',$relay_message);

	            if (preg_match("/^\[([^\]]*)\]\ (.*) \[([^\]]*)\] \[([^\]]*)\]$/", $relay_message, $matches)) {
					$relay_Type		= $matches[1];
					$relay_Text		= $matches[2];
					$relay_Sender	= $matches[3];
					$relay_Append	= "";
	            } elseif (preg_match("/^\[([^\]]*)\]\ (.*)\[([^\]]*)\]$/", $relay_message, $matches)) {
					$relay_Type		= $matches[1];
					$relay_Text		= $matches[2];
					$relay_Sender	= $matches[3];
					$relay_Append	= "";
                } elseif (preg_match("/^(?:[^ ]+) ([^:]+): (.+) You can disable receipt of mass messages and invites in the Preferences for (?:.+)$/i", $relay_message, $matches)) {
					$relay_Type		= "Message";
					$relay_Text		= $matches[2];
					$relay_Sender	= $matches[1];
					$relay_Append	= "";
                } else {
					$relay_Type		= "Announce";
					$relay_Text		= $relay_message;
					$relay_Sender	= $name;
					$relay_Append	= "";						
				}

			if ($relay_Sender && $relay_Text) {
				if (preg_match("/^<a href=.*\>([^\<]*)\<\/a\>[\s]*$/",$relay_Sender,$relaySenderLink)) {
					$relay_Sender = $relaySenderLink[1];
				}
				$relay_SenderLinked = "<a href=\"user:" . "/" . "/" . $relay_Sender . "\">" . $relay_Sender . "</a>";

                if ($relay_Type) {
                    if (preg_match("/wts/i",$relay_Type)) {
						$relay_TypeCaps = "Selling";
					} elseif (preg_match("/wtb/i",$relay_Type)) {
						$relay_TypeCaps = "Buying";
					} elseif (preg_match("/pvm/i",$relay_Type)) {
						$relay_TypeCaps = "PvM";
					} elseif (preg_match("/pvp/i",$relay_Type)) {
						$relay_TypeCaps = "PvP";
					} elseif (preg_match("/lootrights/i",$relay_Type)) {
						$relay_TypeCaps = "Loot Rights";
					} elseif (preg_match("/general/i",$relay_Type)) {
						$relay_TypeCaps = "General";
					} elseif (preg_match("/event/i",$relay_Type)) {
						$relay_TypeCaps = "Event";
					} else {
						$relay_TypeCaps =  ucwords(strtolower($relay_Type));
					}
				}

				$relay_message = "##Announcer_sender##" . $relay_SenderLinked . ":##end## ##Announcer_text##" . $relay_Text . "##end##";
				if ($relay_Type) { $relay_message = "[##Announcer_type##" . $relay_TypeCaps . "##end##] " . $relay_message; }
				if ($relay_Append) { $relay_message .= $relay_Append; }
			} 

			if ($profanity) {						
		                foreach ($profanityfilter as $curse_word) {
		                        if (stristr(trim($relay_message)," ".$curse_word)) {
										$stars = "";
		                                $length = strlen($curse_word);
		                                for ($i = 1; $i <= $length; $i++) {
		                                        $stars .= "*";
		                                }
		                                $relay_message = preg_replace("/".$curse_word."/i",$stars,trim($relay_message));
		                                $stars = "";
		                        }
		                }

			}

			if (preg_match("/^([A-Za-z]{1,})([0-9]{1,})$/",$name,$botnames_matches)) { $botname_short = $botnames_matches[1]; } else { $botname_short = $name; }
			$relay_message = "##Announcer_botname##" . $botname_short . " relay:##end## ##Announcer_text##" . $relay_message . "##end##";

			$ignoredSender = explode(",",$this->bot->core("settings")->get("Announcer", "IgnoreList"));
			$found = false;
			foreach($ignoredSender as $ignored) {
				if(strtolower($relay_Sender)==strtolower($ignored)) $found = true;
			}
			if (!$found) {
				if ($this->bot->core("settings")->get("Announcer", "OrgOn")) $this -> bot -> send_gc($relay_message); 
				if ($this->bot->core("settings")->get("Announcer", "PrivOn")) $this -> bot -> send_pgroup($relay_message);
				$msg = preg_replace("/##end##/U", "", $relay_message);
				$msg = preg_replace("/##([^#]+)##/U", "", $msg);
				if ($this->bot->exists_module("discord")&&$this->bot->core("settings")->get("Announcer", "AlertDisc")) {
					if($this->bot->core("settings")->get("Announcer", "DiscChanId")) { $chan = $this->bot->core("settings")->get("Announcer", "DiscChanId"); } else { $chan = ""; }
					$this->bot->core("discord")->disc_alert($msg, $chan);
				}
				if ($this->bot->exists_module("irc")&&$this->bot->core("settings")->get("Announcer", "AlertIrc")) {
					$this->bot->core("irc")->send_irc("", "", $msg);
				}				
			}

            return true;
		}
		return false;
    }
}
?>
