<?php
/*
* Relay.php - Relaying between guest channel, org chat and other bots via tells or private group
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg Stensås, ShadowRealm Creations and the BeBot development team.
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
* File last changed at $LastChangedDate: 2008-12-02 19:16:14 +0100 (Tue, 02 Dec 2008) $
* Revision: $Id: Relay.php 1870 2008-12-02 18:16:14Z temar $
*/

$relay = new Relay($bot);

/*
The Class itself...
*/
class Relay extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_module("relay");

		$this -> register_command('tell', 'gcr', 'SUPERADMIN');
		$this -> register_command('extpgmsg', 'gcr', 'MEMBER');

		$this -> register_event("privgroup");
		$this -> register_event("gmsg", "org");
		$this -> register_event("pginvite");
		$this -> register_event("connect");
		$this -> register_event("cron", "5min");
		$this -> register_event("cron", "2sec");
		$this -> register_event("extprivgroup");

		$this -> bot -> core('prefs') -> create('AutoInv', 'recieve_auto_invite', 'Automatic invites to private group should be?', 'Off', 'Off;On');

		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("relay", "false") . "
               (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						botname VARCHAR(20),
        	 			type VARCHAR(30),
						time INT,
        				msg TEXT)");
		
		$this -> bot -> core("settings") -> create('Relay', 'Priv', 'Both', 'Where should private group relay to', 'Both;Guildchat;Relaybots;None');
		$this -> bot -> core("settings") -> create('Relay', 'Org', 'Both', 'Where should guild chat group relay to', 'Both;Privgroup;Relaybots;None');
		$this -> bot -> core("settings") -> create('Relay', 'Inc', 'Both', 'Where should incoming messages relay to', 'Both;Guildchat;Privgroup;None');
		$this -> bot -> core("settings") -> create('Relay', 'Relay', '', 'What is the name of the bot that we are using as a relay?');
		$this -> bot -> core("settings") -> create('Relay', 'AutoinviteRelayGroup', '', 'RELAYBOT: What is the name of the groups that all bots will be in for invites to the relay bot? (Leave empty to invite all bots on the roster)');
		$this -> bot -> core("settings") -> create('Relay', 'Status', FALSE, 'Relay should be');
		$this -> bot -> core("settings") -> create('Relay', 'Autoinvite', FALSE, 'RELAYBOT: Autoinvite bots to the relay group');
		$this -> bot -> core("settings") -> create('Relay', 'Type', 'Tells', 'How should we relay, via a private group or via tells?  Tells is not the recommended method of handling relays, is slower, and less reliable, and can only be used between two bots.  See the help for <pre>gcr for more information.', 'Pgroup;Tells;DB');
		$this -> bot -> core("settings") -> create("Relay", "StrictNameCheck", TRUE, "Has the name of the sender of tells with <pre>gcr commands to be an exact match with the name of the relay bot?");
		$this -> bot -> core("settings") -> create('Relay', 'OtherPrefixs', '', 'What other prefixes should be Checked in the in the Relay Group seperated by ; eg @;.;#');
		$this -> bot -> core("settings") -> create("Relay", "Color", FALSE, "color outgoing message to the relay?");

		$this -> bot -> core("colors") -> define_scheme("relay", "channel", "normal");
		$this -> bot -> core("colors") -> define_scheme("relay", "name", "normal");
		$this -> bot -> core("colors") -> define_scheme("relay", "message", "normal");

		$this -> help['description'] = "Plugin to enable relay between guilds and private groups.";
		$this -> help['command']['gcr <message>'] = "Has the bot say a message (useful for testing or other purposes).";
		$this -> help['notes'] = "How to use a private group relay:<BR><BR>Step 1<BR>Create a new bot to use as the relay.  Add the bots that will be using the relay as members.  Configure the relaybot to autoinvite the bots that will be using it.  (It is highly recommended to disable nearly all plugins on the relaybot.  As you are only using it for relaying purposes, there should be no reason why anyone needs access to it other than the bots and yourself.)<BR><BR>Step 2<BR>Install the Relay.php plugin onto the bots that will be using the relay.  Make sure to disable GuildRelay_GUILD.php and Relay_GUILD.php as this will conflict with them.<BR><BR>Step 3<BR>Give the bots that will be relaying the correct access level and permissions to use <pre>gcr. (So if Bot1 is relaying to Bot2 via Relay1, Bot1 needs access to <pre>gcr on Bot2 via pgmsg, and vice versa.)<BR><BR>Step 4<BR>Restart the bots if you haven't already, and configure your settings to your specifications.<BR><BR>Step 5<BR>Enjoy lightning quick relay messages, and less bot lag (due to no longer queueing the relay messages via /tell).";

		$this -> update();

		$this -> db_relay = FALSE;
		$this -> lastsent = 0;
	}

	function update()
	{
		switch ($this -> bot -> db -> get_version("relay"))
		{
			case 1:
				$this -> bot -> core("settings") -> update("Relay", "Type", "defaultoptions", "Pgroup;Tells;DB");
			Default:
		}
		$this -> bot -> db -> set_version("relay", 2);
	}


	function pginvite($group)
	{
		if (strtolower($this -> bot -> core("settings") -> get('Relay', 'Relay')) == strtolower($group))
			$this -> bot -> core("chat") -> pgroup_join($group);
	}

	/*
	This gets called on a msg in the private group.
	This is where we send our message to org chat and to our relay.
	*/
	function privgroup($name, $msg)
	{
		$this -> relay_to_gc($name, $msg);
	}

	/*
	This gets called on a msg in the group.
	This is where we send our message to the private group and to our relay.
	*/
	function gmsg($name, $group, $msg)
	{
		$this -> relay_to_pgroup($name, $msg);
	}

	/*
	This gets called on a tell with the command
	*/
	function tell($name, $msg)
	{
		$input = $this->parse_com($msg, array('com', 'args'));

		if (strtolower($input['com']) == 'gcr' &&
		$this -> bot -> core("settings") -> get('Relay', 'Status') &&
		(($this -> bot -> core("settings") -> get("Relay", "Strictnamecheck") &&
		strtolower($this -> bot -> core("settings") -> get("Relay", "Relay")) == strtolower($name)) ||
		!($this -> bot -> core("settings") -> get("Relay", "Strictnamecheck"))))
		{
			$txt = $input['args'];

			if ($this -> bot -> core("settings") -> get('Relay', 'Inc') == "Both" || $this -> bot -> core("settings") -> get('Relay', 'Inc') == "Guildchat")
			{
				$this -> bot -> send_gc($txt);
			}

			if ($this -> bot -> core("settings") -> get('Relay', 'Inc') == "Both" || $this -> bot -> core("settings") -> get('Relay', 'Inc') == "Privgroup")
			{
				$this -> bot -> send_pgroup($txt);
			}

			$this -> relay_to_irc($txt);
		}
	}


	function extpgmsg($pgroup, $name, $msg, $db=FALSE)
	{
		$input = $this->parse_com($msg, array('com', 'args'));

		if (strtolower($input['com']) == 'gcr' &&
		$this -> bot -> core("settings") -> get('Relay', 'Status') && ($db ||
		strtolower($this -> bot -> core("settings") -> get('Relay', 'Relay')) == strtolower($pgroup)))
		{
			$txt = $input['args'];

			if ($this -> bot -> core("settings") -> get('Relay', 'Inc') == "Both" || $this -> bot -> core("settings") -> get('Relay', 'Inc') == "Guildchat")
			{
				$this -> bot -> send_gc($txt);
			}

			if ($this -> bot -> core("settings") -> get('Relay', 'Inc') == "Both" || $this -> bot -> core("settings") -> get('Relay', 'Inc') == "Privgroup")
			{
				$this -> bot -> send_pgroup($txt);
			}

			$this -> relay_to_irc($txt);
		}
	}

	function extprivgroup($group, $name, $msg)
	{
		$prefixs = $this -> bot -> core("settings") -> get('Relay', 'OtherPrefixs');
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
		if($this -> bot -> core("access_control") -> check_for_access($name, "gcr"))
		{
			$msg = substr($msg, 1);
			$this -> extpgmsg($group, $name, $msg);
		}
	}

	function command_handler($name, $msg, $origin) {}

	/*
	This gets called on a msg in the group.
	This is where we send our message to the private group and to our relay.
	*/
	function relay_to_pgroup($name, $msg)
	{
		if ($this -> bot -> core("settings") -> get('Relay', 'Status'))
		{
			$namestr = "";
			if ($name != "0")
			{
				$namestr = "##relay_name##" . $name . ":##end## ";
			}
			$relaystring = "[##relay_channel##" . $this -> bot -> core("settings") -> get('Relay', 'Gcname') . "##end##] " . $namestr . "##relay_message##" . $msg . " ##end##";
			if ($this -> bot -> core("settings") -> get('Relay', 'Org') == "Both" || $this -> bot -> core("settings") -> get('Relay', 'Org') == "Privgroup")
			{
				$this -> bot -> send_pgroup($relaystring);
			}

			if ($this -> bot -> core("settings") -> get("Relay", "Relay") != '' &&
			($this -> bot -> core("settings") -> get('Relay', 'Org') == "Both" || $this -> bot -> core("settings") -> get('Relay', 'Org') == "Relaybots"))
			{
				$this -> relay_to_bot($relaystring);
			}
		}
	}

	// Relays $msg without any further modifications to other bot(s).
	// If $chat is true $msg will be relayed as chat with added "<pre>gcr " prefix.
	// If $chat is false $msg will be relayed as it is without any addon, this can be used to relay commands to the other bot(s).
	function relay_to_bot($msg, $chat = true)
	{
		if ($chat)
		{
			$prefix = "<pre>gcr ";
		}
		else
		{
			$prefix = "";
		}

		if ($this -> bot -> core("settings") -> get('Relay', 'Status') && ($this -> bot -> core("settings") -> get("Relay", "Relay") != '' || strtoupper($this -> bot -> core("settings") -> get("Relay", "Type")) == "DB"))
		{
			$color = $this -> bot -> core("settings") -> get('Relay', 'Color');
			if (strtolower($this -> bot -> core("settings") -> get('Relay', 'Type')) == "tells")
			{
				$this -> bot -> send_tell($this -> bot -> core("settings") -> get('Relay', 'Relay'), $prefix . $msg, 0, false, TRUE, $color);
			}
			elseif(strtolower($this -> bot -> core("settings") -> get('Relay', 'Type')) == "pgroup")
			{
				$this -> bot -> send_pgroup($prefix . $msg, $this -> bot -> core("settings") -> get('Relay', 'Relay'), TRUE, $color);
			}
			else
			{
				$this -> bot -> db -> query("INSERT INTO #___relay (time, type, botname, msg) VALUES (".time().", 'gcr', '".$this -> bot -> botname."', '".mysql_real_escape_string($msg)."')");
			}
		}
	}

	// Relays $msg to IRC module (and from there after formatting to IRC channel)
	function relay_to_irc($msg)
	{
		$msg = preg_replace("/##end##/U", "", $msg);
		$msg = preg_replace("/##(.+)##/U", "", $msg);

		$this -> bot -> send_irc("", "", $msg);
	}

	/*
	This gets called on a msg in the private group.
	This is where we send our message to org chat and to our relay.
	*/
	function relay_to_gc($name, $msg)
	{
		if ($this -> bot -> core("settings") -> get('Relay', 'Status'))
		{
			$namestr = "";
			if ($name != "0")
			{
				$namestr = "##relay_name##" . $name . ":##end## ";
			}
			$relaystring = "[##relay_channel##" . $this -> bot -> core("settings") -> get('Relay', 'Pgname') . "##end##] " . $namestr . "##relay_message##" . $msg . " ##end##";
			if ($this -> bot -> core("settings") -> get('Relay', 'Priv') == "Both" || $this -> bot -> core("settings") -> get('Relay', 'Priv') == "Guildchat")
			{
				$this -> bot -> send_gc($relaystring);
			}

			if ($this -> bot -> core("settings") -> get("Relay", "Relay") != '' &&
			($this -> bot -> core("settings") -> get('Relay', 'Priv') == "Both" || $this -> bot -> core("settings") -> get('Relay', 'Priv') == "Relaybots"))
			{
				$this -> relay_to_bot($relaystring);
			}
		}
	}

	// This gets called on cron
	function cron($cron)
	{
		if($cron == 300)
		{
			if(!$this -> guildnameset)
			{
				//guildname is not neccessarily set before the bot connects so create those settings here.
				$this -> bot -> core("settings") -> create('Relay', 'Pgname', $this -> bot -> guildname . ' Guest', 'What name should we show when we relay from the private group?');
				$this -> bot -> core("settings") -> create('Relay', 'Gcname', $this -> bot -> guildname, 'What name should we show when we relay from guild chat?');
				$this -> guildnameset = TRUE;
			}
			if ($this -> bot -> core("settings") -> get('Relay', 'Autoinvite'))
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
				else
				{
					$members = $this -> bot -> db -> select("SELECT nickname FROM #___users WHERE user_level >= 1");
					if(!empty($members))
					{
						foreach ($members as $member)
						{
							if ($this -> bot -> core('prefs') -> get($member[0], 'AutoInv', 'recieve_auto_invite') == 'On')
							{
								$this -> bot -> core("chat") -> pgroup_invite($member[0]);
								echo "Inviting " . $member[0] . " to the bot\n";
							}
						}
					}
				}
				$old = time() - 300;
				$this -> bot -> db -> query("DELETE FROM #___relay WHERE time < ".$old);
			}
		}
		elseif($cron == 2)
		{
			if(strtoupper($this -> bot -> core("settings") -> get("Relay", "Type")) == "DB")
			{
				$result = $this -> bot -> db -> select("SELECT id, type, botname, msg FROM #___relay WHERE id > ".$this -> lastsent." ORDER BY id ASC");
				if (!empty($result))
				{
					foreach ($result as $res)
					{
						if(strtolower($res[1]) == "gcr" && $this -> db_relay)
						{
							if(ucfirst(strtolower($res[2])) != $this -> bot -> botname) // make sure we are not doing our own command
							{
								if($this -> bot -> core("access_control") -> check_for_access($res[2], "gcr"))
								{
									$this -> extpgmsg("", $res[2], "gcr ".$res[3], TRUE);
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


	function connect()
	{
		if ($this -> bot -> core("settings") -> get('Relay', 'Autoinvite'))
		{
			$security_group = $this -> bot -> core("settings") -> get('Relay', 'AutoinviteRelayGroup');
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
			else
			{
				$members = $this -> bot -> db -> select("SELECT nickname FROM #___users WHERE user_level > 0");
				if(!empty($members))
				{
					foreach ($members as $member)
					{
						if($this -> bot -> core('prefs') -> get($member[0], 'AutoInv', 'recieve_auto_invite') == 'On')
						{
							$this -> bot -> core("chat") -> pgroup_invite($member[0]);
							echo "Inviting " . $member[0] . " to the bot\n";
						}
					}
				}
			}
		}
	}
}
?>
