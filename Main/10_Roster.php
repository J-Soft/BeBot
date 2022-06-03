<?php
/*
* Roster.php - Handle updating the members list
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
$roster_core = new Roster_Core($bot);
class Roster_Core extends BasePassiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("users", "true") . "
					(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					char_id BIGINT NOT NULL UNIQUE,
					nickname VARCHAR(25) UNIQUE,
					password VARCHAR(64),
					password_salt VARCHAR(5),
					last_seen INT(11) DEFAULT '0',
					last_raid INT(11) DEFAULT '0',
					added_by VARCHAR(25),
					added_at INT(11) DEFAULT '0',
					deleted_by VARCHAR(25),
					deleted_at INT(11),
					banned_by VARCHAR(25),
					banned_at INT(11),
					banned_for VARCHAR(100),
					banned_until INT(11) DEFAULT '0',
					notify INT(1) DEFAULT '0',
					user_level INT(1) DEFAULT '0',
					updated_at INT(11) DEFAULT '0',
					INDEX user_level (user_level),
					INDEX banned_until (banned_until),
					INDEX notify (notify))"
        );
        $this->register_module("roster_core");
        if ($bot->guildbot) {
            $this->register_event("gmsg", "Org Msg");
        }
        $this->register_event("cron", "1hour");
        $this->update_table();
        $this->bot->core("settings")
            ->create("Members", "LastRosterUpdate", 1, "Last time we completed a Roster update", null, true, 2);
        $this->bot->core("settings")
            ->create(
                'Members',
                'Roster',
                'XML',
                'What should we use to look up organization information? (Fallback means that if XML fails the cache will be used)',
                'XML;WhoisCache;Fallback'
            );
        $this->bot->core("settings")
            ->create("Members", "Update", true, "Should the roster be updated automaticly?");
        $this->bot->core("settings")
            ->create("Members", "QuietUpdate", false, "Do roster update quietly without spamming the guild channel?");
		$this->bot->core("settings")->create("Members", "MembersUrl", "http://people.anarchy-online.com", "What is HTTP(s) Members interface  URL (FC official by default, or your prefered mirror) ?");
        $this->bot->core("settings")
            ->create(
                "Roster",
                "AllowedTime",
                24,
                "Is there an exclusive hour (in server local time, from 0 to 23) you'd prefer for the bot autoupdate (use 24 otherwise to disable and allow any hour)?",
                "0;1;2;3;4;5;6;7;8;9;10;11;12;13;14;15;16;17;18;19;20;21;22;23;24"
            );
        $this->bot->core("settings")
            ->create(
                "Roster",
                "RemoveDelay",
                7,
                "After how many days should non-updatable & non-notified buddies be considered okay to delete?",
                "2;7;15;30;90;180;365"
            );				
        $this->startup = true;
        $this->running = false;
    }


    function update_table()
    {
        if ($this->bot->db->get_version("users") == 7) {
            return;
        }
        if ($this->bot->core("settings")->exists("members", "schemaversion")) {
            $this->bot->db->set_version(
                "users",
                $this->bot->core("settings")
                    ->get("members", "schemaversion")
            );
            $this->bot->core("settings")->del("members", "schemaversion");
        }
        switch ($this->bot->db->get_version("users")) {
            case 1:
                $this->bot->db->update_table(
                    "users",
                    array(
                         "banned_for",
                         "banned_until"
                    ),
                    "add",
                    "ALTER TABLE #___users ADD banned_for VARCHAR(100) AFTER banned_at, ADD banned_until INT(11) DEFAULT '0' AFTER banned_for"
                );
                $this->bot->db->set_version("users", 2);
                $this->update_table();
                return;
            case 2:
                $this->bot->db->update_table(
                    "users",
                    array(
                         "user_level",
                         "banned_until",
                         "notify"
                    ),
                    "alter",
                    "ALTER TABLE #___users ADD INDEX (user_level), ADD INDEX (banned_until), ADD INDEX (notify)"
                );
                $this->bot->db->set_version("users", 3);
                $this->update_table();
                return;
            case 3:
                $this->bot->db->update_table(
                    'users',
                    array(
                         'receive_announce',
                         'receive_invite',
                         'admin_level'
                    ),
                    'drop',
                    "ALTER TABLE #___users DROP receive_announce, DROP receive_invite, DROP admin_level"
                );
                $this->bot->db->set_version("users", 4);
                $this->update_table();
                return;
            case 4:
                if ($this->bot->core('prefs')
                    ->exists('AutoInv', 'receive_auto_invite')
                ) {
                    $fields = $this->bot->db->select("EXPLAIN #___users", MYSQLI_ASSOC);
                    if (!empty($fields)) {
                        foreach ($fields as $field) {
                            $columns[$field['Field']] = true;
                        }
                    }
                    if (isset($columns['auto_invite'])) {
                        $invited_users = $this->bot->db->select(
                            'SELECT char_id FROM #___users WHERE auto_invite=1',
                            MYSQLI_ASSOC
                        );
                        if (!empty($invited_users)) {
                            foreach ($invited_users as $invited_user) {
                                $this->bot->core('prefs')
                                    ->change($invited_user['char_id'], 'AutoInv', 'receive_auto_invite', 'On');
                            }
                        }
                        $this->bot->db->update_table(
                            'users',
                            array('auto_invite'),
                            'drop',
                            "ALTER TABLE #___users DROP auto_invite"
                        );
                    }
                    $this->bot->db->set_version("users", 5);
                } else {
                    // We have to delay any further updates until we can correctly update the autoinvite fields!
                    return;
                }
                $this->bot->db->set_version("users", 5);
                $this->update_table();
                return;
            case 5:
                // update pref default and remove old settings, useing user scheme since it used to be in user table
                if ($this->bot->core("settings")
                    ->exists("members", "Receiveannounce")
                ) {
                    if ($this->bot->core("settings")
                        ->get("members", "Receiveannounce")
                    ) {
                        $set = "On";
                    } else {
                        $set = "Off";
                    }
                    $this->bot->core("prefs")
                        ->change_default("Roster Module", "MassMsg", "receive_message", $set);
                    $this->bot->core("settings")
                        ->del("members", "Receiveannounce");
                }
                if ($this->bot->core("settings")
                    ->exists("members", "Receiveinvite")
                ) {
                    if ($this->bot->core("settings")
                        ->get("members", "Receiveinvite")
                    ) {
                        $set = "On";
                    } else {
                        $set = "Off";
                    }
                    $this->bot->core("prefs")
                        ->change_default("Roster Module", "MassMsg", "receive_invite", $set);
                    $this->bot->core("settings")
                        ->del("members", "Receiveinvite");
                }
                $this->bot->db->set_version("users", 6);
                $this->update_table();
                return;
            case 6:
                $this->bot->db->update_table(
                    "users",
                    "char_id",
                    "alter",
                    "ALTER TABLE #___users MODIFY char_id BIGINT NOT NULL"
                );
                $this->bot->db->set_version("users", 7);
                $this->update_table();
                return;
            default:
        }
    }


    function gmsg($name, $group, $msg)
    {
		if (preg_match("/(.+) kicked (.+) from the organization./i", $msg, $info)) {
			$person = $info[2];
			$source = $info[1];
			$id = $this->bot->core("player")->id($person);
			$this->del("Org Message", $id, $person, "from Org Message, kicked by $source");
			$this->bot->send_gc(
				"##highlight##$person ##end##has been kicked from the org by##highlight## $source##end##"
			);
		} else {
			if (preg_match("/(.+) has left the organization./i", $msg, $info)) {
				$person = $info[1];
				$id = $this->bot->core("player")->id($person);
				$this->del("Org Message", $id, $person, "from Org Message");
				$this->bot->send_gc("##highlight##$person ##end##has left the org");
			} else {
				if (preg_match("/(.+) invited (.+) to your organization./i", $msg, $info)) {
					$inviter = $info[1];
					$person = $info[2];
					$id = $this->bot->core("player")->id($person);
					$this->add("Org Message", $id, $person, $inviter);
					$this->bot->send_gc("Welcome##highlight## $person##end##!!!");
				} else {
					if (preg_match("/(.+) kicked from organization \(alignment changed\)./i", $msg, $info)) {
						$person = $info[1];
						$id = $this->bot->core("player")->id($person);
						$this->del("Org Message", $id, $person, "from Side Message");
						$this->bot->send_gc("##highlight##$person ##end##has sideleft the org");
					}
				}					
			}
		}
    }


    /*
    This gets called on cron
    */
    function cron()
    {
        if (!$this->bot->core("settings")->get("members", "Update")) {
            Return;
        }
        if ($this->bot->guildbot) {
            $this->update_guild();
        } else {
			if($this->bot->exists_module("raid") && $this->bot->core("raid")->raid) {
				$this->bot->log("ROSTER", "UPDATE", "Skipping update as raid is running.");
				Return;
			} else {
				$this->update_raid();
			}
        }
    }


    function update_guild($force = false)
    {
		$upid = array(); $denot = array();
		$members = array(); $db_member = array();
		if($force) { $mode="forced"; } else { $mode="auto"; }
		$this->lastrun = $this->bot->core("settings")->get("members", "LastRosterUpdate");
		$allowedt = $this->bot->core("settings")->get("Roster", "AllowedTime");		
		if (isset($allowedt)&&$allowedt!=''&&is_numeric($allowedt)&&$allowedt>=0&&$allowedt<=23&&!$force&&$allowedt!=date('G')) {
			$this->bot->log("ROSTER", "UPDATE", "Roster org update cancelled as now (".date('G').") is not allowed time (".$allowedt.")!");
			Return;
		} elseif ($this->running) {
			$this->bot->log("ROSTER", "UPDATE", "Roster org was already running, cancelled!");
            if (!$this->bot->core("settings")->get("Members", "QuietUpdate")) {
                $this->bot->send_gc("Roster org update was already running, cancelled.");
            }
			Return;
        } elseif (($this->lastrun + 21600) >= time() && $force == false) {
            $this->bot->log("ROSTER", "UPDATE", "Roster update ran less than 6 hours ago, skipping!");
			Return;	
        } else {
			$this->running = true;
			$this->bot->log(
				"ROSTER",
				"UPDATE",
				"Starting roster ".$mode." update for guild id: " . $this->bot->guildid . " on dim: " . $this->bot->dimension
			);
			if (!$this->bot->core("settings")->get("Members", "QuietUpdate")) {
				$this->bot->send_gc("##normal##Roster Org ".$mode." update starting ::: System busy##end##");
			}
			// Get the guild roster
			if (strtolower($this->bot->game) == 'ao') {
				$dimension = $this->bot->dimension;
				switch (strtolower($dimension)) {
					case "testlive":
					case "aotestproxy":
						$dimension = "0";
						break;
					case "rubi-ka";
					case "aoliveproxy":
						$dimension = "5";
						break;
					case "rubi-ka-2019":
					case "aork19proxy":
						$dimension = "6";
						break;
				}
				$members = $this->parse_org($dimension, $this->bot->guildid);
			}
			if(!is_array($members)) {
				$this->bot->log("ROSTER", "ERROR", "XML obtained is broken ... stopping update.");
				Return;
			} elseif (count($members) > 1 || strtolower($this->bot->game) == 'aoc') {
				$buddies = $this->bot->aoc->buddies;
				$this->added = 0;
				$this->removed = 0;
				$this->rerolled = 0;
				$db_members = array();
				$db_members_sql = $this->bot->db->select("SELECT char_id, nickname, user_level, updated_at FROM #___users");
				if (!empty($db_members_sql)) {
					foreach ($db_members_sql as $db_member) {
						$db_members[$db_member[1]] = $db_member;
					}
				}
				unset($db_members_sql);
				if (strtolower($this->bot->game) == 'ao') { // wrong for new bots : && count($db_members) > 0
					/*
					Go through all members and make sure we are up to date.
					*/
					foreach ($members as $member) {
						if(isset($member["nickname"]) && isset($db_members[$member["nickname"]])) $db_member = $db_members[$member["nickname"]];
						else $db_member = array();
						/*
						If we dont have this user in the user table, or if its a guest, or if its a deleted character we have no updates for over few days on,
						its a new member we havent picked up for some reason.
						*/
						$rdelay = $this->bot->core("settings")->get("Roster", "RemoveDelay")*86400;
						if (empty($db_member))
						{
							$this -> add("Roster-XML", $member["id"], $member["nickname"], "from XML");
							$this -> added++;
						}
						else if ($db_member[2] == 1 || ($db_member[2] == 0 && (($db_member[3] + $rdelay) <= time())))
						{
							$this -> add("Roster-XML", $member["id"], $member["nickname"], "from XML");
							$this -> added++;
						}

						/*
						We have an entry for the nickname, but the character id's have changed, rerolled character.
						*/
						else if ($db_member[0] != $member["id"])
						{
							if($member["id"] == "0" || $member["id"] == "-1" || $member["id"] == "" || $member["id"] == NULL || empty($member["id"]) || strlen($id) < 5)
							{
								$this -> bot -> log("ROSTER", "ID", "Get ID Failed for $name (ID: ".$member["id"].")");
							}
							else
							{
								$this -> erase("Roster-XML", $db_member[0], $member["nickname"], "char_id mismatch (ID: " . $db_member[0] . ")");
								$this -> removed++;

								if ($this -> bot -> guildid == $member["org_id"])
								{
									$this -> add("Roster-XML-Reroll", $member["id"], $member["nickname"], "after reroll (ID: " . $member["id"] . ")");
									$this -> added++;

									$this -> rerolled++;
								}
							}
						}
						/*
						Make sure we have an entry in the whois cache for the character.
						*/
						$this->bot->core("whois")->update($member);
						/*
						Make sure the user is on the buddylist.
						*/
						$this->bot->core("chat")->buddy_add($member["nickname"]);
						/*
						Update the timestamp, but only if its a member.
						*/
						if (!empty($db_member) && $db_member[2] <= 2) {
							$upid[$member["id"]] = true;
						}
						/*
						Make sure we don't delete the member in the final step
						*/
						if (isset($buddies[$member["id"]])) {
							unset($buddies[$member["id"]]);
						}
						if (isset($db_members[$member["nickname"]])) {
							unset($db_members[$member["nickname"]]);
						}
					}
					//check  DB members not on Roster
					if (!empty($db_members)) {
						foreach ($db_members as $dbmember) {
							if ($dbmember[2] < 2) {
								continue;
							}
							/*
							Catch newly added members and give them their first update timestamp
							*/
							if ($dbmember[3] == 0) {
								$upid[$dbmember[0]] = true;
							}
							/*
							If we still have no updates for this member after few days, remove.
							*/
							$rdelay = $this->bot->core("settings")->get("Roster", "RemoveDelay")*86400;
							if ((($dbmember[3] + $rdelay) <= time()) && ($dbmember[3] != 0)) {
								$this->del("Roster-XML", $dbmember[0], $dbmember[1], "removed");
								$this->removed++;
							} else {
								$this->bot->log(
									"ROSTER",
									"INFO",
									$dbmember[1] . " is in members table but appears to not to be in XML, skipping removal for now"
								);
							}
							unset($buddies[$dbmember[0]]);
						}
					}
				}
				/*
				Run through our notifylist to make sure they are on the buddylist - and stay there
				*/
				$guests = $this->bot->db->select("SELECT char_id, nickname FROM #___users WHERE notify = '1'");
				if (!empty($guests)) {
					foreach ($guests as $guest) {
						/*
						Make sure the user is on the buddylist.
						*/
						$this->bot->core("chat")->buddy_add($guest[0]);
						/*
						Make sure we don't delete the guest in the final step
						*/
						unset($buddies[$guest[0]]);
					}
				}
				/*
				Cycle through anything still on our buddylist
				*/
				if (!empty($buddies)) {
					foreach ($buddies as $id => $value) {
						$name = $this->bot->core("player")->name($id);
						$member = $this->bot->db->select(
							"SELECT char_id, user_level, updated_at FROM #___users WHERE char_id = '" . $id . "' AND user_level >= '2'"
						);
						if (!empty($member)&&!($name instanceof BotError)) {
							/*
							Catch newly added members and give them their first update timestamp
							*/
							if ($member[0][2] == 0) {
								$upid[$id] = true;
							}
							/*
							If we still have no updates for this member after few days, remove.
							*/
							$rdelay = $this->bot->core("settings")->get("Roster", "RemoveDelay")*86400;
							if ((($member[0][2] + $rdelay) <= time()) && ($member[0][2] != 0)) {
								$this->del("Roster-XML", $id, $name, "removed");
								$this->removed++;
							} else {
								$this->bot->log(
									"ROSTER",
									"INFO",
									"$name is in members table but appears to not to be in XML, skipping removal for now"
								);
							}
						} else {
							$this->bot->core("chat")->buddy_remove($id);
						}
					}
				}
				$members = $this->bot->db->select(
					"SELECT char_id, nickname, user_level, notify, updated_at, added_by FROM #___users ORDER BY nickname"
				);
				if (!empty($members)) {
					foreach ($members as $member) {
						$id = $this->bot->core("player")->id($member[1]);
						/*
						Make sure we have an entry in the whois cache for the character.
						*/
						$whois = $this->bot->core("whois")
							->lookup($member[1], false, true);
						if (strtolower($this->bot->game) == 'ao') {
							/*
							Catch deleted characters.
							*/
							/*
							If we still have no updates for this member after few days, remove.
							*/
							if ($id instanceof BotError) {
								$rdelay = $this->bot->core("settings")->get("Roster", "RemoveDelay")*86400;
								if ((($member[4] + $rdelay) <= time()) && ($member[4] != 0)) {
									$this->erase(
										"Roster",
										$member[0],
										$member[1],
										"as the character appears to have been deleted."
									);
									$this->removed++;
									continue;
								} else {
									$this->bot->log(
										"ROSTER",
										"INFO",
										$member[1] . " is in members table but appears to not to be in XML, skipping removal for now"
									);
								}
							}
							if ($whois instanceof BotError) {
								Continue; // prob shouldnt skip this but it will stop the crashing
							} /*
							Catch rerolled characters.
							*/
							else {
								if ($id != $member[0] && $member[2] >= 1) {
									if ($id == "0" || $id == "-1" || $id == "" || $id == null || empty($id) || strlen(
											$id
										) < 5
									) {
										$this->bot->log("ROSTER", "ID", "Get ID Failed for $name (ID: " . $id . ")");
									}
									$this->erase(
										"Roster",
										$member[0],
										$member[1],
										"as the character appears to have been rerolled. Old: $member[0] New: $id"
									);
									$this->removed++;
									continue;
								} /*
								Catch characters who are no longer in the org.
								*/
								else {
									if (!($whois instanceof BotError) && $whois["org_id"] != $this->bot->guildid && $member[2] >= 1
										&& ($member[5] == "Roster-XML" || $member[5] == "Roster-XML-Reroll" || $member[5] == "Org Message")
									) {
										/*
										If we still have no updates for this member after few days, remove.
										*/
										$rdelay = $this->bot->core("settings")->get("Roster", "RemoveDelay")*86400;
										if ((($member[4] + $rdelay) <= time()) && ($member[4] != 0)) {
											$this->del("Roster-XML", $member[0], $member[1], "removed");
											$this->removed++;
											continue;
										} else {
											$this->bot->log(
												"ROSTER",
												"INFO",
												$member[1] . " is in members table but appears to not to be in XML, skipping removal for now"
											);
										}
									} /*
									If not we just run through the paces and make sure everything is in order.
									*/
									else {
										if ($member[3] == 1 && $member[2] >= 1) {
											/*
											Make sure all on characters on notify list are in buddy list
											*/
											$this->bot->core("chat")->buddy_add($id);
										}
									}
								}
							}
						}
					}
				}
				//make sure all users are on buddy list
				$buddylist = $this->bot->db->select("SELECT nickname FROM #___users WHERE notify = 1");
				if (!empty($buddylist)) {
					foreach ($buddylist as $user) {
						// Do some sanity checking since funcom has broken chatserver
						$uid = $this->bot->core("player")->id($user[0]);
						if ($uid instanceof BotError) {
							$denot[$user[0]] = true;
						} else if (!$this->bot->core("chat")->buddy_exists($user[0])) {
							$this->bot->core("chat")->buddy_add($user[0]);
						}
					}
				}
				$msg = "";
				if ($this->added > 0) {
					$msg .= "::: Added " . $this->added . " members ";
				}
				if ($this->removed > 0) {
					$msg .= "::: Removed " . $this->removed . " members ";
				}
				if ($this->rerolled > 0) {
					$msg .= "::: " . $this->rerolled . " members was found to have rerolled ";
				}
				if (count($upid)>0) {
					$ciin = "";
					foreach ($upid AS $key => $val) {
						$ciin .= $key.",";
					}		
					$ciin = substr($ciin, 0, -1);
					$this->bot->db->query(
						"UPDATE #___users SET updated_at = '" . time() . "' WHERE char_id IN (" . $ciin . ")"
					);
				}
				if (count($denot)>0) {
					$nnin = "";
					foreach ($denot AS $key => $val) {
						$nnin .= "'".$key."',";
					}				
					$nnin = substr($nnin, 0, -1);				
					$this->bot->db->query("UPDATE #___users SET notify = 0 WHERE nickname IN (" . $nnin . ")");
				}
				$this->bot->core("settings")->save("members", "LastRosterUpdate", time());
				$this->bot->log("ROSTER", "UPDATE", "Roster update complete. $msg", true);
				if (!$this->bot->core("settings")->get("Members", "QuietUpdate")) {
					$this->bot->send_gc("##normal##Roster update completed. $msg ##end##");
				}
			} else {
				$this->bot->log("ROSTER", "UPDATE", "Roster update failed. Funcom XML returned 0 members.", true);
				if (!$this->bot->core("settings")->get("Members", "QuietUpdate")) {
					$this->bot->send_gc("##normal##Roster update failed! Funcom XML returned 0 members ##end##");
				}
			}
		}
        $this->bot->core("notify")->update_cache();
        $this->running = false;
    }


    function update_raid($force = false)
    {
		$msg = ""; $upid = array();
		$allowedt = $this->bot->core("settings")->get("Roster", "AllowedTime");
		$this->lastrun = $this->bot->core("settings")->get("members", "LastRosterUpdate");
		if($force) { $mode="forced"; } else { $mode="auto"; }
		if (isset($allowedt)&&$allowedt!=''&&is_numeric($allowedt)&&$allowedt>=0&&$allowedt<=23&&!$force&&$allowedt!=date('G')) {
			$this->bot->log("ROSTER", "UPDATE", "Roster raid update cancelled as now (".date('G').") is not allowed time (".$allowedt.")!");
			Return;
		} elseif ($this->running) {
			$this->bot->log("ROSTER", "UPDATE", "Roster raid update was already running, cancelling!");
            if (!$this->bot->core("settings")->get("Members", "QuietUpdate")) {
                $this->bot->send_pgroup("Roster raid update was already running, cancelling.");
            }
			Return;
        } elseif (($this->lastrun + (60 * 60 * 6)) >= time() && $force == false) {
            $this->bot->log("ROSTER", "UPDATE", "Roster update ran less than 6 hours ago, skipping!");
			Return;
        } else {
			$this->running = true;			
            $this->bot->log("ROSTER", "UPDATE", "Starting raid roster ".$mode." update");
            if (!$this->bot->core("settings")->get("Members", "QuietUpdate")) {
                $this->bot->send_pgroup("##normal##" . $msg . "Roster Raid ".$mode." update starting ::: System busy##end##");
            }
            $buddies = $this->bot->aoc->buddies;
            $num = 0;
            $this->removed = 0;
            $this->rerolled = 0;
            $members = $this->bot->db->select(
                "SELECT char_id, nickname, user_level, notify, updated_at FROM #___users"
            );
            if (!empty($members)) {
                foreach ($members as $member) {
                    $id = $this->bot->core("player")->id($member[1]);
                    /*
                    Catch deleted characters.
                    */
                    if ($id instanceof BotError) {
						$rdelay = $this->bot->core("settings")->get("Roster", "RemoveDelay")*86400;
                        if ((($member[4] + $rdelay) <= time()) && ($member[4] != 0)) {
                            $this->erase(
                                "Roster",
                                $member[0],
                                $member[1],
                                "as the character appears to have been deleted."
                            );
                            $this->removed++;
                        } else {
                            $this->bot->log(
                                "ROSTER",
                                "INFO",
                                $member[1] . " is in members table but Appears to have been Deleted, skipping removal for now"
                            );
							$upid[$member[0]] = true;
                        }
                    } /*
                    Catch rerolled characters.
                    */
                    else {
                        if ($id != $member[0]) {
                            if ($id == "0" || $id == "-1" || $id == "" || $id == null || empty($id) || strlen(
                                    $id
                                ) < 5
                            ) {
                                $this->bot->log("ROSTER", "ID", "Get ID Failed for $name (ID: " . $id . ")");
                            } else {
                                $this->erase(
                                    "Roster",
                                    $member[0],
                                    $member[1],
                                    "as the character appears to have been rerolled. Old: $member[0] New: $id"
                                );
                                $this->rerolled++;
                            }
                        } /*
                        If not we just run through the paces and make sure everything is in order.
                        */
                        else {
                            if ($member[4] != 0) {
								$upid[$id] = true;
                            }
                            /*
                            Make sure we have an entry in the whois cache for the character.
                            */
                            $this->bot->core("whois")->lookup($member[1], false, true);
                            if ($member[3] == 1) {
                                /*
                                Make sure all on characters on notify list are in buddy list
                                */
                                $this->bot->core("chat")->buddy_add($id);
                                /*
                                Make sure we don't remove the characters on notify list from buddy list later
                                */
                                unset($buddies[$member[0]]);
                            }
                        }
                    }
                }
            }
			if (count($upid)>0) {
				$ciin = "";
				foreach ($upid AS $key => $val) {
					$ciin .= $key.",";
				}		
				$ciin = substr($ciin, 0, -1);
				$this->bot->db->query(
					"UPDATE #___users SET updated_at = '" . time() . "' WHERE char_id IN (" . $ciin . ")"
				);
			}			
            $this->bot->log(
                "CRON",
                "ROSTER",
                "Done updating roster. Removed " . $this->removed . " members of which " . $this->rerolled . " was rerolled characters.",
                true
            );
            $this->bot->log("CRON", "ROSTER", "Cleaning buddylist...");
            /*
            cycle through anything still on our buddylist
            */
            foreach ($buddies as $id => $value) {
                $this->bot->core("chat")->buddy_remove($id);
                $num++;
            }
            $this->bot->core("settings")->save("members", "LastRosterUpdate", time());
            $this->bot->log("CRON", "ROSTER", "Cleaning buddylist done. $num invalid buddies removed.");
            if (!$this->bot->core("settings")->get("Members", "QuietUpdate")) {
                $this->bot->send_pgroup("##normal##Roster update completed ##end##");
            }
        }
        $this->running = false;
    }


    function add($source, $id, $name, $reason)
    {
        $this->bot->log("ROSTER", "ADD", "Adding $name $reason");
        $result = $this->bot->core("user")->add($source, $name, $id, 2, 1);
        if (!($result instanceof BotError)) {
            $this->added++;
        }
		$this->bot->core("chat")->buddy_add($id);
    }


    function del($source, $id, $name, $reason)
    {
        $this->bot->log("ROSTER", "DEL", "Deleting $name $reason");
        $result = $this->bot->core("user")->del($source, $name, $id, 1);
        if (!($result instanceof BotError)) {
            $this->removed++;
        }
        $this->bot->core("chat")->buddy_remove($id);
    }


    function erase($source, $id, $nickname, $reason)
    {
        $this->bot->log("ROSTER", "ERASE", "Erasing $nickname $reason");
        $this->bot->core("user")->erase($source, $nickname, 1, $id);
        $this->bot->core("chat")->buddy_remove($id);
    }


    function parse_org($dim, $id)
    {
        if (($this->bot->core("settings")
                    ->get("Members", "Roster") == "XML"
                || $this->bot
                    ->core("settings")
                    ->get("Members", "Roster") == "Fallback")
            && strtolower($this->bot->game) == 'ao'
        ) {
            // Get the guild roster
            $i = 0;
            $j = 0;
            $xml_roster = $this->bot->core("tools")
                ->get_site($this->bot->core("settings")->get("Members", "MembersUrl")."/org/stats/d/$dim/name/$id/basicstats.xml");
            $faction = $this->bot->core("tools")
                ->xmlparse($xml_roster, "side");
            $orgname = $this->bot->core("tools")
                ->xmlparse($xml_roster, "name");
            $this->bot->log("ROSTER", "UPDATE", "XML for the $faction guild $orgname obtained");
            $xml_roster = explode("<member>", $xml_roster);
            unset($xml_roster[0]); //Get rid of the header as it's not a member.
            if (!empty($xml_roster)) {
                // Build array of members
                foreach ($xml_roster as $xml_member) {
                    $member['nickname'] = $this->bot->core("tools")
                        ->xmlparse($xml_member, "nickname");
                    $member["firstname"] = $this->bot->core("tools")
                        ->xmlparse($xml_member, "firstname");
                    $member["lastname"] = $this->bot->core("tools")
                        ->xmlparse($xml_member, "lastname");
                    $member["level"] = $this->bot->core("tools")
                        ->xmlparse($xml_member, "level");
                    $member["gender"] = $this->bot->core("tools")
                        ->xmlparse($xml_member, "gender");
                    $member["breed"] = $this->bot->core("tools")
                        ->xmlparse($xml_member, "breed");
                    $member["faction"] = $faction;
                    $member["profession"] = $this->bot->core("tools")
                        ->xmlparse($xml_member, "profession");
                    $member["at"] = $this->bot->core("tools")
                        ->xmlparse($xml_member, "defender_rank");
                    $member["at_id"] = $this->bot->core("tools")
                        ->xmlparse($xml_member, "defender_rank_id");
                    $member["org_id"] = $id;
                    $member["org"] = $orgname;
                    $member["rank_id"] = $this->bot->core("tools")
                        ->xmlparse($xml_member, "rank");
                    $member["rank"] = $this->bot->core("tools")
                        ->xmlparse($xml_member, "rank_name");
                    $member["pictureurl"] = $this->bot->core("tools")
                        ->xmlparse($xml_member, "photo_url");
                    $member["id"] = $this->bot->core("player")
                        ->id($member["nickname"]); // Hey, don't get the character ID before you have a nickname!

                    // If we cannot lookup the player, just ignore it here already
                    if ($member["id"] instanceof BotError) {
                        $j++;
                    } else {
                        if ($member['nickname'] !== ucfirst(strtolower($this->bot->botname))) {
                            $members[] = $member;
                        }
                        $i++;
                    }
                }
                $this->bot->log(
                    "ROSTER",
                    "UPDATE",
                    "XML for the $faction guild $orgname contained $i member entries. Ignored $j entries that could not be looked up."
                );
            } else {
				$this->bot->log("ROSTER", "ERROR", "XML obtained is empty or broken. Stopping here.");	
				return;
			}
        }
        if (($this->bot->core("settings")
                    ->get("Members", "Roster") == "WhoisCache"
                || $this->bot
                    ->core("settings")
                    ->get("Members", "Roster") == "Fallback")
            && (empty($members) || $members == 0)
        ) {
            $db_members = $this->bot->db->select(
                "SELECT id, nickname, firstname,lastname,level,gender,breed,faction,profession,defender_rank,defender_rank_id,org_id,org_name,org_rank,org_rank_id,pictureurl FROM #___whois WHERE org_id = '"
                . $id . "'"
            );
            if (!empty($db_members)) {
                foreach ($db_members as $key => $db_member) {
                    $members[$key]["nickname"] = $db_member[1];
                    $members[$key]["firstname"] = $db_member[2];
                    $members[$key]["lastname"] = $db_member[3];
                    $members[$key]["level"] = $db_member[4];
                    $members[$key]["gender"] = $db_member[5];
                    $members[$key]["breed"] = $db_member[6];
                    $members[$key]["faction"] = $db_member[7];
                    $members[$key]["profession"] = $db_member[8];
                    $members[$key]["at"] = $db_member[9];
                    $members[$key]["at_id"] = $db_member[10];
                    $members[$key]["org_id"] = $db_member[11];
                    $members[$key]["org"] = $db_member[12];
                    $members[$key]["rank"] = $db_member[13];
                    $members[$key]["rank_id"] = $db_member[14];
                    $members[$key]["pictureurl"] = $db_member[15];
                    $members[$key]["id"] = $db_member[0];
                    if ($members[$key]["nickname"] == ucfirst(strtolower($this->bot->botname))) {
                        unset($members[$key]);
                    }
                }
            }
        }
        return $members;
    }
}

?>
