<?php
/*
* Guide.php - Module Guide inspired from Budabot
*
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
* - Tyrence (RK5)
*
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

$guide = new Guide($bot);

class Guide extends BaseActiveModule
{
    function __construct(&$bot)
    {

        parent::__construct($bot, get_class($this));
		$this->register_module("guide");
        $this->register_command('all', 'guides', 'GUEST');
		$this->register_command('all', 'guide', 'GUEST');
		$this -> help['description'] = "Display various minimal guides ingame.";
		$this -> help['command']['guides'] = "Display a list of clickable ingame guides links.";
		$this -> help['command']['guide <word>'] = "Display the guide named <word>.";
		$this->register_command('all', 'aou', 'GUEST');
		$this -> help['command']['aou <search|read> [keywords|id]'] = "AO Universe guide <search> for [keyword] or <read> [id]";
		$this->bot->core("settings")->create("Guide", "RefSite", "Aoitems", "What site should be used for default items references ?", "Aoitems;Auno");
		$this->path = "./Modules/Ao/";
    }
	
	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^guides$/i", $msg))
			return ($this->guide_list($origin, $name));
		elseif (preg_match("/^guide ([a-zA-Z0-9_-]+)$/i", $msg))
			return $this -> guide_open($name, $msg, $origin);
		elseif (preg_match("/guide/i", $msg))
			return $this -> bot -> send_help($name);
		elseif (preg_match("/^aou search ([\w\s]+)$/i", $msg))
			return $this -> aou_search($name, $msg, $origin);
		elseif (preg_match("/^aou read ([0-9]+)$/i", $msg))
			return $this -> aou_read($name, $msg, $origin);
		elseif (preg_match("/aou/i", $msg))
			return $this -> bot -> send_help($name);
		else
			return ("##error##Error : Broken Guide plugin, received unhandled command: ".$msg."##end##");
	}
	
	function guide_list($origin,$name)
	{
		
		if ($handle = opendir($this->path."Guides/")) {
			$topics = array();
			while (false !== ($filename = readdir($handle))) {
				if(substr($filename,-4)==".txt") {
					$fileroot = substr($filename,0,-4);
					$topics[] = $fileroot;
				}
			}
		}
		
		$blob = "Available guides:\n\n";
		$count = 0;
		foreach ($topics as $topic) {
			$blob .= $this->bot->core("tools")->chatcmd("guide ".$topic, $topic)." ";
			$count++;
		}
		$this->bot->send_output($name, $this->bot->core("tools")->make_blob($count." local guide(s) list", $blob), $origin);
	}
	
	function guide_open($name,$msg,$origin)
	{
		$msg = strtolower(substr($msg,6));
		$filename = $this->path."Guides/".$msg.".txt";
		if(file_exists($filename)) {		
			$handle = fopen($filename, "r");
			$content = fread($handle, filesize($filename));
			fclose($handle);
			$this->bot->send_output($name, $this->bot->core("tools")->make_blob("Read local guide ".$msg, $content), $origin);
		} else {
			$this->bot->send_output($name, "Error : no guide with such name found.", $origin);
		}
	}
	
	function aou_search($name,$msg,$origin)
	{
		$msg = substr($msg,11);
		$aousearch = $this -> bot -> core("tools") -> get_site("https://www.ao-universe.com/mobile/parser.php?bot=bebot&mode=search&search=".urlencode($msg));
			
		if (strpos($aousearch, '<section>') !== false) {
			$sections = explode("<section>", $aousearch);
		} else {
			$this->bot->log("GUIDE", "AOU", "Error on distant XML search for ".$msg);
			$this->bot->send_output($name, "Error from AOU during your search ...", $origin);
			return;
		}
		
		$blob = "Found guides:";
		$count = 0;	$current = "";	
		foreach ($sections as $section) {
			$topic = $this -> bot -> core("tools") -> xmlparse($section, "name");
			$guides = explode("<guide>", $section);
			foreach ($guides as $guide) {
				if($current!=$topic) { $current = $topic; $blob .= "\n\n".$current.": "; }
				$id = $this -> bot -> core("tools") -> xmlparse($guide, "id");
				$title = $this -> bot -> core("tools") -> xmlparse($guide, "name");
				$blob .= $this->bot->core("tools")->chatcmd("aou read ".$id, $title)." ";
				$count++;
			}
		}
		$this->bot->send_output($name, $this->bot->core("tools")->make_blob($count." AOU guide(s) found", $blob), $origin);		
	}	

	function aou_read($name,$msg,$origin)
	{
		$msg = substr($msg,9);
		$aouread = $this -> bot -> core("tools") -> get_site("https://www.ao-universe.com/mobile/parser.php?bot=bebot&mode=view&id=".$msg);
		
		if (strpos($aouread, '<section>') !== false) {
			$section = $this -> bot -> core("tools") -> xmlparse($aouread, "section");
			$content = $this -> bot -> core("tools") -> xmlparse($section, "content");
		} else {
			$this->bot->log("GUIDE", "AOU", "Error on distant XML read for ".$msg);
			$this->bot->send_output($name, "Error from AOU during your read ...", $origin);
			return;
		}

		$blob = "Content of guide:\n\n";
		$title = $this -> bot -> core("tools") -> xmlparse($content, "name");
		$class = $this -> bot -> core("tools") -> xmlparse($content, "class");
		$faction = $this -> bot -> core("tools") -> xmlparse($content, "faction");
		$level = $this -> bot -> core("tools") -> xmlparse($content, "level");
		$blob .= $class." | ".$faction." | ".$level."\n\n";
		$text = $this -> bot -> core("tools") -> xmlparse($content, "text");
		$blob .= $this->processInput($text);
		$blob .= "\n\n".$this->bot->core("tools")->chatcmd("http://www.ao-universe.com/main.php?site=knowledge&id=".$msg, "Browse this guide on AOU", "start");
		$this->bot->send_output($name, $this->bot->core("tools")->make_blob("Read AOU guide ".$title, $blob), $origin);
		
	}
	
	private function processInput($input) {
		$input = preg_replace_callback("/\\[(item|itemname|itemicon)( nolink)?\\](\\d+)\\[\\/(item|itemname|itemicon)\\]/i", array($this, 'replaceItem'), $input);
		$input = preg_replace_callback("/\\[waypoint ([^\\]]+)\\]([^\\]]*)\\[\\/waypoint\\]/", array($this, 'replaceWaypoint'), $input);
		$input = preg_replace_callback("/\\[(localurl|url)=([^ \\]]+)\\]([^\\[]+)\\[\\/(localurl|url)\\]/", array($this, 'replaceGuideLinks'), $input);
		$input = preg_replace("/\\[img\\]([^\\[]+)\\[\\/img\\]/", "", $input);
		$input = preg_replace("/\\[color=#([0-9A-F]+)\\]([^\\[]+)\\[\\/color\\]/", "<font color=#\\1>\\2</font>", $input);
		$input = preg_replace("/\\[color=([^\\]]+)\\]([^\\[]+)\\[\\/color\\]/", "<\\1>\\2<end>", $input);
		$input = str_replace("[center]", "<center>", $input);
		$input = str_replace("[/center]", "</center>", $input);
		$input = str_replace("[i]", "<i>", $input);
		$input = str_replace("[/i]", "</i>", $input);
		$input = str_replace("[b]", "<b>", $input);
		$input = str_replace("[/b]", "</b>", $input);

		$pattern = "/(\\[[^\\]]+\\])/";
		$matches = preg_split($pattern, $input, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		$output = '';
		forEach ($matches as $match) {
			$output .= $this->processTag($match);
		}

		return $output;
	}
	
	private function replaceItem($arr) {
		$type = $arr[1];
		$id = $arr[3];
		$output = '';
		$icon = " <img src=rdb://11336> ";
		
		if($this->bot->core("settings")->get("Guide", "RefSite")=="Auno") {
			$item = " ".$this->bot->core("tools")->chatcmd("https://auno.org/ao/db.php?id=".$id, "Auno:".$id, "start")." ";
		} else {
			$item = " ".$this->bot->core("tools")->chatcmd("https://aoitems.com/item/".$id, "Aoitems:".$id, "start")." ";
		}
		
		if($this->bot->db->get_version("aorefs")>1) {
			$query = "SELECT ql, icon, name FROM aorefs WHERE id = ".$id." ORDER BY ql DESC LIMIT 1";
			$refs = $this->bot->db->select($query);
			if (!empty($refs)) {
				$icon = " <img src=rdb://".$refs[0][1]."> ";
				$item = ' <a href="itemref://'.$id.'/'.$id.'/'.$refs[0][0].'">'.$refs[0][2].'</a> ';
			}
		}
		
		if ($type == "item" || $type == "itemicon") {
			$output .= $icon;
		}
		
		if ($type == "item" || $type == "itemname") {
			$output .= $item;
		}

		return $output;		
	}	
	
	private function replaceWaypoint($arr) {
		$label = $arr[2];
		$params = explode(" ", $arr[1]);
		forEach ($params as $param) {
			list($name, $value) = explode("=", $param);
			$$name = $value;
		}
		
		return $this->bot->core("tools")->chatcmd("$x $y $pf", $label . " ({$x}x{$y})", "waypoint");
	}
	
	private function replaceGuideLinks($arr) {
		$url = $arr[2];
		$label = $arr[3];
		
		if (preg_match("/pid=(\\d+)/", $url, $idArray)) {
			return $this->bot->core("tools")->chatcmd("aou read " . $idArray[1], $label, "tell");
		} else {
			return $this->bot->core("tools")->chatcmd($url, $label, "start");
		}
	}	
	
	private function processTag($tag) {
		switch ($tag) {
			case "[ts_ts]":
				return " + ";
			case "[ts_ts2]":
				return " = ";
			case "[cttd]":
				return " | ";
			case "[cttr]":
			case "[br]":
				return "\n";
		}

		if ($tag[0] == '[') {
			return "";
		}

		return $tag;
	}

}

?>
