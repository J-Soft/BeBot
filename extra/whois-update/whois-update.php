<?php
/*
* whois-update.php - A script that updates the whois cache
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg Stens�s, ShadowRealm Creations and the BeBot development team.
*
* Developed by:
* - Alreadythere (RK2)
* - Blondengy (RK1)
* - Blueeagl3 (RK1)
* - Glarawyn (RK1)
* - Khalem (RK1)
* - Naturalistic (RK1)
*
* See Credits file for all aknowledgements.
*
*  This program is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License.
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
*
* File last changed at $LastChangedDate:2006-08-20 16:36:35 +0200 (sø, 20 aug 2006) $
* Revision: $Id:maintemp.php 210 2006-08-20 16:36:35 +0200 (sø, 20 aug 2006) shadowmaster $
*/

/*
 * Define important values as well as default values.
 */
// Important values:
$thistime = time();		// the current time, needed to remove old entries and set update time. DO NOT CHANGE THIS!
$baseurl = "http://people.anarchy-online.com"; // The base URL for all roster queries
$addons = "whois-update.addons";

// Default values:
$hours = 72;
$delaytime = 10;
$do_unorged_users = false;
$delete_not_updated = false;
$show_org_names = true;
$show_character_names = false;

require('whois-update.conf');

// disable execution timeout:
set_time_limit(0);

/*
Gets a URL
Heavily inspired by Shadowgod's AOXML class and examples from php.net comments.
*/
function get_site($url, $strip_headers = 0, $server_timeout = 15, $read_timeout = 15)
{
	// Parse the URL so we can use it in our raw socket request
	$get_url = parse_url($url);

	// Open the socket
	$fd = fsockopen($get_url["host"], 80, $errno, $errstr, $server_timeout);

	// Make sure the socket was created successfully
	if (!$fd)
	{
		$return["error"] = true;
		$return["errordesc"] = "Errno: $errno Errstr: $errstr";
		return $return;
	}
	else
	{
		// Set the timeout to prevent it from hanging the bot
		socket_set_timeout($fd,$read_timeout);

		// Rebuild the full query after parse_url
		$url = $get_url["path"];
		if (!empty($get_url["query"]))
		{
			$url .= '?';
			$url .= $get_url["query"];
		}

		// Send the HTTP request
		fputs($fd, "GET $url HTTP/1.0\r\n");
		fputs($fd, "Host: " . $get_url["host"] . "\r\n");
		fputs($fd, "Connection: Close\r\n");
		fputs($fd, "User-Agent: BeBot/0.4\r\n\r\n");

		// Check if the server is giving us what we wanted
		$http_response = fgets($fd);

		$results = stream_get_meta_data($fd);

		// Make sure we haven't timed out while waiting for a response
		if ($results["timed_out"] == 1)
		{
			$return["error"] = true;
			$return["errordesc"] = "Timed out while reading from ". $get_url["host"];
			$return["content"] = "";
			fclose($fd);
			return $return;
		}

		// Successfull query with no errors from the server
		if (preg_match("/(200 OK|302 Found)/", $http_response))
		{
			$return["error"] = false;
			$return["content"] = "";

			// Read the contents
			while (!feof($fd))
			{
				$return["content"] .= fgets($fd, 1024);
			}

			$results = stream_get_meta_data($fd);

			// Make sure we didn't time out while reading the response again.
			if ($results["timed_out"] == 1)
			{
				$return["error"] = true;
				$return["errordesc"] = "Timed out while reading from " . $get_url["host"];
				$return["content"] = "";
				fclose($fd);
				return $return;
			}

			// Did the calling function want http headers stripped?
			if ($strip_headers)
			{
				$split = split("\r\n\r\n",$return["content"]);
				$return["content"] = $split[1];
			}

			fclose($fd);
			return $return;
		}
		// Did not return 200 OK :(
		else
		{
			$return["error"] = true;
			$return["errordesc"] = "Server returned: $http_response";
			fclose($fd);
			return $return;
		}
	}
}

function xmlparse($xml, $tag)
{
	$tmp = explode("<" . $tag . ">", $xml);
	// Catch notice about missing array index, return empty string (which happens anyways):
	if (count($tmp) <= 1)
	{
		return "";
	}
	$tmp = explode("</" . $tag . ">", $tmp[1]);
	return $tmp[0];
}

echo "Connecting to the database.\n\n";
$link = mysql_connect($dbserver, $username, $password)
		or die("Could not connect : " . mysql_error());
mysql_select_db($dbname) or die("Could not select database");

// Get all org ids out of the whois table, get the org infos, and add/update all user:
$sqlquery = "SELECT org_id, org_name FROM (SELECT org_id, org_name, count(nickname) AS count FROM " . $tablename;
$sqlquery .= " WHERE org_id != '' GROUP BY org_name) AS temp_whois ORDER BY count DESC;";
$result = mysql_query($sqlquery) or die("Query failed : " . mysql_error());

// simple counter, increased by one each time a get_site() call is made:
$httpqueries = 0;
// same for REPLACE/UPDATE in db:
$sqlquerycount = 0;
// same for failed roster lookups:
$failedrosterlookup = 0;
// same for failed person lookups:
$failedpersonlookup = 0;


while ($orgid = mysql_fetch_array($result, MYSQL_ASSOC))
{
	if ($show_org_names)
	{
		echo gmdate("H:i:s") . " - Querying roster for " . $orgid['org_name'] . " (" . $orgid['org_id'] . ")...\n";
	}
	$org_cont = get_site($baseurl . "/org/stats/d/" . $dimension . "/name/" . $orgid['org_id'] . "/basicstats.xml", 0, 60, 60);
	$httpqueries++;
	if ($org_cont['error'])
	{
		echo "     Could not get roster for " . $orgid['org_name'] . " (" . $orgid['org_id'] . ")!\n";
		echo "     " . $org_cont['errordesc'] . "\n";
		$failedrosterlookup++;
	}
	else
	{
		$orgname = mysql_real_escape_string(xmlparse($org_cont['content'], "name"));
		$orgfaction = xmlparse($org_cont['content'], "side");
		$org = explode("<member>", $org_cont['content']);

		if ($show_org_names)
		{
			echo "     Updating " . $orgname . " (" . $orgid['org_id'] . ")!\n";
		}

		// Parse members, $org[0] is no org member!
		for ($i = 1; $i < count($org); $i++)
		{
			$content = $org[$i];
			$who["nickname"] = xmlparse($content, "nickname");
			$who["firstname"] = mysql_real_escape_string(xmlparse($content, "firstname"));
			$who["lastname"] = mysql_real_escape_string(xmlparse($content, "lastname"));
			$who["level"] = xmlparse($content, "level");
			$who["gender"] = xmlparse($content, "gender");
			$who["breed"] = xmlparse($content, "breed");
			$who["profession"] = xmlparse($content, "profession");
			$who["faction"] = $orgfaction;
			$who["rank"] = xmlparse($content, "rank_name");
			$who["rank_id"] = xmlparse($content, "rank");
			$who["org"] = $orgname;
			$who["org_id"] = $orgid['org_id'];
			$who["at_id"] = xmlparse($content, "defender_rank_id");
			$who["pictureurl"] = xmlparse($content, "photo_url");

			if (empty($who["nickname"]))
			{
				echo "     Warning! Missing members nickname!\n";
				$failedpersonlookup++;
			}
			else if (empty($who["level"]))
			{
				echo "     Warning! Missing members level! (" . $who["nickname"] . ")\n";
				$failedpersonlookup++;
			}
			else if (empty($who["gender"]))
			{
				echo "     Warning! Missing members gender! (" . $who["nickname"] . ")\n";
				$failedpersonlookup++;
			}
			else if (empty($who["breed"]))
			{
				echo "     Warning! Missing members breed! (" . $who["nickname"] . ")\n";
				$failedpersonlookup++;
			}
			else if (empty($who["profession"]))
			{
				echo "     Warning! Missing members profession! (" . $who["nickname"] . ")\n";
				$failedpersonlookup++;
			}
			else if (empty($who["faction"]))
			{
				echo "     Warning! Missing members faction! (" . $who["nickname"] . ")\n";
				$failedpersonlookup++;
			}
			else if (empty($who["rank"]))
			{
				echo "     Warning! Missing members rank! (" . $who["nickname"] . ")\n";
				$failedpersonlookup++;
			}
			else if (empty($who["org"]))
			{
				echo "     Warning! Missing members organization name! (" . $who["nickname"] . ")\n";
				$failedpersonlookup++;
			}
			else if (empty($who["org_id"]))
			{
				echo "     Warning! Missing members organization ID! (" . $who["nickname"] . ")\n";
				$failedpersonlookup++;
			}
			else
			{
				// INSERT new entries into DB, UPDATE existing ones:
				$query = "INSERT INTO " . $tablename . " (nickname, firstname, lastname, level, gender, breed, faction,"
					. " profession, defender_rank_id, org_id, org_name, org_rank, org_rank_id, pictureurl, updated)"
					. " VALUES ('" . $who["nickname"] . "', '" . $who["firstname"] . "', '" . $who["lastname"] . "', '"
					. $who["level"] . "', '" . $who["gender"] . "', '" . $who["breed"] . "', '" . $who["faction"]
					. "', '" . $who["profession"] . "', '" . $who["at_id"] . "', '" . $who["org_id"] . "', '"
					. $who["org"] . "', '" . $who["rank"] . "', '" . $who["rank_id"] . "', '" . $who["pictureurl"]
					. "','" . $thistime . "') ON DUPLICATE KEY UPDATE firstname = VALUES(firstname), lastname = "
					. "VALUES(lastname), level = VALUES(level), gender = VALUES(gender), breed = VALUES(breed), "
					. "faction = VALUES(faction), profession = VALUES(profession), org_id = VALUES(org_id), "
					. "defender_rank_id = VALUES(defender_rank_id), pictureurl = VALUES(pictureurl), updated = "
					. "VALUES(updated), org_name = VALUES(org_name), org_rank = VALUES(org_rank), org_rank_id = "
					. "VALUES(org_rank_id)";

				mysql_query($query) or  die("Query failed : " . mysql_error());
				$sqlquerycount++;
			}
		}

		// Unset all org entries that are still not updated but pointing to the current org id
		$query = "UPDATE whois SET org_id = 0, org_name = '', updated = updated - 86400 WHERE org_id = " . $orgid['org_id'] . " AND updated < " . $thistime . " - 10";
		mysql_query($query) or  die("Query failed : " . mysql_error());
	}

	if ($show_org_names)
	{
		echo "\n";
	}

	// delay next parsing by $delaytime in seconds:
	sleep($delaytime);
}

mysql_free_result($result);

$orgruntime = time() - $thistime;
$orgmins = floor($orgruntime / 60);
$orgsec = $orgruntime % 60;
$orghttp = $httpqueries;
$orgsql = $sqlquerycount;

// Only do non-orged users and other not-yet-updated if it's explicitly wished:
if ($do_unorged_users)
{
	// Now work on all not updated entries left over (no org/switched org):
	$comptime = $thistime - 10;
	$sqlquery = "SELECT nickname FROM " . $tablename . " WHERE updated < " . $comptime;
	$result = mysql_query($sqlquery) or  die("Query failed : " . mysql_error());

	while ($user = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		if ($show_character_names)
		{
			echo "Reading current stats for character " . $user['nickname'] . "...\n";
		}
		$content_arr = get_site($baseurl . "/character/bio/d/" . $dimension . "/name/" . strtolower($user['nickname']) . "/bio.xml");
		$httpqueries++;
		if (!$content_arr['error'])
		{
			$content = $content_arr['content'];
			$who["nick"] = xmlparse($content, "nick");
			$who["firstname"] = mysql_real_escape_string(xmlparse($content, "firstname"));
			$who["lastname"] = mysql_real_escape_string(xmlparse($content, "lastname"));
			$who["level"] = xmlparse($content, "level");
			$who["gender"] = xmlparse($content, "gender");
			$who["breed"] = xmlparse($content, "breed");
			$who["profession"] = xmlparse($content, "profession");
			$who["faction"] = xmlparse($content, "faction");
			$who["rank"] = xmlparse($content, "rank");
			$who["rank_id"] = xmlparse($content, "rank_id");
			if ($who["rank_id"] == '')
				$who["rank_id"] = 0;
			$who["org"] = mysql_real_escape_string(xmlparse($content, "organization_name"));
			$who["org_id"] = xmlparse($content, "organization_id");
			if ($who["org_id"] == '')
				$who["org_id"] = 0;
			$who["at"] = xmlparse($content, "defender_rank_id");
			if ($who["at"] == '')
				$who["at"] = 0;
			$who["pictureurl"] = xmlparse($content, "pictureurl");

			// make sure user info does exist, otherwise nick is empty!
			if ($who["nick"] != '')
			{
				if ($show_character_names)
				{
					echo "     Updating character " . $user['nickname'] . "!\n";
				}

				// add into DB:
				$query = "UPDATE " . $tablename . " SET "
				. "firstname='" . $who["firstname"] . "', lastname='" . $who["lastname"] . "', level='" . $who["level"]
				. "', gender='" . $who["gender"] . "', breed='" . $who["breed"] . "', faction='" . $who["faction"]
				. "', profession='" . $who["profession"] . "', defender_rank_id='" . $who["at"] . "', org_id='"
				. $who["org_id"] . "', org_name='" . $who["org"] . "', org_rank='" . $who["rank"] . "', org_rank_id='"
				. $who["rank_id"] . "', updated='" . $thistime . "', pictureurl = '" . $who["pictureurl"] . "'"
				. " WHERE nickname='" . $who["nick"] . "';";
				mysql_query($query) or  die("Query failed : " . mysql_error());
				$sqlquerycount++;
			}

			// delay next parsing by $delaytime in seconds:
			sleep($delaytime);
		}
	}
	mysql_free_result($result);
}

// What's the latest updated stamp for old outdated entries?
$deletetime = $thistime - $hours * 3600;

// Clean org infos on seriously outdated entries (using same value as for deleting):
$query = "UPDATE whois SET org_id = 0, org_name = '' WHERE updated < " . $deletetime;
mysql_query($query) or  die("Query failed : " . mysql_error());

// Only delete non-updated entries if explicitly wished:
if ($delete_not_updated)
{
	// all not-updated entries:
	$sqlquery = "DELETE FROM " . $tablename . " WHERE updated < " . $deletetime;
	mysql_query($sqlquery) or  die("Query failed : " . mysql_error());
}

// do some statistics:
$sqlquery = "SELECT count(*) as count FROM " . $tablename;
$result = mysql_query($sqlquery) or  die("Query failed : " . mysql_error());
$count = mysql_fetch_array($result, MYSQL_ASSOC);
mysql_free_result($result);

$runtime = time() - $thistime;
$mins = floor($runtime / 60);
$sec = $runtime % 60;

echo "\n\n================================================\n";
echo $orghttp . " http queries for org information done!\n";
echo $httpqueries . " total http queries done!\n";
echo $orgsql . " entries in database associated with an org modified!\n";
echo $sqlquerycount . " entries in database modified!\n";
echo $count['count'] . " entries in the whois cache!\n";
echo $failedrosterlookup . " organization rosters could not be found!\n";
echo $failedpersonlookup . " members had bad data and were not updated!\n";
echo $orgmins . "min " . $orgsec . "sec runtime for org updates!\n";
echo $mins . "min " . $sec . "sec total runtime!\n";
echo "================================================\n";

if (file_exists($addons) && is_readable($addons))
{
	include($addons);
}

mysql_close($link);
?>
