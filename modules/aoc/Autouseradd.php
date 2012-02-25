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
* Autouseradd v1.0, By Noer
* Improved by Temar
* This module automatically adds new users it sees chat on the guildchat to the user database.
*
*
*/
$AutoUserAdd = new AutoUserAdd($bot);
class AutoUserAdd extends BasePassiveModule
{
    private $checked;

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_event("gmsg", "org");
        $this->register_module("autouseradd");
        $this->bot->core("settings")->create("Autouseradd", "Enabled", TRUE, "Should users be added to the Bot?");
        $this->bot->core("settings")->create("Autouseradd", "Notify", TRUE, "Should the User be Notified that he was added to the Bot?");
        // Fill checked array with current members, we won't need to readd them:
        $this->checked = array();
        $mems = $this->bot->db->select("SELECT nickname FROM #___users WHERE user_level = 2", MYSQL_ASSOC);
        if (!empty($mems)) {
            foreach ($mems as $mem)
            {
                $this->checked[$mem['nickname']] = TRUE;
            }
        }
    }

    function register(&$module)
    {
        $this->hooks[] = &$module;
    }

    function gmsg($name, $group, $msg)
    {
        if (!$this->bot->core("settings")->get("Autouseradd", "Enabled"))
            Return;
        // Add all characters when they are noticed in chat the first time:
        if (!isset($this->checked[$name])) {
            $this->checked[$name] = TRUE;
            $result = $this->bot->db->select("SELECT user_level FROM #___users WHERE nickname = '" . $name . "'");
            if (!empty($result)) {
                if ($result[0][0] != 2) {
                    $this->add_user($name);
                }
            }
            else
            {
                $this->add_user($name);
            }
        }
    }

    function add_user($name)
    {
        if ($this->bot->core("settings")->get("Autouseradd", "Notify"))
            $silent = 0;
        else
            $silent = 1;
        $this->bot->core("user")->add($this->bot->botname, $name, 0, MEMBER, $silent);
        if (!empty($this->hooks)) {
            foreach ($this->hooks as $hook)
            {
                $hook->new_user($name);
            }
        }
    }
}

?>