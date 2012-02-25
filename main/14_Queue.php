<?php
/*
* Queue.php - Queue plugin
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


$queue_core = new Queue_Core($bot);


/*
The Class itself...
*/
class Queue_Core extends BasePassiveModule
{
  private $que;
  private $que_low;
  private $queue_left;
  private $last_call;

  /*
  Constructor:
  Hands over a referance to the "Bot" class.
  */
  function __construct(&$bot)
  {
    parent::__construct(&$bot, get_class($this));

    $this->register_module("queue");
    $this->register_event("cron", "1sec");

    $this->queue = array();
    $this->queue_low = array();
  }

  /*
  $name = name of module, to be used when adding stuff to que
  $delay = min time between each in seconds
  $max = max count on items before que
  */
  function register(&$module, $name, $delay, $max = 0, $filter = TRUE)
  {
    $name = strtolower($name);
    $this->link[$name] = $module;
    $this->delay[$name] = $delay;
    $this->max[$name] = $max;
    $this->filter = (bool)$filter;
  }


  /*
  This gets called on cron
  */
  function cron()
  {
    foreach ($this->link as $name => $mod)
    {
      if (!empty($this->queue[$name])) {
        $this->set_queue($name);
        foreach ($this->queue[$name] as $key => $value)
        {
          if ($this->queue_left[$name] >= 1) {
            $mod->queue($name, $value);

            unset($this->queue[$name][$key]);
            $this->queue_left[$name] -= 1;
          }
        }
      }
      if (!empty($this->queue_low[$name]) && empty($this->queue[$name])) {
        $this->set_queue($name);
        foreach ($this->queue_low[$name] as $key => $value)
        {
          if ($this->queue_left[$name] >= 1) {
            $mod->queue($name, $value);

            unset($this->queue_low[$name][$key]);
            $this->queue_left[$name] -= 1;
          }
        }
      }
    }
  }


  /*
  Sets messages left...
  */
  function set_queue($name)
  {
    $time = time();
    $add = ($time - $this->last_call[$name]) / $this->delay[$name];
    if ($add > 0) {
      $this->queue_left[$name] += $add;
      $this->last_call[$name] = $time;
      if ($this->queue_left[$name] > $this->max[$name] && $this->max[$name] != 0) {
        $this->queue_left[$name] = $this->max[$name];
      }
    }
  }


  /*
  Checks if tell can be sent. true if yes, false it has to be put to queue
  */
  function check_queue($name)
  {
    $name = strtolower($name);
    $this->set_queue($name);
    if (($this->queue_left[$name] >= 1) && empty($this->queue[$name]) && empty($this->queue_low[$name])) {
      $this->queue_left[$name] -= 1;
      return true;
    }
    return false;
  }


  /*
  Puts a msg into queue
  */
  function into_queue($name, $info, $priority = 0)
  {
    $name = strtolower($name);
    if ($priority == 0) {
      // Filter duplicate messages. The exact same message twice in a queue
      // results most likely from double clicking a link or spamming the bot
      // (like if 100 people are klicking on a guild info link multiple times).
      // There is no point in sending the same answer back twice in a row.
      if ($this->filter[$name]) {
        foreach ($this->queue[$name] as $item)
        {
          if ($this->bot->core("tools")->compare($info, $item)) {
            return;
          }
        }
      }
      $this->queue[$name][] = $info;
    }
    else
    {
      // Filter duplicate messages.
      if ($this->filter[$name]) {
        foreach ($this->queue_low[$name] as $item)
        {
          if ($this->bot->core("tools")->compare($info, $item)) {
            return;
          }
        }
      }
      $this->queue_low[$name][] = $info;
    }
  }
}

?>