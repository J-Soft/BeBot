<?php
/*
* whois-update.php - A script that updates the whois cache
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

/*
     * Define important values as well as default values.
     */
// Important values:
$thistime = time(); // the current time, needed to remove old entries and set update time. DO NOT CHANGE THIS!
$baseurl = "http://people.anarchy-online.com"; // The base URL for all roster queries
$addons = "whois-update.addons";
// Default values:
$hours = 72;
$delaytime = 10;
$do_unorged_users = false;
$delete_not_updated = false;
$show_org_names = true;
$show_character_names = false;
require ('whois-update.conf');
// disable execution timeout:
set_time_limit(0);

// Check if we have curl available
if (!extension_loaded("curl")) {
  if ($os_windows) {
    if (@!dl("php_curl.dll")) {
      echo "Curl not available\n";
    }
    else
    {
      echo "Curl extension loaded\n";
      if (!extension_loaded("sockets")) {
        if (!dl("php_sockets.dll")) {
          die("Loading php_sockets.dll failed. Sockets extention required to run this script");
        }
      }
    }
  }
  else if (function_exists('curl_init')) {
    echo "Curl extension loaded\n";
  }
  else
  {
    echo "Curl not available\n";
    if (!extension_loaded("sockets")) {
      die("Sockets extention required to run this script");
    }
  }
}

if (!extension_loaded("mysql")) {
  if ($os_windows) {
    if (!dl("php_mysql.dll")) {
      die("Loading php_mysql.dll failed. MySQL extention required to run this script");
    }
  }
  else
  {
    die("MySQL support required to run this script");
  }
}

echo "Connecting to the database.\n\n";
$link = mysql_connect($dbserver, $username, $password) or die("Could not connect : " . mysql_error());
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
  if ($show_org_names) {
    echo gmdate("H:i:s") . " - Querying roster for " . $orgid['org_name'] . " (" . $orgid['org_id'] . ")...\n";
  }
  $org_cont = get_site($baseurl . "/org/stats/d/" . $dimension . "/name/" . $orgid['org_id'] . "/basicstats.xml", 0, 60, 60);
  $httpqueries++;
  if (!$org_cont) {
    echo "     Could not get roster for " . $orgid['org_name'] . " (" . $orgid['org_id'] . ")!\n";
    $failedrosterlookup++;
  }
  else
  {
    $starttime = time();
    $orgname = mysql_real_escape_string(xmlparse($org_cont, "name"));
    $orgfaction = xmlparse($org_cont, "side");
    $org = explode("<member>", $org_cont);
    if ($show_org_names) {
      echo "     Updating " . $orgname . " (" . $orgid['org_id'] . ")!";
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
      if (empty($who["nickname"])) {
        echo "     Warning! Missing members nickname!\n";
        $failedpersonlookup++;
      }
      else if (empty($who["level"])) {
        echo "     Warning! Missing members level! (" . $who["nickname"] . ")\n";
        $failedpersonlookup++;
      }
      else if (empty($who["gender"])) {
        echo "     Warning! Missing members gender! (" . $who["nickname"] . ")\n";
        $failedpersonlookup++;
      }
      else if (empty($who["breed"])) {
        echo "     Warning! Missing members breed! (" . $who["nickname"] . ")\n";
        $failedpersonlookup++;
      }
      else if (empty($who["profession"])) {
        echo "     Warning! Missing members profession! (" . $who["nickname"] . ")\n";
        $failedpersonlookup++;
      }
      else if (empty($who["faction"])) {
        echo "     Warning! Missing members faction! (" . $who["nickname"] . ")\n";
        $failedpersonlookup++;
      }
      else if (empty($who["rank"])) {
        echo "     Warning! Missing members rank! (" . $who["nickname"] . ")\n";
        $failedpersonlookup++;
      }
      else if (empty($who["org"])) {
        echo "     Warning! Missing members organization name! (" . $who["nickname"] . ")\n";
        $failedpersonlookup++;
      }
      else if (empty($who["org_id"])) {
        echo "     Warning! Missing members organization ID! (" . $who["nickname"] . ")\n";
        $failedpersonlookup++;
      }
      else
      {
        // INSERT new entries into DB, UPDATE existing ones:
        $query = "INSERT INTO " . $tablename . " (nickname, firstname, lastname, level, gender, breed, faction," . " profession, defender_rank_id, org_id, org_name, org_rank, org_rank_id, pictureurl, updated)" . " VALUES ('" . $who["nickname"] . "', '" . $who["firstname"] . "', '" . $who["lastname"] . "', '" . $who["level"] . "', '" . $who["gender"] . "', '" . $who["breed"] . "', '" . $who["faction"] . "', '" . $who["profession"] . "', '" . $who["at_id"] . "', '" . $who["org_id"] . "', '" . $who["org"] . "', '" . $who["rank"] . "', '" . $who["rank_id"] . "', '" . $who["pictureurl"] . "','" . $thistime . "') ON DUPLICATE KEY UPDATE firstname = VALUES(firstname), lastname = " . "VALUES(lastname), level = VALUES(level), gender = VALUES(gender), breed = VALUES(breed), " . "faction = VALUES(faction), profession = VALUES(profession), org_id = VALUES(org_id), " . "defender_rank_id = VALUES(defender_rank_id), pictureurl = VALUES(pictureurl), updated = " . "VALUES(updated), org_name = VALUES(org_name), org_rank = VALUES(org_rank), org_rank_id = " . "VALUES(org_rank_id)";
        mysql_query($query) or die("Query failed : " . mysql_error());
        $sqlquerycount++;
      }
    }
    // Unset all org entries that are still not updated but pointing to the current org id
    $query = "UPDATE whois SET org_id = 0, org_name = '', updated = updated - 86400 WHERE org_id = " . $orgid['org_id'] . " AND updated < " . $thistime . " - 10";
    mysql_query($query) or die("Query failed : " . mysql_error());

    echo "     " . $i . " members completed in " . (time() - $starttime) . " seconds\n";
  }
  if ($show_org_names) {
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
if ($do_unorged_users) {
  // Now work on all not updated entries left over (no org/switched org):
  $comptime = $thistime - 10;
  $sqlquery = "SELECT nickname FROM " . $tablename . " WHERE updated < " . $comptime;
  $result = mysql_query($sqlquery) or die("Query failed : " . mysql_error());
  while ($user = mysql_fetch_array($result, MYSQL_ASSOC))
  {
    if ($show_character_names) {
      echo "Reading current stats for character " . $user['nickname'] . "...\n";
    }
    $content_arr = get_site($baseurl . "/character/bio/d/" . $dimension . "/name/" . strtolower($user['nickname']) . "/bio.xml");
    $httpqueries++;
    if ($content_arr) {
      $content = $content_arr;
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
      if ($who["rank_id"] == '') {
        $who["rank_id"] = 0;
      }
      $who["org"] = mysql_real_escape_string(xmlparse($content, "organization_name"));
      $who["org_id"] = xmlparse($content, "organization_id");
      if ($who["org_id"] == '') {
        $who["org_id"] = 0;
      }
      $who["at"] = xmlparse($content, "defender_rank_id");
      if ($who["at"] == '') {
        $who["at"] = 0;
      }
      $who["pictureurl"] = xmlparse($content, "pictureurl");
      // make sure user info does exist, otherwise nick is empty!
      if ($who["nick"] != '') {
        if ($show_character_names) {
          echo "     Updating character " . $user['nickname'] . "!\n";
        }
        // add into DB:
        $query = "UPDATE " . $tablename . " SET " . "firstname='" . $who["firstname"] . "', lastname='" . $who["lastname"] . "', level='" . $who["level"] . "', gender='" . $who["gender"] . "', breed='" . $who["breed"] . "', faction='" . $who["faction"] . "', profession='" . $who["profession"] . "', defender_rank_id='" . $who["at"] . "', org_id='" . $who["org_id"] . "', org_name='" . $who["org"] . "', org_rank='" . $who["rank"] . "', org_rank_id='" . $who["rank_id"] . "', updated='" . $thistime . "', pictureurl = '" . $who["pictureurl"] . "'" . " WHERE nickname='" . $who["nick"] . "';";
        mysql_query($query) or die("Query failed : " . mysql_error());
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
mysql_query($query) or die("Query failed : " . mysql_error());
// Only delete non-updated entries if explicitly wished:
if ($delete_not_updated) {
  // all not-updated entries:
  $sqlquery = "DELETE FROM " . $tablename . " WHERE updated < " . $deletetime;
  mysql_query($sqlquery) or die("Query failed : " . mysql_error());
}
// do some statistics:
$sqlquery = "SELECT count(*) as count FROM " . $tablename;
$result = mysql_query($sqlquery) or die("Query failed : " . mysql_error());
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
if (file_exists($addons) && is_readable($addons)) {
  include ($addons);
}
mysql_close($link);


function get_site($url, $strip_headers = FALSE, $read_timeout = FALSE)
{
  echo "     Fetching $url";
  $starttime = time();

  if (!function_exists('curl_init')) {
    $return = get_site_sock($url, $strip_headers, $read_timeout);
  }
  else
  {
    $return = get_site_curl($url, $strip_headers, $read_timeout);
  }

  if ($return) {
    echo " .... completed in " . (time() - $starttime) . " seconds\n";
  }
  else
  {
    echo "\n";
  }

  return $return;
}

function get_site_sock($url, $strip_headers = FALSE, $read_timeout = FALSE)
{
  $return = get_site_data($url, $strip_headers, $read_timeout);

  if ($return['error']) {
    echo $return['errordesc'] . " Reason (" . $return['content'] . ")";
  }

  return $return;
}

/*
    Gets the data from a URL
    */
function get_site_data($url, $strip_headers = FALSE, $read_timeout = FALSE, $proxy = '')
{
  $get_url = parse_url($url);
  // Check to see if we're using a proxy, and get the IP address for the target host.
  if (!empty($proxy)) {
    $proxy_address = explode(":", $proxy);
    $address = gethostbyname($proxy_address[0]);
    $service_port = $proxy_address[1];
  }
  else
  {
    $address = gethostbyname($get_url['host']);
    /* Get the port for the WWW service. */
    $service_port = getservbyname('www', 'tcp');
  }
  /* Create a TCP/IP socket. */
  $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
  // Check to see if the socket failed to create.
  if ($socket === false) {
    echo "Failed to create socket. Error was: " . socket_strerror(socket_last_error());
    return false;
  }

  // Set some sane read timeouts to prevent the bot from hanging forever.
  if (!$read_timeout) {
    $read_timeout = 30;
  }

  socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $read_timeout,
                                                            "usec" => 0));

  $connect_result = @socket_connect($socket, $address, $service_port);

  // Make sure we have a connection
  if ($connect_result === false) {
    echo "Failed to connect to server " . $address . ":" . $service_port . " (" . $url . ") Error was: " . socket_strerror(socket_last_error());
    return false;
  }
  // Rebuild the full query after parse_url
  $url = $get_url["path"];
  if (!empty($get_url["query"])) {
    $url .= '?' . $get_url["query"];
  }
  $in = "GET $url HTTP/1.0\r\n";
  $in .= "Host: " . $get_url['host'] . "\r\n";
  $in .= "Connection: Close\r\n";
  $in .= "User-Agent: \r\n\r\n";
  $write_result = @socket_write($socket, $in, strlen($in));
  // Make sure we wrote to the server okay.
  if ($write_result === false) {
    echo "Failed to write to server: " . socket_strerror(socket_last_error());
    return false;
  }
  $return["content"] = "";
  $read_result = @socket_read($socket, 2048);
  while ($read_result != "" && $read_result !== false)
  {
    $return .= $read_result;
    $read_result = @socket_read($socket, 2048);
  }
  // Make sure we got a response back from the server.
  if ($read_result === false) {
    echo "Failed to read response: " . socket_strerror(socket_last_error());
    return false;
  }
  $close_result = @socket_close($socket);
  // Make sure we closed our socket properly.  Open sockets are bad!
  if ($close_result === false) {
    echo "Failed to close socket: " . socket_strerror(socket_last_error());
    return false;
  }
  // Did the calling function want http headers stripped?
  if ($strip_headers) {
    $split = split("\r\n\r\n", $return);
    $return = $split[1];
  }
  return $return;
}


function get_site_curl($url, $strip_headers = FALSE, $timeout = FALSE, $post = NULL, $login = NULL) // login should be username:password
{
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERAGENT, "");
  // Set your login and password for authentication
  //curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
  //curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$pw);
  // You can use CURLAUTH_BASIC, CURLAUTH_DIGEST, CURLAUTH_GSSNEGOTIATE,
  // CURLAUTH_NTLM, CURLAUTH_ANY, and CURLAUTH_ANYSAFE
  //
  // You can use the bitwise | (or) operator to combine more than one method.
  // If you do this, CURL will poll the server to see what methods it supports and pick the best one.
  //
  // CURLAUTH_ANY is an alias for CURLAUTH_BASIC | CURLAUTH_DIGEST |
  // CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM
  //
  // CURLAUTH_ANYSAFE is an alias for CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE |
  // CURLAUTH_NTLM
  //
  // Personally I prefer CURLAUTH_ANY as it covers all bases
  // This is occassionally required to stop CURL from verifying the peer's certificate.
  // CURLOPT_SSL_VERIFYHOST may also need to be TRUE or FALSE if
  // CURLOPT_SSL_VERIFYPEER is disabled (it defaults to 2 - check the existence of a
  // common name and also verify that it matches the hostname provided)
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  // Optional: Return the result instead of printing it
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  // Specify a timeout
  if (!$timeout) {
    $timeout = 30;
  }

  curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
  // The usual - get the data and close the session
  $return = curl_exec($ch);
  curl_close($ch);
  // Did the calling function want http headers stripped?
  //if ($strip_headers)// already stripped?
  //{
  //	$split = split("\r\n\r\n",$return);
  //	$return = $split[1];
  //}
  Return $return;
}

/*
    Parse XML crap
    */
function xmlparse($xml, $tag)
{
  $tmp = explode("<" . $tag . ">", $xml);
  if (!isset($tmp[1])) {
    $tmp[1] = "";
  }
  $tmp = explode("</" . $tag . ">", $tmp[1]);
  return $tmp[0];
}

?>
