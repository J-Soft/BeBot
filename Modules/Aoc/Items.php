<?php
/*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 Thomas Juberg Stensås, ShadowRealm Creations and the BeBot development team.
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
*
*  Module is based on "Central Items Database v2.5" By Noer
*
* File last changed at $LastChangedDate: 2008-07-23 16:44:39 +0100 (Wed, 23 Jul 2008) $
* Revision: $Id: Alias.php 1673 2008-07-23 15:44:39Z temar $
*/

/*
* 
* This module submits anonymous data of all items it sees in chat and makes it searchable.
* The module also features server and world first discoveries.
* Getrix 2009-04-19 - Added Passkey and server as bot settings connecting to different itemDB
* MeatHooks 2019-04-30 - Modify for using MeatHooks Minions Item Database, also removed server and world first discoveries.
* 
*/

$Items = new Items($bot);

class Items extends BaseActiveModule
{
	var $registered;
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));

        $this -> register_event("gmsg", "org");
		$this->register_event("tells");
        $this -> bot -> core("colors") -> define_scheme("items", "discover", "lightteal");

		$this -> register_command('all', 'items', 'ANONYMOUS');
		$this -> register_command('all', 'itemrecipes', 'MEMBER');

        $this -> help['description'] = 'Submit and searches the Central Item Database for information about an item (MeatHooks Minions by default).';
        $this -> help['command']['items <text>'] = "Searches for items with the <text> in the name.";
        $this -> help['command']['items <id>'] = "Searches for a specific item with the <id>.";
        $this -> help['command']['items <[item]>'] = "Submits the item(s) into the item database. Several items may be sent in the same submit.";

		$this -> help['command']['itemrecipes <makes#> <[recipeitem]> <needed#> <[item]>'] = "Submit the recipie item into the item database as a recipie (and how many items it would make) with the item that is needed to craft (and how many of the item are needed). Submit item and number combination one at a time only.";
        $this -> help['command']['itemrecipes <text>'] = "Searches for recipes with the <text> in the name.";

        $this -> bot -> core("settings") -> create("Items", "Autosubmit", TRUE, "Automatically submit new items the bot sees to the items database?");
        $this -> bot -> core("settings") -> create("Items", "Autoreply", FALSE, "Automatically reply when new item has been submitted to database?");
        $this -> bot -> core("settings") -> create("Items", "LogURL", FALSE, "Write all URL's used to the log file.");
        $this -> bot -> core("settings") -> create("Items", "Passkey", "none", "Passkey used to access the item database");
        
		$this -> bot -> core("settings") -> create("Items", "ListenPublic", FALSE, "Does the bot listen to public channels (Trial, NewbieHelp, etc). Restart bot if you change this value.");
		$this->register_event("cron", "1min");
		$this->registered = FALSE;
    }

    function cron()
    {		
		if(!$this->registered && $this->bot->core("settings")->get("Items", "ListenPublic")) {
			foreach($this->bot->aoc->gid as $key => $val) {
				if(preg_match('/\~([a-z]+)/i', $val, $match)) {
					$this->register_event("gmsg", $val);
					$this->registered = TRUE;
				}
			}
		} else {
			$this->unregister_event("cron", "1min");
		}		
    }	
	
    function command_handler($name, $msg, $origin)
    {
		$vars = explode(' ', strtolower($msg));

		$com  = $vars[0];
		$items = substr($msg, strlen($com) + 1);

		switch($com)
		{
			case 'itemgroups':
				print_r($this->bot->aoc->gid);
				break;			
			case 'items':
				if (!empty($items))
				{
					$itemarray = $this -> bot -> core('items') -> parse_items($items);
					if (count($itemarray) > 0)
					{
							$result = $this -> submit($name, $items, $origin);
							if(!$result)
							{
								$output = "There is no item referenced in your item registration.";
								return $output;
							}
					}
					else 
					{
						return $this -> bot -> core('items') -> search_item_db($items);
					}
				}
				else 
				{
					$this -> bot -> send_help($name, 'items');
				}
				break;

			case 'itemrecipes':
				if (!empty($items))
				{
					$recipearray = $this -> bot -> core('items') -> parse_recipe_item($items);
					if (count($recipearray) > 0)
					{
						if ($this -> bot -> core("security") -> check_access($name, 'MEMBER'))
						{
							$result = $this -> recipesubmit($name, $items, $origin);
							if(!$result)
							{
								$output = "There is no recipe item referenced in your recipe item registration.";
								return $output;
							}
						}
					}
					else 
					{
						return $this -> bot -> core('items') -> search_item_recipe_db($items);
					}
				}
				else 
				{
					$this -> bot -> send_help($name, 'itemrecipes');
				}
				break;
		}

		return;
	}

    function gmsg($name, $group, $msg)
    {
		$autosubmit = $this -> bot -> core("settings") -> get("Items", "Autosubmit");

        if ($autosubmit)
		{
            $this -> submit($name, $msg, "gmsg");
		}
    }
	
    function tells($name, $msg)
    {
		$autosubmit = $this -> bot -> core("settings") -> get("Items", "Autosubmit");

        if ($autosubmit)
		{
            $this -> submit($name, $msg, "tell");
		}
    }	

    function submit($name, $msg, $origin)
    {
        $items = $this -> bot -> core('items') -> parse_items($msg);

        $passkey = $this -> bot -> core("settings") -> get("Items", "Passkey");
		if($passkey=='none') $passkey = '';

        if ($origin == "org") { $origin = "gc"; }

        if(empty($items))
		{
            return false;
		}

        foreach ($items as $item)
        {
            $result = $this -> bot -> core('items') -> submit_item($item, $name, $passkey);
			if($this -> bot -> core("settings") -> get("Items", "Autoreply")) {
				if ($result == "1")
				{
					if ($origin != "gmsg")
					{
						$output = "Thank you for submitting the item, however the item has already been previously submitted.";
						$this -> bot -> send_output($name, $output, "tell");
					}
				}
				elseif ($result == "2")
				{
					$output = "Congratulations!! You have successfully submitted the new item:  ".$this -> bot -> core('items') -> make_item($item).". Thank You!";
					$this -> bot -> send_output($name, $output, "tell");
				}
				else
				{
					$output = "##error##Error: Item Database returned Error (Info: ".$result.").##end##";
					$this -> bot -> send_output($name, $output, "tell");
				}
			}
        }

        return true;
    }

    function recipesubmit($name, $msg, $origin)
    {
        $items = $this -> bot -> core('items') -> parse_recipe_item($msg);

        $passkey = $this -> bot -> core("settings") -> get("Items", "Passkey");

        if ($origin == "org") { $origin = "gc"; }

        if(empty($items))
		{
            return false;
		}

		if (count($items, COUNT_NORMAL) == 4)
		{
            $result = $this -> bot -> core('items') -> submit_item($items[0], $name, $passkey);
            $result = $this -> bot -> core('items') -> submit_item($items[1], $name, $passkey);			
	        $result = $this -> bot -> core('items') -> submit_recipe_item($items[0], $items[2], $items[1], $items[3], $name, $passkey);
			if($this -> bot -> core("settings") -> get("Items", "Autoreply")) {
				if ($result == "1")
				{
					if ($origin != "gmsg")
					{
						$output = "Thank you for submitting the recipe and needed item, however the recipe and needed item has already been previously submitted.";
						$this -> bot -> send_output($name, $output, "tell");
					}
				}
				elseif ($result == "2")
				{
					$output = "Congratulations!! You have successfully submitted the new recipe ".$this -> bot -> core('items') -> make_item($items[0])." and needed item ".$this -> bot -> core('items') -> make_item($items[1]).". Thank You!";
					$this -> bot -> send_output($name, $output, "tell");
				}
				else
				{
					$output = "##error##Error: Item Database returned Error (Info: ".$result.").##end##";
					$this -> bot -> send_output($name, $output, "tell");
				}
			}
		}

        return true;
    }
}
?>