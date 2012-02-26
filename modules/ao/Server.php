<?php
/*
* Server.php - Show the server load.
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
/*
Add a "_" at the beginning of the file (_ClassName.php) if you do not want it to be loaded.
*/
$server = new Server($bot);
/*
The Class itself...
*/
class Server extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command('all', 'server', 'MEMBER');
        $this->help['description']       = 'Shows the server load.';
        $this->help['command']['server'] = "Shows the load on the server.";
    }


    function command_handler($name, $msg, $origin)
    {
        return $this->server_load();
    }


    /*
    Show server load
    */
    function server_load()
    {
        $server = $this->bot->core("tools")
            ->get_site("http://probes.funcom.com/ao.xml");
        $server = explode("name=\"" . $this->select_dimension() . "\"", $server);
        $server = explode("</dimension>", $server[1]);
        preg_match("/display-name=\"(.+)\"/U", $server[0], $info);
        $dim_name   = $info[1];
        $pfs        = explode("<playfield", $server[0]);
        $playfields = array();
        for ($i = 1; $i < count($pfs); $i++)
        {
            if (preg_match("/name=\"(.+)\" status=\"([0-9])\" load=\"([0-9])\" players=\"(.+)%\"/i", $pfs[$i], $info)) {
                //$playfields[$info[1]] = $info[4];
                if ($info[3] == 0) {
                    $info[3] = "Low";
                }
                else if ($info[3] == 1) {
                    $info[3] = "Med";
                }
                else if ($info[3] == 2) {
                    $info[3] = "High";
                }
                else
                {
                    $info[3] = "Unknown";
                }
                $playfields[$info[1]]['status']  = $info[2];
                $playfields[$info[1]]['load']    = $info[3];
                $playfields[$info[1]]['players'] = $info[4];
            }
        }
        $inside = "##ao_ccheader##:::: " . $dim_name . " Server Status ::::##end####lightyellow##\n\n";
        $inside .= "##lightyellow##Player distribution in % of total players online.##end##\n\n";
        $inside .= "<font color=CCInfoHeader>Servers (Playfield: Load - Players)##end##\n";
        foreach ($playfields as $key => $val)
        {
            if ($playfields[$key]['status'] == 1) {
                $inside .= " " . $key . ": ##highlight##" . $playfields[$key]['load'] . " - " . $playfields[$key]['players'] . "%##end##\n";
            }
            else if ($playfields[$key]['status'] == 2) {
                $inside .= " ##yellow##" . $key . "##end##: ##highlight##" . $playfields[$key]['load'] . " - " . $playfields[$key]['players'] . "%##end##\n";
            }
            else
            {
                $inside .= " ##red##" . $key . "##end##: ##highlight##" . $playfields[$key]['load'] . " - " . $playfields[$key]['players'] . "%##end##\n";
            }
        }
        if (count($playfields) < 10) {
            return "Could not access server information";
        }
        else
        {
            return "Status of servers on " . $dim_name . ": " . $this->bot
                ->core("tools")->make_blob("click to view", $inside);
        }
    }


    /*
    Pick the correct dimention
    */
    function select_dimension()
    {
        switch ($this->bot->dimension)
        {
            case '0':
                $return = "dt";
                break;
            case '1':
                $return = "d1";
                break;
            case '2':
                $return = "d2";
                break;
            case '3':
                $return = "d3";
                break;
        }
        return $return;
    }
}

?>
