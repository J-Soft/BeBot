<?php
/*
* AdminsGUI.php.
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

$admins = new admins($bot);

class admins extends BaseActiveModule
{
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));

        $this->register_command('all', 'admins', 'GUEST');
		$this -> register_alias("admins", "leaders");

        $this->help['description'] = 'Shows bots Admin list.';
        $this->help['command']['admins'] = "Shows the list of admins.";
        //$this -> help['command']['admins all'] = "Shows all the bot admins by org.";
    }


    function command_handler($name, $msg, $channel)
    {
        Return ($this->admins_blob($msg));
    }


    function admins_blob($msg)
    {
		$all = false;
        if (preg_match("/^admins all$/i", $msg, $info)) {
            $all = true;
        }
        $sql = "SELECT gid, name, description, access_level FROM #___security_groups ";
        $sql .= "ORDER BY access_level DESC, gid ASC, name";
        $result = $this->bot->db->select($sql, MYSQLI_ASSOC);
        $owner = "##highlight##Owner (O)##end##\n";
        $superadmins = "##highlight##Superadmin(s) (SA)##end##\n"; $scount = 0;
        $admins = "##highlight##Admin(s) (A)##end##\n"; $acount = 0;
        $leaders = "##highlight##Leader(s) (L)##end##\n"; $lcount = 0;
        //$members = "MEMBER:\n";
        //$guests = "GUEST:\n";
        //$anon = "ANONYMOUS:\n";
        $temp = "";
        $online2 = false;
        $ow = $this->bot->core("security")->owner;
        $main = $this->bot->core("alts")->main($ow);
        $online = $this->bot->core("online")->get_online_state($main);
        $temp .= "\n- ##highlight##$main##end## is " . $online["content"];
        if ($online['status'] == 1) {
            $online2 = true;
        }
        $alts = $this->bot->core("alts")->get_alts($main);
        if (!empty($alts)) {
            foreach ($alts as $alt) {
                $online = $this->bot->core("online")->get_online_state($alt);
                if ($online['status'] == 1 || $all) {
                    $temp .= "\n   - $alt is " . $online["content"];
                }
                if ($online['status'] == 1) {
                    $online2 = true;
                }
            }
        }
        $temp .= "\n";
        if ($online2 || $all) {
            $owner .= $temp;
        }
        foreach ($result as $group) {
            if ($group['access_level'] == SUPERADMIN) {
                $users = $this->bot->core("security")->cache['groups'][$group['gid']]['members'];
				$scount = count($users)+$scount;
                if (!empty($users)) {
                    foreach ($users as $user) {
                        $main = $this->bot->core("alts")
                            ->main($user);
                        $mains['SA'][$main] = true;
                    }
                    ksort($mains['SA']);
                    foreach ($mains['SA'] as $main => $v) {
                        $temp = "";
                        $online2 = false;
                        //$admins .= " + ".$group['name']." (".stripslashes($group['description']).") ";
                        $online = $this->bot->core("online")
                            ->get_online_state($main);
                        $temp .= "\n- ##highlight##$main##end## is " . $online["content"];
                        if ($online['status'] == 1) {
                            $online2 = true;
                        }
                        $alts = $this->bot->core("alts")->get_alts($main);
                        if (!empty($alts)) {
                            foreach ($alts as $alt) {
                                $online = $this->bot->core("online")
                                    ->get_online_state($alt);
                                if ($online['status'] == 1 || $all) {
                                    $temp .= "\n   - $alt is " . $online["content"];
                                }
                                if ($online['status'] == 1) {
                                    $online2 = true;
                                }
                            }
                        }
                        $temp .= "\n";
                        if ($online2 || $all) {
                            $superadmins .= $temp;
                        }
                    }
                }
            } elseif ($group['access_level'] == ADMIN) {
                $users = $this->bot->core("security")->cache['groups'][$group['gid']]['members'];
				$acount = count($users)+$acount;
                if (!empty($users)) {
                    foreach ($users as $user) {
                        $main = $this->bot->core("alts")
                            ->main($user);
                        $mains['A'][$main] = true;
                    }
                    ksort($mains['A']);
                    foreach ($mains['A'] as $main => $v) {
                        $temp = "";
                        $online2 = false;
                        $online = $this->bot->core("online")
                            ->get_online_state($main);
                        if ($online['status'] == 1) {
                            $online2 = true;
                        }
                        $temp .= "\n- ##highlight##$main##end## is " . $online["content"];
                        $alts = $this->bot->core("alts")->get_alts($main);
                        if (!empty($alts)) {
                            foreach ($alts as $alt) {
                                $online = $this->bot->core("online")
                                    ->get_online_state($alt);
                                if ($online['status'] == 1 || $all) {
                                    $temp .= "\n   - $alt is " . $online["content"];
                                }
                                if ($online['status'] == 1) {
                                    $online2 = true;
                                }
                            }
                        }
                        $temp .= "\n";
                        if ($online2 || $all) {
                            $admins .= $temp;
                        }
                    }
                }
            } elseif ($group['access_level'] == LEADER) {
                $users = $this->bot->core("security")->cache['groups'][$group['gid']]['members'];
				$lcount = count($users)+$lcount;
                if (!empty($users)) {
                    foreach ($users as $user) {
                        $main = $this->bot->core("alts")
                            ->main($user);
                        $mains['L'][$main] = true;
                    }
                    ksort($mains['L']);
                    foreach ($mains['L'] as $main => $v) {
                        $temp = "";
                        $online2 = false;
                        $online = $this->bot->core("online")
                            ->get_online_state($main);
                        if ($online['status'] == 1) {
                            $online2 = true;
                        }
                        $temp .= "\n- ##highlight##$main##end## is " . $online["content"];
                        $alts = $this->bot->core("alts")->get_alts($main);
                        if (!empty($alts)) {
                            foreach ($alts as $alt) {
                                $online = $this->bot->core("online")
                                    ->get_online_state($alt);
                                if ($online['status'] == 1 || $all) {
                                    $temp .= "\n   - $alt is " . $online["content"];
                                }
                                if ($online['status'] == 1) {
                                    $online2 = true;
                                }
                            }
                        }
                        $temp .= "\n";
                        if ($online2 || $all) {
                            $leaders .= $temp;
                        }
                    }
                }
            }
            /*elseif ($group['access_level'] == MEMBER)
            {
                $members .= " + ".$group['name']." (".stripslashes($group['description']).") ";
                $members .= "\n";
                $members .= "        ".$this -> make_group_member_list($group['gid']);
                $members .= "\n";
            }
            elseif ($group['access_level'] == GUEST)
            {
                $guests .= " + ".$group['name']." (".stripslashes($group['description']).") ";
                $guests .= "\n";
                $guests .= "        ".$this -> make_group_member_list($group['gid']);
                $guests .= "\n";
            }
            elseif ($group['access_level'] == ANONYMOUS)
            {
                $anon .= " + ".$group['name']." (".stripslashes($group['description']).") ";
                $anon .= "\n";
                $anon .= "        ".$this -> make_group_member_list($group['gid']);
                $anon .= "\n";
            }*/
        }
        $inside = "##ao_ccheader##:::: <botname> Admins ::::##end##\n\n##seablue##";
        $inside .= $owner . "\n";
        $inside .= $scount." ".$superadmins . "\n";
        $inside .= $acount." ".$admins . "\n";
        $inside .= $lcount." ".$leaders . "\n";
        //$inside .= $members."\n";
        //$inside .= $guests."\n";
        //$inside .= $anon."\n";
        if (!$all) {
            $inside .= "\n" . $this->bot->core("tools")
                    ->chatcmd("admins all", "View all bot admins");
        }
        $inside .= "##end##";
        return "Admins list " . $this->bot->core("tools")
            ->make_blob("click to view", $inside);
        //else
        //{
        //	$blob .= "<font color=CCWhite>Online Admins:##end##\n\n";
        //	$end = "\n" . $this -> bot -> core("tools") -> chatcmd("admins all", "View all bot admins");
        //	$result = $this -> bot -> db -> select("SELECT * FROM admins_list where status_gc=1");
        //}
        /*foreach ($result as $row)
{
$player = $row[0];
$level	= $row[3];
$prof	= $row[4];
$org	= $row[5];
$online	= $row[6];
$blob .= "<font color=CCNewbieColor>" . $player . "##end##";
$blob .= "##lightyellow## (".$level."/".$prof.")##end##";
$blob .= "##lightyellow## ".$org."##end##";
if ($online == 1)
$blob .= "##lightyellow## is ##end##<font color=#ffffff>online.##end##";
else
$blob .= "##lightyellow## is ##end##<font color=#ff0000>offline.##end##";
$blob .= "\n";
}
$blob .= $end . "\n";
return "Admins list " . $this -> bot -> core("tools") -> make_blob("click to view", $blob);*/
    }
}

?>
