<?php
/*
* HeartBeat.php - Bot proof of kept alive
* Bitnykk customs
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 J-Soft and the BeBot development team.
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

$HeartBeat = new HeartBeat($bot);

/*
The Class itself...
*/
class HeartBeat extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
		$this->register_event("connect");
		$this->register_event("cron", "5min");
		$this->path = "./Custom/Modules";
    }


    /*
    This gets called on command
    */
    function command_handler($name, $msg, $origin)
    {
		// no command
    }

	/*
    Bot startup
    */
    function connect()
    {	
		$this->heartbeat();
	}		

	/*
	Cron task
    */	
    function cron()
    {
		$this->heartbeat();
    }

	/*
	Heart Beat
    */	
    function heartbeat()
    {	
		$file = $this->bot->botname.".txt";
		$content = date('Y/m/d H:i:s', time());
		if ($handle = opendir($this->path)) {
			while (false !== ($filename = readdir($handle))) {
				if($filename!="."&&$filename!="..") {
					if($filename==$file) {
						unlink($this->path."/".$filename);
					}					
				}
			}
			file_put_contents($this->path."/".$file, $content);
		}		
	}

}

?>
