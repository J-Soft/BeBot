This directory contains a script to update the entries in the whois cache outside of the bot. To do this it queries the whois cache for all existing orgs first. It reads in all org roster from the FC servers, and updates all characters in those organisations. Characters in the org roster but not yet in the cache are added to the cache during this step. After all organisation informations are processed the remaining not yet updated characters are queried on the FC site and updated if they still exist. Stale entries - this means entries that haven't been updated for some time - are deleted out of the cache after a user defined time. Usually those are deleted characters.
Depending on the size of the cache those updates can take several hours.

BeBot does update outdated entries itself, the script takes the whole load out of the bot though, possible reducing lag that way.


SETUP:
To setup the script you need to enter information into the whois-update.conf file. Which information is needed is written in the comments in that file.
After entering all information just start the php file with something like "php whois-update.php". The script should start working now. When it is finished it outputs some statistical data about the updating and the cache.


PHP SETUP:
If you recieve the error:
Fatal error: Call to undefined function: mysql_connect()
You may need to add the following line to your PHP.ini file.

Windows:
extension=php_mysql.dll

Linux:
extension=mysql.so


OPTIONAL:
If you want to run some php file after all the updating is done you can add an whois-update.addons file. If this file exists it will be included at the end of whois-update.php


EXAMPLE BATCH FILE:
If you want to just simply run an update periodically, there is an example batch file (Update Whois Cache.bat).  This batch file assumes that php.exe is installed to the bots root directory.  You may need to change the paths to the PHP excecutable and the whois-update.php file.