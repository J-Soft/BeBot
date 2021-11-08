<?php
/*
* Gsp.php - Module Grid Stream Production (last track and events starts)
* Inspired from Nadyita's work & based on https://gsp.torontocast.stream/streaminfo/
*
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

$gsp = new Gsp($bot);

class Gsp extends BaseActiveModule
{

    var $live;
    var $name;
    var $info;	

    function __construct(&$bot)
    {

        parent::__construct($bot, get_class($this));
        $this->live = 0;
        $this->name = "";
        $this->info = "";
		$this->register_event("cron", "1min");
		$this->register_command("all", "gsp", 'GUEST');
        $this->bot->core("settings")
            ->create(
                "Gsp",
                "Channels",
                "none",
                "To which channel(s) should GSP events be announced?",
                "guild;pgroup;both;none"
            );
        $this->bot->core("settings")
            ->create("Gsp", "AlertDisc", false, "Do we alert Discord of Gsp events ?");
        $this->bot->core("settings")
            ->create("Gsp", "DiscChanId", "", "What Discord ChannelId in case we separate Gsp events from main Discord channel (leave empty for all in main channel) ?");
        $this->bot->core("settings")
            ->create("Gsp", "AlertIrc", false, "Do we alert Irc of Gsp events ?");			
        $this->help['description'] = "Shows elements about GSP live/track.";
        $this->help['command']['gsp'] = "Shows track currently played on GSP.";
        $this->help['notes'] = "GSP event start is announced automatically if set so.";	

    }
	
	function command_handler($name, $msg, $origin)
	{
        $return = false;
        $vars = explode(' ', strtolower($msg));
        $command = $vars[0];
        switch ($command) {
            case 'gsp':
                return $this->last_track();
                break;
            default:
                return "Broken plugin, received unhandled command: $command";
        }
	}

	function last_track()
    {
			$content = $this->bot->core("tools")->get_site("https://gsp.torontocast.stream/streaminfo/");
			$return = "Couldn't reach GSP information ...";
			if (!($content instanceof BotError)) {
				if (strpos($content, '{"live":') !== false) {
					$datas = json_decode($content);
					$count = count($datas->history);
					if ($count>0) {
						$durat = floor($datas->history[0]->duration/1000);
						$left = $durat;
						$hour = floor($left/3600);
						$left = $left - ($hour*3600);
						$min = floor($left/60);
						$sec = $left - ($min*60);
						if ($sec < 10) { $sec = "0".$sec; }
						if ($hour < 10) { $hour = "0".$hour; }
						if ($min < 10) { $min = "0".$min; }
						if($hour=="00") {
							$hms = $min.":".$sec;
						} else {
							$hms = $hour.":".$min.":".$sec;
						}
						$artist = $datas->history[0]->artist;
						$title = "'".$datas->history[0]->title."'";
						$return = $artist."\n ".$title." (".$hms.")";
					}
					$live = $datas->live;
					$name = $datas->name;
					$info = $datas->info;
					if($live==1) {
						$return .= "\n\nGSP event running : ".$name." (".$info.")";
					} else {
						$return .= "\n\nNo GSP event is going on at this moment ...";
					}
				}
			}
			$return .= "\n\n".$this->bot->core("tools")->chatcmd("https://gsp.torontocast.stream/", "Join the stream", "start");
		return $this->bot->core("tools")
                ->make_blob("Currently on GSP", $return);		
    }
	
	function cron()
    {	
		$channels = $this->bot->core("settings")->get("Gsp", "Channels");
		if ($channels != "none") {
			$content = $this->bot->core("tools")->get_site("https://gsp.torontocast.stream/streaminfo/");
			if (!($content instanceof BotError)) {
				if (strpos($content, '{"live":') !== false) {
					$datas = json_decode($content);
					$live = $datas->live;
					$name = $datas->name;
					$info = $datas->info;
					if($this->live!=$live) {
						if($live==1) {
							$msg = "GSP event starting : ".$name." (".$info.")";
							if ($channels == "pgroup" || $channels == "both") {
								$this->bot->send_pgroup($msg);
							}
							if ($channels == "guild" || $channels == "both") {
								$this->bot->send_gc($msg);
							}
							if ($this->bot->exists_module("discord")&&$this->bot->core("settings")->get("Gsp", "AlertDisc")) {
								if($this->bot->core("settings")->get("Gsp", "DiscChanId")) { $chan = $this->bot->core("settings")->get("Gsp", "DiscChanId"); } else { $chan = ""; }
								$this->bot->core("discord")->disc_alert($msg, $chan);
							}
							if ($this->bot->exists_module("irc")&&$this->bot->core("settings")->get("Gsp", "AlertIrc")) {
								$this->bot->core("irc")->send_irc("", "", $msg);
							}							
						}
						$this->live = $live;
						$this->name = $name;
						$this->info = $info;
					}
				}
			}
		}
    }

}

?>
