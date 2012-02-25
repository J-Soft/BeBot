<?php
/*
* GUI to add amd remove shortcuts
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
$shortcut_gui = new ShortCutGUI($bot);
class ShortCutGUI extends BaseActiveModule
{

  function __construct(&$bot)
  {
    parent::__construct($bot, get_class($this));
    $this->register_command("all", "shortcuts", "SUPERADMIN");
    $this->help['description'] = "Allows you view, add and delete entries in the shortcut database.";
    $this->help['command']['shortcuts'] = "Shows currently existing shortcuts with corresponding long entries and allows deleting selected entries.";
    $this->help['command']['shortcuts add "<short>" "<long>"'] = "Adds <short> as shortcut for <long> to the database. Neither <short> nor <long> can contain any \".";
  }

  function command_handler($name, $msg, $origin)
  {
    if (preg_match("/^shortcuts$/i", $msg)) {
      return $this->show_shortcuts();
    }
    elseif (preg_match("/^shortcuts add &quot;(.*)&quot; &quot;(.*)&quot;$/i", $msg, $info))
    {
      return $this->add($info[1], $info[2]);
    }
    elseif (preg_match("/^shortcuts del ([01-9]+)$/i", $msg, $info))
    {
      return $this->del($info[1]);
    }
  }

  function show_shortcuts()
  {
    $shortcuts = $this->bot->db->select("SELECT shortcut, long_desc, id FROM #___shortcuts ORDER BY shortcut ASC");
    if (empty($shortcuts)) {
      return "No shortcuts defined!";
    }
    $blob = "##ao_infoheader##The following shortcuts are defined:##end##\n";
    foreach ($shortcuts as $shortcut)
    {
      $blob .= "\n##ao_infotext##" . stripslashes($shortcut[0]) . " ##end##short for##ao_infotext## ";
      $blob .= stripslashes($shortcut[1]) . "##end## ";
      $blob .= $this->bot->core("tools")->chatcmd("shortcuts del " . $shortcut[2], "[DELETE]");
    }
    return $this->bot->core("tools")->make_blob("Defined shortcuts", $blob);
  }

  function add($short, $long)
  {
    return $this->bot->core("shortcuts")->add($short, $long);
  }

  function del($id)
  {
    return $this->bot->core("shortcuts")->delete_id($id);
  }
}

?>