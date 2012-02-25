<?php
/*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2012 J-Soft and the BeBot development team.
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
*/
/*
* City Progression by Madrulf
* version 1.2
* This module allows members to see resources required to complete building a player city.
*
*
*/
$city = new City($bot);
class City extends BaseActiveModule
{
  /*
      buildings order:
          0-9 = T1 buildings
          10-19 = T2 buildings
          20-29 = T3 buildings
          30-36 = T1 walls
          37-43 = T2 walls
          44-50 = T3 walls

      resources order:
          brace (copper), brick (sandstone), joist (ash), plain facade (silver),
          lintel (iron), block (granite), beam (yew), ornate facde (electrum),
          girder (duskmetal), slab (basalt), frame (oak), grand facade (gold)

  */
  var $buildtimer = 0;
  var $buildid = 0;
  var $stock = array(0,
                     0,
                     0,
                     0,
                     0,
                     0,
                     0,
                     0,
                     0,
                     0,
                     0,
                     0,
                     0);
  var $progress = array(0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0);
  var $max = array('LacheishEast' => array(1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           11,
                                           19,
                                           3,
                                           2,
                                           8,
                                           4,
                                           1,
                                           11,
                                           19,
                                           3,
                                           2,
                                           8,
                                           4,
                                           1,
                                           11,
                                           19,
                                           3,
                                           2,
                                           8,
                                           4,
                                           1),
                   'LacheishWest' => array(1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           12,
                                           21,
                                           2,
                                           2,
                                           6,
                                           6,
                                           2,
                                           12,
                                           21,
                                           2,
                                           2,
                                           6,
                                           6,
                                           2,
                                           12,
                                           21,
                                           2,
                                           2,
                                           6,
                                           6,
                                           2),
                   'LacheishNW' => array(1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         1,
                                         21,
                                         9,
                                         2,
                                         2,
                                         6,
                                         4,
                                         1,
                                         21,
                                         9,
                                         2,
                                         2,
                                         6,
                                         4,
                                         1,
                                         21,
                                         9,
                                         2,
                                         2,
                                         6,
                                         4,
                                         1),
                   'PoitainEast' => array(1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          34,
                                          14,
                                          3,
                                          2,
                                          7,
                                          4,
                                          3,
                                          34,
                                          14,
                                          3,
                                          2,
                                          7,
                                          4,
                                          3,
                                          34,
                                          14,
                                          3,
                                          2,
                                          7,
                                          4,
                                          3),
                   'PoitainSouth' => array(1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           1,
                                           39,
                                           12,
                                           5,
                                           2,
                                           9,
                                           6,
                                           3,
                                           39,
                                           12,
                                           5,
                                           2,
                                           9,
                                           6,
                                           3,
                                           39,
                                           12,
                                           5,
                                           2,
                                           9,
                                           6,
                                           3),
                   'PoitainWest' => array(1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          1,
                                          32,
                                          13,
                                          6,
                                          2,
                                          7,
                                          6,
                                          5,
                                          32,
                                          13,
                                          6,
                                          2,
                                          7,
                                          6,
                                          5,
                                          32,
                                          13,
                                          6,
                                          2,
                                          7,
                                          6,
                                          5),
                   'SwampSE' => array(1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      63,
                                      3,
                                      5,
                                      2,
                                      6,
                                      4,
                                      4,
                                      63,
                                      3,
                                      5,
                                      2,
                                      6,
                                      4,
                                      4,
                                      63,
                                      3,
                                      5,
                                      2,
                                      6,
                                      4,
                                      4),
                   'SwampNW' => array(1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      1,
                                      21,
                                      13,
                                      4,
                                      2,
                                      8,
                                      4,
                                      0,
                                      21,
                                      13,
                                      4,
                                      2,
                                      8,
                                      4,
                                      0,
                                      21,
                                      13,
                                      4,
                                      2,
                                      8,
                                      4,
                                      0));
  var $sequence = array(-1,
                        0,
                        1,
                        2,
                        3,
                        4,
                        5,
                        6,
                        7,
                        8,
                        9,
                        10,
                        11,
                        12,
                        13,
                        14,
                        15,
                        16,
                        17,
                        18,
                        19,
                        20,
                        21,
                        22,
                        23,
                        24,
                        25,
                        26,
                        27,
                        28,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        30,
                        31,
                        32,
                        33,
                        34,
                        35,
                        36,
                        37,
                        38,
                        39,
                        40,
                        41,
                        42,
                        43);
  var $names = array('Keep I',
                     'Trade Post I',
                     'Temple I',
                     'Library I',
                     'Barracks I',
                     'Thieves\' Guild I',
                     'Weaponsmith Workshop I',
                     'Armorsmith Workshop I',
                     'Alchemist Workshop I',
                     'Architect Workshop I',
                     'Keep II',
                     'Trade Post II',
                     'Temple II',
                     'Library II',
                     'Barracks II',
                     'Thieves\' Guild II',
                     'Weaponsmith Workshop II',
                     'Armorsmith Workshop II',
                     'Alchemist Workshop II',
                     'Architect Workshop II',
                     'Keep III',
                     'Trade Post III',
                     'Temple III',
                     'Library III',
                     'Barracks III',
                     'Thieves\' Guild III',
                     'Weaponsmith Workshop III',
                     'Armorsmith Workshop III',
                     'Alchemist Workshop III',
                     'Architect Workshop III',
                     'Wall I',
                     'Curved Wall I',
                     'Staired Wall I',
                     'Gate I',
                     'Tower I',
                     'Ending Tower I',
                     'Corner Tower I',
                     'Wall II',
                     'Curved Wall II',
                     'Staired Wall II',
                     'Gate II',
                     'Tower II',
                     'Ending Tower II',
                     'Corner Tower II',
                     'Wall III',
                     'Curved Wall III',
                     'Staired Wall III',
                     'Gate III',
                     'Tower III',
                     'Ending Tower III',
                     'Corner Tower III');
  var $resources = array(array(10,
                               20,
                               15,
                               5,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
    //start t1 buildings
                         array(10,
                               20,
                               15,
                               5,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(3,
                               5,
                               4,
                               2,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(3,
                               5,
                               4,
                               2,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(3,
                               5,
                               4,
                               1,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(3,
                               5,
                               4,
                               1,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(3,
                               5,
                               4,
                               1,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(3,
                               5,
                               4,
                               1,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(3,
                               5,
                               5,
                               2,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(3,
                               5,
                               4,
                               1,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
    //end t1
                         array(0,
                               0,
                               0,
                               0,
                               120,
                               250,
                               150,
                               50,
                               0,
                               0,
                               0,
                               0,
                               153),
    //start t2 buildings
                         array(0,
                               0,
                               0,
                               0,
                               120,
                               250,
                               150,
                               50,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               50,
                               150,
                               75,
                               20,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               40,
                               120,
                               50,
                               20,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               40,
                               120,
                               50,
                               10,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               40,
                               120,
                               50,
                               10,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               40,
                               120,
                               50,
                               10,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               40,
                               120,
                               50,
                               10,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               50,
                               150,
                               75,
                               20,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               40,
                               120,
                               50,
                               10,
                               0,
                               0,
                               0,
                               0,
                               153),
    //end t2
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               300,
                               500,
                               200,
                               150,
                               249),
    //start t3 buildings
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               2000,
                               3000,
                               1800,
                               600,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               1200,
                               1900,
                               1000,
                               220,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               700,
                               1500,
                               600,
                               220,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               700,
                               1500,
                               600,
                               130,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               700,
                               1500,
                               600,
                               130,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               700,
                               1500,
                               600,
                               130,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               700,
                               1500,
                               600,
                               130,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               1200,
                               1900,
                               1000,
                               220,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               700,
                               1500,
                               600,
                               130,
                               249),
    //end t3
                         array(0,
                               2,
                               1,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
    //t1 wall
                         array(0,
                               2,
                               1,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(0,
                               2,
                               2,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(1,
                               2,
                               2,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(1,
                               2,
                               1,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(1,
                               2,
                               1,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(1,
                               2,
                               1,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               101),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               12,
                               6,
                               0,
                               0,
                               0,
                               0,
                               0,
                               153),
    //t2 wall
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               12,
                               6,
                               0,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               12,
                               12,
                               0,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               6,
                               12,
                               12,
                               0,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               6,
                               12,
                               6,
                               0,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               6,
                               12,
                               6,
                               0,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               6,
                               12,
                               6,
                               0,
                               0,
                               0,
                               0,
                               0,
                               153),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               120,
                               60,
                               0,
                               249),
    //t3 wall
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               120,
                               60,
                               0,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               120,
                               120,
                               0,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               60,
                               120,
                               120,
                               0,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               60,
                               120,
                               60,
                               0,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               60,
                               120,
                               60,
                               0,
                               249),
                         array(0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               0,
                               60,
                               120,
                               60,
                               0,
                               249));
  var $bonuslist = array("Max Health, based on level",
                         "Trader NPC in Guild City",
                         "+1 Heroic Resistance Rating",
                         "+1 Heroic Magic Rating",
                         "+1 Heroic Defense Rating",
                         "+1 Heroic Attack Rating",
                         "Unlocks Level 70 Weapons",
                         "Unlocks Level 70 Armor",
                         "Unlocks Level 70 Alchemy",
                         "Unlocks Tier 2 Plans",
                         "Max Health, based on level",
                         "",
                         "+2 Heroic Resistance Rating",
                         "+2 Heroic Magic Rating",
                         "+2 Heroic Defense Rating",
                         "+2 Heroic Attack Rating",
                         "Unlocks Level 75 Weapons",
                         "Unlocks Level 75 Armor",
                         "Unlocks Level 75 Alchemy",
                         "Unlocks Tier 3 Plans",
                         "Max Health, based on level",
                         "",
                         "+3 Heroic Resistance Rating",
                         "+3 Heroic Magic Rating",
                         "+3 Heroic Defense Rating",
                         "+3 Heroic Attack Rating",
                         "Unlocks Level 80 Weapons",
                         "Unlocks Level 80 Armor",
                         "Unlocks Level 80 Alchemy",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "",
                         "");
  var $gathernames = array("Copper",
                           "Sandstone",
                           "Ash",
                           "Silver",
                           "Iron",
                           "Granite",
                           "Yew",
                           "Electrum",
                           "Duskmetal",
                           "Basalt",
                           "Oak",
                           "Gold",
                           "Coins");
  var $refinednames = array("Brace",
                            "Brick",
                            "Joist",
                            "Plain Facade",
                            "Lintel",
                            "Block",
                            "Beam",
                            "Ornate Facade",
                            "Girder",
                            "Slab",
                            "Frame",
                            "Grand Facade",
                            "Coins");

  function __construct(&$bot)
  {
    parent::__construct($bot, get_class($this));
    $this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("city", "true") . "(
				`key` VARCHAR(32) NOT NULL PRIMARY KEY,
				`vark` VARCHAR(255))");
    $result = $this->bot->db->select("SELECT `vark` FROM #___city WHERE `key` = 'progress'");
    if (!empty($result)) {
      $this->progress = explode(",", $result[0][0]);
    }
    else
    {
      $this->bot->db->query("REPLACE INTO #___city (`key`, `vark`) VALUES ('progress', '" . implode(",", $this->progress) . "')");
    }
    $result = $this->bot->db->select("SELECT `vark` FROM #___city WHERE `key` = 'stock'");
    if (!empty($result)) {
      $this->stock = explode(",", $result[0][0]);
    }
    else
    {
      $this->bot->db->query("REPLACE INTO #___city (`key`, `vark`) VALUES ('stock', '" . implode(",", $this->stock) . "')");
    }
    $this->help['description'] = 'Tracks guild city progression and resources management';
    $this->help['command']['city'] = "Displays status of city progression and resource quantities.";
    $this->help['command']['city help'] = "Lists groups for resource data";
    $this->help['command']['city help <group>'] = "Contains data on resources used to construct buildings in <group>";
    $this->help['command']['build'] = "Display the build menu";
    $this->help['command']['build next'] = "Prepares to build the next building in city progression.";
    $this->help['command']['build confirm'] = "Extra step to complete building part of the city.";
    $this->help['command']['build wall <wall name>'] = "Examples: Tower I, Tower III, Gate II";
    $this->help['command']['build bank <material> <amount>'] = "Use name of crafted blocks or gathered resources, ex: Brace, Brick, Sandstone, etc.";
    $this->register_command("all", "city", "MEMBER");
    $this->register_command("all", "build", "ADMIN");
    $this->bot->core("colors")->define_scheme("city", "titles", "gold");
    $this->bot->core("colors")->define_scheme("city", "buildings", "gold");
    $this->bot->core("colors")->define_scheme("city", "resources", "gold");
    $this->bot->core("settings")->create('City', 'CityLocation', 'LacheishEast', 'Set this to the location of your guild city for accurate wall construction', 'LacheishEast;LacheishWest;LacheishNW;SwampSE;SwampNW;PoitainEast;PoitainSouth;PoitainWest');
    $this->bot->core("settings")->create("City", "ShowAllBuildings", TRUE, "On: Shows all tiers of buildings; Off: Only shows current tier your city is working on.");
    $this->bot->core("settings")->create("City", "ShowAllWalls", TRUE, "On: Shows all tiers of walls; Off: Only shows current tier your city is working on.");
    $this->bot->core("settings")->create("City", "UseRefinedNames", FALSE, "On: Uses Brick, Lintel, Block, etc as building requirements and stock; Off: Uses Sandstone, Copper, Granite, etc as building requirements and stock.");
    $this->bot->core("settings")->create("City", "ShowHelp", TRUE, "On: Adds links to detailed building requirements for each tier.");
    // $this -> bot -> core("settings") -> get("City", "Show All Buildings")
  }

  function command_handler($name, $msg, $origin)
  {
    $vars = explode(' ', strtolower($msg));
    switch ($vars[0])
    {
      case 'city':
        switch ($vars[1])
        {
          case 'help':
            return $this->help(substr($msg, 10));
            break;
          default:
            return $this->status();
            break;
        }
        break;
      case 'build':
        switch ($vars[1])
        {
          case 'next':
            return $this->buildnext();
            break;
          case 'wall':
            return $this->buildwall(substr($msg, 11));
            break;
          case 'confirm':
            return $this->dobuild();
            break;
          case 'bank':
            // !build bank grand facade 10, mat name = 2-3 (2 , quantity = 4 (c-1)
            $c = count($vars);
            for ($i = 2; $i < ($c - 1); $i++)
            {
              if (strlen($m) > 0) {
                $m .= " ";
              }
              $m .= $vars[$i];
            }
            return $this->setmaterial($m, $vars[$c - 1]);
            break;
          default:
            return $this->status(true);
        }
        break;
      default:
        return "Error: City.php was given an unsupported command.";
        break;
    }
    return false;
  }

  function status()
  {
    //create bubble on complete city status'
    $status = $this->bot->core("colors")->colorize("city_titles", "<center>City Progression and Resource Data</center>\n\n");
    //guild bank
    if ($this->bot->core("settings")->get("City", "ShowHelp")) {
      $status .= $this->bot->core("colors")->colorize("city_titles", "Guild Bank [" . $this->bot->core("tools")->chatcmd("city help Resources", "help") . "]\n  ");
    }
    else
    {
      $status .= $this->bot->core("colors")->colorize("city_titles", "Guild Bank\n  ");
    }
    $status .= str_replace(",", "\n  ", $this->reqs($this->stock, "default", true));
    $status .= "\n\n\n";
    //city progression
    $status .= $this->bot->core("colors")->colorize("city_titles", "City Progress\n\n");
    $t1complete = true;
    $t2complete = true;
    $t3complete = true;
    if ($this->bot->core("settings")->get("City", "ShowHelp")) {
      $t1block = $this->bot->core("colors")->colorize("city_titles", " First Tier Buildings [" . $this->bot->core("tools")->chatcmd("city help First Tier Buildings", "help") . "] \n");
      $t2block = $this->bot->core("colors")->colorize("city_titles", " Second Tier Buildings [" . $this->bot->core("tools")->chatcmd("city help Second Tier Buildings", "help") . "]\n");
      $t3block = $this->bot->core("colors")->colorize("city_titles", " Third Tier Buildings [" . $this->bot->core("tools")->chatcmd("city help Third Tier Buildings", "help") . "]\n");
    }
    else
    {
      $t1block = $this->bot->core("colors")->colorize("city_titles", " First Tier Buildings\n");
      $t2block = $this->bot->core("colors")->colorize("city_titles", " Second Tier Buildings\n");
      $t3block = $this->bot->core("colors")->colorize("city_titles", " Third Tier Buildings\n");
    }
    $t1status = $this->bot->core("colors")->colorize("city_titles", " First Tier Buildings Complete\n\n");
    $t2status = $this->bot->core("colors")->colorize("city_titles", " Second Tier Buildings Complete\n\n");
    $t3status = $this->bot->core("colors")->colorize("city_titles", " Third Tier Buildings Complete\n\n");
    $t1mats = array(0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0);
    $t2mats = array(0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0);
    $t3mats = array(0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0);
    for ($w = 0; $w <= 9; $w++)
    {
      if ($this->progress[$w] > $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]) {
        $this->progress[$w] = $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w];
        $this->bot->db->query("REPLACE INTO #___city (`key`, `vark`) VALUES ('progress', '" . implode(",", $this->progress) . "')");
      }
      $t1block .= "  " . $this->progress[$w] . " / " . $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w] . "  " . $this->names[$w] . "\n";
      if ($this->progress[$w] < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]) {
        for ($m = $this->progress[$w]; $m < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]; $m++)
        {
          foreach ($this->resources[$w] as $mi => $mq)
          {
            $t1mats[$mi] += $mq;
          }
        }
        $t1complete = false;
      }
    }
    for ($w = 10; $w <= 19; $w++)
    {
      if ($this->progress[$w] > $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]) {
        $this->progress[$w] = $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w];
        $this->bot->db->query("REPLACE INTO #___city (`key`, `vark`) VALUES ('progress', '" . implode(",", $this->progress) . "')");
      }
      $t2block .= "  " . $this->progress[$w] . " / " . $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w] . "  " . $this->names[$w] . "\n";
      if ($this->progress[$w] < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]) {
        for ($m = $this->progress[$w]; $m < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]; $m++)
        {
          foreach ($this->resources[$w] as $mi => $mq)
          {
            $t2mats[$mi] += $mq;
          }
        }
        $t2complete = false;
      }
    }
    for ($w = 20; $w <= 29; $w++)
    {
      if ($this->progress[$w] > $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]) {
        $this->progress[$w] = $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w];
        $this->bot->db->query("REPLACE INTO #___city (`key`, `vark`) VALUES ('progress', '" . implode(",", $this->progress) . "')");
      }
      $t3block .= "  " . $this->progress[$w] . " / " . $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w] . "  " . $this->names[$w] . "\n";
      if ($this->progress[$w] < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]) {
        for ($m = $this->progress[$w]; $m < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]; $m++)
        {
          foreach ($this->resources[$w] as $mi => $mq)
          {
            $t3mats[$mi] += $mq;
          }
        }
        $t3complete = false;
      }
    }
    if (!$t1complete) {
      $t1status = $this->bot->core("colors")->colorize("city_titles", " Remaining resources to complete First Tier Buildings:\n  ") . str_replace(", ", "\n  ", $this->reqs($t1mats)) . "\n\n";
    }
    if (!$t2complete) {
      $t2status = $this->bot->core("colors")->colorize("city_titles", " Remaining resources to complete Second Tier Buildings:\n  ") . str_replace(", ", "\n  ", $this->reqs($t2mats)) . "\n\n";
    }
    if (!$t3complete) {
      $t3status = $this->bot->core("colors")->colorize("city_titles", " Remaining resources to complete Third Tier Buildings:\n  ") . str_replace(", ", "\n  ", $this->reqs($t3mats)) . "\n\n";
    }
    if ((!$t1complete) || ($t1complete && $this->bot->core("settings")->get("City", "ShowAllBuildings"))) {
      $status .= $t1block . "\n";
    }
    $status .= $t1status;
    if ((!$t2complete && $t1complete) || ($this->bot->core("settings")->get("City", "ShowAllBuildings"))) {
      $status .= $t2block . "\n";
    }
    if ((!$t2complete && $t1complete) || $t2complete || ($this->bot->core("settings")->get("City", "ShowAllBuildings"))) {
      $status .= $t2status;
    }
    if ((!$t3complete && $t1complete && $t2complete) || ($this->bot->core("settings")->get("City", "ShowAllBuildings"))) {
      $status .= $t3block . "\n";
    }
    if ((!$t3complete && $t2complete) || $t3complete || ($this->bot->core("settings")->get("City", "ShowAllBuildings"))) {
      $status .= $t3status;
    }
    $status .= "\n";
    //walls 30-36 = t1 walls, 37-43 = t2, 44-50 = t3
    $status .= $this->bot->core("colors")->colorize("city_titles", "Wall Progress\n\n");
    $w1complete = true;
    $w2complete = true;
    $w3complete = true;
    if ($this->bot->core("settings")->get("City", "ShowHelp")) {
      $w1block = $this->bot->core("colors")->colorize("city_titles", " First Tier Walls [" . $this->bot->core("tools")->chatcmd("city help First Tier Walls", "help") . "]\n");
      $w2block = $this->bot->core("colors")->colorize("city_titles", " Second Tier Walls [" . $this->bot->core("tools")->chatcmd("city help Second Tier Walls", "help") . "]\n");
      $w3block = $this->bot->core("colors")->colorize("city_titles", " Third Tier Walls [" . $this->bot->core("tools")->chatcmd("city help Third Tier Walls", "help") . "]\n");
    }
    else
    {
      $w1block = $this->bot->core("colors")->colorize("city_titles", " First Tier Walls\n");
      $w2block = $this->bot->core("colors")->colorize("city_titles", " Second Tier Walls\n");
      $w3block = $this->bot->core("colors")->colorize("city_titles", " Third Tier Walls\n");
    }
    $w1status = $this->bot->core("colors")->colorize("city_titles", " First Tier Walls Complete\n\n");
    $w2status = $this->bot->core("colors")->colorize("city_titles", " Second Tier Walls Complete\n\n");
    $w3status = $this->bot->core("colors")->colorize("city_titles", " Third Tier Walls Complete\n\n");
    $w1mats = array(0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0);
    $w2mats = array(0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0);
    $w3mats = array(0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0);
    for ($w = 30; $w <= 36; $w++)
    {
      if ($this->progress[$w] > $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]) {
        $this->progress[$w] = $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w];
        $this->bot->db->query("REPLACE INTO #___city (`key`, `vark`) VALUES ('progress', '" . implode(",", $this->progress) . "')");
      }
      $w1block .= "  " . $this->progress[$w] . " / " . $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w] . "  " . $this->names[$w] . "\n";
      if ($this->progress[$w] < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]) {
        for ($m = $this->progress[$w]; $m < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]; $m++)
        {
          foreach ($this->resources[$w] as $mi => $mq)
          {
            $w1mats[$mi] += $mq;
          }
        }
        $w1complete = false;
      }
    }
    for ($w = 37; $w <= 43; $w++)
    {
      if ($this->progress[$w] > $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]) {
        $this->progress[$w] = $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w];
        $this->bot->db->query("REPLACE INTO #___city (`key`, `vark`) VALUES ('progress', '" . implode(",", $this->progress) . "')");
      }
      $w2block .= "  " . $this->progress[$w] . " / " . $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w] . "  " . $this->names[$w] . "\n";
      if ($this->progress[$w] < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]) {
        for ($m = $this->progress[$w]; $m < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]; $m++)
        {
          foreach ($this->resources[$w] as $mi => $mq)
          {
            $w2mats[$mi] += $mq;
          }
        }
        $w2complete = false;
      }
    }
    for ($w = 44; $w <= 50; $w++)
    {
      if ($this->progress[$w] > $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]) {
        $this->progress[$w] = $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w];
        $this->bot->db->query("REPLACE INTO #___city (`key`, `vark`) VALUES ('progress', '" . implode(",", $this->progress) . "')");
      }
      $w3block .= "  " . $this->progress[$w] . " / " . $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w] . "  " . $this->names[$w] . "\n";
      if ($this->progress[$w] < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]) {
        for ($m = $this->progress[$w]; $m < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$w]; $m++)
        {
          foreach ($this->resources[$w] as $mi => $mq)
          {
            $w3mats[$mi] += $mq;
          }
        }
        $w3complete = false;
      }
    }
    if (!$w1complete) {
      $w1status = $this->bot->core("colors")->colorize("city_titles", " Remaining resources to complete First Tier Walls:\n  ") . str_replace(", ", "\n  ", $this->reqs($w1mats)) . "\n\n";
    }
    if (!$w2complete) {
      $w2status = $this->bot->core("colors")->colorize("city_titles", " Remaining resources to complete Second Tier Walls:\n  ") . str_replace(", ", "\n  ", $this->reqs($w2mats)) . "\n\n";
    }
    if (!$w3complete) {
      $w3status = $this->bot->core("colors")->colorize("city_titles", " Remaining resources to complete Third Tier Walls:\n  ") . str_replace(", ", "\n  ", $this->reqs($w3mats)) . "\n\n";
    }
    if ((!$w1complete) || ($w1complete && $this->bot->core("settings")->get("City", "ShowAllWalls"))) {
      $status .= $w1block . "\n";
    }
    $status .= $w1status;
    if ((!$w2complete && $w1complete) || ($this->bot->core("settings")->get("City", "ShowAllWalls"))) {
      $status .= $w2block . "\n";
    }
    if ((!$w2complete && $w1complete) || $w2complete || ($this->bot->core("settings")->get("City", "ShowAllWalls"))) {
      $status .= $w2status;
    }
    if ((!$w3complete && $w1complete && $w2complete) || ($this->bot->core("settings")->get("City", "ShowAllWalls"))) {
      $status .= $w3block . "\n";
    }
    if ((!$w3complete && $w2complete) || $w3complete || ($this->bot->core("settings")->get("City", "ShowAllWalls"))) {
      $status .= $w3status;
    }
    $status .= "\n";
    return $this->bot->core("tools")->make_blob("City Progression and Resource Data", $status);
  }

  function help($topic)
  {
    $topic = ucwords(strtolower($topic));
    //build bubble explaining how resources make building parts and city progression
    if ($topic == "Content" || $topic == "") {
      $topic = "Content";
      $guide = $this->bot->core("colors")->colorize("city_titles", "<center>City Building Guide</center>\n\n");
    }
    else
    {
      $guide = $this->bot->core("colors")->colorize("city_titles", "<center>City Building Guide: $topic</center>\n\n");
      $guide .= "[" . $this->bot->core("tools")->chatcmd("city help", "View All Guides") . "]\n\n";
    }
    switch ($topic)
    {
      case 'First Tier Buildings':
        for ($i = 0; $i <= 9; $i++)
        {
          $guide .= $this->details($i) . "\n\n";
        }
        break;
      case 'Second Tier Buildings':
        for ($i = 10; $i <= 19; $i++)
        {
          $guide .= $this->details($i) . "\n\n";
        }
        break;
      case 'Third Tier Buildings':
        for ($i = 20; $i <= 29; $i++)
        {
          $guide .= $this->details($i) . "\n\n";
        }
        break;
      case 'First Tier Walls':
        for ($i = 30; $i <= 36; $i++)
        {
          $guide .= $this->details($i) . "\n\n";
        }
        break;
      case 'Second Tier Walls':
        for ($i = 37; $i <= 43; $i++)
        {
          $guide .= $this->details($i) . "\n\n";
        }
        break;
      case 'Third Tier Walls':
        for ($i = 44; $i <= 50; $i++)
        {
          $guide .= $this->details($i) . "\n\n";
        }
        break;
      case 'Resources':
        $guide .= $this->bot->core("colors")->colorize("city_titles", "Gathering Basics") . "\n";
        $guide .= "  Trainers are available for all 6 gathering professions starting at level 20. The gathering skills needed to construct a guild city are Stonecutting, Woodcutting, Mining and Prospecting. Gathering quests can be picked up at:\n Caenna Village in Poitain,\n Brandoc Village in Lacheish Plains or\n Nakaset Village in Purple Lotus Swamp.\n\n\n";
        $guide .= $this->bot->core("colors")->colorize("city_titles", "First Tier Resources") . " (Level 20 Gathering)\n";
        $guide .= " " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(0 => 1), false)) . " converts to " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(0 => 1), true)) . "\n";
        $guide .= " " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(1 => 1), false)) . " converts to " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(1 => 1), true)) . "\n";
        $guide .= " " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(2 => 1), false)) . " converts to " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(2 => 1), true)) . "\n";
        $guide .= " " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(3 => 1), false)) . " converts to " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(3 => 1), true)) . "\n\n";
        $guide .= $this->bot->core("colors")->colorize("city_titles", "Second Tier Resources") . " (Level 50 Gathering)\n";
        $guide .= " " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(4 => 1), false)) . " converts to " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(4 => 1), true)) . "\n";
        $guide .= " " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(5 => 1), false)) . " converts to " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(5 => 1), true)) . "\n";
        $guide .= " " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(6 => 1), false)) . " converts to " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(6 => 1), true)) . "\n";
        $guide .= " " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(7 => 1), false)) . " converts to " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(7 => 1), true)) . "\n\n";
        $guide .= $this->bot->core("colors")->colorize("city_titles", "Third Tier Resources") . " (Level 70 Gathering)\n";
        $guide .= " " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(8 => 1), false)) . " converts to " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(8 => 1), true)) . "\n";
        $guide .= " " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(9 => 1), false)) . " converts to " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(9 => 1), true)) . "\n";
        $guide .= " " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(10 => 1), false)) . " converts to " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(10 => 1), true)) . "\n";
        $guide .= " " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(11 => 1), false)) . " converts to " . $this->bot->core("colors")->colorize("city_resources", $this->reqs(array(11 => 1), true)) . "\n\n";
        break;
      case 'Content':
        $guide .= $this->bot->core("tools")->chatcmd("city help Resources", "Resources") . "\n";
        $guide .= $this->bot->core("tools")->chatcmd("city help First Tier Buildings", "First Tier Buildings") . "\n";
        $guide .= $this->bot->core("tools")->chatcmd("city help Second Tier Buildings", "Second Tier Buildings") . "\n";
        $guide .= $this->bot->core("tools")->chatcmd("city help Third Tier Buildings", "Third Tier Buildings") . "\n";
        $guide .= $this->bot->core("tools")->chatcmd("city help First Tier Walls", "First Tier Walls") . "\n";
        $guide .= $this->bot->core("tools")->chatcmd("city help Second Tier Walls", "Second Tier Walls") . "\n";
        $guide .= $this->bot->core("tools")->chatcmd("city help Third Tier Walls", "Third Tier Walls") . "\n";
        break;
      default:
        return "No guide on '$topic'";
    }
    return $this->bot->core("tools")->make_blob("City Building Guide: $topic", $guide);
  }

  function details($id)
  {
    if (strlen($this->names[$id]) > 1) {
      $det = $this->bot->core("colors")->colorize("city_buildings", $this->names[$id]) . "\n";
      $det .= " Requires: " . $this->bot->core("colors")->colorize("city_resources", $this->calcreqs($id)) . "\n";
      if ($this->sequence[$id] > -1) {
        $det .= " Predecessor: " . $this->bot->core("colors")->colorize("city_buildings", $this->names[$this->sequence[$id]]) . "\n";
      }
      if (strlen($this->bonuslist[$id]) > 1) {
        $det .= " Benefit: " . $this->bonuslist[$id];
      }
      return $det;
    }
    return false;
  }

  function buildnext()
  {
    //check progression and set next and timer, no reason to check prereqs as there is only one build order
    for ($i = 0; $i <= 29; $i++)
    {
      if ($this->progress[$i] == 0) {
        $this->buildtimer = time();
        $this->buildid = $i;
        return "Preparing to build " . $this->bot->core("colors")->colorize("city_buildings", $this->names[$i]) . " using " . $this->bot->core("colors")->colorize("city_resources", $this->calcreqs($i)) . ". Type '!build confirm' within the next 30 seconds to build.";
      }
    }
    return "City buildings are already complete!";
  }

  function buildwall($name)
  {
    foreach ($this->names as $i => $n)
    {
      //check through name list to get id of wall
      if ($i < 30) {
        continue;
      }
      if (strtolower($name) == strtolower($n)) {
        //check if prereqs are met
        // There required building exists
        // There are currently more of the required building than the current building unless the required building is a keep
        // We have not reached the maximum number of buildings.
        if ((($this->progress[$this->sequence[$i]] > $this->progress[$i]) || (($this->sequence[$i] == 0) && ($this->progress[0] == 1))) && ($this->progress[$i] < $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$i])) {
          $this->buildtimer = time();
          $this->buildid = $i;
          return "Preparing to build " . $this->bot->core("colors")->colorize("city_buildings", $this->names[$i]) . " using " . $this->bot->core("colors")->colorize("city_resources", $this->calcreqs($i)) . ". Type '!build confirm' within the next 30 seconds to build.";
        }
        else
        {
          if ($this->progress[$i] == $this->max[$this->bot->core("settings")->get("City", "CityLocation")][$i]) {
            return "All " . $this->bot->core("colors")->colorize("city_buildings", $this->names[$i]) . " have been built.";
          }
          elseif (($this->sequence[$i] == 0) || ($this->progress[$i] == 0))
          {
            return "You must build a " . $this->bot->core("colors")->colorize("city_buildings", $this->names[$this->sequence[$i]]) . " before you can build a " . $this->bot->core("colors")->colorize("city_buildings", $this->names[$i]) . ".";
          }
          else
          {
            return "You must build another " . $this->bot->core("colors")->colorize("city_buildings", $this->names[$this->sequence[$i]]) . " before you can build a " . $this->bot->core("colors")->colorize("city_buildings", $this->names[$i]) . ".";
          }
        }
        break;
      }
    }
    return "'$name' is not a valid wall type.";
  }

  function dobuild()
  {
    //check timer to see if a build request was set recently, if so remove resources of building, raise progression and save to database
    if (($this->buildtimer < time()) && (($this->buildtimer + 30) > time())) {
      $this->progress[$this->buildid] = $this->progress[$this->buildid] + 1;
      foreach ($this->stock as $i => $q)
      {
        if ($this->stock[$i] - $this->resources[$this->buildid][$i] > 0) {
          $this->stock[$i] = $this->stock[$i] - $this->resources[$this->buildid][$i];
        }
        else
        {
          $this->stock[$i] = 0;
        }
      }
      $this->bot->db->query("REPLACE INTO #___city (`key`, `vark`) VALUES ('progress', '" . implode(",", $this->progress) . "')");
      $this->bot->db->query("REPLACE INTO #___city (`key`, `vark`) VALUES ('stock', '" . implode(",", $this->stock) . "')");
      $this->buildtimer = 0;
      return $this->bot->core("colors")->colorize("city_buildings", $this->names[$this->buildid]) . " has been built. Removed " . $this->bot->core("colors")->colorize("city_resources", $this->calcreqs($this->buildid)) . " from guild bank.";
    }
    return "No structure selected. Use '!build next' or '!build wall <name>' to start building.";
  }

  function setmaterial($name, $qty)
  {
    //update arrays and database with new quantiy
    $mat = ucwords(strtolower($name));
    if (!is_numeric($qty)) {
      return "Error: Quantity must be a numeric value";
    }
    if ($qty < 0) {
      return "Error: Cannot set negative stock";
    }
    $namelist = array_merge($this->refinednames, $this->gathernames);
    foreach ($namelist as $i => $name)
    {
      if ($mat == $name) {
        if ($i >= 13) {
          $i = $i - 13;
        }
        if ($i != 12) {
          $qty = floor($qty / 10);
        }
        $this->stock[$i] = $qty;
        $this->bot->db->query("REPLACE INTO #___city (`key`, `vark`) VALUES ('stock', '" . implode(",", $this->stock) . "')");
        if ($i == 12) {
          return "Stock for " . $this->bot->core("colors")->colorize("city_resources", $this->refinednames[$i]) . " has been set to " . $this->bot->core("colors")->colorize("city_resources", $this->moneyformat($qty)) . ".";
        }
        elseif ($this->bot->core("settings")->get("City", "UseRefinedNames"))
        {
          return "Stock for " . $this->bot->core("colors")->colorize("city_resources", $this->refinednames[$i]) . " has been set to " . $this->bot->core("colors")->colorize("city_resources", $qty) . ".";
        }
        else
        {
          return "Stock for " . $this->bot->core("colors")->colorize("city_resources", $this->gathernames[$i]) . " has been set to " . $this->bot->core("colors")->colorize("city_resources", $qty * 10) . ".";
        }
        break;
      }
    }
    return "Error: Unknown building material '$name'.";
  }

  function calcreqs($id)
  {
    if (is_array($this->resources[$id])) {
      return $this->reqs($this->resources[$id]);
    }
    else
    {
      return "Invalid building type to calculate requirements.";
    }
  }

  function reqs($array, $userefinednames = "default", $showzeros = false)
  {
    if (is_array($array)) {
      $reqs = "";
      foreach ($array as $i => $q)
      {
        if ($q > 0 || $showzeros) {
          if ($i == 12) {
            $q = $this->moneyformat($q);
          }
          if (!is_bool($userefinednames)) {
            $refnames = $this->bot->core("settings")->get("City", "UseRefinedNames");
          }
          else
          {
            $refnames = $userefinednames;
          }
          if ($refnames || $i == 12) {
            $reqs .= $q . " " . $this->refinednames[$i] . ", ";
          }
          else
          {
            $reqs .= $q * 10 . " " . $this->gathernames[$i] . ", ";
          }
        }
      }
      return substr($reqs, 0, -2);
    }
    else
    {
      return "Invalid data to generate requirements.";
    }
  }

  function moneyformat($c)
  {
    if ($c >= 100) {
      $m .= floor($c / 100) . "G ";
    }
    $m .= round(substr($c, -2)) . "S";
    return $m;
  }
}

?>