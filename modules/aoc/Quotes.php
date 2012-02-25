<?php
/*
* Quotes.php - Module template.
*
* Developed by Sabkor (RK1)
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
$quotes = new Quotes($bot);
class Quotes extends BaseActiveModule
{

  function __construct(&$bot)
  {
    parent::__construct($bot, get_class($this));
    $this->register_command('all', 'quotes', 'MEMBER');
    $this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("quotes", "false") . "
			(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, quote BLOB, contributor VARCHAR(15))");
    $this->help['description'] = 'Immortalize your friends and enemies.';
    $this->help['command']['quotes'] = "Display a random quote from the database.";
    $this->help['command']['quotes #'] = "Display quote number # from the database.";
    $this->help['command']['quotes add text'] = "Add text to the quotes databases.";
    $this->help['command']['quotes rem #'] = "Remove quote number # from the database.";
    $this->help['command']['quotes remove #'] = "Remove quote number # from the database.";
    $this->help['command']['quotes del #'] = "Remove quote number # from the database.";
    $this->help['command']['quotes delete #'] = "Remove quote number # from the database.";
  }

  function command_handler($name, $msg, $origin)
  {
    if (preg_match("/^quotes ([0-9]+)$/i", $msg, $info)) {
      $msg = $this->send_quote($info[1]);
      $this->bot->send_output($name, $msg, $origin);
      if ($origin == 'gc') {
        $this->bot->send_irc("", "", $msg);
      }
    }
    else if (preg_match("/^quotes add (.+)$/i", $msg, $info)) {
      $msg = $this->add_quote($info[1], $name);
      $this->bot->send_output($name, $msg, $origin);
      if ($origin == 'gc') {
        $this->bot->send_irc("", "", $msg);
      }
    }
    else if (preg_match("/^quotes (remove|del|rem|delete) ([0-9]+)$/i", $msg, $info)) {
      $msg = $this->del_quote($info[2], $name);
      $this->bot->send_output($name, $msg, $origin);
      if ($origin == 'gc') {
        $this->bot->send_irc("", "", $this->del_quote($info[2]));
      }
    }
    else
    {
      $msg = $this->send_quote(-1);
      $this->bot->send_output($name, $msg, $origin);
      if ($origin == 'gc') {
        $this->bot->send_irc("", "", $msg);
      }
    }
  }

  function add_quote($strquote, $name)
  {
    $this->bot->db->query("INSERT INTO #___quotes (quote, contributor) VALUES ('" . addslashes($strquote) . "', '" . $name . "')");
    $num = $this->bot->db->select("SELECT id FROM #___quotes ORDER BY id DESC");
    $strmsg = "Thank you, your quote has been added as id #" . $num[0][0];
    return $strmsg;
  }

  function del_quote($qnum, $name)
  {
    $result = $this->bot->db->select("SELECT * FROM #___quotes WHERE id=" . $qnum);
    if (!empty($result)) {
      $this->bot->db->query("DELETE FROM #___quotes WHERE id=" . $qnum);
      return "Quote removed.";
    }
    else
    {
      $num = $this->bot->db->select("SELECT id FROM #___quotes ORDER BY id DESC");
      return "Quote with id of " . $qnum . " not found. (Highest quote ID is " . $num[0][0] . ".)";
    }
  }

  function send_quote($qnum)
  {
    $strquote = "";
    if ($qnum == -1) {
      $num = $this->bot->db->select("SELECT id FROM #___quotes ORDER BY id DESC");
      $result = $this->bot->db->select("SELECT * FROM #___quotes");
      if (!empty($result)) {
        $found = false;
        while ($found == false)
        {
          $row = $this->bot->core("tools")->my_rand(0, $num[0][0]);
          if (!empty($result[$row][0])) {
            $strquote = "#" . $result[$row][0] . " - " . $result[$row][1] . " [Contributed by: " . $result[$row][2] . "]";
            $found = true;
          }
        }
      }
      else
      {
        $strquote = "No quotes exist. Add some!";
      }
    }
    else
    {
      $result = $this->bot->db->select("SELECT * FROM #___quotes WHERE id=" . $qnum);
      if (!empty($result)) {
        $strquote = "#" . $result[0][0] . " - " . $result[0][1] . " [Contributed by: " . $result[0][2] . "]";
      }
      else
      {
        $num = $this->bot->db->select("SELECT id FROM #___quotes ORDER BY id DESC");
        $strquote = "Quote with id of " . $qnum . " not found. (Highest quote ID is " . $num[0][0] . ".)";
      }
    }
    return $strquote;
  }

  function search_quote($qtext)
  {
    $strquote = "";
    $num = $this->bot->db->select("SELECT id FROM #___quotes WHERE quote LIKE '" . $qtext . "'");
    $result = $this->bot->db->select("SELECT * FROM #___quotes");
    if ($num[0][0] > 0) {
      $found = false;
      while ($found == false)
      {
        $row = $this->bot->core("tools")->my_rand(0, $num[0][0]);
        if (!empty($result[$row][0])) {
          $strquote = "#" . $result[$row][0] . " - " . $result[$row][1] . " [Contributed by: " . $result[$row][2] . "]";
          $found = true;
        }
      }
    }
    else
    {
      $strquote = "No quotes found!";
    }
    return $strquote;
  }
}

?>