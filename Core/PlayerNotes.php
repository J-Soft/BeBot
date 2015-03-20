<?php
/*
* PlayerNotes.php - Player Notes Module.
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
$playernotes_core = new PlayerNotes_Core($bot);

/*
The Class itself...
*/

class PlayerNotes_Core extends BasePassiveModule
{ // Start Class
    var $schema_version;


    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->bot->db->query(
          "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("player_notes", "false") . "
			(pnid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				player VARCHAR(30) NOT NULL,
				author VARCHAR(30) NOT NULL,
				note VARCHAR(255) NOT NULL,
				class TINYINT NOT NULL DEFAULT 0,
				timestamp INT UNSIGNED NOT NULL)"
        );
        $this->register_module("player_notes");
        $this->update_schema();
    }


    /*
    Adds a player note.
    */

    function update_schema()
    {
        if ($this->bot->core("settings")
          ->exists('Playernotes', 'Schema_version')
        ) {
            $this->bot->db->set_version(
              "player_notes", $this->bot
              ->core("settings")->get('Playernotes', 'Schema_version')
            );
            $this->bot->core("settings")->del('Playernotes', 'Schema_version');
        }
        switch ($this->bot->db->get_version('player_notes')) {
            case 1:
                // Rename timestmp column to timestamp.
                $this->bot->db->update_table(
                  "player_notes", array(
                  "timestmp",
                  "timestamp"
                ), "change", "ALTER TABLE #___player_notes CHANGE timestmp timestamp INTEGER"
                );
            case 2:
                // Change player column to VARCHAR(30) NOT NULL
                $this->bot->db->update_table("player_notes", "player", "alter",
                  "ALTER TABLE #___player_notes CHANGE player player VARCHAR(30) NOT NULL");
        }
        $this->bot->db->set_version('player_notes', 3);
    } // End function add()

    /*
    Deletes player notes.
    */

function add($player, $author, $note, $class)
    { // Start function add()
        $author = ucfirst(strtolower($author));
        $player = ucfirst(strtolower($player));
        $class = strtolower($class);
        if (!is_numeric($class)) {
            switch ($class) {
                case "admin":
                    $class = 2;
                    break;
                case "ban":
                    $class = 1;
                    break;
                default:
                    $class = 0;
            }
        }
        if ($class > 3) {
            $class = 3; // Currently only 3 classes are defined.
        }
        if (strlen($note) > 255) {
            $note = substr($note, 0, 254);
        }
        $note = mysql_real_escape_string($note);
        $author = mysql_real_escape_string($author);
        $player = mysql_real_escape_string($player);
        $sql = "INSERT INTO #___player_notes (player, author, note, class, timestamp) ";
        $sql .= "VALUES ('$player', '$author', '$note', $class, " . time() . ")";
        $result = $this->bot->db->query($sql);
        if ($result !== false) {
            $sql = "SELECT pnid FROM #___player_notes WHERE player = '" . $player . "' ORDER BY pnid DESC LIMIT 1";
            $result = $this->bot->db->select($sql);
            $return["pnid"] = $result[0][0];
            return ("Successfully added &quot;" . $note . "&quot; note to " . $player . " as note id " . $return["pnid"]);
        } else {
            $this->error->set("An unknown error occurred. Check your bot console for more information.");
            return ($this->error);
        }
    } // End function del()

    /*
    Updates player notes.
    */

function del($pnid)
    { // Start function del()
        $sql = "DELETE FROM #___player_notes WHERE pnid = " . $pnid;
        $result = $this->bot->db->returnQuery($sql);
        if ($result) {
            return ("Deleted player note $pnid");
        } else {
            $this->error->set("Could not delete player note " . $pnid . ". No note with that ID could be found.");
            return ($this->error);
        }
    } // End function update()

    /*
    Retrives player notes.
    $order can be ASC (ascending) or DESC (descending).
    */

function update($pnid, $what, $newvalue)
    { // Start function update()
        if (!is_int($pnid)) {
            $this->error->set("Only integers can be player note ID numbers.");
            return ($this->error);
        }
        $what = mysql_real_escape_string($what);
        $newvalue = mysql_real_escape_string($newvalue);
        $sql = "UPDATE #___player_notes SET " . $what . " = " . $newvalue . " WHERE pnid = " . $pnid;
        if (!$this->bot->db->query($sql)) {
            $this->error->set("There was a MySQL error when updating '$what' to '$newvalue'.");
            return ($this->error);
        }
    } // End function get_notes()

    /*
    Updates the player_notes table schema.
    */

function get_notes($name, $player = "All", $pnid = "all", $order = "ASC")
    { // Start function get_notes()
        $name = ucfirst(strtolower($name)); // Name of person requesting notes.
        $player = ucfirst(strtolower($player)); // Notes attached to this player.
        $sql = "SELECT * FROM #___player_notes";
        $where = "WHERE";
        if ($player != "All") {
            $sql .= " " . $where . " player = '" . $player . "'";
            $where = "AND";
        }
        $leader = $this->bot->core("security")->check_access($name, "LEADER");
        if (!$leader) // Only show general notes to non leaders.
        {
            $sql .= " " . $where . " class = 0";
            $where = "AND";
        }
        if (strtolower($pnid) != "all" and is_numeric($pnid)) {
            $sql .= " " . $where . " pnid = " . $pnid;
        }
        $sql .= " ORDER BY pnid " . $order;
        $result = $this->bot->db->select($sql, MYSQL_ASSOC);
        if (empty($result)) {
            $this->error->set("No notes found for '$player'", false);
            return ($this->error);
        }
        return $result;
    }
} // End of Class
?>
