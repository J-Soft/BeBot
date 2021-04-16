<?php
/*
* TeamSpeak.php - Adds TeamSpeak3 support to the bot
* Bitnykk module from available Bebot archives
* Thanks Teknologist & AoSpeak to accept being default server
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

$teamspeak = new Teamspeak($bot);

class Teamspeak Extends BaseActiveModule
{
	
	function __construct (&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this -> register_command('all', 'ts', 'MEMBER');
		$this -> help['description'] = "Shows information about teamspeak.";
		$this -> help['command']['ts'] = "See description";
		
		$this -> bot -> core("settings") -> create("Teamspeak", "tsip", "141.157.197.21", "What is TS server fixed IP or dynamic hostname ?");
		$this -> bot -> core("settings") -> create("Teamspeak", "tsqp", "10011", "What is TS Query Port ?");
		$this -> bot -> core("settings") -> create("Teamspeak", "tssp", "9987", "What is TS Server Port ?");
		$this -> bot -> core("settings") -> create("Teamspeak", "tssn", "AoSpeak", "What is TS Server Name ?");
		$this -> bot -> core("settings") -> create("Teamspeak", "tsdh", "voice.aospeak.com", "What is TS Display Host ?");
		$this -> bot -> core("settings") -> create("Teamspeak", "tssi", "1", "What is TS Server Id ?");
		$this -> bot -> core("settings") -> create("Teamspeak", "tsln", "", "What is TS Login Name ?");
		$this -> bot -> core("settings") -> create("Teamspeak", "tslp", "", "What is TS Login Password ?");
		
	}

	function command_handler($name, $msg, $origin)
	{
		$this->error->reset(); //Reset the error message so we don't trigger the handler by old error messages.

		$com = $this->parse_com($msg, array('com', 'args'));

		if(empty($com['args']))
			return $this -> show_tstatus();

		return "I do not understand the command: $command";
	}


	function show_tstatus()
	{
		$msg = "TeamSpeak Server Status";
		$blob = "";
		$infolines="";
		
		$timeToEnd = (time() + 5);
		while (!($connection1 = @fsockopen($this->bot->core("settings")->get("Teamspeak","tsip"),$this->bot->core("settings")->get("Teamspeak","tsqp"), $errno, $errstr, 30)) AND (time() < $timeToEnd)) {
			if (time() >= $timeToEnd) {
				return false;
			}
		}
		if ($connection1) {
			echo " connected ";
			if ($this->bot->core("settings")->get("Teamspeak","tsln")) {
				fputs($connection1, "login client_login_name=".$this->bot->core("settings")->get("Teamspeak","tsln")." client_login_password=".$this->bot->core("settings")->get("Teamspeak","tslp")."\n");
			}
			fputs($connection1, "use ".$this->bot->core("settings")->get("Teamspeak","tssi")."\n");			
			fputs($connection1, "channellist\n");	
			fputs($connection1, "clientlist\n");	
			fputs($connection1,"quit\n");
			while(!feof($connection1)){
				$infolines.=fgets($connection1,1024);
			}
			$infolines = preg_replace( "/\r|\n/", "",$infolines);
			preg_match_all("/cid=([0-9]+) pid=(?:[0-9]+) channel_order=(?:[0-9]+) channel_name=([^ ]+) total_clients=([0-9]+) channel_needed_subscribe_power=(?:[0-9]+)/i",$infolines,$channels);
			$chtot = count($channels[0]);
			preg_match_all("/clid=(?:[0-9]+) cid=([0-9]+) client_database_id=(?:[0-9]+) client_nickname=([^ ]+) client_type=0/i",$infolines,$users);
			$cltot = count($users[0]);
		} else {
			return "Can´t connect to the TS server. Pls try again later.";
		}
		$msg = "##lightgray##::::: Teamspeak Server Info :::::##end##\n\n";
		$msg .="Get the client:	\"https://www.teamspeak.com\" \n\n";
		$msg .="Note: To connect use ##highlight##".$this->bot->core("settings")->get("Teamspeak","tsdh").":".$this->bot->core("settings")->get("Teamspeak","tssp")."##end## as the connect address\n";
		$msg .="Server Name: ##highlight##".$this->bot->core("settings")->get("Teamspeak","tssn")."##end##\n";
		$msg .="Server Address: ##highlight##".$this->bot->core("settings")->get("Teamspeak","tsdh")."##end##\n";
		$msg .="Server Port: ##highlight##".$this->bot->core("settings")->get("Teamspeak","tssp")."##end##\n\n";
		$msg .="Total of channels: ##highlight##".$chtot."##end##\n";
		$msg .="Number of Players Currently Connected: ##highlight##".$cltot."##end##\n\n";
		foreach($channels[1] AS $num => $cid) {
			$tmp = ""; $uzr = 0;
			if($channels[3][$num]>0) {
				$tmp .="##highlight##".str_replace("\s"," ",$channels[2][$num]).":##end##\n";
				foreach($users[1] AS $id => $uid) {
					if($cid==$uid) {
						$tmp .=" ".$users[2][$id]." ";
						$uzr++;
					}
				}
				if($uzr>0) $msg .=$tmp."\n\n";
			}
		}	
		return $this -> bot -> core("tools") -> make_blob("Teamspeak server status", '##omni##'.$msg.'##end##');
	}
	
}

?>
