<?php
/*
* Rcalc.php - Module Rcalc.
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

$rcalc = new Rcalc($bot);

class Rcalc extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
                $this -> register_command('all', 'rcalc', 'GUEST');

		$this -> help['description'] = 'Module for calculating research optimal slider position ; to include Apotheosis line, use the complex command version !';
		$this -> help['command']['rcalc <titlelevel> <research>'] = "Enter the final <titlelevel> (from 2 to 7) and the finished <research> line (from 1 to 10) you are planning";
		$this -> help['command']['rcalc <StartLevel> <FinalLevel> <FinalNumberOfR1> <FinalNumberOfR2> <FinalFinalNumberOfR3> <FinalNumberOfR4> <FinalNumberOfR5> <FinalNumberOfR6> <FinalNumberOfR7> <FinalNumberOfR8> <FinalNumberOfR9> <FinalNumberOfR10> <PresentNumberOfR1> <PresentNumberOfR2> <FinalPresentNumberOfR3> <PresentNumberOfR4> <PresentNumberOfR5> <PresentNumberOfR6> <PresentNumberOfR7> <PresentNumberOfR8> <PresentNumberOfR9> <PresentNumberOfR10>'] = "Enter <StartLevel> (from 1 to 219) and <FinalLevel> (from 2 to 220) and how much of each Research level you plan ; you must detail each level (20 numbers from 0 to 7 : first 10 are final goals, last 10 are present states) ; each Apotheosis line would approx count as 2x R7 OR 3x R6 ; whole Apotheosis line worth about 1x R10 (for 9 first) plus 2x R7 OR 3x R6 (for very last)";
	}

        function command_handler($name, $msg, $origin)
        {
		if (preg_match("/^rcalc ([0-9]+) ([0-9]+)$/i", $msg, $info))
		{
			return $this -> rcalc($info);
		}
		elseif (preg_match("/^rcalc ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+) ([0-9]+)$/i", $msg, $info))
		{
			return $this -> rdetail($info);
		}
		else
		{
			$this -> bot -> send_help($name);
		}
        }

        function rcalc($info) {
$TL = $info[1]; $RL = $info[2];
$XP1 = 1; $XP2 = 1; $ALL = 1;
$TL1 = 70550; $TL2 = 1378900; $TL3 = 27405300; $TL4 = 257191100;
$TL5 = 1090699900; $TL6 = 1114048200; $TL7 = 14505601000;
$RL1 = 350000; $RL2 = 3150000; $RL3 = 11200000; $RL4 = 32900000;
$RL5 = 89250000; $RL6 = 224000000; $RL7 = 378000000;
$RL8 = 448000000; $RL9 = 5180000000; $RL10 = 6300000000;
switch($TL) {
case 7: $XP1 = $TL1+$TL2+$TL3+$TL4+$TL5+$TL6+$TL7; break;
case 6: $XP1= $TL1+$TL2+$TL3+$TL4+$TL5+$TL6; break;
case 5: $XP1= $TL1+$TL2+$TL3+$TL4+$TL5; break;
case 4: $XP1= $TL1+$TL2+$TL3+$TL4; break;
case 3: $XP1= $TL1+$TL2+$TL3; break;
default: $XP1= $TL1+$TL2; break; }
switch($RL) {
case 10: $XP2 = $RL1+$RL2+$RL3+$RL4+$RL5+$RL6+$RL7+$RL8+$RL9+$RL10; break;
case 9: $XP2 = $RL1+$RL2+$RL3+$RL4+$RL5+$RL6+$RL7+$RL8+$RL9; break;
case 8: $XP2 = $RL1+$RL2+$RL3+$RL4+$RL5+$RL6+$RL7+$RL8; break;
case 7: $XP2 = $RL1+$RL2+$RL3+$RL4+$RL5+$RL6+$RL7; break;
case 6: $XP2 = $RL1+$RL2+$RL3+$RL4+$RL5+$RL6; break;
case 5: $XP2= $RL1+$RL2+$RL3+$RL4+$RL5; break;
case 4: $XP2= $RL1+$RL2+$RL3+$RL4; break;
case 3: $XP2= $RL1+$RL2+$RL3; break;
case 2: $XP2= $RL1+$RL2; break;
default: $XP2= $RL1; break; }
$ALL = $XP1+$XP2;
$S = ($XP2/$ALL)*100;
return "Optimal Slider : ".ceil($S)."%";
        }

        function rdetail($info) {
$SL = $info[1]; $FL = $info[2]; $XP1 = 0; $ALL = 0; $XP2 = 0;
$R1 = $info[3]*50000; $R2 = $info[4]*450000; $R3 = $info[5]*1600000; $R4 = $info[6]*4700000; $R5 = $info[7]*12750000;
$R6 = $info[8]*32000000; $R7 = $info[9]*54000000; $R8 = $info[10]*64000000; $R9 = $info[11]*740000000; $R10 = $info[12]*900000000;
$XP2 = $R1+$R2+$R3+$R4+$R5+$R6+$R7+$R8+$R9+$R10;
$A1 = $info[13]*50000; $A2 = $info[14]*450000; $A3 = $info[15]*1600000; $A4 = $info[16]*4700000; $A5 = $info[17]*12750000;
$A6 = $info[18]*32000000; $A7 = $info[19]*54000000; $A8 = $info[20]*64000000; $A9 = $info[21]*740000000; $A10 = $info[22]*900000000;
$XP3 = $A1+$A2+$A3+$A4+$A5+$A6+$A7+$A8+$A9+$A10;
$level[1] = 0;
$level[2] = 1450;
$level[3] = 2600;
$level[4] = 3100;
$level[5] = 4000;
$level[6] = 4500;
$level[7] = 5000;
$level[8] = 5500;
$level[9] = 6000;
$level[10] = 6500;
$level[11] = 7000;
$level[12] = 7700;
$level[13] = 8300;
$level[14] = 8900;
$level[15] = 9600;
$level[16] = 10400;
$level[17] = 11000;
$level[18] = 11900;
$level[19] = 12700;
$level[20] = 13700;
$level[21] = 15400;
$level[22] = 16400;
$level[23] = 17600;
$level[24] = 18800;
$level[25] = 20100;
$level[26] = 21500;
$level[27] = 22900;
$level[28] = 24500;
$level[29] = 26100;
$level[30] = 27800;
$level[31] = 30900;
$level[32] = 33000;
$level[33] = 35100;
$level[34] = 37400;
$level[35] = 39900;
$level[36] = 42400;
$level[37] = 45100;
$level[38] = 47900;
$level[39] = 50900;
$level[40] = 54000;
$level[41] = 57400;
$level[42] = 60900;
$level[43] = 64500;
$level[44] = 68400;
$level[45] = 76400;
$level[46] = 81000;
$level[47] = 85900;
$level[48] = 91000;
$level[49] = 96400;
$level[50] = 101900;
$level[51] = 108000;
$level[52] = 114300;
$level[53] = 120800;
$level[54] = 127700;
$level[55] = 135000;
$level[56] = 142600;
$level[57] = 150700;
$level[58] = 161900;
$level[59] = 167800;
$level[60] = 177100;
$level[61] = 203500;
$level[62] = 214700;
$level[63] = 226700;
$level[64] = 239100;
$level[65] = 251900;
$level[66] = 265700;
$level[67] = 280000;
$level[68] = 294800;
$level[69] = 310600;
$level[70] = 327000;
$level[71] = 344400;
$level[72] = 362300;
$level[73] = 381100;
$level[74] = 401000;
$level[75] = 421600;
$level[76] = 443300;
$level[77] = 508100;
$level[78] = 534200;
$level[79] = 561600;
$level[80] = 590200;
$level[81] = 620000;
$level[82] = 651000;
$level[83] = 683700;
$level[84] = 717900;
$level[85] = 753500;
$level[86] = 790800;
$level[87] = 829400;
$level[88] = 870000;
$level[89] = 912600;
$level[90] = 956800;
$level[91] = 1003000;
$level[92] = 1051300;
$level[93] = 1101500;
$level[94] = 1153900;
$level[95] = 1208800;
$level[96] = 1266000;
$level[97] = 1325500;
$level[98] = 1387700;
$level[99] = 1452300;
$level[100] = 1519900;
$level[101] = 1590300;
$level[102] = 1663500;
$level[103] = 1739900;
$level[104] = 1819600;
$level[105] = 1902200;
$level[106] = 1988900;
$level[107] = 2078600;
$level[108] = 2172100;
$level[109] = 2269800;
$level[110] = 2371100;
$level[111] = 2476600;
$level[112] = 2586600;
$level[113] = 2701000;
$level[114] = 2819800;
$level[115] = 2943600;
$level[116] = 3072400;
$level[117] = 3205800;
$level[118] = 3345200;
$level[119] = 3489700;
$level[120] = 3640200;
$level[121] = 3796500;
$level[122] = 3958900;
$level[123] = 4128000;
$level[124] = 4303400;
$level[125] = 4485700;
$level[126] = 4674800;
$level[127] = 4871700;
$level[128] = 5075700;
$level[129] = 5288100;
$level[130] = 5508200;
$level[131] = 5736800;
$level[132] = 5974600;
$level[133] = 6220700;
$level[134] = 6474500;
$level[135] = 6742200;
$level[136] = 7017500;
$level[137] = 7303700;
$level[138] = 7600100;
$level[139] = 7907600;
$level[140] = 8227000;
$level[141] = 8557700;
$level[142] = 8901000;
$level[143] = 9256800;
$level[144] = 9625800;
$level[145] = 10008600;
$level[146] = 10405300;
$level[147] = 10816600;
$level[148] = 11242500;
$level[149] = 11684300;
$level[150] = 12141900;
$level[151] = 12616200;
$level[152] = 13107200;
$level[153] = 13616100;
$level[154] = 14143600;
$level[155] = 14689700;
$level[156] = 15255300;
$level[157] = 15841000;
$level[158] = 16447900;
$level[159] = 17075800;
$level[160] = 17725900;
$level[161] = 18399400;
$level[162] = 19096100;
$level[163] = 19817500;
$level[164] = 20564100;
$level[165] = 21336600;
$level[166] = 22136100;
$level[167] = 22963600;
$level[168] = 23819700;
$level[169] = 24705200;
$level[170] = 25621100;
$level[171] = 26569000;
$level[172] = 27548800;
$level[173] = 28562900;
$level[174] = 29611100;
$level[175] = 30695300;
$level[176] = 31816300;
$level[177] = 32975100;
$level[178] = 34173500;
$level[179] = 35412500;
$level[180] = 36692500;
$level[181] = 38016500;
$level[182] = 39484400;
$level[183] = 40797700;
$level[184] = 42258500;
$level[185] = 43768300;
$level[186] = 45328100;
$level[187] = 46939900;
$level[188] = 48604900;
$level[189] = 50324600;
$level[190] = 52101200;
$level[191] = 53936300;
$level[192] = 55831600;
$level[193] = 57788700;
$level[194] = 59810000;
$level[195] = 61897000;
$level[196] = 64052200;
$level[197] = 66277200;
$level[198] = 68574400;
$level[199] = 70945700;
$level[200] = 73393900;
$level[201] = 80000000;
$level[202] = 96000000;
$level[203] = 115200000;
$level[204] = 138240000;
$level[205] = 165888000;
$level[206] = 199066000;
$level[207] = 238879000;
$level[208] = 286654000;
$level[209] = 343985000;
$level[210] = 412782000;
$level[211] = 495339000;
$level[212] = 594407000;
$level[213] = 713288000;
$level[214] = 855946000;
$level[215] = 1027135000;
$level[216] = 1232562000;
$level[217] = 1479074000;
$level[218] = 1774889000;
$level[219] = 2129867000;
$level[220] = 2555840000;
for ($i = $SL ; $i <= $FL ; $i++) {
    $XP1 += $level[$i]; }
$ALL = $XP1+($XP2-$XP3);
$S = (($XP2-$XP3)/$ALL)*100;
return "From ".$SL." to ".$FL." = ".$XP1." XP ; research left = ".($XP2-$XP3)." XP ; so optimal slider = ".ceil($S)."%";
        }

}
?>