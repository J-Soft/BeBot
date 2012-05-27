<?php
/*
* Settings_Core.php - Settings Management Functions.
* Version: 1.5
* Created by Andrew Zbikowski <andyzib@gmail.com> (AKA Glarawyn, RK1)
* Core module for saving, retriving, and modifying settings in a standard way.
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
Main Functions:

All main functions return an array with 'error' and 'errordesc' elements.
If the 'error' element is TRUE, an error occurred. The 'errordesc'
contains the description of the error.

get($module, $setting)
Gets a single setting from the database, returns an array.
This should only be used if you want to manage settings internally.
get_all($module)
Gets all the settings for a module, returns an array.
This should only be used if you want to manage settings internally.
load_all()
Loads all settings into the global settings array.
save($module, $setting, $value, $noupdate=FALSE)
Saves a setting to the database and updates the global settings array.
create ($module, $setting, $value, $longdesc, $hidden, $disporder)
Creates a new setting, updates the global settings array.
get_data_type($value)
Returns the data type of $value.
set_data_type($value, $datatype)
Changes the data type of $value to $datatype.
check_data($module, $setting, $value, $longdesc, $defaultoptions)
Used to check data before saving to database.
del($module, $setting)
Removes a setting or setting group.
Support Functions:

Support functions help the main functions.

get_data_type($value)
Returns the datatype of $value as a string.
(bool, int, float, string, array)
set_data_type($value, $datatype)
Sets $value to be the specified datatype.
remove_space($string)
Converts spaces to underscores. Returns the modified string.
array2string($array)
Converts an array to a string. Array elements will be seperated by a
semicolon in the returned string.
string2array($string)
Converts a string to an array. Array elements should be seperated by a
semicolon. Returns an array.
*/
$settings = new Settings_Core($bot);
/*
The Class itself...
*/
class Settings_Core extends BasePassiveModule
{ // Start Class
    private $settings_cache;
    private $callbacks;
    private $change_user;


    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("settings", "true") . "
		              (module varchar(25) NOT NULL,
		               setting varchar(50) NOT NULL,
		               value varchar(255) NOT NULL,
		               datatype varchar(25) DEFAULT NULL,
		               longdesc varchar(255) DEFAULT NULL,
		               defaultoptions varchar(255) DEFAULT NULL,
		               hidden BOOLEAN DEFAULT 0,
		               disporder INT UNSIGNED NOT NULL DEFAULT 1,
		               PRIMARY KEY (module, setting))"
        );
        $this->register_module("settings");
        $this->register_event("connect");
        $this->register_event("cron", "1hour");
        $this->create("Settings", "Log", TRUE, "Should changes to settings and the loading of the settings be shown in the log? Errors are always logged!");
        $this->update_settings_table();
        $this->load_all();
        $this->callbacks = array();
        $this->change_user = "";
        if ($this->exists("Maintenance", "info") && $this->get("Maintenance", "info") != "") {
            $this->maintenance = TRUE;
            $this->create("Settings", "Log", TRUE, "Should changes to settings and the loading of the settings be shown in the log? Errors are always logged!");
        }
        else {
            $this->maintenance = FALSE;
        }
    }


    /*
    Periodically reload settings form the database.
    This should ensure that people aren't tempted to modify the
    global settings array directly.
    */
    function cron()
    { // Start function cron()
        $this->load_all();
    } // End function cron()

    function connect()
    {
        $this->load_all();
    }


    /*
    Checks if a settings is in the cache.
    */
    function exists($module, $setting)
    {
        return isset($this->settings_cache[strtolower($module)][strtolower($setting)]);
    }


    /*
    Retrives a setting from the database.
    */
    function get($module, $setting)
    { // Start function get()
        $module = strtolower($module);
        $setting = strtolower($setting);
        if (isset($this->settings_cache[$module][$setting])) {
            return $this->settings_cache[$module][$setting];
        }
        else {
            $this->error->set("The setting named " . $setting . " for setting group " . $module . " does not exisit.");
            return $this->error;
        }
    } // End function get()

    /*
    Retrives all of a modules settings from the database.
    */
    function get_all($module)
    { // Start function get_all()
        $module = strtolower($module);
        if (!empty($this->settings_cache[$module])) {
            return $this->settings_cache[$module];
        }
        else {
            $this->error->set("No settings exisit for group " . $module . ".");
            return $this->error;
        }
    } // End function get_all()

    /*
    Loads all of the settings from the database into the global settings array.
    */
    function load_all()
    { // Start function load_all()
        $sql = "SELECT module,setting,value,datatype FROM #___settings";
        $result = $this->bot->db->select($sql);
        if (empty($result)) {
            $this->settings_cache = array();
            $this->error->set("No settings loaded from database. It's possible that no settings exisit.");
            return $this->error;
        }
        else {
            foreach ($result as $data) {
                $module = strtolower($data[0]);
                $setting = strtolower($data[1]);
                $value = $data[2];
                $datatype = $data[3];
                if ($datatype == "array") {
                    $value = $this->string2array($value);
                }
                else {
                    $value = $this->set_data_type($value, $datatype);
                }
                $this->settings_cache[$module][$setting] = $value;
            }
            if ($this->get("Settings", "Log")) {
                $this->bot->log("SETTINGS", "LOAD", "Loaded settings from database.");
            }
        }
    } // End function load_all()

    /*
    Saves a setting to the database and updates the settings array.
    */
    function save($module, $setting, $value, $noupdate = FALSE)
    { // Start function save()
        // Remove Spaces
        $module = $this->remove_space($module);
        $setting = trim($setting);
        $setting = $this->remove_space($setting);
        // Make sure the setting exists already, otherwise fail.
        if (!isset($this->settings_cache[strtolower($module)][strtolower($setting)])) {
            if (!is_null($this->settings_cache[strtolower($module)][strtolower($setting)])) {
                $this->error->set("Setting " . $setting . " for module " . $module . " could not be saved. " . $setting . " does not exist.");
                return $this->error;
            }
        }
        // Figure out what type of data we have.
        $datatype = $this->get_data_type($value);
        if ($datatype == "unknown") {
            $this->error->set("Setting " . $setting . " for module " . $module . " could not be saved. Datatype is unknown or not supported.");
            return $this->error;
        }
        elseif ($datatype == "array") {
            $value = implode(";", $value);
        }
        elseif ($datatype == "bool") {
            if ($value) {
                $value = "TRUE";
            }
            else {
                $value = "FALSE";
            }
        }
        elseif ($datatype == "null" || strtolower($value) == "null") {
            $value = "null";
            $datatype = "null";
        }
        $status = $this->check_data($module, $setting, $value);
        if ($status instanceof BotError) {
            return $status;
        }
        // Change $value to a string and add escape slashes if needed.
        $module_sql = mysql_real_escape_string(strval($module));
        $setting_sql = mysql_real_escape_string(strval($setting));
        $value_sql = mysql_real_escape_string(strval($value));
        if ($noupdate) {
            $sql
                =
                "INSERT IGNORE INTO #___settings (module, setting, value, datatype) VALUES ('" . $module_sql . "','" . $setting_sql . "','" . $value_sql . "','" . $datatype . "')";
        }
        else {
            // $dupkey = "ON DUPLICATE KEY UPDATE value = '".$value_sql."', datatype = '".$datatype."'";
            // $sql = "INSERT ".$into.$values.$dupkey;
            $sql = "UPDATE #___settings SET value = '" . $value_sql . "',datatype = '" . $datatype . "' WHERE module = '" . $module_sql . "' AND setting ='" . $setting_sql . "'";
        }
        $result = $this->bot->db->returnQuery($sql);
        if ($result) {
            if ($this->get("Settings", "Log")) {
                $this->bot->log("SETTINGS", "SAVED", $setting . " for module " . $module . " set to " . $value . " as datatype " . $datatype);
            }
            $oldvalue = $this->settings_cache[strtolower($module)][strtolower($setting)];
            $this->settings_cache[strtolower($module)][strtolower($setting)] = $this->set_data_type($value, $datatype);
            if (isset($this->callbacks[strtolower($module)][strtolower($setting)])) {
                foreach (
                    $this->callbacks[strtolower($module)][strtolower($setting)]
                    as $mods
                ) {
                    if ($mods != NULL) {
                        $mods->settings($this->change_user, strtolower($module), strtolower($setting), $this->settings_cache[strtolower($module)][strtolower($setting)], $oldvalue);
                    }
                }
            }
        }
        else {
            $this->error->set("Could not save setting " . stripslashes($setting) . " for module " . stripslashes($module) . " to database. SQL: " . $sql);
            $this->change_user = "";
            return $this->error;
        }
        // Unset change_user, this change is done:
        $this->change_user = "";
        return $status;
    } // End function save()

    /*
    Creates a new setting in the database.
    TODO: This should most likely be split in smaller functions
    Also we're suffering from a slight case of inner-platform effect.
    */
    function create(
        $module, $setting, $value, $longdesc, $defaultoptions = "",
        $hidden = FALSE, $disporder = 1
    )
    { // Start function create()
        // FIXME: Maybe check to see if the setting exists before going farther?
        $sql = "SELECT";
        // Remove whitespace from $module, $setting, and $value
        $module = $this->remove_space($module);
        $setting = $this->remove_space($setting);
        $datatype = $this->get_data_type($value);
        // Convert $value to a string.
        if ($datatype == "bool") {
            $defaultoptions = "On;Off"; // Default options for bool should always be On;Off.
            if ($value) {
                $value = "TRUE";
            }
            else {
                $value = "FALSE";
            }
        }
        elseif ($datatype == "null") {
            $value = "null";
        }
        elseif (is_array($value)) {
            $value = $this->array2string($value);
            $hidden = TRUE; // I am not dealing with arrays in the settings menu. :D
        }
        else {
            $value = strval($value);
        }
        if (!is_numeric($disporder)) {
            $disporder = 1;
        }
        else {
            $disporder = intval($disporder);
        }
        // Check for anything that will prevent the setting from being saved to the database.
        $status = $this->check_data($module, $setting, $value, $longdesc, $defaultoptions);
        if ($status instanceof BotError) {
            return $status;
        }
        // Not really needed, but doing it anyway.
        if ($hidden) {
            $hidden = 1;
        }
        else {
            $hidden = 0;
        }
        // Make sure any strings are escaped for MySQL.
        $module = mysql_real_escape_string($module);
        $setting = mysql_real_escape_string($setting);
        $valuesql = mysql_real_escape_string($value);
        $longdesc = mysql_real_escape_string($longdesc);
        $defaultoptions = mysql_real_escape_string($defaultoptions);
        $sql = "SELECT longdesc, defaultoptions, hidden, disporder FROM #___settings WHERE module = '" . $module . "' AND setting = '" . $setting . "'";
        $check = $this->bot->db->select($sql);
        if (!empty($check)) {
            $check = $check[0];
            if ($check[0] != stripslashes($longdesc) || $check[1] != stripslashes($defaultoptions) || $check[2] != stripslashes($hidden) || $check[3] != stripslashes($disporder)) {
                $sql = "UPDATE #___settings SET longdesc = '" . $longdesc . "', defaultoptions = '" . $defaultoptions;
                $sql .= "', hidden = " . $hidden . ", disporder = " . $disporder . " WHERE module = '" . $module . "' AND setting = '" . $setting . "'";
                $this->bot->db->query($sql);
                if ($this->get("Settings", "Log")) {
                    $this->bot->log("SETTINGS", "UPDATED", "Updated values for " . stripslashes($setting) . " for module " . stripslashes($module));
                }
            }
        }
        else {
            $sql = "INSERT INTO #___settings (module, setting, value, datatype, longdesc, defaultoptions, hidden, disporder) ";
            $sql .= "VALUES ('" . $module . "', '" . $setting . "', '" . $valuesql . "', '" . $datatype . "', '" . $longdesc . "', '" . $defaultoptions;
            $sql .= "', " . $hidden . ", " . $disporder . ") ON DUPLICATE KEY UPDATE longdesc = VALUES(longdesc), ";
            $sql .= "defaultoptions = VALUES(defaultoptions), hidden = VALUES(hidden), disporder = VALUES(disporder)";
            $result = $this->bot->db->returnQuery($sql);
            if (!$result) {
                $this->error->set("Setting " . $setting . " for module " . $module . " was not created because it already exists.");
                if (isset($this->maintenance)) {
                    $this->bot->core("maintenance")->new_data[strtolower(stripslashes($module))][strtolower(stripslashes($setting))] = array(
                        $datatype,
                        stripslashes($longdesc),
                        stripslashes($defaultoptions),
                        $hidden,
                        $disporder
                    );
                }
                return $this->error;
                // $this -> bot -> log("SETTINGS", "WARNING", $status['errordesc']); // FIXME: Comment this when done debugging.
            }
            else {
                $this->settings_cache[strtolower($module)][strtolower($setting)] = $this->set_data_type($value, $datatype);
                if ($this->get("Settings", "Log")) {
                    $this->bot->log("SETTINGS", "Created", "Created " . stripslashes($setting) . " for module " . stripslashes($module) . " with value of " . $value);
                }
            }
        }
        if (isset($this->maintenance)) {
            $this->bot->core("maintenance")->new_data[strtolower(stripslashes($module))][strtolower(stripslashes($setting))] = array(
                $datatype,
                stripslashes($longdesc),
                stripslashes($defaultoptions),
                $hidden,
                $disporder
            );
        }
        return $status;
    } // End function create()

    /*
    Updates description, default options, hidden, or display order for a setting.
    $this -> bot -> core("settings") -> update("Settings", "Log", "descritpion", "Toggles logging of setting actions.");
    $module and $setting define which setting to update.
    $what Is the property that is being updated: longdesc, defaultoptions, hidden, or disporder.
    $to is the new value for $what.
    */
    function update($module, $setting, $what, $to)
    { // Start function update()
        $this->bot->log(
            "SETTINGS", "Notice:",
            "Please note: update() is discontinued and may be removed in the future. create() does updates if changes to the definition of a setting is needed. Called for setting $module $setting"
        );
        $module = $this->remove_space($module);
        $setting = $this->remove_space($setting);
        $what = mysql_real_escape_string($what);
        $to = mysql_real_escape_string($to);
        $what = strtolower($what);
        $to = strtolower($to);
        $sql = "UPDATE #___settings SET " . $what . " = '" . $to . "' WHERE module = '" . $module . "' AND setting = '" . $setting . "'";
        $this->bot->db->query($sql);
        if ($this->get("Settings", "Log")) {
            $this->bot->log(
                "SETTINGS", "UPDATE", "Set " . stripslashes($what) . " for setting [" . stripslashes($module) . "][" . stripslashes($setting) . "] to " . stripslashes($to)
            );
        }
    } // End function update()

    /*
    Deletes a setting or group of settings from the database.
    */
    function del($module, $setting = NULL)
    { // Start function del()
        // Remove whitespace from $module, $setting, and $value
        $module = $this->remove_space($module);
        $setting = $this->remove_space($setting);
        if (is_null($setting)) // Delete a setting group.
        {
            if (!isset($this->settings_cache[strtolower($module)])) {
                $this->error->set("Setting " . $setting . " for module " . $module . " does not exisit.");
                return $this->error;
            }
            else {
                $sql = "DELETE FROM #___settings WHERE module = '" . mysql_real_escape_string($module) . "'";
                $this->bot->db->query($sql);
                unset($this->settings_cache[strtolower($module)]);
                return "Deleted settings for $module.";
            }
        }
        else {
            if (!isset($this->settings_cache[strtolower($module)][strtolower($setting)])) {
                $this->error->set("Setting " . $setting . " for module " . $module . " does not exisit.");
                return $this->error;
            }
            else {
                unset($this->settings_cache[strtolower($module)][strtolower($setting)]);
                $sql = "DELETE FROM #___settings WHERE module = '" . mysql_real_escape_string($module) . "' AND setting = '" . mysql_real_escape_string($setting) . "'";
                $this->bot->db->query($sql);
                return "Deleted setting '$setting' from '$module'.";
            }
        }
    } // End function del()

    /*
    Make sure information is OK to save.
    For now, just checks length, but other checks can be added.
    */
    function check_data(
        $module, $setting, $value, $longdesc = NULL,
        $defaultoptions = NULL
    )
    { // Start function check_data()
        // Pack the data into an array so we can easily itterate through it.
        $check[0] = array(
            "module",
            $module
        );
        $check[1] = array(
            "setting",
            $setting
        );
        $check[2] = array(
            "value",
            $value
        );
        $check[3] = array(
            "longdesc",
            $longdesc
        );
        $check[4] = array(
            "defaultoptions",
            $defaultoptions
        );
        foreach ($check as $data) {
            if (strlen(strval($data[1])) > 255) // Must be 255 or less characters.
            {
                $this->error->set($data[0] . " is longer than 255 characters.");
                return $this->error;
            }
        }
        return TRUE; // No errors found if we get this far.
    } // End function check_data()

    /*
    Retunrns the datatype of the value passed.
    */
    function get_data_type($value)
    { // Start function get_data_type()
        if (is_bool($value)) {
            return "bool";
        }
        elseif (is_null($value)) {
            return "null";
        }
        elseif (is_int($value)) {
            return "int";
        }
        elseif (is_float($value)) {
            return "float";
        }
        elseif (is_string($value)) {
            return "string";
        }
        elseif (is_array($value)) {
            return "array";
        }
        else {
            return "unknown";
        }
    } // End function get_data_dype()

    function set_data_type($value, $datatype)
    { // Start function set_data_type()
        $datatype = strtolower($datatype);
        /*
        bool, float, int, and null need to be converted from strings to
        the proper datatypes. Strings are already in string form and will
        use the default case, any unrecognized datatype will be returned
        as a string.
        */
        switch ($datatype) { // Start switch ($datatype)
        case "bool":
            if ($value == "TRUE") {
                $value = TRUE;
            }
            else {
                $value = FALSE;
            }
            break;
        case "null":
            $value = NULL;
            break;
        case "float":
            $value = floatval($value);
            break;
        case "int":
            $value = intval($value);
            break;
        case "array":
            $value = explode(";", $value);
            break;
        default:
            $value = strval($value);
            break;
        } // End switch ($datatype)
        return $value; // Return typed data.
    } // End function set_data_type()

    /*
    Updates the settings table to lastest version.
    */
    function update_settings_table()
    {
        $schemaversion = $this->bot->db->get_version("settings");
        Switch ($schemaversion) {
        case 1:
            $sql = "ALTER IGNORE TABLE #___settings ADD COLUMN disporder INT UNSIGNED NOT NULL DEFAULT 1";
            $this->bot->log("SETTINGS", "UPDATE", "Settings Table updated to schema version 2");
            $this->bot->db->update_table("settings", "disporder", "add", $sql);
        }
        $this->bot->db->set_version("settings", 2);
        /*$done = FALSE;
       while ($done == FALSE):
           // Upgrade from Schema 1.0 to 1.5.
           $sql = "SELECT value FROM #___settings WHERE module = 'Settings' AND setting = 'SchemaVersion'";
           $result = $this -> bot -> db -> select($sql);
           if (empty($result))
           {
               $sql = "SELECT value FROM #___settings WHERE module = 'Settings' AND setting = 'Schemaversion'";
               $result = $this -> bot -> db -> select($sql);
           }

           if (empty($result)) // If still empty, table may not exisit.
           {
               $this -> bot -> log("SETTINGS", "ERROR", "Could not determine version of the settings table. Quitting.");
               die("The bot has been shutdown");
           }

           if ($result[0][0] == 1)
           {
               $sql = "ALTER IGNORE TABLE #___settings ADD COLUMN disporder INT UNSIGNED NOT NULL DEFAULT 1";
               $this -> bot -> log("SETTINGS", "UPDATE", "Settings Table updated to schema version 1.5");
               $this -> bot -> db -> query($sql);
               $this -> save("Settings", "Schemaversion", 1.5);
               //$sql = "UPDATE #___settings SET value = 1.5 WHERE module = 'Settings' and setting = 'Schemaversion'";
               //$this -> bot -> db -> query($sql);
           }
           elseif ($result[0][0] == 1.5) // Upgrade from Schema 1.5 to 2
           {
               //// Fix module names
               //$sql = "SELECT DISTINCT module FROM #___settings";
               //$res = $this -> bot -> db -> select($sql);
               //foreach ($res as $val)
               //{
               //	$sql = "UPDATE #___settings SET module = '".ucfirst(strtolower($val[0]))."' WHERE module ='".$val[0]."'";
               //	$this -> bot -> db -> query($sql);
               //}
               //// Fix setting names
               //$sql = "SELECT DISTINCT module,setting FROM #___settings";
               //$res = $this -> bot -> db -> select($sql);
               //foreach ($res as $val)
               //{
               //	$sql = "UPDATE #___settings SET setting = '".ucfirst(strtolower($val[1]))."' ";
               //	$sql .= "WHERE module = '".$val[0]."' AND setting ='".$val[1]."'";
               //	$this -> bot -> db -> query($sql);
               //}
               $this -> save("Settings", "Schemaversion", 2.0);
               //$sql = "UPDATE #___settings SET value = 2.0 WHERE module = 'Settings' and setting = 'Schemaversion'";
               //$this -> bot -> db -> query($sql);
               $this -> bot -> log("SETTINGS", "UPDATE", "Settings Table updated to schema version 2.0");
           }
           elseif ($result[0][0] == 2.0)
           {
               $done = TRUE;
               return FALSE; // Nothing to do.
           }
       endwhile; */
    }


    /*
    The following functions aren't really needed,
    but they make debugging and format changes easier.
    */
    /*
    Removes spaces from a string
    */
    function remove_space($string)
    { // Start function remove_space()
        return str_replace(" ", "_", $string);
    } // End function remove_space()

    /*
    Converts an array to a string.
    Array elements are seperated with a semicolon.
    */
    function array2string($array)
    { // Start function array2string()
        return implode(";", $array);
    } // End function array2string()

    /*
    Converts a string to an array.
    */
    function string2array($string)
    { // Start function string2array()
        return explode(";", $string);
    } // End function string2array()

    function set_change_user($name)
    {
        $this->change_user = $name;
    }


    function register_callback($module, $setting, &$reg_module)
    {
        $module_name = get_class($reg_module);
        if (isset($this->callbacks[strtolower($module)][strtolower($setting)][$module_name])) {
            return "$module_name has the setting $setting of the module $module already registered!";
        }
        $this->callbacks[strtolower($module)][strtolower($setting)][$module_name] = &$reg_module;
        return FALSE;
    }


    function unregister_callback($module, $setting, &$reg_module)
    {
        $module_name = get_class($reg_module);
        if (!isset($this->callbacks[strtolower($module)][strtolower($setting)][$module_name])) {
            return "$module_name has the setting $setting of the module $module already registered!";
        }
        $this->callbacks[strtolower($module)][strtolower($setting)][$module_name] = NULL;
        unset($this->callbacks[strtolower($module)][strtolower($setting)][$module_name]);
        return FALSE;
    }
} // End of Class
?>
