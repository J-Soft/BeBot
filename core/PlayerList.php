<?php
/*
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
new PlayerList($bot);
class PlayerList extends BasePassiveModule
{
	private $namecache = array();
	private $uidcache = array();

	public function __construct($bot)
	{
		parent::__construct($bot, get_class($this));
		$this->register_module('player');
		$dispatcher = Event_Dispatcher2::getInstance();
		$dispatcher->addObserver(array($this , 'signal_handle'), 'onPlayerName');
	}

	public function signal_handle($signal)
	{
		$data = $signal->getNotificationObject();
		list ($uid, $uname) = $data->message;
		if ($uid != 0 and $uid != - 1 and !empty($uname))
		{
			echo "Debug: Adding $uid ($uname)\n";
			$this->add($uid, $uname);
		}
		else
		{
			echo "Debug: Not adding $uid ($uname)\n";
		}
	}

	public function add($id, $name)
	{
		$name = ucfirst(strtolower($name));
		$this->namecache[$name] = array('id' => $id , 'expire' => time() + 21600);
		$this->uidcache[$id] = array('name' => $name , 'expire' => time() + 21600);
	}

	public function id($uname)
	{
		if ($uname instanceof BotError)
		{
			echo 'FIXME: core/PlayerList.php function id recieving BotError as $uname\nError is ' . $uname->get . '\n';
			return ($uname);
		}
		
		if (empty($uname))
		{
			$this->error->set("id() called with empty string");
			debug_print_backtrace();
			return ($this->error);
		}
		
		$uname = ucfirst(strtolower($uname));
		if (is_numeric($uname))
		{
			$this->debug_output("Attempting to look up an id for an id ($uname)");
			debug_print_backtrace();
			return $uname;
		}
		
		//Check if we have the player in cache.
		if (! isset($this->namecache[$uname]))
		{
			// Lookup user from Funcom server first
			echo "Debug: id() calling lookup_user for $uname\n";
			$this->bot->aoc->lookup_user($uname);
			// We should have the user in cache if we got a response from FC server
			if (isset($this->namecache[$uname]))
			{
				//$id = array_search($uname, $this->cache);//why are we putting it in $id then getting it again for return?
				$return = $this->namecache[$uname]['id'];
				return $return;
			}
			else
			{
				// If we we didn't get a responce from funcom, it's possible it was just a fluke, so try to get userid from whois
				// table if the information there isn't stale.
				$query = "SELECT ID,UPDATED FROM #___whois WHERE nickname = '$uname' LIMIT 1";
				$result = $this->bot->db->select($query, MYSQL_ASSOC);
				// If we have a whois result, and its under 48 hours old, 
				if (! empty($result))
				{
					if ($result[1] + 172800 >= time())
					{
						$age = time() - $result[1];
						$age = $age / 60 / 60;
						$this->bot->log("PLAYERLIST", "WARN", "Userid lookup for $uname failed, but using whois info that is $age hours old.");
						//cache in memory for future reference.
						$this->add($result[0]['ID'], $uname);
						return $result[0]['ID'];
					}
					else
					{
						// If we failed to get userid and we have no up to date whois information, the character most likely does NOT exist.
						$this->error->set("Unable to find player '$uname' and whois information is unreliable. The player might have been deleted.");
						return ($this->error);
					}
				}
			}
		}
		else if (isset($this->namecache[$uname]))
		{
			$return = $this->namecache[$uname]['id'];
			return $return;
		}
		else
		{
			$this->error->set("id() unable to find player '$uname'");
			return ($this->error);
		}
	}

	public function name($uid)
	{
		//echo "Looking up uname for $uid!\n";
		if (! is_numeric($uid))
		{
			$this->debug_output("Attempting to look up a username for a username ($uid)");
			debug_print_backtrace();
			return $uid;
		}
		
		if (empty($uid))
		{
			$this->error->set("name() called with empty string");
			debug_print_backtrace();
			return ($this->error);
		}

		//Check if we need to ask the server about the user
		if (! isset($this->uidcache[$uid]))
		{
			echo "Debug: name() calling lookup_user for $uid\n";
			$this->bot->aoc->lookup_user($uid);

			//The user should now definitively be in the cache if it exists.
			if (isset($this->uidcache[$uid]))
			{
				$return = $this->uidcache[$uid]['name'];
				return $return;
			}
			else
			{
				// If we we didn't get a responce from funcom, it's possible it was just a fluke, so try to get nickname from whois
				// table if the information there isn't stale.
				$query = "SELECT NICKNAME,UPDATED FROM #___whois WHERE id = '$uid' LIMIT 1";
				$result = $this->bot->db->select($query, MYSQL_ASSOC);
				// If we have a whois result, and its under 48 hours old, 
				if (! empty($result))
				{
					if (isset($result[1]) and $result[1] + 172800 >= time())
					{
						$age = time() - $result[1];
						$age = $age / 60 / 60;
						$this->bot->log("PLAYERLIST", "WARN", "Username lookup for $uid failed, but using whois info that is $age hours old.");
						//cache in memory for future reference.
						$this->add($uid, $result[0]['NICKNAME']);
						return $result[0]['NICKNAME'];
					}
				}
			}
		}
		else if (isset($this->uidcache[$uid]))
		{
			$return = $this->uidcache[$uid]['name'];
			return $return;
		}
		else
		{
			$this->error->set("name() unable to find player '$uid'");
			return ($this->error);
		}
	}

	public function exists($user)
	{
		$return = false;
		if (empty($user))
		{
			$this->error->set("exist() called with empty string.");
			debug_print_backtrace();
			return $this->error;
		}
		if (is_numeric($user))
		{
			// Looking for Name
			$return = isset($this->uidcache[$user]);
		}
		else
		{
			// Looking for ID
			$return = isset($this->namecache[$user]);
		}
		return $return;
	}

	public function get_namecache()
	{
		return $this->namecache;
	}

	public function get_uidcache()
	{
		return $this->uidcache;
	}

	public function dump()
	{
		var_dump($this->namecache);
		var_dump($this->uidcache);
	}
}
?>