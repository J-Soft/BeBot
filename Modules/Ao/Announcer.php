<?php
/* Announcer.php
* From work by Doctorhyde@RK5, Alreadythere@RK2
* To use this module : edit few parameters below in 2 EDITABLE zones, according to your needs
* Then start bot, setup parameters & do !register to join the target Botnet + start relaying
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
		$this->register_command('all', 'unregister', 'SUPERADMIN');
		
		$this -> bot -> core("colors") -> define_scheme("Announcer", "botname", "bluesilver");
		$this -> bot -> core("colors") -> define_scheme("Announcer", "sender", "bluegray");
		$this -> bot -> core("colors") -> define_scheme("Announcer", "type", "brightgreen");
		$this -> bot -> core("colors") -> define_scheme("Announcer", "text", "lightyellow");
		
		$this -> help['description'] = 'Allows to register/unregister from configured BotNet.';
		$this -> help['command']['register']="Registers your bot into configured BotNet.";
		$this -> help['command']['unregister']="Unregisters your bot from configured BotNet.";
		
		$this->bot->core("settings")->create("Announcer", "OrgOn", false, "Should the bot be relaying from Botnet to guild channel ?", "On;Off");
		$this->bot->core("settings")->create("Announcer", "PrivOn", false, "Should the bot be relaying from Botnet to private channel ?", "On;Off");
		$this->bot->core("settings")->create("Announcer", "BotName", "Darknet", "What's the Botnet main BotName (Darknet by default) ?", "Darknet");
        $this->bot->core("settings")
            ->create("Announcer", "AlertDisc", false, "Do we alert Discord of Botnet spam ?");
        $this->bot->core("settings")
            ->create("Announcer", "DiscChanId", "", "What Discord ChannelId in case we separate Botnet spam from main Discord channel (leave empty for all in main channel) ?");
        $this->bot->core("settings")
            ->create("Announcer", "AlertIrc", false, "Do we alert Irc of Botnet spam ?");		
		
	}

	function command_handler($name, $msg, $origin) {
		if (preg_match("/^register$/i", $msg))
			return $this->register($name, $origin);
		elseif (preg_match("/^unregister$/i", $msg))
			return $this->unregister($name, $origin);
	}

	function register($name, $origin) {
		$main = $this->bot->core("settings")->get("Announcer", "BotName");
        if (!$this->bot->core('player')->id($main) instanceof BotError) {
			$this->bot->send_tell($main, "register", 1, false);
            return "Register command sent to ##highlight##$main##end##";
        } else {
			return "##error##Character ##highlight##$main##end## does not exist.##end##";
		}
	}

	function unregister($name, $origin) {
		$main = $this->bot->core("settings")->get("Announcer", "BotName");
        if (!$this->bot->core('player')->id($main) instanceof BotError) {
			$this->bot->send_tell($main, "unregister", 1, false);
            return "Unregister command sent to ##highlight##$main##end##";
        } else {
			return "##error##Character ##highlight##$main##end## does not exist.##end##";
		}
	}	
	
        function tells($name, $relay_message) {
		
		if (!$this->bot->core("settings")->get("Announcer", "OrgOn") && !$this->bot->core("settings")->get("Announcer", "PrivOn")) {
			return false;
		}
		$main = $this->bot->core("settings")->get("Announcer", "BotName");
		$announcers = array(
			$main,
				$main."1",$main."2",$main."3",$main."4",$main."5",$main."6",$main."7",$main."8",$main."9",
				$main."10",$main."11",$main."12",$main."13",$main."14",$main."15",$main."16",$main."17",$main."18",$main."19",
				$main."20",$main."21",$main."22",$main."23",$main."24",$main."25",$main."26",$main."27",$main."28",$main."29",
				$main."30",$main."31",$main."32",$main."33",$main."34",$main."35",$main."36",$main."37",$main."38",$main."39",
				$main."40",$main."41",$main."42",$main."43",$main."44",$main."45",$main."46",$main."47",$main."48",$main."49",
		);
/* EDITABLE: Do you want to filter profanity out? FALSE here = strip profanity, TRUE here = don't bother */
		$profanity = TRUE;
		$profanityfilter = array("fuck","fcuk","shit"); // etc ...
/* EDITABLE: customize quoted "" words comma , separated (except last one) into the array just upper */	

		if (in_array($name,$announcers))
        {
			$relay_Sender = "";
			$font_pattern = '/\<([\/]*)font([^\>]*)\>/';
			$relay_message = preg_replace($font_pattern,'',$relay_message);

	                if (preg_match("/^\[([^\]]*)\]\ (.*) \[([^\]]*)\] \[([^\]]*)\]$/", $relay_message, $matches)) {
				$relay_Type		= $matches[1];
				$relay_Text		= $matches[2];
				$relay_Sender		= $matches[3];
				$relay_Append		= "";

	                } elseif (preg_match("/^\[([^\]]*)\]\ (.*)\[([^\]]*)\]$/", $relay_message, $matches)) {
				$relay_Type		= $matches[1];
				$relay_Text		= $matches[2];
				$relay_Sender		= $matches[3];
				$relay_Append		= "";
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

			if (! $profanity) {
		                foreach ($profanityfilter as $curse_word) {
		                        if (stristr(trim($relay_message)," ".$curse_word)) {
		                                $length = strlen($curse_word);
		                                for ($i = 1; $i <= $length; $i++) {
		                                        $stars .= "*";
		                                }
		                                $relay_message = eregi_replace($curse_word,$stars,trim($relay_message));
		                                $stars = "";
		                        }
		                }

			}

			if (preg_match("/^([A-Za-z]{1,})([0-9]{1,})$/",$name,$botnames_matches)) { $botname_short = $botnames_matches[1]; } else { $botname_short = $name; }
			$relay_message = "##Announcer_botname##" . $botname_short . " relay:##end## ##Announcer_text##" . $relay_message . "##end##";

/* EDITABLE: Do you want to filter some specific name(s) that ain't wanted technically or caused problems ? */
			$ignoredSender = array(
					"",
					""
				);
/* EDITABLE: each name is in quotes "" and separated by comma , (except final one) */

			if (!in_array($relay_Sender,$ignoredSender)) {
				if ($this->bot->core("settings")->get("Announcer", "OrgOn")) $this -> bot -> send_gc($relay_message); 
				if ($this->bot->core("settings")->get("Announcer", "PrivOn")) $this -> bot -> send_pgroup($relay_message);
				$msg = preg_replace("/##end##/U", "", $relay_message);
				$msg = preg_replace("/##([^#]+)##/U", "", $msg);
				if ($this->bot->exists_module("discord")&&$this->bot->core("settings")->get("Announcer", "AlertDisc")) {
					if($this->bot->core("settings")->get("Announcer", "DiscChanId")) { $chan = $this->bot->core("settings")->get("Announcer", "DiscChanId"); } else { $chan = ""; }
					$this->bot->core("discord")->disc_alert($msg, $chan);
				}
				if ($this->bot->exists_module("irc")&&$this->bot->core("settings")->get("Announcer", "AlertIrc")) {
					$this->bot->core("irc")->send_irc("", "", $msg_message);
				}				
			}

                        return true;
		}
		return false;
        }
}
?>
