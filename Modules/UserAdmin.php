<?php
/***************************************************************************************************
 * BeBot - An Anarchy Online & Age of Conan Chat Automaton
 * Copyright (C) 2004 Jonas Jax
 * Copyright (C) 2005-2021 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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
 ***************************************************************************************************
 * UserAdmin module for Bebot
 * This module allows an admin to manage member data including buddy list.
 * Author:	Kentarii [Ragnarok] @ EN Fury PvP
 * E-mail:	Does not take a rocket scientist to figure out..
 * Website:	http://aoc-is.better-than.tv/
 ***************************************************************************************************
 * UPDATED BY
 * Author:	MeatHooks of MeatHooks Minions on Crom PvE
 * E-mail:	meathooks@meathooksminions.com
 * Website:	conan.meathooksminions.com
  ***************************************************************************************************
 * ADAPTED BY
 * Author:	Bitnykk @ RK5 from version 0.0.7
 * Website:	http://bebot.link/
  ***************************************************************************************************
 * Changelog:
 *	2021-10-13	0.0.7	Adapted for PHP7+ & Bebot 0.7.x serie ; added clear idle users function.
 *	2018-06-03	0.0.6	Remove Yellow Gremlin integration.
						Corrent checking/removing alts and not using correct table names.
 *	2011-02-07	0.0.5	Add utf8_decode to support special chars in output. Fix help section.
 *						Add idle members listings.
 *	2011-02-03	0.0.4	Add clear methods on user list. Move configuration to bebot settings.
 *	2011-01-29	0.0.3	Fixed some stuff.
 *	2011-01-19	0.0.2	First public release.
 *	2011-01-12	0.0.1	First version.
 ***************************************************************************************************
 */

$UserAdmin = new UserAdmin($bot);

class UserAdmin extends BaseActiveModule {
	var $bot;
	var $version;

	function __construct(&$bot) {
		parent::__construct($bot, get_class($this));

		$this -> version = '0.0.7';

		$this -> register_command('all', 'useradmin', "SUPERADMIN");
		$this -> help['description'] = "This module allows a superadmin to manage member data including buddy list.";
		$this -> help['command']['useradmin'] = "Show overview of members, buddies and stats.";
		$this -> help['command']['useradmin userlist'] = "Displays a list of all of the bot's users.";
		$this -> help['command']['useradmin userlist <all|member|guest|anonymous|banned>'] = "Displays a filtered list of the bot's users.";
		$this -> help['command']['useradmin userlist clear <guest|anonymous|banned>'] = "Purge users from the bot's users.";
		$this -> help['command']['useradmin memberlist'] = "Displays a list of all of the bot's members.";
		$this -> help['command']['useradmin memberlist main'] = "Displays a list of all of the bot's members which are main characters.";
		$this -> help['command']['useradmin memberlist alt'] = "Displays a list of all of the bot's members which are alt characters.";
		$this -> help['command']['useradmin memberlist cidle <#>'] = "Count of members who have been idle for <#> days.";
		$this -> help['command']['useradmin memberlist idle <#>'] = "List of members sorted by last seen who have been idle for <#> days.";
		$this -> help['command']['useradmin memberlist clear <#>'] = "Remove all users who have been idle for <#> days ; ALWAYS backup first as these datas might then be unrecoverable (except for AO org members possibly readded by rosterupdate).";
		$this -> help['command']['useradmin altlist list obsolete'] = "List obsolete entries in the alts table for characters who are no longer members of the bot.";
		$this -> help['command']['useradmin altlist clear obsolete'] = "Remove entries from the alts table for characters who are no longer members of the bot. Note: Semi-safe to run.. if people add alts before they are invited to guild, this might get lost though.";
		$this -> help['command']['useradmin buddylist'] = "Displays a list of all of the bot's buddies.";
		$this -> help['command']['useradmin buddylist missing'] = "Displays a list of members not currently added to the bot's buddylist.";
		$this -> help['command']['useradmin buddylist clear'] = "Wipes all of the bots buddies from the bot's buddylist. Note: Safe to run, you can always re-add members by running rosterupdate.";
		$this -> help['command']['useradmin buddy add <id>'] = "Add a character identified by id to the bot's buddylist.";
		$this -> help['command']['useradmin buddy remove <id>'] = "Remove a character identified by id from the bot's buddylist. Note: Safe to run, you can always re-add members by running rosterupdate.";
		$this -> help['command']['useradmin whois clear <all|member|guest|anonymous|banned|obsolete>'] = "Remove entries from the whois database. Note: Safe to run, whois database will build itself up again when characters gets added to bot or people run whois.";
		$this -> help['notes'] = "Important DISCLAIMER:\nAny use of this module is 100% at your own risk.\nYou will be sole responsible if you happen to delete unrecoverable datas by mistake.\nSo always backup your full database first before doing anymore command you may regret - too late.";
	}

	function command_handler($name, $msg, $origin) {
		if (preg_match('/^useradmin userlist$/i', $msg)) {
			$rv = $this -> list_users();
		}
		else if (preg_match('/^useradmin userlist (all|member|guest|anonymous|banned)$/i', $msg, $m)) {
			$rv = $this -> list_users(strtolower($m[1]));
		}
		else if (preg_match('/^useradmin userlist clear (guest|anonymous|banned)$/i', $msg, $m)) {
			$rv = $this -> clear_users(strtolower($m[1]));
		}
		else if (preg_match('/^useradmin memberlist$/i', $msg)) {
			$rv = $this -> list_members();
		}
		else if (preg_match('/^useradmin memberlist (main|alt)$/i', $msg, $m)) {
			$rv = $this -> list_members($m[1]);
		}
		else if (preg_match('/^useradmin memberlist (cidle) ([\d]+)$/i', $msg, $m)) {
			$rv = $this -> list_members($m[1], intval($m[2]));
		}		
		else if (preg_match('/^useradmin memberlist (idle) ([\d]+)$/i', $msg, $m)) {
			$rv = $this -> list_members($m[1], intval($m[2]));
		}
		else if (preg_match('/^useradmin memberlist clear ([\d]+)$/i', $msg, $m)) {
			$rv = $this -> clear_users($m[1]);
		}		
		else if (preg_match('/^useradmin altlist clear (all|obsolete)$/i', $msg, $m)) {
			$rv = $this -> clear_alts(strtolower($m[1]));
		}
		else if (preg_match('/^useradmin altlist list obsolete$/i', $msg)) {
			$rv = $this -> list_obsolete_alts();
		}
		else if (preg_match('/^useradmin buddylist$/i', $msg)) {
			$rv = $this -> list_buddies();
		}
		else if (preg_match('/^useradmin buddylist missing$/i', $msg)) {
			$rv = $this -> list_missing_buddies();
		}
		else if (preg_match('/^useradmin buddylist clear$/i', $msg)) {
			$rv = $this -> clear_buddies();
		}
		else if (preg_match('/^useradmin buddy add ([\d]+)$/i', $msg, $m)) {
			$rv = $this -> add_buddy($m[1]);
		}
		else if (preg_match('/^useradmin buddy remove ([\d]+)$/i', $msg, $m)) {
			$rv = $this -> remove_buddy($m[1]);
		}
		else if (preg_match('/^useradmin whois clear (all|member|guest|anonymous|banned|obsolete)$/i', $msg, $m)) {
			$rv = $this -> clear_whois(strtolower($m[1]));
		}
		else {
			$rv = $this -> show_overview($name, $origin);
		}
		return $this -> prefix_output($rv);
	}

	function prefix_output($rv) {
		return ($rv) ? "##white####bluegray##[-UserAdmin-]##end## :: ". $rv . "##end##" : false;
	}

	/**
	 * Create a standard header with optional $title
	 */
	function blob_header($title = null) {
		if ($title) return "<font color='#8CB6FF'>UserAdmin :: $title</font>\n";
		else return "<font color='#8CB6FF'>UserAdmin</font>\n";
	}

	/**
	 * Create a section header with $title
	 */
	function blob_section_header($title) {
		return "<b></b>\n<font color='#9AD5D9'>$title</font>\n";
	}

	function user_level_decode($lvl) {
		$lvl = strtolower(trim($lvl));
		$lvls = array(
			'all' => null,
			'member' => MEMBER,
			'guest' => GUEST,
			'anonymous' => ANONYMOUS,
			'banned' => BANNED,
			'obsolete' => 'obsolete',
		);
		if (in_array($lvl, array_keys($lvls))) {
			return $lvls[$lvl];
		}
		return $lvl;
	}

	function user_level_encode($lvl) {
		$lvl = trim($lvl);
		$lvls = array(
			MEMBER => 'member',
			GUEST => 'guest',
			ANONYMOUS => 'anonymous',
			BANNED => 'banned',
		);
		if (in_array($lvl, array_keys($lvls))) {
			return $lvls[$lvl];
		}
		return '';
	}

	/**
	 * Show overview.
	 * - members, guests, non-members (determined by user_level; MEMBER=2,GUEST=1,ANONYMOUS=0,BANNED=-1)
	 * - buddies
	 * - guild affiliation for members
	 * @return string
	 */
	function show_overview($name, $origin) {
		$member_count = $guest_count = $anonymous_count = $banned_count = 0;
		$buddy_member_count = $buddy_guest_count = $buddy_anonymous_count = $buddy_banned_count = 0;
		$whois_member_count = $whois_guest_count = $whois_anonymous_count = $whois_banned_count = 0;

		$main_count = $alt_count = 0;

		$buddies = $this -> bot -> aoc -> buddies;
		$whois = $this -> load_whois();
		$alts = $this -> load_alts();
		$users = $this -> load_users();

		foreach ($users as $u) {
			if ($u['user_level'] == MEMBER) {
				$member_count++;
				if (isset($buddies[$u['char_id']])) $buddy_member_count++;
				if (isset($whois[$u['char_id']])) $whois_member_count++;
				if (isset($alts[$u['nickname']])) $alt_count++;
				else $main_count++;
			}
			else if ($u['user_level'] == GUEST) {
				$guest_count++;
				if (isset($buddies[$u['char_id']])) $buddy_guest_count++;
				if (isset($whois[$u['char_id']])) $whois_guest_count++;
			}
			else if ($u['user_level'] == ANONYMOUS) {
				$anonymous_count++;
				if (isset($buddies[$u['char_id']])) $buddy_anonymous_count++;
				if (isset($whois[$u['char_id']])) $whois_anonymous_count++;
			}
			else if ($u['user_level'] == BANNED) {
				$banned_count++;
				if (isset($buddies[$u['char_id']])) $buddy_banned_count++;
				if (isset($whois[$u['char_id']])) $whois_banned_count++;
			}
			else {
				// We should never get here...
				$this -> bot -> log('UserAdmin', 'WARNING', "Invalid user_level for char_id: '". $u['char_id'] ."', nickname: '". $u['nickname'] ."'.");
			}
		}

		// let's build a blob
		$output = $this -> blob_header('Overview');
		$output .= "\nThis is a SUPERADMIN sensible tool. Remember to BACKUP your database before clearing any data.\nBuddies however are stored in the bot's friendlist & could be rebuilt by <pre>rosterupdate. Same goes for <pre>whois datas which are pulled on demand.\nALL other datas might be ##red##unrecoverable##end## (except for AO Org Members possibly readded by <pre>rosterupdate). So please first read the ". $this -> make_cmd('help', 'useradmin', 'help') ." section for more details on each function.\n";

		$output .= $this -> section_overview('Users', array(
			array('title' => 'Total Users', 'count' => count($users), 'links' => array($this -> make_cmd('list', 'userlist all'))),
			array('title' => 'Members', 'count' => $member_count, 'links' => array($this -> make_cmd('list', 'userlist member'))),
			array('title' => 'Guests', 'count' => $guest_count, 'links' => array($this -> make_cmd('list', 'userlist guest'), $this -> make_cmd('clear', 'userlist clear guest'))),
			array('title' => 'Anonymous (deleted)', 'count' => $anonymous_count, 'links' => array($this -> make_cmd('list', 'userlist anonymous'), $this -> make_cmd('clear', 'userlist clear anonymous'))),
			array('title' => 'Banned', 'count' => $banned_count, 'links' => array($this -> make_cmd('list', 'userlist banned'), $this -> make_cmd('clear', 'userlist clear banned'))),
		));

		$output .= $this -> section_overview('Members', array(
			array('title' => 'Total Members', 'count' => $member_count, 'links' => array($this -> make_cmd('list', 'memberlist'))),
			array('title' => '##green##Count##end## Idle Members', 'count' => null, 'links' => array($this -> make_cmd('30', 'memberlist cidle 30'), $this -> make_cmd('60', 'memberlist cidle 60'), $this -> make_cmd('90', 'memberlist cidle 90'), $this -> make_cmd('180', 'memberlist cidle 180'), $this -> make_cmd('270', 'memberlist cidle 270'), $this -> make_cmd('360', 'memberlist cidle 360'))),
			array('title' => '##orange##List##end## Idle Members', 'count' => null, 'links' => array($this -> make_cmd('30', 'memberlist idle 30'), $this -> make_cmd('60', 'memberlist idle 60'), $this -> make_cmd('90', 'memberlist idle 90'), $this -> make_cmd('180', 'memberlist idle 180'), $this -> make_cmd('270', 'memberlist idle 270'), $this -> make_cmd('360', 'memberlist idle 360'))),
			array('title' => '##red##Clear##end## Idle Members', 'count' => null, 'links' => array($this -> make_cmd('90', 'memberlist clear 90'), $this -> make_cmd('180', 'memberlist clear 180'), $this -> make_cmd('360', 'memberlist clear 360'), $this -> make_cmd('720', 'memberlist clear 720'))),						
			array('title' => 'Main chars', 'count' => $main_count, 'links' => array($this -> make_cmd('list', 'memberlist main'))),
			array('title' => 'Alt chars', 'count' => $alt_count, 'links' => array($this -> make_cmd('list', 'memberlist alt'))),
			array('title' => 'Obsolete entries in alts table', 'count' => count($alts) - $alt_count, 'links' => array($this -> make_cmd('list', 'altlist list obsolete'), $this -> make_cmd('clear', 'altlist clear obsolete'))),
		));

		$output .= $this -> section_overview('Buddies', array(
			array('title' => 'Total Buddies', 'count' => count($buddies), 'links' => array($this -> make_cmd('list', 'buddylist'), $this -> make_cmd('clear', 'buddylist clear'), $this -> make_cmd('rosterupdate', '', 'rosterupdate'))),
			array('title' => 'Members', 'count' => $buddy_member_count, 'links' => array()),
			array('title' => 'Missing members', 'count' => ($member_count - $buddy_member_count), 'links' => array($this -> make_cmd('list', 'buddylist missing'))),
			array('title' => 'Guests', 'count' => $buddy_guest_count, 'links' => array()),
			array('title' => 'Anonymous (deleted)', 'count' => $buddy_anonymous_count, 'links' => array()),
			array('title' => 'Banned', 'count' => $buddy_banned_count, 'links' => array()),
		));

		$output .= $this -> section_overview('Whois', array(
			array('title' => 'Total Whois Entries', 'count' => count($whois), 'links' => array($this -> make_cmd('clear', 'whois clear all'))),
			array('title' => 'Members', 'count' => $whois_member_count, 'links' => array($this -> make_cmd('clear', 'whois clear member'))),
			array('title' => 'Guests', 'count' => $whois_guest_count, 'links' => array($this -> make_cmd('clear', 'whois clear guest'))),
			array('title' => 'Anonymous (deleted)', 'count' => $whois_anonymous_count, 'links' => array($this -> make_cmd('clear', 'whois clear anonymous'))),
			array('title' => 'Banned', 'count' => $whois_banned_count, 'links' => array($this -> make_cmd('clear', 'whois clear banned'))),
			array('title' => 'Obsolete entries in whois table', 'count' => count($whois) - ($whois_member_count+$whois_guest_count+$whois_anonymous_count+$whois_banned_count), 'links' => array($this -> make_cmd('clear', 'whois clear obsolete'))),
		));

		$this -> bot -> send_tell($name, $this -> prefix_output(sprintf("Members: ##seablue##%d/%d##end## :: Buddies: ##seablue##%d/%d##end## :: Whois: ##seablue##%d/%d##end## :: %s", $member_count, count($users), $buddy_member_count, count($buddies), $whois_member_count, count($whois), $this -> make_blob('Show', $output))), $origin, 1);

		return false;
	}

	/**
	 * List all buddies
	 * @return string
	 */
	function list_buddies() {
		$buddies = $this -> bot -> aoc -> buddies;
		$count = 0;
		if (empty($buddies)) {
			return "No buddies in <botname>'s buddylist!";
		}
		$buddylist = array();
		foreach ($buddies as $id => $value) {
			$buddylist[$id] = $this -> bot -> core('chat') -> get_uname($id);
			$count++;
		}
		asort($buddylist);
		// let's build a blob
		$output = $this -> blob_header('Buddylist') ."<b></b>\n";
		foreach ($buddylist as $id => $name) {
			$output .= sprintf("%s :: [ %s ]\n", utf8_decode($name), $this -> make_cmd('remove', 'buddy remove '. $id));
		}
		return sprintf("Found ##seablue##%d##end## buddies :: %s", $count, $this -> make_blob('Show', $output));
	}

	/**
	 * List all members who are not on the buddylist
	 * @return string
	 */
	function list_missing_buddies() {
		$buddies = $this -> bot -> aoc -> buddies;
		$users = $this -> load_users(array('u.user_level' => MEMBER));
		if (count($users) > 0) {
			// let's build a blob
			$output = $this -> blob_header('Members not in buddylist') ."<b></b>\n";
			$count = 0;
			foreach ($users as $u) {
				if (!isset($buddies[$u['char_id']])) {
					$output .= sprintf("%s :: %d :: %s :: [ %s | %s | %s | %s ]\n",
						$nickname,
						$u['char_id'],
						$u['last_seen_str'],
						$this -> make_cmd('add', 'buddy add '. $u['char_id']),
						$this -> make_cmd('alts', $nickname, 'alts'),
						$this -> make_cmd('whois', $nickname, 'whois'),
						$this -> make_cmd('delete', $nickname, 'member del'));
					$count++;
				}
			}
			if ($count > 0) return sprintf("Found ##seablue##%d##end## members not in buddylist :: %s", $count, $this -> make_blob('Show', $output));
			else return "All current members are in <botname>'s memberlist";
		}
		else {
			return "No members in <botname>'s memberlist!";
		}
	}

	/**
	 * Clear the buddylist
	 * @return string
	 */
	function clear_buddies() {
		$buddies = $this -> bot -> aoc -> buddies;
		$count = 0;
		foreach ($buddies as $id => $value) {
			$this -> bot -> core('chat') -> buddy_remove($id);
			$count++;
		}
		return sprintf("Removed ##seablue##%d##end## buddies from <botname>'s buddylist", $count);
	}

	/**
	 * Add a character to the buddylist
	 * @param int $char_id
	 * @return string
	 */
	function add_buddy($char_id) {
		$char_name = $this -> bot -> core('chat') -> get_uname($char_id);
		if (!$this -> bot -> core('chat') -> buddy_exists($char_id)) {
			$this -> bot -> core('chat') -> buddy_add($char_id);
			return sprintf("Added ##seablue##%s##end## to <botname>'s buddylist", $char_name);
		}
		else {
			return sprintf("##seablue##%s##end## is already on <botname>'s buddylist", $char_name);
		}
	}

	/**
	 * Remove a character from the buddylist
	 * @param int $char_id
	 * @return string
	 */
	function remove_buddy($char_id) {
		$char_name = $this -> bot -> core('chat') -> get_uname($char_id);
		if ($this -> bot -> core('chat') -> buddy_exists($char_id)) {
			$this -> bot -> core('chat') -> buddy_remove($char_id);
			return sprintf("Removed ##seablue##%s##end## from <botname>'s buddylist", $char_name);
		}
		else {
			return sprintf("##seablue##%s##end## is not on <botname>'s buddylist", $char_name);
		}
	}

	function list_members($filter = null, $limit = 0) {
		if ($filter) $filter = ucfirst(strtolower($filter));
		$cond = array('u.user_level' => MEMBER);
		if ($filter == 'Idle' || $filter == 'Cidle') {
			if ($limit > 0) {
				$offset_time = time() - ($limit * 24 * 60 * 60); // limit is used to determine days since last seen
				$cond['u.last_seen >'] = 0;
				$cond['u.last_seen <'] = intval($offset_time);
			}
			$users = $this -> load_users($cond, ' ORDER BY u.last_seen DESC');
			if (count($users) > 0) {
				$output = $this -> blob_header(count($users). ' idle members last '. $limit .' days') ."<b></b>\n";
				if ($filter == 'Idle') {
					foreach ($users as $char_id => $u) {
						$output .= sprintf("%s @ %s :: [ %s | %s | %s ]\n",
							$u['nickname'],
							$u['last_seen_date'],
							$this -> make_cmd('alts', $u['nickname'], 'alts'),
							$this -> make_cmd('whois', $u['nickname'], 'whois'),
							$this -> make_cmd('delete', $u['nickname'], 'member del'));
					}
				}
				return sprintf("Found ##seablue##%d##end## idle members the last %d day(s) :: %s", count($users), $limit, $this -> make_blob('Show', $output));
			}
			else {
				return "No idlers above ". $limit ." day(s) were found in <botname>'s memberlist.";
			}
		}
		else {
			$users = $this -> load_users($cond);
			if (count($users) > 0) {
				if ($filter == 'Main' || $filter == 'Alt') {
					$alts = $this -> load_alts();
					$count = 0;
					$output = $this -> blob_header($filter .' characters') ."<b></b>\n";
					foreach ($users as $char_id => $u) {
						if (($filter == 'Main' && !isset($alts[$u['nickname']]) ) || ($filter == 'Alt' && isset($alts[$u['nickname']]))) {
							$count++;
							$output .= sprintf("%s @ %s :: [ %s | %s | %s ]\n",
								$u['nickname'],
								$u['last_seen_date'],
								$this -> make_cmd('alts', $u['nickname'], 'alts'),
								$this -> make_cmd('whois', $u['nickname'], 'whois'),
								$this -> make_cmd('delete', $u['nickname'], 'member del'));
						}
					}
					return sprintf("Found ##seablue##%d##end## %s characters :: %s", $count, $filter, $this -> make_blob('Show', $output));
				}
				else { // List all members
					$output = $this -> blob_header('All members') ."<b></b>\n";
					foreach ($users as $char_id => $u) {
						$output .= sprintf("%s @ %s :: [ %s | %s | %s ]\n",
							$u['nickname'],
							$u['last_seen_date'],
							$this -> make_cmd('alts', $u['nickname'], 'alts'),
							$this -> make_cmd('whois', $u['nickname'], 'whois'),
							$this -> make_cmd('delete', $u['nickname'], 'member del'));
					}
					return sprintf("Found ##seablue##%d##end## members :: %s", count($users), $this -> make_blob('Show', $output));
				}
			}
			else {
				return "<botname>'s memberlist is empty.";
			}
		}
	}

	function list_users($level = null) {
		$users = array();
		if ($level == 'all' || !$level) {
			$users = $this -> load_users();
			$output = $this -> blob_header('Userlist') ."<b></b>\n";
		}
		else if (in_array($level, array('member', 'guest', 'anonymous', 'banned'))) {
			$lvl = $this -> user_level_decode($level);
			$users = $this -> load_users(array('u.user_level' => $lvl));
			$output = $this -> blob_header('Userlist :: '. $level ."<b></b>\n");
		}
		if (count($users) > 0) {
			foreach ($users as $char_id => $u) {
				$output .= sprintf("%s ( %s-%d @ %s )\n",
					$u['nickname'],
					strtoupper(substr(($this -> user_level_encode($u['user_level'])),0,1)),
					$u['char_id'],
					$u['last_seen_date']);
			}
			return sprintf("Found ##seablue##%d##end## users :: %s", count($users), $this -> make_blob('Show', $output));
		}
		else {
			return "##red##No matching users found in <botname>'s userlist.##end##";
		}
	}

	/**
	 * Clear entries from users table
	 * @param string $filter
	 * @return string
	 */
	function clear_users($level) {
		if (in_array($level, array('guest', 'anonymous', 'banned'))) {
			$lvl = $this -> user_level_decode($level);
			if ($this -> bot -> db -> query("DELETE FROM #___users WHERE user_level = ". $lvl)) return "Cleared ". $level ." entries from <botname>'s users table.";
			else return "##red##Error clearing ". $level ." entries from <botname>'s users table.##end##";
		} elseif(is_numeric($level)&&$level>=90) {
			$offset_time = time() - ($level * 24 * 60 * 60); // limit is used to determine days since last seen
			if ($this -> bot -> db -> query("DELETE FROM #___users WHERE last_seen > 0 AND last_seen < ". $offset_time)) return "Cleared at least ". $level ." days old entries from <botname>'s users table.";
			else return "##red##Error clearing at least ". $level ." days old idle entries from <botname>'s users table.##end##";			
		}
		return false;
	}

	/**
	 * Clear entries from whois table
	 * @param string $filter
	 * @return string
	 */
	function clear_whois($level) {
		if ($level == 'all') {
			if ($this -> bot -> db -> query("TRUNCATE #___whois")) return "Cleared all entries from <botname>'s whois database.";
			else return "##red##Error clearing all entries from <botname>'s whois database.##end##";
		}		
		else if ($level == 'obsolete') {
			if ($this -> bot -> db -> query("DELETE FROM #___whois WHERE ID NOT IN (SELECT char_id FROM #___users)")) return "Cleared obsolete entries from <botname>'s whois database.";
			else return "##red##Error clearing obsolete entries from <botname>'s whois database.##end##";
		}
		else if (in_array($level, array('member', 'guest', 'anonymous', 'banned'))) {
			$lvl = $this -> user_level_decode($level);
			if ($this -> bot -> db -> query("DELETE FROM #___whois WHERE ID IN (SELECT char_id FROM #___users WHERE user_level = ". $lvl .")")) return "Cleared ". $level ." entries from <botname>'s whois database.";
			else return "##red##Error clearing ". $level ." entries from <botname>'s whois database.##end##";
		}
		return false;
	}

	/**
	 * Clear entries from alts table
	 * @param string $filter
	 * @return string
	 */
	function clear_alts($filter) {
		switch ($filter) {
			case 'obsolete':
				if ($this -> bot -> db -> query("DELETE FROM #___alts WHERE main NOT IN (SELECT nickname FROM #___users WHERE user_level = ". MEMBER .")") && $this -> bot -> db -> query("DELETE FROM #___alts WHERE alt NOT IN (SELECT nickname FROM #___users WHERE user_level = ". MEMBER .")")) return "Cleared obsolete entries from <botname>'s alts table.";
				else return "##red##Error clearing obsolete entries from <botname>'s alts table.##end##";
				break;
		}
		return false;
	}

	/**
	 * List entries from alts table
	 * @param string $filter
	 * @return string
	 */
	function list_obsolete_alts() {
		if ($rows = $this -> bot -> db -> select("SELECT alt, main FROM #___alts WHERE main NOT IN (SELECT nickname FROM #___users WHERE user_level = ". MEMBER .") ORDER BY alt", MYSQLI_ASSOC)) {
			if (count($rows) > 0) {
				$output = $this -> blob_header('Obsolete entries in alts table') ."<b></b>\n";
				foreach ($rows as $r) {
					$output .= sprintf("%s (%s)\n", $r['alt'], $r['main']);
				}
				return sprintf("Found ##seablue##%d##end## obsolete entries in alts table :: %s", count($rows), $this -> make_blob('Show', $output));
			}
		}			
		return "##red##No obsolete entries found in <botname>'s alts table.##end##";
	}

	function load_users($cond = array(), $orderby = ' ORDER BY u.nickname') {
		$users = array();
		if ($rows = $this -> bot -> db -> select("SELECT u.char_id, u.nickname, u.last_seen, FROM_UNIXTIME(u.last_seen) AS last_seen_str, IF(u.last_seen, DATE_FORMAT(FROM_UNIXTIME(u.last_seen), '%Y-%m-%d'), 'N/A') AS last_seen_date, u.added_at, u.banned_at, u.deleted_at, u.user_level FROM #___users u". $this -> where_sql($cond) . $orderby, MYSQLI_ASSOC)) {
			foreach ($rows as $r) {
				$r['nickname'] = utf8_decode($r['nickname']);
				$users[$r['char_id']] = $r;
			}
		}
		return $users;
	}

	function load_alts($cond = array(), $orderby = ' ORDER BY alt') {
		$alts = array();
		if ($rows = $this -> bot -> db -> select("SELECT alt, main FROM #___alts". $this -> where_sql($cond) . $orderby, MYSQLI_ASSOC)) {
			foreach ($rows as $r) {
				$r['main'] = utf8_decode($r['main']);
				$r['alt'] = utf8_decode($r['alt']);
				$alts[$r['alt']] = $r['main'];
			}
		}
		return $alts;
	}

	function load_whois($cond = array(), $orderby = ' ORDER BY nickname') {
		$whois = array();
		if ($rows = $this -> bot -> db -> select("SELECT ID, nickname FROM #___whois". $this -> where_sql($cond) . $orderby, MYSQLI_ASSOC)) {
			foreach ($rows as $r) {
				$whois[$r['ID']] = utf8_decode($r['nickname']);
			}
		}
		return $whois;
	}

	/**
	 * Wrapper for make_blob
	 * @param string $title
	 * @param string $content
	 * @return string
	 */
	function make_blob($title, $content, $attributes = '') {
		// Using " inside a blob will end the blob.
		// Convert opening and closing tags with " to '
		// Convert any other " to HTML entities.
		$content = str_replace("=\"", "='", $content);
		$content = str_replace("\">", "'>", $content);
		$content = str_replace("\"", "&quot;", $content);
		return "<a href=\"text://##white##" . $content . "##end##\"". ($attributes ? ' '. $attributes : '') .">" . $title . "</a>";
	}

	/**
	 * Wrapper for chatcmd
	 * @param string $textlink
	 * @param string $subcmd
	 * @param string $botcmd
	 * @return string
	 */
	function make_cmd($textlink, $subcmd, $botcmd = 'useradmin') {
		$command = $botcmd;
		if (strlen($subcmd) > 0) $command .= ' '. $subcmd;
		return $this -> bot -> core('tools') -> chatcmd($command, $textlink);
	}

    function datetime_to_ts($dt) {
		if (preg_match('/^\s*(\d\d\d\d)-(\d\d)-(\d\d)(\s+(\d?\d):(\d\d):(\d\d))?\s*$/', $dt, $r)) {
			return mktime(intval($r[5]), intval($r[6]), intval($r[7]), $r[2], $r[3], $r[1]);
		}
		else {
			return false;
		}
    }

	function section_overview($title, $lines) {
		if ($title) $output = $this -> blob_section_header($title);
		if (is_array($lines) && count($lines) > 0) {
			foreach ($lines as $l) {
				if ($l['count'] === null) {
					if (count($l['links']) > 0)
						$output .= sprintf("%s%s\n", $l['title'], $this -> link_list($l['links']));
					else
						$output .= sprintf("%s\n", $l['title']);
				}
				else {
					if ($l['count'] > 0 && count($l['links']) > 0)
						$output .= sprintf("%s: ##seablue##%d##end##%s\n", $l['title'], $l['count'], $this -> link_list($l['links']));
					else 
						$output .= sprintf("%s: ##seablue##%d##end##\n", $l['title'], $l['count']);
				}
			}
		}
		return $output;
	}

	function link_list($links, $separator = ' | ', $prefix = ' :: [ ', $suffix = ' ]') {
		if (is_array($links) && count($links) > 0) {
			return $prefix . implode($separator, $links) . $suffix;
		}
		else if (strlen($links) > 0) {
			return $prefix . $links . $suffix;
		}
		return '';
	}

	function to_db_value(&$value, $numeric_padding = false) {
		if (is_null($value)) return 'NULL';
		if (is_numeric($value)) return ($numeric_padding) ? "'". $value ."'" : $value;
		if (get_magic_quotes_gpc()) $value = stripslashes($value);
		return "'". mysql_escape_string($value) ."'";
	}

	/* $conditions = assoc array, $k=>$v becomes "WHERE $k='$v' [AND $k1='$v1']...",
		 or if $v is an array, "WHERE $k in ('$v[0]', '$v[1]', '$v[2]') [AND $k1 in...]".
		 $v is null: "WHERE $k=NULL"
		 $v is an empty array: "" (not added to where cause)
		 special case: $conditions has 1 value, $k=>empty array: "WHERE 0=1" (always false)
	*/
	function where_sql($cond, $prefix = ' WHERE ', $glue = ' AND ') {
		if (!is_array($cond) || count($cond) == 0) return '';

		$sql = '';
		foreach ($cond as $k => $v) {
			if (is_array($v)) {
				if (count($v) == 0) {
					if (count($cond) == 1) return $prefix .'0 = 1'; // Single conditional, array, zero values = always false
					die("where_sql called with zero arguments to $k");
				}
				else if (count($v) == 1) {
					$v =& array_pop($v);
				}
			}
			$sql .= strlen($sql) > 0 ? $glue : $prefix;
			if (!is_array($v)) {
				if (!$k) die("where_sql called with empty key");
				$l = $k[strlen($k) - 1];
				if ($l=='%') {
					$sql .= ' '. substr($k, 0, -1) ." LIKE '%". mysql_escape_string($v) ."%'";
				}
				else {
					if (is_null($v)) $l = ' IS ';
					else if (($l == '<') || ($l == '>') || ($l == '=')) $l = '';
					else $l = ' = ';
					$sql .= ' '. $k . $l . $this -> to_db_value($v);
				}
			}
			else {
				$sql .= $k .' IN (';
				$sep = '';
				foreach ($v as $a) {
					$sql .= $sep;
					$sep = ', ';
					$sql .= $this -> to_db_value($a);
				}
				$sql .= ')';
			}
		}
		return $sql;
	}

}
?>
