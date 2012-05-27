<?php
/*
* Items.php - Item handling for AoC
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
$items_core = new Items_Core($bot);
class Items_Core extends BasePassiveModule
{
    var $server = 'http://aocdb.lunevo.net/';
    var $itemPattern = '(<a style="text-decoration:none" href="itemref:\/\/([0-9]*)\/([0-9]*)\/([0-9]*)\/([0-9a-f]*\:[0-9a-f]*\:[0-9a-f]*:[0-9a-f]*)\/([0-9a-f]*\:[0-9a-f]*\:[0-9a-f]*:[0-9a-f]*)"><font color=#([0-9a-f]*)>\[([\\-a-zA-Z0-9_\'&\s\-]*)\]<\/font><\/a>)';


    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_module("items");
    }


    /*
    Takes an item string and returns an array of item arrays - each with lowid, highid, ql, lowcrc, highcc, colour and name.
    If $item is unparsable it returns a BotError
    */
    function parse_items($itemText)
    {
        $items = array();
        $count = preg_match_all('/' . $this->itemPattern . '/i', $itemText, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $item['lowid'] = $match[2];
            $item['highid'] = $match[3];
            $item['ql'] = $match[4];
            $item['lowcrc'] = $match[5];
            $item['highcrc'] = $match[6];
            $item['colour'] = $match[7];
            $item['name'] = $match[8];
            $items[] = $item;
        }
        return $items;
    }


    /*
    Creates a text blob.  Alternate uses ' instead of ".
    */
    function make_item($item, $alternate = FALSE)
    {
        if (empty($item)) {
            return '';
        }
        if ($alternate) {
            return '<a style="text-decoration:none" href="itemref://' . $item['lowid'] . '/' . $item['highid'] . '/' . $item['ql'] . '/' . $item['lowcrc'] . '/' . $item['highcrc']
                . '"><font color=#' . $item['colour'] . '>[' . $item['name'] . ']</font></a>';
        }
        else {
            return "<a style='text-decoration:none' href='itemref://" . $item['lowid'] . "/" . $item['highid'] . "/" . $item['ql'] . "/" . $item['lowcrc'] . "/" . $item['highcrc']
                . "'><font color=#" . $item['colour'] . ">[" . $item['name'] . "]</font></a>";
        }
    }


    //Returns true if $item is an itemref, false otherwise.
    function is_item($item)
    {
        if (1 > preg_match('/' . $this->itemPattern . '/i', $item)) {
            return FALSE;
        }
        return TRUE;
    }


    function submit_item($item, $name)
    {
        if (empty($item)) {
            return -1;
        }
        $checksum = md5(
            'aocitems' + $item['lowid'] + $item['highid'] + $item['ql'] + $item['lowcrc'] + $item['highcrc'] + $item['colour'] + $item['itemname'] + $this->bot->dimension
                + $this->bot->guild + $name
        );
        $url = $this->server . "botsubmit/v3/";
        $url .= '?lowid=' . urlencode($item['lowid']);
        $url .= '&highid=' . urlencode($item['highid']);
        $url .= '&ql=' . urlencode($item['ql']);
        $url .= '&lowcrc=' . urlencode($item['lowcrc']);
        $url .= '&highcrc=' . urlencode($item['highcrc']);
        $url .= '&color=' . urlencode($item['colour']);
        $url .= '&name=' . urlencode($item['name']);
        $url .= '&server=' . urlencode($this->bot->dimension);
        $url .= '&guildname=' . urlencode($this->bot->guild);
        $url .= '&username=' . urlencode($name);
        $url .= '&checksum=' . urlencode($checksum);
        return $this->bot->core("tools")->get_site($url, 1);
    }


    function search_item_db_details($words)
    {
        $url = $this->server . "botsearch/";
        $url .= '?single=1';
        $url .= '&id=' . $words;
        $result = $this->bot->core("tools")->get_site($url, 1);
        //A comment explaining the logic of this check would be appreciated! Why are we looking for mysql_real_escape_string here?
        if (strstr($result, 'mysql_real_escape_string') !== FALSE) {
            return ("Error in query to database");
        }
        return $result;
    }


    function search_item_db($words)
    {
        $url = $this->server . "botsearch/";
        $url .= '?search=' . urlencode($words);
        $url .= '&botname=' . $this->bot->botname;
        $url .= '&pre=' . urlencode($this->bot->commpre);
        return $this->bot->core("tools")->get_site($url, 1);
    }
}

?>
