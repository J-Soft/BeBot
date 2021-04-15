<?php
/*
* TeamSpeak.php - Adds TeamSpeak support to the bot
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
		
		$this -> bot -> core("settings") -> create("Teamspeak", "tsip", "141.157.197.21", "What is TS server IP ?");		
		$this -> bot -> core("settings") -> create("Teamspeak", "tsqp", "10011", "What is TS Query Port ?");		
		$this -> bot -> core("settings") -> create("Teamspeak", "tssp", "9987", "What is TS Server Port ?");		
		$this -> bot -> core("settings") -> create("Teamspeak", "tssn", "AoSpeak", "What is TS Server Name ?");		
		$this -> bot -> core("settings") -> create("Teamspeak", "tsdh", "voice.aospeak.com", "What is TS Display Host ?");
		
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
			fputs($connection1,"sel ".$this->bot->core("settings")->get("Teamspeak","tssp")."\n");
			fputs($connection1,"si\n");
			fputs($connection1,"quit\n");

			while(!feof($connection1)){
				$infolines.=fgets($connection1,1024);
			}
			$infolines = str_replace("[TS]","",$infolines);
			$infolines = str_replace("OK","",$infolines);
			$infolines = trim($infolines);

			// Servername, here but not used, uncomment if you want to use it
			$name=substr($infolines,$this -> indexOf($infolines,"server_name="),strlen($infolines));
			$name=substr($name,0,$this -> indexOf($name,"server_platform=")-strlen("server_platform="));

			// Connected Users
			$user=substr($infolines,$this -> indexOf($infolines,"server_currentusers="),strlen($infolines));
			$user=substr($user,0,$this -> indexOf($user,"server_currentchannels=")-strlen("server_currentchannels="));

			// Server Max Users
			$maxusers=substr($infolines, $this -> indexOf($infolines,"server_maxusers="),strlen($infolines));
			$maxusers=substr($maxusers,0,$this -> indexOf($maxusers,"server_allow_codec_celp51=")-strlen("server_allow_codec_celp51="));

			// Number of channels, here but not used, uncomment if you want to use it
			$numchan=substr($infolines,$this -> indexOf($infolines,"server_currentchannels="),strlen($infolines));
			$numchan=substr($numchan,0,$this -> indexOf($numchan,"server_bwinlastsec=")-strlen("server_bwinlastsec="));

		} else {
			return "Can´t connect to the TS server. Pls try again later.";
		}

		$msg = "##lightgray##::::: Teamspeak Server Info :::::##end##\n\n";
		$msg .="Get the client:	\"http://www.goteamspeak.com\" \n\n";
		$msg .="Note: To connect use ##highlight##".$this->bot->core("settings")->get("Teamspeak","tsdh").":".$this->bot->core("settings")->get("Teamspeak","tssp")."##end## as the connect address\n";
		$msg .="Server Name: ##highlight##".$this->bot->core("settings")->get("Teamspeak","tssn")."##end##\n";
		$msg .="Server Address: ##highlight##".$this->bot->core("settings")->get("Teamspeak","tsdh")."##end##\n";
		$msg .="Server Port: ##highlight##".$this->bot->core("settings")->get("Teamspeak","tssp")."##end##\n\n";
		$msg .="Number of Players Currently Connected: ##highlight##".$user."##end##\n";
		$msg .="Server Maximum: ##highlight##".$maxusers."##end##\n";
		$msg .="Players (Time Connected):\n";

		$player_array = $this -> getTSChannelUsers($this->bot->core("settings")->get("Teamspeak","tsip"), $this->bot->core("settings")->get("Teamspeak","tssp"), $this->bot->core("settings")->get("Teamspeak","tsqp"));
		$count=count($player_array);
		if ($count > 1) {
			$arraycount=1;
			while ($count > 1) {
				$player=$player_array[$arraycount][14];
				$player=str_replace("\"","",$player);
				$player=ucfirst($player);
				$time=$player_array[$arraycount][8];
				$time=$this -> time_convert($time);

				$msg .= '##highlight##'.$player."##end##($time)\n";
				$count--;
				$arraycount++;
			}
		} else {
			 $msg .= '##highlight##No Players Connected##end##';
		}
		return $this -> bot -> core("tools") -> make_blob("Teamspeak server status", '##omni##'.$msg.'##end##');
	}

	function indexOf($str,$strChar)
	{
		 if(strlen(strchr($str,$strChar))>0) {
				$position_num = strpos($str,$strChar) + strlen($strChar);
				return $position_num;
		 } else {
				return -1;
		 }
	}

	function getTSChannelUsers($ip,$port,$tPort)
	{
		$uArray = array();
		$innerArray = array();
		$out = "";
		$j = 0;
		$k = 0;

		$fp = @fsockopen($ip, $tPort, $errno, $errstr, 30);
		if($fp) {
				fputs($fp, "pl ".$port."\n");
				fputs($fp, "quit\n");
				while(!feof($fp)) {
					 $out .= fgets($fp, 1024);
				}
				$out = str_replace("[TS]", "", $out);
				$out = str_replace("loginname", "loginname\t", $out);
				$data = explode("\t", $out);
				$num = count($data);

				for($i=0;$i<count($data);$i++) {
					$innerArray[$j] = $data[$i];
					if($j>=15)
					{
						$uArray[$k]=$innerArray;
						$j = 0;
						$k = $k+1;
					} else {
						$j++;
					}
				}
				fclose($fp);
		 }
			return $uArray;
	}

	function time_convert($time)
	{
		$hours = floor($time/3600);
		$minutes = floor(($time%3600)/60);
		$seconds = floor(($time%3600)%60);

		if($hours>0) $time = $hours."h ".$minutes."m ".$seconds."s";
		else if($minutes>0) $time = $minutes."m ".$seconds."s";
		else $time = $seconds."s";

		return $time;
	}
}

?>
