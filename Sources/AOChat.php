<?php
/*
* AOChat, a PHP class for talking with the Age of Conan and Anarchy Online chat servers.
* It requires the sockets extension (to connect to the chat server..)
* from PHP 5.2.0+ and the BCMath extension (for generating
* and calculating the login keys) to work.
*
* Copyright (C) 2006-2012 J-Soft and the BeBot development team.
* Copyright (c) 2008 Allan Noer <allan@noer.biz>
* Copyright (C) 2002-2005  Oskari Saarenmaa <auno@auno.org>.
*
* This is an adapted version of Auno's original AOChat PHP class.
* This version has been adapted for use with pre PHP 5 versions aswell as other bugfixes
* by the community and BeBot development team.
*
* Age of Conan Changes:
* This was later ported and adjusted to the protocol used for the Age of Conan
* chat server. The original structure and variables has been re-used in order
* to make it very easy to port existing Anarchy Online projects to Age of
* Conan. Some things like the private channels are not currently supported
* by the Age of Conan game client, but still works on the server. The
* functions related to these has been kept in order secure backward compability.
*
* With AoC update 1.05 the login protocol changed so that the bot can not login
* directly to the chat server. It first has to login to the actual game world
* (universe) like a normal player. After that it discards packets from the
* game world and gets disconnected from it after a while, but stays in the chat
* server. Thanks go out to Chaoz from official AoC forums, who provided the patch.
*
* A disassembly of the official java chat client for Anarchy Online
* and Slicer's AO::Chat perl module were used as a reference for this
* class.
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307
* USA
*/
set_time_limit(0);
ini_set("html_errors", 0);

// Make sure we can handle BIGINT on 32bit systems
echo "Debug: PHP_INT_SIZE is ";
var_dump(PHP_INT_SIZE);
echo "\n";
if (PHP_INT_SIZE != 8) {
  $precision = ini_get('precision');
  echo "Debug: Precision is $precision\n";
  if ($precision <= 16) {
    if (!ini_set('precision', 16)) {
      die("On 32bit systems we need precision of 16 or greater and we where unable to raise the limit.\nPlease set precision in php.ini to 16.");
    }

    echo "Debug: Setting precision to 16...";
    $precision = ini_get('precision');

    if ($precision == 16) {
      echo "success\n";
    }
  }
}


/* Packet type definitions - so we won't have to use the number IDs
* .. I did not distinct between server and client message types, as
* they are mostly the same for same type packets, but maybe it should
* have been done anyway..  // auno - 2004/mar/26
*/

include "AocLogin/LoginServerConnection.php";
include "AocLogin/CharacterServerConnection.php";
define('AOCP_LOGIN_CHARID', 0);
define('AOCP_LOGIN_SEED', 0);
define('AOCP_LOGIN_REQUEST', 2);
define('AOCP_LOGIN_SELECT', 3);
define('AOCP_LOGIN_OK', 5);
define('AOCP_LOGIN_ERROR', 6);
define('AOCP_LOGIN_CHARLIST', 7);
define('AOCP_CLIENT_UNKNOWN', 10);
define('AOCP_CLIENT_NAME', 20);
define('AOCP_CLIENT_LOOKUP', 21);
define('AOCP_MSG_PRIVATE', 30);
define('AOCP_MSG_VICINITY', 34);
define('AOCP_MSG_VICINITYA', 35);
define('AOCP_MSG_SYSTEM', 36);
define('AOCP_CHAT_NOTICE', 37);
define('AOCP_BUDDY_LOGONOFF', 40); //Incoming
define('AOCP_BUDDY_ADD', 40); //Outgoing
define('AOCP_BUDDY_REMOVE', 41);
define('AOCP_ONLINE_SET', 42);
define('AOCP_PRIVGRP_INVITE', 50);
define('AOCP_PRIVGRP_KICK', 51);
define('AOCP_PRIVGRP_JOIN', 52);
define('AOCP_PRIVGRP_PART', 53);
define('AOCP_PRIVGRP_KICKALL', 54);
define('AOCP_PRIVGRP_CLIJOIN', 55);
define('AOCP_PRIVGRP_CLIPART', 56);
define('AOCP_PRIVGRP_MESSAGE', 57);
define('AOCP_PRIVGRP_REFUSE', 58);
define('AOCP_GROUP_ANNOUNCE', 60);
define('AOCP_GROUP_PART', 61);
define('AOCP_GROUP_DATA_SET', 64);
define('AOCP_GROUP_MESSAGE', 65);
define('AOCP_GROUP_CM_SET', 66);
define('AOCP_CLIENTMODE_GET', 70);
define('AOCP_CLIENTMODE_SET', 71);
define('AOCP_PING', 100);
define('AOCP_FORWARD', 110);
define('AOCP_CC', 120);
define('AOCP_ADM_MUX_INFO', 1100);
define('AOCP_GROUP_JOIN', AOCP_GROUP_ANNOUNCE); /* compat */
define('AOC_GROUP_NOWRITE', 0x00000002);
define('AOC_GROUP_NOASIAN', 0x00000020);
define('AOC_GROUP_MUTE', 0x01010000);
define('AOC_GROUP_LOG', 0x02020000);
define('AOC_BUDDY_KNOWN', 0x01);
define('AOC_BUDDY_ONLINE', 0x02);
define('AOC_FLOOD_LIMIT', 7);
define('AOC_FLOOD_INC', 2);
define('AOC_PRIORITY_HIGH', 1000);
define('AOC_PRIORITY_MED', 500);
define('AOC_PRIORITY_LOW', 100);
define('AOEM_UNKNOWN', 0xFF);
define('AOEM_ORG_JOIN', 0x10);
define('AOEM_ORG_KICK', 0x11);
define('AOEM_ORG_LEAVE', 0x12);
define('AOEM_ORG_DISBAND', 0x13);
define('AOEM_ORG_FORM', 0x14);
define('AOEM_ORG_VOTE', 0x15);
define('AOEM_ORG_STRIKE', 0x16);
define('AOEM_NW_ATTACK', 0x20);
define('AOEM_NW_ABANDON', 0x21);
define('AOEM_NW_OPENING', 0x22);
define('AOEM_NW_TOWER_ATT_ORG', 0x23);
define('AOEM_NW_TOWER_ATT', 0x24);
define('AOEM_NW_TOWER', 0x25);
define('AOEM_AI_CLOAK', 0x30);
define('AOEM_AI_RADAR', 0x31);
define('AOEM_AI_ATTACK', 0x32);
define('AOEM_AI_REMOVE_INIT', 0x33);
define('AOEM_AI_REMOVE', 0x34);
define('AOEM_AI_HQ_REMOVE_INIT', 0x35);
define('AOEM_AI_HQ_REMOVE', 0x36);


class AOChat
{
  var $state, $debug, $gid, $chars, $char, $grp, $buddies;
  var $socket, $last_packet, $last_ping;
  public static $instance;

  /* Initialization */
  private function __construct($bothandle)
  {
    $this->bot = Bot::get_instance($bothandle);
    $this->bothandle = $bothandle;
    $this->game = $this->bot->game;
    $this->disconnect();
    $this->login_num = 0;
    /*
    Check if we are running on a 64bit system or not
    */
    if (PHP_INT_SIZE == 4) {
      $phpbit = "32 bit";
      $this->sixtyfourbit = false;
    }
    else
    {
      $phpbit = "64 bit";
      $this->sixtyfourbit = true;
    }
    $this->bot->log("MAIN", "START", "PHP install detected as being $phpbit");
  }

  public function get_instance($bothandle)
  {
    if (!isset(self::$instance[$bothandle])) {
      $class = __CLASS__;
      self::$instance[$bothandle] = new $class($bothandle);
    }
    return self::$instance[$bothandle];
  }

  function disconnect()
  {
    if (is_resource($this->socket)) {
      socket_close($this->socket);
    }
    $this->socket = NULL;
    $this->serverseed = NULL;
    $this->chars = NULL;
    $this->char = NULL;
    $this->last_packet = 0;
    $this->last_ping = 0;
    $this->state = "connect";
    $this->gid = array();
    $this->grp = array();
    $this->chars = array();
    $this->buddies = array();
  }

  /* Network stuff */
  function connect($server = "default", $port = "default")
  {
    if ($this->game == "ao") {
      if ($server == "default") {
        $server = "chat2.d1.funcom.com";
      }
      if ($port == "default") {
        $port = 7012;
      }
    }
    if ($this->state !== "connect") {
      die("AOChat: not expecting connect.\n");
    }
    $s = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!is_resource($s)) /* this is fatal */ {
      die("Could not create socket.\n");
    }
    $this->socket = $s;
    $this->state = "auth";
    if (@socket_connect($s, $server, $port) === false) {
      trigger_error("Could not connect to the " . strtoupper($this->game) . " Chat server ($server:$port): " . socket_strerror(socket_last_error($s)), E_USER_WARNING);
      $this->disconnect();
      return false;
    }
    /* For AO we expect the login seed when we connect to the chatserver */
    if ($this->game == "ao") {
      $packet = $this->get_packet();
      if (!is_object($packet) || $packet->type != AOCP_LOGIN_SEED) {
        trigger_error("Received invalid greeting packet from " . strtoupper($this->game) . " Chat server.", E_USER_WARNING);
        $this->disconnect();
        return false;
      }
    }
    return $s;
  }

  /*
  Connecting to the universe function
  */
  function authenticateConan($serverAddress, $serverPort, $username, $password, $character, $sixtyfourbit)
  {
    $this->accountid = 0;
    $this->serverseed = NULL;
    $this->ServerAddress = "";
    $this->ServerPort = 0;
    $this->username = $username;
    $this->character = $character;
    $this->password = $password;
    $this->sixtyfourbit = $sixtyfourbit;
    //
    // Connect to the login server and log in with the username and password
    //
    $loginServer = new LoginServerConnection($this, $username, $password, $serverAddress, $serverPort, LOGIN_TYPE_PROTOBUF);
    if (!$loginServer->Connect()) {
      trigger_error("Could not connect to the Loginserver (" . $serverAddress . ":" . $serverPort . ")");
      return false;
    }
    if (!$loginServer->HandlePackets()) {
      $loginServer->Disconnect("Error while handling packets for loginserver");
      trigger_error("Error while handling packets for Loginserver (" . $serverAddress . ":" . $serverPort . ")");
      return false;
    }
    $loginServer->Disconnect("Done");

    //
    // Connect to the character server and log in the bot character
    //
    $characterServer = new CharacterServerConnection($this, $loginServer->GetAccountID(), $character, $loginServer->GetLoginCookie(), $loginServer->GetCharacterServerAddress(), $loginServer->GetCharacterServerPort(), $loginServer->GetEndpointType());
    if (!$characterServer->Connect()) {
      trigger_error("Could not connect to the Characterserver (" . $loginServer->GetCharacterServerAddress() . ":" . $loginServer->GetCharacterServerPort() . ")");
      return false;
    }
    if (!$characterServer->HandlePackets()) {
      $characterServer->Disconnect("Error while handling packets for characterserver");
      trigger_error("Error while handling packets for Characterserver (" . $loginServer->GetCharacterServerAddress() . ":" . $loginServer->GetCharacterServerPort() . ")");
      return false;
    }
    $characterServer->Disconnect("Done");


    // Make sure we give this to the main program
    $this->accountid = $loginServer->GetAccountID();
    $this->serverseed = $characterServer->GetChatServerCookie();
    $this->ServerAddress = $characterServer->GetChatServerAddress();
    $this->ServerPort = $characterServer->GetChatServerPort();

    // Resolve the login character
    $this->char = $this->getLoginCharacter($this->character);

    // Connect to the chat server
    $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!is_resource($this->socket)) /* this is fatal */ {
      die("Could not create socket.\n");
    }
    if (@socket_connect($this->socket, $this->ServerAddress, $this->ServerPort) === false) {
      trigger_error("Could not connect to the " . strtoupper($this->game) . " Chatserver (" . $this->ServerAddress . ":" . $this->ServerPort . ")" . socket_strerror(socket_last_error($this->socket)), E_USER_WARNING);
      $this->disconnect();
      return false;
    }

    // Prepare the login packet and send it
    if ($this->char["id"] != 0 && $this->serverseed != 0) {
      $this->login_num++;

      $loginCharacterPacket = new AOChatPacket("out", AOCP_LOGIN_CHARID, array(1,
                                                                               $this->char["id"],
                                                                               $this->serverseed,
                                                                               $this->char["language"]));
      $this->send_packet($loginCharacterPacket);
      $this->state = "connected";
      return true;
    }

    if ($this->serverseed != 0) {
      trigger_error("Could not connect to the " . strtoupper($this->game) . " Chatserver (" . $this->ServerAddress . ":" . $this->ServerPort . ") Character array/id was missing.\n");
    }
    else
    {
      trigger_error("Could not connect to the " . strtoupper($this->game) . " Chatserver (" . $this->ServerAddress . ":" . $this->ServerPort . ") Login cookie was missing.\n");
    }
    return false;
  }


  // Resolve the characterid
  function getLoginCharacter($char)
  {
    // Check if we have been given a character id or character name
    if (is_int($char)) {
      $field = "id";
    }
    else if (is_string($char)) {
      $field = "name";
      $char = ucfirst(strtolower($char));
    }
    else
    {
      return 0;
    }
    // Make sure we have a valid character to login
    if (!is_array($char)) {
      foreach ($this->chars as $e)
      {
        if ($e[$field] == $char) {
          return $e;
        }
      }
    }
    return 0;
  }

  /*
  Authentication function
  */
  function authenticate($username, $password)
  {
    // If we are not authenticating, bail
    if ($this->state != "auth") {
      die("AOChat: not expecting authentication.\n");
    }
    $key = $this->generate_login_key($this->serverseed, $username, $password);
    // Prepare and send the login packet.
    $pak = new AOChatPacket("out", AOCP_LOGIN_REQUEST, array(0,
                                                             $username,
                                                             $key));
    $this->send_packet($pak);
    $packet = $this->get_packet();
    // If we receive anything but the character list, something's wrong.
    if ($packet->type != AOCP_LOGIN_CHARLIST) {
      die("AOChat: {$packet->args[0]}\n");
    }
    // Prepare an array of all characters returned
    for ($i = 0; $i < sizeof($packet->args[0]); $i++)
    {
      $this->chars[] = array("id" => $packet->args[0][$i],
                             "name" => ucfirst(strtolower($packet->args[1][$i])),
                             "level" => $packet->args[2][$i],
                             "online" => $packet->args[3][$i]);
    }
    $this->username = $username;
    // Authentication successfull, we are now logging in
    $this->state = "login";
    return $this->chars;
  }

  /*
  Login Function

  This function gets called after we have successfully authenticated and are ready to select a character to login.
  */
  function login($char)
  {
    // If we have not authenticated, bail
    if ($this->state != "login") {
      if ($this->login_num >= 1) {
        die("AOChat: authentication failed. Keygeneration failure likely\n");
      }
      else
      {
        die("AOChat: not expecting login.\n");
      }
    }
    // Allows us to catch if we've been here before and failed.
    $this->login_num += 1;
    // Check if we have been given a character id or character name
    if (is_int($char)) {
      $field = "id";
    }
    else if (is_string($char)) {
      $field = "name";
      $char = ucfirst(strtolower($char));
    }
    // Make sure we have a valid character to login
    if (!is_array($char)) {
      if (empty($field)) {
        return false;
      }
      else
      {
        foreach ($this->chars as $e)
        {
          if ($e[$field] == $char) {
            $char = $e;
            break;
          }
        }
      }
    }
    if (!is_array($char)) {
      die("AOChat: no valid character to login.\n");
    }

    // Prepare the login packet and send it
    $pq = new AOChatPacket("out", AOCP_LOGIN_SELECT, $char["id"]);
    $this->send_packet($pq);
    $pr = $this->get_packet();

    // Check if login was successfull.
    if ($pr->type != AOCP_LOGIN_OK) {
      return false;
    }

    $this->char = $char;
    // We are authenticated and logged in. Everything is ok.
    $this->state = "ok";
    return true;
  }

  function wait_for_packet($time = 1)
  {
    $b = array();
    $c = array();
    $sec = (int)$time;
    if (is_float($time)) {
      $usec = (int)($time * 1000000 % 1000000);
    }
    else
    {
      $usec = 0;
    }
    $a = array($this->socket);
    if (!socket_select($a, $b, $c, $sec, $usec)) {
      if ((time() - $this->last_packet) > 60) {
        if ((time() - $this->last_ping) > 60) {
          $this->send_ping();
        }
      }
      return NULL;
    }
    return $this->get_packet();
  }

  /**
   * Function waits for maximal $time seconds (can be float) for a packet of type $type
   * that corresponds to the given arguments given in $arg.
   *
   * The function returns when the time is up, when an error occured (disconnect), or
   * when the awaited packed was received, whatever comes first. All packets, including
   * the one we are waiting for, are processed normally, except that this function
   * MUST NO be called recursively.
   *
   * The concept used here becomes probably deprecated, once the dispatcher concept
   * is fully used.
   *
   * @param $type the type of packet, we are waiting for, e.g. AOCP_CLIENT_LOOKUP
   * @param $args array of packet arguments to match. Order is important. NULL values are skipped.
   * @param $time maximum time in seconds to wait for the packet, can be float including micro seconds
   * @return the packet we are waiting for, or FALSE if the packet didn't arrive,
   *         or "disconnected" if connection was disconnected
   */
  function wait_for_certain_packet($type, $args = array(), $time = 5)
  {
    // Prevent this function from being called recursively
    static $already_running = false;
    if ($already_running) {
      $this->bot->log("NETWORK", "ERROR", "AOChat::wait_for_certain_packet() called recursively! Don't do that!");
      $this->bot->log("DEBUG", "AOChat", $this->bot->debug_bt());
      return false;
    }
    $already_running = true;

    // Save start time
    $time_left = $time;
    list($usec, $sec) = explode(" ", microtime());
    $start_time = (float)$usec + (float)$sec;
    while ($time_left > 0)
    {
      // Call the cron job to let timed things happen on time
      $this->bot->cron();

      // Wait for a packed max. 1 second
      $packet = $this->wait_for_packet(($time_left > 1 ? 1 : $time_left));

      // Check if connection was lost --> return
      if ($packet == "disconnected") {
        $already_running = false;
        return "disconnected";
      }

      // Check if this packet is the one we are looking for --> return
      if (($packet instanceof AOChatPacket) && ($packet->type == $type)) {
        $args_match = true;
        for ($i = 0; $i < count($packet->args); $i++)
        {
          if ($args[$i] !== NULL && $packet->args[$i] != $args[$i]) {
            $args_match = false;
          }
        }
        if ($args_match) {
          $already_running = false;
          return $packet;
        }
      }

      // Calculate time left for next cycle
      list($usec, $sec) = explode(" ", microtime());
      $current_time = (float)$usec + (float)$sec;
      $time_left = (float)$time - ($current_time - $start_time);
    }
    $already_running = false;
    return false;
  }

  /**
   * Process packets and cron timers until a buddy add was confirmend by the server.
   * @param $uid uder id of the user that just got added
   */
  function wait_for_buddy_add($uid)
  {
    if ($uid === $this->char['id']) {
      return;
    } // Bot can't add itself to buddy list, so waiting would be useless.
    $args = array($uid);
    $this->wait_for_certain_packet(AOCP_BUDDY_LOGONOFF, $args);
  }

  /**
   * Process packets and cron timers until a lookup answer for a specific user name was returned.
   * @param $uname user name of the user, for which we just asked the server to lookup
   */
  function wait_for_lookup_user($uname)
  {
    $args = array(NULL,
                  $uname);
    return $this->wait_for_certain_packet(AOCP_CLIENT_LOOKUP, $args);
  }

  function read_data($len)
  {
    $data = "";
    $rlen = $len;
    while ($rlen > 0)
    {
      if (($tmp = socket_read($this->socket, $rlen)) === false) {
        if (!is_resource($this->socket)) {
          $this->disconnect();
          die("Read error: $last_error\n");
        }
        else
        {
          printf("Read error: %s\n", socket_strerror(socket_last_error($this->socket)));
          return "";
        }
      }
      if ($tmp == "") {
        echo ("Read error: EOF\n");
        if (!is_resource($this->socket)) {
          $this->disconnect();
          die("Read error: Too many EOF errors, disconnecting.\n");
        }
        else
        {
          return "";
        }
      }
      $data .= $tmp;
      $rlen -= strlen($tmp);
    }
    return $data;
  }

  function get_packet()
  {
    // Get the bot instance
    $bot = Bot::get_instance($this->bothandle);
    // Include a the signal_message (Should probably be included somewhere else)
    //require_once ('Dispatcher/signal_message.php');
    $head = $this->read_data(4);
    if (strlen($head) != 4) {
      return "disconnected";
    }
    list (, $type, $len) = unpack("n2", $head);
    $data = $this->read_data($len);
    // For AOC they are not sending the OK packet anymore
    // So when you receive the first packet, you are logged in
    if ($this->game == "aoc" && $this->state != "ok") {
      // $bot->log("LOGIN", "RESULT", "Bot is now loggend in.");
      $this->state = "ok";
    }
    if (is_resource($this->debug)) {
      fwrite($this->debug, "<<<<<\n");
      fwrite($this->debug, $head);
      fwrite($this->debug, $data);
      fwrite($this->debug, "\n=====\n");
    }
    $packet = new AOChatPacket("in", $type, $data);
    $bot->cron();
    switch ($type)
    {
      // system
      case AOCP_LOGIN_SEED:
        $this->serverseed = $packet->args[0];
        break;
      case AOCP_LOGIN_OK:
        $bot->log("LOGIN", "RESULT", "OK");
        break;
      case AOCP_GROUP_ANNOUNCE:
        list ($gid, $name, $status) = $packet->args;
        //$signal = new signal_message('aochat', $gid, $name);
        //$dispatcher->post($signal, 'onGroupAnnounce');
        //unset($signal);
        $event = new sfEvent($this, 'core.on_group_announce', array('source' => $gid,
                                                                    'message' => $name,
                                                                    'status' => $status));
        $this->bot->dispatcher->notify($event);

        // TODO: Group caching should most likely be done somewhere else.
        $this->grp[$gid] = $status;
        $this->gid[$gid] = $name;
        $this->gid[strtolower($name)] = $gid;
        // Deprecated call: Should listen to the event already sendt.
        $bot->inc_gannounce($packet->args);
        break;
      // invites
      case AOCP_PRIVGRP_INVITE:
        // Event is a privgroup invite
        list ($gid) = $packet->args;
        //$signal = new signal_message('aochat', $gid, 'invite');
        //$dispatcher->post($signal, 'onGroupInvite');

        $event = new sfEvent($this, 'core.on_group_invite', array('source' => $gid,
                                                                  'message' => 'invite'));
        $this->bot->dispatcher->notify($event);

        // Deprecated call: Should listen to the signal already sendt.
        $bot->inc_pginvite($packet->args);
        break;
      // buddy/player
      case AOCP_CLIENT_NAME:
        // Cross-game compatibility
        if ($this->game == "aoc") {
          list ($id, $unknown, $name) = $packet->args;
        }
        else
        {
          list ($id, $name) = $packet->args;
        }
        $name = ucfirst(strtolower($name));

        //$signal = new signal_message('aochat', 'bot', array($id , $name));
        //$dispatcher->post($signal, 'onPlayerName');
        //unset($signal);

        $event = new sfEvent($this, 'core.on_player_name', array('id' => $id,
                                                                 'name' => $name));
        $this->bot->dispatcher->notify($event);

        break;
      case AOCP_CLIENT_LOOKUP:
        list ($id, $name) = $packet->args;
        $name = ucfirst(strtolower($name));

        //$signal = new signal_message('aochat', 'bot', array($id , $name));
        //$dispatcher->post($signal, 'onPlayerName');
        //unset($signal);

        // We need to make sure we catch 4294967295
        if ($id > 4294967294 && $id < 4294967296) {
          $id = -1;
        }

        echo "Debug: Firing event core.on_player_id ($id, $name)\n";

        $event = new sfEvent($this, 'core.on_player_id', array('id' => $id,
                                                               'name' => $name));
        $this->bot->dispatcher->notify($event);

        break;
      case AOCP_BUDDY_LOGONOFF:
        // Event is a buddy logging on/off
        list ($id, $status) = $packet->args;

        if ($this->game == "aoc") {
          list ($bid, $bonline, $blevel, $blocation, $bclass) = $packet->args;
          $this->buddies[$bid] = ($bonline ? AOC_BUDDY_ONLINE : 0) | AOC_BUDDY_KNOWN;
          $event = new sfEvent($this, 'core.on_buddy_onoff', array('id' => $bid,
                                                                   'online' => $bonline,
                                                                   'level' => $blevel,
                                                                   'location' => $blocation,
                                                                   'class' => $bclass));
        }
        else
        {
          list ($bid, $bonline, $btype) = $packet->args;
          $this->buddies[$bid] = ($bonline ? AOC_BUDDY_ONLINE : 0) | (ord($btype) ? AOC_BUDDY_KNOWN : 0);
          $event = new sfEvent($this, 'core.on_buddy_onoff', array('id' => $bid,
                                                                   'online' => $bonline,
                                                                   'type' => $btype));
        }

        //$signal = new signal_message('aochat', $id, $status);
        //if ($status)
        //{
        //	$dispatcher->post($signal, 'onBuddyJoin');
        //}
        //else
        //{
        //	$dispatcher->post($signal, 'onBuddyLeave');
        //}
        //unset($signal);

        $this->bot->dispatcher->notify($event);


        // Deprecated call. Should listen to the signal already sendt.
        //$bot->inc_buddy($packet->args);
        break;
      case AOCP_BUDDY_REMOVE:
//				$signal = new signal_message('aochat', 'system', $packet->args[0]);
//				$dispatcher->post($signal, 'onBuddyRemove');
//				unset($signal);

        $event = new sfEvent($this, 'core.on_buddy_remove', array('source' => 'system',
                                                                  'message' => $pakcte->args[0]));
        $this->bot->dispatcher->notify($event);

        // TODO: This should probably be cached somewhere else.
        unset($this->buddies[$packet->args[0]]);
        break;
      case AOCP_LOGIN_ERROR:
        $this->state = "disconnected";
        if ($this->game == "aoc" && $this->login_num >= 1 && $this->login_num < 3) {
          // Up this
          $this->bot->log("LOGIN", "ERROR", "Received login error. Retrying ...");
          $this->login_num++;
          // Disconnect from the territoryserver
          if (is_resource($this->socket)) {
            socket_close($this->socket);
          }
          // Connect to the chat server
          $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
          if (!is_resource($this->socket)) /* this is fatal */ {
            die("Could not create socket.\n");
          }
          if (@socket_connect($this->socket, $this->ServerAddress, $this->ServerPort) === false) {
            trigger_error("Could not connect to the " . strtoupper($this->game) . " Chatserver (" . $this->ServerAddress . ":" . $this->ServerPort . ")" . socket_strerror(socket_last_error($s)), E_USER_WARNING);
            $this->disconnect();
            return false;
          }
          // echo "Resending auth to chatserver [Character:" . $this->char["name"] . ", id:" . $this->char["id"] . "]\n";
          $this->state = "connected";
          $loginCharacterPacket = new AOChatPacket("out", AOCP_LOGIN_CHARID, array(1,
                                                                                   $this->char["id"],
                                                                                   $this->serverseed,
                                                                                   "en"));
          $this->send_packet($loginCharacterPacket);
        }
        break;
      case AOCP_PRIVGRP_CLIJOIN:
        // Event is someone joining the privgroup
        // Deprecated call. Should listen to the signal already sendt.
        list ($id, $name) = $packet->args;

        //$signal = new signal_message('aochat', $id, 'join');
        //$dispatcher->post($signal, 'onPgJoin');
        //unset($signal);

        $event = new sfEvent($this, 'core.on_privgroup_join', array('source' => $id,
                                                                    'message' => 'join'));
        $this->bot->dispatcher->notify($event);


        // Deprecated call, Should listen to the signal already sendt.
        $bot->inc_pgjoin($packet->args);
        break;
      case AOCP_PRIVGRP_CLIPART:
        // Event is someone leaveing the privgroup
        list ($id, $name) = $packet->args;

        //$signal = new signal_message('aochat', $id, 'leave');
        //$dispatcher->post($signal, 'onPgLeave');
        //unset($signal);

        $event = new sfEvent($this, 'core.on_privgroup_leave', array('source' => $id,
                                                                     'message' => 'leave'));
        $this->bot->dispatcher->notify($event);

        // Deprecated call. Should listen to the signal already sendt.
        $bot->inc_pgleave($packet->args);
        break;
      // Messages
      case AOCP_MSG_PRIVATE:
        // Event is a tell
        // Tells should always be commands
        list ($id, $message) = $packet->args;

        //$signal = new signal_message('aochat', $id, $message);
        //$dispatcher->post($signal, 'onTell');
        //unset($signal);

        $event = new sfEvent($this, 'core.on_tell', array('source' => $id,
                                                          'message' => $message));
        $this->bot->dispatcher->notify($event);


        // Deprecated call. Should listen to the signal already sendt.
        $bot->inc_tell($packet->args);
        break;
      case AOCP_PRIVGRP_MESSAGE:
        // Event is a privgroup message
        list (, $id, $message) = $packet->args;
        //$signal = new signal_message('aochat', $id, $message);
        //$dispatcher->post($signal, 'onPgMessage');

        $event = new sfEvent($this, 'core.on_privgroup_message', array('source' => $id,
                                                                       'message' => $message));
        $this->bot->dispatcher->notify($event);


        // Check if this is a command
        // If it is not post it to all observers of the PRIVGRP_MESSAGE channel.
        // Deprecated call. Should listen to the signal already sendt.
        $bot->inc_pgmsg($packet->args);
        break;
      case AOCP_GROUP_MESSAGE:
        /* Hack to support extended messages */
        // This should be re-hacked so that we can handle the extmsgs here.
        if ($packet->args[1] === 0 && substr($packet->args[2], 0, 2) == "~&") {
          $em = new AOExtMsg($packet->args[2]);
          if ($em->type != AOEM_UNKNOWN) {
            $packet->args[2] = $em->text;
            $packet->args[] = $em;
          }
        }
        // Event is a group message (guildchat, towers etc)
        // Check if it is a command
        // If it is not post it to all observers of the GROUP_MESSAGE of the originating group
        // Deprecated call. Should listen to the signal already sendt.
        $bot->inc_gmsg($packet->args);
        break;
      // Events currently being debugged for possible inclusion
      case AOCP_MSG_VICINITYA:
        $bot->log("MAIN", "INC", "Vicinity announcement");
        if (is_resource($this->debug)) {
          fwrite($this->debug, "<<<<<\n");
          fwrite($this->debug, print_r($packet->args, TRUE));
          fwrite($this->debug, "\n=====\n");
        }
        break;
      // Events we ignore
      // some notice, e.g. after buddy add
      case AOCP_CHAT_NOTICE:
        // Character list upon login
      case AOCP_LOGIN_CHARLIST:
        // AO server pings
      case AOCP_PING:
        break;
      default:
        $bot->log("MAIN", "TYPE", "Unhandeled packet of type $type. Args: " . serialize($packet->args));
        if (is_resource($this->debug)) {
          fwrite($this->debug, "<<<<<\n");
          fwrite($this->debug, print_r($packet->args, TRUE));
          fwrite($this->debug, "\n=====\n");
        }
        break;
    }
    $this->last_packet = time();
    return $packet;
  }

  function send_packet($packet)
  {
    $data = pack("n2", $packet->type, strlen($packet->data)) . $packet->data;
    if (is_resource($this->debug)) {
      fwrite($this->debug, ">>>>>\n");
      fwrite($this->debug, $data);
      fwrite($this->debug, "\n=====\n");
    }
    socket_write($this->socket, $data, strlen($data));
    return true;
  }

  /* User and group lookup functions */
  function lookup_user($u)
  {
    //		$stack = array();
    $i = 0;
    $timeout = time() + 15;
    $p = FALSE;
    // put the user on the call stack.
    $u = ucfirst(strtolower($u));
    //		$timelimit = time() + $timeout;
    //		array_unshift($stack, array('user' => $u , 'timeout' => $timelimit));
    $pq = new AOChatPacket("out", AOCP_CLIENT_LOOKUP, $u);
    $this->send_packet($pq);

    while ($p == FALSE)
    {
      $i++;
      $pr = $this->get_packet();
      if ($pr->type == AOCP_CLIENT_LOOKUP) {
        $p = TRUE;
      }
      if ($timeout <= time()) {
        echo "Debug: lookup_user timed out while looking up $u\n";
        $p = TRUE;
      }
    }

    echo "Debug: lookup_user for $u completed in $i iterations\n";

    /*** FIXME ***/
    // This is really ugly, and we really need to detect if we receive a Client Lookup packet as the lookup could be negative or null.
    // In those cases this loop would run 200 times or for 15 seconds even if we have gotten a reply.
    // We now detect when we receive the AOCP_CLIENT_LOOKUP package so we don't loop uneccecary. Maybe add some error catching in the event we do complete 200 loops?
    /*** FIXME no. 2 ***/
    // Maybe the new function $this->wait_for_lookup_user(...) could be used.
    // But its still untested and forbidden recursive calls are likely!

    /*
    for ($i = 0; ($i < 200) && (!$this->bot->core('player')->exists($stack[0]['user'])) && ($stack[0]['timeout'] > time()) && (!$p); $i ++)
    {
        $pr = $this->get_packet();
        if ($pr->type == AOCP_CLIENT_LOOKUP)
        {
            $p = true;
        }
    }
    array_shift($stack);
    */

    if (!$this->bot->core('player')->exists($u)) {
      return false;
    }
  }

  function lookup_group($arg, $type = 0)
  {
    $is_gid = false;
    // This should probably be moved out of AOChat and into core/PlayerList.php
    if ($type && ($is_gid = (strlen($arg) === 5 && (ord($arg[0]) & ~0x80) < 0x10))) {
      return $arg;
    }
    if (!$is_gid) {
      $arg = strtolower($arg);
    }
    return isset($this->gid[$arg]) ? $this->gid[$arg] : false;
  }

  function get_gid($g)
  {
    // This should probably be moved out of AOChat and into core/PlayerList.php
    return $this->lookup_group($g, 1);
  }

  function get_gname($g)
  {
    // This should probably be moved out of AOChat and into core/GroupList.php
    if (($gid = $this->lookup_group($g, 1)) === false) {
      return false;
    }
    return $this->gid[$gid];
  }

  /* Sending various packets */
  function send_ping()
  {
    $this->last_ping = time();
    return $this->send_packet(new AOChatPacket("out", AOCP_PING, "AOChat.php"));
  }

  function send_tell($user, $msg, $blob = "\0")
  {
    if (!is_numeric($user)) {
      $uid = $this->bot->core('player')->id($user);
    }
    else
    {
      $uid = $user;
    }
    if ($uid instanceof BotError) {
      return false;
    }
    return $this->send_packet(new AOChatPacket("out", AOCP_MSG_PRIVATE, array($uid,
                                                                              $msg,
                                                                              $blob)));
  }

  /* General chat groups */
  function send_group($group, $msg, $blob = "\0")
  {
    if (($gid = $this->get_gid($group)) === false) {
      return false;
    }
    return $this->send_packet(new AOChatPacket("out", AOCP_GROUP_MESSAGE, array($gid,
                                                                                $msg,
                                                                                $blob)));
  }

  function group_join($group)
  {
    if (($gid = $this->get_gid($group)) === false) {
      return false;
    }
    return $this->send_packet(new AOChatPacket("out", AOCP_GROUP_DATA_SET, array($gid,
                                                                                 $this->grp[$gid] & ~AOC_GROUP_MUTE,
                                                                                 "\0")));
  }

  function group_leave($group)
  {
    if (($gid = $this->get_gid($group)) === false) {
      return false;
    }
    return $this->send_packet(new AOChatPacket("out", AOCP_GROUP_DATA_SET, array($gid,
                                                                                 $this->grp[$gid] | AOC_GROUP_MUTE,
                                                                                 "\0")));
  }

  function group_status($group)
  {
    if (($gid = $this->get_gid($group)) === false) {
      return false;
    }
    return $this->grp[$gid];
  }

  /* Private chat groups */
  function send_privgroup($group, $msg, $blob = "\0")
  {
    if (!is_numeric($group)) {
      $gid = $this->bot->core('player')->id($group);
    }
    else
    {
      $gid = $group;
    }
    if ($gid instanceof BotError) {
      return false;
    }
    return $this->send_packet(new AOChatPacket("out", AOCP_PRIVGRP_MESSAGE, array($gid,
                                                                                  $msg,
                                                                                  $blob)));
  }

  function privategroup_join($group)
  {
    $gid = $this->bot->core('player')->id($group);
    if ($uid instanceof BotError) {
      return false;
    }
    return $this->send_packet(new AOChatPacket("out", AOCP_PRIVGRP_JOIN, $gid));
  }

  function join_privgroup($group) /* Deprecated - 2004/Mar/26 - auno@auno.org */
  {
    return $this->privategroup_join($group);
  }

  function privategroup_leave($group)
  {
    $uid = $this->bot->core('player')->id($user);
    if ($uid instanceof BotError) {
      return false;
    }
    return $this->send_packet(new AOChatPacket("out", AOCP_PRIVGRP_PART, $gid));
  }

  function privategroup_invite($user)
  {
    $uid = $this->bot->core('player')->id($user);
    if ($uid instanceof BotError) {
      return false;
    }
    return $this->send_packet(new AOChatPacket("out", AOCP_PRIVGRP_INVITE, $uid));
  }

  function privategroup_kick($user)
  {
    $uid = $this->bot->core('player')->id($user);
    if ($uid instanceof BotError) {
      return false;
    }
    return $this->send_packet(new AOChatPacket("out", AOCP_PRIVGRP_KICK, $uid));
  }

  function privategroup_kick_all()
  {
    return $this->send_packet(new AOChatPacket("out", AOCP_PRIVGRP_KICKALL, 0));
  }

  /* Buddies */
  function buddy_add($user, $type = "\1")
  {
    if (is_numeric($user)) {
      $uid = $user;
    }
    else
    {
      $uid = $this->bot->core('player')->id($user);
    }
    if ($uid instanceof BotError) {
      return false;
    }
    if ($uid === $this->char['id']) {
      return false;
    }
    if ($this->game == "ao") {
      $uid = array($uid,
                   $type);
    }
    return $this->send_packet(new AOChatPacket("out", AOCP_BUDDY_ADD, $uid));
  }

  function buddy_remove($user)
  {
    $uid = $this->bot->core('player')->id($user);
    if ($uid instanceof BotError) {
      return false;
    }
    return $this->send_packet(new AOChatPacket("out", AOCP_BUDDY_REMOVE, $uid));
  }

  function buddy_remove_unknown()
  {
    if ($this->game == "ao") {
      $array = array("rembuddy",
                     "?");
    }
    else
    {
      $array = array(2,
                     "rembuddy",
                     "?");
    }
    return $this->send_packet(new AOChatPacket("out", AOCP_CC, array($array)));
  }

  function buddy_exists($who)
  {
    if (!is_numeric($who)) {
      $uid = $this->bot->core('player')->id($who);
    }
    else
    {
      $uid = $who;
    }
    if ($uid instanceof BotError) {
      return false;
    }
    if (!isset($this->buddies[$uid])) {
      return 0;
    }
    return $this->buddies[$uid];
  }

  function buddy_online($who)
  {
    return ($this->buddy_exists($who) & AOC_BUDDY_ONLINE) ? true : false;
  }

  /* Login key generation and encryption */
  function get_random_hex_key($bits)
  {
    $str = "";
    do
    {
      $str .= sprintf('%02x', $this->bot->core("tools")->my_rand(0, 0xff));
    }
    while (($bits -= 8) > 0);
    return $str;
  }

  function bighexdec($x)
  {
    if (substr($x, 0, 2) != "0x") {
      return $x;
    }
    $r = "0";
    for ($p = $q = strlen($x) - 1; $p >= 2; $p--)
    {
      $r = bcadd($r, bcmul(hexdec($x[$p]), bcpow(16, $q - $p)));
    }
    return $r;
  }

  function bigdechex($x)
  {
    $r = "";
    while ($x != "0")
    {
      $r = dechex(bcmod($x, 16)) . $r;
      $x = bcdiv($x, 16);
    }
    return $r;
  }

  function bcmath_powm($base, $exp, $mod)
  {
    $base = $this->bighexdec($base);
    $exp = $this->bighexdec($exp);
    $mod = $this->bighexdec($mod);
    if (function_exists("bcpowmod")) /* PHP5 finally has this */ {
      $r = bcpowmod($base, $exp, $mod);
      return $this->bigdechex($r);
    }
    $r = 1;
    $p = $base;
    while (true)
    {
      if (bcmod($exp, 2)) {
        $r = bcmod(bcmul($p, $r), $mod);
        $exp = bcsub($exp, "1");
        if (bccomp($exp, "0") == 0) {
          return $this->bigdechex($r);
        }
      }
      $exp = bcdiv($exp, 2);
      $p = bcmod(bcmul($p, $p), $mod);
    }
  }

  /*
  * This is 'half' Diffie-Hellman key exchange.
  * 'Half' as in we already have the server's key ($dhY)
  * $dhN is a prime and $dhG is generator for it.
  *
  * http://en.wikipedia.org/wiki/Diffie-Hellman_key_exchange
  */
  function generate_login_key($servkey, $username, $password)
  {
    $dhY = "0x9c32cc23d559ca90fc31be72df817d0e124769e809f936bc14360ff4bed758f260a0d596584eacbbc2b88bdd410416163e11dbf62173393fbc0c6fefb2d855f1a03dec8e9f105bbad91b3437d8eb73fe2f44159597aa4053cf788d2f9d7012fb8d7c4ce3876f7d6cd5d0c31754f4cd96166708641958de54a6def5657b9f2e92";
    $dhN = "0xeca2e8c85d863dcdc26a429a71a9815ad052f6139669dd659f98ae159d313d13c6bf2838e10a69b6478b64a24bd054ba8248e8fa778703b418408249440b2c1edd28853e240d8a7e49540b76d120d3b1ad2878b1b99490eb4a2a5e84caa8a91cecbdb1aa7c816e8be343246f80c637abc653b893fd91686cf8d32d6cfe5f2a6f";
    $dhG = "0x5";
    $dhx = "0x" . $this->get_random_hex_key(256);
    $dhX = $this->bcmath_powm($dhG, $dhx, $dhN);
    $dhK = $this->bcmath_powm($dhY, $dhx, $dhN);
    $str = sprintf("%s|%s|%s", $username, $servkey, $password);
    if (strlen($dhK) < 32) {
      $dhK = str_repeat("0", 32 - strlen($dhK)) . $dhK;
    }
    else
    {
      $dhK = substr($dhK, 0, 32);
    }
    $prefix = pack("H16", $this->get_random_hex_key(64));
    $length = 8 + 4 + strlen($str); /* prefix, int, ... */
    $pad = str_repeat(" ", (8 - $length % 8) % 8);
    $strlen = pack("N", strlen($str));
    $plain = $prefix . $strlen . $str . $pad;
    $crypted = $this->aochat_crypt($dhK, $plain);
    return $dhX . "-" . $crypted;
  }

  /*
  * Takes a number and reduces it to a 32-bit value. The 32-bits
  * remain a binary equivalent of 32-bits from the previous number.
  * If the sign bit is set, the result will be negative, otherwise
  * the result will be zero or positive.
  * Function by: Feetus of RK1
  */
  function ReduceTo32Bit($value)
  {
    // If its negative, lets go positive ... its easier to do everything as positive.
    if ($this->big_cmp($value, 0) == -1) {
      $value = $this->NegativeToUnsigned($value);
    }
    $bit = 0x80000000;
    $bits = array();
    // Find the largest bit contained in $value above 32-bits
    while ($this->big_cmp($value, $bit) > -1)
    {
      $bit = $this->big_mul($bit, 2);
      $bits[] = $bit;
    }
    // Subtract out bits above 32 from $value
    while (NULL != ($bit = array_pop($bits)))
    {
      if ($this->big_cmp($value, $bit) >= 0) {
        $value = $this->big_sub($value, $bit);
      }
    }
    // Make negative if sign-bit is set in 32-bit value
    if ($this->big_cmp($value, 0x80000000) != -1) {
      $value = $this->big_sub($value, 0x80000000);
      $value -= 0x80000000;
    }
    return $value;
  }

  /*
  * This function returns the binary equivalent postive integer to a given negative
  * integer of arbitrary length. This would be the same as taking a signed negative
  * number and treating it as if it were unsigned. To see a simple example of this
  * on Windows, open the Windows Calculator, punch in a negative number, select the
  * hex display, and then switch back to the decimal display.
  */
  function NegativeToUnsigned($value)
  {
    if ($this->big_cmp($value, 0) != -1) {
      return $value;
    }
    $value = $this->big_mul($value, -1);
    $higherValue = 0xFFFFFFFF;
    // We don't know how many bytes the integer might be, so
    // start with one byte and then grow it byte by byte until
    // our negative number fits inside it. This will make the resulting
    // positive number fit in the same number of bytes.
    while ($this->big_cmp($value, $higherValue) == 1)
    {
      $higherValue = $this->big_add($this->big_mul($higherValue, 0x100), 0xFF);
    }
    $value = $this->big_add($this->big_sub($higherValue, $value), 1);
    return $value;
  }

  // On linux systems, unpack("H*", pack("L*", <value>)) returns differently than on Windows.
  // This can be used instead of unpack/pack to get the value we need.
  function SafeDecHexReverseEndian($value)
  {
    $result = "";
    if (!$this->sixtyfourbit) {
      $hex = dechex($this->ReduceTo32Bit($value));
      $len = strlen($hex);
      while ($len < 8)
      {
        $hex = "0$hex";
        $len++;
      }
      $bytes = str_split($hex, 2);
    }
    else
    {
      $bytes = unpack("H*", pack("L*", $value));
    }
    for ($i = 3; $i >= 0; $i--)
    {
      $result .= $bytes[$i];
    }
    return $result;
  }

  /*
  * Wrapper for bccomp for easy adding of other bignum library support
  */
  function big_cmp($a, $b)
  {
    $return = bccomp($a, $b);
    return $return;
  }

  /*
  * Wrapper for bcmul for easy adding of other bignum library support
  */
  function big_mul($a, $b)
  {
    $return = bcmul($a, $b);
    return $return;
  }

  /*
  * Wrapper for bcadd for easy adding of other bignum library support
  */
  function big_add($a, $b)
  {
    $return = bcadd($a, $b);
    return $return;
  }

  /*
  * Wrapper for bcsub for easy adding of other bignum library support
  */
  function big_sub($a, $b)
  {
    $return = bcsub($a, $b);
    return $return;
  }

  function aochat_crypt($key, $str)
  {
    if (strlen($key) != 32) {
      return false;
    }
    if (strlen($str) % 8 != 0) {
      return false;
    }
    $now = array(0,
                 0);
    $prev = array(0,
                  0);
    $ret = "";
    $keyarr = unpack("L*", pack("H*", $key));
    $dataarr = unpack("L*", $str);
    for ($i = 1; $i <= sizeof($dataarr); $i += 2)
    {
      $now[0] = (int)$this->ReduceTo32Bit($dataarr[$i]) ^ (int)$this->ReduceTo32Bit($prev[0]);
      $now[1] = (int)$this->ReduceTo32Bit($dataarr[$i + 1]) ^ (int)$this->ReduceTo32Bit($prev[1]);
      $prev = $this->aocrypt_permute($now, $keyarr);
      $ret .= $this->SafeDecHexReverseEndian($prev[0]);
      $ret .= $this->SafeDecHexReverseEndian($prev[1]);
    }
    return $ret;
  }

  function aocrypt_permute($x, $y)
  {
    $a = $x[0];
    $b = $x[1];
    $c = 0;
    $d = (int)0x9e3779b9;
    for ($i = 32; $i-- > 0;)
    {
      $c = (int)$this->ReduceTo32Bit($c + $d);
      $a += (int)$this->ReduceTo32Bit((int)$this->ReduceTo32Bit(((int)$this->ReduceTo32Bit($b) << 4 & -16) + $y[1]) ^ (int)$this->ReduceTo32Bit($b + $c)) ^ (int)$this->ReduceTo32Bit(((int)$this->ReduceTo32Bit($b) >> 5 & 134217727) + $y[2]);
      $b += (int)$this->ReduceTo32Bit((int)$this->ReduceTo32Bit(((int)$this->ReduceTo32Bit($a) << 4 & -16) + $y[3]) ^ (int)$this->ReduceTo32Bit($a + $c)) ^ (int)$this->ReduceTo32Bit(((int)$this->ReduceTo32Bit($a) >> 5 & 134217727) + $y[4]);
    }
    return array($a,
                 $b);
  }
}


/* The AOChatPacket class - turning packets into binary blobs and binary
* blobs into packets
*/

/*
* I - 32 bit integer: uint32_t
* S - 8 bit string array: uint16_t length, char str[length]
* G - 40 bit binary data: unsigned char data[5]
* i - integer array: uint16_t count, uint32_t[count]
* s - string array: uint16_t count, aochat_str_t[count]
*
* D - 'data', we have relabeled all 'D' type fields to 'S'
* M - mapping [see t.class in ao_nosign.jar] - unsupported
*
*/

class AOChatPacket
{
  var $args, $type, $dir, $data;

  function AOChatPacket($dir, $type, $data)
  {
    //This is a hack that should be done better. I'm just not sure how.
    if (strtolower(AOCHAT_GAME) == "ao") {
      $aocpdifs = array("IS",
                        "IIS",
                        "IS",
                        "s");
    }
    else
    {
      $aocpdifs = array("IIS",
                        "IBBIB",
                        "I",
                        "ISS");
    }
    $GLOBALS["aochat-packetmap"] = array(
      "in" => array(
        AOCP_LOGIN_SEED => array("name" => "Login Seed",
                                 "args" => "S"),
        AOCP_LOGIN_OK => array("name" => "Login Result OK",
                               "args" => ""),
        AOCP_LOGIN_ERROR => array("name" => "Login Result Error",
                                  "args" => "S"),
        AOCP_LOGIN_CHARLIST => array("name" => "Login CharacterList",
                                     "args" => "isii"),
        AOCP_CLIENT_UNKNOWN => array("name" => "Client Unknown",
                                     "args" => "I"),
        AOCP_CLIENT_NAME => array("name" => "Client Name",
                                  "args" => $aocpdifs[0]),
        AOCP_CLIENT_LOOKUP => array("name" => "Lookup Result",
                                    "args" => "IS"),
        AOCP_MSG_PRIVATE => array("name" => "Message Private",
                                  "args" => "ISS"),
        AOCP_MSG_VICINITY => array("name" => "Message Vicinity",
                                   "args" => "ISS"),
        AOCP_MSG_VICINITYA => array("name" => "Message Anon Vicinity",
                                    "args" => "SSS"),
        AOCP_MSG_SYSTEM => array("name" => "Message System",
                                 "args" => "S"),
        AOCP_CHAT_NOTICE => array("name" => "Chat Notice",
                                  "args" => "IIIS"),
        AOCP_BUDDY_ADD => array("name" => "Buddy Added",
                                "args" => $aocpdifs[1]),
        AOCP_BUDDY_REMOVE => array("name" => "Buddy Removed",
                                   "args" => "I"),
        AOCP_PRIVGRP_INVITE => array("name" => "Privategroup Invited",
                                     "args" => "I"),
        AOCP_PRIVGRP_KICK => array("name" => "Privategroup Kicked",
                                   "args" => "I"),
        AOCP_PRIVGRP_PART => array("name" => "Privategroup Part",
                                   "args" => "I"),
        AOCP_PRIVGRP_CLIJOIN => array("name" => "Privategroup Client Join",
                                      "args" => "II"),
        AOCP_PRIVGRP_CLIPART => array("name" => "Privategroup Client Part",
                                      "args" => "II"),
        AOCP_PRIVGRP_MESSAGE => array("name" => "Privategroup Message",
                                      "args" => "IISS"),
        AOCP_PRIVGRP_REFUSE => array("name" => "Privategroup Refuse Invite",
                                     "args" => "II"),
        AOCP_GROUP_ANNOUNCE => array("name" => "Group Announce",
                                     "args" => "GSIS"),
        AOCP_GROUP_PART => array("name" => "Group Part",
                                 "args" => "G"),
        AOCP_GROUP_MESSAGE => array("name" => "Group Message",
                                    "args" => "GISS"),
        AOCP_PING => array("name" => "Pong",
                           "args" => "S"),
        AOCP_FORWARD => array("name" => "Forward",
                              "args" => "IM"),
        AOCP_ADM_MUX_INFO => array("name" => "Adm Mux Info",
                                   "args" => "iii")),
      "out" => array(
        AOCP_LOGIN_CHARID => array("name" => "Login CharacterID",
                                   "args" => "IIIS"),
        AOCP_LOGIN_REQUEST => array("name" => "Login Response GetCharLst",
                                    "args" => "ISS"),
        AOCP_LOGIN_SELECT => array("name" => "Login Select Character",
                                   "args" => "I"),
        AOCP_CLIENT_LOOKUP => array("name" => "Name Lookup",
                                    "args" => "S"),
        AOCP_MSG_PRIVATE => array("name" => "Message Private",
                                  "args" => "ISS"),
        AOCP_BUDDY_ADD => array("name" => "Buddy Add",
                                "args" => $aocpdifs[2]),
        AOCP_BUDDY_REMOVE => array("name" => "Buddy Remove",
                                   "args" => "I"),
        AOCP_ONLINE_SET => array("name" => "Onlinestatus Set",
                                 "args" => "I"),
        AOCP_PRIVGRP_INVITE => array("name" => "Privategroup Invite",
                                     "args" => "I"),
        AOCP_PRIVGRP_KICK => array("name" => "Privategroup Kick",
                                   "args" => "I"),
        AOCP_PRIVGRP_JOIN => array("name" => "Privategroup Join",
                                   "args" => "I"),
        AOCP_PRIVGRP_PART => array("name" => "Privategroup Part",
                                   "args" => "I"),
        AOCP_PRIVGRP_KICKALL => array("name" => "Privategroup Kickall",
                                      "args" => ""),
        AOCP_PRIVGRP_MESSAGE => array("name" => "Privategroup Message",
                                      "args" => "ISS"),
        AOCP_GROUP_DATA_SET => array("name" => "Group Data Set",
                                     "args" => "GIS"),
        AOCP_GROUP_MESSAGE => array("name" => "Group Message",
                                    "args" => "GSS"),
        AOCP_GROUP_CM_SET => array("name" => "Group Clientmode Set",
                                   "args" => "GIIII"),
        AOCP_CLIENTMODE_GET => array("name" => "Clientmode Get",
                                     "args" => "IG"),
        AOCP_CLIENTMODE_SET => array("name" => "Clientmode Set",
                                     "args" => "IIII"),
        AOCP_PING => array("name" => "Ping",
                           "args" => "S"),
        AOCP_CC => array("name" => "CC",
                         "args" => $aocpdifs[3])));
    $this->args = array();
    $this->type = $type;
    $this->dir = $dir;
    $pmap = $GLOBALS["aochat-packetmap"][$dir][$type];
    if (!$pmap) {
      echo "Unsupported packet type (" . $dir . ", " . $type . ")\n";
      return false;
    }
    if ($dir == "in") {
      if (!is_string($data)) {
        echo "Incorrect argument for incoming packet, expecting a string.\n";
        return false;
      }
      for ($i = 0; $i < strlen($pmap["args"]); $i++)
      {
        $sa = $pmap["args"][$i];
        switch ($sa)
        {
          case "I":
            $temparray = unpack("N", $data);
            // If we are not running 64bit php, we need to use float instead of int due to large numbers
            // And due PHP not converting from int to float when unpack() is used, we have to force it.
            if (PHP_INT_SIZE != 8) {
              // We mainly use this for userid's which never have negative values
              // However some error returns use negative values so using -100 instead of -1 just as a precaution
              if ($temparray[1] < -100) {
                $temparray[1] += 0x100000000;
              }
            }
            $res = array_pop($temparray);
            $data = substr($data, 4);
            break;
          case "B":
            $temparray = unpack("C", $data);
            $res = array_pop($temparray);
            $data = substr($data, 1);
            break;
          case "S":
            $temparray = unpack("n", $data);
            $len = array_pop($temparray);
            $res = substr($data, 2, $len);
            $data = substr($data, 2 + $len);
            break;
          case "G":
            $res = substr($data, 0, 5);
            $data = substr($data, 5);
            break;
          case "i":
            $temparray = unpack("n", $data);
            $len = array_pop($temparray);
            $res = array_values(unpack("N" . $len, substr($data, 2)));
            $data = substr($data, 2 + 4 * $len);
            break;
          case "s":
            $temparray = unpack("n", $data);
            $len = array_pop($temparray);
            $data = substr($data, 2);
            $res = array();
            while ($len--)
            {
              $temparray = unpack("n", $data);
              $slen = array_pop($temparray);
              $res[] = substr($data, 2, $slen);
              $data = substr($data, 2 + $slen);
            }
            break;
          default:
            echo "Unknown argument type! (" . $sa . ")\n";
            continue (2);
        }
        $this->args[] = $res;
      }
    }
    else
    {
      if (!is_array($data)) {
        $args = array($data);
      }
      else
      {
        $args = $data;
      }
      $data = "";
      for ($i = 0; $i < strlen($pmap["args"]); $i++)
      {
        $sa = $pmap["args"][$i];
        $it = array_shift($args);
        if (is_null($it)) {
          echo "Missing argument for packet. (PacketID:$type)\n";
          break;
        }
        switch ($sa)
        {
          case "I":
            $data .= pack("N", $it);
            break;
          case "i":
            $data .= pack("n", $it);
            break;
          case "S":
            $data .= pack("n", strlen($it)) . $it;
            break;
          case "G":
            $data .= $it;
            break;
          case "s":
            $data .= pack("n", sizeof($it));
            foreach ($it as $it_elem)
            {
              $data .= pack("n", strlen($it_elem)) . $it_elem;
            }
            break;
          default:
            echo "Unknown argument type! (" . $sa . ")\n";
            continue (2);
        }
      }
      $this->data = $data;
    }
    return true;
  }
}

/* New "extended" messages, parser and abstraction.
* These were introduced in 16.1.  The messages use postscript
* base85 encoding (not ipv6 / rfc 1924 base85).  They also use
* some custom encoding and references to further confuse things.
*
* Messages start with the magic marker ~& and end with ~
* Messages begin with two base85 encoded numbers that define
* the category and instance of the message.  After that there
* are an category/instance defined amount of variables which
* are prefixed by the variable type.  A base85 encoded number
* takes 5 bytes.  Variable types:
*
* s: string, first byte is the length of the string
* i: signed integer (b85)
* u: unsigned integer (b85)
* f: float (b85)
* R: reference, b85 category and instance
* F: recursive encoding
* ~: end of message
*
* Message categories:
*  501 : More org messages
*        0xad0ae9b : Organization leave because of alignment change
*                    s(Char)
*  506 : NW messages
*        0x0c299d4 : Tower attack
*                    R(Faction), s(Org), s(Char),
*                    R(Faction), s(Org),
*                    s(Zone), i(Zone-X), i(Zone-Y)
*        0x8cac524 : Area abandoned
*                    R(Faction), s(Org), s(Zone)
*  508 : Org messages
*        0x04e87e7 : Character joined the organization
*                    s(Char)
*        0x2360067 : Character was kicked
*                    s(Kicker), s(Kicked)
*        0x2bd9377 : Character has left
*                    s(Char)
*        0x8487156 : Change of governing form
*                    s(Char), s(Form)
*        0x88cc2e7 : Organization disbanded
*                    s(Char)
*        0xc477095 : Vote begins
*                    s(Vote text), u(Minutes), s(Choices)
* 1001 : AI messages
*        0x01 : Cloak
*               s(Char), s(Cloak status)
*        0x02 : Radar alert
*        0x03 : Alien attack
*               s(Zone)
*        0x04 : Org HQ removed
*               s(Char), s(Zone)
*        0x05 : Building removal initiated
*               s(Char), R(House type), s(Zone)
*        0x06 : Building removed
*               s(Char), R(House type), s(Zone)
*        0x07 : Org HQ remove initiated
*               s(Char), s(Zone)
*
* Reference categories:
*  509 : House types (?)
*        0x00 : Normal House
* 2005 : Faction
*        0x00 : Neutral
*        0x01 : Clan
*        0x02 : Omni
*
*/

$GLOBALS["msg_cat"] = array(
  501 => array(0xad0ae9b => array(AOEM_ORG_LEAVE,
                                  "{NAME} has left the organization because of alignment change.",
                                  "s{NAME}"),
  ),
  506 => array(0x0c299d4 => array(AOEM_NW_ATTACK,
                                  "The {ATT_SIDE} organization {ATT_ORG} just entered a state of war! {ATT_NAME} attacked the {DEF_SIDE} organization {DEF_ORG}'s tower in {ZONE} at location ({X}, {Y}).",
                                  "R{ATT_SIDE}/s{ATT_ORG}/s{ATT_NAME}/R{DEF_SIDE}/s{DEF_ORG}/s{ZONE}/i{X}/i{Y}"),
               0x8cac524 => array(AOEM_NW_ABANDON,
                                  "Notum Wars Update: The {SIDE} organization {ORG} lost their base in {ZONE}.",
                                  "R{SIDE}/s{ORG}/s{ZONE}"),
               0x70de9b2 => array(AOEM_NW_OPENING,
                                  "(PLAYER) just initiated an attack on playfield (PF) at location ((X),(Y)). That area is controlled by (DEF_ORG). All districts controlled by your organization are open to attack! You are in a state of war. Leader chat informed.",
                                  "s(PLAYER)/i(PF)/i(X)/i(Y)/s(DEF_ORG)"),
               0x5a1d609 => array(AOEM_NW_TOWER_ATT_ORG,
                                  "The tower (TOWER) in (ZONE) was just reduced to (HEALTH) % health by (ATT_NAME) from the (ATT_ORG) organization!",
                                  "s(TOWER)/s(ZONE)/i(HEALTH)/s(ATT_NAME)/s(ATT_ORG)"),
               0xd5a1d68 => array(AOEM_NW_TOWER_ATT,
                                  "The tower (TOWER) in (ZONE) was just reduced to (HEALTH) % health by (ATT_NAME)!",
                                  "s(TOWER)/s(ZONE)/i(HEALTH)/s(ATT_NAME)"),
               0xfd5a1d4 => array(AOEM_NW_TOWER,
                                  "The tower (TOWER) in (ZONE) was just reduced to (HEALTH) % health!",
                                  "s(TOWER)/s(ZONE)/i(HEALTH)"),
  ),
  508 => array(0xa5849e7 => array(AOEM_ORG_JOIN,
                                  "{INVITER} invited {NAME} to your organization.",
                                  "s{INVITER}/s{NAME}"),
               0x2360067 => array(AOEM_ORG_KICK,
                                  "{KICKER} kicked {NAME} from the organization.",
                                  "s{KICKER}/s{NAME}"),
               0x2bd9377 => array(AOEM_ORG_LEAVE,
                                  "{NAME} has left the organization.",
                                  "s{NAME}"),
               0x8487156 => array(AOEM_ORG_FORM,
                                  "{NAME} changed the organization governing form to {FORM}.",
                                  "s{NAME}/s{FORM}"),
               0x88cc2e7 => array(AOEM_ORG_DISBAND,
                                  "{NAME} has disbanded the organization.",
                                  "s{NAME}"),
               0xc477095 => array(AOEM_ORG_VOTE,
                                  "Voting notice: {SUBJECT}\nCandidates: {CHOICES}\nDuration: {DURATION} minutes",
                                  "s{SUBJECT}/u{MINUTES}/s{CHOICES}"),
               0xa8241d4 => array(AOEM_ORG_STRIKE,
                                  "Blammo! {NAME} has launched an orbital attack!",
                                  "s{NAME}"),
  ),
  1001 => array(0x01 => array(AOEM_AI_CLOAK,
                              "{NAME} turned the cloaking device in your city {STATUS}.",
                              "s{NAME}/s{STATUS}"),
                0x02 => array(AOEM_AI_RADAR,
                              "Your radar station is picking up alien activity in the area surrounding your city.",
                              ""),
                0x03 => array(AOEM_AI_ATTACK,
                              "Your city in {ZONE} has been targeted by hostile forces.",
                              "s{ZONE}"),
                0x04 => array(AOEM_AI_HQ_REMOVE,
                              "{NAME} removed the organization headquarters in {ZONE}.",
                              "s{NAME}/s{ZONE}"),
                0x05 => array(AOEM_AI_REMOVE_INIT,
                              "{NAME} initiated removal of a {TYPE} in {ZONE}.",
                              "s{NAME}/R{TYPE}/s{ZONE}"),
                0x06 => array(AOEM_AI_REMOVE,
                              "{NAME} removed a {TYPE} in {ZONE}.",
                              "s{NAME}/R{TYPE}/s{ZONE}"),
                0x07 => array(AOEM_AI_HQ_REMOVE_INIT,
                              "{NAME} initiated removal of the organization headquarters in {ZONE}.",
                              "s{NAME}/s{ZONE}"),
  ),
);

$GLOBALS["ref_cat"] = array(
  509 => array(0x00 => "Normal House"),
  2005 => array(0x00 => "Neutral",
                0x01 => "Clan",
                0x02 => "Omni"),
);

class AOExtMsg
{

  function AOExtMsg($str = NULL)
  {
    $this->type = AOEM_UNKNOWN;
    if (!empty($str)) {
      $this->read($str);
    }
  }

  function arg($n)
  {
    $key = "{" . strtoupper($n) . "}";
    if (isset($this->args[$key])) {
      return $this->args[$key];
    }
    return NULL;
  }

  function read($msg)
  {
    if (substr($msg, 0, 2) !== "~&") {
      return false;
    }
    $msg = substr($msg, 2);
    $category = $this->b85g($msg);
    $instance = $this->b85g($msg);
    if (!isset($GLOBALS["msg_cat"][$category]) || !isset($GLOBALS["msg_cat"][$category][$instance])) {
      echo "\nAOChat ExtMsg Debug: Unknown Cat: $category Instance: $instance\n\n";
      return false;
    }
    $typ = $GLOBALS["msg_cat"][$category][$instance][0];
    $fmt = $GLOBALS["msg_cat"][$category][$instance][1];
    $enc = $GLOBALS["msg_cat"][$category][$instance][2];
    $args = array();
    foreach (explode("/", $enc) as $eone)
    {
      $ename = substr($eone, 1);
      $msg = substr($msg, 1); // skip the data type id
      switch ($eone[0])
      {
        case "s":
          $len = ord($msg[0]) - 1;
          $str = substr($msg, 1, $len);
          $msg = substr($msg, $len + 1);
          $args[$ename] = $str;
          break;
        case "i":
        case "u":
          $num = $this->b85g($msg);
          $args[$ename] = $num;
          break;
        case "R":
          $cat = $this->b85g($msg);
          $ins = $this->b85g($msg);
          if (!isset($GLOBALS["ref_cat"][$cat]) || !isset($GLOBALS["ref_cat"][$cat][$ins])) {
            $str = "Unknown ($cat, $ins)";
          }
          else
          {
            $str = $GLOBALS["ref_cat"][$cat][$ins];
          }
          $args[$ename] = $str;
          break;
      }
    }
    $str = strtr($fmt, $args);
    $this->type = $typ;
    $this->text = $str;
    $this->args = $args;
  }

  function b85g(&$str)
  {
    $n = 0;
    for ($i = 0; $i < 5; $i++)
    {
      $n = $n * 85 + ord($str[$i]) - 33;
    }
    $str = substr($str, 5);
    return $n;
  }
}

?>
