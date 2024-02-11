<?php
/*
*  Recruit.php - Guild Recruitment Helper Script
*  Developed by Meathooks & Elesar of the Eldar Guild (AoC / Dagoth)
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

$recruit = new Recruit($bot);

class Recruit extends BaseActiveModule
{
	var $bot;
	var $verify;

	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this -> verify = array();

		$this -> register_command('tell', 'recruit', 'ANONYMOUS');
		$this -> help['description'] = 'Allows a person to send a tell to the bot to start the recruitment process. Uses the !news system to warn offline officers.';
		$this -> register_event("cron", "59min");
		$this -> bot -> core("settings") -> create("Recruit", "GuildName", "", "Guild Name to use for all messages (tells, public, etc).");
		$this -> bot -> core("settings") -> create("Recruit", "LastOfficer", $this->bot->owner, "Last Officer to receive a recruitment tell message");
		$this -> bot -> core("settings") -> create("Recruit", "LastOfficer1", $this->bot->owner, "Last Officer to receive a recruitment tell message");
		$this -> bot -> core("settings") -> create("Recruit", "LastOfficer2", $this->bot->owner, "Last Officer to receive a recruitment tell message");
		$this -> bot -> core("settings") -> create("Recruit", "LastOfficerNo", 2, "Last Officer slot used");
		$this -> bot -> core("settings") -> create("Recruit", "SpamPublic", false, "Should the bot be spamming a public channel every hour ?", "On;Off");	
		$this -> bot -> core("settings") -> create("Recruit", "WhatChan", "NewbieHelp", "Which channel (access depends if toon is free or paid) should be spammed every hour (can be randomized) ?", "NewbieHelp;Trial;Random");		
	}

	function command_handler($name, $msg, $origin)
	{
		$com = $this->parse_com($msg, array("com", "args"));
		switch($com['com'])
		{
		    case 'recruit':
			return $this->do_recruit($name);		
			break;

		    case 'default':
			$this->bot->send_help($name);
		}
	}

	function do_recruit($name)
	{
		$guildname = str_replace("'","`",$this->bot->core("settings")->get("Recruit","GuildName"));
		$lastofficer = $this->bot->core("settings")->get("Recruit","LastOfficer");
		$lastofficer1 = $this->bot->core("settings")->get("Recruit","LastOfficer1");
		$lastofficer2 = $this->bot->core("settings")->get("Recruit","LastOfficer2");
		$lastofficerno = $this->bot->core("settings")->get("Recruit","LastOfficerNo");

		//$return = $this->bot->core("whois")->lookup($name);

		//$tell_officer_msg = "##highlight##RECRUITMENT:##end## Please contact ##highlight##$name (a lvl " . $return['level'] . " " . $return['profession'] . ")##end##, he/she has requested recruitment information.  Thank you.";
		$tell_officer_msg = "##highlight##RECRUITMENT:##end## Please contact ##highlight##$name##end##, he/she has requested recruitment information.  Thank you.";
		$tell_reply_msg_found = "An Officer is ##highlight##Online##end##.  You will be contacted as soon as possible.  ##highlight##Thank you for your interest in $guildname.##end##";
		$tell_reply_msg_notfound = "There currently are no Guild Officers Online to speak to you about your request for recruitment information.  Your information has been stored and as soon as an Officer does logon, you will be contacted.  ##highlight##Thank you for your interest in $guildname.##end##";
		$newsfrom = "$guildname - Recruitment";
		//$newstext = "RECRUITMENT: ##highlight##$name (a lvl " . $return['level'] . " " . $return['profession'] . ")##end## requested recruitment information but there were no officers online.  Please contact ##highlight##$name##end## as soon as possible.  If you are the officer that is going to contact this person, please delete this news item so that other officers do not also contact him/her.  Thank you.";
		$newstext = "RECRUITMENT: ##highlight##$name##end## requested recruitment information but there were no officers online.  Please contact ##highlight##$name##end## as soon as possible.  If you are the officer that is going to contact this person, please delete this news item so that other officers do not also contact him/her.  Thank you.";

		$botstring = $this->bot->core("online")->otherbots();
		$channel = "gc";

		$online = $this -> bot -> db -> select("SELECT t1.nickname, t2.level, org_rank, org_name FROM "
				. "#___online AS t1 LEFT JOIN #___whois AS t2 ON t1.nickname = t2.nickname WHERE status_" . $channel . "=1" ." AND "
				. $botstring . "ORDER BY t1.nickname");

		$found = 0;
		$lastofficeronline = 0;
		if (!empty($online))
		{
			foreach ($online as $player)
			{
				$level = $this->bot->core("security")->get_access_name($this->bot->core("security")->get_access_level($player[0]));

				if ($level == 'LEADER' || $level == 'ADMIN' || $level == 'SUPERADMIN' || $level == 'OWNER')
				{
					if ($player[0] == $lastofficer || $player[0] == $lastofficer1 || $player[0] == $lastofficer2)
					{
						$found = 0;
						$lastofficeronline = 1;
					}
					else
					{
						$found = 1;
						$lastofficeronline = 0;
						$officer = $player[0];
						switch ($lastofficerno) 
						{
							case 0:
								$this->bot->core("settings")->save("Recruit", "LastOfficer1", $officer);					
								$this->bot->core("settings")->save("Recruit", "LastOfficerNo", 1);					
								break;
							case 1:
								$this->bot->core("settings")->save("Recruit", "LastOfficer2", $officer);					
								$this->bot->core("settings")->save("Recruit", "LastOfficerNo", 2);					
								break;
							case 2:
								$this->bot->core("settings")->save("Recruit", "LastOfficer", $officer);					
								$this->bot->core("settings")->save("Recruit", "LastOfficerNo", 0);					
								break;
						}
						break;
					}
				}
			}

			if ($found == 0)
			{
				if ($lastofficeronline == 1)
				{
					switch ($lastofficerno) 
					{
						case 0:
							$lastofficer = $this->bot->core("settings")->get("Recruit", "LastOfficer2");
							$this->bot->core("settings")->save("Recruit", "LastOfficerNo", 2);					
							if ($lastofficer == $this->bot->owner)
							{
								$lastofficer = $this->bot->core("settings")->get("Recruit", "LastOfficer1");
								$this->bot->core("settings")->save("Recruit", "LastOfficerNo", 1);
							}
							if ($lastofficer == $this->bot->owner)
							{
								$this->bot->core("settings")->save("Recruit", "LastOfficerNo", 0);
								$lastofficer = $this->bot->core("settings")->get("Recruit", "LastOfficer");
							}
							break;
						case 1:
							$lastofficer = $this->bot->core("settings")->get("Recruit", "LastOfficer1");					
							$this->bot->core("settings")->save("Recruit", "LastOfficerNo", 1);
							if ($lastofficer == $this->bot->owner)
							{
								$lastofficer = $this->bot->core("settings")->get("Recruit", "LastOfficer");
								$this->bot->core("settings")->save("Recruit", "LastOfficerNo", 0);
							}
							break;
						case 2:
							$lastofficer = $this->bot->core("settings")->get("Recruit", "LastOfficer");					
							$this->bot->core("settings")->save("Recruit", "LastOfficerNo", 0);
							break;
					}
					$officer = $lastofficer;
	
					$this->bot->send_tell($officer, $tell_officer_msg);
					return $tell_reply_msg_found;
				}
				else
				{
					$this->bot->db->query("INSERT INTO #___news (type, time, name, news) VALUES ('1', " . time() . 
								    ", '$newsfrom', '" . addslashes($newstext) . "')");
					return $tell_reply_msg_notfound;
				}
			}
			else
			{
				$this->bot->send_tell($officer, $tell_officer_msg);
				return $tell_reply_msg_found;
			}
		}
		else
		{
			$this->bot->db->query("INSERT INTO #___news (type, time, name, news) VALUES ('1', " . time() . 
						    ", '$newsfrom', '" . addslashes($newstext) . "')");
			return $tell_reply_msg_notfound;
		}
	}
	
    function cron()
    {
		if ($this->bot->core("settings")->get("Recruit", "SpamPublic")) {
			$whatchan = ucfirst($this->bot->core("settings")->get("Recruit", "WhatChan"));
			$channel = ""; $channels = array();
			if($whatchan=="Random") {			
				foreach($this->bot->aoc->gid as $key => $val) {
					if(preg_match('/\~([a-z]+)/i', $val, $match)) {
						$channels[] = substr($val,1);
					}
				}
				if(count($channels)>0) {
					$max = count($channels)-1;
					$rand = random_int(0,$max);
					$channel = "~".$channels[$rand];
				}
			} else {
				foreach($this->bot->aoc->gid as $key => $val) {
					if ($val == "~".$whatchan) $channel = "~".$whatchan;
				}
			}
			if($channel=="") {
				$this->bot->log("RECRUIT", "ERROR", "Bot has no access to channel ".$whatchan);
				return false;
			}
			$msg = str_replace("'","`",$this->bot->core("settings")->get("Recruit","GuildName"))." ";
			$blob = "";
			if (file_exists("./Text/" . $this->bot->botname . "Recruit.txt")) { // custom
				$blob .= implode("", file("./Text/" . $this->bot->botname . "Recruit.txt"));
			} elseif (file_exists("./Text/Recruit.txt")) {
				$blob .= implode("", file("./Text/Recruit.txt"));
			}
			if($blob=="") { // default
				$blob = "<a href=\"text://To initiate recruitment process: <a href='chatcmd:///tell ".$this->bot->botname." recruit'>Start application</a><br><br>\">Click this!</a>";
			}
			if(mb_detect_encoding($msg, 'UTF-8', false)) $msg = mb_convert_encoding($msg, 'UTF-8', mb_list_encodings());
			if(mb_detect_encoding($blob, 'UTF-8', false)) $blob = mb_convert_encoding($blob, 'UTF-8', mb_list_encodings());
			$this -> bot -> aoc -> send_group($channel,$msg.$blob);
			$this->bot->log("RECRUIT", "NOTICE", "Sent recruit message on ".$channel);
		}
    }	
	
}
?>