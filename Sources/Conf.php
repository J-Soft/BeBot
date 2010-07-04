<?php
/*
* Conf.php - Creating config files on first start of bot
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
$conf = new Conf($argv, $confc);
class Conf
{
	private $conf;
	private $login;

	function __construct($argv, $confc)
	{
		$this->confc = $confc;
		$this->argv = $argv[1];
		$this->check($argv);
		$this->mysql_check();
		$this->load();
	}

	function load($argv = FALSE)
	{
		if (file_exists("./conf/" . $this->cf))
		{
			require "./conf/" . $this->cf;
			if (empty($ao_password) || $ao_password == "")
			{
				fwrite(STDOUT, "Password for $ao_username:");
				system('stty -echo');
				$this->pw = trim(fgets(STDIN));
				system('stty echo');
			}
		}
	}

	function check($argv = FALSE)
	{
		if (! $this->cf)
		{
			// If an argument is given, use that to create the config file name.
			if (is_array($argv) && count($argv) > 1)
				$this->cf = ucfirst(strtolower($argv[1])) . ".Bot.conf";
			else
				$this->cf = "Bot.conf";
		}
		if (! file_exists("./conf/" . $this->cf))
		{
			$setup = $this->todo();
		}
	}

	function todo($rep = FALSE)
	{
		if (! $rep)
			echo $this->cf . " Does not Exist\n What do you want to do?\n(r=Retry, n=new, c=Change, q=quit)\n";
		$do = $this->ask("Select: ");
		$do = strtolower($do);
		if ($do == "q")
			Die("The bot has been shutdown\n");
		elseif ($do == "c")
		{
			echo "Please Enter a New Value to use:\n";
			$newarg = $this->ask("");
			if ($newarg == "")
			{
				$this->argv = FALSE;
				$this->cf = "Bot.conf";
			}
			else
			{
				$this->argv = $newarg;
				$this->cf = ucfirst(strtolower($newarg)) . ".Bot.conf";
			}
			Return $this->check();
		}
		elseif ($do == "r")
			Return $this->check();
		elseif ($do == "n")
			Return $this->set_conf();
		else
			$this->todo(TRUE);
	}

	function set_conf()
	{
		echo "\nCreating Conf File: " . $this->cf . "\n";
		$ao_username = $this->ask("User Name:");
		$ao_password = $this->ask("Password:");
		$bot_name = $this->ask("Botname:");
		$dimension = $this->ask("Dimension/Server Name:");
		if (! is_numeric($dimension))
			$guild = $this->ask("Guild:");
		$owner = $this->ask("Owner:");
		while (! $guildbot)
		{
			echo "Is this a Guild Bot? (y/yes or n/no)\n";
			$gb = $this->ask("Guild bot:");
			$gb = strtolower($gb);
			if ($gb == "y" || $gb == "yes")
				$guildbot = "TRUE";
			elseif ($gb == "n" || $gb == "no")
				$guildbot = "FALSE";
		}
		echo "Superadmins enter nothing when done.\n";
		$sa[0] = '
	// $super_admin["Superadmin1"] = true;';
		$sa[1] = '
	// $super_admin["Superadmin2"] = true;';
		$san = 0;
		while (! $sac)
		{
			$saask = $this->ask("SuperAdmin:");
			if ($saask != "")
			{
				$sa[$san] = '
	$super_admin["' . $saask . '"] = true;';
				$san ++;
			}
			else
				$sac = TRUE;
		}
		$sa[0] .= '// Bot superadmins.';
		$sa = implode("", $sa);
		$file = '<?php
	//These are the general settings of the bot:

	$ao_username = "' . $ao_username . '";				// Account username
	$ao_password = "' . $ao_password . '";				// Account password
	$bot_name = "' . $bot_name . '";				// Name of the bot-character
	$dimension = "' . $dimension . '";				// The name of the server you play on, or (1, 2 or 3 for AO)
	$guild = "' . $guild . '";				// Name of the guild running the bot (AOC only)

	/*
	Suggested values for owner and super_admin:
	We suggest that the owner should be the main characer on the
	account for $ao_username.
	super_admis should be alts on the same account.

	Defining a superadmin in the config file prevents their removal and banning.
	You are able to add more superadmins with the ingame interface.
	Superadmins defined in game are able to be removed and banned.
	*/
	$owner = "' . $owner . '";				 // Owner of the bot.' . $sa . '


	// $other_bots["Bot1"] = true;	 // All other bots that are guildmembers/raidbotmembers
	// $other_bots["Bot2"] = true;


	$guildbot = ' . $guildbot . ';				// false if its a raidbot.
	$guild_id = 00000001;			// only if its a guildbot.


	$log = "chat";					 // logging all/chat/off
	$log_path = "./log";			 // relative/absolute path of logfiles
	$log_timestamp = "none";	//Valid options are: datetime, date, time, none.  Always defaults to datetime if missing or invalid.


	/*
	The next two entries define a list of additional core and module directories to be loaded after the core/ and custom/core/
	btw the module/ and custom/module/ directories. The list is parsed as a comma-seperated list relative the the base directory
	of the bot, without any / at the end of the directory names.
	*/
	$core_directories = "";	// optional additional core directories
	$module_directories = "";	// optional additional module directories


	$command_prefix = "!";		// Please make sure this is in a Reg-Exp format... (exampe: for "." use "\.")
								 	// The prefix cannot be more then one character - either empty or one character (more only for regexp format)


	$cron_delay = 30;				// Number of seconds before the cron jobs get executed the first time.
	$tell_delay = 2222;				// Number of miliseconds between tells. (anti-flooding)
	$reconnect_time = 60;			// Time to wait for auto reconnect after an error has accured.
	if(is_numeric($dimension))
	$max_blobsize = 12000;			// Maximum size of text blobs in byte. For AO
	else
	$max_blobsize = 8000;			// Maximum size of text blobs in byte. For AoC
	$accessallbots = FALSE;			 // Allow Access to All Bots in modules like BotStatistics

	/*
	WARNING!  Enabling proxies will allow you to pull information from web servers if you have been blocked.
	The more proxy addresses you have, the slower each lookup will be.  It is recommended that no more than
	one proxy be added at any given time.  Proxies will only be used as a fallback (if the first lookup fails).
	Format for $proxy_server_address: IP:PORT (The list is parsed as a comma-seperated list)
	Example: $proxy_server_address = "4.2.2.2:80,4.2.2.3:80,4.2.2.4:80";
	*/
	$use_proxy_server = false;				// Enable simple web proxy server for HTTP lookups?
	$proxy_server_address = "";				// Proxy server to use address to use

?>';
		$fp = fopen('./conf/' . $this->cf, 'w');
		fwrite($fp, $file);
		fclose($fp);
		echo $this->cf . " Created\n";
	}

	function mysql_check()
	{
		//get botname
		include ("./conf/" . $this->cf);
		$botname = ucfirst(strtolower($bot_name));
		$botname_mysql_conf = "conf/" . $botname . ".MySQL.conf";
		if (file_exists($botname_mysql_conf))
			Return;
		elseif (file_exists("conf/MySQL.conf"))
			Return;
		else
			$this->mysql_todo($botname);
	}

	function mysql_todo($botname, $rep = FALSE)
	{
		if (! $rep)
			echo $botname . ".MySQL.conf and MySQL.conf Does not Exist\n What do you want to do?\n(r=Retry, n=new, q=quit)\n";
		$do = $this->ask("Select: ");
		$do = strtolower($do);
		if ($do == "q")
			Die("The bot has been shutdown\n");
		elseif ($do == "r")
			Return $this->mysql_check();
		elseif ($do == "n")
			Return $this->mysql_set_conf($botname);
		else
			$this->mysql_todo(TRUE);
	}

	function mysql_set_conf($botname)
	{
		echo "\nCreating MySQL Conf File\n";
		echo "Would u like to use botname like botname.MySQL.conf  (y/yes or n/no)\n";
		while (! $filename)
		{
			$ubn = $this->ask("Use Botname:");
			$ubn = strtolower($ubn);
			if ($ubn == "y" || $ubn == "yes")
				$filename = $botname . ".MySQL.conf";
			elseif ($ubn == "n" || $ubn == "no")
				$filename = "MySQL.conf";
		}
		echo "MySQL Details:\n";
		$dbase = $this->ask("Database name:");
		$user = $this->ask("Username:");
		$pass = $this->ask("Password:");
		echo "Database server (usually localhost) Enter nothing for localhost";
		$server = $this->ask("Server:");
		if ($server == "")
			$server = "localhost";
		echo "The bot will use " . $botname . " as prefix on default, Do you want to use Default? (y/yes or n/no)\n";
		while (! $prefix)
		{
			$ubn = $this->ask("Use default prefix:");
			$ubn = strtolower($ubn);
			if ($ubn == "y" || $ubn == "yes")
				$prefix = '//$table_prefix = "";';
			elseif ($ubn == "n" || $ubn == "no")
				$prefix = "ask";
		}
		if ($prefix == "ask")
		{
			$prefix = $this->ask("Prefix:");
			$prefix = '$table_prefix = "' . $prefix . '";';
		}
		echo "The bot will use " . $botname . "_tablenames for tablename table on default, Do you want to use Default? (y/yes or n/no)\n";
		while (! $mt)
		{
			$mtq = $this->ask("Use default prefix:");
			$mtq = strtolower($mtq);
			if ($mtq == "y" || $mtq == "yes")
				$mt = '//$master_tablename = "botname_tablenames";';
			elseif ($mtq == "n" || $mtq == "no")
				$mt = "ask";
		}
		if ($mt == "ask")
		{
			$mt = $this->ask("Table name for tablenames:");
			$mt = '$master_tablename = "' . $mt . '";';
		}
		$file = '<?php
	/*
	Database name
	*/
	$dbase = "' . $dbase . '";

	/*
	Database username
	*/
	$user = "' . $user . '";

	/*
	Database password
	*/
	$pass = "' . $pass . '";

	/*
	Database server (usually localhost)
	*/
	$server = "' . $server . '";

	/*
	Database table prefix
	The bot will use <botname> as prefix on default, you only need to change this entry if you
	want a different prefix or none at all, in which case you have to set it to an empty
	string ("").
	If you want a different or no prefix you will have to uncomment the line below by removing
	the // in front of it and set it to the wished value.
	*/
	' . $prefix . '

	/*
	Master prefix table.
	This is the mastertable containing information about all tablenames and whether those use
	or don\'t use a prefix. Only uncomment the line below by removing the // in front of it if
	you want to use a different mastertable then botname_tablenames, which is used on default.
	*/
	' . $mt . '
?>';
		$fp = fopen('./conf/' . $filename, 'w');
		fwrite($fp, $file);
		fclose($fp);
		echo $filename . " Created\n";
	}

	function ask($ask)
	{
		// ask for input
		fwrite(STDOUT, $ask);
		// get input
		Return (trim(fgets(STDIN)));
	}
}
?>