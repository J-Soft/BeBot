<?php
/*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg Stensås, ShadowRealm Creations and the BeBot development team.
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
* File last changed at $LastChangedDate: 2008-12-02 03:08:55 +0000 (Tue, 02 Dec 2008) $
* Revision: $Id: Symbiants.php 39 2008-12-02 03:08:55Z temar $
*/

/*
* Something written by Wolfbiter
* Extended by BlueEagl3.
* !pb now split out to be in a separate module
*/

$symb_sql = new Symb_sql($bot);

class Symb_sql extends BaseActiveModule
{
	private $slots = array(
		'ocular'=>'eye',
		'brain'=>'head',
		'ear'=>'ear',
		'right arm'=>'rarm',
		'chest'=>'chest',
		'left arm'=>'larm',
		'right wrist'=>'rwrist',
		'waist'=>'waist',
		'left wrist'=>'lwrist',
		'right hand'=>'rhand',
		'thigh'=>'legs',
		'left hand'=>'lhand',
		'feet'=>'feet');
		
		
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this -> bot = &$bot;
		
		$this -> register_command("all", "symb", "GUEST");
		
		$this->help['command']['description'].= "Advanced search for symbiants and pocket bosses.";
		$this->help['command']['symb <level>[-<level2>]']="Find symbs of QL level. If [-<level2>] is specified it finds symbs between the two"; 
		$this->help['command']['symb <slot>']="Finds symbiant for the given slot.";
		$this->help['command']['symb <unit>']="Find a symbiant for the given <unit>.";
		$this->help['command']['symb <name>']="Find symbiants with the given <name>";
		$this->help['command']['pb <name>']="Find the pocket boss closest matching <name>";
		$this->help['command']['pb <pattern>']="Find the pocket boss produced by (pieces of) <pattern>";
		$this->help['notes'] = "You can use as many of each option as you like in every search.<br>";
		$this->help['notes'].= "Valid options for &gt;slot> are: eye, ocular, head, brain, ear, ";
		$this->help['notes'].= "right arm, rarm, chest, left arm, larm, ";
		$this->help['notes'].= "right wrist, rwritst, waist, left wrist, lwrist, ";
		$this->help['notes'].= "right hand, rhand, legs, thigh, left hand, lhand and feet<br>";
		$this->help['notes'].= "Valid options for &gt;unit> are: adventurer, adv, agent, beurocrat, crat, ";
		$this->help['notes'].= "doctor, doc, enforcer, enf, engineer, eng, fixer, martial artist, ma, ";
		$this->help['notes'].= "meta physicist, mp, nano technician, nt, soldier, sol and trader.<br><br>";
		$this->help['notes'].= "&gt;pattern> needs to be an item that is a pattern or piece of a pattern.";

		$this -> tables();
	}

	function tables()
	{
		$this -> bot -> db -> define_tablename("symbiants", "false");
		Switch($this -> bot -> db -> get_version("symbiants"))
		{
			case 1:
			case 2:
				$filename = "./extra/symbiants/symbiants.sql";
				$handle = fopen($filename, "r");
				$query = fread($handle, filesize($filename));
				fclose($handle);
				$query = explode(";", $query);
				foreach($query as $q)
				{
					if(!empty($q))
						$this -> bot -> db -> query($q);
				}
		}
		$this -> bot -> db -> set_version("symbiants", 3);
	}

	function command_handler($name,  $msg, $source)
	{
		$com = $this->parse_com($msg, array('com', 'args'));
		switch($com['com'])
		{
			case 'symb':
				return($this->symb($com['args']));
				break;
		}
	}

	function symb($args)
	{
		$query_ql = false; //quality level in query format
		$query_slot = false; //slots in query format
		$query_unit = false; //unit in query format
		$query_name = false; //Name of symbiant in query format
		$prof_units = array();

		//Condition args.
		
		//Lower case everything so we know what we're working with.
		$args = strtolower($args);
		//This is intended to change <low> - <high> to <low>-<high> but no checks are made to see if there are numbers surrounding the hypen
		$args = str_replace(' - ', '-', $args);
		//Turn any short hand profession into it's full name.
		$args = preg_replace(explode(',', '/\b'.$this->bot->core('professions')->get_shortcuts('\b/,/\b').'\b/'), $this->bot->core('professions')->get_profession_array(), $args);
		//Turn symbiant-style and full-name slots into short hand implant-style slots
		foreach($this->slots as $slot_name => $name)
		{
			$args=str_replace($slot_name, $name, $args);
		}
		//Turn any profession into a list of units they can use
		foreach($this->bot->core('professions')->get_profession_array() as $profession)
		{
			$args =str_replace($profession, $this->bot->core('professions')->get_unit_list($profession, ' '), $args);
		}
		
		//Make arguments an array we can iterate trough
		$args = explode(' ', $args);
		//Now figure out which argument is what and build a query for the database and readable output for the blob
		foreach ($args as $arg) 
		{
			//Check if $arg is a unit
			if(in_array($arg, $this->bot->core('professions')->get_unit_array()))
			{
				if(!in_array($arg, $prof_units))
				{
					$prof_units[]=$arg;
					if($query_unit === false)
					{
						$query_unit = "unit = '$arg'";
						$readable_units = "$arg";
					}
					else
					{
						$query_unit .= " or unit = '$arg'";
						$readable_units.= " and $arg";
					}
				}
			}
			
			//Check if $arg is a slot
			elseif($slot_match = array_search($arg, $this->slots)) 
			{
				if($query_slot === false)
				{
					$query_slot = "slot = '{$this->slots[$slot_match]}'";
					$readable_slot = $slot_match;
				}
				else
				{
					$query_slot .= " or slot='{$this->slots[$slot_match]}'";
					$readable_slot .= " and $slot_match";
				}
			}

			//Check if $arg is a number and thus a QL
			elseif(is_numeric($arg)) 
			{
				if($query_ql === false)
				{
					$query_ql="ql = $arg";
					$readable_ql = "$arg";
				}
				else
				{
					$query_ql .= " OR ql = $arg";
					$readable_ql.= " or $arg";
				}
			}

			//Check if $arg is a QL-range
			elseif (preg_match("/(\d{1,3})-(\d{1,3})/",$arg,$pql)) 
			{
				//Check if the range is low-high
				if($pql[1]<$pql[2])
				{
					$low = $pql[1];
					$high = $pql[2];
				}
				else
				{
					$low = $pql[2];
					$high = $pql[1];
				}
				if($query_ql === false)
				{
					$query_ql = " (ql >= $low AND ql <= $high)";
					$readable_ql = "between $low and $high";
				}
				else
				{
					$query_ql .= " OR (ql >= $low and ql <= $high)";
					$readable_ql .= " or between $low and $high";
				}
			}

			//Assume that $arg is name since it's none of the above
			else 
			{
				if($query_name === false)
				{
					$query_name = "t1.name like '%$arg%'";
					$readable_name = "'$arg'";
				}
				else
				{
					$query_name.= " or t1.name like '%$arg%'";
					$readable_name.= " or '$arg'";
				}
			}
		}
		
		//Put together readable output
		$readable_output ="<center>##blob_title##::: Symbiants :::##end##</center>\n";
		if(!empty($readable_units))
			$readable_output .= "##highlight##Units: ##end##$readable_units\n";
		if(!empty($readable_slot))
			$readable_output.= "##highlight##Slots: ##end##$readable_slot \n";
		if (!empty($readable_ql))
			$readable_output.= "##highlight##QL: ##end## $readable_ql\n";
		if (!empty($readable_name))
			$readable_output.="##highlight##Matching: ##end##$readable_name\n";
		$readable_output.="------------------------------------------\n\n";
		
		
		//Build a query
		if($query_ql !== false)
		{
			$where_string = "($query_ql)";
		}
		
		if($query_slot !== false)
		{
			if(!empty($where_string)) $where_string.= ' and ';
			$where_string.= "($query_slot)";
		}
		
		if($query_unit!==false)
		{
			if(!empty($where_string)) $where_string.= ' and ';
			$where_string.= "($query_unit)";
		}
		
		if($query_name!==false)
		{
			if(!empty($where_string)) $where_string.= ' and ';
			$where_string.= "($query_name)";
		}
		
		$query = "SELECT ql, slot, unit, t1.name AS symb, itemref, t2.name as boss FROM symbiants AS t1 JOIN pocketbosses AS t2 ON t1.boss_id = t2.id WHERE $where_string ORDER BY ql, unit";
		
		$symbiants = $this->bot->db->select($query, MYSQL_ASSOC);
		if(empty($symbiants))
		{
			$readable_output.='No matches...';
			return($this->bot->core('tools')->make_blob('Symbiants', $readable_output));
		}
		else
		{
			foreach($symbiants as $symb)
			{
				$title = "QL {$symb['ql']} {$symb['symb']} ".array_search($symb['slot'], $this->slots)." symbiant, {$symb['unit']} unit aban";
				$title = ucwords($title);
				$link = "<a href='itemref://{$symb['itemref']}/{$symb['itemref']}/{$symb['ql']}'>$title</a>";
				$readable_output .= "$link\n";
			}
		}
		return($this->bot->core('tools')->make_blob('Symbiants', $readable_output));
	}

}

?>
