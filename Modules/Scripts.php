<?php
/*
* Scripts.php - Makes the bot execute scripts
* Scripts module by Andrew Zbikowski <andyzib@gmail.com> (AKA Glarawyn, RK1)
* Based on Rules_Raid.php by Blondengy
* Converted to script module by Kelmino & Bitnykk
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
* - Bitnykk (RK5)
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
$scripts = new Scripts($bot);
/*
The Class itself...
*/
class Scripts Extends BaseActiveModule
{
	var $path;
    function __construct (&$bot)
    {
        parent::__construct($bot, get_class($this));

        $this -> register_command('all', 'scripts', 'GUEST');
        $this -> register_command('all', 'script', 'GUEST');

        $this -> help['description'] = 'Shows code popup of scripts. Scripts are shared by bot owner only.';
        $this -> help['command']['scripts'] = 'Shows the list of all available scripts.';
        $this -> help['command']['script <scriptname>'] = 'Shows a specific script code to be copied-pasted.';
		
		$this -> path = "Extras/Scripts";
    }

    /*
        This function handles all the inputs and returns output
        suitable for send_tell, send_pgroup, and send_gc.
    */
    function command_handler($name, $msg, $origin)
    {
        $this->error->reset(); //Reset the error message so we don't trigger the handler by old error messages.

        $com = $this->parse_com($msg, array('com', 'args', 'error'));

        if(empty($com['args']))
            return $this -> make_list();
        else if(empty($com['error']))
            return $this -> make_script($com['args']);

        return "Command not understood, here's the list of scripts instead: " . $this -> make_list();
    }

    /*
    Makes list of script(s)
    */
    function make_list()
    {		
		$dir = $this->path;
		$files = scandir($dir);
		$content = "";
		$total = 0;
		foreach ($files as $key => $value) {			
			$path = realpath($dir . DIRECTORY_SEPARATOR . $value);
			if (!is_dir($path) && $value != ".gitkeep") {
				$content .= $this -> bot -> core("tools") -> chatcmd("scripts ".$value, $value)." \n";
				$total++;
			}					
		}
		if($total==0) $return = "0 script(s) found : no file currently available in expected path (".$dir.").";
        else $return = $total." script(s) found : " . $this -> bot -> core("tools") -> make_blob("click to view", $content);
		return $return;
    }

    /*
    Shows one script code
    */
    function make_script($script)
    {
		$dir = $this->path;
        if($script != ".gitkeep" && false !== ($handle = @fopen($dir."/".$script, "r")))
        {
            $content = fread($handle, filesize($dir."/".$script));
            fclose($handle);
            $content = "<b>:::: Script [".$script."] ::::</b>\n\n".$content;
            $return = "Script (".$script."):: " . $this -> bot -> core("tools") -> make_blob("click to view", $content);
        } else {
			$return = "Specified script not found ...";
		}
        return $return;
    }
}
?>
