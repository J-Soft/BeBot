<?php
/*
 * Logon.php - Announces logon/logoff events in guildchat
 *
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2009 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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
$Logon = new Logon($bot);
/*
The Class itself...
*/
class Logon extends BaseActiveModule
{
	var $last_log;
	var $start;

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("logon", "true") . "
				(id INT NOT NULL PRIMARY KEY,
				message VARCHAR(255))");
		$this->last_log = array();
		$this->start = time() + 3600;
		$this->help['description'] = 'Announces logon logoff events in guildchat.';
		$this->help['command']['logon <message>'] = "Sets a custom logon message to be displayed when you log on.";
		$this->help['command']['logon'] = "Deletes your custom logon message.";
		$this->register_command("all", "logon", "MEMBER");
		$this->register_event("buddy");
		$this->register_event("connect");
		$this->bot->core("colors")->define_scheme("logon", "logon_spam", "darkaqua");
		$this->bot->core("colors")->define_scheme("logon", "level", "lightteal");
		$this->bot->core("colors")->define_scheme("logon", "ailevel", "lightgreen");
		$this->bot->core("colors")->define_scheme("logon", "organization", "darkaqua");
		$this->bot->core("colors")->define_scheme("logon", "logoff_spam", "yellow");
		$this->bot->core("settings")->create("Logon", "Enable", TRUE, "Should logon spam be enabled at all?");
		$this->bot->core("settings")->create("Logon", "Members", TRUE, "Should Members logon be spamed?");
		$this->bot->core("settings")->create("Logon", "Guests", FALSE, "Should Guests logon be spamed?");
		$this->bot->core("settings")->create("Logon", "Others", FALSE, "Should Others logon be spamed?");
		$this->bot->core("settings")->create("Relay", "Logon", FALSE, "Should logon spam be relayed to the linked org bots?");
		$this->bot->core("settings")->create("Relay", "LogonInPgroup", TRUE, "Should logons be shown in the private group of the bot too?");
		$this->bot->core("settings")->create("Relay", "OrgLogon", FALSE, "Should prefixing the org channel shortcut to the logon information be used when relaying logons?");
		$this->bot->core("settings")->create("Relay", "Alias", TRUE, "Should a Users Main Alias be Shown with logon message?");
	}

	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^logon (.+)/i", $msg, $info))
		{
			return $this->set_msg($name, $info[1]);
		}
		elseif (preg_match("/^logon$/i", $msg, $info))
		{
			return $this->set_msg($name, '');
		}
		return false;
	}

	function buddy($name, $msg)
	{
		if ($msg == 1 || $msg == 0)
		{
			if (($this->start < time()) && ($this->bot->core("settings")->get("Logon", "Enable")))
			{
				if ($this->bot->core("notify")->check($name))
				{
					$level = $this->bot->db->select("SELECT user_level FROM #___users WHERE nickname = '$name'");
					if (! empty($level))
						$level = $level[0][0];
					else
						$level = 0;
					if ($level == "2")
					{
						if ($this->bot->core("settings")->get("Logon", "Members"))
							$spam = TRUE;
					}
					elseif ($level == "1")
					{
						if ($this->bot->core("settings")->get("Logon", "Guests"))
							$spam = TRUE;
					}
					elseif ($this->bot->core("settings")->get("Logon", "Others"))
						$spam = TRUE;
					if ($spam)
					{
						$id = $this->bot->core("chat")->get_uid($name);
						if (! isset($this->last_log["on"][$name]))
						{
							$this->last_log["on"][$name] = 0;
						}
						if (! isset($this->last_log["off"][$name]))
						{
							$this->last_log["off"][$name] = 0;
						}
						if ($msg == 1)
						{
							if ($this->last_log["on"][$name] < (time() - 5))
							{
								$result = $this->bot->core("whois")->lookup($name);
								if ($result instanceof BotError)
									$result = array("level" => 0);
								$aliasm = $this->bot->core("alias")->get_main($name);
								if ($aliasm)
								{
									$res = "##highlight##" . $aliasm . "##end## Logged On";
									$res .= " (" . $name . " ";
								}
								else
								{
									$res = "\"" . $name . "\"";
									if (! empty($result["firstname"]))
									{
										$res = $result["firstname"] . " " . $res;
									}
									if (! empty($result["lastname"]))
									{
										$res .= " " . $result["lastname"];
									}
									$res .= " (";
								}
								$res .= "Lvl ##logon_level##" . $result["level"] . "##end##";
								if ($this->bot->game == "ao")
								{
									$res .= "/##logon_ailevel##" . $result["at_id"] . "##end## " . $result["faction"] . " " . $result["profession"];
									if ($result["org"] != '')
									{
										$res .= ", ##logon_organization##" . $result["rank"] . " of " . $result["org"] . "##end##";
									}
								}
								else
									$res .= " " . $result["class"];
								$res .= ")";
								if (! $aliasm)
									$res .= " Logged On";
								if ($this->bot->core("settings")->get("Whois", "Details") == TRUE)
								{
									if ($this->bot->core("settings")->get("Whois", "ShowMain") == TRUE)
									{
										$main = $this->bot->core("alts")->main($name);
										if (strcasecmp($main, $name) != 0)
										{
											$res .= " :: Alt of $main";
										}
									}
									$res .= " :: " . $this->bot->core("whois")->whois_details($name, $result);
								}
								else if ($this->bot->core("settings")->get("Whois", "Alts") == TRUE)
								{
									$alts = $this->bot->core("alts")->show_alt($name);
									if ($alts['alts'])
									{
										$res .= " :: " . $alts['list'];
									}
								}
								$result = $this->bot->db->select("SELECT message FROM #___logon WHERE id = " . $id);
								if (! empty($result))
								{
									$res .= "  ::  " . stripslashes($result[0][0]);
								}
								$this->show_logon("##logon_logon_spam##" . $res . "##end##");
								$this->last_log["on"][$name] = time();
							}
						}
						else
						{
							if ($this->last_log["off"][$name] < (time() - 5))
							{
								$this->show_logon("##logon_logoff_spam##" . $name . " logged off##end##");
								$this->last_log["off"][$name] = time();
							}
						}
					}
				}
			}
		}
	}

	function show_logon($txt)
	{
		$this->bot->send_gc($txt);
		if ($this->bot->core("settings")->get("Relay", "Logoninpgroup"))
		{
			$this->bot->send_pgroup($txt);
		}
		if ($this->bot->core("settings")->get('Relay', 'Logon') && $this->bot->core("settings")->get('Relay', 'Status'))
		{
			$pre = "";
			if ($this->bot->core("settings")->get("Relay", "Orglogon"))
			{
				$pre = "##relay_channel##[" . $this->bot->core("settings")->get("Relay", "Gcname") . "]##end## ";
			}
			$this->bot->core("relay")->relay_to_bot($pre . $txt);
		}
	}

	function connect()
	{
		$this->start = time() + 3 * $this->bot->crondelay;
	}

	function set_msg($name, $message)
	{
		$id = $this->bot->core("chat")->get_uid($name);
		$message = mysql_real_escape_string($message);
		$this->bot->db->query("REPLACE INTO #___logon (id, message) VALUES ('" . $id . "', '" . $message . "')");
		return "Thank you " . $name . ". You logon message has been set.";
	}
}
?>