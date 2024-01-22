<?php
/*
* TowerAttack.php - Handle Tower attack events.
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
* - Bitnykk (RK5)
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
$towerAttack = new TowerAttack($bot);
/*
The Class itself...
*/
class TowerAttack extends BaseActiveModule
{
	var $suppress, $suppressdata;
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
		$this->register_module("towerattack");
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("tower_attack", "false") . "
				(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				time int,
				off_guild VARCHAR(50),
				off_side VARCHAR(10),
				off_player VARCHAR(20),
				off_level int,
				off_profession VARCHAR(15),
				def_guild VARCHAR(50),
				def_side VARCHAR(10),
				zone VARCHAR(50),
				x_coord INT,
				y_coord INT,
				UNIQUE (time, off_guild, off_side, off_player, def_guild, def_side, zone, x_coord, y_coord),
				INDEX (off_guild),
				INDEX (off_side),
				INDEX (def_guild),
				INDEX (def_side),
				INDEX (zone))"
        );
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("tower_result", "false") . "
				(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				time int,
				win_guild VARCHAR(50),
				win_side VARCHAR(10),
				lose_guild VARCHAR(50),
				lose_side VARCHAR(10),
				zone VARCHAR(50),
				UNIQUE (time, win_guild, win_side, lose_guild, lose_side, zone),
				INDEX (win_guild),
				INDEX (win_side),
				INDEX (zone))"
        );
        $this->register_command("all", "battle", "GUEST");
        $this->register_command("all", "victory", "GUEST");
        $this->register_event("gmsg", "All Towers");
        $this->register_event("gmsg", "Org Msg");
        $this->register_event("gmsg", "Tower Battle Outcome");
        $this->register_event("cron", "5sec");
        $this->bot->core("settings")
            ->create("TowerAttack", "Spam", true, "Is spamming of tower attacks into org chat on or off?");
        $this->bot->core("settings")
            ->create(
                "TowerAttack",
                "SpamPrevention",
                true,
                "Should several attacks on the same site in a short timeframe be collected and spammed as a blob instead of showing each attack singulary?"
            );
        $this->bot->core("settings")
            ->create(
                "TowerAttack",
                "SpamTo",
                "both",
                "Where should any tower spam be displayed to? Just gc, just pgmsg, or both?",
                "gc;pgmsg;both"
            );
        $this->bot->core("settings")
            ->create(
                "TowerAttack",
                "RelayTowerDamage",
                "none",
                "Should damage spam of towers be relayed to the private group and/or any linked bots?",
                "none;pgroup;relay;both"
            );
        $this->bot->core("settings")
            ->create(
                "TowerAttack",
                "ReadOnly",
                false,
                "Should the bot only read tower attacks without adding them to the tables? Useful if you got several bots sharing tower tables."
            );
        $this->bot->core("settings")
            ->create(
                "TowerAttack",
                "AttackStringOrged",
                "#!off_guild!# attacked #!def_guild!# in##highlight## #!zone!# (#!lca_num!#)##end##! #!blob!#",
                "This string is used as base for all tower attack spam of orged characters. For more information read the comment in TowerAttack.php for the format_attack_string() function",
                ""
            );
        $this->bot->core("settings")
            ->create(
                "TowerAttack",
                "AttackStringUnorged",
                "#!off_player!# attacked #!def_guild!# in##highlight## #!zone!# (#!lca_num!#)##end##! #!blob!#",
                "This string is used as base for all tower attack spam of orged characters. For more information read the comment in TowerAttack.php for the format_attack_string() function",
                ""
            );
        $this->bot->core("settings")
            ->create(
                "TowerAttack",
                "BlobStringOrged",
                "#!time!# - #!off_guild!# (#!off_player!#, #!off_level!# #!off_profession!#) attacked #!def_guild!# in #!zone!# (#!lca_num!#, L #!lca_minlevel!# - #!lca_maxlevel!#).",
                "This string is used as base for every entry in the history blob for tower attacks. For more information read the comment in TowerAttack.php for the format_attack_string() function",
                ""
            );
        $this->bot->core("settings")
            ->create(
                "TowerAttack",
                "BlobStringUnorged",
                "#!time!# - #!off_player!# (#!off_level!# #!off_profession!#) attacked #!def_guild!# in #!zone!# (#!lca_num!#, L #!lca_minlevel!# - #!lca_maxlevel!#).",
                "This string is used as base for every entry in the history blob for tower attacks. For more information read the comment in TowerAttack.php for the format_attack_string() function",
                ""
            );
        $this->bot->core("settings")
            ->create(
                "TowerAttack",
                "AttacksPerBlob",
                20,
                "How many attacks should be shown in the attack history blob?",
                "3;5;8;10;13;15;18;20"
            );
        $this->bot->core("settings")
            ->create(
                "TowerAttack",
                "VictoryBlobSize",
                20,
                "How many victories from the history should be displayed at once?",
                "5;10;15;20"
            );
        $this->bot->core("settings")
            ->create(
                "TowerAttack",
                "VictoryString",
                "#!time!# - #!off_guild!# won vs #!def_guild!# in #!zone!#!",
                "This string is used as base for the victory lines in the victory blob. For more information read the comment in TowerAttack.php for the format_attack_string() function",
                ""
            );
        $this->bot->core("settings")
            ->create(
                "TowerAttack",
                "SuppressTime",
                30,
                "This is the time after last attack on same org that the attack with be suppressed for in seconds",
                "10;20;30;60;120;180;300"
            );
        $this->bot->core("settings")
            ->create("TowerAttack", "AlertDisc", false, "Do we alert Discord of Tower Attacks ?");
        $this->bot->core("settings")
            ->create("TowerAttack", "DiscChanId", "", "What Discord ChannelId in case we separate Tower Attacks from main Discord channel (leave empty for all in main channel) ?");
        $this->bot->core("settings")
            ->create("TowerAttack", "DiscTag", "", "Should we add a Discord Tag (e.g. @here or @everyone) to Towers Attacks for notifying Discord users (leave empty for no notification) ?");
        $this->bot->core("settings")
            ->create("TowerAttack", "AlertIrc", false, "Do we alert Irc of Tower Attacks ?");			
        $this->update_table();
        $this->help['description'] = 'Handle tower attack events.';
        $this->help['command']['battle'] = "Shows recent tower attacks.";
        $this->help['command']['victory'] = "Shows recent tower victories.";
        $this->help['notes'] = "The bot MUST be in the top-three rank of the guild for this module to work.";
    }


    function update_table()
    {
        if ($this->bot->core("settings")
            ->exists("TowerAttack", "SchemaVersion")
        ) {
            $this->bot->db->set_version(
                "tower_result",
                $this->bot
                    ->core("settings")->get("TowerAttack", "SchemaVersion")
            );
            $this->bot->db->set_version(
                "tower_attack",
                $this->bot
                    ->core("settings")->get("TowerAttack", "SchemaVersion")
            );
            $this->bot->core("settings")->del("TowerAttack", "SchemaVersion");
        }
        switch ($this->bot->db->get_version("tower_result")) {
            case 1:
				$col = $this->bot->db->select("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '#___tower_result' AND COLUMN_NAME = 'zone'");
				if(count($col)==0) {
					$this->bot->db->update_table(
						"tower_result",
						"zone",
						"add",
						"ALTER TABLE #___tower_result ADD UNIQUE (zone) VARCHAR(50)"
					);
				}
				$index = $this->bot->db->select("SHOW INDEX FROM #___tower_result WHERE KEY_NAME = 'zone'");
				if(count($index)==0) {				
					$this->bot->db->query("ALTER TABLE #___tower_result ADD INDEX (zone)");
				}				
				$col = $this->bot->db->select("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '#___tower_result' AND COLUMN_NAME = 'win_guild'");
				if(count($col)==0) {
					$this->bot->db->update_table(
						"tower_result",
						"win_guild",
						"add",
						"ALTER TABLE #___tower_result ADD UNIQUE (win_guild) VARCHAR(50)"
					);
				}
				$index = $this->bot->db->select("SHOW INDEX FROM #___tower_result WHERE KEY_NAME = 'win_guild'");
				if(count($index)==0) {				
					$this->bot->db->query("ALTER TABLE #___tower_result ADD INDEX (win_guild)");
				}					
				$col = $this->bot->db->select("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '#___tower_result' AND COLUMN_NAME = 'win_side'");
				if(count($col)==0) {
					$this->bot->db->update_table(
						"tower_result",
						"win_side",
						"add",
						"ALTER TABLE #___tower_result ADD UNIQUE (win_side) VARCHAR(50)"
					);
				}
				$index = $this->bot->db->select("SHOW INDEX FROM #___tower_result WHERE KEY_NAME = 'win_side'");
				if(count($index)==0) {				
					$this->bot->db->query("ALTER TABLE #___tower_result ADD INDEX (win_side)");
				}				
				$col = $this->bot->db->select("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '#___tower_result' AND COLUMN_NAME = 'time'");
				if(count($col)==0) {
					$this->bot->db->update_table(
						"tower_result",
						"time",
						"add",
						"ALTER TABLE #___tower_result ADD UNIQUE (time) VARCHAR(50)"
					);
				}				
				$col = $this->bot->db->select("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '#___tower_result' AND COLUMN_NAME = 'lose_guild'");
				if(count($col)==0) {
					$this->bot->db->update_table(
						"tower_result",
						"lose_guild",
						"add",
						"ALTER TABLE #___tower_result ADD UNIQUE (lose_guild) VARCHAR(50)"
					);
				}
				$col = $this->bot->db->select("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '#___tower_result' AND COLUMN_NAME = 'lose_side'");
				if(count($col)==0) {
					$this->bot->db->update_table(
						"tower_result",
						"lose_side",
						"add",
						"ALTER TABLE #___tower_result ADD UNIQUE (lose_side) VARCHAR(50)"
					);
				}			
        }
        $this->bot->db->set_version("tower_result", 2);
        switch ($this->bot->db->get_version("tower_attack")) {
            case 1:
                echo "\nMaking sure that tower_attack table does not contain duplicate entries (same timestamp, org, side and zone.\nThis may take a few seconds on large existing tables!\n";
                $tablename = "temp_tower_attack_" . time() . "_temp";
                $this->bot->db->query(
                    "CREATE TABLE IF NOT EXISTS " . $tablename . "(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " . "time int, " . "off_guild VARCHAR(50), " . "off_side VARCHAR(10), "
                    . "off_player VARCHAR(20), " . "off_level int, " . "off_profession VARCHAR(15), " . "def_guild VARCHAR(50), " . "def_side VARCHAR(10), " . "zone VARCHAR(50), "
                    . "x_coord INT, " . "y_coord INT, " . "UNIQUE (time, off_guild, off_side, off_player, def_guild, def_side, zone, x_coord, y_coord), " . "INDEX (off_guild), "
                    . "INDEX (off_side), " . "INDEX (def_guild), " . "INDEX (def_side), " . "INDEX (zone))"
                );
                $this->bot->db->query(
                    "INSERT IGNORE INTO " . $tablename . "(time, off_guild, off_side, off_player, off_level, off_profession, def_guild, def_side, zone, " . "x_coord, y_coord) "
                    . "SELECT time, off_guild, off_side, off_player, off_level, off_profession, def_guild, def_side, zone, " . "x_coord, y_coord FROM #___tower_attack"
                );
                echo "Done with copying all unique entries!\n";
                $this->bot->db->dropTable("#___tower_attack");
                $this->bot->db->query("ALTER TABLE " . $tablename . " RENAME #___tower_attack");
        }
        $this->bot->db->set_version("tower_attack", 2);
    }


    function command_handler($name, $msg, $channel)
    {
        if (preg_match("/^battle$/i", $msg)) {
            return $this->battle_blob();
        } else {
            if (preg_match("/^victory$/i", $msg)) {
                return $this->victory_blob();
            }
        }
    }


    /*
    Makes the victory results
    */
    function victory_blob()
    {
        $battle = "##blob_title##:::: Recent Battle Results ::::##end##\n\n";
        $result = $this->bot->db->select(
            "SELECT time, win_guild as off_guild, win_side as off_side, lose_guild as def_guild,"
            . " lose_side as def_side, zone FROM #___tower_result ORDER BY time DESC LIMIT 0, " . $this->bot
                ->core("settings")
                ->get("TowerAttack", "VictoryBlobSize"),
            MYSQLI_ASSOC
        );
        if (empty($result)) {
            return "No tower victories noticed yet!";
        }
        foreach ($result as $res) {
            $battle .= "##blob_text##" . $this->format_attack_string(
                    $res,
                    $this->bot
                        ->core("settings")
                        ->get("TowerAttack", "VictoryString"),
                    true
                ) . "</font>\n\n";
        }
        return "Tower Battles Won: " . $this->bot->core("tools")
            ->make_blob("click to view", $battle);
    }


    /*
    Makes the battle results
    */
    function battle_blob()
    {
        $battle = "##blob_title##:::: Recent Tower Battles ::::##end##\n\n";
        $result = $this->bot->db->select(
            "SELECT time, off_guild, off_side, off_player, off_level, off_profession, "
            . "def_guild, def_side, zone, x_coord, y_coord FROM #___tower_attack ORDER BY time DESC LIMIT 0, " . $this->bot
                ->core("settings")
                ->get("TowerAttack", "AttacksPerBlob"),
            MYSQLI_ASSOC
        );
        if (empty($result)) {
            return "No tower attacks noticed yet!";
        }
        foreach ($result as $res) {
            if ($res["off_guild"] != "") {
                $str = $this->bot->core("settings")
                    ->get("TowerAttack", "BlobStringOrged");
            } else {
                $str = $this->bot->core("settings")
                    ->get("TowerAttack", "BlobStringUnorged");
            }
            $battle .= "##blob_text##" . $this->format_attack_string($res, $str) . "</font>\n\n";
        }
        return "Tower Battles: " . $this->bot->core("tools")
            ->make_blob("click to view", $battle);
    }

    /*
    This gets called to relay spotted messages below
    */
    function relay_msg($msg)
	{
		if ($this->bot->exists_module("discord")&&$this->bot->core("settings")->get("TowerAttack", "AlertDisc")) {
			if($this->bot->core("settings")->get("TowerAttack", "DiscChanId")) { $chan = $this->bot->core("settings")->get("TowerAttack", "DiscChanId"); } else { $chan = ""; }
			if($this->bot->core("settings")->get("TowerAttack", "DiscTag")) { $dctag = $this->bot->core("settings")->get("TowerAttack", "DiscTag")." "; } else { $dctag = ""; }
			$this->bot->core("discord")->disc_alert($dctag.$msg, $chan);
		}
		if ($this->bot->exists_module("irc")&&$this->bot->core("settings")->get("TowerAttack", "AlertIrc")) {
			$this->bot->core("irc")->send_irc("", "", $msg);
		}		
	}

    /*
    This gets called on a msg in the group
    */
    function gmsg($name, $group, $msg)
    {
        $attack = false;
        $victory = false;
        if (preg_match(
            "/The (clan|neutral|omni) organization (.+) just entered a state of war! (.+) attacked the (clan|neutral|omni) organization (.+)'s tower in (.+) at location \(([0-9]+), ([0-9]+)\)/i",
            $msg,
            $info
        )
        ) {
			$this->relay_msg($msg);
            $infos["off_guild"] = $info[2];
            $infos["off_side"] = ucfirst(strtolower($info[1]));
            $infos["off_player"] = $info[3];
            $infos["def_guild"] = $info[5];
            $infos["def_side"] = ucfirst(strtolower($info[4]));
            $infos["zone"] = $info[6];
            $infos["x_coord"] = $info[7];
            $infos["y_coord"] = $info[8];
            $attack = true;
        } else {
            if (preg_match(
                "/(.+) just attacked the (clan|neutral|omni) organization (.+)'s tower in (.+) at location \(([0-9]+), ([0-9]+)\)/i",
                $msg,
                $info
            )
            ) {
				$this->relay_msg($msg);
                $infos["off_guild"] = "";
                $infos["off_side"] = "";
                $infos["off_player"] = $info[1];
                $infos["def_guild"] = $info[3];
                $infos["def_side"] = ucfirst(strtolower($info[2]));
                $infos["zone"] = $info[4];
                $infos["x_coord"] = $info[5];
                $infos["y_coord"] = $info[6];
                $attack = true;
            } else {
                if (preg_match(
                    "/(.+) (Clan|Omni|Neutral) organization (.+) attacked the (Clan|Omni|Neutral) (.+) at their base in (.+). The attackers won!!/i",
                    $msg,
                    $info
                )
                ) {
					$this->relay_msg($msg);
                    if (!$this->bot->core("settings")->get("TowerAttack", "ReadOnly")) {
                        $this->bot->db->query(
                            "INSERT INTO #___tower_result (time, win_guild, win_side, lose_guild, " . "lose_side, zone) VALUES ('" . time(
                            ) . "', '" . mysqli_real_escape_string($this->bot->db->CONN,
                                $info[3]
                            )
                            . "', '" . $info[2] . "', '" . mysqli_real_escape_string($this->bot->db->CONN,
                                $info[5]
                            ) . "', '" . $info[4] . "', '" . $info[6] . "')"
                        );
                    }
                } else {
                    if (preg_match(
                        "/The tower (.+) in (.+) was just reduced to (.+) % health by (.+) from the (.+) organization!$/i",
                        $msg,
                        $info
                    )
                    ) {
						$this->relay_msg($msg);
                        $this->relay_tower_damage($info[1], $info[2], $info[3], $info[4], $info[5]);
                    } else {
                        if (preg_match(
                            "/The tower (.+) in (.+) was just reduced to (.+) % health by (.+)!$/i",
                            $msg,
                            $info
                        )
                        ) {
							$this->relay_msg($msg);
                            $this->relay_tower_damage($info[1], $info[2], $info[3], $info[4]);
                        } else {
                            if (preg_match(
                                "/The tower (.+) in (.+) was just reduced to (.+) % health!$/i",
                                $msg,
                                $info
                            )
                            ) {
								$this->relay_msg($msg);
                                $this->relay_tower_damage($info[1], $info[2], $info[3]);
                            }
                        }
                    }
                }
            }
        }
        if ($attack) {
            $infos["time"] = time();
            $player = $this->bot->core("whois")
                ->lookup($infos["off_player"]);
            if ($player instanceof BotError) {
                $player = array(
                    'level' => '0',
                    'profession' => 'Unknown',
                    'off_side' => 'Unknown'
                );
            } else {
                if (empty($player["level"])) {
                    $player["level"] = '0';
                }
                if (empty($player["profession"])) {
                    $player["profession"] = 'Unknown';
                }
                if (empty($infos["off_side"])) {
                    $infos["off_side"] = $player["faction"];
                }
            }
            if (!isset($this->suppress[$infos["def_guild"]])) {
                $this->suppress[$infos["def_guild"]] = 0;
            }
            $infos["off_level"] = $player["level"];
            $infos["off_profession"] = $player["profession"];
            if ($this->bot->core("settings")->get("TowerAttack", "Spam")) {
                $spamtime = $this->bot->core("settings")
                    ->get("TowerAttack", "SuppressTime");
                if ($this->bot->core("settings")
                        ->get("TowerAttack", "SpamPrevention")
                    && ($this->suppress[$infos["def_guild"]] + $spamtime) > time()
                ) {
                    $this->suppressdata[$infos["def_guild"]][] = $infos;
                    $this->suppress[$infos["def_guild"]] = time();
                } else {
                    if ($infos["off_guild"] != "") {
                        $msg = $this->format_attack_string(
                            $infos,
                            $this->bot
                                ->core("settings")
                                ->get("TowerAttack", "AttackStringOrged")
                        );
                    } else {
                        $msg = $this->format_attack_string(
                            $infos,
                            $this->bot
                                ->core("settings")
                                ->get("TowerAttack", "AttackStringUnOrged")
                        );
                    }
                    $this->suppress[$infos["def_guild"]] = time();
                    $this->bot->send_output(
                        "",
                        $msg,
                        $this->bot
                            ->core("settings")->get("TowerAttack", "SpamTo")
                    );
                }
            }
            if (!($this->bot->core("settings")->get("TowerAttack", "ReadOnly"))
            ) {
                $this->bot->db->query(
                    "INSERT INTO #___tower_attack (time, off_guild, off_side, off_player, " . "off_level, off_profession, def_guild, def_side, zone, x_coord, y_coord) VALUES ('"
                    . $infos["time"] . "', '" . mysqli_real_escape_string($this->bot->db->CONN,
                        $infos["off_guild"]
                    ) . "', '" . $infos["off_side"] . "', '" . $infos["off_player"] . "', '"
                    . $infos["off_level"] . "', '" . $infos["off_profession"] . "', '" . mysqli_real_escape_string($this->bot->db->CONN,
                        $infos["def_guild"]
                    ) . "', '" . $infos["def_side"] . "', '"
                    . $infos["zone"] . "', '" . $infos["x_coord"] . "', '" . $infos["y_coord"] . "')"
                );
            }
        }
    }


    // Gets LCA Area info from LCA Table
    function get_lcainfo($zone, $x, $y)
    {
        $rad = 290; //Tower Attack Radius for LCA Table Search
        $glca = $this->bot->db->select(
            "SELECT * FROM #___land_control_zones WHERE area = '" . $zone . "'
		AND x BETWEEN " . $x . "-" . $rad . " and " . $x . "+" . $rad . "
		AND y BETWEEN " . $y . "-" . $rad . " and " . $y . "+" . $rad
        );
        if (!empty($glca)) {
            $lca["pid"] = $glca[0][4];
            $lca["lrng"] = $glca[0][1];
            $lca["hrng"] = $glca[0][2];
            $lca["area"] = $glca[0][3];
            $lca["x"] = $glca[0][5];
            $lca["y"] = $glca[0][6];
            $lca["name"] = $glca[0][7];
        } else {
            $lca["pid"] = '??';
            $lca["name"] = '??';
            $lca["hrng"] = 'Unknown';
        }
        return $lca;
    }


    // Formats the attack spam, using the array $infos to replace tags in $string.
    // Each field name in the array can be used as a tag encased in #! !# to be replaced with the info.
    // The additional tag #!blob!# can be used to add a blob containing all important information about the attack.
    // The tag #!lca_num!# can be used to enter the number on huge map, the tags #!lca_name!#, #!lca_minlevel!#, #!lca_maxlevel!#
    // can be used for additional information about the attacked tower site.
    // List of the fields in the infos[] array: time, off_guild, off_side, off_player, off_level, off_profession, def_guild,
    // def_side, zone, x_coord, y_coord. time is expected as unix timestamp, meaning seconds since unix 0 time. It is gmdate'd.
    // off_guild, off_player iand def_guild are colorized using off_side information.
    // #!br!# can be used to add linebreaks to the output.
    function format_attack_string($infos, $string, $victory = false)
    {
        if ($victory) {
            $infos["lca_num"] = "";
            $infos["lca_name"] = "";
            $infos["lca_minlevel"] = "";
            $infos["lca_maxlevel"] = "";
            $infos["blob"] = "";
        } else {
            $lca = $this->get_lcainfo($infos["zone"], $infos["x_coord"], $infos["y_coord"]);
            $infos["lca_num"] = "##red##x" . $lca['pid'] . "</font>";
            $infos["lca_name"] = $lca["name"];
            $infos["lca_minlevel"] = $lca["lrng"];
            $infos["lca_maxlevel"] = $lca["hrng"];
            $prtlca = "LCA: " . $infos["lca_num"] . "##normal## - " . $lca['name'] . "##end## ##highlight##(L " . $lca['lrng'] . " - " . $lca['hrng'] . ")##end##\n";
            $battle = "##blob_title##" . $infos["zone"] . " (" . $infos["x_coord"] . "x" . $infos["y_coord"] . ")##end##\n";
            $battle .= $prtlca;
            $battle .= "Attacker: ##" . $infos["off_side"] . "##" . $infos["off_player"] . " ##end##(" . $infos["off_level"];
            $battle .= " " . $infos["off_profession"] . ")\n";
            if (!empty($infos["off_guild"])) {
                $battle .= "Attacking Guild: ##" . $infos["off_side"] . "##" . $infos["off_guild"] . "##end##\n";
            } else {
				$infos["off_guild"] = "Unknown";
			}
            $battle .= "Defending Guild: ##" . $infos["def_side"] . "##" . $infos["def_guild"] . "##end##";
            $infos["blob"] = $this->bot->core("tools")
                ->make_blob("More", $battle);
        }
		if(!isset($infos["off_player"])) $infos["off_player"] = "Unknown";
        $infos["br"] = "\n";
        $infos["time"] = gmdate(
            $this->bot->core("settings")
                ->get("Time", "FormatString"),
            $infos["time"]
        );
        if ($infos["off_side"] == "") {
            $who = $this->bot->core("whois")->lookup($infos["off_player"]);
            if ($who instanceof BotError) {
                $infos["off_side"] = "error";
            } else {
                $infos["off_side"] = $who["faction"];
            }
        }
        $infos["off_guild"] = "##" . $infos["off_side"] . "##" . $infos["off_guild"] . "</font>";
        $infos["off_player"] = "##" . $infos["off_side"] . "##" . $infos["off_player"] . "</font>";
        $infos["def_guild"] = "##" . $infos["def_side"] . "##" . $infos["def_guild"] . "</font>";
        foreach ($infos as $key => $value) {
            $string = str_ireplace("#!" . $key . "!#", $value, $string);
        }
        return $string;
    }


    function cron()
    {
        if (!empty($this->suppress)) {
            foreach ($this->suppress as $def_guild => $time) {
                $spamtime = $this->bot->core("settings")
                        ->get("TowerAttack", "SuppressTime") + 5;
                if (($time + $spamtime) < time()) {
                    unset($this->suppress[$def_guild]);
                    if (!empty($this->suppressdata[$def_guild])) {
                        $inside = "##blob_title##..:: Suppressed Attacks ::..\n";
                        $inside .= "##" . $this->suppressdata[$def_guild][0]["def_side"] . "##" . $def_guild . " ##end##in ";
                        $inside .= $this->suppressdata[$def_guild][0]["zone"] . " at " . $this->suppressdata[$def_guild][0]["x_coord"];
                        $inside .= "x" . $this->suppressdata[$def_guild][0]["y_coord"] . "##end####blob_text##\n";
                        $lca = $this->get_lcainfo(
                            $this->suppressdata[$def_guild][0]["zone"],
                            $this->suppressdata[$def_guild][0]["x_coord"],
                            $this->suppressdata[$def_guild][0]["y_coord"]
                        );
                        $inside .= "##white##LCA: ##red##x" . $lca['pid'] . "##end####normal## - " . $lca['name'] . "##end## ##highlight##(L " . $lca['lrng'] . " - " . $lca['hrng']
                            . ")##end##\n\n";
                        foreach ($this->suppressdata[$def_guild] as $data) {
                            if (!empty($data["off_guild"])) {
                                $inside .= "Attacker: ##" . $data["off_side"] . "##" . $data["off_player"] . "##end## (##" . $data["off_side"] . "##" . $data["off_guild"]
                                    . "##end##, level " . $data["off_level"] . " " . $data["off_profession"] . ")\n";
                            } else {
                                $inside .= "Attacker: ##" . $data["off_side"] . "##" . $data["off_player"] . "##end## (Level " . $data["off_level"] . " " . $data["off_profession"]
                                    . ")\n";
                            }
                        }
                        $msg = "Suppressed Attacks on ##" . $this->suppressdata[$def_guild][0]["def_side"] . "##" . $def_guild . "##end## :: " . $this->bot
                                ->core("tools")
                                ->make_blob("click to view", $inside);
                        $this->bot->send_output(
                            "",
                            $msg,
                            $this->bot
                                ->core("settings")->get("TowerAttack", "SpamTo")
                        );
                        unset($this->suppressdata[$def_guild]);
                    }
                }
            }
        }
    }


    function relay_tower_damage(
        $tower,
        $zone,
        $health,
        $attacker = "",
        $org = ""
    ) {
        if (strtolower(
                $this->bot->core("settings")
                    ->get("TowerAttack", "RelayTowerDamage")
            ) == "none"
        ) {
            return;
        }
        $msg = "The tower##highlight## " . $tower . "##end## in##highlight## " . $zone;
        $msg .= "##end## was just reduced to##highlight## " . $health . "##end## % health";
        if ($attacker != "") {
            $who = $this->bot->core("whois")->lookup($attacker);
            if ($who instanceof BotError) {
                $msg .= " by##Unknown## " . $attacker . "##end##";
            } else {
                $msg .= " by##" . $who['faction'] . "## " . $attacker . "##end##";
                if ($org != "") {
                    $msg .= " (##" . $who['faction'] . "##" . $org . "##end##)";
                }
            }
        }
        $msg .= "!";
        $msg = "##highlight##[" . $this->bot->core("shortcuts")
                ->get_short($this->bot->guildname) . "]##end## " . $msg;
        if (strtolower(
                $this->bot->core("settings")
                    ->get("TowerAttack", "RelayTowerDamage")
            ) == "both"
            || strtolower(
                $this->bot
                    ->core("settings")
                    ->get("TowerAttack", "RelayTowerDamage")
            ) == "relay"
        ) {
            $this->bot->core("relay")->relay_to_bot($msg);
        }
        if (strtolower(
                $this->bot->core("settings")
                    ->get("TowerAttack", "RelayTowerDamage")
            ) == "both"
            || strtolower(
                $this->bot
                    ->core("settings")
                    ->get("TowerAttack", "RelayTowerDamage")
            ) == "pgroup"
        ) {
            $this->bot->send_pgroup($msg);
        }
    }
}

?>
