<?php
/*
* Maintenance.php - Database Maintenance module.
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
$maintenance = new Maintenance($bot);
/*
The Class itself...
*/
class Maintenance extends BaseActiveModule
{

  /*
  Constructor:
  Hands over a reference to the "Bot" class.
  */
  function __construct(&$bot)
  {
    parent::__construct($bot, get_class($this));
    $this->register_event("cron", "5sec");
    $this->register_event("connect");
    $this->register_module("maintenance");
    // TODO: help
  }

  function command_handler($name, $msg, $origin)
  {
    $msg = strtolower($msg);
    $vars = explode(" ", $msg, 4);
    switch ($vars[0])
    {
      case 'maintenance':
        if (!empty($vars[1])) {
          if (!empty($vars[2])) {
            Switch ($vars[2])
            {
              case 1:
              case 'start':
                return $this->step1($name, $vars[1], $origin);
                break;
              case 'dont':
                return $this->dontdo($name, $vars[3], $origin);
                break;
              case 'check':
              case 'refresh':
                $inside = $this->check($this->old_data, $this->new_data, $this->compare);
                Return ("Maintenance ToDo list: " . $this->bot->core("tools")->make_blob("Click to view", $inside));
                break;
              case 'done':
                Return $this->step3($vars[1]);
                break;
              Default:
                Return ("Error: Unknown Action " . $vars[2]);
                break;
            }
          }
        }
        else
        {
          return $this->main($origin);
        }
      default:
        return "Broken plugin, received unhandled command: $command in Maintenance.php";
    }
  }

  function connect()
  {
    $this->register_command("all", "maintenance", "SUPERADMIN");
    $this->bot->core("settings")->create("Maintenance", "info", "", "Info saved while restarting, blank when not doing maintenance.", NULL, TRUE, 2);
    /*	$info = $this -> bot -> core("settings") -> get("Maintenance", "info");

   if($info != "")
   {
       $rostermod = $this -> bot -> core("roster_core");
       $this -> bot -> unregister_event("cron", "24hour", $rostermod);
   } */
  }

  function cron()
  {
    $this->croncount++;
    Switch ($this->croncount)
    {
      case 1: // Skip first cron just to make sure bot if fully loaded.
        Break;
      case 2:
        $info = $this->bot->core("settings")->get("Maintenance", "info");
        if ($info != "") {
          $info = explode(" ", $info);
          Switch ($info[0])
          {
            case 'settings':
              Switch ($info[3])
              {
                case 2:
                  $this->step2($info[1], "settings", $info[2]);
                  break;
                //case 3:
                //	$this -> step3($info[1], "settings", $info[2]);
                //	Break;
                Default:
                  $this->bot->send_output($info[1], "Error: Unknown Step number: " . $info[3], $info[2]);
                  break;
              }
              break;
            Default:
              $this->bot->send_output($info[1], "Error: Unknown Mode: " . $info[0], $info[2]);
              break;
          }
        }
      Default:
        $this->unregister_event("cron", "5sec");
        break;
    }
  }

  function main($origin)
  {
    $info = $this->bot->core("settings")->get("Maintenance", "info");
    if ($info != "") {
      $inside = "##blob_title##     Maintenance Screen - Settings##end##\n\n";
      $inside .= "##blob_text##" . $this->bot->core("tools")->chatcmd("maintenance settings check", "Refresh ToDo list", $origin) . "\n";
      $inside .= $this->bot->core("tools")->chatcmd("maintenance settings done", "Run Settings Maintenance", $origin) . "\n##end##";
      Return ("Maintenance Control Panel :: " . $this->bot->core("tools")->make_blob("Click to view", $inside));
    }
    else
    {
      $inside = "##blob_title##     Maintenance Main Screen##end##\n\n";
      $inside .= "##blob_text##" . $this->bot->core("tools")->chatcmd("maintenance settings start", "Settings", $origin) . " (will restart)\n##end##";
      Return ("Maintenance Control Panel :: " . $this->bot->core("tools")->make_blob("Click to view", $inside));
    }
  }

  function step1($name, $mode, $origin)
  {
    $mode = strtolower($mode);
    Switch ($mode)
    {
      case 'settings':
        $this->bot->core("settings")->save("Maintenance", "info", "settings $name $origin 2");
        $this->bot->send_output("", "Restarting for Maintenance", "both");
        $this->bot->disconnect();
        die("Restarting for Maintinance");
      Default:
        Return ("Error Unknown Maintenance mode: $mode");
    }
  }

  function step2($name, $mode, $origin)
  {
    $mode = strtolower($mode);
    Switch ($mode)
    {
      case 'settings':
        $olddata = $this->bot->db->select("SELECT module, setting, datatype, longdesc, defaultoptions, hidden, disporder FROM #___settings");
        if (!empty($olddata)) {
          foreach ($olddata as $o)
          {
            $this->old_data[strtolower($o[0])][strtolower($o[1])] = array($o[2],
                                                                          $o[3],
                                                                          $o[4],
                                                                          $o[5],
                                                                          $o[6]);
          }
          $this->compare = array("datatype",
                                 "longdesc",
                                 "defaultoptions",
                                 "hidden",
                                 "disporder");
          $inside = $this->check($this->old_data, $this->new_data, $this->compare);
          if (!$inside) {
            $this->bot->send_output($name, "No Maintenance Required for Settings", $origin);
            $this->step3('settings');
          }
          else
          {
            $this->bot->send_output($name, "Maintenance ToDo list: " . $this->bot->core("tools")->make_blob("Click to view", $inside), $origin);
          }
        }
        else
        {
          $this->bot->send_output($name, "Error: Old or New Table is Empty or doesnt Exist", $origin);
        }
        Break;
      Default:
        Return ("Error Unknown Maintenance mode: $mode");
    }
  }

  function check($old, $new, $compare)
  {
    $this->del = array();
    $this->update = array();
    foreach ($old as $mod => $data)
    {
      foreach ($data as $set => $value)
      {
        if (!isset($new[strtolower($mod)][strtolower($set)])) {
          $this->del[] = array($mod,
                               $set);
        }
        else
        {
          if ($compare) {
            foreach ($compare as $id => $name)
            {
              if (!isset($this->dontupdate[$mod][$set][$name]) && $value[$id] != $new[strtolower($mod)][strtolower($set)][$id]) {
                $this->update[] = array($mod,
                                        $set,
                                        $name,
                                        $value[$id],
                                        $new[strtolower($mod)][strtolower($set)][$id]);
              }
            }
          }
        }
      }
    }
    if (empty($this->del) && empty($this->update)) {
      Return FALSE;
    }
    $inside = ":: Settings Maintenance ::\n\n";
    $inside .= " ::: " . $this->bot->core("tools")->chatcmd("maintenance settings check", "refresh") . " ::: " . $this->bot->core("tools")->chatcmd("maintenance settings done", "done") . " ::: \n\n";
    $inside .= "Delete:\n";
    if (!empty($this->del)) {
      foreach ($this->del as $d)
      {
        $inside .= "    " . $d[0] . " => " . $d[1] . "  " . $this->bot->core("tools")->chatcmd("maintenance settings dont del " . $d[0] . " " . $d[1], "[Dont Delete]") . "\n";
      }
    }
    $inside .= "\nChange:\n";
    if (!empty($this->update)) {
      foreach ($this->update as $u)
      {
        $inside .= $u[0] . " => " . $u[1] . ":\n    " . $u[2] . ": " . $u[3] . " => " . $u[4] . "  " . $this->bot->core("tools")->chatcmd("maintenance settings dont update " . $u[0] . " " . $u[1] . " " . $u[2], "[Dont Change]") . "\n";
      }
    }
    $inside .= "\n ::: " . $this->bot->core("tools")->chatcmd("maintenance settings check", "refresh") . " ::: " . $this->bot->core("tools")->chatcmd("maintenance settings done", "done") . " ::: ";
    Return $inside;
  }

  function dontdo($name, $msg, $origin)
  {
    $msg = explode(" ", $msg, 4);
    Switch ($msg[0])
    {
      case 'del':
        if (isset($this->old_data[$msg[1]][$msg[2]])) {
          unset($this->old_data[$msg[1]][$msg[2]]);
          Return ($msg[1] . " => " . $msg[2] . " Will not be Deleted.");
        }
        else
        {
          Return ("Error: Setting " . $msg[1] . " => " . $msg[2] . " Not found");
        }
      case 'update':
        if (isset($this->old_data[$msg[1]][$msg[2]])) {
          $compare = array_flip($this->compare);
          if (isset($compare[$msg[3]])) {
            $this->dontupdate[$msg[1]][$msg[2]][$msg[3]] = TRUE;
            Return ($msg[1] . " => " . $msg[2] . " => " . $msg[3] . " Will not be Changed.");
          }
          else
          {
            Return ("Error: Field " . $msg[3] . " Not found");
          }
        }
        else
        {
          Return ("Error: Setting " . $msg[1] . " => " . $msg[2] . " Not found");
        }
      Default:
        Return ("Error: Unknown Action (Valid: del, update)");
    }
  }

  function step3($mode)
  {
    $mode = strtolower($mode);
    Switch ($mode)
    {
      case 'settings':
        $info = $this->bot->core("settings")->get("Maintenance", "info");
        if ($info != "") {
          $this->check($this->old_data, $this->new_data, $this->compare);
          if (!empty($this->del)) {
            foreach ($this->del as $del)
            {
              $this->bot->db->query("DELETE FROM #___settings WHERE module = '" . $del[0] . "' AND setting = '" . $del[1] . "'");
            }
          }
          if (!empty($this->update)) {
            foreach ($this->update as $up)
            {
              $this->bot->db->query("UPDATE #___settings SET " . $up[2] . " = '" . $up[4] . "' WHERE module = '" . $up[0] . "' AND setting = '" . $up[1] . "'");
            }
          }
          $this->bot->core("settings")->save("Maintenance", "info", "");
          $this->bot->send_output("", "Maintenance Complete", "both");
          $this->bot->core("settings")->load_all();
          Return FALSE;
        }
        else
        {
          Return ("Error: Maintenance Table for Settings Not Found. Aborting.");
        }
        Break;
      Default:
        Return ("Error Unknown Maintenance mode: $mode");
    }
  }
}

?>