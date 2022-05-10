<?php
/**
 * Bank module by Bitnykk based on Dream's AOIA(+) .csv file format
 * Read explanations at http://wiki.bebot.link/index.php/Bank
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
 * - Dream (RK5)
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
$Bank = new Bank($bot);
class Bank extends BaseActiveModule
{	

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
		$this->register_module("bank");
		$this->register_command('all', 'banksearch', 'GUEST');
        $this->register_command('all', 'banklook', 'GUEST');		
		$this->register_command('all', 'bankadd', 'ADMIN');
		$this->register_command('all', 'bankrem', 'ADMIN');
        $this->help['description'] = 'Manages bank sharing so the people in org know who owns an item they could ask for.';
        $this->help['command']['banksearch [ql] <item>'] = "Searches and displays information about an <item> of the optional [ql].";
        $this->help['command']['bankadd <url>'] = "Adds a webhosted .csv file to the bot's Extras/Bank folder (existing file is updated, overwrite NOT UNDOABLE).";
        $this->help['command']['banklook [file]'] = "Browses bank and its files (with deletion links for Admins)";		
        $this->help['command']['bankrem [line] <file>'] = "Removes a .csv file (or just optionnal line) from the bot's Extras/Bank folder (NOT UNDOABLE).";
        $this->help['notes'] = "This module uses Dream's AOIA(+) .csv file format. Equipped items are ignored by default.";
		$this->path = "./Extras/Bank";
		$this->bot->core("settings")->create("Bank", "IncludeEquipped", false, "Should we include currently equipped items?");					
    }

    function command_handler($name, $msg, $origin)
    {
        if (preg_match('/^banksearch/i', $msg, $info)) {
			return $this->banksearch($msg, $name);
        } elseif (preg_match('/^bankadd/i', $msg, $info)) {
			return $this->bankadd($msg);
        } elseif (preg_match('/^banklook/i', $msg, $info)) {
			return $this->banklook($msg, $name);
        } elseif (preg_match('/^bankrem/i', $msg, $info)) {
			return $this->bankrem($msg);
        } else {
            $this->bot->send_help($name);
        }
    }
	
	function banksearch($msg, $name)
	{
	   $words = trim(substr($msg, strlen('banksearch')));
	   $inside = "";
		if (!empty($words)) {
			$total = 0;
			$parts = explode(' ', $words);
			if (count($parts) > 1 && is_numeric($parts[0]) && $parts[0] > 0) {
				$ql = $parts[0];
				unset($parts[0]);
				$search = implode(' ', $parts);
			} else {
				$ql = 0;
				$search = $words;
			}
			if ($handle = opendir($this->path)) {
				while (false !== ($filename = readdir($handle))) {
					if($filename!="."&&$filename!=".."&&$filename!=".gitkeep") {
						$file = pathinfo($filename, PATHINFO_FILENAME);
						$ext = pathinfo($filename, PATHINFO_EXTENSION);
						if ($ext=="csv") {
							$content = file_get_contents($this->path."/".$file.".csv");
							$line = preg_split('#\r?\n#', $content, 0);
							for($i=0;$i<count($line);$i++) {
								if($i>0) {
									if(preg_match("/^\"?([0-9a-z: ,.\'-]+)\"?,([0-9]+),([0-9a-z-]+),([^,]*),([^,]+),([0-9]+),([0-9]+),([0-9]+),([^,]?)/i",$line[$i],$value)) {
										$match = true;
										foreach($parts as $part) {
											if(stripos($value[1],$part)===false) $match = false;
										}
										if ($match && ( $this->bot->core("settings")->get("Bank", "IncludeEquipped")==true || ($this->bot->core("settings")->get("Bank", "IncludeEquipped")==false&&strpos($value[5],"Equipped")===false) ) ) {
											if($ql==0||$ql==$value[2]) {
												$total++;
												$inside .= "<a href='itemref://".$value[6]."/".$value[7]."/".$value[2]."'>".str_replace('\'','`',$value[1])."</a>";
												if ($value[4]!="") $bag = " in bag ".str_replace('\'','`',$value[4]); else $bag = " in inventory";
												if($name!=$value[3]) $inside .= " <a href='chatcmd:///tell ".$value[3]." Hi, I would please need: ".str_replace('\'','`',$value[1])." (QL ".$value[2].") you share".$bag."'>[ASK]</a>";
												else $inside .= " [YOURS]";
												if ($this->bot->core("security")->check_access($name, "ADMIN")) {
													$inside .= " <a href='chatcmd:///tell ".$this->bot->botname." bankrem ".$i." ".$file.".".$ext."'>[REM]</a>";
												}												
												$inside .= "\n";
											}
										}
									}
								}
							}										
						}					
					}
				}
			}						
			return $total." result(s) found in Bank : ".$this->bot->core("tools")->make_blob("click to view", $inside);
		} else {
			return "Usage: banksearch [quality] <item>";
		}
	}
	
	function bankadd($msg)
	{
		$url = trim(substr($msg, strlen('bankadd')));
		if (preg_match('/\b(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[-A-Z0-9+&@#\/%=~_|$?!:,.]*[A-Z0-9+&@#\/%=~_|$]/i', $url, $info)) {
			$n = strrpos($url,".");
			if($n===false||strtolower(substr($url,$n+1))!='csv') {
				return "Provided url is not a .csv file";
			}
			$content = $this->bot->core("tools")->get_site($url);
			if (!($content instanceof BotError)) {
				$line = preg_split('#\r?\n#', $content, 0);
				if ($line[0]=="Item Name,QL,Character,Backpack,Location,LowID,HighID,ContainerID,Link") {
					$file  = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
					if(file_exists($this->path."/".$file.".csv")) {
						unlink($this->path."/".$file.".csv");
						file_put_contents($this->path."/".$file.".csv", $content);
						return "Existing .csv file got updated.";
					} else {
						file_put_contents($this->path."/".$file.".csv", $content);
						return "New .csv file has been created.";
					}
				} else {
					return "Distant content not a csv format.";
				}
			} else {
				return "Wrong distant file provided.";
			}
		} else {
			return "Wrong url was provided.";
		}
	}

	function banklook($msg, $name)
	{
		$file = trim(substr($msg, strlen('banklook')));		
		$inside = "";
		if (empty($file)) {
			if ($handle = opendir($this->path)) {
				while (false !== ($filename = readdir($handle))) {
					if($filename!="."&&$filename!=".."&&$filename!=".gitkeep") {
						$file = pathinfo($filename, PATHINFO_FILENAME);
						$ext = pathinfo($filename, PATHINFO_EXTENSION);
						if ($ext=="csv") {
							$inside .= "<a href='chatcmd:///tell ".$this->bot->botname." banklook ".$file.".".$ext."'>".$file.".".$ext."</a>";
							if ($this->bot->core("security")->check_access($name, "ADMIN")) {
								$inside .= " <a href='chatcmd:///tell ".$this->bot->botname." bankrem ".$file.".".$ext."'>[REM]</a>";
							}
							$inside .= "\n";
						} else {
							if ($this->bot->core("security")->check_access($name, "ADMIN")) {
								$inside .= $file.".".$ext." <a href='chatcmd:///tell ".$this->bot->botname." bankrem ".$file.".".$ext."'>[REM]</a>\n";
							}
						}
					}
				}
				return "Bank files browser : ".$this->bot->core("tools")->make_blob("click to view", $inside);
			}
		} else {
			$file = pathinfo($file, PATHINFO_FILENAME);
			if(file_exists($this->path."/".$file.".csv")) {
				$content = file_get_contents($this->path."/".$file.".csv");
				$line = preg_split('#\r?\n#', $content, 0);
				for($i=0;$i<count($line);$i++) {
					if($i>0) {
						if(preg_match("/^\"?([0-9a-z: ,.\'-]+)\"?,([0-9]+),([0-9a-z-]+),([^,]*),([^,]+),([0-9]+),([0-9]+),([0-9]+),([^,]?)/i",$line[$i],$value)) {
							if ( $this->bot->core("settings")->get("Bank", "IncludeEquipped")==true || ($this->bot->core("settings")->get("Bank", "IncludeEquipped")==false&&strpos($value[5],"Equipped")===false) ) {
								if($i==1) $inside .= $value[3]."'s shared bank :\n\n";							
								$inside .= "<a href='itemref://".$value[6]."/".$value[7]."/".$value[2]."'>".str_replace('\'','`',$value[1])."</a>";
								if ($value[4]!="") $bag = " in bag ".str_replace('\'','`',$value[4]); else $bag = " in inventory";
								if($name!=$value[3]) $inside .= " <a href='chatcmd:///tell ".$value[3]." Hi, I would please need: ".str_replace('\'','`',$value[1])." (QL ".$value[2].") you share".$bag."'>[ASK]</a>";
								else $inside .= " [YOURS]";
								if ($this->bot->core("security")->check_access($name, "ADMIN")) {
									$inside .= " <a href='chatcmd:///tell ".$this->bot->botname." bankrem ".$i." ".$file.".csv'>[REM]</a>";
								}
								$inside .= "\n";
							}
						}
					}
				}
				return $file." file content : ".$this->bot->core("tools")->make_blob("click to view", $inside);
			} else {
				return "Non-existing filename provided.";
			}
		}
	}
	
	function bankrem($msg)
	{		
		$file = trim(substr($msg, strlen('bankrem')));			   
		$num = 0;
		if (!empty($file)) {
			$parts = explode(' ', $file);			
			if (count($parts) > 1 && is_numeric($parts[0]) && $parts[0] > 0) {
				$num = $parts[0];
				unset($parts[0]);
				$file = implode(' ', $parts);
				if(file_exists($this->path."/".$file)) {
					$content = file_get_contents($this->path."/".$file);
					$line = preg_split('#\r?\n#', $content, 0);
					$edited = "";
					for($i=0;$i<count($line);$i++) {
						if($i!=$num) {
							$edited .= $line[$i]."\n";
						}
					}
					unlink($this->path."/".$file);
					file_put_contents($this->path."/".$file, $edited);
					return "File edited, line removed (refresh for more).";
				} else {
					return "No such entry into Bank folder.";
				}								
			} else {				
				if(file_exists($this->path."/".$file)) {
					unlink($this->path."/".$file);
					return "Target file has been removed.";
				} else {
					return "No such file into Bank folder.";
				}				
			}							
		} else {
			return "Usage: bankrem [line] <filename>";
		}
	}	
	
}

?>
