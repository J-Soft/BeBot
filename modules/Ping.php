<?php
/*
* Ping.php - Pings the chat server.
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
$ping = new ping($bot);
/*
The Class itself...
*/
class ping extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->verify = array();
        $this->register_command('all', 'ping', 'OWNER');
        $this->register_command('all', 'tracert', 'OWNER');
        $this->help['description'] = 'Runs a ping or trace route to chat server that the bot is currently running on.';
        $this->help['command']['ping'] = "Pings the current chat server and shows the result.";
        $this->help['command']['tracert'] = "Runs a trace route to the current chat server and shows the result.";
        $this->bot->core("settings")->create("Ping", "Server", "Windows", "Is the server running Windows or Linux/Unix?", "Windows;Linux");
        $this->bot->core("settings")->create("Ping", "PingCount", 4, "How many times should we ping the server?", "1;2;3;4;5;7;10");
    }

    function command_handler($name, $msg, $origin)
    {
        if (preg_match("/^ping$/i", $msg)) {
            return $this->ping_server();
        }
        else if (preg_match("/^tracert$/i", $msg)) {
            return $this->tracert_server();
        }
    }

    function ping_server()
    {
        $count = $this->bot->core("settings")->get("Ping", "PingCount");
        $host = $this->select_dimension(); //Dimension we're on
        // replace bad chars
        $host = preg_replace("/[^A-Za-z0-9.-]/", "", $host);
        $count = preg_replace("/[^0-9]/", "", $count);
        //check target IP or domain
        if ($this->bot->core("settings")->get("Ping", "Server") == "Linux") {
            $results = system("ping -c$count -w$count $host", $details);
            system("killall ping");
        }
        else
        {
            $results = exec("ping -n $count $host", $details);
        }
        $msg = "<b>Server:</b> " . $host . "\n";
        $msg .= "<b>Ping Count:</b> " . $count . "\n\n";
        $msg .= "<b>Results:</b>\n";
        if (empty($results))
            $msg .= "Could not find results.  Please check <i>!settings ping</i> and verify you have the correct system type selected.";
        else
        {
            foreach ($details as $key => $value)
            {
                $msg .= $value . "\n";
            }
        }
        return "Ping results :: " . $this->bot->core("tools")->make_blob("click to view", $msg);
    }

    function tracert_server()
    {
        $count = $this->bot->core("settings")->get("Ping", "PingCount");
        $host = $this->select_dimension(); //Dimension we're on
        // replace bad chars
        $host = preg_replace("/[^A-Za-z0-9.-]/", "", $host);
        $count = preg_replace("/[^0-9]/", "", $count);
        //check target IP or domain
        if ($this->bot->core("settings")->get("Ping", "Server") == "Linux") {
            $results = system("traceroute $host", $details);
            system("killall -q traceroute");
        }
        else
        {
            $results = exec("tracert $host", $details);
        }
        $msg = "<b>Server:</b> " . $host . "\n";
        $msg .= "<b>Results:</b>\n";
        if (empty($results))
            $msg .= "Could not find results.  Please check <i>!settings ping</i> and verify you have the correct system type selected.";
        else
        {
            foreach ($details as $key => $value)
            {
                $msg .= $value . "\n";
            }
        }
        return "Trace route results :: " . $this->bot->core("tools")->make_blob("Click to view", $msg);
    }

    /*
     Pick the correct dimention
     */
    function select_dimension()
    {
        switch ($this->bot->dimension)
        {
            case 0:
                return "chat.dt.funcom.com";
            case 1:
                return "chat.d1.funcom.com";
            case 2:
                return "chat.d2.funcom.com";
            case 3:
                return "chat.d3.funcom.com";
            default:
                return false;
        }
    }
}

?>