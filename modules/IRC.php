<?php
/*
* IRC.php - IRC Relay.
*
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
*/
/*
Add a "_" at the beginning of the file (_IRC.php) if you do not want it to be loaded.
*/
include_once ('Sources/SmartIRC.php');
$irc = new IRC($bot);
/*
The Class itself...
*/
class IRC extends BaseActiveModule
{
	var $bot;
	var $last_log;
	var $is;
	var $whois;
	var $target;
	var $irc;

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->register_module("irc");
		$this->register_command("all", "irc", "SUPERADMIN");
		$this->register_command("all", "irconline", "GUEST");
		$this->register_event("pgjoin");
		$this->register_event("pgleave");
		$this->register_event("buddy");
		$this->register_event("connect");
		$this->register_event("disconnect");
		$this->register_event("privgroup");
		$this->register_event("gmsg", "org");
		$this->help['description'] = "Handles the IRC relay of the bot.";
		$this->help['command']['irconline'] = "Shows users in the IRC Channel.";
		$this->help['command']['irc connect'] = "Tries to connect to the IRC channel.";
		$this->help['command']['irc disconnect'] = "Disconnects from the IRC server.";
		$this->help['notes'] = "The IRC relay is configured via settings, for all options check /tell <botname> <pre>settings IRC.";
		// Create default settings:
		if ($this->bot->guildbot)
		{
			$guildprefix = "[" . $this->bot->guildname . "]";
			$pgroupprefix = "[" . $this->bot->guildname . "'s Guestchannel]";
			$chatgroups = "gc";
			$announcewhat = "buddies";
		}
		else
		{
			$guildprefix = "[]";
			$pgroupprefix = "[" . $this->bot->botname . "]";
			$chatgroups = "pgroup";
			$announcewhat = "joins";
		}
		$this->bot->core("settings")->create("IRC", "Connected", FALSE, "Is the bot connected to the IRC server?", "On;Off", TRUE);
		$this->bot->core("settings")->save("irc", "connected", FALSE);
		$this->bot->core("settings")->create("Irc", "Server", "", "Which IRC server is used?");
		$this->bot->core("settings")->create("Irc", "Port", "6667", "Which port is used to connect to the IRC server?");
		$this->bot->core("settings")->create("Irc", "Channel", "#" . $this->bot->botname, "Which IRC channel should be used?");
		$this->bot->core("settings")->create("Irc", "ChannelKey", "", "What is the IRC channel key if any?");
		$this->bot->core("settings")->create("Irc", "Nick", $this->bot->botname, "Which nick should the bot use in IRC?");
		$this->bot->core("settings")->create("Irc", "IrcGuildPrefix", $guildprefix, "Which prefix should ingame guild chat relayed to IRC get?");
		$this->bot->core("settings")->create("Irc", "IrcGuestPrefix", $pgroupprefix, "Which prefix should ingame chat in the chat group of the bot relayed to IRC get?");
		$this->bot->core("settings")->create("Irc", "GuildPrefix", "[IRC]", "Which prefix should IRC chat relayed to ingame chat get?");
		$this->bot->core("settings")->create("Irc", "AnnounceTo", "both", "Where should joins and leaves to the IRC channel be announced to?", "none;gc;pgmsg;both");
		$this->bot->core("settings")->create("Irc", "AnnounceWhat", $announcewhat, "Which events should be announced to IRC? None, only buddies logging on, only joins to the chatgroup or both?", "none;buddies;joins;both");
		$this->bot->core("settings")->create("Irc", "Reconnect", TRUE, "Should the bot automatically reconnect to IRC?");
		$this->bot->core("settings")->create("Irc", "RelayGuildName", "", "What is the name for GC guildrelay?");
		$this->bot->core("settings")->create("Irc", "ItemRef", "AOMainframe", "Should AO Mainframe of AUNO be used for links in item refs?", "AOMainframe;AUNO");
		$this->bot->core("settings")->create("Irc", "Chat", $chatgroups, "Which channels should be relayed into IRC and vice versa?", "gc;pgroup;both");
		$this->bot->core("settings")->create("Irc", "MaxRelaySize", 500, "What's the maximum amount of characters relayed to IRC?");
		$this->bot->core("settings")->create("Irc", "NotifyOnDrop", FALSE, "Should the chat be notified if something isn't relayed because it's too large?");
		$this->bot->core("settings")->create("Irc", "UseGuildRelay", TRUE, "Should chat coming from IRC also be relayed over the guild relay if it's set up?");
		$this->bot->core("colors")->define_scheme("Irc", "Text", "normal");
		$this->bot->core("colors")->define_scheme("Irc", "User", "normal");
		$this->bot->core("colors")->define_scheme("Irc", "Group", "normal");
		$this->irc = null;
		$this->last_log["st"] = time();
		$this->bot->core("timer")->register_callback("IRC", $this);
		$this->spam[0] = array(0 , 0 , 0 , 0);
		$this->bot->db->query("UPDATE #___online SET status_gc = 0 WHERE botname = '" . $this->bot->botname . " - IRC'");
	}

	function command_handler($name, $msg, $source)
	{
		$com = $this->parse_com($msg);
		switch (strtolower($com['com']))
		{
			case 'irc':
				switch (strtolower($com['sub']))
				{
					case 'connect':
						return $this->irc_connect($name);
						break;
					case 'disconnect':
						return $this->irc_disconnect();
						break;
					case 'server':
						return $this->change_server($com['args']);
						break;
					case 'port':
						return $this->change_port($com['args']);
						break;
					case 'channel':
						return $this->change_chan($com['args']);
						break;
					case 'channelkey':
						return $this->change_chankey($com['args']);
						break;
					case 'nick':
						return $this->change_nick($com['args']);
						break;
					case 'ircprefix':
						return $this->change_ircprefix($com['args']);
						break;
					case 'guildprefix':
						return $this->change_guildprefix($com['args']);
						break;
					case 'reconnect':
						if (($com['args'] == 'on') || ($com['args'] == 'off'))
							return $this->reconnect($com['args']);
						break;
					case 'relayguildname':
						return $this->change_relayguildname($com['args']);
						break;
					case 'itemref':
						if (($com['args'] == 'auno') || ($com['args'] == 'aodb'))
							return $this->change_itemref($com['args']);
						break;
					case 'chat':
						if (($com['args'] == 'gc') || ($com['args'] == 'pg') || ($com['args'] == 'both'))
							return $this->change_chat($com['args']);
						break;
				}
				break;
			case 'irconline':
				return $this->names();
				break;
		}
	}

	function strip_formatting($msg)
	{
		if (strtolower($this->bot->core("settings")->get("Irc", "Itemref")) == "auno")
			$rep = "http://auno.org/ao/db.php?id=\\1&id2=\\2&ql=\\3";
		else
			$rep = "http://aomainframe.net/showitem.asp?LowID=\\1&HiID=\\2&QL=\\3";
		$msg = preg_replace("/<a href=\"itemref:\/\/([0-9]*)\/([0-9]*)\/([0-9]*)\">(.*)<\/a>/iU", chr(3) . chr(3) . "\\4" . chr(3) . " " . chr(3) . "(" . $rep . ")" . chr(3) . chr(3), $msg);
		$msg = preg_replace("/<a style=\"text-decoration:none\" href=\"itemref:\/\/([0-9]*)\/([0-9]*)\/([0-9]*)\">(.*)<\/a>/iU", chr(3) . chr(3) . "\\4" . chr(3) . " " . chr(3) . "(" . $rep . ")" . chr(3) . chr(3), $msg);
		$msg = preg_replace("/<a href=\"(.+)\">/isU", "[link]", $msg);
		$msg = preg_replace("/<a style=\"text-decoration:none\" href=\"(.+)\">/isU", "[link]", $msg);
		$msg = preg_replace("/<\/a>/iU", "[/link]", $msg);
		$msg = preg_replace("/<font(.+)>/iU", "", $msg);
		$msg = preg_replace("/<\/font>/iU", "", $msg);
		return $msg;
	}

	function send_irc($prefix, $name, $msg)
	{
		if (! $this->bot->core("settings")->get("irc", "connected"))
		{
			return false;
		}
		$msg = $this->strip_formatting($msg);
		// If msg is too long to be relayed drop it:
		if (strlen($msg) > $this->bot->core("settings")->get("Irc", "Maxrelaysize"))
		{
			return false;
		}
		$ircmsg = "";
		if ($prefix != "")
		{
			$ircmsg = chr(2) . chr(2) . chr(2) . $prefix . chr(2) . ' ';
		}
		if ($name != "")
		{
			$ircmsg .= $name . ': ';
		}
		$ircmsg .= $msg;
		$ircmsg = htmlspecialchars_decode($ircmsg);
		$this->irc->message(SMARTIRC_TYPE_CHANNEL, $this->bot->core("settings")->get("Irc", "Channel"), $ircmsg);
		return true;
	}

	/*
	This gets called on a msg in the group
	*/
	function gmsg($name, $group, $msg)
	{
		$msg = str_replace("&gt;", ">", $msg);
		$msg = str_replace("&lt;", "<", $msg);
		if (($this->irc != null) && ((strtolower($this->bot->core("settings")->get("Irc", "Chat")) == "gc") || (strtolower($this->bot->core("settings")->get("Irc", "Chat")) == "both")))
		{
			if (! $this->send_irc($this->bot->core("settings")->get("Irc", "Ircguildprefix"), $name, $msg))
			{
				if ($this->bot->core("settings")->get("Irc", "Notifyondrop"))
				{
					$msg2 = "##error##Last line not relayed to IRC as it's containing too many characters!##end##";
					$this->spam[2][$this->spam[0][2] + 1] = time();
					if ($this->spam[0][2] == 5)
					{
						if ($this->spam[2][1] > time() - 30)
						{
							$this->irc_disconnect();
							$msg2 = "IRC Spam Detected, Disconnecting IRC";
						}
						$this->spam[0][2] = 0;
					}
					else
						$this->spam[0][2] ++;
					$this->bot->send_gc($msg2);
				}
			}
		}
	}

	/*
	This gets called on a msg in the privgroup without a command
	*/
	function privgroup($name, $msg)
	{
		$msg = str_replace("&gt;", ">", $msg);
		$msg = str_replace("&lt;", "<", $msg);
		if (($this->irc != null) && ((strtolower($this->bot->core("settings")->get("Irc", "Chat")) == "pgroup") || (strtolower($this->bot->core("settings")->get("Irc", "Chat")) == "both")))
		{
			if (! $this->send_irc($this->bot->core("settings")->get("Irc", "Ircguestprefix"), $name, $msg))
			{
				if ($this->bot->core("settings")->get("Irc", "Notifyondrop"))
				{
					$msg2 = "##error##Last line not relayed to IRC as it's containing too many characters!##end##";
					$this->spam[3][$this->spam[0][3] + 1] = time();
					if ($this->spam[0][3] == 5)
					{
						if ($this->spam[3][1] > time() - 30)
						{
							$this->irc_disconnect();
							$msg2 = "IRC Spam Detected, Turning Off AutoReconnect";
						}
						$this->spam[0][3] = 0;
					}
					else
						$this->spam[0][3] ++;
					$this->bot->send_pgroup($msg2);
				}
				return;
			}
		}
	}

	/*
	This gets called on cron
	*/
	function cron()
	{
		if (($this->irc != null) && (! $this->irc->_rawreceive()))
		{
			$this->irc_disconnect();
			$this->bot->send_gc("IRC connection lost...");
			$this->spam[1][$this->spam[0][1] + 1] = time();
			if ($this->spam[0][1] >= 2)
			{
				if ($this->spam[1][1] > time() - 30)
				{
					$this->change_reconnect("off");
					$this->bot->send_gc("IRC Spam Detected, Turning Off AutoReconnect");
				}
				$this->spam[0][1] = 0;
			}
			else
				$this->spam[0][1] ++;
			if ($this->bot->core("settings")->get("Irc", "Reconnect"))
			{
				$this->irc_connect();
			}
		}
	}

	/*
	This gets when bot connects
	*/
	function connect()
	{
		if ($this->bot->core("settings")->get("Irc", "Reconnect"))
		{
			$res = $this->bot->core("timer")->list_timed_events("IRC");
			if (! empty($res))
			{
				foreach ($res as $con)
				{
					$this->bot->core("timer")->del_timer("IRC", $con['id']);
				}
			}
			$this->bot->core("timer")->add_timer(true, "IRC", 30, "IRC-Connect", "internal", 0, "None");
		}
	}

	function timer($name, $prefix, $suffix, $delay)
	{
		if ($name == "IRC-Connect")
		{
			$this->irc_connect("c");
		}
	}

	/*
	This gets called when bot disconnects
	*/
	function disconnect()
	{
		if ($this->bot->core("settings")->get("irc", "connected"))
		{
			$this->irc_disconnect();
		}
	}

	/*
	This gets called if a buddy logs on/off
	*/
	function buddy($name, $msg)
	{
		if ($msg == 1 || $msg == 0)
		{
			// Only handle this if connected to IRC server
			if (! $this->bot->core("settings")->get("irc", "connected"))
			{
				return;
			}
			if ($this->last_log["st"] < time() - $this->bot->crondelay)
			{
				if (($this->bot->core("notify")->check($name)) && ((strtolower($this->bot->core("settings")->get("Irc", "AnnounceWhat")) == "buddies") || (strtolower($this->bot->core("settings")->get("Irc", "AnnounceWhat")) == "both")))
				{
					if ($msg == 1)
					{
						if ($this->last_log["on"][$name] < (time() - 5))
						{
							$id = $this->bot->core('player')->id($name);
							$who = $this->bot->core("whois")->lookup($name);
							if ($who instanceof BotError)
							{
								$res = $name . " logged on";
							}
							else
							{
								$res = "\"" . $name . "\"";
								if (! empty($who['firstname']))
								{
									$res = $who['firstname'] . " " . $res;
								}
								if (! empty($who['lastname']))
								{
									$res .= " " . $who['lastname'];
								}
								$res .= " (Lvl " . $who['level'] . " ";
								if ($this->bot->game == "ao")
									$res .= "/ " . $who['at_id'] . " (" . $who['at'] . ") ";
								$res .= $who['class'];
								if ($who['org_name'] != '')
								{
									$res .= ", " . $who['org_rank'] . " of " . $who['org'];
								}
								$res .= ") logged on";
							}
							$main = $this->bot->core("alts")->main($name);
							if ($main != $this->bot->core('player')->name($name))
							{
								$alts = " :: Alt of " . $main;
							}
							$this->send_irc($this->bot->core("settings")->get("Irc", "Ircguildprefix"), "", chr(2) . chr(3) . '3***' . chr(2) . " " . $res . $alts);
							$this->last_log["on"][$name] = time();
						}
					}
					else
					{
						if ($this->last_log["off"][$name] < (time() - 5))
						{
							$this->send_irc($this->bot->core("settings")->get("Irc", "Ircguildprefix"), "", chr(2) . chr(3) . '3***' . chr(2) . " " . $name . " has logged off.");
							$this->last_log["off"][$name] = time();
						}
					}
				}
				else if ((! $this->bot->core("notify")->check($name)) && isset($this->is[$name]))
				{
					if ($msg == 1)
						$msg = $name . " is online.";
					else
						$msg = $name . " is offline.";
					$this->irc->message(SMARTIRC_TYPE_CHANNEL, $this->is[$name], $msg);
					unset($this->is[$name]);
				}
				else if ((! $this->bot->core("notify")->check($name)) && isset($this->whois[$name]))
				{
					$msg = $this->whois_player($name) . " ";
					$this->irc->message(SMARTIRC_TYPE_CHANNEL, $this->whois[$name], $msg);
					unset($this->whois[$name]);
				}
			}
		}
	}

	function pgjoin($name)
	{
		// Only handle this if connected to IRC server
		if (! $this->bot->core("settings")->get("irc", "connected"))
		{
			return;
		}
		if ((strtolower($this->bot->core("settings")->get("Irc", "AnnounceWhat")) == "joins") || (strtolower($this->bot->core("settings")->get("Irc", "AnnounceWhat")) == "both"))
		{
			$this->send_irc($this->bot->core("settings")->get("Irc", "IrcGuestprefix"), "", chr(2) . chr(3) . '3***' . chr(2) . ' ' . $name . ' has joined the guest channel.');
		}
	}

	function pgleave($name)
	{
		// Only handle this if connected to IRC server
		if (! $this->bot->core("settings")->get("irc", "connected"))
		{
			return;
		}
		if ((strtolower($this->bot->core("settings")->get("Irc", "AnnounceWhat")) == "joins") || (strtolower($this->bot->core("settings")->get("Irc", "AnnounceWhat")) == "both"))
		{
			$this->send_irc($this->bot->core("settings")->get("Irc", "IrcGuestprefix"), "", chr(2) . chr(3) . '3***' . chr(2) . ' ' . $name . ' has left the guest channel.');
		}
	}

	/*
	* Change server to connect to
	*/
	function change_server($new)
	{
		$this->bot->core("settings")->save("Irc", "Server", $new);
		if ($this->irc == null)
			return "Server has been changed to ##highlight##$new##end##.";
		else
			return "Server has been changed to ##highlight##$new##end##. You must reconnect to the IRC server.";
	}

	/*
	* Change port to connect to
	*/
	function change_port($new)
	{
		$this->bot->core("settings")->save("Irc", "Port", $new);
		if ($this->irc == null)
			return "Port has been changed to ##highlight##$new##end##.";
		else
			return "Port has been changed to ##highlight##$new##end##. You must reconnect to the IRC server.";
	}

	/*
	* Change channel to connect to
	*/
	function change_chan($new)
	{
		//Make sure the channel has a leading #
		if (substr($new, 0, 1) !== '#')
		{
			$new = '#' . $new;
		}
		$this->bot->core("settings")->save("Irc", "Channel", $new);
		if ($this->irc == null)
			return "Channel has been changed to ##highlight##$new##end##.";
		else
			return "Channel has been changed to ##highlight##$new##end##. You must reconnect to the IRC server.";
	}

	/*
	* Change channel key for the channel
	*/
	function change_chankey($new)
	{
		$this->bot->core("settings")->save("Irc", "Channelkey", $new);
		return "Channelkey has been changed.";
	}

	/*
	* Change channel to connect to
	*/
	function change_nick($new)
	{
		$this->bot->core("settings")->save("Irc", "Nick", $new);
		if ($this->irc == null)
			return "Nick has been changed to ##highlight##$new##end##.";
		else
			return "Nick has been changed to ##highlight##$new##end##. You must reconnect to the IRC server.";
	}

	/*
	* Change guildprefix
	*/
	function change_guildprefix($new)
	{
		if ($new == "\"\"")
			$new = "";
		$this->bot->core("settings")->save("Irc", "Guildprefix", $new);
		return "Guild prefix has been changed.";
	}

	/*
	* Change ircprefix
	*/
	function change_ircprefix($new)
	{
		if ($new == "\"\"")
			$new = "";
		$this->bot->core("settings")->save("Irc", "Ircguildprefix", $new);
		return "IRC prefix has been changed.";
	}

	/*
	* Change announce
	*/
	function change_announce($new)
	{
		if (strtolower($new) == "on")
			$tmp = TRUE;
		else
			$tmp = FALSE;
		$this->bot->core("settings")->save("Irc", "Announce", $tmp);
		return "Announce has been switched " . $new . ".";
	}

	/*
	* Change reconnect
	*/
	function change_reconnect($new)
	{
		$tmp = 0;
		$stmp = FALSE;
		if (strtolower($new) == "on")
		{
			$tmp = 1;
			$stmp = TRUE;
		}
		$this->bot->core("settings")->save("Irc", "Reconnect", $stmp);
		if ($this->irc != null)
			$this->irc->setAutoReconnect($tmp);
		return "Reconnect has been switched " . $new . ".";
	}

	/*
	* Change guildprefix
	*/
	function change_relayguildname($new)
	{
		$this->bot->core("settings")->save("Irc", "Relayguildname", $new);
		return "Guildname for GC-Relay has been changed.";
	}

	/*
	* Change itemref
	*/
	function change_itemref($new)
	{
		$tmp = "AOMainframe";
		if (strtolower($new) == "auno")
		{
			$tmp = "AUNO";
		}
		$this->bot->core("settings")->save("Irc", "Itemref", $tmp);
		return "Itemref has been switched to " . $new . ".";
	}

	/*
	* Change chat
	*/
	function change_chat($new)
	{
		$tmp = strtolower($new);
		$this->bot->core("settings")->save("Irc", "Chat", $tmp);
		return "Chat has been switched to " . $new . ".";
	}

	/*
	* Connect(!!!)
	*/
	function irc_connect($name = "")
	{
		$server = $this->bot->core('settings')->get('Irc', 'Server');
		$channel = $this->bot->core('settings')->get('Irc', 'Channel');
		$nick = $this->bot->core('settings')->get('Irc', 'Nick');
		//Sanity check some values.
		if (empty($server))
		{
			$this->error->set("An IRC server was not defined. Please see <pre>settings irc");
			return $this->error;
		}
		if ((empty($channel)) || ($channel === '#'))
		{
			$this->error->set("An IRC channel was not defined. Please see <pre>settings irc");
			return $this->error;
		}
		if (empty($nick))
		{
			$this->error->set("A Nickname was not defined. Please see <pre>settings irc");
			return $this->error;
		}
		//Make sure that the channel name is prefixed with a '#'
		if (substr($channel, 0, 1) !== '#')
		{
			$this->bot->core('settings')->save('Irc', 'Channel', '#' . $channel);
		}
		if (($name != "") && ($name != "c"))
			$this->bot->send_tell($name, "Connecting to IRC server: " . $this->bot->core("settings")->get("Irc", "Server"));
		else if ($name == "")
			$this->bot->send_gc("Connecting to IRC server: " . $this->bot->core("settings")->get("Irc", "Server"));
		$this->irc = new Net_SmartIRC();
		$this->irc->setUseSockets(TRUE);
		$this->irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, $this->bot->commpre . 'online', $this->bot->commands["tell"]["irc"], 'irc_online');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, $this->bot->commpre . 'whois', $this->bot->commands["tell"]["irc"], 'irc_whois');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, $this->bot->commpre . 'is (.*)', $this->bot->commands["tell"]["irc"], 'irc_is');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, $this->bot->commpre . 'level (.*)', $this->bot->commands["tell"]["irc"], 'irc_level');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, $this->bot->commpre . 'lvl (.*)', $this->bot->commands["tell"]["irc"], 'irc_level');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, $this->bot->commpre . 'pvp (.*)', $this->bot->commands["tell"]["irc"], 'irc_level');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_QUERY, $this->bot->commpre . 'level (.*)', $this->bot->commands["tell"]["irc"], 'irc_level');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_QUERY, $this->bot->commpre . 'lvl (.*)', $this->bot->commands["tell"]["irc"], 'irc_level');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_QUERY, $this->bot->commpre . 'pvp (.*)', $this->bot->commands["tell"]["irc"], 'irc_level');
		//$this -> irc -> registerActionhandler(SMARTIRC_TYPE_QUERY, $this -> bot -> commpre . 'command', $this -> bot -> commands["tell"]["irc"], 'command');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_QUERY, $this->bot->commpre . 'is (.*)', $this->bot->commands["tell"]["irc"], 'irc_is');
		//$this -> irc -> registerActionhandler(SMARTIRC_TYPE_QUERY, $this -> bot -> commpre . 'tell (.*)', $this -> bot -> commands["tell"]["irc"], 'ao_msg');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_QUERY, $this->bot->commpre . 'online', $this->bot->commands["tell"]["irc"], 'irc_online');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_QUERY, $this->bot->commpre . 'whois', $this->bot->commands["tell"]["irc"], 'irc_whois');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_QUERY, $this->bot->commpre . 'uid (.*)', $this->bot->commands["tell"]["irc"], 'irc_uid');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_NAME, '.*', $this->bot->commands["tell"]["irc"], 'irc_query');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '.*', $this->bot->commands["tell"]["irc"], 'irc_recieve');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_JOIN, '.*', $this->bot->commands["tell"]["irc"], 'irc_join');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_NICKCHANGE, '.*', $this->bot->commands["tell"]["irc"], 'irc_nick');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_PART, '.*', $this->bot->commands["tell"]["irc"], 'irc_part');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_QUIT, '.*', $this->bot->commands["tell"]["irc"], 'irc_part');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_KICK, '.*', $this->bot->commands["tell"]["irc"], 'irc_part');
		$this->irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '.*', $this->bot->commands["tell"]["irc"], 'irc_recieve_msg');
		$this->irc->setCtcpVersion($this->bot->botversionname . " (" . $this->bot->botversion . ")");
		$this->irc->setAutoReconnect((($this->bot->core("settings")->get("Irc", "Reconnect")) ? 1 : 0));
		$this->irc->connect($this->bot->core("settings")->get("Irc", "Server"), $this->bot->core("settings")->get("Irc", "Port"));
		$this->irc->login($this->bot->core("settings")->get("Irc", "Nick"), 'BeBot', 0, 'BeBot');
		$this->irc->join(array($this->bot->core("settings")->get("Irc", "Channel")), $this->bot->core("settings")->get("Irc", "Channelkey"));
		$this->register_event("cron", "1sec");
		$this->bot->core("settings")->save("irc", "connected", TRUE);
		$this->bot->db->query("UPDATE #___online SET status_gc = 0 WHERE botname = '" . $this->bot->botname . " - IRC'");
		return "Done connecting...";
	}

	/*
	* Disconnect(!!!)
	*/
	function irc_disconnect()
	{
		if ($this->irc != null)
		{
			$this->irc->disconnect();
			$this->irc = null;
			$this->unregister_event("cron", "1sec");
			$this->bot->core("settings")->save("irc", "connected", FALSE);
			$this->bot->db->query("UPDATE #___online SET status_gc = 0 WHERE botname = '" . $this->bot->botname . " - IRC'");
			return "Disconnected from IRC server.";
		}
		else
			return "IRC already disconnected.";
	}

	/*
	* Gets called for an inc IRC message
	*/
	function irc_recieve(&$irc, &$data)
	{
		if ((strtolower($data->message) != strtolower(str_replace("\\", "", $this->bot->commpre . 'online'))) && (strtolower($data->message) != strtolower(str_replace("\\", "", $this->bot->commpre . 'is'))) && (strtolower($data->message) != strtolower(str_replace("\\", "", $this->bot->commpre . 'whois'))) && (strtolower($data->message) != strtolower(str_replace("\\", "", $this->bot->commpre . 'level'))))
		{
			$msg = str_replace("<", "&lt;", $data->message);
			$msg = str_replace(">", "&gt;", $msg);
			// Turn item refs back to ingame format
			$itemstring = "<a href=\"itemref://\\3/\\4/\\5\">\\1</a>";
			$msg = preg_replace("/" . chr(3) . chr(3) . "(.+?)" . chr(3) . " " . chr(3) . "\((.+?)id=([0-9]+)&id2=([0-9]+)&ql=([0-9]+)\)" . chr(3) . chr(3) . "/iU", $itemstring, $msg);
			$msg = preg_replace("/" . chr(3) . chr(3) . "(.+?)" . chr(3) . " " . chr(3) . "\((.+?)LowID=([0-9]+)&HiID=([0-9]+)&QL=([0-9]+)\)" . chr(3) . chr(3) . "/iU", $itemstring, $msg);
			// Check if it's relayed chat of another bot
			if (preg_match("/" . chr(2) . chr(2) . chr(2) . "(.+)" . chr(2) . "(.+)/i", $msg, $info))
			{
				$txt = "##irc_group##" . $info[1] . "##end## ##irc_text##" . $info[2] . "##end##";
			}
			else
			{
				$txt = "##irc_group##" . $this->bot->core("settings")->get("Irc", "Guildprefix") . "##end## ##irc_user##" . $data->nick . ":##end####irc_text## " . $msg . "##end##";
			}
			$this->bot->send_output("", $txt, $this->bot->core("settings")->get("Irc", "Chat"));
			if ($this->bot->core("settings")->get("Irc", "Useguildrelay") && $this->bot->core("settings")->get("Relay", "Relay"))
			{
				$this->bot->core("relay")->relay_to_bot($txt);
			}
			if (! empty($this->ircmsg))
			{
				foreach ($this->ircmsg as $send)
					$send->irc($data->nick, $msg, "msg");
			}
		}
	}

	/*
	* Gets called when someone joins IRC chan
	*/
	function irc_join(&$irc, &$data)
	{
		if (($data->nick != $this->bot->core("settings")->get("Irc", "Nick")) && (strtolower($this->bot->core("settings")->get("Irc", "AnnounceTo")) != "none"))
		{
			$msg = "##irc_group##" . $this->bot->core("settings")->get("Irc", "Guildprefix") . "##end## ##highlight##" . $data->nick . "##end## has logged##highlight## on##end##.";
			$this->bot->send_output("", $msg, $this->bot->core("settings")->get("Irc", "AnnounceTo"));
		}
		if (($data->nick != $this->bot->core("settings")->get("Irc", "Nick")))
		{
			$this->irconline[strtolower($data->nick)] = strtolower($data->nick);
			$this->bot->db->query("INSERT INTO #___online (nickname, botname, status_gc) VALUES ('" . $data->nick . "', '" . $this->bot->botname . " - IRC', 1) ON DUPLICATE KEY UPDATE status_gc = 1");
		}
		if ($this->bot->core("settings")->get("Irc", "Useguildrelay") && $this->bot->core("settings")->get("Relay", "Relay"))
		{
			$this->bot->core("relay")->relay_to_bot($msg);
		}
		if (! empty($this->ircmsg))
		{
			foreach ($this->ircmsg as $send)
				$send->irc($data->nick, "", "join");
		}
	}

	/*
	* Gets called when someone leaves IRC chan
	*/
	function irc_part(&$irc, &$data)
	{
		if (($data->nick != $this->bot->core("settings")->get("Irc", "Nick")) && (strtolower($this->bot->core("settings")->get("Irc", "AnnounceTo")) != "none"))
		{
			$msg = "##irc_group##" . $this->bot->core("settings")->get("Irc", "Guildprefix") . "##end## ##highlight##" . $data->nick . "##end## has logged##highlight## off##end## (" . $data->message . ").";
			$this->bot->send_output("", $msg, $this->bot->core("settings")->get("Irc", "AnnounceTo"));
		}
		if (($data->nick != $this->bot->core("settings")->get("Irc", "Nick")))
			unset($this->irconline[strtolower($data->nick)]);
		$this->bot->db->query("UPDATE #___online SET status_gc = 0 WHERE botname = '" . $this->bot->botname . " - IRC' AND nickname = '" . $data->nick . "'");
		if ($this->bot->core("settings")->get("Irc", "Useguildrelay") && $this->bot->core("settings")->get("Relay", "Relay"))
		{
			$this->bot->core("relay")->relay_to_bot($msg);
		}
		if (! empty($this->ircmsg))
		{
			foreach ($this->ircmsg as $send)
				$send->irc($data->nick, "", "part");
		}
	}

	/*
	* Gets called when someone does !is
	*/
	function irc_is(&$irc, &$data)
	{
		if ($data->type == SMARTIRC_TYPE_QUERY)
		{
			$target = $data->nick;
		}
		else
		{
			$target = $this->bot->core("settings")->get("Irc", "Channel");
		}
		if (! preg_match("/^" . $this->bot->commpre . "is ([a-zA-Z0-9]{4,25})$/i", $data->message, $info))
			$msg = "Please enter a valid name.";
		else
		{
			$info[1] = ucfirst(strtolower($info[1]));
			$msg = "";
			if (! $this->bot->core('player')->id($info[1]))
				$msg = "Player " . $info[1] . " does not exist.";
			else if ($info[1] == ucfirst(strtolower($this->bot->botname)))
				$msg = "I'm online!";
			else if ($this->bot->core("chat")->buddy_exists($info[1]))
			{
				if ($this->bot->core("chat")->buddy_online($info[1]))
					$msg = $info[1] . " is online.";
				else
					$msg = $info[1] . " is offline.";
			}
			else
			{
				$this->is[$info[1]] = $target;
				$this->bot->core("chat")->buddy_add($info[1]);
			}
		}
		if (! empty($msg))
			$this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
	}

	/*
	* Gets called when someone does !uid
	*/
	function irc_uid(&$irc, &$data)
	{
		if ($data->type == SMARTIRC_TYPE_QUERY)
		{
			$target = $data->nick;
		}
		else
		{
			$target = $this->bot->core("settings")->get("Irc", "Channel");
		}
		if (! preg_match("/^" . $this->bot->commpre . "uid ([a-zA-Z0-9]{4,25})$/i", $data->message, $info))
			$msg = "Please enter a valid name.";
		else
		{
			$info[1] = ucfirst(strtolower($info[1]));
			$msg = $info[1] . ": " . $this->bot->core('player')->id($info[1]);
		}
		if (! empty($msg))
			$this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
	}

	/*
	* Gets called when someone does !online
	*/
	function irc_online(&$irc, &$data)
	{
		if ($data->type == SMARTIRC_TYPE_QUERY)
		{
			$target = $data->nick;
		}
		else
		{
			$target = $this->bot->core("settings")->get("Irc", "Channel");
		}
		$channels = "";
		if (strtolower($this->bot->core("settings")->get("Irc", "Chat")) == "both")
		{
			$channels = "(status_pg = 1 OR status_gc = 1)";
		}
		elseif (strtolower($this->bot->core("settings")->get("Irc", "Chat")) == "gc")
		{
			$channels = "status_gc = 1";
		}
		elseif (strtolower($this->bot->core("settings")->get("Irc", "Chat")) == "pgroup")
		{
			$channels = "status_pg = 1";
		}
		$online = $this->bot->db->select("SELECT DISTINCT(nickname) FROM #___online WHERE " . $this->bot->core("online")->otherbots() . " AND " . $channels . " ORDER BY nickname ASC");
		if (empty($online))
		{
			$msg = "Nobody online on notify!";
		}
		else
		{
			$msg = count($online) . " players online: ";
			$msgs = array();
			foreach ($online as $name)
			{
				$msgs[] = $name[0];
			}
			$msg .= implode(", ", $msgs);
		}
		$this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
	}

	function irc_send_local($msg)
	{
		if ($msg)
			$this->irc->message(SMARTIRC_TYPE_CHANNEL, $this->bot->core("settings")->get("Irc", "Channel"), $msg);
	}

	function whois_player($name)
	{
		$who = $this->bot->core("whois")->lookup($name);
		if (! $who)
		{
			$this->whois[$name] = $this->target;
		}
		elseif (! ($who instanceof BotError))
		{
			$at = "(AT " . $who["at_id"] . " - " . $who["at"] . ") ";
			$result = "\"" . $who["nickname"] . "\"";
			if (! empty($who["firstname"]) && ($who["firstname"] != "Unknown"))
			{
				$result = $who["firstname"] . " " . $result;
			}
			if (! empty($who["lastname"]) && ($who["lastname"] != "Unknown"))
			{
				$result .= " " . $who["lastname"];
			}
			if ($this->bot->game == "ao")
			{
				$result .= " is a level " . $who["level"] . " " . $at . "" . $who["gender"] . " " . $who["breed"] . " ";
				$result .= $who["profession"] . ", " . $who["faction"];
			}
			else
			{
				$result .= " is a level " . $who["level"] . " ";
				$result .= $who["class"];
			}
			if (! empty($who["rank"]))
			{
				$result .= ", " . $who["rank"] . " of " . $who["org"] . "";
			}
			if ($this->bot->core("settings")->get("Whois", "Details") == TRUE)
			{
				if ($this->bot->core("settings")->get("Whois", "ShowMain") == TRUE)
				{
					$main = $this->bot->core("alts")->main($name);
					if ($main != $name)
					{
						$result .= " :: Alt of " . $main;
					}
				}
			}
		}
		else
		{
			$result = $who;
		}
		return $result;
	}

	function irc_whois(&$irc, &$data)
	{
		if ($data->type == SMARTIRC_TYPE_QUERY)
		{
			$target = $data->nick;
		}
		else
		{
			$target = $this->bot->core("settings")->get("Irc", "Channel");
		}
		$this->target = $target;
		preg_match("/^" . $this->bot->commpre . "whois (.+)$/i", $data->message, $info);
		$info[1] = ucfirst(strtolower($info[1]));
		if (! $this->bot->core('player')->id($info[1]))
		{
			$msg = "Player " . $info[1] . " does not exist.";
		}
		else if ($this->bot->core("chat")->buddy_exists($info[1]))
		{
			$msg = $this->whois_player($info[1]);
		}
		else
		{
			$this->whois[$info[1]] = $target;
			$this->bot->core("chat")->buddy_add($info[1]);
		}
		$this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
	}

	function irc_nick(&$irc, &$data)
	{
		if ($data->nick != $this->bot->core("settings")->get("Irc", "Nick"))
		{
			unset($this->irconline[strtolower($data->nick)]);
			$this->irconline[strtolower($data->message)] = strtolower($data->message);
		}
		$txt = "##irc_group##" . $this->bot->core("settings")->get("Irc", "Guildprefix") . "##end## ##irc_user##" . $data->nick . "##end####irc_text## is known as##end## ##irc_user##" . $data->message . "##end##";
		if (strtolower($this->bot->core("settings")->get("Irc", "AnnounceTo")) != "none")
		{
			$this->bot->send_output("", $txt, $this->bot->core("settings")->get("Irc", "AnnounceTo"));
		}
		if ($this->bot->core("settings")->get("Irc", "Useguildrelay") && $this->bot->core("settings")->get("Relay", "Relay"))
		{
			$this->bot->core("relay")->relay_to_bot($txt);
		}
		$this->bot->db->query("UPDATE #___online SET nickname = '" . $data->message . "' WHERE botname = '" . $this->bot->botname . " - IRC' AND nickname = '" . $data->nick . "'");
	}

	// gets the names list on connection
	function irc_query(&$irc, &$data)
	{
		if (strcasecmp($data->channel, $this->bot->core("settings")->get("Irc", "Channel")) == 0)
		{
			$this->irconline = array();
			if (! empty($data->messageex))
			{
				foreach ($data->messageex as $ircuser)
				{
					$ircuser = ltrim($ircuser, '@+');
					if ($ircuser != $this->bot->core("settings")->get("irc", "nick"))
					{
						$this->irconline[strtolower($ircuser)] = strtolower($ircuser);
						$this->bot->db->query("INSERT INTO #___online (nickname, botname, status_gc) VALUES ('" . $ircuser . "', '" . $this->bot->botname . " - IRC', 1) ON DUPLICATE KEY UPDATE status_gc = 1");
					}
				}
			}
		}
	}

	/*
	This should show the names of everyone in the IRC Channel
	*/
	function names()
	{
		if ($this->bot->core("settings")->get("irc", "connected"))
		{
			$names = $this->irconline;
			if (empty($names))
			{
				$msg = 'Nobody online in ##highlight##' . $this->bot->core("settings")->get("Irc", "Channel") . '##end##!';
			}
			else
			{
				$msg = '##highlight##' . count($names) . '##end## users in ##highlight##' . $this->bot->core("settings")->get("Irc", "Channel") . '##end##: ';
				foreach ($names as $name)
					$msg .= '##highlight##' . $name . '##end##, ';
				$msg = substr($msg, 0, - 2);
			}
			return $msg;
		}
		else
			return 'Not connected to IRC';
	}

	/*
 	Level command for irc
 	*/
	function irc_level(&$irc, &$data)
	{
		if ($data->type == SMARTIRC_TYPE_QUERY)
			$target = $data->nick;
		else
			$target = $this->bot->core("settings")->get("Irc", "Channel");
		$msg = explode(" ", $data->message, 2);
		$msg = $this->bot->commands["tell"]["level"]->get_level($msg[1]);
		$msg = $this->strip_formatting($msg);
		if (! empty($msg))
			$this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
	}

	function irc_recieve_msg(&$irc, &$data)
	{
		$msg = explode(" ", $data->message, 2);
		Switch ($msg[0])
		{
			case $this->bot->commpre . 'is':
			case $this->bot->commpre . 'online':
			case $this->bot->commpre . 'whois':
			case $this->bot->commpre . 'uid':
			case $this->bot->commpre . 'level':
			case $this->bot->commpre . 'lvl':
			case $this->bot->commpre . 'pvp':
				Break; //These should of been handled elsewere
			case 'is':
				$data->message = $this->bot->commpre . $data->message;
				$this->irc_is($irc, $data);
				Break;
			case 'online':
				$data->message = $this->bot->commpre . $data->message;
				$this->irc_online($irc, $data);
				Break;
			case 'whois':
				$data->message = $this->bot->commpre . $data->message;
				$this->irc_whois($irc, $data);
				Break;
			case 'uid':
				$data->message = $this->bot->commpre . $data->message;
				$this->irc_uid($irc, $data);
				Break;
			case 'level':
			case 'lvl':
			case 'pvp':
				$data->message = $this->bot->commpre . $data->message;
				$this->irc_level($irc, $data);
				Break;
			Default:
				if ($data->type == SMARTIRC_TYPE_QUERY)
					$target = $data->nick;
				else
					$target = $this->bot->core("settings")->get("Irc", "Channel");
				$msg = "Error: Unknown Command " . $msg[0];
				$this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
		}
	}
}
?>