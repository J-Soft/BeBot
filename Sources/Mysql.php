<?php
/*
* Mysql.php - MySQL interaction
*
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
class MySQL
{
    var $CONN = "";
    var $DBASE = "";
    var $USER = "";
    var $PASS = "";
    var $SERVER = "";
    var $bot;
    public static $instance;


    public static function get_instance($bothandle)
    {
        $bot = Bot::get_instance($bothandle);
        if (!isset(self::$instance[$bothandle])) {
            $class = __CLASS__;
            self::$instance[$bothandle] = new $class($bothandle);
        }
        return self::$instance[$bothandle];
    }


    private function __construct($bothandle)
    {
        $this->bot = Bot::get_instance($bothandle);
        $this->botname = $this->bot->botname;
        $this->error_count = 0;
        $this->last_error = 0;
        $this->last_reconnect = 0;
        $this->underscore = "_";
        $nounderscore = false;
        /*
        Load up config
        */
        $botname_mysql_conf = "Conf/" . $this->botname . ".Mysql.conf";
        if (file_exists($botname_mysql_conf)) {
            include $botname_mysql_conf;
            $this->bot->log("MYSQL", "START", "Loaded MySQL configuration from " . $botname_mysql_conf, false);
        } else {
            include "Conf/Mysql.conf";
            $this->bot->log("MYSQL", "START", "Loaded MySQL configuration from Conf/Mysql.conf", false);
        }
        $this->USER = $user;
        $this->PASS = $pass;
        $this->SERVER = $server;
        $this->DBASE = $dbase;
        if (empty($master_tablename)) {
            $this->master_tablename = strtolower($this->botname) . "_tablenames";
        } else {
            $master_tablename = str_ireplace("<botname>", strtolower($this->botname), $master_tablename);
            $this->master_tablename = $master_tablename;
        }
        if (!isset($table_prefix)) {
            $this->table_prefix = strtolower($this->botname);
        } else {
            $table_prefix = str_ireplace("<botname>", strtolower($this->botname), $table_prefix);
            $this->table_prefix = $table_prefix;
        }
        if ($nounderscore) {
            $this->underscore = "";
        }
        $this->connect(true);
        /*
        Make sure we have the master table for tablenames that the bot cannot function without.
        */
        $this->query(
            "CREATE TABLE IF NOT EXISTS " . $this->master_tablename
            . "(internal_name VARCHAR(255) NOT NULL PRIMARY KEY, prefix VARCHAR(100), use_prefix VARCHAR(10) NOT NULL DEFAULT 'false', schemaversion INT(3) NOT NULL DEFAULT 1)"
        );
        $this->query(
            "CREATE TABLE IF NOT EXISTS table_versions (internal_name VARCHAR(255) NOT NULL PRIMARY KEY, schemaversion INT(3) NOT NULL DEFAULT 1)"
        );
        $this->update_master_table();
        return true;
    }


    function update_master_table()
    {
        $columns = array_flip(
            array(
                 "internal_name",
                 "prefix",
                 "use_prefix",
                 "schemaversion"
            )
        );
        $fields = $this->select("EXPLAIN " . $this->master_tablename, MYSQLI_ASSOC);
        if (!empty($fields)) {
            foreach ($fields as $field) {
                unset($columns[$field['Field']]);
            }
        }
        if (!empty($columns)) {
            foreach ($columns as $column => $temp) {
                switch ($column) {
                    case 'schemaversion':
                        $this->query(
                            "ALTER TABLE " . $this->master_tablename . " ADD COLUMN schemaversion INT(3) NOT NULL DEFAULT 1"
                        );
                        break;
                }
            }
        }
    }


    function connect($initial = false)
    {
        if ($initial) {
            $this->bot->log("MYSQL", "START", "Establishing MySQL database connection....");
        }
        $conn = @($GLOBALS["___mysqli_ston"] = mysqli_connect($this->SERVER, $this->USER, $this->PASS));
        if (!$conn) {
            $this->error("Cannot connect to the database server!", $initial, false);
            return false;
        }
        if (!((bool)mysqli_query($conn, "USE $this->DBASE"))) {
            $this->error("Database not found or insufficient priviledges!", $initial, false);
            return false;
        }
        if ($initial == true) {
            $this->bot->log("MYSQL", "START", "MySQL database connection test successfull.");
        }
        $this->CONN = $conn;
    }


    function close()
    {
        if ($this->CONN != null) {
            mysqli_close($this->CONN);
            $this->CONN = null;
        }
    }

    function real_escape_string($string)
    {
        return mysqli_real_escape_string($this->CONN, (string)$string);
    }

    function error($text, $fatal = false, $connected = true)
    {
        $msg = ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error(
            $GLOBALS["___mysqli_ston"]
        ) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
        $this->error_count++;
        $this->bot->log("MySQL", "ERROR", "(# " . $this->error_count . ") on query: $text", $connected);
        $this->bot->log("MySQL", "ERROR", $msg, $connected);
        // If this error is occuring while we are trying to first connect to the database when starting
        // rthe bot its a fatal error.
        if ($fatal == true) {
            $this->bot->log("MySQL", "ERROR", "A fatal database error has occurred. Shutting down.", $connected);
            exit();
        }
    }


    function select($sql, $result_form = MYSQLI_NUM)
    {
        $this->connect();
        $data = array();
        $sql = $this->add_prefix($sql);
        $result = mysqli_query($this->CONN, $sql);
        if (!$result) {
            $this->error($sql);
            return false;
        }
        if (empty($result)) {
            return false;
        }
        while ($row = mysqli_fetch_array($result, $result_form)) {
            $data[] = $row;
        }
        ((mysqli_free_result($result) || (is_object($result) && (get_class(
                        $result
                    ) == "mysqli_result"))) ? true : false);
        return $data;
    }


    function query($sql)
    {
        $this->connect();
        $sql = $this->add_prefix($sql);
        $return = mysqli_query($this->CONN, $sql);
        if (!$return) {
            $this->error($sql);
            return false;
        } else {
            return true;
        }
    }


    function returnQuery($sql)
    {
        $this->connect();
        $sql = $this->add_prefix($sql);
        $result = mysqli_query($this->CONN, $sql);
        if (!$result) {
            return false;
        } else {
            return $result;
        }
    }


    function dropTable($sql)
    {
        $this->connect();
        $sql = $this->add_prefix($sql);
        $result = mysqli_query($this->CONN, "DROP TABLE " . $sql);
        if (!$result) {
            $this->error($sql);
            return false;
        } else {
            return true;
        }
    }


    function add_prefix($sql)
    {
        $pattern = '/\w?(#___.+?)\b/';
        return preg_replace_callback(
            $pattern,
            array(
                 &$this,
                 'strip_prefix_control'
            ),
            $sql
        );
    }


    function strip_prefix_control($matches)
    {
        $tablename = $this->get_tablename(substr($matches[1], 4));
        return $tablename;
    }


    /*
    Returns a table name, adding prefix.
    Creates a default name of $prefix_$table and adds this to the database if the tablename doesn't exist yet.
    For speed purposes names are cached after the first query - tablenames don't change during runtime.
    */
    function get_tablename($table)
    {
        // get name out of cached entries if possible:
        if (isset($this->tablenames[$table])) {
            return $this->tablenames[$table];
        }
        // check the database for the name, default prefix and default suffix:
        $name = $this->select("SELECT * FROM " . $this->master_tablename . " WHERE internal_name = '" . $table . "'");
        if (empty($name)) {
            // no entry existing, create one:
            if (empty($this->table_prefix)) {
                $tablename = $table;
            } else {
                $tablename = $this->table_prefix . $this->underscore . $table;
            }
            $this->query(
                "INSERT INTO " . $this->master_tablename . " (internal_name, prefix, use_prefix) VALUES ('" . $table . "', '" . $this->table_prefix . "', 'true')"
            );
        } else {
            // entry exists, create the correct tablename:
            if ($name[0][2] == 'true' && !empty($this->table_prefix)) {
                $tablename = $name[0][1] . $this->underscore . $table;
            } else {
                $tablename = $table;
            }
        }
        // cache the entry and return it:
        $this->tablenames[$table] = $tablename;
        return $tablename;
    }


    /*
    Used for first defines of tablenames, allows to set if prefix should be used.
    If the tablename already exists, the existing name is returned - NO NAMES ARE REDEFINED!
    Otherwise same as get_tablename()
    */
    function define_tablename($table, $use_prefix)
    {
        // get name out of cached entries if possible:
        if (isset($this->tablenames[$table])) {
            return $this->tablenames[$table];
        }
        // check the database for the name, default prefix and default suffix:
        $name = $this->select("SELECT * FROM " . $this->master_tablename . " WHERE internal_name = '" . $table . "'");
        if (empty($name)) {
            // no entry existing, create one:
            $tablename = '';
            $prefix = '';
            if (((strtolower($use_prefix) == 'true') || ($use_prefix === true)) && !empty($this->table_prefix)) {
                $prefix = $this->table_prefix;
                $tablename = $prefix . $this->underscore . $table;
                $use_prefix = 'true';
            } else {
                $tablename = $table;
                $use_prefix = 'false';
            }
            $this->query(
                "INSERT INTO " . $this->master_tablename . " (internal_name, prefix, use_prefix) VALUES ('" . $table . "', '" . $prefix . "', '" . $use_prefix . "')"
            );
        } else {
            // entry exists, create the correct tablename:
            if ($name[0][2] == 'true' && !empty($this->table_prefix)) {
                $tablename = $name[0][1] . $this->underscore . $table;
            } else {
                $tablename = $table;
            }
        }
        // cache the entry and return it:
        $this->tablenames[$table] = $tablename;
        return $tablename;
    }


    function get_version($table)
    {
        $version = $this->select(
            "SELECT schemaversion, use_prefix FROM " . $this->master_tablename . " WHERE internal_name = '" . $table . "'"
        );
        if (!empty($version)) {
            if ($version[0][1] == "false") {
                $version2 = $this->select(
                    "SELECT schemaversion FROM table_versions WHERE internal_name = '" . $table . "'"
                );
                if (!empty($version2)) {
                    Return ($version2[0][0]);
                }
            }
            Return ($version[0][0]);
        } else {
            Return (1);
        }
    }


    function set_version($table, $version)
    {
        if (!is_numeric($version)) {
            echo "DB Error Trying to set version: " . $version . " for table " . $table . "!\n";
            //$this->bot->log("DB", "ERROR", "Trying to set version: " . $version . " for table " . $table . "!");
        } else {
            $this->query(
                "UPDATE " . $this->master_tablename . " SET schemaversion = " . $version . " WHERE internal_name = '" . $table . "'"
            );
            $usep = $this->select(
                "SELECT use_prefix FROM " . $this->master_tablename . " WHERE internal_name = '" . $table . "'"
            );
            if ($usep[0][0] == "false") {
                $this->query(
                    "INSERT INTO table_versions (internal_name, schemaversion) VALUES ('" . $table . "', " . $version
                    . ") ON DUPLICATE KEY UPDATE schemaversion = VALUES(schemaversion)"
                );
            }
        }
    }


    function update_table($table, $column, $action, $query)
    {
        $fields = $this->select("EXPLAIN #___" . $table, MYSQLI_ASSOC);
        if (!empty($fields)) {
            foreach ($fields as $field) {
                $columns[$field['Field']] = true;
            }
        }
        Switch (strtolower($action)) {
            case 'add': // make sure it doesnt exist
                $do = true;
                if (is_array($column)) {
                    foreach ($column as $c) {
                        if (isset($columns[$c])) {
                            $do = false;
                        }
                    }
                } else {
                    if (isset($columns[$column])) {
                        $do = false;
                    }
                }
                if ($do) {
                    $this->query($query);
                }
                Break;
            case 'drop': // Make sure it does exist
            case 'alter':
            case 'modify':
                $do = true;
                if (is_array($column)) {
                    foreach ($column as $c) {
                        if (!isset($columns[$c])) {
                            $do = false;
                        }
                    }
                } else {
                    if (!isset($columns[$column])) {
                        $do = false;
                    }
                }
                if ($do) {
                    $this->query($query);
                }
                Break;
            case 'change':
                if (isset($columns[$column[0]]) && !isset($columns[$column[1]])) {
                    $this->query($query);
                }
                Break;
            Default:
                echo "Unknown MYSQL UPDATE Action '" . $action . "'";
                $this->query($query);
        }
    }
}

?>
