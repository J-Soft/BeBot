<?php
/*
* TowerAttack.php - Handle Alien attack events.
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
$alienAttack = new AlienAttack($bot);
/*
The Class itself...
*/
class AlienAttack extends BaseActiveModule
{

    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("org_city", "true") . "
        		(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        		time INT,
        		action VARCHAR(10),
        		player VARCHAR(15))"
        );
        $this->register_command('all', 'city', 'GUEST');
        $this->register_alias("city", "cloak");
        $this->register_event("gmsg", "org");
        $this->help['description'] = "Shows information and history pertaining to the city and city controller.";
        $this->help['command']['city'] = "- See description";
        $this->help['command']['cloak'] = "- See description";
        $this->register_event("logon_notify");
        $this->register_event("timer", "city");
        $classid = $this->bot->core("timer")
            ->create_timer_class("CityWarning", "Notify class used by the AlienAttack module.");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, -2, 0, "", "");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 60, "", "in one minute");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 300, "", "in five minutes");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 900, "", "in 15 minutes");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 1800, "", "in 30 minutes");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 3600, "", "in one hour");
        $classid = $this->bot->core("timer")
            ->create_timer_class("CityWarningSpam", "Notify class used by the AlienAttack module.");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, -2, 0, "", "");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 300, "", "");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 600, "", "");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 900, "", "");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 1200, "", "");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 1500, "", "");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 1800, "", "");
        $classid = $this->bot->core("timer")
            ->create_timer_class("CityCloakReady", "Notify class used by the AlienAttack module.");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, -2, 0, "", "");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 60 * 60, "", "");
        $classid = $this->bot->core("timer")
            ->create_timer_class("CityCloakReminder", "Notify class used by the AlienAttack module.");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, -2, 0, "", "");
        $nextid = $this->bot->core("timer")
            ->create_timer_class_entry($classid, $nextid, 60 * 15, "", "");
        $this->delete_cloak_reminder();
        $this->bot->core("settings")
            ->create(
                "AlienAttack",
                "Spam",
                "none",
                "Should the bot spam to gc or tells (on logon) or both?",
                "none;gc;tell;both"
            );
        $this->bot->core("settings")
            ->create(
                "AlienAttack",
                "Channel",
                "gc",
                "Into which channel should any output about alien attacks and city changes be send?",
                "gc;pgmsg;both"
            );
        $this->bot->core("settings")
            ->create(
                "AlienAttack",
                "PublicTimer",
                false,
                "Should a public timer in addition to the periodic spam be created on cloak up and down?"
            );
        $this->bot->core("settings")
            ->create(
                "AlienAttack",
                "CloakReminder",
                true,
                "Should the bot send a reminder every 15mins if the cloak is still disabled?"
            );
        $this->spam = false;
        $setting = $this->bot->core("settings")->get('AlienAttack', 'Spam');
        if ($setting == "tell" || $setting == "both") {
            $result = $this->bot->db->select(
                "SELECT time FROM #___org_city WHERE action = 'off' ORDER BY time DESC LIMIT 0, 1"
            );
            if (!empty($result)) {
                if ($result[0][0] > time() - 1800) {
                    $this->spam = true;
                }
            }
        }
        //FIXME: Add delete Timer for reminder once timer repeat fixed.
    }


    function timer($name, $prefix, $suffix, $delay)
    {
        $name = explode(" ", $name);
        $type = $name[1];
        $name = $name[0];
        $channel = $this->bot->core("settings")->get("AlienAttack", "Channel");
        if ($type == "spam") {
            $this->bot->send_output(
                "",
                "##red##Warning##end##: Alien Raid in City is in Progress. Please dont enter the city",
                $channel
            );
            if ($delay == 0) {
                $this->spam = false;
            }
        } elseif ($type == "cloak") {
            if ($delay == 0) {
                $this->bot->send_output(
                    "",
                    "Cloaking device was disabled one hour ago. It is now possible to enable it again.",
                    $channel
                );
            } elseif ($delay != 3600) {
                $this->bot->send_output(
                    "",
                    "Cloaking device is disabled. It will be possible to enable it again " . $suffix,
                    $channel
                );
            }
            if ($delay == 60) {
                if ($this->bot->core("settings")
                    ->get("AlienAttack", "CloakReminder")
                ) {
                    $this->timerid = $this->bot->core("timer")
                        ->add_timer(false, "city", 60 * 15, " cloakr", "internal", 60 * 15, "CityCloakReminder");
                }
            }
        } elseif ($type == "cloakr") {
            if ($delay == 0) {
                $this->bot->send_output("", "Cloaking device is still disabled.", $channel);
            }
        } elseif ($type == "cloakready") {
            if ($delay == 0) {
                $this->bot->send_output(
                    "",
                    "Cloaking device has been enabled one hour ago. Alien attacks can now be initiated.",
                    $channel
                );
            }
        }
    }


    /*
    This gets called on a tell with the command
    */
    function command_handler($name, $msg, $origin)
    {
        return $this->city_blob();
    }


    /*
    Makes the battle results
    */
    function city_blob()
    {
        $result = $this->bot->db->select(
            "SELECT time, action, player FROM #___org_city ORDER BY time DESC LIMIT 0, 12"
        );
        if (!$result) {
            return "No city events found in database.";
        } else {
            $city = "##blob_title##::::: Recent City Attacks :::::##end##\n\n";
            foreach ($result as $res) {
                $city .= "##blob_text##Time:##end## " . gmdate(
                        $this->bot
                            ->core("settings")
                            ->get("Time", "FormatString"),
                        $res[0]
                    ) . "\n";
                if ($res[1] == "attack") {
                    $city .= "City was attacked.\n";
                } else {
                    if ($res[1] == "on") {
                        $city .= $res[2] . " turned cloaking ##highlight##on##end##.\n";
                    } else {
                        if ($res[1] == "off") {
                            $city .= $res[2] . " turned cloaking ##highlight##off##end##.\n";
                        } else {
                            if ($res[1] == "hq") {
                                $city .= $res[2] . " destroyed the ##highlight##HQ##end##.\n";
                            } else {
                                if ($res[1] == "house") {
                                    $city .= $res[2] . " destroyed a ##highlight##building##end##.\n";
                                } else {
                                    if ($res[1] == "payment") {
                                        $city .= "City payment warning.\n";
                                    }
                                }
                            }
                        }
                    }
                }
                $city .= "\n";
            }
            $result = $this->bot->db->select(
                "SELECT time, action FROM #___org_city WHERE action = 'on' OR action = 'off' ORDER BY time DESC LIMIT 0, 1"
            );
            if (empty($result)) {
                $avilmin = 0;
                $avilsec = 0;
                $status = "enable";
                $status2 = "disable";
                $ttchange = true;
            } else {
                $avilmin = date("i", 3600 - (time() - $result[0][0]));
                $avilsec = date("s", 3600 - (time() - $result[0][0]));
                if ($result[0][1] == "on") {
                    $status = "enable";
                    $status2 = "disable";
                } else {
                    $status = "disable";
                    $status2 = "enable";
                }
                if ($result[0][0] > (time() - 3600)) {
                    $ttchange = true;
                } else {
                    $ttchange = false;
                }
            }
            $state = "The cloaking device is ##highlight##" . $status . "d##end##.";
            $state .= $ttchange ? "" : " It is now possible to ##highlight##" . $status2 . "##end## it. ";
            $state .= $ttchange ?
                " It will be possible to ##highlight##" . $status2 . "##end## it in ##highlight##" . $avilmin . "##end## minutes and ##highlight##" . $avilsec . "##end## seconds. "
                : "";
            return $state . $this->bot->core("tools")
                ->make_blob("City History", $city);
        }
    }


    /*
    This gets called on a msg in the group
    */
    function gmsg($name, $group, $msg)
    {
        if ($name == "0") {
            $channel = $this->bot->core("settings")
                ->get("AlienAttack", "Channel");
            $action = "none";
            $player = "";
            if (preg_match(
                "/Your radar station is picking up alien activity in the area surrounding your city./i",
                $msg
            )
            ) {
                $this->bot->send_output("", "Alien attack incoming! Beware!", $channel);
            } else {
                if (preg_match("/Your city in (.+) has been targeted by hostile forces./i", $msg, $info)) {
                    $action = "attack";
                    $zone = $info[1];
                    $this->bot->send_output(
                        "",
                        "Our city in " . $zone . " is about to be under attack! 0MGZ RUN!!!!",
                        $channel
                    );
                } else {
                    if (preg_match("/(.+) turned the cloaking device in your city off./i", $msg, $info)) {
                        $action = "off";
                        $player = $info[1];
                        $setting = $this->bot->core("settings")
                            ->get('AlienAttack', 'Spam');
                        if ($setting == "gc" || $setting == "both") {
                            $this->bot->core("timer")
                                ->add_timer(false, "city", 1860, $player . " spam", "internal", 0, "CityWarningSpam");
                        }
                        $this->spam = true;
                        $this->bot->core("timer")
                            ->add_timer(false, "city", 60 * 60 + 1, $player . " cloak", "internal", 0, "CityWarning");
                        $this->delete_cloak_reminder();
                        if ($this->bot->core("settings")
                            ->get("AlienAttack", "PublicTimer")
                        ) {
                            $this->bot->core("timer")
                                ->add_timer(
                                    false,
                                    $player,
                                    60 * 60 + 1,
                                    $this->bot
                                        ->core("shortcuts")
                                        ->get_short($this->bot->guildname) . "'s cloak can be enabled again",
                                    "gc",
                                    0,
                                    "CityWarning"
                                );
                        }
                        $this->bot->send_output(
                            "",
                            "##highlight##" . $player . "##end## turned the cloaking device in our city ##highlight##off##end##!",
                            $channel
                        );
                    } else {
                        if (preg_match("/(.+) turned the cloaking device in your city on./i", $msg, $info)) {
                            $action = "on";
                            $player = $info[1];
                            $this->delete_cloak_reminder();
                            $this->bot->core("timer")
                                ->add_timer(
                                    false,
                                    "city",
                                    60 * 60 + 1,
                                    $player . " cloakready",
                                    "internal",
                                    0,
                                    "CityCloakReady"
                                );
                            if ($this->bot->core("settings")
                                ->get("AlienAttack", "PublicTimer")
                            ) {
                                $this->bot->core("timer")
                                    ->add_timer(
                                        false,
                                        $player,
                                        60 * 60 + 1,
                                        $this->bot
                                            ->core("shortcuts")
                                            ->get_short($this->bot->guildname) . "'s cloak can be disabled again",
                                        "gc",
                                        0,
                                        "CityWarning"
                                    );
                            }
                            $this->bot->send_output(
                                "",
                                "##highlight##" . $player . "##end## turned the cloaking device in our city back ##highlight##on##end##!",
                                $channel
                            );
                        } else {
                            if (preg_match(
                                "/(.+) initiated removal of the organization headquarters in (.+)/i",
                                $msg,
                                $info
                            )
                            ) {
                                $action = "HQ";
                                $player = $info[1];
                                $zone = $info[2];
                                $this->bot->send_output(
                                    "",
                                    "##highlight##" . $player . "##end## is removeing our HQ in ##highlight##" . $zone
                                    . "##end##! Our city... will.. *sobs* ...be destroyed!! *starts crying*",
                                    $channel
                                );
                            } else {
                                if (preg_match("/(.+) removed the organization headquarters in (.+)/i", $msg, $info)) {
                                    $action = "HQ removed";
                                    $player = $info[1];
                                    $zone = $info[2];
                                    $this->bot->send_output(
                                        "",
                                        "##highlight##" . $player . "##end## has removed our HQ in ##highlight##" . $zone
                                        . "##end##! We are now homeless street urchin people!! *crys even harder*",
                                        $channel
                                    );
                                } else {
                                    if (preg_match("/(.+) initiated removal of a (.+) in (.+)/i", $msg, $info)) {
                                        $action = $info[2] . " removal initiated";
                                        $player = $info[1];
                                        $zone = $info[3];
                                        $this->bot->send_output(
                                            "",
                                            "##highlight##" . $player . "##end## is removing a " . $info[2] . " at our city in ##highlight##" . $zone . "##end##.",
                                            $channel
                                        );
                                    } else {
                                        if (preg_match("/(.+) removed a (.+) in (.+)/i", $msg, $info)) {
                                            $action = $info[2] . " removed";
                                            $player = $info[1];
                                            $zone = $info[3];
                                            $this->bot->send_output(
                                                "",
                                                "##highlight##" . $player . "##end## removed a " . $info[2] . " at our city in ##highlight##" . $zone . "##end##.",
                                                $channel
                                            );
                                        } else {
                                            if (preg_match(
                                                "/^The upkeep for your organization housing has not been paid./i",
                                                $msg
                                            )
                                            ) {
                                                $action = "payment";
                                                $player = "";
                                            } else {
                                                $action = "unknown";
                                                $player = $msg;
                                                //$this -> bot -> send_output("", "Something wierd is going on, and I don't know what it is!", $channel);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($action != "none") {
                $this->bot->db->query(
                    "INSERT INTO #___org_city (time, action, player) VALUES
                                      (" . time() . ", '" . $action . "', '" . $player . "')"
                );
            }
        }
    }


    function delete_cloak_reminder()
    {
        $reg_timer = $this->bot->core("timer")->list_timed_events("city");
        if (!empty($reg_timer)) {
            foreach ($reg_timer as $timer) {
                if (strtolower($timer['name']) == 'cloakr' || strtolower($timer['name']) == ' cloakr') {
                    $this->bot->core("timer")->del_timer("city", $timer['id']);
                }
            }
        }
    }


    function notify($name)
    {
        $setting = $this->bot->core("settings")->get('AlienAttack', 'Spam');
        if ($this->spam && ($setting == "tell" || $setting == "both")) {
            $this->bot->send_tell(
                $name,
                "##red##Warning##end##: Alien Raid in City is in Progress. Please dont enter the city"
            );
        }
    }
}

?>
