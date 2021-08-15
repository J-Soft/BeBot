<?php
/*
* Ding.php - Module template.
* Ding - Send an org message when you ding :)
* Sevrius Veeshan - Copyright (C) 2018
* Edited by Bitnykk from Budabot's work by Neksus / Mdkdoc420 / Tyrence
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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

$ding = new Ding($bot);

class Ding extends BaseActiveModule
{
	function __construct (&$bot)
	{
		// Initialize the base module
		parent::__construct($bot, get_class($this));
		
		// SL/AI Events
        $this->bot->dispatcher->connect(
            'Core.on_sl220',
            array(
                 $this,
                 'sl220'
            )
        );
        $this->bot->dispatcher->connect(
            'Core.on_ai30',
            array(
                 $this,
                 'ai30'
            )
        );		

		// Register command
		$this->register_command('all', 'ding', 'GUEST');

		// Add description/help
		$this -> help['description'] = "Display a 'Ding' message on the org channel.";
		$this -> help['command']['ding <level>'] = "Display a 'Ding' message with the <level> on the org channel.";
	}

	function command_handler($source, $msg, $origin)
	{		
		$this->error->reset();
		
		$vars = explode(' ', strtolower($msg));
		$command = $vars[0];

		switch($command)
		{
			case 'ding':			
				return $this -> send_ding($source, $vars[1]);
				break;			
			default:				
				$this -> error -> set("Broken plugin, received unhandled command: $command");
				return($this->error->message());
		}
	}
	
    public function sl220($data)
    {
		if(isset($data['player'])) {
            $user_check = $this->bot->db->select(
                "SELECT user_level FROM #___users WHERE nickname = '" . $data['player'] . "'",
                MYSQLI_ASSOC
            );
			if(isset($user_check[0]['user_level']) && $user_check[0]['user_level']>0) {
				$user_update = $this->bot->db->select(
					"UPDATE #___whois SET level=220 WHERE nickname = '" . $data['player'] . "'",
					MYSQLI_ASSOC
				);
				$this->bot->send_tell($data['player'], "GG for your 220 ding!!!");
			}
		}
	}

    public function ai30($data)
    {
		if(isset($data['player'])) {
            $user_check = $this->bot->db->select(
                "SELECT user_level FROM #___users WHERE nickname = '" . $data['player'] . "'",
                MYSQLI_ASSOC
            );
			if(isset($user_check[0]['user_level']) && $user_check[0]['user_level']>0) {
				$user_update = $this->bot->db->select(
					"UPDATE #___whois SET defender_rank_id=30 WHERE nickname = '" . $data['player'] . "'",
					MYSQLI_ASSOC
				);
				$this->bot->send_tell($data['player'], "GG for your AI lvl 30!!!");
			}			
		}
	}	
	
	function send_ding($name, $level)
	{
		if ($level < 2) {
			$dingText = array(
				"You didn't even start yet...",
				"Did you somehow start from level 0?",
				"Dinged from 0 to 1? Congratz xD");
		} else if ($level == 100) {
			$dingText = array(
				"Congratz! Level 100 - you rock!",
				"Congratulations! Time to twink up for T.I.M!",
				"Gratz, you're half way to 200. More missions, MORE!",
				"Woot! Congrats, don't forget to put on your 1k token board.");
		} else if ($level == 150) {
			$dingText = array(
				"S10 time!!!",
				"Time to ungimp yourself! Horray!. Congrats =)",
				"What starts with A, and ends with Z? ALIUMZ!",
				"Wow, is it that time already? TL 5 really? You sure are moving along! Gratz");
		} else if ($level == 180) {
			$dingText = array(
				"Congratz! Now go kill some aliumz at S13/28/35/42!!",
				"Only 20 more froob levels to go! HOORAH!",
				"Yay, only 10 more levels until TL 6! Way to go!");
		} else if ($level == 190) {
			$dingText = array(
				"Wow holy shiznits! You're TL 6 already? Congrats!",
				"Just a few more steps and you're there buddy, keep it up!",
				"Almost party time! just a bit more to go. We'll be sure to bring you a cookie!");
		} else if ($level == 200) {
			$dingText = array(
				"Congratz! The big Two Zero Zero!!! Party at ICC !",
				"Best of the best in froob terms, congratulations!",
				"What a day indeed. Finally done with froob levels. Way to go!");
		} else if ($level > 200 && $level < 220) {
			$dingText = array(
				"Congratz! Just a few more levels to go!",
				"Enough with the dingin you are making the fr00bs feel bad!",
				"Come on save some dings for the rest!");
		} else if ($level == 220) {
			$dingText = array(
				"Congratz! You have reached the end of the line! No more fun for you :P",
				"Holy shit, you finally made it! What an accomplishment... Congratulations for reaching a level reserved for the greatest!",
				"I'm going to miss you a great deal, because after this, we no longer can be together. We must part so you can continue getting your research and AI levels done! Farewell!",
				"How was the inferno grind? I'm glad to see you made it through, and congratulations for finally getting the level you well deserved!",
				"Our congratulations, to our newest level 220 member for his dedication. We present him with his new honorary rank, enlighted!");
		} else if ($level > 220) {
			$dingText = array(
				"Umm...no.",
				"You must be high, because that number is too high...",
				"Ha, ha... ha, yeah... no...",
				"You must be a GM or one hell of an exploiter, that number it too high!",
				"Yeah, and I'm Chuck Norris...",
				"Not now, not later, not ever... find a more reasonable level!");
		} else {
			$dingText = array(
				"Ding ding ding... now ding some more!",
				"Keep em coming!",
				"Don't stop now, you're getting there!",
				"Come on, COME ON! Only few more levels to go until 220!");
		}		
		$rand = array_rand($dingText,1);
		return($dingText[$rand]);
	}
}
?>
