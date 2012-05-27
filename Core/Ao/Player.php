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
class Player
{
    //Game spesific variables
    private $uid = FALSE;
    private $uname = FALSE; //aka nickname
    private $firstname = FALSE;
    private $lastname = FALSE;
    private $breed = FALSE;
    private $gender = FALSE;
    private $level = FALSE;
    private $profession = FALSE;
    private $ai_level = FALSE;
    private $organization = FALSE;
    private $org_rank = FALSE;
    //Bot spesific variables
    private $accesslevel = FALSE;
    private $user_level = FALSE;
    private $preferences = array();


    //When constructing a new player we need to have the bot handle so that the
    //class can look up certain variables automagically.
    public function __construct(&$bothandle, $data)
    {
        $this->bot = $bothandle;
        $this->error = new BotError($this->bot, get_class($this));
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }


    /*
        This function allows coders to use $player->uid instead of player->get_uid() when wanting to
        access a variable while still allowing the class to look up any values it has not already cached.
    */
    public function _get($variable)
    {
        switch ($variable) {
        case 'uid':
        case 'id':
            return ($this->get_uid());
            break;
        case 'uname':
        case 'nick':
        case 'nickname':
            return ($this->get_uname());
            break;
        case 'firstname':
        case 'lastname':
        case 'breed':
        case 'gender':
        case 'level':
        case 'profession':
        case 'ai_level':
        case 'ai_rank':
        case 'organization':
        case 'org_rank':
            return ($this->get_whois($variable));
            break;
        case 'pref':
        case 'preferences':
            return ($this->get_preferences($variable));
            break;
        default:
            $this->error->set("Unknown attribute '$variable'.");
            return $this->error;
            break;
        }
    }


    public function get_uid($uname)
    {
        //Make sure we have the uid at hand.
        if (!$this->uid) {
            $this->uid = $this->bot->core('player')->get_uid($uname);
            if ($this->uid instanceof BotError) {
                //The uid could not be resolved.
                $this->error = $this->uid;
                $this->uid = FALSE;
                return $this->error;
            }
        }
        return $this->uid;
    }


    public function get_uname($uid)
    {
        //Make sure we have the uname at hand.
        if (!$this->uname) {
            $this->uname = $this->bot->core('player')->get_uname($uid);
            if ($this->uname instanceof BotError) {
                //The uid could not be resolved.
                $this->error = $this->uname;
                $this->uname = 'Unknown';
                return $this->error;
            }
        }
        return $this->uid;
    }


    public function get_whois($attribute)
    {
        //Make sure we have the attribute at hand.
        if (!$this->$attribute) {
            //Make sure we have a uname
            if (!$this->uname) {
                //If we don't have a uname already we should have an uid.
                $this->get_uname($this->uid);
            }
            $data = $this->bot->core('whois')->lookup($this->uname);
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
        return ($this->$attribute);
    }


    //Lookup the preferences in the table if we haven't already done that.
    public function get_preferences($variable)
    {
    }
}

?>
