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
$preferences = new Preferences_GUI($bot);
/*
The Class itself...
*/
class Preferences_GUI extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        //Create access settings for this module
        $this->register_command("all", "preferences", "MEMBER", array('default' => 'SUPERADMIN'));
        $this->register_alias("preferences", "prefs");
        $this->help['description']            = 'Player Preferences';
        $this->help['command']['preferences'] = "Shows the preferences interface.";
        $this->help['notes']                  = 'When a default is changed all users who have not customised ';
        $this->help['notes'] .= 'that setting will also have their preferences changed.<br>';
        $this->help['notes'] .= 'When a default is changed from option A to option B and back again ';
        $this->help['notes'] .= 'users who had customised their preference to option B will be reset ';
        $this->help['notes'] .= 'and have option A as default again.';
    }


    function command_handler($name, $msg, $origin)
    {
        $com = $this->parse_com($msg, array('com',
                                            'sub',
                                            'module',
                                            'preference',
                                            'value'));
        switch ($com['sub'])
        {
            case '':
                //No arguments
                return ($this->bot->core("prefs")->show_modules($name));
                break;
            case 'show':
                //Show a spesific preference
                switch ($com['module'])
                {
                    case '':
                        //Show all Modules
                        return ($this->bot->core('prefs')->show_modules($name));
                        break;
                    default:
                        //Show module spesific preferences
                        return ($this->bot->core('prefs')
                            ->show_prefs($name, $com['module']));
                        break;
                }
                break;
            case 'set':
                //Set a given value
                return ($this->bot->core("prefs")
                    ->change($name, $com['module'], $com['preference'], $com['value']));
                break;
            case 'default':
                //Set a default value
                return ($this->bot->core("prefs")
                    ->change_default($name, $com['module'], $com['preference'], $com['value']));
                break;
            case 'reset':
                return ($this->bot->core("prefs")
                    ->reset($name, $com['module']));
                break;
            default:
                $this->error->set("Unknown command ##highlight##'{$com['sub']}'##end##");
                return ($this->error);
        }
    }
}

?>
