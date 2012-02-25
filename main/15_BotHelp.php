<?php
/*
* BotHelp.php - Bot Help Systems
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

$bothelp_core = new BotHelp_Core($bot);

/*
The Class itself...
*/
class BotHelp_Core extends BaseActiveModule
{
    private $help_cache;

    /*
     Constructor:
     Hands over a referance to the "Bot" class.
     */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));

        $this->register_module("help");
        $this->register_command("all", "help", "GUEST");

        $this->help['description'] = "The bot help system.";
        $this->help['command']['help [command]'] = "Shows help on [command]. If no argument is given shows the help menu";
        $this->help['notes'] = "No notes";
    }

    function command_handler($name, $msg, $origin)
    {
        $vars = explode(' ', $msg);
        unset($vars[0]);

        if (empty($this->help_cache)) {
            $this->update_cache();
        }

        if (!isset($vars[1])) {
            return ($this->show_help_menu($name, 'source', $origin));
        }
        else
        {
            switch ($vars[1])
            {
                case 'tell':
                case 'gc':
                case 'pgmsg':
                    return ($this->show_help_menu($name, $vars[1]));
                    break;
                default:
                    return ($this->show_help($name, $vars[1]));
                    break;
            }
        }
    }


    function show_help_menu($name, $section = 'source', $origin = FALSE)
    {
        switch ($section)
        {
            case 'source':
                switch ($origin)
                {
                    case 'tell':
                        $window = $this->get_commands($name, 'tell');
                        break;
                    case 'gc':
                        $window = $this->get_commands($name, 'gc');
                        break;
                    case 'pgmsg':
                        $window = $this->get_commands($name, 'pgmsg');
                        break;
                }
                return ($this->bot->core("tools")->make_blob('Help', $window));
                break;
            default:
                $window = $this->get_commands($name, $section);
                return ($this->bot->core("tools")->make_blob('Help', $window));
                break;
        }
    }


    /*
     Gets commands for a given channel
     */
    function get_commands($name, $channel)
    {
        $channel = strtolower($channel);
        $lvl = $this->bot->core("security")->get_access_name($this->bot->core("security")->get_access_level($name));
        $window = ":: BeBot Help ::\n\n" . $this->help_cache[$channel][$lvl];
        return $window;
    }

    function update_cache()
    {
        $this->make_help_blobs("tell");
        $this->make_help_blobs("pgmsg");
        $this->make_help_blobs("gc");
    }

    function make_help_blobs($channel)
    {
        $channel = strtolower($channel);
        $this->help_cache[$channel] = array();
        foreach ($this->bot->core("access_control")->get_access_levels() as $lvl)
        {
            $this->help_cache[$channel][$lvl] = "";
        }
        unset($this->help_cache[$channel]["DISABLED"]);
        unset($this->help_cache[$channel]["DELETED"]);

        ksort($this->bot->commands[$channel]);
        foreach ($this->bot->commands[$channel] as $command => $module)
        {
            if (is_array($module->help)) {
                $cmdstr = $this->bot->core("tools")->chatcmd("help " . $command, $command) . " ";
            }
            else
            {
                $cmdstr = $command . " ";
            }
            switch ($this->bot->core("access_control")->get_min_access_level($command, $channel))
            {
                case ANONYMOUS:
                    $this->help_cache[$channel]['ANONYMOUS'] .= $cmdstr;
                case GUEST:
                    $this->help_cache[$channel]['GUEST'] .= $cmdstr;
                case MEMBER:
                    $this->help_cache[$channel]['MEMBER'] .= $cmdstr;
                case LEADER:
                    $this->help_cache[$channel]['LEADER'] .= $cmdstr;
                case ADMIN:
                    $this->help_cache[$channel]['ADMIN'] .= $cmdstr;
                case SUPERADMIN:
                    $this->help_cache[$channel]['SUPERADMIN'] .= $cmdstr;
                case OWNER:
                    $this->help_cache[$channel]['OWNER'] .= $cmdstr;
                    break;
                default:
                    break;
            }
            unset($cmdstr);
        }
    }

    function show_help($name, $command)
    {
        if (!$this->bot->core("access_control")->check_for_access($name, $command)) {
            return ("##highlight##$command##end## does not exist or you do not have access to it.");
        }
        elseif (!empty($this->bot->commands['tell'][$command]))
        {
            $com = $this->bot->commands['tell'][$command];
        }
        elseif (!empty($this->bot->commands['gc'][$command]))
        {
            $com = $this->bot->commands['gc'][$command];
        }
        elseif (!empty($this->bot->commands['pgmsg'][$command]))
        {
            $com = $this->bot->commands['pgmsg'][$command];
        }
        else
        {
            return ("##highlight##$command##end## does not exist or you do not have access to it.");
        }
        $window = "##blob_title## ::::: HELP ON " . strtoupper($command) . " :::::##end##<br><br>";
        if (isset($com->help)) {
            $help = $com->help;
            $window .= '##highlight##' . $help['description'] . '##end##<br><br>';
            $module_commands = array();
            foreach ($help['command'] as $key => $value)
            {
                // Only show help for the specific command, not all help for module!
                $parts = explode(' ', $key, 2);
                if (strcasecmp($command, $parts[0]) == 0) {
                    $key = str_replace('<', '&lt;', $key);
                    $value = str_replace('<', '&lt;', $value);
                    $window .= " ##highlight##<pre>$key##end## - ##blob_text##$value##end##<br>";
                }
                else
                {
                    if ($this->bot->core("access_control")->check_for_access($name, $parts[0])) {
                        $module_commands[$parts[0]] = $this->bot->core("tools")->chatcmd("help " . $parts[0], $parts[0]);
                    }
                }
            }
            $window .= '<br>##blob_title##NOTES:##end##<br>##blob_text##' . $help['notes'] . '##end##';
            if (!empty($module_commands)) {
                ksort($module_commands);
                $window .= "<br><br>##blob_title##OTHER COMMANDS OF THIS MODULE:##end##<br>";
                $window .= implode(" ", $module_commands);
            }
        }
        else
        {
            $window .= '##error##No Help Found##end##';
        }
        return ('help on ' . $this->bot->core("tools")->make_blob($command, $window));
    }
}

?>