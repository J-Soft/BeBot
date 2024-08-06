<?php
/*
* IRC.php - IRC Relay.
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
* - Bitnykk (RK5)
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
Add a "_" at the beginning of the file (_IRC.php) if you do not want it to be loaded.
*/
include_once('Sources/SmartIRC/SmartIRC.php');
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
	var $note = "Irc side commands available are : help, tara, viza, is, online/sm, whois, alts, level/lvl/pvp, bots/bot/up";
	var $spam, $irconline, $ircmsg;

    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_module("irc");
        $this->register_command("all", "irc", "SUPERADMIN");
        $this->register_command("all", "irconline", "GUEST");
		$this -> register_alias("irconline", "irco");
        $this->register_event("buddy");
        $this->register_event("connect");
        $this->register_event("disconnect");
        $this->register_event("privgroup");
        $this->register_event("gmsg", "org");
        $this->help['description'] = "Handles the IRC relay of the bot.";
        $this->help['command']['irconline'] = "Shows users in the IRC Channel.";
        $this->help['command']['irc connect'] = "Tries to connect to the IRC channel.";
        $this->help['command']['irc disconnect'] = "Disconnects from the IRC server.";
        $this->help['notes'] = "The IRC relay is configured via settings, for all options check /tell <botname> <pre>settings IRC. ".$this->note;
        // Create default settings:
        if ($this->bot->guildbot) {
            $guildprefix = "[" . $this->bot->guildname . "]";
            $pgroupprefix = "[" . $this->bot->guildname . "'s Guestchannel]";
            $chatgroups = "gc";
            $announcewhat = "buddies";
        } else {
            $guildprefix = "[]";
            $pgroupprefix = "[" . $this->bot->botname . "]";
            $chatgroups = "pgroup";
            $announcewhat = "joins";
        }
        $this->bot->core("settings")
            ->create("IRC", "IncLog", false, "Should the bot be logging IRC Incoming messages ?", "On;Off");
        $this->bot->core("settings")
            ->create("IRC", "OutLog", false, "Should the bot be logging IRC Outgoing messages ?", "On;Off");		
        $this->bot->core("settings")
            ->create("IRC", "Connected", false, "Is the bot connected to the IRC server?", "On;Off", true);
        $this->bot->core("settings")->save("irc", "connected", false);
        $this->bot->core("settings")
            ->create("Irc", "Server", "", "Which IRC server is used?");
        $this->bot->core("settings")
            ->create("Irc", "OAuthTok", "", "What OAuth Token, if needed to connect (otherwise leave empty)?");
        $this->bot->core("settings")
            ->create("Irc", "Port", "6667", "Which port is used to connect to the IRC server?");
        $this->bot->core("settings")
            ->create("Irc", "Channel", "#" . $this->bot->botname, "Which IRC channel should be used?");
        $this->bot->core("settings")
            ->create("Irc", "ChannelKey", "", "What is the IRC channel key if any?");
        $this->bot->core("settings")
            ->create("Irc", "Nick", $this->bot->botname, "Which nick should the bot use in IRC?");
        $this->bot->core("settings")
            ->create(
                "Irc",
                "IrcGuildPrefix",
                $guildprefix,
                "Which prefix should ingame guild chat relayed to IRC get?"
            );
        $this->bot->core("settings")
            ->create(
                "Irc",
                "IrcGuestPrefix",
                $pgroupprefix,
                "Which prefix should ingame chat in the chat group of the bot relayed to IRC get?"
            );
        $this->bot->core("settings")
            ->create("Irc", "GuildPrefix", "[IRC]", "Which prefix should IRC chat relayed to ingame chat get?");
        $this->bot->core("settings")
            ->create("Irc", "Reconnect", true, "Should the bot automatically reconnect to IRC?");
        /*$this->bot->core("settings")
            ->create("Irc", "RelayGuildName", "", "What is the name for GC guildrelay?");*/
        $this->bot->core("settings")
            ->create(
                "Irc",
                "ItemRef",
                "AUNO",
                "Should AOItems or AUNO be used for links in item refs?",
                "AOItems;AUNO"
            );
        $this->bot->core("settings")
            ->create(
                "Irc",
                "Chat",
                $chatgroups,
                "Which channels should be relayed into IRC and vice versa?",
                "gc;pgroup;both"
            );
        $this->bot->core("settings")
            ->create("Irc", "MaxRelaySize", 500, "What's the maximum amount of characters relayed to IRC?");
        $this->bot->core("settings")
            ->create(
                "Irc",
                "NotifyOnDrop",
                false,
                "Should the chat be notified if something isn't relayed because it's too large?"
            );
        /*$this->bot->core("settings")
            ->create(
                "Irc",
                "UseGuildRelay",
                true,
                "Should chat coming from IRC also be relayed over the guild relay if it's set up?"
            );*/
        $this->bot->core("settings")
            ->create(
                "Irc",
                "Announce",
                true,
                "Should we announce logons and logoffs as controlled by the Logon module to IRC?"
            );
        $this->bot->core("settings")
            ->create("Irc", "ignoreSyntax", "", "Is there a first letter that should make the bot ignore messages for IRC relay (leave empty if none) ?");
		$this->bot->core("settings")
            ->create("Irc", "PrivRelay", false, "Should the bot be relaying private join/leave to Irc ?", "On;Off");			
        $this->bot->core("colors")->define_scheme("Irc", "Text", "normal");
        $this->bot->core("colors")->define_scheme("Irc", "User", "normal");
        $this->bot->core("colors")->define_scheme("Irc", "Group", "normal");
        $this->irc = null;
        $this->last_log["st"] = time();
        $this->bot->core("timer")->register_callback("IRC", $this);
        $this->spam[0] = array(
            0,
            0,
            0,
            0
        );
        $this->bot->db->query(
            "UPDATE #___online SET status_gc = 0 WHERE botname = '" . $this->bot->botname . " - IRC'"
        );
    }


    function command_handler($name, $msg, $source)
    {
        $com = $this->parse_com($msg);
        switch (strtolower($com['com'])) {
            case 'irc':
                switch (strtolower($com['sub'])) {
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
                        if (($com['args'] == 'on') || ($com['args'] == 'off')) {
                            return $this->reconnect($com['args']);
                        }
                        break;
                    /*case 'relayguildname':
                        return $this->change_relayguildname($com['args']);
                        break;*/
                    case 'itemref':
                        if (($com['args'] == 'auno') || ($com['args'] == 'aodb')) {
                            return $this->change_itemref($com['args']);
                        }
                        break;
                    case 'chat':
                        if (($com['args'] == 'gc') || ($com['args'] == 'pg') || ($com['args'] == 'both')) {
                            return $this->change_chat($com['args']);
                        }
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
        if (strtolower(
                $this->bot->core("settings")
                    ->get("Irc", "ItemRef")
            ) == "auno"
        ) {
            $rep = "http://auno.org/ao/db.php?id=\\1&id2=\\2&ql=\\3";
        } else {
            $rep = "http://aoitems.com/item/\\1/\\2/\\3";
        }
        $msg = preg_replace(
            "/<a href=\"itemref:\/\/([0-9]*)\/([0-9]*)\/([0-9]*)\">(.*)<\/a>/iU",
            chr(3) . chr(3) . "\\4" . chr(3) . " " . chr(3) . "(" . $rep . ")" . chr(3) . chr(3),
            $msg
        );
        $msg = preg_replace(
            "/<a style=\"text-decoration:none\" href=\"itemref:\/\/([0-9]*)\/([0-9]*)\/([0-9]*)\">(.*)<\/a>/iU",
            chr(3) . chr(3) . "\\4" . chr(3) . " " . chr(3) . "(" . $rep . ")" . chr(3) . chr(3),
            $msg
        );
		$msg = preg_replace("/<a href='user:\/\/(.+)\'>/isU", "", $msg);
		$msg = preg_replace("/<a href=\"user:\/\/(.+)\">/isU", "", $msg);
        $msg = preg_replace("/<a href=\"(.+)\">/isU", "\\1", $msg);
        $msg = preg_replace("/<a style=\"text-decoration:none\" href=\"(.+)\">/isU", "\\1", $msg);
        $msg = preg_replace("/<\/a>/iU", "", $msg);
        $msg = preg_replace("/<font(.+)>/iU", "", $msg);
        $msg = preg_replace("/<\/font>/iU", "", $msg);
		$msg = $this->bot->core("tools")->cleanString($msg,1);
        return $msg;
    }	

    function send_irc($prefix, $name, $msg)
    {
        if (!$this->bot->core("settings")->get("irc", "connected")) {
            return false;
        }
        $msg = $this->strip_formatting($msg);
        // If msg is too long to be relayed drop it:
        if (strlen($msg) > $this->bot->core("settings")
                ->get("Irc", "Maxrelaysize")
        ) {
            return false;
        }
        $ircmsg = "";
        if ($prefix != "") {
            $ircmsg = chr(2) . chr(2) . chr(2) . $prefix . chr(2) . ' ';
        }
        if ($name != "") {
            $ircmsg .= $name . ': ';
        }
        $ircmsg .= $msg;
		if ($this->bot->core("settings")->get("IRC", "OutLog")) $this->bot->log("IRC", "Outgoing", $ircmsg);
        $ircmsg = htmlspecialchars_decode($ircmsg);
        $this->irc->message(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->core("settings")
                ->get("Irc", "Channel"),
            $ircmsg
        );
        return true;
    }


    /*
    This gets called on a msg in the group
    */
    function gmsg($name, $group, $msg)
    {
		$ignore = $this->bot->core("settings")->get("Irc", "ignoreSyntax");
		if($ignore!=""&&substr($msg,0,1)==$ignore) return false;		
        $msg = str_replace("&gt;", ">", $msg);
        $msg = str_replace("&lt;", "<", $msg);
        if (($this->irc != null)
            && ((strtolower(
                        $this->bot->core("settings")
                            ->get("Irc", "Chat")
                    ) == "gc")
                || (strtolower(
                        $this->bot
                            ->core("settings")->get("Irc", "Chat")
                    ) == "both"))
        ) {
            if (!$this->send_irc(
                $this->bot->core("settings")
                    ->get("Irc", "Ircguildprefix"),
                $name,
                $msg
            )
            ) {
                if ($this->bot->core("settings")->get("Irc", "Notifyondrop")) {
                    $msg2 = "##error##Last line not relayed to IRC as it's containing too many characters!##end##";
                    $this->spam[2][$this->spam[0][2] + 1] = time();
                    if ($this->spam[0][2] == 5) {
                        if ($this->spam[2][1] > time() - 30) {
                            $this->irc_disconnect();
                            $msg2 = "IRC Spam Detected, Disconnecting IRC";
                        }
                        $this->spam[0][2] = 0;
                    } else {
                        $this->spam[0][2]++;
                    }
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
		$ignore = $this->bot->core("settings")->get("Irc", "ignoreSyntax");
		if($ignore!=""&&substr($msg,0,1)==$ignore) return false;			
        $msg = str_replace("&gt;", ">", $msg);
        $msg = str_replace("&lt;", "<", $msg);
        if (($this->irc != null)
            && ((strtolower(
                        $this->bot->core("settings")
                            ->get("Irc", "Chat")
                    ) == "pgroup")
                || (strtolower(
                        $this->bot
                            ->core("settings")->get("Irc", "Chat")
                    ) == "both"))
        ) {
            if (!$this->send_irc(
                $this->bot->core("settings")
                    ->get("Irc", "Ircguestprefix"),
                $name,
                $msg
            )
            ) {
                if ($this->bot->core("settings")->get("Irc", "Notifyondrop")) {
                    $msg2 = "##error##Last line not relayed to IRC as it's containing too many characters!##end##";
                    $this->spam[3][$this->spam[0][3] + 1] = time();
                    if ($this->spam[0][3] == 5) {
                        if ($this->spam[3][1] > time() - 30) {
                            $this->irc_disconnect();
                            $msg2 = "IRC Spam Detected, Turning Off AutoReconnect";
                        }
                        $this->spam[0][3] = 0;
                    } else {
                        $this->spam[0][3]++;
                    }
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
        if (($this->irc != null) && (!$this->irc->_rawreceive())) {
            $this->irc_disconnect();
            $this->bot->send_gc("IRC connection lost...");
            $this->spam[1][$this->spam[0][1] + 1] = time();
            if ($this->spam[0][1] >= 2) {
                if ($this->spam[1][1] > time() - 30) {
                    $this->change_reconnect("off");
                    $this->bot->send_gc("IRC Spam Detected, Turning Off AutoReconnect");
                }
                $this->spam[0][1] = 0;
            } else {
                $this->spam[0][1]++;
            }
            if ($this->bot->core("settings")->get("Irc", "Reconnect")) {
                $this->irc_connect();
            }
        }
    }


    /*
    This gets when bot connects
    */
    function connect()
    {
        if ($this->bot->core("settings")->get("Irc", "Reconnect")) {
            $res = $this->bot->core("timer")->list_timed_events("IRC");
            if (!empty($res)) {
                foreach ($res as $con) {
                    $this->bot->core("timer")->del_timer("IRC", $con['id']);
                }
            }
            $this->bot->core("timer")
                ->add_timer(true, "IRC", 30, "IRC-Connect", "internal", 0, "None");
        }
    }


    function timer($name, $prefix, $suffix, $delay)
    {
        if ($name == "IRC-Connect") {
            $this->irc_connect("c");
        }
    }


    /*
    This gets called when bot disconnects
    */
    function disconnect()
    {
        if ($this->bot->core("settings")->get("irc", "connected")) {
            $this->irc_disconnect();
        }
    }


    /*
    This gets called if a buddy logs on/off
    */
    function buddy($name, $msg)
    {
        if ($msg == 1 || $msg == 0) {
            // Only handle this if connected to IRC server
            if (!$this->bot->core("settings")->get("irc", "connected")) {
                return;
            }

            if ((!$this->bot->core("notify")
                    ->check($name))
                && isset($this->is[$name])
            ) {
                if ($msg == 1) {
                    $msg = $name . " is online.";
                } else {
                    $msg = $name . " is offline.";
                }
                $this->irc->message(SMARTIRC_TYPE_CHANNEL, $this->is[$name], $msg);
                unset($this->is[$name]);
            } else {
                if ((!$this->bot->core("notify")
                        ->check($name))
                    && isset($this->whois[$name])
                ) {
                    $msg = $this->whois_player($name) . " ";
                    $this->irc->message(SMARTIRC_TYPE_CHANNEL, $this->whois[$name], $msg);
                    unset($this->whois[$name]);
                }
            }
        }
    }


    /*
    * Change server to connect to
    */
    function change_server($new)
    {
        $this->bot->core("settings")->save("Irc", "Server", $new);
        if ($this->irc == null) {
            return "Server has been changed to ##highlight##$new##end##.";
        } else {
            return "Server has been changed to ##highlight##$new##end##. You must reconnect to the IRC server.";
        }
    }


    /*
    * Change port to connect to
    */
    function change_port($new)
    {
        $this->bot->core("settings")->save("Irc", "Port", $new);
        if ($this->irc == null) {
            return "Port has been changed to ##highlight##$new##end##.";
        } else {
            return "Port has been changed to ##highlight##$new##end##. You must reconnect to the IRC server.";
        }
    }


    /*
    * Change channel to connect to
    */
    function change_chan($new)
    {
        //Make sure the channel has a leading #
        if (substr($new, 0, 1) !== '#') {
            $new = '#' . $new;
        }
        $this->bot->core("settings")->save("Irc", "Channel", $new);
        if ($this->irc == null) {
            return "Channel has been changed to ##highlight##$new##end##.";
        } else {
            return "Channel has been changed to ##highlight##$new##end##. You must reconnect to the IRC server.";
        }
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
        if ($this->irc == null) {
            return "Nick has been changed to ##highlight##$new##end##.";
        } else {
            return "Nick has been changed to ##highlight##$new##end##. You must reconnect to the IRC server.";
        }
    }


    /*
    * Change guildprefix
    */
    function change_guildprefix($new)
    {
        if ($new == "\"\"") {
            $new = "";
        }
        $this->bot->core("settings")->save("Irc", "Guildprefix", $new);
        return "Guild prefix has been changed.";
    }


    /*
    * Change ircprefix
    */
    function change_ircprefix($new)
    {
        if ($new == "\"\"") {
            $new = "";
        }
        $this->bot->core("settings")->save("Irc", "Ircguildprefix", $new);
        return "IRC prefix has been changed.";
    }


    /*
    * Change announce
    */
    function change_announce($new)
    {
        if (strtolower($new) == "on") {
            $tmp = true;
        } else {
            $tmp = false;
        }
        $this->bot->core("settings")->save("Irc", "Announce", $tmp);
        return "Announce has been switched " . $new . ".";
    }


    /*
    * Change reconnect
    */
    function change_reconnect($new)
    {
        $tmp = 0;
        $stmp = false;
        if (strtolower($new) == "on") {
            $tmp = 1;
            $stmp = true;
        }
        $this->bot->core("settings")->save("Irc", "Reconnect", $stmp);
        if ($this->irc != null) {
            $this->irc->setAutoReconnect($tmp);
        }
        return "Reconnect has been switched " . $new . ".";
    }


    /*
    * Change guildprefix
    */
    /*function change_relayguildname($new)
    {
        $this->bot->core("settings")->save("Irc", "Relayguildname", $new);
        return "Guildname for GC-Relay has been changed.";
    }*/


    /*
    * Change itemref
    */
    function change_itemref($new)
    {
        $tmp = "AOItems";
        if (strtolower($new) == "auno") {
            $tmp = "AUNO";
        }
        $this->bot->core("settings")->save("Irc", "ItemRef", $tmp);
        return "ItemRef has been switched to " . $new . ".";
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
        if (empty($server)) {
            $this->error->set("An IRC server was not defined. Please see <pre>settings irc");
            return $this->error;
        }
        if ((empty($channel)) || ($channel === '#')) {
            $this->error->set("An IRC channel was not defined. Please see <pre>settings irc");
            return $this->error;
        }
        if (empty($nick)) {
            $this->error->set("A Nickname was not defined. Please see <pre>settings irc");
            return $this->error;
        }
        //Make sure that the channel name is prefixed with a '#'
        if (substr($channel, 0, 1) !== '#') {
            $this->bot->core('settings')
                ->save('Irc', 'Channel', '#' . $channel);
        }
        if (($name != "") && ($name != "c")) {
            $this->bot->send_tell(
                $name,
                "Connecting to IRC server: " . $this->bot
                    ->core("settings")->get("Irc", "Server")
            );
        } else {
            if ($name == "") {
                $this->bot->send_gc(
                    "Connecting to IRC server: " . $this->bot
                        ->core("settings")->get("Irc", "Server")
                );
            }
        }
        $this->irc = new Net_SmartIRC();
        $this->irc->setUseSockets(true);
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'online',
            $this->bot->commands["tell"]["irc"],
            'irc_online'
        );		
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'sm',
            $this->bot->commands["tell"]["irc"],
            'irc_online'
        );
		$this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'tara',
            $this->bot->commands["tell"]["irc"],
            'irc_tara'
        );		
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'viza',
            $this->bot->commands["tell"]["irc"],
            'irc_viza'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'help',
            $this->bot->commands["tell"]["irc"],
            'irc_help'
        );		
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'whois',
            $this->bot->commands["tell"]["irc"],
            'irc_whois'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'is (.*)',
            $this->bot->commands["tell"]["irc"],
            'irc_is'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'alts (.*)',
            $this->bot->commands["tell"]["irc"],
            'alts_is'
        );		
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'level (.*)',
            $this->bot->commands["tell"]["irc"],
            'irc_level'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'lvl (.*)',
            $this->bot->commands["tell"]["irc"],
            'irc_level'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'pvp (.*)',
            $this->bot->commands["tell"]["irc"],
            'irc_level'
        );			
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'level (.*)',
            $this->bot->commands["tell"]["irc"],
            'irc_level'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'lvl (.*)',
            $this->bot->commands["tell"]["irc"],
            'irc_level'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'pvp (.*)',
            $this->bot->commands["tell"]["irc"],
            'irc_level'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'bots',
            $this->bot->commands["tell"]["irc"],
            'irc_up'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'bot',
            $this->bot->commands["tell"]["irc"],
            'irc_up'
        );			
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            $this->bot->commpre . 'up',
            $this->bot->commands["tell"]["irc"],
            'irc_up'
        );		
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'bots',
            $this->bot->commands["tell"]["irc"],
            'irc_up'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'bot',
            $this->bot->commands["tell"]["irc"],
            'irc_up'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'up',
            $this->bot->commands["tell"]["irc"],
            'irc_up'
        );		
        //$this -> irc -> registerActionhandler(SMARTIRC_TYPE_QUERY, $this -> bot -> commpre . 'command', $this -> bot -> commands["tell"]["irc"], 'command');
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'is (.*)',
            $this->bot->commands["tell"]["irc"],
            'irc_is'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'alts (.*)',
            $this->bot->commands["tell"]["irc"],
            'alts_is'
        );		
        //$this -> irc -> registerActionhandler(SMARTIRC_TYPE_QUERY, $this -> bot -> commpre . 'tell (.*)', $this -> bot -> commands["tell"]["irc"], 'ao_msg');
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'online',
            $this->bot->commands["tell"]["irc"],
            'irc_online'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'sm',
            $this->bot->commands["tell"]["irc"],
            'irc_online'
        );		
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'tara',
            $this->bot->commands["tell"]["irc"],
            'irc_tara'
        );
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'viza',
            $this->bot->commands["tell"]["irc"],
            'irc_viza'
        );	
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'help',
            $this->bot->commands["tell"]["irc"],
            'irc_help'
        );			
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            $this->bot->commpre . 'whois',
            $this->bot->commands["tell"]["irc"],
            'irc_whois'
        );
        $this->irc->registerActionhandler(SMARTIRC_TYPE_NAME, '.*', $this->bot->commands["tell"]["irc"], 'irc_query');
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_CHANNEL,
            '.*',
            $this->bot->commands["tell"]["irc"],
            'irc_receive'
        );
        $this->irc->registerActionhandler(SMARTIRC_TYPE_JOIN, '.*', $this->bot->commands["tell"]["irc"], 'irc_join');
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_NICKCHANGE,
            '.*',
            $this->bot->commands["tell"]["irc"],
            'irc_nick'
        );
        $this->irc->registerActionhandler(SMARTIRC_TYPE_PART, '.*', $this->bot->commands["tell"]["irc"], 'irc_part');
        $this->irc->registerActionhandler(SMARTIRC_TYPE_QUIT, '.*', $this->bot->commands["tell"]["irc"], 'irc_part');
        $this->irc->registerActionhandler(SMARTIRC_TYPE_KICK, '.*', $this->bot->commands["tell"]["irc"], 'irc_part');
        $this->irc->registerActionhandler(
            SMARTIRC_TYPE_QUERY,
            '.*',
            $this->bot->commands["tell"]["irc"],
            'irc_receive_msg'
        );
        $this->irc->setCtcpVersion($this->bot->botversionname . " (" . $this->bot->botversion . ")");
        $this->irc->setAutoReconnect(
            (($this->bot->core("settings")
                ->get("Irc", "Reconnect")) ? 1 : 0)
        );
        $this->irc->connect(
            $this->bot->core("settings")
                ->get("Irc", "Server"),
            $this->bot->core("settings")
                ->get("Irc", "Port")
        );
        if($this->bot->core("settings")->get("Irc", "OAuthTok")==""||$this->bot->core("settings")->get("Irc", "OAuthTok")==" ") {
			$this->irc->login(
				$this->bot->core("settings")
					->get("Irc", "Nick"),
				'BeBot',
				0,
				'BeBot'
			);
		} else {
			$this->irc->login(
				$this->bot->core("settings")
					->get("Irc", "Nick"),
				$this->bot->core("settings")
					->get("Irc", "Nick"),
				0,
				$this->bot->core("settings")
					->get("Irc", "Nick"),
				$this->bot->core("settings")->get("Irc", "OAuthTok")
			);			
		}
        $this->irc->join(
            array(
                 $this->bot->core("settings")
                     ->get("Irc", "Channel")
            ),
            $this->bot
                ->core("settings")->get("Irc", "Channelkey")
        );
        $this->register_event("cron", "1sec");
        $this->bot->core("settings")->save("irc", "connected", true);
        $this->bot->db->query(
            "UPDATE #___online SET status_gc = 0 WHERE botname = '" . $this->bot->botname . " - IRC'"
        );
        return "Done connecting...";
    }


    /*
    * Disconnect(!!!)
    */
    function irc_disconnect()
    {
        if ($this->irc != null) {
            $this->irc->disconnect();
            $this->irc = null;
            $this->unregister_event("cron", "1sec");
            $this->bot->core("settings")->save("irc", "connected", false);
            $this->bot->db->query(
                "UPDATE #___online SET status_gc = 0 WHERE botname = '" . $this->bot->botname . " - IRC'"
            );
            return "Disconnected from IRC server.";
        } else {
            return "IRC already disconnected.";
        }
    }


    /*
    * Gets called for an inc IRC message
    */
    function irc_receive(&$irc, &$data)
    {
		if(mb_detect_encoding($data->message, 'UTF-8', true)) $data->message = mb_convert_encoding($data->message, 'ISO-8859-1', 'UTF-8');
        if ((strtolower($data->message) != strtolower(str_replace("\\", "", $this->bot->commpre . 'online')))
            && (strtolower($data->message) != strtolower(str_replace("\\", "", $this->bot->commpre . 'is')))
            && (strtolower($data->message) != strtolower(str_replace("\\", "", $this->bot->commpre . 'whois')))
            && (strtolower($data->message) != strtolower(str_replace("\\", "", $this->bot->commpre . 'level')))
			&& (strtolower($data->message) != strtolower(str_replace("\\", "", $this->bot->commpre . 'tara')))
			&& (strtolower($data->message) != strtolower(str_replace("\\", "", $this->bot->commpre . 'viza')))
			&& (strtolower($data->message) != strtolower(str_replace("\\", "", $this->bot->commpre . 'help')))
			&& (strtolower($data->message) != strtolower(str_replace("\\", "", $this->bot->commpre . 'bots')))
        ) {
            $msg = str_replace("<", "&lt;", $data->message);
            $msg = str_replace(">", "&gt;", $msg);
            // Turn item refs back to ingame format
            $itemstring = "<a href=\"itemref://\\3/\\4/\\5\">\\1</a>";
            $msg = preg_replace(
                "/" . chr(3) . chr(3) . "(.+?)" . chr(3) . " " . chr(
                    3
                ) . "\((.+?)id=([0-9]+)&id2=([0-9]+)&ql=([0-9]+)\)" . chr(3) . chr(3) . "/iU",
                $itemstring,
                $msg
            );
            $msg = preg_replace(
                "/" . chr(3) . chr(3) . "(.+?)" . chr(3) . " " . chr(
                    3
                ) . "\((.+?)LowID=([0-9]+)&HiID=([0-9]+)&QL=([0-9]+)\)" . chr(3) . chr(3) . "/iU",
                $itemstring,
                $msg
            );
            // Check if it's relayed chat of another bot
            if (preg_match("/" . chr(2) . chr(2) . chr(2) . "(.+)" . chr(2) . "(.+)/i", $msg, $info)) {
                $txt = "##irc_group##" . $info[1] . "##end## ##irc_text##" . $info[2] . "##end##";
            } else {
                $txt = "##irc_group##" . $this->bot->core("settings")
                        ->get(
                            "Irc",
                            "Guildprefix"
                        ) . "##end## ##irc_user##" . $data->nick . ":##end####irc_text## " . $msg . "##end##";
            }
            $this->bot->send_output(
                "",
                $txt,
                $this->bot->core("settings")
                    ->get("Irc", "Chat")
            );
			if ($this->bot->core("settings")->get("IRC", "IncLog")) $this->bot->log("IRC", "Incoming", $txt);
            /*if ($this->bot->core("settings")
                    ->get("Irc", "UseGuildRelay")
                && $this->bot
                    ->core("settings")->get("Relay", "Relay")
            ) {
                $this->bot->core("relay")->relay_to_bot($txt);
            }*/
            if (!empty($this->ircmsg)) {
                foreach ($this->ircmsg as $send) {
                    $send->irc($data->nick, $msg, "msg");
                }
            }
        }
    }


    /*
    * Gets called when someone joins IRC chan
    */
    function irc_join(&$irc, &$data)
    {
        if (($data->nick != $this->bot->core("settings")
                    ->get("Irc", "Nick"))
            && $this->bot->core("settings")
                    ->get("Irc", "Announce")
        ) {
            $msg = "##irc_group##" . $this->bot->core("settings")
                    ->get(
                        "Irc",
                        "Guildprefix"
                    ) . "##end## ##highlight##" . $data->nick . "##end## has logged##highlight## on##end##.";
            $this->bot->send_output(
                "",
                $msg,
                $this->bot->core("settings")
                    ->get("Irc", "Chat")
            );
        }
        if (($data->nick != $this->bot->core("settings")->get("Irc", "Nick"))) {
            $this->irconline[strtolower($data->nick)] = strtolower($data->nick);
            $this->bot->db->query(
                "INSERT INTO #___online (nickname, botname, status_gc) VALUES ('" . $data->nick . "', '" . $this->bot->botname . " - IRC', 1) ON DUPLICATE KEY UPDATE status_gc = 1"
            );
        }
        /*if ($this->bot->core("settings")
                ->get("Irc", "UseGuildRelay")
            && $this->bot->core("settings")
                ->get("Relay", "Relay")
        ) {
            $this->bot->core("relay")->relay_to_bot($msg);
        }*/
        if (!empty($this->ircmsg)) {
            foreach ($this->ircmsg as $send) {
                $send->irc($data->nick, "", "join");
            }
        }
    }


    /*
    * Gets called when someone leaves IRC chan
    */
    function irc_part(&$irc, &$data)
    {
        if (($data->nick != $this->bot->core("settings")
                    ->get("Irc", "Nick"))
            && $this->bot->core("settings")
                    ->get("Irc", "Announce")
        ) {
            $msg = "##irc_group##" . $this->bot->core("settings")
                    ->get(
                        "Irc",
                        "Guildprefix"
                    ) . "##end## ##highlight##" . $data->nick . "##end## has logged##highlight## off##end## (" . $data->message . ").";
            $this->bot->send_output(
                "",
                $msg,
                $this->bot->core("settings")
                    ->get("Irc", "Chat")
            );
        }
        if (($data->nick != $this->bot->core("settings")->get("Irc", "Nick"))) {
            unset($this->irconline[strtolower($data->nick)]);
        }
        $this->bot->db->query(
            "UPDATE #___online SET status_gc = 0 WHERE botname = '" . $this->bot->botname . " - IRC' AND nickname = '" . $data->nick . "'"
        );
        /*if ($this->bot->core("settings")
                ->get("Irc", "UseGuildRelay")
            && $this->bot->core("settings")
                ->get("Relay", "Relay")
        ) {
            $this->bot->core("relay")->relay_to_bot($msg);
        }*/
        if (!empty($this->ircmsg)) {
            foreach ($this->ircmsg as $send) {
                $send->irc($data->nick, "", "part");
            }
        }
    }


    /*
    * Gets called when someone does !is
    */
    function irc_is(&$irc, &$data)
    {
        if ($data->type == SMARTIRC_TYPE_QUERY) {
            $target = $data->nick;
        } else {
            $target = $this->bot->core("settings")->get("Irc", "Channel");
        }
        if (!preg_match("/^" . $this->bot->commpre . "is ([a-zA-Z0-9]{4,25})$/i", $data->message, $info)) {
            $msg = "Please enter a valid name.";
        } else {
            $info[1] = ucfirst(strtolower($info[1]));
            $msg = "";
            if ($this->bot->core('player')->id($info[1]) instanceof BotError) {
                $msg = "Player " . $info[1] . " does not exist.";
            } else {
                if ($info[1] == ucfirst(strtolower($this->bot->botname))) {
                    $msg = "I'm online!";
                } else {
                    if ($this->bot->core("chat")->buddy_exists($info[1])) {
                        if ($this->bot->core("chat")->buddy_online($info[1])) {
                            $msg = $info[1] . " is online.";
                        } else {
                            $msg = $info[1] . " is offline.";
                        }
                    } else {
                        $this->is[$info[1]] = $target;
                        $this->bot->core("chat")->buddy_add($info[1]);
                    }
                }
            }
        }
        if (!empty($msg)) {
            $this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
        }
    }
	
	
    /*
    * Gets called when someone does !alts
    */
    function alts_is(&$irc, &$data)
    {
        $msg = "";		
        if ($data->type == SMARTIRC_TYPE_QUERY) {
            $target = $data->nick;
        } else {
            $target = $this->bot->core("settings")->get("Irc", "Channel");
        }
        if (!preg_match("/^" . $this->bot->commpre . "alts ([a-zA-Z0-9]{4,25})$/i", $data->message, $info)) {
            $msg = "Please enter a valid name.";
        } else {
            $info[1] = ucfirst(strtolower($info[1]));
            if ($this->bot->core('player')->id($info[1]) instanceof BotError) {
                $msg = "Player " . $info[1] . " does not exist.";
            } else {
				$main = $this->bot->core("alts")->main($info[1]);
				$list = $this->bot->core("alts")->get_alts($main);
				if(count($list)>0) $msg = $main."'s alts : ".implode(" ", $list);
				else $msg = $main." has no alts defined!";
            }
        }
        if (!empty($msg)) {
            $this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
        }
    }	


    /*
    * Gets called when someone does !online
    */
    function irc_online(&$irc, &$data)
    {
		$sent = "";
        if ($data->type == SMARTIRC_TYPE_QUERY) {
            $target = $data->nick;
        } else {
            $target = $this->bot->core("settings")->get("Irc", "Channel");
        }
        $channels = "";
        if (strtolower(
                $this->bot->core("settings")
                    ->get("Irc", "Chat")
            ) == "both"
        ) {
            $channels = "(status_pg = 1 OR status_gc = 1)";
        } elseif (strtolower(
                $this->bot->core("settings")
                    ->get("Irc", "Chat")
            ) == "gc"
        ) {
            $channels = "status_gc = 1";
        } elseif (strtolower(
                $this->bot->core("settings")
                    ->get("Irc", "Chat")
            ) == "pgroup"
        ) {
            $channels = "status_pg = 1";
        }
        $online = $this->bot->db->select(
            "SELECT DISTINCT(nickname), botname FROM #___online WHERE " . $this->bot
                ->core("online")
                ->otherbots() . " AND " . $channels . " ORDER BY nickname ASC"
        );	
        if (empty($online)) {
            $msg = "Nobody online on notify!";
        } else {
            $orglist = array();
			$othlist = array();
            foreach ($online as $name) {
				if($name[1] == $this->bot->botname) {
					$orglist[] = $name[0];
				}
            }			
			foreach ($online as $name) {
				if($name[1] != $this->bot->botname && !in_array($name[0], $orglist) ) {
					$othlist[] = $name[0];
				}				
			}		
            $sent = count($orglist) . " online in org + ". count($othlist) . " others : ";
			if(count($orglist)>0&&count($othlist)>0) { $spacer = " + "; } else { $spacer = " "; }
            $sent .= implode(" ", $orglist). $spacer . implode(" ", $othlist);			
        }
        $this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $sent);
    }


    function irc_send_local($msg)
    {
        if ($msg) {
            $this->irc->message(
                SMARTIRC_TYPE_CHANNEL,
                $this->bot
                    ->core("settings")->get("Irc", "Channel"),
                $msg
            );
        }
    }


    function whois_player($name)
    {
        $who = $this->bot->core("whois")->lookup($name);
        if (!$who) {
            $this->whois[$name] = $this->target;
        } elseif (!($who instanceof BotError)) {
			if (strtolower($this->bot->game) == 'ao') {
				$at = "(AT " . $who["at_id"] . " - " . $who["at"] . ") ";
			}
            $result = "\"" . $who["nickname"] . "\"";
            if (!empty($who["firstname"]) && ($who["firstname"] != "Unknown")) {
                $result = $who["firstname"] . " " . $result;
            }
            if (!empty($who["lastname"]) && ($who["lastname"] != "Unknown")) {
                $result .= " " . $who["lastname"];
            }
            if (strtolower($this->bot->game) == 'ao') {
                $result .= " is a level " . $who["level"] . " " . $at . "" . $who["gender"] . " " . $who["breed"] . " ";
                $result .= $who["profession"] . ", " . $who["faction"];
            } else {
                $result .= " is a level " . $who["level"] . " ";
                $result .= $who["class"];
            }
            if (!empty($who["rank"])) {
                $result .= ", " . $who["rank"] . " of " . $who["org"] . "";
            }
            if ($this->bot->core("settings")->get("Whois", "Details") == true) {
                if ($this->bot->core("settings")
                        ->get("Whois", "ShowMain") == true
                ) {
                    $main = $this->bot->core("alts")->main($name);
                    if ($main != $name) {
                        $result .= " :: Alt of " . $main;
                    }
                }
            }
        } else {
            $result = $who;
        }
        return $result;
    }

    function irc_tara(&$irc, &$data)
    {
        if ($data->type == SMARTIRC_TYPE_QUERY) {
            $target = $data->nick;
        } else {
            $target = $this->bot->core("settings")->get("Irc", "Channel");
        }
        $this->target = $target;
		$msg = "No Tarasque/Cameloot timer found.";
		if($this->bot->exists_module("taraviza")) {
			$msg = $this->bot->core("taraviza")->show_tara("user");
		}
        $this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
    }	

    function irc_viza(&$irc, &$data)
    {
        if ($data->type == SMARTIRC_TYPE_QUERY) {
            $target = $data->nick;
        } else {
            $target = $this->bot->core("settings")->get("Irc", "Channel");
        }
        $this->target = $target;
		$msg = "No Vizaresh/Gauntlet timer found.";
		if($this->bot->exists_module("taraviza")) {
			$msg = $this->bot->core("taraviza")->show_viza("user");
		}		
        $this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
    }		
	
    function irc_up(&$irc)
    {
        $target = $this->bot->core("settings")->get("Irc", "Channel");
        $this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $this->bot->core("bot_statistics")->up_bots($this->bot->botname, "Irc"));
    }	
	
    function irc_help(&$irc)
    {
        $target = $this->bot->core("settings")->get("Irc", "Channel");
        $this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $this->note);
    }		
	
    function irc_whois(&$irc, &$data)
    {
		$msg = "";
        if ($data->type == SMARTIRC_TYPE_QUERY) {
            $target = $data->nick;
        } else {
            $target = $this->bot->core("settings")->get("Irc", "Channel");
        }
        $this->target = $target;
        preg_match("/^" . $this->bot->commpre . "whois (.+)$/i", $data->message, $info);
        $info[1] = ucfirst(strtolower($info[1]));
        if (!$this->bot->core('player')->id($info[1])) {
            $msg = "Player " . $info[1] . " does not exist.";
        } else {
            if ($this->bot->core("chat")->buddy_exists($info[1])) {
                $msg = $this->whois_player($info[1]);
            } else {
                $this->whois[$info[1]] = $target;
                $this->bot->core("chat")->buddy_add($info[1]);
            }
        }
        $this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
    }


    function irc_nick(&$irc, &$data)
    {
        if ($data->nick != $this->bot->core("settings")->get("Irc", "Nick")) {
            unset($this->irconline[strtolower($data->nick)]);
            $this->irconline[strtolower($data->message)] = strtolower($data->message);
        }
        $txt = "##irc_group##" . $this->bot->core("settings")
                ->get(
                    "Irc",
                    "Guildprefix"
                ) . "##end## ##irc_user##" . $data->nick . "##end####irc_text## is known as##end## ##irc_user##" . $data->message . "##end##";
        if ($this->bot->core("settings")
                ->get("Irc", "Announce")
        ) {
            $this->bot->send_output(
                "",
                $txt,
                $this->bot->core("settings")
                    ->get("Irc", "Chat")
            );
        }
        /*if ($this->bot->core("settings")
                ->get("Irc", "UseGuildRelay")
            && $this->bot->core("settings")
                ->get("Relay", "Relay")
        ) {
            $this->bot->core("relay")->relay_to_bot($txt);
        }*/
        $this->bot->db->query(
            "UPDATE #___online SET nickname = '" . $data->message . "' WHERE botname = '" . $this->bot->botname . " - IRC' AND nickname = '" . $data->nick . "'"
        );
    }


    // gets the names list on connection
    function irc_query(&$irc, &$data)
    {
        if (strcasecmp(
                $data->channel,
                $this->bot->core("settings")
                    ->get("Irc", "Channel")
            ) == 0
        ) {
            $this->irconline = array();
            if (!empty($data->messageex)) {
                foreach ($data->messageex as $ircuser) {
                    $ircuser = ltrim($ircuser, '@+');
                    if ($ircuser != $this->bot->core("settings")
                            ->get("irc", "nick")
                    ) {
                        $this->irconline[strtolower($ircuser)] = strtolower($ircuser);
                        $this->bot->db->query(
                            "INSERT INTO #___online (nickname, botname, status_gc) VALUES ('" . $ircuser . "', '" . $this->bot->botname
                            . " - IRC', 1) ON DUPLICATE KEY UPDATE status_gc = 1"
                        );
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
        if ($this->bot->core("settings")->get("irc", "connected")) {
            $names = $this->irconline;
            if (empty($names)) {
                $msg = 'Nobody online in ##highlight##' . $this->bot
                        ->core("settings")->get("Irc", "Channel") . '##end##!';
            } else {
                $msg = '##highlight##' . count($names) . '##end## users in ##highlight##' . $this->bot
                        ->core("settings")->get("Irc", "Channel") . '##end##: ';
                foreach ($names as $name) {
                    $msg .= '##highlight##' . $name . '##end##, ';
                }
                $msg = substr($msg, 0, -2);
            }
            return $msg;
        } else {
            return 'Not connected to IRC';
        }
    }


    /*
    Level command for irc
    */
    function irc_level(&$irc, &$data)
    {
        if ($data->type == SMARTIRC_TYPE_QUERY) {
            $target = $data->nick;
        } else {
            $target = $this->bot->core("settings")->get("Irc", "Channel");
        }
        if (strtolower($this->bot->game) == 'ao') {
			$msg = explode(" ", $data->message, 2);
			$msg = $this->bot->commands["tell"]["level"]->get_level($msg[1]);
			$msg = $this->strip_formatting($msg);
		} else {
			$msg = "Unsupported command by context.";
		}
        if (!empty($msg)) {
            $this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
        }
    }
	
	
    /*
    Default command for irc
    */
    function irc_default(&$irc, &$data)
    {
        if ($data->type == SMARTIRC_TYPE_QUERY) {
            $target = $data->nick;
        } else {
            $target = $this->bot->core("settings")->get("Irc", "Channel");
        }
        $msg = "Unsupported command by default.";
        if (!empty($msg)) {
            $this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
        }
    }	


	/*
    Command distribution
    */
    function irc_receive_msg(&$irc, &$data)
    {
        $msg = explode(" ", $data->message, 2);
        Switch ($msg[0]) {
            case $this->bot->commpre . 'is':
            case $this->bot->commpre . 'online':
			case $this->bot->commpre . 'sm':
            case $this->bot->commpre . 'whois':
            case $this->bot->commpre . 'level':
            case $this->bot->commpre . 'lvl':
            case $this->bot->commpre . 'pvp':
            case $this->bot->commpre . 'tara':
            case $this->bot->commpre . 'viza':
            case $this->bot->commpre . 'help':
            case $this->bot->commpre . 'bots':
            case $this->bot->commpre . 'bot':
            case $this->bot->commpre . 'up':			
                Break; //These should of been handled elsewere
            case 'is':
                $data->message = $this->bot->commpre . $data->message;
                $this->irc_is($irc, $data);
                Break;
            case 'alts':
                $data->message = $this->bot->commpre . $data->message;
                $this->alts_is($irc, $data);
                Break;				
            case 'online':
			case 'sm':
                $data->message = $this->bot->commpre . $data->message;
                $this->irc_online($irc, $data);
                Break;
            case 'whois':
                $data->message = $this->bot->commpre . $data->message;
                $this->irc_whois($irc, $data);
                Break;
            case 'level':
            case 'lvl':
            case 'pvp':
                $data->message = $this->bot->commpre . $data->message;
				if (strtolower($this->bot->game) == 'ao') {
					$this->irc_level($irc, $data);
				} else {
					$this->irc_default($irc, $data);
				}
                Break;
			case 'tara':
                $data->message = $this->bot->commpre . $data->message;
                $this->irc_tara($irc, $data);
                Break;
			case 'viza':
                $data->message = $this->bot->commpre . $data->message;
                $this->irc_viza($irc, $data);
                Break;
			case 'bots':			
			case 'bot':
			case 'up':
				$this->irc_up($irc);
                Break;				
			case 'help':
				$this->irc_help($irc);
                Break;
            Default:
                if ($data->type == SMARTIRC_TYPE_QUERY) {
                    $target = $data->nick;
                } else {
                    $target = $this->bot->core("settings")
                        ->get("Irc", "Channel");
                }
                $msg = "Error: Unknown Command " . $msg[0];
                $this->irc->message(SMARTIRC_TYPE_CHANNEL, $target, $msg);
        }
    }
}

?>
