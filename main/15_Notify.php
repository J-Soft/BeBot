<?php
/*
* Handling of the notify list for BeBot.
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
$notify_core = new Notify_Core($bot);
class Notify_Core extends BasePassiveModule
{
	private $cache;

	function Notify_Core(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->register_module("notify");
		$this->update_cache();
	}

	function update_cache()
	{
		$this->cache = array();
		$notifylist = $this->bot->db->select("SELECT nickname FROM #___users WHERE notify = 1");
		if (! empty($notifylist))
		{
			foreach ($notifylist as $user)
			{
				$this->cache[ucfirst(strtolower($user[0]))] = TRUE;
			}
		}
	}

	function check($name)
	{
		return isset($this->cache[ucfirst(strtolower($name))]);
	}

	function add($source, $user)
	{
		$id = $this->bot->core("chat")->get_uid($user);
		$user = ucfirst(strtolower($user));
		if ($id == 0)
		{
			$this->error->set($user . " is no valid character name!");
			return $this->error;
		}
		// Make sure user is in users table
		$usr = $this->bot->db->select("SELECT notify FROM #___users WHERE nickname = '" . $user . "'");
		if (empty($usr))
		{
			// Need to add $user to users table as anonymous and silent
			$this->bot->core("user")->add($source, $user, 0, 0, 1);
		}
		else
		{
			// Check if already on notify
			if ($usr[0][0] == 1)
			{
				$this->error->set($user . " is already on the notify list!");
				return $this->error;
			}
		}
		// Mark for notify in users table and cache
		$this->bot->db->query("UPDATE #___users SET notify = 1 WHERE nickname = '" . $user . "'");
		$this->cache[$user] = TRUE;
		// Now add to notify list if not yet there
		$this->bot->core("chat")->buddy_add($id);
		return $user . " added to notify list!";
	}

	function del($user)
	{
		$id = $this->bot->core("chat")->get_uid($user);
		$user = ucfirst(strtolower($user));
		if ($id == 0)
		{
			$this->error->set($user . " is no valid character name!");
			return $this->error;
		}
		// Make sure $user is on notify (and in users table)
		$usr = $this->bot->db->select("SELECT notify FROM #___users WHERE nickname = '" . $user . "'");
		if (empty($usr))
		{
			$this->error->set($user . " is not on notify list!");
			return $this->error;
		}
		if ($usr[0][0] == 0)
		{
			$this->error->set($user . " is not on notify list!");
			return $this->error;
		}
		// Unflag notify for user in table and cache
		$this->bot->db->query("UPDATE #___users SET notify = 0 WHERE nickname = '" . $user . "'");
		unset($this->cache[$user]);
		// If in buddy list remove
		$this->bot->core("chat")->buddy_remove($id);
		$this->bot->db->query("UPDATE #___online SET status_gc = 0 WHERE nickname = '" . $user . "' AND botname = '" . $this->bot->botname . "'");
		return $user . " removed from notify list!";
	}

	function list_cache()
	{
		$count = 0;
		$notify_list = $this->cache;
		asort($notify_list);
		foreach ($notify_list as $key => $value)
		{
			$notify_db = $this->bot->db->select("SELECT notify FROM #___users WHERE nickname = '" . $key . "'");
			$msg .= $key;
			if ($value == 1)
			{
				$msg .= " [##green##Cache##end##]";
			}
			else
			{
				$msg .= " [##red##Cache##end##]";
			}
			if ($notify_db[0][0] == 1)
			{
				$msg .= "[##green##DB##end##]";
			}
			else
			{
				$msg .= "[##red##DB##end##]";
			}
			if ($notify_db[0][0] != $value)
			{
				$msg .= " ##yellow##MISMATCH##end##\n";
			}
			else
			{
				$msg .= "\n";
			}
			$count ++;
		}
		return $count . " members in <botname>'s notify cache :: " . $this->bot->core("tools")->make_blob("click to view", $msg);
	}

	function clear_cache()
	{
		$count = 0;
		$count = count($this->cache);
		unset($this->cache);
		$this->cache = array();
		return "Removed " . $count . " members from <botname>'s notify cache.";
	}

	function get_all()
	{
		Return $this->cache;
	}
}
?>