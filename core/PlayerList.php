<?php
new PlayerList($bot);
class PlayerList extends BasePassiveModule
{
	private $cache = array();

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
		$this->add($uid, $uname);
	}
	
	public function add($id, $name)
	{
		$this->cache[(int)$id] = $name;
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
			$this->debug_output("Attempting to look up an id for an id ($uname) {$this->cache[$uname]}");
			return $uname;
		}
		else
		{
			//Check if we have the player in cache.
			if(!$this->exists($uname))
			{
				//Check the database
				$query = "SELECT ID FROM #___whois WHERE nickname = '$uname' LIMIT 1";
				$result = $this->bot->db->select($query, MYSQL_ASSOC);
				if(!empty($result))
				{
					//cache in memory for future reference.
					$this->add($result[0]['ID'], $uname);
					return $result[0]['ID'];
				}
				$this->bot->aoc->lookup_user($uname);
			}
			
			//The user should now definitively in the cache if it exists.
			if($this->exists($uname))
			{
				$id = array_search($uname, $this->cache);//why are we putting it in $id then getting it again for return?
				return(array_search($uname, $this->cache));
			}
			else
			{
				$this->error->set("Unable to find player '$uname'");
				return($this->error);
			}
		}
	}
	
	public function name($uid)
	{
// 			echo "Looking up uname for $uid!\n";
		if(!is_numeric($uid))
		{
			$this->debug_output("Attempting to look up a name for a name ($uid) ".array_search($uid, $this->cache));
			return $uid;
		}
		else
		{
			//Check if we need to ask the server about the user
			if(!$this->exists($uid))
			{
				$this->bot->aoc->lookup_user($uid);
			}
			//The user should now definitively be in the cache if it exists.
			if($this->exists($uid))
			{
				return $this->cache[$uid];
			}
			else
			{
				$this->error->set("Unable to find player '$uid'");
				return($this->error);
			}
		}
	}
	
	public function exists($user)
	{
		if(empty($user))
		{
			$this->error->set("Attempted to check if an empty player exists.");
			debug_print_backtrace();
			return $this->error;
		}
		return (isset($this->cache[$user]) || in_array($user, $this->cache));
	}
	
	public function get_cache()
	{
		return $this->cache;
	}
	
	public function dump()
	{
		var_dump($this->cache);
	}
	
}
?>