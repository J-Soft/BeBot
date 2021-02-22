<?php
/*
* Whereis.php - Module whereis
* Adapted from Budabots's work by Jaqueme & Malosar
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

$whereis = new Whereis($bot);

class Whereis extends BaseActiveModule
{
	function __construct (&$bot)
	{
		parent::__construct($bot, get_class($this));

		$this->register_command('all', 'whereis', 'GUEST');
		$this -> register_alias("whereis", "where");

		$this -> help['description'] = "Search and show various bosses/NPCs and their details.";
		$this -> help['command']['whereis <keyword>'] = "Search for existing whereis by keywords.";
	}

	function command_handler($source, $msg, $origin)
	{
		$this->error->reset();
		
		$vars = explode(' ', strtolower($msg));
		$command = $vars[0];
		
		switch($command)
		{
			case 'whereis':
				return $this -> send_whereis($source, $origin, $msg);
				break;		
			default:			
				$this -> error -> set("Broken plugin, received unhandled command: $command");
				return($this->error->message());
		}
	}
	
	function send_whereis($name, $origin, $msg="")
	{
		//  name zone keywords pf x y
		$elements[] =  array("12-man", "Xan", "12m 12man", 6013, 418, 324);
		$elements[] =  array("4 Holes", "in the west central part of the world. It has whompas at 1200 x 1225 to 2ho, 20k, and Broken Shores. North of 4holes is Stret West Bank, south is Andromeda, east Stret East Bank, no zone avail to the west.", "Four Holes Fourholes 4ho", 760, 893, 1754);
		$elements[] =  array("A Dancing Fool", "Baboons (located in Omni Entertainment)", NULL, 705, 766, 766);
		$elements[] =  array("A Face In The Sand", "Aegean", NULL, 585, 1400, 2750);
		$elements[] =  array("A Spoiled Brat", "in a park in Omni-Ent", NULL, 705, 946, 350);
		$elements[] =  array("Abmouth Supremus", "in the lower levels of the Subway Dungeon at the very end. He spawns two infectors when attacked.", NULL, 0, 0, 0);
		$elements[] =  array("Ace Camp", "Eastern Fouls Plain", "Primus", 620, 720, 1380);
		$elements[] =  array("Aegean", "in the northeast part of the world. No grid access point and no whompa. East of Aegean is Varmint Woods, to the west is Athen Shire, southeast is Upper Stret East Bank, southwest Wartorn Valley and Stret West Bank, no zone available to the north.", "Aegean Aegan Aegean Aegeon Aegon Agean Ageon", 585, 0, 0);
		$elements[] =  array("Afreet Ellis", "Inferno, NE of Frontier Garden", NULL, 4605, 2560, 2910);
		$elements[] =  array("Ahomac", "Inferno, Fronter", NULL, 4605, 2745, 2341);
		$elements[] =  array("Akshk`i", "Inferno, South of Frontier", "Akshki", 4605, 2294, 2253);
		$elements[] =  array("Albtraum", "Inferno, Burning Mashes", NULL, 4005, 1135, 756);
		$elements[] =  array("Algid Slither", "Adonis, Abyss N", NULL, 4873, 1510, 3320);
		$elements[] =  array("Alpha Skincrawler", "Crypt of Home, Broken Shores", NULL, 0, 0, 0);
		$elements[] =  array("Alvin Odeleder", "Lush Fields Outpost", "Alvinodeleder", 695, 1538, 2500);
		$elements[] =  array("Anansi Devotee", "East of Sorrow In Inferno", NULL, 4605, 1650, 1400);
		$elements[] =  array("Anansi Disciple", "Burning Mashes", NULL, 4005, 942, 697);
		$elements[] =  array("Anansi Dreamkeeper", "Burning Mashes", NULL, 4005, 942, 697);
		$elements[] =  array("Anansi Gopher", "Burning Mashes, on the ramp", NULL, 4005, 860, 719);
		$elements[] =  array("Anansi Orator", "North West from the Petrified Hecklers", NULL, 4005, 945, 1350);
		$elements[] =  array("Anansi Protector", "East of Sorrow In Inferno", NULL, 4605, 1650, 1400);
		$elements[] =  array("Anansi Scribe", "East of Sorrow In Inferno", NULL, 4605, 1650, 1400);
		$elements[] =  array("Anansi Speaker", "North West from the Petrified Hecklers", NULL, 4005, 945, 1350);
		$elements[] =  array("Anansi Storyteller", "North West from the Petrified Hecklers", NULL, 4005, 945, 1350);
		$elements[] =  array("Anansi Zealot", "Burning Mashes", NULL, 4005, 942, 697);
		$elements[] =  array("Anansi`s Abettor", "Inferno, Far SW of Sorrow", NULL, 4005, 590, 422);
		$elements[] =  array("Anansi`s Disciple", "Inferno", NULL, 4605, 3381, 2694);
		$elements[] =  array("Anansi`s Favorite", "Inferno, just NE of Frontier", NULL, 4605, 2719, 2656);
		$elements[] =  array("Anansi`s Left Hand", "South West of Oasis, East of Sorrow in Inferno. Calmer reccomended.", NULL, 4005, 1939, 1760);
		$elements[] =  array("Anansi`s Right Hand", "Inferno, East of Sorrow", NULL, 4005, 1914, 1603);
		$elements[] =  array("Ancient Tombstone", "North of Yutto`s Mashes, surrounded by spirits", NULL, 4005, 2327, 1390);
		$elements[] =  array("Andromeda", "in the southwest part of the world. Whompas to Tir, Newland, and Omni Trade. To the north (east) of Andromeda is 4 Holes, to the north (west) is Stret East Bank. To the east is Milky Way, south (east) is Lush Fields, south Clondyke. No zone avail to the west.", "655 Andromada Andromedia Andromida Icc", 655, 3250, 900);
		$elements[] =  array("Angel`s Trumpet", "Clondyke (seems to be in multiple locations nearby)", "Bronto Datura Inoxia", 670, 2359, 3656);
		$elements[] =  array("Aniitap`s Shadow", "Inferno, North of Frontier", NULL, 4605, 2416, 3238);
		$elements[] =  array("Aniitap", "Inferno", "", 4605, 2453, 3357);
		$elements[] =  array("Another Face In The Sand", "Mort", NULL, 560, 1300, 2650);
		$elements[] =  array("Apprentice Beasthandler", "Inferno, 255 Incarnator", NULL, 4605, 3325, 3134);
		$elements[] =  array("Aquarius", "Pandemonium, East Node", NULL, 0, 0, 0);
		$elements[] =  array("Architect Striker", "in the Subway Dungeon, usually on the bridge.", NULL, 0, 0, 0);
		$elements[] =  array("Area Y", "beyond Galway View next to Arthers Pass", NULL, 0, 0, 0);
		$elements[] =  array("Arid Rift", "", "", 6013, 257, 635);
		$elements[] =  array("Aries", "Pandemonium, West Node", NULL, 0, 0, 0);
		$elements[] =  array("Asanon", "Inferno, Fronter", NULL, 4605, 2785, 2385);
		$elements[] =  array("Asase`s Drudge", "", "", 4005, 632, 297);
		$elements[] =  array("Asperous Imp", "Elysium, Near Ergo", NULL, 0, 0, 0);
		$elements[] =  array("Astypalia", "Inferno, West of Razors Lair", NULL, 4605, 1777, 2715);
		$elements[] =  array("Atakirh", "Inferno, South of Frontier", NULL, 4605, 1961, 2451);
		$elements[] =  array("Athen Old", "in the NW part of the world. It has a whompa to Tir, Wailing Wastes and Bliss at 445 x 318. Out the east gate is Wartorn Valley and out west gate is West Athen", NULL, 540, 512, 573);
		$elements[] =  array("Athen Shire", "in the NW part of the world. It has no grid access or whompa. To the north is Wailing Wastes, to the east is Wartorn Valley and Aegean, to the west is The Longest Road, to the south is Holes in the Wall. The War Academy at 1740 x 1970.", "Athen Shir Shire Shite Shrine Sire Athens Athenshire", 550, 0, 0);
		$elements[] =  array("Athen West", "in the NW part of the world. It has no whompa. To the north, south and west is Athen Shire, to the east Athen Old.", "Athen West Athens Athenwest Old Weat Grid", 545, 472, 410);
		$elements[] =  array("Athlar", "Elysium, inside mountains SW and NW", NULL, 0, 0, 0);
		$elements[] =  array("Augmented Cyborg Hellfury", "Cyborg Barracks Greater Tir County", NULL, 647, 3232, 2340);
		$elements[] =  array("Avalon", "in the north west part of the world. It has a whompa to Athens and Wailing Wastes at 2175x3815. There is no zone available to the north, east and west. Southeast is Wailing Wastes. camelot castle (dungeon) at 2090 x 3820. clan op with scanner at 1540 x 3730 and 2140 x 3110. Omni outposts with scanner at 800 x 1630 and at 1870 x 1230.", "Avalon Avolon Omni Outpost", 505, 2070, 3760);
		$elements[] =  array("Aztur The Immortal", "Temple Of The Three Winds, Greater Tir County. You need to kill Uklesh the Frozen and Khalum before he spawns.", "Azzy", 647, 0, 0);
		$elements[] =  array("Baboons", "Omni Entertainment", NULL, 705, 766, 766);
		$elements[] =  array("Bahirae Serugiusu", "Omni Trade", "Token", 710, 411, 394);
		$elements[] =  array("Bane", "Crypt of Home, Broken Shores", NULL, 0, 0, 0);
		$elements[] =  array("Beast 215", "", "", 4605, 3497, 2063);
		$elements[] =  array("Beer And Booze Bar", "Mort, in the city called Hope. The bar has a 5% supression gas.", "Beer &amp; Booze And Bar N", 566, 2840, 1920);
		$elements[] =  array("Belial Forest", "in the SE part of the world. No grid access. Whompas in Wine lead to Broken Shores and Varmint Woods at 2150 x 2319. To the north of Belial Forest is Deep Artery Valley, south is Eastern Foul Plains, west is S.Artery Valley and Milky Way is south and southwest. No zone to east.", "Belial Forrest Belialforest", 605, 2150, 2319);
		$elements[] =  array("Bending Eremite", "Elysium, Sand dunes near Cold Rock", NULL, 0, 0, 0);
		$elements[] =  array("Best In Brass", "Galway Shire, Rome Stretch", "Bestinbrass Brast", 687, 400, 750);
		$elements[] =  array("Bia`s Favorite", "Inferno", "", 4605, 2764, 2655);
		$elements[] =  array("Bigot Helozabasael", "Inferno", "", 4005, 1482, 2274);
		$elements[] =  array("Bigot Inmodeah", "Inferno", "", 4005, 696, 1151);
		$elements[] =  array("Bigot Laniheanheh", "Inferno", "", 4005, 1339, 2480);
		$elements[] =  array("Bigot Nzaemihiel", "Inferno", "", 4005, 749, 1184);
		$elements[] =  array("Bigot Pohihanepael", "Inferno", "", 4605, 3237, 3210);
		$elements[] =  array("Bigot", "Inferno, SW of Sorrow, N of Sorrow, 255 Inc", NULL, 0, 0, 0);
		$elements[] =  array("Biomare", "The Longest Road. Recommended for teams of lvl 40-70. Look for a door that says Foreman above the doorway.", "Biological Materials Research Omni-Med Bio Facility Foremans Office", 795, 1930, 775);
		$elements[] =  array("Black", "Adonis, Abyss N", NULL, 4873, 1644, 2237);
		$elements[] =  array("Bliss", "The Longest Road. Bliss has shops, clan insure terms, banks and mission terms. Bliss has whompas to Athen Old, Avalon, and Broken Shores", NULL, 795, 3700, 1615);
		$elements[] =  array("Bold Eremite", "Inferno, southeast of Sorrow (straight south of Anansi Devotee`s) around", NULL, 4605, 1650, 1150);
		$elements[] =  array("Bonzo", "at the Beer and Booze Bar, Mort", NULL, 560, 2835, 1930);
		$elements[] =  array("Borealis Grid", "", "bor grid", 800, 636, 728);
		$elements[] =  array("Borealis", "in the central-west part of the world. Whompa to Stret West Bank at 682 x 531. Whompa to Newland right next to that. East of Borealis is Holes in the Wall. No zones currently available to the north, south, and west.", "Bor Borealis Approach City Grid", 800, 635, 727);
		$elements[] =  array("Brainy Ant Woods", "Greater Tir County", "Ants Biniary", 647, 1300, 1500);
		$elements[] =  array("Brenda Diamond", "at the ruins near Home in Broken Shores", NULL, 665, 430, 2200);
		$elements[] =  array("Brimstone Demon", "East-North-East of Sorrow and spawns among the other demons.  Sometimes it`s bugged and spawns inside the nearby mountain, petition to get them killed/moved out.", NULL, 4005, 1650, 1625);
		$elements[] =  array("Broken Shores", "in the southwest corner of the world. It has a grid access point in City of Home. In the north is a Whompah at 1000 x 3760 to Bliss and to Wine. There is a Whompah to Rome Red and 4 Holes Trade at 2340 x 2250", "Broken Shord Shore Shores Brokenshores Bs Grid", 665, 644, 1313);
		$elements[] =  array("Brumal Slither", " Adonis, Abyss N", NULL, 4873, 1440, 3414);
		$elements[] =  array("Brutish Dryad", "Elysium by Omni Shunpike exit 699 x 914 and 735 x 956, Utopolis and South Elysium", NULL, 4542, 699, 914);
		$elements[] =  array("Brutus Leonidis", "Athen Shire", NULL, 550, 1550, 300);
		$elements[] =  array("Cacophonous Imp", "Elysium, Near Ergo", NULL, 0, 0, 0);
		$elements[] =  array("Calamity Eremite", "Inferno SouthEast of Sorrow (South of Anansi Devotees), Area: Burning Marshes (south of the rock)", NULL, 4005, 1685, 1164);
		$elements[] =  array("Camelot", "in the northwest part of the world, in the center of the city of Avalon and can be reached by whompa from Old Athens or Wailing Wastes.", NULL, 505, 0, 0);
		$elements[] =  array("Cancer", "Pandemonium, East Node", NULL, 0, 0, 0);
		$elements[] =  array("Cantankerous Golem", "Adonis, 2401 x 712 SE and 784 x 892 SW", NULL, 4872, 2401, 712);
		$elements[] =  array("Capricorn", "Pandemonium, North Node", NULL, 0, 0, 0);
		$elements[] =  array("Captain Lewison", "Reet retreat, Stret West Bank", NULL, 790, 1206, 2807);
		$elements[] =  array("Cardboard Palm", "Clondyke", "Bronto Zamia Furfuracea", 670, 1684, 1516);
		$elements[] =  array("Catervauling Minx", "Elysium, Near Ergo & Fallen Forest", NULL, 0, 0, 0);
		$elements[] =  array("Cenobite Shadow", "Crypt of Home, Broken Shores", NULL, 0, 0, 0);
		$elements[] =  array("Central Artery Valley", "in the mideastern part of the world. No grid or whompa. North of Central Artery Valley is Varmint Woods, south is Southern Artery Valley, east is Deep Artery Valley, west is Upper Stret East Bank. NE corner is Greater Tir Cnty and SW corner is Stret East Bank.", "Cav Base Central Art Valley Centralarteryvalley", 590, 0, 0);
		$elements[] =  array("Cerubin The Rejected", "Crypt of Home, Broken Shores", NULL, 0, 0, 0);
		$elements[] =  array("Chilly Slither", "Adonis, Abyss N", NULL, 4873, 1465, 3373);
		$elements[] =  array("Chimera Aberrant", "around the +10 ring dungeon just inside Inf", NULL, 4005, 1091, 380);
		$elements[] =  array("Chimera Crusher", "East of the Portal to Pen, around the +10 ring dungeon just inside Inf", NULL, 4005, 1090, 400);
		$elements[] =  array("Chimera Monitor", "Inferno, West of Dark Marshes Unred Temple", NULL, 4005, 1328, 583);
		$elements[] =  array("Chimera Trainer", "Inferno, West of Dark Marshes Unred Temple", NULL, 4005, 1388, 656);
		$elements[] =  array("City Administrator Rex Chapman", "Omni-1 Entertainment near the billboards next to the Whompa to Rome", NULL, 705, 905, 522);
		$elements[] =  array("City Of Home", "Broken Shores. It has shops, clan/neut insure terms, banks.", "Cityofhome coh", 665, 735, 1459);
		$elements[] =  array("Clan Modified A-4000", "at Avalon", "Clan Modified A 4000 a-4000 A4000", 505, 1105, 2520);
		$elements[] =  array("Clan Modified A-4001", "in Wailing Wastes", "A-4001 A 4001 A4001", 551, 0, 0);
		$elements[] =  array("Clan Trader Shop", "Old Athens (SW of Grid - Finest Edition)", "", 540, 370, 500);
		$elements[] =  array("Clan Trader Shop", "Tir (NW - Computers Inc)", "", 640, 395, 545);
		$elements[] =  array("Clawfinger Forefather", "Smuggler`s Den, Southern Fouls Hills", "Claw Finger Forefather Clawfinger Fore Father", 615, 1749, 869);
		$elements[] =  array("Clondyke", "in the southwest part of the world. It has a grid access point at 1054 x 4023, no whompa. North of Clondyke is Andromeda, west is Galway County, east is Lush Fields. No zone avail to the south.", "Clondike Coldyke Condyke Klondyke", 670, 0, 0);
		$elements[] =  array("Coal Lizard", "Misty Marshes NW from Dark Marshes", NULL, 4005, 1635, 1110);
		$elements[] =  array("Coco", "next to the bar in `The Cup`, in West Athen", NULL, 545, 0, 0);
		$elements[] =  array("Coiling Eremite", "Elysium, Sand dunes near Cold Rock", NULL, 0, 0, 0);
		$elements[] =  array("Cold Slither", "Adonis, Abyss N", NULL, 4873, 1560, 3375);
		$elements[] =  array("Colonel Frank Kaehler", "Omni-Forest", "Colonel Frank Kaehler Keahler Kehler", 716, 700, 2000);
		$elements[] =  array("Comatosed Soul", "Elysium, E Whispervale and 115 Inc", NULL, 0, 0, 0);
		$elements[] =  array("Commander Jocasta", "at Cyborg Barracks in Greater Tir County", NULL, 647, 3232, 2340);
		$elements[] =  array("Commander Kelly Frederickson", "Tir County", "Commander Kelly Frederickson Fredrickson", 646, 1750, 1100);
		$elements[] =  array("Commander Kend Ash", "Omni-1 Trade", "Bazzit`s Alien Library Linked Hacker Tool Vanguard Node Access Card Kyr`Ozch Structural Analyzer", 710, 415, 405);
		$elements[] =  array("Condemned Subway - Borealis", "Borealis, south part of city", "Abandoned Subway Codemned Comdemned Condemned Condemnedsubway", 800, 637, 463);
		$elements[] =  array("Condemned Subway - Galway Shire", "Galyway Shire, just outside the east gates of Rome Blue", "Abandoned Subway Codemned Comdemned Condemned Condemnedsubway", 687, 168, 874);
		$elements[] =  array("Condemned Subway - Old Athen", "Old Athen, just NE of town center", "Abandoned Subway Codemned Comdemned Condemned Condemnedsubway", 540, 487, 439);
		$elements[] =  array("Conflagrant Spirit", "in Inferno, NW of Yuttos", NULL, 4005, 2247, 1393);
		$elements[] =  array("Contemplating Spirits And Unrepentant Spirits", "among the runes just north of the portal to Penumbra in Inferno surrounding the Spirit Of Disruption.", NULL, 4005, 0, 0);
		$elements[] =  array("Corrupt Spirit", "Adonis, 1524 x 464 S and 1941 x 2241 N", NULL, 4872, 1524, 464);
		$elements[] =  array("Crabby Golem", "Adonis, 2334 x 559 SE and 783 x 1012 SW", NULL, 4872, 2334, 559);
		$elements[] =  array("Cranky Golem", "Adonis, 2402 x 638 SE and 849 x 1177 SW", NULL, 4872, 2402, 638);
		$elements[] =  array("Creepy Spider", "Inferno, N of 255 Incarnator", NULL, 4605, 3222, 3419);
		$elements[] =  array("Crete", "Inferno, 255 Incarnator", NULL, 4605, 3140, 3039);
		$elements[] =  array("Cuty", "Tir County, Crater Farm Region", NULL, 646, 1500, 600);
		$elements[] =  array("Cyborg Barracks", "in the northeast part of the world, in Greater Tir County. Recommended for teams of lvl 70-90.", "Borg Baracks Barrack Barrackes Barracks Barracksccccc Barracs Barraks Camp Campp Camps Domain Dungeon Borgbarracks Borgs Cyborg Cyborgbarracks", 647, 3230, 2340);
		$elements[] =  array("Cyborg Brigadier General", "Mort (Can be found wandering at the Ruins on Mort Crater, use the Sentinals grid exit)", NULL, 560, 1400, 530);
		$elements[] =  array("Cyborg Lieutenant Colonel", "Mort (Can be found wandering at the Ruins on Mort Crater, use the Sentinals grid exit)", NULL, 560, 1400, 530);
		$elements[] =  array("Daedra Iberra", "Pleasent Meadows", NULL, 630, 1510, 720);
		$elements[] =  array("Dana McCoy", "Lower Scheol", NULL, 4881, 1220, 110);
		$elements[] =  array("Dancing Atrox Bar", "Omni-1 Screening Area (Omni Forest). You may know this place as Relax Bar.", "Relax Bar Club Bar", 716, 300, 1850);
		$elements[] =  array("Daria Marie Walzer", "Lush Fields", NULL, 695, 1782, 2062);
		$elements[] =  array("Daring Dryad", "Penumbra, South of W Pipe Entrance", NULL, 0, 0, 0);
		$elements[] =  array("Dauntless Dryad", "Penumbra, South of W Pipe Entrance", NULL, 0, 0, 0);
		$elements[] =  array("Deep Artery Valley", "in the mideastern part of the world. No grid and no whompa. To the north of Deep Artery Valley is Greater Tir County (but a force field blocks you from zoning north), south is Belial Forest, west is Central Artery Valley and Southern Artery Valley, no zone to east.", "Deeparteryvalley", 595, 0, 0);
		$elements[] =  array("Defender Of The Three", "Temple Of The Three Winds, Greater Tir County", NULL, 647, 0, 0);
		$elements[] =  array("Deidre Lux", "in the East Last Ditch area of Stret West Bank.", "Bazzit`s Alien Library Hacker Tool Vanguard Node Access Card", 790, 1260, 2845);
		$elements[] =  array("Delinquent Spirit", "Adonis, S", NULL, 4872, 1579, 433);
		$elements[] =  array("Den Smuggler Pilot", "Smugglers Den, Southern Fouls Hills", NULL, 615, 1755, 872);
		$elements[] =  array("Devoted Spirit Hunter", "Inferno, West of Sorrow", NULL, 4005, 645, 1314);
		$elements[] =  array("Diamondine Soldier", "Eastern Fouls Plain", NULL, 620, 1925, 1450);
		$elements[] =  array("Diamondine Trainee", "Eastern Fouls Plain", NULL, 620, 1925, 1450);
		$elements[] =  array("Ding", "Greater Tir County", NULL, 647, 0, 0);
		$elements[] =  array("Distracted Snake Tamer", "Inferno, 225 Incarnator", NULL, 4605, 3515, 1957);
		$elements[] =  array("Disturbing Imp", "Elysium, Near Ergo", NULL, 0, 0, 0);
		$elements[] =  array("Doctor Krank", "Centeral Artery Valley", "Dr Krank Dr Crank Drcrank", 590, 2965, 1315);
		$elements[] =  array("Dodga Demercel", "Rising Sun, Aegean", NULL, 585, 0, 0);
		$elements[] =  array("Dominus Facut the Bloodless", "Inner Sanctum, 3rd Floor", NULL, 0, 0, 0);
		$elements[] =  array("Dominus Jiannu", "Inner Sanctum, 3rd Floor", NULL, 0, 0, 0);
		$elements[] =  array("Dominus Ummoh the Pedagogue", "Inner Sanctum, 3rd Floor", NULL, 0, 0, 0);
		$elements[] =  array("Donna Red", "Nascense", "Quest Clan Garden Key Alban Redeemed", 4310, 985, 1760);
		$elements[] =  array("Dr. Hercules Lincoln", "", "", 610, 1133, 2376);
		$elements[] =  array("Dr. Jones - Physical Anthropologist", "Lower Scheol", NULL, 4881, 1180, 83);
		$elements[] =  array("Dr.Curry - Linguistic Anthropologist", "Upper Scheol", NULL, 4880, 1089, 1137);
		$elements[] =  array("Dr.Darnell - Social Anthropologist", "Upper Scheol", NULL, 4880, 305, 1111);
		$elements[] =  array("Dr.Hestyia - Archaeologist", "Lower Scheol", NULL, 4881, 799, 595);
		$elements[] =  array("Eastern Fouls Plain", "in the southeast part of the world. No grid or whompa. North of E.Fouls Plain is Belial Forest (but a force field keeps you from zoning north), south is Southern Fouls Hills, north west is Milky Way, southwest is Pleasant Meadow. No zone avail to the east.", "East Foul Plains Easternfoulsplain Efp", 620, 0, 0);
		$elements[] =  array("Ecclesiast Abal Fal", "Nascense", "Quest Clan Garden Key Alban Redeemed", 4310, 1890, 690);
		$elements[] =  array("Eddie", "Stret West Bank, outside Reets Retreat", "Eddy", 790, 1212, 2828);
		$elements[] =  array("Eden Cafe", "Omni Entertainment, South", NULL, 705, 744, 417);
		$elements[] =  array("Eighth Brood Champion", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("Electro Unique", "Wailing Wastes, north of Athens Shire", "Electro Umique Uneak Unique Uniqui Electrounique", 551, 650, 1950);
		$elements[] =  array("Elian Zuwadza", "Galway", NULL, 685, 450, 1300);
		$elements[] =  array("Elmer Ragg", "Mort", NULL, 560, 1731, 942);
		$elements[] =  array("Ember Chimera", "Burning Marshes SW of Sorrow", NULL, 4005, 757, 870);
		$elements[] =  array("Enjoy It While It Lasts", "Tir City", "bar club", 640, 650, 400);
		$elements[] =  array("Enkindled Spirit", "Inferno, NW of Yuttos", NULL, 4005, 2326, 1280);
		$elements[] =  array("Eradicator Deimos", "Cyborg Barracks, Greater Tir County", "deimos demos eradicater eradicator", 647, 3232, 2340);
		$elements[] =  array("Ergo, Inferno Guardian of Shadows", "", "", 4605, 2800, 3378);
		$elements[] =  array("Ergo, Penumbra Guardian of Shadows", "", "", 4321, 2171, 2446);
		$elements[] =  array("Eric Mendelson Outpost", "Varmint Woods, there are mission terms, insure term and shopping terms", "Erik Ericmendelsonoutpost", 600, 2450, 2100);
		$elements[] =  array("Eroded Ancient Statue", "", "", 610, 1033, 2005);
		$elements[] =  array("Escaped Gargantula", "Galway County", NULL, 685, 2160, 1150);
		$elements[] =  array("Estella Fire", "Inferno", "", 4605, 3554, 2030);
		$elements[] =  array("Ethel Anthony", "near the Newland Desert Whompah, standing next to Jens Stoltenberg", "AI Quest Social Clothing Combined Armor", 565, 2179, 1547);
		$elements[] =  array("Eumenides", "Condemned Subway, at the end of the one rail way hall, past the slum runners but before the infectors", NULL, 0, 0, 0);
		$elements[] =  array("Fanatic Spirit Hunter", "Inferno, North of Sorrow", NULL, 4005, 1386, 1990);
		$elements[] =  array("Fearless Dryad", "Penumbra, South of W Pipe Entrance", NULL, 0, 0, 0);
		$elements[] =  array("Feral Vortexoid Mindbreaker", "Burning Marshes SW of Sorrow", NULL, 4005, 617, 737);
		$elements[] =  array("Feral Vortexoid Soother", "Burning Marshes SW of Sorrow", NULL, 4005, 617, 737);
		$elements[] =  array("Feral Vortexoid Striker", "Burning Marshes SW of Sorrow", NULL, 4005, 617, 737);
		$elements[] =  array("Fetid Eremite", "Inferno", "", 4605, 2611, 2122);
		$elements[] =  array("Fiery Imp", "", "", 4005, 836, 186);
		$elements[] =  array("Fiery Soldier", "Eastern Fouls Plain", NULL, 620, 365, 2060);
		$elements[] =  array("Fiery Trainee", "Eastern Fouls Plain", NULL, 620, 365, 2060);
		$elements[] =  array("Fifth Brood Champion", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("Fire Flea", "Inferno, it`s the first creature you will encounter. When killed, a Fiery Imp will appear.", "", 4005, 836, 186);
		$elements[] =  array("First Brood Champion", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("Fly Agaric", "Clondyke", "Bronto Amanita Muscaria", 670, 588, 3441);
		$elements[] =  array("Forefather", "Smuggler`s Den, Southern Fouls Hills", NULL, 615, 1755, 872);
		$elements[] =  array("Fourth Brood Champion", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("Freedom Outpost", "Athen Shire. Insure terms, shops and mission terms and banks are available here. Directly beside the outpost is the zone to Wailing Wastes.", "Freedomoutpost", 550, 1553, 370);
		$elements[] =  array("Fritz", "in the Neuts r` us club in Newland City.", "AI Quest Social Clothing Hair Care Contraption Combined Armor", 566, 447, 340);
		$elements[] =  array("Galvano", "Greater Omni-Forest, Grassland", "Galivino Galvano Galven Galvino", 717, 2020, 2190);
		$elements[] =  array("Galway Castle Model", "Galway County", NULL, 685, 1110, 1000);
		$elements[] =  array("Galway County", "in the southwest part of the world. It has a grid access point at 1416 x 1091 and whompas at 2530 x 1175 to Outpost 10-3, Omni-1 Trade, and Rome . West is Galway Shire, east is Clondyke, no zones avail to north and south.", "Galaway County Galway Country Galwaycounty", 685, 2530, 1175);
		$elements[] =  array("Galway Shire", "in the southwest part of the world. No grid access or whompa. East is Galway County, west is Broken Shores, no zones avail to north and south. Rome is located midway in Galway Shire on far west side.", "Galaway Shire Galwayshire", 687, 0, 0);
		$elements[] =  array("Gartua The Doorkeeper", "Temple Of The Three Winds, Greater Tir County", NULL, 647, 0, 0);
		$elements[] =  array("Gashing Soul Dredge", "Adonis, NE", NULL, 4872, 2899, 2580);
		$elements[] =  array("Gemini", "Pandemonium, East Node", NULL, 0, 0, 0);
		$elements[] =  array("General Freewheeler", "Avalon, at the Main OT Base", NULL, 505, 1800, 1200);
		$elements[] =  array("General Hardcastle", "Avalon, at the Main OT Base", NULL, 505, 1800, 1200);
		$elements[] =  array("General Kaehler Jr", "Avalon, Secondary Base", NULL, 505, 0, 0);
		$elements[] =  array("General Kronilis", "Perpetual Wastelands", NULL, 570, 2000, 2500);
		$elements[] =  array("General Nirtox", "Perpetual Wastelands", NULL, 570, 0, 0);
		$elements[] =  array("General Serverus", "Cyborg Barracks, Greater Tir County", NULL, 647, 3232, 2340);
		$elements[] =  array("General Vivyan", "Perpetual Wastelands", NULL, 570, 2120, 1760);
		$elements[] =  array("Genghis Pan", "Mongol Meat, Tir", NULL, 0, 0, 0);
		$elements[] =  array("George", "Greater Tir County", NULL, 647, 3200, 2300);
		$elements[] =  array("Gianna Molla/Perugino", "Stret West Bank, East Last Ditch area", "Quest Wedding Ring Omni-Tek Trash Can ", 790, 1285, 2980);
		$elements[] =  array("Gilbert Glove", "Newland Desert", "AI Quest Social Clothing Cybernetic Fingertips Combined Armor", 565, 3110, 550);
		$elements[] =  array("Gnuff", "in the Will to Fight dungeon, in the center at 1111 x 833. He drops a Crystal of Rift Power, which will give you a random buff, like when you open a shrine.", NULL, 0, 0, 0);
		$elements[] =  array("Good Time Party Mixer", "Newland City. And one for Clans is located at Reets Retreat", NULL, 566, 463, 339);
		$elements[] =  array("Gouger Scorpiod", "Pleasant Meadows (within a fairly large area of)", "Bronto", 630, 2188, 1938);
		$elements[] =  array("Greasy Joints", "Newland Desert", "Greasy Gears Jints Joings Joint Joints Now Jones Greasyjoints Greazy Greazyjoints", 565, 890, 950);
		$elements[] =  array("Greater Omni Forest", "in the southeast part of the world. No grid or whompa.", "Greater Omni Foreset Forest Forrest Greateromniforest", 717, 0, 0);
		$elements[] =  array("Greater Tir County Hotsprings", "Greater Tir County", "Hot spring Springs Hotsprings", 647, 2850, 1650);
		$elements[] =  array("Greater Tir County", "in the northeast part of the world. Grid access in Tir City at 555 x 527 and a whompa in Tir City at 475 x 466 to Newland City. To the south of Greater Tir County is Tir County, west is Varmint Woods, no zones avail to north or east.", "Greatertircounty", 647, 0, 0);
		$elements[] =  array("Gridman", "at Fixer Grid, at the top", "Grid Man Gridman", 4107, 0, 0);
		$elements[] =  array("Ground Chief Vortexx", "Xan", "Vortex Vort", 6013, 525, 305);
		$elements[] =  array("Guardian Of Dissent", "North East of Dark Marshes, has low spawn rate and drop rate, and spawns in multiple locations", NULL, 4005, 2180, 890);
		$elements[] =  array("Guardian Of Tomorrow", "Temple Of The Three Winds, Greater Tir County", NULL, 647, 0, 0);
		$elements[] =  array("Harry`s Outpost", "Lush Fields. You can grid to Harry`s or take the west exit from Omni-Trade. Or take the teleportal from the west side of Pleasent Meadows.", "Harries Harry`s Harry;s Harrys Grid Outpost", 695, 2980, 3125);
		$elements[] =  array("Harry, Himself", "Harry`s Outpost, Lush Fields", NULL, 695, 0, 0);
		$elements[] =  array("Hawqana", "Inferno, 225 Incarnator", NULL, 4605, 3620, 2222);
		$elements[] =  array("Hebial", "Inferno, Frontier", "Hebiel", 4605, 2714, 2241);
		$elements[] =  array("Herbalist Geralt", "Rhinoman Village, Newland Desert", "AI Quest Social Clothing", 565, 2815, 1680);
		$elements[] =  array("Hezak The Immortal", "Inner Sanctum, 3rd Floor", NULL, 0, 0, 0);
		$elements[] =  array("High Commander Brock", "Tir, SE OP", NULL, 646, 2861, 738);
		$elements[] =  array("High Commander Fielding", "Wailing Wastes, NE corner", NULL, 551, 2700, 3500);
		$elements[] =  array("High Commander Frederickson", "Wailing Wastes", NULL, 551, 0, 0);
		$elements[] =  array("High Commander Hoover", "Wailing Wastes, NE corner", NULL, 551, 2700, 3500);
		$elements[] =  array("Holes In The Wall", "in the west central part of the world. No grid access and no whompa. North of Holes in the Wall is Athen Shire, south and east is Stret West bank, Borealis to the west.", NULL, 791, 0, 0);
		$elements[] =  array("Hollow Island Weed", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("Hope", "a neutral city in Mort. It has whompas to Stret West Bank and Newland Desert at 2888 x 1909", NULL, 560, 2888, 1909);
		$elements[] =  array("Horatio Campbell", "Omni Trade", NULL, 710, 300, 200);
		$elements[] =  array("Howling Minx", "Elysium, Near Ergo & Fallen Forest", NULL, 0, 0, 0);
		$elements[] =  array("Howling Predator", "Elysium, South of Remnans", NULL, 0, 0, 0);
		$elements[] =  array("ICC", "Andromeda. Whompas to ICC come from Newland, Omni Trade and Tir.", NULL, 655, 3250, 900);
		$elements[] =  array("Ian Warr", "Eastern Fouls Plain", NULL, 620, 720, 1380);
		$elements[] =  array("Iced Slither", "Adonis, Abyss N", NULL, 4873, 1398, 3470);
		$elements[] =  array("Ida Schuller", "Omni-Trade", NULL, 710, 0, 0);
		$elements[] =  array("Imelda Dane", "Newland City, Neuts r` Us club", "AI Quest Social Clothing Combined Armor", 566, 447, 340);
		$elements[] =  array("Incandescent Spirit", "Inferno, NW of Yuttos", NULL, 4005, 2408, 1347);
		$elements[] =  array("Incarnator QL225", "", "", 4605, 3390, 1998);
		$elements[] =  array("Incarnator QL255", "", "", 4605, 3224, 3068);
		$elements[] =  array("Inferno Redeemed Garden Access: Yutto Marshes Lord Galahad Statue", "Inferno", "Redeemed Lord Galahad Statue Garden of", 4605, 2625, 1190);
		$elements[] =  array("Inferno Unredeemed Garden Access: Dark Marshes Lord Mordeth Statue", "Inferno", "Lord Mordeth Statue Garden of Unredeemed Inferno Access", 4605, 2050, 715);
		$elements[] =  array("Inferno Unredeemed Garden Access: Inferno Barracks Lord Mordeth Statue", "Inferno", "Lord Mordeth Statue Garden of Unredeemed Inferno Access Barracks", 4605, 3021, 975);
		$elements[] =  array("Inferno Unredeemed Garden Access: Unredeemed Yutto Marshes Lord Mordeth Statue", "Inferno", "Lord Mordeth Statue Garden of Unredeemed Inferno Access Barracks", 4605, 2555, 1165);
		$elements[] =  array("Inferno Unredeemed Sanctuary Access: Oasis", "Inferno", "Lord Mordeth`s Sanctuary Statue Unredeemed Inferno", 4605, 2120, 1990);
		$elements[] =  array("Inferno Unredeemed Sanctuary Access: Sorrow Outlook", "Inferno", "Lord Mordeth`s Sanctuary Statue Unredeemed Inferno", 4605, 1385, 1525);
		$elements[] =  array("Inferno Unredeemed Sanctuary Access: Xark`s Lair", "Inferno", "Lord Mordeth`s Sanctuary Statue Unredeemed Inferno", 4605, 3135, 1895);
		$elements[] =  array("Information Officer Stiller", "Avalon, main OTAF base", NULL, 505, 1800, 1288);
		$elements[] =  array("Iniquitous Spirit", "Adonis, S", NULL, 4872, 1624, 499);
		$elements[] =  array("Inobak the Gelid", "Inner Sanctum, 3rd Floor", NULL, 0, 0, 0);
		$elements[] =  array("Instanced Pandemonium", "Team Instance in Pandemonium near the portal to Inferno", "ipande", 4328, 145, 34);
		$elements[] =  array("Inventor Bobic", "Tir County", NULL, 646, 1910, 1398);
		$elements[] =  array("Investigator Marciello", "Newland Desert, near the Meetmedere grid", "AI Quest Social Clothing Combined Armor Body Heat Pattern Analyzer.", 565, 1590, 2820);
		$elements[] =  array("Irascible Golem", "Adonis, 2315 x 665 SE and 865 x 1310 SW", NULL, 4872, 2315, 665);
		$elements[] =  array("Iron Reet", "Mutant Domain", NULL, 696, 857, 986);
		$elements[] =  array("Isham", "Inferno, South of Frontier", NULL, 4605, 2104, 1891);
		$elements[] =  array("Ishimk`Imk", "Inferno, 225 Incarnator", "Ishimkimk", 4605, 3573, 2168);
		$elements[] =  array("Iskop the Idolator", "Inner Sanctum, 3rd Floor", NULL, 0, 0, 0);
		$elements[] =  array("Ithaki", "Inferno, 255 Incarnator", "", 4605, 3129, 3096);
		$elements[] =  array("Iziris Agathon", "Omni-Trade, NE", NULL, 710, 0, 0);
		$elements[] =  array("Jack Legchopper", "Varmint Woods", NULL, 600, 0, 0);
		$elements[] =  array("Janella Gheron", "Cyborg Barracks, Greater Tir County", NULL, 647, 3200, 2300);
		$elements[] =  array("Jeuru the Defiler", "Inner Sanctum, 3rd Floor", NULL, 0, 0, 0);
		$elements[] =  array("Joo", "Omni Forest Area, Sunken Swamps", NULL, 716, 500, 2700);
		$elements[] =  array("Jukes", "Adonis, Abyss walking around near other Dryads", NULL, 4873, 0, 0);
		$elements[] =  array("Karl Berth", "Lower Scheol", NULL, 4881, 1172, 2051);
		$elements[] =  array("Kendric Kuzio", "Deep Artery Valley", NULL, 595, 1532, 999);
		$elements[] =  array("Kira Quinn", "Lower Scheol", NULL, 4881, 817, 1528);
		$elements[] =  array("Klapam Forest", "Stret East Bank", NULL, 635, 0, 0);
		$elements[] =  array("Lab Director", "The Longest Road, Foremans Office", NULL, 795, 1940, 775);
		$elements[] =  array("Lacerator Gunbeetle", "Pleasant Meadows (within a fairly large area of)", "Bronto", 630, 2405, 633);
		$elements[] =  array("Leading Blossom", "Upper Scheol", NULL, 4880, 304, 1114);
		$elements[] =  array("Leet Crater", "just south east of Omni-Pol Barracks region in Omni Forrest, which is just south outside Omni Entertainment`s City Gates.", NULL, 716, 0, 0);
		$elements[] =  array("Leo", "Pandemonium, West Node", NULL, 0, 0, 0);
		$elements[] =  array("Leona", "Newland, Bronto Burger", "Doll Rotten Peter Bacchante`s Fancy Yellow Hanging Lanterns Yellow Lanterns", 567, 0, 0);
		$elements[] =  array("Libra", "Pandemonium, Middle Node", NULL, 0, 0, 0);
		$elements[] =  array("Lien the Memorystalker", "Temple Of The Three Winds, Greater Tir County", NULL, 647, 0, 0);
		$elements[] =  array("Limber Dryad", "Elysium, Utopolis and South Elysium", NULL, 0, 0, 0);
		$elements[] =  array("Limnos", "Inferno, 255 Incarnator", NULL, 4605, 3121, 2972);
		$elements[] =  array("Limping Predator", "Elysium, South of Remnans", NULL, 0, 0, 0);
		$elements[] =  array("Live Metal", "Greater Tir County, Rocky Outcrops", "Livemetal", 647, 1520, 2210);
		$elements[] =  array("Ljotur The Lunatic", "Drill Island, Deep Artery Valley", NULL, 595, 1103, 722);
		$elements[] =  array("Lord Ghasap", "Avalon Dungeon, Avalon", NULL, 505, 2092, 3822);
		$elements[] =  array("Lord Of The Void", "Inferno, E of Frontier, W of Frontier. Nasc Wilds", NULL, 0, 0, 0);
		$elements[] =  array("Lost Soul", "Elysium, E Whispervale and 115 Inc", NULL, 0, 0, 0);
		$elements[] =  array("Lurking Dryad", "Adonis, NW", NULL, 4872, 1232, 2798);
		$elements[] =  array("Lush Fields", "located in the south central part of the world. Lush Fields has grid access at 1443 x 667 (Lush Hills Resort)and at Harry`s at 3115 x 3183. Ferrys to Harry`s at 3563 x 916, ferrys to PM OT outpost at 3391 x 797 and 3195 x 3178 and ferry to Omni Trade at 3295 x 2917. No whompa. NW is Andromeda, northeast is Milky Way, west is Clondyke, east is Pleasant Meadows. MutantDomain is located centrally on eastern border.", "Lush Feilds Fhields Field Fields Outpost Resort Forest Hill Hills Meadows Op Woods Lushfields North West Mines Nw Omni Mine Prime", 695, 1443, 667);
		$elements[] =  array("Majestik Woods", "Rome Blue, NE", NULL, 687, 400, 2520);
		$elements[] =  array("Malah-Fulcifera", "Inferno", "", 4605, 2127, 1861);
		$elements[] =  array("Mantis Queen", "Smuggler`s Den, Southern Fouls Hills", NULL, 615, 1749, 869);
		$elements[] =  array("Marcus Poet Laureate", "Broken Shores", NULL, 665, 1500, 2900);
		$elements[] =  array("Marcus Robicheaux", "Broken Shores", NULL, 665, 1500, 2900);
		$elements[] =  array("Marvin", "Southern Artery Valley", NULL, 610, 1250, 2350);
		$elements[] =  array("Mary-Ann", "Newland", "AI Quest Social Clothing Thermo Vest combined armor", 567, 1144, 388);
		$elements[] =  array("Master Divenchy", "Newland, near the crash site north of the lake", "AI Quest Social Clothing Combined Armor", 567, 900, 860);
		$elements[] =  array("Maychwaham", "Inferno, South of Frontier", "", 4605, 2068, 2155);
		$elements[] =  array("Maychwyawi", "Inferno", "", 4605, 2648, 2962);
		$elements[] =  array("Medusa Philanderer", "Inferno", "", 4605, 2567, 2937);
		$elements[] =  array("Metalomania", "Lush Fields, Harry`s Outpost", NULL, 695, 2980, 3125);
		$elements[] =  array("Mick Nugget McMullet", "Clondyke", "Mc Nugget Mcnugget Mick Mcmullet Nuggets", 670, 1100, 3700);
		$elements[] =  array("Milky Way Spaceship Crash Site", "Milky Way", NULL, 625, 3300, 700);
		$elements[] =  array("Milky Way", "located in the southeast part of the world. No grid access or whompa. North of Milky Way is S.Artery Valley, nw is Stret East Bank, west is Andromeda, sw is Lush Fields, south is Pleasant Meadows, SE is Eastern Foul Plains, NE is Belial Forest.", NULL, 625, 0, 0);
		$elements[] =  array("Miner Beetles", "Pleasant Meadows", NULL, 630, 1100, 2300);
		$elements[] =  array("Misa Ramirez", "Newland City, Neuts r` us club", "AI Quest Social Clothing Combined Armor", 566, 447, 340);
		$elements[] =  array("Mitaar Hero", "Xan", "Technomaster Sinuh", 6013, 345, 407);
		$elements[] =  array("Molested Molecules", "Condemned Subway", NULL, 0, 0, 0);
		$elements[] =  array("Monday Kline", "at Stret E at TRA only", NULL, 635, 1300, 890);
		$elements[] =  array("Mooching Dryad", "Adonis, NW", NULL, 4872, 986, 2573);
		$elements[] =  array("Moog", "Southern Artery Valley", NULL, 610, 1850, 2550);
		$elements[] =  array("Morgan Le Faye", "Avalon Dungeon, Avalon, down the stairs where the PvP zone starts, and to the right", NULL, 505, 2092, 3822);
		$elements[] =  array("Mort", "located in the northeast part of the world. It has whompas to Stret West Bank and Newland Desert at 2888 x 1909. Grid access is at 1928 x 1255 (Sentinels). There is no zones available to the north or west of Mort, to the south is Newland, to the east is Perpetual Wastelands.", "Mort Crater Dungeon Sentinel Base Outpost Sentinels", 560, 1928, 1255);
		$elements[] =  array("Morty", "Tir County, Kuroshio Forest", NULL, 646, 300, 1200);
		$elements[] =  array("Mull", "Adonis, Abyss walking around near other Dryads", NULL, 4873, 0, 0);
		$elements[] =  array("Mutant Domain", "located in the west central part of the world. No grid access or whompa. To the north, south and west of Mutant Domain is Lush Fields, east is Pleasant Meadows. This zone lies centrally on the eastern border of Lush Fields.", "Mutant Domain Swamp Village Mutantdomain", 696, 0, 0);
		$elements[] =  array("Natsmaahpt", "Inferno", "", 4605, 3057, 1348);
		$elements[] =  array("Neleb The Deranged", "Omni Forest, at the very end of the Steps of Madness", NULL, 716, 800, 2844);
		$elements[] =  array("Nelly Johnson", "Eastern Fouls Plain", NULL, 620, 720, 1380);
		$elements[] =  array("Nematet The Custodian Of Time", "Temple Of The Three Winds, Greater Tir County", NULL, 647, 0, 0);
		$elements[] =  array("Netrom", "Southern Artery Valley (a small outpost type ruins)", NULL, 610, 1988, 606);
		$elements[] =  array("Neutral Trader Shop", "20K Outpost, Pleasant Meadows (Whompah)", "", 630, 1190, 2350);
		$elements[] =  array("Neutral Trader Shop", "Borealis (West - HItech)", "", 800, 652, 576);
		$elements[] =  array("Neutral Trader Shop", "Harrys Outpost, Lush Fields (SW of Grid - Supplies)", "", 695, 3040, 3030);
		$elements[] =  array("Neutral Trader Shop", "Newland City (West - Supplies)", "", 566, 290, 315);
		$elements[] =  array("Neuts R Us", "Newland City. A club. Most Player Cities have a Whompah to here.", "Neuters R Us Neutsrus", 566, 447, 340);
		$elements[] =  array("Neverta Canyon", "", "", 6013, 725, 562);
		$elements[] =  array("Newland City", "in the north east part of the world. It has a grid access point outside city west gate at 1172 x 482 and whompas to the ICC, Tir, and Borealis at 390 x 300. To the north, east, south and west of Newland City is Newland.", NULL, 566, 390, 300);
		$elements[] =  array("Newland Desert", "in the northeast part of the world. It has a grid access point at 1172 x 482 and whompas to Newland City and Hope at 2200 x 1575. To the north is Newland, to the south is Varmint Woods. There are currently no zones available to the east or west.", "Meetmedeer Meetmeder Meetmedere Metmedere Newland Dessert Newlanddesert", 565, 2200, 1575);
		$elements[] =  array("Newland", "in the northeast part of the world. Grid access at 1527 x 2767 (Meetmedere), whompas inside Newland City to ICC, Newland Desert, and Borealis at 390 x 300. To the north of Newland is Mort, to the south is Newland Desert. Currently no zones available to the east or west.", NULL, 567, 0, 0);
		$elements[] =  array("Ninth Brood Champion", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("Nippy Slither", "Adonis, Abyss N", NULL, 4873, 1375, 3472);
		$elements[] =  array("Nodda Gregg", "Tir County", NULL, 646, 1933, 1494);
		$elements[] =  array("Nolan Deslandes", "Neuters R Us, Newland City", NULL, 566, 447, 340);
		$elements[] =  array("Notum Cannons", "Clondyke", NULL, 670, 1200, 3400);
		$elements[] =  array("Notum Profundis", "Eastern Fouls Plain", NULL, 620, 773, 1430);
		$elements[] =  array("Notum Soldier", "Eastern Fouls Plain", NULL, 620, 2000, 2400);
		$elements[] =  array("Notum Trainee", "Eastern Fouls Plain", NULL, 620, 2000, 2400);
		$elements[] =  array("Notum Tree", "Avalon", NULL, 505, 2450, 1300);
		$elements[] =  array("Noxious Eremite", "Inferno", "", 4605, 2615, 2233);
		$elements[] =  array("Numiel", "Inferno, Fronter", NULL, 4605, 2821, 2454);
		$elements[] =  array("Nuts & Bolts", "Aegean, Wartorn Valley", NULL, 585, 790, 680);
		$elements[] =  array("Nyame`s Abettor", "", "", 4005, 717, 445);
		$elements[] =  array("Nyame`s Drudge", "", "", 4005, 1851, 1761);
		$elements[] =  array("Obediency Inspector", "Eastern Fouls Plain, at the lake", NULL, 620, 1225, 2800);
		$elements[] =  array("Obsolete Soul Dredge", "Inferno", "", 4605, 3149, 3217);
		$elements[] =  array("Ofoz", "Newland city, to avoid the attention of the Unicorn forces. You can find him near the north city gate by the mission terminals.", NULL, 566, 0, 0);
		$elements[] =  array("Omni Forest", "in the southeast part of the world. No grid access or whompa. North of Omni Forest is Pleasant Meadows, northeast is Eastern Foul Plains, east us Southern Foul Plains. To the west is Omni Entertainment.", "Omni Forrest ", 716, 0, 0);
		$elements[] =  array("Omni Trader Shop", "Omni-1 Entertainment (SE - Big Yalm)", "", 705, 845, 430);
		$elements[] =  array("Omni Trader Shop", "Omni-1 Trade (NW - Finest Edition)", "", 710, 230, 490);
		$elements[] =  array("Omni Trader Shop", "Rome Blue district (West Wall)", "", 735, 540, 330);
		$elements[] =  array("Omni Trader Shop", "Rome Green district (East Wall)", "", 740, 410, 340);
		$elements[] =  array("Omni-1 Entertainment", "in the southeast part of the world. It has a grid access point at 879 x 579 and 582x337. Whompa in the north east of town at 890 x 671 lead to 20K. Whompas in south east at 900 x 470 lead to Omni-1 Trade and Rome Red.", "Omni Entertainment, Omni Ent", 705, 0, 0);
		$elements[] =  array("Omni-1 HQ", "in the southeast part of the world. It has a grid access point at 602 x 468.", "Hq Omni Hq", 700, 602, 468);
		$elements[] =  array("Omni-1 Trade", "in the southeast part of the world. It has a grid access point at 407 x 575. Whompas to ICC, Omni Entertainment, and Galway Castle at 370x380. Out the east gate is Omni1 HQ. Out the west gate is Lush Fields.", "Omni Trade", 710, 0, 0);
		$elements[] =  array("Omni-Pol Command Juggernaut", "Primary Base, Avalon. E-W Road Mutant Domain", NULL, 505, 0, 0);
		$elements[] =  array("Omni-Tek Mission Agency", "Rome Blue, Center", "daily mission freelancers", 735, 658, 314);
		$elements[] =  array("One Who Asks The Unasked", "Inferno, Valley of the Dead, under a tent", "", 4005, 1194, 759);
		$elements[] =  array("One Who Is Full Of Compassion", "Lower Scheol", NULL, 4881, 1359, 1917);
		$elements[] =  array("One Who Is Invited Last", "", "", 4005, 2483, 1187);
		$elements[] =  array("One Who Learns The Past", "Inferno, just north of the portal to Penumbra", NULL, 4005, 906, 182);
		$elements[] =  array("One Who Talks With The Past", "Inferno, in the Valley of the Dead, under a tent", NULL, 4005, 1194, 759);
		$elements[] =  array("One Whose Words Happen To Rhyme", "Inferno, just north of the portal to Penumbra", NULL, 4005, 906, 182);
		$elements[] =  array("Operator Bhotaar-Bhotaar Roch", "The Garden of Roch", NULL, 4683, 320, 341);
		$elements[] =  array("Oscar", "Greater Omni Forest", NULL, 717, 0, 0);
		$elements[] =  array("Ossuz", "Elysium, inside mountains SW and NW", NULL, 0, 0, 0);
		$elements[] =  array("Outpost 10-3", "Southern Artery Valley, with whompas to Galway Castle, 2HO, and 20K", "Outpost 10 3 Outpost 103", 610, 1150, 2340);
		$elements[] =  array("Ownz", "Tir County, Crownhead Forrest", NULL, 646, 2200, 700);
		$elements[] =  array("Pained Predator", "Elysium, South of Remnans", NULL, 0, 0, 0);
		$elements[] =  array("Patricia Johnson", "Ace Camp, Eastern Fouls Plain", NULL, 620, 720, 1380);
		$elements[] =  array("Paxos", "Inferno", "", 4605, 3143, 3168);
		$elements[] =  array("Peacekeeper Constad", "", "dustbrigade db", 655, 3277, 922);
		$elements[] =  array("Pendpod Trapper", "Pleasant Meadows (within a fairly large area of)", "Bronto", 630, 890, 1689);
		$elements[] =  array("Penelopez Magistrale", "Newland Desert. Multi-grip Soles AI Quest Social Clothing", "AI Quest Social Clothing Combined Armor Multi-grip Soles", 565, 790, 2310);
		$elements[] =  array("Peristaltic Abomination", "Adonis, Abyss East & West", NULL, 4873, 0, 0);
		$elements[] =  array("Peristaltic Aversion", "Adonis, Abyss East & West", NULL, 4873, 0, 0);
		$elements[] =  array("Perpetual Wastelands", "in the northeast part of the world. It has no grid access point and no whompa. To the west is Mort and there are currently no zones available to the east, north or south.", "Perpetual Wasetlands Perpetual Waste Lands", 570, 0, 0);
		$elements[] =  array("Peter Lee", "Ace Camp, Eastern Fouls Plain", NULL, 620, 720, 1380);
		$elements[] =  array("Phatmos", "Inferno, West of Razors Lair", NULL, 4605, 1516, 2807);
		$elements[] =  array("Pietro Molla", "Aegean", "Quest Wedding Ring Omni-Tek Trash Can", 585, 535, 345);
		$elements[] =  array("Pisces", "Pandemonium, Middle Node", NULL, 0, 0, 0);
		$elements[] =  array("Pit Demon", "Crypt of Home, Broken Shores", NULL, 0, 0, 0);
		$elements[] =  array("Pleasant Meadows", "in the southeast part of the world. It has a ferry grid to Harry`s at 360x1568, grid ferry to Omni Outpost in Lush Fields at 360 x 1565. Whompas at 1261 x 2300 to Outpost 10-3, OmniEntertainment, and 4HOles . North is Milky Way, south is Omni Forest, east is Eastern Foul Plains, west is Lush Fields.", "20k Pleasant Fields Pleasent Meadows Pleasant Meadow Pleasent Meadow", 630, 1261, 2300);
		$elements[] =  array("Polly", "Omni Forest, Swamp River Delta and Northern Drylands", NULL, 716, 450, 1280);
		$elements[] =  array("Polymorphed Lunatic", "Drill Island, Deep Artery Valley", NULL, 595, 1103, 722);
		$elements[] =  array("Powa", "Greater Tir County", NULL, 647, 800, 2400);
		$elements[] =  array("Primus Outlaw", "Primus Camp, Eastern Fouls Plain", NULL, 620, 0, 0);
		$elements[] =  array("Primus Scrap Pillager", "Primus Camp, Eastern Fouls Plain", NULL, 620, 0, 0);
		$elements[] =  array("Professor Van Horn", "Newland Desert", NULL, 565, 2900, 1600);
		$elements[] =  array("Prototype Inferno", "Cyborg Barracks, Greater Tir County", NULL, 647, 3232, 2340);
		$elements[] =  array("Punctilious Hiathlin", "Inferno, 255 Incarnator", "Punctlious", 4605, 3332, 2968);
		$elements[] =  array("Pursued Spirit", "Inferno", "", 4005, 722, 1063);
		$elements[] =  array("Putrid Eremite", "Inferno, 225 Incarnator", NULL, 4605, 3434, 1889);
		$elements[] =  array("Pyiininnik`s Shadow", "", "", 4605, 3240, 1689);
		$elements[] =  array("Pyiininnik", "Inferno", "", 4605, 3325, 1684);
		$elements[] =  array("Qi Qiao Jie", "Borealis. He sells valentines items.", NULL, 800, 720, 675);
		$elements[] =  array("Quintus Romulus", "Foremans Office", NULL, 0, 0, 0);
		$elements[] =  array("R-2000 Vermin Disposal Unit", "Greater Tir County, Brainy Ant Woods", "R 2000 Vermin Disposal Unit R2000 Vermin Disposal Unit", 647, 800, 1800);
		$elements[] =  array("Ramon Bauer", "Old Athen", "Bazzit`s Alien Library Linked Hacker Tool Vanguard Node Access Card Kyr`Ozch Structural Analyzer", 540, 500, 565);
		$elements[] =  array("Razor the Battletoad", "Inferno, Razor`s Lair", "", 4605, 1953, 2609);
		$elements[] =  array("Red", "Aegean", NULL, 585, 650, 1450);
		$elements[] =  array("Redeemed Temple Inferno", "", "", 4605, 2320, 3242);
		$elements[] =  array("Reet Retreat", "Stret West Bank, Last Ditch. A club.", "Leet Retreat Reet Retrat", 790, 1206, 2807);
		$elements[] =  array("Rhino Cockpit", "Newland Desert. You can get there by going NE from Newland City, zone and continue NE once in Newland Desert. Rhino Cockpit has become a pretty popular site for L 25-35 groups", "Rhino Pit", 565, 3140, 1920);
		$elements[] =  array("Rhompa Bar", "Omni-Ent", "Rhompa Club", 705, 714, 698);
		$elements[] =  array("Richelieu", "Southern Artery Valley", NULL, 610, 1850, 2550);
		$elements[] =  array("Ris Lee", "Ace Camp, Eastern Fouls Plain", NULL, 620, 720, 1380);
		$elements[] =  array("Rising Sun", "Aegean, south of Wartorn Valley", "Risingsun", 585, 0, 0);
		$elements[] =  array("Robin Raag", "Smuggler`s Den, behind Ash at 21 x 217", NULL, 123, 1749, 869);
		$elements[] =  array("Rome Blue", "in the southwest part of the world. Out the west gates is Rome Red where the whompas are. Out the east gate is Galway Shire.", "Omni Blue", 735, 647, 315);
		$elements[] =  array("Rome Green", "in the southwest part of the world. Out the east gate is Rome Red where the whompas are.  From SL there is a portal that is located very near some shops.", "Romegreen", 740, 0, 0);
		$elements[] =  array("Rome Red Grid", "", "rrg", 730, 251, 318);
		$elements[] =  array("Rome Red", "in the southwest part of the world. It has a grid access point at 251 x 318. Whompas at 350x315 to Omni Entertainment, Galway Castle, and Broken Shores. Rome Blue is to the east and Rome Green is to the west.", NULL, 730, 309, 314);
		$elements[] =  array("Ron McBain", "Stret East Bank, at the 2HO Outpost", NULL, 635, 750, 1700);
		$elements[] =  array("Rotting Eremite", "Inferno, 225 Incarnator", NULL, 4605, 3369, 1898);
		$elements[] =  array("Sabulum", "Perpetual Wastelands. It is a neutral town.", NULL, 570, 1050, 2400);
		$elements[] =  array("Sadistic Soul Dredge", "Elysium, E Whispervale and 115 Inc", NULL, 0, 0, 0);
		$elements[] =  array("Sagittarius", "Pandemonium, North Node", NULL, 0, 0, 0);
		$elements[] =  array("Salahpt", "Inferno, South of Frontier", NULL, 4605, 2140, 2644);
		$elements[] =  array("Sally Tall", "Meetmedere, in a small pocket of 75% gas", NULL, 565, 1480, 2760);
		$elements[] =  array("Sam Chin", "Tir County, located in Inquisitive Wasp, southeast of Tir City. Deliver the supply crate from Genghis Pan.", NULL, 646, 2725, 620);
		$elements[] =  array("Sanatsimk", "Inferno", "", 4605, 3071, 1503);
		$elements[] =  array("Scalding Weaver", "in Sorrow Pass (the chasm near sorrow), this has a very large spawn area so look all around for them as well including outside the pass", NULL, 4005, 1450, 1425);
		$elements[] =  array("Scary Spider", "Inferno, SE of 255 Incarnator", "", 4605, 3494, 2772);
		$elements[] =  array("Scientist Maud Stevens", "Borealis, up by the Radar Dish", "AI Quest Social Clothing combined armor", 800, 360, 405);
		$elements[] =  array("Scorpio", "Pandemonium, North Node", NULL, 0, 0, 0);
		$elements[] =  array("Scratching Soul Dredge", "Adonis, NE", NULL, 4872, 2666, 2562);
		$elements[] =  array("Screeching Imp", "Elysium, Near Ergo", NULL, 0, 0, 0);
		$elements[] =  array("Scrupulous Hiathlin", "Inferno, 255 Incarnator", "", 4605, 3386, 3127);
		$elements[] =  array("Second Brood Champion", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("Sentinel Commander Higgins", "Tir City", NULL, 640, 500, 500);
		$elements[] =  array("Seventh Brood Champion", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("Shy Eremite", "Inferno, South-East of Sorrow (straight south of Anansi Devotee`s) around", NULL, 4605, 1650, 1150);
		$elements[] =  array("Silent Spider", "Inferno", "", 4605, 3208, 3529);
		$elements[] =  array("Simon Stark", "Newland Desert", "AI Quest Social Clothing Antiseptic Protector Combined Armor", 565, 1155, 1747);
		$elements[] =  array("Sinful Soul Dredge", "Adonis, 1581 x 595 S and 1741 x 2198 N", NULL, 4872, 1581, 595);
		$elements[] =  array("Sipius Aban Lux-Wel", "Garden of Aban, Nascense.", "Quest Clan Garden Key Alban Redeemed", 4676, 465, 495);
		$elements[] =  array("Sirocco", "Old Athen", NULL, 540, 210, 215);
		$elements[] =  array("Sixth Brood Champion", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("Skulking Dryad", "Adonis, NW", NULL, 4872, 1113, 2639);
		$elements[] =  array("Skylight", "Adonis, Abyss N", NULL, 4873, 1600, 2075);
		$elements[] =  array("Slinking Dryad", "Adonis, West and NW Island", NULL, 4872, 0, 0);
		$elements[] =  array("Smokey Willy", "Omni-Ent, in the north western corner of sewers", NULL, 705, 472, 1043);
		$elements[] =  array("Smoky Salamander", "Is One Of Many Spots In Burning Marshes", NULL, 4005, 1650, 1260);
		$elements[] =  array("Smoldering Shadow", "near the petrified hecklers near Sorrow, around 1081, 1160 and can also be find north of there around 759 x 1436", NULL, 4005, 1081, 1160);
		$elements[] =  array("Smuggler`s Den", "in the southeast corner of the world, in Southern Fouls Hills at 1755 x 872.", "Smuggler Den", 615, 1755, 872);
		$elements[] =  array("Snaking Dryad", "Adonis, NW", NULL, 4872, 868, 2503);
		$elements[] =  array("Snatching Soul Dredge", "Adonis, Dead Ends Ark", NULL, 4872, 0, 0);
		$elements[] =  array("Somphos Argeele", "North West of Dark Marshes and spawns around a large rock", NULL, 4005, 1770, 900);
		$elements[] =  array("Somphos Argef", "North West of Dark Marshes and spawns around a large rock", NULL, 4005, 1770, 900);
		$elements[] =  array("Somphos Sorlivet", "North West of Dark Marshes and spawns around a large rock (Spawns on the East Side of the rock)", NULL, 4005, 1770, 900);
		$elements[] =  array("South Fouls Hills", "in the southeast corner of the world. No grid and no whompa.North of S.Fouls Hills is Eastern Foul Plains, west is Omni Forest, northwest is Pleasant Meadows. No zones avail to south or east.", NULL, 615, 0, 0);
		$elements[] =  array("Southern Artery Valley", "in the mideastern part of the world. No grid access. Whompas to Galway Castle, 2HO, and 20K at 1150 x 2340. North of S.Artery Valley is Central Artery Valley, south is Milky Way (but a force field will not allow you to zone south), west is Stret East Bank and east is (ne) Deep Artery Valley (se) Belial Forest.", NULL, 610, 1150, 2340);
		$elements[] =  array("Special Agent Lamb", "Deep Artery Valley", NULL, 0, 0, 0);
		$elements[] =  array("Spetses", "Inferno, 255 Incarnator", NULL, 4605, 3261, 3163);
		$elements[] =  array("Spirit Of Disruption", "Inferno, among the ruins just north of the portal to Penumbra", NULL, 4605, 1015, 205);
		$elements[] =  array("Splintered Girder", "Elysium, West wall of Central Ely", NULL, 0, 0, 0);
		$elements[] =  array("Stanley Adams", "Varmint Woods", NULL, 600, 3850, 1900);
		$elements[] =  array("Stark", "Adonis, Abyss N", NULL, 4873, 1701, 2321);
		$elements[] =  array("Stealing Dryad", "Adonis, NW", NULL, 4872, 920, 2437);
		$elements[] =  array("Steele Filar", "Inferno, NE of Frontier", NULL, 4605, 2736, 2936);
		$elements[] =  array("Stephen Richards", "", "", 4005, 2483, 1187);
		$elements[] =  array("Steps Of Madness", "in the southeast part of the world, in Omni Forest at 800 x 2800. Leave Omni Ent by the east gate to reach the dungeon. Recommended for teams of lvl 35-45.", "Step Of Madness Madness Dungeon", 716, 800, 2800);
		$elements[] =  array("Stolt Jensenberg", "At Stolt`s Trading Outpost near the Whom-pahs in Newland Desert", "Jens Stoltenberg", 565, 2172, 1550);
		$elements[] =  array("Stoltz Outpost", "just across the Newland Desert zone line in Newland. It has food machines only.", NULL, 565, 2172, 1543);
		$elements[] =  array("Stret East Bank", "in the mid-west part of the world. Grid access in 2HO at 667 x 1638, whompas at 783 x 1599 to Outpost 10-3 and 4 Holes. Ferry to 4 HOles at 820 x 1977. To the north of Stret East Bank is Upper Stret East Bank, to the northwest is Stret West Bank, to the northeast is Central Artery Valley, to the southwest is Andromeda, to the southeast is Milky Way, to the east is Southern Artery Valley and to the west is 4 Holes.", "Stret Eastbank", 635, 783, 1599);
		$elements[] =  array("Stret West Bank", "in the west central part of the world. It has a ferry to Stret East Bank at 1141 x 529 and whompa at 1279 x 2894 (in Last Ditch) to Borealis and to Hope. North is Aegean(ne) and Athen Shire(nw), south is 4HOles, east is Upper Stret East Bank, west is Borealis.", "Stret Westbank", 790, 1279, 2894);
		$elements[] =  array("Strike Foreman", "Condemned Subway, usually on the bridge", NULL, 0, 0, 0);
		$elements[] =  array("Striking Ant Tir Outpost", "Tir County. The outpost has mission terms, shops, banks and insure terms.", NULL, 646, 1915, 1492);
		$elements[] =  array("Stumpy", "Greater Omni Forest", NULL, 717, 2000, 1300);
		$elements[] =  array("Suir-Katan, The Custodian", "", "", 4605, 2500, 2527);
		$elements[] =  array("Supply Master Eel", "Avalon, Secondary Base", "Supplymaster Eel", 505, 900, 1600);
		$elements[] =  array("Supply Master Smug", "Wailing Wastes, NE corner", "Supplymaster Smug", 551, 2700, 3500);
		$elements[] =  array("Susan Furor", "at Poole/Galway County at AGT only", NULL, 685, 1219, 1940);
		$elements[] =  array("Swirling Eremite", "Elysium, Sand dunes near Cold Rock", NULL, 0, 0, 0);
		$elements[] =  array("Syros", "Inferno", "", 4605, 3208, 2975);
		$elements[] =  array("T.I.M.", "Foremans, The Longest Road", NULL, 795, 2000, 800);
		$elements[] =  array("Tarasque", "Avalon Dungeon, Avalon", NULL, 505, 2092, 3822);
		$elements[] =  array("Taurus", "Pandemonium, Middle Node", NULL, 0, 0, 0);
		$elements[] =  array("Tdecin", "Elysium, inside mountains SW and NW", NULL, 0, 0, 0);
		$elements[] =  array("Tearing Soul Dredge", "Adonis, NE", NULL, 4872, 2763, 2638);
		$elements[] =  array("Techleader Praetor", "Cyborg Barracks, Greater Tir County", NULL, 647, 3232, 2340);
		$elements[] =  array("Technologist Frank Jobin", "Lower Scheol", NULL, 4881, 1066, 1813);
		$elements[] =  array("Temple Of The Three Winds", "Greater Tir County, out the Tir west gate and go north. There is a shortcut teleportal in the SE part of Rome Green near 420 x 240 (behind some red boxes). You must be L 60 or below to enter.", "Totw", 647, 420, 240);
		$elements[] =  array("Tenth Brood Champion", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("The Beast", "Pandemonium", NULL, 0, 0, 0);
		$elements[] =  array("The Broken Falls", "Broken Shores", NULL, 665, 1450, 3100);
		$elements[] =  array("The Brood Mother", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("The Carbon Crystal", "Southern Artery Valley", NULL, 610, 2600, 2900);
		$elements[] =  array("The Collector", "", "", 4328, 142, 23);
		$elements[] =  array("The Cup", "West Athens, directly beside the grid access.  A quiet little club, the Red Tigers hold their weekly meeting there every Sunday at 20:00 GMT.", "thecup", 545, 452, 415);
		$elements[] =  array("The Curator", "Temple Of The Three Winds, Greater Tir County", NULL, 647, 0, 0);
		$elements[] =  array("The Enigma House", "Central Artery Valley", NULL, 590, 1900, 1400);
		$elements[] =  array("The Eremite Statue", "Deep Artery Valley", NULL, 595, 1300, 2300);
		$elements[] =  array("The Essence Of Primal Understanding", "Inferno, Burning Marshes", NULL, 4005, 1975, 1115);
		$elements[] =  array("The Fixer Grid", "accessable from any grid post. You must first complete the Fixer quest, or get a L100+ fixer to help you get inside.", NULL, 4107, 0, 0);
		$elements[] =  array("The Fixer Shop", "Borealis. It looks like a pile of junk and you need 180 B&E to use it as well as being a fixer :)", "Fixershop", 800, 440, 400);
		$elements[] =  array("The Forestwatch Trees", "Southern Fouls Hills", NULL, 615, 1650, 1650);
		$elements[] =  array("The Happy Rebel", "Tir City", NULL, 640, 550, 550);
		$elements[] =  array("The Hollow Island Suzerain", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("The Iron Reet", "Mutant Domain", NULL, 696, 857, 986);
		$elements[] =  array("The Longest Road", "in the northwest part of the world. No grid access, has woompa access to Avalon, Athen Old, and Broken Shores at 3700 x 1615 in the town of Bliss. Athen Shire is to the east. No zones to the north, south, or west. There is a neutral outpost at 3650 x 560 with an ICC scanner and more. Biomare (dungeon) is located at 1930 x 775", "Logest Road lLngest Raod", 795, 0, 0);
		$elements[] =  array("The Nightheart", "Pandemonium", NULL, 0, 0, 0);
		$elements[] =  array("The Obediency Enforcer", "Eastern Fouls Plain, at the lake", "Obediency Inspector OE", 620, 1225, 2800);
		$elements[] =  array("The One (Babyface)", "Southern Fouls Hills", NULL, 615, 2250, 1810);
		$elements[] =  array("The One Who Sees Dead People", "", "", 4005, 1176, 672);
		$elements[] =  array("The Outzone (AKA APF)", "is a raid zone for L180+ characters. To enter the Outzone, board the Unicorn Transport Shuttle found in Andromeda outside the ICC. Or use a <a href='itemref://260424/260424/1'>Decrypted Kyr`Ozch Data Core</a> when you`re near the ICC or Unicorn Shuttle Boarding Zone.", "Apf Icc", 655, 3440, 1300);
		$elements[] =  array("The Pest", "Deep Artery Valley", NULL, 595, 1360, 2300);
		$elements[] =  array("The Re-animator", "Temple Of The Three Winds, Greater Tir County", NULL, 647, 0, 0);
		$elements[] =  array("The Retainer Of Ergo", "", "", 4605, 2807, 3377);
		$elements[] =  array("The Satellite Dish", "Borealis", NULL, 800, 350, 350);
		$elements[] =  array("The Trash King", "Athen Shire", "Trash King TK", 550, 1600, 940);
		$elements[] =  array("Third Brood Champion", "Hollow Island", NULL, 605, 0, 0);
		$elements[] =  array("Tinos", "Inferno, West of Razors Lair", NULL, 4605, 1605, 2503);
		$elements[] =  array("Tiny", "Adonis, Abyss", NULL, 4873, 1691, 1504);
		$elements[] =  array("Tir County", "in the northeast part of the world. Grid access and whompa in Tir City. To the north is Greater Tir County, south is Deep Artery Valley (but a force field blocks you from zoning south), west is Varmint Woods, southeast is Central Artery Valley, no zones avail to east.", NULL, 646, 0, 0);
		$elements[] =  array("Tir", "located in the north east part of the world. It has a grid access point at 555 x 527 and a whompas at 475 x 466 to Varmint Woods, ICC, and Athen. To the north, south, east and west of Tir is Tir County.", NULL, 640, 555, 527);
		$elements[] =  array("Tiunissik`s Shadow", "Inferno", "", 4005, 1597, 2051);
		$elements[] =  array("Tiunissik", "Inferno, North of Sorrow", NULL, 4005, 1600, 1852);
		$elements[] =  array("Torrid Spirit", "Inferno, North of Sorrow", NULL, 4005, 1475, 2143);
		$elements[] =  array("Torrith The Ancient", "Greater Tir County", NULL, 647, 400, 2200);
		$elements[] =  array("Trap", "Adonis, Abyss", NULL, 4873, 1718, 1460);
		$elements[] =  array("Trash King Lackey", "Athens Shire", "Trashkinglackey", 550, 1600, 940);
		$elements[] =  array("Trash King", "Athen Shire, Junkyard outside West Athen", NULL, 550, 1600, 1000);
		$elements[] =  array("Tri Plumbo", "The Longest Road, The Foremans Office", "Triplumbo", 795, 1940, 775);
		$elements[] =  array("Tribo Ratcatcher", "West Athens", "catcher rat ratcatcher tribo", 545, 325, 362);
		$elements[] =  array("Trip", "Adonis, Abyss", NULL, 4873, 1766, 1489);
		$elements[] =  array("Trup", "Adonis, Abyss", NULL, 4873, 1729, 1535);
		$elements[] =  array("Tsunayoshi Smith", "Southern Artery Valley, near the Largest Soul Fragment.", "Melee Shop", 610, 2600, 2900);
		$elements[] =  array("Tuq`usk", "Inferno, South of Frontier", "Tuqusk", 4605, 2283, 2069);
		$elements[] =  array("Turk", "Adonis, Abyss N", NULL, 4873, 1755, 2184);
		$elements[] =  array("Twin Altars", "Broken Shores", NULL, 665, 400, 2250);
		$elements[] =  array("Tyro Beasthandler", "Inferno, 255 Incarnator", NULL, 4605, 3366, 3049);
		$elements[] =  array("Uklesh The Frozen", "Temple Of The Three Winds, Greater Tir County", NULL, 647, 0, 0);
		$elements[] =  array("Uncle Bazzit", "Newland Desert, in his workshop (Meetmedere grid exit)", "AI Quest Social Clothing", 565, 1545, 2725);
		$elements[] =  array("Unicorn Landing Beacon", "Andromoda, Nepal, north of ICC HQ", NULL, 655, 3440, 1308);
		$elements[] =  array("Unredeemed Temple Inferno", "Inferno", "", 4605, 3724, 3414);
		$elements[] =  array("Upper Stret East Bank", "in the central part of the world. No grid access or whompa. To the north (w) of Upper Stret East Bank is Aegean and north (e) Varmint Woods, to the east is Central Artery Valley, to the west is Stret West Bank, southeast is 4Holes, south is Stret East Bank, se is S.Artery Valley.", "Upper East Stret Bank Upperstreteastbank Upper Stret Eastbank", 650, 0, 0);
		$elements[] =  array("Ushamaham", "Inferno, South of Frontier", "", 4605, 2069, 2219);
		$elements[] =  array("Ushap`ing", "Inferno", "", 4605, 3509, 1417);
		$elements[] =  array("Varmint Woods", "in the southeast part of the world. No grid but there are whompa`s to Tir, Wine, and Wailing Wastes at 2484 x 2106. To the north of Varmnit Woods is Newland Desert, to the south is Central Artery Valley and Stret East bank, east is Greater Tir County, west is Aegean", "Vermint Woods Varmint Wood Varmintwoods", 600, 2484, 2106);
		$elements[] =  array("Vergil Aeneid", "Condemned Subway", NULL, 0, 0, 0);
		$elements[] =  array("Victor Nonya", "Inferno, near Oasis. Spirits quest boss.", "", 4605, 2138, 1878);
		$elements[] =  array("Vile Spirit", "Adonis, 1541 x 500 S and 1751 x 2244 N", NULL, 4872, 0, 0);
		$elements[] =  array("Virgo", "Pandemonium, West Node", NULL, 0, 0, 0);
		$elements[] =  array("Wailing Wastes", "in the northwest part of the world. No grid access, but has whompas at 1370 x 1735 to Athens, Avalon, and Varmit Woods. To the north is Avalon, to the south is Athen Shire, no zones to east and west. Clan OP with scanner at 2430 x 3380", "WW Waleing Wastes Waling Waste Wailingwastes", 551, 1370, 1735);
		$elements[] =  array("Waning Soul", "Elysium, E Whispervale and 115 Inc", NULL, 0, 0, 0);
		$elements[] =  array("Wartorn Valley", "in the northeast part of the world. No grid or whompa. To the north, east and west of Wartorn Valley is Aegean, to the south is gate to Athen Old.", "Warton Valley Wartornvalley", 586, 0, 0);
		$elements[] =  array("Waywaqa", "Inferno", "", 4605, 3275, 1454);
		$elements[] =  array("Weakened Chimera", "Inferno, East of the Portal to Pen, around the +10 ring dungeon", NULL, 4005, 1090, 400);
		$elements[] =  array("Wicked Soul Dredge", "Adonis, NE", NULL, 4872, 1530, 547);
		$elements[] =  array("Will To Fight", "Stret West Bank, east of Reet Retreet. You must be L75 or above to enter.", "Pvp Dungeon", 790, 2245, 3124);
		$elements[] =  array("Windcaller Karrec", "ICC (S in a shipping container)", "TOTW Temple Of The Three Winds", 655, 3212, 789);
		$elements[] =  array("Windcaller Yatilla", "Temple Of The Three Winds, Greater Tir County", NULL, 647, 0, 0);
		$elements[] =  array("Wine", "Belial Forest (east side of the world) with whompas to Broken Shores and Varmint Woods at 2150 x 2319.  A clan town.", NULL, 605, 2150, 2319);
		$elements[] =  array("Wounded Predator", "Elysium, South of Remnans", NULL, 0, 0, 0);
		$elements[] =  array("Xark the Battletoad", "Inferno, Xarks Lair, NE of Yuttos", "", 4605, 2931, 2030);
		$elements[] =  array("Zias", "Elysium, inside mountains SW and NW", NULL, 0, 0, 0);
		$elements[] =  array("Zibell The Wanderer", "Central Artery Valley", NULL, 590, 3432, 2649);
		$elements[] =  array("Zodiac", "Pandemonium Caina, NE of the garden statues. Portal boss for entering pandemonium", "", 4328, 190, 85);
		$elements[] =  array("Zoftig Blimp", "Mort, City of Hope", NULL, 560, 0, 0);
		
		$blob = ""; $all = 0;
		$keywords = substr($msg,8);
		if($keywords=="") {
			return $this->bot->send_output($name, "No keywords given ...", $origin);
		}
				
		foreach($elements as $element) {
			if($element[0] == $keywords) {
				if($element[3]!=0&&$element[4]!=0&&$element[5]!=0) {
					$waypoint = $this->bot->core("tools")->chatcmd($element[4]." ".$element[5]." ".$element[3], "Click for waypoint", "waypoint");
				} else {
					$waypoint = "";
				}
				$blob .= $element[0]." : ".$element[1]."\n".$waypoint;
				return $this->bot->send_output($name, $element[0]." details: ".$this->bot->core("tools")->make_blob("click to view", $blob), $origin);
			}
		}
		
		$results = array();
		$words = explode(" ",$keywords);
		foreach($elements as $element) {
			foreach($words as $word) {
				if(strpos(strtolower($element[2]),strtolower($word))!==false||strpos(strtolower($element[1]),strtolower($word))!==false||strpos(strtolower($element[0]),strtolower($word))!==false) {
					if(!isset($results[$element[0]])) {
						$results[$element[0]] = $element;
					}
				}
			}
		}
		if(count($results)==0) {
			return $this->bot->send_output($name, "No result found ...", $origin);
		} else {
			foreach($results as $result) {
				$all++;
				$blob .= $this->bot->core("tools")->chatcmd("whereis ".$result[0],$result[0])." | ";
			}
		}
		return $this->bot->send_output($name, $all." element(s) found: ".$this->bot->core("tools")->make_blob("click to view", $blob), $origin);
	}
}
?>
