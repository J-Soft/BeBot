<?php
/*
* About.php - Gives info about the bot.
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
$about = new About($bot);
/*
The Class itself...
*/
class About extends BaseActiveModule
{

    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        //Sed default access control levels
        $this->register_command('all', 'about', 'GUEST');
        $this->register_alias('about', 'version');
        $this->help['description'] = "Shows information about the bot.";
        $this->help['command']['about'] = "See description";
		
		$this -> register_event("buddy");
		$this -> register_event("cron", "6hour");

		$this->bot->core("settings")->create("Version", "LastCheck", 1, "Last time we completed a version check", NULL, TRUE, 99);
		$this->bot->core("settings")->create("Version", "CheckURL", "http://bebot.link/bebotversion.php", "URL to check for new BeBot Version", NULL, TRUE, 99);
		$this->bot->core("settings")->save("Version", "CheckURL", "http://bebot.link/bebotversion.php");
		$this->bot->core("settings")->create("Version", "CheckUpdate", TRUE, "Should the bot periodically check if there are new updates available?", "On;Off", FALSE, 10);		
		
		$this->info = array();
		$this->versiontype = "s";
		$this->updatewaiting = FALSE;
		$this->lastrun = FALSE;
		$this->owner = $this->bot->owner;
		
    }


    /*
    Unified message handler
    */
    function command_handler($name, $msg, $origin)
    {
        $return = false;
        /*
        This should really be moved to the bot core.. but until i get the time to modify every single module... :\
        */
        $vars = explode(' ', strtolower($msg));
        $command = $vars[0];
        switch ($command) {
            case 'about':
                return $this->about_blob();
                break;
            default:
                return "Broken plugin, received unhandled command: $command";
        }
    }

	/*
    Warn SA of new possible update
    */
	function buddy($name, $msg)
	{
		if ((!empty($this->updatewaiting)) and ($msg == 1) and $this->bot->core("security")->check_access($name, "SUPERADMIN"))
		{
			$this->bot->send_tell($name, BOT_VERSION_NAME . " v." . $this->updatewaiting['version'] . " is available and was released " . $this->updatewaiting['date'] . " :: " . $this->bot->core("tools")-> make_blob("Details", $this->updatewaiting['window']));
		}
	}

    /*
    Auto checks updates
    */	
	function cron()
	{
		// Do nothing if automatic checking is disabled.
		if (!$this ->bot->core("settings")->get("Version", "CheckUpdate"))
		{
			return;
		}
		$this->lastrun = $this->bot->core("settings")->get("Version", "LastCheck");
		if (($this -> lastrun + (60 * 60 * 23)) >= time())
		{
			$this -> bot -> log("VERSION", "UPDATE", "Version check ran less than 23 hours ago, skipping!");
			return;
		}
		$this->version_check();
	}

    /*
    Version check
    */	
	function version_check()
	{
		$available = FALSE;
		$newer = FALSE;
		
		$this -> bot -> log("VERSION", "UPDATE", "Initiating version check");		
		// Fetch version XML
		if ($this->bot->core("settings")->exists("Version", "CheckURL"))
		{
			$xml = $this->bot->core("tools")->get_site($this->bot->core("settings")->get("Version", "CheckURL"));
		}
		else
		{
			$this -> bot -> log("VERSION", "ERROR", "No Update URL set");
			return;
		}

		if (!isset($xml["error"]))
		{
			// Check which version we are checking for
			if (BOT_VERSION_STABLE == FALSE)
			{
				$this->versiontype = "d";
			}
			$this->info['date'] = $this -> bot -> core("tools") -> xmlparse($xml, $this->versiontype . "rel");
			$this->info['upversionstring'] = $this -> bot -> core("tools") -> xmlparse($xml, $this->versiontype . "ver");
			
			if (empty($this->info['upversionstring']))
			{
				return;
			}
				
			$this->info['upversion'] = explode(".", $this->info['upversionstring']);
			$this->info['myversion'] = explode(".", BOT_VERSION);
	
			// Check major version
			if ($this->info['myversion'][0] != $this->info['upversion'][0])
			{
				if ($this->info['myversion'][0] < $this->info['upversion'][0])
				{
					$available = TRUE;
				}
				else
				{
					$newer = TRUE;
				}
				
			}
			elseif (BOT_VERSION_SNAPSHOT == TRUE)
			{
				$available = TRUE;
			}
			
			// Check minor version
			if ($this->info['myversion'][1] != $this->info['upversion'][1])
			{
				if ($this->info['myversion'][1] < $this->info['upversion'][1])
				{
					$available = TRUE;
				}
				else
				{
					$newer = TRUE;
				}
			}
			elseif (BOT_VERSION_SNAPSHOT == TRUE)
			{
				$available = TRUE;
			}
			
			// Check patch version
			if ($this->info['myversion'][2] != $this->info['upversion'][2])
			{
				if ($this->info['myversion'][2] < $this->info['upversion'][2])
				{
					$available = TRUE;
				}
				else
				{
					$newer = TRUE;
				}
			}
			elseif (BOT_VERSION_SNAPSHOT == TRUE)
			{
				$available = TRUE;
			}
					
			if ($newer == TRUE)
			{
				$this->set_update_time();
				$this -> bot -> log("VERSION", "UPDATE", "Running newer version already. (Running: " . BOT_VERSION . " Reported newest: " . $this->info['upversionstring'] . ")");
				return;
			}
			else if ($available == TRUE)
			{
				$window = "";
				
				if ($this->bot->core("settings")->exists("Version", "CheckURL"))
				{
					$xml = $this->bot->core("tools")->get_site($this->bot->core("settings")->get("Version", "CheckURL") . "?ver=" . BOT_VERSION);
					if (!isset($xml["error"]))
					{
						$window .= "##blob_title##Release information for " . BOT_VERSION_NAME . " v." . $this->info['upversionstring'] ."##end##\n##blob_text##";
						$window .= $this->bot->core("tools")->xmlparse($xml, "info") . "##end##\n\n";
						$window .= "##blob_title##Changelog##end##\n##blob_text##";
						$window .= $this->bot->core("tools")->xmlparse($xml, "log") . "##end##\n\n";
					}
					else
					{
						$this -> bot -> log("VERSION", "ERROR", "Failed to obtain changelog and info XML: " . $xml["errordesc"] . " " . $xml);
						$window .= $xml["errordesc"] . " " . $xml;
					}
				}
				else
				{
					return;
				}
				$this->set_update_time();
				$this->bot->log("VERSION", "UPDATE", "Found new available version " . $this->info['upversionstring']);
				$this->updatewaiting['version'] = $this->info['upversionstring'];
				$this->updatewaiting['window'] = $window;
				$this->updatewaiting['date'] = $this->info['date'];
				return;
			}
			else
			{
				$this -> bot -> log("VERSION", "UPDATE", "No new version detected");
				return;
			}
		}
		else
		{
			$this -> bot -> log("VERSION", "ERROR", "Failed to obtain XML: " . $xml["errordesc"] . " " . $xml);
			return;
		}
	}	

    /*
    Makes the about-blob
    */
    function about_blob()
    {
        $version = BOT_VERSION_NAME . " v." . BOT_VERSION;
        $inside = "##blob_text##Bot Client:##end##\n";
        $inside .= "$version\n\n";
        $inside .= "##blob_text##Developers:##end##\n";
        $inside .= "Alreadythere (RK2)\n";
        $inside .= "Blondengy (RK1)\n";
        $inside .= "Blueeagl3 (RK1)\n";
        $inside .= "Glarawyn (RK1)\n";
        $inside .= "Khalem (RK1)\n";
        $inside .= "Naturalistic (RK1)\n";
        $inside .= "Temar (RK1 / Doomsayer)\n\n";
        $inside .= "##blob_text##Special thanks to:##end##\n";
        $inside .= "Akarah (RK1)\n";
        $inside .= "Bigburtha (RK2) aka Craized\n";
        $inside .= "Derroylo (RK2)\n";
        $inside .= "Foxferal (RK1)\n";
        $inside .= "Jackjonez (RK1)\n";
        $inside .= "Sabkor (RK1)\n";
        $inside .= "Vhab (RK1)\n";
        $inside .= "Wolfbiter (RK1)\n";
        $inside .= "Xenixa (RK1)\n";
        $inside .= "Zacix (RK2)\n";
        $inside .= "Zarkingu (RK2)\n";
        $inside .= "Bitnykk (RK5)\n";		
        $inside .= "Auno for writing and maintaining the PHP AOChat library\n";
        $inside .= "And last but not least, the greatest MMORPG community in existence.\n\n";
        $inside .= "##blob_text##Links:##end##\n";
        if (strtolower($this->bot->game) == 'ao') {
		$inside .= $this->bot->core("tools")
                ->chatcmd("http://bebot.link", "BeBot website and support forums", "start") . "\n";
		} else {
		$inside .= "BeBot website and support forums: http://bebot.link \n";
		}
        $return = "$version ::: " . $this->bot->core("tools")
                ->make_blob('More details', $inside);
        return $return;
    }
	
	/*
	* Update the last update time
	*/
	function set_update_time()
	{
		$this -> bot -> core("settings") -> save("Version", "LastCheck", time());
	}	
	
}

?>
