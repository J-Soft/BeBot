<?php
/*
* Cluster.php - Module Cluster.
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
* - Bitnykk (RK5)
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

$cluster = new Cluster($bot);

class Cluster extends BaseActiveModule
{
	var $bot;

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct (&$bot)
	{
		parent::__construct($bot, get_class($this));
		
		$this -> register_command("all", "cluster", "GUEST");
		
 		$this -> help['description'] = 'Determines maximum implant ql that a cluster will fit into.';
		$this -> help['command']['cluster <ql> <type>']="Shows you max ql implant that a <type> cluster will fit into. Where <type> can be faded, bright, or shiny.";

	}
	
	/*
	This gets called on private channel with the command
	*/
	function command_handler($name, $msg, $origin)
	{
			$com = $this->parse_com($msg, array('com', 'ql', 'type'));
			switch($com['com'])
			{
				case'cluster':
					Return($this -> cluster_blob($com['ql'], $com['type']));
				break;
				Default:
					Return "##error##Error : Broken plugin, received unhandled command: ".$vars[0]."##end##";
			}
	}
			

	/*
	Makes the implant-blob
	*/
	function cluster_blob($ql, $type) {
	
	if ($type == "faded") $result = round($ql * (1/0.82),0);
	else if ($type == "bright") $result = round($ql * (1/0.84),0);
	else if ($type == "shiny") $result = round($ql * (1/0.86),0);
	else if ($type == "") return "Be sure to specify cluster type: faded, bright, or shiny.";
	else return "Unknown input, usage: !cluster ql type";
		
	
	if ($ql >= 301) return "Input ql too high.";
	if ($ql <= 0) return "Input ql too low.";
	
	if ($ql >= 163 && $ql <= 200 && $result >= 200) $result = 200;
	if ($result >= 300) $result = 300;
		
	if ($result <= 200) return "A QL <font color=#97BE37>" . $ql . "</font> ". $type ." cluster can fit into a maximum of QL <font color=#97BE37>" . $result . "</font> implant.";
	if ($result >= 201) return "A QL <font color=#97BE37>" . $ql . "</font> ". $type ." cluster can fit into a maximum of QL <font color=#97BE37>" . $result . "</font> refined implant.";
	}


}
?>