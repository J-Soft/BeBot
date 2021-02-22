<?php
/*
* Aggdef.php - Module Aggdef.
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

$aggdef = new aggdef($bot);


class aggdef extends BaseActiveModule
{
	var $bot;

	function __construct (&$bot)
	{
		parent::__construct($bot, get_class($this));
		
		$this -> register_command("all", "aggdef", "GUEST");
		
 		$this -> help['description'] = 'Determines AggDef slider setting';
		$this -> help['command']['aggdef <attack> <recharge> <init>']="Shows at what percentage you have to put the aggdef slider.";

	}
	
	function command_handler($name, $msg, $origin)
	{
			$com = $this->parse_com($msg, array('com', 'ar', 'rr','in'));
			switch($com['com'])
			{
				case'aggdef':
					Return($this -> aggdef_blob($com['ar'],$com['rr'],$com['in']));
				break;
				Default:
					Return "##error##Error : Broken plugin, received unhandled command: ".$vars[0]."##end##";
			}
	}
	
	function attackinit($change)
	{
		if ($change <=0)
		{
			return 1;
		}
		else
		{
			return round($change*600);
		}
	}

	function rechargeinit($change)
	{
		if ($change <=0)
		{
			return 1;
		}
		else
		{
			return round($change*300);
		}
	}
	
	function aggdef_blob($attackrate,$rechargerate,$inits)
	{
			$diminishratio = 1/3;
					
			if ($inits > 1200)
			$inits = 1200+round(($inits-1200)*diminishratio);
			
			$attackspeed = $attackrate - ($inits/600);
			$rechargespeed = $rechargerate - ($inits/300);
						
			if ($attackspeed < $rechargespeed)
				$speedchange = $rechargespeed -1;
			else
				$speedchange = $attackspeed -1;
			
			$barpos = 87.5+($speedchange*50);
						
			if ($barpos >100 )
				$barpos = 100;

			if ($barpos <0 ) 
				$barpos = 0;
			
			$attackinitreq = $this->attackinit($attackrate + 0.75);
			$rechargeinitreq = $this->rechargeinit($rechargerate + 0.75);
			
			if ($attackinitreq > $rechargeinitreq)
			{
				$finalinit = $attackinitreq;
			}
			else
			{	
				$finalinit = $rechargeinitreq;
			}
			
			if ($finalinit > 1200)
			{
				$finalinit = 1200 + round((finalinit-1200)/diminishratio);
			}	
			$inside = "Aggdef slider calculator\n\n";
			
			$inside .= "Your attack speed : <font color=#FFF000>".$attackrate."</font>\n";
			$inside .= "Your recharge speed : <font color=#FFF000>".$rechargerate."</font>\n";
			$inside .= "Your inits : <font color=#FFF000>".$inits."</font>\n\n\n";
			
			$inside .= "Optimal slider position with your inits : <font color=#FFF000>".round($barpos)."%</font>\n";
			$inside .= "Inits needed for full def : <font color=#FFF000>".$finalinit."</font>\n\n\n\n\n\n";
			$inside .= "Made by Quintrell - RK1";
			
			return $this -> bot -> core("tools") -> make_blob("AggDef slider Information", $inside);
	}
		
}
?>