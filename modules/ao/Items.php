<?php
/**
 * Items database changed to Xyphos.
 *
 * Central Items Database v1.1, By Vhab
 * Latest version can always be found at: http://bebot.shadow-realm.org/index.php/topic,380.0.html
 * Details about the database itself: http://aodevs.com/index.php/topic,84.0.html
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
$VhItems = new VhItems($bot);
class VhItems extends BaseActiveModule
{
  var $icons = 'true';
  var $color_header = 'DFDF00';
  var $color_highlight = '97BE37';
  var $color_normal = 'CCF0AD';
  var $server = 'http://cidb.xyphos.com/';
  var $max = 50;

  function __construct(&$bot)
  {
    parent::__construct($bot, get_class($this));
    $this->register_command('all', 'items', 'GUEST');
    $this->help['description'] = 'Searches the central database for information about an item.';
    $this->help['command']['items [ql] <item>'] = "Searches and displays information about an <item> of the optional [ql]";
    $this->help['notes'] = "This module uses the Xyphos Items Database .";
  }

  function command_handler($name, $msg, $origin)
  {
    if (preg_match('/^items/i', $msg, $info)) {
      $words = trim(substr($msg, strlen('items')));
      if (!empty($words)) {
        $parts = explode(' ', $words);
        if (count($parts) > 1 && is_numeric($parts[0])) {
          $ql = $parts[0];
          unset($parts[0]);
          $search = implode(' ', $parts);
        }
        else
        {
          $ql = 0;
          $search = $words;
        }
        $url = $this->server;
        $url .= '?bot=BeBot';
        $url .= '&output=aoml';
        $url .= '&max=' . $this->max;
        $url .= '&search=' . urlencode($search);
        $url .= '&ql=' . $ql;
        $url .= '&icons=' . $this->icons;
        if ($this->color_header) {
          $url .= '&color_header=' . $this->color_header;
        }
        if ($this->color_highlight) {
          $url .= '&color_highlight=' . $this->color_highlight;
        }
        if ($this->color_normal) {
          $url .= '&color_normal=' . $this->color_normal;
        }
        $result = $this->bot->core("tools")->get_site($url, 1);
        //Again I do not see why we're looking for mysql_real_escape_string
        if (strstr($result, 'mysql_real_escape_string') !== false) {
          return ("Recieved garbled reply from vhabot!");
        }
        return $result;
      }
      else
      {
        return "Usage: items [quality] [item]";
      }
    }
    else
    {
      $this->bot->send_help($name);
    }
  }
}

?>