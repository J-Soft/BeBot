<?php
/*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2012 J-Soft and the BeBot development team.
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
		//$dispatcher = Event_Dispatcher2::getInstance();
		//$dispatcher->addObserver(array($this , 'signal_handle'), 'onPlayerName');
		$this->bot->dispatcher->connect('core.on_player_name', array($this, 'signal_handle'));
		$this->bot->dispatcher->connect('core.on_player_id', array($this, 'signal_handle'));
	}

	public function signal_handle($data)
	{
		//$data = $signal->getNotificationObject();
		//list ($uid, $uname) = $data->message;
		
		/*
		echo "Debug core.on_player_name and on_player_id ";
		var_dump($data['id']);
		echo " " . $data['name'];
		echo "\n";
		*/
		if ((!$data['id'] < 1) && !empty($data['name']))
		{
			$this->add($data['id'], $data['name']);
		}
		else
		{
			echo "Was NOT added due to invalid userID\n";
		}
		
		return true;
	}

	public function add($id, $name)
	{
		$name = ucfirst(strtolower($name));
		
		/*echo "Debug caching $name ($id)\n";*/
		if ($id == 0)
		{
			$this->bot->log("DEBUG", "PlayerList", "Debug " . $name . " has an userid less than 1!!!\n");
			$this->bot->log("DEBUG", "PlayerList", $this->bot->debug_bt());
		}
		
		$this->namecache[$name] = array('id' => $id , 'expire' => time() + 21600);
		$this->uidcache[$id] = array('name' => $name , 'expire' => time() + 21600);
	}

	public function id($uname)
	{
		if ($uname instanceof BotError)
		{	

			$this->bot->log("DEBUG", "PlayerList", "FIXME: core/PlayerList.php function id recieving BotError as " . $uname . "\nError is: " . $uname->get() . "\n");
			$this->bot->log("DEBUG", "PlayerList", $this->bot->debug_bt());
			return $uname;
		}
		
		if (empty($uname))
		{
			// This is normal and can happen, if the user just types "!whois" etc.
			$this->error->set("Tried to get user id for an empty user name.");
			$this->bit->log("DEBUG", "PlayerList", "Tried to get user id for an empty user name.");
			$this->bot->log("DEBUG", "PlayerList", $this->bot->debug_bt());
			return $this->error;
		}
		$uname = ucfirst(strtolower($uname));
		if (is_numeric($uname))
		{
			$this->debug_output("Attempting to look up an id for an id ($uname)");
			return $uname;
		}
		// Check if we have not the player in cache.
		if (!isset($this->namecache[$uname]))
		{
			// Lookup user from Funcom server first
			$this->bot->aoc->lookup_user($uname);
			// We should have the user in cache if we got a response from FC server
			if (isset($this->namecache[$uname]))
			{
				return $this->namecache[$uname]['id'];
			}
			else
			{
				// If we we didn't get a response from funcom, it's possible it was just a fluke, so try to get userid from whois
				// table if the information there isn't stale.
				$query = "SELECT ID,UPDATED FROM #___whois WHERE nickname = '$uname' LIMIT 1";
				$result = $this->bot->db->select($query, MYSQL_ASSOC);
				// If we have a whois result, and its under 48 hours old, 
				if (!empty($result) && isset($result[0]['UPDATED']))
				{
					if (($result[0]['UPDATED'] + 172800 >= time()) && ($result[0]['ID'] >= 1))
					{
						$age = time() - $result[0]['UPDATED'];
						$age = $age / 60 / 60;
						// cache in memory for future reference.
						$this->add($result[0]['ID'], $uname);
						return $result[0]['ID'];
					}
				}
			}
		}
		else if (isset($this->namecache[$uname]))// so we HAVE the player in cache...
		{
			return $this->namecache[$uname]['id'];
		}
		
		$this->error->set("Unable to find player '$uname' and all lookups have failed. The player might have been deleted.");
		return $this->error;
	}

	/*
	* name() will send a lookup request to the chatserver to return the userid of someone.
	* 
	*/
	public function name($uid)
	{
		//echo "Looking up uname for $uid!\n";
		if (! is_numeric($uid))
		{
			$this->bot->log("DEBUG", "PlayerList", "Attempting to look up a username for a username (" . $uid . ")");
			$this->bot->log("DEBUG", "PlayerList", $this->bot->debug_bt());
			return $uid;
		}
		if (empty($uid))
		{
			$this->error->set("name() called with empty string");
			$this->bot->log("DEBUG", "PlayerList", "name() called with empty string");
			$this->bot->log("DEBUG", "PlayerList", $this->bot->debug_bt());
			return ($this->error);
		}
		//Check if we need to ask the server about the user
		if (! isset($this->uidcache[$uid]))
		{
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
				if (! empty($result) && isset($result[0]['UPDATED']))
				{
					if ($result[0]['updated'] + 172800 >= time())
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

		$this->error->set("name() unable to find player '$uid'");
		return ($this->error);
	}

	public function exists($user)
	{
		$return = false;
		if (empty($user))
		{
			$this->error->set("exist() called with empty string.");
			$this->bot->log("DEBUG", "PlayerList", "exist() called with empty string.");
			$this->bot->log("DEBUG", "PlayerList", $this->bot->debug_bt());
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