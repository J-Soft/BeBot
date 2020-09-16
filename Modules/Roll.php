<?php
/*
* Roll.php - Rolls a number and flips a coin.
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
$roll = new Roll($bot);
/*
The Class itself...
*/
class Roll extends BaseActiveModule
{
    var $bot;
    var $roll_info;
    /*
        $roll_info is a two-dimentional indexed/associative array with these fields
        $roll_info[$index]['name'] == the name of the person performing the roll
        $roll_info[$index]['time'] == the time at which the roll was performed
        $roll_info[$index]['min'] == the highest possible result of the roll
        $roll_info[$index]['max'] == the highest possible result of the roll
        $roll_info[$index]['result'] == The result of the roll
        $roll_info[$index]['info'] == Everything appended after <limit> or after the !flip command
    */
    var $lastroll;


    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->register_command('all', 'roll', 'GUEST');
        $this->register_command('all', 'flip', 'GUEST');
        $this->register_command('tell', 'verify', 'ANONYMOUS');
        $this->help['description'] = 'Throws a dice and shows the result.';
        $this->help['command']['roll <min> <max> [item]']
            = "Rolls a number between <min> and <max> and shows the result. You can provide an optional [item] to record what the dice is being rolled for.";
        $this->help['command']['flip [item]'] = "Flips a coin and shows the result. You can provide an optional [item] to record what the coin is being flipped for.";
        $this->help['command']['verify <num>'] = "Shows the result of roll <num>";
        $this->bot->core("settings")
            ->create(
                "Roll",
                "RollTime",
                30,
                "How many seconds must someone wait before they can roll again?",
                "5;10;20;30;45;60;120;300;600"
            );
    }


    /*
    This gets called on a tell with the command
    */
    function command_handler($name, $msg, $origin)
    {
        $com = $this->parse_com(
            $msg,
            array(
                 "com",
                 "args"
            )
        );
        switch ($com['com']) {
            case 'roll':
                $args = $this->parse_com(
                    $com['args'],
                    array(
                         'min',
                         'max',
                         'item'
                    )
                );
                if (!isset($args['max'])) {
                    $args['max'] = $args['min'];
                    $args['min'] = 1;
                }
                if (!isset($args['item'])) {
                    $args['item'] = "";
                }
                return ($this->do_roll($name, $args['min'], $args['max'], $args['item']));
                break;
            case 'flip':
                return ($this->do_flip($name, $com['args']));
                break;
            case 'verify':
                return ($this->verify($com['args']));
                break;
            case 'default':
                $this->bot->send_help($name);
        }
    }


    /*
    Verifys result
    */
    function verify($num)
    {
        if (empty($num)) {
            $num = count($this->roll_info);
        }
        if ($num < 0 || $num > count($this->roll_info)) {
            $this->error->set("Invalid verification ID");
            return ($this->error);
        } else {
            $roll = $this->roll_info[$num - 1];
            $name = "##highlight##{$roll['name']}##end##";
            if (!empty($roll['item'])) {
                $item = "Target: ##highlight##'{$roll['item']}'##end##\n";
            } else { $item = "..."; }
            $time = time() - $roll['time'];
            $window = "##blob_title##::: Roll verification: $num :::##end##\n\n";
            $window .= "Roller: ##highlight##{$name}##end##\n";
            $window .= "Time: ##highlight##$time seconds ago##end##\n";
            $window .= $item;
            $window .= "-----------------\n";
            $window .= "Range: {$roll['range']}\n";
            $window .= "Result: {$roll['result']}\n";
            $window .= "-----------------\n";
            $blob = $this->bot->core('tools')
                ->make_blob("Roll result: {$roll['result']}. Verify id: $num", $window);
            return ($blob);
        }
    }


    /*
    Starts the roll
    */
    function do_roll($name, $min, $max, $item)
    {
        if (!isset($this->lastroll[$name])
            || ($this->lastroll[$name] < time() - $this->bot
                    ->core("settings")->get("Roll", "RollTime"))
        ) {
            if (empty($max)) {
                $this->error->set("You need to specify a maximum value");
                return ($this->error);
            }
            if (($max != (int)$max) || $min != (int)$min) {
                $this->error->set("The min and max values need to be an integer.");
                return ($this->error);
            }
            if ($max < 2) {
                $this->error->set("There is no point in rolling for less than one person.");
                return ($this->error);
            }
            $result['name'] = $name;
            $result['time'] = time();
            $result['range'] = "$min - $max";
            $result['result'] = $this->bot->core("tools")
                ->my_rand($min, $max);
            $result['item'] = $item;
            $this->lastroll[$name] = time();
            $this->roll_info[] = $result;
            $tempdebug = count($this->roll_info);
            echo "Debug: " . $tempdebug . $result['result'] . "\n";

            return ($this->verify(count($this->roll_info)));
        } else {
            return "You may only roll once every " . $this->bot
                ->core("settings")->get("Roll", "RollTime") . " seconds.";
        }
    }


    /*
    Starts the flip
    */
    function do_flip($name, $item)
    {
        if (!isset($this->lastroll[$name])
            || ($this->lastroll[$name] < time() - $this->bot
                    ->core("settings")->get("Roll", "RollTime"))
        ) {
            $result['name'] = $name;
            $result['time'] = time();
            $result['range'] = 'heads/tails';
            $result['result'] = ($this->bot->core("tools")
                ->my_rand(0, 1) ? 'heads' : 'tails');
            $result['item'] = $item;
            $this->lastroll[$name] = time();
            $this->roll_info[] = $result;
            return ($this->verify(count($this->roll_info)));
        } else {
            return "You may only flip once every " . $this->bot
                ->core("settings")->get("Roll", "RollTime") . " seconds.";
        }
    }
}

?>
