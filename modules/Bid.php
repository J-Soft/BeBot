<?php
/*
* Bid.php - Raid bidding.
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg Stensås, ShadowRealm Creations and the BeBot development team.
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
*
* File last changed at $LastChangedDate: 2008-11-30 23:09:06 +0100 (Sun, 30 Nov 2008) $
* Revision: $Id: Bid.php 1833 2008-11-30 22:09:06Z alreadythere $
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

		$this -> bid = "";

		$this -> register_command('tell', 'bid', 'MEMBER');

		$this -> help['description'] = "Handles auctions using raid points";
		$this -> help['command']['bid start <item>'] = "Starts an auction for <item>. <item> can be text or an item ref.";
		$this -> help['command']['bid <points>'] = "Bid <points> raid points for the item currently on auction.";
		$this -> help['command']['bid info'] = "Shows information about the current auction.";
	}



	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		$msg = explode(" ", $msg, 3);
		Switch($msg[1])
		{
			case 'start':
				$this -> start_bid($name, $msg[2]);
				Break;
			case 'info':
				$this -> info_bid($name);
				Break;
			Default:
				if(is_numeric($msg[1]))
					$this -> place_bid($name, $msg[1]);
				else
					$this -> bot -> send_help($name);
		}
	}



	/*
	Starts the bidding
	*/
	function start_bid($name, $item)
	{
		if ($this -> bot -> core("security") -> check_access($name, "leader"))
		{
			$this -> bid = $item;
			$this -> maxbid = 1;
			$this -> secondbid = 1;
			$this -> highestbidder = "";
			$this -> announce = time() + 15;
			$this -> announced = false;
			$this -> end = time() + 60;
			$this -> register_event("cron", "2sec");
			$msg = "\n##highlight##-------------------------------------##end##\n";
			$msg .= "##highlight##$name##end## started auction ";
			$msg .= "on ##highlight##$item##end##! You have ";
			$msg .= "##highlight##60 seconds##end## to place bids :: " . $this -> info();
			$msg .= "\n##highlight##-------------------------------------##end##";
			$this -> bot -> send_output("", $msg, "both");
		}
		else
			$this -> bot -> send_tell($name, "You must be a raidleader to do this");
	}



	/*
	Place a bid
	*/
	function place_bid($name, $ammount)
	{
		$update = true;

		if (empty($this -> bid))
		{
			$this -> bot -> send_tell($name, "No auction in progress.");
			return false;
		}
		else if ($ammount < 2)
		{
			$this -> bot -> send_tell($name, "Min bid is set to ####highlight##2##end## raidpoints.");
			return false;
		}

		$result = $this -> bot -> db -> select("SELECT points FROM #___raid_points WHERE id = " . $this -> points_to($name));
		if (empty($result))
		{
			$this -> bot -> send_tell($name, "You appear to not have any points yet. No points table entry found.");
			return false;
		}

		$result = $result[0][0];

		$currenthigh = (($this -> maxbid == $this -> secondbid) ? ($this -> maxbid) : ($this -> secondbid + 1));

		if ($result < $ammount)
		$this -> bot -> send_tell($name, "You only have ##highlight##" . $result .
		"##end## raidpoints. Please place bid again.");
		else if ($this -> maxbid == $ammount)
		{
			$this -> secondbid = $ammount;
		}
		else if ($this -> maxbid < $ammount)
		{
			if ($name != $this -> highestbidder)
			{
				$this -> secondbid = $this -> maxbid;
				$this -> highestbidder = $name;
			}

			$this -> maxbid = $ammount;
		}
		else if ($currenthigh < $ammount)
		{
			if ($name != $this -> highestbidder)
			$this -> secondbid = $ammount;
		}

		$highest = (($this -> maxbid == $this -> secondbid) ? ($this -> maxbid) : ($this -> secondbid + 1));

		if ($highest != $currenthigh)
		{
			$secs = $this -> end - time();

			if ($secs < 10)
			{
				$secs = 10;
				$this -> end = time() + 10;
			}

			$this -> bot -> send_output("", "##highlight##" . $this -> highestbidder . "##end## leads with " .
			"##highlight##$highest##end## points. Bidding ends " .
			"in ##highlight##$secs##end## seconds :: " . $this -> info(), "both");
		}
	}



	/*
	Gets called on a cronjob...
	*/
	function cron()
	{
		if ($this -> end < time())
		{
			if (empty($this -> highestbidder))
			{
				$this -> bot -> send_output("", "Auction is over. No bids where placed. Item is FFA.", "both");
			}
			else
			{
				$highest = (($this -> maxbid == $this -> secondbid) ? ($this -> maxbid) : ($this -> secondbid + 1));
				$this -> bot -> send_output("", "##highlight##" . $this -> highestbidder . "##end## has won the auction for ##highlight##" .
				$this -> bid . "##end##. ##highlight##$highest##end## points are beeing deduced from his account.", "both");
				$this -> bot -> db -> query("UPDATE #___raid_points SET points = points - " . $highest .
				" WHERE id = " . $this -> points_to($this -> highestbidder));
			}
			$this -> bid = "";
			$this -> unregister_event("cron", "2sec");
		}
	}



	/*
	Show info about bidding
	*/
	function info_bid($name)
	{
		$inside = "##blob_title##::::: Bidding info :::::##end##\n\n";
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
		$this -> bot -> send_tell ($name, "Infomation about bidding :: " . $this -> bot -> core("tools") -> make_blob("click for info", $inside));
	}



	/*
	Show info about bidding
	*/
	function info()
	{
		$inside = "##blob_title##::::: Bidding info :::::##end##\n\n";
		$inside .= "To place a bid write:\n";
		$inside .= "##highlight##/tell <botname> <pre>bid &lt;points&gt;##end##\n";
		$inside .= "(Replace &lt;points&gt; with the number of points you would like to bid)\n";
		return $this -> bot -> core("tools") -> make_blob("click for info", $inside);
	}



	/*
	Get correct char for points
	*/
	function points_to($name)
	{
		return $this -> bot -> commands["tell"]["points"] -> points_to($name);
	}
}
?>
