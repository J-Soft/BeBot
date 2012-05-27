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
///////////////////////////////////
// nroll.php 1.1 for BeBot
///////////////////////////////////
// (c) Copyright 2007 by Philip Stolarczyk
// All Rights Reserved
// Licensed for distribution under the GPL (Gnu General Public License) version 2.0 or later
///////////////////////////////////
$nroll = new Nroll($bot);
//////////////////////////////////////////////////////////////////////
// The Class itself...
class Nroll extends BaseActiveModule
{
    var $bot;
    var $help;
    var $verifyresult;
    var $verifytime;
    var $verifyname;


    // Constructor
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->output = "group";
        $this->result = "";
        $this->verifyresult = array();
        $this->verifytime = array();
        $this->verifyname = array();
        $this->register_command("all", "nroll", "GUEST");
        $this->register_command("all", "nverify", "GUEST");
        $this->help['description'] = 'Randomly choose one of several options.';
        $this->help['command']['nroll keywords'] = "Randomly choose one of several keywords, seperated by commas or if no commas are present, by spaces.";
        $this->help['command']['nverify #'] = "Verify a previous nroll.";
    }


    function command_handler($name, $msg, $origin)
    {
        $output = "";
        if (preg_match("/^nroll (.+)$/i", $msg, $info)) {
            $options_str = $info[1];
            $options = array();
            if (strpos($options_str, ",") === FALSE) {
                $options = split(" ", $options_str);
            }
            else {
                $options = split(",", $options_str);
            }
            $result = $options[array_rand($options)];
            $this->verifyresult[] = $result;
            $this->verifytime[] = time();
            $this->verifyname[] = $name;
            end($this->verifyresult);
            $output = "I choose <font color=yellow>$result</font>.  To verify, /tell <botname> <pre>nverify " . key($this->verifyresult);
            //$this -> bot -> send_output($name, $output, $origin);
        }
        elseif (preg_match("/^nverify (.+)$/i", $msg, $info)) {
            if (isset($this->verifyresult[$info[1]])) {
                $output
                    = "I chose <font color=yellow>" . ($this->verifyresult[$info[1]]) . "</font> for <font color=green>" . $this->verifyname[$info[1]] . "</font> <font color=red>"
                    . (time() - $this->verifytime[$info[1]]) . "</font> seconds ago.";
            }
            else {
                $output = "Results not found.  Please check your query and try again.  If that doesn't work, give up, it ain't worth it.";
            }
            //$this -> bot -> send_output($name, $output, $origin);
        }
        return $output;
    }
}

?>
