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
class message
{
    public $source;
    public $sender;
    public $destination = array();
    public $message;

    function __construct($source, $sender, $message)
    {
        $this->source = $source;
        $this->sender = $sender;
        $this->message = $message;
    }

    function set_destination($destination, $overwrite = false)
    {
        if (empty($this->destination)) {
            $this->destination[] = $destination;
            return true;
        }
        else
        {
            return false;
        }
    }

    function add_destination($destination)
    {
        if (!in_array($destination, $this->destination)) {
            $this->destination[] = $destination;
        }
    }
}

?>