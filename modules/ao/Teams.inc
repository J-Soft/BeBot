<?php
/*
* Written by Zacix for BeBot
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
define("MAXMEM", 6);

class Team {
	var $teamMembers;
	var $teamName;
	var $numMembers;
	var $leader;

	function Team(&$teamMember,$teamName) {
		$this->teamMembers = array();
		$this->teamName = $teamName;
		$this->numMembers = 0;
		$this->AddMember(&$teamMember);
	}

	function SetLeader(&$teamMember) {
		$this->leader = &$teamMember;
	}

	function AddMember(&$teamMember) {
		if(count($this->teamMembers) < MAXMEM) {
			$this->numMembers++;
			return array_push($this->teamMembers,&$teamMember);
		}
		else {
			return false;
		}
	}

	function DelMember($name) {
		$offset = $this->GetMemberOffset($name);
		if(!is_bool($offset)) {
			unset($this->teamMembers[$offset]);
			$this->teamMembers = array_values($this->teamMembers);
			$this->numMembers--;
			return true;
		}
		else {
			return false;
		}
	}

	function GetMemberOffset($name) {
		for($i=0;$i<count($this->teamMembers);$i++) {
			if($this->teamMembers[$i]->GetName() == $name) {
				return $i;
			}
		}
		return false;
	}
	function &GetMember($name) {
		for($i=0;$i<count($this->teamMembers);$i++) {
			print_r(get_object_vars($this->teamMembers[$i]));
			if($this->teamMembers[$i]->GetName() == $name) {
				return $this->teamMembers[$i];
			}
		}
		return false;
	}

	function Count() {
		return $this->numMembers;
	}

	function Clear() {
		for($i=0;$i<count($this->teamMembers);$i++) {
			$this->teamMembers[$i]->DelMember();
		}
	}

	function ClearLeader() {
		for($i=0;$i<count($this->teamMembers);$i++) {
			$this->teamMembers[$i]->SetLeader(false);
		}
	}

	function GetName() {
		return $this->teamName;
	}

	function &GetTeamMembers() {
		return $this->teamMembers;
	}

	function SetName($name) {
		$this->teamName = $name;
	}
}

class TeamMember {
	var $name;
	var $level;
	var $profession;
	var $guild;
	var $leader;

	function TeamMember($name,$level,$profession,$guild,$leader=false) {
		$this->name = $name;
		$this->level = $level;
		$this->profession = $profession;
		$this->guild = $guild;
		$this->leader = $leader;
	}

	function GetName() {
		return $this->name;
	}

	function GetLevel() {
		return $this->level;
	}

	function GetProfession() {
		return $this->profession;
	}

	function GetGuild() {
		return $this->guild;
	}

	function IsLeader() {
		return $this->leader;
	}

	function SetLeader($leader) {
		$this->leader = $leader;
	}
}

?>
