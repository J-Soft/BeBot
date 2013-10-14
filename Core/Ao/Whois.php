<?php
/*
* Whois.php - Funcom XML whois lookup with database caching.
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
whois($name):
Preform an XML lookup for $name
- On error returns
instanceof BotError with description of the error.
- Returns array:
$array["id"] - Character id
$array["nickname"] - Character nickname
$array["firstname"] - Character first name or NULL
$array["lastname"] - Character last name or NULL
$array["level"] - Character level
$array["gender"] - Character gender
$array["profession"] - Character profession
$array["faction"] - Caracter faction
$array["rank"] - Character guild rank name or NULL
$array["rank_id"] - Character guild rank id or NULL
$array["org"] - Character guild name or NULL
$array["org_id"] - Character guild id or NULL
$array["at"] - Character alien defender rank name or NULL
$array["at_id"] - Character alien defender rank id or NULL
$array["pictureurl"] - URL to character picture
*/
$whois_core = new Whois_Core($bot);
class Whois_Core extends BasePassiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        /*
        Create tables for our whois cache if it does not already exsist.
        */
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("whois", "false") . " (
					ID BIGINT NOT NULL default '0',
					nickname varchar(15) NOT NULL default '',
					firstname varchar(20) NOT NULL default '',
					lastname varchar(20) NOT NULL default '',
					level tinyint(3) unsigned NOT NULL default '1',
					gender enum('Female','Male','Neuter') NOT NULL default 'Female',
					breed enum('Atrox','Nano','Opifex','Solitus') NOT NULL default 'Atrox',
					faction enum('Clan','N/A','Neutral','Omni') NOT NULL default 'Clan',
					profession enum('Adventurer','Agent','Bureaucrat','Doctor','Enforcer','Engineer','Fixer','Keeper','Martial Artist',
						'Meta-Physicist','Nano-Technician','Shade','Soldier','Trader') NOT NULL default 'Adventurer',
					defender_rank enum('Able','Accomplished','Adept','Amateur','Backer','Beginner','Challenger','Champ','Common',
						'Competent','Defender','Fair','Fledgling','Guardian','Hero','Intermediate','Medalist','Mediocre','Newcomer',
						'None','Patron','Protector','Qualified','Starter','Student','Suited','Supporter','Talented','Trustworthy',
						'Vanquisher','Vindicator') NOT NULL default 'None',
					defender_rank_id enum('0','1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19',
						'20','21','22','23','24','25','26','27','28','29','30') NOT NULL default '0',
					org_id bigint(10) NOT NULL default '0',
					org_name varchar(50) NOT NULL default '',
					org_rank varchar(20) NOT NULL default '',
					org_rank_id enum('0','1','2','3','4','5','6','7','8','9','10') NOT NULL default '0',
					pictureurl varchar(100) NOT NULL default '',
					used bigint(25) NOT NULL default '0',
					updated int(10) NOT NULL default '0',
					PRIMARY KEY  (nickname),
					KEY ID (ID),
					KEY Profession (profession),
					KEY Faction (faction),
					KEY OrgID (org_id),
					KEY Orgname (org_name),
					KEY Level (level),
					KEY Alienlevel (defender_rank_id),
					KEY updated (updated),
					KEY used (used)
				)"
        );
        $this->register_module("whois");
        $this->register_event("cron", "1hour");
        $this->cache = array();
        $this->alien_rank_name = array();
        $this->alien_rank = array();
        $this->create_name_cache();
        $this->bot->core("settings")
            ->create(
            "Whois", "MaxCacheSize", 100,
            "What is the maximum number of whois entries that should be cached internally in the bot at the same time to reduce load on the SQL server?",
            "10;25;50;75;100;200;300;500;1000"
        );
        $this->bot->core("settings")
            ->create(
            "Whois", "MaxTimeInCache", 3,
            "After how many hours in the internal query cache should an entry be removed because it could be outdated or free memory should be freed? This setting does not affect database entries in any way.",
            "1;2;3;4;5;6;7;8;9;10;11;12"
        );
        $this->bot->core("settings")
            ->create(
            "Whois", "TimeValid", 1,
            "After how many days should the whois information in a database entry be considered outdated and looked for an update? NOTICE: Only use values higher then one day if you have trouble connecting to the FC website or are running the external updating script.",
            "1;2;3;4;5"
        );
        $this->bot->core("settings")
            ->create('Whois', 'LookupOrder', 'funcom_auno', 'Which order should we use to look up players information?', 'funcom_auno;auno_funcom;funcom_only;auno_only');
        $this->bot->core("settings")
            ->create('Whois', "Debug", FALSE, "Show debug information (such as Character ID, Org ID, etc)");
        $this->update_table();
    }


    function update_table()
    {
        if ($this->bot->db->get_version("whois") == 5) {
            return;
        }
        switch ($this->bot->db->get_version("whois")) {
        case 1: // Update Table version to prevent repeat update calls
            //was an update for a setting
            $this->bot->db->set_version("whois", 2);
            $this->update_table();
            return;
        case 2:
            $this->bot->db->set_version("whois", 3);
            $this->update_table();
            return;
        case 3:
            $this->bot->db->set_version("whois", 4);
            $this->update_table();
            return;
        case 4:
            $this->bot->db->update_table("whois", "ID", "alter", "ALTER TABLE #___whois MODIFY ID BIGINT NOT NULL");
            $this->bot->db->set_version("whois", 5);
            $this->update_table();
            return;
        default:
        }

    }


    function create_name_cache()
    {
        $this->alien_rank_name[0] = "None";
        $this->alien_rank_name[1] = "Fledgling";
        $this->alien_rank_name[2] = "Amateur";
        $this->alien_rank_name[3] = "Beginner";
        $this->alien_rank_name[4] = "Starter";
        $this->alien_rank_name[5] = "Newcomer";
        $this->alien_rank_name[6] = "Student";
        $this->alien_rank_name[7] = "Common";
        $this->alien_rank_name[8] = "Intermediate";
        $this->alien_rank_name[9] = "Mediocre";
        $this->alien_rank_name[10] = "Fair";
        $this->alien_rank_name[11] = "Able";
        $this->alien_rank_name[12] = "Accomplished";
        $this->alien_rank_name[13] = "Adept";
        $this->alien_rank_name[14] = "Qualified";
        $this->alien_rank_name[15] = "Competent";
        $this->alien_rank_name[16] = "Suited";
        $this->alien_rank_name[17] = "Talented";
        $this->alien_rank_name[18] = "Trustworthy";
        $this->alien_rank_name[19] = "Supporter";
        $this->alien_rank_name[20] = "Backer";
        $this->alien_rank_name[21] = "Defender";
        $this->alien_rank_name[22] = "Challenger";
        $this->alien_rank_name[23] = "Patron";
        $this->alien_rank_name[24] = "Protector";
        $this->alien_rank_name[25] = "Medalist";
        $this->alien_rank_name[26] = "Champ";
        $this->alien_rank_name[27] = "Hero";
        $this->alien_rank_name[28] = "Guardian";
        $this->alien_rank_name[29] = "Vanquisher";
        $this->alien_rank_name[30] = "Vindicator";
        foreach ($this->alien_rank_name as $level => $name) {
            $this->alien_rank[$name] = $level;
        }
    }


    function cron()
    {
        $this->cleanup_cache();
    }


    // Removes old entries from cache to make room for new ones
    // All entries older then MaxTimeInCache get removed
    // If none meets this requirement the oldest entry gets removed to free one spot
    function cleanup_cache()
    {
        $oldesttime = -1;
        $oldestname = "";
        $thistime = time();
        foreach ($this->cache as $nick => $who) {
            if ($who["timestamp"] < $thistime - 60 * 60 * $this->bot
                ->core("settings")->get("Whois", "MaxTimeInCache")
            ) {
                unset($this->cache[$nick]);
            }
            else {
                if ($oldesttime == -1) {
                    $oldesttime = $who["timestamp"];
                    $oldestname = $nick;
                }
                else {
                    if ($oldesttime > $who["timestamp"]) {
                        $oldesttime = $who["timestamp"];
                        $oldestname = $nick;
                    }
                }
            }
        }
        if (count($this->cache) >= $this->bot->core("settings")
            ->get("Whois", "MaxCacheSize")
        ) {
            unset($this->cache[$oldestname]);
        }
    }


    // Remove $who from cache
    function remove_from_cache($who)
    {
        if (isset($this->cache[$who])) {
            unset($this->cache[$who]);
        }
    }


    // Add $who to cache, make sure cache doesn't grow too large
    function add_to_cache($who)
    {
        // If cache has grown to maximum size clean it up
        if (count($this->cache) >= $this->bot->core("settings")
            ->get("Whois", "MaxCacheSize")
        ) {
            $this->cleanup_cache();
        }
        // We got room now, just add the new entry to cache
        $who["timestamp"] = time();
        $this->cache[ucfirst(strtolower($who["nickname"]))] = $who;
    }


    /**
     * Internal whois function with caching. Code mainly by Alreadythere.
     *
     * @param $name      User name of the player to lookup whois information
     * @param $noupdate  If no stale data is cached, get whois information by retrieving funcoms xml player list via web.
     * @param $nowait    This has NO MEANING AT ALL! It only exists to be compatible with the AoC equivalent of this function.
     *
     * @return The WHO array, or false, or BotError
     */
    function lookup($name, $noupdate = FALSE, $nowait = FALSE)
    {
        if ($this->bot->core("settings")->get("Statistics", "Enabled")) {
            $this->bot->core("statistics")
                ->capture_statistic("Whois", "Lookup");
        }

        $name = ucfirst(strtolower($name));
        $uid = $this->bot->core("player")->id($name);

        /*
        Make sure we havent been passed a bogus name.
        */
        if ($uid instanceof BotError) {
            $this->error->set("$name appears to be a non exsistant character.");
            return $this->error;
        }
        // Check cache for entry first.
        if (isset($this->cache[$name])) {
            // return entry if cached one isn't outdated yet
            if ($this->cache[$name]["timestamp"] >= time() - 60 * 60 * $this->bot
                ->core("settings")->get("Whois", "MaxTimeInCache")
            ) {
                return $this->cache[$name];
            }
            // entry outdated, remove it and get it from db again
            unset($this->cache[$name]);
        }
        $lookup = $this->bot->db->select("SELECT * FROM #___whois WHERE nickname = '" . $name . "'", MYSQLI_ASSOC);
        /*
        If we have a result, we assume we might need to use it in case funcom XML is unresponsive.
        */
        if (!empty($lookup)) {
            $who["id"] = $lookup[0]['ID'];
            $who["nickname"] = $lookup[0]['nickname'];
            $who["firstname"] = stripslashes($lookup[0]['firstname']);
            $who["lastname"] = stripslashes($lookup[0]['lastname']);
            $who["level"] = $lookup[0]['level'];
            $who["gender"] = $lookup[0]['gender'];
            $who["breed"] = $lookup[0]['breed'];
            $who["profession"] = $lookup[0]['profession'];
            $who["class"] = $lookup[0]['profession'];
            $who["faction"] = $lookup[0]['faction'];
            $who["rank"] = $lookup[0]['org_rank'];
            $who["rank_id"] = $lookup[0]['org_rank_id'];
            $who["org"] = stripslashes($lookup[0]['org_name']);
            $who["org_id"] = $lookup[0]['org_id'];
            $who["at_id"] = $lookup[0]['defender_rank_id'];
            $who["at"] = $this->alien_rank_name[$who["at_id"]];
            $who["pictureurl"] = $lookup[0]['pictureurl'];
            // Check if user id needs to be updated, only done if entry in DB has 0 as UID:
            if ($lookup[0]['ID'] == 0 || $lookup[0]['ID'] == -1) {
                $this->bot->db->query("UPDATE #___whois SET ID = '" . $uid . "' WHERE nickname = '" . $name . "'");
                $lookup[0]['ID'] = $uid;
                $who["id"] = $uid;
            }
            /*
            If the result isn't stale yet and the userid's match, use it.
            */
            if ($lookup[0]['updated'] >= (time() - ($this->bot->core("settings")
                ->get("whois", "timevalid") * 24 * 3600))
                && $lookup[0]['ID'] == $uid
            ) {
                $this->add_to_cache($who);
                return $who;
            }
        }
        /*
        If noupdate = true and old data exists, return the old data.
        If noupdate = true but no old data, return error.
        */
        if ($noupdate) {
            if (empty($lookup)) {
                // No old data exists, return error:
                $this->error->set("No chached character data was found for $name, but no web lookup mode was requested!");
                return $this->error;
            }
            else {
                // only cache valid entries
                // FIXME: Why caching it here? If we got a db result and it was up-to-date,
                //        we already chached and returned it. If it wasn't up-to-date there
                //        is no point in chaching it. However we might wanna return the
                //        outdated info, because the caller didn't want to update it.
                $this->add_to_cache($who);
                return $who;
            }
        }
        /*
        Get XML data via the web.
        */
        $result = $this->get_playerxml($name);
        /*
        We got a result.
        */
        if (!($result instanceof BotError)) {
            $who["id"] = $uid;
            $who["nickname"] = $this->bot->core("tools")
                ->xmlparse($result, "nick");
            $who["firstname"] = $this->bot->core("tools")
                ->xmlparse($result, "firstname");
            $who["lastname"] = $this->bot->core("tools")
                ->xmlparse($result, "lastname");
            $who["level"] = $this->bot->core("tools")
                ->xmlparse($result, "level");
            $who["gender"] = $this->bot->core("tools")
                ->xmlparse($result, "gender");
            $who["breed"] = $this->bot->core("tools")
                ->xmlparse($result, "breed");
            $who["profession"] = $this->bot->core("tools")
                ->xmlparse($result, "profession");
            $who["faction"] = $this->bot->core("tools")
                ->xmlparse($result, "faction");
            $who["rank"] = $this->bot->core("tools")
                ->xmlparse($result, "rank");
            $who["rank_id"] = $this->bot->core("tools")
                ->xmlparse($result, "rank_id");
            if ($who["rank_id"] == '') {
                $who["rank_id"] = '0';
            }
            $who["org"] = $this->bot->core("tools")
                ->xmlparse($result, "organization_name");
            $who["org_id"] = $this->bot->core("tools")
                ->xmlparse($result, "organization_id");
            if ($who["org_id"] == '') {
                $who["org_id"] = '0';
            }
            $who["at_id"] = $this->bot->core("tools")
                ->xmlparse($result, "defender_rank_id");
            if ($who["at_id"] == '') {
                $who["at_id"] = '0';
            }
            $who["at"] = $this->alien_rank_name[$who["at_id"]];
            $who["pictureurl"] = $this->bot->core("tools")
                ->xmlparse($result, "pictureurl");
            /*
            Update our cache
            */
            $this->update($who);
            // Cache result
            $this->add_to_cache($who);
            return $who;
        }
        /*
        Unable to lookup from XML, fallback to our cached version.
        */
        else {
            if (!(empty($lookup))) {
                return $who;
            }
            /*
            All has failed, fess up.
            */
            else {
                $this->error->set(
                    "Character lookup could not be completed. people.anarchy-online.com and www.auno.org lookups have failed and no cached data is available for $name."
                );
                return $this->error;
            }
        }
    }


    /*
    Get player XML data. If lookup via FunCom fails, try Auno.
    Return whatever we get. If both FunCom and Auno's XML fail,
    you rewrite $xml->description.
    */
    function get_playerxml($name)
    { // Start function get_playerxml()
        $name = strtolower($name);
        $fcurl = "http://people.anarchy-online.com/character/bio/d/" . $this->bot->dimension . "/name/" . strtolower($name) . "/bio.xml";
        $aunourl = "http://auno.org/ao/char.php?output=xml&dimension=" . $this->bot->dimension . "&name=" . strtolower($name);
        if ($this->bot->core("settings")
            ->get("Whois", "LookupOrder") == "funcom_auno"
        ) {
            $site1NAME = "Anarchy-Online";
            $site1URL = $fcurl;
            $site2NAME = "Auno";
            $site2URL = $aunourl;
        }
        elseif ($this->bot->core("settings")
            ->get("Whois", "LookupOrder") == "funcom_only"
        ) {
            $site1NAME = "Anarchy-Online";
            $site1URL = $fcurl;
            $site2NAME = FALSE;
        }
        elseif ($this->bot->core("settings")
            ->get("Whois", "LookupOrder") == "auno_only"
        ) {
            $site1NAME = "Auno";
            $site2URL = $aunourl;
            $site2NAME = FALSE;
        }
        else {
            $site1NAME = "Auno";
            $site2URL = $aunourl;
            $site2NAME = "Anarchy-Online";
            $site1URL = $fcurl;
        }
        $xml = $this->bot->core("tools")->get_site($site1URL);
        $xml = $this->check_xml($xml);
        if ($this->bot->core("settings")->get("Statistics", "Enabled")) {
            $this->bot->core("statistics")
                ->capture_statistic("Whois", "Lookup", $site1NAME);
        }
        if (($xml instanceof BotError) && $site2NAME) {
            $xml = $this->bot->core("tools")->get_site($site2URL);
            $xml = $this->check_xml($xml);
            if ($this->bot->core("settings")->get("Statistics", "Enabled")) {
                $this->bot->core("statistics")
                    ->capture_statistic("Whois", "Lookup", $site2NAME);
            }
        }
        if ($xml instanceof BotError) {
            // If we get here, both Auno and FunCom XML lookups have failed.
            // Rewrite the error message to reflect this before returning.
            $xml->set_description("people.anarchy-online.com and www.auno.org lookups have failed for $name.");
            if ($this->bot->core("settings")->get("Statistics", "Enabled")) {
                $this->bot->core("statistics")
                    ->capture_statistic("Whois", "Lookup", "BadName");
            }
        }
        // Retrun whatever we've got at this point.
        return $xml;
    } // End function get_playerxml()

    /*
    Performs a quick check to make sure XML data is parsable.
    */
    function check_xml($xml)
    { // Start function check_xml()
        if ($xml instanceof BotError) {
            return $xml; // The XML is bad to start with, no more checking needed.
        }
        if (strpos($xml, '404 Not Found') !== FALSE) {
            $this->error->set("404 Not Found error encountered");
            return $this->error;
        }
        $nickname = $this->bot->core("tools")->xmlparse($xml, "nick");
        /*
        We have an empty nick despite having gotten a valid responce from Funcom XML? Bail!
        This should __never__ happen, but you can never rule out errors on Funcom's end.
        */
        if ($nickname == '') {
            $error = $this->bot->core("tools")->xmlparse($xml, "error");
            if ($error != '') {
                $this->error->set(
                    "Error encountered while parsing XML data: " . $this->bot
                        ->core("tools")->xmlparse($xml, "description")
                );
                return $this->error;
            }

            $this->error->set("Could not parse XML data.");
            return $this->error;
        }
        else {
            return $xml;
        } // If we get here, all should be well.
    } // End function check_xml()

    /*
    Updates whois cache info with passed array.
    */
    function update($who)
    { // Start function update()
        //Adding in some validation and error handling due to an unknown bug (work around).
        //If ID is stops being 0, then remove this code.
        if ($who["id"] instanceof BotError) {
            $this->bot->log("DEBUG", "WHOIS", "update() encountered instanceof BotError for " . $who["nickname"]);
            return false;
        } else {
            if ($who["id"] < 1) {
                $this->bot->log('Whois', 'Update', $who["nickname"] . " had an invalid user ID! UID: " . $who["id"]);
                $who["id"] = $this->bot->core("player")->id($who["nickname"]);
            }

            if ($who["id"] >= 1) {
                /*
                Update our database cache
                */
                $this->bot->db->query(
                    "INSERT INTO #___whois (id, nickname, firstname, lastname, level, gender, breed, faction,"
                        . " profession, defender_rank_id, org_id, org_name, org_rank, org_rank_id, pictureurl, updated)" . " VALUES ('" . $who["id"] . "', '" . $who["nickname"]
                        . "', '" . $this->bot->db->real_escape_string($who["firstname"]) . "', '" . $this->bot->db->real_escape_string($who["lastname"]) . "', '" . $who["level"] . "', '" . $who["gender"]
                        . "', '" . $who["breed"] . "', '" . $who["faction"] . "', '" . $who["profession"] . "', '" . $who["at_id"] . "', '" . $who["org_id"] . "', '"
                        . $this->bot->db->real_escape_string($who["org"]) . "', '" . $who["rank"] . "', '" . $who["rank_id"] . "', '" . $who["pictureurl"] . "','" . time()
                        . "') ON DUPLICATE KEY UPDATE id = VALUES(id), "
                        . "firstname = VALUES(firstname), lastname = VALUES(lastname), level = VALUES(level), gender = VALUES(gender), "
                        . "breed = VALUES(breed), faction = VALUES(faction), profession = VALUES(profession), "
                        . "defender_rank_id = VALUES(defender_rank_id), pictureurl = VALUES(pictureurl), updated = VALUES(updated), "
                        . "org_id = VALUES(org_id), org_name = VALUES(org_name), org_rank = VALUES(org_rank), org_rank_id = VALUES(org_rank_id)"
                );
                // Clear from memory cache
                $this->remove_from_cache($who["nickname"]);
                return true;
            }
            else {
                return false;
            }
        }
    } // End function udpate()

    function whois_details($source, $whois)
    {
        $seen = "";
        $alts = "";
        $window = "\n##normal## Name:##end## ##highlight##";
        if ($whois['firstname'] != '') {
            $window .= $whois['firstname'] . " ";
        }
        $window .= "'##{$whois['faction']}##{$whois['nickname']}##end##'";
        if ($whois['lastname'] != '') {
            $window .= " " . $whois['lastname'];
        }
        $window .= "\n";
        $window .= " ##normal##Level: ##highlight##{$whois['level']}##end##\n";
        $window .= " Defender Rank: ##highlight##{$whois['at']} ({$whois['at_id']})##end##\n";
        $window .= " Breed: ##highlight##{$whois['breed']}##end##\n";
        $window .= " Gender: ##highlight##{$whois['gender']}##end##\n";
        $window .= " Profession: ##highlight##{$whois['profession']}##end##\n";
        if ($this->bot->core("settings")->get('Whois', 'Debug')) {
            $window .= " Character ID: ##highlight##" . $this->bot
                ->core("tools")
                ->int_to_string($whois['id']) . "##end####end##\n\n";
        }
        if ($this->bot->core("security")->check_access(
            $source, $this->bot
                ->core("settings")->get('Security', 'Whois')
        )
        ) {
            $access = $this->bot->core("security")
                ->get_access_level($whois['nickname']);
            $this->bot->core("security")->get_access_name($access);
            $window .= " ##normal##Bot access: ##highlight##" . ucfirst(
                strtolower(
                    $this->bot
                        ->core("security")->get_access_name($access)
                )
            );
            if ($this->bot->core("settings")->get('Whois', 'Debug')) {
                $window .= " ($access)";
            }
            $window .= " ##end####end##\n\n";
        }
        if (!empty($whois['org'])) {
            $window .= " ##normal##Organization: ##highlight##{$whois['org']}##end##\n";
            $window .= " Organization Rank: ##highlight##{$whois['rank']}";
            if ($this->bot->core("settings")->get('Whois', 'Debug')) {
                $window .= " ({$whois['rank_id']})";
            }
            $window .= "##end##\n";
            if ($this->bot->core("settings")->get('Whois', 'Debug')) {
                $window .= " Organization ID: ##highlight##" . ($whois['org_id']) . "##end##\n";
            }
            $window .= "\n";
        }
        $online = $this->bot->core("online")
            ->get_online_state($whois['nickname']);
        $window .= "##normal## Status: " . $online['content'] . $seen . "##end##\n";
        if ($online['status'] <= 0) {
            if ($this->bot->core("settings")->get("Whois", "LastSeen")) {
                $lastseen = $this->bot->core("online")
                    ->get_last_seen($whois['nickname']);
                if ($lastseen) {
                    $window .= "##normal## Last Seen: ##highlight##" . gmdate(
                        $this->bot
                            ->core("settings")
                            ->get("time", "formatstring"), $lastseen
                    ) . "##end####end##\n";
                }
            }
        }
        if ($this->bot->core("settings")->get('Whois', 'Debug')) {
            $whois_debug = $this->bot->db->select("SELECT updated FROM #___whois WHERE nickname = '" . $whois['nickname'] . "'", MYSQLI_ASSOC);
            $user_debug = $this->bot->db->select(
                "SELECT id,notify,user_level,added_by,added_at,deleted_by,deleted_at,updated_at FROM #___users WHERE nickname = '" . $whois['nickname'] . "'", MYSQLI_ASSOC
            );
            $window .= "\n##red## Debug Information:##end##\n";
            if (isset($whois_debug[0]) && !empty($whois_debug[0]['updated'])) {
                $window .= " ##normal##Whois Updated Time: ##highlight## " . gmdate(
                    $this->bot
                        ->core("settings")
                        ->get("time", "formatstring"), $whois_debug[0]['updated']
                ) . "##end##\n";
            }
            if (isset($user_debug) && !empty($user_debug[0]['id'])) {
                if (!empty($user_debug[0]['added_by'])) {
                    $window .= " ##normal##User Added By: ##highlight## " . $user_debug[0]['added_by'] . "##end##\n";
                    $window .= " ##normal##User Added At: ##highlight## " . gmdate(
                        $this->bot
                            ->core("settings")
                            ->get("time", "formatstring"), $user_debug[0]['added_at']
                    ) . "##end##\n";
                }
                if (!empty($user_debug[0]['deleted_by'])) {
                    $window .= " ##normal##User Deleted By: ##highlight## " . $user_debug[0]['deleted_by'] . "##end##\n";
                    $window .= " ##normal##User Deleted At: ##highlight## " . gmdate(
                        $this->bot
                            ->core("settings")
                            ->get("time", "formatstring"), $user_debug[0]['deleted_at']
                    ) . "##end##\n";
                }
                $window .= " ##normal##User Updated At: ##highlight## " . gmdate(
                    $this->bot
                        ->core("settings")
                        ->get("time", "formatstring"), $user_debug[0]['updated_at']
                ) . "##end##\n";
                $flag_count = 0;
                if ($user_debug[0]['notify'] == 1) {
                    if ($flag_count >= 1) {
                        $flag .= ", ";
                    }
                    $flag .= "Notify";
                    $flag_count++;
                }
                if ($user_debug[0]['user_level'] == 2) {
                    if ($flag_count >= 1) {
                        $flag .= ", ";
                    }
                    $flag .= "Member";
                    $flag_count++;
                }
                if ($user_debug[0]['user_level'] == 1) {
                    if ($flag_count >= 1) {
                        $flag .= ", ";
                    }
                    $flag .= "Guest";
                    $flag_count++;
                }
                if ($user_debug[0]['user_level'] == 0) {
                    if ($flag_count >= 1) {
                        $flag .= ", ";
                    }
                    $flag .= "Not a Member";
                    $flag_count++;
                }
                if ($flag_count >= 1) {
                    $window .= " ##normal##Flags: ##highlight##\n";
                    $window .= $flag . "\n##end####end##";
                }
            }
        }
        if ($this->bot->core("settings")->get("Whois", "Alts") == TRUE) {
            $alts = $this->bot->core("alts")->show_alt($whois['nickname'], 1);
            if ($alts['alts']) {
                $window .= "\n" . $alts['list'];
            }
        }
        if ($this->bot->core("settings")->get("Whois", "ShowOptions") == TRUE) {
            $window .= "\n##normal##::: Options :::##end##\n";
            $window .= $this->bot->core("tools")
                ->chatcmd('addbuddy ' . $whois['nickname'], 'Add to buddylist', 'cc') . "\n";
            $window .= $this->bot->core("tools")
                ->chatcmd('rembuddy ' . $whois['nickname'], 'Remove from buddylist', 'cc') . "\n";
            $window .= $this->bot->core("tools")
                ->chatcmd('history ' . $whois['nickname'], 'Character history') . "\n";
        }
        if ($this->bot->core("settings")->get("Whois", "ShowLinks") == TRUE) {
            $funcomURL = "http://people.anarchy-online.com/character/bio/d/" . $this->bot->dimension . "/name/" . strtolower($whois['nickname']);
            $vhabotURL = "http://characters.vhabot.net/character.php?character=" . strtolower($whois['nickname']) . "&dimension=" . $this->bot->dimension;
            $aunoURL = "http://auno.org/ao/char.php?dimension=" . $this->bot->dimension . "&name=" . strtolower($whois['nickname']);
            $window .= "\n##normal##::: Links :::##end##\n";
            $window .= $this->bot->core("tools")
                ->chatcmd($funcomURL, 'Official character bio', 'start') . "\n";
            $window .= $this->bot->core("tools")
                ->chatcmd($vhabotURL, 'Vhab\'s character info (beta)', 'start') . "\n";
            $window .= $this->bot->core("tools")
                ->chatcmd($aunoURL, 'Auno\'s character info', 'start') . "\n";
        }
        return ($this->bot->core("tools")->make_blob("Details", $window));
    }
}

?>
