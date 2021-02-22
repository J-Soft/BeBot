<?php
/*
* Axp.php - Module template.
* AXP - Display AI Level informations
* Sevrius Veeshan - Copyright (C) 2018
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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

$axp = new Axp($bot);

class Axp extends BaseActiveModule
{
	function __construct (&$bot)
	{
		// Initialize the base module
		parent::__construct($bot, get_class($this));

		// Register command
		$this->register_command('all', 'axp', 'GUEST');

		// Add description/help
		$this -> help['description'] = "Display all AI Levels informations.";
		$this -> help['command']['axp'] = "No argument for this command.";
		$this -> register_alias('axp', 'aixp');
	}

	function command_handler($source, $msg, $origin)
	{		
		$this->error->reset();
		
		$vars = explode(' ', strtolower($msg));
		$command = $vars[0];

		switch($command)
		{
			case 'axp':			
				return $this -> show_infos($source);
				break;			
			default:				
				$this -> error -> set("Broken plugin, received unhandled command: $command");
				return($this->error->message());
		}
	}
	
	function show_infos($source)
	{		
		$command = "Alien Experience";
		$window = "##lightyellow## Alien Experience<br>
		##aqua##<u>AI Lvl - AXP   - Rank         - Lvl Req.</u><br>##green##
		 1 -      1.500 - Fledgling - 5
		 2 -     9.000 - Amateur - 15
		 3 -    22.500 - Beginner - 25
		 4 -    42.000 - Starter - 35
		 5 -    67.500 - Newcomer - 45
		 6 -    99.000 - Student - 55
		 7 -   136.500 - Common - 65
		 8 -   180.000 - Intermediate - 75
		 9 -   229.500 - Mediocre - 85
		10 -   285.000 - Fair - 95
		11 -   346.500 - Able - 105
		12 -   414.000 - Accomplished - 110
		13 -   487.500 - Adept - 115
		14 -   567.000 - Qualified - 120
		15 -   697.410 - Competent - 125
		16 -   857.814 - Suited - 130
		17 - 1.055.112 - Talented - 135
		18 - 1.297.787 - Trustworthy - 140
		19 - 1.596.278 - Supporter - 145
		20 - 1.931.497 - Backer - 150
		21 - 2.298.481 - Defender - 155
		22 - 2.689.223 - Challenger - 160
		23 - 3.092.606 - Patron - 165
		24 - 3.494.645 - Protector - 170
		25 - 3.879.056 - Medalist - 175
		26 - 4.228.171 - Champ - 180
		27 - 4.608.707 - Hero - 185
		28 - 5.023.490 - Guardian - 190
		29 - 5.475.604 - Vanquisher - 195
		30 - 5.968.409 - Vindicator - 200";
		return($this -> bot -> core("tools") -> make_blob($command, $window));
	}
}
?>
