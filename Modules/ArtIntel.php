<?php
/*
* ArtIntel.php - Adds 'intelligent' features to the bot
* Bitnykk module for Sembly Bebot @ Tussa & Masta
* Calls https://www.wolframalpha.com/ & https://www.pandorabots.com/
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 J-Soft and the BeBot development team.
*
* Developed by:
* - Alreadythere (RK2)
* - Blondengy (RK1)
* - Blueeagl3 (RK1)
* - Glarawyn (RK1)
* - Khalem (RK1)
* - Naturalistic (RK1)
* - Temar (RK1)
* - Bitnykk (RK5)
* See Credits file for all acknowledgements.
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

$artintel = new ArtIntel($bot);

class ArtIntel extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
		$this -> register_event("privgroup");
		$this -> register_event("gmsg", "org");
		$this -> bot -> core("settings") -> create("ArtIntel", 'ConvoSyntax', '?', 'The syntax to have a smart convo with A.I.');
		$this -> bot -> core("settings") -> create("ArtIntel", 'ConvoId', 'fb76251d8e3649ba', 'App Id on pandorabots.com for smart convo');
		$this -> bot -> core("settings") -> create("ArtIntel", 'ScienceSyntax', '=', 'The syntax to ask A.I. for science facts');
		$this -> bot -> core("settings") -> create("ArtIntel", 'ScienceId', 'DEMO', 'App Id on wolframalpha.com for science facts');

    }
	

	function command_handler($sender, $msg, $channel)
	{
		// no command to handle into this module
	}	
	
	/*
	This gets called on a msg in the private group.
	*/
	function privgroup($name, $msg)
	{
		$aic = $this->bot->core("settings")->get('ArtIntel', 'ConvoSyntax');
		$ais = $this->bot->core("settings")->get('ArtIntel', 'ScienceSyntax');
		if(strlen($msg)>2 && substr($msg,0,1)==$aic ) {
			$this->ainit($name, $msg, 'convo', 'private');
		} elseif(strlen($msg)>2 && substr($msg,0,1)==$ais ) {
			$this->ainit($name, $msg, 'science', 'private');
		}
	}

	/*
	This gets called on a msg in the guild group.
	*/
	function gmsg($name, $group, $msg)
	{
		$aic = $this->bot->core("settings")->get('ArtIntel', 'ConvoSyntax');
		$ais = $this->bot->core("settings")->get('ArtIntel', 'ScienceSyntax');
		if(strlen($msg)>2 && substr($msg,0,1)==$aic ) {
			$this->ainit($name, $msg, 'convo', 'guild');
		} elseif(strlen($msg)>2 && substr($msg,0,1)==$ais ) {
			$this->ainit($name, $msg, 'science', 'guild');
		}
	}	

	/*
	This gets called on any triggered request.
	*/
	function ainit($name, $msg, $type, $chan)
	{
		$req = substr($msg,1);
		$reply = "I have no answer for that.";
		if($type=='convo') {
			$data["input"] = $req;
			$data["botcust2"] = substr(md5($name), 0, 16);		
			$result = $this -> post_it($data, "https://www.pandorabots.com/pandora/talk?botid=".$this->bot->core("settings")->get('ArtIntel', 'ConvoId')); 
			if (isset($result["errno"]))
			{
				return($result);
			}
			else
			{
				$answer = str_replace("\n","",$result);
				$test = preg_match('#<H2>(.+)</H2>#', $answer, $spliced);
				$spliced = trim($spliced[1]);			
				if ($test ==1 && strlen($spliced) > 0 ) {
					$trans = array(
						"  " => " ",
						"A.L.I.C.E." => "Luci",
						"1967" => "29480",
						"2 MB" => "300 TB",
						"64MB" => "200 TB",
						" ." => ".",
						"\n" => " "
						);
					$reply = strtr($spliced, $trans);
				}
			}
		}
		if($type=='science') {
			if(substr($req,-1)!="?") $req = $req."?";
			$url = "http://api.wolframalpha.com/v1/result?appid=".$this->bot->core("settings")->get('ArtIntel', 'ScienceId')."&i=".urlencode($req);
			$getit = $this->bot->core("tools")->get_site($url);
			if(strlen($getit)>0&&substr($getit,0,5)!="Error") {
				$reply = utf8_decode($getit);
			}
		}
		if($chan=='private') {
			$this -> bot -> send_pgroup(":: ".$reply." ::");
		} else {
			$this -> bot -> send_gc(":: ".$reply." ::");
		}
	}
	
	/*
	This formats the final convo request.
	*/	
	function post_it($datastream, $url)
	{
		$reqbody = "";
		foreach($datastream as $key => $val) {
			if (!empty($reqbody)) $reqbody.= "&";
			$reqbody.= $key."=".urlencode($val);
		}
		$opts = array('http' =>
			array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $reqbody
			)
		);
		$context = stream_context_create($opts);
		$result = file_get_contents($url, false, $context);
		return $result;
	}	
	
	
}

?>
