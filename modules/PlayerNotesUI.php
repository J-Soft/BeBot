<?php
/*
*
* PlayerNotesInterface.php - User Interface for Player Notes.
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg Stens?s, ShadowRealm Creations and the BeBot development team.
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
*
* File last changed at $LastChangedDate: 2008-11-30 23:09:06 +0100 (sø, 30 nov 2008) $
* Revision: $Id: PlayerNotesUI.php 1833 2008-11-30 22:09:06Z alreadythere $
*/

$playernotes_ui = new PlayerNotes_UI($bot);

/*
The Class itself...
*/
class PlayerNotes_UI extends BaseActiveModule
{ // Start Class
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_command("all", "notes", "MEMBER", array('add' => 'MEMBER', 'rem'=>'ADMIN'));
		$this -> register_alias("notes", "note");
	}

	/*
	This function handles all the inputs and returns FALSE if the
	handler should not send output, otherwise returns a string
	sutible for output via send_tell, send_pgroup, and send_gc.
	*/
	function command_handler($name, $msg, $source)
	{ // Start function handler()
		$com = $this -> parse_com($msg);
		Switch($com['sub'])
		{
			case'add':
				return $this -> add_note($name, $com['args']);
				break;
			case'add':
			case'admin':
				$admin = explode(" ", $msg[2], 2);
				if(strtolower($admin[0]) == "add" || strtolower($admin[0]) == "admin")
					return $this -> add_note($name, $admin[1], TRUE);
				else
					return $this -> add_note($name, $msg[2], FALSE);
			case'rem':
			case'del':
				return $this -> rem_note($com['args']);
			case'':
				return $this -> show_all_notes($name, $source);
			Default:
				return $this -> show_notes($name, $com['sub']);
		}
		return false;
	} // End function handler()

	function add_note($author, $msg, $admin=FALSE)
	{ // Start function add_note()
		$args = $this -> parse_com($msg, array('target', 'reason'));
		$author = ucfirst(strtoupper($author));
		$player = ucfirst(strtoupper($args['target']));
		$note = $args['reason'];

		if ($admin)
		{
			if ($this -> bot -> core("security") -> check_access($author, "ADMIN"))
			{
				return($this -> bot -> core("player_notes") -> add($player, $author, $note, "admin"));
			}
			else
			{
				$this -> error -> set("Your access level must be ADMIN or higher to add admin notes.");
				return($this -> error);
			}
		}
		else
		{
			return($this -> bot -> core("player_notes") -> add($player, $author, $note, "default"));
		}
	} // End function add_note()

	function rem_note($pnid)
	{ // Start function rem_note()
		return ($this -> bot -> core("player_notes") -> del($pnid));
	} // End function rem_note()

	/*
	Show notes for a player
	*/
	function show_notes ($source, $player)
	{ // Start function show_notes()
		$source = ucfirst(strtolower($source));
		$player = ucfirst(strtolower($player));
		if (!$this -> bot -> core("chat") -> get_uid($player))
		{
			$this->error->set("Player '$player' is not a valid character");
			return($this->error);
		}
		$result = $this -> bot -> core("player_notes") -> get_notes($source, $player, "all", "DESC");
		if ($result instanceof BotError) // Some error occured
		{
			return $result;
		}
		else
		{
			$inside = "Notes for ".$player.":\n\n";
			foreach ($result as $note)
			{
				if ($note['class'] == 1)
				{
					$inside .= "Ban Reason #";
				}
				elseif ($note['class'] == 2)
				{
					$inside .= "Admin Note #";
				}
				else
				{
					$inside .= "Note #";
				}
				$inside .= $note['pnid']." added by ".$note['author']." on ".gmdate($this -> bot -> core("settings") -> get("Time", "FormatString"), $note['timestamp']).":\n";
				$inside .= $note['note'];
				$inside .= "\n\n";
			}
			return ("Notes for ".$this -> bot -> core("tools") -> make_blob($player, $inside));
		}
	} // End function show_notes()

	/*
	Show notes for all players
	*/
	function show_all_notes($source, $origin)
	{
		$source = ucfirst(strtolower($source));
		$result = $this -> bot -> core("player_notes") -> get_notes($source, "all", "all", "DESC");
		if ($result instanceof BotError) // Some error occured
		{
			return $return;
		}
		else
		{
			$inside = "  :: All Players with Notes ::\n\n";
			foreach ($result as $note)
			{
				$count[$note["player"]][$note['class']]++;
			}
			foreach($count as $player => $data)
			{
				foreach(array(0, 1, 2) as $n)
					if($data[$n] < 1)
						$data[$n] = 0;
				$inside .= $this -> bot -> core("tools") -> chatcmd("notes ".$player, $player, $origin)." ".$data[2]." Admin Notes, ".$data[1]." Ban Notes, ".$data[0]." Normal Notes\n";
			}
			$return = "All Players with Notes :: ".$this -> bot -> core("tools") -> make_blob("Click to view", $inside);
		}
		Return $return;
	}

} // End of Class
?>