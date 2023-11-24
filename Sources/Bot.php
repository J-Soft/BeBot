<?php
/*
* Bot.php - The actual core functions for the bot
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
/*
This is where the basic magic happens
Some functions you might need:

connect():
Connects the bot with AO Chatserver

disconnect():
Disconnects the bot from AO Chatserver

reconnect():
Disconnects and then connects the bot from AO Chatserver

log($first, $second, $msg):
Writes to console/log file.

make_blob($title, $content):
Makes a text blob.
- Returns blob.

make_chatcommand($link, $title):
Creates a clickable chatcommand link
- Returns string

make_item($lowid, $highid, $ql, $name)
Makes an item reference.
- Returns reference blob.

send_tell($to, $msg):
Sends a tell to character.

send_pgroup($msg):
Sends a msg to the privategroup.

send_gc($msg):
Sends a msg to the guildchat.

send_help($to):
Sends /tell <botname> <pre>help.

send_permission_denied($to, $command, $type)
If $type is missing or 0 error is returned to the calling function, else it
sends a permission denied error to the apropriate location based on $type for $command.

get_site($url, $strip_headers, $server_timeout, $read_timeout):
Retrives the content of a site

int_to_string($int)
Used to convert an overflowed (unsigned) integer to a string with the correct positive unsigned integer value
If the passed integer is not negative, the integer is merely passed back in string form with no modifications.
- Returns a string.

string_to_int($string)
Used to convert an unsigned interger in string form to an overflowed (negative) integere
If the passed string is not an integer large enough to overflow, the string is merely passed back in integer form with no modifications.
- Returns an integer.
*/
define('CHAT_AO_TELL', bindec("000000001")); // 00 00 00 001
define('CHAT_AO_PGROUP', bindec("000000010")); // 00 00 00 010
define('CHAT_AO_GC', bindec("000000100")); // 00 00 00 100
define('CHAT_AO', bindec("000000111")); // 00 00 00 111
define('CHAT_IRC_PRIV', bindec("000001000")); // 00 00 01 000
define('CHAT_IRC_CHAN', bindec("000010000")); // 00 00 10 000
define('CHAT_IRC', bindec("000011000")); // 00 00 11 000
define('CHAT_MSN_PRIV', bindec("000100000")); // 00 01 00 000
define('CHAT_MSN_PUB', bindec("001000000")); // 00 10 00 000
define('CHAT_MSN', bindec("001100000")); // 00 11 00 000
define('CHAT_PRIVATE', bindec("000101001")); // 00 01 01 001
define('CHAT_ALL', bindec("111111111")); // 11 1111111
define("SAME", 1);
define("TELL", 2);
define("GC", 4);
define("PG", 8);
define("RELAY", 16);
define("IRC", 32);
define("ALL", 255);
class Bot
{
    var $lasttell;
    var $banmsgout;
    var $dimension;
    var $botversion;
    var $botversionname;
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
	var $sixtyfourbit;
	public $slave;
    public $owner;
    public $super_admin;
    private $module_links = array();
    private $cron_times = array();
    private $cron_job_timer = array();
    private $cron_job_active = array();
    private $cron_actived = false;
    private $cron = array();
    private $startup_time;
    public $glob = array();
    public $botname;
    public $bothandle; // == botname@dimension
    public $debug = false;
    public static $instance;
    public $dispatcher;


    public static function factory($config_file = null)
    {
        require('./Conf/ServerList.php');
        if (!empty($config_file)) {
            $config_file = ucfirst(strtolower($config_file)) . ".Bot.conf";
        } else {
            $config_file = "Bot.conf";
        }
        //Read config_file
        if (file_exists("./Conf/" . $config_file)) {
            require_once "./Conf/" . $config_file;
            echo "Loaded bot configuration from Conf/" . $config_file . "\n";
        } else {
            die("Could not read config file Conf/" . $config_file);
        }

        if (empty($ao_password) || $ao_password == "") {
            $fp = fopen('./Conf/pw', 'r');
            if ($fp) {
                $ao_password = fread($fp, filesize('./Conf/pw'));
                fclose($fp);
                $fp = fopen('./Conf/pw', 'w');
                fwrite($fp, "");
                fclose($fp);
            } else {
                if (empty($ao_password) || $ao_password == "") {
                    die("No password set in either ./Conf/" . $config_file . " or in Conf/pw");
                }
            }
        }
        //Determine which game we are playing
        if (!empty($server_list['Ao'][$dimension])) {
            define('AOCHAT_GAME', 'Ao');
        } elseif (!empty($server_list['Aoc'][$dimension])) {
            define('AOCHAT_GAME', 'Aoc');
        } else {
            die("Unable to find dimension '$dimension' in any game.");
        }
        //Make sure that the log path exists.
        $logpath = $log_path . "/" . strtolower($bot_name) . "@RK" . $dimension;
        if (!file_exists($logpath)) {
            mkdir($logpath, 0777, true);
        }
        //Determine bothandle
        $bothandle = $bot_name . "@" . $dimension;
        //Check if bot has already been created.
        if (isset(self::$instance[$bothandle])) {
            return self::$instance[$bothandle];
        }
        //instantiate bot
        $class = __CLASS__;
        self::$instance[$bothandle] = new $class($bothandle);
        self::$instance[$bothandle]->server = $server_list[AOCHAT_GAME][$dimension]['server'];
        self::$instance[$bothandle]->port = $server_list[AOCHAT_GAME][$dimension]['port'];
        //initialize bot.
        self::$instance[$bothandle]->username = $ao_username;
        self::$instance[$bothandle]->password = $ao_password;
        self::$instance[$bothandle]->botname = ucfirst(strtolower($bot_name));
        self::$instance[$bothandle]->dimension = ucfirst(strtolower($dimension));
        self::$instance[$bothandle]->botversion = 'BOT_VERSION';
        self::$instance[$bothandle]->botversionname = 'BOT_VERSION_NAME';
        self::$instance[$bothandle]->other_bots = array();
        self::$instance[$bothandle]->commands = array();
        self::$instance[$bothandle]->commpre = $command_prefix;
        self::$instance[$bothandle]->crondelay = $cron_delay;
        self::$instance[$bothandle]->telldelay = $tell_delay;
        self::$instance[$bothandle]->maxsize = $max_blobsize;
        self::$instance[$bothandle]->reconnecttime = $reconnect_time;
        self::$instance[$bothandle]->guildbot = $guildbot;
        self::$instance[$bothandle]->guildid = $guild_id;
        self::$instance[$bothandle]->guildname = $guild;
        self::$instance[$bothandle]->log = $log;
        self::$instance[$bothandle]->log_path = $logpath;
        self::$instance[$bothandle]->log_timestamp = $log_timestamp;
        self::$instance[$bothandle]->banmsgout = array();
        self::$instance[$bothandle]->use_proxy_server = $use_proxy_server;
        self::$instance[$bothandle]->proxy_server_address = explode(",", $proxy_server_address);
        self::$instance[$bothandle]->starttime = time();
        self::$instance[$bothandle]->game = AOCHAT_GAME;
        self::$instance[$bothandle]->accessallbots = $accessallbots;
        self::$instance[$bothandle]->core_directories = $core_directories;
        self::$instance[$bothandle]->module_directories = $module_directories;
        //We need to keep these too.
        if (isset($slave)) {
            self::$instance[$bothandle]->slave = $slave;
        } else {
            self::$instance[$bothandle]->slave = null;
        }		
        if (isset($owner)) {
            self::$instance[$bothandle]->owner = $owner;
        } else {
            self::$instance[$bothandle]->owner = null;
        }
        if (isset($super_admin)) {
            self::$instance[$bothandle]->super_admin = $super_admin;
        } else {
            self::$instance[$bothandle]->super_admin = null;
        }
        // create new ConfigMagik-Object (HACXX ALERT! This should most likely be a singleton!)
        self::$instance[$bothandle]->ini = ConfigMagik::get_instance(
            $bothandle,
            "Conf/" . ucfirst(strtolower($bot_name)) . ".Modules.ini",
            true,
            true
        );
        self::$instance[$bothandle]->register_module(self::$instance[$bothandle]->ini, 'ini');
        //Instantiate singletons
        self::$instance[$bothandle]->irc = & $irc; //To do: This should probably be a singleton aswell.
        self::$instance[$bothandle]->aoc = AOChat::get_instance($bothandle);
        self::$instance[$bothandle]->db = MySQL::get_instance($bothandle);
        //Pass back the handle of the bot for future reference.
        return ($bothandle);
    }


    public static function get_instance($bothandle)
    {
        if (!isset(self::$instance[$bothandle])) {
            return false;
        }
        return self::$instance[$bothandle];
    }


    private function __construct()
    {
        //Empty
    }


    function load_files($section, $directory)
    {
        if (!is_dir($directory)) {
            $this->log("LOAD", "ERROR", "The specified directory '$directory' is inaccessible!");
            return;
        }
        $bot = $this;
        $section = ucfirst(strtolower($section));
        $this->log(strtoupper($section), "LOAD", "Loading $section-modules from '$directory'");
        $folder = dir("./$directory");
        $filelist = array();
        //Create an array of files loadable.
        while ($module = $folder->read()) {
            $is_disabled = $this->ini->get($module, $section);
			if($is_disabled == "") {
				$is_disabled = $this->ini->get($module, "Custom_Modules");
			}
            if (!is_dir($module) && !preg_match("/^_/", $module) && preg_match(
                    "/\.php$/i",
                    $module
                ) && $is_disabled != "FALSE"
            ) {
                $filelist[] = $module;
            }
        }
        if (!empty($filelist)) {
            sort($filelist);
            foreach ($filelist as $file) {
                require_once("$directory/$file");
                $this->log(strtoupper($section), "LOAD", $file);
            }
        }
        echo "\n";
    }


    /*
    Connects the bot to AO's chat server
    */
    function connect()
    {
        // Make sure all cronjobs are locked, we don't want to run any cronjob before we are logged in!
        $this->cron_activated = false;
        // Get dimension server
        switch ($this->dimension) {
			case "0":
				$dimension = "Testlive";
				break;
			case "5";
				$dimension = "Rubi-Ka";
				break;
			case "6";
				$dimension = "Rubi-Ka-2019";
				break;	
			case "90";
				$dimension = "AoTestProxy";
				$this->dimension = 0;
				break;					
			case "95";
				$dimension = "AoLiveProxy";
				$this->dimension = 5;
				break;					
			case "96";
				$dimension = "AoRk19Proxy";
				$this->dimension = 6;
				break;					
			Default:
                $dimension = ucfirst(strtolower($this->dimension));
        }
        Require("Conf/ServerList.php");
        if (isset($server_list['Ao'][$dimension])) {
            $server = $server_list['Ao'][$dimension]['server'];
            $port = $server_list['Ao'][$dimension]['port'];
			$checkao = true;
        } elseif (isset($server_list['Aoc'][$dimension])) {
            $server = $server_list['Aoc'][$dimension]['server'];
            $port = $server_list['Aoc'][$dimension]['port'];
			$checkao = false;
        } else {
            die("Unknown dimension " . $this->dimension);
        }

        $this->log("LOGIN", "STATUS", "Connecting to $this->game server $server:$port");
		
		if($checkao) { // Ao authentication is simplest
			if (!$this->aoc->connect($this->server, $this->port)) {
				$this->cron_activated = false;
				$this->disconnect();
				$this->log("CONN", "ERROR", "Can't connect to server. Retrying in " . $this->reconnecttime . " seconds.");
				sleep($this->reconnecttime);
				die("The bot is restarting.\n");
			}
			// Authenticate
            $this->log("LOGIN", "STATUS", "Authenticating $this->username");
            $this->aoc->authenticate($this->username, $this->password);
            // Login the bot character
            $this->log("LOGIN", "STATUS", "Logging in $this->botname");
            $this->aoc->login(ucfirst(strtolower($this->botname)));
		}
        
        if (!$checkao) { // Aoc authentication is a bit different
			if ($this->aoc->authenticateConan($server, $port, $this->username, $this->password, $this->botname, $this->sixtyfourbit) == false) {
                $this->cron_activated = false;
                $this->disconnect();
                $this->log(
                    "CONN",
                    "ERROR",
                    "Can't connect to server. Retrying in " . $this->reconnecttime . " seconds."
                );
                sleep($this->reconnecttime);
                die("The bot is restarting.\n");
            }
        }

        /*
        We're logged in. Make sure we no longer keep username and password in memory.
        */
        unset($this->username);
        unset($this->password);
        if (strtolower($this->game) == 'aoc') {
            $dispg = true;
        } else {
            $dispg = false;
        }

        // Create the CORE settings, settings module is initialized here
        $this->core("settings")
            ->create(
                "Core",
                "RequireCommandPrefixInTells",
                false,
                "Is the command prefix (in this bot <pre>) required for commands in tells?"
            );
        $this->core("settings")
            ->create(
                "Core",
                "LogGCOutput",
                true,
                "Should the bots own output be logged when sending messages to organization chat?"
            );
        $this->core("settings")
            ->create(
                "Core",
                "LogPGOutput",
                true,
                "Should the bots own output be logged when sending messages to private groups?"
            );
        $this->core("settings")
            ->create(
                "Core",
                "SimilarCheck",
                false,
                "Should the bot try to match a similar written command if an exact match is not found? This is not recommended if you dont use a prefix!"
            );
        $this->core("settings")
            ->create(
                "Core",
                "SimilarMinimum",
                75,
                "What is the minimum percentage of similarity that has to be reached to consider two commands similar?",
                "75;80;85;90;95"
            );
        $this->core("settings")
            ->create(
                "Core",
                "CommandErrorTell",
                false,
                "Should the bot output an Access Level Error if a user tries to use a command in tells he doesn't have access to?"
            );
        $this->core("settings")
            ->create(
                "Core",
                "CommandErrorPgMsg",
                false,
                "Should the bot output an Access Level Error if a user tries to use a command in the private group he doesn't have access to?"
            );
        $this->core("settings")
            ->create(
                "Core",
                "CommandErrorGc",
                false,
                "Should the bot output an Access Level Error if a user tries to use a command in guild chat he doesn't have access to?"
            );
        $this->core("settings")
            ->create(
                "Core",
                "CommandErrorExtPgMsg",
                false,
                "Should the bot output an Access Level Error if a user tries to use a command in an external private group he doesn't have access to?"
            );
        $this->core("settings")
            ->create(
                "Core",
                "CommandDisabledError",
                false,
                "Should the bot output a Disabled Error if they try to use a command that is Disabled?"
            );
        $this->core("settings")
            ->create(
                "Core",
                "DisableGC",
                false,
                "Should the Bot output into and reactions to commands in the guildchat be disabled?"
            );
        $this->core("settings")
            ->create(
                "Core",
                "DisablePGMSG",
                $dispg,
                "Should the Bot output into and reactions to commands in it's own private group be disabled?"
            );
        $this->core("settings")
            ->create(
                "Core",
                "ColorizeTells",
                true,
                "Should tells going out be colorized on default? Notice: Modules can set a nocolor flag before sending out tells."
            );
        $this->core("settings")
            ->create("Core", "ColorizeGC", true, "Should output to guild chat be colorized using the current theme?");
        $this->core("settings")
            ->create(
                "Core",
                "ColorizePGMSG",
                true,
                "Should output to private group be colorized using the current theme?"
            );
        $this->core("settings")
            ->create(
                "Core",
                "BanReason",
                true,
                "Should the Details on the Ban be Given to user when he tries to use bot?"
            );
        $this->core("settings")
            ->create("Core", "DisableGCchat", false, "Should the Bot read none command chat in GC?");
        $this->core("settings")
            ->create(
                "Core",
                "DisablePGMSGchat",
                $dispg,
                "Should the Bot read none command chat in it's own private group?"
            );

        // Tell modules that the bot is connected
        if (!empty($this->commands["connect"])) {
            $keys = array_keys($this->commands["connect"]);
            foreach ($keys as $key) {
                if ($this->commands["connect"][$key] != null) {
                    $this->commands["connect"][$key]->connect();
                }
            }
        }
        $this->startup_time = time() + $this->crondelay;
        // Set the time of the first cronjobs
        foreach ($this->cron_times as $timestr => $value) {
            $this->cron_job_timer[$timestr] = $this->startup_time;
        }
        // and unlock all cronjobs again:
        $this->cron_activated = true;
        //Store time of connection
        $this->connected_time = time();
    }


    /*
    Reconnect the bot.
    */
    function reconnect()
    {
        $this->cron_activated = false;
        $this->disconnect();
        $this->log("CONN", "ERROR", "Bot has disconnected. Reconnecting in " . $this->reconnecttime . " seconds.");
        sleep($this->reconnecttime);
        die("The bot is restarting.\n");
    }


    /*
    Dissconnect the bot
    */
    function disconnect()
    {
        $this->aoc->disconnect();
        if (!empty($this->commands["disconnect"])) {
            $keys = array_keys($this->commands["disconnect"]);
            foreach ($keys as $key) {
                if ($this->commands["disconnect"][$key] != null) {
                    $this->commands["disconnect"][$key]->disconnect();
                }
            }
        }
    }


    function replace_string_tags($msg)
    {
        $msg = str_replace("<botname>", $this->botname, $msg);
        $msg = str_replace("<guildname>", $this->guildname, $msg);
        $msg = str_replace("<pre>", str_replace("\\", "", $this->commpre), $msg);
        return $msg;
    }


    /*
    sends a tell asking user to use "help"
    */
    function send_help($to, $command = false)
    {
        if ($command == false) {
            $this->send_tell($to, "/tell <botname> <pre>help");
        } else {
            $this->send_tell(
                $to,
                $this->core("help")
                    ->show_help($to, $command)
            );
        }
    }


    /*
    sends a message over IRC if it's enabled and connected
    */
    function send_irc($prefix, $name, $msg)
    {
        //		if (isset($this -> irc) && $this -> exists_module("irc"))
        if ($this->exists_module("irc")) {
            if ($this->core("settings")->get("Irc", "Connected")) {
                // Parse the color codes and let the IRC module deal with filtering.
                $msg = $this->core("colors")->parse($msg);
                $this->core("irc")->send_irc($prefix, $name, $msg);
            }
        }
    }


    /*
    Notifies someone that they are banned, but only once.
    */
    function send_ban($to, $msg = false)
    {
        if (!isset($this->banmsgout[$to]) || $this->banmsgout[$to] < (time() - 60 * 5)) {
            $this->banmsgout[$to] = time();
            if ($msg === false) {
                if ($this->core("settings")->get("Core", "BanReason")) {
                    $why = $this->db->select(
                        "SELECT banned_by, banned_for, banned_until FROM #___users WHERE nickname = '" . $to . "'"
                    );
                    if ($why[0][2] > 0) {
                        $until = "Temporary ban until " . gmdate(
                                $this
                                    ->core("settings")
                                    ->get("Time", "FormatString"),
                                $why[0][2]
                            );
                    } else {
                        $until = "Permanent ban.";
                    }
                    $why = " by ##highlight##" . $why[0][0] . "##end## for Reason: ##highlight##" . $why[0][1] . "##end##\n" . $until;
                } else {
                    $why = ".";
                }
                $this->send_tell($to, "You are banned from <botname>" . $why);
            } else {
                $this->send_tell($to, $msg);
            }
        } else {
            return false;
        }
    }


    /*
    Sends a permission denied error to user for the given command.
    */
    function send_permission_denied($to, $command, $type = 0)
    {
        $string = "You do not have permission to access $command";
        if ($type = 0) {
            return $string;
        } else {
            $this->send_output($to, $string, $type);
        }
    }


    /*
    send a tell. Set $low to 1 on tells that are likely to cause spam.
    */
    function send_tell(
        $to,
        $msg,
        $low = 0,
        $color = true,
        $sizecheck = true,
        $parsecolors = true
    ) {
        // parse all color tags:
        if ($parsecolors) {
            $msg = $this->core("colors")->parse($msg);
        }
        $send = true;
        if ($sizecheck) {
            if (strlen($msg) < 100000) {
                if (preg_match("/<a href=\"(.+)\">/isU", $msg, $info)) {
                    if (strlen($info[1]) > $this->maxsize) {
                        $this->cut_size($msg, "tell", $to, $low);
                        $send = false;
                    }
                }
            } else {
                $info = explode('<a href="', $msg, 2);
                if (count($info) > 1) {
                    if (strlen($msg) > $this->maxsize) {
                        $this->cut_size($msg, "tell", $to, $low);
                        $send = false;
                    }
                }
            }
        }
        if ($send) {
            $msg = $this->replace_string_tags($msg);
            if ($color && $this->core("settings")->get("Core", "ColorizeTells")
            ) {
                $msg = $this->core("colors")->colorize("normal", $msg);
            }
            if ($this->core("chat_queue")->check_queue()) {
                if (is_numeric($to)) {
                    $to_name = $this->core('player')->name($to);
                } else {
                    $to_name = $to;
                }
                $this->log("TELL", "OUT", "-> " . $to_name . ": " . $msg);
                $msg = utf8_encode($msg);
                $this->aoc->send_tell($to, $msg);
            } else {
                $this->core("chat_queue")->into_queue($to, $msg, "tell", $low);
            }
        }
    }


    /*
    send a message to privategroup
    */
    function send_pgroup(
        $msg,
        $group = null,
        $checksize = true,
        $parsecolors = true
    ) {
        // Never send any privategroup message in AoC, because this would disconnect the bot
        if (strtolower($this->game) == 'aoc') {
            /*** FIXME ***/
            // We need to eradicate calls to this from all modules for sanitys sake.
            return false;
        }
        if ($group == null) {
            $group = $this->botname;
        }
        if ($group == $this->botname
            && $this->core("settings")
                ->get("Core", "DisablePGMSG")
        ) {
            return false;
        }
        // parse all color tags:
        if ($parsecolors) {
            $msg = $this->core("colors")->parse($msg);
        }
        $gid = $this->core("player")->id($group);
        $send = true;
        if ($checksize) {
            if (preg_match("/<a href=\"(.+)\">/isU", $msg, $info)) {
                if (strlen($info[1]) > $this->maxsize) {
                    $this->cut_size($msg, "pgroup", $group);
                    $send = false;
                }
            }
        }
        if ($send) {
            $msg = $this->replace_string_tags($msg);
            $msg = utf8_encode($msg);
            if (strtolower($group) == strtolower($this->botname)) {
                if ($this->core("settings")->get("Core", "ColorizePGMSG")) {
                    $msg = $this->core("colors")->colorize("normal", $msg);
                }
                $this->aoc->send_privgroup($gid, $msg);
            } else {
                $this->aoc->send_privgroup($gid, $msg);
            }
        }
    }


    /*
    * Send a message to guild channel
    */
    function send_gc($msg, $low = 0, $checksize = true)
    {
        if ($this->core("settings")->get("Core", "DisableGC")) {
            Return false;
        }
        // parse all color tags:
        $msg = $this->core("colors")->parse($msg);
        $send = true;
        if ($checksize) {
            if (preg_match("/<a href=\"(.+)\">/isU", $msg, $info)) {
                if (strlen($info[1]) > $this->maxsize) {
                    $this->cut_size($msg, "gc", "", $low);
                    $send = false;
                }
            }
        }
        if ($send) {
            $msg = $this->replace_string_tags($msg);
            if ($this->core("settings")->get("Core", "ColorizeGC")) {
                $msg = $this->core("colors")->colorize("normal", $msg);
            }
            if (strtolower($this->game) == 'ao') {
                $guild = $this->guildname;
            } else {
                $guild = "~Guild";
            }
            if ($this->core("chat_queue")->check_queue()) {
                $msg = utf8_encode($msg);
                $this->aoc->send_group($guild, $msg);
            } else {
                $this->core("chat_queue")->into_queue($guild, $msg, "gc", $low);
            }
        }
    }


    function send_output($source, $msg, $type, $low = 0)
    {
        // Parse color tags now to be sure they don't get changed by output filters
        $msg = $this->core("colors")->parse($msg);
        // Output filter
        if ($this->core("settings")->exists('Filter', 'Enabled')) {
            if ($this->core("settings")->get('Filter', 'Enabled')) {
                $msg = $this->core("stringfilter")->output_filter($msg);
            }
        }
        if (!is_numeric($type)) {
            $type = strtolower($type);
        }
        switch ($type) {
            case '0':
            case '1':
            case 'tell':
                $this->send_tell($source, $msg, $low);
                break;
            case '2':
            case 'pgroup':
            case 'pgmsg':
                $this->send_pgroup($msg);
                break;
            case '3':
            case 'gc':
                $this->send_gc($msg, $low);
                break;
            case '4':
            case 'both':
                $this->send_gc($msg, $low);
                $this->send_pgroup($msg);
                break;
            default:
                $this->log(
                    "OUTPUT",
                    "ERROR",
                    "Broken plugin, type: $type is unknown to me; source: $source, message: $msg"
                );
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
        if (isset($this->commands["tell"][$cmd]) || isset($this->commands["gc"][$cmd]) || isset($this->commands["pgmsg"][$cmd]) || isset($this->commands["extpgmsg"][$cmd])) {
            return $use;
        }
        $perc = $this->core("settings")->get("Core", "SimilarMinimum");
        foreach ($this->commands[$channel] as $compare_cmd => $value) {
            similar_text($cmd, $compare_cmd, $percentage);
            if ($percentage >= $perc && $percentage > $use[0]) {
                $use = array(
                    $percentage,
                    $compare_cmd
                );
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
        if ($this->commands[$channel][$command] != null) {
            if ($this->core("access_control")
                ->check_rights($user, $command, $msg, $channel)
            ) {
                if ($channel == "extpgmsg") {
                    $this->commands[$channel][$command]->$channel($pgname, $user, $msg);
                } else {
                    $this->commands[$channel][$command]->$channel($user, $msg);
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

    This should be reworked to do things in the following manner
    *) Determine the access level of the person sending the message.
    *) If we can rule out that the message is not a command we go to the next step which should be relaying
    *) strip the prefix
    *) search the command library for a match and execute if found
    *) search the command library for a similar command, notify user about the typo and execute if found

    */
    function handle_command_input($user, $msg, $channel, $pgname = null)
    {
        $match = false;
        $this->command_error_text = false;
        if (!empty($this->commands[$channel])) {
            if ($this->core("security")->is_banned($user)) {
                $this->send_ban($user);
                return true;
            }
            $stripped_prefix = str_replace("\\", "", $this->commpre);
            // Add missing command prefix in tells if the settings allow for it:
            if ($channel == "tell"
                && !$this->core("settings")
                    ->get("Core", "RequireCommandPrefixInTells")
                && $this->commpre != ""
                && $msg[0] != $stripped_prefix
            ) {
                $msg = $stripped_prefix . $msg;
            }
            // Only if first character is the command prefix is any check for a command needed,
            // or if no command prefix is used at all:
            if ($this->commpre == "" || $msg[0] == $stripped_prefix) {
                // Strip command prefix if it is set - we already checked that the input started with it:
                if ($this->commpre != "") {
                    $msg = substr($msg, 1);
                }
                // Check if Command is an Alias of another Command
                $msg = $this->core("command_alias")->replace($msg);
                $cmd = explode(" ", $msg, 3);
                $cmd[0] = strtolower($cmd[0]);
                $msg = implode(" ", $cmd);
                if (isset($this->commands[$channel][$cmd[0]])) {
                    $match = true;
                    if ($this->check_access_and_execute($user, $cmd[0], $msg, $channel, $pgname)) {
                        return true;
                    }
                } elseif ($this->core("settings")->get("Core", "SimilarCheck")) {
                    $use = $this->find_similar_command($channel, $cmd[0]);
                    if ($use[0] > 0) {
                        $cmd[0] = $use[1];
                        $msg = explode(" ", $msg, 2);
                        $msg[0] = $use[1];
                        $msg = implode(" ", $msg);
                        if (isset($this->commands[$channel][$use[1]])) {
                            $match = true;
                            if ($this->check_access_and_execute($user, $use[1], $msg, $channel, $pgname)) {
                                return true;
                            }
                        }
                    }
                }
                if ($this->core("settings")
                        ->get("Core", "CommandError" . $channel)
                    && $match
                ) {
                    $minlevel = $this->core("access_control")
                        ->get_min_rights($cmd[0], $msg, $channel);
                    if ($minlevel == OWNER + 1) {
                        $minstr = "DISABLED";
                    } else {
                        $minstr = $this->core("security")
                            ->get_access_name($minlevel);
                    }
                    $req = array(
                        "Command",
                        $msg,
                        $minstr
                    );
                    if ($req[2] == "DISABLED") {
                        if ($this->core("settings")
                            ->get("Core", "CommandDisabledError")
                        ) {
                            $this->command_error_text
                                = "You're not authorized to use this " . $req[0] . ": ##highlight##" . $req[1] . "##end##, it is Currently ##highlight##DISABLED##end##";
                        }
                    } else {
                        $this->command_error_text
                            = "You're not authorized to use this " . $req[0] . ": ##highlight##" . $req[1] . "##end##, Your Access Level is required to be at least ##highlight##"
                            . $req[2] . "##end##";
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
    function hand_to_chat($found, $user, $msg, $channel, $group = null)
    {
        if ($found) {
            return true;
        }
        if ($channel == "gmsg") {
            if ($group == $this->guildname || (strtolower($this->game) == 'aoc' && $group == "~Guild")) {
                $group = "org";
            }
            if (isset($this->commands[$channel][$group])) $registered = $this->commands[$channel][$group];
        } else {
            if (isset($this->commands[$channel])) $registered = $this->commands[$channel];
        }
        if (!empty($registered)) {
            $keys = array_keys($registered);
            foreach ($keys as $key) {
                if ($channel == "extprivgroup") {
                    if ($this->commands[$channel][$key] != null) {
                        $found = $found | $this->commands[$channel][$key]->$channel($group, $user, $msg);
                    }
                } else {
                    if ($channel == "gmsg") {
                        if ($this->commands[$channel][$group][$key] != null) {
                            $found = $found | $this->commands[$channel][$group][$key]->$channel($user, $group, $msg);
                        }
                    } else {
                        if ($this->commands[$channel][$key] != null) {
                            $found = $found | $this->commands[$channel][$key]->$channel($user, $msg);
                        }
                    }
                }
            }
        }
        return $found;
    }


    function incoming_chat($message)
    {
    }


    /*
    Incoming Tell
    */
    function inc_tell($args)
    {
        //Get the name of the user. It's easier to handle... or is it?
        $user = $this->core("player")->name($args[0]);
        $found = false;
        // Ignore bot chat, no need to handle it's own output as input again
        if ($user == 'BOTNAME') {
            // Danger will robinson. We just sent a tell to ourselves!!!!!!!!!
            $this->log("CORE", "INC_TELL", "Danger will robinson. Received tell from myself: $args[1]");
            return;
        }
        //Silently ignore tells from other bots.
        if (isset($this->other_bots[$user])) //TO DO: Do we ever ucfirst(strtolower()) the other bots?
        {
            return;
        }
        if (preg_match("/is AFK .Away from keyboard./i", $args[1]) || preg_match(
                "/.tell (.+)help/i",
                $args[1]
            ) || preg_match("/I only listen to members of this bot/i", $args[1])
            || preg_match("/I am away from my keyboard right now,(.+)your message has been logged./i", $args[1])
            || preg_match("/Away From Keyboard/i", $args[1])
        ) {
            //We probably sendt someone a tell when not here. Let's leave it at that.
            return;
        }
        $args[1] = utf8_decode($args[1]);
        $this->log("TELL", "INC", $user . ": " . $args[1]);
        $found = $this->handle_command_input($user, $args[1], "tell");
        $found = $this->hand_to_chat($found, $user, $args[1], "tells");
        if (isset($this->command_error_text)&&$this->command_error_text!="") {
            $this->send_tell($args[0], $this->command_error_text);
        } elseif (!$found
            && $this->core("security")
                ->check_access($user, "GUEST")
        ) {
            $this->send_help($args[0]);
        } else {
            if (!$found) {
                if (isset($this->guild_bot)&&$this->guild_bot) {
                    $this->send_tell($args[0], "I only listen to members of " . $this->guildname . ".");
                } else {
                    $this->send_tell($args[0], "I only listen to members of this bot.");
                }
            }
        }
        unset($this->command_error_text);
    }


    /*
    Someone joined privategroup
    */
    function inc_pgjoin($args)
    {
        $pgname = $this->core("player")->name($args[0]);
        if (empty($pgname) || $pgname == "") {
            $pgname = $this->botname;
        }
        $user = $this->core("player")->name($args[1]);
        if (strtolower($pgname) == strtolower($this->botname)) {
            $this->log("PGRP", "JOIN", $user . " joined privategroup.");
            if (!empty($this->commands["pgjoin"])) {
                $keys = array_keys($this->commands["pgjoin"]);
                foreach ($keys as $key) {
                    if ($this->commands["pgjoin"][$key] != null) {
                        $this->commands["pgjoin"][$key]->pgjoin($user);
                    }
                }
            }
        } else {
            $this->log("PGRP", "JOIN", $user . " joined the exterior privategroup of " . $pgname . ".");
            if (!empty($this->commands["extpgjoin"])) {
                $keys = array_keys($this->commands["extpgjoin"]);
                foreach ($keys as $key) {
                    if ($this->commands["extpgjoin"][$key] != null) {
                        $this->commands["extpgjoin"][$key]->extpgjoin($pgname, $user);
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
        $pgname = $this->core("player")->name($args[0]);
        if (empty($pgname) || $pgname == "") {
            $pgname = $this->botname;
        }
        $user = $this->core("player")->name($args[1]);
        if (strtolower($pgname) == strtolower($this->botname)) {
            $this->log("PGRP", "LEAVE", $user . " left privategroup.");
            if (!empty($this->commands["pgleave"])) {
                $keys = array_keys($this->commands["pgleave"]);
                foreach ($keys as $key) {
                    if ($this->commands["pgleave"][$key] != null) {
                        $this->commands["pgleave"][$key]->pgleave($user);
                    }
                }
            }
        } else {
            $this->log("PGRP", "LEAVE", $user . " left the exterior privategroup " . $pgname . ".");
            if (!empty($this->commands["extpgleave"])) {
                $keys = array_keys($this->commands["extpgleave"]);
                foreach ($keys as $key) {
                    if ($this->commands["extpgleave"][$key] != null) {
                        $this->commands["extpgleave"][$key]->extpgleave($pgname, $user);
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
        $pgname = $this->core("player")->name($args[0]);
        $user = $this->core("player")->name($args[1]);
        $found = false;
        if (empty($pgname) || $pgname == "") {
            $pgname = $this->botname;
        }

        $dispgmsg = $this->core("settings")->get("Core", "DisablePGMSG");
        $dispgmsgchat = $this->core("settings")
            ->get("Core", "DisablePGMSGchat");
        if ($pgname == $this->botname && $dispgmsg && $dispgmsgchat) {
            return false;
        }
        $args[2] = utf8_decode($args[2]);
        // Ignore bot chat, no need to handle it's own output as input again
        if (strtolower($this->botname) == strtolower($user)) {
            if ($this->core("settings")->get("Core", "LogPGOutput")) {
                $this->log(
                    "PGRP",
                    "MSG",
                    "[" . $this->core("player")
                        ->name($args[0]) . "] " . $user . ": " . $args[2]
                );
            }
            return;
        } else {
            $this->log(
                "PGRP",
                "MSG",
                "[" . $this->core("player")
                    ->name($args[0]) . "] " . $user . ": " . $args[2]
            );
        }
        if (!isset($this->other_bots[$user])) {
            if (strtolower($pgname) == strtolower($this->botname)) {
                if (!$dispgmsg) {
                    $found = $this->handle_command_input($user, $args[2], "pgmsg");
                }
                if (!$dispgmsgchat) {
                    $found = $this->hand_to_chat($found, $user, $args[2], "privgroup");
                }
            } else {
                $found = $this->handle_command_input($user, $args[2], "extpgmsg", $pgname);
                $found = $this->hand_to_chat($found, $user, $args[2], "extprivgroup", $pgname);
            }
            if ($this->command_error_text) {
                $this->send_pgroup($this->command_error_text, $pgname);
            }
            unset($this->command_error_text);
        }
    }


    /*
    Incoming group announce
    */
    function inc_gannounce($args)
    {
        if ($args[2] == 32772 && strtolower($this->game) == 'ao') {
            $this->guildname = $args[1];
            $this->log("CORE", "INC_GANNOUNCE", "Detected org name as: $args[1]");
        }
    }


    /*
    * Incoming private group invite
    */
    function inc_pginvite($args)
    {
        $group = $this->core("player")->name($args[0]);
        if (!empty($this->commands["pginvite"])) {
            $keys = array_keys($this->commands["pginvite"]);
            foreach ($keys as $key) {
                if ($this->commands["pginvite"][$key] != null) {
                    $this->commands["pginvite"][$key]->pginvite($group);
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
        $group = $this->core("chat")->lookup_group($args[0]);
        if (!$group) {
            $group = $this->core("chat")->get_gname($args[0]);
        }
        $args[2] = utf8_decode($args[2]);
        if (isset($this->commands["gmsg"][$group]) || $group == $this->guildname || (strtolower(
                    $this->game
                ) == 'aoc' && $group == "~Guild")
        ) {
            if (strtolower($this->game) == 'aoc' && $group == "~Guild") {
                $msg = "[" . $this->guildname . "] ";
            } else {
                $msg = "[" . $group . "] ";
            }
            if ($args[1] != 0) {
                $msg .= $this->core("player")->name($args[1]) . ": ";
            }
            $msg .= $args[2];
        } else {
            // If we dont have a hook active for the group, and its not guildchat... BAIL now before wasting cycles
            return false;
        }
        $disgc = $this->core("settings")->get("Core", "DisableGC");
        $disgcchat = $this->core("settings")->get("Core", "DisableGCchat");
        if (($group == $this->guildname || (strtolower(
                        $this->game
                    ) == 'aoc' && $group == "~Guild")) && $disgc && $disgcchat
        ) {
            Return false;
        }
        if ($args[1] == 0) {
            $user = "0";
        } else {
            $user = $this->core("player")->name($args[1]);
        }
        // Ignore bot chat, no need to handle it's own output as input again
        if (strtolower($this->botname) == strtolower($user)) {
            if ($this->core("settings")->get("Core", "LogGCOutput")) {
                $this->log("GROUP", "MSG", $msg);
            }
            return;
        } else {
            $this->log("GROUP", "MSG", $msg);
        }
        if (!isset($this->other_bots[$user])) {
            if ($group == $this->guildname || (strtolower($this->game) == 'aoc' && $group == "~Guild")) {
                if (!$disgc) {
                    $found = $this->handle_command_input($user, $args[2], "gc");
                }

                if ($this->command_error_text) {
                    $this->send_gc($this->command_error_text);
                }
                unset($this->command_error_text);
            }

            if (!$disgcchat) {
                $found = $this->hand_to_chat($found, $user, $args[2], "gmsg", $group);
            }
        }
    }


    /*
    Does all the checks and work for a specific cron time
    */
    function cronjob($time, $duration)
    {
        if (($this->cron_job_timer[$duration] <= $time) && ($this->cron_job_active[$duration] == false)) {
            if (!empty($this->cron[$duration])) {
                $this->cron_job_active[$duration] = true;
                $crons = array_keys($this->cron[$duration]);
                for ($i = 0; $i < count($crons); $i++) {
                    if ($this->cron[$duration][$crons[$i]] != null) {
                        $this->cron[$duration][$crons[$i]]->cron($duration);
                    }
                }
            }
            $this->cron_job_active[$duration] = false;
            $this->cron_job_timer[$duration] = time() + $duration;
        }
    }


    /*
    CronJobs of the bot
    */
    function cron()
    {
        if (!$this->cron_activated) {
            return;
        }
        $time = time();
        // Check timers:
        $this->core("timer")->check_timers();
        if (empty($this->cron)) {
            return;
        }
        foreach ($this->cron_times as $interval) {
            $this->cronjob($time, $interval);
        }
    }


    /*
    Writes events to the console and log if logging is turned on.
    */
    function log($first, $second, $msg, $write_to_db = false)
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
        // Change Encrypted Text to a Simple thing to say its encripted
        $msg = preg_replace('/gcr &\$enc\$& ([a-z0-9]+) ([a-z0-9]+) ([a-z0-9]+) /U', "gcr <Encryted Message>", $msg);
        $msg = preg_replace('/gcr &\$enc\$& ([a-z0-9]+) ([a-z0-9]+) ([a-z0-9]+)/', "gcr <Encryted Message>", $msg);
        $msg = $this->replace_string_tags($msg);
        if ($this->log_timestamp == 'date') {
            $timestamp = "[" . gmdate("Y-m-d") . "]\t";
        } elseif ($this->log_timestamp == 'time') {
            $timestamp = "[" . gmdate("H:i:s") . "]\t";
        } elseif ($this->log_timestamp == 'none') {
            $timestamp = "";
        } else {
            $timestamp = "[" . gmdate("Y-m-d H:i:s") . "]\t";
        }
        $line = $timestamp . "[" . $first . "]\t[" . $second . "]\t" . $msg . "\n";
        echo $this->botname . " " . $line;
        // We have a possible security related event.
        // Log to the security log and notify guildchat/pgroup.
        if (preg_match("/^security$/i", $second)) {
            if ($this->guildbot) {
                $this->send_gc($line);
            } else {
                $this->send_pgroup($line);
            }
            $log = fopen($this->log_path . "/security.txt", "a");
            fputs($log, $line);
            fclose($log);
        }
        if (($this->log == "all") || (($this->log == "chat") && (($first == "GROUP") || ($first == "TELL") || ($first == "PGRP")))) {
            $log = fopen($this->log_path . "/" . gmdate("Y-m-d") . ".txt", "a");
            fputs($log, $line);
            fclose($log);
        }
        if ($write_to_db) {
            $logmsg = substr($msg, 0, 500);
            $this->db->query(
                "INSERT INTO #___log_message (message, first, second, timestamp) VALUES ('" . $this->db->real_escape_string(
                    $logmsg
                ) . "','" . $first . "','" . $second . "','" . time()
                . "')"
            );
        }
    }


    /*
    Cut msg into Size Small enough to Send
    */
    function cut_size($msg, $type, $to = "", $pri = 0)
    {
        if (strlen($msg) < 100000) {
            preg_match("/^(.*)<a href=\"(.+)\">(.*)$/isU", $msg, $info);
        } else {
            $var = explode("<a href=\"", $msg, 2);
            $var2 = explode("\">", $var[1], 2);
            $info[1] = $var[0];
            $info[2] = $var2[0];
            $info[3] = $var2[1];
        }		
        $info[2] = str_replace("<br>", "\n", $info[2]);
        $content = explode("\n", $info[2]);
        $page = 0;
        $result[$page] = "";
        foreach ($content as $line) {
            if ((strlen($result[$page]) + strlen($line) + 12) < $this->maxsize) {
                $result[$page] .= $line . "\n";
            } else {
				$line = preg_replace('/<\/([^>]+)> /','</$1>#@?-_#', $line);
				$words = explode("#@?-_#", $line);
				$wcnt = count($words);
				$wrow = 0;
				foreach ($words as $word) {
					if ((strlen($result[$page]) + strlen($word) + 12) < $this->maxsize) {
						if($wrow+1!=$wcnt) $result[$page] .= $word . " ";
						elseif($wrow+1==$wcnt) $result[$page] .= $word . "\n";
					} else {				
						$page++;
						if($wrow+1!=$wcnt) $result[$page] = $word . " ";
						elseif($wrow+1==$wcnt) $result[$page] = $word . "\n";
					}
					$wrow++;
				}
            }
        }
        $between = "";
        for ($i = 0; $i <= $page; $i++) {
            if ($i != 0) {
                $between = "text://";
            }
			$msg = $info[1] . "<a href=\"" . $between . $result[$i] . "\">" . $info[3] . " <font color=#ffffff>(page " . ($i + 1) . " of " . ($page + 1) . ")</font>";
            if ($type == "tell") {
                $this->send_tell($to, $msg, $pri, true, false);
            } else {
                if ($type == "pgroup") {
                    $this->send_pgroup($msg, $to, false);
                } else {
                    if ($type == "gc") {
                        $this->send_gc($msg, $pri, false);
                    }
                }
            }
        }
    }


    // Registers a new reference to a module, used to access the new module by other modules.
    public function register_module(&$ref, $name)
    {
        if (isset($this->module_links[strtolower($name)])) {
            $this->log(
                'CORE',
                'ERROR',
                "Module '$name' has Already Been Registered by " . get_class(
                    $this->module_links[strtolower($name)]
                ) . " so cannot be registered by " . get_class($ref) . "."
            );
            return;
        }
        $this->module_links[strtolower($name)] = & $ref;
    }


    // Unregisters a module link.
    public function unregister_module($name)
    {
        $this->module_links[strtolower($name)] = null;
        unset($this->module_links[strtolower($name)]);
    }


    public function exists_module($name)
    {
        $name = strtolower($name);
        Return (isset($this->module_links[$name]));
    }


    // Returns the reference to the module registered under $name. Returns NULL if link is not registered.
    public function core($name)
    {
        if (isset($this->module_links[strtolower($name)])) {
            return $this->module_links[strtolower($name)];
        }
        $dummy = new BasePassiveModule($this, $name);
        $this->log('CORE', 'ERROR', "Module '$name' does not exist or is not loaded.");
        return $dummy;
    }


    /*
    * Interface to register and unregister commands
    */
    public function register_command($channel, $command, &$module)
    {
        $channel = strtolower($channel);
        $command = strtolower($command);
        $allchannels = array(
            "gc",
            "tell",
            "pgmsg"
        );
        if ($channel == "all") {
            foreach ($allchannels as $cnl) {
                $this->commands[$cnl][$command] = & $module;
            }
        } else {
            $this->commands[$channel][$command] = & $module;
        }
    }


    public function unregister_command($channel, $command)
    {
        $channel = strtolower($channel);
        $command = strtolower($command);
        $allchannels = array(
            "gc",
            "tell",
            "pgmsg"
        );
        if ($channel == "all") {
            foreach ($allchannels as $cnl) {
                $this->commands[$cnl][$command] = null;
                unset($this->commands[$cnl][$command]);
            }
        } else {
            $this->commands[$channel][$command] = null;
            unset($this->commands[$channel][$command]);
        }
    }


    public function exists_command($channel, $command)
    {
        $channel = strtolower($channel);
        $command = strtolower($command);
        $exists = false;
        $allchannels = array(
            "gc",
            "tell",
            "pgmsg"
        );
        if ($channel == "all") {
            foreach ($allchannels as $cnl) {
                $exists = $exists & isset($this->commands[$cnl][$command]);
            }
        } else {
            $exists = isset($this->commands[$channel][$command]);
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
        $allchannels = array(
            "gc",
            "tell",
            "pgmsg"
        );
        if ($channel == "all") {
            $handlers = array();
            foreach ($allchannels as $cnl) {
                $handlers[] = get_class($this->commands[$cnl][$command]);
            }
            $handler = implode(", ", $handles);
        } else {
            $handler = get_class($this->commands[$channel][$command]);
        }
        return $handler;
    }


    /*
    * Interface to register and unregister commands
    */
    public function register_event($event, $target, &$module)
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
        if (in_array($event, $events)) {
            if ($event == 'gmsg') {
                if ($target) {
                    $this->commands[$event][$target][get_class($module)] = & $module;
                    return false;
                } else {
                    return "No channel specified for gmsg. Not registering.";
                }
            } elseif ($event == 'cron') {
                $time = strtotime($target, 0);
                if ($time > 0) {
                    if (!isset($this->cron_job_active[$time])) {
                        $this->cron_job_active[$time] = false;
                    }
                    if (!isset($this->cron_job_timer[$time])) {
                        $this->cron_job_timer[$time] = max(time(), $this->startup_time);
                    }
                    $this->cron_times[$time] = $time;
                    $this->cron[$time][get_class($module)] = & $module;
                    return false;
                } else {
                    return "Cron time '$target' is invalid. Not registering.";
                }
            } elseif ($event == 'timer') {
                if ($target) {
                    $this->core("timer")->register_callback($target, $module);
                    return false;
                } else {
                    return "No name for the timer callback given! Not registering.";
                }
            } elseif ($event == 'logon_notify') {
                $this->core("logon_notifies")->register($module);
                return false;
            } elseif ($event == 'settings') {
                if (is_array($target) && isset($target['module']) && isset($target['setting'])) {
                    return $this->core("settings")
                        ->register_callback($target['module'], $target['setting'], $module);
                }
                return "No module and/or setting defined, can't register!";
            } elseif ($event == 'irc') {
                $this->core("irc")->ircmsg[] = & $module;
                return false;
            } else {
                $this->commands[$event][get_class($module)] = & $module;
                return false;
            }
        } else {
            return "Event '$event' is invalid. Not registering.";
        }
    }


    public function unregister_event($event, $target, &$module)
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
        if (in_array($event, $events)) {
            if ($event == 'gmsg') {
                if (isset($this->commands[$event][$target][get_class($module)])) {
                    $this->commands[$event][$target][get_class($module)] = null;
                    unset($this->commands[$event][$target][get_class($module)]);
                    return false;
                } else {
                    return "GMSG $target is not registered or invalid!";
                }
            } elseif ($event == 'cron') {
                $time = strtotime($target, 0);
                if (isset($this->cron[$time][get_class($module)])) {
                    $this->cron[$time][get_class($module)] = null;
                    unset($this->cron[$time][get_class($module)]);
                    return false;
                } else {
                    return "Cron time '$target' is not registered or invalid!";
                }
            } elseif ($event == 'timer') {
                return $this->core("timer")->unregister_callback($target);
            } elseif ($event == 'logon_notify') {
                $this->core("logon_notifies")->unregister($module);
                return false;
            } elseif ($event == 'settings') {
                if (is_array($target) && isset($target['module']) && isset($target['setting'])) {
                    return $this->core("settings")
                        ->unregister_callback($target['module'], $target['setting'], $module);
                }
                return "No module and/or setting defined, can't unregister!";
            } else {
                $this->commands[$event][get_class($module)] = null;
                unset($this->commands[$event][get_class($module)]);
                return false;
            }
        } else {
            return "Event '$event' is invalid. Not registering.";
        }
    }


    function debug_bt()
    {
        $trace = debug_backtrace();
        $r = '';
		// decomment below for detailed debugging
        /*foreach ($trace as $i => $call) {
            if (is_object($call['object'])) {
                $call['object'] = 'CONVERTED OBJECT OF CLASS ' . get_class($call['object']);
            }

            if (is_array($call['args'])) {
                foreach ($call['args'] AS &$arg) {
                    if (is_object($arg)) {
                        $arg = 'CONVERTED OBJECT OF CLASS ' . get_class($arg);
                    }
                }
            }

            $r .= "#" . $i . " " . (isset($call['file']) ? $call['file'] : '') . '(' . (isset($call['line']) ? $call['line'] : '') . ') ';
            $r .= (!empty($call['object']) ? $call['object'] . $call['type'] : '');
            $r .= $call['function'] . '(' . implode(', ', $call['args']) . ')';
            $r .= "\n";
        }*/

        return $r;
    }

}

?>
