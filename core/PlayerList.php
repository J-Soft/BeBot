<?php
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
		$dispatcher->addObserver(array($this, 'signal_handle'), 'onPlayerName');
	}
	
	public function signal_handle($signal)
	{
		$data = $signal -> getNotificationObject();
		list($uid, $uname) = $data -> message;
		if ($uid != 0 and $uid != -1)
		{
			$this->add($uid, $uname);
		}
	}
	
	public function add($id, $name)
	{
		$name = ucfirst(strtolower($name));
		$this -> namecache[$name] = array('id' => $id, 'expire' => time() +  21600);
		$this -> uidcache[$id] = array('name' => $name, 'expire' => time() +  21600);
	}
	
	public function id($uname)
	{
		if($uname instanceof BotError)
		{
			echo 'FIXME: core/PlayerList.php function id recieving BotError as $uname\nError is '.$uname -> get.'\n';
			return($uname);
		}
		$uname = ucfirst(strtolower($uname));
		if(is_numeric($uname))
		{
			$this->debug_output("Attempting to look up an id for an id ($uname)");
			return $uname;
		}
		else
		{
			//Check if we have the player in cache.
			if(!isset($this -> namecache[$uname]))
			{
				// Lookup user from Funcom server first
				$this -> bot -> aoc -> lookup_user($uname);
				
				// We should have the user in cache if we got a response from FC server
				if(isset($this -> namecache[$uname]))
				{
					//$id = array_search($uname, $this->cache);//why are we putting it in $id then getting it again for return?
					$return = $this -> namecache[$uname]['id'];
					return $return;
				}
				else
				{
					// If we we didn't get a responce from funcom, it's possible it was just a fluke, so try to get userid from whois
					// table if the information there isn't stale.
					$query = "SELECT ID,UPDATED FROM #___whois WHERE nickname = '$uname' LIMIT 1";
					$result = $this -> bot -> db -> select($query, MYSQL_ASSOC);
					// If we have a whois result, and its under 48 hours old, 
					if(!empty($result))
					{
						if ($result[1] + 172800 >= time())
						{
							$age = time() - $result[1];
							$age = $age / 60 / 60;
							$this -> bot -> log("PLAYERLIST", "WARN", "Userid lookup for $uname failed, but using whois info that is $age hours old.");
							//cache in memory for future reference.
							$this->add($result[0]['ID'], $uname);
							return $result[0]['ID'];
						}
						else
						{
							// If we failed to get userid and we have no up to date whois information, the character most likely does NOT exist.
							$this -> error -> set("Unable to find player '$uname' and whois information is unreliable. The player might have been deleted.");
							return($this -> error);
						}
					}
				}
			}
			else if (isset($this -> namecache[$uname]))
			{
				$return = $this -> namecache[$uname]['id'];
				return $return;
			}
			$this -> error -> set("id() unable to find player '$uname'");
			return($this -> error);
		}
	}
	
	public function name($uid)
	{
		//echo "Looking up uname for $uid!\n";
		if(!is_numeric($uid))
		{
			$this->error->set("name() called with non numeric value of $uid");
			debug_print_backtrace();
			return $this->error;
		}
		else
		{
			//Check if we need to ask the server about the user
			if(!isset($this -> uidcache[$uid]))
			{
				$this->bot->aoc->lookup_user($uid);
			}
			//The user should now definitively be in the cache if it exists.

			if(isset($this -> uidcache[$uid]))
			{
				$return = $this -> uidcache[$uid]['name'];
				return $return;
			}
			else
			{
				$this->error->set("name() unable to find player '$uid'");
				return($this->error);
			}
		}
	}
	
	public function exists($user)
	{
		$return = false;
		if(empty($user))
		{
			$this->error->set("exist() called with empty string.");
			debug_print_backtrace();
			return $this->error;
		}
		
		if (is_numeric($user))
		{
			// Looking for Name
			$return = isset($this -> uidcache[$user]);
		}
		else
		{
			// Looking for ID
			$return = isset($this -> namecache[$user]);
		}
		return $return;
	}
	
	public function get_namecache()
	{
		return $this -> namecache;
	}

	public function get_uidcache()
	{
		return $this -> uidcache;
	}
	
	public function dump()
	{
		var_dump($this -> namecache);
		var_dump($this -> uidcache);		
	}
	
}
?>