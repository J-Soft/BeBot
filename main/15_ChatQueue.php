<?php
/*
* ChatQueue.php - Queue plugin to prevent flooding of the chat server
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2009 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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
$chat_queue_core = new Chat_Queue_Core($bot);
/*
The Class itself...
*/
class Chat_Queue_Core extends BasePassiveModule
{
	private $que;
	private $que_low;
	private $msgs_left;
	private $last_call;

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this->register_module("chat_queue");
		$this->register_event("cron", "2sec");
		$this->queue = array();
		$this->queue_low = array();
	}

	/*
	This gets called on cron
	*/
	function cron()
	{
		if (! empty($this->queue))
		{
			$this->set_msgs();
			foreach ($this->queue as $key => $value)
			{
				if ($this->msgs_left >= 1)
				{
					$to = $value[0];
					$msg = $value[1];
					if ($value[2] == "tell")
					{
						$this->bot->log("TELL", "OUT", "-> " . $this->bot->core("chat")->get_uname($to) . ": " . $msg);
						$msg = utf8_encode($msg);
						$this->bot->aoc->send_tell($to, $msg);
					}
					else
					{
						$msg = utf8_encode($msg);
						$this->bot->aoc->send_group($to, $msg);
					}
					unset($this->queue[$key]);
					$this->msgs_left -= 1;
				}
			}
		}
		if (! empty($this->queue_low))
		{
			$this->set_msgs();
			foreach ($this->queue_low as $key => $value)
			{
				if ($this->msgs_left >= 1)
				{
					$to = $value[0];
					$msg = $value[1];
					if ($value[2] == "tell")
					{
						$this->bot->log("TELL", "OUT", "-> " . $this->bot->core("chat")->get_uname($to) . ": " . $msg);
						$msg = utf8_encode($msg);
						$this->bot->aoc->send_tell($to, $msg);
					}
					else
					{
						$msg = utf8_encode($msg);
						$this->bot->aoc->send_group($to, $msg);
					}
					unset($this->queue_low[$key]);
					$this->msgs_left -= 1;
				}
				else
					return false;
			}
		}
		return true;
	}

	/*
	Sents messages left...
	*/
	function set_msgs()
	{
		$time = time();
		$this->msgs_left += ($time - $this->last_call) / ($this->bot->telldelay / 1000);
		$this->last_call = $time;
		if ($this->msgs_left > 4)
			$this->msgs_left = 4;
	}

	/*
	Checks if tell can be sent. true if yes, false it has to be put to queue
	*/
	function check_queue()
	{
		$this->set_msgs();
		if (($this->msgs_left >= 1) && empty($this->queue) && empty($this->queue_low))
		{
			$this->msgs_left -= 1;
			return true;
		}
		return false;
	}

	/*
	Puts a msg into queue
	*/
	function into_queue($to, $msg, $type, $priority)
	{
		if ($priority == 0)
			$this->queue[] = array($to , $msg , $type);
		else
			$this->queue_low[] = array($to , $msg , $type);
	}
}
?>