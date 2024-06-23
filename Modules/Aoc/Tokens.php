<?php
/*
// With this module you can store your amount of relics on a single character.
// Made by Ruskebusk @ Crom
//
// Changelog:
// 22.01.24:	v0.9.9		Reworked by Bitnykk for Bebot 0.8+ compatibility	
// 28.04.20:	v0.9.8		Added Mythical Relic.
// 20.04.20:	v0.9.7		Fixed !tokens show <name> to actually look for if the hide settings is on or off.
// 14.04.20:	v0.9.6		Added table ##___tokens_track to add new functions like: added/removed tokens last 7 days / 30 days
// 12.03.20:  	v0.9.5		Added a 'hide_me' column which can be used to hide tokens from others. Need to add rest also.
// 22.09.19:	v0.9.4		Added gold, silver, copper and tin to the database. Should be able to see how much each character have.
// 17.08.19:	v0.9.3		Made it check for valid tokens, if its not valid it is not added.
// 16.08.19:	v0.9.2		Removed update_table() as its not needed.
// 15.08.19:	v0.9.1		Fixed update_table() to "work".
// 							Changed !tokens show to have <name> optional, if no name added it will show the character doing the command.
// 12.05.19:	Started working on this module.
//
// TODO:
// 	- Want to add a list of alts if someone uses: !tokens showall. List should contain the names from "alts" DB
	  in a clickable format that is again doing a !tokens show <name> to get the tokens for that character.
	- Fix the code a bit more.
*/

$tokens = new Tokens($bot);

class Tokens extends BaseActiveModule
{
     var $version;

    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->version = "0.9.9";		
        $this->register_command('all', 'tokens', 'ANONYMOUS');
		$this -> register_alias("tokens", "token");
        $this->help['description'] = "This module helps you to keep track of tokens for your character.";
        $this->help['command']['tokens add <shortname> <number>'] = "Adds <number> to <shortname>.";
        $this->help['command']['tokens rem <shortname> <number>'] = "Removes <number> from <shortname>.";
        $this->help['command']['tokens set <shortname> <number>'] = "Sets <number> to <shortname>.";
        $this->help['command']['tokens show <username OR shortname>'] = "Shows all your own tokens named <shortname> ; or shows player <username> tokens (if not hidden by user).";
        $this->help['command']['tokens'] = "Shows all tokens for your current character.";
		$this->help['command']['tokens alts'] = "Shows your declared alts with tokens links.";
        $this->help['command']['tokens history'] = "Shows previous tokens you have added."; // Optional add 7, 30 or token type
        $this->bot->core('prefs')->create('Tokens', 'Hide', 'Do you want to hide your tokens to other players?', 'No', 'Yes;No');
        $this->help['notes'] = sprintf("(C) Module by Ruskebusk @ Crom - version: ##lightbeige##%s##end##\n\nSupported tokens shortnames: \nAtlantean Shard = shards\nCampaign Badges = camp\nConquest Trophies = conq\nDragon Tear = t4rare\nEmerald Essence = t6rare\nEsteem Token = esteem\nMark of Acclaim = moa\nPortent Relic = portent\nRare Relics = t3rare\nRare Trophy = raretrophy\nShard of Pure Ice = t5rare\nSimple Relic 1 = t1\nSimple Relic 2 = t2\nSimple Relic 3 = t3\nSimple Relic 4 = t4\nSimple Relic 5 = t5\nSimple Relic 6 = t6\nSimple Trophy 1 = st1\nSimple Trophy 2 = st2\nSimple Trophy 3 = st3\nToken of Guilding = guilding\nVeteran Tokens = veteran\nVictory Token = victory\n", $this->version);
        $this->initializeDatabase();			
    }

    function initializeDatabase()
    {
        $this->bot->db->query("
            CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("tokens", "true") . "(
                `token_id` INT(11) NOT NULL AUTO_INCREMENT,
                `token_username` VARCHAR(255),
                `shards` INT(5) DEFAULT 0,
                `camp` INT(5) DEFAULT 0,
                `conq` INT(5) DEFAULT 0,
                `t4rare` INT(5) DEFAULT 0,
                `t6rare` INT(5) DEFAULT 0,
                `esteem` INT(5) DEFAULT 0,
                `hyper` INT(5) DEFAULT 0,
                `moa` INT(5) DEFAULT 0,
                `mythic` INT(5) DEFAULT 0,
                `portent` INT(5) DEFAULT 0,
                `t3rare` INT(5) DEFAULT 0,
                `raretrophy` INT(5) DEFAULT 0,
                `t5rare` INT(5) DEFAULT 0,
                `t1` INT(5) DEFAULT 0,
                `t2` INT(5) DEFAULT 0,
                `t3` INT(5) DEFAULT 0,
                `t4` INT(5) DEFAULT 0,
                `t5` INT(5) DEFAULT 0,
                `t6` INT(5) DEFAULT 0,
                `st1` INT(5) DEFAULT 0,
                `st2` INT(5) DEFAULT 0,
                `st3` INT(5) DEFAULT 0,
                `guilding` INT(5) DEFAULT 0,
                `veteran` INT(5) DEFAULT 0,
                `victory` INT(5) DEFAULT 0,
                `gold` INT(10) DEFAULT 0,
                `silver` INT(3) DEFAULT 0,
                `copper` INT(3) DEFAULT 0,
                `tin` INT(3) DEFAULT 0,
                PRIMARY KEY (`token_id`)
            )"
        ); // removed hidden field as better dealt by preferences

        $this->bot->db->query("
            CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("tokens_track", "true") . "(
                `track_id` INT(11) NOT NULL AUTO_INCREMENT,
                `track_username` VARCHAR(50) NOT NULL,
                `token` VARCHAR(50) NOT NULL,
                `token_amount` INT(11) NOT NULL,
                `track_action` VARCHAR(50) NOT NULL,
                `track_date` DATETIME NOT NULL,
                PRIMARY KEY (track_id)
            )"); // renamed table to tokens_track
    }

	
    function command_handler($name, $msg, $source)
    {
        $args = $this->parse_com(
			$msg,
            array(
                 "com",
				 "sub",
                 "args"
            )); // setted the arg manager correctly
        switch ($args['com']) {
            case 'tokens':
                return $this->sub_handler($name, $args);
            default:
                $this->bot->send_help($name);
        }
    }

	
    function sub_handler($name, $args)
    {
		$name = ucfirst(strtolower($name));
        switch ($args['sub']) {
            case 'alts':
                return $this->showAlts($name);			
            case 'add':
                return $this->handleTokenAction($name, $args['args'], "add");
            case 'rem':
            case 'del':
                return $this->handleTokenAction($name, $args['args'], "rem");
            case 'set':
                return $this->handleTokenAction($name, $args['args'], "set");
            case 'show':
                return $this->showTokens($name, $args['args']);
            case 'history':
                return $this->showHistory($name, $args['args']);
            default:
                return $this->showTokens($name, "all");
        }
    }

	
    function handleTokenAction($name, $args, $action)
    {
        $tokens = explode(" ", $args);
		
        if (count($tokens) != 2 || !is_numeric($tokens[1]) || $tokens[1] <= 0) {
            return "Invalid input. Expected format: 'token name' 'positive integer number of tokens'. ";
        }
        $tokenName = $tokens[0];
        $tokenAmount = $tokens[1];
        if (!$this->valid($tokenName, $tokenAmount)) {
            return "Invalid token name or amount.";
        }
        // Perform the required action (add, remove, set) on the tokens
        switch ($action) {
            case "add":
                // Add the specified number of tokens
                $result = $this->add_tokens($name, $tokenName, $tokenAmount);
                if ($result) { 
					return "##lime##$tokenAmount##end## ##white##" . $this->valid($tokenName, true) . "##end## added successfully."; // possible to have 'new total' value here? maybe use return in add_tokens instead of this one?
                } else {
                    return "Failed to add tokens.";
                }
                break;
            case "rem":
                // Remove the specified number of tokens
                $result = $this->remove_tokens($name, $tokenName, $tokenAmount);
                if ($result) {
                    return "##lime##$tokenAmount##end## ##white##" . $this->valid($tokenName, true) . "##end## removed successfully.";
                } else {
                    return "Failed to remove tokens.";
                }
                break;
            case "set":
                // Set the number of tokens to the specified amount
                $result = $this->set_tokens($name, $tokenName, $tokenAmount);
                if ($result) {
                    return "##white##" . $this->valid($tokenName, true) . "##end## set to ##lime##$tokenAmount##end## successfully.";
                } else {
                    return "Failed to set tokens.";
                }
                break;
            default:
                return "Invalid action.";
                break;
        }
    }

    
    function showAlts($name)
	{
        return "To be deved soon ..."; // LEFT TO DO
		// NOTE carefull that if the player has set tokens to hidden, he won't be able to read from other alts unless more checks are done
	}	
	
	
    function showHistory($name, $args = false)
	{
        // ********************************************************
        // ********************************************************
        // ********************************************************
        // ********************************************************

        $argumenter = explode(" ", $args);
		// $token = $args[0]; // token type ie: shards or 7  or 30 or empty
		$token = $argumenter[0];

		$today = date("Y-m-d H:i:s", strtotime("+2 hours"));
		$output = "<center>##ao_infoheader##::::##end####ao_infoheadline## $name's##end## ##ao_infoheader## token history, today: UTC: ##end####ao_infoheadline##$today##end## ##ao_infoheader##::::##end##</center>";

		if (is_numeric($token)) {
			// Show x days history up to 60 days
			if ($token >= 0 && $token <= 60) {
				$tid = date("Y-m-d H:i:s", strtotime("-$token days"));
				$outputLast = "##ao_feedback##Showing activity for the last $token days ($tid).<br>##end##";
				$sql = "SELECT token, token_amount, track_action, track_date FROM #___tokens_track WHERE track_username='$name' AND track_date > '$tid' ORDER BY track_date DESC";
			}else{
                // if more than 60 show last 60 days.
				$tid = date("Y-m-d H:i:s", strtotime("-60 days"));
				$outputLast = "##ao_feedback##Showing activity for the last 60 days ($tid).<br>##end##";
				$sql = "SELECT token, token_amount, track_action, track_date FROM #___tokens_track WHERE track_username='$name' AND track_date > '$tid' ORDER BY track_date DESC";
            }
		} else {
			$finn = $this->valid($token, false);
			if ($finn) {
				$outputLast = "##ao_feedback##Shows entries only for ##end####aqua##" . $this->valid($token, true) . ".##end##";
				$sql = "SELECT token, token_amount, track_action, track_date FROM #___tokens_track WHERE track_username='$name' AND token='$token' ORDER BY track_date DESC";
			} else {
				$outputLast = "##yellow##Limited to last 40 entries.##end##";
				$sql = "SELECT token, token_amount, track_action, track_date FROM #___tokens_track WHERE track_username='$name' ORDER BY track_date DESC LIMIT 40";
			}
		}

        $results = $this->bot->db->select($sql);

		// show all tokens
		$output .= "##aqua##Date - action - amount - token type<br>##end##";
        $row = 0;
		if (!empty($results)) {
			foreach ($results as $result) {
                $row++;
				$tr_token = $result[0];
				$token_amount = $result[1];
				$add_rem = $result[2];
				$added_date = $result[3];
				if ($this->valid($tr_token)) {
					$vistoken = $this->valid($tr_token, true);
				} else {
					$vistoken = $tr_token;
				}
				$output .= "##ao_infotext##$row##end## - ##ao_infotextbold##$added_date##end## - ##ao_infotext##$add_rem##end## - ##ao_infotextbold##$token_amount##end## - ##ao_infotext##$vistoken##end##<br>";
			}
		} else {
			return "You have not added tokens lately!";
		}
		$output .= $outputLast;
		// $output .= "<br>User: $username a: $a, finn: $finn, tomt: $tomt, tr_user: $tr_user, token: $tr_token, tamount: $token_amount, add: $add_rem, date: $added_date, tid: $tid, sql: $sql<br>";

		// vis resultatet enten sånn eller sånn
		return $this->bot->core("tools")->make_blob("History for $name", $output);

        // ********************************************************
        // ********************************************************
        // ********************************************************
        // ********************************************************
        // ********************************************************



		// return "To be deved soon ..."; // LEFT TO DO
		// NOTE beware table is now tokens_track ; if some arguments must be passed (pagination, type, etc) rewrite upper handler & note
	}

	
   
    function showTokens($name, $tokenName)
	{
        // $name is the player that sends the command. 
        // $tokenName is the name of the player to look for.
        // var_dump("1: " . $name);
        // var_dump("2: " . $tokenName);
        // var_dump("3: " . $this->bot->core("player")->id($name));
        // var_dump("4: " . $this->bot->core("player")->id($tokenName));
        // var_dump("5: " . $this->bot->core("prefs")->get($name, "Tokens", "Hide"));
        // var_dump("6: " . $this->bot->core("prefs")->get($tokenName, "Tokens", "Hide"));

        $tokenName = strtolower($tokenName);
		if($tokenName!="t1" && $tokenName!="t2" && $tokenName!="t3" && $tokenName!="t4" && $tokenName!="t5" && $tokenName!="t6" && 
		   $tokenName!="t3rare" && $tokenName!="t4rare" && $tokenName!="t5rare" && $tokenName!="t6rare" && $tokenName!="moa" && 
		   $tokenName!="shards" && $tokenName!="camp" && $tokenName!="conq" && $tokenName!="esteem" && $tokenName!="portent" && 
		   $tokenName!="raretrophy" && $tokenName!="st1" && $tokenName!="st2" && $tokenName!="st3" && $tokenName!="guilding" && 
		   $tokenName!="veteran" && $tokenName!="victory" && $tokenName!="gold" && $tokenName!="silver" && $tokenName!="copper" && 
		   $tokenName!="tin" && $tokenName!="mythic" && $tokenName!="hyper" && $tokenName!="all") { // not an existing token nor all self
				if ($this->bot->core("player")->id($tokenName)) { // 
					// unless player hides em
					if(ucfirst($name)!=ucfirst($tokenName) && $this->bot->core("prefs")->get($tokenName, "Tokens", "Hide") == "Yes") return "Sorry but $tokenName doesn't share tokens.";
					// turn up for Username & all
					$name = ucfirst($tokenName);
					$tokenName = "all";
				} else {					
					// otherwise wrong command exit
					return $this->bot->send_help($name);
				}
		}
        $output = "<center>##ao_infoheadline##:::: Tokens for ##end####ao_infoheadline##$name##end## ##ao_infoheadline##::::##end##</center>";
        		
        if ($tokenName == "all") {
            $sql = "SELECT t1,t2,t3,t4,t5,t6,t3rare,t4rare,t5rare,t6rare,moa,shards,camp,conq,esteem,portent,raretrophy,st1,st2,st3,guilding,veteran,victory,gold,silver,copper,tin,mythic,hyper FROM #___tokens WHERE token_username = '".$name."' LIMIT 1";
            $all = $this->bot->db->select($sql, MYSQLI_ASSOC);
            if (!empty($all)) { // fixed level of data access & added number checks below to ligthen display
				if($all[0]['shards']>0) $output .= "##ao_skillcolor##Atlantean Shard:##end## ##lime##".$all[0]['shards']."##end##<br />";
                if($all[0]['camp']>0) $output .= "##ao_skillcolor##Campaign Badges:##end## ##lime##".$all[0]['camp']."##end##<br />";
                if($all[0]['conq']>0) $output .= "##ao_skillcolor##Conquest Trophies:##end## ##lime##".$all[0]['conq']."##end##<br />";
                if($all[0]['t4rare']>0) $output .= "##ao_skillcolor##Dragon Tear:##end## ##lime##".$all[0]['t4rare']."##end##<br />";
                if($all[0]['t6rare']>0) $output .= "##ao_skillcolor##Emerald Essence:##end## ##lime##".$all[0]['t6rare']."##end##<br />";
                if($all[0]['esteem']>0) $output .= "##ao_skillcolor##Esteem Token:##end## ##lime##".$all[0]['esteem']."##end##<br />";
                if($all[0]['hyper']>0) $output .= "##ao_skillcolor##Hyperborean Relic:##end## ##lime##".$all[0]['hyper']."##end##<br />";
                if($all[0]['moa']>0) $output .= "##ao_skillcolor##Mark of Acclaim:##end## ##lime##".$all[0]['moa']."##end##<br />";
                if($all[0]['mythic']>0) $output .= "##ao_skillcolor##Mythical Relic:##end## ##lime##".$all[0]['mythic']."##end##<br />";
                if($all[0]['portent']>0) $output .= "##ao_skillcolor##Portent Relic:##end## ##lime##".$all[0]['portent']."##end##<br />";
                if($all[0]['t3rare']>0) $output .= "##ao_skillcolor##Rare Relics:##end## ##lime##".$all[0]['t3rare']."##end##<br />";
                if($all[0]['raretrophy']>0) $output .= "##ao_skillcolor##Rare Trophy:##end## ##lime##".$all[0]['raretrophy']."##end##<br />";
                if($all[0]['t5rare']>0) $output .= "##ao_skillcolor##Shard of Pure Ice:##end## ##lime##".$all[0]['t5rare']."##end##<br />";
                if($all[0]['t1']>0) $output .= "##ao_skillcolor##Simple Relic 1:##end## ##lime##".$all[0]['t1']."##end##<br />";
                if($all[0]['t2']>0) $output .= "##ao_skillcolor##Simple Relic 2:##end## ##lime##".$all[0]['t2']."##end##<br />";
                if($all[0]['t3']>0) $output .= "##ao_skillcolor##Simple Relic 3:##end## ##lime##".$all[0]['t3']."##end##<br />";
                if($all[0]['t4']>0) $output .= "##ao_skillcolor##Simple Relic 4:##end## ##lime##".$all[0]['t4']."##end##<br />";
                if($all[0]['t5']>0) $output .= "##ao_skillcolor##Simple Relic 5:##end## ##lime##".$all[0]['t5']."##end##<br />";
                if($all[0]['t6']>0) $output .= "##ao_skillcolor##Simple Relic 6:##end## ##lime##".$all[0]['t6']."##end##<br />";
                if($all[0]['st1']>0) $output .= "##ao_skillcolor##Simple Trophy 1:##end## ##lime##".$all[0]['st1']."##end##<br />";
                if($all[0]['st2']>0) $output .= "##ao_skillcolor##Simple Trophy 2:##end## ##lime##".$all[0]['st2']."##end##<br />";
                if($all[0]['st3']>0) $output .= "##ao_skillcolor##Simple Trophy 3:##end## ##lime##".$all[0]['st3']."##end##<br />";
                if($all[0]['guilding']>0) $output .= "##ao_skillcolor##Token of Guilding:##end## ##lime##".$all[0]['guilding']."##end##<br />";
                if($all[0]['veteran']>0) $output .= "##ao_skillcolor##Veteran Tokens:##end## ##lime##".$all[0]['veteran']."##end##<br />";
                if($all[0]['victory']>0) $output .= "##ao_skillcolor##Victory Token:##end## ##lime##".$all[0]['victory']."##end##<br />";
                if($all[0]['gold']>0) $output .= "##ao_skillcolor##Gold:##end## ##lime##".$all[0]['gold']."##end##<br />";
                if($all[0]['silver']>0) $output .= "##ao_skillcolor##Silver:##end## ##lime##".$all[0]['silver']."##end##<br />";
                if($all[0]['copper']>0) $output .= "##ao_skillcolor##Copper:##end## ##lime##".$all[0]['copper']."##end##<br />";
                if($all[0]['tin']>0) $output .= "##ao_skillcolor##Tin:##end## ##lime##".$all[0]['tin']."##end##<br />";
                return $this->bot->core("tools")->make_blob("Tokens for $name", $output);
            } else {
                return ("$name has no tokens!");
            }
        } else {
            $sql = "SELECT ".$tokenName." FROM #___tokens WHERE token_username = '".$name."'";
            $check = $this->bot->db->select($sql);
            if (!empty($check)) {
                foreach ($check as $key) {
                    $tokens = $key[0];
                }
                return ("$name has ##lime##" . $tokens . "##end## ##white##" . $this->valid($tokenName, true) . "##end##!");
            } else {
                return ("$name does not have any ".$tokenName); // detailed reply
            }
        }
	}


    function add_tokens($name, $tokenName, $amount)
    {
		// Check if the username exists in the database ; following same logic than other functions below
        if ($this->usernameExists($name)) {
            // Username exists, get current tokens
			$getToken = $this->bot->db->select("SELECT ".$tokenName." FROM #___tokens WHERE token_username='".$name."' LIMIT 1", MYSQLI_ASSOC); // added MYSQLI_ASSOC 
            // var_dump("1: " . print_r($getToken[0]));                 no
            // var_dump("2: " . print_r($getToken[0][$tokenName]));     works
            // print_r("3: " . $getToken);                              error Array to string conversion in 
            // print_r("4: " . $getToken[0][0]);                        error Undefined array key 0 in
            

            $currentTokens = $getToken[0][$tokenName]; // Assuming the token value is in the first row of the result

            // Calculate new token value after addition
            $newValue = $currentTokens + $amount;
            // Update the tokens
            $updateSQL = "UPDATE #___tokens SET ".$tokenName." = ".$newValue." WHERE token_username='".$name."'";
            $result = $this->bot->db->query($updateSQL); 

            if ($result) {
                // Add a record to tokens_track table
                $this->addTokenTrackingRecord($name, $tokenName, $amount, 'add');
                // return "$amount $tokenName added successfully. New total: $newValue";
                return true; // should we have this here or return true and use handleTokenAction "feedback".
            } else {
                // return "Failed to add $amount to $tokenName.";
                return false;
            }			
        } else {
            // Username does not exist, insert a new record
            $insertSQL = "INSERT INTO #___tokens (token_username, ".$tokenName.") VALUES ('".$name."', ".$amount.")";
            $result = $this->bot->db->query($insertSQL); // Use bot->db->query() for INSERT queries

            if ($result) {
                // Add a record to tokens_track table
                $this->addTokenTrackingRecord($name, $tokenName, $amount, 'add');
                // return "New record added for $name with ##white##" . $this->valid($tokenName, true) . "##end## set to ##lime##$amount##end##.";
                return true; 
            } else {
                // return "Failed to add a new record for $name.";
                return false;
            }
        }
    }

    

    function remove_tokens($name, $tokenName, $amount)
    {
        // Check if the username exists in the database
        if ($this->usernameExists($name)) {
            // Username exists, get current tokens
            $getToken = $this->bot->db->select("SELECT ".$tokenName." FROM #___tokens WHERE token_username='".$name."' LIMIT 1", MYSQLI_ASSOC); // added MYSQLI_ASSOC
            $currentTokens = $getToken[0][$tokenName]; // Assuming the token value is in the first row of the result
            // var_dump("1: " . print_r($getToken[0]));                             no
            // var_dump("2: " . print_r($getToken[0][$tokenName]));                 works
            // print_r("3: " . $getToken[0][0]);                                    error: Undefined array key 0 in
            // print_r("4: " . $getToken[0][$tokenName]);                           works
            // print_r("5: " . $getToken);                                          error: Array to string conversion in

            // Ensure the player has enough tokens to be removed
            if ($currentTokens < $amount) {
                // Set token amount to 0 if the user wants to remove more tokens than they have
                $newValue = 0;
            } else {
                // Calculate new token value after removal
                $newValue = $currentTokens - $amount;
            }
            // Update the tokens
            $updateSQL = "UPDATE #___tokens SET $tokenName = $newValue WHERE token_username='$name'";
            $result = $this->bot->db->query($updateSQL); 

            if (!empty($result)) {
                print_r("6: " . $result);
                // Add a record to tokens_track table
                $this->addTokenTrackingRecord($name, $tokenName, $amount, 'remove');
            
                // return "##lime##$amount##end## ##white##" . $this->valid($tokenName, true) . "##end## removed successfully. New total: ##lime##$newValue##end##";
                return true;
            } else {
                // return "Failed to remove $amount ##white##" . $this->valid($tokenName, true) . "##end##.";
                return false;
            }
        } else {
            // Username does not exist
            // return "Username $name not found in the database. Cannot remove tokens.";
            return false;
        }
    }
    
   function set_tokens($name, $tokenName, $amount)
    {    
        // Check if the username exists in the database
        if ($this->usernameExists($name)) {
            $hent = true;
        }else {
            $hent = $this->bot->db->select("SELECT $tokenName FROM #___tokens WHERE token_username='".$name."' LIMIT 1");
        }       
        if ($hent) {
            // Username exists, update the tokens
            $updateSQL = "UPDATE #___tokens SET ".$tokenName." = ".$amount." WHERE token_username='".$name."'";
            $result = $this->bot->db->query($updateSQL);

            if ($result) {
                // Add a record to tokens_track table
                $this->addTokenTrackingRecord($name, $tokenName, $amount, 'set');
            
                // return "##white##" . $this->valid($tokenName, true) . "##end## set to ##lime##$amount##end## successfully.";
                return true;
            } else {
                // return "Failed to set ##white##" . $this->valid($tokenName, true) . "##end## to $amount.";
                return false;
            }            
        } else {
            // Username does not exist, insert a new record
            $insertSQL = "INSERT INTO #___tokens (token_username, ".$tokenName.") VALUES ('".$name."', ".$amount.")";
            $result = $this->bot->db->query($insertSQL);

            if ($result) {
                // return "New record added for $name with ##white##" . $this->valid($tokenName, true) . "##end## set to ##lime##$amount##end##.";
                return true;
            } else {
                // return "Failed to add a new record for $name.";
                return false;
            }
        }
    }

    
      /**
     * Adds a token tracking record.
     * 
     * @param {string} name The username 
     * @param {string} tokenName The name of the token
     * @param {number} amount The token amount
     * @param {string} action The action taken
     */
    function addTokenTrackingRecord($name, $tokenName, $amount, $action)
    {
        $insertTrackingSQL = "INSERT INTO #___tokens_track (track_username, token, token_amount, track_action, track_date) 
                              VALUES ('" . $name . "', '" . $tokenName . "', " . $amount . ", '" . $action . "', NOW())";
        $this->bot->db->query($insertTrackingSQL);
    }

    /**
     * Checks if a username exists in the tokens table.
     * 
     * @param {string} name - The username to check.
     * @returns {boolean} - True if the username exists, false otherwise.
     */
    function usernameExists($name)
    {
        $checkUsernameSQL = "SELECT COUNT(*) FROM #___tokens WHERE token_username='" . $name . "'";
        $result = $this->bot->db->select($checkUsernameSQL);
        // Check if the query was successful and $result is an array
        if ($result[0][0] > 0) {
            return true;
        } else {
            return false;
        }
        // Return false if there was an issue with the query or result
        return false;
    }

    
      /**
     * Checks if a token type is valid and returns the full token name or short name.
     * 
     * @param string $tokenType The token type to check.
     * @param bool $option Whether to return the full token name or short name. 
     * @return string The full token name if valid and $option=true, otherwise the short name or false.
     */
    function valid($tokenType, $option = false)
    {
        $validTokens = array(
            "shards" => "Atlantean Shards",
            "camp" => "Campaign Badges",
            "hide" => "Hide settings",
            "conq" => "Conquest Trophies",
            "t4rare" => "Dragon Tear",
            "t6rare" => "Emerald Essence",
            "esteem" => "Esteem Token",
            "hyper" => "Hyperborean Relic",
            "moa" => "Mark of Acclaim",
            "mythic" => "Mythical Relic",
            "portent" => "Portent Relic",
            "t3rare" => "Rare Relics",
            "raretrophy" => "Rare Trophy",
            "t5rare" => "Shard of Pure Ice",
            "t1" => "Simple Relic 1",
            "t2" => "Simple Relic 2",
            "t3" => "Simple Relic 3",
            "t4" => "Simple Relic 4",
            "t5" => "Simple Relic 5",
            "t6" => "Simple Relic 6",
            "st1" => "Simple Trophy 1",
            "st2" => "Simple Trophy 2",
            "st3" => "Simple Trophy 3",
            "guilding" => "Token of Guilding",
            "veteran" => "Veteran Tokens",
            "victory" => "Victory Token",
            "gold" => "Gold Coins",
            "silver" => "Silver Coins",
            "copper" => "Copper Coins",
            "tin" => "Tin Coins"
        );
        // Check if the token type is a valid key in the validTokens array
        // True returns the full name of the token, ie: shards will return Atlantean Shards
        // False returns false or shortname of token, ie: shards will return shards
        if (!$option) {
            return (($validTokens[$tokenType]) ? $tokenType : false);
        } else {
            return ($validTokens[$tokenType]);
        }
    }

   
}
?>
