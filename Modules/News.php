<?php
/*
* News.php - A BeBot Module - News, Raids, Headline
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
/*
* This version combines the Original News and Raids Modules by Foxferal
* also includes updates/ideas by Naturalistic and Zarkingu.
*    Additional Database Fields added/changed:
*    ID renamed to TIME, ID added to Auto Inc starting at 1
*    TYPE field to Denote News Type I.E. 1 = News, 2 = Headline, 3 = Raid News
*/
$news = new News($bot);
/*
The Class itself...
*/
class News extends BaseActiveModule
{

    /*
    Constructor:
    Hands over a reference to the "Bot" class.
    */
    function __construct(&$bot)
    {
        parent::__construct($bot, get_class($this));
        $this->bot->db->query(
            "CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("news", "true") . " (
		           id INT NOT NULL auto_increment PRIMARY KEY,
		           type INT default '1',
		           time INT NOT NULL default '0',
		           name VARCHAR(255) default NULL,
		           news TEXT
		           )"
        );
		if($this->bot->db->get_version("news")<2) {
			$this->bot->db->query("ALTER TABLE #___news MODIFY COLUMN Name VARCHAR(255)");
			$this -> bot -> db -> set_version("news", 2);
		}
        //Register commands
        $this->register_command('all', 'news', 'GUEST', array('add' => 'MEMBER'));
        $this->register_command('all', 'headline', 'GUEST', array('add' => 'ADMIN'));
        $this->register_command(
            'all',
            'raids',
            'MEMBER',
            array(
                 'add' => 'LEADER',
                 'del' => 'LEADER'
            )
        );
        // Register for logon notifies and pgjoin
        $this->register_event("logon_notify");
        $this->register_event("pgjoin");
        //These are required in order to let authors delete their own messages but not everyones.
        $this->bot->core("settings")
            ->create(
                "News",
                "Headline_Del",
                "ADMIN",
                "Who should be able to delete headlines",
                "ADMIN;LEADER;MEMBER;GUEST;ANONYMOUS"
            );
        $this->bot->core("settings")
            ->create(
                "News",
                "News_Del",
                "ADMIN",
                "Who should be able to delete news",
                "ADMIN;LEADER;MEMBER;GUEST;ANONYMOUS"
            );
        $this->bot->core("prefs")
            ->create(
                "News",
                "Logonspam",
                "What should news spam when logging on?",
                "Last_headline",
                "Last_headline;Link;Nothing"
            );
        $this->bot->core("prefs")
            ->create(
                "News",
                "PGjoinspam",
                "What should news spam when joining private group?",
                "Nothing",
                "Last_headline;Link;Nothing"
            );
        $this->help['description'] = 'Sets and shows headlines, news and raid events.';
        $this->help['command']['news'] = "Shows current headlines and news";
        $this->help['command']['raids'] = "Shows current raid events";
        $this->help['command']['headline add <newsitem>'] = "Adds <newsitem> to current news. ";
        $this->help['command']['news add <newsitem>'] = "Adds <newsitem> to current news. ";
        $this->help['command']['raids add <newsitem>'] = "Adds <newsitem> to current raids. ";
        $this->help['notes'] = "The deletion of headlines, news and raids are managed by the GUI.";
    }


    function notify($name, $startup = false)
    {
        $spam = "";
		if (!$startup) {
            switch ($this->bot->core("prefs")->get($name, "News", "Logonspam")) {
                case 'Last_headline':
                    $spam .= $this->get_last_headline();
                    $spam .= $this->get_news($name);
                    if ($spam != "No news.") {
                        $this->bot->send_output($name, $spam, 'tell');
                    }
                    break;
                case 'Link':
                    $spam .= $this->get_news($name);
                    if ($spam != "No news.") {
                        $this->bot->send_output($name, $spam, 'tell');
                    }
            }
        }
    }


    function pgjoin($name)
    {
        $spam = "";		
        switch ($this->bot->core("prefs")->get($name, "News", "PGjoinspam")) {
            case 'Last_headline':
                $spam .= $this->get_last_headline();
                $spam .= $this->get_news($name);
                if ($spam != "No news.") {
                    $this->bot->send_output($name, $spam, 'tell');
                }
                break;
            case 'Link':
                $spam .= $this->get_news($name);
                if ($spam != "No news.") {
                    $this->bot->send_output($name, $spam, 'tell');
                }
        }
    }


    function command_handler($name, $msg, $origin)
    {
        $com = $this->parse_com($msg);
        switch ($com['com']) {
            case 'news':
                return $this->sub_handler($name, $com, 1);
                break;
            case 'headline':
                return $this->sub_handler($name, $com, 2);
                break;
            case 'raids':
                return $this->sub_handler($name, $com, 3);
                break;
            default:
                $this->error->set("News received unknown command '{$com['com']}'.");
                return $this->error;
                break;
        }
    }


    function sub_handler($name, $com, $type)
    {
        switch ($com['sub']) {
            case '':
            case 'read':
                if (($type == 1) || ($type == 2)) {
                    return $this->get_news($name);
                } else {
                    return $this->get_raids($name);
                }
                break;
            case 'add':
                return $this->set_news($name, $com['args'], $type);
                break;
            case 'del':
            case 'rem':
                return $this->del_news($name, $com['args']);
                break;
            default:
                //No keywords recognized. Assume that person in attempting to add news and forgot the "add" keyword
                $news = "{$com['sub']} {$com['args']}";
                return $this->set_news($name, $news, $type);
                break;
        }
    }


    /*
    Get news
    */
    function get_news($name)
    {
        $newsdate = ""; $inside = "";
        $result_headline = $this->bot->db->select(
            "SELECT id, time, name, news FROM #___news WHERE type = '2' ORDER BY time DESC LIMIT 0, 3"
        );
        if (!empty($result)) {
            $newsdate = gmdate(
                $this->bot->core("settings")
                    ->get("Time", "FormatString"),
                $result[0][1]
            );
        }			
        if (!empty($result_headline)) {
	        $inside = "<center>##ao_infoheadline##:::: Headline ::::##end##</center>\n";
            foreach ($result_headline as $val) {
				$valdate = gmdate(
					$this->bot->core("settings")
						->get("Time", "FormatString"),
					$val[1]
				);	
				$inside .= "##ao_infoheader##On " . 
					$valdate
                    . " GMT ##ao_cctext##" . $val[2] . "##end## Reported:\n";
				if (mb_detect_encoding($val[3], 'UTF-8', true)) {
					$text = mb_convert_encoding($val[3], 'ISO-8859-1', 'UTF-8');
				} else {
					$text = $val[3];
				}					
                $inside .= "##ao_infotext##" . stripslashes($text);
                if (($this->bot->core("security")
                        ->check_access(
                            $name,
                            $this->bot->core("settings")
                                ->get('News', 'Headline_del')
                        ))
                    || ($name == $val[2])
                ) {
                    $inside .= " [" . $this->bot->core("tools")
                            ->chatcmd("headline del " . $val[0], "Delete") . "]";
                }
                $inside .= "\n\n";
            }
        }
        // Create News Items
        $result = $this->bot->db->select(
            "SELECT id, time, name, news FROM #___news WHERE type = '1' ORDER BY time DESC LIMIT 0, 10"
        );
        if (!empty($result)) {
            $inside .= "<center>##ao_infoheadline##:::: News ::::##end##</center>\n";
            foreach ($result as $val) {
				$valdate = gmdate(
					$this->bot->core("settings")
						->get("Time", "FormatString"),
					$val[1]
				);				
                $inside .= "##ao_infoheader##On " .
					$valdate
                    . " GMT ##ao_cctext##" . $val[2] . "##end## Reported:\n";
				if (mb_detect_encoding($val[3], 'UTF-8', true)) {
					$text = mb_convert_encoding($val[3], 'ISO-8859-1', 'UTF-8');
				} else {
					$text = $val[3];
				}					
                $inside .= "##ao_infotext##" . stripslashes($text);
                if (($this->bot->core("security")
                        ->check_access(
                            $name,
                            $this->bot->core("settings")
                                ->get('News', 'News_del')
                        ))
                    || ($name == $val[2])
                ) {
                    $inside .= " [" . $this->bot->core("tools")
                            ->chatcmd("news del " . $val[0], "Delete") . "]";
                }
                $inside .= "\n\n";
            }
        }
        if (!empty($inside)) {
            return "News last updated " . $newsdate . ":: " . $this->bot
                ->core("tools")->make_blob("click to view", $inside);
        } else {
            return "No news.";
        }
    }


    /*
    Fetch the latest newsitem
    */
    function get_last_headline()
    {
        $query = "SELECT name, news from #___news WHERE type = '2' ORDER BY time DESC LIMIT 1";
        $news = $this->bot->db->select($query, MYSQLI_ASSOC);		
        if (empty($news)) {
            return false;
        } else {
			if (mb_detect_encoding($news[0]['news'], 'UTF-8', true)) {
				$text = mb_convert_encoding($news[0]['news'], 'ISO-8859-1', 'UTF-8');
			} else {
				$text = $news[0]['news'];
			}				
            $news = $news[0]['name'] . ':##highlight## ' . $text . "##end##\n";
            return $news;
        }
    }


    /*
    Get Raids
    */
    function get_raids($name)
    {
		$newsdate = "";
        $inside = "<center>##ao_infoheadline##:::: Planned Raids ::::##end##</center>\n";
        $result = $this->bot->db->select("SELECT id, time  FROM #___news WHERE type = '3' ORDER BY id DESC LIMIT 0, 1");
        if (!empty($result)) {
            $newsdate = gmdate(
                $this->bot->core("settings")
                    ->get("Time", "FormatString"),
                $result[0][1]
            );
        }
        $result_raids = $this->bot->db->select(
            "SELECT id, time, name, news FROM #___news WHERE type = '3' ORDER BY time DESC LIMIT 0, 10"
        );
        if (!empty($result_raids)) {
            foreach ($result_raids as $val) {		
				$valdate = gmdate(
					$this->bot->core("settings")
						->get("Time", "FormatString"),
					$val[1]
				);				
                $inside .= "##ao_infoheader##" .
					$valdate
                    . " GMT ##ao_cctext##" . $val[2] . "##end## wrote:\n";
				if (mb_detect_encoding($val[3], 'UTF-8', true)) {
					$text = mb_convert_encoding($val[3], 'ISO-8859-1', 'UTF-8');
				} else {
					$text = $val[3];
				}						
                $inside .= " ##ao_infotext##" . stripslashes($text);
                if ($this->bot->core("security")->check_access(
                        $name,
                        $this->bot
                            ->core("settings")
                            ->get('News', 'News_del')
                    )
                    || ($name == $val[2])
                ) {
                    $inside .= " [" . $this->bot->core("tools")
                            ->chatcmd("raids del " . $val[0], "Delete") . "]";
                }
                $inside .= "\n\n";
            }
        }
        return "Planned Raids last updated " . $newsdate . ":: " . $this->bot
            ->core("tools")->make_blob("click to view", $inside);
    }


    /*
    Adds news (Access is checked on command level)
    */
    function set_news($name, $msg, $type)
    {
		if(mb_detect_encoding($msg, 'UTF-8', false)) $msg = mb_convert_encoding($msg, 'UTF-8', mb_list_encodings());
        $this->bot->db->query(
            "INSERT INTO #___news (type, time, name, news) VALUES ('" . $type . "', " . time(
            ) . ", '" . $name . "', '" . addslashes($msg) . "')"
        );
        return "Your entry has been submitted.";
    }


    function del_news($name, $msg)
    {
        $result = $this->bot->db->select("SELECT name  FROM #___news WHERE id = '" . $msg . "'");
        if (empty($result)) {
            $this->error->set("No entry with id '$msg' found.");
            return $this->error;
        } else {
            foreach ($result as $val) {
                $res_name = $val[0];
            }
        }
        if (($this->bot->core("security")->check_access(
                $name,
                $this->bot
                    ->core("settings")
                    ->get('News', 'News_del')
            ))
            || ($name == $res_name)
        ) {
            $this->bot->db->query("DELETE FROM #___news WHERE id = '" . $msg . "'");
            return "Entry has been removed.";
        } else {
            $this->error->set(
                "You must be " . $this->bot->core("settings")
                    ->get('News', 'News_del') . " or higher or own the entry to delete news"
            );
            return $this->error;
        }
    }
}

?>
