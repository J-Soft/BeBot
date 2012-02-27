<?php
/*
* Tokens.php - Determines how many tokens and token bags you need based on your level, current tokens, and goal tokens.
* Developed by Siocuffin (RK1), adapted for BeBot 0.4 by Khalem (RK1)
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
$tokens = new tokens($bot);
/*
The Class itself...
*/
class tokens extends BaseActiveModule
{

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command("all", "tokens", "GUEST");
        $this->help['description']                                                  = "Token calculator";
        $this->help['command']['tokens']                                            = "Displays how many side tokens you get per token disk at various levels";
        $this->help['command']['tokens <target/current> <target/current>']          = "Calculates the amount of tokens, token bags or VP tokens (and price) based on your current level, <target> and <current> tokens";
        $this->help['command']['tokens <level > <target/current> <target/current>'] = "Calculates the amount of tokens, token bags or VP tokens (and price) based on your <level>, <target> and <current> tokens";
    }


    /*
    Unified message handler
    */
    function command_handler($source, $msg, $type)
    {
        $return = false;
        $clan   = false;
        /*
        This should really be moved to the bot core.. but until i get the time to modify every single module... :\
        */
        $vars    = explode(' ', strtolower($msg));
        $command = $vars[0];
        switch ($command)
        {
            case 'tokens':
                $count = count($vars);
                if ($count == 3) {
                    if (!ctype_digit($vars[1]) || !ctype_digit($vars[2])) {
                        $this->error->set("Values given are not numerical");
                        return $this->error;
                    }
                    else
                    {
                        $who = $this->bot->core("whois")->lookup($source);
                        if (!($who instanceof BotError)) {
                            $level = $who["level"];
                            if ($who["faction"] == 'Clan') {
                                $clan = true;
                            }
                        }
                        $return = $this->ShowTokens($level, $vars[1], $vars[2], $clan);
                    }
                }
                else if ($count == 4) {
                    if (!ctype_digit($vars[1]) || !ctype_digit($vars[2]) || !ctype_digit($vars[3])) {
                        $this->error->set("Values given are not numerical");
                        return $this->error;
                    }
                    else
                    {
                        $who = $this->bot->core("whois")->lookup($source);
                        if (!($who instanceof BotError)) {
                            if ($who["faction"] == 'Clan') {
                                $clan = true;
                            }
                        }
                        $return = $this->ShowTokens($vars[1], $vars[2], $vars[3], $clan);
                    }
                }
                else if ($count == 1) {
                    $inside = "##normal##::: ##highlight##Token overview##end## :::\n\n";
                    $inside .= "Level 1-14: ##highlight##1##end## tokens per level\n";
                    $inside .= "Level 15-49: ##highlight##2##end## tokens per level\n";
                    $inside .= "Level 50-74: ##highlight##3##end## tokens per level (Please note that Clan aligned characters will not get 3 tokens until level 51)\n";
                    $inside .= "Level 75-99: ##highlight##4##end## tokens per level\n";
                    $inside .= "Level 100-124: ##highlight##5##end## tokens per level\n";
                    $inside .= "Level 124-149: ##highlight##6##end## tokens per level\n";
                    $inside .= "Level 150-174: ##highlight##7##end## tokens per level\n";
                    $inside .= "Level 175-189: ##highlight##8##end## tokens per level\n";
                    $inside .= "Level 190-220: ##highlight##9##end## tokens per level\n\n";
                    $inside .= "Veteran tokens give ##highlight##50##end## tokens and cost ##highlight##7##end## veteran points\n";
                    $inside .= "OFAB tokens give ##highlight##10##end##/##highlight##100##end## tokens and cost ##highlight##1000##end##/##highlight##10000##end## victory points\n";
                    return "##normal##::: ##highlight##Token overview##end## ::: " . $this->bot
                        ->core("tools")->make_blob("click to view", $inside);
                }
                else
                {
                    $this->error->set("##highlight##$msg##end## is not valid input. Please see <pre>help tokens ");
                    return $this->error;
                }
                break;
            default:
                return "Broken plugin, received unhandled command: $command";
        }
        return false;
    }


    function ShowTokens($level, $goal, $current, $clan)
    {
        $return = false;
        // Make sure our goal is larger than our current tokens
        if ($goal == $current) {
            $this->error->set("Your goal tokens can not be the same as your current tokens!");
            return $this->error;
        }
        elseif ($goal < $current)
        {
            $tempgoal = $goal;
            $goal     = $current;
            $current  = $tempgoal;
        }
        // Make sure we have a legal level range
        if (($level < 1) || ($level > 220)) {
            $this->error->set("Enter a level between ##highlight##1##end## and ##highlight##220##end##!");
            return $this->error;
        }
        // Make sure we have a sane token count for both goal and current
        if (!is_finite($goal) || !is_finite($current)) {
            $this->error->set("Please enter sane values for both goal and current tokens!");
            return $this->error;
        }
        if ($level > 189) {
            $tpl = 9;
        }
        else if ($level > 174) {
            $tpl = 8;
        }
        else if ($level > 149) {
            $tpl = 7;
        }
        else if ($level > 124) {
            $tpl = 6;
        }
        else if ($level > 99) {
            $tpl = 5;
        }
        else if ($level > 74) {
            $tpl = 4;
        }
        // Heres the odd one out. According to amongst others http://wiki.aodevs.com/wiki/Token_and_Tokenboards, Omni will get 3 TPL from level 50, but clan will only get from level 51. Rounding error anyone?
        else if ($level > 49) {
            $tpl = 3;
        }
        else if ($level > 14) {
            $tpl = 2;
        }
        else
        {
            $tpl = 1;
        }
        $need    = $goal - $current;
        $Step1   = $need / $tpl;
        $Step2   = $Step1 / 7;
        $bags    = ceil(4 * $Step2);
        $Step1   = ceil($Step1);
        $VPtoke  = ceil($need / 10);
        $VP      = $VPtoke * 1000;
        $VP2     = $VPtoke * 10;
        $VetToke = ceil($need / 50);
        $Vet     = $VetToke * 7;
        $Vet2    = $VetToke * 50;
        $inside  = "##normal##::: ##highlight##Token calculator results##end## :::\n\n";
        $inside .= "::: ##highlight##Your status##end## :::\n";
        $inside .= "Your level: ##highlight##" . $level . "##end##\n";
        $inside .= "Current # of tokens: ##highlight##" . number_format($current) . "##end##\n";
        $inside .= "Goal # of Tokens: ##highlight##" . number_format($goal) . "##end##\n";
        $inside .= "Tokens needed: ##highlight##" . number_format($need) . "##end##\n";
        $inside .= "Tokens per token disk: ##highlight##" . number_format($tpl) . "##end##\n\n";
        $inside .= "::: ##highlight## Your Calculated Token Options##end## :::\n";
        $inside .= "Token bags needed: ##highlight##" . number_format($bags) . "##end##\n";
        $inside .= "Token discs needed: ##highlight##" . number_format($Step1) . "##end##\n";
        $inside .= "Veteran tokens(##highlight##*##end##): ##highlight##" . number_format($VetToke) . "##end## for ##highlight##" . number_format($Vet) . "##end## veteran points.\n";
        $inside .= "OFAB tokens(##highlight##**##end##): ##highlight##" . number_format($VPtoke) . "##end## for ##highlight##" . number_format($VP) . "##end## victory points.\n\n";
        $inside .= "##highlight##*##end## One veteran token is a set of 50 tokens, thus ##highlight##" . number_format($VetToke) . "##end## will equal ##highlight##" . number_format($Vet2) . " tokens.##end##\n";
        $inside .= "##highlight##**##end## OFAB tokens listed are the set of 10, thus ##highlight##" . number_format($VPtoke) . "##end## will equal ##highlight##" . number_format($VP2) . " tokens.##end####end##";
        $info = "Token calculator ::: " . $this->bot->core("tools")
            ->make_blob('Click for results', $inside);
        return $info;
    }
}

?>
