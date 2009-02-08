Please note, this is a text version of the documentation available at http://bebot.shadow-realm.org/wiki/installation
Due to the nature of a wiki, information may be updated at a rapid pace, so whenever possible please refer to the wiki for up to date instructions and general documentation.

Installing bebot 0.4

This installation documentation is a work-in-progress. Currently it’s a merge of the old install documentation with some adaptions.

TO DO:

    *
      Version information on PHP needs to be checked
    *
      Write up installation instructions for PHP on windows
    *
      Write up installation instructions for PHP/MySQL on *nix
    *
      Instructions on how to get and install the bot files needs to be checked when 0.4 is released

(note: This is a wiki. All contributors are welcome to fix things as they find them)
Prerequisites (What do you need?)

To install and run BeBot you need the following:

    *
      Anarchy Online account (free or paid)
    *
      Anarchy Online game (To create the player that is the bot)
    *
      PHP version >= 4.4
    *
      MySQL database server version >= 4.1.1
    *
      The BeBot files

Optional (helps run it effectively):

    *
      An “always-on” internet connection
    *
      A dedicated machine to run the bot

Getting started
Create a player

A bot is basically a regular player toon so you’ll need to start Anarchy Online and create a toon on the appropriate dimention. The breed, gender and profession is irrelevant for the function of the bot. The name of the toon will be the name of the bot. You can use an already existing player.
General notes

You can log on and play with another player on the same account as the bot runs from. However if you attempt to log on as the bot toon while it is running the bot will be disconnected from the chat server.
Notes for guild-style bot

A guild bot needs to be a member of the guild it is to be a bot for. This means that it needs to have the same faction as the guild.
Notes on towers

For tower wars to work as intended the bot needs to be in the top three ranks of an org. This is because the [ALL TOWERS] channel is restricted to these ranks.
Install the bot files

To install the bot simply unzip the files to a directory maintaining the directory structure.
Configuring the bot
StartBot.php

Open the file StartBot.php which is located in the install directory in a text editor. Here you need to set the $php_bin to the path and name of your php executable. (Line number 42 for windows, 63 for *nix). If you start the bot from the same directory as the binary php file (for windows “php.exe”) you can just enter the name. Otherwise you’ll have to enter the name and path of the binary executable (ie C:/Program files/PHP/php.exe or C:\\Programfiles\\PHP\\php.exe or /usr/local/bin/php5) Note that you can use forward slashes (recomended) or double backslashes (not recomended) on windows systems. Using single backslashes WILL NOT WORK as php iterpets \ as a special escape character.

Set $main_php to the location of the “Main.php” file (line number 49 and 64 for windows and *nix respectively). Once again you can just enter “Main.php” if you’re starting the bot from the directory its in.

If StartBot.php is in another directory than the bot itself (not recomended) you must specify the complete path to Main.php
conf/Bot.conf

Open the file Bot.conf, which is in the conf directory where you installed the bot, in a text editor. Here you will have to enter the Anarchy-Online username, password and the name of the bot along with the dimension number you would like to run it on.

$owner is the super-duper admin of the bot. This user has got all rights. This should be the name of the toon which you intend to configure the bot with as some settings require you to be owner in order to change.

Below that is the configuration lines for your super admins. You can add as many superadmins as you like. Just copy and past that line and exchange the name. These names cannot be downgraded in-game so it is recomended that you hard-code as few super admins here as possible. Instead of putting the names in here we recomend using the !adduser <username> SUPERADMIN in-game to add superadmins.

The next section works just like the superadmins. You can tell the bot what other bots it may encounter in the guild. This is so that the bot just ignores tells and messages from the other bot and doesn’t end up in a spam war with it.

For setting up the guild bot section please see bottom of this section

To switch logging off set “$log” to “off”. You can also set it to “chat” which will only log incoming and outgoing messages. If set to “all” everything displayed on the console will be put into the log. The default is “chat”. Set “$log_path” to the place where you want logs to be saved.

With “$command_prefix” you can determin what symbol commands start with. The default here is “!”. Note that you need to use the regex string here. This means that to use “.” as your command prefix you need to enter “\.” because “.” has got a special meaning in regular expressions. This goes for a lot of other characters as well.

The last 4 values are probably best left at default.
Guild bot ONLY

To make a guild bot set “$guildbot” to true. Change “$guild_name” to the exact name of your guild. Set “$guild_id” to the id of your guild. The easiest way to find out the guild ID is going to http://www.anarchy-online.com/content/community/people/ and finding your guild. The URL will now read something like this: “http://www.anarchy-online.com/org/stats/d/1/name/xxxxxx” where the “xxxxxx” would be your guild ID. To relay chat to another guild bot you want to set $guild_relay_target = “Name_of_other_guildbot”; If you do not want this feature set $guild_relay_target = False; (note: False, not “False”. If you use quotes your bot will attempt to relay to the player named ‘False’)
Raid bot ONLY

To make a raid bot set “$guildbot” to false. leave “$guild_name” blank. Set “$guild_id” to 0. For a raid bot you most likely want to set $guild_relay_target = False; (note: False, not “False”)
conf/MySQL.conf

Set the MySQL username, password, server IP adress or server host name and database name for the bot.
Starting the bot

    *
      Open a console (In windows press “Start” ? “Run” ? enter “cmd” and press enter).
    *
      Now run the start.php
    *
      Windows: Navigate to the directory of your bot assuming you have the php.exe in the same directory and write “php start.php”.
    *
      Linux: Assuming your can run php5 from anywhere navigate to your bot directory and write “php5 start.php” (assuming your php binary is named “php5”).

The console should now state that the bot is loading the modules, authenticating and connecting. Once this is done (it should only take a few seconds) You can log onto an character ingame which you have configured as “superadmin”. You should now be able to talk to the bot.
In-game setup

Starting with version 0.3 there is a new settings module to handle configuration of most aspects and many modules for BeBot. Most notable is the Security module. To start configuring it send a tell to your bot with “!settings security”. For a list of modules whose settings are configurable by the new interface send a tell to your bot with “!settings”.
Adding and Removing Modules

If you do not wish for certain modules to be used by a bot just put an underscore (”_”) at the front of the name in the “modules” directory. There are several files already in the distribution that have been commented out in this way: Most notable is _ExampleModule.php which is a template to make your own modules.

The three files with “1k” at the end are replacement modules for the three others incase your raidbot has more than 1000 members. In that case remove the underscore from those three files and add it to the other three appropriate modules.
Installing 3rd party modules

We recomend you put any 3rd party modules into the directory named custom instead of putting it directly into the modules or core directories. This is because if a 3rd party module has got the same name as a standard module or a module with that name is added later it will be over-written if you upgrade the bot. Modules in the ‘custom’ directory are never touched by installing upgrades to BeBot.
Installing MySQL on windows

First, you’ll need MySQL. Download the Windows Essentials package from MySQL.com. This is a MSI based installer. If it doesn’t work for you, go to Microsoft Update to install the latest Windows Installer.

The installer is simple.
Phase 1: MySQL Installer

   1.
      Click Next.
   2.
      Select Typical, Click Next.
   3.
      Click Install.
   4.
      Select Skip Sign-Up, Click Next.
   5.
      Check Configure MySQL Server Now, Click Finish.

Now you should be in the MySQL Server Instance Configuration Wizzard.
Phase 2: MySQL Server Instance Configuration Wizzard

   1.
      Click Next.
   2.
      Select Detailed Configuration, Click Next.
   3.
      Select Developer Machine, Click Next.
   4.
      Select Multifunctional Database, Click Next.
   5.
      Select C: and Installation Path, Click Next.
   6.
      Select Decision Support, Click Next.
   7.
      Check Enable TCP/IP Networking and Enable Strict Mode, Click Next.
   8.
      Select Standard Character Set, Click Next.
   9.
      Check Install As Windows Servers, Check Launch the MySQL Server automatically, Check Include Bin Directory in Windows PATH, Click Next.
  10.
      Enter and confim your root password. Write down your root password and do not loose it!!! Do not check Enable root access from remote machines. Click Next.
  11.
      Click Execute.
  12.
      Click Finish. Reboot your computer.

At this point, you have MySQL installed and configured, but you haven’t yet created a MySQL database.
Phase 3: Logging into MySQL

   1.
      Open a command prompt. (Start > Run > CMD, Click OK)
   2.
      In the command prompt, enter the command mysql -u root -p
   3.
      Enter your root password (You did write it down so you wouldn’t forget it right?)

You should now have a MySQL prompt that looks like: mysql>
Phase 4: Create a Database and Database User for BeBot

   1.
      Type CREATE DATABASE databasename CHARACTER SET latin1; (replace databasename with the name of the database you wish to create)
   2.
      Press Enter/Return.
   3.
      CREATE USER username@localhost; (Change username to the username you want, keep @localhost)
   4.
      Press Enter/Return.
   5.
      SET PASSWORD FOR username@localhost = PASSWORD(’newpassword’); (Change username and newpassword to your selected username and password, again keep @localhost)
   6.
      Press Enter/Return.
   7.
      Type GRANT ALL on databasename.* TO username@localhost; (Again, change username, keep @localhost)
   8.
      Press Enter/Return.
   9.
      Type quit then Enter/Return to exit the MySQL Monitor.

Phase 5: Configure BeBot

   1.
      Edit MySQL.conf, add the values you selected.

  $dbase = "databasename";
  $user = "username";
  $pass = "newpassword";
  $server = "localhost";

That’s it, you should now be able to start the bot provided PHP is set up correctly.
Backing Up and Restoring your Database

It’s reccomended that you backup your database. If the worst happens, you can easily recover. And the command to do so is simple: mysqldump -u username -p –databases databasename –add-drop-table -a -f > filename.sql
Restoring a database fom a backup

To restor the database from a previously made backup: mysql -u username -p databasename < filename.sql IMPORTANT: All changes made to the guild and bot after the backup WILL BE OVERWRITTEN!
