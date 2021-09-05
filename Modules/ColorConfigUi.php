<?php
/*
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
$colorconfig = new ColorConfig($bot);
/*
The Class itself...
*/
class ColorConfig extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command('all', 'color', 'SUPERADMIN');
        $this->register_command('all', 'theme', 'SUPERADMIN');
        $this->help['description'] = "Configures the colors used by the bot.";
        $this->help['command']['color'] = "Shows the interface to configure colors for the bot.";
        $this->help['command']['theme'] = "Allows switching between existing theme files.";
        $this->help['command']['theme export <filename'] = "Write all current scheme settings to file <filename>.scheme.xml.";
        $this->help['command']['theme import'] = "List all existing scheme files to update current settings.";
        $this->help['notes'] = "No notes";
    }


    /*
    This gets called on a tell with the command
    */
    function command_handler($name, $msg, $origin)
    {
        if (preg_match("/^color$/i", $msg)) {
            return $this->show_colors();
        } elseif (preg_match("/^color menu$/i", $msg)) {
            return $this->color_menu();
        } elseif (preg_match("/^color module ([A-Za-z_]+)$/i", $msg, $info)) {
            return $this->module_menu($info[1]);
        } elseif (preg_match("/^color select ([A-Za-z_]+) ([A-Za-z_]+)$/i", $msg, $info)) {
            return $this->select_color($info[1], $info[2]);
        } elseif (preg_match("/^color set ([A-Za-z_]+) ([A-Za-z_]+) ([A-Za-z_]+)$/i", $msg, $info)) {
            return $this->set_color($info[1], $info[2], $info[3]);
        } elseif (preg_match("/^theme$/i", $msg)) {
            return $this->show_themes();
        } elseif (preg_match("/^theme select (.*)$/i", $msg, $info)) {
            return $this->select_theme($info[1]);
        } elseif (preg_match("/^theme export ([a-z01-9_-]+)$/i", $msg, $info)) {
            return $this->export_schemes($info[1], $name);
        } elseif (preg_match("/^theme import$/i", $msg)) {
            return $this->show_scheme_files();
        } elseif (preg_match("/^theme import ([a-z01-9_-]+)$/i", $msg, $info)) {
            return $this->import_schemes($info[1]);
        }
        return false;
    }


    function show_colors()
    {
        $cols = $this->bot->db->select(
            "SELECT concat(module, '_', name) FROM #___color_schemes ORDER BY module, name ASC"
        );
        if (empty($cols)) {
            return "No schemes defined at all!";
        }
        $blob = "##ao_infotext##The following color schemes are defined.##end## ";
        $blob .= $this->bot->core("tools")
                ->chatcmd("color menu", "Edit a color scheme") . "\n";
        foreach ($cols as $col) {
            $blob .= "\n##" . $col[0] . "##" . $col[0] . "##end##";
        }
        return $this->bot->core("tools")->make_blob("Defined colors", $blob);
    }


    /*
    Shows the existing modules with color settings:
    */
    function color_menu()
    {
        $mods = $this->bot->db->select("SELECT DISTINCT(module) FROM #___color_schemes ORDER BY module ASC");
        if (empty($mods)) {
            return "##error##No modules defined!";
        }
        $blob_text = "##ao_infotext##Select a module to update:##end##\n";
        foreach ($mods as $mod) {
            $blob_text .= "\n" . $this->bot->core("tools")
                    ->chatcmd("color module " . $mod[0], $mod[0]);
        }
        return $this->bot->core("tools")
            ->make_blob("Color modules", $blob_text);
    }


    /*
    Shows the colors of a specific module:
    */
    function module_menu($module)
    {
        $schemes = $this->bot->db->select(
            "SELECT DISTINCT(name) FROM #___color_schemes WHERE module = '" . $module . "' ORDER BY name ASC"
        );
        if (empty($schemes)) {
            return "##error##No schemes defined in module " . $module . "!";
        }
        $blob_text = "##ao_infotext##Select a color to update in module " . $module . ":##end##\n";
        foreach ($schemes as $scheme) {
            $blob_text .= "\n" . $this->bot->core("tools")
                    ->chatcmd("color select " . $module . " " . $scheme[0], $scheme[0]);
        }
        return $this->bot->core("tools")
            ->make_blob("Select a scheme to update", $blob_text);
    }


    /*
    Allows to pick a new color for a specific scheme:
    */
    function select_color($module, $scheme)
    {
        $cols = $this->bot->db->select("SELECT name FROM #___colors ORDER BY name ASC");
        if (empty($cols)) {
            return "No colors defined! How can this be?";
        }
		$scur = $this->bot->db->select("SELECT color_code FROM #___color_schemes WHERE module='".$module."' AND name='".$scheme."' LIMIT 1");
		if(isset($scur[0][0])&&$scur[0][0]!='') { $cur=$scur[0][0]; } else { $cur='?unknown?'; }
        $blob = "##ao_infotext##Select a color to use for##end## ##" . $module . "_" . $scheme . "##" . $module . "_" . $scheme;
        $blob .= "##end####ao_infotext##:##end##\n";
        foreach ($this->bot->core("colors")->get_theme() as $color => $code) {
            $blob .= "\n##" . $color . "##" . $color . " ##end##";
			if($cur==$color) {
				$blob .= "Current!";
			} else {
				$blob .= $this->bot->core("tools")
					->chatcmd("color set " . $module . " " . $scheme . " " . $color, "Select!");
			}
        }
        foreach ($cols as $col) {
            $blob .= "\n##" . $col[0] . "##" . $col[0] . " ##end##";
			if($cur==$col[0]) {
				$blob .= "Current!";
			} else {
				$blob .= $this->bot->core("tools")
					->chatcmd("color set " . $module . " " . $scheme . " " . $col[0], "Select!");
			}
		}
        return $this->bot->core("tools")->make_blob("Pick a color!", $blob);
    }


    /*
    Sets a scheme to a new color:
    */
    function set_color($module, $scheme, $newcolor)
    {
        $res = $this->bot->db->select(
            "SELECT * FROM #___colors WHERE name = '" . mysqli_real_escape_string($this->bot->db->CONN,$newcolor) . "'"
        );
        if (empty($res) && !$this->bot->core("colors")->check_theme($newcolor)
        ) {
            return "##error##You have to select an existing color name!##end##";
        }
        $res = $this->bot->db->select(
            "SELECT * FROM #___color_schemes WHERE module = '" . mysqli_real_escape_string($this->bot->db->CONN,
                $module
            ) . "' AND name = '" . mysqli_real_escape_string($this->bot->db->CONN,$scheme) . "'"
        );
        if (empty($res)) {
            return "##error##You have to select an existing color scheme!##end##";
        }
        $this->bot->core("colors")->update_scheme($module, $scheme, $newcolor);
        return "Scheme ##highlight## " . $module . "_" . $scheme . " ##end## set to color ##" . $newcolor . "##" . $newcolor . "##end##";
    }


    function show_themes()
    {
        $blob = "##blob_title##Themes available##end##\n";
        $folder = dir("./Themes/");
        while ($themefile = $folder->read()) {
            if (!is_dir($themefile) && preg_match("/(.*)\.colors\.xml$/i", $themefile, $info)) {
                if (strcasecmp(
                        $info[1],
                        $this->bot->core("settings")
                            ->get("Color", "Theme")
                    ) == 0
                ) {
                    $blob .= "\n##blob_text##" . $info[1] . " (currently in use)##end##";
                } else {
                    $blob .= "\n" . $this->bot->core("tools")
                            ->chatcmd("theme select " . $info[1], $info[1]);
                }
            }
        }
        return $this->bot->core("tools")->make_blob("Select a theme!", $blob);
    }


    function select_theme($newscheme)
    {
        $this->bot->core("settings")->save("Color", "Theme", $newscheme);
        $this->bot->core("colors")->create_color_cache();
        return "##highlight##" . $newscheme . " ##end##selected as new color scheme!";
    }


    function export_schemes($filename, $name)
    {
        return $this->bot->core("colors")->create_scheme_file($filename, $name);
    }


    function show_scheme_files()
    {
        $blob = "##blob_title##Scheme files available##end##\n";
        $folder = dir("./Themes/");
        while ($schemefile = $folder->read()) {
            if (!is_dir($schemefile) && preg_match("/(.*)\.scheme\.xml$/i", $schemefile, $info)) {
                $blob .= "\n" . $this->bot->core("tools")
                        ->chatcmd("theme import " . $info[1], $info[1]);
            }
        }
        return $this->bot->core("tools")
            ->make_blob("Select a scheme file!", $blob);
    }


    function import_schemes($filename)
    {
        return $this->bot->core("colors")->read_scheme_file($filename);
    }
}

?>
