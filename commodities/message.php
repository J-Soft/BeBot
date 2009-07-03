<?php
class message
{
	public $source;
	public $sender;
	public $destination = array();
	public $message;
	
	function __construct($source, $sender, $message)
	{
		$this->source = $source;
		$this->sender = $sender;
		$this->message = $message;
	}
	
	function set_destination($destination, $overwrite = false)
	{
		if(empty($this->destination))
		{
			$this->destination[]=$destination;
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function add_destination($destination)
	{
		if(!in_array($destination, $this->destination))
		{
			$this->destination[]=$destination;
		}
	}
}
?>