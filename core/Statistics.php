<?php
/*
* Statistics.php - Statistics Module.
* Displays statistics and provides statistics and debugging related functions.
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2009 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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
$statistics = new statistics($bot);
/*
The Class itself...
*/
class statistics extends BasePassiveModule
{ // Start Class

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("statistics", "true") . " (
					id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					module VARCHAR(100) NOT NULL,
					action VARCHAR(100) NOT NULL,
					comment VARCHAR(100) default '',
					count INT(10) unsigned NOT NULL
				)");
		$this->register_module("statistics");
		$this->bot->core("settings")->create('Statistics', "Enabled", False, "Capture Statistics?");
	}

	/*
	This is a relatively simple module,
	but one that will perform some important functions.
	What we're capturing is the module name, the action, and a simple count.
	*/
	function capture_statistic($module, $action, $comment = "", $count = 1)
	//function capture_statistic ()
	{
		if ($this->bot->core("settings")->get("Statistics", "Enabled"))
		{
			$total_count = $this->bot->db->select("SELECT count FROM #___statistics WHERE module = '" . $module . "' AND action = '" . $action . "' AND comment = '" . $comment . "'");
			if (! empty($total_count))
			{
				echo "Total Count: " . $total_count[0][0] . "\n";
				echo "Count: " . $count . "\n";
				$total_count = $total_count[0][0] + $count;
				$this->bot->db->query("UPDATE #___statistics SET count = '" . $total_count . "' WHERE module = '" . $module . "' AND action = '" . $action . "' AND comment = '" . $comment . "'");
				return;
			}
			else
			{
				$total_count = $count;
				$this->bot->db->query("INSERT INTO #___statistics (module, action, comment, count) VALUES ('" . $module . "','" . $action . "','" . $comment . "'," . $total_count . ")");
				return;
			}
		}
	}
} // End of Class
?>