<?php
/*
* Identify.php - Identify the end result of Alien Identify Material, Galactic Gem, or other misc items.
*
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
$identify = new Identify($bot);
/*
The Class itself...
*/
class Identify extends BaseActiveModule
{

  function __construct(&$bot)
  {
    parent::__construct($bot, get_class($this));
    $this->register_command('all', 'identify', 'GUEST');
    $this->register_alias("identify", "bio");
    $this->register_alias("identify", "inspect");
    $this->help['description'] = "Identify the end result of a given item. Useful for biomaterial, galactic gems, and other misc items.";
    $this->help['command']['identify <item>'] = "Identifies the object <item>.";
    $this->help['notes'] = "<item> must be an item reference. Multiple items may be posted at a time.";
  }

  function command_handler($name, $msg, $origin)
  {
    if (preg_match("/^identify (.+)$/i", $msg, $info)) {
      return $this->item_parser($info[1]);
    }
    else
    {
      return "0 Items Posted";
    }
  }

  function item_parser($msg)
  {
    $items = preg_split('/<\/a>/', $msg, -1, PREG_SPLIT_NO_EMPTY);
    //$items = explode("><",$msg);
    foreach ($items as $item)
    {
      if (preg_match("/<a href=\"itemref:\/\/([0-9]+)\/([0-9]+)\/([0-9]+)\">/i", $item, $info)) {
        $return .= $this->identify_item($info[1], $info[2], $info[3]);
        $count++;
      }
    }
    if ($count == 0) {
      Return ("0 Items Posted");
    }
    if ($count == 1) {
      $return = str_replace("\n", " ", $return);
      return $count . " item posted :: " . $return;
    }
    else
    {
      return $count . " items posted :: " . $this->bot->core("tools")->make_blob("click to view", $return);
    }
  }

  function identify_item($low, $high, $ql)
  {
    switch ($high)
    {
      // List all the AI Biomaterial items
      case 247103:
        $highid = 247107;
        $lowid = 247106;
        $purpose = "Used for making alien armor (lower requirements for armor)";
        $type = "Pristine Kyr'Ozch Bio-Material";
        break;
      case 247105:
        $highid = 247109;
        $lowid = 247108;
        $purpose = "Used for making alien armor";
        $type = "Mutated Kyr'Ozch Bio-Material";
        break;
      case 247698:
        $highid = 247674;
        $lowid = 247673;
        $purpose = "(Weapon Upgrade) Brawl, Fast Attack";
        $type = "Kyr'Ozch Bio-Material - Type 76";
        break;
      case 247700:
        $highid = 247676;
        $lowid = 247675;
        $purpose = "(Weapon Upgrade) Brawl, Dimach, Fast Attack";
        $type = "Kyr'Ozch Bio-Material - Type 112";
        break;
      case 247702:
        $highid = 247677;
        $lowid = 247678;
        $purpose = "(Weapon Upgrade) Brawl, Dimach, Fast Attack, Sneak Attack";
        $type = "Kyr'Ozch Bio-Material - Type 240";
        break;
      case 247704:
        $highid = 247679;
        $lowid = 247680;
        $purpose = "(Weapon Upgrade) Dimach, Fast Attack, Parry, Riposte";
        $type = "Kyr'Ozch Bio-Material - Type 880";
        break;
      case 247706:
        $highid = 247682;
        $lowid = 247681;
        $purpose = "(Weapon Upgrade) Dimach, Fast Attack, Sneak Attack, Parry, Riposte";
        $type = "Kyr'Ozch Bio-Material - Type 992";
        break;
      case 247708:
        $highid = 247684;
        $lowid = 247683;
        $purpose = "(Weapon Upgrade) Fling shot";
        $type = "Kyr'Ozch Bio-Material - Type 1";
        break;
      case 247710:
        $highid = 247686;
        $lowid = 247685;
        $purpose = "(Weapon Upgrade) Aimed Shot";
        $type = "Kyr'Ozch Bio-Material - Type 2";
        break;
      case 247712:
        $highid = 247688;
        $lowid = 247687;
        $purpose = "(Weapon Upgrade) Burst";
        $type = "Kyr'Ozch Bio-Material - Type 4";
        break;
      case 247714:
        $highid = 247690;
        $lowid = 247689;
        $purpose = "(Weapon Upgrade) Fling Shot, Burst";
        $type = "Kyr'Ozch Bio-Material - Type 5";
        break;
      case 247716:
        $highid = 247692;
        $lowid = 247691;
        $purpose = "(Weapon Upgrade) Burst, Full Auto";
        $type = "Kyr'Ozch Bio-Material - Type 12 ";
        break;
      case 247718:
        $highid = 247694;
        $lowid = 247693;
        $purpose = "(Weapon Upgrade) Fling Shot, Aimed Shot";
        $type = "Kyr'Ozch Bio-Material - Type 3 ";
        break;
      case 247720:
        $highid = 247696;
        $lowid = 247695;
        $purpose = "(Weapon Upgrade) Burst, Fling Shot, Full Auto";
        $type = "Kyr'Ozch Bio-Material - Type 13";
        break;
      case 254804:
        $highid = 254805;
        $lowid = 247765;
        $purpose = "Used for making high QL buildings";
        $type = "Kyr'Ozch Viral Serum";
        break;
      //List all the Alaapaa stuff (gems/robe)
      case 269800:
        $highid = 168432;
        $purpose = "Modi: HP/ NR/ Nano";
        $type = "Galactic Jewel of the Infinite Moebius";
        break;
      case 269811:
        $highid = 168473;
        $purpose = "Modi: HP/ add Melee Dmg/ %-Reflect/Reflect-Dmg";
        $type = "Galactic Jewel of the Bruised Brawler";
        break;
      case 269812:
        $highid = 168553;
        $purpose = "Modi: HP/ add Fire Dmg/ %-Reflect/Reflect-Dmg";
        $type = "Galactic Jewel of the Searing Desert";
        break;
      case 269813:
        $highid = 168620;
        $purpose = "Modi: HP/ add Cold Dmg/ %-Reflect/Reflect-Dmg";
        $type = "Galactic Jewel of the Frozen Tundra";
        break;
      case 269814:
        $highid = 168717;
        $purpose = "Modi: HP/ add Projectile Dmg/ %-Reflect/Reflect-Dmg";
        $type = "Galactic Jewel of the Jagged Landscape";
        break;
      case 269815:
        $highid = 168843;
        $purpose = "Modi: HP/ add Poison Dmg/ %-Reflect/Reflect-Dmg";
        $type = "Galactic Jewel of the Silent Killer";
        break;
      case 269816:
        $highid = 229984;
        $purpose = "Currently not tradeskillable";
        $type = "Galactic Jewel of the Frail Juggernaut";
        break;
      case 269817:
        $highid = 230176;
        $purpose = "Currently not tradeskillable";
        $type = "Galactic Jewel of the Icy Tundra";
        break;
      case 269818:
        $highid = 230216;
        $purpose = "Currently not tradeskillable";
        $type = "Galactic Jewel of the Craggy Landscape";
        break;
      case 269819:
        $highid = 230256;
        $purpose = "Currently not tradeskillable";
        $type = "Galactic Jewel of the Scarlet Sky";
        break;
      case 270000:
        $highid = 269999;
        $purpose = "Agent, Bureaucrat, Nano-Technician, or Meta-Physicist Robe";
        $type = "Robe of City Lights";
        break;
      //Turn Spirit Pouches
      case 246236:
        $highid = 215071;
        $lowid = 215070;
        $purpose = "Clan Adventurer weapon upgrade";
        $type = "Turn Spirit of the Masked Adjutant";
        break;
      //List all the 2007 Christmas presents
      case 205842:
        $highid = 205841;
        $purpose = "Humor never killed anyone.";
        $type = "Funny Arrow";
        break;
      case 205843:
        $highid = 205840;
        $purpose = "110 percent more cool.";
        $type = "Monster Sunglasses";
        break;
      case 205844:
        $highid = 205839;
        $purpose = "It is said that you once were able to fly when wearing this cap.";
        $type = "Karlsson Propellor Cap";
        break;
      case 274208:
        $purpose = "Random Gift: Candy Cane, Double Candy Canes, The Naughty List Bikini Top/Bottom/Boxers, Gingerbread Man/Woman/Heart, Santa Leet Doll, Gingerbread House, Snowman";
        break;
      case 274209:
        $purpose = "Random Gift: Candy Cane, Double Candy Canes, The Naughty List Bikini Top/Bottom/Boxers, Gingerbread Man/Woman/Heart, Santa Leet Doll, Gingerbread House, Snowman";
        break;
      case 274210:
        $purpose = "Random Gift: Candy Cane, Double Candy Canes, The Naughty List Bikini Top/Bottom/Boxers, Gingerbread Man/Woman/Heart, Santa Leet Doll, Gingerbread House, Snowman";
        break;
      case 274211:
        $purpose = "Random Gift: Candy Cane, Double Candy Canes, The Naughty List Bikini Top/Bottom/Boxers, Gingerbread Man/Woman/Heart, Santa Leet Doll, Gingerbread House, Snowman";
        break;
      case 274212:
        $purpose = "Random Gift: Candy Cane, Double Candy Canes, The Naughty List Bikini Top/Bottom/Boxers, Gingerbread Man/Woman/Heart, Santa Leet Doll, Gingerbread House, Snowman";
        break;
      case 274213:
        $purpose = "Random Gift: Candy Cane, Double Candy Canes, The Naughty List Bikini Top/Bottom/Boxers, Gingerbread Man/Woman/Heart, Santa Leet Doll, Gingerbread House, Snowman";
        break;
      //List a default in case we don't find anything that matches.
      default:
        $purpose = "Unknown Item";
    }
    if (empty($lowid)) {
      $lowid = $highid;
    }
    $return = "##highlight##QL " . $ql . "##end## :: ";
    if (!empty($type)) {
      $return .= $this->bot->core("tools")->make_item($lowid, $highid, $ql, $type) . "\n";
    }
    $return .= $purpose . "\n";
    return $return;
  }
}

?>
