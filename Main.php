<?php
/*
* Main.php - Main loop and parser
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2012 J-Soft and the BeBot development team.
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
define('BOT_VERSION', "0.7.0");
define('BOT_VERSION_INFO', ".git(snapshot)");
define('BOT_VERSION_NAME', "BeBot");

// Is this a development snapshot from BZR?
define('BOT_VERSION_SNAPSHOT', true);

// Is this a stable release or a development release?
define('BOT_VERSION_STABLE', false);

$bot_version = BOT_VERSION;
$php_version = phpversion();
// Set the time zone to UTC
date_default_timezone_set('UTC');
/*
OS detection, borrowed from Angelsbot.
*/
$os = getenv("OSTYPE");
if (empty($os)) {
    $os = getenv("OS");
}
if (preg_match("/^windows/i", $os)) {
    define('OS_WINDOWS', true);
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
          v.$bot_version - PHP $php_version        \n
		  OS: $os                                  \n
===================================================\n
";

sleep(2);


/*
Load up the required files.
RequirementsCheck.php: Check that we're running in a sane environment
MySQL.conf: The MySQL configuration.
MySQL.php: Used to communicate with the MySQL database
AOChat.php: Interface to communicate with AO chat servers
Bot.php: The actual bot itself.
*/
require_once "./Sources/RequirementsCheck.php";
require_once "./Sources/Mysql.php";
require_once "./Sources/AoChat.php";
require_once "./Sources/ConfigMagik.php";
require_once "./Sources/Bot.php";
require_once "./Sources/SymfonyEvent/sfEventDispatcher.php";

/*
Creating the bot.
*/
echo "Creating main Bot class!\n";
if (isset($argv[1])) {
    $bothandle = Bot::factory($argv[1]);
} else {
    $bothandle = Bot::factory();
}
$bot = Bot::get_instance($bothandle);
$bot->dispatcher = new sfEventDispatcher();

//Load modules.
$bot->load_files('Commodities', 'Commodities'); //Classes that do not instantiate themselves.
$bot->load_files('Commodities', "Commodities/{$bot->game}");
$bot->load_files('Main', 'Main');
$bot->load_files('Core', 'Core');
$bot->load_files('Core', "Core/{$bot->game}");
$bot->load_files('Core', 'Custom/Core');
if (!empty($bot->core_directories)) {
    $core_dirs = explode(",", $bot->core_directories);
    foreach ($core_dirs as $core_dir) {
        $bot->load_files('Core', trim($core_dir));
    }
}
$bot->load_files('Modules', 'Modules');
$bot->load_files('Modules', "Modules/{$bot->game}");
$bot->load_files('Modules', 'Custom/Modules');
if (!empty($bot->module_directories)) {
    $module_dirs = explode(",", $bot->module_directories);
    foreach ($module_dirs as $module_dir) {
        $bot->load_files('Modules', trim($module_dir));
    }
}
// Start up the bot.
$bot->connect();

while (true) {
    if ($bot->aoc->wait_for_packet() == "disconnected") {
        $bot->reconnect();
    }
    $bot->cron();
}
?>
