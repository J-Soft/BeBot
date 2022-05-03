<?php
/*
* Say.php - Makes the bot say things. :-)
*
* Say module by Andrew Zbikowski <andyzib@gmail.com> (AKA Glarawyn, RK1)
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
		$this->register_command("all", "sendtell", "ADMIN");
		$this->register_command("all", "sendhelp", "ADMIN");
        $this->bot->core("settings")
            ->create(
                "Say",
                "OutputChannel",
                "both",
                "Into which channel should the output of !say be sent? Either gc, pgmsg, both or original channel.",
                "gc;pgmsg;both;origin"
            );
        $this->help['description'] = 'Makes the bot say things.';
        $this->help['command']['say something'] = "Makes that bot say 'something' in org/private channel.";
		$this->help['command']['sendtell someone message'] = "Makes the bot send 'message' in /tell to someone.";
		$this->help['command']['sendtell someone command'] = "Makes the bot send command's help in /tell to someone.";
        $this->help['command']['whosaidthat'] = "Find out who made the bot say that.";
    }


    function command_handler($name, $msg, $source)
    { // Start function handler()
        $args = $this->parse_com(
            $msg,
            array(
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
                        $name,
                        $this->saythis($name, $args['args']),
                        $this->bot
                            ->core("settings")->get("Say", "OutputChannel")
                    );
                }
                return false;
			case "sendtell":
				return $this->sendtell($name, $args['args']);
			case "sendhelp":
				return $this->sendhelp($name, $args['args']);				
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
	
	
    function sendhelp($name, $message)
    {
        if(isset($message) && $message!="") {
			$args = explode(' ',$message);
			if($this->bot->core("whois")->lookup($args[0]) instanceof BotError) {
				return "Player ".$args[0]." doesn't exist";
			} elseif(strtolower($name)==strtolower($args[0])) {
				return "No use to send help to yourself";
			} elseif(count($args)==2 && isset($args[1]) && $args[1]!="") {
				$this->whosaidthat['time'] = time();
				$this->whosaidthat['name'] = $name;
				$this->whosaidthat['what'] = $message;	
				$this->bot->send_help($args[0],$args[1]);
				return "Help sent to ".$args[0];							
			} else {
				return "Can't send wrong command";
			}
		} else {
			return "Please provide player & command";
		}
        return $message;
    }		
	
	
    function sendtell($name, $message)
    {
        if(isset($message) && $message!="") {
			$args = explode(' ',$message, 2);
			if($this->bot->core("whois")->lookup($args[0]) instanceof BotError) {
				return "Player ".$args[0]." does not exist";
			} elseif(strtolower($name)==strtolower($args[0])) {
				return "No use to send a tell to yourself";
			} elseif(isset($args[1]) && $args[1]!="") {
				$this->whosaidthat['time'] = time();
				$this->whosaidthat['name'] = $name;
				$this->whosaidthat['what'] = $message;					
				$this->bot->send_tell($args[0],$args[1]);
				return "Message sent to ".$args[0];							
			} else {
				return "Can't send empty message";
			}
		} else {
			return "Please provide player & message";
		}
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
