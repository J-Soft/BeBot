<?php
/*
* Mail.php - Allows asynchroneous communications between members of the bot
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
* This version combines the Original News and Raids Modules by Foxferal
* also includes updates/ideas by Naturalistic and Zarkingu.
*    Additional Database Fields added/changed:
*    ID renamed to TIME, ID added to Auto Inc starting at 1
*    TYPE field to Denote News Type I.E. 1 = News, 2 = Headline, 3 = Raid News
*/
$mail = new Mail($bot);
/*
The Class itself...
*/
class Mail extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        //$this -> register_event("cron", "12hour");
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("mail_message", "true") . "
						(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						received TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
						expires TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
						is_read BOOL DEFAULT false,
						mailbox VARCHAR(13),
						recipient VARCHAR(13),
						sender VARCHAR(13),
						message TEXT)"
        );
        //Register commands for this module
        $this->register_command('all', 'mail', 'GUEST');
		$this->register_command('all', 'mailed', 'GUEST');
        //Register events for this module
        $this->register_event("logon_notify");
        $this->register_event('connect');
        //Create settings
        $this->bot->core("settings")
            ->create(
                "Mail",
                "Max_life_read",
                "6_months",
                "How long should a read message be kept?",
                "1_week;2_weeks;1_month;6_months;1_year;2_years"
            );
        $this->bot->core("settings")
            ->create(
                "Mail",
                "Max_life_unread",
                "1_year",
                "How long should an unread message be kept?",
                "1_week;2_weeks;1_month;6_months;1_year;2_years"
            );
        //Create preferences
        $this->bot->core("prefs")
            ->create(
                "Mail",
                "Life_read",
                "How long should a read message be kept?",
                "1_month",
                "1_week;2_weeks;1_month;6_months;1_year;2_years",
                "GUEST"
            );
        $this->bot->core("prefs")
            ->create(
                "Mail",
                "Life_unread",
                "How long should an unread message be kept?",
                "6_months",
                "1_week;2_weeks;1_month;6_months;1_year;2_years",
                "GUEST"
            );
        $this->bot->core("prefs")
            ->create(
                "Mail",
                "Logon_notification",
                "Do you want to be notified about new mail when you log on?",
                "Yes",
                "Yes;No",
                "GUEST"
            );
        $this->help['description'] = "Module to send mail messages to other members of the bot.";
        $this->help['command']['mail'] = "Shows a list of messages for you.";
		$this->help['command']['mailed'] = "Shows a list of messages from you.";
        $this->help['command']['mail send <name> <message>'] = "Send the mail <message> to player <name>";
        $this->help['notes'] = "Mail is delivered to any registered alt of the player <name>";
    }


    function command_handler($name, $msg, $origin)
    {
        $this->error->reset();
        $com = $this->parse_com(
            $msg,
            array(
                 'com',
                 'sub',
                 'target',
                 'message'
            )
        );
		if(ucfirst($com['com'])=='Mailed') {
			return ($this->make_item_blob('Mailed list', $this->mail_sent($name)));
		} else {
			switch (ucfirst($com['sub'])) {
				case 'Delete':
					if ((isset($com['target'])) && (is_int(intval($com['target'])))) {
						return ($this->mail_delete($name, $com['target']));
						unset($com['target']); //We don't want to trigger the first check below.
					}
				//No break here as we want the list to be sent after deleting a message
				case '':
				case 'Read':
					if ((isset($com['target'])) && (is_int(intval($com['target'])))) {
						return ($this->make_item_blob(
							"Mail item {$com['target']}",
							$this->mail_read($name, $com['target'])
						));
					} else {
						return ($this->make_item_blob('Mail list', $this->mail_list($name)));
					}
					break;
				case 'Send':
					return ($this->mail_send($name, $com['target'], $com['message']));
					break;
				default:
					//No matches. Sending usage information (Another useless comment)
					$this->error->set("Unknown sub command '##highlight##{$com['sub']}##end##'. ");
					return ($this->error->message());
					break;
			}
		}
    }


    //Re-declaring gc() so that users always get a reply in tells (We don't want to share all our mail with everyone)
    function gc($name, $msg)
    {
        $this->tell($name, $msg);
    }


    //Re-declaring pgmsg() so that users always get a reply in tells (We don't want to share all our mail with everyone)
    function pgmsg($name, $msg)
    {
        $this->tell($name, $msg);
    }


    function notify($name, $startup = false)
    {
        //Notify people that are logging on if they've got new mail
        if ((!$startup)
            && ($this->bot->core("prefs")
                    ->get($name, "Mail", "Logon_notification") == true)
        ) {
            $mailbox = $this->bot->core("alts")->main($name);
            $no_of_messages = $this->new_mail_count($mailbox);
            if ($no_of_messages != 0) {
                $this->bot->send_tell(
                    $name,
                    $this->make_item_blob(
                        "You've got ##error##$no_of_messages##end## new messages.",
                        $this->mail_list($name)
                    )
                );
            }
        }
    }


    function connect()
    {
        $this->start = time() + $this->bot->crondelay;
    }


    function cron()
    {
        //TO DO: Check for messages that are older than "max life" and discard them.
    }


    function new_mail_count($mailbox)
    {
        //Getting the number of new mail (obviously)
        $query = "SELECT COUNT(id) AS no_of_messages FROM #___mail_message WHERE mailbox='$mailbox' AND is_read=0";
        $result = $this->bot->db->select($query, MYSQLI_ASSOC);
        if (empty($result)) {
            return 0;
        } else {
            return ($result[0]['no_of_messages']);
        }
    }


    function mail_list($user)
    {
        //Returns a window containing new and read mail
        $mailbox = $this->bot->core("alts")->main($user);
        $window = "##yellow##:::##end## Mail for ##highlight##$user##end## ($mailbox) ##yellow##:::##end##<br><br>";
        $query = "SELECT * FROM #___mail_message WHERE mailbox='$mailbox' ORDER BY is_read, received DESC";
        $messages = $this->bot->db->select($query, MYSQLI_ASSOC);
        if (!empty($messages)) {
            foreach ($messages as $message) {
                $message['message'] = base64_decode($message['message']);
                //Make the "unread" header if it hasn't been made already and there is unread mail
                if (($message['is_read'] == '0') && (empty($unread_header))) {
                    $window .= "--- Unread messages ---<br>";
                    $unread_header = true;
                }
                //Make the "read" header if it hasn't been made already and there is unread mail
                if (($message['is_read'] == '1') && (empty($read_header))) {
                    $window .= "<br>--- Read messages ---<br>";
                    $read_header = true;
                }
                //Only show the 20-23 first characters of the message in the list.
                if (strlen($message['message']) > 23) {
                    $message['message'] = substr($message['message'], 0, 20) . '...';
                }
                $window .= $this->bot->core("tools")
                        ->chatcmd("mail delete " . $message['id'], "[delete]") . " ";
                $window .= "{$message['received']} ";
                $window .= "To: ##highlight##{$message['recipient']}##end## ";
                $window .= "From: ##highlight##{$message['sender']}##end##  ::: ";
                $window .= $this->bot->core("tools")
                        ->chatcmd("mail read " . $message['id'], $message['message']) . "<br>";
            }
        } else {
            $window .= "No mail for you.";
        }
        return ($window);
    }
	
    function mail_sent($user)
    {
        //Returns a window containing sent mail
        $window = "##yellow##:::##end## Mail from ##highlight##$user##end## ##yellow##:::##end##<br><br>";
        $query = "SELECT * FROM #___mail_message WHERE sender='$user' ORDER BY id DESC";
        $messages = $this->bot->db->select($query, MYSQLI_ASSOC);
        if (!empty($messages)) {
            foreach ($messages as $message) {
                $message['message'] = base64_decode($message['message']);
                //for unread
                if (($message['is_read'] == '0')) {
                    $window .= "(Unread) ";
                }
                //for read
                if (($message['is_read'] == '1')) {
                    $window .= "(Read) ";
                }
                //Only show the 20-23 first characters of the message in the list.
                if (strlen($message['message']) > 23) {
                    $message['message'] = substr($message['message'], 0, 20) . '...';
                }
                $window .= "{$message['received']} ";
                $window .= "To: ##highlight##{$message['recipient']}##end## ";
                $window .= "From: ##highlight##{$message['sender']}##end##  ::: ";
                $window .= $message['message'] . "<br>";
            }
        } else {
            $window .= "No mail from you.";
        }
        return ($window);
    }	


    function mail_read($user, $id)
    {
        $mailbox = $this->bot->core("alts")->main($user);
        $window = "##yellow##:::##end## Mail for ##highlight##$user##end## ($mailbox) ##yellow##:::##end##<br><br>";
        $query = "SELECT * FROM #___mail_message WHERE id=$id AND mailbox='$mailbox'";
        $messages = $this->bot->db->select($query, MYSQLI_ASSOC);
        if (!empty($messages)) {
            $message = $messages[0];
            $window .= "##highlight##To:##end## {$message['recipient']}<br>";
            $window .= "##highlight##From:##end## {$message['sender']}<br>";
            $window .= "##highlight##Sent:##end## {$message['received']}<br><br>";
            $window .= "##normal##" . base64_decode($message['message']) . "##end##<br><br>";
            $window .= "[" . $this->bot->core("tools")
                    ->chatcmd("mail delete " . $message['id'], "delete") . "] ";
            $window .= "[" . $this->bot->core("tools")
                    ->chatcmd(
                        "mail send {$message['sender']} The message you sent on {$message['received']} has been read",
                        "Notify sender"
                    ) . "]";
            $time = strtotime(
                "+" . str_replace(
                    '_',
                    ' ',
                    $this->bot
                        ->core("prefs")->get($user, 'Mail', 'Life_read')
                )
            );
            $query = "UPDATE #___mail_message SET is_read=true, expires=FROM_UNIXTIME('$time') WHERE id=$id AND is_read=false";
            $this->bot->db->query($query);
        } else {
            $window .= "<br>Message $id was not found.";
        }
        return ($window);
    }


    function mail_send($sender, $recipient, $message)
    {
        $recipient = ucfirst(strtolower($recipient));
        $mailbox = $this->bot->core("alts")->main($recipient);
        $time = str_replace(
            '_',
            ' ',
            $this->bot->core("settings")
                ->get('Mail', 'Max_life_unread')
        );
        $expires = strtotime("+$time");
        if (!$this->bot->core("security")->check_access($recipient, "GUEST")) {
            $this->error->set(
                "The recipient ($recipient) is not a known member or guest of this bot. Please check spelling."
            );
            return ($this->error->message());
        } elseif (empty($message)) {
            return ("There is no point in sending empty messages. Usage: <pre>mail send &lt;recipient&gt; &lt;message&gt;");
        } else {
            $mail_message = mysqli_real_escape_string($this->bot->db->CONN,$message);
            $mail_message = str_replace('<', '&lt;', $mail_message);
            $mail_message = base64_encode($mail_message);
            $query
                = "INSERT INTO #___mail_message (mailbox, recipient, sender, message, expires) VALUES('$mailbox', '$recipient', '$sender', '$mail_message', FROM_UNIXTIME($expires))";
            $this->bot->db->query($query);
            $alts = $this->bot->core("alts")->get_alts($mailbox);
            $alts[] = $mailbox;
            foreach ($alts as $alt) {
                if ($this->bot->core("chat")->buddy_exists($alt)) {
                    if ($this->bot->core("chat")->buddy_online($alt)) {
                        $online[] = $alt;
                    }
                }
            }
            if (!empty($online)) {
                foreach ($online as $send) {
                    $this->bot->send_tell(
                        $send,
                        $this->make_item_blob("You've just received a new message.", $this->mail_list($send))
                    );
                }
            }
            return ("Message sent to $recipient ($mailbox).");
        }
    }


    function mail_delete($name, $id)
    {
        $mailbox = $this->bot->core("alts")->main($name);
        $query = "DELETE FROM #___mail_message WHERE id=$id AND mailbox='$mailbox'";
        $this->bot->db->query($query);
        if (mysqli_affected_rows($this->bot->db->CONN) == 1) {
            return ("Mail $id has been deleted.");
        } else {
            $this->error->set("Mail message '$id' was either not found or did not belong to $name.");
            return ($this->error->message());
        }
    }


    //Specialized make_blob to make ITEMREFs clickable.
    function make_item_blob($title, $content)
    {
        $content = str_replace("<botname>", $this->bot->botname, $content);
        $content = str_replace("<pre>", str_replace("\\", "", $this->bot->commpre), $content);
        $content = str_replace("\"", "'", $content);
        return "<a href=\"text://" . $content . "\">" . $title . "</a>";
    }
}

?>
