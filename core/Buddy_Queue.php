<?php
/*
* Buddy_Queue.php - Queue plugin to prevent flooding
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
$buddy_queue_core = new Buddy_Queue_Core($bot);
/*
The Class itself...
*/
class Buddy_Queue_Core extends BasePassiveModule
{
	private $queue;
	private $unused;
	private $last_add;

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this->register_module("buddy_queue");
		$this->register_event("cron", "1sec");
		$this->queue = array();
		$this->unused = 0;
		$this->last_add = time();
		$this->bot->core("settings")->create("Buddy_Queue", "Enabled", TRUE, "Should buddies be queued or added as requested? (Queueing buddies may slow down the bot.)");
		$this->bot->core("settings")->create("Buddy_Queue", "Rate", 1, "How many buddy add and removes should be done per second?", "1;2;3;4;5;6;7;8;9;10");
	}

	function do_add($uid)
	{
		if (! empty($uid) && $uid != 0 && $uid != - 1)
		{
			if (! ($this->bot->core("chat")->buddy_exists($uid)))
			{
				$this->bot->aoc->buddy_add($uid);
				$this->bot->log("BUDDY QUEUE", "BUDDY-ADD", $this->bot->core("player")->name($uid));
			}
		}
		else
		{
			$this->bot->log("BUDDY QUEUE", "BUDDY-ERROR", "Tried to add " . $this->bot->core("chat")->get_uname($uid) . " as a buddy when they already are one.");
		}
	}

	function do_delete($uid)
	{
		if (! empty($uid) && $uid != 0 && $uid != - 1)
		{
			if (($this->bot->core("chat")->buddy_exists($uid)))
			{
				$this->bot->aoc->buddy_remove($uid);
				$this->bot->core("online")->logoff($this->bot->core("chat")->get_uname($uid));
				$this->bot->log("BUDDY QUEUE", "BUDDY-DEL", $this->bot->core("chat")->get_uname($uid));
			}
			else
			{
				$this->bot->log("BUDDY QUEUE", "BUDDY-ERROR", "Tried to remove " . $this->bot->core("chat")->get_uname($uid) . " as a buddy when they are not one.");
			}
		}
	}

	/*
	This gets called on cron
	*/
	function cron()
	{
		// Make sure that there can't be bursts of delayed cron() calls:
		if (time() <= $this->last_add)
		{
			return;
		}
		$this->last_add = time();
		if (! empty($this->queue))
		{
			$count = 0;
			foreach ($this->queue as $uid => $action)
			{
				unset($this->queue[$uid]);
				if ($action)
				{
					$this->do_add($uid);
				}
				else
				{
					$this->do_delete($uid);
				}
				$count ++;
				if ($count >= $this->bot->core("settings")->get("Buddy_Queue", "Rate"))
				{
					return;
				}
			}
		}
		else
		{
			if ($this->unused > 0)
			{
				$this->unused --;
			}
		}
	}

	/*
	Checks if buddy can be added or removed. true if yes, false it has to be put to queue
	*/
	function check_queue()
	{
		if ((empty($this->queue) && $this->unused == 0) || ! ($this->bot->core("settings")->get("Buddy_Queue", "Enabled")))
		{
			$this->unused = 2;
			return true;
		}
		else
			return false;
	}

	/*
	Puts a buddy into the queue
	$type is a boolean, true means add the uid, false means delete it
	*/
	function into_queue($uid, $type)
	{
		$this->queue[$uid] = $type;
		return true;
	}
}
?>