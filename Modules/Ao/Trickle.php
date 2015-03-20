<?php
/*
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
Trickle.php
Version 1.2
Author: Shrike, AKA Briwn (RK1 Engineer)
Cleaned up by Terje AKA Blueeagl3(RK1)
For: Bebot
*/
$trickle = new Trickle($bot);

class Trickle extends BaseActiveModule
{
    var $skill;
    var $help;


    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command('all', 'trickle', 'GUEST');
        $this->help['description'] = "This command calculates the triclke-down to skills that come from increasing base abillities.";
        $this->help['command']['trickle [sta <num>] [agi <num>] [int <num>] [sen <num>] [str <num>] [psy <num>]']
          = 'Will show the amount of skill you gain by increasing the base abilities.';
        $this->help['notes'] = "The bot accepts full length names as well as abbrevations for the base stats.";
        //Set up the data
        $this->skill['Nano skills']['TS'] = array(
          'agi' => .2,
          'int' => .8
        );
        $this->skill['Nano skills']['MC'] = array(
          'sta' => .2,
          'int' => .8
        );
        $this->skill['Nano skills']['BioMet'] = array(
          'psy' => .2,
          'int' => .8
        );
        $this->skill['Nano skills']['MatMet'] = array(
          'psy' => .2,
          'int' => .8
        );
        $this->skill['Nano skills']['PsyMod'] = array(
          'sen' => .2,
          'int' => .8
        );
        $this->skill['Nano skills']['SI'] = array(
          'str' => .2,
          'int' => .8
        );
        $this->skill['Aiding']['First aid'] = array(
          'agi' => .3,
          'int' => .3,
          'sen' => .4
        );
        $this->skill['Aiding']['Treatment'] = array(
          'agi' => .3,
          'int' => .5,
          'sen' => .2
        );
        $this->skill['Body']['Body dev'] = array('sta' => 1.0);
        $this->skill['Body']['Nano pool'] = array(
          'psy' => .7,
          'sen' => .1,
          'int' => .1,
          'sta' => .1
        );
        $this->skill['Body']['Martial arts'] = array(
          'agi' => .5,
          'psy' => .3,
          'str' => .2
        );
        $this->skill['Body']['Brawling'] = array(
          'str' => .6,
          'sta' => .4
        );
        $this->skill['Body']['Riposte'] = array(
          'sen' => .5,
          'agi' => .5
        );
        $this->skill['Body']['Dimach'] = array(
          'sen' => .8,
          'psy' => .2
        );
        $this->skill['Body']['Adventuring'] = array(
          'str' => .2,
          'agi' => .5,
          'sta' => .3
        );
        $this->skill['Body']['Swimming'] = array(
          'str' => .2,
          'agi' => .2,
          'sta' => .6
        );
        $this->skill['Melee']['1h Blunt'] = array(
          'str' => .5,
          'agi' => .1,
          'sta' => .4
        );
        $this->skill['Melee']['2h Blunt'] = array(
          'str' => .5,
          'sta' => .5
        );
        $this->skill['Melee']['1h Edged'] = array(
          'str' => .3,
          'agi' => .4,
          'sta' => .3
        );
        $this->skill['Melee']['2h Edged'] = array(
          'str' => .6,
          'sta' => .4
        );
        $this->skill['Melee']['Piercing'] = array(
          'str' => .2,
          'agi' => .5,
          'sta' => .3
        );
        $this->skill['Melee']['Melee Energy'] = array(
          'int' => .5,
          'sta' => .5
        );
        $this->skill['Melee']['Parry'] = array(
          'str' => .5,
          'agi' => .2,
          'sen' => .3
        );
        $this->skill['Melee']['Sneak attack'] = array(
          'sen' => .8,
          'psy' => .2
        );
        $this->skill['Melee']['Fast attack'] = array(
          'agi' => .6,
          'sen' => .4
        );
        $this->skill['Melee']['Multi melee'] = array(
          'str' => .3,
          'agi' => .6,
          'sta' => .1
        );
        $this->skill['Misc weapons']['Sharp objects'] = array(
          'str' => .2,
          'agi' => .6,
          'sen' => .2
        );
        $this->skill['Misc weapons']['Grenade'] = array(
          'sen' => .4,
          'agi' => .4,
          'int' => .2
        );
        $this->skill['Misc weapons']['Heavy weapons'] = array(
          'str' => .4,
          'agi' => .6
        );
        $this->skill['Ranged']['Bow'] = array(
          'sen' => .4,
          'agi' => .4,
          'str' => .2
        );
        $this->skill['Ranged']['Pistol'] = array(
          'sen' => .4,
          'agi' => .6
        );
        $this->skill['Ranged']['Assault rifle'] = array(
          'sen' => .2,
          'agi' => .3,
          'sta' => .4,
          'str' => .1
        );
        $this->skill['Ranged']['MG/SMG'] = array(
          'sen' => .1,
          'sta' => .3,
          'str' => .3,
          'agi' => .3
        );
        $this->skill['Ranged']['Shotgun'] = array(
          'str' => .4,
          'agi' => .6
        );
        $this->skill['Ranged']['Rifle'] = array(
          'sen' => .4,
          'agi' => .6
        );
        $this->skill['Ranged']['Ranged energy'] = array(
          'sen' => .4,
          'psy' => .4,
          'int' => .2
        );
        $this->skill['Ranged']['Fling shot'] = array('agi' => 1);
        $this->skill['Ranged']['Aimed shot'] = array('sen' => 1);
        $this->skill['Ranged']['Burst'] = array(
          'agi' => .5,
          'str' => .3,
          'sta' => .2
        );
        $this->skill['Ranged']['Full auto'] = array(
          'sta' => .4,
          'str' => .6
        );
        $this->skill['Ranged']['Bow special'] = array(
          'sen' => .4,
          'agi' => .5,
          'str' => .1
        );
        $this->skill['Ranged']['Multi ranged'] = array(
          'agi' => .6,
          'int' => .4
        );
        $this->skill['Spying']['Concealment'] = array(
          'agi' => .3,
          'sen' => .7
        );
        $this->skill['Spying']['Break & enter'] = array(
          'agi' => .4,
          'sen' => .3,
          'psy' => .3
        );
        $this->skill['Spying']['Trap disarm'] = array(
          'sen' => .6,
          'int' => .2,
          'agi' => .2
        );
        $this->skill['Spying']['Perception'] = array(
          'int' => .3,
          'sen' => .7
        );
        $this->skill['Navigation']['Vehicle air'] = array(
          'sen' => .6,
          'int' => .2,
          'agi' => .2
        );
        $this->skill['Navigation']['Vehicle ground'] = array(
          'sen' => .6,
          'int' => .2,
          'agi' => .2
        );
        $this->skill['Navigation']['Vehicle water'] = array(
          'sen' => .6,
          'int' => .2,
          'agi' => .2
        );
        $this->skill['Navigation']['Map navigation'] = array(
          'sen' => .5,
          'int' => .4,
          'psy' => .10
        );
        $this->skill['Trade & Repair']['Mech engi'] = array(
          'int' => .5,
          'agi' => .5
        );
        $this->skill['Trade & Repair']['Electric engi'] = array(
          'int' => .5,
          'agi' => .3,
          'sta' => .2
        );
        $this->skill['Trade & Repair']['Quantum FT'] = array(
          'int' => .5,
          'psy' => .5
        );
        $this->skill['Trade & Repair']['Weapon smith'] = array(
          'int' => .5,
          'str' => .5
        );
        $this->skill['Trade & Repair']['Pharma tech'] = array(
          'int' => .8,
          'agi' => .2
        );
        $this->skill['Trade & Repair']['Nano prog'] = array('int' => 1);
        $this->skill['Trade & Repair']['Comp lit'] = array('int' => 1);
        $this->skill['Trade & Repair']['Psychology'] = array(
          'int' => .5,
          'sen' => .5
        );
        $this->skill['Trade & Repair']['Chemistry'] = array(
          'int' => .5,
          'sta' => .5
        );
        $this->skill['Trade & Repair']['Tutoring'] = array(
          'int' => .7,
          'sen' => .2,
          'psy' => .1
        );
    }


    function command_handler($source, $msg, $type)
    {
        $this->error->reset();
        $msg = strtolower($msg);
        $com = $this->parse_com(
          $msg, array(
            'com',
            'stat1',
            'val1',
            'stat2',
            'val2',
            'stat3',
            'val3',
            'stat4',
            'val4',
            'stat5',
            'val5',
            'stat6',
            'val6'
          )
        );
        if (empty($com['stat1'])) {
            $this->error->set('You need to specify which stats to trickle from.');
            return ($this->error);
        }
        $stats = array(
          'str',
          'sta',
          'sen',
          'agi',
          'int',
          'psy'
        );
        //Check the validity of the stats
        for ($cnt = 1; $cnt < 7; $cnt++) {
            $statno = 'stat' . $cnt;
            //We only want the three first chars of the stat
            $com[$statno] = substr($com[$statno], 0, 3);
            if ((!in_array($com[$statno], $stats)) && (!empty($com[$statno]))) {
                $this->error->set($this->error->get() . "'{$com[$statno]}' is not a recognized base stat.\n");
            }
        }
        if ($this->error->status() == true) {
            return $this->error;
        }
        for ($cnt = 1; $cnt < 7; $cnt++) {
            if ((!empty($com['stat' . $cnt])) && (!empty($com['val' . $cnt]))) {
                $stats[$com['stat' . $cnt]] = intval($com['val' . $cnt]);
            }
        }
        $trickle = $this->Calc_trickle($stats);
        return ($this->CreateBlob($stats, $trickle));
    }


    function Calc_trickle($upgrade)
    {
        foreach ($this->skill as $group_name => $group) {
            foreach ($group as $item_name => $item) {
                foreach ($item as $stat => $factor) {
                    $increase[$group_name][$item_name] += ($upgrade[$stat] / 4) * $factor;
                }
            }
        }
        return ($increase);
    }


    function CreateBlob($stats, $trickle)
    {
        $msg = "##ao_infoheadline##::: Ability Trickle-down :::##end##\n\n";
        $msg2 = "";
        if ($stats['int'] > 0) {
            $msg2 .= "##ao_infotext## - Intelligence: ##end##{$stats['int']}\n";
        }
        if ($stats['sta'] > 0) {
            $msg2 .= "##ao_infotext## - Stamina: ##end##{$stats['sta']}\n";
        }
        if ($stats['agi'] > 0) {
            $msg2 .= "##ao_infotext## - Agility: ##end##{$stats['agi']}\n";
        }
        if ($stats['sen'] > 0) {
            $msg2 .= "##ao_infotext## - Sense: ##end##{$stats['sen']}\n";
        }
        if ($stats['psy'] > 0) {
            $msg2 .= "##ao_infotext## - Psychic: ##end##{$stats['psy']}\n";
        }
        if ($stats['str'] > 0) {
            $msg2 .= "##ao_infotext## - Strength: ##end##{$stats['str']}\n";
        }
        $msg .= $msg2 . "\n";
        foreach ($trickle as $group_name => $group) {
            $msg .= "##ao_infoheadline##$group_name##end##\n";
            foreach ($group as $skill_name => $skill) {
                if ($skill > 0) {
                    $msg .= "##ao_infotext##$skill_name:##end## $skill\n";
                }
            }
            $msg .= "\n";
        }
        return $this->bot->core("tools")->make_blob("Ability trickles", $msg);
    }
}

?>
