<?php
/*
* Bots.php - Module Manage Online / Offline Status monitoring and Statistics.
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
$botstatisticsui = new BotStatisticsUI($bot);
class BotStatisticsUI extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command("all", "bots", "MEMBER");
    }


    function command_handler($name, $msg, $origin)
    {
        $var = explode(" ", $msg, 2);
        $command = $var[0];
        switch ($var[0]) {
            case 'bots':
                $reply = $this->check_bots($name, $origin, $var[1]);
                if ($reply !== false) {
                    Return ($reply);
                }
            default:
                Return ("##error##Error : Broken plugin, received unhandled command: ##highlight##" . $var[0] . "##end## in Bots.php##end##");
        }
    }


    function check_bots($name, $origin, $msg)
    {
        if (!$this->bot->accessallbots) {
            $msg = $this->bot->botname . " " . $this->bot->dimension;
        }
        if (!empty($msg)) {
            $msg = explode(" ", $msg, 2);
            if (!empty($msg[1])) {
                Return $this->bot->core("bot_statistics")
                    ->check_bots($name, $origin, $msg[0], $msg[1]);
            } else {
                Return $this->bot->core("bot_statistics")
                    ->check_bots($name, $origin, $msg[0]);
            }
        } else {
            Return $this->bot->core("bot_statistics")
                ->check_bots($name, $origin);
        }
    }
}

?>
