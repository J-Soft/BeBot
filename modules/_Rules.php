<?php
/*
* Rules.php - Displays raidrules.
*
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
* File last changed at $LastChangedDate: 2008-11-30 23:09:06 +0100 (Sun, 30 Nov 2008) $
* Revision: $Id: _Rules.php 1833 2008-11-30 22:09:06Z alreadythere $
*/

$rules = new Rules($bot);



/*
The Class itself...
*/
class Rules extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_command('all', 'rules', 'GUEST');
	}



	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		return $this -> make_rules();
	}



	/*
	Make the rules
	*/
	function make_rules()
	{
		$content = "<font color=CCInfoHeadline> :::: RULES ::::</font>\n\n";
		$content .= implode("", file("./txt/rules.txt"));

		return "<botname>'s Rules :: " . $this -> bot -> core("tools") -> make_blob("click to view", $content);
	}
}
?>