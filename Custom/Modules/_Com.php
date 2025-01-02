<?php
/*
* Com.php - Module Com
* This module turns current bot into a Com(munication) dedicated tool, possibly via AoCp proxy for 1K+ friendlist.
* It works by calls on existing functions within Autouseradd and Massmsg modules that must be active but only in Com bot.
* It will join all of set channels, hosted by bots in which it should be set at least LEADER + sharing online DB is recommended.
* Those notified targets are then listened for member/buddy autoaddition in Com bot, so their Autouseradd should be disabled.
* Also, for LEADER and above, the Com bot offers !sendcom as replacing Massmsg's !announce - also preferably disabled on all targets.
* Endly, on any user logon, if some raid is running that is not yet locked, the player will be informed about and invited to join. 
* In short: Com module centralizes all management of big friendlist + massive /tell send, lighting all targets of such tasks.
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

$com = new Com($bot);

class Com extends BaseActiveModule
{
	var $raids = array();

    function __construct(&$bot)
    {

        parent::__construct($bot, get_class($this));
		$this->register_module("com");		
		$this->register_event("cron", "1min");
		$this->register_event("extprivgroup");
		$this->register_command('extpgmsg', 'sendcom', 'LEADER');
		$this -> bot -> core("settings") -> create("Com", "JoinCom", "join", "What's the join command on the target bot(s) (join by default).");
		$this -> bot -> core("settings") -> create("Com", "InvCom", "invite", "What's the invite command on the main bot (invite by default).");
		$this -> bot -> core("settings") -> create("Com", "InvBot", "", "What's the name of the main bot in which logging players are invited (empty to disable).");
        $this->bot->core("settings")
            ->create(
                "Com",
                "Channels",
                "",
                "List all surveyed bots (where this bot is at least MEMBER) in a comma-separated list."
            );
        $this->bot->dispatcher->connect(
            'Core.on_group_invite',
            array(
                 $this,
                 'pginvite'
            )
        );
		$this -> bot -> core("settings") -> create("Com", "RaidRunStr", "Raid is running:", "What's the string start for a running raid (default: 'Raid is running:'");
		$this -> bot -> core("settings") -> create("Com", "RaidLockStr", "raid is locked", "What's the string end for a locked raid (default: 'raid is locked'");
		$this->register_event("logon_notify");
    }
	
	function command_handler($name, $msg, $origin)
	{
		// no user command needed for this automated or redirected module
	}
	
	function cron()
    {
		if($this->bot->core("settings")->get("Com", "JoinCom")!="") $joincom = $this->bot->core("settings")->get("Com", "JoinCom");
		else $joincom = "join";
		if($this->bot->core("settings")->get("Com", "Channels")!="") {
			$bots = explode(",", $this->bot->core("settings")->get("Com", "Channels"));
			foreach($bots as $bot) {
				if (!$this->bot->core('player')->id($bot) instanceof BotError) {
					$result = $this->bot->db->select("SELECT user_level FROM #___users WHERE nickname = '" . $bot . "'");
					if (!empty($result)) {
						if ($result[0][0] != 2) {
							$this->bot->core("user")->add($this->bot->botname, $bot, 0, MEMBER, 1);
						}
					} else {
						$this->bot->core("user")->add($this->bot->botname, $bot, 0, MEMBER, 1);
					}					
					if ($this->bot->core('chat')->buddy_online($bot)) $online = true;
					else $online = false;
					$joined = false;
					foreach($this->bot->aoc->gid as $key => $val) {
						if($key==strtolower($bot)||$val==ucfirst($bot)) $joined = true;
					}
					if(!$joined&&$online&&ucfirst($bot)!=$this->bot->botname) $this->bot->send_tell(ucfirst($bot), $joincom, 1, false);
				}
			}
		}
    }
	
    public function pginvite($data)
    {
        $group = $data['source'];
        //echo "Debug received group invite: " . $group . "\n";
        if($this->bot->core("settings")->get("Com", "Channels")!="") {
			$bots = explode(",", $this->bot->core("settings")->get("Com", "Channels"));
			foreach($bots as $bot) {
				if(ucfirst($bot)==$group) {
					//echo "Debug: joining " . $group . "\n";
					$this->bot->core("chat")->pgroup_join($group);
					$this->register_event("gmsg", $group);
				}
			}
        }
    }

    function extprivgroup($group, $name, $msg)
    {		
		if ($this->bot->exists_module("autouseradd")) {
			$this->bot->core("autouseradd")->gmsg($name, $group, $msg);
		}
		if($this->bot->core("settings")->get("Com", "Channels")!="") {
			$bots = explode(",", $this->bot->core("settings")->get("Com", "Channels"));
			foreach($bots as $bot) {
				if(ucfirst($bot)==$name) {
					if(preg_match("/".$this->bot->core("settings")->get("Com", "RaidRunStr")."/",$msg)) {
						if(!preg_match("/".$this->bot->core("settings")->get("Com", "RaidLockStr")."/",$msg)) {
							$this->raids[ucfirst($bot)] = array("msg"=>$msg,"last"=>time());
						} else {
							unset($this->raids[ucfirst($bot)]);
						}
					}
				}
			}
		}
	}
	
    function notify($name, $startup = false)
    {
		if (!$startup) {
			$who = $this->bot->core("whois")->lookup($name);
			if (!$who instanceof BotError && $this->bot->core("security")->get_access_level_player($name)!=-1) {
				$limit = time()-60;
				foreach($this->raids AS $bot => $vals) {					
					if(isset($vals['msg'])&&isset($vals['last'])&&$vals['last']>$limit) {
						$this->bot->send_tell($name, $vals['msg']);
					}
				}
			}
			$bot = $this->bot->core("settings")->get("Com", "InvBot");
			if(ucfirst($bot)!=""&&ucfirst($bot)!=$name) {
				if($this->bot->core("settings")->get("Com", "InvCom")!="") $invcom = $this->bot->core("settings")->get("Com", "InvCom");
				else $invcom = "invite";			
				if ($this->bot->core('prefs')
						->get($name, 'AutoInv', 'receive_auto_invite') == 'On'
				) {
					$blob = $this->bot->core("tools")
						->chatcmd("preferences set autoinv receive_auto_invite Off", "Click here to remove yourself from autoinvite");
					$this->bot->send_tell(
						$name,
						"If you don't want this bot to invite you in the future, click " . $this->bot
							->core("tools")
							->make_blob('here', $blob) . " or type: /tell <botname> <pre>preferences set autoinv receive_auto_invite Off"
					);
					$this->bot->send_tell(ucfirst($bot), $invcom." ".$name, 1, false);
				}
			}
		}
	}
	
    function extpgmsg($pgroup, $name, $msg, $db = false)
    {
		$help = "Command usage: !sendcom message";
		if(substr($msg,0,8)=="sendcom ") {
			$msg = substr($msg,8);
			if(strlen($msg)>0) {
				if ($this->bot->exists_module("massmsg")) {
					$this->bot->core("massmsg")->mass_msg($name, $msg, $pgroup);
				}
			} else {
				$this->bot->send_tell($name, $help);
			}
		} else {
			$this->bot->send_tell($name, $help);
		}
	}

}

?>
