<?php
/*
* Vote.php - Module to handle votes in organizations.
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
/*
Add a "_" at the beginning of the file (_ClassName.php) if you do not want it to be loaded.
*/
$VoteClass = new Vote($bot);
/*
The Class itself...
*/
class Vote extends BaseActiveModule
{

	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("votes", "true") . "
							(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							description VARCHAR(255),
							min_level INT default 10,
							started BOOL,
							endtime INT,
							votestarter VARCHAR(14),
							votes_total INT,
							winner VARCHAR(255),
							votes_percent INT)");
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("vote_options", "true") . "
							(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							vote_id INT,
							description VARCHAR(255),
							votes INT)");
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("vote_ballots", "true") . "
							(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							vote_id INT,
							player VARCHAR(14),
							option_id INT)");
		$this->register_command('all', 'vote', 'MEMBER');
		$this->register_event("buddy");
		$this->register_event('connect');
		$this->register_event('disconnect');
		$this->register_event('cron', '1hour');
		$this->bot->core("settings")->create('Vote', 'New', 'ADMIN', 'Who should be able to start new votes', 'GUEST;MEMBER;LEADER;ADMIN;SUPERADMIN;OWNER');
		$this->bot->core("settings")->create('Vote', 'End', 'ADMIN', 'Who should be able to end votes', 'GUEST;MEMBER;LEADER;ADMIN;SUPERADMIN;OWNER');
		$this->bot->core("settings")->create('Vote', 'Spam', 'Yes', 'Should players who haven\'t voted be spammed when logging on', 'No;Yes');
		$this->help['description'] = 'This module enables the guild to arrange votes.';
		$this->help['command']['vote'] = "Shows the vote interface";
		$this->help['command']['vote new <description> [;option 1;...;option n]'] = "Adds adds a new vote about <description>. It is possible to add options here separated by semicolons.";
		$this->help['command']['vote restrict <vote#> [min_level]'] = "Only players with <min_level> access can vote on vote <vote#>. If min_level is omitted a menu of availible levels is shown";
		$this->help['command']['vote addopt <vote#> <option1> [;option 2;...;option n]'] = "Adds adds new option(s) to <vote#>";
		$this->help['command']['vote delopt <option_id>'] = "Remove option with id <option_id>";
		$this->help['command']['vote edit <option_id> <newtext>'] = "Replaces the text for option with id <option_id> with <newtext>";
		$this->help['command']['vote time <vote#> <time>'] = "Sets the vote to run for <time>";
		$this->help['command']['vote start <vote#>'] = "Makes the vote go live (if it passes all checks)";
		$this->help['command']['vote end <vote#>'] = "Ends vote <vote#> regardless of any set time";
		$this->help['notes'] = "This module is under development!<br>";
		$this->help['notes'] .= "The time argument needs to be a format as described by http://www.gnu.org/software/tar/manual/html_node/tar_109.html ";
		$this->help['notes'] .= "This means that formats like most dates and also '1 month', 'next thursday' etc. are allowed.<br>";
		$this->help['notes'] .= "Since semicolon (;) is used as a separator it cannot be used in vote- or option descriptions.";
	}

	/*
	Unified message handler
	$source: The originating player
	$msg: The actual message, including command prefix and all
	$type: The channel the message arrived from. 1 Being tells, 2 being private groupm 3 being guildchat
	*/
	function command_handler($source, $msg, $type)
	{
		$vars = explode(' ', strtolower($msg));
		//Source should always be the main of the character executing the command.
		$source = $this->bot->core("alts")->main($source);
		$command = $vars[0];
		$subcom = $vars[1];
		unset($vars[0], $vars[1]);
		switch ($command)
		{
			case 'vote':
				switch ($subcom)
				{
					case '':
						return $this->vote_interface($source);
						break;
					case 'show':
						return $this->vote_interface($source, $vars[2]);
						break;
					case 'all':
						return $this->vote_interface($source, 'all');
						break;
					case 'cast':
						$vote_no = $vars[2];
						$option_no = $vars[3];
						return $this->cast_vote($source, $vote_no, $option_no);
						break;
					case 'new':
						return $this->new_vote($source, implode(' ', $vars));
						break;
					case 'restrict':
						$vote_no = $vars[2];
						unset($vars[2]);
						return $this->restrict_vote($source, $vote_no, implode(' ', $vars));
						break;
					case 'addopt':
						$vote_no = $vars[2];
						unset($vars[2]);
						return $this->add_option($source, $vote_no, implode(' ', $vars));
						break;
					case 'delopt':
						$option_id = $vars[2];
						unset($vars[2]);
						return $this->del_option($source, implode(' ', $vars));
						break;
					case 'edit':
						$option_no = $vars[2];
						unset($vars[2]);
						return $this->edit_option($source, $option_no, implode(' ', $vars));
						break;
					case 'time':
						$vote_no = $vars[2];
						unset($vars[2]);
						return $this->set_time($source, $vote_no, implode(' ', $vars));
						break;
					case 'start':
						$vote_no = $vars[2];
						return $this->start_vote($source, $vote_no);
						break;
					case 'end':
						$vote_no = $vars[2];
						return $this->end_vote($source, $vote_no);
						break;
					default:
						return $this->bot->send_help($source, 'vote');
				}
				break;
			default:
				// Just a safety net to allow you to catch errors where a module has registered  a command, but fails to actually do anything about it
				// $this -> bot -> send_output($source, $text, $type) will send $text to $source by tell if $type is 1 (tell) or to the apropriate channel if $type is 2 or 3.
				$this->bot->send_output($source, "Broken plugin, recieved unhandled command: $command", $type);
		}
	}

	/*
	This gets called if a buddy logs on/off
	*/
	function buddy($name, $msg)
	{
		//Check if there are active votes which this toon has not voted in and show a list of such votes in tells.
	}

	/*
	This gets called on cron
	*/
	function cron()
	{
		//Check for expired votes
		$now = time();
		$query = "SELECT id FROM #___votes WHERE endtime > $now AND endtime <> 0";
		if ($votes = $this->bot->db->select($query, MYSQL_ASSOC))
		{
			foreach ($votes as $vote)
			{
				$this->end_vote($this->bot->name, $vote['id']);
			}
		}
	}

	/*
	This gets called when bot connects
	*/
	function connect()
	{
		//Get the org form and ranks
	}

	/*
	This gets called when bot disconnects
	*/
	function disconnect()
	{
		//Not sure if we'll be doing anything useful here.
	}

	/*
	Custom functions go below here
	*/
	function new_vote($name, $description)
	{
		//Check that we haven't got an empty description
		if (! empty($description))
		{
			//Check if this person is allowed to start a new vote.
			if ($this->bot->core("security")->check_access($name, $this->bot->core("settings")->get('Vote', 'New')))
			{
				//Parse for separator (;) Syntax is !vote new description; option 1;...;option n;
				$args = explode(';', $description);
				var_dump($args);
				$description = $args[0];
				unset($args[0]);
				var_dump($args);
				//Add the vote description
				$query = "INSERT INTO #___votes (description, votestarter) VALUES('$description', '$name')";
				if (! $this->bot->db->query($query))
				{
					return ("Error running query: $query");
				}
				else
				{
					//Get the ID of the vote.
					$vote_id = mysql_insert_id($this->bot->db->CONN);
					var_dump($args);
					//Add options (if any)
					foreach ($args as $option)
					{
						$query = "INSERT INTO #___vote_options (vote_id, description) VALUES($vote_id, '$option')";
						$this->bot->db->query($query);
					}
				}
			}
			//Show vote interface for the new vote.
			return $this->vote_interface($name, $vote_id);
		}
		else
		{
			return ("##error##You need to specify a description for your vote.##end##");
		}
	}

	function add_option($name, $vote_no, $description)
	{
		//Check if this person is allowed to add options to this vote
		$query = "INSERT vote_id,description INTO #___vote_options VALUES($vote_no, '$description')";
	}

	function del_option($name, $option_id)
	{
		//Check if this person is allowed to add options to this vote
		$query = "DELETE FROM #___vote_options WHERE id=$option_id";
	}

	function edit_option($name, $option_no, $newdescription)
	{
		$query = "UPDATE #___vote_options SET description='$newdescription' WHERE id=$option_id";
	}

	function set_time($name, $vote_no, $time)
	{
		$timestamp = strtotime($time);
		if (($timestamp === false) or ($timestamp === - 1))
			return "Illegal time: $time";
		else
		{
			$query = "UPDATE #___votes SET endtime = $timestamp WHERE id = $vote_no";
		}
	}

	function restrict_vote($name, $vote_no, $level = false)
	{
		if (level === false)
		{
			//Show a list of ranks as listed in $this -> bot -> core("security") -> cache[orgranks] and guest, member, leader. admin.
		}
		else
		{
			//Set restricted level to given level
		}
	}

	function start_vote($name, $vote_no)
	{
		//Check that the person attempting to start the vote is the person that created it.
		$query = "SELECT * FROM #___votes WHERE id = $voteno and votestarter = '$name'";
		//Check that two or more options are defined for the vote
		$query = "SELECT COUNT(id) as num_options from #___vote_options WHERE voteid = $vote_no";
		//Set started to true (1)
		$query = "UPDATE #___votes SET started = true WHERE id = $voteno";
		//Return the vote interface
		return $this->vote_interface($name, $vote_no);
	}

	function end_vote($name, $vote_id)
	{
		$query = "SELECT vote_starter, started from #___votes WHERE id = $vote_id";
		if ($vote = $this->bot->db->select($query))
		{
			$vote = $vote[0];
			//Check that the person attempting to end the vote is allowed to end the vote. (botname should always be allowed to end votes)
			if (($name !== $vote['vote_starter']) && (! $this->bot->core("security")->check_access($name, $this->bot->core("settings")->get('Vote', 'End'))) && ($name !== $this->bot->botname))
			{
				return ("You have got insufficient privileges to end this vote.");
			}
			//Check that the vote hasn't already been ended
			if ($vote['started'] === - 1)
			{
				return ("Vote $vote_id has already ended");
			}
			//Count all ballots and check that they add up to the number on each option and the total votes.
			$query = "SELECT COUNT(id) AS votes, option_id FROM #___vote_ballots WHERE vote_id = $vote_id GROUP BY option_id ORDER BY votes DESC";
			$vote_counts = $this->bot->db->select($query);
			//Update the vote_options table with the number of votes each option got.
			foreach ($vote_counts as $vote_count)
			{
				$query = "UPDATE #___vote_options SET votes = {$vote_count['votes']} WHERE id = {$vote_count['option_id']}";
				$this->bot->db->query($query);
			}
			//Update the votes table with the winner and vote count
			//Set endtime to -1 as to signify that the vote has ended
			$query = "SELECT votes, description FROM #___vote_options WHERE vote_id = $vote_id ORDER BY votes DESC LIMIT 1";
			$winner = $this->bot->db->select($query);
			$winner = $winner[0];
			$query = "SELECT SUM(votes) AS total FROM #___vote_options WHERE vote_id = $vote_id";
			$total = $this->bot->db->select($query);
			$total = $total[0]['total'];
			$percent = round($total / $winner['votes']);
			$query = "UPDATE #___votes SET winner = '{$winner['description']}', total_votes = $total, votes_percent = $percent, end_time=-1 WHERE id = $vote_id";
			$this->bot->db->query($query);
		}
		return "Vote $vote_id has ended.";
	}

	function cast_vote($name, $vote_no, $option_no)
	{
		//Check that this vote has not ended
		$query = $this->bot->db->select("SELECT * FROM #___votes WHERE id = $vote_no AND endtime > -1");
		if (empty($query))
		{
			return "Vote ended or invalid";
		}
		//Check that this toon has not already voted in this vote
		$query = $this->bot->db->select("SELECT * FROM #___vote_ballots WHERE vote_id = $vote_no AND player = '$name'");
		if (! empty($query))
		{
			return "You have already voted";
		}
		//Check its a valid option id
		//Check the highest rank of this player to determine if he can vote
		//Add the vote to the ballots table
		$this->bot->db->query("INSERT INTO #___vote_ballots (vote_id, player, option_id) VALUES ($vote_no, $name, $option_no)");
		//Update the number of votes recieved by the option
		$this->bot->db->query("UPDATE #___vote_options SET votes = votes + 1 WHERE vote_id = $vote_no AND id = " . $option_no);
		//Update the number of votes on the vote
		$this->bot->db->query("UPDATE #___votes SET votes_total = votes_total + 1 WHERE id = " . $vote_no);
		return "vote cast!";
	}

	function vote_interface($name, $vote = false)
	{
		if ($vote === false)
		{
			//Get the highest rank of this players toons
			//List all active votes that the person can vote on
			$rank = 2;
			$query = "SELECT id, description FROM #___votes WHERE endtime > -1 AND started = 1 AND min_level >= $rank";
			$votes = $this->bot->db->select($query, MYSQL_ASSOC);
			if (! empty($votes))
			{
				echo "found a vote";
				foreach ($votes as $vote)
				{
					//Check that the player hasn't voted on this vote yet.
					$query = "SELECT * FROM #___vote_ballots WHERE vote_id  = {$vote['id']} AND player = '$name'";
					$ballots = $this->bot->db->select($query, MYSQL_ASSOC);
					if (empty($ballots))
					{
						//Add the vote to the list of votes to show.
						$window .= "{$vote['id']}: <a href='chat:///tell <botname> <pre>vote show {$vote['id']}'>{$vote['description']}</a><br>";
					}
				}
				return $this->bot->core("tools")->make_blob("Availible votes", $window);
			}
			else
				return ("votes = Empty");
		}
		elseif ($vote == 'all')
		{
			//List all votes, even ones inactive and already voted on.
			$query = "SELECT * FROM #___votes WHERE minlevel <= $rank";
		}
		else
		{
			//Check that the vote is started.
			if (true)
			{
				//Check that the player hasn't voted on this vote.
				//list options for vote $vote
				$query = " SELECT id, description FROM #___votes WHERE id=$vote UNION SELECT id, description FROM #___vote_options WHERE vote_id=$vote";
				$descriptions = $this->bot->db->select($query, MYSQL_ASSOC);
				$votedesc = $descriptions[0];
				unset($descriptions[0]);
				$window = "Vote {$votedesc['id']}: {$votedesc['description']}<br><br>";
				foreach ($descriptions as $option)
				{
					$window .= $option['id'] . ": " . $this->bot->core("tools")->chatcmd("vote cast " . $option['id'], $option['description']) . "<br>";
				}
				return ("Vote " . $votedesc['id'] . " :: " . $this->bot->core("tools")->make_blob("click to view", $window));
			}
			else
			{
				//Check if this person is the starter of the vote and show management options
			}
		}
	}
}
?>