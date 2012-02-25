<?php
/*
* User interface to add, list and remove timers.
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
$timergui = new TimerGUI($bot);
/*
The Class itself...
*/
class TimerGUI extends BaseActiveModule
{

  function __construct(&$bot)
  {
    parent::__construct($bot, get_class($this));
    $this->register_command('all', 'timer', 'GUEST');
    $this->register_command('all', 'rtimer', 'GUEST');
    $this->register_alias('timer', 'timers');
    $this->register_command('all', 'remtimer', 'GUEST');
    $this->register_command('all', 'ptimer', 'GUEST');
    $this->register_alias('ptimer', 'publictimer');
    $this->register_alias('ptimer', 'ptimers');
    $this->register_alias('ptimer', 'publictimers');
    $this->register_command('all', 'tset');
    $this->register_alias('tset', 'timersettings');
    $this->register_alias('tset', 'tsettings');
    $this->bot->core("timer")->create_class_setting("PublicTimer", "LowSpam", "The default class used for timers in a public channel (org, pgroup).");
    $this->bot->core("timer")->create_class_setting("PrivateTimer", "Standard", "The default class used for timer in tells.");
    $this->help['description'] = 'Setting and removing of timers..';
    $this->help['command']['timer'] = "Lists all current timer for the bot and offers support to delete them.";
    $this->help['command']['timer [class] #[mshd] title'] = "Adds a timer for # minutes (m), seconds (s), hours (h) or days (d). If no time unit is added it's # seconds. [class] is an optional parameter defining which timer class to use.";
    $this->help['command']['timer [class] #[:##[:##[:##]]] title'] = "Adds a timer using the format days:hours:minutes:seconds, with the lowest time unit always being seconds (so 1:20 means 1min 20secs, 1:03:05 means 1hour 3mins 5secs). On every : there have to follow exactly two numbers. You don't have to enter all numbers. [class] is an optional parameter defining which timer class to use.";
    $this->help['command']['rtimer [class] <dur>[mshd] <repeat>[mshd] title'] = "Adds a repeating timer for <dur> minutes (m), seconds (s), hours (h) or days (d). If no time unit is added it's <dur> seconds. <rep> is the time between repetitions of the timer, the same rules as for <dur> apply. [class] is an optional parameter defining which timer class to use.";
    $this->help['command']['rtimer [class] <dur>[:##[:##[:##]]] <repeat>[:##[:##[:##]]] title'] = "Adds a timer using the format days:hours:minutes:seconds, with the lowest time unit always being seconds (so 1:20 means 1min 20secs, 1:03:05 means 1hour 3mins 5secs). On every : there have to follow exactly two numbers. You don't have to enter all numbers. <rep> is the time between repetitions of the timer, the same rules as for <dur> apply. [class] is an optional parameter defining which timer class to use.";
    $this->help['command']['ptimer'] = "Lists and creates timers in the org chat, disregarding the originating channel. Syntax otherwise exactly as the syntax of the timer command, read there for more information.";
    $this->help['command']['tset'] = 'Lists all existing timer class settings and allows to change them.';
  }

  function command_handler($name, $msg, $channel)
  {
    $command = explode(" ", $msg, 2);
    Switch (strtolower($command[0]))
    {
      case 'timer':
        $classused = $this->bot->core("timer")->get_class_setting("PublicTimer");
        if ($channel == "tell") {
          $classused = $this->bot->core("timer")->get_class_setting("PrivateTimer");
        }
        if (preg_match("/^timer ([a-z]+ )?([1-9][0-9]*[mshd]?) (.*)/i", $msg, $info)) {
          if ($info[1] != "") {
            $classused = $info[1];
          }
          return $this->add_timer($name, $info[2], $info[3], $classused, 0, $channel);
        }
        elseif (preg_match("/^timer ([a-z]+ )?([0-9]+(:[0-9][0-9]){0,3}) (.*)/i", $msg, $info))
        {
          if ($info[1] != "") {
            $classused = $info[1];
          }
          return $this->add_timer($name, $info[2], $info[4], $classused, 0, $channel);
        }
        elseif (preg_match("/^timer$/i", $msg))
        {
          return $this->show_timer($name, $channel);
        }
        else
        {
          return "Correct Format: ##highlight##<pre>timer [class] #[mshd] title##end## or ##highlight##<pre>timer [class] #[:##[:##[:##]]] title##end## [class] is an optional parameter";
        }
      case 'rtimer':
        if (preg_match("/^rtimer ([a-z]+ )?([1-9][0-9]*[mshd]?) ([1-9][0-9]*[mshd]?) (.*)/i", $msg, $info)) {
          if ($info[1] != "") {
            $classused = $info[1];
          }
          return $this->add_timer($name, $info[2], $info[4], $classused, $info[3], $channel);
        }
        elseif (preg_match("/^rtimer ([a-z]+ )?([0-9]+(:[0-9][0-9]){0,3}) ([0-9]+(:[0-9][0-9]){0,3}) (.*)/i", $msg, $info))
        {
          if ($info[1] != "") {
            $classused = $info[1];
          }
          return $this->add_timer($name, $info[2], $info[6], $classused, $info[4], $channel);
        }
        else
        {
          return "Correct Format: ##highlight##<pre>rtimer [class] <dur>[mshd] <repeat>[mshd] title##end## or ##highlight##<pre>rtimer [class] <dur>[:##[:##[:##]]] <repeat>[:##[:##[:##]]] title##end## [class] is an optional parameter";
        }
      case 'remtimer':
        return $this->rem_timer($name, $command[1]);
      case 'ptimer':
        if (isset($command[1])) {
          return $this->command_handler($name, 'timer ' . $command[1], 'gc');
        }
        else
        {
          return $this->command_handler($name, 'timer', 'gc');
        }
      case 'tset':
        if (preg_match("/^tset$/i", $msg)) {
          return $this->show_timer_settings();
        }
        elseif (preg_match("/^tset show ([01-9]+)/i", $msg, $info))
        {
          return $this->change_timer_setting($info[1]);
        }
        elseif (preg_match("/^tset update ([01-9]+) ([01-9]+)/i", $msg, $info))
        {
          return $this->update_timer_setting($info[1], $info[2]);
        }
      default:
        return false;
    }
  }

  function add_timer($owner, $timestr, $name, $class, $repeatstr, $channel)
  {
    $duration = $this->bot->core("time")->parse_time($timestr);
    $repeat = $this->bot->core("time")->parse_time($repeatstr);
    if ($repeat != 0 && $repeat < $this->bot->core("settings")->get("Timer", "MinRepeatInterval")) {
      return "The repeat interval must be at least##highlight## " . $this->bot->core("settings")->get("Timer", "MinRepeatInterval") . "##end## seconds!";
    }
    $this->bot->core("timer")->add_timer(false, $owner, $duration, $name, $channel, $repeat, $class);
    $msg = "Timer ##highlight##" . $name . " ##end##with ##highlight##" . $this->bot->core("time")->format_seconds($duration) . " ##end##runtime started!";
    if ($repeat > 0) {
      $msg .= " The timer has a repeat interval of##highlight## " . $this->bot->core("time")->format_seconds($repeat) . " ##end##";
    }
    return $msg;
  }

  function show_timer($name, $channel)
  {
    $channelstr = "channel = '" . $channel . "'";
    if ($this->bot->core("settings")->get("timer", "global")) {
      $channelstr = "channel = 'global'";
    }
    elseif ($this->bot->core("settings")->get("timer", "guestchannel") == "both" && ($channel == "pgmsg" || $channel == "gc"))
    {
      $channelstr = "(channel = 'both' OR channel = '" . $channel . "')";
    }
    $namestr = "";
    if ($channel == "tell") {
      $namestr = " AND owner = '" . $name . "'";
    }
    $timers = $this->bot->db->select("SELECT * FROM #___timer WHERE " . $channelstr . $namestr . " ORDER BY endtime ASC", MYSQL_ASSOC);
    if (empty($timers)) {
      return "No timers defined!";
    }
    $thistime = time();
    $listing = "";
    foreach ($timers as $timer)
    {
      $listing .= "\n##blob_text##Timer ##end##" . $timer['name'] . " ##blob_text##has ##end##";
      $listing .= $this->bot->core("time")->format_seconds($timer['endtime'] - $thistime);
      $listing .= " ##blob_text##remaining";
      if ($timer['repeatinterval'] > 0) {
        $listing .= " and is repeated every ##end##" . $this->bot->core("time")->format_seconds($timer['repeatinterval']) . "##blob_text##";
      }
      $listing .= ". Owner:##end## " . $timer['owner'] . " ";
      $listing .= $this->bot->core("tools")->chatcmd("remtimer " . $timer['id'], "[DELETE]");
    }
    return $this->bot->core("tools")->make_blob("Current timers", "##blob_title##Timers for <botname>:##end##\n" . $listing);
  }

  function rem_timer($name, $id)
  {
    return $this->bot->core("timer")->del_timer($name, $id, false);
  }

  // Shows all existing timer settings with their current values:s
  function show_timer_settings()
  {
    $tsets = $this->bot->db->select('SELECT a.id AS id, a.name AS name, b.name AS class, a.description as description ' . 'FROM #___timer_class_settings AS a, #___timer_classes AS b WHERE a.current_class = b.id ORDER BY name ASC', MYSQL_ASSOC);
    if (empty($tsets)) {
      return 'No timer class settings existing!';
    }
    $blob = "##blob_title##Timer class settings##end##\n\n";
    $blob .= "##blob_text##Click one one of the links to open a window to change the class for a timer class setting.\n\n";
    foreach ($tsets as $tset)
    {
      $blob .= "##blob_title##Name: ##end##";
      $blob .= $this->bot->core("tools")->chatcmd("tset show " . $tset['id'], $tset['name']);
      $blob .= "\n##blob_title##Current class: ##end##" . $tset['class'] . "\n";
      $blob .= "##blob_title##Description: ##end##" . $tset['description'] . "\n\n";
    }
    $blob .= "##end##";
    return $this->bot->core("tools")->make_blob("Timer class settings", $blob);
  }

  // Shows timer class setting with id $tsetid, it's current value and it's default value
  // shows an interface to pick the new value among all existing timer classes
  function change_timer_setting($tsetid)
  {
    $tsets = $this->bot->db->select('SELECT a.id AS id, a.name AS name, b.name AS current_class, ' . 'c.name AS default_class, c.id AS default_id, a.description as description ' . 'FROM #___timer_class_settings AS a, #___timer_classes AS b, #___timer_classes AS c ' . 'WHERE a.current_class = b.id AND a.default_class = c.id AND a.id = ' . $tsetid, MYSQL_ASSOC);
    if (empty($tsets)) {
      return "Illegal ID!";
    }
    $tset = $tsets[0];
    $blob = "##blob_title##Timer class setting " . $tset['name'] . "##end##\n";
    $blob .= "##blob_text##\n##blob_title##Description: ##end##" . $tset['description'] . "\n";
    $blob .= "##blob_title##Current class: ##end##" . $tset['current_class'] . "\n";
    $blob .= "##blob_title##Default class: ##end##";
    $blob .= $this->bot->core("tools")->chatcmd("tset update " . $tsetid . " " . $tset['default_id'], $tset['default_class']);
    $blob .= "\n\nClick on the name of one of the following classes to change this setting to the selected class.\n\n";
    $blob .= "Make sure the timer setting is compatible, otherwise you may get unexpected behavior and output of the timers. ";
    $blob .= "Try to go by the descriptions for this.\nYou can always go back to the default class by picking that above.\n\n";
    $classes = $this->bot->db->select("SELECT * FROM #___timer_classes ORDER BY name ASC", MYSQL_ASSOC);
    if (empty($classes)) {
      return "No timer classes defined!";
    }
    foreach ($classes as $class)
    {
      $blob .= "\n\n##blob_title##Class name: ##end##";
      $blob .= $this->bot->core("tools")->chatcmd("tset update " . $tsetid . " " . $class['id'], $class['name']);
      $blob .= "\n##blob_title##Description: ##end##" . $class['description'];
    }
    return $this->bot->core("tools")->make_blob("Timer class settings", $blob);
  }

  // Sets $tset to $newclassid if $tsetid is valid
  function update_timer_setting($tsetid, $newclassid)
  {
    $tsets = $this->bot->db->select('SELECT name FROM #___timer_class_settings WHERE id = ' . $tsetid, MYSQL_ASSOC);
    if (empty($tsets)) {
      return "Illegal ID of timer class setting!";
    }
    $tset = $tsets[0];
    $classes = $this->bot->db->select('SELECT name FROM #___timer_classes WHERE id = ' . $newclassid, MYSQL_ASSOC);
    if (empty($classes)) {
      return "Illegal ID of timer class!";
    }
    $class = $classes[0];
    if ($this->bot->core("timer")->update_class_setting($tset['name'], $class['name'])) {
      return "Changed timer class setting ##highlight##" . $tset['name'] . "##end## to new timer class ##highlight##" . $class['name'] . "##end##";
    }
    return "Error updating timer class!";
  }
}

?>