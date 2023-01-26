<?php
/*
* Gmi.php - Offers pure chat GMI search based on Nadyita's API
* 
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2010 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
*
* Developed by:
* - Alreadythere (RK2)
* - Blondengy (RK1)
* - Blueeagl3 (RK1)
* - Glarawyn (RK1)
* - Khalem (RK1)
* - Naturalistic (RK1)
* - Temar (RK1)
* - Bitnykk (RK2) 
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
*/

$gmi = new Gmi($bot);

/*
The Class itself...
*/
class Gmi extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this -> register_command("all", "gmi", "GUEST");
		$this -> help['description'] = "Offers pure chat GMI search based on Nadyita's API";
        $this->bot->core("settings")
            ->create("Gmi", "ApiUrl", "https://gmi.nadybot.org", "What's the GMI search API URL we should use (Nadybot's by default) ?");	
		$this->apiver='v1.0';
	}
	
	function command_handler($name, $msg, $channel)
	{
		if (preg_match("/^gmi ([0-9]+) (.+)$/i", $msg, $info))
			return $this -> gmi_search($name, $info, $channel);
		elseif (preg_match("/^gmi (.+)$/i", $msg, $info))
			return $this -> gmi_check($name, $info, $channel);
		else return "Wrong use of command, retry with existing item name e.g. : !gmi whatever item name";
	}	
	
	function gmi_check($name, $info, $channel)
	{	
		$return = ""; $inside = ""; $count = 0;
		if($this->bot->db->get_version("aorefs")>1) {
			$query = "SELECT id, name, ql FROM aorefs WHERE name LIKE '%".strtolower(addslashes($info[1]))."%' ORDER BY ql DESC";
			$refs = $this->bot->db->select($query);
			if (!empty($refs)) {	
				$count = count($refs);
				foreach($refs AS $ref) {
					$inside .= $ref[1]." QL".$ref[2]." [".$this->bot->core("tools")->chatcmd("gmi ".$ref[0]." ".$ref[1], "GMI")."]\n";
				}
			} else {
				$inside .= "No searchable item(s) found corresponding to your keyword(s)";
			}
		}
		return $count." searchable item(s) : ".$this->bot->core("tools")->make_blob("click to view", $inside);
	}

	function gmi_search($name, $info, $channel)
	{
		$return = "";
		if($this->bot->core("settings")->get("Gmi", "ApiUrl")!='') {
			$url = $this->bot->core("settings")->get("Gmi", "ApiUrl")."/".$this->apiver."/"."aoid/".$info[1];
			$content = $this->bot->core("tools")->get_site($url);	
			if (!($content instanceof BotError)) {
				if (strpos($content, '"buy_orders":') !== false || strpos($content, '"sell_orders":') !== false) {
					$blocs = json_decode($content);
					$now = new DateTimeImmutable();
					$inside = ":: ".$info[2]." ::\n\n";
					if(isset($blocs->sell_orders) && count($blocs->sell_orders)>0) {
						$inside .= "__________SELL Orders_________\n";
						$inside .= $this->tab("PRICE",15)." ".$this->tab("QL",5)." ".$this->tab("COUNT",5)." ".$this->tab("SELLER",18)." ".$this->tab("EXPIRATION",20)."\n";						
						foreach($blocs->sell_orders AS $sell) {
							$interval = new DateInterval("PT{$sell->expiration}S");
							$expire = $now->diff($now->add($interval))->format('%a days %h h %i min');
							$inside .= $this->tab($sell->price,15)." ".$this->tab($sell->ql,5)." ".$this->tab($sell->count,5)." ".$this->tab($sell->seller,18)." ".$this->tab($expire,20)."\n";
						}
						$inside .= "\n\n";
					}					
					if(isset($blocs->buy_orders)&& count($blocs->buy_orders)>0) {
						$inside .= "__________BUY Orders__________\n";
						$inside .= $this->tab("PRICE",15)." ".$this->tab("MIN",5)." ".$this->tab("MAX",5)." ".$this->tab("CNT",5)." ".$this->tab("BUYER",18)." ".$this->tab("EXPIRATION",20)."\n";
						foreach($blocs->buy_orders AS $buy) {
							$interval = new DateInterval("PT{$buy->expiration}S");
							$expire = $now->diff($now->add($interval))->format('%a days %h h %i min');				
							$inside .= $this->tab($buy->price,15)." ".$this->tab($buy->min_ql,5)." ".$this->tab($buy->max_ql,5)." ".$this->tab($buy->count,5)." ".$this->tab($buy->buyer,18)." ".$this->tab($expire,20)."\n";
						}
						$inside .= "\n\n";
					}
					$return = $info[2]." GMI search : ".$this->bot->core("tools")->make_blob("click to view", $inside);
				} else {
					$return = "Uncorrect GMI data obtained, may retry later on.";
				}
			} else {
				$return = "GMI couldn't be reached, so no data obtained.";
			}
		}
		return $return;
	}
	
	function tab($value,$length) {
		if(strlen($value)<$length) {
			$diff = $length-strlen($value);
			for($i=0;$i<$diff;$i++) {
				$value .= "&nbsp;";
			}		
			return $value;
		} else {
			return $value;
		}
	}
	
}