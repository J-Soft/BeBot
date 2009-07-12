<?php
/*
* Tier2.php - Tier2 raid management
*
* Written by Sabar(RK1) for BeBot.
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
$tier2 = new Tier2($bot);
/*
The Class itself...
*/
class Tier2 extends BaseActiveModule
{
	var $pgroup;
	var $raid;
	var $ids;
	var $qls;
	var $names;
	var $wills;
	var $advents;
	var $red_glyphs;
	var $space_glyphs;
	var $groups;
	var $users;
	var $profs;
	var $raffles;

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->bot = &$bot;
		$this->raid = false;
		$this->pgroup = array();
		$this->users = array();
		$this->ids = array("repose" => 239469 , "tempest" => 239470 , "unshake" => 239471 , "indust" => 239475 , "benign" => 239476 , "prudent" => 239478 , "passion" => 239479 , "impet" => 239480 , "embryo" => 239836 , "aban" => 218343 , "enel" => 218342 , "ocra" => 218341 , "green" => 218346 , "horned" => 218347 , "orange" => 218345 , "purple" => 218344 , "white" => 218340);
		$this->qls = array("repose" => 1 , "tempest" => 1 , "unshake" => 1 , "indust" => 1 , "benign" => 1 , "prudent" => 1 , "passion" => 1 , "impet" => 1 , "embryo" => 1 , "aban" => 160 , "enel" => 160 , "ocra" => 160 , "green" => 160 , "horned" => 160 , "orange" => 160 , "purple" => 160 , "white" => 160);
		$this->names = array("repose" => "Will of the Reposeful" , "tempest" => "Will of the Tempestuous" , "unshake" => "Will of the Unshakable" , "indust" => "Advent of the Industrious" , "benign" => "Advent of the Benign" , "prudent" => "Advent of the Prudent" , "passion" => "Advent of the Passionate" , "impet" => "Advent of the Impetuous" , "embryo" => "Embryo of Yoma'Arallu" , "aban" => "Red Glyph of Aban" , "enel" => "Red Glyph of Enel" , "ocra" => "Red Glyph of Ocra" , "green" => "Green Tower Glyph of Thar" , "horned" => "Horned Black Glyph of Shere" , "orange" => "Orange Moon Glyph of Roch" , "purple" => "Purple Star Glyph of Bhotaar" , "white" => "White Sun Glyph of Xum" , "will_ffa" => "Will Free For All" , "advent_ffa" => "Advent Free For All");
		$this->profs = array("Adventurer" , "Agent" , "Bureaucrat" , "Doctor" , "Enforcer" , "Engineer" , "Fixer" , "Keeper" , "Martial Artist" , "Meta-Physicist" , "Nano-Technician" , "Shade" , "Soldier" , "Trader");
		$this->wills = array('Adventurer' => 'repose' , 'Agent' => 'tempest' , 'Bureaucrat' => 'tempest' , 'Doctor' => 'repose' , 'Enforcer' => 'unshake' , 'Engineer' => 'unshake' , 'Fixer' => 'repose' , 'Keeper' => 'unshake' , 'Martial Artist' => 'unshake' , 'Meta-Physicist' => 'repose' , 'Nano-Technician' => 'tempest' , 'Shade' => 'unshake' , 'Soldier' => 'tempest' , 'Trader' => 'repose');
		$this->advents = array('Adventurer' => 'prudent' , 'Agent' => 'impet' , 'Bureaucrat' => 'passion' , 'Doctor' => 'benign' , 'Enforcer' => 'prudent' , 'Engineer' => 'indust' , 'Fixer' => 'impet' , 'Keeper' => 'benign' , 'Martial Artist' => 'impet' , 'Meta-Physicist' => 'passion' , 'Nano-Technician' => 'benign' , 'Shade' => 'passion' , 'Soldier' => 'prudent' , 'Trader' => 'indust');
		$this->red_glyphs = array('Adventurer' => 'ocra' , 'Agent' => 'enel' , 'Bureaucrat' => 'enel' , 'Doctor' => 'ocra' , 'Enforcer' => 'aban' , 'Engineer' => 'aban' , 'Fixer' => 'ocra' , 'Keeper' => 'aban' , 'Martial Artist' => 'aban' , 'Meta-Physicist' => 'ocra' , 'Nano-Technician' => 'enel' , 'Shade' => 'aban' , 'Soldier' => 'enel' , 'Trader' => 'ocra');
		$this->space_glyphs = array('Adventurer' => 'purple' , 'Agent' => 'orange' , 'Bureaucrat' => 'white' , 'Doctor' => 'green' , 'Enforcer' => 'purple' , 'Engineer' => 'horned' , 'Fixer' => 'orange' , 'Keeper' => 'green' , 'Martial Artist' => 'orange' , 'Meta-Physicist' => 'white' , 'Nano-Technician' => 'green' , 'Shade' => 'white' , 'Soldier' => 'purple' , 'Trader' => 'horned');
		$this->groups = array("embryo" => "embryo" , "repose" => "will" , "tempest" => "will" , "unshake" => "will" , "benign" => "advent" , "impet" => "advent" , "indust" => "advent" , "passion" => "advent" , "prudent" => "advent" , "will_ffa" => "will_ffa" , "advent_ffa" => "advent_ffa");
		foreach (array("embryo" , "repose" , "tempest" , "unshake" , "benign" , "impet" , "indust" , "passion" , "prudent" , "will_ffa" , "advent_ffa") as $each)
		{
			$this->raffles[$each] = array();
		}
		$this->register_command("pgmsg", "t2", "GUEST");
		$this->register_command("tell", "t2", "GUEST");
		$this->register_event("pgjoin");
		$this->register_event("pgleave");
		$this->register_event("disconnect");
	}

	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^t2 start$/i", $msg))
			$this->start_raid($name);
		else if (preg_match("/^t2 stop$/i", $msg))
			$this->stop_raid($name);
		else if (preg_match("/^t2 admin$/i", $msg))
			$this->admin_console($name);
		else if (preg_match("/^t2 admin loot$/i", $msg))
			$this->admin_loot_console($name);
		else if (preg_match("/^t2 admin user$/i", $msg))
			$this->admin_user_console($name);
		else if (preg_match("/^t2 admin rejoin ([a-zA-Z0-9]+) ([a-zA-Z0-9_]+)$/i", $msg, $info))
			$this->admin_rejoin($name, $info[1], $info[2]);
		else if (preg_match("/^t2 join$/i", $msg))
			$this->join_window($name);
		else if (preg_match("/^t2 loot ([a-zA-Z_]+)$/i", $msg, $info))
			$this->declare_loot($name, $info[1], $info[1]);
		else if (preg_match("/^t2 join all$/i", $msg))
			$this->enter_all_raffles($name);
		else if (preg_match("/^t2 join embryo$/i", $msg))
			$this->enter_raffle($name, "embryo", "embryo");
		else if (preg_match("/^t2 join will_ffa$/i", $msg))
			$this->enter_raffle($name, "will_ffa", "will_ffa");
		else if (preg_match("/^t2 join advent_ffa$/i", $msg))
			$this->enter_raffle($name, "advent_ffa", "advent_ffa");
		else if (preg_match("/^t2 join will$/i", $msg))
			$this->enter_raffle($name, $this->wills[$this->users[$name][profession]], "will");
		else if (preg_match("/^t2 join advent$/i", $msg))
			$this->enter_raffle($name, $this->advents[$this->users[$name][profession]], "advent");
		else if (preg_match("/^t2 leave embryo$/i", $msg))
			$this->leave_raffle($name, "embryo", "embryo");
		else if (preg_match("/^t2 leave will_ffa$/i", $msg))
			$this->leave_raffle($name, "will_ffa", "will_ffa");
		else if (preg_match("/^t2 leave advent_ffa$/i", $msg))
			$this->leave_raffle($name, "advent_ffa", "advent_ffa");
		else if (preg_match("/^t2 leave will$/i", $msg))
			$this->leave_raffle($name, $this->wills[$this->users[$name][profession]], "will");
		else if (preg_match("/^t2 leave advent$/i", $msg))
			$this->leave_raffle($name, $this->advents[$this->users[$name][profession]], "advent");
		else if (preg_match("/^t2 setprof ([a-zA-Z\s]+)$/i", $msg, $info))
			$this->setprof($name, $info[1]);
		return false;
	}

	/*
	This gets called if someone joins the privgroup
	*/
	function pgjoin($name)
	{
		$index = array_search($name, $this->pgroup);
		if ($index === false)
			array_push($this->pgroup, $name);
		if ($this->raid)
		{
			$this->bot->send_tell($name, "There is a Tier 2 raid in progress");
			$this->join_window($name);
			if ($this->users[$name])
				$this->users[$name][gone] = false;
		}
	}

	/*
	This gets called if someone leaves the privgroup
	*/
	function pgleave($name)
	{
		$index = array_search($name, $this->pgroup);
		if ($index !== FALSE)
			array_splice($this->pgroup, $index, 1);
		if ($this->raid)
		{
			$raffles = array("embryo" , "will" , "advent" , "will" , "advent");
			foreach (array("embryo" , "will_ffa" , "advent_ffa" , $this->wills[$this->users[$name][profession]] , $this->advents[$this->users[$name][profession]]) as $type)
			{
				$raffle = array_shift($raffles);
				$this->users[$name][$raffle] = false;
				$index = array_search($name, $this->raffles[$type]);
				if ($index !== FALSE)
					array_splice($this->raffles[$type], $index, 1);
			}
		}
	}

	/*
	This gets called when bot disconnects
	*/
	function disconnect()
	{
		$this->pgroup = array();
	}

	function start_raid($name)
	{
		if ($this->bot->core("security")->check_access($name, "leader"))
		{
			if (! $this->raid)
			{
				$this->raid = true;
				$this->bot->send_pgroup("<font color=#ffff00>$name</font> has started a Tier 2 raid");
				$this->admin_console($name);
				foreach ($this->pgroup as $player)
					$this->join_window($player);
			}
			else
			{
				$this->bot->send_tell($name, "The Tier 2 raid is already running");
			}
		}
		else
		{
			$this->bot->send_tell($name, "You must be raidleader to start a Tier 2 raid");
		}
	}

	function stop_raid($name)
	{
		if ($this->bot->core("security")->check_access($name, "leader"))
		{
			if ($this->raid)
			{
				$this->raid = false;
				$this->bot->send_pgroup("<font color=#ffff00>$name</font> has stopped the Tier 2 raid");
				$this->users = array();
				foreach (array("embryo" , "repose" , "tempest" , "unshake" , "benign" , "impet" , "indust" , "passion" , "prudent" , "will_ffa" , "advent_ffa") as $each)
				{
					$this->raffles[$each] = array();
				}
			}
			else
			{
				$this->bot->send_tell($name, "There is no Tier 2 raid running");
			}
		}
		else
		{
			$this->bot->send_tell($name, "You must be raidleader to stop a Tier 2 raid");
		}
	}

	function admin_rejoin($name, $player, $group)
	{
		if (! $this->bot->core("security")->check_access($name, "leader"))
		{
			$this->bot->send_tell($name, "You must be a raidleader to access this command");
			return;
		}
		if (! $this->raid)
		{
			$this->bot->send_tell($name, "There is no Tier 2 raid running");
			return;
		}
		$item = $group;
		if ($item == "will")
			$item = $this->wills[$this->users[$player][profession]];
		if ($item == "advent")
			$item = $this->advents[$this->users[$player][profession]];
		$index = array_search($player, $this->raffles[$item]);
		if ($index === false)
		{
			array_push($this->raffles[$item], $player);
			$this->users[$player]["got_" . $group] = false;
			$this->bot->send_tell($name, "You have added $player to the " . $this->names[$item] . " raffle");
			$this->bot->send_tell($player, "You have been added to the " . $this->names[$item] . " raffle");
		}
		else
		{
			$this->bot->send_tell($name, "$player is already in that raffle");
		}
	}

	function enter_raffle($name, $type, $raffle)
	{
		if (! $type)
		{
			$this->join_window($name);
			return;
		}
		if ($this->users[$name][$raffle])
		{
			$this->bot->send_tell($name, "You are already entered in the $raffle raffle");
			return;
		}
		$this->users[$name][$raffle] = true;
		if (! $this->users[$name]['got_' . $raffle])
			array_push($this->raffles[$type], $name);
		$this->bot->send_tell($name, "You have been entered in the $raffle raffle.");
		$this->join_window($name);
	}

	function enter_all_raffles($name)
	{
		if (! $this->users[$name])
		{
			$this->join_window($name);
			return;
		}
		foreach (array("embryo" , "will" , "advent" , "will_ffa" , "advent_ffa") as $cat)
		{
			if ($this->users[$name][$cat])
			{
				continue;
			}
			$item = $cat;
			if ($cat == "will")
				$item = $this->wills[$this->users[$name][profession]];
			if ($cat == "advent")
				$item = $this->advents[$this->users[$name][profession]];
			$this->users[$name][$cat] = true;
			if (! $this->users[$name]["got_" . $cat])
			{
				array_push($this->raffles[$item], $name);
			}
		}
		$this->bot->send_tell($name, "You have been entered in all raffles");
		$this->join_window($name);
	}

	function leave_raffle($name, $type, $raffle)
	{
		if (! $this->users[$name])
		{
			$this->join_window($name);
			return;
		}
		if (! $this->users[$name][$raffle])
		{
			$this->bot->send_tell($name, "You are not entered in the $raffle raffle");
			return;
		}
		$this->users[$name][$raffle] = false;
		$index = array_search($name, $this->raffles[$type]);
		if ($index !== FALSE)
			array_splice($this->raffles[$type], $index, 1);
		$this->bot->send_tell($name, "You have been removed from the $raffle raffle.");
		$this->join_window($name);
	}

	function reset_loot_list($group, $item)
	{
		foreach ($this->users as $name => $data)
		{
			if ($data[gone])
			{
				continue;
			}
			if (! $data[$group])
			{
				continue;
			}
			if ($group == "advent")
			{
				if ($item != $this->advents[$data[profession]])
				{
					continue;
				}
			}
			if ($group == "will")
			{
				if ($item != $this->wills[$data[profession]])
				{
					continue;
				}
			}
			$data["got_" . $group] = false;
			array_push($this->raffles[$item], $name);
		}
	}

	function declare_loot($name, $item, $show_name)
	{
		if (! $this->bot->core("security")->check_access($name, "leader"))
		{
			$this->bot->send_tell($name, "You must be a raidleader to access this command");
			return;
		}
		if (! $this->raid)
		{
			$this->bot->send_tell($name, "There is no Tier 2 raid running");
			return;
		}
		$group = $this->groups[$item];
		if (! $group)
		{
			$this->bot->send_tell($name, "Invalid Item");
			return;
		}
		$users = $this->raffles[$item];
		if (count($users) == 0)
		{
			$this->reset_loot_list($group, $item);
			$users = $this->raffles[$item];
		}
		if (count($users) == 0)
		{
			if (preg_match("/_ffa$/i", $item) || $item == "embryo")
			{
				$this->bot->send_pgroup($this->names[$item] . " has dropped and is Free For All");
				return;
			}
			$this->declare_loot($name, $group . "_ffa", $item);
			return;
		}
		shuffle($this->raffles[$item]);
		$winner = array_shift($this->raffles[$item]);
		$is_ffa = "";
		if ($item != $show_name)
		{
			$is_ffa = " and is Free For All";
		}
		$this->bot->send_pgroup($this->names[$show_name] . " has dropped" . $is_ffa . ". <font color=#ffff00>$winner</font> may loot. There are " . count($this->raffles[$item]) . " players left before resetting list for this item.");
		$this->users[$winner]["got_" . $group] = true;
		$blob = "<font color=CCInfoHeadline> ::: You have loot rights for " . $this->names[$item] . " :::\n\n";
		$blob .= "A " . $this->names[$item] . " has dropped, and you have the rights to loot it.\n";
		$blob .= $this->bot->core("tools")->chatcmd("t2 join", "Change my status");
		$this->bot->send_tell($winner, $this->bot->core("tools")->make_blob("You have loot rights", $blob));
	}

	// Sends a window blob that shows the user's current Tier 2 raid status, allowing them to join the different parts of the raid
	function join_window($name)
	{
		if (! $this->raid)
		{
			$this->bot->send_tell($name, "There is current no raid in progress");
			return;
		}
		if (! $this->users[$name])
		{
			$profession = $this->get_profession($name);
			$this->users[$name][profession] = $profession;
			$this->users[$name][embryo] = false;
			$this->users[$name][will] = false;
			$this->users[$name][advent] = false;
			$this->users[$name][will_ffa] = false;
			$this->users[$name][advent_ffa] = false;
			// Flags so users can't leave and rejoin bot to reset their raffle status
			$this->users[$name][got_embryo] = false;
			$this->users[$name][got_will] = false;
			$this->users[$name][got_advent] = false;
			$this->users[$name][got_will_ffa] = false;
			$this->users[$name][got_advent_ffa] = false;
			// Flag saying the user has left the chat (they are kept in the raid to maintain their got_ flags
			$this->users[$name][gone] = false;
		}
		// The script is dependant upon knowing for certain the profession of the player
		if (! $this->users[$name][profession])
		{
			$blob .= "<font color=CCInfoHeader>I was unable to determine your profession. Please select it from the following list</font>\n";
			foreach ($this->profs as $prof)
			{
				$blob .= $this->bot->core("tools")->chatcmd("t2 setprof " . $prof, $prof) . "\n\n";
			}
			$this->bot->send_tell($name, $this->bot->core("tools")->make_blob("Click Here and select your profession", $blob));
			return;
		}
		$blob = "<font color=CCInfoHeadline> ::: Join the Tier 2 Raid :::\n\n";
		$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 join all", "Join All Raffles") . "]  ";
		if ($this->bot->core("security")->check_access($name, "leader"))
		{
			$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 admin", "Administration Console") . "]  ";
		}
		$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 join", "Refresh") . "]\n\n";
		$blob .= '<font color=CCInfoHeader>Profession:</font> ' . $this->users[$name][profession] . "\n\n";
		$blob .= $this->make_link('embryo') . ": ";
		if (! $this->users[$name][embryo])
			$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 join embryo", "Enter") . "]  [Leave]";
		else
			$blob .= "[Enter]  [" . $this->bot->core("tools")->chatcmd("t2 leave embryo", "Leave") . "]";
		$blob .= "\n\n";
		$blob .= $this->make_link($this->wills[$this->users[$name][profession]]) . ": ";
		if (! $this->users[$name][will])
			$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 join will", "Enter") . "]  [Leave]";
		else
			$blob .= "[Enter]  [" . $this->bot->core("tools")->chatcmd("t2 leave will", "Leave") . "]";
		$blob .= "\n\n";
		$blob .= $this->make_link($this->advents[$this->users[$name][profession]]) . ": ";
		if (! $this->users[$name][advent])
			$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 join advent", "Enter") . "]  [Leave]";
		else
			$blob .= "[Enter]  [" . $this->bot->core("tools")->chatcmd("t2 leave advent", "Leave") . "]";
		$blob .= "\n\n";
		$blob .= "<font color=CCInfoHeader>Free For All:</font>\n";
		$blob .= "If an Advent or a Will drops, and no-one is registered to loot it, it becomes Free For All.\n\n";
		$blob .= "Will Free For All: ";
		if (! $this->users[$name][will_ffa])
			$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 join will_ffa", "Enter") . "]  [Leave]";
		else
			$blob .= "[Enter]  [" . $this->bot->core("tools")->chatcmd("t2 leave will_ffa", "Leave") . "]";
		$blob .= "\n\n";
		$blob .= "Advent Free For All: ";
		if (! $this->users[$name][advent_ffa])
			$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 join advent_ffa", "Enter") . "]  [Leave]";
		else
			$blob .= "[Enter]  [" . $this->bot->core("tools")->chatcmd("t2 leave advent_ffa", "Leave") . "]";
		$blob .= "\n\n";
		$blob .= "(1) Embryo + (3) Will = " . $this->make_link($this->red_glyphs[$this->users[$name][profession]]) . "\n";
		$blob .= "(3) Advent = " . $this->make_link($this->space_glyphs[$this->users[$name][profession]]) . "\n\n";
		$blob .= "You can trade (3) identical Wills or (2) identical Advents for one of your profession ";
		$blob .= "by trading them to the NPC \"Exchange Officer of IPS\" stationed outside the profession ";
		$blob .= "shop in Jobe Harbor";
		$this->bot->send_tell($name, $this->bot->core("tools")->make_blob("Tier 2 Raid Status", $blob));
	}

	function admin_console($name)
	{
		if (! $this->bot->core("security")->check_access($name, "leader"))
		{
			$this->bot->send_tell($name, "You must be a raidleader to access this command");
			return;
		}
		$blob = "<font color=CCInfoHeadline> ::: Administrate the Tier 2 Raid :::\n\n";
		if ($this->raid)
			$blob .= "[Start]  -  [" . $this->bot->core("tools")->chatcmd("t2 stop", "Stop") . "]";
		else
			$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 start", "Start") . "]  -  [Stop]";
		$blob .= "  -  [" . $this->bot->core("tools")->chatcmd("t2 admin", "Refresh") . "]";
		$blob .= "  -  [" . $this->bot->core("tools")->chatcmd("t2 join", "Your Status") . "]\n\n";
		$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 admin loot", "Small Loot Window") . "]  -  ";
		$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 admin user", "User Admin") . "]\n\n";
		$blob .= "<font color=CCInfoHeader>Loot List</font>\n";
		$blob .= "Clicking on one of these announces that it has dropped, and who should loot it\n\n";
		foreach ($this->raffles as $item => $list)
		{
			$blob .= $this->bot->core("tools")->chatcmd("t2 loot " . $item, $this->names[$item]) . " (" . count($list) . " entered)\n";
		}
		$this->bot->send_tell($name, $this->bot->core("tools")->make_blob("Administrate Tier 2 Raid", $blob));
	}

	function admin_loot_console($name)
	{
		if (! $this->bot->core("security")->check_access($name, "leader"))
		{
			$this->bot->send_tell($name, "You must be a raidleader to access this command");
			return;
		}
		if (! $this->raid)
		{
			$this->bot->send_tell($name, "There is no Tier 2 raid running");
			return;
		}
		$blob = "[" . $this->bot->core("tools")->chatcmd("t2 admin", "Complete Admin") . "]";
		$cat = "";
		foreach ($this->raffles as $item => $list)
		{
			$cur_cat = $this->groups[$item];
			if ($cat != $cur_cat)
			{
				$blob .= "\n";
				$cat = $cur_cat;
			}
			$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 loot " . $item, $item) . "]  ";
		}
		$this->bot->send_tell($name, $this->bot->core("tools")->make_blob("Small Loot Window", $blob));
	}

	function admin_user_console($name)
	{
		if (! $this->bot->core("security")->check_access($name, "leader"))
		{
			$this->bot->send_tell($name, "You must be a raidleader to access this command");
			return;
		}
		if (! $this->raid)
		{
			$this->bot->send_tell($name, "There is no Tier 2 raid running");
			return;
		}
		$blob = "<font color=CCInfoHeadline> ::: Administrate Tier 2 Raid Users :::\n\n";
		$blob .= "[" . $this->bot->core("tools")->chatcmd("t2 admin", "Administration Console") . "]  [" . $this->bot->core("tools")->chatcmd("t2 admin user", "Refresh") . "]\n\n";
		foreach ($this->users as $u_name => $data)
		{
			$blob .= "<font color=CCInfoHeader>" . $u_name . ": </font>\n";
			foreach (array("embryo" , "will" , "advent" , "will_ffa" , "advent_ffa") as $cat)
			{
				$blob .= "    " . $cat . ": ";
				if ($data[$cat])
				{
					$blob .= "[Entered]";
				}
				else
				{
					$blob .= "[NOT Entered]";
				}
				if ($data[$cat] && $data["got_" . $cat])
				{
					$blob .= "  [" . $this->bot->core("tools")->chatcmd("t2 admin rejoin $u_name $cat", "Re-Add to Raffle List") . "]  ";
				}
				else if ($data[$cat])
				{
					$blob .= "  [In list]";
				}
				$blob .= "\n";
			}
		}
		$this->bot->send_tell($name, $this->bot->core("tools")->make_blob("Tier 2 User Console", $blob));
	}

	// Tries to figure out what profession the user is
	function get_profession($name)
	{
		if ($this->users[$name]['profession'])
			return $this->users[$name]['profession'];
		$result = $this->bot->core("whois")->lookup($name);
		if ($result instanceof BotError)
			Return "Unknown";
		return $result["profession"];
	}

	// If the system was unable to figure out the user's profession, they are asked to set it
	function setprof($name, $profession)
	{
		// User tried to call this function before they got the window asking them to choose the profession.
		// They have to go there first, so the bot can try and deduce the profession without the user setting it
		if (! $this->users[$name])
		{
			$this->join_window($name);
			return;
		}
		if (! $this->wills[$profession])
		{
			$this->bot->send_tell($name, "Invalid Profession.");
			return;
		}
		if ($this->users[$name][profession])
		{
			$this->bot->send_tell($name, "Your profession can not be changed.");
			return;
		}
		$this->users[$name][profession] = $profession;
		$this->bot->send_tell($name, "Your profession has been set.");
		$this->join_window($name);
	}

	// Makes a clickable link of the pre-configured Tier 2 item (for tells)
	function make_ref($item)
	{
		if (! $this->ids[$item])
			return "";
		return $this->bot->core("tools")->make_item($this->ids[$item], $this->ids[$item], $this->qls[$item], $this->names[$item]);
	}

	function make_link($item)
	{
		if (! $this->ids[$item])
			return "";
		return "<a href='itemref://" . $this->ids[$item] . "/" . $this->ids[$item] . "/" . $this->qls[$item] . "'>" . $this->names[$item] . "</a>";
	}
}
?>
