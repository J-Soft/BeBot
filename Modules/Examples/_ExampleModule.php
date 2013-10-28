<?php
/*
* _ExampleModule.php - Module template.
* <module name> - <module description>
* <Copyright notice for your module>
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
/*
Add a "_" at the beginning of the file (_ClassName.php) if you do not want it to be loaded.
*/
$thisClass = new ClassName($bot);
/*
The Class itself...
*/
class ClassName extends BaseActiveModule
{

    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    Defines access control for the commands
    Creates settings for the module
    Defines help for the commands
    */
    function __construct(&$bot)
    {
        //Initialize the base module
        parent::__construct($bot, get_class($this));
        /*
                Register commands with the bot.
                Possible values for $channel:
                tell (incomeing tell)
                pgmsg (message in privategroup)
                gc (message in guildchat)
                all (tell, pgmsg, gc at once)
                extpgmsg (external private group, this is not covered by the command_handler() on default)

                $command should be a string with the command the module should react to

                $access_level is the default access level for the command

                $sub_access_levels is an array of entries in the format "subcommand" => "level" to define the default access level for subcommands
        */
        $this->register_command($channel, $command, $access_level, $sub_access_levels);
        /*
                Register events with the bot
                Events are triggered without the use of a command. This can be joins, all tower-messages and so on
        */
        $this->register_event($event);
        /*
               possible values for $event are
               pgjoin (Someone joining priv group)
               pgleave (Someone leaving priv group)
               extpgjoin (someone joins an external private group)
               extpgleave (someone leaves an external private group)
               buddy (Buddy logging on/off)
               connect (bot connects)
               disconnect (bot disconnects)
               privgroup (processes non-command text in the private group)
               extprivgroup (processes non-command text in external private chat groups)
               gmsg, group (message in some group. NOTE: Replace the "group" with the group you want to listen to. For example: $this->register_event('gmsg', 'IRRK News Wire');
               gmsg, "org" (catch any org chat)
               cron, interval (cron jobs that occur every 'interval')
                   interval can be
                   1sec (Be very carefull with this. Too many 1 second cronjobs will slow the bot down. Use only when you absolutely need close to realtime turnover)
                   2sec
                   5sec
                   10sec
                   30sec
                   1min
                   2min
                   5min
                   1hour
                   3hour
                   6hour
                   12hour
                   18 hour
                   24hour
                   example: $this->register_event('cron', '2min');

        */
        /*
            Create settings for this module. The settings contains seven parts
            'module' is the module or settings group in which the setting should be placed
            'setting' is the name of the setting as referred to by the modules
            'default' is the default value of the module
            'description of setting' is a description of what the setting is for. This is used in the settings interface.
            'option1;option2;option3' are the availible options for the setting.
        */
        $this->bot->core("settings")
            ->create('module', 'setting', 'default', 'description of setting', 'option1;option2;option3');
        /*
            Create preferences for this module. The preferences contains six parts
            'module' is the module or preference group in which the preference should be placed
            'name' is the name of the preference as referred to by the modules
            'description' is a description of the preference. This is used in the preference interface
            'default' is the default value of the preference.
            'option1;option2' is a semicolon (;) separated list of possible options
            'access' is the access level required to acces the setting. This can be one of GUEST, MEMBER, ADMIN, SUPERADMIN or OWNER
        */
        $this->bot->core("prefs")
            ->create("module", "name", "description", "default", "option1;option2", "access");
        /*
            Create help for this module
            'description' is a brief description of what the module does
            'command' is an array that holds
                'command1' is one of the commands handled by this module
                'command1 <keyword>' is what the command does when given the <keyword>
            'notes' are notes that are useful to know.
        */
        $this->help['description'] = 'Description of the module';
        $this->help['command']['command1'] = "What does command1 do without any keywords";
        $this->help['command']['command1 <keyword>'] = "What does command1 do with keyword";
        $this->help['command']['command2 <keyword> [param]'] = "What does command2 do with <keyword> and the optional [param]";
        $this->help['notes'] = "Notes for the help goes in here.";
    }


    /*
    Unified message handler
    $source: The originating player
    $msg: The actual message, including command prefix and all
    $type: The channel the message arrived from. This can be either "tell", "pgmsg" or "gc"
    */
    function command_handler($source, $msg, $origin)
    {
        //ALWAYS reset the error handler before parsing the commands to prevent stale errors from giving false reports
        $this->error->reset();
        //The default is to split the command to com, sub and args. If you want to split it some other way change the pattern for it
        //parse_com() returns an array where the pattern is the keys and the values are split out from $msg
        $com = $this->parse_com(
            $msg,
            array(
                 'com',
                 'sub',
                 'args'
            )
        );
        $command = $vars[0];
        switch ($com['com']) {
            case 'command1':
                return ($this->somefunction($name, $com));
                break;
            case 'command2':
                return ($this->someotherfunction($name, $com));
            default:
                // Just a safety net to allow you to catch errors where a module has registered  a command, but fails to actually do anything about it
                $this->error->set("Broken plugin, received unhandled command: $command");
                return ($this->error->message());
        }
    }


    /*
    This gets called on a msg in the group if you previously registered the event 'gmsg, group'
    */
    function gmsg($name, $group, $msg)
    {
    }


    /*
    This gets called on a msg in the privgroup without a command if you previously registered the event 'privgroup'
    */
    function privgroup($name, $msg)
    {
    }


    /*
    This gets called if someone joins the privgroup if you previously registered the event 'pgjoin'
    */
    function pgjoin($name)
    {
    }


    /*
    This gets called if someone leaves the privgroup if you previously registered the event 'pgleave'
    */
    function pgleave($name)
    {
    }


    /*
    This gets called if a buddy logs on/off  if you previously registered the event 'buddy'
    */
    function buddy($name, $msg)
    {
    }


    /*
    This gets called on cron  if you previously registered the event 'cron, interval'
    $interval is the string name for the time delay between two cron jobs.
    */
    function cron($interval)
    {
    }


    /*
    This gets called when bot connects  if you previously registered the event 'connect'
    */
    function connect()
    {
    }


    /*
    This gets called when bot disconnects  if you previously registered the event 'disconnect'
    */
    function disconnect()
    {
    }
}

?>
