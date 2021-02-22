<?php
/*
* Skills.php - Various skill calculation
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
*
* Developed by:
* - Bitnykk (RK2) - after Healnjoo, Nogoal, Imoutochan ...
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
*/

$check = new Skills($bot);

class Skills extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));

		$this -> register_command('all', 'aimedshot', 'GUEST');
		$this -> register_alias("aimedshot", "as");
		$this -> register_command('all', 'brawl', 'GUEST');
		$this -> register_alias("brawl", "bra");
		$this -> register_command('all', 'burst', 'GUEST');
		$this -> register_alias("burst", "bu");
		$this -> register_command('all', 'dimach', 'GUEST');
		$this -> register_alias("dimach", "dim");
		$this -> register_alias("dimach", "di");
		$this -> register_command('all', 'fullauto', 'GUEST');
		$this -> register_alias("fullauto", "fa");
		$this -> register_alias("fullauto", "fa");
		$this -> register_command('all', 'fastattack', 'GUEST');
		$this -> register_alias("fastattack", "fast");
		$this -> register_command('all', 'flingshot', 'GUEST');
		$this -> register_alias("flingshot", "fs");
		$this -> register_alias("flingshot", "fling");
		$this -> register_command('all', 'mafist', 'GUEST');
		$this -> register_alias("mafist", "ma");
		$this -> register_command('all', 'nanoinit', 'GUEST');
		$this -> register_alias("nanoinit", "ni");
		$this -> register_alias("nanoinit", "nanoi");

		$this -> help['command']['Aimedshot [Aimed Shot skill] [Weapon attack] [Weapon recharge]']="Command to get AS multiplier, recharge and cap.";
		$this -> help['command']['Brawl [Brawl skill]']="Command to get Brawl recharge, damage and stun details.";
		$this -> help['command']['Burst [Burst skill] [Weapon attack] [Weapon recharge] [Burst delay]']="Command to get Burst recharge and cap.";
		$this -> help['command']['Dimach [Dimach kill] [Profession]']="Command to get Dimach recharge and details.";
		$this -> help['command']['Fullauto [Full Auto skill] [Weapon attack] [Weapon recharge] [Full Auto delay]']="Command to get Full Auto details.";
		$this -> help['command']['Fastattack [Fast Attack skill] [Weapon attack]']="Command to get Fast Attack recharge and cap.";
		$this -> help['command']['Flingshot [Fling skill] [Weapon attack] ']="Command to get Fling Shot recharge and cap.";
		$this -> help['command']['Mafist [Martial Art skill] [Profession]']="Command to get MA Fist recharge and details.";
		$this -> help['command']['Nanoinit [Nano Init skill] [Nano Attack]']="Command to get Nano Init details.";

	}

	function command_handler($name, $msg, $origin)
	{
        	       if (preg_match('/^aimedshot ([0-9]+) (.+) (.+)/i', $msg, $info)) {
                	return $this -> aimed($info[1], $info[2], $info[3]);
                 } elseif (preg_match('/^burst ([0-9]+) (.+) (.+) (.+)/i', $msg, $info)) {
                	return $this -> burst($info[1], $info[2], $info[3], $info[4]);
                 } elseif (preg_match('/^brawl ([0-9]+)/i', $msg, $info)) {
                	return $this -> brawl($info[1]);
                 } elseif (preg_match('/^dimach ([0-9]+) (.+)/i', $msg, $info)) {
                	return $this -> dimach($info[1], $info[2]);
                 } elseif (preg_match('/^fullauto ([0-9]+) (.+) (.+) (.+)/i', $msg, $info)) {
                	return $this -> full($info[1], $info[2], $info[3]);
                 } elseif (preg_match('/^fastattack ([0-9]+) (.+)/i', $msg, $info)) {
                	return $this -> fast($info[1], $info[2], $info[3]);
                 } elseif (preg_match('/^flingshot ([0-9]+) (.+)/i', $msg, $info)) {
                	return $this -> fling($info[1], $info[2], $info[3]);
                 } elseif (preg_match('/^mafist ([0-9]+) (.+)/i', $msg, $info)) {
                	return $this -> mafist($info[1], $info[2], $info[3]);
                 } elseif (preg_match('/^nanoinit ([0-9]+) (.+)/i', $msg, $info)) {
                	return $this -> nanoi($info[1], $info[2], $info[3]);
                 } else {
                 $inside = $this -> bot -> core("tools") -> chatcmd("help aimedshot", "Aimedshot")." ";
                 $inside .= $this -> bot -> core("tools") -> chatcmd("help burst", "Burst")." ";
                 $inside .= $this -> bot -> core("tools") -> chatcmd("help brawl", "Brawl")." ";
                 $inside .= $this -> bot -> core("tools") -> chatcmd("help dimach", "Dimach")." ";
                 $inside .= $this -> bot -> core("tools") -> chatcmd("help fullauto", "Full")." ";
                 $inside .= $this -> bot -> core("tools") -> chatcmd("help fastattack", "Fast")." ";
                 $inside .= $this -> bot -> core("tools") -> chatcmd("help flingshot", "Fling")." ";
                 $inside .= $this -> bot -> core("tools") -> chatcmd("help mafist", "Mafist")." ";
                 $inside .= $this -> bot -> core("tools") -> chatcmd("help nanoinit", "Nano")." ";
                 return "Check ". $this -> bot -> core("tools") -> make_blob("HELP", $inside) ." for this command ...";
                 }
	}

        function interpolate($x1, $x2, $y1, $y2, $x) {
			$result = ($y2 - $y1)/($x2 - $x1) * ($x - $x1) + $y1;
			$result = round($result,0);
			return $result;
	}
	
	function timestamp($sec_value) {
			$stamp = "";
			if ($sec_value > 3599) {
				$hours = floor($sec_value/3600);
				$sec_value = $sec_value - $hours*3600;
				$stamp .= "<font color=#FFF000>".$hours."</font> hour(s) ";
			}
			if ($sec_value > 59) {
				$minutes = floor($sec_value/60);
				$sec_value = $sec_value - $minutes*60;
				$stamp .= "<font color=#FFF000>".$minutes."</font> minute(s) ";
			}
			if (!($sec_value == 0))
				$stamp .= "<font color=#FFF000>".$sec_value."</font> second(s)";
			return $stamp;
	}

	function aimed($As, $Att, $Rech)
	{
	$Cap = floor($Att+10);
	$Ref	= ceil(($Rech*40) - ($As*3/100) + $Att - 1);
	if($Ref < $Cap) $Ref = $Cap;
	$Multi	= round($As/95,0);
	$Ascap = ceil(((4000 * $Rech) - 1100)/3);

        $inside = "Aimed Shot calculator\n\n";
        $inside .= "Your attack speed : <font color=#FFF000>".$Att."</font>\n";
	$inside .= "Your recharge speed : <font color=#FFF000>".$Rech."</font>\n";
	$inside .= "Your AS : <font color=#FFF000>".$As."</font>\n\n\n";
	$inside	.= "AS Multiplier:<font color=#FFF000> ".$Multi."x</font>\n";
	$inside	.= "AS Recharge: <font color=#FFF000>".$Ref."</font> seconds.\n";
	$inside .= "With your weap, your AS recharge will cap at <font color=#FFF000>".$Cap."</font>s.\n";
	$inside	.= "You need <font color=#FFF000>".$Ascap."</font> AS skill to cap your recharge.";

	return $this -> bot -> core("tools") -> make_blob("Aimed Shot Information", $inside);
        }

        function burst($Burst, $Att, $Rech, $Delay)
	{
	$Cap = round($Att+8,0);
	$Brech = ($Rech*20) + (($Delay/100) - ($Burst/25));
	if($Brech <=0)
		$Brech = $Cap;
	$Bcap = round((($Rech*2000)+$Delay-900)/4);

        $inside = "Burst calculator\n\n";
        $inside .= "Your attack speed : <font color=#FFF000>".$Att."</font>\n";
	$inside .= "Your recharge speed : <font color=#FFF000>".$Rech."</font>\n";
	$inside .= "Your Burst Delay : <font color=#FFF000>".$Delay."</font>\n\n\n";
	$inside .= "Your Burst : <font color=#FFF000>".$Burst."</font>\n\n\n";
	$inside	.= "Burst Recharge: <font color=#FFF000>".$Brech."</font> seconds.\n";
	$inside .= "With your weap, your Burst recharge will cap at <font color=#FFF000>".$Cap."</font>s.\n";
	$inside	.= "You need <font color=#FFF000>".$Bcap."</font> Burst skill to cap your recharge.";

	return $this -> bot -> core("tools") -> make_blob(" Burst Information", $inside);
	}

	function brawl($Bra)
	{
	$skill_list = array( 1, 1000, 1001, 2000, 2001, 3000);
	$min_list 	= array( 1,  100,  101,  170,  171,  235);
	$max_list 	= array( 2,  500,  501,  850,  851, 1145);
	$crit_list 	= array( 3,  500,  501,  600,  601,  725);
	
		if ($Bra < 1001)
			$i = 0;
		elseif ($Bra < 2001)
			$i = 2;
		elseif ($Bra < 3001)
			$i = 4;
		else {
                        $Bra = 3000;
                        $i = 4;
		}
		
		$min  = $this->interpolate($skill_list[$i], $skill_list[($i+1)], $min_list[$i], $min_list[($i+1)], $Bra);
		$max  = $this->interpolate($skill_list[$i], $skill_list[($i+1)], $max_list[$i], $max_list[($i+1)], $Bra);
		$crit = $this->interpolate($skill_list[$i], $skill_list[($i+1)], $crit_list[$i], $crit_list[($i+1)], $Bra);
		$stunC = (($Bra < 1000) ? "10%, <font color=#cccccc>will become </font>20<font color=#cccccc>% above </font>1000<font color=#cccccc> brawl skill</font>" : "20%");
		$stunD = (($Bra < 2001) ?  "3s, <font color=#cccccc>will become </font>4<font color=#cccccc>s above </font>2001<font color=#cccccc> brawl skill</font>" :  "4s");

        $inside = "Brawl calculator\n\n";
	$inside .= "Your Brawl skill : <font color=#FFF000>".$Bra."</font>\n";
	$inside	.= "Recharge : <font color=#FFF000> 15 sec (constant)</font>\n";
	$inside	.= "Damage : <font color=#FFF000>".$min."</font>-<font color=#FF0000>".$max."</font>(<font color=#00FF00>".$crit."</font>) \n";
	$inside .= "Stun chance: ".$stunC."\n";
	$inside .= "Stun duration: ".$stunD."\n";

        return $this -> bot -> core("tools") -> make_blob("Brawl Information", $inside);
	}

	function dimach($Dimach, $Prof)
	{
	$skill_list 	= array(   1, 1000, 1001, 2000, 2001, 3000);
	$gen_dmg_list	= array(   1, 2000, 2001, 2500, 2501, 2850);
	$MA_rech_list 	= array(1800, 1800, 1188,  600,  600,  300);
	$MA_dmg_list	= array(   1, 2000, 2001, 2340, 2341, 2550);
	$shad_rech_list = array( 300,  300,  300,  300,  240,  200);
	$shad_dmg_list	= array(   1,  920,  921, 1872, 1873, 2750);
	$shad_rec_list	= array(  70,   70,   70,   75,   75,   80);
	$keep_heal_list = array(   1, 3000, 3001,10500,10501,30000);     

		if ($Dimach < 1001)
			$i = 0;
		elseif ($Dimach < 2001)
			$i = 2;
		elseif ($Dimach < 3001)
			$i = 4; 
		else { 
                        $Dimach = 3000;
                        $i = 4;
		}

		switch ($Prof) {
			case "martial artist";
			case "ma";
			case "martial";
				$MA_dmg 	 = $this->interpolate($skill_list[$i], $skill_list[($i+1)], $MA_dmg_list[$i],  $MA_dmg_list[($i+1)],  $Dimach);
				$MA_dim_rech = $this->interpolate($skill_list[$i], $skill_list[($i+1)], $MA_rech_list[$i], $MA_rech_list[($i+1)], $Dimach);
				$info = "Damage: ".$MA_dmg."-".$MA_dmg."(1)\n";
				$info .= "Recharge ".$this->timestamp($MA_dim_rech)."\n";
				$class_name = "Martial Artist";
			break;
			case "keep";
			case "keeper";
				$keep_heal 	= $this->interpolate($skill_list[$i], $skill_list[($i+1)], $keep_heal_list[$i],$keep_heal_list[($i+1)], $Dimach);
				$info = "Self heal: ".$keep_heal." HP\n";
				$info .= "Recharge: 1 hour (constant)\n";
				$class_name = "Keeper";
			break;
			case "sh";
			case "shad";
			case "shade";
				$shad_dmg 	= $this->interpolate($skill_list[$i], $skill_list[($i+1)], $shad_dmg_list[$i], $shad_dmg_list[($i+1)],  $Dimach);
				$shad_rec 	= $this->interpolate($skill_list[$i], $skill_list[($i+1)], $shad_rec_list[$i], $shad_rec_list[($i+1)],  $Dimach);
				$shad_dim_rech	= $this->interpolate($skill_list[$i], $skill_list[($i+1)], $shad_rech_list[$i], $shad_rech_list[($i+1)], $Dimach);
				$info = "Damage: ".$shad_dmg."-".$shad_dmg."(1)\n";
				$info .= "HP drain: ".$shad_rec."%\n";
				$info .= "Recharge ".$this->timestamp($shad_dim_rech)."\n";
				$class_name = "Shade";
			break;
			default;
				$gen_dmg = interpolate($skill_list[$i], $skill_list[($i+1)], $gen_dmg_list[$i],  $gen_dmg_list[($i+1)], $Dimach);
				$info .= "Damage: ".$gen_dmg."-".$gen_dmg."(1)\n";
				$info .= "Recharge: 30 minutes (constant)\n";
				$class_name = "All classes besides MA, Shade and Keeper";
			break;
		}

        $inside = "Dimach calculator\n\n";
		$inside .= "Your Class : <font color=#FFF000>".$class_name."</font>\n";
		$inside .= "Your Dimach Skill : <font color=#FFF000>".$Dimach."</font>\n";
		$inside .= $info;

        return $this -> bot -> core("tools") -> make_blob("Dimach Information", $inside);
	}

	function full($FullAutoSkill, $AttTim, $RechT, $FARecharge)
	{
	$FACap = floor(10+$AttTim);
	$FA_Recharge = ceil(($RechT*40)+($FARecharge/100)-($FullAutoSkill/25) + $AttTim - 1);
	if ($FA_Recharge<$FACap) $FA_Recharge = $FACap;
	$FA_Skill_Cap = ceil((40*$RechT + $FARecharge/100 - 11))*25;
	$MaxBullets = 5 + floor($FullAutoSkill/100);
	
        $inside = "FA calculator\n\n";
	$inside	.= "Weapon Attack: <font color=#FFF000>". $AttTim ."</font>s\n";
	$inside	.= "Weapon Recharge: <font color=#FFF000>". $RechT ."</font>s\n";
	$inside	.= "Full Auto Recharge value: <font color=#FFF000>". $FARecharge ."</font>\n";
	$inside	.= "FA Skill: <font color=#FFF000>". $FullAutoSkill ."</font>\n\n";
	$inside	.= "Your Full Auto recharge:<font color=#FFF000> ". $FA_Recharge ."s</font>.\n";
	$inside .= "Your Full Auto can fire a maximum of <font color=#FFF000>".$MaxBullets." bullets</font>.\n";
	$inside .= "With your weap, your Full Auto recharge will cap at <font color=#FFF000>".$FACap."</font>s.\n";
	$inside	.= "You will need at least <font color=#FFF000>".$FA_Skill_Cap."</font> Full Auto skill to cap your recharge.\n\n";
	$inside .= "From <font color=#FFF000>0 to 10K</font> damage, the bullet damage is unchanged.\n";
	$inside .= "From <font color=#FFF000>10K to 11.5K</font> damage, each bullet damage is halved.\n";
	$inside .= "From <font color=#FFF000>11K to 15K</font> damage, each bullet damage is halved again.\n";
	$inside .= "<font color=#FFF000>15K</font> is the damage cap.\n\n";

	return $this -> bot -> core("tools") -> make_blob("Full Auto Information", $inside);
	}

	function fast($fastSkill, $AttTim)
	{
	$fasthardcap = 4+$AttTim;
	$fastrech =  round(($AttTim*16)-($fastSkill/100));
	if($fastrech < $fasthardcap)
		$fastrech = $fasthardcap;
	$fastskillcap = (($AttTim*16)-$fasthardcap)*100;

        $inside = "Fast Attack calculator\n\n";
	$inside	.= "Attack: <font color=#FFF000>". $AttTim ." </font>second(s).\n";
	$inside	.= "Fast Atk Skill: <font color=#FFF000>". $fastSkill ."</font>\n";
	$inside	.= "Fast Atk Recharge:<font color=#FFF000> ". $fastrech ."</font>s\n";
	$inside	.= "You need <font color=#FFF000>".$fastskillcap."</font> Fast Atk Skill to cap your fast attack at: <font color=#FFF000>".$fasthardcap."</font>s";
        
	return $this -> bot -> core("tools") -> make_blob("Fast Attack Information", $inside);
	}

	function fling($FlingSkill, $AttTim)
	{
	$flinghardcap = 4+$AttTim;
	$flingrech =  round(($AttTim*16)-($FlingSkill/100));
	if($flingrech < $flinghardcap)
		$flingrech = $flinghardcap;
	$flingskillcap = (($AttTim*16)-$flinghardcap)*100;

        $inside = "Fling Shot calculator\n\n";
	$inside	.= "Attack: <font color=#FFF000>". $AttTim ." </font>second(s).\n";
	$inside	.= "Fling Skill: <font color=#FFF000>". $FlingSkill ."</font>\n";
	$inside	.= "Fling Recharge:<font color=#FFF000> ". $flingrech ."</font>s\n";
	$inside	.= "You need <font color=#FFF000>".$flingskillcap."</font> Fling Skill to cap your fling at: <font color=#FFF000>".$flinghardcap."</font>s";
	
	return $this -> bot -> core("tools") -> make_blob("Fling Shot Information", $inside);
	}

	function mafist($MaSkill, $class)
	{
	$skill_list = array(1,200,1000,1001,2000,2001,3000);
	$MA_min_list = array (3,25,90,91,203,204,425);
	$MA_max_list = array (5,60,380,381,830,831,1280);
	$MA_crit_list = array(3,50,500,501,560,561,770);
	$shade_min_list = array (3,25,55,56,130,131,280);
	$shade_max_list = array (5,60,258,259,682,683,890);
	$shade_crit_list = array(3,50,250,251,275,276,300);
	$gen_min_list = array (3,25,65,66,140,141,300);
	$gen_max_list = array (5,60,280,281,715,716,990);
	$gen_crit_list = array(3,50,500,501,605,605,630);

		if ($MaSkill < 200)
			$i = 0; 
		elseif ($MaSkill < 1001)
			$i = 1; 
		elseif ($MaSkill < 2001)
			$i = 3; 
		elseif ($MaSkill < 3001)
			$i = 5; 
		else { 
			$Maskill = 3000;
			$i = 5;
		}

		switch ($class) {
			case "martial artist";
			case "ma";
			case "martial";
				$min_list = $MA_min_list; $max_list = $MA_max_list; $crit_list = $MA_crit_list; $class_name = "Martial Artist";
			break;
			case "sh";
			case "shad";
			case "shade";
				$min_list = $shade_min_list; $max_list = $shade_max_list; $crit_list = $shade_crit_list; $class_name = "Shade";
			break;
			default;
				$min_list = $gen_min_list; $max_list = $gen_max_list; $crit_list = $gen_crit_list; $class_name = "All classes besides MA and Shade";
			break;
		}

		$min = $this->interpolate($skill_list[$i], $skill_list[($i + 1)], $min_list[$i], $min_list[($i + 1)], $MaSkill);
		$max = $this->interpolate($skill_list[$i], $skill_list[($i + 1)], $max_list[$i], $max_list[($i + 1)], $MaSkill);
		$crit = $this->interpolate($skill_list[$i], $skill_list[($i + 1)], $crit_list[$i], $crit_list[($i + 1)], $MaSkill);
		$dmg = "<font color=#FFF000>".$min."</font>-<font color=#FFF000>".$max."</font>(<font color=#FFF000>".$crit."</font>)";

		$fistql = round($MaSkill/2,0);
		
		if ($fistql <= 200) {
			$speed = 1.25;
		} else if ($fistql <= 500) {
			$speed = 1.25 + (0.2*(($fistql-200)/300));
		} else if ($fistql <= 1000)	{
			$speed = 1.45 + (0.2*(($fistql-500)/500));  
		} else if ($fistql <= 1500)	{
			$speed = 1.65 + (0.2*(($fistql-1000)/500));
		}
		$speed = round($speed,2);
		
        $inside = "MA Fist calculator\n\n";
		$inside .= "Class: <font color=#FFF000>".$class_name."</font>\n";
		$inside	.= "MA Skill: <font color=#FFF000>". $MaSkill ."</font>\n";
		$inside	.= "Fist damage: ".$dmg."\n";
		$inside .= "Fist speed: <font color=#FFF000>".$speed."</font>s/<font color=#FFF000>".$speed."</font>s\n";

	return $this -> bot -> core("tools") -> make_blob("MA Fist Information", $inside);
	}

	function nanoi($RechT, $AttTim)
	{
	if( $RechT < 1200 )
		{
		$AttCalc	= round(((($AttTim - ($RechT / 200)) )/0.02) + 87.5, 0);
		}
	else 
		{
		$RechTk = $RechT - 1200;
		$AttCalc = round(((($AttTim - (1200/200) - ($RechTk / 200 / 6)))/0.02) + 87.5, 0);
		}

	$InitResult = $AttCalc;
	if( $InitResult < 0 ) $InitResult = 0;
	if( $InitResult > 100 ) $InitResult = 100;
		
	$Initatta1 = round((((100 - 87.5) * 0.02) - $AttTim) * (-200),0);
	if($Initatta1 > 1200) { $Initatta1 = round((((((100-87.5)*0.02)-$AttTim+6)*(-600)))+1200,0); }
	$Init1 = $Initatta1;
		
	$Initatta2 = round((((87.5-87.5)*0.02)-$AttTim)*(-200),0);
	if($Initatta2 > 1200) { $Initatta2 = round((((((87.5-87.5)*0.02)-$AttTim+6)*(-600)))+1200,0); }
	$Init2 = $Initatta2;
			
	$Initatta3 = round((((0-87.5)*0.02)-$AttTim)*(-200),0);
	if($Initatta3 > 1200) { $Initatta3 = round((((((0-87.5)*0.02)-$AttTim+6)*(-600)))+1200,0); }
	$Init3 = $Initatta3;
			
        $inside = "Nano Init calculator\n\n";
	$inside	.= "Attack:<font color=#FFF000> ". $AttTim ." </font>second(s).\n";
	$inside	.= "Init Skill:<font color=#FFF000> ". $RechT ."</font>\n";
	$inside	.= "Def/Agg:<font color=#FFF000> ". $InitResult ."%</font>\n";
	$inside	.= "You must set your AGG bar at<font color=#FFF000> ". $InitResult ."% (". round($InitResult*8/100,2) .") </font>to instacast your nano.\n\n";
	$inside	.= "NanoC. Init needed to instacast at Full Agg:<font color=#FFF000> ". $Init1 ." </font>inits.\n";
	$inside	.= "NanoC. Init needed to instacast at neutral (88%bar):<font color=#FFF000> ". $Init2 ." </font>inits.\n";
	$inside	.= "NanoC. Init needed to instacast at Full Def:<font color=#FFF000> ". $Init3 ." </font>inits.";

	return $this -> bot -> core("tools") -> make_blob("Nano Init Information", $inside);
	}

}
?>