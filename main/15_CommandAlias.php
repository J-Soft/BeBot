<?php
/*
* CommandAlias.php
* - Core module to handle command aliases.
*
* Written by Temar
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
$commandalias_core = new CommandAlias_Core($bot);
class CommandAlias_Core extends BasePassiveModule
{
    private $alias;

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_module("command_alias");
        $this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("command_alias", "false") . " (
						alias VARCHAR(100) NOT NULL,
						command VARCHAR(30) NOT NULL
					)");
        $this->alias = array();
        $this->create_caches();
    }

    function create_caches()
    {
        $aliaslist = $this->bot->db->select("SELECT alias, command FROM #___command_alias");
        if (!empty($aliaslist)) {
            foreach ($aliaslist as $alias)
            {
                $this->register($alias[1], $alias[0]);
            }
        }
    }

    function register($command, $alias)
    {
        if (strtolower($alias) == "comalias") {
            echo "Error: comalias can't be added as an command alias\n";
            Return;
        }
        $alias = explode(" ", $alias, 3);
        if (!empty($alias[1]))
            $this->alias_sub[strtolower($alias[0])][strtolower($alias[1])] = $command;
        else
            $this->alias[strtolower($alias[0])] = $command;
    }

    function unregister($alias)
    {
        $alias = strtolower($alias);
        $get = $this->bot->db->select("SELECT alias, command FROM #___command_alias WHERE alias = '" . mysql_real_escape_string($alias) . "'");
        if (empty($get) && isset($this->alias[$alias])) {
            unset($this->alias[$alias]);
            Return TRUE;
            ;
        }
        else
            Return FALSE;
    }

    function replace($msg)
    {
        $msg = explode(" ", $msg, 3);
        if (!empty($msg[1]) && isset($this->alias_sub[strtolower($msg[0])][strtolower($msg[1])])) {
            $msg[0] = $this->alias_sub[strtolower($msg[0])][strtolower($msg[1])];
            unset($msg[1]);
        }
        elseif (isset($this->alias[strtolower($msg[0])]))
        {
            $msg[0] = $this->alias[strtolower($msg[0])];
        }
        $msg = implode(" ", $msg);
        Return $msg;
    }

    function add($msg)
    {
        $var = explode(" ", $msg, 2);
        $var[0] = strtolower($var[0]);
        $var[1] = explode(" ", $var[1], 2);
        $var[1][0] = strtolower($var[1][0]);
        $var[1] = implode(" ", $var[1]);
        if (!isset($this->alias[$var[0]])) {
            if ($var[0] !== "comalias") {
                $this->bot->db->query("INSERT INTO #___command_alias (alias, command) VALUES ('" . mysql_real_escape_string($var[0]) . "', '" . mysql_real_escape_string($var[1]) . "')");
                $this->alias[$var[0]] = $var[1];
                Return ("##highlight##" . $var[0] . "##end## is now an alias of ##highlight##" . $this->alias[$var[0]] . "##end##!");
            }
            else
                Return ("##highlight##" . $var[0] . "##end## Cannot be set as an alias!");
        }
        else
            Return ("##highlight##" . $var[0] . "##end## is already an alias of ##highlight##" . $this->alias[$var[0]] . "##end##!");
    }

    function get_list()
    {
        $aliaslist = $this->bot->db->select("SELECT alias, command FROM #___command_alias");
        if (!empty($aliaslist)) {
            foreach ($aliaslist as $l)
                $tablealiases[$l[0]] = $l[1];
        }
        if (!empty($this->alias)) {
            $inside = ":: Command aliases ::\n\n";
            foreach ($this->alias as $key => $value)
            {
                $inside .= "##orange##" . $key . "##end## is an alias of ##orange##" . $value . "##end##.";
                if (isset($tablealiases[$key])) {
                    $inside .= " " . $this->bot->core("tools")->chatcmd("comalias del " . $key, "[DELETE]");
                }
                $inside .= "\n";
            }
            Return "Command aliases :: " . $this->bot->core("tools")->make_blob("click to view", $inside);
        }
        else
        {
            Return "No command aliases set!";
        }
    }

    function exists($alias)
    {
        $alias = strtolower($alias);
        if (isset($this->alias[$alias])) {
            Return TRUE;
        }
        else
        {
            Return FALSE;
        }
    }

    function del($alias)
    {
        $alias = strtolower($alias);
        $get = $this->bot->db->select("SELECT alias, command FROM #___command_alias WHERE alias = '" . mysql_real_escape_string($alias) . "'");
        if (!empty($get)) {
            $this->bot->db->query("DELETE FROM #___command_alias WHERE alias = '" . mysql_real_escape_string($alias) . "'");
            unset($this->alias[$alias]);
            Return "Alias ##highlight##" . $alias . "##end## deleted.";
        }
        else if (isset($this->alias[$alias]))
            Return "Alias ##highlight##" . $alias . "##end## cannot be deleted.";
        else
            Return "Alias ##highlight##" . $alias . "##end## not found.";
    }
}

?>