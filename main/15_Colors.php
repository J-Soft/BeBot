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
* Copyright (C) 2005-2010 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->register_module("colors");
		$this->register_event("cron", "1hour");
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("colors", "false") . " (
						name varchar(25) NOT NULL default '',
						code varchar(25) NOT NULL default '',
						PRIMARY KEY  (name)
					)");
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("color_schemes", "true") . " (
						module varchar(25) NOT NULL default '',
						name varchar(25) NOT NULL default '',
						color_code varchar(25) NOT NULL default '',
						PRIMARY KEY (module, name)
					)");
		$this->startup = TRUE;
		$this->bot->core("settings")->create("Color", "Theme", "Default", "What is the name of the theme file to use?", "", TRUE);
		$this->define_color("aqua", "#00FFFF", FALSE);
		$this->define_color("beige", "#FFE3A1", FALSE);
		$this->define_color("black", "#000000", FALSE);
		$this->define_color("blue", "#0000FF", FALSE);
		$this->define_color("bluegray", "#8CB6FF", FALSE);
		$this->define_color("bluesilver", "#9AD5D9", FALSE);
		$this->define_color("brown", "#999926", FALSE);
		$this->define_color("darkaqua", "#2299FF", FALSE);
		$this->define_color("darklime", "#00A651", FALSE);
		$this->define_color("darkorange", "#DF6718", FALSE);
		$this->define_color("darkpink", "#FF0099", FALSE);
		$this->define_color("forestgreen", "#66AA66", FALSE);
		$this->define_color("fuchsia", "#FF00FF", FALSE);
		$this->define_color("gold", "#CCAA44", FALSE);
		$this->define_color("gray", "#808080", FALSE);
		$this->define_color("green", "#008000", FALSE);
		$this->define_color("lightbeige", "#FFFFC9", FALSE);
		$this->define_color("lightfuchsia", "#FF63FF", FALSE);
		$this->define_color("lightgray", "#D9D9D2", FALSE);
		$this->define_color("lightgreen", "#00DD44", FALSE);
		$this->define_color("brightgreen", "#00F000", FALSE);
		$this->define_color("lightmaroon", "#FF0040", FALSE);
		$this->define_color("lightteal", "#15E0A0", FALSE);
		$this->define_color("dullteal", "#30D2FF", FALSE);
		$this->define_color("lightyellow", "#DEDE42", FALSE);
		$this->define_color("lime", "#00FF00", FALSE);
		$this->define_color("maroon", "#800000", FALSE);
		$this->define_color("navy", "#000080", FALSE);
		$this->define_color("olive", "#808000", FALSE);
		$this->define_color("orange", "#FF7718", FALSE);
		$this->define_color("pink", "#FF8CFC", FALSE);
		$this->define_color("purple", "#800080", FALSE);
		$this->define_color("red", "#FF0000", FALSE);
		$this->define_color("redpink", "#FF61A6", FALSE);
		$this->define_color("seablue", "#6699FF", FALSE);
		$this->define_color("seagreen", "#66FF99", FALSE);
		$this->define_color("silver", "#C0C0C0", FALSE);
		$this->define_color("tan", "#DDDD44", FALSE);
		$this->define_color("teal", "#008080", FALSE);
		$this->define_color("white", "#FFFFFF", FALSE);
		$this->define_color("yellow", "#FFFF00", FALSE);
		$this->define_color("omni", "#00ffff", FALSE);
		$this->define_color("clan", "#ff9933", FALSE);
		$this->define_color("neutral", "#ffffff", FALSE);
		$this->define_scheme("ao", "admin", "pink", FALSE);
		$this->define_scheme("ao", "cash", "gold", FALSE);
		$this->define_scheme("ao", "ccheader", "white", FALSE);
		$this->define_scheme("ao", "cctext", "lightgray", FALSE);
		$this->define_scheme("ao", "clan", "brightgreen", FALSE);
		$this->define_scheme("ao", "emote", "darkpink", FALSE);
		$this->define_scheme("ao", "error", "red", FALSE);
		$this->define_scheme("ao", "feedback", "yellow", FALSE);
		$this->define_scheme("ao", "gm", "redpink", FALSE);
		$this->define_scheme("ao", "infoheader", "lightgreen", FALSE);
		$this->define_scheme("ao", "infoheadline", "tan", FALSE);
		$this->define_scheme("ao", "infotext", "forestgreen", FALSE);
		$this->define_scheme("ao", "infotextbold", "white", FALSE);
		$this->define_scheme("ao", "megotxp", "yellow", FALSE);
		$this->define_scheme("ao", "meheald", "bluegray", FALSE);
		$this->define_scheme("ao", "mehitbynano", "white", FALSE);
		$this->define_scheme("ao", "mehitother", "lightgray", FALSE);
		$this->define_scheme("ao", "menubar", "lightteal", FALSE);
		$this->define_scheme("ao", "misc", "white", FALSE);
		$this->define_scheme("ao", "monsterhitme", "red", FALSE);
		$this->define_scheme("ao", "mypet", "orange", FALSE);
		$this->define_scheme("ao", "newbie", "seagreen", FALSE);
		$this->define_scheme("ao", "news", "brightgreen", FALSE);
		$this->define_scheme("ao", "none", "fuchsia", FALSE);
		$this->define_scheme("ao", "npcchat", "bluesilver", FALSE);
		$this->define_scheme("ao", "npcdescription", "yellow", FALSE);
		$this->define_scheme("ao", "npcemote", "lightbeige", FALSE);
		$this->define_scheme("ao", "npcooc", "lightbeige", FALSE);
		$this->define_scheme("ao", "npcquestion", "lightgreen", FALSE);
		$this->define_scheme("ao", "npcsystem", "red", FALSE);
		$this->define_scheme("ao", "npctrade", "lightbeige", FALSE);
		$this->define_scheme("ao", "otherhitbynano", "bluesilver", FALSE);
		$this->define_scheme("ao", "otherpet", "darkorange", FALSE);
		$this->define_scheme("ao", "pgroup", "white", FALSE);
		$this->define_scheme("ao", "playerhitme", "red", FALSE);
		$this->define_scheme("ao", "seekingteam", "seablue", FALSE);
		$this->define_scheme("ao", "seekingteam", "seablue", FALSE);
		$this->define_scheme("ao", "shout", "lightbeige", FALSE);
		$this->define_scheme("ao", "skillcolor", "beige", FALSE);
		$this->define_scheme("ao", "system", "white", FALSE);
		$this->define_scheme("ao", "team", "seagreen", FALSE);
		$this->define_scheme("ao", "tell", "aqua", FALSE);
		$this->define_scheme("ao", "tooltip", "black", FALSE);
		$this->define_scheme("ao", "tower", "lightfuchsia", FALSE);
		$this->define_scheme("ao", "vicinity", "lightyellow", FALSE);
		$this->define_scheme("ao", "whisper", "dullteal", FALSE);
		// No tags cache created yet:
		$this->startup = FALSE;
		$this->no_tags = TRUE;
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
		if ($this->color_tags['##' . $color . '##'] != '')
			return $this->color_tags['##' . $color . '##'];
		else
			return "<font color=#000000>";
	}

	function colorize($color, $text)
	{
		if ($this->color_tags['##' . $color . '##'] != '')
			return $this->color_tags['##' . $color . '##'] . $text . "</font>";
		else
			return $text;
	}

	// defines a new color:
	function define_color($name, $code, $cache = TRUE)
	{
		$this->bot->db->query("INSERT IGNORE INTO #___colors (name, code) VALUES ('" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($code) . "')");
		if ($cache)
		{
			$this->no_tags = TRUE;
			$this->create_color_cache();
		}
	}

	// defines a new color scheme:
	function define_scheme($module, $scheme, $color_name, $cache = TRUE)
	{
		$this->bot->db->query("INSERT IGNORE INTO #___color_schemes" . " (module, name, color_code) VALUES ('" . mysql_real_escape_string($module) . "', '" . mysql_real_escape_string($scheme) . "', '" . mysql_real_escape_string($color_name) . "')");
		if ($cache)
		{
			$this->no_tags = TRUE;
			$this->create_color_cache();
		}
	}

	// defines a new color scheme, using a new color (at least it's assumed that the color is new):
	function define_color_scheme($module, $scheme, $color_name, $color_code)
	{
		// first add color:
		$this->bot->db->query("INSERT IGNORE INTO #___colors" . " (name, code) VALUES ('" . mysql_real_escape_string($color_name) . "', '" . mysql_real_escape_string($color_code) . "')");
		// then add scheme:
		$this->bot->db->query("INSERT IGNORE INTO #___color_schemes" . " (module, name, color_code) VALUES ('" . mysql_real_escape_string($module) . "', '" . mysql_real_escape_string($scheme) . "', '" . mysql_real_escape_string($color_name) . "')");
		$this->no_tags = TRUE;
		$this->create_color_cache();
	}

	// changes the color reference for a scheme:
	function update_scheme($module, $scheme, $new_color_name)
	{
		$this->bot->db->query("UPDATE #___color_schemes" . " SET color_code = '" . mysql_real_escape_string($new_color_name) . "' WHERE module = '" . mysql_real_escape_string($module) . "' AND name = '" . mysql_real_escape_string($scheme) . "'");
		$this->no_tags = TRUE;
		$this->create_color_cache();
	}

	// Read scheme file in, update all schemes in the bot with new information out of the file
	function read_scheme_file($filename)
	{
		$theme_dir = "./themes/";
		// Make sure filename is valid
		if (! preg_match("/^([a-z01-9-_]+)$/i", $filename))
		{
			$this->error->set("Illegal filename for scheme file! The filename must only contain letters, numbers, - and _!");
			return $this->error;
		}
		$scheme_file = file($theme_dir . $filename . ".scheme.xml");
		if (! $scheme_file)
		{
			$this->error->set("Scheme file not existing or empty!");
			return $this->error;
		}
		foreach ($scheme_file as $scheme_line)
		{
			if (preg_match("/scheme module=\"([a-z_]+)\" name=\"([a-z_]+)\" code=\"([a-z]+)\"/i", $scheme_line, $info))
			{
				$this->bot->db->query("UPDATE #___color_schemes" . " SET color_code = '" . mysql_real_escape_string($info[3]) . "' WHERE module = '" . mysql_real_escape_string($info[1]) . "' AND name = '" . mysql_real_escape_string($info[2]) . "'");
			}
		}
		$this->no_tags = TRUE;
		$this->create_color_cache();
		return "Theme file " . $filename . " read, schemes updated!";
	}

	// Creates a scheme file containing all schemes in the bot table
	function create_scheme_file($filename, $name)
	{
		$theme_dir = "./themes/";
		// Make sure filename is valid
		if (! preg_match("/^([a-z01-9-_]+)$/i", $filename))
		{
			$this->error->set("Illegal filename for scheme file! The filename must only contain letters, numbers, - and _!");
			return $this->error;
		}
		$header = '<schemes name="Scheme for ' . ucfirst(strtolower($this->bot->botname)) . '" version="1.0" author="' . ucfirst(strtolower($name)) . '" link="">';
		$footer = '</schemes>';
		$filename = $filename .= ".scheme.xml";
		$handle = fopen($theme_dir . $filename, "w");
		if (! $handle)
		{
			$this->error->set("Can't open scheme file " . $filename . "!");
			return $this->error;
		}
		$schemes = $this->bot->db->select("SELECT * FROM #___color_schemes ORDER BY module ASC, name ASC");
		if (empty($schemes))
		{
			$this->error->set("No schemes defined!");
			return $this->error;
		}
		$status = TRUE;
		if (! fwrite($handle, $header . "\n"))
		{
			$status = FALSE;
		}
		foreach ($schemes as $scheme)
		{
			if (! fwrite($handle, '<scheme module="' . $scheme[0] . '" name="' . $scheme[1] . '" code="' . $scheme[2] . '" />' . "\n"))
			{
				$status = FALSE;
			}
		}
		if (! fwrite($handle, $footer . "\n"))
		{
			$status = FALSE;
		}
		fclose($handle);
		if (! $status)
		{
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
		if (! $handle)
		{
			return FALSE;
		}
		// Now write the lines for the default theme file, mark errors to bail after closing file
		$status = TRUE;
		foreach ($theme_file as $theme_line)
		{
			if (! fwrite($handle, $theme_line . "\n"))
			{
				$status = FALSE;
			}
		}
		// Close file
		fclose($handle);
		// If error while writing bail
		if (! $status)
		{
			return FALSE;
		}
		// Return default theme file
		$this->bot->log("COLOR", "THEME", "Created default theme!");
		return $theme_file;
	}

	// Reads the selected theme file. If the file doesn't exist it creates one with default colors
	function read_theme()
	{
		$theme_dir = "./themes/";
		// Security check, theme filename HAS to be all letters or numbers, otherwise dying here for security reasons!
		if (! preg_match("/^([a-z01-9-_]+)$/i", $this->bot->core("settings")->get("Color", "Theme")))
		{
			die("POSSIBLE SECURITY PROBLEM! The theme filename can only contain letters, numbers - and _ for security reasons!\nThe bot has been shutdown.\n");
		}
		$theme_file_name = $theme_dir . $this->bot->core("settings")->get("Color", "Theme") . ".colors.xml";
		// If theme file doesn't exist try to create it
		if (! is_file($theme_file_name))
		{
			$theme_file = $this->create_theme_file($theme_file_name);
		}
		else
		{
			$theme_file = file($theme_file_name);
		}
		// If we don't got a theme file here yet we are in serious trouble, bail out!
		if (! $theme_file)
		{
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
		foreach ($theme_file as $theme_line)
		{
			$theme_line = trim($theme_line);
			if (preg_match("/color name=\"([a-z_]+)\" code=\"(#[0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f])\"/i", $theme_line, $info))
			{
				$this->theme[strtolower($info[1])] = $info[2];
			}
			elseif (preg_match("/color name=\"([a-z_]+)\" color=\"([a-z]+)\"/i", $theme_line, $info))
			{
				$cols = $this->bot->db->select("SELECT code FROM #___colors WHERE name = '" . mysql_real_escape_string($info[2]) . "'");
				if (empty($cols))
				{
					$this->theme[strtolower($info[1])] = "#000000";
				}
				else
				{
					$this->theme[strtolower($info[1])] = $cols[0][0];
				}
			}
			elseif (preg_match("/theme name=\"(.*)\" version=\"(.*)\" author=\"(.*)\" link=\"(.*)\"/i", $theme_line, $info))
			{
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
		if ($this->startup)
		{
			return;
		}
		$this->no_tags = FALSE;
		$this->color_tags = array();
		// Parse theme file
		$this->read_theme();
		// Create color tags for themes
		$theme_strings = array();
		foreach ($this->theme as $color => $colorcode)
		{
			$this->color_tags["##" . strtolower($color) . "##"] = "<font color=" . $colorcode . ">";
			$theme_strings[] = "color_code = '" . $color . "'";
		}
		$theme_string = implode(" OR ", $theme_strings);
		// Create all other color tags
		$cols = $this->bot->db->select("SELECT name, code FROM #___colors");
		if (! empty($cols))
		{
			foreach ($cols as $col)
			{
				if (! isset($this->color_tags["##" . strtolower($col[0]) . "##"]))
				{
					$this->color_tags["##" . strtolower($col[0]) . "##"] = "<font color=" . $col[1] . ">";
				}
			}
		}
		// Create all scheme tags for schemes using theme colors
		$cols = $this->bot->db->select("SELECT concat(module, '_', name) AS scheme, color_code FROM #___color_schemes " . "WHERE " . $theme_string . " UNION SELECT name AS scheme, color_code FROM #___color_schemes WHERE (" . $theme_string . ") AND module = 'global'");
		if (! empty($cols))
		{
			foreach ($cols as $col)
			{
				if (! isset($this->color_tags["##" . strtolower($col[0]) . "##"]))
				{
					$this->color_tags["##" . strtolower($col[0]) . "##"] = "<font color=" . $this->theme[strtolower($col[1])] . ">";
				}
			}
		}
		// Now create all scheme tags for schemes using no theme colors
		$cols = $this->bot->db->select("SELECT concat(t1.module, '_', t1.name) AS name, t2.code FROM " . "#___color_schemes AS t1, #___colors AS t2 WHERE t1.color_code = t2.name AND NOT (" . $theme_string . ") UNION " . "SELECT t1.name AS name, t2.code AS code FROM #___color_schemes AS t1, #___colors AS t2 WHERE " . "t1.color_code = t2.name AND t1.module = 'global' AND NOT (" . $theme_string . ")");
		$this->color_tags["##end##"] = "</font>";
		if (! empty($cols))
		{
			foreach ($cols as $col)
			{
				if (! isset($this->color_tags["##" . strtolower($col[0]) . "##"]))
				{
					$this->color_tags["##" . strtolower($col[0]) . "##"] = "<font color=" . $col[1] . ">";
				}
			}
		}
	}

	// replaces all color tags with the corresponding font commands:
	function parse($text)
	{
		if ($this->no_tags)
		{
			$this->create_color_cache();
		}
		// No replacing if no tags can be in the text
		if (strpos($text, "##") === false)
		{
			return $text;
		}
		// Go ahead and replace all tags
		foreach ($this->color_tags as $tag => $font)
		{
			$text = str_ireplace($tag, $font, $text);
		}
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