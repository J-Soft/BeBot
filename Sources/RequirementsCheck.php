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
* - Bitnykk (RK5)
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
//From Main.php
/*
Detect if we are being run from a shell or if someone is stupid enough to try and run from a web browser.
*/
if ((!empty($_SERVER['HTTP_HOST'])) || (!empty($_SERVER['HTTP_USER_AGENT']))) {
    die("BeBot does not support being run from a web server and it is inherently dangerous to do so!\nFor your own good and for the safety of your account information, please do not attempt to run BeBot from a web server!");
}
// The minimum required PHP version to run.
if ((float)phpversion() < 5.4) {
    die("BeBot requires PHP version 5.4.0 or later to work.\n");
}
// The recommended PHP version to run.
if ((float)phpversion() < 8.0) {
    echo "BeBot recommends PHP version 8.0.0 or later to run.\n";
}
/*
Load extentions we need
*/
if (!extension_loaded("sockets")) {
    if ('OS_WINDOWS') {
        if (!dl("php_sockets.dll")) {
            die("Loading php_sockets.dll failed. Sockets extention required to run this bot");
        }
    } else {
        if (!dl("sockets.so")) {
            die("Loading sockets.so failed. Sockets extention required to run this bot");
        }
    }
}
if (!extension_loaded("mysqli")) {
    if ('OS_WINDOWS') {
        if (!dl("php_mysqli.dll")) {
            die("Loading php_mysqli.dll failed. MySQLi extention required to run this bot");
        }
    } else {
        if (!dl("mysqli.so")) {
            die("Loading mysqli.so failed. MySQLi extention required to run this bot");
        }
    }
}
if (!extension_loaded("mbstring")) {
    if ('OS_WINDOWS') {
        if (!dl("php_mbstring.dll")) {
            die("Loading php_mbstring.dll failed. MbString extention required to run this bot");
        }
    } else {
        if (!dl("mbstring.so")) {
            die("Loading mbstrin.so failed. MbString extention required to run this bot");
        }
    }
}
if (!extension_loaded("bcmath")) {
    if ('OS_WINDOWS') {
        if (!dl("php_bcmath.dll")) {
            die("Loading php_bcmath.dll failed. BcMath extention required to run this bot");
        }
    } else {
        if (!dl("bcmath.so")) {
            die("Loading bcmath.so failed. BcMath extention required to run this bot");
        }
    }
}
//From AOChat.php
// The minimum required PHP version to run.
if ((float)phpversion() < 5.2) {
    die("AOChat class needs PHP version >= 5.2.0 to work.\n");
}
// We need sockets to work
if (!extension_loaded("sockets")) {
    die("AOChat class needs the Sockets extension to work.\n");
}
// For Authentication we need gmp or bcmath
if (!extension_loaded("bcmath")) {
    die("AOChat class needs the BCMath extension to work.\n");
}
// Check if we have curl available
if (!extension_loaded("curl")) {
    if ('OS_WINDOWS') {
        if (@!dl("php_curl.dll")) {
            echo "Curl not available\n";
        } else {
            echo "Curl extension loaded\n";
        }
    } else {
        if (function_exists('curl_init')) {
            echo "Curl extension loaded\n";
        } else {
            echo "Curl not available\n";
        }
    }
}
?>
