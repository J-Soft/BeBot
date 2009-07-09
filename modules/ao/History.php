<?php
/*
* History.php - Online plugin to display user history
*
* HTML processing by Foxferal RK1
* XML processing by Alreadythere RK2
* Put together and colourised by Jackjonez RK1
*
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
$history = new History($bot);
/*
The Class itself...
*/
class History extends BaseActiveModule
{

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this->register_command('all', 'history', 'GUEST');
		$this->help['description'] = "Plugin to display the history of a player.";
		$this->help['command']['history <name>'] = "Shows the history of the player <name>";
		$this->help['notes'] = "No notes";
	}

	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^history (.+)$/i", $msg, $info))
			return $this->player_history($info[1]);
		else if (preg_match("/^history$/i", $msg, $info))
			return "No name specified";
		return false;
	}

	/*
      Get info on player
    */
	function player_history($name)
	{
		$name = ucfirst(strtolower($name));
		$id = $this->bot->core("chat")->get_uid($name);
		if (! empty($id))
		{
			$output = "##blob_title##::: Character history for " . $name . " :::##end##\n\n";
			$content = $this->bot->core("tools")->get_site("http://auno.org/ao/char.php?output=xml&dimension=" . $this->bot->dimension . "&name=" . $name . " ");
			if (! ($content instanceof BotError))
			{
				$history = $this->bot->core("tools")->xmlparse($content, "history");
				$events = explode("<entry", $history);
				for ($i = 1; $i < count($events); $i ++)
				{
					if (preg_match("/date=\"(.+)\" level=\"([0-9]+)\" ailevel=\"([0-9]*)\" faction=\"(.+)\" guild=\"(.*)\" rank=\"(.*)\"/i", $events[$i], $result))
					{
						if (empty($result[5]))
						{
							$result[5] = "##white##Not in guild##end##";
						}
						if (empty($result[4]))
						{
							$result[4] = "##white##No faction##end##";
						}
						else if ($result[4] == 'Omni')
						{
							$result[4] = "##omni##Omni ##end##";
						}
						else if ($result[4] == 'Clan')
						{
							$result[4] = "##clan##Clan ##end##";
						}
						else if ($result[4] == 'Neutral')
						{
							$result[4] = "##neutral##Neutral ##end##";
						}
						if (! empty($result[6]))
						{
							$result[6] = "##aqua##(" . $result[6] . ")##end##";
						}
						$output .= "##blob_text##Date:##end## " . $result[1] . "##blob_text##  ";
						$output .= "Level:##end## " . $result[2] . " ##blob_text## AI:##end## " . $result[3] . " ";
						$output .= "##blob_text##&#8226;##end## " . $result[4] . " ##blob_text##&#8226;##end## ";
						$output .= $result[5] . " " . $result[6] . "\n";
					}
				}
				return "History for " . $name . " :: " . $this->bot->core("tools")->make_blob("click to view", $output);
			}
			else
			{
				return $content;
			}
		}
		else
		{
			return "Player ##highlight##" . $name . "##end## does not exist.";
		}
	}
}
?>
