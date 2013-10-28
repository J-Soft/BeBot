<?php
/*
* StringFilterInterface.php - Interface for StringFilter.
* Created by Andrew Zbikowski <andyzib@gmail.com> (AKA Glarawyn, RK1)
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
$stringfilter_interface = new StringFilter_Interface($bot);
/*
The Class itself...
*/
class StringFilter_Interface extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command('all', 'filter', 'ADMIN');
        $this->help['description'] = 'Add and remove strings to the bot\'s filter.';
        $this->help['command']['filter'] = "- Display the current string filter list.";
        $this->help['command']['filter add <string>'] = "- Replace <strong> with default replacment text.";
        $this->help['command']['filter add <string1> replace: <string2>'] = "- Replace <string1> with <string2>.";
        $this->help['command']['filter rem <string>'] = "Remove <string> from the list.";
    }


    function command_handler($name, $msg, $type)
    {
        // preg_match just works better than explode for string based input that may have spaces.
        if (preg_match("/^filter add (.+?) replace: (.+)$/i", $msg, $info)) {
            return $this->add($info[1], $info[2]);
        } else {
            if (preg_match("/^filter add (.+?)$/i", $msg, $info)) {
                return $this->add($info[1]);
            } else {
                if (preg_match("/^filter rem (.+)$/i", $msg, $info)) {
                    return $this->rem($info[1]);
                } else {
                    return $this->show($name);
                }
            }
        }
    }


    function add($string, $new = null)
    {
        return $this->bot->core("stringfilter")->add_string($string, $new);
    }


    function rem($string)
    {
        return $this->bot->core("stringfilter")->rem_string($string);
    }


    function show($source)
    {
        $return = $this->bot->core("stringfilter")->get_strings();
        $inside = "Filtered String List:\n\n";
        foreach ($return as $string => $replace) {
            $inside .= "Search for: \"" . $string . "\" Replace with: \"" . $replace . "\" " . $this->bot
                    ->core("tools")->chatcmd("filter rem " . $string, "[REMOVE]");
            $inside .= "\n";
        }
        return $this->bot->core("tools")
            ->make_blob("Filtered String List", $inside);
    }
}

?>
