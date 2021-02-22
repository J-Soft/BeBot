<?php
/*
* Ladder.php - Module ladder
* Adapted from work by Lucier, Tyrence & Imoutochan
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

$ladder = new Ladder($bot);

class Ladder extends BaseActiveModule
{
	function __construct (&$bot)
	{
		// Initialize the base module
		parent::__construct($bot, get_class($this));

		// Register command
		$this->register_command('all', 'ladder', 'GUEST');
		$this -> register_alias("ladder", "ladders");

		// Add description/help
		$this -> help['description'] = "Calculate maximum implant(s) you can ladder once buffed & equiped to twink (but without implants).";
		$this -> help['command']['ladder <skill> <value>'] = "Calculates your laddering over given value (number) of skill (treatment or ability).";
	}

	function command_handler($source, $msg, $origin)
	{
		$this->error->reset();
		
		$vars = explode(' ', strtolower($msg));
		$command = $vars[0];

		switch($command)
		{
			case 'ladder':
				if(count($vars)==3&&isset($vars[2])&&is_numeric($vars[2])&&$vars[2]>0&&isset($vars[1])&&$vars[1]!="") {
					return $this -> send_ladder($source, $origin, strtolower($vars[1]), $vars[2]);
				} else {
					$this->bot->send_help($source);
				}
				break;		
			default:				
				$this -> error -> set("Broken plugin, received unhandled command: $command");
				return($this->error->message());
		}
	}
	
	function send_ladder($name, $origin, $skill, $val)
	{		
		switch($skill) {
			case 'treatment':
			case 'treat':
				$skill = 'treatment';
				$req = "abil";
				break;
			case 'ability':
			case 'abil':
			case 'agility':
			case 'agil':
			case 'agi':
			case 'stamina':
			case 'stam':
			case 'sta':
			case 'strength':
			case 'streng':
			case 'str':
			case 'intelligence':
			case 'intelli':
			case 'intel':
			case 'psychic':
			case 'psyc':
			case 'psy':
			case 'sense':
			case 'sens':
			case 'sen':
				$skill = 'ability';
				$req = "treat";
				break;
			default:
				$skill = 'unknown';
				$req = "";
				break;
		}
		
		if ($skill=='unknown') return $this->bot->send_output($name, "Provided skill isn't supported!", $origin);
		if ($skill=='ability'&&$val<6) return $this->bot->send_output($name, "Ability must be at least 6!", $origin);
		if ($skill=='treatment'&&$val<11) return $this->bot->send_output($name, "Treatment must be at least 11!", $origin);
		
		$shi = 0;
		$bri = 0;
		$fad = 0;
		$all = $val;
		$tmp = 0;
		$buf = 0;
		$add = true;
		$cnt = 0;
		$blob = "From ##seablue##".$all."##end## ".$skill." ...</b>";		
		
		while ($add) {
			$add = false;
			$blob .= "\n\n";
			// shiny
			$tmp = $all-$shi;
			if($skill=='ability') $buf = $this->findmax($tmp,5000);
			if($skill=='treatment') $buf = $this->findmax(5000,$tmp);
			if($buf[1][0]>$shi) {
				$add = true;
				if($shi>0) {
					$blob .= " ##red##Remove##end## shiny | ";
				}
				$all = $all-$shi+$buf[1][0];
				$shi = $buf[1][0];
				$blob .= " ##green##Add##end## QL ".$buf[1][1]."-".$buf[0]." shiny (".$buf[4]." ".$req.") | ";
			}
			// bright
			$tmp = $all-$bri;
			if($skill=='ability') $buf = $this->findmax($tmp,5000);
			if($skill=='treatment') $buf = $this->findmax(5000,$tmp);
			if($buf[2][0]>$bri) {
				$add = true;
				if($bri>0) {
					$blob .= " ##red##Remove##end## bright | ";
				}
				$all = $all-$bri+$buf[2][0];
				$bri = $buf[2][0];
				$blob .= " ##green##Add##end## QL ".$buf[2][1]."-".$buf[0]." bright (".$buf[4]." ".$req.") | ";
			}
			// faded
			$tmp = $all-$fad;
			if($skill=='ability') $buf = $this->findmax($tmp,5000);
			if($skill=='treatment') $buf = $this->findmax(5000,$tmp);
			if($buf[3][0]>$fad) {
				$add = true;
				if($fad>0) {
					$blob .= " ##red##Remove##end## faded | ";
				}
				$all = $all-$fad+$buf[3][0];
				$fad = $buf[3][0];
				$blob .= " ##green##Add##end## QL ".$buf[3][1]."-".$buf[0]." faded (".$buf[4]." ".$req.") | ";
			}
			if($add) $cnt++;
		}
		
		$blob .= "... to end up with ##seablue##".$all."##end## ".$skill;
		return $this->bot->send_output($name, $cnt." step(s) laddering: ".$this->bot->core("tools")->make_blob("click to view", $blob), $origin);
	}
	
	function findmax($ability, $treatment)
	{
		// QL Treq Sreq Sshi Sbri Sfaf Tshi Tbri Tfad
		$impinfos[] = array(1, 11, 6, 5, 3, 2, 6, 3, 2);
		$impinfos[] = array(2, 16, 8, 5, 3, 2, 6, 3, 2);
		$impinfos[] = array(3, 20, 10, 6, 3, 2, 7, 4, 2);
		$impinfos[] = array(4, 25, 12, 6, 3, 2, 7, 4, 3);
		$impinfos[] = array(5, 30, 14, 6, 4, 2, 8, 4, 3);
		$impinfos[] = array(6, 35, 16, 6, 4, 3, 8, 5, 3);
		$impinfos[] = array(7, 39, 18, 7, 4, 3, 9, 5, 3);
		$impinfos[] = array(8, 44, 20, 7, 4, 3, 9, 5, 3);
		$impinfos[] = array(9, 49, 22, 7, 4, 3, 10, 5, 4);
		$impinfos[] = array(10, 54, 24, 7, 4, 3, 10, 6, 4);
		$impinfos[] = array(11, 58, 26, 8, 5, 3, 11, 6, 4);
		$impinfos[] = array(12, 63, 28, 8, 5, 3, 11, 6, 4);
		$impinfos[] = array(13, 68, 30, 8, 5, 3, 12, 7, 4);
		$impinfos[] = array(14, 72, 32, 8, 5, 3, 12, 7, 5);
		$impinfos[] = array(15, 77, 34, 9, 5, 3, 13, 7, 5);
		$impinfos[] = array(16, 82, 36, 9, 5, 4, 13, 8, 5);
		$impinfos[] = array(17, 87, 38, 9, 5, 4, 14, 8, 5);
		$impinfos[] = array(18, 91, 40, 9, 6, 4, 14, 8, 5);
		$impinfos[] = array(19, 96, 42, 10, 6, 4, 15, 8, 6);
		$impinfos[] = array(20, 101, 44, 10, 6, 4, 15, 9, 6);
		$impinfos[] = array(21, 105, 46, 10, 6, 4, 16, 9, 6);
		$impinfos[] = array(22, 110, 48, 10, 6, 4, 16, 9, 6);
		$impinfos[] = array(23, 115, 50, 11, 6, 4, 17, 10, 6);
		$impinfos[] = array(24, 120, 52, 11, 6, 4, 17, 10, 7);
		$impinfos[] = array(25, 124, 54, 11, 7, 4, 18, 10, 7);
		$impinfos[] = array(26, 129, 56, 11, 7, 5, 18, 11, 7);
		$impinfos[] = array(27, 134, 58, 12, 7, 5, 19, 11, 7);
		$impinfos[] = array(28, 139, 60, 12, 7, 5, 19, 11, 7);
		$impinfos[] = array(29, 143, 62, 12, 7, 5, 20, 11, 8);
		$impinfos[] = array(30, 148, 64, 12, 7, 5, 20, 12, 8);
		$impinfos[] = array(31, 153, 66, 13, 8, 5, 21, 12, 8);
		$impinfos[] = array(32, 157, 68, 13, 8, 5, 21, 12, 8);
		$impinfos[] = array(33, 162, 70, 13, 8, 5, 22, 13, 8);
		$impinfos[] = array(34, 167, 72, 13, 8, 5, 22, 13, 9);
		$impinfos[] = array(35, 172, 74, 14, 8, 5, 23, 13, 9);
		$impinfos[] = array(36, 176, 76, 14, 8, 6, 23, 14, 9);
		$impinfos[] = array(37, 181, 78, 14, 8, 6, 24, 14, 9);
		$impinfos[] = array(38, 186, 80, 14, 9, 6, 24, 14, 9);
		$impinfos[] = array(39, 190, 82, 15, 9, 6, 25, 14, 10);
		$impinfos[] = array(40, 195, 84, 15, 9, 6, 25, 15, 10);
		$impinfos[] = array(41, 200, 86, 15, 9, 6, 26, 15, 10);
		$impinfos[] = array(42, 205, 88, 15, 9, 6, 26, 15, 10);
		$impinfos[] = array(43, 209, 90, 16, 9, 6, 27, 16, 10);
		$impinfos[] = array(44, 214, 92, 16, 9, 6, 27, 16, 11);
		$impinfos[] = array(45, 219, 94, 16, 10, 6, 28, 16, 11);
		$impinfos[] = array(46, 224, 96, 16, 10, 7, 28, 17, 11);
		$impinfos[] = array(47, 228, 98, 17, 10, 7, 29, 17, 11);
		$impinfos[] = array(48, 233, 100, 17, 10, 7, 29, 17, 11);
		$impinfos[] = array(49, 238, 102, 17, 10, 7, 30, 17, 12);
		$impinfos[] = array(50, 242, 104, 17, 10, 7, 30, 18, 12);
		$impinfos[] = array(51, 247, 106, 18, 11, 7, 31, 18, 12);
		$impinfos[] = array(52, 252, 108, 18, 11, 7, 31, 18, 12);
		$impinfos[] = array(53, 257, 110, 18, 11, 7, 32, 19, 12);
		$impinfos[] = array(54, 261, 112, 18, 11, 7, 32, 19, 13);
		$impinfos[] = array(55, 266, 114, 19, 11, 7, 33, 19, 13);
		$impinfos[] = array(56, 271, 116, 19, 11, 8, 33, 20, 13);
		$impinfos[] = array(57, 276, 118, 19, 11, 8, 34, 20, 13);
		$impinfos[] = array(58, 280, 120, 19, 12, 8, 34, 20, 13);
		$impinfos[] = array(59, 285, 122, 20, 12, 8, 35, 20, 14);
		$impinfos[] = array(60, 290, 124, 20, 12, 8, 35, 21, 14);
		$impinfos[] = array(61, 294, 126, 20, 12, 8, 36, 21, 14);
		$impinfos[] = array(62, 299, 128, 20, 12, 8, 36, 21, 14);
		$impinfos[] = array(63, 304, 130, 21, 12, 8, 37, 22, 14);
		$impinfos[] = array(64, 309, 132, 21, 12, 8, 37, 22, 15);
		$impinfos[] = array(65, 313, 134, 21, 13, 8, 38, 22, 15);
		$impinfos[] = array(66, 318, 136, 21, 13, 9, 38, 23, 15);
		$impinfos[] = array(67, 323, 138, 22, 13, 9, 39, 23, 15);
		$impinfos[] = array(68, 327, 140, 22, 13, 9, 39, 23, 15);
		$impinfos[] = array(69, 332, 142, 22, 13, 9, 40, 24, 16);
		$impinfos[] = array(70, 337, 144, 22, 13, 9, 40, 24, 16);
		$impinfos[] = array(71, 342, 146, 23, 14, 9, 41, 24, 16);
		$impinfos[] = array(72, 346, 148, 23, 14, 9, 41, 24, 16);
		$impinfos[] = array(73, 351, 150, 23, 14, 9, 42, 25, 16);
		$impinfos[] = array(74, 356, 152, 23, 14, 9, 42, 25, 17);
		$impinfos[] = array(75, 361, 154, 24, 14, 9, 43, 25, 17);
		$impinfos[] = array(76, 365, 156, 24, 14, 10, 43, 26, 17);
		$impinfos[] = array(77, 370, 158, 24, 14, 10, 44, 26, 17);
		$impinfos[] = array(78, 375, 160, 24, 15, 10, 44, 26, 17);
		$impinfos[] = array(79, 379, 162, 25, 15, 10, 45, 27, 18);
		$impinfos[] = array(80, 384, 164, 25, 15, 10, 45, 27, 18);
		$impinfos[] = array(81, 389, 166, 25, 15, 10, 46, 27, 18);
		$impinfos[] = array(82, 394, 168, 25, 15, 10, 46, 27, 18);
		$impinfos[] = array(83, 398, 170, 26, 15, 10, 47, 28, 18);
		$impinfos[] = array(84, 403, 172, 26, 16, 10, 47, 28, 19);
		$impinfos[] = array(85, 408, 174, 26, 16, 10, 48, 28, 19);
		$impinfos[] = array(86, 413, 176, 26, 16, 11, 48, 29, 19);
		$impinfos[] = array(87, 417, 178, 27, 16, 11, 49, 29, 19);
		$impinfos[] = array(88, 422, 180, 27, 16, 11, 49, 29, 19);
		$impinfos[] = array(89, 427, 182, 27, 16, 11, 50, 30, 20);
		$impinfos[] = array(90, 431, 184, 27, 16, 11, 50, 30, 20);
		$impinfos[] = array(91, 436, 186, 28, 17, 11, 51, 30, 20);
		$impinfos[] = array(92, 441, 188, 28, 17, 11, 51, 30, 20);
		$impinfos[] = array(93, 446, 190, 28, 17, 11, 52, 31, 20);
		$impinfos[] = array(94, 450, 192, 28, 17, 11, 52, 31, 21);
		$impinfos[] = array(95, 455, 194, 29, 17, 11, 53, 31, 21);
		$impinfos[] = array(96, 460, 196, 29, 17, 12, 53, 32, 21);
		$impinfos[] = array(97, 464, 198, 29, 17, 12, 54, 32, 21);
		$impinfos[] = array(98, 469, 200, 29, 18, 12, 54, 32, 21);
		$impinfos[] = array(99, 474, 202, 30, 18, 12, 55, 33, 22);
		$impinfos[] = array(100, 479, 204, 30, 18, 12, 55, 33, 22);
		$impinfos[] = array(101, 483, 206, 30, 18, 12, 56, 33, 22);
		$impinfos[] = array(102, 488, 208, 30, 18, 12, 56, 33, 22);
		$impinfos[] = array(103, 493, 210, 31, 18, 12, 57, 34, 23);
		$impinfos[] = array(104, 498, 212, 31, 19, 12, 57, 34, 23);
		$impinfos[] = array(105, 502, 214, 31, 19, 12, 58, 34, 23);
		$impinfos[] = array(106, 507, 216, 31, 19, 13, 58, 35, 23);
		$impinfos[] = array(107, 512, 218, 32, 19, 13, 59, 35, 23);
		$impinfos[] = array(108, 516, 220, 32, 19, 13, 59, 35, 24);
		$impinfos[] = array(109, 521, 222, 32, 19, 13, 60, 36, 24);
		$impinfos[] = array(110, 526, 224, 32, 19, 13, 60, 36, 24);
		$impinfos[] = array(111, 531, 226, 33, 20, 13, 61, 36, 24);
		$impinfos[] = array(112, 535, 228, 33, 20, 13, 61, 36, 24);
		$impinfos[] = array(113, 540, 230, 33, 20, 13, 62, 37, 25);
		$impinfos[] = array(114, 545, 232, 33, 20, 13, 62, 37, 25);
		$impinfos[] = array(115, 549, 234, 34, 20, 13, 63, 37, 25);
		$impinfos[] = array(116, 554, 236, 34, 20, 14, 63, 38, 25);
		$impinfos[] = array(117, 559, 238, 34, 20, 14, 64, 38, 25);
		$impinfos[] = array(118, 564, 240, 34, 21, 14, 64, 38, 26);
		$impinfos[] = array(119, 568, 242, 35, 21, 14, 65, 39, 26);
		$impinfos[] = array(120, 573, 244, 35, 21, 14, 65, 39, 26);
		$impinfos[] = array(121, 578, 246, 35, 21, 14, 66, 39, 26);
		$impinfos[] = array(122, 583, 248, 35, 21, 14, 66, 39, 26);
		$impinfos[] = array(123, 587, 250, 36, 21, 14, 67, 40, 27);
		$impinfos[] = array(124, 592, 252, 36, 22, 14, 67, 40, 27);
		$impinfos[] = array(125, 597, 254, 36, 22, 14, 68, 40, 27);
		$impinfos[] = array(126, 601, 256, 36, 22, 15, 68, 41, 27);
		$impinfos[] = array(127, 606, 258, 37, 22, 15, 69, 41, 27);
		$impinfos[] = array(128, 611, 260, 37, 22, 15, 69, 41, 28);
		$impinfos[] = array(129, 616, 262, 37, 22, 15, 70, 42, 28);
		$impinfos[] = array(130, 620, 264, 37, 22, 15, 70, 42, 28);
		$impinfos[] = array(131, 625, 266, 38, 23, 15, 71, 42, 28);
		$impinfos[] = array(132, 630, 268, 38, 23, 15, 71, 42, 28);
		$impinfos[] = array(133, 635, 270, 38, 23, 15, 72, 43, 29);
		$impinfos[] = array(134, 639, 272, 38, 23, 15, 72, 43, 29);
		$impinfos[] = array(135, 644, 274, 39, 23, 15, 73, 43, 29);
		$impinfos[] = array(136, 649, 276, 39, 23, 16, 73, 44, 29);
		$impinfos[] = array(137, 653, 278, 39, 24, 16, 74, 44, 29);
		$impinfos[] = array(138, 658, 280, 39, 24, 16, 74, 44, 30);
		$impinfos[] = array(139, 663, 282, 40, 24, 16, 75, 45, 30);
		$impinfos[] = array(140, 668, 284, 40, 24, 16, 75, 45, 30);
		$impinfos[] = array(141, 672, 286, 40, 24, 16, 76, 45, 30);
		$impinfos[] = array(142, 677, 288, 40, 24, 16, 76, 46, 30);
		$impinfos[] = array(143, 682, 290, 41, 24, 16, 77, 46, 31);
		$impinfos[] = array(144, 686, 292, 41, 25, 16, 77, 46, 31);
		$impinfos[] = array(145, 691, 294, 41, 25, 16, 78, 46, 31);
		$impinfos[] = array(146, 696, 296, 41, 25, 17, 78, 47, 31);
		$impinfos[] = array(147, 701, 298, 42, 25, 17, 79, 47, 31);
		$impinfos[] = array(148, 705, 300, 42, 25, 17, 79, 47, 32);
		$impinfos[] = array(149, 710, 302, 42, 25, 17, 80, 48, 32);
		$impinfos[] = array(150, 715, 304, 42, 25, 17, 80, 48, 32);
		$impinfos[] = array(151, 720, 306, 43, 26, 17, 81, 48, 32);
		$impinfos[] = array(152, 724, 308, 43, 26, 17, 81, 49, 32);
		$impinfos[] = array(153, 729, 310, 43, 26, 17, 82, 49, 33);
		$impinfos[] = array(154, 734, 312, 43, 26, 17, 82, 49, 33);
		$impinfos[] = array(155, 738, 314, 44, 26, 17, 83, 49, 33);
		$impinfos[] = array(156, 743, 316, 44, 26, 18, 83, 50, 33);
		$impinfos[] = array(157, 748, 318, 44, 27, 18, 84, 50, 33);
		$impinfos[] = array(158, 753, 320, 44, 27, 18, 84, 50, 34);
		$impinfos[] = array(159, 757, 322, 45, 27, 18, 85, 51, 34);
		$impinfos[] = array(160, 762, 324, 45, 27, 18, 85, 51, 34);
		$impinfos[] = array(161, 767, 326, 45, 27, 18, 86, 51, 34);
		$impinfos[] = array(162, 772, 328, 45, 27, 18, 86, 52, 34);
		$impinfos[] = array(163, 776, 330, 46, 27, 18, 87, 52, 35);
		$impinfos[] = array(164, 781, 332, 46, 28, 18, 87, 52, 35);
		$impinfos[] = array(165, 786, 334, 46, 28, 18, 88, 52, 35);
		$impinfos[] = array(166, 790, 336, 46, 28, 19, 88, 53, 35);
		$impinfos[] = array(167, 795, 338, 47, 28, 19, 89, 53, 35);
		$impinfos[] = array(168, 800, 340, 47, 28, 19, 89, 53, 36);
		$impinfos[] = array(169, 805, 342, 47, 28, 19, 90, 54, 36);
		$impinfos[] = array(170, 809, 344, 47, 28, 19, 90, 54, 36);
		$impinfos[] = array(171, 814, 346, 48, 29, 19, 91, 54, 36);
		$impinfos[] = array(172, 819, 348, 48, 29, 19, 91, 55, 36);
		$impinfos[] = array(173, 823, 350, 48, 29, 19, 92, 55, 37);
		$impinfos[] = array(174, 828, 352, 48, 29, 19, 92, 55, 37);
		$impinfos[] = array(175, 833, 354, 49, 29, 19, 93, 55, 37);
		$impinfos[] = array(176, 838, 356, 49, 29, 20, 93, 56, 37);
		$impinfos[] = array(177, 842, 358, 49, 30, 20, 94, 56, 37);
		$impinfos[] = array(178, 847, 360, 49, 30, 20, 94, 56, 38);
		$impinfos[] = array(179, 852, 362, 50, 30, 20, 95, 57, 38);
		$impinfos[] = array(180, 857, 364, 50, 30, 20, 95, 57, 38);
		$impinfos[] = array(181, 861, 366, 50, 30, 20, 96, 57, 38);
		$impinfos[] = array(182, 866, 368, 50, 30, 20, 96, 58, 38);
		$impinfos[] = array(183, 871, 370, 51, 30, 20, 97, 58, 39);
		$impinfos[] = array(184, 875, 372, 51, 31, 20, 97, 58, 39);
		$impinfos[] = array(185, 880, 374, 51, 31, 20, 98, 58, 39);
		$impinfos[] = array(186, 885, 376, 51, 31, 21, 98, 59, 39);
		$impinfos[] = array(187, 890, 378, 52, 31, 21, 99, 59, 39);
		$impinfos[] = array(188, 894, 380, 52, 31, 21, 99, 59, 40);
		$impinfos[] = array(189, 899, 382, 52, 31, 21, 100, 60, 40);
		$impinfos[] = array(190, 904, 384, 52, 31, 21, 100, 60, 40);
		$impinfos[] = array(191, 908, 386, 53, 32, 21, 101, 60, 40);
		$impinfos[] = array(192, 913, 388, 53, 32, 21, 101, 61, 40);
		$impinfos[] = array(193, 918, 390, 53, 32, 21, 102, 61, 41);
		$impinfos[] = array(194, 923, 392, 53, 32, 21, 102, 61, 41);
		$impinfos[] = array(195, 927, 394, 54, 32, 21, 103, 61, 41);
		$impinfos[] = array(196, 932, 396, 54, 32, 22, 103, 62, 41);
		$impinfos[] = array(197, 937, 398, 54, 33, 22, 104, 62, 41);
		$impinfos[] = array(198, 942, 400, 54, 33, 22, 104, 62, 42);
		$impinfos[] = array(199, 946, 402, 55, 33, 22, 105, 63, 42);
		$impinfos[] = array(200, 951, 404, 55, 33, 22, 105, 63, 42);
		$impinfos[] = array(201, 1001, 426, 55, 33, 22, 106, 63, 42);
		$impinfos[] = array(202, 1012, 433, 55, 33, 22, 106, 63, 42);
		$impinfos[] = array(203, 1022, 440, 55, 33, 22, 107, 63, 42);
		$impinfos[] = array(204, 1033, 446, 56, 33, 22, 107, 64, 42);
		$impinfos[] = array(205, 1043, 453, 56, 33, 22, 107, 64, 43);
		$impinfos[] = array(206, 1054, 460, 56, 34, 22, 108, 64, 43);
		$impinfos[] = array(207, 1065, 467, 56, 34, 22, 108, 64, 43);
		$impinfos[] = array(208, 1075, 473, 56, 34, 22, 108, 65, 43);
		$impinfos[] = array(209, 1086, 480, 56, 34, 23, 109, 65, 43);
		$impinfos[] = array(210, 1096, 487, 57, 34, 23, 109, 65, 43);
		$impinfos[] = array(211, 1107, 494, 57, 34, 23, 110, 65, 44);
		$impinfos[] = array(212, 1118, 500, 57, 34, 23, 110, 65, 44);
		$impinfos[] = array(213, 1128, 507, 57, 34, 23, 110, 66, 44);
		$impinfos[] = array(214, 1139, 514, 57, 34, 23, 111, 66, 44);
		$impinfos[] = array(215, 1149, 521, 58, 35, 23, 111, 66, 44);
		$impinfos[] = array(216, 1160, 527, 58, 35, 23, 111, 66, 44);
		$impinfos[] = array(217, 1171, 534, 58, 35, 23, 112, 67, 44);
		$impinfos[] = array(218, 1181, 541, 58, 35, 23, 112, 67, 45);
		$impinfos[] = array(219, 1192, 548, 58, 35, 23, 112, 67, 45);
		$impinfos[] = array(220, 1203, 554, 58, 35, 23, 113, 67, 45);
		$impinfos[] = array(221, 1213, 561, 59, 35, 23, 113, 67, 45);
		$impinfos[] = array(222, 1224, 568, 59, 35, 23, 113, 68, 45);
		$impinfos[] = array(223, 1234, 575, 59, 35, 24, 114, 68, 45);
		$impinfos[] = array(224, 1245, 581, 59, 36, 24, 114, 68, 45);
		$impinfos[] = array(225, 1256, 588, 59, 36, 24, 114, 68, 46);
		$impinfos[] = array(226, 1266, 595, 60, 36, 24, 115, 69, 46);
		$impinfos[] = array(227, 1277, 602, 60, 36, 24, 115, 69, 46);
		$impinfos[] = array(228, 1287, 608, 60, 36, 24, 116, 69, 46);
		$impinfos[] = array(229, 1298, 615, 60, 36, 24, 116, 69, 46);
		$impinfos[] = array(230, 1309, 622, 60, 36, 24, 116, 69, 46);
		$impinfos[] = array(231, 1319, 629, 60, 36, 24, 117, 70, 47);
		$impinfos[] = array(232, 1330, 635, 61, 36, 24, 117, 70, 47);
		$impinfos[] = array(233, 1340, 642, 61, 37, 24, 117, 70, 47);
		$impinfos[] = array(234, 1351, 649, 61, 37, 24, 118, 70, 47);
		$impinfos[] = array(235, 1362, 656, 61, 37, 24, 118, 71, 47);
		$impinfos[] = array(236, 1372, 663, 61, 37, 24, 118, 71, 47);
		$impinfos[] = array(237, 1383, 669, 62, 37, 25, 119, 71, 47);
		$impinfos[] = array(238, 1393, 676, 62, 37, 25, 119, 71, 48);
		$impinfos[] = array(239, 1404, 683, 62, 37, 25, 119, 71, 48);
		$impinfos[] = array(240, 1415, 690, 62, 37, 25, 120, 72, 48);
		$impinfos[] = array(241, 1425, 696, 62, 37, 25, 120, 72, 48);
		$impinfos[] = array(242, 1436, 703, 62, 38, 25, 120, 72, 48);
		$impinfos[] = array(243, 1446, 710, 63, 38, 25, 121, 72, 48);
		$impinfos[] = array(244, 1457, 717, 63, 38, 25, 121, 73, 49);
		$impinfos[] = array(245, 1468, 723, 63, 38, 25, 122, 73, 49);
		$impinfos[] = array(246, 1478, 730, 63, 38, 25, 122, 73, 49);
		$impinfos[] = array(247, 1489, 737, 63, 38, 25, 122, 73, 49);
		$impinfos[] = array(248, 1499, 744, 64, 38, 25, 123, 73, 49);
		$impinfos[] = array(249, 1510, 750, 64, 38, 25, 123, 74, 49);
		$impinfos[] = array(250, 1521, 757, 64, 38, 25, 123, 74, 49);
		$impinfos[] = array(251, 1531, 764, 64, 39, 26, 124, 74, 50);
		$impinfos[] = array(252, 1542, 771, 64, 39, 26, 124, 74, 50);
		$impinfos[] = array(253, 1553, 777, 64, 39, 26, 124, 75, 50);
		$impinfos[] = array(254, 1563, 784, 65, 39, 26, 125, 75, 50);
		$impinfos[] = array(255, 1574, 791, 65, 39, 26, 125, 75, 50);
		$impinfos[] = array(256, 1584, 798, 65, 39, 26, 125, 75, 50);
		$impinfos[] = array(257, 1595, 804, 65, 39, 26, 126, 75, 50);
		$impinfos[] = array(258, 1606, 811, 65, 39, 26, 126, 76, 51);
		$impinfos[] = array(259, 1616, 818, 66, 39, 26, 127, 76, 51);
		$impinfos[] = array(260, 1627, 825, 66, 40, 26, 127, 76, 51);
		$impinfos[] = array(261, 1637, 831, 66, 40, 26, 127, 76, 51);
		$impinfos[] = array(262, 1648, 838, 66, 40, 26, 128, 77, 51);
		$impinfos[] = array(263, 1659, 845, 66, 40, 26, 128, 77, 51);
		$impinfos[] = array(264, 1669, 852, 66, 40, 26, 128, 77, 52);
		$impinfos[] = array(265, 1680, 858, 67, 40, 27, 129, 77, 52);
		$impinfos[] = array(266, 1690, 865, 67, 40, 27, 129, 77, 52);
		$impinfos[] = array(267, 1701, 872, 67, 40, 27, 129, 78, 52);
		$impinfos[] = array(268, 1712, 879, 67, 40, 27, 130, 78, 52);
		$impinfos[] = array(269, 1722, 886, 67, 41, 27, 130, 78, 52);
		$impinfos[] = array(270, 1733, 892, 68, 41, 27, 130, 78, 52);
		$impinfos[] = array(271, 1743, 899, 68, 41, 27, 131, 79, 53);
		$impinfos[] = array(272, 1754, 906, 68, 41, 27, 131, 79, 53);
		$impinfos[] = array(273, 1765, 913, 68, 41, 27, 131, 79, 53);
		$impinfos[] = array(274, 1775, 919, 68, 41, 27, 132, 79, 53);
		$impinfos[] = array(275, 1786, 926, 68, 41, 27, 132, 79, 53);
		$impinfos[] = array(276, 1796, 933, 69, 41, 27, 133, 80, 53);
		$impinfos[] = array(277, 1807, 940, 69, 41, 27, 133, 80, 54);
		$impinfos[] = array(278, 1818, 946, 69, 42, 27, 133, 80, 54);
		$impinfos[] = array(279, 1828, 953, 69, 42, 28, 134, 80, 54);
		$impinfos[] = array(280, 1839, 960, 69, 42, 28, 134, 81, 54);
		$impinfos[] = array(281, 1849, 967, 70, 42, 28, 134, 81, 54);
		$impinfos[] = array(282, 1860, 973, 70, 42, 28, 135, 81, 54);
		$impinfos[] = array(283, 1871, 980, 70, 42, 28, 135, 81, 54);
		$impinfos[] = array(284, 1881, 987, 70, 42, 28, 135, 81, 55);
		$impinfos[] = array(285, 1892, 994, 70, 42, 28, 136, 82, 55);
		$impinfos[] = array(286, 1903, 1000, 70, 42, 28, 136, 82, 55);
		$impinfos[] = array(287, 1913, 1007, 71, 43, 28, 136, 82, 55);
		$impinfos[] = array(288, 1924, 1014, 71, 43, 28, 137, 82, 55);
		$impinfos[] = array(289, 1934, 1021, 71, 43, 28, 137, 83, 55);
		$impinfos[] = array(290, 1945, 1027, 71, 43, 28, 137, 83, 55);
		$impinfos[] = array(291, 1956, 1034, 71, 43, 28, 138, 83, 56);
		$impinfos[] = array(292, 1966, 1041, 72, 43, 28, 138, 83, 56);
		$impinfos[] = array(293, 1977, 1048, 72, 43, 29, 139, 83, 56);
		$impinfos[] = array(294, 1987, 1054, 72, 43, 29, 139, 84, 56);
		$impinfos[] = array(295, 1998, 1061, 72, 43, 29, 139, 84, 56);
		$impinfos[] = array(296, 2009, 1068, 72, 44, 29, 140, 84, 56);
		$impinfos[] = array(297, 2019, 1075, 72, 44, 29, 140, 84, 57);
		$impinfos[] = array(298, 2030, 1081, 73, 44, 29, 140, 85, 57);
		$impinfos[] = array(299, 2040, 1088, 73, 44, 29, 141, 85, 57);
		$impinfos[] = array(300, 2051, 1095, 73, 44, 29, 141, 85, 57);
		
		
		$sofnipmi = array_reverse($impinfos);
		$max = $shi = $bri = $fad = array();
		
		if($ability==5000) { // treatment search
			foreach($impinfos AS $key => $info) {
				if($info[1]<=$treatment) {
					foreach($sofnipmi AS $yek => $ofni) {
						if($info[6]==$ofni[6]) { // shiny
							$shi = array($info[6],$ofni[0]);
						}
						if($info[7]==$ofni[7]) { // bright
							$bri = array($info[7],$ofni[0]);
						}						
						if($info[8]==$ofni[8]) { // faded
							$fad = array($info[8],$ofni[0]);
						}									
					}
					$max = array($info[0],$shi,$bri,$fad,$info[2]);
				}
			}
		} elseif($treatment==5000) { // ability search
			foreach($impinfos AS $key => $info) {
				if($info[2]<=$ability) {
					foreach($sofnipmi AS $yek => $ofni) {
						if($info[3]==$ofni[3]) { // shiny
							$shi = array($info[3],$ofni[0]);
						}
						if($info[4]==$ofni[4]) { // bright
							$bri = array($info[4],$ofni[0]);
						}						
						if($info[5]==$ofni[5]) { // faded
							$fad = array($info[5],$ofni[0]);
						}									
					}
					$max = array($info[0],$shi,$bri,$fad,$info[1]);
				}
			}			
		}
		//print_r($max); // debug
		return $max;
	}
}
?>
