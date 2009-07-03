<?php
/*
* Main.php - Main loop and parser
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
* File last changed at $LastChangedDate: 2009-01-24 21:08:06 +0000 (Sat, 24 Jan 2009) $
* Revision: $Id: Main.php 1963 2009-01-24 21:08:06Z alreadythere $
*/

define('BOT_VERSION', "0.7.0.bzr(snapshot)");
$bot_version = BOT_VERSION;
$php_version = phpversion();

// Set the time zone to UTC
date_default_timezone_set('UTC');

/*
OS detection, borrowed from Angelsbot.
*/
$os = getenv("OSTYPE");
if (empty($os))
{
	$os = getenv("OS");
}

if (preg_match("/^windows/i", $os))
{
	$os_windows = true;
}

/*
Check if we are running on a 64bit system or not
*/
if (PHP_INT_SIZE == 4)
{
	$sixtyfourbit = false;
	$osbit = "32bit";
}
else
{
	$sixtyfourbit = true;
	$osbit = "64bit";
}



echo "
===================================================\n
    _/_/_/              _/_/_/                _/   \n
   _/    _/    _/_/    _/    _/    _/_/    _/_/_/_/\n
  _/_/_/    _/_/_/_/  _/_/_/    _/    _/    _/     \n
 _/    _/  _/        _/    _/  _/    _/    _/      \n
_/_/_/      _/_/_/  _/_/_/      _/_/        _/_/   \n
         An Anarchy Online Chat Automaton          \n
                     And                           \n
          An Age of Conan Chat Automaton           \n
             v.$bot_version - PHP $php_version     \n
			 OS: $os                               \n
	Your operating system is detected as $osbit    \n
===================================================\n
";

sleep(2);


/*
Load up the required files.
RequirementCheck.php: Check that we're running in a sane environment
MySQL.conf: The MySQL configuration.
MySQL.php: Used to communicate with the MySQL database
AOChat.php: Interface to communicate with AO chat servers
Bot.php: The actual bot itself.
*/

echo "Loading required files...\n RequirementCheck.php...";
require_once "./Sources/RequirementCheck.php";
echo "success...\n MySQL.php...";
require_once "./Sources/MySQL.php";
echo "success...\n AOChat.php...";
require_once "./Sources/AOChat.php";
echo "success...\n ConfigMagik.php...";
require_once "./Sources/ConfigMagik.php";
echo "success...\n Bot.php...";
require_once "./Sources/Bot.php";
echo "success...\n Dispatcher.php...";
require_once "./Sources/Dispatcher.php";
echo "success...\n All required files loaded.\n";
/*
Creating the bot.
*/
echo "Creating main Bot class!\n";
$bothandle = Bot::factory($argv[1]);
$bot = Bot::get_instance($bothandle);
  
//Load modules.
$bot->load_files('Commodities', 'commodities'); //Classes that do not instantiate themselves.
$bot->load_files('Commodities', "commodities/{$bot->game}");
$bot->load_files('Main', 'main');
$bot->load_files('Core', 'core');
$bot->load_files('Core', "core/{$bot->game}");
$bot->load_files('Core', 'custom/core');
if(!empty($bot->core_directories))
{
	$core_dirs = explode(",", $bot->core_directories);
	foreach ($core_dirs as $core_dir)
	{
		$bot->load_files('Core', trim($core_dir));
	}
}
$bot->load_files('Modules', 'modules');
$bot->load_files('Modules', "modules/{$bot->game}");
$bot->load_files('Modules', 'custom/modules');
if(!empty($bot->module_directories))
{
	$module_dirs = explode(",", $bot->module_directories);
	foreach ($module_dirs as $module_dir)
 	{
		$bot->load_files('Modules', trim($module_dir));
 	}
}

// Start up the bot.
$bot -> connect();

?>