<?php
/*
* BuddyList.php -
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
new BuddyList($bot);
class BuddyList extends BasePassiveModule
{
  public $buddy_status = array();

  public function __construct($bot)
  {
    parent::__construct($bot, get_class($this));
    $this->register_module('buddy');

    if ($this->bot->game == "aoc") {
      $this->bot->dispatcher->connect('core.on_buddy_onoff', array($this,
                                                                  'buddy_aoc'));
    }
    else
    {
      $this->bot->dispatcher->connect('core.on_buddy_onoff', array($this,
                                                                  'buddy_ao'));
    }
  }


  function buddy_ao($args)
  {
    $user = $this->bot->core("player")->name($args['id']);


    if (empty($user)) {
      $this->bot->log("DEBUG", "BuddyList", "buddy_ao() got empty user");
      $this->bot->log("DEBUG", "BuddyList", $this->bot->debug_bt());
      return $this->error;
    }

    $member = $this->bot->core("notify")->check($user);

    // Make sure we only cache members and guests to prevent any issues with !is and anything else that might do buddy actions on non members.
    if ($member) {
      // Buddy logging on
      if ($args['online'] == 1) {
        // Do we have a logon for a user already logged on?
        if (isset($this->bot->glob["online"][$user])) {
          // $this -> log("BUDDY", "ERROR", $user . " logged on despite of already being marked as logged on!!");
          return;
        }
        else
        {
          // Enter the user into the online buddy list
          $this->bot->glob["online"][$user] = $user;
        }
      }
      else
      {
        // Do we have a logoff without a prior login?
        if (!isset($this->bot->glob["online"][$user])) {
          // $this -> log("BUDDY", "ERROR", $user . " logged off with no prior logon!!");
          return;
        }
        else
        {
          unset($this->bot->glob["online"][$user]);
        }
      }
    }
    $end = "";
    if (!$member) {
      $end = " (not on notify)";
      // Using aoc -> buddy_remove() here is an exception, all the checks in chat -> buddy_remove() aren't needed!
      $this->bot->aoc->buddy_remove($user);
    }
    else
    {
      $end = " (" . $this->bot->core("security")->get_access_name($this->bot->core("security")->get_access_level($user)) . ")";
    }
    $this->bot->log("BUDDY", "LOG", $user . " logged [" . (($args['online'] == 1) ? "on" : "off") . "]" . $end);

    // **** FIXME ***
    // This should be event driven
    if (!empty($this->bot->commands["buddy"])) {
      $keys = array_keys($this->bot->commands["buddy"]);
      foreach ($keys as $key)
      {
        if ($this->bot->commands["buddy"][$key] != NULL) {
          $this->bot->commands["buddy"][$key]->buddy($user, $args['online']);
        }
      }
    }
  }

  function buddy_aoc($args)
  {
    // Get the users current state
    $old_who = $this->bot->core("Whois")->lookup($user, true); // $noupdate MUST be true to avoid adding buddy recursively
    if (array_key_exists($user, $this->buddy_status)) {
      $old_buddy_status = $this->buddy_status[$user];
    }
    else
    {
      $old_buddy_status = 0;
    }

    $who = array();
    $who["id"] = $args['id'];
    $who["nickname"] = $user;
    $who["online"] = $args['online'];
    $who["level"] = $args['level'];
    $who["location"] = $args['location']; // For offline users 'location' contains the last online time in milliseconds since 1970!
    $class_name = $this->bot->core("Whois")->class_name[$args['class']];
    $who["class"] = $class_name;
    $lookup = $this->db->select("SELECT * FROM #___craftingclass WHERE name = '" . $user . "'", MYSQL_ASSOC);
    if (!empty($lookup)) {
      $who["craft1"] = $lookup[0]['class1'];
      $who["craft2"] = $lookup[0]['class2'];
    }
    $this->bot->core("Whois")->update($who);
    if ($old_who instanceof BotError) {
      $old_who = array();
      $old_who["level"] = 0;
      $old_who["location"] = 0;
    }
    // status change flags:
    // 1 = online
    // 2 = LFG
    // 4 = AFK
    if (0 == $who["online"]) {
      $buddy_status = 0;
    }
    else if (1 == $who["online"]) {
      $buddy_status = 1;
    }
    else if (2 == $who["online"]) {
      $buddy_status = $old_buddy_status | 2;
    }
    else if (3 == $who["online"]) {
      $buddy_status = $old_buddy_status | 4;
    }

    $this->buddy_status[$user] = $buddy_status;
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
    if (1 == ($changed & 1)) {
      if (1 == ($old_buddy_status & 1)) {
        // User just went offline
        $current_statuses[] = 0;
      }
      else
      {
        // User just came online
        $current_statuses[] = 1;
      }
    }
    if (2 == ($changed & 2)) {
      if (2 == ($old_buddy_status & 2)) {
        // User just returned from LFG
        $current_statuses[] = 4;
      }
      else
      {
        // User just went LFG
        $current_statuses[] = 2;
      }
    }
    if (4 == ($changed & 4)) {
      if (4 == ($old_buddy_status & 4)) {
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
    if ($old_who["level"] != $who["level"] && $old_who["level"] != 0) {
      // User has changed level
      $current_statuses[] = 7;
    }
    if ($old_who["location"] != $who["location"] && $old_who["location"] != 0 && $who["online"] != 0 && !in_array(0, $current_statuses)) {
      // User has changed location
      $current_statuses[] = 6;
    }
    // Make sure we only cache members and guests to prevent any issues with !is and anything else that might do buddy actions on non members.
    if ($member) {
      if (in_array(1, $current_statuses)) {
        // User just came online
        // Enter the user into the online buddy list
        $this->bot->glob["online"][$user] = $user;
      }
      else if (in_array(0, $current_statuses)) {
        // User just went offline
        unset($this->bot->glob["online"][$user]);
      }
      $end = " (" . $this->bot->core("security")->get_access_name($this->bot->core("security")->get_access_level($user)) . ")";
    }
    else
    {
      $end = " (not on notify)";
      // Using aoc -> buddy_remove() here is an exception, all the checks in chat -> buddy_remove() aren't needed!
      $this->bot->aoc->buddy_remove($user);
    }
    foreach ($current_statuses as $status)
    {
      $this->bot->log("BUDDY", "LOG", $user . " changed status [" . $status . "]" . $end);
      if (!empty($this->bot->commands["buddy"])) {
        $keys = array_keys($this->bot->commands["buddy"]);
        foreach ($keys as $key)
        {
          if ($this->bot->commands["buddy"][$key] != NULL) {
            $this->bot->commands["buddy"][$key]->buddy($user, $status, $args['level'], $args['location'], $args['class']);
          }
        }
      }
    }
  }
}

?>