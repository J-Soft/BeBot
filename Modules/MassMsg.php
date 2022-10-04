<?php
/*
* MassMsg.php - Sends out mass messages and invites.
* Improved by Bitnykk with great help from Tyrence
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
$massmsg = new MassMsg($bot);
/*
The Class itself...
*/
class MassMsg extends BaseActiveModule
{
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command('all', 'announce', 'LEADER');
        $this->register_command('all', 'massinv', 'LEADER');
        $this->bot->core("queue")->register($this, "invite", 0.2, 5);
        $this->help['description'] = 'Sends out mass messages and invites.';
        $this->help['command']['announce <message>'] = "Sends out announcement <message> as tells to all online members.";
        $this->help['command']['massinv <message>'] = "Sends out announcement <message> as tells to all online members and invites them to the private group.";
        $this->bot->core("settings")
            ->create('MassMsg', 'MassMsg', 'Both', 'Who should get mass messages and invites?', 'Guild;Private;Both;Online');
        $this->bot->core("settings")
            ->create(
                'MassMsg',
                'MinAccess',
                'GUEST',
                'Which access level must characters online have to receive mass messages and invites?',
                'ANONYMOUS;GUEST;MEMBER;LEADER;ADMIN;SUPERADMIN;OWNER'
            );
        $this->bot->core("settings")
            ->create(
                'MassMsg',
                'IncludePrefLink',
                true,
                'Should a link to preferences be included in the messages/invites?'
            );
        $this->bot->core("settings")
            ->create(
                'MassMsg',
                'tell_to_PG_users',
                false,
                'Should Bot Send message to users in PG instead of just Outputing to PG and ignoreing them'
            );
        $this->bot->core('prefs')
            ->create('MassMsg', 'receive_message', 'Do you want to receive mass-messages?', 'Yes', 'Yes;No');
        $this->bot->core('prefs')
            ->create('MassMsg', 'receive_invites', 'Do you want to receive mass-invites?', 'Yes', 'No;Yes');
        $this->bot->core("settings")
            ->create("MassMsg", "AlertDisc", false, "Do we alert Discord of Mass Msg/Inv ?");
        $this->bot->core("settings")
            ->create("MassMsg", "DiscChanId", "", "What Discord ChannelId in case we separate announces from main Discord channel (leave empty for all in main channel) ?");			
        $this->bot->core("settings")
            ->create("MassMsg", "DiscTag", "", "Should we add a Discord Tag (e.g. @here or @everyone) to announces/invites for notifying Discord users (leave empty for no notification) ?");
        $this->bot->core("settings")
            ->create("MassMsg", "AlertIrc", false, "Do we alert Irc of Mass Msg/Inv ?");				
        $this->bot->core("colors")->define_scheme("massmsg", "type", "aqua");
        $this->bot->core("colors")->define_scheme("massmsg", "msg", "orange");
        $this->bot->core("colors")
            ->define_scheme("massmsg", "disable", "seablue");
    }


    function command_handler($name, $msg, $origin)
    {
        $com = $this->parse_com(
            $msg,
            array(
                 'com',
                 'args'
            )
        );
        switch ($com['com']) {
            case 'announce':
                $this->bot->send_output($name, "Mass message being sent. Please stand by...", $origin);
                return ($this->mass_msg($name, $com['args'], 'Message'));
                break;
            case 'massinv':
                $this->bot->send_output($name, "Mass invite being sent. Please stand by...", $origin);
                return ($this->mass_msg($name, $com['args'], 'Invite'));
                break;
            default:
                $this->bot->send_help($name);
        }
    }


    function mass_msg($sender, $msg, $type)
    {		
		if ($this->bot->exists_module("discord")&&$this->bot->core("settings")->get("MassMsg", "AlertDisc")) {
			if($this->bot->core("settings")->get("MassMsg", "DiscChanId")) { $chan = $this->bot->core("settings")->get("MassMsg", "DiscChanId"); } else { $chan = ""; }
			if($this->bot->core("settings")->get("MassMsg", "DiscTag")) { $dctag = $this->bot->core("settings")->get("MassMsg", "DiscTag")." "; } else { $dctag = ""; }
			$this->bot->core("discord")->disc_alert($dctag.$sender." announced : " .$msg, $chan);
		}
		if ($this->bot->exists_module("irc")&&$this->bot->core("settings")->get("MassMsg", "AlertIrc")) {			
			$this->bot->core("irc")->send_irc("", "", $sender." announced : " .$msg);
		}
        //get a list of online users in the configured channel.
		$status = array();
        $users = $this->bot->core('online')->list_users(
            $this->bot
                ->core('settings')->get('MassMsg', 'MassMsg')
        );
        if ($users instanceof BotError) {
            return ($users);
        }
		if($this->bot->core('settings')->get('MassMsg', 'MassMsg')=='Online') {
			foreach ($users as $num => $recipient) {
				$check = $this->bot->core("online")->get_online_state($recipient);
				if($check['status']==0) {
					unset($users[$num]);
				}
			}
		}
        $msg = "##massmsg_type##$type from##end## ##highlight##$sender##end##: ##massmsg_msg##$msg##end##";
        $msg = $this->bot->core("colors")->parse($msg);
        $inchattell = $this->bot->core('settings')
            ->get('MassMsg', 'tell_to_PG_users');
        if (!$inchattell) {
            //Send to PG and ignore all in PG
            $this->bot->send_pgroup("\n" . $msg, null, true, false);
        }
		$msg = $msg." <a href=\"text://To join the bot click: <a href='chatcmd:///tell ".$this->bot->botname." join'>Join</a>";
		if ($this->bot->exists_module("raid")&&$this->bot->core("raid")->raid) {
			$msg = $msg."<br>To join the raid click: <a href='chatcmd:///tell ".$this->bot->botname." raid join'>Raid</a>";
			$msg = $msg."<br>To go lft click: <a href='chatcmd:///lft ".$this->bot->botname."'>LFT</a>";
		}
		$msg = $msg."\">Link(s)</a>";
        if ($this->bot->core('settings')->get('MassMsg', 'IncludePrefLink')) {
            $msg = $msg . "\n##massmsg_disable##You can disable receipt of mass messages and invites in the ##end##";
            $msg = $this->bot->core("colors")->parse($msg);
        }
        $msg = $this->bot->core("colors")->colorize("normal", $msg);
        foreach ($users as $recipient) {
            if ($this->bot->core('prefs')
                    ->get($recipient, 'MassMsg', 'receive_message') == 'Yes'
            ) {
                $massmsg = true;
            } else {
                $massmsg = false;
            }
            if ($this->bot->core('prefs')
                    ->get($recipient, 'MassMsg', 'receive_invites') == 'Yes'
            ) {
                $massinv = true;
            } else {
                $massinv = false;
            }
            //Add link to preferences according to settings
            if ($this->bot->core('settings')->get('MassMsg', 'IncludePrefLink')
            ) {
                if (!isset($blobs[(int)$massmsg][(int)$massinv])) {
                    $blob = $this->bot
                        ->core('prefs')
                        ->show_prefs($recipient, 'MassMsg', false);
                    $blob = $this->bot
                        ->core("colors")->parse($blob);
                    $blob = $this->bot
                        ->core("colors")->colorize("normal", $blob);
                    $blobs[(int)$massmsg][(int)$massinv] = $blob;
                }
                $message = $msg . $blobs[(int)$massmsg][(int)$massinv];
				if(strtolower($this->bot->game)=='ao'&&$this->bot->port>9000) {
					$message = str_replace("<pre>",$this->bot->commpre,$message);
				}
            } else {
                $message = $msg;
            }			
            //If they want messages they will get them regardless of type
            if ($massmsg) {
                if (!$inchattell
                    && $this->bot->core("online")
                        ->in_chat($recipient)
                ) {
                    $status[$recipient]['sent'] = false;
                    $status[$recipient]['pg'] = true;
                } else {
					if(strtolower($this->bot->game)=='ao'&&$this->bot->port>9000) $this->bot->aoc->send_tell($recipient, $message, "spam");
                    else $this->bot->send_tell($recipient, $message, 1, false, true, false);					
                    $status[$recipient]['sent'] = true;
                }
            } else {
                $status[$recipient]['sent'] = false;
            }
            //If type is an invite and they want invites, they will receive both a message and an invite regardless of receive_message setting
            if ($type == 'Invite') {
                if ($massinv) {
                    if ($this->bot->core("online")->in_chat($recipient)) {
                        $status[$recipient]['sent'] = false;
                        $status[$recipient]['pg'] = true;
                    } else {
                        //Check if they've already gotten the tell so we don't spam unneccessarily.
                        if (!$status[$recipient]['sent']) {
							if(strtolower($this->bot->game)=='ao'&&$this->bot->port>9000) $this->bot->aoc->send_tell($recipient, $message, "spam");
                            else $this->bot->send_tell($recipient, $message, 1, false, true, false);
                            $status[$recipient]['sent'] = true;
                        }
                        if ($this->bot->core("queue")->check_queue("invite")) {
                            $this->bot->core('chat')->pgroup_invite($recipient);
                        } else {
                            $this->bot->core("queue")
                                ->into_queue("invite", $recipient);
                        }
                        $status[$recipient]['invited'] = true;
                    }
                } else {
                    $status[$recipient]['invited'] = false;
                }
            }
        }
        return (count($users)." mass messages/invites complete. " . $this->make_status_blob($status));
    }


    function make_status_blob($status_array)
    {
		if(!is_array($status_array)||count($status_array)==0) return "Found no member to send message to.";
        $window = "<center>##blob_title##::: Status report for mass message :::##end##</center>\n";
        foreach ($status_array as $recipient => $status) {
            $window .= "\n##highlight##$recipient##end## - Message: ";
            if ($status['sent']) {
                $window .= "##lime##Sent to user##end##";
            } elseif ($status['pg']) {
                $window .= "##lime##Viewed in PG##end##";
            } else {
                $window .= "##error##Blocked by preferences##end##";
            }
            if (isset($status['invited'])) {
                if ($status['invited']) {
                    $window .= " - Invite to pgroup: ##lime##sent to user##end##";
                } else {
                    $window .= " - Invite to pgroup: ##error##blocked by preferences##end##";
                }
            }
            if (strtolower($this->bot->botname) == "bangbot") {
                if ($status['sent'] || $status['pg']) {
                    //Update announce count...
                    $result = $this->bot->db->select(
                        "SELECT announces FROM stats WHERE nickname = '" . $recipient . "'"
                    );
                    if (!empty($result)) {
                        $this->bot->db->query(
                            "UPDATE stats SET announces = announces+1 WHERE nickname = '" . $recipient . "'"
                        );
                    }
                }
            }
        }
        return ($this->bot->core('tools')->make_blob('report', $window));
    }

	function queue($name, $recipient)
	{
		$this->bot->core('chat')->pgroup_invite($recipient);
	}		
}

?>
