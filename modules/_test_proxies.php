<?php
/*
* Proxy Test Plugin, By Ebag333
* A useful little plugin to test any proxies that are setup.
* Disabled by default as most people will never need it.
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
$testproxy = new testproxy($bot);
class testproxy extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command('tell', 'testproxy', 'SUPERADMIN');
        $this->help['description'] = 'This plugin runs through all the proxies that have been setup and tests each of them.';
    }


    function command_handler($name, $msg, $origin)
    {
        $strip_headers = 0;
        $server_timeout = 25;
        $read_timeout = 30;
        $url = "http://people.anarchy-online.com/character/bio/d/2/name/ebagmp/bio.xml";
        foreach ($this->bot->proxy_server_address as $proxy) {
            $result = $this->bot->core("tools")
                ->get_site_data($url, $strip_headers, $server_timeout, $read_timeout, $proxy);
            if ($result["error"] == TRUE || $result["error"] == 1) {
                $status = "Failed\n" . $result["errordesc"];
            }
            else {
                $status = "Good";
            }
            $blob .= $proxy . " - " . $status . "\n\n";
        }
        return "Results for Proxies :: " . $this->bot->core("tools")
            ->make_blob("click to view", $blob);
    }
}

?>
