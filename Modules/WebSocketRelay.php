<?php
/*
* WebSocketRelay.php - WebSocket relay via Highway & optionnal AES-GCM
* Implemented from a fork of Taxoh's PHP-WebSockets-client-server
* Thanks to Nadyita, Yakachi & Tyrence for their advices
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

if ((float)phpversion() > 6.9) {
	// PWS Client working with PHP 7.0+
	include_once('Sources/WebSockets/websocket.php');
}

$WebSocketRelay = new WebSocketRelay($bot);

/*
The Class itself...
*/

/**
 * Websocket client that read/write random data.
 *
 * Console options:
 *  --uri <uri> : The URI to connect to
 *  --timeout <int> : Timeout in seconds, 55 default
 *  --fragment_size <int> : Fragment size as bytes, 4096*8 default
 */

 class WebSocketRelay extends BaseActiveModule
{

	var $last = 0;
	var $client = null;
	var $busy = false;
	var $crondelay = "2sec";
	var $room = "";
	var $start = 0;
	var $monbuds = false;
	var $others = [];

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
		$this->register_module("websocket");
		$this->register_event("connect");
		$this->register_event("privgroup");
        $this->register_event("gmsg", "org");
		$this->register_event("buddy");	
        $this->register_event("pgjoin");
        $this->register_event("pgleave");		
        $this->register_command("all", "ws", "SUPERADMIN");
        $this->help['description'] = 'Commands to control the WebSocket Relay under PHP 7.0 or beyond.';
        $this->help['command']['ws'] = "Supports connect, disconnect & others as subcommands.";
        $this->help['notes'] = "The AES-GCM encryption layer is optionnal, leave Password blank to skip it.";
        $this->bot->core("settings")
            ->create("WebSocket", "WsRelay", false, "Should the bot be relaying from/to WebSocket ?", "On;Off", true);
        $this->bot->core("settings")
            ->create("WebSocket", "Server", "wss://ws.nadybot.org", "Which WebSocket server is used ? (default is Nady's highway wss://ws.nadybot.org)");
        $this->bot->core("settings")
            ->create("WebSocket", "Port", "443", "Which port is used to connect to the WebSocket server ? (usually 80 or 8080 for ws & 443 for wss) ");
        $this->bot->core("settings")
            ->create("WebSocket", "Room", "", "Which WebSocket channel/room should be used ? (usually a long name with - separators) ");
        $this->bot->core("settings")
            ->create("WebSocket", "WhatChat", "both", "Which channel(s) should be relayed into Discord and vice versa ?", "gc;pgroup;both");
		$this -> bot -> core("settings")
			-> create("WebSocket", 'nearSyntax', '', 'What syntax (eg: @) to send message towards external WebSocket relay (leave empty to relay everything ; please note internal relay org <-> private bypasses it) ?');
		$this -> bot -> core("settings")
			-> create("WebSocket", 'Label', '', 'What optionnal label should we send to WebSocket (can be left empty) ?');
		$this -> bot -> core("settings")
			-> create("WebSocket", 'Password', '', 'What optionnal AES-GCM Password should we use to encrypt/decrypt messages of WebSocket (can be left empty for no encrypt/decrypt which is unsafe) ?');
		$this->start = time();
    }
	
    /*
    This gets called on command
    */
    function command_handler($name, $msg, $origin)
    {
        $msg = explode(" ", $msg);
        Switch ($msg[0]) {
            case 'ws':
                if(isset($msg[1])) {
					Switch ($msg[1]) {
						case 'connect':
						case 'others':
						case 'disconnect':
							return $this->ws($name,$msg[1]);
							break;
						default:
							return "##error##Error: provided subcommand not recognized##end##";
					}				
				} else return "##error##Error: No subcommand provided for WebSocket function##end##";
                break;
            default:
                return "##error##Error: WebSocketRelay Module received Unknown Command ##highlight##$msg[0]##end####end##";
        }
        return false;
    }	
	
	/*
    Called at bot startup
    */
    function connect()
    {	
		if ($this->bot->core("settings")->get("WebSocket", "WsRelay")) {
			$this->bot->core('settings')->save('WebSocket', 'WsRelay', FALSE);
			$this->ws('','connect');
		}
	}		

    /*
    This gets called on a msg in the guild channel
    */
    function gmsg($name, $group, $msg)
    {	
		if ($this->bot->core("settings")->get("WebSocket", "WsRelay")&&$this->client->opened&&$this->room!="") {
			if (strtolower($this->bot->core("settings")->get("WebSocket", "WhatChat"))=="gc" || strtolower($this->bot->core("settings")->get("WebSocket", "WhatChat"))=="both" ) {
				$syntax = $this->bot->core("settings")->get("WebSocket", "nearSyntax");
				if ($syntax==''||substr($msg,0,1)==$syntax) {
					if($syntax!='') $msg = trim(substr($msg,1));
					$id = $this->bot->core('player')->id($name);
					$source = $this->bot->botname;
					if($this->bot->core("settings")->get("WebSocket", "Label")!='') $label = '"'.$this->bot->core("settings")->get("WebSocket", "Label").'"';
					else $label = 'null';
					$dimension = $this->bot->dimension;
					$msg = str_replace('\\','\\\\',$msg);				
					$message = str_replace('"', '&quot;',$this->bot->core("tools")->cleanString($msg,1));
					$body = '{"type": "message", ';
					$body .= '"user": {"id": '.$id.', "name": "'.$name.'"}, ';
					$body .= '"source": {"name": "'.$source.'", "label": '.$label.', "channel": "", "type": "org", "server": '.$dimension.'}, '; // add channel Guild when Nad will support it ?
					$body .= '"message": "'.$message.'"}';
					if($this->bot->core("settings")->get("WebSocket", "Password")!=''&&$this->bot->core("settings")->get("WebSocket", "Password")!=' ') $body = $this->encrypt($body,$this->bot->core("settings")->get("WebSocket", "Password"));
					else $body = str_replace('"', '\"',$body);
					$payload = '{"type": "message", "room": "'.$this->room.'", "body": "'.$body.'" }';				
					$this->client->send($payload,'text',true);				
				}
			}
		}
	}	
	
    /*
    This gets called on a msg in the private group
    */
    function privgroup($name, $msg)
    {
		if ($this->bot->core("settings")->get("WebSocket", "WsRelay")&&$this->client->opened&&$this->room!="") {
			if (strtolower($this->bot->core("settings")->get("WebSocket", "WhatChat"))=="pgroup" || strtolower($this->bot->core("settings")->get("WebSocket", "WhatChat"))=="both" ) {
				$syntax = $this->bot->core("settings")->get("WebSocket", "nearSyntax");
				if ($syntax==''||substr($msg,0,1)==$syntax) {
					if($syntax!='') $msg = trim(substr($msg,1));
					$id = $this->bot->core('player')->id($name);
					$source = $this->bot->botname;
					if($this->bot->core("settings")->get("WebSocket", "Label")!='') $label = '"'.$this->bot->core("settings")->get("WebSocket", "Label").'"';
					else $label = 'null';
					$dimension = $this->bot->dimension;
					$msg = str_replace('\\','\\\\',$msg);
					$message = str_replace('"', '&quot;',$this->bot->core("tools")->cleanString($msg,1));
					$body = '{"type": "message", ';
					$body .= '"user": {"id": '.$id.', "name": "'.$name.'"}, ';
					$body .= '"source": {"name": "'.$source.'", "label": '.$label.', "channel": "", "type": "priv", "server": '.$dimension.'}, '; // add channel Guest when Nad will support it ?
					$body .= '"message": "'.$message.'"}';					
					if($this->bot->core("settings")->get("WebSocket", "Password")!=''&&$this->bot->core("settings")->get("WebSocket", "Password")!=' ') $body = $this->encrypt($body,$this->bot->core("settings")->get("WebSocket", "Password"));
					else $body = str_replace('"', '\"',$body);
					$payload = '{"type": "message", "room": "'.$this->room.'", "body": "'.$body.'" }';				
					$this->client->send($payload,'text',true);
				}
			}
		}
	}	
	
	/*
    This is on buddy logon/off
    */		
    function buddy($name, $msg)
    {
		if ($this->bot->core("settings")->get("WebSocket", "WsRelay")&&$this->client->opened&&$this->room!="") {
			if ($this->monbuds && ($msg == 1 || $msg == 0)) {
				if ($this->bot->core("notify")->check($name)) {
					if (!isset($this->bot->other_bots[$name])) {					
						$id = $this->bot->core('player')->id($name);
						$source = $this->bot->botname;
						if($this->bot->core("settings")->get("WebSocket", "Label")!='') $label = '"'.$this->bot->core("settings")->get("WebSocket", "Label").'"';
						else $label = 'null';
						$dimension = $this->bot->dimension;
						if($msg == 1) $type = 'logon';
						else $type = 'logoff';
						$body = '{"type": "'.$type.'", ';
						$body .= '"user": {"id": '.$id.', "name": "'.$name.'"}, ';
						$body .= '"source": {"name": "'.$source.'", "label": '.$label.', "channel": "", "type": "org", "server": '.$dimension.'}}'; // add channel Guild when Nad will support it ?
						if($this->bot->core("settings")->get("WebSocket", "Password")!=''&&$this->bot->core("settings")->get("WebSocket", "Password")!=' ') $body = $this->encrypt($body,$this->bot->core("settings")->get("WebSocket", "Password"));
						else $body = str_replace('"', '\"',$body);
						$payload = '{"type": "message", "room": "'.$this->room.'", "body": "'.$body.'"}';				
						$this->client->send($payload,'text',true);
					}
				}
			}
		}
	}

/*
 from TYR
	{"user": {"id": 1234, "name": "Toon"}, "type": "logoff", "source": 
	{"name": "Org Name", "label": "TEST", "channel": "", "type": "org", "server": 5}}
 from NAD
	{"type":"logon","user":{"id":1234,"name":"Toon"},
	"source":{"name":"Botname","label":null,"channel":null,"type":"priv","server":5}}
*/	
	
	/*
    This is on private join
    */			
    function pgjoin($name)
    {
		if ($this->bot->core("settings")->get("WebSocket", "WsRelay")&&$this->client->opened&&$this->room!="") {
			if (!isset($this->bot->other_bots[$name])) {
				$id = $this->bot->core('player')->id($name);
				$source = $this->bot->botname;
				if($this->bot->core("settings")->get("WebSocket", "Label")!='') $label = '"'.$this->bot->core("settings")->get("WebSocket", "Label").'"';
				else $label = 'null';
				$dimension = $this->bot->dimension;
				$body = '{"type": "logon", ';
				$body .= '"user": {"id": '.$id.', "name": "'.$name.'"}, ';
				$body .= '"source": {"name": "'.$source.'", "label": '.$label.', "channel": "", "type": "priv", "server": '.$dimension.'}}'; // add channel Guest when Nad will support it ?							
				if($this->bot->core("settings")->get("WebSocket", "Password")!=''&&$this->bot->core("settings")->get("WebSocket", "Password")!=' ') $body = $this->encrypt($body,$this->bot->core("settings")->get("WebSocket", "Password"));				
				else $body = str_replace('"', '\"',$body);
				$payload = '{"type": "message", "room": "'.$this->room.'", "body": "'.$body.'"}';				
				$this->client->send($payload,'text',true);
			}
		}
    }

	/*
    This is on private leave
    */		
    function pgleave($name)
    {
		if ($this->bot->core("settings")->get("WebSocket", "WsRelay")&&$this->client->opened&&$this->room!="") {
			if (!isset($this->bot->other_bots[$name])) {
				$id = $this->bot->core('player')->id($name);
				$source = $this->bot->botname;
				if($this->bot->core("settings")->get("WebSocket", "Label")!='') $label = '"'.$this->bot->core("settings")->get("WebSocket", "Label").'"';
				else $label = 'null';
				$dimension = $this->bot->dimension;
				$body = '{"type": "logoff", ';
				$body .= '"user": {"id": '.$id.', "name": "'.$name.'"}, ';
				$body .= '"source": {"name": "'.$source.'", "label": '.$label.', "channel": "", "type": "priv", "server": '.$dimension.'}}'; // add channel Guest when Nad will support it ?	
				if($this->bot->core("settings")->get("WebSocket", "Password")!=''&&$this->bot->core("settings")->get("WebSocket", "Password")!=' ') $body = $this->encrypt($body,$this->bot->core("settings")->get("WebSocket", "Password"));
				else $body = str_replace('"', '\"',$body);
				$payload = '{"type": "message", "room": "'.$this->room.'", "body": "'.$body.'"}';				
				$this->client->send($payload,'text',true);
			}
		}
    }		
	
	/*
    WS controls
    */	
    function ws($name,$param)
    {
		$return = "";
		$server = $this->bot->core("settings")->get("WebSocket", "Server");
		if((float)phpversion() <= 6.9) {
			$return = "WebSocket relay requires PHP from 7.0 and beyond.";
		}
		elseif ($param=="connect"&&$server!=''&&!$this->bot->core("settings")->get("WebSocket", "WsRelay")) {			
			$port = $this->bot->core("settings")->get("WebSocket", "Port");
			if($port!=''&&$port>0) {
				$url = $server.':'.$port;
			} else {
				$url = $server;
			}
			$room = $this->bot->core("settings")->get("WebSocket", "Room");
			if($room!='') {
				$this->room = $room;
			}
			$this->client = new WebSocketClient();
			$this->client->connect($url);
			$this->last = time();						
			$this->register_event("cron", $this->crondelay);
			$return = "WebSocket relay is being ##green##started##end##";
		}
		elseif ($param=="connect"&&$this->bot->core("settings")->get("WebSocket", "WsRelay")&&$this->client->opened) {
			$return = "WebSocket relay was already ##green##started##end##";
		}
		elseif ($param=="others") {
			$count = count($this->others);
			$tags = "";
			if($count>0) {
				foreach($this->others as $other) {
					if(strlen($tags)>0) $tags .= ','.$other;
					else $tags = $other;
				}
				if($count>1) $return = $count." other orgs tags seen so far : ".$tags;
				else $return = $count." other org tag seen so far : ".$tags;
			} else {
				$return = "No other org(s) tag(s) detected yet ...";
			}
			
		}		
		elseif ($param=="autokill"||($param=="disconnect"&&$this->bot->core("settings")->get("WebSocket", "WsRelay")&&$this->client->opened)) {
			$this->unregister_event("cron", $this->crondelay);
			$this->bot->core('settings')->save('WebSocket', 'WsRelay', FALSE);
			$this->last = 0;
			$this->room = "";
			$this->client->close();	
			$this->client = null;			
			$return = "WebSocket relay has been ##red##stopped##end##";
		}
		elseif ($param=="disconnect"&&!$this->bot->core("settings")->get("WebSocket", "WsRelay")) {
			$return = "WebSocket relay is already ##red##stopped##end##";
		}
		else {
			$return = "##error##WebSocket subcommand and/or parameter error ...##end##";
		}
		
		if ($name != "") {
			$this->bot->send_tell($name, $return);
		} else {
			$this->bot->send_output("", $return,$this->bot->core("settings")->get("WebSocket", "WhatChat"));
		}			
	}
	
	/*
    WS cron tasks
    */	
    function cron()
    {
		if($this->client->opened&&!$this->busy) {
			$this->busy = true;	
			$receives = $this->client->recv(false);
			$now = time();
			if(!$this->monbuds&&$now-$this->start>=300) $this->monbuds = true;
			if(!is_null($receives)&&count($receives)>0) {		
				foreach($receives as $receive) {
					$data = json_decode($receive['payload']);					
					if($data->type=='hello') {
						$this->bot->core('settings')->save('WebSocket', 'WsRelay', TRUE);
						if($this->room!='') {
							$this->client->send('{"type": "join", "room": "'.$this->room.'"}','text',true);
						}
					}
					elseif($data->type=='room-info') {
						$this->bot->log("WEBSOCKET", "INFO", "Joined the relay room");
						$this->onlineout();
						$body = '{"type": "online_list_request"}';
						if($this->bot->core("settings")->get("WebSocket", "Password")!=''&&$this->bot->core("settings")->get("WebSocket", "Password")!=' ') $body = $this->encrypt($body,$this->bot->core("settings")->get("WebSocket", "Password"));
						else $body = str_replace('"', '\"',$body);
						$this->client->send('{"type": "message", "room": "'.$this->room.'", "body": "'.$body.'"}','text',true);
					}
					elseif($data->type=='message') {
						if($this->bot->core("settings")->get("WebSocket", "Password")!=''&&$this->bot->core("settings")->get("WebSocket", "Password")!=' ') $data->body = $this->decrypt($data->body,$this->bot->core("settings")->get("WebSocket", "Password"));
						$body = json_decode($data->body);						
						if(!isset($body->type)||is_null($body->type)) {
							$this->bot->log("WEBSOCKET", "WARNING", "WebSocket incoming packet couldn't be decrypted, wrong Password value ?!");
						}
						elseif($body->type=='message'&&isset($body->message)&&is_string($body->message)) {
/*
   FROM TYR (without & with relay_prefix set)
    [body] => {"user": {"id": 1234, "name": "Toon"}, "message": "text-tyr", "type ": "message",
	"source": {"name": "Org Name", "label": "relay_prefix", "channel": "", "type": "org", "server": 5}}	
   FROM NAD (slight difference : shares botname instead of orgname)
    [body] => {"type":"message","message":"text-nad","user":{"name":"Toon","id":1234},
	"source":{"name":"Botname","server":5,"type":"priv"}}
*/
							if(isset($body->source->label)&&$body->source->label!=''&&$body->source->label!=' '&&$body->source->label!=null&&$body->source->label!='null'&&strlen($body->source->label)>2) $source = $body->source->label;
							else $source = $this->analyse(trim($body->source->name));
							$channel = ucfirst($body->source->type);						
							if(isset($body->user->name)&&$body->user->name!=null) $sender = '<a href="user://'.$body->user->name.'">'.$body->user->name.'</a>: ';						
							else $sender = '';
							$message = $body->message;
							$inc = '['.$source.'] ['.$channel.'] '.$sender.$message;
							$this->bot->send_output("", $inc,$this->bot->core("settings")->get("WebSocket", "WhatChat"));
						}
						elseif($body->type=='online_list_request') {
							$this->onlineout();
						}
						elseif($body->type=='online_list') {
							if(isset($body->online[0]->source->label)&&$body->online[0]->source->label!=''&&$body->online[0]->source->label!=' '&&$body->online[0]->source->label!=null&&$body->online[0]->source->label!='null'&&strlen($body->online[0]->source->label)>2) $source = $body->online[0]->source->label;
							else $source = $this->analyse(trim($body->online[0]->source->name));
							if(!in_array($source,$this->others)) array_push($this->others,$source);
							$sql = "UPDATE #___online SET status_gc = '0', status_pg = '0' WHERE botname = '" . $source . "'";
							$this->bot->db->query($sql);
							for($i=0;$i<2;$i++) {
								if(isset($body->online[$i])) {
									if($body->online[$i]->source->type=='priv') $column = 'status_pg';
									else $column = 'status_gc';								
									foreach($body->online[$i]->users as $user) {
										$this->statusin($source,$user->name,$column,1);
									}
								}
							}
						}
						elseif($body->type=='user_status') {
							if(isset($body->source->label)&&$body->source->label!=''&&$body->source->label!=' '&&$body->source->label!=null&&$body->source->label!='null'&&strlen($body->source->label)>2) $source = $body->source->label;
							else $source = $this->analyse(trim($body->source->name));
							if($body->source->type=='priv') $column = 'status_pg';
							else $column = 'status_gc';
							if($body->type=='logon') $newstatus = 1;
							else $newstatus = 0;						
							$this->statusin($source,$body->user->name,$column,$newstatus);
						}							
					}
				}
				$this->last = $now;
			} else {
				if($now-$this->last > $this->client->stall_time/2) {
					$this->client->send('','ping',true);				
					$this->last = $now;
				}
			}			
		} elseif(!$this->client->opened) {
			$this->ws('','autokill');
		}
		$this->busy = false;
	}

	/*
    Updates our online list from external statuses
    */		
	function statusin($name,$nickname,$column,$newstatus)
	{
		if ($this->bot->core("settings")->get("WebSocket", "WsRelay")&&$this->client->opened&&$this->room!="") {
			switch ($column) {
				case "status_gc":
					$leveln = ", level";
					$level = ", 0";
					$levele = ", level = 0";
					break;
				case "status_pg":
					$leveln = "";						
					$level = "";
					$levele = "";						
					break;
				default:
					$column = false;
					break;
			}
			if ($column) {	
				$sql = "INSERT INTO #___online (nickname, botname, " . $column . ", " . $column . "_changetime" . $leveln . ") ";
				$sql .= "VALUES ('" . $nickname . "', '" . $name . "', '" . $newstatus . "', '" . time(
					) . "'" . $level . ") ";
				$sql .= "ON DUPLICATE KEY UPDATE " . $column . " = '" . $newstatus . "', " . $column . "_changetime = '" . time(
					) . "'" . $levele;
				$this->bot->db->query($sql);
				$this->bot->core("whois")->lookup($nickname);
			}
		}
	}
	
	/*
    Sends our local online list to others in websocket
    */		
	function onlineout()
	{
		if ($this->bot->core("settings")->get("WebSocket", "WsRelay")&&$this->client->opened&&$this->room!="") {
			$onmsg = ""; $pg=""; $gc="";
            $online = $this->bot->db->select(
                "SELECT nickname, status_gc, status_pg FROM #___online WHERE (status_gc = 1 OR status_pg = 1) AND botname = '" . $this->bot->botname . "' ORDER BY nickname"
            );
            if (empty($online)) {
                $online = "";
            } else {
                foreach ($online as $on) {
                    if ($on[1] == 1) {
						if($gc!='') $com = ', ';
						else $com = '';
						$id = $this->bot->core('player')->id($on[0]);
                        $gc .= $com.'{"id": '.$id.', "name": "'.$on[0].'"}';
                    } elseif ($on[2] == 1) {
						if($pg!='') $com = ', ';
						else $com = '';						
						$id = $this->bot->core('player')->id($on[0]);
                        $pg .= $com.'{"id": '.$id.', "name": "'.$on[0].'"}';
                    }
                }
            }
			$source = $this->bot->botname;
			if($this->bot->core("settings")->get("WebSocket", "Label")!='') $label = '"'.$this->bot->core("settings")->get("WebSocket", "Label").'"';
			else $label = 'null';
			$dimension = $this->bot->dimension;
			$body = '{"type": "online_list", "online": [';
			$body .= '{"source": {"name": "'.$source.'", "label": '.$label.', "channel": "", "type": "org", "server": '.$dimension.'}, '; // add channel Guild when Nad will support it ?
			$body .= '"users": ['.$gc.']}, ';
			$body .= '{"source": {"name": "'.$source.'", "label": '.$label.', "channel": "Guest", "type": "priv", "server": '.$dimension.'}, '; // add channel Guest when Nad will support it ?
			$body .= '"users": ['.$pg.']}';			
			$body .= ']}';				
			if($this->bot->core("settings")->get("WebSocket", "Password")!=''&&$this->bot->core("settings")->get("WebSocket", "Password")!=' ') $body = $this->encrypt($body,$this->bot->core("settings")->get("WebSocket", "Password"));
			else $body = str_replace('"', '\"',$body);
			$payload = '{"type": "message", "room": "'.$this->room.'", "body": "'.$body.'"}';					
			$this->client->send($payload,'text',true);
		}
	}
	
	/*
    Analyses the source
    */		
	function analyse($source) {
		$return = $source;
		if (strpos($source,' ')!==false) {
			$return = $this->acronymize($source);
		} elseif(preg_match('/^[A-Z]{1}[a-zA-Z0-9-]*$/', $source)&&$this->bot->core('player')->id($source)) {
			$result = $this->bot->core("whois")->lookup($source);
			if (!isset($result["error"])&&isset($result["org"])&&strlen($result["org"])>0) {
				$return = $this->analyse(trim($result["org"]));
			}
		} else {
			$return = strtoupper(substr($source,0,3));
		}
		return $return;
	}
	
	/*
    Shortens a long source
    */		
	function acronymize($longname)
	{
		$letters=array();
		$words=explode(' ', $longname);
		foreach($words as $word)
		{
			$word = (substr($word, 0, 1));
			array_push($letters, $word);
		}
		$shortname = strtoupper(implode($letters));
		return $shortname;
	}
	
	/*
    Encrypt into AES-GCM
    */		
	function encrypt($textToEncrypt,$password)
	{
		$cipher = 'aes-256-gcm';
		$iv_len = openssl_cipher_iv_length($cipher);
		$key = openssl_digest($password, 'SHA256', true);		
		$exp = explode(" ", microtime());
		$micro = $exp[0];
		$secs = $exp[1];
		$iv = pack("NN", $secs, (float)$micro*100000000);
		$iv .= random_bytes($iv_len - strlen($iv));		
		$tag = "";
		$ciphertext = openssl_encrypt($textToEncrypt, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
		return base64_encode($iv.$tag.$ciphertext);
	}
	
	/*
    Decrypt from AES-GCM
    */		
	function decrypt($textToDecrypt,$password)
	{
		$cipher = 'aes-256-gcm';		
		$iv_len = openssl_cipher_iv_length($cipher);		
		$key = openssl_digest($password, 'SHA256', true);		
		$encrypted = base64_decode($textToDecrypt);
		$iv = substr($encrypted, 0, $iv_len);
		$tag = substr($encrypted, $iv_len, $tag_length = 16);
		$ciphertext = substr($encrypted, $iv_len + $tag_length);
		return openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
	}	
	
}

?>
