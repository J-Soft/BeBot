<?php
/*
* AOChatWrapper.php - A class of wrappers around AOChat functions.
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg Stens?s, ShadowRealm Creations and the BeBot development team.
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
*
* File last changed at $LastChangedDate: 2007-07-25 11:54:01 -0700 (Wed, 25 Jul 2007) $
* Revision: $Id: 15_AOChatWrapper.php 1833 2008-11-30 22:09:06Z alreadythere $
*/


$aochat_wrapper_core = new AOChatWrapper_Core($bot);

/*
The Class itself...
*/
class AOChatWrapper_Core extends BasePassiveModule
{
	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_module("chat");
	}

	/*
	This is a wrapper function for aoc->get_uid() that checks the whois cache if aoc->get_uid() failes
	*/
	function get_uid($user)
	{
		if(empty($user))
		{
			$this->error->set('No user specified');
			// TODO: adapt all calls to get_uid() to check for instanceof BotError?
			// return($this->error);
			return false;
		}

		//Attempt to get uid from FC (This fails randomly)
		$uid = $this -> bot -> aoc -> get_uid($user);

		//When it fails attempt to get it from the cache.
		if($uid === false)
		{
			$db_uid = $this -> bot -> db -> select("SELECT ID FROM #___whois WHERE nickname = '" . $user . "' LIMIT 1", MYSQL_ASSOC);

			if (!empty($db_uid))
			{
				$uid = $db_uid[0]['ID'];
			}
			else
			{
				$this -> error -> set("I was unable to get the user id for user: '$user'");
				// TODO: adapt all calls to get_uid() to check for instanceof BotError?
				// return($this->error);
				return false;
			}
		}

		return $uid;
	}

	/*
	This is a wrapper function for aoc->get_uname() that checks the whois cache if aoc->get_uname() failes
	*/
	function get_uname($user)
	{
		if ($user === false || $user === 0 || $user === -1)
		{
			return false;
		}

		$name = $this -> bot -> aoc -> get_uname($user);

		if($name === false || $name === 0 || $name === -1)
		{
			$db_name = $this -> bot -> db -> select("SELECT nickname FROM #___whois WHERE ID = '" . $user . "' OR nickname = '" . $user . "'");

			if (!empty($db_name))
			{
				$name = $db_name[0][0];
			}
			else
			{
				$name = false;
				$this -> bot -> log('GETUNAME', 'FAILED', "I was unable to get the user name belonging to: $user");
			}
		}
		return $name;
	}

	/* Buddies */
	function buddy_add($user, $que = TRUE)
	{
		$add = true;
		if (empty($user) || ($uid = $this -> get_uid($user)) === false)
		{
			return false;
		}
		else
		{
			if (!($this -> bot -> aoc -> buddy_exists($uid)) && $uid != 0 && $uid != -1
			&& $uid != $this -> get_uid($this -> bot -> botname) && $this -> get_uname($uid) != -1)
			{
				if (!$que || $this -> bot -> core("buddy_queue") -> check_queue())
				{
					$this -> bot -> aoc -> buddy_add($uid);
					$this -> bot -> log("BUDDY", "BUDDY-ADD", $this -> get_uname($uid));
					return true;
				}
				else
				{
					$return = $this -> bot -> core("buddy_queue") -> into_queue($uid, $add);
					return $return;
				}
			}
			else
				return false;
		}
	}

	function buddy_remove($user)
	{
		$add = false;
		if (empty($user) || ($uid = $this -> get_uid($user)) === false)
		{
			return false;
		}
		else
		{
			if (($this -> bot -> aoc -> buddy_exists($uid)))
			{
				if ($this -> bot -> core("buddy_queue") -> check_queue())
				{
					$this -> bot -> aoc -> buddy_remove($uid);
					$this -> bot -> log("BUDDY", "BUDDY-DEL", $this -> get_uname($uid));
					return true;
				}
				else
				{
					$return = $this -> bot -> core("buddy_queue") -> into_queue($uid, $add);
					return $return;
				}
			}
			else
				return false;
		}
	}

	function buddy_exists($who)
	{
		return $this -> bot -> aoc -> buddy_exists($who);
	}

	function buddy_online($who)
	{
		return $this -> bot -> aoc -> buddy_online($who);
	}

	/*
	accept invite to private group
	*/
	function pgroup_join($group)
	{
		if ($group == NULL)
			return false;

		$this -> bot -> log("PGRP", "ACCEPT", "Accepting Invite for Private Group [" . $group . "]");
		return $this -> bot -> aoc -> privategroup_join($group);
	}

	/*
	leave private group
	*/
	function pgroup_leave($group)
	{
		if ($group == NULL)
			return false;

		$this -> bot -> log("PGRP", "LEAVE", "Leaving Private Group [" . $group . "]");
		return $this -> bot -> aoc -> privategroup_leave($group);
	}

	/*
	decline private group
	*/
	function pgroup_decline($group)
	{
		return $this -> send_pgroup_leave($group);
	}

	/*
	private group status
	added - 2007/Sep/1 - anarchyonline@mafoo.org
	*/
	function pgroup_status($group)
	{
		if ($group == NULL)
			$group = $this -> bot -> botname;
		return $this -> bot -> aoc -> group_status($group);
	}

	function pgroup_invite($user)
	{
		$this -> bot -> log("PGRP", "INVITE", "Invited " . $user . " to private group");
		return $this -> bot -> aoc -> privategroup_invite($user);
	}

	function pgroup_kick($user)
	{
		$this -> bot -> log("PGRP", "KICK", "Kicking " . $user . " from private group");
		return $this -> bot -> aoc -> privategroup_kick($user);
	}

	function pgroup_kick_all()
	{
		$this -> bot -> log("PGRP", "KICKALL", "Kicking all user from private group");
		return $this -> bot -> aoc -> privategroup_kick_all();
	}

	function lookup_group($arg, $type=0)
	{
		return $this -> bot -> aoc -> lookup_group($arg, $type);
	}

	function get_gname($g)
	{
		return $this -> bot -> aoc -> get_gname($g);
	}
}
?>