<?php
/*
* Say.php - Makes the bot say things. :-)
*
* Say module by Andrew Zbikowski <andyzib@gmail.com> (AKA Glarawyn, RK1)
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
$say = new Say($bot);

/*
The Class itself...
*/

class Say extends BaseActiveModule
{ // Start Class
    var $whosaidthat;


    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->whosaidthat = array();
        // Setup Access Control
        $this->register_command("all", "say", "ADMIN");
        $this->register_command("all", "whosaidthat", "MEMBER");
        $this->bot->core("settings")
          ->create("Say", "OutputChannel", "both",
            "Into which channel should the output of say be sent? Either gc, pgmsg, both or original channel.",
            "gc;pgmsg;both;origin");
        $this->help['description'] = 'Makes the bot say things.';
        $this->help['command']['say something'] = "Makes that bot say 'something'";
        $this->help['command']['whosaidthat'] = "Find out who made the bot say that.";
    }


    function command_handler($name, $msg, $source)
    { // Start function handler()
        $args = $this->parse_com(
          $msg, array(
            "com",
            "args"
          )
        );
        switch ($args['com']) {
            case "say":
                if (strtolower(
                    $this->bot->core("settings")
                      ->get("Say", "OutputChannel")
                  ) == "origin"
                ) {
                    return $this->saythis($name, $args['args']);
                } else {
                    $this->bot->send_output(
                      $name, $this->saythis($name, $args['args']), $this->bot
                      ->core("settings")->get("Say", "OutputChannel")
                    );
                }
                return false;
            case "whosaidthat":
                return $this->whosaidthat();
        }
        $this->bot->send_help($name);
        return false;
    } // End function handler()

    function saythis($name, $message)
    {
        $this->whosaidthat['time'] = time();
        $this->whosaidthat['name'] = $name;
        $this->whosaidthat['what'] = $message;
        return $message;
    }


    function whosaidthat()
    {
        if (empty($this->whosaidthat)) {
            $output = "Nobody has used the say command since I logged in.";
        } else {
            $output = $this->whosaidthat['name'];
            $output .= ' made me say "';
            $output .= $this->whosaidthat['what'];
            $output .= '" ';
            $output .= time() - $this->whosaidthat['time'];
            $output .= ' seconds ago.';
        }
        return $output;
    }
} // End of Class
?>
