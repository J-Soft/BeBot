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

// RC1 DiscordPhpKiss lightest forked standalone @ https://github.com/bitnykk/discord-php-kiss
define("DISCORD_API", "https://discordapp.com/api/v6");
require_once('Sources/DiscordPhpKiss/discord_curl.php');

// RC2 Nebucord : heavier standalone with threading issue
/*function autoLoader($dir, &$results = array()) {
    $files = scandir($dir);
    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path) && substr($path, -4)=='.php') {
			$slash = str_replace("\\","/",$path);
			$explode = explode('Sources/Nebucord/src/',$slash);
			$results[] = 'Sources/Nebucord/src/'.$explode[1];
        } else if ($value != "." && $value != "..") {
            autoLoader($path, $results);
        }
    }
	return $results;
}
$autoList = autoLoader('Sources/Nebucord/src/');
$autoInv = array_reverse($autoList);
foreach ($autoInv as $key) {
	//echo " * ".$key." * ";
	include_once($key);
}*/

// RC3 SimpleDiscord : require Websocket + Psr Log ; buggy & incomplete
/*$results = array();
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
$results[] = 'Sources/Log/Psr/Log/LoggerInterface.php';
autoLoader('Sources/Log/Psr/Log/',$results,'LoggerInterface.php');
$results[] = 'Sources/Websocket/lib/Message/Message.php';
autoLoader('Sources/Websocket/lib/Message/',$results,'Message.php');
$results[] = 'Sources/Websocket/lib/Exception.php';
autoLoader('Sources/Websocket/lib/',$results,'Exception.php');
autoLoader('Sources/SimpleDiscord/src/RestClient/Resources/',$results);
$results[] = 'Sources/SimpleDiscord/src/RestClient/RestClient.php';
autoLoader('Sources/SimpleDiscord/src/DiscordSocket/',$results);
$results[] = 'Sources/SimpleDiscord/src/SimpleDiscord.php';
//$inverts = array_reverse($results);
foreach ($results as $key) {
	//echo " * ".$key." * ";
	include_once($key);
}*/

$discordrelay = new DiscordRelay($bot);
/*
The Class itself...
*/
class DiscordRelay extends BaseActiveModule
{
	
	var $lastmsg = 0;
	var $lastcheck = 0;
	var $crondelay = "2sec";

    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
		$this->register_module("discord");
		$this->register_command("all", "discord", "SUPERADMIN");
		$this->register_event("connect");
		$this->register_event("privgroup");
        $this->register_event("gmsg", "org");
        $this->help['description'] = "Handles the Discord relay of the bot.";
        $this->help['command']['discord connect'] = "Tries relaying from/to the Discord channel.";
        $this->help['command']['discord disconnect'] = "Stops relaying from/to the Discord channel.";
        $this->help['notes'] = "The Discord relay is configured via settings, for all options check /tell <botname> <pre>settings discord.";
        $this->bot->core("settings")
            ->create("discord", "DiscordRelay", false, "Should the bot be relaying from/to Discord server ?", "On;Off");		
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
		}
	}

	/*
    Just a test function, might delete for release
    */
    function discord_test()
    {	
		/*$nebucord = new \Nebucord\Nebucord(['token' => $this->token, 'ctrlusr' => ['controluser-snowflake1', 'controluser-snowflake2']]);
		$nebucord->bootstrap()->run();*/
		/*$nebucordREST = new \Nebucord\NebucordREST(['token' => $this->token]);
		$message_model = $nebucordREST->channel->createMessage($this->channel, "test sending message");*/
		
		/*$simpledisc = new \SimpleDiscord\SimpleDiscord([
			"token" => $this->token,
			"debug" => 3
		]);
		$simpledisc->run();*/	
		
		// DiscordPhpKiss POST content
		/*$channel = $this->channel;
		$token = $this->token;
		$route = "/channels/{$channel}/messages";
		$msg = strip_tags("bot sending test ".time());
		$data = array("content" => $msg);
		$result = discord_post($route, $token, $data);*/
	}

	/*
    Called at bot startup
    */
    function connect()
    {	
		if ($this->bot->core("settings")->get("discord", "DiscordRelay")) {
			$this->register_event("cron", $this->crondelay);
			$this->bot->log("DISCORD", "STATUS", "Restored Discord relay after bot startup");
		}
	}	
	
    /*
    Manual relay activation
    */	
    function discord_connect($name = "")
    {	
		if (!$this->bot->core("settings")->get("discord", "DiscordRelay")) {
			$this->bot->core('settings')->save('discord', 'DiscordRelay', TRUE);
			$this->register_event("cron", $this->crondelay);
			if (($name != "") && ($name != "c")) {
				$this->bot->send_tell($name,"Relaying from/to Discord channel");
			} else {
				if ($name == "") {
					$this->bot->send_gc("Relaying from/to Discord channel");
				}
			}
		} else {
			if (($name != "") && ($name != "c")) {
				$this->bot->send_tell($name,"Discord relay is already activated");
			} else {
				if ($name == "") {
					$this->bot->send_gc("Discord relay is already activated");
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
					$this->bot->send_gc("Discord relay has been stopped");
				}
			}
		} else {
			if (($name != "") && ($name != "c")) {
				$this->bot->send_tell($name,"Discord relay was already stopped");
			} else {
				if ($name == "") {
					$this->bot->send_gc("Discord relay was already stopped");
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
				if ($channel>0 && $token!="") {
					$route = "/channels/{$channel}/messages";
					$sent = "[Orgchat] ".ucfirst($name).": ".$this->cleanString($msg);
					$data = array("content" => $sent);
					$result = discord_post($route, $token, $data);
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
				if ($channel>0 && $token!="") {
					$route = "/channels/{$channel}/messages";
					$sent = "[Privchat] ".ucfirst($name).": ".$this->cleanString($msg);
					$data = array("content" => $sent);
					$result = discord_post($route, $token, $data);
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
								$sent = "[Discord] ".ucfirst($msg['author']['username']).": ".strip_tags(utf8_decode($msg['content']));
								$this->bot->send_output("", $sent,$this->bot->core("settings")->get("discord", "WhatChat"));
							}
					}
				}
				$this->lastcheck = date('Y-m-d').'T'.date("H:i:s").'.000000+00:00';
			}
		}
    }	
	
}

?>