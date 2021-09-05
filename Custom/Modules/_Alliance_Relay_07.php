<?php
/*
* Alliance Relay Module for 0.7.x
*
* By: Heffalomp and Snoopshake @ RK2
* Crash fix : Bitnykk/Kynethic
* Thanks to: Hyde, Temar, all the nice people in the bebot community, and the creators of BeBot :)
*
*/

$hrelay = new HRelay($bot);

/*
The Class itself...
*/
class HRelay extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));

		$this -> register_module("hrelay");

		$this -> register_command('tell', 'agcr', 'SUPERADMIN');
		$this -> register_command('extpgmsg', 'agcr', 'MEMBER');

		$this -> register_event("privgroup");
		$this -> register_event("gmsg", "org");
		$this -> register_event("pginvite");
		$this -> register_event("connect");
		$this -> register_event("cron", "5min");
		$this -> register_event("cron", "2sec");
		$this -> register_event("extprivgroup");
		/*
		Relays, Change This next Line to add more relays 
		*/
		$this -> relays = array(1 => "one", 2 => "two", 3 => "three", 4 => "four", 5 => "five");
		foreach($this -> relays as $relaynum => $relayname)
		{
			$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("hrelay".$relaynum, "true") . "
				(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							botname VARCHAR(20),
							type VARCHAR(30),
							time INT,
							msg TEXT)");
		}
			$this -> bot = &$bot;
		foreach($this -> relays as $relaynum => $relayname)
		{
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'farSyntax', '!', 'The syntax pre-ID of the bot on the far side of relay'.$relaynum);
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'nearSyntax', '@', 'The syntax to send a message to relay'.$relaynum);
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'Priv', 'Both', 'Where should private group relay to', 'Both;Guildchat;Relaybots;None');
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'Org', 'Both', 'Where should guild chat group relay to', 'Both;Privgroup;Relaybots;None');
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'Inc', 'Both', 'Where should incoming messages relay to', 'Both;Guildchat;Privgroup;None');
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'Relay', '', 'What is the name of the bot that we are using as a relay?');
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'AutoinviteRelayGroup', '', 'RELAYBOT: What is the name of the groups that all bots will be in for invites to the relay bot? (Leave empty to invite all bots on the roster)');
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'Status', FALSE, 'Relay should be');
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'Autoinvite', FALSE, 'RELAYBOT: Autoinvite bots to the relay group');
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'Type', 'Tells', 'How should we relay, via a private group or via tells?  Tells is not the recommended method of handling relays, is slower, and less reliable, and can only be used between two bots.  See the help for <pre>gcr for more information.', 'Pgroup;Tells;DB');
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'StrictNameCheck', TRUE, 'Has the name of the sender of tells with <pre>gcr commands to be an exact match with the name of the relay bot?');
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'OtherPrefixs', '', 'What other prefixes should be Checked in the in the Relay Group seperated by ; eg @;.;#');
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'Pgname', 'Orgname Guest', 'What name should we show when we relay from the private group?');
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'Gcname', 'Orgname', 'What name should we show when we relay from guild chat?');
			$this -> bot -> core("settings") -> create("HRelay".$relaynum, 'IRC', FALSE, "Relay to IRC?");
		}
		
		/*
		Coloring
		*/
		foreach($this -> relays as $relaynum => $relayname)
		{
			$this -> bot -> core("colors") -> define_scheme("hrelay".$relayname, "channel", "normal");
			$this -> bot -> core("colors") -> define_scheme("hrelay".$relayname, "name", "normal");
			$this -> bot -> core("colors") -> define_scheme("hrelay".$relayname, "message", "normal");
		}
		
		$this -> help['description'] = "Handles misc/large relay setups.";
		$this -> help['command']['agcr'] = "Has the bot say a message (useful for testing or other purposes).";
		$this -> help['notes'] = "How to use a private group relay:<BR><BR>Step 1<BR>Create a new bot to use as the relay.  Add the bots that will be using the relay as members.  Configure the relaybot to autoinvite the bots that will be using it.  (It is highly recommended to disable nearly all plugins on the relaybot.  As you are only using it for relaying purposes, there should be no reason why anyone needs access to it other than the bots and yourself.)<BR><BR>Step 2<BR>Install the Relay.php plugin onto the bots that will be using the relay.  Make sure to disable GuildRelay_GUILD.php and Relay_GUILD.php as this will conflict with them.<BR><BR>Step 3<BR>Give the bots that will be relaying the correct access level and permissions to use <pre>gcr. (So if Bot1 is relaying to Bot2 via Relay1, Bot1 needs access to <pre>gcr on Bot2 via pgmsg, and vice versa.)<BR><BR>Step 4<BR>Restart the bots if you haven't already, and configure your settings to your specifications.<BR><BR>Step 5<BR>Enjoy lightning quick relay messages, and less bot lag (due to no longer queueing the relay messages via /tell).";

		$this -> update();

		$this -> db_relay = FALSE;
		$this -> lastsent = 0;
	}

	function update()
	{
		foreach($this -> relays as $relaynum => $relayname)
		{
			switch ($this -> bot -> db -> get_version("hrelay".$relaynum))
			{
				case 0:
					$this -> bot -> core("settings") -> update("HRelay".$relaynum, "Type", "defaultoptions", "Pgroup;Tells;DB");
				Default:
			}
			$this -> bot -> db -> set_version("hrelay".$relaynum, 1);
		}
	}


	function pginvite($group)
	{
		foreach($this -> relays as $relaynum => $relayname)
		{
			if (strtolower($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Relay')) == strtolower($group))
				$this -> bot -> core("chat") -> pgroup_join($group);
		}
	}

	/*
	This gets called on a msg in the private group.
	This is where we send our message to org chat and to our relay.
	*/
	function privgroup($name, $msg)
	{
		$this -> relay_to_pbot1($name, $msg);
	}

	/*
	This gets called on a msg in the group.
	This is where we send our message to the private group and to our relay.
	*/
	function gmsg($name, $group, $msg)
	{
		$this -> relay_to_pgroup2($name, $msg);
	}

	/*
	This gets called on a tell with the command
	*/
	function tell($name, $msg)
	{
		foreach($this -> relays as $relaynum => $relayname)
		{	
			if (preg_match("/^agcr /im", $msg) &&
			$this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Status') && (($this -> bot -> core("settings") -> get("HRelay".$relaynum, "Strictnamecheck") &&
			strtolower($this -> bot -> core("settings") -> get("HRelay".$relaynum, "Relay")) == strtolower($name)) ||
			!($this -> bot -> core("settings") -> get("HRelay".$relaynum, "Strictnamecheck"))))
			{
				$parts = explode(' ', $msg);
				unset($parts[0]);
				$txt = implode(' ', $parts);

				/* Hyde: parse out the Channel, Name and Message text, colorize it using the hrelayone colors */
				if (preg_match("/\[<font color=#[ABCDEFabcdef0-9]{6}>([^\]]*)<\/font>\] <font color=#[ABCDEFabcdef0-9]{6}>([^\ ]*):<\/font> <font color=#[ABCDEFabcdef0-9]{6}>(.*)<\/font>$/", $txt, $matches) || preg_match("/\[([^\]]*)] ([^\ ]*): (.*)$/", $txt, $matches))
				{
					$txtirc = "[" . $matches[1] . "]" . $matches[2] . ": " . $matches[3];
					$txt_channel = ("##hrelay".$relayname."_channel##" . $matches[1] . "##end##");
					$txt_name = ("##hrelay".$relayname."_name##" . $matches[2] . "##end##");
					$txt_message = ("##hrelay".$relayname."_message##" . $matches[3] . "##end##");
					$txt = "[" . $txt_channel . "] " . $txt_name . ": " . $txt_message;
				}
				if ($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Inc') == "Both" || $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Inc') == "Guildchat")
				{
					$this -> bot -> send_gc($txt);
				}

				if ($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Inc') == "Both" || $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Inc') == "Privgroup")
				{
					$this -> bot -> send_pgroup($txt);
				}
				if ($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Irc') == TRUE)
				{
					$this -> relay_to_irc2($txtirc);
				}
			}
		}
	}


	function extpgmsg($pgroup, $name, $msg, $db=FALSE)
	{
		foreach($this -> relays as $relaynum => $relayname)
		{
			if (preg_match("/^agcr /im", $msg) &&
			$this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Status') && ($db ||
			strtolower($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Relay')) == strtolower($pgroup)))
			{
				$txt = explode(' ', $msg, 2);
				$txt = $txt[1];

	            /* Hyde: parse out the Channel, Name and Message text, colorize it using the hrelay colors */
				if (preg_match("/\[<font color=#[ABCDEFabcdef0-9]{6}>([^\]]*)<\/font>\] <font color=#[ABCDEFabcdef0-9]{6}>([^\ ]*):<\/font> <font color=#[ABCDEFabcdef0-9]{6}>(.*)<\/font>$/", $txt, $matches) || preg_match("/\[([^\]]*)] ([^\ ]*): (.*)$/", $txt, $matches))
				{
					$txtirc = "[" . $matches[1] . "]" . $matches[2] . ": " . $matches[3];
					$txt_channel = ("##hrelay".$relayname."_channel##" . $matches[1] . "##end##");
					$txt_name = ("##hrelay".$relayname."_name##" . $matches[2] . "##end##");
					$txt_message = ("##hrelay".$relayname."_message##" . $matches[3] . "##end##");
					$txt = "[" . $txt_channel . "] " . $txt_name . ": " . $txt_message;
				}
				if ($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Inc') == "Both" || $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Inc') == "Guildchat")
				{
					$this -> bot -> send_gc($txt);
				}

				if ($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Inc') == "Both" || $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Inc') == "Privgroup")
				{
					$this -> bot -> send_pgroup($txt);
				}
				
				if ($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Irc') == TRUE)
				{
					$this -> relay_to_irc2($txtirc);
				}
			}
		}
	}

	function extprivgroup($group, $name, $msg)
	{
		foreach($this -> relays as $relaynum => $relayname)
		{
			$prefixs = $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'OtherPrefixs');
			if($prefixs == "")
				Return;
			$prefixs = str_replace(" ", "", $prefixs);
			$prefixs = explode(";", $prefixs);
			foreach($prefixs as $pre)
			{
				if($msg[0] == $pre)
					$found = TRUE;
			}
			if(!$found)
			Return;
			if($this -> bot -> core("access_control") -> check_for_access($name, "agcr"))
			{
				$msg = substr($msg, 1);
				$this -> extpgmsg($group, $name, $msg);
			}
		}
	}

	function command_handler($name, $msg, $origin) {}

	/*
	This gets called on a msg in the group.
	This is where we send our message to the private group and to our relay.
	*/
	function relay_to_pgroup2($name, $msg)
	{
		foreach($this -> relays as $relaynum => $relayname)
		{
			if ($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Status') && preg_match("/^" . $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'nearSyntax') . " (.+)/i", $msg, $info))
			{
				$namestr = "";
				if ($name != "0")
				{
					$namestr = "##relay_name##" . $name . ":##end## ";
				}
//				$relaystring = "[##relay_channel##" . $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Gcname') . "##end##] " . $namestr . "##relay_message##" . $info[1] . " ##end##";
				$relaystring = "[" . $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Gcname') . "] " . $name . ": " . $info[1];

//				if ($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Org') == "Both" || $this -> bot -> core("settings") -> get('Relay', 'Org') == "Privgroup")
//				{
//					$this -> bot -> send_pgroup($relaystring);
//				}

				if ($this -> bot -> core("settings") -> get("HRelay".$relaynum, "Relay") != '' &&
				($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Org') == "Both" || $this -> bot -> core("settings") -> get('Relay', 'Org') == "Relaybots"))
				{
					$this -> relay_to_bot($relaynum, $relaystring);
				}
			}
		}
	}
	/*
	Relays from privgroup to relay (Relay.php handles regular privgroup <-> guild relaying)
	*/
	function relay_to_pbot1 ($name, $msg)
	{
		foreach($this -> relays as $relaynum => $relayname)
		{
			if ($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Status'))
			{
				if (preg_match("/^" . $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'nearSyntax') . " (.+)/i", $msg, $info))
				{
					$relaystring = "[" . $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Pgname') . "] " . $name . ": " . $info[1];
					//$relaystring = "[##hrelay".$relayname."_channel##" . $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Pgname') . "##end##] ##hrelay".$relayname."_name##" . $name . ":##end## ##hrelay".$relayname."_message##" . $info[1] . " ##end##";
					if ($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Relay') != '' &&
					($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Priv') == "Both" || $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Org') == "Relaybots"))
					{
						$this -> relay_to_bot($relaynum, $relaystring);
					}
				}
			}
		}
	}		
	
	// Relays $msg without any further modifications to other bot(s).
	// If $chat is true $msg will be relayed as chat with added "<pre>gcr " prefix.
	// If $chat is false $msg will be relayed as it is without any addon, this can be used to relay commands to the other bot(s).
	function relay_to_bot($bot, $msg, $chat = true)
	{
/*		if ($chat)
		{
			$prefix = $this -> bot -> core("settings") -> get('HRelay'.$bot, 'farSyntax"<pre>agcr ";
		}
		else
		{
			$prefix = "";
		}
*/
		if ($this -> bot -> core("settings") -> get('HRelay'.$bot, 'Status') && ($this -> bot -> core("settings") -> get("HRelay".$bot, "Relay") != '' || strtoupper($this -> bot -> core("settings") -> get("HRelay".$bot, "Type")) == "DB"))
		{
			$prefix = $this -> bot -> core("settings") -> get('HRelay'.$bot, 'farSyntax') . "agcr ";
			if (strtolower($this -> bot -> core("settings") -> get('HRelay'.$bot, 'Type')) == "tells")
			{
				$this -> bot -> send_tell($this -> bot -> core("settings") -> get('HRelay'.$bot, 'Relay'), $prefix . $msg, 0, false);
			}
			elseif(strtolower($this -> bot -> core("settings") -> get('HRelay'.$bot, 'Type')) == "pgroup")
			{
				$this -> bot -> send_pgroup($prefix . $msg, $this -> bot -> core("settings") -> get('HRelay'.$bot, 'Relay'));
			}
			else
			{
				$this -> bot -> db -> query("INSERT INTO #___hrelay".$bot." (time, type, botname, msg) VALUES (".time().", 'agcr', '".$this -> bot -> botname."', '".mysql_real_escape_string($msg)."')");
			}
		}
	}

	// Relays $msg to IRC module (and from there after formatting to IRC channel)
	function relay_to_irc2($msg)
	{
		$this -> bot -> send_irc("", "", $msg);
	}

	// This gets called on cron
	function cron($cron)
	{
		if($cron == 300)
		{
			foreach($this -> relays as $relaynum => $relayname)
			{
				if ($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Autoinvite'))
				{
					$security_group = $this -> bot -> core("settings") -> get('Relay', 'AutoinviteRelayGroup');
					if (!empty($security_group))
					{
						$security_groups_gid = $this -> bot -> db -> select("SELECT gid,name FROM #___security_groups WHERE name = '$security_group'");

						if(!empty($security_groups_gid))
						{
							if($security_groups_gid[0][0])
							{
								$relayedbots_gid = $security_groups_gid[0][0];
								$thisbotname = $this -> bot -> botname;
								$invitelist = $this -> bot -> db -> select("SELECT ol.nickname,ol.status_pg,ol.botname,sm.gid,sm.name FROM #___online AS ol LEFT JOIN #___security_members AS sm ON ol.nickname = sm.name WHERE sm.gid = $relayedbots_gid AND ol.status_pg = 0 AND ol.botname = \"$thisbotname\"");
								if ($invitelist[0])
								{
									foreach ($invitelist as $inviteme)
									{
										$this -> bot -> core("chat") -> pgroup_invite($inviteme[0]);
										echo " NOTICE: Inviting " . $inviteme[0] . " to the bot\n";
									}
								}
							}
						}
						else
							echo " Error: Relay Module is Unable to find Security group \"$security_group\"\n";
					}
					$old = time() - 300;
					$this -> bot -> db -> query("DELETE FROM #___relay WHERE time < ".$old);
				}
			}
		}
		elseif($cron == 2)
		{
			foreach($this -> relays as $relaynum => $relayname)
			{
				if(strtoupper($this -> bot -> core("settings") -> get("HRelay".$relaynum, "Type")) == "DB")
				{
					$result = $this -> bot -> db -> select("SELECT id, type, botname, msg FROM #___hrelay".$relaynum." WHERE id > ".$this -> lastsent." ORDER BY id ASC");
					if (!empty($result))
					{
						foreach ($result as $res)
						{
							if(strtolower($res[1]) == "agcr" && $this -> db_relay)
							{
								if(ucfirst(strtolower($res[2])) != $this -> bot -> botname) // make sure we are not doing our own command
								{
									if($this -> bot -> core("access_control") -> check_for_access($res[2], "agcr"))
									{
										$this -> extpgmsg("", $res[2], "agcr ".$res[3], TRUE);
									}
								}
							}
							$this -> lastsent = $res[0];
						}
					}
					$this -> db_relay = TRUE;
				}
				else
					$this -> db_relay = FALSE;
			}
		}
	}


	function connect()
	{
		foreach($this -> relays as $relaynum => $relayname)
		{
			if ($this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'Autoinvite'))
			{
				$security_group = $this -> bot -> core("settings") -> get('HRelay'.$relaynum, 'AutoinviteRelayGroup');
				if (!empty($security_group))
				{
					$security_groups_gid = $this -> bot -> db -> select("SELECT gid,name FROM #___security_groups WHERE name = '$security_group'");

					if($security_groups_gid[0][0])
					{
						$relayedbots_gid = $security_groups_gid[0][0];
						$thisbotname = $this -> bot -> botname;
						$invitelist = $this -> bot -> db -> select("SELECT ol.nickname,ol.status_pg,ol.botname,sm.gid,sm.name FROM online AS ol LEFT JOIN #___security_members AS sm ON ol.nickname = sm.name WHERE sm.gid = $relayedbots_gid AND ol.status_pg = 0 AND ol.botname = \"$thisbotname\"");
						if ($invitelist[0])
						{
							foreach ($invitelist as $inviteme)
							{
								$this -> bot -> core("chat") -> pgroup_invite($inviteme[0]);
								echo " NOTICE: Inviting " . $inviteme[0] . " to the bot\n";
							}
						}
					}
				}
			}
		}
	}
}
?>
