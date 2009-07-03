<?php
class signal_message
{
	public $channel;
	public $sender;
	public $message;
	
	public function __construct($channel, $sender, $message)
	{
		$this->channel=$channel;
		$this->sender=$sender;
		$this->message=$message;
	}
	
	public function get_array()
	{
		//Not required, but may be useful.
		return array('channel'=>$this->channel, 'sender'=>$this->sender, 'message'=>$this->$message);
	}
}
?>