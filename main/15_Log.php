<?php
/*
* Log.php - Displays special logs
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
$log = new Log($bot);
/*
The Class itself...
*/
class Log extends BaseActiveModule
{

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("log_message", "true") . "
		        (id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		        message VARCHAR(500) NOT NULL,
		        first VARCHAR(45) NOT NULL,
		        second VARCHAR(45) NOT NULL,
		        timestamp INTEGER UNSIGNED NOT NULL)");
		$this->register_command("all", "log", "OWNER");
		$this->bot->core("settings")->create('Log', 'LimitMessage', 10, 'How many of the last log messages should we show?', '5;10;25;50');
		$this->help['description'] = 'Module to manage and display logs.';
		$this->help['command']['log'] = "Displays a list of log categories.";
		$this->help['command']['log <category>'] = "Displays the last " . $this->bot->core("settings")->get("Log", "LimitMessage") . " logs for that category.";
		$this->help['command']['log all'] = "Displays the last " . $this->bot->core("settings")->get("Log", "LimitMessage") . " logs for all log messages.";
	}

	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^log$/i", $msg))
			return $this->show_log();
		else if (preg_match("/^log (.+)$/i", $msg, $info))
			return $this->show_log($info[1]);
	}

	/*
	Starts a Log
	*/
	function show_log($category = false)
	{
		if ($category === false)
		{
			$results = $this->bot->db->select("SELECT DISTINCT first FROM #___log_message ORDER BY first");
			$inside .= $this->bot->core("tools")->chatcmd("log all", "Show the last " . $this->bot->core("settings")->get("Log", "LimitMessage") . " log messages") . "\n";
			if (! empty($results))
			{
				foreach ($results as $result)
				{
					$inside .= $this->bot->core("tools")->chatcmd("log " . $result[0], $result[0]) . "\n";
				}
			}
			else
				$inside = "No log messages found.";
			return "Log categories :: " . $this->bot->core("tools")->make_blob("Click to view", $inside);
		}
		elseif ($category == "all")
		{
			$results = $this->bot->db->select("SELECT first,second,message,timestamp FROM #___log_message ORDER BY timestamp DESC LIMIT " . $this->bot->core("settings")->get("Log", "LimitMessage"));
			if (! empty($results))
			{
				foreach ($results as $result)
				{
					$inside .= "[" . gmdate($this->bot->core("settings")->get("time", "formatstring"), $result[3]) . "]\n" . $result[1] . ": " . $result[2] . "\n\n";
				}
			}
			else
				$inside = "No log messages found.";
			return "Log messages :: " . $this->bot->core("tools")->make_blob("Click to view", $inside);
		}
		else
		{
			$results = $this->bot->db->select("SELECT first,second,message,timestamp FROM #___log_message WHERE first = '" . $category . "' ORDER BY timestamp DESC LIMIT " . $this->bot->core("settings")->get("Log", "LimitMessage"));
			if (! empty($results))
			{
				foreach ($results as $result)
				{
					$inside .= "[" . gmdate($this->bot->core("settings")->get("time", "formatstring"), $result[3]) . "]\n" . $result[1] . ": " . $result[2] . "\n\n";
				}
			}
			else
				$inside = "No log messages found.";
			return "Log messages for " . $category . " :: " . $this->bot->core("tools")->make_blob("Click to view", $inside);
		}
	}
}
?>