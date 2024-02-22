<?php
/*
* Items.php - Item handling for AoC
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 Thomas Juberg Stensås, ShadowRealm Creations and the BeBot development team.
*
* Developed by:
* - Alreadythere (RK2)
* - Blondengy (RK1)
* - Blueeagl3 (RK1)
* - Glarawyn (RK1)
* - Khalem (RK1)
* - Naturalistic (RK1)
* - Noer
* - Temar (RK1)
* - Vrykolas
* - Bitnykk (RK5)
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
* File last changed at $LastChangedDate: 2008-10-29 23:33:21 +0100 (Mi, 29 Okt 2008) $
* Revision: $Id: StartBot.php 1794 2008-10-29 22:33:21Z temar $
* Getrix 2009-10-29 - Added fix for German chars.
* Getrix 2009-10-26 - Added fix to botname so its starts with upercase then lower. (Fixed bug 26/10)
* Getrix 2009-07-03 - Added search against same DB as submit(Settings,Server) (Using v4).
* Getrix 2009-04-19 - Added Passkey and server as bot settings connecting to different itemDB.
* MeatHooks 2019-04-30 - Modify for using MeatHooks Minions Item Database and remove server setting in db.
*/

$items_core = new Items_Core($bot);

class Items_Core extends BasePassiveModule
{
	var $itemPattern = '<a style="text-decoration:none" href="itemref:\/\/([0-9]*)\/([0-9]*)\/([0-9]*)\/([0-9]*)\/([0-9a-f]*\:[0-9a-f]*\:[0-9a-f]*:[0-9a-f]*)\/([0-9a-f]*\:[0-9a-f]*\:[0-9a-f]*:[0-9a-f]*)\/([0-9a-f]*\:[0-9a-f]*\:[0-9a-f]*:[0-9a-f]*)"><font color=#([0-9a-f]*)>\[([^\]]*)\]<\/font><\/a>';

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));

        $this -> register_module("items");
		$this->bot->core("settings")->create("Items", "CIDB", "https://conan.meathooksminions.com", "What is HTTP(s) Central Item Database URL (MeatHooks Minions by default, or your prefered mirror) ?");
		$this->bot->core("settings")->create("Items", "ItemSubmit", "aoc_items/itemdb_botsubmit.php", "What is CIBD inner path to the Item Submit ; by default from Meathook's API value is : aoc_items/itemdb_botsubmit.php");
		$this->bot->core("settings")->create("Items", "RecipeSubmit", "aoc_items/itemdb_botrecipesubmit.php", "What is CIBD inner path to the Recipe Submit ; by default from Meathook's API value is : aoc_items/itemdb_botrecipesubmit.php");
		$this->bot->core("settings")->create("Items", "ItemSearch", "aoc_items/itemdb_botsearch.php", "What is CIBD inner path to the Item Search ; by default from Meathook's API value is : aoc_items/itemdb_botsearch.php");
		$this->bot->core("settings")->create("Items", "RecipeSearch", "aoc_items/itemdb_botrecipesearch.php", "What is CIBD inner path to the Recipe Search ; by default from Meathook's API value is : aoc_items/itemdb_botrecipesearch.php");
    }

    function parse_items($itemText)
    {
        $items = array();

        $count = preg_match_all('/'.$this->itemPattern.'/i', $itemText, $matches, PREG_SET_ORDER);

        foreach($matches as $match)
        {
            $item['lowid']   = $match[1];
            $item['highid']  = $match[2];
            $item['lowlvl']  = $match[3];
            $item['highlvl'] = $match[4];
            $item['lowcrc']  = $match[5];
            $item['midcrc']  = $match[6];
            $item['highcrc'] = $match[7];
            $item['color']   = $match[8];
			$base = $match[9];
			$conv = iconv("ISO-8859-1", "UTF-8", $base);
			if($base!=$conv) $item['name'] = $conv; // ISO->UTF
			else $item['name'] = $base; // ISO==UTF
            $items[] = $item;
        }
        return $items;
    }

	function parse_recipe_item($itemText)
    {
        $items = array();

        $count = preg_match_all('/'.$this->itemPattern.'/i', $itemText, $matches, PREG_SET_ORDER);
		if ($count != 2)
		{
			return $items;
		}

		$pos = strpos($itemText, "<a");
		$qty_makes = trim(substr($itemText, 0, $pos - 1));
		if (!is_numeric($qty_makes))
		{
			return $items;
		}

		$pos = strpos($itemText, "</a>");
		$pos2 = strpos($itemText, "<a", $pos);

		$qty_needed = trim(substr($itemText, $pos + 5, $pos2 - $pos - 6));
		if (!is_numeric($qty_needed))
		{
			return $items;
		}

        foreach($matches as $match)
        {
            $item['lowid']   = $match[1];
            $item['highid']  = $match[2];
            $item['lowlvl']  = $match[3];
            $item['highlvl'] = $match[4];
            $item['lowcrc']  = $match[5];
            $item['midcrc']  = $match[6];
            $item['highcrc'] = $match[7];
            $item['color']   = $match[8];
			$base = $match[9];
			$conv = iconv("ISO-8859-1", "UTF-8", $base);
			if($base!=$conv) $item['name'] = $conv; // ISO->UTF
			else $item['name'] = $base; // ISO==UTF
            $items[] = $item;
        }

		$items[] = $qty_makes;
		$items[] = $qty_needed;

        return $items;
    }

    function make_item($item, $alternate = false)
    {
        if(empty($item))
            return '';

        if($alternate)
            return '<a style="text-decoration:none" href="itemref://'.$item['lowid'].'/'.$item['highid'].'/'.$item['lowlvl']."/".$item['highlvl'].'/'.$item['lowcrc'].'/'.$item['midcrc']."/".$item['highcrc'].'"><font color=#'.$item['color'].'>['.$item['name'].']</font></a>';
        else
            return "<a style='text-decoration:none' href='itemref://".$item['lowid']."/".$item['highid']."/".$item['lowlvl']."/".$item['highlvl']."/".$item['lowcrc']."/".$item['midcrc']."/".$item['highcrc']."'><font color=#".$item['color'].">[".$item['name']."]</font></a>";
    }

    function is_item($item)
    {
        if(1 > preg_match('/'.$this->itemPattern.'/i', $item))
            return false;

        return true;
    }

    function submit_item($item, $name, $passkey)
    {
        if(empty($item))
            return -1;
                
        $item_botname    = $this -> bot -> botname;
        $item_botname    = ucfirst(strtolower($item_botname));
        $item_guild      = $this -> bot -> guildname;
        $item_dimension  = $this -> bot -> dimension;
        
        $salt = $item['lowid']."_".$item['highid']."_".$item['lowlvl']."_".$item['highlvl']."_".$item['lowcrc']."_".$item['midcrc']."_".$item['highcrc']."_".$item['color']."_".$item['name']."_".$item_botname."_".$name."_".$passkey;

        $checksum = md5('aocitems' . $salt );
        $url  = $this->bot->core("settings")->get("Items", "CIDB")."/".$this->bot->core("settings")->get("Items", "ItemSubmit");
        $url .= '?lowid='.urlencode($item['lowid']);
        $url .= '&highid='.urlencode($item['highid']);
        $url .= '&lowlvl='.urlencode($item['lowlvl']);
        $url .= '&highlvl='.urlencode($item['highlvl']);
        $url .= '&lowcrc='.urlencode($item['lowcrc']);
        $url .= '&midcrc='.urlencode($item['midcrc']);
        $url .= '&highcrc='.urlencode($item['highcrc']);
        $url .= '&color='.urlencode($item['color']);
        $url .= '&name='.urlencode($item['name']);
        $url .= '&server='.urlencode($item_dimension);
        $url .= '&guildname='.urlencode($item_guild);
        $url .= '&botname='.urlencode($item_botname);
        $url .= '&username='.urlencode($name);
        $url .= '&checksum='.urlencode($checksum);
		
		if ($this -> bot -> core("settings") -> get("Items", "LogURL"))
		{
			$this->bot->log("Items", "URL", "$url");
		}

        return $this -> bot -> core("tools") -> get_site($url, 1);
    }

	function submit_recipe_item($recipeitem, $recipeqty, $item, $itemqty, $name, $passkey)
	{
        if(empty($item) || empty($recipeitem))
		{
            return -1;
		}
                
         $result = $this -> bot -> core('items') -> submit_item($recipeitem, $name, $passkey);

         $result = $this -> bot -> core('items') -> submit_item($item, $name, $passkey);
		
		$item_botname    = $this -> bot -> botname;
        $item_botname    = ucfirst(strtolower($item_botname));
        $item_guild      = $this -> bot -> guildname;
        $item_dimension  = $this -> bot -> dimension;

		$salt = $recipeitem['lowid']."_".$recipeitem['highid']."_".$recipeqty."_".$item['lowid']."_".$item['highid']."_".$itemqty."_".$item_botname."_".$name."_".$passkey;
		$salt = str_replace("&", "", $salt);

        $checksum = md5('aocrecipe' . $salt);

        $url  = $this->bot->core("settings")->get("Items", "CIDB")."/".$this->bot->core("settings")->get("Items", "RecipeSubmit");
        $url .= '?recipelowid='.urlencode($recipeitem['lowid']);
        $url .= '&recipehighid='.urlencode($recipeitem['highid']);
        $url .= '&recipeqty='.urlencode($recipeqty);
        $url .= '&itemlowid='.urlencode($item['lowid']);
        $url .= '&itemhighid='.urlencode($item['highid']);
        $url .= '&itemqty='.urlencode($itemqty);
        $url .= '&server='.urlencode($item_dimension);
        $url .= '&guildname='.urlencode($item_guild);
        $url .= '&botname='.urlencode($item_botname);
        $url .= '&username='.urlencode($name);
        $url .= '&checksum='.urlencode($checksum);
		
		if ($this -> bot -> core("settings") -> get("Items", "LogURL"))
		{
			$this->bot->log("Items", "URL", "$url");
		}

        return $this -> bot -> core("tools") -> get_site($url, 1);
	}

    function search_item_db($words)
    {
        $passkey    = $this -> bot -> core("settings") -> get("Items", "Passkey");
        $botname    = $this -> bot -> botname;
        $botname    = ucfirst(strtolower($botname));
        $salt       = $passkey."_".$botname;
        $checksum   = md5('aocitems' . $salt );
        
        $url  = $this->bot->core("settings")->get("Items", "CIDB")."/".$this->bot->core("settings")->get("Items", "ItemSearch");
        $url .= '?search='.urlencode($words);
        $url .= '&botname='.urlencode($this->bot->botname);
        $url .= '&pre='.urlencode($this -> bot -> commpre);
        $url .= '&checksum='.urlencode($checksum);

		if ($this -> bot -> core("settings") -> get("Items", "LogURL"))
		{
			$this->bot->log("Items", "URL", "$url");
		}

        $result = $this -> bot -> core("tools") -> get_site($url, 1);

        if (!empty($result))
		{
            return $result;
		}
        else
		{
            return "Error in query to the database";
		}
    }

    function search_item_recipe_db($words)
    {
        $passkey    = $this -> bot -> core("settings") -> get("Items", "Passkey");
        $botname    = $this -> bot -> botname;
        $botname    = ucfirst(strtolower($botname));
        $salt       = $passkey."_".$botname;
        $checksum   = md5('aocrecipe' . $salt );
        
        $url  = $this->bot->core("settings")->get("Items", "CIDB")."/".$this->bot->core("settings")->get("Items", "RecipeSearch");
        $url .= '?search='.urlencode($words);
        $url .= '&botname='.urlencode($this->bot->botname);
        $url .= '&pre='.urlencode($this -> bot -> commpre);
        $url .= '&checksum='.urlencode($checksum);

		if ($this -> bot -> core("settings") -> get("Items", "LogURL"))
		{
			$this->bot->log("Items", "URL", "$url");
		}

        $result = $this -> bot -> core("tools") -> get_site($url, 1);
		
        if (!empty($result))
		{
            return $result;
		}
        else
		{
            return "Error in query to the database";
		}
    }
}
?>
