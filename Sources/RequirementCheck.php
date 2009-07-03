<?php
//From Main.php
/*
Detect if we are being run from a shell or if someone is stupid enough to try and run from a web browser.
*/
if ((!empty($_SERVER['HTTP_HOST'])) || (!empty($_SERVER['HTTP_USER_AGENT'])))
{
	die("BeBot does not support being run from a web server and it is inherently dangerous to do so!\nFor your own good and for the safety of your account information, please do not attempt to run BeBot from a web server!");
}

// The minimum required PHP version to run.
if((float)phpversion() < 5.2)
{
	die("BeBot requires PHP version 5.2.0 or later to work.\n");
}

/*
Load extentions we need
*/

if (!extension_loaded("sockets"))
{
	if ($os_windows)
	{
		if (!dl("php_sockets.dll"))
		{
			die("Loading php_sockets.dll failed. Sockets extention required to run this bot");
		}
	}
	else
	{
		die("Sockets extention required to run this bot");
	}

}

if (!extension_loaded("mysql"))
{
	if ($os_windows)
	{
		if (!dl("php_mysql.dll"))
		{
			die("Loading php_mysql.dll failed. MySQL extention required to run this bot");
		}
	}
	else
	{
		die("MySQL support required to run this bot");
	}
}

//From AOChat.php
// The minimum required PHP version to run.
if((float)phpversion() < 5.2)
{
	die("AOChat class needs PHP version >= 5.2.0 to work.\n");
}

// We need sockets to work
if(!extension_loaded("sockets"))
{
	die("AOChat class needs the Sockets extension to work.\n");
}

// For Authentication we need gmp or bcmath

if(!extension_loaded("bcmath"))
{
	die("AOChat class needs the BCMath extension to work.\n");
}

// Check if we have curl available
if (!extension_loaded("curl"))
{
	if ($os_windows)
	{
		if (@!dl("php_curl.dll"))
		{
			echo "Curl not available\n";
		}
		else
		{
			echo "Curl extension loaded\n";
		}			
	}
	else if(function_exists('curl_init'))
	{
		echo "Curl extension loaded\n";
	}	
	else
	{
		echo "Curl not available\n";
	}
}

?>