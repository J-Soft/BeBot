<?php
/*
* Written by Zacix for BeBot & modified by Bitnykk
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2010 Thomas Juberg, ShadowRealm Creations and the BeBot development ri.
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
* File last changed at $LastChangedDate: nov 2017 $
* Revision: $Id: Ris.inc 1835 $
*/
define("MAXMEM", 36);

class Ri {
	var $riMembers;
	var $riName;
	var $numMembers;
	var $leader;

	function __construct($riMember,$riName) {
		$this->riMembers = array();
		$this->riName = $riName;
		$this->numMembers = 0;
		$this->AddMember($riMember);
	}

	function SetLeader($riMember) {
		$this->leader = $riMember;
	}

	function AddMember($riMember) {
		if(count($this->riMembers) < MAXMEM) {
			$this->numMembers++;
			return array_push($this->riMembers,$riMember);
		}
		else {
			return false;
		}
	}

	function DelMember($name) {
		$offset = $this->GetMemberOffset($name);
		if(!is_bool($offset)) {
			unset($this->riMembers[$offset]);
			$this->riMembers = array_values($this->riMembers);
			$this->numMembers--;
			return true;
		}
		else {
			return false;
		}
	}

	function GetMemberOffset($name) {
		for($i=0;$i<count($this->riMembers);$i++) {
			if($this->riMembers[$i]->GetName() == $name) {
				return $i;
			}
		}
		return false;
	}
	function GetMember($name) {
		for($i=0;$i<count($this->riMembers);$i++) {
			if($this->riMembers[$i]->GetName() == $name) {
				return $this->riMembers[$i];
			}
		}
		return false;
	}

	function Count() {
		return $this->numMembers;
	}

	function Clear() {
		for($i=0;$i<count($this->riMembers);$i++) {
			$this->riMembers[$i]->DelMember();
		}
	}

	function ClearLeader() {
		for($i=0;$i<count($this->riMembers);$i++) {
			$this->riMembers[$i]->SetLeader(false);
		}
	}

	function GetName() {
		return $this->riName;
	}

	function GetRiMembers() {
		return $this->riMembers;
	}

	function SetName($name) {
		$this->riName = $name;
	}
}

class RiMember {
	var $name;
	var $level;
	var $profession;
	var $guild;
	var $leader;

	function __construct($name,$level,$profession,$guild,$leader=false) {
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
