<?php
/*
* Time.php - Time Module.
* Displays current time and provides time related functions.
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
$time_core = new Time_Core($bot);
/*
The Class itself...
*/
class Time_Core extends BaseActiveModule
{ // Start Class

    /*
     Constructor:
     Hands over a referance to the "Bot" class.
     */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_module("time");
        $this->register_command("all", "time", "GUEST");
        $this->help['description'] = "Shows the time.";
        $this->help['command']['time'] = "Shows the current time.";
        $this->bot->core("settings")->create("Time", "FormatString", "F jS, Y H:i", "The format string used in all gmdate() calls throughout the bot. For more info check the help to gmdate() in the php manual. WARNING: DO NOT CHANGE THIS IF YOU DON'T KNOW WHAT THIS MEANS! Wrong entries will break the time display throughout the bot!");
    }

    /*
     This function handles all the inputs and returns FALSE if the
     handler should not send output, otherwise returns a string
     sutible for output via send_tell, send_pgroup, and send_gc.
     */
    function command_handler($name, $msg, $source)
    { // Start function handler()
        return $this->show_time();
    } // End function handler()

    function show_time()
    { // Start function show_time()
        $output = "It is currently " . gmdate("H:i:s F j,");
        if ($this->bot->game == "ao") {
            $output .= " " . $this->ao_year() . " Rubi-Ka Universal Time. ";
            $e1 = " from Uncle Pumpkin-head";
            $e2 = "Leet";
        }
        else
            $e2 = "Conan";
        if (gmdate("n") == 10 && gmdate("j") == 31) // OMG Pumpkinsheads!
            $output .= "##darkorange##Happy Halloween" . $e1 . "!!##end##";
        if (gmdate("n") == 12 && gmdate("j") == 25) // OMG Christmas!
            $output .= "##red##Merry##end## ##lime##Christmas##end## ##red##from##end## ##lime##Santa##end## ##red##" . $e2 . "!##end##";
        return $output;
    } // End function show_time()

    /*
     Return the fictional AO Year based on the current year.
     AO is based 27,474 years in the future.
     */
    function ao_year()
    { // Start function ao_year()
        return 27474 + gmdate("Y", time());
    } // End function ao_year()

    /*
     Calculates the Hours, minutes, and seconds to next gate time.
     Returns an associtive array.
     */
    function get_DHMS($seconds)
    { // Start function get_DHMS()
        $tmp['days'] = intval($seconds / 86400); // 86400 seconds in a day
        $partDay = $seconds - ($tmp['days'] * 86400);
        $tmp['hours'] = intval($partDay / 3600); // 3600 seconds in an hour
        $partHour = $partDay - ($tmp['hours'] * 3600);
        $tmp['minutes'] = intval($partHour / 60); // 60 seconds in a minute
        $tmp['seconds'] = $partHour - ($tmp['minutes'] * 60);
        return $tmp;
    } // End function get_DHMS()

    /*
     Retruns H:M:S
     */
    function format_seconds($totalsec)
    { // Start function format_seconts()
        if ($totalsec < 0) {
            $minus = "-";
            $totalsec = $totalsec - ($totalsec * 2);
        }
        $hours = floor($totalsec / (60 * 60));
        $rest = $totalsec % (60 * 60);
        $minutes = floor($rest / 60);
        $seconds = $rest % 60;
        return sprintf($minus . "%02d:%02d:%02d", $hours, $minutes, $seconds);
    } // End function format_seconts()

    function parse_time($timestr)
    {
        $duration = 0;
        $timesize = 1;
        $timeunit = 1;
        if (stristr($timestr, 'm')) {
            $timesize = 60;
            $timeunit = 2;
        }
        elseif (stristr($timestr, 'h'))
        {
            $timesize = 60 * 60;
            $timeunit = 3;
        }
        elseif (stristr($timestr, 'd'))
        {
            $timesize = 60 * 60 * 24;
            $timeunit = 4;
        }
        if (stristr($timestr, ':')) {
            $timeparts = explode(":", $timestr);
            $numberlength = 0;
            for ($i = count($timeparts) - 1; $i >= 0; $i--)
            {
                settype($timeparts[$i], "integer");
                $numberlength += $timesize * $timeparts[$i];
                if ($timeunit == 1) {
                    $timesize = 60;
                }
                elseif ($timeunit == 2)
                {
                    $timesize = 60 * 60;
                }
                elseif ($timeunit >= 3)
                {
                    $timesize = 24 * 60 * 60;
                }
                $timeunit++;
            }
        }
        else
        {
            $numberlength = $timestr;
            settype($numberlength, "integer");
            $numberlength = $numberlength * $timesize;
        }
        $duration = $numberlength;
        return $duration;
    }

    /*
      * Takes $time and calculates how many minutes, hours and days it was ago.
      * The result is returned as a string of the format "X days, Y hours and Z mins ago"
      */
    function time_ago($time)
    {
        $diftime = time() - $time;
        $timestr = ' ';
        $diftime = floor($diftime / 60);
        $timestr .= $diftime % 60 . " mins";
        $diftime = floor($diftime / 60);
        if ($diftime > 0) {
            if ($diftime > 24) {
                $diftimedays = floor($diftime / 24);
                $timestr = $diftime % 24 . " hours" . $timestr;
                return $diftimedays . " days " . $timestr . " ago";
            }
            else
            {
                $timestr = $diftime . " hours" . $timestr;
                return $timestr . " ago";
            }
        }
        return $timestr . " ago";
    }
}

?>