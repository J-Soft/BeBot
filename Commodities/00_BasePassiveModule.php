<?php

/*
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

class BasePassiveModule
{
    public $module_name; // A reference to the bot
    protected $bot; //Name of the module extending this class.
    protected $error; //This holds an error class.
    protected $link_name;


    function __construct(&$bot, $module_name)
    {
        //Save reference to bot
        $this->bot = &$bot;
        $this->module_name = $module_name;
        $this->link_name = null;
        $this->error = new BotError($bot, $module_name);
    }

    public function __call($name, $args)
    {
        foreach ($args as $i => $arg) {
            if (is_object($arg)) {
                $args[$i] = "::object::";
            }
        }
        $args = implode(', ', $args);
        $msg = "Undefined function $name($args)!";
        $this->error->set($msg);
        return $this->error->message();
    }

    public function debug_output($title)
    {
        if ($this->bot->debug) {
            if ($title != "") {
                echo $title . "\n";
            }
            $this->bot->log("DEBUG", "BasePassive", $this->bot->debug_bt());
        }
    }

    protected function register_event($event, $target = false)
    {
        $ret = $this->bot->register_event($event, $target, $this);
        if ($ret) {
            $this->error->set($ret);
        }
    }

    protected function unregister_event($event, $target = false)
    {
        $ret = $this->bot->unregister_event($event, $target, $this);
        if ($ret) {
            $this->error->set($ret);
        }
    }

    protected function register_module($name)
    {
        if ($this->link_name == null) {
            $this->link_name = strtolower($name);
            $this->bot->register_module($this, strtolower($name));
        }
    }

    protected function unregister_module()
    {
        if ($this->link_name != null) {
            $this->bot->unregister_module($this->link_name);
        }
    }

    protected function output_destination($name, $msg, $channel = false)
    {
        if ($channel !== false) {
            if ($channel & SAME) {
                if ($channel & $this->source) {
                    $channel -= SAME;
                } else {
                    $channel += $this->source;
                }
            }
        } else {
            $channel += $this->source;
        }
        if ($channel & TELL) {
            $this->bot->send_tell($name, $msg);
        }
        if ($channel & GC) {
            $this->bot->send_gc($msg);
        }
        if ($channel & PG) {
            $this->bot->send_pgroup($msg);
        }
        if ($channel & RELAY) {
            $this->bot->core("relay")->relay_to_pgroup($name, $msg);
        }
        if ($channel & IRC) {
            $this->bot->send_irc($this->module_name, $name, $msg);
        }
    }
}

?>
