<?php
/*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2009 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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
*/
/*
* Central Items Database v2.5, By Noer
* This module submits anonymous data of all items it sees in chat and makes it searchable.
* The module also features server and world first discoveries.
*
*/
$Items = new Items($bot);
class Items extends BaseActiveModule
{

	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->register_event("gmsg", "org");
		$this->register_event("gmsg", "Trade");
		$this->register_event("gmsg", "RegionAquilonia");
		$this->register_event("gmsg", "RegionCimmeria");
		$this->register_event("gmsg", "RegionStygia");
		$this->register_event("gmsg", "Playfield");
		$this->bot->core("colors")->define_scheme("items", "discover", "lightteal");
		$this->register_command('all', 'items', 'GUEST');
		$this->register_command('all', 'item', 'GUEST');
		$this->register_command('all', 'itemreg', 'GUEST');
		$this->help['description'] = 'Searches the database for information about an item.';
		$this->help['command']['items <text>'] = "Searches for items with the <text> in name.";
		$this->help['command']['item <id>'] = "Displays information about a given item with a given <id>.";
		$this->help['command']['itemreg <item ref>'] = "Submits the item(s) to the central item database. Several references can be send in same submit.";
		$this->bot->core("settings")->create("Items", "Autosubmit", TRUE, "Automatically submit new items the bot sees to central database?");
		$this->bot->core("settings")->create("Items", "Itemaware", TRUE, "Notify if a user discovers an item as first on server or in world? (always notifies when !itemreg is used)");
	}

	function command_handler($name, $msg, $origin)
	{
		if (preg_match('/^items/i', $msg, $info))
		{
			$words = trim(substr($msg, strlen('items')));
			if (! empty($words))
				return $this->bot->core('items')->search_item_db($words);
			else
				return "Usage: items [text]";
		}
		elseif (preg_match('/^itemreg/i', $msg, $info))
		{
			$words = trim(substr($msg, strlen('item')));
			if (! empty($words))
			{
				$result = $this->submit($name, $words, $origin);
				if (! $result)
				{
					$output = "There is no item reference in your item registration.";
					Return $output;
				}
			}
			else
				Return "Usage: itemreg [itemref]";
		}
		elseif (preg_match('/^item/i', $msg, $info))
		{
			$words = trim(substr($msg, strlen('item')));
			if (! empty($words) && (is_numeric($words)))
				return $this->bot->core('items')->search_item_db_details($words);
			else
				return "Usage: \"<pre>item [id]\". To search for an item use <pre>items.";
		}
		else
			$this->bot->send_help($name);
	}

	/*
	This gets called on a msg in the group
	*/
	function gmsg($name, $group, $msg)
	{
		if ($this->bot->core("settings")->get("Items", "Autosubmit"))
			$this->submit($name, $msg);
	}

	/*
	Autosubmits a link.
	*/
	function submit($name, $msg, $origin = FALSE)
	{
		$items = $this->bot->core('items')->parse_items($msg);
		if (empty($items))
			return false;
		if ($origin == "org")
			$origin = "gc";
		foreach ($items as $item)
		{
			$result = $this->bot->core('items')->submit_item($item, $name);
			if ($result == "0" && $origin)
			{
				$output = "Thank you for submitting the item, however the item was already discovered by others.";
				$this->bot->send_output($name, $output, $origin);
			}
			elseif ($result == "1" && ($origin || $this->bot->core("settings")->get("Items", "Itemaware")))
			{
				if (! $origin)
					$origin = "tell";
				$output = "Congratulations!! You are the ##items_discover##world's first##end## to discover " . $this->bot->core('items')->make_item($item) . "!";
				$this->bot->send_output($name, $output, $origin);
			}
			elseif ($result == "2" && ($origin || $this->bot->core("settings")->get("Items", "Itemaware")))
			{
				if (! $origin)
					$origin = "tell";
				$output = "Congratulations!! You are the ##items_discover##first on this server##end## to discover this " . $this->bot->core('items')->make_item($item) . "!";
				$this->bot->send_output($name, $output, $origin);
			}
			elseif ($origin)
			{
				$output = "##error##Error: Item Server returned Error.##end##";
				$this->bot->send_output($name, $output, $origin);
			}
		}
		return true;
	}
}
?>