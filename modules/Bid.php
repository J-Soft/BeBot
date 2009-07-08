<?php
/*
* Bid.php - Raid bidding.
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2009 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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
* See Credits file for all aknowledgements.
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
$bid = new Bid($bot);
/*
The Class itself...
*/
class Bid extends BaseActiveModule
{
	var $help;
	var $bid;
	var $maxbid;
	var $secondbid;
	var $highestbidder;
	var $announce;
	var $announced;
	var $end;

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this->bid = "";
		$this->register_command('all', 'bid', 'MEMBER');
		$this->help['description'] = "Handles auctions using raid points";
		$this->help['command']['bid start <item>'] = "Starts an auction for <item>. <item> can be text or an item ref.";
		$this->help['command']['bid <points>'] = "Bid <points> raid points for the item currently on auction.";
		$this->help['command']['bid info'] = "Shows information about the current auction.";
		$this->help['command']['bid cancel'] = "Cancel the current auction.";
		$this->help['command']['bid [lock|unlock]'] = "Lock or Unlock the Current Auction to Raid users.";
		$this->help['command']['bid [history|list]'] = "Shows last 20 auctions or all since restart if less.";
		$this->bot->core("settings")->create("bid", "timer", 60, "How Long shold a Auction Last?");
		$this->bot->core("settings")->create("bid", "raid_locked", FALSE, "Should Auction be Locked to Users in Raid?");
	}

	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		$msg = explode(" ", $msg, 3);
		Switch ($msg[1])
		{
			case 'start':
				return $this->start_bid($name, $msg[2]);
			case 'info':
				$this->info_bid($name);
				Break;
			case 'cancel':
				Return $this->cancel($name);
			case 'lock':
				Return $this->cancel($name, TRUE);
			case 'unlock':
				Return $this->lock($name, FALSE);
			case 'history':
			case 'list':
				Return $this->history();
			Default:
				if (is_numeric($msg[1]) || strtolower($msg[1]) == "all")
				{
					if ($origin == "tell")
						$this->place_bid($name, $msg[1]);
					else
						Return ("Bids in /tell Only");
				}
				else
					$this->bot->send_help($name, "bid");
		}
	}

	/*
	Starts the bidding
	*/
	function start_bid($name, $item)
	{
		if ($this->bot->core("security")->check_access($name, "leader"))
		{
			if ($this->bid && $this->bid != "")
			{
				Return ("##error##Error: Auction in Progress for item ##highlight##" . $this->bid . "##end####end##");
			}
			$itemref = explode(" ", $item, 5);
			if (strtolower($itemref[0]) == "&item&")
			{
				$item = $this->bot->core("tools")->make_item($itemref[1], $itemref[2], $itemref[3], $itemref[4], TRUE);
			}
			$this->bid = $item;
			$this->maxbid = 0;
			$this->name = $name;
			$this->secondbid = 0;
			$this->highestbidder = "";
			$this->locked = $this->bot->core("settings")->get("bid", "raid_locked");
			$this->announce = time() + 15;
			$this->announced = false;
			$timer = $this->bot->core("settings")->get("Bid", "timer");
			$this->end = time() + $timer;
			$this->register_event("cron", "2sec");
			$msg = "\n##highlight##-------------------------------------##end##\n";
			$msg .= "##highlight##$name##end## started auction ";
			$msg .= "on ##highlight##$item##end##! \nYou have ";
			$msg .= "##highlight##$timer seconds##end## to place bids :: " . $this->info();
			$msg .= "\n##highlight##-------------------------------------##end##";
			$this->bot->send_output("", $msg, "both");
		}
		else
			$this->bot->send_tell($name, "You must be a raidleader to do this");
	}

	function cancel($name)
	{
		if ($this->bot->core("security")->check_access($name, "leader"))
		{
			if ($this->bid && $this->bid != "")
			{
				$this->bot->send_output("", "Auction for item ##highlight##" . $this->bid . "##end## Canceled", "both");
				$this->bid = "";
				$this->type = FALSE;
				$this->unregister_event("cron", "2sec");
			}
			else
			{
				Return ("##error##Error: No Auction in Progress##end##");
			}
		}
		else
			$this->bot->send_tell($name, "You must be a raidleader to do this");
	}

	/*
	Place a bid
	*/
	function place_bid($name, $ammount)
	{
		$update = true;
		if (strtolower($ammount) == "all")
		{
			$ammount = $this->bot->db->select("SELECT " . $this->type . "_points FROM #___raid_points WHERE id = " . $this->points_to($name));
			if (! empty($ammount))
				$ammount = $ammount[0][0];
			else
				$ammount = 0;
		}
		if (empty($this->bid))
		{
			$this->bot->send_tell($name, "No auction in progress.");
			return false;
		}
		else if ($ammount < 1)
		{
			$this->bot->send_tell($name, "Min bid is set to ####highlight##1##end## raidpoints.");
			return false;
		}
		if ($this->locked)
		{
			if ($this->bot->exists_module("raid") && $this->bot->core("raid")->raid && ! isset($this->bot->core("raid")->user[$name]))
			{
				$this->bot->send_tell($name, "This Auction is Locked to Raid Users Only");
				return false;
			}
		}
		$result = $this->bot->db->select("SELECT points FROM #___raid_points WHERE id = " . $this->points_to($name));
		if (empty($result))
		{
			$this->bot->send_tell($name, "You appear to not have any points yet. No points table entry found.");
			return false;
		}
		$result = $result[0][0];
		$currenthigh = (($this->maxbid == $this->secondbid) ? ($this->maxbid) : ($this->secondbid + 1));
		$currenthighb = $this->highestbidder;
		if ($result < $ammount)
			$this->bot->send_tell($name, "You only have ##highlight##" . $result . "##end## raidpoints. Please place bid again.");
		else if ($this->highestbidder == $name)
		{
			if ($this->maxbid < $ammount)
			{
				if ($this->secondbid == $this->maxbid)
					$this->secondbid -= 1;
				$this->maxbid = $ammount;
				$this->bot->send_tell($name, "Max bid Changed to ##highlight##$ammount##end##.");
				return false;
			}
		}
		else if ($this->maxbid == $ammount)
		{
			$this->secondbid = $ammount;
		}
		else if ($this->maxbid < $ammount)
		{
			if ($name != $this->highestbidder)
			{
				$this->secondbid = $this->maxbid;
				$this->highestbidder = $name;
			}
			$this->maxbid = $ammount;
		}
		else if ($currenthigh < $ammount)
		{
			if ($name != $this->highestbidder)
				$this->secondbid = $ammount;
		}
		$highest = (($this->maxbid == $this->secondbid) ? ($this->maxbid) : ($this->secondbid + 1));
		if ($highest != $currenthigh)
		{
			$secs = $this->end - time();
			if ($secs < 10)
			{
				$secs = 10;
				$this->end = time() + 10;
			}
			if ($this->highestbidder == $currenthighb)
			{
				$obb = "##highlight##$name##end## tried to outbid with ##highlight##$ammount##end##, ";
			}
			$this->bot->send_output("", $obb . "##highlight##" . $this->highestbidder . "##end## leads with " . "##highlight##$highest##end## points. Bidding ends " . "in ##highlight##$secs##end## seconds :: " . $this->info(), "both");
		}
	}

	/*
	Gets called on a cronjob...
	*/
	function cron()
	{
		if ($this->end < time())
		{
			if (count($this->history) > 20)
			{
				foreach ($this->history as $k => $v)
				{
					if (! $done)
					{
						unset($this->history[$k]);
						$done = TRUE;
					}
				}
			}
			if (empty($this->highestbidder))
			{
				$this->bot->send_output("", "Auction is over. No bids where placed. Item is FFA.", "both");
				$this->history[] = array(time() , $this->bid , FALSE);
			}
			else
			{
				$highest = (($this->maxbid == $this->secondbid) ? ($this->maxbid) : ($this->secondbid + 1));
				$this->bot->send_output("", "##highlight##" . $this->highestbidder . "##end## has won the auction for ##highlight##" . $this->bid . "##end##. ##highlight##$highest##end## points are being deducted from this account.", "both");
				$this->bot->core("points")->rem_points($this->name, $this->highestbidder, $highest, "Auction: " . $this->bid, TRUE);
				//	$this -> bot -> db -> query("UPDATE #___raid_points SET points = points - " . $highest .
				//	" WHERE id = " . $this -> points_to($this -> highestbidder));
				$this->history[] = array(time() , $this->bid , $this->highestbidder , $highest);
			}
			$this->bid = "";
			$this->unregister_event("cron", "2sec");
		}
	}

	/*
	Show info about bidding
	*/
	function info_bid($name)
	{
		$inside = "##blob_title##::::: Bidding Help :::::##end##\n\n";
		$inside .= "To place a bid write:\n";
		$inside .= "##blob_text##/tell <botname> <pre>bid &lt;points&gt;##end##\n";
		$inside .= "(Replace &lt;points&gt; with the number of points you would like to bid)\n\n";
		$inside .= "First you may place your bids as stated above.\n";
		$inside .= "The auction ends after 60 seconds or 10 seconds after the last bid was placed. So you always have some time to outbid ";
		$inside .= "but please try to bid the highest you're willing to pay right from the start to not make auctions last too long.\n\n";
		$inside .= "Highest bids work kindof like ebay:\n";
		$inside .= "You bid a maximum ammount, but the amount you acutally pay is the second highest bid + 1 point.\n";
		$inside .= "So if you bid 20 points and the second highest bidder bids 6, all others lower the bot will automaticaly bid 7 for you. If someone raises to 8 the bot will bid 9 for you.\n";
		$inside .= "Now someone bids 16. You will still remain in lead with 17 points. As soon as someone bids something higher then 20 however, this person will be in the lead.\n";
		$inside .= "If two people bid the same amount, the first of the two who placed a bid will get the item. ";
		$inside .= "So it really does help bidding the max you're willing to spend right from the start instead of trying \n";
		$inside .= "to correct later on.\n";
		$this->bot->send_tell($name, "Infomation about bidding :: " . $this->bot->core("tools")->make_blob("click for info", $inside));
	}

	/*
	Show info about bidding
	*/
	function info()
	{
		$inside = "##blob_title##::::: Auction :::::##end##\n\n";
		$inside .= "##darkorange##" . $this->highestbidder . "##end## Leading bid" . "##highlight## [ " . $this->maxbid . " ]##end##" . " for = [ " . $this->bid . " ]" . "\n\n";
		$ammounts = array(2 , 10 , 20 , 50 , 70 , "nl" , 100 , 150 , 170 , 200 , 300 , "nl" , 400 , 500 , 600 , 700 , 1000);
		$highest = (($this->maxbid == $this->secondbid) ? ($this->maxbid) : ($this->secondbid + 1));
		foreach ($ammounts as $am)
		{
			if ($am == "nl")
				$inside .= "\n";
			elseif ($am < $highest)
			{
				$inside .= "[ Bid 2 ] | ";
			}
			else
			{
				$inside .= $this->bot->core("tools")->chatcmd("bid 2", "[ Bid 2 ]") . " | ";
			}
		}
		$inside = substr($inside, 0, - 3);
		$inside .= "\n\n";
		$inside .= $this->bot->core("tools")->chatcmd("points", ":: <font color=#99CC00>Check your points##end## ::") . "\n\n";
		$inside .= "To place a bid write:\n";
		$inside .= "##highlight##/tell <botname> <pre>bid &lt;points&gt;##end##\n";
		$inside .= "(Replace &lt;points&gt; with the number of points you would like to bid)\n";
		Return $this->bot->core("tools")->make_blob(":: Enter Auction ::", $inside);
	}

	function lock($name, $lock)
	{
		if ($this->bot->core("security")->check_access($name, $this->bot->core("settings")->get('Raid', 'Command')))
		{
			if ($lock)
			{
				if ($this->locked)
				{
					$this->bot->send_tell($name, "Auction is Already ##highlight##locked##end##");
					return FALSE;
				}
				else
				{
					$this->locked = true;
					$this->bot->send_output("", "##highlight##$name##end## has ##highlight##locked##end## the Auction.", "both");
					return ("Auction ##highlight##locked##end##");
				}
			}
			else
			{
				if (! $this->locked)
				{
					$this->bot->send_tell($name, "Auction is Already ##highlight##unlocked##end##");
					return FALSE;
				}
				else
				{
					$this->locked = false;
					$this->bot->send_output("", "##highlight##$name##end## has ##highlight##unlocked##end## the Auction.", "both");
					return ("Auction ##highlight##unlocked##end##");
				}
			}
		}
		else
			return "You must be a " . $this->bot->core("settings")->get('Raid', 'Command') . " to do this";
	}

	function history()
	{
		if (! empty($this->history))
		{
			$inside = " :: Auction History ::";
			$history = array_reverse($this->history);
			foreach ($history as $h)
			{
				$inside .= "\n\n" . gmdate($this->bot->core("settings")->get("Time", "FormatString"), $h[0]) . " GMT";
				$inside .= "\nItem: " . $h[1];
				if ($h[2])
					$inside .= "\nResult: $h[2] for $h[3] points";
				else
					$inside .= "\nResult: FFA";
			}
			Return ("Auction History :: " . $this->bot->core("tools")->make_blob("click to view", $inside));
		}
		else
		{
			Return ("No Auction History Found");
		}
	}

	/*
	Get correct char for points
	*/
	function points_to($name)
	{
		return $this->bot->commands["tell"]["points"]->points_to($name);
	}
}
?>