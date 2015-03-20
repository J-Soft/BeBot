<?php
/*
* Global shortcut handling.
* Written by Alreadythere (RK2).
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
$shortcuts_core = new ShortCuts_Core($bot);

class ShortCuts_Core extends BasePassiveModule
{
    var $short; // cache of shortcuts indexed by long descriptions
    var $long; // cache of long descriptions indexed by shortcuts

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->bot->db->query(
          "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("shortcuts",
            "false") . " (id INT NOT NULL AUTO_INCREMENT UNIQUE, "
          . " shortcut VARCHAR(20) NOT NULL PRIMARY KEY, " . " long_desc VARCHAR(255) NOT NULL UNIQUE)"
        );
        $this->bot->db->query(
          "INSERT IGNORE INTO #___shortcuts (`shortcut`, `long_desc`) VALUES "
          . "('Pres', 'President'), ('Gen', 'General'), ('SC', 'Squad Commander'), ('UC', 'Unit Commander'), "
          . "('UL', 'Unit Leader'), ('UM', 'Unit Member'), ('App', 'Applicant'), ('Dir', 'Director'), ('BM', 'Board Member'), "
          . "('Exec', 'Executive'), ('Mem', 'Member'), ('Adv', 'Advisor'), ('Vet', 'Veteran'), ('Mon', 'Monarch'), "
          . "('Coun', 'Counsel'), ('Fol', 'Follower'), ('Anar', 'Anarchist'), ('Lord', 'Lord'), ('Knght', 'Knight'), " . "('Vas', 'Vassal '), ('Peas', 'Peasant')"
        );
        $this->register_module("shortcuts");
        $this->register_event("cron", "1hour");
        $this->create_caches();
    }


    // Creates the caches, both indexes are transformed to lower case
    function create_caches()
    {
        $this->short = array();
        $this->long = array();
        $ret = $this->bot->db->select("SELECT shortcut, long_desc FROM #___shortcuts");
        if (!empty($ret)) {
            foreach ($ret as $pair) {
                $pair[0] = stripslashes($pair[0]);
                $pair[1] = stripslashes($pair[1]);
                $this->short[strtolower($pair[1])] = $pair[0];
                $this->long[strtolower($pair[0])] = $pair[1];
            }
        }
    }


    function cron()
    {
        $this->create_caches();
    }


    // Returns the shortcut for the argument if it exists, the unmodified argument otherwise
    function get_short($long)
    {
        if (isset($this->short[strtolower($long)])) {
            return $this->short[strtolower($long)];
        }
        return $long;
    }


    // Returns the long description of a shortcut if the shortcut is defined, the unmodified argument otherwise
    function get_long($short)
    {
        if (isset($this->long[strtolower($short)])) {
            return $this->long[strtolower($short)];
        }
        return $short;
    }


    // Adds a new shortcut to table and cache, returns an error if the shortcut is already defined
    function add($short, $long)
    {
        if (isset($this->short[strtolower($long)])) {
            $this->error->set('The text ' . $long . ' already is in the databse with shortcut "' . $this->short[strtolower($long)] . '"!');
            return $this->error;
        }
        if (isset($this->long[strtolower($short)])) {
            $this->error->set('The shortcut ' . $short . ' is already defined for "' . $this->long[strtolower($short)] . '"!');
            return $this->error;
        }
        $this->long[strtolower($short)] = $long;
        $this->short[strtolower($long)] = $short;
        $this->bot->db->query("INSERT INTO #___shortcuts (shortcut, long_desc) VALUES ('" . mysql_real_escape_string($short) . "', '" . mysql_real_escape_string($long) . "')");
        return 'New shortcut "' . $short . '" added to database with corresponding long entry "' . $long . '".';
    }


    // Removes an entry based on the shortcut
    function delete_shortcut($short)
    {
        if (!isset($this->long[strtolower($short)])) {
            $this->error->set('The shortcut "' . $short . '" does not exist in the database!');
            return $this->error;
        }
        unset($this->short[strtolower($this->long[strtolower($short)])]);
        unset($this->long[strtolower($short)]);
        $this->bot->db->query("DELETE FROM #___shortcuts WHERE shortcut = '" . mysql_real_escape_string($short) . "'");
        return 'The shortcut "' . $short . '" and the corresponding long description "' . $this->long[strtolower($short)] . '" were deleted!';
    }


    // Removes an entry based on the long description
    function delete_description($long)
    {
        if (!isset($this->short[strtolower($long)])) {
            $this->error->set('The description "' . $long . '" does not exist in the database!');
            return $this->error;
        }
        unset($this->long[strtolower($this->short[strtolower($long)])]);
        unset($this->short[strtolower($long)]);
        $this->bot->db->query("DELETE FROM #___shortcuts WHERE long_desc = '" . mysql_real_escape_string($long) . "'");
        return 'The description "' . $long . '" and the corresponding shortcut "' . $this->short[strtolower($long)] . '" were deleted!';
    }


    // Removes an entry based on it's ID
    function delete_id($id)
    {
        $ret = $this->bot->db->select("SELECT shortcut, long_desc FROM #___shortcuts WHERE id = " . $id);
        if (empty($ret)) {
            $this->error->set("No entry with the ID " . $id . " exists!");
            return $this->error;
        }
        $ret[0][0] = stripslashes($ret[0][0]);
        $ret[0][1] = stripslashes($ret[0][1]);
        unset($this->long[strtolower($ret[0][1])]);
        unset($this->short[strtolower($ret[0][0])]);
        $this->bot->db->query("DELETE FROM #___shortcuts WHERE id = " . $id);
        return "The entry with the ID " . $id . " has been deleted. Shortcut: " . $ret[0][0] . ", long description: " . $ret[0][1] . ".";
    }
}

?>
