<?php
/*
* Aigen.php - Module Ai Generals
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

$aigen = new aigen($bot);

class aigen extends BaseActiveModule
{

	function __construct (&$bot)
	{
		parent::__construct($bot, get_class($this));
	
		$this -> register_command("all", "aigen", "MEMBER");
		$this -> help['description'] = "Gives information on alien general drops";
		$this -> help['command']['aigen <expression>'] = "aigen [Ankari|Ilari|Rimah|Jaax|Xoch|Cha]";
	}
	
	function command_handler($source, $msg, $type)
	{
		$this->error->reset();
		
		$com = $this->parse_com($msg, array('com', 'sub'));
		$com['sub'] = strtolower($com['sub']);	
		switch($com['com'])
		{
			case 'aigen':
				if ($com['sub'] == 'ankari' || $com['sub'] == 'ilari' || $com['sub'] == 'rimah' || $com['sub'] == 'jaax' || $com['sub'] == 'xoch' || $com['sub'] == 'cha')
				{
					return($this -> make_aigen($source, $type, $com));
					break;
				} else {
					$this -> error -> set("You have to submit a name of a existing general. Only Ankari|Ilari|Rimah|Jaax|Xoch|Chav known");
					return($this->error->message());
					break;
				}
			default:
				$this -> error -> set("Broken plugin, recieved unhandled command: $command");
				return($this->error->message());
		}
	}

	function make_aigen($source, $type, $com)
	{
		$gen = $com['sub'];
		$ai['ankari']="<u>Ankari</u><br><br>Low Evade/Dodge,low AR, casting Viral/Virral nukes.<br><br>Boss of this type drops:<br><br><img src=rdb://100337></img><br><a href='itemref://247145/247145/300'>Arithmetic Lead Viralbots</a>.<br><orange>(Nanoskill / Tradeskill)<br><img src=rdb://255705></img><br><a href='itemref://247684/247684/300'>Kyr'Ozch Bio-Material - Type 1</a></br><br><img src=rdb://255705></img><br><a href='itemref://247685/247685/300'>Kyr'Ozch Bio-Material - Type 2</a>";
		$ai['ilari']="<u>Ilari</u><br><br>Low Evade/Dodge.<br>Boss of this type drops:<br><br><img src=rdb://100337></img><br><a href='itemref://247146/247146/300'>Spiritual Lead Viralbots</a>.<orange><br>(Nanocost / Nanopool / Max Nano)<br><img src=rdb://255705></img><br><a href='itemref://247681/247681/300'>Kyr'Ozch Bio-Material - Type 992</a><br><img src=rdb://255705></img><br><a href='itemref://247679/247679/300'>Kyr'Ozch Bio-Material - Type 880</a>";
		$ai['rimah']="<u>Rimah</u><br><br>Low Evade/Dodge.<br>Boss of this type drops:<br><br><img src=rdb://100337></img><br><a href='itemref://247143/247143/300'>Observant Lead Viralbots</a>.<orange><br>(Init / Evades)<br><img src=rdb://255705></img><br><a href='itemref://247675/247675/300'>Kyr'Ozch Bio-Material - Type 112</a><br><img src=rdb://255705></img><br><a href='itemref://247678/247678/300'>Kyr'Ozch Bio-Material - Type 240</a>";
		$ai['jaax']="<u>Jaax</u><br><br>High Evade, Low Dodge.<br>Boss of this type drops:<br><br><img src=rdb://100337></img><br><a href='itemref://247139/247139/300'>Strong Lead Viralbots</a>.<orange><br>(Melee / Spec Melee / Add All Def / Add Damage)<br><img src=rdb://255705></img><br><a href='itemref://247694/247694/300'>Kyr'Ozch Bio-Material - Type 3</a><br><img src=rdb://255705></img><br><a href='itemref://247688/247688/300'>Kyr'Ozch Bio-Material - Type 4</a>";
		$ai['xoch']="<u>Xoch</u><br><br>High Evade/Dodge, casting Ilari Biorejuvenation heals.<br>Boss of this type drops:<br><br><img src=rdb://100337></img><br><a href='itemref://247137/247137/300'>Enduring Lead Viralbots</a>.<orange><br>(Max Health / Body Dev)</br><br><img src=rdb://255705></img><br><a href='itemref://247690/247690/300'>Kyr'Ozch Bio-Material - Type 5</a><br><img src=rdb://255705></img><br><a href='itemref://247692/247692/300'>Kyr'Ozch Bio-Material - Type 12</a><br><img src=rdb://255705></img><br><a href='itemref://288673/288673/300'>Kyr'Ozch Bio-Material - Type 48</a>";
		$ai['cha']="<u>Cha</u><br><br>High Evade/NR, Low Dodge.<br>Boss of this type drops:<br><br><img src=rdb://100337></img><br><a href='itemref://247141/247141/300'>Supple Lead Viralbots</a>.<orange><br>(Ranged / Spec Ranged / Add All Off)<br><img src=rdb://255705></img><br><a href='itemref://247696/247696/300'>Kyr'Ozch Bio-Material - Type 13</a><br><img src=rdb://255705></img><br><a href='itemref://247674/247674/300'>Kyr'Ozch Bio-Material - Type 76</a>";
		
		$aigen_output = '<a href="text://'.$ai[$gen].'">'.$gen.'</a>';
		$this -> bot -> send_output($source, $aigen_output, $type);
	}

}
?>