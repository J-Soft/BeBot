<?php
/*
* route.php - Module route
* Improved after Budabot's work by Tyrence based on POD13's root
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

/* FIXME pathing uses dirty way, could be inceptly improved as Tyrence suggests here :
https://github.com/Budabot/Budabot/blob/master/modules/WHOMPAH_MODULE/WhompahController.class.php#L133-L156 */

$route = new Route($bot);

class Route extends BaseActiveModule
{
	function __construct (&$bot)
	{
		// Initialize the base module
		parent::__construct($bot, get_class($this));

		// Register command
		$this->register_command('all', 'route', 'GUEST');
		$this -> register_alias("route", "routes");
		$this -> register_alias("route", "whompa");
		$this -> register_alias("route", "whompas");
		$this -> register_alias("route", "grid");
		$this -> register_alias("route", "path");		

		// Add description/help
		$this -> help['description'] = "Search and show route between 2 places.";
		$this -> help['command']['route <place1> <place2>'] = "Search for existing whompa/grid route between 2 places among : icc, cam, bliss, bsn, bss, oa, tir, vw, ww, wine, nlc, nld, bor, hope, swb, trade, ent, gc, 20k, 4ho, sav, 2ho, rome, tlr, md, sab, sfh, cav, trn, trs.";
	}

	function command_handler($source, $msg, $origin)
	{
		$this->error->reset();
		
		$vars = explode(' ', strtolower($msg));
		$command = $vars[0];
		
		switch($command)
		{
			case 'route':
				if(count($vars)==3&&$vars[1]!=""&&$vars[2]!=""&&$vars[1]!=$vars[2]) {
					return $this -> send_route($source, $origin, strtolower($vars[1]), strtolower($vars[2]));
				} else {
					$this->bot->send_help($source);
				}
				break;		
			default:				
				$this -> error -> set("Broken plugin, received unhandled command: $command");
				return($this->error->message());
		}
	}
	
	function send_route($name, $origin, $from="", $to="")
	{
		// id fullname zone side shortname
		$cities[1]=array('ICC', 'Andromeda', 'Neutral', 'icc');
		$cities[2]=array('Camelot', 'Avalon', 'Clan', 'cam');
		$cities[3]=array('Bliss', 'The Longest Road', 'Clan', 'bliss');
		$cities[4]=array('Broken Shores - North', 'Broken Shores', 'Clan', 'bsn');
		$cities[5]=array('Old Athen', 'Athen', 'Clan', 'oa');
		$cities[6]=array('Tir', 'Tir County', 'Clan', 'tir');
		$cities[7]=array('Varmint Woods', 'Varmint Woods', 'Clan', 'vw');
		$cities[8]=array('Wailing Wastes', 'Wailing Wastes', 'Clan', 'ww');
		$cities[9]=array('Wine', 'Belial Forest', 'Clan', 'wine');
		$cities[10]=array('Newland City', 'Newland', 'Neutral', 'nlc');
		$cities[11]=array('Newland Desert', 'Newland Desert', 'Neutral', 'nld');
		$cities[12]=array('Borealis', 'Borealis', 'Neutral', 'bor');
		$cities[13]=array('Hope', 'Mort', 'Neutral', 'hope');
		$cities[14]=array('Stret West Bank', 'Stret West Bank', 'Neutral', 'swb');
		$cities[15]=array('Omni Trade', 'Omni Trade', 'Omni', 'trade');
		$cities[16]=array('Omni Entertainment', 'Omni Entertainment', 'Omni', 'ent');
		$cities[17]=array('Galway Castle', 'Galway County', 'Omni', 'gc');
		$cities[18]=array('20K', 'Pleasant Meadows', 'Omni', '20k');
		$cities[19]=array('4 Holes', '4 Holes', 'Clan', '4ho');
		$cities[20]=array('Broken Shores - South', 'Broken Shores', 'Omni', 'bss');
		$cities[21]=array('Outpost 10-3', 'Southern Artery Valley', 'Omni', 'sav');
		$cities[22]=array('2HO', 'Stret East Bank', 'Omni', '2ho');
		$cities[23]=array('Rome', 'Rome Red', 'Omni', 'rome');
		$cities[24]=array('The Longest Road', 'The Longest Road', 'Omni', 'tlr');
		$cities[25]=array('Mutant Domain', 'Mutant Domain', 'Omni', 'md');
		$cities[26]=array('Sabulum', 'Perpetual Wasteland', 'Neutral', 'sab');
		$cities[27]=array('Southern Fouls Hills', 'Southern Fouls Hills', 'Omni', 'sfh');
		$cities[28]=array('Central Artery Valley', 'Central Artery Valley', 'Clan', 'cav');
		$cities[29]=array('The Reck North', 'The Reck', 'Omni', 'trn');
		$cities[30]=array('The Reck South', 'The Reck', 'Clan', 'trs');
		$cities[31]=array('Grid', 'Grid', 'Neutral', 'grid'); // added to simplify route search
			
		// city1 city2
		// whomp links
		$links[]=array(1,10);
		$links[]=array(1,15);
		$links[]=array(1,6);
		$links[]=array(2,3);
		$links[]=array(2,8);
		$links[]=array(3,2);
		$links[]=array(3,4);
		$links[]=array(3,5);
		$links[]=array(4,3);
		$links[]=array(4,9);
		$links[]=array(5,3);
		$links[]=array(5,6);
		$links[]=array(5,8);
		$links[]=array(6,1);
		$links[]=array(6,5);
		$links[]=array(6,7);
		$links[]=array(7,6);
		$links[]=array(7,8);
		$links[]=array(7,9);
		$links[]=array(8,2);
		$links[]=array(8,5);
		$links[]=array(8,7);
		$links[]=array(9,4);
		$links[]=array(9,7);
		$links[]=array(10,1);
		$links[]=array(10,11);
		$links[]=array(10,12);
		$links[]=array(11,10);
		$links[]=array(11,13);
		$links[]=array(12,10);
		$links[]=array(12,14);
		$links[]=array(13,11);
		$links[]=array(13,14);
		$links[]=array(14,12);
		$links[]=array(14,13);
		$links[]=array(15,1);
		$links[]=array(15,16);
		$links[]=array(15,17);
		$links[]=array(16,15);
		$links[]=array(16,18);
		$links[]=array(16,23);
		$links[]=array(16,25);
		$links[]=array(17,15);
		$links[]=array(17,21);
		$links[]=array(17,23);
		$links[]=array(18,16);
		$links[]=array(18,21);
		$links[]=array(20,23);
		$links[]=array(21,17);
		$links[]=array(21,18);
		$links[]=array(21,22);
		$links[]=array(22,21);
		$links[]=array(22,24);
		$links[]=array(23,16);
		$links[]=array(23,17);
		$links[]=array(23,20);
		$links[]=array(24,22);
		$links[]=array(25,16);
		$links[]=array(26,13);
		$links[]=array(13,26);
		$links[]=array(25,22);
		$links[]=array(22,25);
		$links[]=array(24,20);
		$links[]=array(20,24);
		$links[]=array(27,18);
		$links[]=array(18,27);
		$links[]=array(28,19);
		$links[]=array(19,28);
		$links[]=array(19,4);
		$links[]=array(4,19);
		$links[]=array(30,6);
		$links[]=array(6,30);
		$links[]=array(29,15);
		$links[]=array(15,29);
		// grid links
		$links[]=array(31,20);
		$links[]=array(31,23);
		$links[]=array(31,17);
		$links[]=array(31,15);
		$links[]=array(31,16);
		$links[]=array(31,1);
		$links[]=array(31,19);
		$links[]=array(31,22);
		$links[]=array(31,12);
		$links[]=array(31,5);
		$links[]=array(31,6);
		$links[]=array(31,10);
		$links[]=array(31,13);
		$links[]=array(31,2);
		$links[]=array(20,31);
		$links[]=array(23,31);
		$links[]=array(17,31);
		$links[]=array(15,31);
		$links[]=array(16,31);
		$links[]=array(1,31);
		$links[]=array(19,31);
		$links[]=array(22,31);
		$links[]=array(12,31);
		$links[]=array(5,31);
		$links[]=array(6,31);
		$links[]=array(10,31);
		$links[]=array(13,31);
		$links[]=array(2,31);		
		
		$allowed = array('icc','cam','bliss','bsn','bss','oa','tir','vw','ww','wine','nlc','nld','bor','hope','swb','trade','ent','gc','20k','4ho','sav','2ho','rome','tlr','md','sab','sfh','cav','trn','trs');
		if(!in_array($from,$allowed)||!in_array($to,$allowed)) {
			return $this->bot->send_output($name, "Error, use allowed places only: ".$this->bot->core("tools")->make_blob("click to view", "icc, cam, bliss, bsn, bss, oa, tir, vw, ww, wine, nlc, nld, bor, hope, swb, trade, ent, gc, 20k, 4ho, sav, 2ho, rome, tlr, md, sab, sfh, cav, trn, trs"), $origin);
		}

		$fid = 0; $tid = 0; 
		foreach($cities as $id => $city) {
			if($city[3]==$from) {
				$from = $city;
				$fid = $id; echo "f".$id." ";
			}
			if($city[3]==$to) {
				$to = $city;
				$tid = $id; echo "t".$id." ";
			}
		}
		
		$pre = "No";
		$blob = "Sorry, couldn't find a route ...";
		$searching = true;
		if($searching) {
			foreach($links as $link) {
				if($link[0]==$fid&&$link[1]==$tid) {
					$searching = false; $pre = "Direct";
					$blob = $this->parse_city($from)." > ".$this->parse_city($to);
				}
			}
		}
		if($searching) {
			foreach($links as $link1) {
				if($link1[0]==$fid) {
					foreach($links as $link2) {
						if($link2[1]==$tid) {
							if($link1[1]==$link2[0]) {
								$searching = false; $pre = "1 station";
								$blob = $this->parse_city($from)." > ".$this->parse_city($cities[$link1[1]])." > ".$this->parse_city($to);
							}
						}
					}
				}
			}
		}
		if($searching) {
			foreach($links as $link1) {
				if($link1[0]==$fid) {
					foreach($links as $link2) {
						if($link2[1]==$tid) {
							foreach($links as $link3) {
								if($link1[1]==$link3[0]&&$link3[1]==$link2[0]) {
									$searching = false; $pre = "2 stations";
									$blob = $this->parse_city($from)." > ".$this->parse_city($cities[$link1[1]])." > ".$this->parse_city($cities[$link2[0]])." > ".$this->parse_city($to);
								}								
							}
						}
					}
				}
			}
		}	
		if($searching) {
			foreach($links as $link1) {
				if($link1[0]==$fid) {
					foreach($links as $link2) {
						if($link2[1]==$tid) {
							foreach($links as $link3) {
								if($link1[1]==$link3[0]) {
									foreach($links as $link4) {
										if($link2[0]==$link4[1]&&$link3[1]==$link4[0]) {
											$searching = false; $pre = "3 stations";
											$blob = $this->parse_city($from)." > ".$this->parse_city($cities[$link1[1]])." > ".$this->parse_city($cities[$link3[1]])." > ".$this->parse_city($cities[$link2[0]])." > ".$this->parse_city($to);
										}
									}
								}								
							}
						}
					}
				}
			}
		}
		if($searching) {
			foreach($links as $link1) {
				if($link1[0]==$fid) {
					foreach($links as $link2) {
						if($link2[1]==$tid) {
							foreach($links as $link3) {
								if($link1[1]==$link3[0]) {
									foreach($links as $link4) {
										if($link2[0]==$link4[1]) {
											foreach($links as $link5) {
												if($link5[0]==$link3[1]&&$link5[1]==$link4[0]) {
													$searching = false; $pre = "4 stations";
													$blob = $this->parse_city($from)." > ".$this->parse_city($cities[$link1[1]])." > ".$this->parse_city($cities[$link3[1]])." > ".$this->parse_city($cities[$link4[0]])." > ".$this->parse_city($cities[$link2[0]])." > ".$this->parse_city($to);													
												}
											}
										}
									}
								}								
							}
						}
					}
				}
			}
		}
		if($searching) { // prolly useless but safer to have
			foreach($links as $link1) {
				if($link1[0]==$fid) {
					foreach($links as $link2) {
						if($link2[1]==$tid) {
							foreach($links as $link3) {
								if($link1[1]==$link3[0]) {
									foreach($links as $link4) {
										if($link2[0]==$link4[1]) {
											foreach($links as $link5) {
												if($link5[0]==$link3[1]) {
													foreach($links as $link6) {
														if($link6[0]==$link5[1]&&$link6[1]==$link4[0]) {
															$searching = false; $pre = "5 stations";
															$blob = $this->parse_city($from)." > ".$this->parse_city($cities[$link1[1]])." > ".$this->parse_city($cities[$link3[1]])." > ".$this->parse_city($cities[$link5[1]])." > ".$this->parse_city($cities[$link4[0]])." > ".$this->parse_city($cities[$link2[0]])." > ".$this->parse_city($to);													
														}
													}
												}
											}
										}
									}
								}								
							}
						}
					}
				}
			}
		}	
		
		return $this->bot->send_output($name, $pre." route result: ".$this->bot->core("tools")->make_blob("click to view", $blob), $origin);
	}
	
	function parse_city($city) {
		$side = $city[2];
		$name = $city[0];
		$zone = $city[1];		
		if($side=="Clan") { $color = "red"; } elseif($side=="Omni") { $color = "blue"; }  else { $color = "white"; }
		return "##".$color."##".$name."##end## (".$zone.")";
	}
}
?>
