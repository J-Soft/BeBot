<?php
/*
* Bots.php - Module Manage Online / Offline Status monitoring and Statistics.
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
$botstatistics_core = new BotStatistics_Core($bot);
class BotStatistics_Core extends BasePassiveModule
{

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this->bot->core("settings")->create("Bots", "DB", "", "Use dif Database? (Restart Required)");
		if ($this->bot->core("settings")->get("bots", "DB") !== "")
		{
			$this->DB = $this->bot->core("settings")->get("bots", "DB") . ".";
		}
		else
		{
			$this->DB = "";
		}
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->DB . $this->bot->db->define_tablename("bots", "false") . " (
				ID INT NOT NULL auto_increment PRIMARY KEY,
				bot VARCHAR(20),
				dim VARCHAR(20) NOT NULL default '',
				online INT NOT NULL default '0',
				time INT NOT NULL default '0',
				start INT NOT NULL default '0',
				total INT NOT NULL default '0',
				restarts INT NOT NULL default '0'
				)");
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->DB . $this->bot->db->define_tablename("bots_log", "false") . " (
				ID INT NOT NULL auto_increment PRIMARY KEY,
				bot VARCHAR(20),
				dim VARCHAR(20) NOT NULL default '',
				start INT NOT NULL default '0',
				end INT NOT NULL default '0'
				)");
		$this->update_table();
		$this->start();
		$this->register_event("cron", "1min");
		$this->register_event("cron", "24hour");
		$this->register_event("disconnect");
		$this->register_module("bot_statistics");
	}

	function update_table()
	{
		Switch ($this->bot->db->get_version("bots"))
		{
			case 1:
				$this->bot->db->update_table("bots", "restarts", "add", "ALTER IGNORE TABLE " . $this->DB . "bots ADD restarts INT DEFAULT '0'");
			case 2:
				$this->bot->db->update_table("bots", "dim", "alter", "ALTER TABLE " . $this->DB . "bots modify dim VARCHAR(20) NOT NULL default ''");
			Default:
		}
		$this->bot->db->set_version("bots", 3);
		Switch ($this->bot->db->get_version("bots_log"))
		{
			case 1:
				$this->bot->db->update_table("bots", "dim", "alter", "ALTER TABLE " . $this->DB . "bots_log modify dim VARCHAR(20) NOT NULL default ''");
			Default:
		}
		$this->bot->db->set_version("bots", 2);
	}

	function start()
	{
		$result = $this->bot->db->select("SELECT bot, dim, online, time FROM " . $this->DB . "#___bots WHERE bot = '" . $this->bot->botname . "' AND dim = '" . $this->bot->dimension . "'");
		if (empty($result))
			$this->bot->db->query("INSERT INTO " . $this->DB . "#___bots (bot, dim, online, time, start) VALUES ('" . $this->bot->botname . "', '" . $this->bot->dimension . "', " . time() . ", 0, " . time() . ")");
		else
		{
			if ($result[0][2] < $result[0][3]) // Make sure Bot was Online long enough to do time stamp with cron to prevent Spamming into log if crashloop.
			{
				$this->bot->db->query("INSERT INTO " . $this->DB . "#___bots_log (bot, dim, start, end) VALUES ('" . $result[0][0] . "', '" . $result[0][1] . "', " . $result[0][2] . ", " . $result[0][3] . ")");
			}
			$this->bot->db->query("UPDATE " . $this->DB . "#___bots SET online = " . time() . " WHERE bot = '" . $this->bot->botname . "' AND dim = '" . $this->bot->dimension . "'");
		}
	}

	function check_bots($name, $origin, $bot = FALSE, $dim = FALSE)
	{
		if (! $dim)
			$dim = $this->bot->dimension;
		if ($bot)
		{
			$bot = mysql_real_escape_string($bot);
			$dim = mysql_real_escape_string($dim);
			$result = $this->bot->db->select("SELECT bot, dim, online, time, start, total, restarts FROM " . $this->DB . "#___bots WHERE bot = '" . $bot . "' AND dim = '" . $dim . "'");
			if (! empty($result))
			{
				$bot = $result[0];
				$inside = ":::  Bot: " . $bot[0] . "  :::\n";
				$inside .= "\nStatus: ";
				if ($bot[3] + (60 * 3) > time())
					$inside .= "##green##Online##end## for " . $this->timedif($bot[2], $bot[3]);
				else
					$inside .= "##red##Offline##end## for " . $this->timedif($bot[3], time());
				$log = $this->bot->db->select("SELECT start, end FROM " . $this->DB . "#___bots_log WHERE bot = '" . $bot[0] . "' AND dim = '" . $bot[1] . "'");
				$day = 60 * 60 * 24;
				$daytime = time() - $day;
				if ($daytime < $bot[4])
					$day = time() - $bot[4];
				$week = $day * 7;
				$weektime = time() - $week;
				if ($weektime < $bot[4])
					$week = time() - $bot[4];
				$month = $day * 30;
				$monthtime = time() - $month;
				if ($monthtime < $bot[4])
					$month = time() - $bot[4];
				$weekon = 0;
				$monthon = 0;
				$allon = 0;
				$restartd = - 1;
				$restartw = - 1;
				$restartm = - 1;
				$restart = - 1;
				if ($bot[3] + (60 * 3) > time())
					$bot[3] = time();
				$log[] = array($bot[2] , $bot[3]);
				foreach ($log as $l)
				{
					if ($l[0] > $daytime)
					{
						$restartd += 1;
						$on = $l[1] - $l[0];
						$dayon += $on;
					}
					elseif ($l[1] > $daytime)
					{
						$restartd += 1;
						$on = $l[1] - $daytime;
						$dayon += $on;
					}
					if ($l[0] > $weektime)
					{
						$restartw += 1;
						$on = $l[1] - $l[0];
						$weekon += $on;
					}
					elseif ($l[1] > $weektime)
					{
						$restartw += 1;
						$on = $l[1] - $weektime;
						$weekon += $on;
					}
					if ($l[0] > $monthtime)
					{
						$restartm += 1;
						$on = $l[1] - $l[0];
						$monthon += $on;
					}
					elseif ($l[1] > $monthtime)
					{
						$restartm += 1;
						$on = $l[1] - $monthtime;
						$monthon += $on;
					}
					$restart += 1;
					$on = $l[1] - $l[0];
					$allon += $on;
				}
				$restart += $bot[6];
				$allon += $bot[5];
				$perc = ($dayon / $day) * 100;
				$perc = round($perc, 1);
				if ($perc == 100 && ($dayon != $day))
					$perc = 99.9;
				$off = $day - $dayon;
				$off = $this->timedif(0, $off, FALSE);
				$dayon = $this->timedif(0, $dayon, FALSE);
				$inside .= "\n\nLast 24 Hours:\n     Online: $dayon\n     Offline: $off\n     Restarts: $restartd\n     Percent: " . $perc . "%";
				$perc = ($weekon / $week) * 100;
				$perc = round($perc, 1);
				if ($perc == 100 && ($weekon != $week))
					$perc = 99.9;
				$off = $week - $weekon;
				$off = $this->timedif(0, $off, FALSE);
				$weekon = $this->timedif(0, $weekon, FALSE);
				$inside .= "\n\nLast 7 Days:\n     Online: $weekon\n     Offline: $off\n     Restarts: $restartw\n     Percent: " . $perc . "%";
				$perc = ($monthon / $month) * 100;
				$perc = round($perc, 1);
				if ($perc == 100 && ($weekon != $week))
					$perc = 99.9;
				$off = $month - $monthon;
				$off = $this->timedif(0, $off, FALSE);
				$monthon = $this->timedif(0, $monthon, FALSE);
				$inside .= "\n\nLast 30 Days:\n     Online: $monthon\n     Offline: $off\n     Restarts: $restartm\n     Percent: " . $perc . "%";
				$sincestart = time() - $bot[4];
				$perc = ($allon / $sincestart) * 100;
				$perc = round($perc, 1);
				if ($perc == 100 && ($weekon != $week))
					$perc = 99.9;
				$off = $sincestart - $allon;
				$off = $this->timedif(0, $off, FALSE);
				$allon = $this->timedif(0, $allon, FALSE);
				$inside .= "\n\nSince Install:\n     Online: $allon\n     Offline: $off\n     Restarts: $restart\n     Percent: " . $perc . "%";
				Return ("Bot Stats for ##highlight##" . $bot[0] . "##end## :: " . $this->bot->core("tools")->make_blob("click to view", $inside));
			}
			else
				Return ("Bot not Found.");
		}
		else
		{
			$result = $this->bot->db->select("SELECT bot, dim, online, time FROM " . $this->DB . "#___bots");
			if (! empty($result))
			{
				foreach ($result as $bot)
				{
					if ($bot[3] + (60 * 3) > time())
						$status = "##green##Online##end## for " . $this->timedif($bot[2], $bot[3]);
					else
						$status = "##red##Offline##end## for " . $this->timedif($bot[3], time());
					$inside[$bot[1]] .= "\n" . $this->bot->core("tools")->chatcmd("bots " . $bot[0] . " " . $bot[1], $bot[0], $origin) . " is " . $status;
				}
				$inside2 = ":::  Bots  :::\n";
				foreach ($inside as $key => $value)
				{
					if (is_numeric($key))
						$key = "RK " . $key;
					$inside2 .= "\n\n##orange##" . $key . " \n##end##";
					$inside2 .= $value;
				}
				Return ("Bots :: " . $this->bot->core("tools")->make_blob("click to view", $inside2));
			}
			else
				Return ("No Bots Found.");
		}
	}

	function timedif($low, $high, $showmins = TRUE)
	{
		$dif = $high - $low;
		if ($dif < 60 * 60)
		{
			$mins = floor($dif / 60);
			if ($mins > 1)
				$ms = "s";
			Return ($mins . " Minute" . $ms);
		}
		elseif ($dif < 60 * 60 * 24)
		{
			$mins = floor($dif / 60);
			$hours = floor($mins / 60);
			$minstorem = $hours * 60;
			$minsrem = $mins - $minstorem;
			if ($minsrem > 1)
				$ms = "s";
			if ($hours > 1)
				$hs = "s";
			if ($showmins)
				Return ($hours . " Hour" . $hs . " and " . $minsrem . " Minute" . $ms);
			else
				Return ($hours . " Hour" . $hs);
		}
		else
		{
			$mins = floor($dif / 60);
			$hours = floor($mins / 60);
			$days = floor($hours / 24);
			$minstorem = $hours * 60;
			$minsrem = $mins - $minstorem;
			$hourstorem = $days * 24;
			$hoursrem = $hours - $hourstorem;
			if ($minsrem > 1)
				$ms = "s";
			if ($hoursrem > 1)
				$hs = "s";
			if ($days > 1)
				$ds = "s";
			if ($showmins)
				Return ($days . " Day" . $ds . ", " . $hoursrem . " Hour" . $hs . " and " . $minsrem . " Minute" . $ms);
			else
				Return ($days . " Day" . $ds . ", " . $hoursrem . " Hour" . $hs);
		}
	}

	function cron($cron)
	{
		$this->online = TRUE;
		$this->bot->db->query("UPDATE " . $this->DB . "#___bots SET time = '" . time() . "' WHERE bot = '" . $this->bot->botname . "' AND dim = '" . $this->bot->dimension . "'");
		if ($cron == 86400)
		{
			$monthago = time() - (60 * 60 * 24 * 30);
			$log = $this->bot->db->select("SELECT ID, start, end FROM " . $this->DB . "#___bots_log WHERE bot = '" . $this->bot->botname . "' AND dim = '" . $this->bot->dimension . "' AND end < " . $monthago);
			if (! empty($log))
			{
				foreach ($log as $l)
				{
					$total = $l[2] - $l[1];
					$this->bot->db->query("UPDATE " . $this->DB . "#___bots SET total = total + " . $total . ", restarts = restarts + 1 WHERE bot = '" . $this->bot->botname . "' AND dim = '" . $this->bot->dimension . "'");
					$this->bot->db->query("DELETE FROM " . $this->DB . "#___bots_log WHERE ID = " . $l[0]);
				}
			}
		}
	}

	function disconnect()
	{
		if ($this->online)
			$this->bot->db->query("UPDATE " . $this->DB . "#___bots SET time = " . time() . " WHERE bot = '" . $this->bot->botname . "' AND dim = '" . $this->bot->dimension . "'");
	}
}
?>