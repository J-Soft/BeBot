<?php
/*
*  Xp.php - (A)XP helper
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

$xp = new XP($bot);

  /*
	The Class itself...
  */
class XP extends BaseActiveModule
{
	var $bot;

	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));

		
		$this -> register_command('all', 'xp', 'GUEST');
		$this -> register_command('all', 'axp', 'GUEST');
		$this -> register_command('all', 'lexp', 'GUEST');
		
		$this -> help['description'] = "Show (A)XP needed";
		$this -> help['command']['xp [level]']="Show XP needed for that level.";
		$this -> help['command']['xp [level] [level]']="Show XP needed for that level range.";
		$this -> help['command']['xp']="Show level requirement on AXP/research levels.";
		$this -> help['command']['axp [level]']="Show AXP needed for that level.";
		$this -> help['command']['axp [level] [level]']="Show AXP needed for that level range.";
		$this -> help['command']['axp']="Show level requirement on AXP/research levels.";
		$this -> help['command']['lexp [level]']="Show XP needed for that research level.";
		$this -> help['command']['lexp [level] [level]']="Show XP needed for that research level range.";
		$this -> help['command']['lexp']="Show level requirement on AXP/research levels.";
	}
	
	function command_handler($source, $msg, $origin)
	{
	  //ALWAYS reset the error handler before parsing the commands to prevent stale errors from giving false reports
	  $this->error->reset();
      
      $com = $this->parse_com($msg, array('com', 'lvlstart', 'lvlend'));
      if (isset($com['lvlend'])) {
		$com['lvlend'] = explode(' ', $com['lvlend']);
		$com['lvlend'] = $com['lvlend'][0];
	  }

      switch($com['com'])
      {
         case 'xp':
			if (isset($com['lvlstart']) && isset($com['lvlend'])) { return($this -> ShowMultiXP($com['lvlstart'], $com['lvlend'])); }
			if (isset($com['lvlstart']) && !isset($com['lvlend'])) { return($this -> ShowSingleXP($com['lvlstart'])); }
			if (!isset($com['lvlstart']) && !isset($com['lvlend'])) { return($this -> ShowAXPLevelReq()); }
            break;
         case 'axp':
            if (isset($com['lvlstart']) && isset($com['lvlend'])) { return($this -> ShowMultiAXP($com['lvlstart'], $com['lvlend'])); }
			if (isset($com['lvlstart']) && !isset($com['lvlend'])) { return($this -> ShowSingleAXP($com['lvlstart'])); }
			if (!isset($com['lvlstart']) && !isset($com['lvlend'])) { return($this -> ShowAXPLevelReq()); }
		 case 'lexp':
            if (isset($com['lvlstart']) && isset($com['lvlend'])) { return($this -> ShowMultiLE($com['lvlstart'], $com['lvlend'])); }
			if (isset($com['lvlstart']) && !isset($com['lvlend'])) { return($this -> ShowSingleLE($com['lvlstart'])); }
			if (!isset($com['lvlstart']) && !isset($com['lvlend'])) { return($this -> ShowAXPLevelReq()); }
         default:
            // Just a safety net to allow you to catch errors where a module has registered  a command, but fails to actually do anything about it
            $this -> error -> set("Broken plugin, received unhandled command: $command");
            return($this->error->message());
      }
	}

	function ShowMultiXP($start, $end)
	{
		$xp = $sk = 0;
		if($start >= 1 && $start <= 220 && $end >= 1 && $end <= 220)
		{
			if($start < $end)
			{
				for($i=$start;$i<=$end-1;$i++)
				{
					if($i <= 199)
						$xp += $this -> ReturnXP($i);
					else
						$sk += $this -> ReturnXP($i);
				}
				if($xp && !$sk)
					return "From the beginning of ##highlight##".$start."##end## to ##highlight##".$end."##end##, you need ##highlight##".number_format($xp)."##end## XP";
				else if(!$xp && $sk)
					return "From the beginning of ##highlight##".$start."##end## to ##highlight##".$end."##end##, you need ##highlight##".number_format($sk)."##end## SK";
				else if($xp && $sk)
					return "From the beginning of ##highlight##".$start."##end## to ##highlight##".$end."##end##, you need ##highlight##".number_format($xp)."##end## XP and ##highlight##".number_format($sk)."##end## SK";
			}
			else
				return "The last level cant be higher then the first level";
		}
		else
			return "You need to specify a level between 1 and 220";
	}

	function ShowMultiAXP($start, $end)
	{
		$axp = 0;		
		if($start >= 0 && $start <= 30 && $end >= 1 && $end <= 30)
		{
			if($start < $end)
			{
				for($i=$start+1;$i<=$end;$i++)
					$axp += $this -> ReturnAXP($i);
				return "From the beginning of ##highlight##".$start."##end## to ##highlight##".$end."##end##, you need ##highlight##".number_format($axp)."##end## AXP";
			}
			else
				return "The last level cant be higher then the first level";
		}
		else
			return "You need to specify a level between 0 and 30";
	}
	
	function ShowMultiLE($start, $end)
	{
		$lexp = 0;				
		if($start >= 0 && $start <= 10 && $end >= 1 && $end <= 10)
		{
			if($start < $end)
			{
				for($i=$start+1;$i<=$end;$i++)
					$lexp += $this -> ReturnLE($i);
				return "From the beginning of ##highlight##".$start."##end## to ##highlight##".$end."##end##, you need ##highlight##".number_format($lexp)."##end## XP";
			}
			else
				return "The last level cant be higher then the first level";
		}
		else
			return "You need to specify a level between 0 and 10";
	}

	function ShowSingleXP($level)
	{
		if($level >= 1 && $level <= 220)
		{
			if($level <= 199)
				return "Level ##highlight##".$level."##end## needs ##highlight##".number_format($this -> ReturnXP($level))."##end## XP to level";
			else
				return "Level ##highlight##".$level."##end## needs ##highlight##".number_format($this -> ReturnXP($level))."##end## SK to level";
		}
		else
			return "You need to specify a level between 1 and 220";
	}

	function ShowSingleAXP($level)
	{
		if($level >= 1 && $level <= 30)
			return "Level ##highlight##".$level."##end## needs ##highlight##".number_format($this -> ReturnAXP($level))."##end## AXP to level";
		else
			return "You need to specify a level between 1 and 30";
	}
	
	function ShowSingleLE($level)
	{
		if($level >= 1 && $level <= 10)
			return "Level ##highlight##".$level."##end## needs ##highlight##".number_format($this -> ReturnLE($level))."##end## XP to level";
		else
			return "You need to specify a level between 1 and 10";
	}

	function ShowAXPLevelReq()
	{
		$inside = "##blob_title##Alien/Research level vs Real level##end##\n\n";

		$inside .= "##blob_text##";
		$inside .= "R1= Level 1\n";
		$inside .= "A1= Level 5\n";
		$inside .= "A2= Level 15\n";
		$inside .= "A3= Level 25\n";
		$inside .= "A4= Level 35\n";
		$inside .= "A5= Level 45\n";
		$inside .= "R2= Level 50\n";
		$inside .= "A6= Level 55\n";
		$inside .= "A7= Level 65\n";
		$inside .= "A8= Level 75\n";
		$inside .= "R3= Level 75\n";
		$inside .= "A9= Level 85\n";
		$inside .= "A10= Level 95\n";
		$inside .= "R4= Level 100\n";
		$inside .= "A11= Level 105\n";
		$inside .= "A12= Level 110\n";
		$inside .= "A13= Level 115\n";
		$inside .= "A14= Level 120\n";
		$inside .= "A15= Level 125\n";
		$inside .= "R5= Level 125\n";
		$inside .= "A16= Level 130\n";
		$inside .= "A17= Level 135\n";
		$inside .= "A18= Level 140\n";
		$inside .= "A19= Level 145\n";
		$inside .= "A20= Level 150\n";
		$inside .= "R6= Level 150\n";
		$inside .= "A21= Level 155\n";
		$inside .= "A22= Level 160\n";
		$inside .= "A23= Level 165\n";
		$inside .= "A24= Level 170\n";
		$inside .= "A25= Level 175\n";
		$inside .= "R7= Level 175\n";
		$inside .= "A26= Level 180\n";
		$inside .= "A27= Level 185\n";
		$inside .= "A28= Level 190\n";
		$inside .= "R8= Level 190\n";
		$inside .= "R9= Level 190\n";
		$inside .= "A29= Level 195\n";
		$inside .= "A30= Level 200\n";
		$inside .= "R10= Level 200";
		$inside .= "##end##";
		Return("Alien/Research level requirements :: ".$this -> bot -> core("tools") -> make_blob("Click to View", $inside));
	}

	function ReturnAXP($lvl)
	{
		$level[1] = 1500;
		$level[2] = 9000;
		$level[3] = 22500;
		$level[4] = 42000;
		$level[5] = 67500;
		$level[6] = 99000;
		$level[7] = 136500;
		$level[8] = 180000;
		$level[9] = 229500;
		$level[10] = 285000;
		$level[11] = 346500;
		$level[12] = 414000;
		$level[13] = 487500;
		$level[14] = 567000;
		$level[15] = 697410;
		$level[16] = 857814;
		$level[17] = 1055112;
		$level[18] = 1297787;
		$level[19] = 1596278;
		$level[20] = 1931497;
		$level[21] = 2298481;
		$level[22] = 2689223;
		$level[23] = 3092606;
		$level[24] = 3494645;
		$level[25] = 3879056;
		$level[26] = 4228171;
		$level[27] = 4608707;
		$level[28] = 5023490;
		$level[29] = 5475604;
		$level[30] = 5968409;
		return $level[$lvl];
	}

	function ReturnXP($lvl)
	{
		$level[1] = 1450;
		$level[2] = 2600;
		$level[3] = 3100;
		$level[4] = 4000;
		$level[5] = 4500;
		$level[6] = 5000;
		$level[7] = 5500;
		$level[8] = 6000;
		$level[9] = 6500;
		$level[10] = 7000;
		$level[11] = 7700;
		$level[12] = 8300;
		$level[13] = 8900;
		$level[14] = 9600;
		$level[15] = 10400;
		$level[16] = 11000;
		$level[17] =11900;
		$level[18] =12700;
		$level[19] =13700;
		$level[20] =15400;
		$level[21] =16400;
		$level[22] =17600;
		$level[23] =18800;
		$level[24] =20100;
		$level[25] =21500;
		$level[26] =22900;
		$level[27] =24500;
		$level[28] =26100;
		$level[29] =27800;
		$level[30] =30900;
		$level[31] =33000;
		$level[32] =35100;
		$level[33] =37400;
		$level[34] =39900;
		$level[35] =42400;
		$level[36] =45100;
		$level[37] =47900;
		$level[38] =50900;
		$level[39] = 54000;
		$level[40] =57400;
		$level[41] =60900;
		$level[42] =64500;
		$level[43] =68400;
		$level[44] =76400;
		$level[45] = 81000;
		$level[46]=85900;
		$level[47]=91000;
		$level[48]=96400;
		$level[49]=101900;
		$level[50]=108000;
		$level[51]=114300;
		$level[52]=120800;
		$level[53]=127700;
		$level[54]=135000;
		$level[55]=142600;
		$level[56]=150700;
		$level[57]=161900;
		$level[58]=167800;
		$level[59]=177100;
		$level[60]=203500;
		$level[61]=214700;
		$level[62]=226700;
		$level[63]=239100;
		$level[64]=251900;
		$level[65]=265700;
		$level[66]=280000;
		$level[67]=294800;
		$level[68]=310600;
		$level[69]=327000;
		$level[70]=344400;
		$level[71]=362300;
		$level[72]=381100;
		$level[73]=401000;
		$level[74]=421600;
		$level[75]=443300;
		$level[76]=508100;
		$level[77]=534200;
		$level[78]=561600;
		$level[79]=590200;
		$level[80]=620000;
		$level[81]=651000;
		$level[82]=683700;
		$level[83]=717900;
		$level[84]=753500;
		$level[85]=790800;
		$level[86]=829400;
		$level[87]=870000;
		$level[88]=912600;
		$level[89]=956800;
		$level[90]=1003000;
		$level[91]=1051300;
		$level[92]=1101500;
		$level[93]=1153900;
		$level[94]=1208800;
		$level[95]=1266000;
		$level[96]=1325500;
		$level[97]=1387700;
		$level[98]=1452300;
		$level[99]=1519900;
		$level[100]=1590300;
		$level[101]=1663500;
		$level[102]=1739900;
		$level[103]=1819600;
		$level[104]=1902200;
		$level[105]=1988900;
		$level[106]=2078600;
		$level[107]=2172100;
		$level[108]=2269800;
		$level[109]=2371100;
		$level[110]=2476600;
		$level[111]=2586600;
		$level[112]=2701000;
		$level[113]=2819800;
		$level[114]=2943600;
		$level[115]=3072400;
		$level[116]=3205800;
		$level[117]=3345200;
		$level[118]=3489700;
		$level[119]=3640200;
		$level[120]=3796500;
		$level[121]=3958900;
		$level[122]=4128000;
		$level[123]=4303400;
		$level[124]=4485700;
		$level[125]=4674800;
		$level[126]=4871700;
		$level[127]=5075700;
		$level[128]=5288100;
		$level[129]=5508200;
		$level[130]=5736800;
		$level[131]=5974600;
		$level[132]=6220700;
		$level[133]=6474500;
		$level[134]=6742200;
		$level[135]=7017500;
		$level[136]=7303700;
		$level[137]=7600100;
		$level[138]=7907600;
		$level[139]=8227000;
		$level[140]=8557700;
		$level[141]=8901000;
		$level[142]=9256800;
		$level[143]=9625800;
		$level[144]=10008600;
		$level[145]=10405300;
		$level[146]=10816600;
		$level[147]=11242500;
		$level[148]=11684300;
		$level[149]=12141900;
		$level[150]=12616200;
		$level[151]=13107200;
		$level[152]=13616100;
		$level[153]=14143600;
		$level[154]=14689700;
		$level[155]=15255300;
		$level[156]=15841000;
		$level[157]=16447900;
		$level[158]=17075800;
		$level[159]=17725900;
		$level[160]=18399400;
		$level[161]=19096100;
		$level[162]=19817500;
		$level[163]=20564100;
		$level[164]=21336600;
		$level[165]=22136100;
		$level[166]=22963600;
		$level[167]=23819700;
		$level[168]=24705200;
		$level[169]=25621100;
		$level[170]=26569000;
		$level[171]=27548800;
		$level[172]=28562900;
		$level[173]=29611100;
		$level[174]=30695300;
		$level[175]=31816300;
		$level[176]=32975100;
		$level[177]=34173500;
		$level[178]=35412500;
		$level[179]=36692500;
		$level[180]=38016500;
		$level[181]=39384400;
		$level[182]=40797700;
		$level[183]=42258500;
		$level[184]=43768300;
		$level[185]=45328100;
		$level[186]=46939900;
		$level[187]=48604900;
		$level[188]=50324600;
		$level[189]=52101200;
		$level[190]=53936300;
		$level[191]=55831600;
		$level[192]=57788700;
		$level[193]=59810000;
		$level[194]=61897000;
		$level[195]=64052200;
		$level[196]=66277200;
		$level[197]=68574400;
		$level[198]=70945700;
		$level[199]=73393900;
		$level[200]=80000;
		$level[201]=96000;
		$level[202]=115200;
		$level[203]=138240;
		$level[204]=165888;
		$level[205]=199066;
		$level[206]=238879;
		$level[207]=286654;
		$level[208]=343985;
		$level[209]=412782;
		$level[210]=495339;
		$level[211]=594407;
		$level[212]=713288;
		$level[213]=855946;
		$level[214]=1027135;
		$level[215]=1232562;
		$level[216]=1479074;
		$level[217]=1774889;
		$level[218]=2129867;
		$level[219]=2555840;
		$level[220]=0;

		return $level[$lvl];
	}
	
	function ReturnLE($lvl)
	{
		$level[1] = 50000;
		$level[2] = 450000;
		$level[3] = 1600000;
		$level[4] = 4700000;
		$level[5] = 12750000;
		$level[6] = 32000000;
		$level[7] = 54000000;
		$level[8] = 64000000;
		$level[9] = 740000000;
		$level[10] = 900000000;
		return $level[$lvl];
	}
  }
?>
