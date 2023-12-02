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
abstract class BaseActiveModule extends BasePassiveModule
{
    public $help; //A window containing help text for this module
    var $source, $module_name;

    function __construct(&$bot, $module_name)
    {
        //Save reference to bot
        parent::__construct($bot, $module_name);
    }


    // Prototype for the command_handler
    abstract protected function command_handler($name, $msg, $origin);


    // Interface to register command. Now with checks for duplicate command definitions.
    // $channel is the channel the command should be registered for. "all" can be used to register a command for gc, pgmsg and tell at once.
    // $command is the command to register.
    // $access is the minimum access level required to use the command on default.
    // $subcommands is an array with keys of subcommands and entries of access levels to define access
    // rights for possible subcommands. If $subcommands is NULL it will be ignored.
    protected function register_command(
        $channel,
        $command,
        $access = "SUPERADMIN",
        $subcommands = null
    ) {
        $levels = array(
            'ANONYMOUS',
            'GUEST',
            'MEMBER',
            'LEADER',
            'ADMIN',
            'SUPERADMIN',
            'OWNER'
        );
        $channels = array(
            'gc',
            'pgmsg',
            'tell',
            'extpgmsg',
            'all'
        );
        $allchannels = array(
            'gc',
            'pgmsg',
            'tell'
        );
        if ((in_array($channel, $channels)) && (in_array($access, $levels))) {
            if (!$this->bot->exists_command($channel, $command)) {
                $this->bot->register_command($channel, $command, $this);
                $this->bot->core("access_control")
                    ->create($channel, $command, $access);
                if ($subcommands != null) {
                    foreach ($subcommands as $subcommand => $subacl) {
                        $this->bot->core("access_control")
                            ->create_subcommand($channel, $command, $subcommand, $subacl);
                    }
                }
            } else {
                //Say something useful for modules not registering commands properly.
                $old_module = $this->bot->get_command_handler($channel, $command);
                $this->error->set(
                    "Duplicate command definition! The command '$command' for channel '$channel'"
                    . " has already been registered by '$old_module' and is attempted re-registered by {$this->module_name}"
                );
            }
        } else {
            $this->error->set("Illegal channel or access level when registering command '$command'");
        }
    }


    protected function unregister_command($channel, $command)
    {
        $channels = array(
            'gc',
            'pgmsg',
            'tell',
            'extpgmsg',
            'all'
        );
        $allchannels = array(
            'gc',
            'pgmsg',
            'tell'
        );
        if (in_array($channel, $channels)) {
            if ($this->bot->exists_command($channel, $command)) {
                $this->bot->unregister_command($channel, $command);
            }
        }
    }


    // Registers a command alias for an already defined command.
    protected function register_alias($command, $alias)
    {
        $this->bot->core("command_alias")->register($command, $alias);
    }


    protected function unregister_alias($alias)
    {
        $this->bot->core("command_alias")->del($alias);
    }


    // This function aids in parsing the command.
    protected function parse_com(
        $command,
        $pattern
        = array(
            'com',
            'sub',
            'args'
        )
    ) {
        //preg_match for items and insert a replacement.
        if (strtolower($this->bot->game) == 'aoc') {
            $search_pattern = '/' . $this->bot->core('items')->itemPattern . '/i';
        } else {
            $search_pattern = '|<a href="itemref://([0-9]+)/([0-9]+)/([0-9]{1,3})">([^<]+)</a>|';
        }
        $item_count = preg_match_all($search_pattern, $command, $items, PREG_SET_ORDER);
        for ($cnt = 0; $cnt < $item_count; $cnt++) {
            $command = preg_replace($search_pattern, "##item_$cnt##", $command, 1);
        }
        //Split the command
        $num_pieces = count($pattern);
        $num_com = count(explode(' ', $command));
        $pieces = explode(' ', $command, $num_pieces);
        $com = array_combine(array_slice($pattern, 0, $num_com), $pieces);
        //Replace any item references with the original item strings.
        foreach ($com as &$com_item) {
            for ($cnt = 0; $cnt < $item_count; $cnt++) {
                $com_item = str_replace("##item_$cnt##", $items[$cnt][0], $com_item);
            }
        }
        unset($com_item);
        if (!isset($com['sub'])) {
            $com['sub'] = "";
        }
        if (!isset($com['args'])) {
            $com['args'] = "";
        }
        return ($com);
    }


    /************************************************************************
     * Default to replying in the same channel as the command has been received
     *************************************************************************/
    public function reply($name, $msg)
    {
        if ($msg != false) {
            if ($msg instanceof BotError) {
                //We got an error. Return the error message.
                $this->reply($name, $msg->message());
            } else {
                $this->output_destination($name, "##normal##$msg##end##", SAME);
            }
        }
    }


    public function tell($name, $msg)
    {
        $this->source = TELL;
        $this->error->reset();
        $reply = $this->command_handler($name, $msg, "tell");
        if (($reply !== false) && ($reply !== '')) {
            $this->reply($name, $reply);
        }
    }


    public function gc($name, $msg)
    {
        $this->source = GC;
        $this->error->reset();
        $reply = $this->command_handler($name, $msg, "gc");
        if (($reply !== false) && ($reply !== '')) {
            $this->reply($name, $reply);
        }
    }


    public function pgmsg($name, $msg)
    {
        $this->source = PG;
        $this->error->reset();
        $reply = $this->command_handler($name, $msg, "pgmsg");
        if (($reply !== false) && ($reply !== '')) {
            $this->reply($name, $reply);
        }
    }
}

?>
