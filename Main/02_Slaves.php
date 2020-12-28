<?php
/*
* 02_Slaves.php - Fully Automated Slave Bot Module
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
*
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

// Module Version 1.7

if(!empty($slaves))
	$slaves = new Slaves($slaves, $bot);

class Slaves
{
	var $lasttell;
	var $banmsgout;
	var $dimension;
	var $botversion;
	var $botversionname;
	var $other_bots;
	var $aoc;
	var $irc;
	var $db;
	var $commpre;
	var $crondelay;
	var $telldelay;
	var $maxsize;
	var $reconnecttime;
	var $guildbot;
	var $guildid;
	var $guild;
	var $log;
	var $log_path;
	var $log_timestamp;
	var $use_proxy_server;
	var $proxy_server_address;
	var $starttime;
	var $commands;

	private $module_links;
	private $cron_times;
	private $cron_job_timer;
	private $cron_job_active;
	private $cron_actived;
	private $cron;
	private $startup_time;
	private $slavesl;
	public $slaves;
	public $buddy_status = array();
	public $glob;
	public $botname;

	/*
	Constructor:
	Prepares bot.
	*/
	function __construct($slaves, $bot)
	{
		$this -> bot = $bot;
		$this -> mainbotname = $botname;
		//$this -> bot -> aoc -> slave = $this;
		/*$this -> dimension = ucfirst(strtolower($dim));
		$this -> botversion = $botversion;
		$this -> botversionname = $botversionname;
		$this -> other_bots = $other_bots;
		//$this -> aoc = &$aoc;
		$this -> irc = &$irc;
		$this -> bot -> db = &$db;
		$this -> bot -> commands = array();
		$this -> commpre = $commprefix;
		$this -> cron = array();
		$this -> crondelay = $crondelay;
		$this -> telldelay = $telldelay;
		$this -> maxsize = $maxsize;
		$this -> reconnecttime = $recontime;
		$this -> guildbot = $guildbot;
		$this -> guildid = $guildid;
		$this -> guildname = $guild;
		$this -> bot -> log = $log;
		$this -> bot -> log_path = $log_path;
		$this -> bot -> log_timestamp = $log_timestamp;
		$this -> banmsgout = array();
		$this -> use_proxy_server = $use_proxy_server;
		$this -> proxy_server_address = explode(",", $proxy_server_address);
		$this -> starttime = time();

		$this -> module_links = array();

		$this -> cron_times = array();
		$this -> cron_job_activate = array();
		$this -> cron_job_timer = array();
		$this -> cron_activated = false;
		$this -> game = $game;
		$this -> accessallbots = $accessallbots;

		$this -> bot -> glob = array(); */

		$this -> aoc[0] = $this -> bot -> aoc;
		$slaven = 1;
		if(!empty($slaves))
		{
			foreach($slaves as $slave)
			{
				$slave[2] = ucfirst(strtolower($slave[2]));
				$this -> load_aochat($slaven);
				$this -> slavesl[$slaven] = $slave;
				$this -> slaves[$slaven] = $slave[2];
				$this -> slavesn[$slave[2]] = $slaven;
				$slaven++;
			}
		}
		//$this -> bot -> register_event("cron", "1sec", $this);
		$this -> bot -> register_event("connect", FALSE, $this);

		$this -> callbacks();
		$this -> update_user_table();
		$this -> update_buddy_cache();
	}

	function update_user_table()
	{
		$this -> bot -> db -> define_tablename("users_slaves_updates", TRUE);
		switch ($this -> bot -> db -> get_version("users_slaves_updates"))
		{
			case 1: 
				$this -> bot -> db -> update_table("users", "slave", "add",
				"ALTER TABLE #___users ADD slave INT default '0' AFTER notify");
			default:
		}
		$this -> bot -> db -> set_version("users_slaves_updates", 2);
	}

	function update_buddy_cache($reasign=FALSE)
	{
		$this -> buds = array();
		$notifylist = $this -> bot -> db -> select("SELECT nickname, slave FROM #___users WHERE notify = 1 ORDER BY nickname DESC");
		if (!empty($notifylist))
		{
			if($reasign)
			{
				$bots = count($this -> slaves) + 1;// should only do this is rosterupdate is going to run
				echo "count bots = $bots\n";
				$i=1;
				while($i < $bots)
				{
					$slvh[$i] = round((count($notifylist) / $bots) * $i);
					$i++;
				}
				$slvh[$i] = 9999999999;
				$i=0;
				foreach ($notifylist as $k => $user)
				{
					if($k > $slvh[$i+1])
						$i++;
					if($user[1] != $i)
					{
						$this -> bot -> db -> query("UPDATE #___users SET slave = $i WHERE nickname = '".$user[0]."'");
						$notifylist[$k][1] = $i;
					}
				}
			}
			foreach ($notifylist as $user)
			{
				$this -> buds[ucfirst(strtolower($user[0]))] = $user[1];
			}
		}
		//check buddies already loaded
		foreach($this -> aoc as $slave => $aoc)
		{
			$buds = $aoc -> buddies;
			if(!empty($buds))
			{
				foreach($buds as $id => $value)
				{
					$name = $aoc -> get_uname($id);
					if($this -> buds[ucfirst(strtolower($name))] != $slave)
					{
						$aoc -> buddy_remove($name);
						$this -> log($slave, "BUDDY", "LOG", $name . " Removed from Buddy List (wrong bot)");
					}
				}
			}
		}
	}

	function load_aochat($num)
	{
		$function = 'callback_'.$num;
		$this -> aoc[$num] = new AOChat($function, $this -> bot -> game);
	}

	function callbacks()
	{
		function callback_1($type, $args, $cbargs)
		{
			global $slaves;
			$slaves -> callback($type, $args, $cbargs, 1);
		}

		function callback_2($type, $args, $cbargs)
		{
			global $slaves;
			$slaves -> callback($type, $args, $cbargs, 2);
		}

		function callback_3($type, $args, $cbargs)
		{
			global $slaves;
			$slaves -> callback($type, $args, $cbargs, 3);
		}

		function callback_4($type, $args, $cbargs)
		{
			global $slaves;
			$slaves -> callback($type, $args, $cbargs, 4);
		}

		function callback_5($type, $args, $cbargs)
		{
			global $slaves;
			$slaves -> callback($type, $args, $cbargs, 5);
		}
	}

	function callback($type, $args, $cbargs, $slave)
	{
		$this -> bot -> cron();

		switch ($type)
		{
			case 5:
				$this -> log($slave, "LOGIN", "RESULT", "OK");
				break;
			case 6:
				$this -> log($slave, "LOGIN", "RESULT", "Error");
				break;
			case 20:
				// Silently ignore for now (AOCP_CLIENT_NAME)
				break;
			case AOCP_MSG_PRIVATE:
				// Event is a tell
				$this -> inc_tell($args, $slave);
				break;
			case AOCP_BUDDY_ADD:
				// Event is a buddy logging on/off
				$this -> inc_buddy($args, $slave);
				break;
			case AOCP_PRIVGRP_CLIJOIN:
				// Event is someone joining the privgroup
				//$slaves -> inc_pgjoin($args);
				break;
			case AOCP_PRIVGRP_CLIPART:
				// Event is someone leaveing the privgroup
				//$slaves -> inc_pgleave($args);
				break;
			case AOCP_PRIVGRP_MESSAGE:
				// Event is a privgroup message
				//$slaves -> inc_pgmsg($args);
				break;
			case AOCP_GROUP_MESSAGE:
				// Event is a group message (guildchat, towers etc)
				//$slaves -> inc_gmsg($args);
				break;
			case AOCP_PRIVGRP_INVITE:
				// Event is a privgroup invite
				//$slaves -> inc_pginvite($args);
				break;
			case AOCP_GROUP_ANNOUNCE:
				//$slaves -> inc_gannounce($args);
				break;
			default:
				//$this -> bot -> log ("MAIN", "TYPE", "Uhandeled packet type $type");
		}
	/*	$time_end = microtime_float();
$time = $time_end - $time_start;

echo "Did nothing in $time seconds\n"; */

	}

	/*
	Connects the bot to AO's chat server
	*/
	function connect()
	{
		$this -> bot -> crontimer["2sec"] = time() + 60;
		$this -> bot -> crontimer["1min"] = time() + 60;
		$this -> bot -> crontimer["1hour"] = time() + 60;
		$this -> bot -> crontimer["12hour"] = time() + 60;
		$this -> bot -> crontimer["24hour"] = time() + 60;

		if(!empty($this -> slavesl))
		{
			foreach($this -> slavesl as $key => $slave)
			{
				$this -> connect2($key);
			}
			//$this -> run_while();
		}
		sleep(2);
	}

	function run_while()
	{
		if (!empty($this -> bot -> commands["connect"]))
		{
			unset($this -> bot -> commands["connect"]["Slaves"]);
			$keys = array_keys($this -> bot -> commands["connect"]);
			foreach ($keys as $key)
			{
				if ($this -> bot -> commands["connect"][$key] != NULL)
				{
					$this -> bot -> commands["connect"][$key] -> connect();
				}
			}
		}
		while(true)
		{
			if ($this -> aoc[0] -> wait_for_packet() == "disconnected")
				$bot -> reconnect();
			foreach($this -> aoc as $aoc)
				$aoc -> wait_for_packet();

			$this -> bot -> cron();
		}
		return;
		$time = 1;
		if(!empty($this -> slaves))
		{
			foreach($this -> slaves as $key => $slave)
			{
				$packet = "not null";
				while($packet != NULL)
					$packet = $this -> aoc[$key] -> wait_for_packet($time);
			}
		}
	}

	function connect2($num)
	{

		// Get dimension server
		switch($this -> bot -> dimension)
		{
			case "0":
				$dimension = "Testlive";
				break;
			case "1";
				$dimension = "Atlantean";
				break;
			case "2":
				$dimension = "Rimor";
				break;
			case "3":
				$dimension = "Die neue welt";
				break;
            case "5":
                $dimension = "Rubi-Ka";
                break;				
			Default:
				$dimension = ucfirst(strtolower($this -> bot -> dimension));
		}

		Require("conf/ServerList.php");

		if(isset($server_list['ao'][$dimension]))
		{
			$server = $server_list['ao'][$dimension]['server'];
			$port = $server_list['ao'][$dimension]['port'];
		}
		elseif(isset($server_list['aoc'][$dimension]))
		{
			$server = $server_list['aoc'][$dimension]['server'];
			$port = $server_list['aoc'][$dimension]['port'];
		}
		else
			echo "Unknown dimension ".$this -> dimension." Connect Canceled for Slave ".$num;

		// Open connection
		$this -> log($num, "LOGIN", "STATUS", "Connecting to $this->game server $server:$port");
		if (!$this -> aoc[$num] -> connect($server, $port, $this -> bot -> sixtyfourbit)) // Heffalomp fix :-)
		{
			$this -> cron_activated = false;
		//	$this -> disconnect();
			$this -> log($num, "CONN", "ERROR", "Can't connect to server. Retrying in " . $this -> reconnecttime . " seconds.");
			//sleep($this -> reconnecttime);
			//die("The bot is restarting.\n");
		}

		// Authenticate
		$this -> log($num, "LOGIN", "STATUS", "Authenticating ".$this -> slavesl[$num][0], $num);
		$this -> aoc[$num] -> authenticate($this -> slavesl[$num][0], $this -> slavesl[$num][1]);

		// Login the bot character
		$this -> log($num, "LOGIN", "STATUS", "Logging in ".$this -> slavesl[$num][2], $num);
		$this -> aoc[$num] -> login(ucfirst(strtolower($this -> slavesl[$num][2])));

		/*
		We're logged in. Make sure we no longer keep username and password in memory.
		*/
	//	$this -> slavesl[$num][0] = NULL;
	//	$this -> slavesl[$num][1] = NULL;

		// Tell modules that the bot is connected
		/*if (!empty($this -> bot -> commands["connect"]))
		{
			$keys = array_keys($this -> bot -> commands["connect"]);
			foreach ($keys as $key)
			{
				if ($this -> bot -> commands["connect"][$key] != NULL)
				{
					$this -> bot -> commands["connect"][$key] -> connect();
				}
			}
		}

		$this -> startup_time = time() + $this -> crondelay;
		// Set the time of the first cronjobs
		foreach ($this -> cron_times AS $timestr => $value)
		{
			$this -> cron_job_timer[$timestr] = $this -> startup_time;
		}

		// and unlock all cronjobs again:
		$this -> cron_activated = true; */

		//Store time of connection
		$this -> connected_time = time();
	}



	/*
	Reconnect the bot.
	*/
	function reconnect($slave)
	{
	//	$this -> cron_activated = false;
	//	$this -> disconnect();
		$this -> log($slave, "CONN", "ERROR", "Slave ".$this -> slavesn[$slave[2]]." has disconnected. Reconnecting...");
//		in " . $this -> bot -> reconnecttime . " seconds.");
	//	foreach($this -> buds as $name => $s)
	//	{
	//		if($s == $slave)
		//	{
		//		unset($this -> bot -> glob["online"][$user]);
		//	}
		//}
		$this -> aoc[$slave] -> disconnect();
		$this -> connect2($slave);
	//	sleep($this -> bot -> reconnecttime);
	//	die("The bot is restarting.\n");
	}



	/*
	Dissconnect the bot
	*/
	function disconnect()
	{
		$this -> aoc -> disconnect();

		if (!empty($this -> bot -> commands["disconnect"]))
		{
			$keys = array_keys($this -> bot -> commands["disconnect"]);
			foreach ($keys as $key)
			{
				if ($this -> bot -> commands["disconnect"][$key] != NULL)
				{
					$this -> bot -> commands["disconnect"][$key] -> disconnect();
				}
			}
		}
	}



	function replace_string_tags($msg)
	{
		$msg = str_replace("<botname>", $this -> botname, $msg);
		$msg = str_replace("<guildname>", $this -> guildname, $msg);
		$msg = str_replace("<pre>", str_replace("\\", "", $this -> commpre), $msg);

		return $msg;
	}
	/*
	sends a tell asking user to use "help"
	*/
	function send_help($to, $command=FALSE)
	{
		if ($command == FALSE)
		{
			$this -> send_tell($to, "/tell <botname> <pre>help");
		}
		else
		{
			$this -> send_tell($to, $this -> bot -> core("help") -> show_help($to, $command));
		}
	}


	/*
	sends a message over IRC if it's enabled and connected
	*/
	function send_irc($prefix, $name, $msg)
	{
		if (isset($this -> irc) && $this -> bot -> core("settings") -> exists("irc", "connected"))
		{
			if ($this -> bot -> core("settings") -> get("Irc", "Connected"))
			{
				$this -> bot -> core("irc") -> send_irc($prefix, $name, $msg);
			}
		}
	}

	/*
	Notifies someone that they are banned, but only once.
	*/
	function send_ban($to, $msg=FALSE)
	{
		if (!isset($this -> banmsgout[$to]))
		{
			$this -> banmsgout[$to] = time();
			if ($msg === FALSE)
			{
				$this -> send_tell($to, "You are banned from <botname>.");
			}
			else
			{
				$this -> send_tell($to, $msg);
			}
		}
		else
		{
			return FALSE;
		}
	}

	/*
	Sends a permission denied error to user for the given command.
	*/
	function send_permission_denied($to, $command, $type=0)
	{
		$string = "You do not have permission to access $command";
		if ($type = 0)
		{
			return $string;
		}
		else
		{
			$this -> send_output($to, $string, $type);
		}
	}



	/*
	send a tell. Set $low to 1 on tells that are likely to cause spam.
	*/
	function send_tell($to, $msg, $low=0, $color=true, $sizecheck=TRUE, $parsecolors=TRUE, $slave=FALSE)
	{
		// parse all color tags:
		if($parsecolors)
		$msg = $this -> bot -> core("colors") -> parse($msg);

		$send = true;
		if($sizecheck)
		{
			if(strlen($msg) < 100000)
			{
				if (preg_match("/<a href=\"(.+)\">/isU", $msg, $info))
				{
					if (strlen($info[1]) > $this -> bot -> maxsize)
					{
						$this -> cut_size($slave, $msg, "tell", $to, $low);
						$send = false;
					}
				}
			}
			else
			{
				$info = explode('<a href="', $msg, 2);
				if(count($info) > 1)
				{
					if (strlen($msg) > $this -> bot -> maxsize)
					{
						$this -> cut_size($slave, $msg, "tell", $to, $low);
						$send = false;
					}
				}
			}
		}

		if ($send)
		{
			$msg = $this -> bot -> replace_string_tags($msg);

			if ($color && $this -> bot -> core("settings") -> get("Core", "ColorizeTells"))
			{
				$msg = $this -> bot -> core("colors") -> colorize("normal", $msg);
			}

			$q = $this -> chatq -> check_queue($slave);
			if ($q)
			{
				$this -> log($q[1], "TELL", "OUT", "-> " . $this -> bot -> core("chat") -> get_uname($to) . ": " . $msg, $slave);
				$msg = utf8_encode($msg);
				$this -> aoc[$q[1]] -> send_tell($to, $msg);
			}
			else
				$this -> chatq -> into_queue($to, $msg, "tell", $low, $slave);
		}
	}



	/*
	send a message to privategroup
	*/
	function send_pgroup($msg, $group = NULL, $checksize = TRUE, $parsecolors=TRUE)
	{
		if ($group == NULL)
			$group = $this -> botname;

		if ($group == $this -> botname && $this -> bot -> core("settings") -> get("Core", "DisablePGMSG"))
		{
			return FALSE;
		}

		// parse all color tags:
		if($parsecolors)
			$msg = $this -> bot -> core("colors") -> parse($msg);

		$gid = $this -> bot -> core("chat") -> get_uid($group);

		$send = true;
		if($checksize)
		{
			if(strlen($msg) < 100000)
			{
				if (preg_match("/<a href=\"(.+)\">/isU", $msg, $info))
				{
					if (strlen($info[1]) > $this -> bot -> maxsize)
					{
						$this -> cut_size(FALSE, $msg, "pgroup", $group);
						$send = false;
					}
				}
			}
			else
			{
				$info = explode('<a href="', $msg, 2);
				if(count($info) > 1)
				{
					if (strlen($msg) > $this -> bot -> maxsize)
					{
						$this -> cut_size(FALSE, $msg, "pgroup", $group);
						$send = false;
					}
				}
			}
		}

		if ($send)
		{
			$msg = $this -> replace_string_tags($msg);

			$msg = utf8_encode($msg);

			if (strtolower($group) == strtolower($this -> botname))
			{
				if ($this -> bot -> core("settings") -> get("Core", "ColorizePGMSG"))
				{
					$msg = $this -> bot -> core("colors") -> colorize("normal", $msg);
				}
				$this -> aoc -> send_privgroup($gid,$msg);
			}
			else
				$this -> aoc -> send_privgroup($gid,$msg);
		}
	}


	/*
	* Send a message to guild channel
	*/

	function send_gc($slave, $msg, $low=0, $checksize = TRUE)
	{
		if($this -> bot -> core("settings") -> get("Core", "DisableGC"))
		{
			Return FALSE;
		}

		// parse all color tags:
		$msg = $this -> bot -> core("colors") -> parse($msg);

		$send = true;
		if($checksize)
		{
			if(strlen($msg) < 100000)
			{
				if (preg_match("/<a href=\"(.+)\">/isU", $msg, $info))
				{
					if (strlen($info[1]) > $this -> bot -> maxsize)
					{
						$this -> cut_size(FALSE, $msg, "gc", "", $low);
						$send = false;
					}
				}
			}
			else
			{
				$info = explode('<a href="', $msg, 2);
				if(count($info) > 1)
				{
					if (strlen($msg) > $this -> bot -> maxsize)
					{
						$this -> cut_size(FALSE, $msg, "gc", "", $low);
						$send = false;
					}
				}
			}
		}

		if ($send)
		{
			$msg = $this -> replace_string_tags($msg);

			if ($this -> bot -> core("settings") -> get("Core", "ColorizeGC"))
			{
				$msg = $this -> bot -> core("colors") -> colorize("normal", $msg);
			}
			
			if($this -> game == "ao")
				$guild = $this -> bot -> guildname;
			else
				$guild = "~Guild";

			$q = $this -> chatq -> check_queue($slave);
			if ($q)
			{
				$msg = utf8_encode($msg);
				$this -> aoc[$q[1]] -> send_group($guild, $msg);
			}
			else
				$this -> chatq -> into_queue($guild, $msg, "gc", $low, $slave);
		}
	}

	function send_output($source, $msg, $type)
	{
		// Parse color tags now to be sure they don't get changed by output filters
		$msg = $this -> bot -> core("colors") -> parse($msg);

		// Output filter
		if ($this -> bot -> core("settings") -> exists('Filter', 'Enabled'))
		{
			if ($this -> bot -> core("settings") -> get('Filter', 'Enabled'))
			{
				$msg = $this -> bot -> core("stringfilter") -> output_filter($msg);
			}
		}

		if (!is_numeric($type))
		{
			$type = strtolower($type);
		}
		switch($type)
		{
			case '0':
			case '1':
			case 'tell':
				$this -> send_tell($source, $msg);
				break;
			case '2':
			case 'pgroup':
			case 'pgmsg':
				$this -> send_pgroup($msg);
				break;
			case '3':
			case 'gc':
				$this -> send_gc($msg);
				break;
			case '4':
			case 'both':
				$this -> send_gc($msg);
				$this -> send_pgroup($msg);
				break;
			default:
				$this -> log($slave, "OUTPUT", "ERROR", "Broken plugin, type: $type is unknown to me; source: $source, message: $msg");
		}
	}



	/*
	 * This function tries to find a similar written command based compared to $cmd, based on
	 * all available commands in $channel. The percentage of match and the closest matching command
	 * are returned in an array.
	 */
	function find_similar_command($channel, $cmd)
	{
		$use = array(0);
		$percentage = 0;

		if(isset($this -> bot -> commands["tell"][$cmd]) ||
			isset($this -> bot -> commands["gc"][$cmd]) ||
			isset($this -> bot -> commands["pgmsg"][$cmd]) ||
			isset($this -> bot -> commands["extpgmsg"][$cmd]))
		{
			return $use;
		}

		$perc = $this -> bot -> core("settings") -> get("Core", "SimilarMinimum");
		foreach($this -> bot -> commands[$channel] as $compare_cmd => $value)
		{
			similar_text($cmd, $compare_cmd, $percentage);
			if ($percentage >= $perc
			&& $percentage > $use[0])
			{
				$use = array($percentage, $compare_cmd);
			}
		}
		return $use;
	}



	/*
	 * This function checks if $user got access to $command (with possible subcommands based on $msg)
	 * in $channel. If the check is positive the command is executed and TRUE returned, otherwise FALSE.
	 * $pgname is used to identify which external private group issued the command if $channel = extpgmsg.
	 */
	function check_access_and_execute($user, $command, $msg, $channel, $pgname)
	{
		if ($this -> bot -> commands[$channel][$command] != NULL)
		{
			if ($this -> bot -> core("access_control") -> check_rights($user, $command, $msg, $channel))
			{
				if ($channel == "extpgmsg")
				{
					$this -> bot -> commands[$channel][$command] -> $channel($pgname, $user, $msg);
				}
				else
				{
					$this -> bot -> commands[$channel][$command] -> $channel($user, $msg);
				}
				return true;
			}
		}
		return false;
	}



	/*
	 * This function check if $msg contains a command in the channel.
	 * If $msg contains a command it checks for access rights based on the $user, command and $channel.
	 * If $user may access the command $msg is handed over to the parser of the responsible module.
	 * This function returns true if the $msg has been handled, and false otherwise.
	 * $pgname is used to identify external private groups.
	 */
	function handle_command_input($user, $msg, $channel, $pgname = NULL)
	{
		$match = false;
		$this -> command_error_text = false;

		if (!empty($this -> bot -> commands[$channel]))
		{
			if ($this -> bot -> core("security") -> is_banned($user))
			{
				$this -> send_ban($user);
				return true;
			}

			$stripped_prefix = str_replace("\\", "", $this -> commpre);

			// Add missing command prefix in tells if the settings allow for it:
			if ($channel == "tell" && !$this -> bot -> core("settings") -> get("Core", "RequireCommandPrefixInTells") && $this -> commpre != ""
			&& $msg[0] != $stripped_prefix)
			{
				$msg = $stripped_prefix . $msg;
			}

			// Only if first character is the command prefix is any check for a command needed,
			// or if no command prefix is used at all:
			if ($this -> commpre == "" || $msg[0] == $stripped_prefix)
			{
				// Strip command prefix if it is set - we already checked that the input started with it:
				if ($this -> commpre != "")
				{
					$msg = substr($msg, 1);
				}

				// Check if Command is an Alias of another Command
				$msg = $this -> bot -> core("command_alias") -> replace($msg);

				$cmd = explode(" ", $msg, 3);
				$cmd[0] = strtolower($cmd[0]);

				$msg = implode(" ", $cmd);

				if (isset($this -> bot -> commands[$channel][$cmd[0]]))
				{
					$match = TRUE;

					if ($this -> check_access_and_execute($user, $cmd[0], $msg, $channel, $pgname))
					{
						return true;
					}
				}
				elseif($this -> bot -> core("settings") -> get("Core", "SimilarCheck"))
				{
					$use = $this -> find_similar_command($channel, $cmd[0]);
					if($use[0] > 0)
					{
						$cmd[0] = $use[1];
						$msg = explode(" ", $msg, 2);
						$msg[0] = $use[1];
						$msg = implode(" ", $msg);
						if(isset($this -> bot -> commands[$channel][$use[1]]))
						{
							$match = TRUE;

							if ($this -> check_access_and_execute($user, $use[1], $msg, $channel, $pgname))
							{
								return true;
							}
						}
					}
				}
				if ($this -> bot -> core("settings") -> get("Core", "CommandError" . $channel) && $match)
				{
					$minlevel = $this -> bot -> core("access_control") -> get_min_rights($cmd[0], $msg, $channel);
					if ($minlevel == OWNER + 1)
					{
						$minstr = "DISABLED";
					}
					else
					{
						$minstr = $this -> bot -> core("security") -> get_access_name($minlevel);
					}
					$req = array("Command", $msg, $minstr);
					if ($req[2] == "DISABLED")
					{
						if($this -> bot -> core("settings") -> get("Core", "CommandDisabledError"))
						{
							$this -> command_error_text = "You're not authorized to use this ".$req[0].": ##highlight##".$req[1]."##end##, it is Currently ##highlight##DISABLED##end##";
						}
					}
					else
					{
						$this -> command_error_text = "You're not authorized to use this ".$req[0].": ##highlight##".$req[1]."##end##, Your Access Level is required to be at least ##highlight##".$req[2]."##end##";
					}
				}
			}

			return false;
		}
	}

	/*
	 * This function handles input after a successless try to find a command in it.
	 * If some modules has registered a chat handover for $channel it will hand it over here.
	 * It checks $found first, if $found = true it doesn't do anything.
	 * $group is used by external private groups and to listen to specific chat channels outside the bot.
	 * Returns true if some module accessing this chat returns true, false otherwise.
	 */
	function hand_to_chat($found, $user, $msg, $channel, $group = NULL)
	{
		if ($found)
		{
			return true;
		}
		if ($channel == "gmsg")
		{
			if ($group == $this -> guildname || ($this -> game == "aoc" && $group == "~Guild"))
			{
				$group = "org";
			}
			$registered = $this -> bot -> commands[$channel][$group];
		}
		else
		{
			$registered = $this -> bot -> commands[$channel];
		}
		if (!empty($registered))
		{
			$keys = array_keys($registered);
			foreach ($keys as $key)
			{
				if ($channel == "extprivgroup")
				{
					if ($this -> bot -> commands[$channel][$key] != NULL)
					{
						$found = $found | $this -> bot -> commands[$channel][$key] -> $channel($group, $user, $msg);
					}
				}
				else if ($channel == "gmsg")
				{
					if ($this -> bot -> commands[$channel][$group][$key] != NULL)
					{
						$found = $found | $this -> bot -> commands[$channel][$group][$key] -> $channel($user, $group, $msg);
					}
				}
				else
				{
					if ($this -> bot -> commands[$channel][$key] != NULL)
					{
						$found = $found | $this -> bot -> commands[$channel][$key] -> $channel($user, $msg);
					}
				}
			}
		}
		return $found;
	}

	/*
	Incoming Tell
	*/
	function inc_tell($args, $slave)
	{
		if (!preg_match("/is AFK .Away from keyboard./i", $args[1]) && !preg_match("/.tell (.+)help/i",$args[1]) && !preg_match("/I only listen to members of this bot/i",$args[1] ) && !preg_match("/I am away from my keyboard right now,(.+)your message has been logged./i",$args[1]) && !preg_match("/Away From Keyboard/i", $args[1]))
		{
			$user = $this -> bot -> core("chat") -> get_uname($args[0]);
			$found = false;

			$args[1] = utf8_decode($args[1]);

			// Ignore bot chat, no need to handle it's own output as input again
			if (strtolower($this -> slaves[$slave]) == strtolower($user))
			{
				// Danger will robinson. We just sent a tell to ourselves!!!!!!!!!
				$this -> log($slave, "CORE", "INC_TELL", "Danger will robinson. Received tell from myself: $args[1]", $slave);
				return;
			}
			if (strtolower($this -> bot -> botname) == strtolower($user))
			{
				return;
			}
			foreach($this -> slaves as $slv)
			{
				if(strtolower($slv) == strtolower($user))
					Return;
			}

			$this -> log($slave, "TELL", "INC", $user . ": " . $args[1], $slave);

			//$this -> send_tell($args[0], "I am a Slave bot of " . $this -> bot -> botname . ".", $slave);
			//$this -> bot -> inc_tell($args, $slave
			//Return;
			if (!isset($this -> bot -> other_bots[$user]))
			{
				$found = $this -> bot -> handle_command_input($user, $args[1], "tell");

				$found = $this -> bot -> hand_to_chat($found, $user, $args[1], "tells");

				if ($this -> bot -> command_error_text)
				{
					$this -> bot -> send_tell($args[0], $this -> bot -> command_error_text);
				}
				elseif (!$found && $this -> bot -> core("security") -> check_access($user, "GUEST"))
				{
					$this -> bot -> send_help($args[0]);
				}
				else if (!$found)
				{
					if ($this -> bot -> guild_bot)
					{
						$this -> bot -> send_tell($args[0], "I only listen to members of " . $this -> bot -> guildname . ".");
					}
					else
					{
						$this -> bot -> send_tell($args[0], "I only listen to members of this bot.");
					}
				}
				unset($this -> bot -> command_error_text);
			}
		}
	}



	/*
	Buddy logging on/off
	*/
	function inc_buddy($args, $slave)
	{
		$user = $this -> get_uname($args[0], $slave);
		$mem = $this -> bot -> core("notify") -> check($user);
		//echo "id=".$args[0]." user=$user mem=$mem\n";
		//var_dump($args);

		if(isset($this -> buds[ucfirst(strtolower($user))]))
		{
			if($this -> buds[ucfirst(strtolower($user))] != $slave)
			{
				$this -> aoc[$slave] -> buddy_remove($user);
				$this -> log($slave, "BUDDY", "LOG", $user . " logged [" . (($args[1] == 1) ? "on" : "off") . "] (wrong bot)");
				Return;
			}
		}
		$this -> bot -> aoc -> buds[$user] = $slave;
		if($this -> bot -> game == "ao")
		{

			// Make sure we only cache members and guests to prevent any issues with !is and anything else that might do buddy actions on non members.
			if ($mem)
			{
				// Buddy logging on
				if ($args[1] == 1)
				{
					// Do we have a logon for a user already logged on?
					if (isset($this -> bot -> glob["online"][$user]))
					{
						 $this -> log($slave, "BUDDY", "ERROR", $user . " logged on despite of already being marked as logged on!!");
						return;
					}
					else
					{
						// Enter the user into the online buddy list
						$this -> bot -> glob["online"][$user] = $user;
					}
				}
				else
				{
					// Do we have a logoff without a prior login?
					if (!isset($this -> bot -> glob["online"][$user]))
					{
						// $this -> log($slave, "BUDDY", "ERROR", $user . " logged off with no prior logon!!");
						return;
					}
					else
					{
						unset($this -> bot -> glob["online"][$user]);
					}
				}
			}

			$end = "";
			if (!$mem)
			{
				$end = " (not on notify)";
				// Using aoc -> buddy_remove() here is an exception, all the checks in chat -> buddy_remove() aren't needed!
				$this -> aoc[$slave] -> buddy_remove($user);
			}
			else
			{
				$end = " (" . $this -> bot -> core("security") -> get_access_name($this -> bot -> core("security") -> get_access_level($user)) . ")";
			}

			$this -> log($slave, "BUDDY", "LOG", $user . " logged [" . (($args[1] == 1) ? "on" : "off") . "]" . $end);

			if (!empty($this -> bot -> commands["buddy"]))
			{
				$keys = array_keys($this -> bot -> commands["buddy"]);
				foreach ($keys as $key)
				{
					if ($this -> bot -> commands["buddy"][$key] != NULL)
					{
						$this -> bot -> commands["buddy"][$key] -> buddy($user, $args[1]);
					}
				}
			}
		}
		else
		{
			// Get the users current state
			$old_who = $this -> bot -> core("Whois") -> lookup($user);

			if(array_key_exists($user, $this -> bot -> buddy_status))
				$old_buddy_status = $this -> bot -> buddy_status[$user];
			else
				$old_buddy_status = 0;

			$who = array();
			$who["id"] = $args[0];
			$who["nickname"] = $user;
			$who["online"] = $args[1];
			$who["level"] = $args[2];
			$who["location"] = $args[3];
			$class_name = $this -> bot -> core("Whois") -> class_name[$args[4]];
			$who["class"] = $class_name;
			$lookup = $this -> bot -> db -> select("SELECT * FROM #___craftingclass WHERE name = '" . $user . "'", MYSQL_ASSOC);
			if (!empty($lookup))
			{
				$who["craft1"] = $lookup[0]['class1'];
				$who["craft2"] = $lookup[0]['class2'];
			}
			$this -> bot -> core("Whois") -> update($who);

			if($old_who["error"])
			{
				$old_who["level"] = 0;
				$old_who["location"] = 0;
			}

			// status change flags:
			// 1 = online
			// 2 = LFG
			// 4 = AFK
			if(0 == $who["online"])
				$buddy_status = 0;
			else if(1 == $who["online"])
				$buddy_status = 1;
			else if(2 == $who["online"])
				$buddy_status = $old_buddy_status | 2;
			else if(3 == $who["online"])
				$buddy_status = $old_buddy_status | 4;

			$this -> bot -> buddy_status[$user] = $buddy_status;

			$changed = $buddy_status ^ $old_buddy_status;

			$current_statuses = array();

			/* Player Statuses
			0 = logged off
			1 = logged on
			2 = went LFG
			3 = went AFK
			4 = stopped LFG
			5 = no longer AFK
			6 = changed location
			7 = changed level
			*/

			// Deal with overriding status changes
			if(1 == ($changed & 1))
			{
				if(1 == ($old_buddy_status & 1))
				{
					// User just went offline
					$current_statuses[] = 0;
				}
				else
				{
					// User just came online
					$current_statuses[] = 1;
				}
			}
			if(2 == ($changed & 2))
			{
				if(2 == ($old_buddy_status & 2))
				{
					// User just returned from LFG
					$current_statuses[] = 4;
				}
				else
				{
					// User just went LFG
					$current_statuses[] = 2;
				}
			}

			if(4 == ($changed & 4))
			{
				if(4 == ($old_buddy_status & 4))
				{
					// User just returned from AFK
					$current_statuses[] = 5;
				}
				else
				{
					// User just went AFK
					$current_statuses[] = 3;
				}
			}

			// Deal with events we don't have to remember
			if($old_who["level"] != $who["level"] && $old_who["level"] != 0)
			{
				// User has changed level
				$current_statuses[] = 7;
			}
			if($old_who["location"] != $who["location"] && $old_who["location"] != 0 && $who["online"] != 0 && !in_array(0, $current_statuses))
			{
				// User has changed location
				$current_statuses[] = 6;
			}

			// Make sure we only cache members and guests to prevent any issues with !is and anything else that might do buddy actions on non members.
			if ($mem)
			{
				if(in_array(1, $current_statuses))
				{
					// User just came online
					// Enter the user into the online buddy list
					$this -> bot -> glob["online"][$user] = $user;
				}
				else if(in_array(0, $current_statuses))
				{
					// User just went offline
					unset($this -> bot -> glob["online"][$user]);
				}
				$end = " (" . $this -> bot -> core("security") -> get_access_name($this -> bot -> core("security") -> get_access_level($user)) . ")";
			}
			else
			{
				$end = " (not on notify)";
				// Using aoc -> buddy_remove() here is an exception, all the checks in chat -> buddy_remove() aren't needed!
				$this -> aoc[$slave] -> buddy_remove($user);
			}


			foreach($current_statuses as $status)
			{
				$this -> log($slave, "BUDDY", "LOG", $user . " changed status [" . $status . "]" . $end);

				if (!empty($this -> bot -> commands["buddy"]))
				{
					$keys = array_keys($this -> bot -> commands["buddy"]);
					foreach ($keys as $key)
					{
						if ($this -> bot -> commands["buddy"][$key] != NULL)
						{
							$this -> bot -> commands["buddy"][$key] -> buddy($user, $status, $args[2], $args[3], $args[4]);
						}
					}
				}
			}
		}
	}


	function get_uname($user, $slave)
	{
		if ($user === false || $user === 0 || $user === -1)
		{
			return false;
		}

		$name = $this -> aoc[$slave] -> get_uname($user);

		if($name === false || $name === 0 || $name === -1 || $name == "-1")
		{
			$db_name = $this -> bot -> db -> select("SELECT nickname FROM #___whois WHERE ID = '" . $user . "' OR nickname = '" . $user . "'");

			if (!empty($db_name))
			{
				$name = $db_name[0][0];
			}
			else
			{
				$name = false;
				$this -> bot -> log('GETUNAME', 'FAILED', "I was unable to get the user name belonging to: $user");
			}
		}
		return $name;
	}


	/*
	Someone joined privategroup
	*/
	function inc_pgjoin($args)
	{
		$pgname = $this -> bot -> core("chat") -> get_uname($args[0]);

		if (empty($pgname) || $pgname == "")
		$pgname = $this -> botname;

		$user = $this -> bot -> core("chat") -> get_uname($args[1]);

		if (strtolower($pgname) == strtolower($this -> botname))
		{
			$this -> log($slave, "PGRP", "JOIN", $user . " joined privategroup.");
			if (!empty($this -> bot -> commands["pgjoin"]))
			{
				$keys = array_keys($this -> bot -> commands["pgjoin"]);
				foreach ($keys as $key)
				{
					if ($this -> bot -> commands["pgjoin"][$key] != NULL)
					{
						$this -> bot -> commands["pgjoin"][$key] -> pgjoin($user);
					}
				}
			}
		}
		else
		{
			$this -> log($slave, "PGRP", "JOIN", $user . " joined the exterior privategroup of " . $pgname . ".");
			if (!empty($this -> bot -> commands["extpgjoin"]))
			{
				$keys = array_keys($this -> bot -> commands["extpgjoin"]);
				foreach ($keys as $key)
				{
					if ($this -> bot -> commands["extpgjoin"][$key] != NULL)
					{
						$this -> bot -> commands["extpgjoin"][$key] -> extpgjoin($pgname, $user);
					}
				}
			}
		}
	}



	/*
	Someone left privategroup
	*/
	function inc_pgleave($args)
	{
		$pgname = $this -> bot -> core("chat") -> get_uname($args[0]);

		if (empty($pgname) || $pgname == "")
		$pgname = $this -> botname;

		$user = $this -> bot -> core("chat") -> get_uname($args[1]);

		if (strtolower($pgname) == strtolower($this -> botname))
		{
			$this -> log($slave, "PGRP", "LEAVE", $user . " left privategroup.");
			if (!empty($this -> bot -> commands["pgleave"]))
			{
				$keys = array_keys($this -> bot -> commands["pgleave"]);
				foreach ($keys as $key)
				{
					if ($this -> bot -> commands["pgleave"][$key] != NULL)
					{
						$this -> bot -> commands["pgleave"][$key] -> pgleave($user);
					}
				}
			}
		}
		else
		{
			$this -> log($slave, "PGRP", "LEAVE", $user . " left the exterior privategroup " . $pgname . ".");
			if (!empty($this -> bot -> commands["extpgleave"]))
			{
				$keys = array_keys($this -> bot -> commands["extpgleave"]);
				foreach ($keys as $key)
				{
					if ($this -> bot -> commands["extpgleave"][$key] != NULL)
					{
						$this -> bot -> commands["extpgleave"][$key] -> extpgleave($pgname, $user);
					}
				}
			}
		}
	}



	/*
	Message in privategroup
	*/
	function inc_pgmsg($args)
	{
		$pgname = $this -> bot -> core("chat") -> get_uname($args[0]);
		$user = $this -> bot -> core("chat") -> get_uname($args[1]);
		$found = false;

		if (empty($pgname) || $pgname == "")
		$pgname = $this -> botname;

		if ($pgname == $this -> botname && $this -> bot -> core("settings") -> get("Core", "DisablePGMSG"))
		{
			return FALSE;
		}

		$args[2] = utf8_decode($args[2]);

		// Ignore bot chat, no need to handle it's own output as input again
		if (strtolower($this -> botname) == strtolower($user))
		{
			if ($this -> bot -> core("settings") -> get("Core", "LogPGOutput"))
			{
				$this -> log($slave, "PGRP", "MSG", "[" . $this -> bot -> core("chat") -> get_uname($args[0]) . "] " .
				$user . ": " . $args[2]);
			}
			return;
		}
		else
		{
			$this -> log($slave, "PGRP", "MSG", "[" . $this -> bot -> core("chat") -> get_uname($args[0]) . "] " .
			$user . ": " . $args[2]);
		}

		if (!isset($this -> other_bots[$user]))
		{
			if (strtolower($pgname) == strtolower($this -> botname))
			{
				$found = $this -> handle_command_input($user, $args[2], "pgmsg");
				$found = $this -> hand_to_chat($found, $user, $args[2], "privgroup");
			}
			else
			{
				$found = $this -> handle_command_input($user, $args[2], "extpgmsg", $pgname);
				$found = $this -> hand_to_chat($found, $user, $args[2], "extprivgroup", $pgname);
			}
			if($this -> command_error_text)
			{
				$this -> send_pgroup($this -> command_error_text, $pgname);
			}
			unset($this -> command_error_text);
		}
	}

	/*
	Incoming group announce
	*/
	function inc_gannounce($args)
	{
		if ($args[2] == 32772 && $this -> game == "ao")
		{
			$this -> guildname = $args[1];
			$this -> log($slave, "CORE", "INC_GANNOUNCE", "Detected org name as: $args[1]");
		}
	}

	/*
	* Incoming private group invite
	*/
	function inc_pginvite($args)
	{
		$group = $this -> bot -> core("chat") -> get_uname($args[0]);

		if (!empty($this -> bot -> commands["pginvite"]))
		{
			$keys = array_keys($this -> bot -> commands["pginvite"]);
			foreach ($keys as $key)
			{
				if ($this -> bot -> commands["pginvite"][$key] != NULL)
				{
					$this -> bot -> commands["pginvite"][$key] -> pginvite($group);
				}
			}
		}
	}


	/*
	* Incoming group message (Guildchat, towers etc)
	*/
	function inc_gmsg($args)
	{
		$found = false;

		$group = $this -> bot -> core("chat") -> lookup_group($args[0]);

		if (!$group)
		{
			$group = $this -> bot -> core("chat") -> get_gname($args[0]);
		}

		$args[2] = utf8_decode($args[2]);

		if (isset($this -> bot -> commands["gmsg"][$group]) || $group == $this -> guildname || ($this -> game == "aoc" && $group == "~Guild"))
		{
			if($this -> game == "aoc" && $group == "~Guild")
				$msg = "[" . $this -> guildname . "] ";
			else
				$msg = "[" . $group . "] ";
			if ($args[1] != 0)
			{
				$msg .= $this -> bot -> core("chat") -> get_uname($args[1]) . ": ";
			}
			$msg .= $args[2];
		}
		else
		{
			// If we dont have a hook active for the group, and its not guildchat... BAIL now before wasting cycles
			return FALSE;
		}

		if (($group == $this -> guildname || ($this -> game == "aoc" && $group == "~Guild")) && $this -> bot -> core("settings") -> get("Core", "DisableGC"))
		{
			Return FALSE;
		}

		if ($args[1] == 0)
		{
			$user = "0";
		}
		else
		{
			$user = $this -> bot -> core("chat") -> get_uname($args[1]);
		}
		// Ignore bot chat, no need to handle it's own output as input again
		if (strtolower($this -> botname) == strtolower($user))
		{
			if ($this -> bot -> core("settings") -> get("Core", "LogGCOutput"))
			{
				$this -> log($slave, "GROUP", "MSG", $msg);
			}
			return;
		}
		else
		{
			$this -> log($slave, "GROUP", "MSG", $msg);
		}

		if (!isset($this -> other_bots[$user]))
		{
			if ($group == $this -> guildname || ($this -> game == "aoc" && $group == "~Guild"))
			{
				$found = $this -> handle_command_input($user, $args[2], "gc");

				if($this -> command_error_text)
				{
					$this -> send_gc($this -> command_error_text);
				}
				unset($this -> command_error_text);
			}

			$found = $this -> hand_to_chat($found, $user, $args[2], "gmsg", $group);
		}
	}

	function log($slave, $first, $second, $msg, $write_to_db = false)
	{
		//Remove font tags
		$msg = preg_replace("/<font(.+)>/U", "", $msg);
		$msg = preg_replace("/<\/font>/U", "", $msg);
		//Remove color tags
		$msg = preg_replace("/##end##/U", "]", $msg);
		$msg = preg_replace("/##(.+)##/U", "[", $msg);
		//Change links to the text [link]...[/link]
		$msg = preg_replace("/<a href=\"(.+)\">/sU", "[link]", $msg);
		$msg = preg_replace("/<\/a>/U", "[/link]", $msg);

		if ($this -> bot -> log_timestamp == 'date')
			$timestamp = "[" . gmdate("Y-m-d") . "]\t";
		elseif ($this -> bot -> log_timestamp == 'time')
			$timestamp = "[" . gmdate("H:i:s") . "]\t";
		elseif ($this -> bot -> log_timestamp == 'none')
			$timestamp = "";
		else
			$timestamp = "[" . gmdate("Y-m-d H:i:s") . "]\t";


		$line = $timestamp . "[" . $first . "]\t[" . $second . "]\t" . $msg . "\n";
		if($slave == 0)
			$bot = $this -> bot -> botname;
		else
		{
			$bot = $this -> slaves[$slave];
			$b1 = strlen($this -> bot -> botname);
			$b2 = strlen($this -> slaves[$slave]);
			$dif = $b1 - $b2;
			if($dif > 0)
				$bot .= str_repeat(" ", $dif);
		}
		echo $bot . " " . $line;


		// We have a possible security related event.
		// Log to the security log and notify guildchat/pgroup.
		if (preg_match("/^security$/i", $second))
		{
			if ($this -> bot -> guildbot)
			{
				$this -> bot -> send_gc ($line);
			}
			else
			{
				$this -> bot -> send_pgroup ($line);
			}
			$log = fopen($this -> bot -> log_path . "/security.txt", "a");
			fputs($log, $line);
			fclose($log);
		}

		if (($this -> bot -> log == "all") || (($this -> bot -> log == "chat") && (($first == "GROUP") || ($first == "TELL") || ($first == "PGRP"))))
		{
			$log = fopen($this -> bot -> log_path . "/" . gmdate("Y-m-d") . ".txt", "a");
			fputs($log, $line);
			fclose($log);
		}

		if ($write_to_db)
		{
			$logmsg = substr($msg, 0, 500);
			$this -> bot -> db -> query("INSERT INTO #___log_message (message, first, second, timestamp) VALUES ('" . mysql_real_escape_string($logmsg) . "','" . $first . "','" . $second . "','" . time() . "')");
		}
	}

	function cut_size($slave, $msg, $type, $to="", $pri=0)
	{
		if(strlen($msg) < 100000)
		{
			preg_match("/^(.*)<a href=\"(.+)\">(.*)$/isU", $msg, $info);
		}
		else
		{
			$var = explode("<a href=\"", $msg, 2);
			$var2 = explode ("\">", $var[1], 2);
			$info[1] = $var[0];
			$info[2] = $var2[0];
			$info[3] = $var2[1];
		}

		$info[2] = str_replace("<br>","\n",$info[2]);
		$content = explode("\n", $info[2]);
		$page = 0;
		$result[$page] = "";
		foreach($content as $line)
		{
			if ((strlen($result[$page]) + strlen($line) + 12) < $this -> bot -> maxsize)
			$result[$page] .= $line . "\n";
			else
			{
				$page++;
				$result[$page] .= $line . "\n";
			}
		}

		$between = "";
		for ($i = 0; $i <= $page; $i++)
		{
			if ($i != 0) $between = "text://";
			$msg = $info[1] . "<a href=\"" . $between . $result[$i] . "\">" . $info[3] .
			" <font color=#ffffff>(page ".($i+1)." of ".($page+1).")</font>";

			if ($type == "tell")
				$this -> send_tell($to, $msg, $pri, TRUE, FALSE, TRUE, $slave);
			else if ($type == "pgroup")
				$this -> send_pgroup($msg, $to, FALSE);
			else if ($type == "gc")
				$this -> send_gc($slave, $msg, $pri, FALSE);
		}
	}

	// Registers a new reference to a module, used to access the new module by other modules.
	public function register_module($ref, $name)
	{
		if (isset($this -> module_links[strtolower($name)]))
		{
			$this -> log($slave, 'CORE', 'ERROR', "Module '$name' has Already Been Registered by ".get_class($this -> module_links[strtolower($name)])." so cannot be registered by ".get_class($ref).".");
			return;
		}
		$this -> module_links[strtolower($name)] = $ref;
	}

	// Unregisters a module link.
	public function unregister_module($name)
	{
		$this -> module_links[strtolower($name)] = NULL;
		unset($this -> module_links[strtolower($name)]);
	}

	// Returns the reference to the module registered under $name. Returns NULL if link is not registered.
	public function core($name)
	{
		if (isset($this -> module_links[strtolower($name)]))
		{
			return $this -> module_links[strtolower($name)];
		}
		$dummy = new BasePassiveModule($this, $name);
		$this -> log($slave, 'CORE', 'ERROR', "Module '$name' does not exist or is not loaded.");
		return $dummy;
	}

	/*
	 * Interface to register and unregister commands
	 */

	public function exists_command($channel, $command)
	{
		$channel = strtolower($channel);
		$command = strtolower($command);
		$exists = false;
		$allchannels = array("gc", "tell", "pgmsg");

		if ($channel == "all")
		{
			foreach ($allchannels AS $cnl)
			{
				$exists = $exists & isset($this -> bot -> commands[$cnl][$command]);
			}
		}
		else
		{
			$exists = isset($this -> bot -> commands[$channel][$command]);
		}

		return $exists;
	}

	public function get_all_commands()
	{
		Return $commands;
	}

	public function get_command_handler($channel, $command)
	{
		$channel = strtolower($channel);
		$command = strtolower($command);
		$handler = "";
		$allchannels = array("gc", "tell", "pgmsg");

		if ($channel == "all")
		{
			$handlers = array();
			foreach ($allchannels AS $cnl)
			{
				$handlers[] = get_class($this -> bot -> commands[$cnl][$command]);
			}
			$handler = implode(", ", $handles);
		}
		else
		{
			$handler = get_class($this -> bot -> commands[$channel][$command]);
		}

		return $handler;
	}

	/*
	 * Interface to register and unregister commands
	 */
	public function register_event($event, $target, $module)
	{
		$event = strtolower($event);

		$events = array(
		'connect',
		'disconnect',

		'pgjoin',
		'pginvite',
		'pgleave',
		'extpgjoin',
		'extpgleave',

		'cron',
		'settings',
		'timer',

		'logon_notify',
		'buddy',

		'privgroup',
		'gmsg',
		'tells',
		'extprivgroup',
		
		'irc'
		);

		if(in_array($event, $events))
		{
			if($event == 'gmsg')
			{
				if ($target)
				{
					$this -> bot -> commands[$event][$target][get_class($module)] = $module;
					return false;
				}
				else
				{
					return "No channel specified for gmsg. Not registering.";
				}
			}
			elseif($event == 'cron')
			{
				$time = strtotime($target, 0);

				if($time > 0)
				{
					if (!isset($this -> cron_job_active[$time]))
					{
			 			$this -> cron_job_active[$time] = false;
					}
					if (!isset($this -> cron_job_timer[$time]))
					{
						$this -> cron_job_timer[$time] = max(time(), $this -> startup_time);
					}
					$this -> cron_times[$time] = $time;
					$this -> cron[$time][get_class($module)] = $module;
					return false;
				}
				else
				{
					return "Cron time '$target' is invalid. Not registering.";
				}
			}
			elseif ($event == 'timer')
			{
				if ($target)
				{
					$this -> bot -> core("timer") -> register_callback($target, $module);
					return false;
				}
				else
				{
					return "No name for the timer callback given! Not registering.";
				}
			}
			elseif ($event == 'logon_notify')
			{
				$this -> bot -> core("logon_notifies") -> register($module);
				return false;
			}
			elseif ($event == 'settings')
			{
				if (is_array($target) && isset($target['module']) && isset($target['setting']))
				{
					return $this -> bot -> core("settings") -> register_callback($target['module'], $target['setting'], $module);
				}
				return "No module and/or setting defined, can't register!";
			}
			elseif($event == 'irc')
			{
				$this -> bot -> core("irc") -> ircmsg[] = $module;
				return false;
			}
			else
			{
				$this -> bot -> commands[$event][get_class($module)] = $module;
				return false;
			}
		}
		else
		{
			return "Event '$event' is invalid. Not registering.";
		}
		return false;
	}

	public function unregister_event($event, $target, $module)
	{
		$event = strtolower($event);

		$events = array(
		'connect',
		'disconnect',

		'pgjoin',
		'pginvite',
		'pgleave',
		'extpgjoin',
		'extpgleave',

		'cron',
		'settings',
		'timer',

		'logon_notify',
		'buddy',

		'privgroup',
		'gmsg',
		'tells',
		'extprivgroup'
		);

		if(in_array($event, $events))
		{
			if($event == 'gmsg')
			{
				if (isset($this -> bot -> commands[$event][$target][get_class($module)]))
				{
					$this -> bot -> commands[$event][$target][get_class($module)] = NULL;
					unset($this -> bot -> commands[$event][$target][get_class($module)]);
					return false;
				}
				else
				{
					return "GMSG $target is not registered or invalid!";
				}
			}
			elseif($event == 'cron')
			{
				$time = strtotime($target, 0);
				if(isset($this -> cron[$time][get_class($module)]))
				{
					$this -> cron[$time][get_class($module)] = NULL;
					unset($this -> cron[$time][get_class($module)]);
					return false;
				}
				else
				{
					return "Cron time '$target' is not registered or invalid!";
				}
			}
			elseif ($event == 'timer')
			{
				return $this -> bot -> core("timer") -> unregister_callback($target, $module);
			}
			elseif ($event == 'logon_notify')
			{
				$this -> bot -> core("logon_notifies") -> unregister($module);
				return false;
			}
			elseif ($event == 'settings')
			{
				if (is_array($target) && isset($target['module']) && isset($target['setting']))
				{
					return $this -> bot -> core("settings") -> unregister_callback($target['module'], $target['setting'], $module);
				}
				return "No module and/or setting defined, can't unregister!";
			}
			else
			{
				$this -> bot -> commands[$event][get_class($module)] = NULL;
				unset($this -> bot -> commands[$event][get_class($module)]);
				return false;
			}
		}
		else
		{
			return "Event '$event' is invalid. Not registering.";
		}
		return false;
	}
}

if(!empty($slaves))
	$aoc = new AOChati($bot, $aoc, $slaves);

class AOChati
{
	var $bot, $aoc, $slaves;
	function __construct($bot, $aoc, $slaves)
	{
		$this -> bot = $bot;
		$this -> slaves = &$slaves;
		$this -> aoc = $aoc;
		$this -> bot -> aoc = $this;
	}

 	function buddy_add($user, $type="\1")
	{
		$uname = $this -> aoc -> get_uname($user);
		if(isset($this -> slaves -> buds[$uname]))
		{
			echo "buddy add bot (already assigned) = ".$this -> slaves -> buds[$uname]."\n";
			Return $this -> slaves -> aoc[$this -> slaves -> buds[$uname]] -> buddy_add($user, $type="\1");
		}
		else
		{
			$lowest = 99999;
			foreach($this -> slaves -> aoc as $num => $aoc)
			{
				$buds = count($aoc -> buddies);
				if($buds < $lowest)
				{
					$lowest = $buds;
					$lowestnum = $num;
				}
			}
			echo "buddy add bot = ".$lowestnum."\n";
			return $this -> slaves -> aoc[$lowestnum] -> buddy_add($user, $type="\1");
		}
	}

	function buddy_remove($user)
	{
		$uname = $this -> aoc -> get_uname($user);
		if(isset($this -> slaves -> buds[$uname]))
			Return $this -> slaves -> aoc[$this -> slaves -> buds[$uname]] -> buddy_remove($user);
		else
			Return $this -> aoc -> buddy_remove($user);
	}

	function buddy_remove_unknown()
	{
		foreach($this -> slaves -> aoc as $aoc)
		{
			$aoc -> buddy_remove_unknown();
		}
	}

	function buddy_exists($who)
	{
		$uname = $this -> aoc -> get_uname($who);
		if(isset($this -> slaves -> buds[$uname]))
		{
			//echo "buddy exists bot = ".$this -> slaves -> buds[$uname]."\n";
			Return $this -> slaves -> aoc[$this -> slaves -> buds[$uname]] -> buddy_exists($who);
		}
		else
		{
			Return $this -> aoc -> buddy_exists($who);
		}
	}

	function buddy_online($who)
	{
		$uname = $this -> aoc -> get_uname($who);
		if(isset($this -> slaves -> buds[$uname]))
			Return $this -> slaves -> aoc[$this -> slaves -> buds[$uname]] -> buddy_online($who);
		else
			Return $this -> aoc -> buddy_online($who);
	}

	function wait_for_packet($time = 1)
	{
		while(true)
		{
			$notnull = FALSE;
			if(!empty($this -> slaves -> aoc))
			{
				foreach($this -> slaves -> aoc as $num => $aoc)
				{
					$time = microtime(true);
					while($time + 0.2 > microtime(true))
					{
						$packet = $aoc -> wait_for_packet(0);
						if ($packet == "disconnected")
							$this -> slaves -> reconnect($num);
						elseif($packet === NULL)
							$time = $time - 1;
						else
							$notnull = TRUE;
						$this -> bot -> cron();
					}
					$time = microtime(true);
					while($time + 0.5 > microtime(true))
					{
						$packet = $this -> aoc -> wait_for_packet(0);
						if ($packet == "disconnected")
							$this -> bot -> reconnect();
						elseif($packet === NULL)
							$time = $time - 1;
						else
							$notnull = TRUE;
						$this -> bot -> cron();
					}
				}
			}
			$time = microtime(true);
			while($time + 0.5 > microtime(true))
			{
				if(!$notnull)
					$sec = 1;
				else
					$sec = 0;
				$packet = $this -> aoc -> wait_for_packet($sec);
				if ($packet == "disconnected")
					$this -> bot -> reconnect();
				elseif($packet === NULL)
					$time = $time - 1;
				else
					$notnull = TRUE;
				$this -> bot -> cron();
			}
		}

	$this -> slaves -> aoc[1] -> wait_for_packet();
	return;
		if(!empty($this -> slaves -> aoc))
		{
			foreach($this -> slaves -> aoc as $aoc)
				$aoc -> wait_for_packet();
		}
		Return $this -> aoc -> wait_for_packet();
	}

	public function __get($name)
	{
		if(strtolower($name) == "buddies")
		{
			echo "Buddies: ";
			foreach($this -> slaves -> aoc as $num => $aoc)
			{
				$buds = $aoc -> buddies;
				echo $num."=".count($buds).", ";
				$abuds2 += count($buds);
				foreach($buds as $k => $v)
				{
					$abuds[$k] = $v;
				}
			}
			echo "Total=$abuds2(".count($abuds)." unique)\n";
			Return $abuds;
		}
		Return $this -> aoc -> $name;
	}

	public function __call($name, $args)
	{
		Switch(count($args))
		{
			case '0':
				return $this -> aoc -> $name();
			case '1':
				return $this -> aoc -> $name($args[0]);
			case '2':
				return $this -> aoc -> $name($args[0], $args[1]);
			case '3':
				return $this -> aoc -> $name($args[0], $args[1], $args[2]);
			case '4':
				return $this -> aoc -> $name($args[0], $args[1], $args[2], $args[3]);
			case '5':
				return $this -> aoc -> $name($args[0], $args[1], $args[2], $args[3], $args[4]);
			case '6':
				return $this -> aoc -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
			case '7':
				return $this -> aoc -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
			case '8':
				return $this -> aoc -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
			case '9':
				return $this -> aoc -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]);
			case '10':
				return $this -> aoc -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9]);
		}
	}
}

if(!empty($slaves))
	$bot = new boti($bot, $slaves);

class boti
{
	var $bot, $slaves;
	function __construct($bot, $slaves)
	{
		$this -> bot = $bot;
		$this -> slaves = $slaves;
	}

	function inc_buddy($args)
	{
		$this -> slaves -> inc_buddy($args, 0);
	}

	function send_tell($to, $msg, $low=0, $color=true, $sizecheck=TRUE, $parsecolors=TRUE)
	{
		$this -> slaves -> send_tell($to, $msg, $low, $color, $sizecheck, $parsecolors);
	}

	public function __get($name)
	{
		Return $this -> bot -> $name;
	}

	public function __call($name, $args)
	{
		Switch(count($args))
		{
			case '0':
				return $this -> bot -> $name();
			case '1':
				return $this -> bot -> $name($args[0]);
			case '2':
				return $this -> bot -> $name($args[0], $args[1]);
			case '3':
				return $this -> bot -> $name($args[0], $args[1], $args[2]);
			case '4':
				return $this -> bot -> $name($args[0], $args[1], $args[2], $args[3]);
			case '5':
				return $this -> bot -> $name($args[0], $args[1], $args[2], $args[3], $args[4]);
			case '6':
				return $this -> bot -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
			case '7':
				return $this -> bot -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
			case '8':
				return $this -> bot -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
			case '9':
				return $this -> bot -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]);
			case '10':
				return $this -> bot -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9]);
		}
	}
}

if(!empty($slaves))
	$chat_queue_core_slave = new Chat_Queue_Core_Slave($bot, $slaves);

/*
The Class itself...
*/
class Chat_Queue_Core_Slave extends BasePassiveModule
{
	private $que;
	private $que_low;
	private $msgs_left;
	private $last_call;



	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct($bot, $slaves)
	{
		parent::__construct($bot, get_class($this));
		$this -> slaves = $slaves;
		$this -> slaves -> chatq = $this;

		$this -> register_event("cron", "2sec");

		$this -> queue = array();
		$this -> queue_low = array();
	}



	/*
	This gets called on cron
	*/
	function cron()
	{
		$this -> set_msgs();
		if(!empty($this -> slaves -> slaves))
		{
			$bots = $this -> slaves -> slaves;
		}
		$bots[0] = TRUE;
		if (!empty($this-> queue))
		{
			foreach ($this -> queue as $key => $value)
			{
				$sent = FALSE;
				foreach($bots as $num => $slave)
				{
					if(!$sent)
					{
						if ($this -> msgs_left[$num] >= 1)
						{
							if(!$value[3] || $value[3] == $num)
							{
								$to = $value[0];
								$msg = $value[1];
								if ($value[2] == "tell")
								{
									$this -> slaves -> log($num, "TELL", "OUT", "-> " . $this -> bot -> core("chat") -> get_uname($to) . ": " . $msg);
									$msg = utf8_encode($msg);
									$this -> slaves -> aoc[$num] -> send_tell($to, $msg);
								}
								else
								{
									$msg = utf8_encode($msg);
									$this -> slaves -> aoc[$num] -> send_group($to, $msg);
								}

								unset($this -> queue[$key]);
								$this -> msgs_left[$num] -= 1;
								$sent = TRUE;
							}
						}
					}
				}
			}
		}
		if (!empty($this-> queue_low))
		{
			$this -> set_msgs();
			foreach ($this -> queue_low as $key => $value)
			{
				$sent = FALSE;
				foreach($bots as $num => $slave)
				{
					if(!$sent)
					{
						if ($this -> msgs_left[$num] >= 1)
						{
							if(!$value[3] || $value[3] == $num)
							{
								$to = $value[0];
								$msg = $value[1];
								if ($value[2] == "tell")
								{
									$this -> slaves -> log($num, "TELL", "OUT", "-> " . $this -> bot -> core("chat") -> get_uname($to) . ": " . $msg);
									$msg = utf8_encode($msg);
									$this -> slaves -> aoc[$num] -> send_tell($to, $msg);
								}
								else
								{
									$msg = utf8_encode($msg);
									$this -> slaves -> aoc[$num] -> send_group($to, $msg);
								}

								unset($this -> queue_low[$key]);
								$this -> msgs_left[$num] -= 1;
							}
						}
					}
				}
			}
		}
		return true;
	}



	/*
	Sents messages left...
	*/
	function set_msgs()
	{
		$time = time();
		$this -> msgs_left[0] += ($time - $this -> last_call[0]) / ($this -> bot -> telldelay / 1000);
		$this -> last_call[0] = $time;
		if ($this -> msgs_left[0] > 4)
			$this -> msgs_left[0] = 4;
		if(!empty($this -> slaves -> slaves))
		{
			foreach($this -> slaves -> slaves as $num => $slave)
			{
				$this -> msgs_left[$num] += ($time - $this -> last_call[$num]) / ($this -> bot -> telldelay / 1000);
				$this -> last_call[$num] = $time;
				if ($this -> msgs_left[$num] > 4)
					$this -> msgs_left[$num] = 4;
			}
		}
	}



	/*
	Checks if tell can be sent. true if yes, false it has to be put to queue
	*/
	function check_queue($slave)
	{
		$this -> set_msgs();
		if($slave)
		{
			if (($this -> msgs_left[$slave] >= 1) && empty($this -> queue) && empty($this -> queue_low))
			{
				$this -> msgs_left[$slave] -= 1;
				return (array(true, $slave));
			}
			return false;
		}
		if (($this -> msgs_left[0] >= 1) && empty($this -> queue) && empty($this -> queue_low))
		{
			$this -> msgs_left[0] -= 1;
			return (array(true, 0));
		}
		if(!empty($this -> slaves -> slaves))
		{
			foreach($this -> slaves -> slaves as $num => $slave)
			{
				if (($this -> msgs_left[$num] >= 1) && empty($this -> queue) && empty($this -> queue_low))
				{
					$this -> msgs_left[$num] -= 1;
					return (array(true, $num));
				}
			}
		}
		return false;
	}



	/*
	Puts a msg into queue
	*/
	function into_queue($to, $msg, $type, $priority, $slave)
	{
		if ($priority == 0)
		$this -> queue[] = array($to, $msg, $type, $slave);
		else
		$this -> queue_low[] = array($to, $msg, $type, $slave);
	}
}

if(!empty($slaves))
	$roster_core_slave = new Roster_Core_Slave($bot, $slaves);

class Roster_Core_Slave extends BasePassiveModule
{
	function __construct($bot, $slaves)
	{
		parent::__construct($bot, get_class($this));
		$this -> slaves = $slaves;
		$this -> register_event("connect");
	}

	function connect()
	{
		$this -> roster = $this -> bot -> core("roster_core");
		$this -> bot -> unregister_module("roster_core");
		$this -> register_module("roster_core");
	}

	function update_guild($force = false)
	{
		$this -> lastrun = $this -> bot -> core("settings") -> get("members", "LastRosterUpdate");
		if (($this -> lastrun + (60 * 60 * 6)) >= time() && $force == false)
		{
			$this -> bot -> log("ROSTER", "UPDATE", "Roster update ran less than 6 hours ago, skipping!");
			$this -> bot -> send_gc("##normal##".$msg."Roster update not scheduled ::: System ready##end##");
			Return;
		}
		if($this -> roster -> running)
		{
			$this -> bot -> send_gc("Roster update is Already Running");
			Return("Roster update is Already Running");
		}
		$this -> slaves -> update_buddy_cache(TRUE);
		$this -> roster -> update_guild($force);
	}

	function update_raid($force = false)
	{
		if($this -> running)
		{
			$this -> bot -> send_pgroup("Roster update is Already Running");
			Return("Roster update is Already Running");
		}

		$this -> lastrun = $this -> bot -> core("settings") -> get("members", "LastRosterUpdate");
		if (($this -> lastrun + (60 * 60 * 6)) >= time() && $force == false)
		{
			$this -> bot -> log("ROSTER", "UPDATE", "Roster update ran less than 6 hours ago, skipping!");
			if($this -> bot -> game == "ao")
				$this -> bot -> send_pgroup("##normal##".$msg."Roster update not scheduled ::: System ready##end##");
		}
		$this -> slaves -> update_buddy_cache(TRUE);
		$this -> roster -> update_raid($force);
	}

	public function __get($name)
	{
		Return $this -> roster -> $name;
	}

	public function __call($name, $args)
	{
		Switch(count($args))
		{
			case '0':
				return $this -> roster -> $name();
			case '1':
				return $this -> roster -> $name($args[0]);
			case '2':
				return $this -> roster -> $name($args[0], $args[1]);
			case '3':
				return $this -> roster -> $name($args[0], $args[1], $args[2]);
			case '4':
				return $this -> roster -> $name($args[0], $args[1], $args[2], $args[3]);
			case '5':
				return $this -> roster -> $name($args[0], $args[1], $args[2], $args[3], $args[4]);
			case '6':
				return $this -> roster -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
			case '7':
				return $this -> roster -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
			case '8':
				return $this -> roster -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
			case '9':
				return $this -> roster -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]);
			case '10':
				return $this -> roster -> $name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9]);
		}
	}
}
?>