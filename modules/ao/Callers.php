<?php
/*
* Callers.php - Designate and list "Callers" for raids and events.
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
$callers = new Callers($bot);
/*
The Class itself...
*/
class Callers extends BaseActiveModule
{

  function __construct(&$bot)
  {
    parent::__construct($bot, get_class($this));
    $this->callers = array();
    $this->register_command('all', 'caller', 'GUEST', array("clear" => "LEADER",
                                                           "add" => "LEADER",
                                                           "del" => "LEADER"));
    $this->register_alias("caller", "callers");
    $this->help['description'] = "Designate and list 'callers' for raids and events";
    $this->help['command']['caller'] = "Lists the set callers";
    $this->help['command']['caller clear'] = "Clears the list of callers";
    $this->help['command']['caller add <name>'] = "Adds player <name> as a caller on the list.";
    $this->help['command']['caller del <name>'] = "Removes player <name> as a caller from the list.";
  }

  function command_handler($name, $msg, $origin)
  {
    if (preg_match("/^caller clear/i", $msg)) {
      return $this->clear_callers($name);
    }
    else if (preg_match("/^caller add (.+)/i", $msg, $info)) {
      return $this->caller_add($info[1]);
    }
    else if (preg_match("/^caller del (.+)/i", $msg, $info)) {
      return $this->caller_del($info[1]);
    }
    else if (preg_match("/^caller/i", $msg)) {
      return $this->show_callers();
    }
  }

  /*
  Add a caller
  */
  function caller_add($name)
  {
    $name = ucfirst(strtolower($name));
    if ($this->bot->core('player')->id($name)) {
      $this->callers[$name] = 1;
      return "##YELLOW##" . $name . "##END## has been added to caller list. " . $this->show_callers();
    }
    else
    {
      return "Player ##YELLOW##" . $name . "##END## does not exist.";
    }
  }

  /*
  Remove a caller
  */
  function caller_del($name)
  {
    $name = ucfirst(strtolower($name));
    if ($name == "All") {
      $this->callers = array();
      return "List of callers has been cleared.";
    }
    else if ($this->bot->core('player')->id($name) != -1) {
      if (isset($this->callers[$name])) {
        unset($this->callers[$name]);
        return "##YELLOW##" . $name . "##END## has been removed from caller list. " . $this->show_callers();
      }
      else
      {
        return "##YELLOW##" . $name . "##END## is not on list of callers. " . $this->show_callers();
      }
    }
    else
    {
      return "Player ##YELLOW##" . $name . "##END## does not exist.";
    }
  }

  function clear_callers($name)
  {
    $name = ucfirst(strtolower($name));
    $this->callers = array();
    return "Caller list cleared by ##YELLOW##" . $name . "##END##.";
  }

  /*
  Return the list of callers
  */
  function show_callers()
  {
    $call = array_keys($this->callers);
    if (empty($call)) {
      return "No callers on list.";
    }
    else
    {
      $batch = "";
      $count = 0;
      $list = "##AO_INFOHEADLINE##::: List of callers :::##END##\n\n";
      foreach ($call as $player)
      {
        if ($count >= 1) {
          $batch .= " \\n ";
        }
        $count++;
        $list .= " - $player: [<a href='chatcmd:///macro $player /assist $player'>Create Macro</a>] [<a href='chatcmd:///assist $player'>Assist</a>]\n";
        $batch .= "/assist " . $player;
      }
      $list .= "\n";
      $list .= "All Callers: [<a href='chatcmd://$batch'>Assist</a>]";
      $list .= "\n\nAll Callers Macro:\n/macro <botname> $batch";
      return $this->bot->core("tools")->make_blob("List of Callers", $list);
    }
  }
}

?>