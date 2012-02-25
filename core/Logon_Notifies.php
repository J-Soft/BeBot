<?php
/*
* Logon_Notifies.php - Notifies registered modules when members log on.
* This notify is send after a configurable delay to avoid spamming right at logon.
*
* Written by Alreadythere (RK2).
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
/*
 * Global storing for delaying notifies of modules on logon.
 * This module offers the storing backend as well as the buddy tracking.
 * The notification delay can be set by a global setting.
 * 
 * Modules need to register themself for callbacks when a buddy has to be notified.
 * Only guests and members are handled in this module. All registering modules must have
 * have a function notify($nickname, $startup) handling all module dependant stuff.
 * The $nickname is the name of the character that logged on, $startup is a boolean defining
 * if the Bot still is in the startup phase or not anymore. This can be used to remove spamming
 * of some things on bot restarts, useful for modules that only should do something if a
 * character logs in for real, instead of the possibly false logons on bot startup.
 */
$logon_notifies_core = new Logon_Notifies_Core($bot);
class Logon_Notifies_Core extends BasePassiveModule
{
  var $bot;
  var $modules;

  function __construct(&$bot)
  {
    parent::__construct($bot, get_class($this));
    $this->register_module("logon_notifies");
    $this->register_event("buddy");
    $this->register_event("connect");
    $this->register_event("cron", "2sec");
    $this->modules = array();
    $this->cron_running = 0;
    $this->notifies = array();
    $this->waiting = FALSE;
    $this->bot->core("settings")->create("Logon_Notifies", "Notify_Delay", 5, "How many seconds should be waited after logon of a buddy till any notifies are sent to him?", "0;1;2;3;4;5;10;15;30");
    $this->bot->core("settings")->create("Logon_Notifies", "Enabled", TRUE, "Are notifies on logon enabled or disabled?");
    // Set startup high enough that it cannot be reached before connection is done successfully:
    $this->startup = time() + 3600;
  }

  // registers a new module
  function register(&$module)
  {
    $this->modules[get_class($module)] = &$module;
  }

  function unregister(&$module)
  {
    if (isset($this->modules[get_class($module)])) {
      $this->modules[get_class($module)] = NULL;
      unset($this->modules[get_class($module)]);
    }
  }

  function buddy($name, $msg)
  {
    if ($msg == 1 && $this->bot->core("settings")->get("Logon_notifies", "Enabled") && $this->bot->core("security")->check_access($name, "GUEST") && $this->bot->core("notify")->check($name)) {
      $this->notifies[$name] = time() + $this->bot->core("settings")->get("Logon_notifies", "Notify_delay");
      $this->waiting = TRUE;
    }
  }

  function connect()
  {
    $this->startup = time() + 120 + $this->bot->core("settings")->get("Logon_notifies", "Notify_delay");
  }

  function cron()
  {
    if (!($this->waiting) || empty($this->modules)) {
      return;
    }
    if (empty($this->notifies)) {
      $this->waiting = FALSE;
      $this->cron_running = 0;
      return;
    }
    if ($this->cron_running == 0) {
      $this->cron_running = 1;
    }
    else
    {
      return;
    }
    $thistime = time();
    if ($thistime >= $this->startup) {
      $starting = false;
    }
    else
    {
      $starting = true;
    }
    foreach ($this->notifies as $user => $time)
    {
      if ($time <= $thistime) {
        foreach ($this->modules as $module)
        {
          if ($module != NULL) {
            $module->notify($user, $starting);
          }
        }
        unset($this->notifies[$user]);
      }
    }
    $this->cron_running = 0;
    return;
  }
}

?>