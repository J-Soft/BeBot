<?php
/*
* Colors.php - central color storage and retrieving
*
* Written by Alreadythere
* Copyright (C) 2006 Christian Plog
*
* colorize() by Wolfbiter
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
$colors_core = new Colors_Core($bot);
/*
The Class itself...
*/
class Colors_Core extends BasePassiveModule
{
    private $no_tags;
    private $color_tags;
    private $theme_info;
    private $theme;


    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_module("colors");
        $this->register_event("cron", "1hour");
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("colors", "false") . " (
						name varchar(25) NOT NULL default '',
						code varchar(25) NOT NULL default '',
						PRIMARY KEY  (name)
					)"
        );
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("color_schemes", "true") . " (
						module varchar(25) NOT NULL default '',
						name varchar(25) NOT NULL default '',
						color_code varchar(25) NOT NULL default '',
						PRIMARY KEY (module, name)
					)"
        );
        $this->startup = true;
        $this->bot->core("settings")
            ->create("Color", "Theme", "Default", "What is the name of the theme file to use?", "", true);
        $this->define_color("aqua", "#00FFFF", false);
        $this->define_color("beige", "#FFE3A1", false);
        $this->define_color("black", "#000000", false);
        $this->define_color("blue", "#0000FF", false);
        $this->define_color("bluegray", "#8CB6FF", false);
        $this->define_color("bluesilver", "#9AD5D9", false);
        $this->define_color("brown", "#999926", false);
        $this->define_color("darkaqua", "#2299FF", false);
        $this->define_color("darklime", "#00A651", false);
        $this->define_color("darkorange", "#DF6718", false);
        $this->define_color("darkpink", "#FF0099", false);
        $this->define_color("forestgreen", "#66AA66", false);
        $this->define_color("fuchsia", "#FF00FF", false);
        $this->define_color("gold", "#CCAA44", false);
        $this->define_color("gray", "#808080", false);
        $this->define_color("green", "#008000", false);
        $this->define_color("lightbeige", "#FFFFC9", false);
        $this->define_color("lightfuchsia", "#FF63FF", false);
        $this->define_color("lightgray", "#D9D9D2", false);
        $this->define_color("lightgreen", "#00DD44", false);
        $this->define_color("brightgreen", "#00F000", false);
        $this->define_color("lightmaroon", "#FF0040", false);
        $this->define_color("lightteal", "#15E0A0", false);
        $this->define_color("dullteal", "#30D2FF", false);
        $this->define_color("lightyellow", "#DEDE42", false);
        $this->define_color("lime", "#00FF00", false);
        $this->define_color("maroon", "#800000", false);
        $this->define_color("navy", "#000080", false);
        $this->define_color("olive", "#808000", false);
        $this->define_color("orange", "#FF7718", false);
        $this->define_color("pink", "#FF8CFC", false);
        $this->define_color("purple", "#800080", false);
        $this->define_color("red", "#FF0000", false);
        $this->define_color("redpink", "#FF61A6", false);
        $this->define_color("seablue", "#6699FF", false);
        $this->define_color("seagreen", "#66FF99", false);
        $this->define_color("silver", "#C0C0C0", false);
        $this->define_color("tan", "#DDDD44", false);
        $this->define_color("teal", "#008080", false);
        $this->define_color("white", "#FFFFFF", false);
        $this->define_color("yellow", "#FFFF00", false);
        $this->define_color("omni", "#00ffff", false);
        $this->define_color("clan", "#ff9933", false);
        $this->define_color("neutral", "#ffffff", false);
        $this->define_scheme("ao", "admin", "pink", false);
        $this->define_scheme("ao", "cash", "gold", false);
        $this->define_scheme("ao", "ccheader", "white", false);
        $this->define_scheme("ao", "cctext", "lightgray", false);
        $this->define_scheme("ao", "clan", "brightgreen", false);
        $this->define_scheme("ao", "emote", "darkpink", false);
        $this->define_scheme("ao", "error", "red", false);
        $this->define_scheme("ao", "feedback", "yellow", false);
        $this->define_scheme("ao", "gm", "redpink", false);
        $this->define_scheme("ao", "infoheader", "lightgreen", false);
        $this->define_scheme("ao", "infoheadline", "tan", false);
        $this->define_scheme("ao", "infotext", "forestgreen", false);
        $this->define_scheme("ao", "infotextbold", "white", false);
        $this->define_scheme("ao", "megotxp", "yellow", false);
        $this->define_scheme("ao", "meheald", "bluegray", false);
        $this->define_scheme("ao", "mehitbynano", "white", false);
        $this->define_scheme("ao", "mehitother", "lightgray", false);
        $this->define_scheme("ao", "menubar", "lightteal", false);
        $this->define_scheme("ao", "misc", "white", false);
        $this->define_scheme("ao", "monsterhitme", "red", false);
        $this->define_scheme("ao", "mypet", "orange", false);
        $this->define_scheme("ao", "newbie", "seagreen", false);
        $this->define_scheme("ao", "news", "brightgreen", false);
        $this->define_scheme("ao", "none", "fuchsia", false);
        $this->define_scheme("ao", "npcchat", "bluesilver", false);
        $this->define_scheme("ao", "npcdescription", "yellow", false);
        $this->define_scheme("ao", "npcemote", "lightbeige", false);
        $this->define_scheme("ao", "npcooc", "lightbeige", false);
        $this->define_scheme("ao", "npcquestion", "lightgreen", false);
        $this->define_scheme("ao", "npcsystem", "red", false);
        $this->define_scheme("ao", "npctrade", "lightbeige", false);
        $this->define_scheme("ao", "otherhitbynano", "bluesilver", false);
        $this->define_scheme("ao", "otherpet", "darkorange", false);
        $this->define_scheme("ao", "pgroup", "white", false);
        $this->define_scheme("ao", "playerhitme", "red", false);
        $this->define_scheme("ao", "seekingteam", "seablue", false);
        $this->define_scheme("ao", "seekingteam", "seablue", false);
        $this->define_scheme("ao", "shout", "lightbeige", false);
        $this->define_scheme("ao", "skillcolor", "beige", false);
        $this->define_scheme("ao", "system", "white", false);
        $this->define_scheme("ao", "team", "seagreen", false);
        $this->define_scheme("ao", "tell", "aqua", false);
        $this->define_scheme("ao", "tooltip", "black", false);
        $this->define_scheme("ao", "tower", "lightfuchsia", false);
        $this->define_scheme("ao", "vicinity", "lightyellow", false);
        $this->define_scheme("ao", "whisper", "dullteal", false);
        // No tags cache created yet:
        $this->startup = false;
        $this->no_tags = true;
        $this->theme_info = "";
        $this->theme = array();
        $this->create_color_cache();
    }


    /*
    This makes sure the cache is up-to-date with the tables.
    */
    function cron()
    {
        $this->create_color_cache();
    }


    function get($color)
    {
        if ($this->color_tags['##' . $color . '##'] != '') {
            return $this->color_tags['##' . $color . '##'];
        } else {
            return "<font color=#000000>";
        }
    }


    function colorize($color, $text)
    {
        if ($this->color_tags['##' . $color . '##'] != '') {
            return $this->color_tags['##' . $color . '##'] . $text . "</font>";
        } else {
            return $text;
        }
    }


    // defines a new color:
    function define_color($name, $code, $cache = true)
    {
        $this->bot->db->query(
            "INSERT IGNORE INTO #___colors (name, code) VALUES ('" . $this->bot->db->real_escape_string(
                $name
            ) . "', '" . $this->bot->db->real_escape_string($code) . "')"
        );
        if ($cache) {
            $this->no_tags = true;
            $this->create_color_cache();
        }
    }


    // defines a new color scheme:
    function define_scheme($module, $scheme, $color_name, $cache = true)
    {
        $this->bot->db->query(
            "INSERT IGNORE INTO #___color_schemes" . " (module, name, color_code) VALUES ('" . $this->bot->db->real_escape_string(
                $module
            ) . "', '" . $this->bot->db->real_escape_string($scheme)
            . "', '" . $this->bot->db->real_escape_string($color_name) . "')"
        );
        if ($cache) {
            $this->no_tags = true;
            $this->create_color_cache();
        }
    }


    // defines a new color scheme, using a new color (at least it's assumed that the color is new):
    function define_color_scheme($module, $scheme, $color_name, $color_code)
    {
        // first add color:
        $this->bot->db->query(
            "INSERT IGNORE INTO #___colors" . " (name, code) VALUES ('" . $this->bot->db->real_escape_string(
                $color_name
            ) . "', '" . $this->bot->db->real_escape_string($color_code) . "')"
        );
        // then add scheme:
        $this->bot->db->query(
            "INSERT IGNORE INTO #___color_schemes" . " (module, name, color_code) VALUES ('" . $this->bot->db->real_escape_string(
                $module
            ) . "', '" . $this->bot->db->real_escape_string($scheme)
            . "', '" . $this->bot->db->real_escape_string($color_name) . "')"
        );
        $this->no_tags = true;
        $this->create_color_cache();
    }


    // changes the color reference for a scheme:
    function update_scheme($module, $scheme, $new_color_name)
    {
        $this->bot->db->query(
            "UPDATE #___color_schemes" . " SET color_code = '" . $this->bot->db->real_escape_string(
                $new_color_name
            ) . "' WHERE module = '" . $this->bot->db->real_escape_string($module)
            . "' AND name = '" . $this->bot->db->real_escape_string($scheme) . "'"
        );
        $this->no_tags = true;
        $this->create_color_cache();
    }


    // Read scheme file in, update all schemes in the bot with new information out of the file
    function read_scheme_file($filename)
    {
        $theme_dir = "./Themes/";
        // Make sure filename is valid
        if (!preg_match("/^([a-z01-9-_]+)$/i", $filename)) {
            $this->error->set(
                "Illegal filename for scheme file! The filename must only contain letters, numbers, - and _!"
            );
            return $this->error;
        }
        $scheme_file = file($theme_dir . $filename . ".scheme.xml");
        if (!$scheme_file) {
            $this->error->set("Scheme file not existing or empty!");
            return $this->error;
        }
        foreach ($scheme_file as $scheme_line) {
            if (preg_match(
                "/scheme module=\"([a-z_]+)\" name=\"([a-z_]+)\" code=\"([a-z]+)\"/i",
                $scheme_line,
                $info
            )
            ) {
                $this->bot->db->query(
                    "UPDATE #___color_schemes" . " SET color_code = '" . $this->bot->db->real_escape_string(
                        $info[3]
                    ) . "' WHERE module = '" . $this->bot->db->real_escape_string($info[1])
                    . "' AND name = '" . $this->bot->db->real_escape_string($info[2]) . "'"
                );
            }
        }
        $this->no_tags = true;
        $this->create_color_cache();
        return "Theme file " . $filename . " read, schemes updated!";
    }


    // Creates a scheme file containing all schemes in the bot table
    function create_scheme_file($filename, $name)
    {
        $theme_dir = "./Themes/";
        // Make sure filename is valid
        if (!preg_match("/^([a-z01-9-_]+)$/i", $filename)) {
            $this->error->set(
                "Illegal filename for scheme file! The filename must only contain letters, numbers, - and _!"
            );
            return $this->error;
        }
        $header = '<schemes name="Scheme for ' . ucfirst(
                strtolower($this->bot->botname)
            ) . '" version="1.0" author="' . ucfirst(strtolower($name)) . '" link="">';
        $footer = '</schemes>';
        $filename = $filename .= ".scheme.xml";
        $handle = fopen($theme_dir . $filename, "w");
        if (!$handle) {
            $this->error->set("Can't open scheme file " . $filename . "!");
            return $this->error;
        }
        $schemes = $this->bot->db->select("SELECT * FROM #___color_schemes ORDER BY module ASC, name ASC");
        if (empty($schemes)) {
            $this->error->set("No schemes defined!");
            return $this->error;
        }
        $status = true;
        if (!fwrite($handle, $header . "\n")) {
            $status = false;
        }
        foreach ($schemes as $scheme) {
            if (!fwrite(
                $handle,
                '<scheme module="' . $scheme[0] . '" name="' . $scheme[1] . '" code="' . $scheme[2] . '" />' . "\n"
            )
            ) {
                $status = false;
            }
        }
        if (!fwrite($handle, $footer . "\n")) {
            $status = false;
        }
        fclose($handle);
        if (!$status) {
            $this->error->set("Error while writing schemes!");
            return $this->error;
        }
        return "Scheme file " . $filename . " created!";
    }


    // Creates default theme file with given name
    function create_theme_file($name)
    {
        $theme_file[] = '<theme name="Default theme for BeBot" version="1.0" author="Alreadythere" link="">';
        $theme_file[] = '<color name="normal" color="lightyellow" />';
        $theme_file[] = '<color name="highlight" color="forestgreen" />';
        $theme_file[] = '<color name="error" color="red" />';
        $theme_file[] = '<color name="blob_title" color="tan" />';
        $theme_file[] = '<color name="blob_text" color="forestgreen" />';
        $theme_file[] = '</theme>';
        // Try to open the theme file, bail on error
        $handle = fopen($name, "a");
        if (!$handle) {
            return false;
        }
        // Now write the lines for the default theme file, mark errors to bail after closing file
        $status = true;
        foreach ($theme_file as $theme_line) {
            if (!fwrite($handle, $theme_line . "\n")) {
                $status = false;
            }
        }
        // Close file
        fclose($handle);
        // If error while writing bail
        if (!$status) {
            return false;
        }
        // Return default theme file
        $this->bot->log("COLOR", "THEME", "Created default theme!");
        return $theme_file;
    }


    // Reads the selected theme file. If the file doesn't exist it creates one with default colors
    function read_theme()
    {
        $theme_dir = "./Themes/";
        // Security check, theme filename HAS to be all letters or numbers, otherwise dying here for security reasons!
        if (!preg_match(
            "/^([a-z01-9-_]+)$/i",
            $this->bot->core("settings")
                ->get("Color", "Theme")
        )
        ) {
            die("POSSIBLE SECURITY PROBLEM! The theme filename can only contain letters, numbers - and _ for security reasons!\nThe bot has been shutdown.\n");
        }
        $theme_file_name = $theme_dir . $this->bot->core("settings")
                ->get("Color", "Theme") . ".colors.xml";
        // If theme file doesn't exist try to create it
        if (!is_file($theme_file_name)) {
            $theme_file = $this->create_theme_file($theme_file_name);
        } else {
            $theme_file = file($theme_file_name);
        }
        // If we don't got a theme file here yet we are in serious trouble, bail out!
        if (!$theme_file) {
            die("CRITICAL ERROR: Could not read nor create color theme file!\nThe bot has been shutdown.\n");
        }
        // Initialize theme array with the colors required by a theme
        $this->theme = array();
        $this->theme["normal"] = "#000000";
        $this->theme["highlight"] = "#000000";
        $this->theme["error"] = "#000000";
        $this->theme["blob_title"] = "#000000";
        $this->theme["blob_text"] = "#000000";
        // Parse the input file now
        foreach ($theme_file as $theme_line) {
            $theme_line = trim($theme_line);
            if (preg_match(
                "/color name=\"([a-z_]+)\" code=\"(#[0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f])\"/i",
                $theme_line,
                $info
            )
            ) {
                $this->theme[strtolower($info[1])] = $info[2];
            } elseif (preg_match("/color name=\"([a-z_]+)\" color=\"([a-z]+)\"/i", $theme_line, $info)) {
                $cols = $this->bot->db->select(
                    "SELECT code FROM #___colors WHERE name = '" . $this->bot->db->real_escape_string($info[2]) . "'"
                );
                if (empty($cols)) {
                    $this->theme[strtolower($info[1])] = "#000000";
                } else {
                    $this->theme[strtolower($info[1])] = $cols[0][0];
                }
            } elseif (preg_match(
                "/theme name=\"(.*)\" version=\"(.*)\" author=\"(.*)\" link=\"(.*)\"/i",
                $theme_line,
                $info
            )
            ) {
                $this->theme_info = "Name of theme: " . $info[1] . "\n";
                $this->theme_info .= "Version: " . $info[2] . "\n";
                $this->theme_info .= "Author: " . $info[3] . "\n";
                $this->theme_info .= "Link: " . $info[4];
            }
        }
    }


    // caches all possible color tags in the $this -> color_tags() array:
    function create_color_cache()
    {
        // Don't create the cache before the initialising is done!
        if ($this->startup) {
            return;
        }
        $this->no_tags = false;
        $this->color_tags = array();
        // Parse theme file
        $this->read_theme();
        // Create color tags for themes
        $theme_strings = array();
        foreach ($this->theme as $color => $colorcode) {
            $this->color_tags["##" . strtolower($color) . "##"] = "<font color=" . $colorcode . ">";
            $theme_strings[] = "color_code = '" . $color . "'";
        }
        $theme_string = implode(" OR ", $theme_strings);
        // Create all other color tags
        $cols = $this->bot->db->select("SELECT name, code FROM #___colors");
        if (!empty($cols)) {
            foreach ($cols as $col) {
                if (!isset($this->color_tags["##" . strtolower($col[0]) . "##"])) {
                    $this->color_tags["##" . strtolower($col[0]) . "##"] = "<font color=" . $col[1] . ">";
                }
            }
        }
        // Create all scheme tags for schemes using theme colors
        $cols = $this->bot->db->select(
            "SELECT concat(module, '_', name) AS scheme, color_code FROM #___color_schemes " . "WHERE " . $theme_string
            . " UNION SELECT name AS scheme, color_code FROM #___color_schemes WHERE (" . $theme_string . ") AND module = 'global'"
        );
        if (!empty($cols)) {
            foreach ($cols as $col) {
                if (!isset($this->color_tags["##" . strtolower($col[0]) . "##"])) {
                    $this->color_tags["##" . strtolower($col[0]) . "##"] = "<font color=" . $this->theme[strtolower(
                            $col[1]
                        )] . ">";
                }
            }
        }
        // Now create all scheme tags for schemes using no theme colors
        $cols = $this->bot->db->select(
            "SELECT concat(t1.module, '_', t1.name) AS name, t2.code FROM " . "#___color_schemes AS t1, #___colors AS t2 WHERE t1.color_code = t2.name AND NOT (" . $theme_string
            . ") UNION " . "SELECT t1.name AS name, t2.code AS code FROM #___color_schemes AS t1, #___colors AS t2 WHERE "
            . "t1.color_code = t2.name AND t1.module = 'global' AND NOT (" . $theme_string . ")"
        );
        $this->color_tags["##end##"] = "</font>";
        if (!empty($cols)) {
            foreach ($cols as $col) {
                if (!isset($this->color_tags["##" . strtolower($col[0]) . "##"])) {
                    $this->color_tags["##" . strtolower($col[0]) . "##"] = "<font color=" . $col[1] . ">";
                }
            }
        }
    }


    // replaces all color tags with the corresponding font commands:
    function parse($text)
    {
        if ($this->no_tags) {
            $this->create_color_cache();
        }
        // No replacing if no tags can be in the text
        if (strpos($text, "##") === false) {
            return $text;
        }
	// Go ahead and replace all tags
	$maxpass = 3; // Increase for more sublevels only if needed
	$stop = "##end##";
	$trig = "(?:(?!#{2}).)+";
	for($i=1;$i<$maxpass+1;$i++) {
		foreach ($this -> color_tags as $tag => $font) {
			if ($tag != $stop) {
				while (preg_match("/(".$tag.$trig.$stop.")/i", $text, $match, PREG_OFFSET_CAPTURE)) {
					  $prev = substr($text, 0, $match[1][1]);
					  $repl = preg_replace("/".$tag."/i", $font, $match[1][0], 1);
					  $repl = preg_replace("/".$stop."/i", "</font>", $repl, 1);
					  $post = substr($text, $match[1][1] + strlen($match[1][0]));
					  $text = $prev.$repl.$post;
				}
			}
		}
	}
	$text = preg_replace("/##[^#]+##/i", "", $text);
	return $text;
    }


    function get_theme()
    {
        return $this->theme;
    }


    function check_theme($col)
    {
        return isset($this->theme[strtolower($col)]);
    }
}

?>
