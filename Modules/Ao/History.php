<?php
/*
* History.php - Online plugin to display user history
*
* HTML processing by Foxferal RK1
* XML processing by Alreadythere RK2
* Put together and colourised by Jackjonez RK1
* Improved by Bitnykk (2021) with thanks to Auno & AP+Tyrence
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

$history = new History($bot);

class History extends BaseActiveModule
{
	var $path;
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->help['description'] = "Plugin to display the history/archive of a player.";
        $this->register_command('all', 'history', 'GUEST');
		$this->register_command('all', 'archive', 'GUEST');
		$this -> register_alias("archive", "archives");
        $this->help['command']['history <name>'] = "Show the history of player <name>";
		$this->help['command']['archive <name>'] = "Show the archive of player <name>";
        $this->help['notes'] = "(!history checks AP/Tyrence's RK-5/19 while !archive checks Auno RK-1/2)";
		$this -> bot -> core("settings") -> create("History", "CleanDelay", 30, "After how many Days should a History cache file be considered old and deleted ?", "7;15;30;60;120");
		$this->path = "./Extras/History";
		$this->register_event("cron", "24hour");
    }


    function command_handler($name, $msg, $origin)
    {
        if (preg_match("/^history (.+)$/i", $msg, $info)) {
            return $this->player_history($name, $origin, $info[1]);			
        } elseif (preg_match("/^history$/i", $msg, $info)) {
                return "No player name specified";
        } elseif (preg_match("/^archive (.+)$/i", $msg, $info)) {
            return $this->player_archive($name, $origin, $info[1]);			
        } elseif (preg_match("/^archive$/i", $msg, $info)) {
                return "No player name specified";
        } else {
			return false;
		}
    }

    function player_history($asker, $origin, $name)
    {
        $name = ucfirst(strtolower($name));
        if (! ($this->bot->core("player")->id($name) instanceof BotError) ) {
			// START AP/Tyr HISTORY
			$dim = $this->bot->dimension;			
			if($dim==5||$dim==6) {
				$file = $dim.".".$name.".json";
				$output = "##blob_title##::: History for " . $name . " on RK".$dim." :::##end##\n\n";
				if(file_exists($this->path."/".$file)) {
					$content = file_get_contents($this->path."/".$file);
					$distant = false;
				} else {				
					$content = $this->bot->core("tools")->get_site("https://pork.jkbff.com/pork/history.php?server=".$dim."&name=".$name);
					$distant = true;
				}
				if (!($content instanceof BotError)) {
					if (strpos($content, '{"nickname":"'.$name.'",') !== false) {
						$history = json_decode($content);
						foreach($history AS $result) {
// nickname char_id level breed gender defender_rank guild_rank_name last_changed faction guild_id guild_name deleted
							if (empty($result->guild_name)) {
								$result->guild_name = "##white##Not in guild##end##";
							}
							if (empty($result->faction)) {
								$result->faction = "##white##No faction##end##";
							} else {
								if ($result->faction == 'Omni') {
									$result->faction = "##omni##Omni ##end##";
								} else {
									if ($result->faction == 'Clan') {
										$result->faction = "##clan##Clan ##end##";
									} else {
										if ($result->faction == 'Neutral') {
											$result->faction = "##neutral##Neutral ##end##";
										}
									}
								}
							}
							if (!empty($result->guild_rank_name)) {
								$result->guild_rank_name = "##aqua##(" . $result->guild_rank_name . ")##end##";
							}
							$output .= "##blob_text##Date:##end## " . date('Y-m-d',$result->last_changed) . "##blob_text##  ";
							$output .= "Level:##end## " . $result->level . " ##blob_text## AI:##end## " . $result->defender_rank . " ";
							$output .= "##blob_text##&#8226;##end## " . $result->faction . " ##blob_text##&#8226;##end## ";
							$output .= $result->guild_name . " " . $result->guild_rank_name . "\n";
						}
						$this->bot->send_output($asker, "RK".$dim." history found about ".$name.": ".$this->bot->core("tools")->make_blob("click to view", $output), $origin);
						if($distant) $this->file_cache($file,$content);
					} elseif (strpos($content, '[]') !== false) {
						if($distant) $this->file_cache($file,$content);
					} else {
						return "Content history  error on : ".$content;
					}
				} else {
					return "Bot history error on: ".$content;
				}
			} else {
				return "Dimension history error on: ".$dim;
			}
			// END AP/Tyr HISTORY			
        } else {
            return "Player ##highlight##" . $name . "##end## does not exist.";
        }
    }	
	
    function player_archive($asker, $origin, $name)
    {
        $name = ucfirst(strtolower($name));
        if (! ($this->bot->core("player")->id($name) instanceof BotError) ) {
			// START AUNO ARCHIVES
			for($j=1;$j<=2;$j++) {
				$file = $j.".".$name.".xml";
				$output = "##blob_title##::: Archive(s) for " . $name . " on RK".$j." :::##end##\n\n";
				if(file_exists($this->path."/".$file)) {
					$content = file_get_contents($this->path."/".$file);
					$distant = false;
				} else {
					$content = $this->bot->core("tools")->get_site("https://auno.org/ao/char.php?output=xml&dimension=".$j."&name=".$name);
					$distant = true;
				}
				if (!($content instanceof BotError)) {
					if (strpos($content, '<history>') !== false) {
						$history = $this->bot->core("tools")->xmlparse($content, "history");
						$events = explode("<entry", $history);
						for ($i = 1; $i < count($events); $i++) {
						// date level ailevel faction guild rank
							if (preg_match(
								"/date=\"(.+)\" level=\"([0-9]+)\" ailevel=\"([0-9]*)\" faction=\"(.+)\" guild=\"(.*)\" rank=\"(.*)\"/i",
								$events[$i],
								$result
							)
							) {
								if (empty($result[5])) {
									$result[5] = "##white##Not in guild##end##";
								}
								if (empty($result[4])) {
									$result[4] = "##white##No faction##end##";
								} else {
									if ($result[4] == 'Omni') {
										$result[4] = "##omni##Omni ##end##";
									} else {
										if ($result[4] == 'Clan') {
											$result[4] = "##clan##Clan ##end##";
										} else {
											if ($result[4] == 'Neutral') {
												$result[4] = "##neutral##Neutral ##end##";
											}
										}
									}
								}
								if (!empty($result[6])) {
									$result[6] = "##aqua##(" . $result[6] . ")##end##";
								}
								$output .= "##blob_text##Date:##end## " . $result[1] . "##blob_text##  ";
								$output .= "Level:##end## " . $result[2] . " ##blob_text## AI:##end## " . $result[3] . " ";
								$output .= "##blob_text##&#8226;##end## " . $result[4] . " ##blob_text##&#8226;##end## ";
								$output .= $result[5] . " " . $result[6] . "\n";
							}
						}
						$this->bot->send_output($asker, "RK".$j." archive found about ".$name.": ".$this->bot->core("tools")->make_blob("click to view", $output), $origin);
						if($distant) $this->file_cache($file,$content);
					} elseif (strpos($content, '<description>') !== false) {
						if($distant) $this->file_cache($file,$content);
					} else {
						return "Content archive error on : ".$content;
					}
				} else {
					return "Bot archive error on: ".$content;
				}
			}
			// END AUNO ARCHIVES			
        } else {
            return "Player ##highlight##" . $name . "##end## does not exist.";
        }
    }
	
    function file_cache($file,$content)
    {
		if ($handle = opendir($this->path)) {
			while (false !== ($filename = readdir($handle))) {
				if($filename!="."&&$filename!=".."&&$filename!="history.txt") {
					if($filename==$file) {
						unlink($this->path."/".$filename);
					}					
				}
			}
			file_put_contents($this->path."/".$file, $content);
		}
	}
	
	function cron()
	{
		$daylta = $this->bot->core("settings")->get("History", "CleanDelay") * 3600 * 24;
		$limit = time() - $daylta;
		if ($handle = opendir($this->path)) {
			while (false !== ($filename = readdir($handle))) {
				if($filename!="."&&$filename!=".."&&$filename!="history.txt") {
					if(filemtime($this->path."/".$filename)<$limit) {
						unlink($this->path."/".$filename);
					}
				}
			}
		}		
	}	
	
}

?>
