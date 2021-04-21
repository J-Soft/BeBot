<?php
/*
* Raid_Load.php - Module to handle the Raid Loot setup.
*       This module sets up and populates the data base that will be used for the Raid_Loot.php module.
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
*
* Developed by: Shelly Targe with assistance of Ryph - 2020 Update by Bitnykk
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
*/

$raid_load = new raid_load($bot);


/*
The Class itself...
*/
class raid_load extends BasePassiveModule
{
        function __construct(&$bot)
        {
                parent::__construct($bot, get_class($this));

                // id   = mysql DB record number
                // table_version = Version flag
                // raid = raid name
                // area = raid
                // boss = what boss the item belongs to
                // name = the name of the loot
                // img  = the aodb image number for the icon
                // ref  = the aodb info details number
                $version = 10;
                $this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("RaidLoot", "true") . "
                        (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                         table_version INT,
                         raid VARCHAR(255),
                         area VARCHAR(255),
                         boss VARCHAR(255),
                         name VARCHAR(255),
                         img INT,
                         ref INT,
                         multiloot INT)");
                $query = "SELECT id, table_version FROM #___RaidLoot WHERE id = 1 ";
                $test_db = $this -> bot -> db -> select($query, MYSQLI_ASSOC);
                if (!isset($test_db[0]['table_version']) || $test_db[0]['table_version'] < $version) // Update if needed : for production
				//if (true) // Always forced update : for testing
                {
                // The table is out of sync, drop and then reload it
						echo "\n ... Updating Raid Loots ... \n";
                        $this -> bot -> db -> query("DROP TABLE #___RaidLoot");
                        $this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("RaidLoot", "true") . "
                                (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                 table_version VARCHAR(5),
                                 raid VARCHAR(255),
                                 area VARCHAR(255),
                                 boss VARCHAR(255),
                                 name VARCHAR(255),
                                 img INT,
                                 ref INT,
                                 multiloot INT)");
                }
                $query = "SELECT id, table_version, name, ref FROM #___RaidLoot WHERE ref = '244750'";
                $test_db = $this -> bot -> db -> select($query, MYSQLI_ASSOC);
                if(empty($test_db)) // The table has been cleaned, let's refill it below
                {
                //Following loot description scheme :
                //'Raid', 'Area', 'Mob', 'Item Name', 'Icon Value', 'AOID', 'MultiLoot'
				//Pandemonium Zods, TNH, Beast
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastArmor', 'The Beast', 'Sigil of Bahomet', '131259', '244750', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastArmor', 'The Beast', 'Helmet of Hypocrisy', '245034', '244716', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastArmor', 'The Beast', 'Burden of Competence', '245030', '244718', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastArmor', 'The Beast', 'Shoulderplates of Sabotage', '245036', '244713', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastArmor', 'The Beast', 'Cuirass of Obstinacy', '245031', '244717', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastArmor', 'The Beast', 'Sleeves of Senseless Violence', '245029', '244705', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastArmor', 'The Beast', 'Gauntlets of Deformation', '245033', '244704', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastArmor', 'The Beast', 'Armplates of Elimination', '245029', '244712', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastArmor', 'The Beast', 'Greaves of Malfeasance', '245035', '244715', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastArmor', 'The Beast', 'Boots of Concourse', '245032', '244714', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Shadowbreeds', 'The Beast', 'The Dark Side (Omni)', '136597', '215444', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Shadowbreeds', 'The Beast', 'The Lighter Side (Clan)', '136594', '215442', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Shadowbreeds', 'The Beast', 'The Unknown Path (Neutral)', '136596', '215443', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Ardency (NT)', '131260', '244700', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Enterprice (Fixer)', '131260', '244693', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Enticement (Shade)', '131260', '244703', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Equanimity (MA)', '131260', '244691', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Faith (Keeper)', '131260', '244702', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Fidelity (Soldier)', '131260', '244690', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Fortitude (Enf)', '131260', '244698', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Freedom (Adv)', '131260', '244695', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Ingenuity (Engi)', '131260', '244692', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Interchange (Trader)', '131260', '244696', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Management (Crat)', '131260', '244697', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Moral (MP)', '131260', '244701', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Recovery (Doc)', '131260', '244699', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'Stars', 'The Beast', 'Star of Stealth (Agent)', '131260', '244694', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Sunrise Hilt (Clan)', '235342', '246813', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Sunset Hilt (Omni)', '235341', '246814', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Abandonment', '245083', '244742', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Anger', '210184', '244802', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Angst', '245082', '244821', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Chaos', '245089', '244910', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Deceit', '213075', '244779', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Envy', '244836', '244843', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Gluttony', '218714', '244785', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Greed', '244931', '244862', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Hatred', '158269', '244762', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Lust', '244837', '244914', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Pride', '233217', '244783', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Sloth', '233214', '244912', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lord of Wisdom', '293991', '293997', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Abandonment', '245083', '244743', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Anger', '210184', '244801', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Angst', '245082', '244820', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Chaos', '245089', '244909', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Deceit', '213075', '244778', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Envy', '244836', '244842', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Gluttony', '218714', '244784', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Greed', '244931', '244859', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Hatred', '158269', '244761', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Lust', '244837', '244913', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Pride', '233217', '244782', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Sloth', '233214', '244911', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'BeastWeapons', 'The Beast', 'Lady of Wisdom', '293991', '294000', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'TNH', 'The Night Heart', 'Maar&#39;s Blue Belt of Double Prudence (Int/Psy)', '244991', '244989', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'TNH', 'The Night Heart', 'Maar&#39;s Red Belt of Double Power (Str/Sta)', '244991', '244988', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'TNH', 'The Night Heart', 'Maar&#39;s Yellow Belt of Double Speed (Agi/Sen)', '244991', '244990', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'TNH', 'The Night Heart', 'Notum Seed (Clan)', '246815', '246818', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'TNH', 'The Night Heart', 'Novictum Seed (Omni)', '246816', '246817', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'NorthZods', 'Capricorn', 'Capricorn Bracer of Toxication', '205519', '244564', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'NorthZods', 'Capricorn', 'Capricorn&#39;s Guide to Alchemy', '136332', '244563', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'NorthZods', 'Capricorn', 'Capricorn&#39;s Reliable Memory', '205523', '244561', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'NorthZods', 'Capricorn', 'Gloves of the Caring Capricorn', '21871', '244562', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'NorthZods', 'Sagittarius', 'Comfort of the Sagittarius', '37969', '244645', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'NorthZods', 'Sagittarius', 'First Creation of the Sagittarius', '226595', '244644', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'NorthZods', 'Sagittarius', 'Sagittarius&#39; Hearty Spirit Helper', '12701', '244643', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'NorthZods', 'Sagittarius', 'Strong Mittens of the Sagittarius', '22939', '244647', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'NorthZods', 'Scorpio', 'Punters of the Scorpio', '213546', '244650', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'NorthZods', 'Scorpio', 'Sash of Scorpio Strength', '161070', '244648', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'NorthZods', 'Scorpio', 'Scorpio&#39;s Aim of Anger', '245000', '244655', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'NorthZods', 'Scorpio', 'Scorpio&#39;s Shell of Change', '22401', '244654', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'MiddleZods', 'Libra', 'Aim of Libra', '130840', '244637', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'MiddleZods', 'Libra', 'Libra&#39;s Charming Assistant', '213659', '244635', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'MiddleZods', 'Libra', 'Urbane Pants of Libra', '22914', '244581', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'MiddleZods', 'Libra', 'Well Balanced Spirit Helper of Libra', '119141', '244579', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'MiddleZods', 'Pisces', 'Cosmic Guide of the Pisces', '151918', '244641', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'MiddleZods', 'Pisces', 'Mystery of Pisces', '161079', '244640', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'MiddleZods', 'Pisces', 'Octopus Contraption of the Pisces', '203513', '244639', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'MiddleZods', 'Pisces', 'Soul Mark of Pisces', '226602', '244638', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'MiddleZods', 'Taurus', 'Taurus&#39; Ring of the Heart', '84067', '244661', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'MiddleZods', 'Taurus', 'Taurus&#39; Spirit of Patience', '130792', '244659', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'MiddleZods', 'Taurus', 'Taurus&#39; Spirit of Reflection', '149940', '244658', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'MiddleZods', 'Taurus', 'Taurus&#39; Swordmaster Spirit', '130788', '244660', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'EastZods', 'Aquarius', 'Aquarius&#39; Boots of Small Steps', '37596', '244357', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'EastZods', 'Aquarius', 'Aquarius&#39; Multitask Calculator', '205528', '244376', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'EastZods', 'Aquarius', 'Intuitive Memory of the Aquarius', '244359', '244358', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'EastZods', 'Aquarius', 'Mediative Gloves of the Aquarius', '99790', '244223', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'EastZods', 'Cancer', 'Cancer&#39;s Gloves of Automatic Knowledge', '37106', '244559', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'EastZods', 'Cancer', 'Cancer&#39;s Ring of Circumspection', '84063', '244560', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'EastZods', 'Cancer', 'Cancer&#39;s Silver Boots of the Autodidact', '37127', '244543', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'EastZods', 'Cancer', 'Cancer&#39;s Time-Saving Memory', '205514', '244558', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'EastZods', 'Gemini', 'Collector Pants of Gemini', '30938', '244566', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'EastZods', 'Gemini', 'Cross Dimensional Gyro of Gemini', '149938', '244567', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'EastZods', 'Gemini', 'Gemini&#39;s Double Band of Linked Information', '151932', '244572', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'EastZods', 'Gemini', 'Gemini&#39;s Green Scope of Variety', '130891', '244565', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Aries', 'Aries&#39; Tiara of the Quick Witted', '84061', '244540', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Aries', 'Boon of Aries', '205497', '244405', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Aries', 'Dynamic Sleeve of Aries (Multi Melee - Right Arm)', '22953', '244542', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Aries', 'Dynamic Sleeve of Aries (Multi Ranged - Left Arm)', '22953', '244541', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Aries', 'Quick-Draw Holster of Aries', '205533', '244469', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Leo', 'Enthusiastic Spirit Helper of the Leo', '119139', '244575', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Leo', 'Leo&#39;s Faithful Boots of Ancient Gold', '22883', '244574', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Leo', 'Leo&#39;s Grandiose Gold Armband of Plenty', '151931', '244573', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Leo', 'Leo&#39;s Mellow Gold Pad of Auto-Support', '213665', '244578', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Virgo', 'Virgo&#39;s Analytical Spirit Helper', '11652', '244665', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Virgo', 'Virgo&#39;s Arrow Guide', '13280', '244662', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Virgo', 'Virgo&#39;s Modest Spirit of Faith', '20411', '244664', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'pande', 'WestZods', 'Virgo', 'Virgo&#39;s Practical Spirit Helper', '11646', '244663', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                //Alien Playfields
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Core', 'Power Core Mainboard', '287968', '287975', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Core', 'Power Core Stabilizer', '287971', '287976', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Core', 'Inactive Power Core', '287970', '287974', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }				
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Belt', 'Basic Belt', '288089', '288131', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Belt', 'Viral Belt Control Component', '288090', '288130', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Belt', 'Viral Belt NCU Slots', '288091', '288133', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Belt', 'Viral Belt Nanobot Power Unit', '288092', '288135', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 NCU', 'Active Viral CPU Upgrade', '288101', '288106', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 NCU', 'Active Viral Computer Deck Range Increaser', '288104', '288125', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 NCU', 'Active Viral NCU Coolant Sink', '288098', '288129', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 NCU', 'Viral Memory Storage Unit (Damage)', '288099', '288140', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 NCU', 'Passive Viral CPU Upgrade', '288100', '288107', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 NCU', 'Passive Viral Computer Deck Range Increaser', '288103', '288126', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 NCU', 'Passive Viral NCU Coolant Sink', '288097', '288128', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 NCU', 'Viral Memory Storage Unit (XP)', '288102', '288139', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Axe', '288702', '288290', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Cannon', '288706', '288298', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Carbine', '288706', '288283', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Crossbow', '288707', '288286', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Energy Pistol', '288708', '288297', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Energy Rapier', '288709', '288287', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Grenade Gun', '288706', '288296', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Hammer', '288703', '288292', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Nunchacko', '288713', '288291', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Pistol', '288708', '288293', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Rapier', '288709', '288288', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Rifle', '288711', '288284', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Shotgun', '288712', '288289', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Sledgehammer', '288705', '288285', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Submachine Gun', '288710', '288295', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Weapon', 'Special Edition Kyr&#39;Ozch Sword', '288704', '288294', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Storage', 'Kyr&#39;Ozch Storage Box (Viral Communications Larvae)', '288690', '288281', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '7', 'S7 Storage', 'Kyr&#39;Ozch Storage Container (Kyr&#39;Ozch Nano Protection Ring)', '288105', '288204', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Boss Loot', 'Inactive Alien Tank Armor', '22401', '268507', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Boss Loot', 'Inactive Alien Battery', '220416', '268496', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Boss Loot', 'Inactive Alien Beacon', '220416', '268494', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Boss Loot', 'Inactive Alien Material Conversion Kit', '99658', '268510', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Boss Loot', 'Inactive Alien Reflex Modifier', '220416', '268499', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Boss Loot', 'Inactive Alien Translation Device', '12692', '268477', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Boss Loot', 'Inactive Empty Alien Augmentation Device', '218751', '268493', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Common Loot', 'Alien Activation Crystal', '12252', '268478', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Common Loot', 'Alien Data Storage Crystal - Combat', '83069', '268479', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Common Loot', 'Alien Data Storage Crystal - Defense', '83069', '268481', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Common Loot', 'Alien Data Storage Crystal - Insight', '83069', '268484', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Common Loot', 'Alien Data Storage Crystal - Medical', '83069', '268483', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Common Loot', 'Alien Data Storage Crystal - Nano Technology', '83069', '268480', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Common Loot', 'Alien Data Storage Crystal - Protection', '83069', '268485', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Common Loot', 'Alien Data Storage Crystal - Technical', '83069', '268482', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '10', 'Common Loot', 'Alien Armor Materials', '144715', '268508', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '13', 'Master Genesplicer Cha&#39;Khaz', 'Action Probability Estimator', '203502', '257960', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '13', 'Master Genesplicer Cha&#39;Khaz', 'Dynamic Gas Redistribution Valves', '205508', '257962', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '13', 'Master Genesplicer Cha&#39;Khaz', 'Kyr&#39;Ozch Helmet', '230855', '257706', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '13', 'Master Genesplicer Cha&#39;Khaz', 'Hacker Ice-Breaker Source', '257196', '257968', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '13', 'Master Genesplicer Cha&#39;Khaz', 'Kyr&#39;Ozch Battlesuit Audio Processor', '218775', '257529', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '13', 'Master Genesplicer Cha&#39;Khaz', 'Kyr&#39;Ozch Rank Identification', '218768', '257531', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '13', 'Master Genesplicer Cha&#39;Khaz', 'Kyr&#39;Ozch Video Processing Unit', '218758', '257533', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '13', 'Master Genesplicer Cha&#39;Khaz', 'Kyr&#39;Ozch Data Core', '25800', '258294', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '13', 'Master Genesplicer Cha&#39;Khaz', 'Biotech Matrix', '275972', '275916', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '13', 'Master Genesplicer Cha&#39;Khaz', 'Gelatinous Lump', '275962', '275909', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '28', 'Embalmer Cha&#39;Khaz', 'Notum Amplification Coil', '257195', '257963', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '28', 'Embalmer Cha&#39;Khaz', 'Inertial Adjustment Processing Unit', '11618', '257959', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '28', 'Embalmer Cha&#39;Khaz', 'Kyr&#39;Ozch Helmet', '230855', '257706', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '28', 'Embalmer Cha&#39;Khaz', 'Hacker Ice-Breaker Source', '257196', '257968', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '28', 'Embalmer Cha&#39;Khaz', 'Kyr&#39;Ozch Battlesuit Audio Processor', '218775', '257529', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '28', 'Embalmer Cha&#39;Khaz', 'Kyr&#39;Ozch Rank Identification', '218768', '257531', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '28', 'Embalmer Cha&#39;Khaz', 'Kyr&#39;Ozch Video Processing Unit', '218758', '257533', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '28', 'Embalmer Cha&#39;Khaz', 'Kyr&#39;Ozch Data Core', '25800', '258293', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '28', 'Embalmer Cha&#39;Khaz', 'Crystalline Matrix', '275964', '275912', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '28', 'Embalmer Cha&#39;Khaz', 'Kyr&#39;Ozch Circuitry', '275965', '275914', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '35', 'Field Marshal and Support Cha&#39;Khaz', 'Visible Light Remodulation Device', '235270', '257964', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '35', 'Field Marshal and Support Cha&#39;Khaz', 'Energy Redistribution Unit', '257197', '257961', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '35', 'Field Marshal and Support Cha&#39;Khaz', 'Kyr&#39;Ozch Helmet', '230855', '257706', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '35', 'Field Marshal and Support Cha&#39;Khaz', 'Hacker Ice-Breaker Source', '257196', '257968', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '35', 'Field Marshal and Support Cha&#39;Khaz', 'Kyr&#39;Ozch Battlesuit Audio Processor', '218775', '257529', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '35', 'Field Marshal and Support Cha&#39;Khaz', 'Kyr&#39;Ozch Rank Identification', '218768', '257531', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '35', 'Field Marshal and Support Cha&#39;Khaz', 'Kyr&#39;Ozch Video Processing Unit', '218758', '257533', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '35', 'Field Marshal and Support Cha&#39;Khaz', 'Kyr&#39;Ozch Data Core', '25800', '258292', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '35', 'Field Marshal and Support Cha&#39;Khaz', 'Alpha Program Chip', '275970', '275918', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '35', 'Field Marshal and Support Cha&#39;Khaz', 'Beta Program Chip', '275969', '275919', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '35', 'Field Marshal and Support Cha&#39;Khaz', 'Kyr&#39;Ozch Processing Unit', '275960', '275907', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '35', 'Field Marshal and Support Cha&#39;Khaz', 'Odd Kyr&#39;Ozch Nanobots', '11750', '275906', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'East - Field Marshal & Support', 'Visible Light Remodulation Device', '235270', '257964', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'East - Field Marshal & Support', 'Energy Redistribution Unit', '257197', '257961', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'East - Field Marshal & Support', 'Hacker Ice-Breaker Source', '257196', '257968', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'North - Master Genesplicer', 'Action Probability Estimator', '203502', '257960', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'North - Master Genesplicer', 'Dynamic Gas Redistribution Valves', '205508', '257962', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'North - Master Genesplicer', 'Hacker Ice-Breaker Source', '257196', '257968', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'West - Proto-Embalmer', 'Notum Amplification Coil', '257195', '257963', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'West - Proto-Embalmer', 'Inertial Adjustment Processing Unit', '11618', '257959', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'West - Proto-Embalmer', 'Hacker Ice-Breaker Source', '257196', '257968', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'West - Proto-Embalmer', 'Alien Matrix Alpha Box', '275887', '275706', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'West - Proto-Embalmer', 'Alien Matrix Beta Box', '275889', '275854', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'Artillery Commander', 'Kyr&#39;Ozch Invasion Plan', '35990', '262656', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'apf', '42', 'Artillery Commander', 'Unlearning Device', '290826', '260422', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                //Legacy of the Xan
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Unknown Mixture', '281093', '281095', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'A piece of cloth', '281096', '281098', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Nanodeck Activation Device', '280784', '281157', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Multi Colored Xan Belt Tuning Device', '280987', '279447', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Green Xan Belt Tuning Device', '280988', '279446', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Xan Weapon Upgrade Device', '246391', '280786', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Xan Combat Merit Board Base', '279442', '279439', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Xan Defense Merit Board Base', '279443', '279440', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Brute&#39;s Gem (Enf)', '281224', '281213', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Builder&#39;s Gem (Engi)', '281224', '281214', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Dictator&#39;s Gem (Crat)', '281224', '281211', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Explorer&#39;s Gem (Adv)', '281224', '281210', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Hacker&#39;s Gem (Fix)', '281224', '281215', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Healer&#39;s Gem (Doc)', '281224', '281212', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Master&#39;s Gem (MA)', '281224', '281217', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Merchant&#39;s Gem (Trader)', '281224', '281222', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Protector&#39;s Gem (Keeper)', '281224', '281216', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Sniper&#39;s Gem (Agent)', '281224', '281209', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Spirit&#39;s Gem (Shade)', '281224', '281220', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Techno Wizard&#39;s Gem (NT)', '281224', '281219', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Warrior&#39;s Gem (Soldier)', '281224', '281221', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Worshipper&#39;s Gem (MP)', '281224', '281218', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Xan Ear Symbiant (any profile by rightclick)', '230978', '278894', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan', 'Xan Thigh Symbiant (any profile by rightclick)', '215191', '278903', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Right Arm Symbiant, Artillery Unit Beta', '215176', '278899', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Right Arm Symbiant, Control Unit Beta', '215176', '279016', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Right Arm Symbiant, Extermination Unit Beta', '215176', '279029', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Right Arm Symbiant, Infantry Unit Beta', '215176', '279042', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Right Arm Symbiant, Support Unit Beta', '215176', '279055', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Right Hand Symbiant, Artillery Unit Beta', '215173', '278900', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Right Hand Symbiant, Control Unit Beta', '215173', '279017', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Right Hand Symbiant, Extermination Unit Beta', '215173', '279030', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Right Hand Symbiant, Infantry Unit Beta', '215173', '279043', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Right Hand Symbiant, Support Unit Beta', '215173', '279056', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Feet Symbiant, Artillery Unit Beta', '215184', '278904', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Feet Symbiant, Control Unit Beta', '215184', '279021', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Feet Symbiant, Extermination Unit Beta', '215184', '279034', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Feet Symbiant, Infantry Unit Beta', '215184', '279047', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Symbiants', 'Xan Feet Symbiant, Support Unit Beta', '215184', '279060', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Right Limb Spirit of Essence - Beta', '231004', '279087', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Right Limb Spirit of Strength - Beta', '231004', '279088', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Right Limb Spirit of Weakness - Beta', '231004', '279089', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Right Hand Defensive Spirit - Beta', '231002', '279090', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Right Hand Strength Spirit - Beta', '231002', '279091', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Spirit of Insight - Right Hand - Beta', '231002', '279092', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Spirit of Feet Defense - Beta', '230990', '279101', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Spirit of Feet Strength - Beta', '230990', '279102', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Spirit of Defense - Beta', '230998', '279099', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Spirit of Essence - Beta', '230998', '279100', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Spirit of Essence Whispered - Beta', '230986', '279072', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Spirit of Knowledge Whispered - Beta', '230986', '279073', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', '12-man', 'Deranged Xan Spirits', 'Xan Spirit of Strength Whispered - Beta', '230986', '279074', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Technomaster Sinuh', 'Nanodeck Activation Device', '280784', '281157', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Technomaster Sinuh', 'Multi Colored Xan Belt Tuning Device', '280987', '279447', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Technomaster Sinuh', 'Green Xan Belt Tuning Device', '280988', '279446', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Technomaster Sinuh', 'Xan Weapon Upgrade Device', '246391', '280786', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Technomaster Sinuh', 'Xan Combat Merit Board Base', '279442', '279439', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Technomaster Sinuh', 'Xan Defense Merit Board Base', '279443', '279440', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Brain Symbiant, Artillery Unit Beta', '215189', '278892', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Brain Symbiant, Control Unit Beta', '215189', '279009', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Brain Symbiant, Extermination Unit Beta', '215189', '279022', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Brain Symbiant, Infantry Unit Beta', '215189', '279035', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Brain Symbiant, Support Unit Beta', '215189', '279048', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Chest Symbiant, Artillery Unit Beta', '215181', '278895', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Chest Symbiant, Control Unit Beta', '215181', '279012', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Chest Symbiant, Extermination Unit Beta', '215181', '279025', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Chest Symbiant, Infantry Unit Beta', '215181', '279038', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Chest Symbiant, Support Unit Beta', '215181', '279051', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Left Hand Symbiant, Artillery Unit Beta', '215171', '278897', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Left Hand Symbiant, Control Unit Beta', '215171', '279014', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Left Hand Symbiant, Extermination Unit Beta', '215171', '279027', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Left Hand Symbiant, Infantry Unit Beta', '215171', '279040', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Left Hand Symbiant, Support Unit Beta', '215171', '279053', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Left Wrist Symbiant, Artillery Unit Beta', '215198', '278898', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Left Wrist Symbiant, Control Unit Beta', '215198', '279015', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Left Wrist Symbiant, Extermination Unit Beta', '215198', '279028', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Left Wrist Symbiant, Infantry Unit Beta', '215198', '279041', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Symbiants', 'Xan Left Wrist Symbiant, Support Unit Beta', '215198', '279054', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Spirits', 'Xan Brain Spirit of Computer Skill - Beta', '230992', '279321', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Spirits', 'Xan Brain Spirit of Offence - Beta', '230992', '279068', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Spirits', 'Xan Essence Brain Spirit - Beta', '230992', '279069', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Spirits', 'Xan Left Hand Spirit of Defence - Beta', '230994', '279083', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Spirits', 'Xan Left Hand Spirit of Strength - Beta', '230994', '279084', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Spirits', 'Xan Spirit of Left Wrist Defense - Beta', '231000', '279085', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Spirits', 'Xan Spirit of Left Wrist Strength - Beta', '231000', '279086', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Spirits', 'Xan Heart Spirit of Essence - Beta', '230984', '279075', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Spirits', 'Xan Heart Spirit of Knowledge - Beta', '230984', '279076', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Spirits', 'Xan Heart Spirit of Strength - Beta', '230984', '279077', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Spirits', 'Xan Spirit of Clear Thought - Beta', '230992', '279070', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'alienthreat', 'Sinuh Spirits', 'Xan Heart Spirit of Weakness - Beta', '230984', '279078', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Ground Chief Vortexx', 'Nanodeck Activation Device', '280784', '281157', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Ground Chief Vortexx', 'Multi Colored Xan Belt Tuning Device', '280987', '279447', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Ground Chief Vortexx', 'Green Xan Belt Tuning Device', '280988', '279446', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Ground Chief Vortexx', 'Xan Weapon Upgrade Device', '246391', '280786', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Ground Chief Vortexx', 'Xan Combat Merit Board Base', '279442', '279439', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Ground Chief Vortexx', 'Xan Defense Merit Board Base', '279443', '279440', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Left Arm Symbiant, Artillery Unit Beta', '215179', '278896', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Left Arm Symbiant, Control Unit Beta', '215179', '279013', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Left Arm Symbiant, Extermination Unit Beta', '215179', '279026', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Left Arm Symbiant, Infantry Unit Beta', '215179', '279039', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Left Arm Symbiant, Support Unit Beta', '215179', '279052', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Ocular Symbiant, Artillery Unit Beta', '230980', '278893', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Ocular Symbiant, Control Unit Beta', '230980', '279010', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Ocular Symbiant, Extermination Unit Beta', '230980', '279023', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Ocular Symbiant, Infantry Unit Beta', '230980', '279036', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Ocular Symbiant, Support Unit Beta', '230980', '279049', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Right Wrist Symbiant, Artillery Unit Beta', '215170', '278901', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Right Wrist Symbiant, Control Unit Beta', '215170', '279018', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Right Wrist Symbiant, Extermination Unit Beta', '215170', '279031', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Right Wrist Symbiant, Infantry Unit Beta', '215170', '279044', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Right Wrist Symbiant, Support Unit Beta', '215170', '279057', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Waist Symbiant, Artillery Unit Beta', '215193', '278902', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Waist Symbiant, Control Unit Beta', '215193', '279019', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Waist Symbiant, Extermination Unit Beta', '215193', '279032', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Waist Symbiant, Infantry Unit Beta', '215193', '279045', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Symbiants', 'Xan Waist Symbiant, Support Unit Beta', '215193', '279058', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Spirits', 'Xan Spirit of Right Wrist Offence - Beta', '231006', '279093', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Spirits', 'Xan Spirit of Right Wrist Weakness - Beta', '231006', '279094', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Spirits', 'Xan Left Limb Spirit of Essence - Beta', '230995', '279079', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Spirits', 'Xan Left Limb Spirit of Strength - Beta', '230995', '279080', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Spirits', 'Xan Left Limb Spirit of Understanding - Beta', '230995', '279081', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Spirits', 'Xan Left Limb Spirit of Weakness - Beta', '230995', '279082', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Spirits', 'Xan Midriff Spirit of Essence - Beta', '230976', '279095', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Spirits', 'Xan Midriff Spirit of Knowledge - Beta', '230976', '279096', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Spirits', 'Xan Midriff Spirit of Strength - Beta', '230976', '279097', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Spirits', 'Xan Midriff Spirit of Weakness - Beta', '230976', '279098', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Spirits', 'Xan Spirit of Discerning Weakness - Beta', '230988', '279071', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'lox', 'vortexx', 'Vortexx Spirits', 'Xan Spirit of Essence - Beta', '230988', '279173', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                //Dust Brigade
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Dust Brigade Special Ops Helmet', '292077', '292191', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }			
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Advanced Dust Brigade Notum Infuser', '292570', '292567', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }	
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Dust Brigade Bracer - First Edition', '292571', '292566', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Dust Brigade Bracer - Second Edition', '292572', '292565', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Dust Brigade Bracer - Third Edition', '292569', '292564', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Enhanced Safeguarded NCU Memory Unit', '292679', '269985', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Enhanced Safeguarded NCU Memory Unit', '292672', '269986', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Enhanced Safeguarded NCU Memory Unit', '292686', '269987', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Dust Brigade Assistance Module', '292179', '292158', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Dust Brigade Assistance Module', '292177', '292159', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Dust Brigade Assistance Module', '292182', '292160', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Dust Brigade Assistance Module', '292180', '292161', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Dust Brigade Assistance Module', '292178', '292162', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Dust Brigade Assistance Module', '292176', '292163', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db3', 'db3', 'Dust Brigade Assistance Module', '292179', '292603', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }				
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db2', 'Ground Chief Aune', 'Basic Infused Dust Brigade Bracer', '84062', '274541', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db2', 'Ground Chief Aune', 'Dust Brigade Notum Infuser', '218768', '274552', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db2', 'Ground Chief Aune', 'Dust Brigade Solar Notum Infuser', '218768', '274558', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db2', 'Ground Chief Aune', 'Dust Brigade Engineer Pistol', '264787', '274559', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db2', 'Ground Chief Aune', 'Black Molybdenum-Matrix of Xan', '272534', '272458', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db2', 'Ground Chief Aune', 'White Molybdenum-Matrix of Xan', '272535', '272459', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db2', 'Ground Chief Aune', 'Enhanced Dustbrigade Chemist Gloves', '21871', '270393', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db2', 'Ground Chief Aune', 'Enhanced Dustbrigade Covering', '155108', '269997', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db2', 'Ground Chief Aune', 'Enhanced Dustbrigade Flexible Boots', '31746', '270392', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db2', 'Ground Chief Aune', 'Enhanced Dustbrigade Notum Gloves', '21871', '270394', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db2', 'Ground Chief Aune', 'Enhanced Dustbrigade Sleeves', '13233', '269996', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Enhanced Dustbrigade Chemist Gloves', '21871', '270393', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Enhanced Dustbrigade Combat Chestpiece', '32162', '269993', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Enhanced Dustbrigade Covering', '155108', '269997', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Enhanced Dustbrigade Fleixble Boots', '31746', '270392', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Enhanced Dustbrigade Notum Gloves', '21871', '270394', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Enhanced Dustbrigade Sleeves', '13233', '269996', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Enhanced Dustbrigade Spirit-tech Chestpiece', '32162', '269994', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Enhanced Safeguarded NCU Memory Unit (Int/Psy)', '119134', '269985', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Enhanced Safeguarded NCU Memory Unit (Str/Sta)', '119134', '269986', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Enhanced Safeguarded NCU Memory Unit (Agi/Sen)', '119134', '269987', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Protected Safeguarded NCU Memory Unit (Evades)', '119134', '269990', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Master Combat Program', '269949', '269960', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Master Melee Program', '269948', '269961', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'db', 'db1', 'Ground Chief Mikkelsen', 'Master Nano Technology Program', '269950', '269959', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                //Alappaa
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'alappaa', 'alappaa', 'Reprogrammed Mezzorash', 'Frozen Shoulderpads', '269968', '269896', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'alappaa', 'alappaa', 'Reprogrammed Mezzorash', 'Combat Program', '269949', '269946', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'alappaa', 'alappaa', 'Reprogrammed Mezzorash', 'Melee Program', '269948', '269947', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'alappaa', 'alappaa', 'Reprogrammed Mezzorash', 'Nano Technology Program', '269950', '269945', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                //Albtraum
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmemories', 'Crystalised Memories', 'Crystalised Memories of a Defender', '72771', '267711', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmemories', 'Crystalised Memories', 'Crystalised Memories of a Doctor', '72771', '267726', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmemories', 'Crystalised Memories', 'Crystalised Memories of a Engineer', '72771', '267714', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmemories', 'Crystalised Memories', 'Crystalised Memories of a Mechanic', '72771', '267701', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmemories', 'Crystalised Memories', 'Crystalised Memories of an Archer', '72771', '267708', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmemories', 'Crystalised Memories', 'Crystalised Memories of an Instructor', '72771', '267710', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmemories', 'Crystalised Memories', 'Crystalised Memories of a Scientist', '72771', '267713', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmemories', 'Crystalised Memories', 'Crystalised Memories of a Sniper', '72771', '267704', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmemories', 'Crystalised Memories', 'Crystalised Memories of a Surgeon', '72771', '267728', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmemories', 'Crystalised Memories', 'Crystalised Memories of a Technician', '72771', '267698', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmemories', 'Crystalised Memories', 'Crystalised Memories of a Warrior', '72771', '267697', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albrings', 'Albtraum Rings', 'Ring of Divided Loyalty', '245483', '267905', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albrings', 'Albtraum Rings', 'Ring of Gruesome Misery', '84067', '267906', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albrings', 'Albtraum Rings', 'Ring of Sister Merciless', '84067', '267907', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albrings', 'Albtraum Rings', 'Ring of Sister Pestilence', '84067', '267909', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albrings', 'Albtraum Rings', 'Ring of Summoned Terror', '84067', '267911', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmisc', 'Albtraum Misc', 'Ancient Damage Generation Device', '218770', '267725', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmisc', 'Albtraum Misc', 'Ancient Scrap of Spirit Knowledge', '163575', '267681', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmisc', 'Albtraum Misc', 'Ancient Scrap of Saturated Spirit Knowledge', '163575', '267679', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmisc', 'Albtraum Misc', 'Ancient Speed Preservation Unit', '218753', '267625', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmisc', 'Albtraum Misc', 'Ancient Vision Preservation Unit', '218752', '267626', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmisc', 'Albtraum Misc', 'Dormant Ancient Circuit', '158233', '267794', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmisc', 'Albtraum Misc', 'Empty Ancient Device', '218753', '267709', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmisc', 'Albtraum Misc', 'Energy Infused Crystal', '156567', '267749', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmisc', 'Albtraum Misc', 'Inactive Ancient Engineering Device', '156094', '267748', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmisc', 'Albtraum Misc', 'Inactive Ancient Medical Device', '218774', '267735', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmisc', 'Albtraum Misc', 'Inactive Ancient Bracer', '84048', '267746', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'albtraum', 'albmisc', 'Albtraum Misc', 'Inert Knowledge Crystal', '151030', '267737', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                //Biodome
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Uqhart the Absorber', 'Pattern of Indomitable Life (MP Rihwen)', '231182', '255549', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Uqhart the Absorber', 'Pattern of the Occurence of Death (NT Self Illumination)', '231182', '255554', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Uqhart the Absorber', 'Business Card (Associate) (Crat Carlo)', '258540', '258563', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Jathos&#39; Molybdenum Armor (Not Omni)', 'Jathos&#39; Molybdenum Plate Boots', '252431', '252002', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Jathos&#39; Molybdenum Armor (Not Omni)', 'Jathos&#39; Molybdenum Plate Gloves', '252439', '252004', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Jathos&#39; Molybdenum Armor (Not Omni)', 'Jathos&#39; Molybdenum Plate Helmet', '252430', '252005', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Jathos&#39; Molybdenum Armor (Not Omni)', 'Jathos&#39; Molybdenum Plate Pants', '252437', '252000', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Jathos&#39; Molybdenum Armor (Not Omni)', 'Jathos&#39; Molybdenum Plate Sleeves', '252435', '252003', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Jathos&#39; Molybdenum Armor (Not Omni)', 'Jathos&#39; Molybdenum Plate Vest', '252433', '252001', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Kegern&#39;s Molybdenum Armor (Not Clan)', 'Kegern&#39;s Molybdenum Plate Boots', '252432', '252020', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Kegern&#39;s Molybdenum Armor (Not Clan)', 'Kegern&#39;s Molybdenum Plate Gloves', '252440', '252018', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Kegern&#39;s Molybdenum Armor (Not Clan)', 'Kegern&#39;s Molybdenum Plate Helmet', '252429', '252017', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Kegern&#39;s Molybdenum Armor (Not Clan)', 'Kegern&#39;s Molybdenum Plate Pants', '252438', '252022', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Kegern&#39;s Molybdenum Armor (Not Clan)', 'Kegern&#39;s Molybdenum Plate Sleeves', '252436', '252019', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Kegern&#39;s Molybdenum Armor (Not Clan)', 'Kegern&#39;s Molybdenum Plate Vest', '252434', '252021', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Proactive Armor (Not Clan)', 'Proactive Boots', '252432', '252013', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Proactive Armor (Not Clan)', 'Proactive Gloves', '252440', '252015', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Proactive Armor (Not Clan)', 'Proactive Helmet', '252429', '252016', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Proactive Armor (Not Clan)', 'Proactive Pants', '252438', '252011', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Proactive Armor (Not Clan)', 'Proactive Sleeves', '252436', '252014', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Proactive Armor (Not Clan)', 'Proactive Vest', '252434', '252012', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Reactive Armor (Not Omni)', 'Reactive Boots', '252431', '251996', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Reactive Armor (Not Omni)', 'Reactive Gloves', '252439', '251998', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Reactive Armor (Not Omni)', 'Reactive Helmet', '252430', '251999', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Reactive Armor (Not Omni)', 'Reactive Pants', '252437', '251995', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Reactive Armor (Not Omni)', 'Reactive Sleeves', '252435', '251997', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'biodome', 'biodome', 'Reactive Armor (Not Omni)', 'Reactive Vest', '252433', '251994', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                //Dreadloch Camps
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Doctor&#39;s Left Hand of Grace', '264789', '267618', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Doctor&#39;s Right Hand of Hope', '264786', '267619', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Dreadloch Balanced Freedom Arms (Crat)', '264788', '267255', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Dreadloch Combat Remodulator ', '264837', '267261', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Dreadloch Enhanced Bear', '264827', '267254', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Dreadloch Enhanced Panther', '264815', '267253', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Dreadloch Modified Shark', '264839', '267158', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Dreadloch Obliterator', '19800', '27127', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Dreadloch Rapier', '31807', '267128', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Dreadloch Remodulator', '264837', '267256', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Dreadloch Shen Sticks', '229956', '267258', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Dreadloch Thrasher', '210185', '267125', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadweapons', 'Dreadloch Weapons', 'Dreadloch Tigress (MP)', '264841', '267257', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadarmor', 'Dreadloch Armor', 'Hellfyre Magma Suit', '41162', '267317', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadarmor', 'Dreadloch Armor', 'Smuggled Combat Merit Board Base', '216287', '267106', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadarmor', 'Dreadloch Armor', 'Smuggled Nanite Merit Board Base', '216287', '267108', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadutil', 'Dreadloch Utilities', 'Dreadloch Aiming Apparatus', '130840', '267164', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadutil', 'Dreadloch Utilities', 'Dreadloch Damage Amplifier (Soldier or MA)', '99264', '267260', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadutil', 'Dreadloch Utilities', 'Dreadloch Endurance Booster', '99266', '267166', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadutil', 'Dreadloch Utilities', 'Dreadloch Endurance Booster - Enforcer Special', '99266', '267168', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadutil', 'Dreadloch Utilities', 'Dreadloch Endurance Booster - Nanomage Edition', '99266', '267167', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadutil', 'Dreadloch Utilities', 'Dreadloch Sniper&#39;s Friend', '99265', '267286', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadutil', 'Dreadloch Utilities', 'Dreadloch Stab Guidance System', '99265', '267285', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadutil', 'Dreadloch Utilities', 'Dreadloch Stabilising Aid', '130840', '267165', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'dreadloch', 'dreadutil', 'Dreadloch Utilities', 'Dreadloch Survival Predictor (Crat or Trader)', '99265', '267259', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                //Escaped Prisoners
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'prisoners', 'prisoners', 'Prisoner Items', 'Reflex Pistol (Adv)', '264785', '274977', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'prisoners', 'prisoners', 'Prisoner Items', 'Sturdy Detention Boots', '31746', '274976', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'prisoners', 'prisoners', 'Prisoner Items', 'Hacked Medi-Blade', '264782', '274974', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'prisoners', 'prisoners', 'Prisoner Items', 'Crude Upgrade Kit', '218768', '274975', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                //Hollow Island
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hilion', 'Lion&#39;s Leather Armor', 'Lion&#39;s Leather Boots', '13261', '216281', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hilion', 'Lion&#39;s Leather Armor', 'Lion&#39;s Leather Gloves', '21871', '216279', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hilion', 'Lion&#39;s Leather Armor', 'Lion&#39;s Leather Pants', '13304', '216280', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hilion', 'Lion&#39;s Leather Armor', 'Lion&#39;s Leather Sleeves', '21849', '216278', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hilion', 'Lion&#39;s Leather Armor', 'Lion&#39;s Leather Vest', '19812', '216277', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hifirstbrood', 'First Brood Champion', 'Liquid Notum Ring', '151921', '163696', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hifirstbrood', 'First Brood Champion', 'Nelly&#39;s Chip', '43133', '216341', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hisecondbrood', 'Second Brood Champion', 'Remains of AESA 10', '144712', '216272', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hisecondbrood', 'Second Brood Champion', 'Soft Ring with Fluff', '84064', '163712', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hithirdbrood', 'Third Brood Champion', 'Notum Miner&#39;s Hard Hat', '205765', '216276', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hithirdbrood', 'Third Brood Champion', 'Glimmering Magnetic Ring', '84046', '163710', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hifourthbrood', 'Fourth Brood Champion', 'Mutated Eremite Leg', '20411', '216271', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hifourthbrood', 'Fourth Brood Champion', 'Luminous Copper Band', '151930', '163706', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hifourthbrood', 'Fourth Brood Champion', 'Crystalized Medusa Queen Hippocampus', '156559', '164598', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hififthbrood', 'Fifth Brood Champion', 'Ring of the Endless Depths', '151920', '163704', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hififthbrood', 'Fifth Brood Champion', 'Thick Leather Wristbands', '161086', '216283', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hisixthbrood', 'Sixth Brood Champion', 'I am the Eel', '151923', '163633', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hisixthbrood', 'Sixth Brood Champion', 'Suzerain&#39;s Spiritual Chief Headwear (MP)', '205776', '216450', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiseventhbrood', 'Seventh Brood Champion', 'I am the Bear', '151923', '163637', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiseventhbrood', 'Seventh Brood Champion', 'Suzerain&#39;s Military Chief Headwear (NT)', '205774', '216449', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiseventhbrood', 'Seventh Brood Champion', 'Gurgling River Sprite', '20407', '216268', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiseventhbrood', 'Seventh Brood Champion', 'Expensive Gift from Earth', '205832', '216286', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hieighthbrood', 'Eighth Brood Champion', 'I am the Owl', '151923', '163635', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hieighthbrood', 'Eighth Brood Champion', 'Chip of the Eight', '43118', '216381', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hieighthbrood', 'Eighth Brood Champion', 'Suzerain&#39;s Political Chief Headwear (Crat)', '205775', '216448', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hininthbrood', 'Ninth Brood Champion', 'Smooth Gold Alloy Ring', '151931', '163699', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hininthbrood', 'Ninth Brood Champion', 'Mutated Eremite Foetus', '20411', '216270', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hitenthbrood', 'Tenth Brood Champion', 'Hammered Gold Ring', '151927', '163701', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hisuzerain', 'Hollow Island Suzerain', 'Brood Champion Gauntlets', '21870', '216372', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hisuzerain', 'Hollow Island Suzerain', 'Clan Ring of Salvation', '84067', '163641', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hibroodmother', 'Brood Mother', 'Professional Marksman&#39;s Kit', '130788', '216282', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'The Argument', '21138', '223469', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Fly Catcher&#39;s Specs', '205757', '216430', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Profiteer&#39;s Helper', '159123', '223466', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Silly Ring', '151927', '163645', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Beefeater&#39;s Merit Board (Clan Enf)', '216287', '216284', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Captain Lotto&#39;s Merit Board (Omni Enf)', '216287', '216285', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Front Fighter Merit Board (Clan Agent/Soldier)', '216287', '216366', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Merit Board of the Angry Men (Clan NT or Doc)', '216287', '216303', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Merit Board of the Blue Wolf (Clan Engi or Trader)', '216287', '216369', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Merit Board of the Long Distance Runner (Clan Fixer or Adv)', '216287', '216363', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Merit Board of the Rainbow Chrysanthemum (Clan MA)', '216287', '216370', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Messenger Merit Board (Omni Fixer or Adv)', '216287', '216364', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Metal Fungus Merit Board (Omni MA)', '216287', '216371', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Omni-Tek Engineer Corps Merit Board (Omni Engi or Trader)', '216287', '216368', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Omni-Tek Nano Reservoire Merit Board (Omni NT or Doc)', '216287', '216302', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Omni-Tek Shock Trooper Merit Board (Omni Agent/Soldier)', '216287', '216367', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Standard Clan Leader Merit Board (Clan Crat or MP)', '216287', '216323', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hi', 'hiweed', 'Hollow Island Weed', 'Technocrat Merit Board (Omni Crat or MP)', '216287', '216325', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                //Inner Sanctum
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'iskey', 'Jeuru the Defiler', 'Inner Sanctum Knowledge (Top Half of Key)', '154676', '206257', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'iskey', 'Iskop the Idolator', 'Inner Sanctum Knowledge (Bottom Half of Key)', '154676', '206258', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Abyssal Desecrator', '204827', '206058', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Frost-bound Reaper', '204741', '206061', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Frost Shard', '204740', '206055', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Gelid Blade of Inobak', '204740', '206057', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Icebound Heart', '204740', '206056', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Impious Dominator', '13339', '206052', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Maw of the Abyss', '13340', '206049', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Parasitic Hecataleech', '38056', '206047', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Permafrost', '204741', '206062', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Prelude to Chaos', '113994', '206053', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Right Hand of Entropy', '113997', '206054', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Skull of Despair', '204742', '206060', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isweapons', 'IS Weapons', 'Skull of Misery', '204742', '206059', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'First Circle of the Inner Sanctum (BM/MM)', '84065', '206224', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'First Circle of the Inner Sanctum (MC/TS)', '84065', '206225', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'First Circle of the Inner Sanctum (PM/SI)', '84065', '206226', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'Second Circle of the Inner Sanctum (BM/MM)', '84068', '206235', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'Second Circle of the Inner Sanctum (MC/TS)', '84068', '206236', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'Second Circle of the Inner Sanctum (PM/SI)', '84068', '206232', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'Third Circle of the Inner Sanctum (BM/MM)', '151926', '206238', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'Third Circle of the Inner Sanctum (MC/TS)', '151926', '206237', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'Third Circle of the Inner Sanctum (PM/SI)', '151926', '206239', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'Bloodslave Ring', '84066', '206201', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'Ring of Putrescent Flesh', '151927', '206202', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'Ring of Wilting Flame', '84060', '206203', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isrings', 'IS Rings', 'Twilight&#39;s Murder', '151921', '206204', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isabaddon', 'Abaddon', 'Charred Abaddon Chassis', '41163', '206136', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isabaddon', 'Abaddon', 'Abaddon Upgrade: Defense', '43126', '206153', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isabaddon', 'Abaddon', 'Abaddon Upgrade: Essence', '43121', '206156', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isabaddon', 'Abaddon', 'Abaddon Upgrade: Life', '43135', '206151', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isabaddon', 'Abaddon', 'Abaddon Upgrade: Nano', '43126', '206154', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isabaddon', 'Abaddon', 'Abaddon Upgrade: Offense', '43135', '206152', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isabaddon', 'Abaddon', 'Abaddon Upgrade: Skill', '43121', '206155', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'iskarmic', 'Tattered Book: Karmic Fist', 'Tattered book: Karmic Fist (section 1)', '136332', '206180', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'iskarmic', 'Tattered Book: Karmic Fist', 'Tattered book: Karmic Fist (section 2)', '136332', '206181', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'iskarmic', 'Tattered Book: Karmic Fist', 'Tattered book: Karmic Fist (section 3)', '136332', '206182', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'iskarmic', 'Tattered Book: Karmic Fist', 'Tattered book: Karmic Fist (section 4)', '136332', '206183', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'iskarmic', 'Tattered Book: Karmic Fist', 'Tattered book: Karmic Fist (section 5)', '136332', '206184', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'iskarmic', 'Tattered Book: Karmic Fist', 'Tattered book: Karmic Fist (section 6)', '136332', '206185', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Carapace of the Infernal Tyrant', 'Carapace of the Infernal Tyrant (Body)', '21977', '205957', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Carapace of the Infernal Tyrant', 'Carapace of the Infernal Tyrant (Boots)', '21978', '205960', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Carapace of the Infernal Tyrant', 'Carapace of the Infernal Tyrant (Gloves', '21979', '205959', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Carapace of the Infernal Tyrant', 'Carapace of the Infernal Tyrant (Helmet)', '22270', '205961', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Carapace of the Infernal Tyrant', 'Carapace of the Infernal Tyrant (Pants)', '21980', '205958', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Carapace of the Infernal Tyrant', 'Carapace of the Infernal Tyrant (Sleeves)', '21976', '205956', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Carapace of the Infernal Tyrant', 'Bloodseal of the Infernal Tyrant', '25801', '206196', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Carapace of the Infernal Tyrant', 'Ichor of the Immortal One', '11753', '206067', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Gloomfall Armor', 'Gloomfall Armor (Body)', '13249', '205951', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Gloomfall Armor', 'Gloomfall Armor (Boots)', '13265', '205955', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Gloomfall Armor', 'Gloomfall Armor (Gloves)', '13279', '205953', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Gloomfall Armor', 'Gloomfall Armor (Helmet)', '22269', '205950', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Gloomfall Armor', 'Gloomfall Armor (Pants)', '13294', '205952', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'Gloomfall Armor', 'Gloomfall Armor (Sleeves)', '13227', '205954', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'IS Armor', 'Blighted Soulmark', '25794', '206011', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'IS Armor', 'Bloodmark', '25796', '206006', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'IS Armor', 'Bloodshed Armband', '84050', '206248', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'IS Armor', 'Corrupted Flesh', '144704', '206015', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'IS Armor', 'Defiled Bloodmark', '25796', '206007', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'IS Armor', 'Desecrated Bloodmark', '25796', '206008', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'IS Armor', 'Jeuru&#39;s Oscillating Ligature', '84053', '206064', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'IS Armor', 'Might of the Revenant', '144709', '206013', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'IS Armor', 'Obsidian Jar', '119185', '206457', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'IS Armor', 'Small Obsidian Jar', '119173', '206477', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'IS Armor', 'Soulmark', '25794', '206009', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'isarmor', 'IS Armor', 'Tainted Soulmark', '25794', '206010', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'ismisc', 'IS Miscellaneous', 'Constrained Gridspace Waveform', '25798', '206068', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'ismisc', 'IS Miscellaneous', 'Fist of the Dominator', '12666', '206247', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'ismisc', 'IS Miscellaneous', 'Mnemonic Fragment', '119141', '206018', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'ismisc', 'IS Miscellaneous', 'Nano Crystal (Saemus&#39; Crystalizer)', '42450', '206753', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'ismisc', 'IS Miscellaneous', 'Rod of Dismissal', '100306', '206017', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'ismisc', 'IS Miscellaneous', 'Teachings of the Immortal One', '136329', '206242', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'ismisc', 'IS Miscellaneous', 'The Primeval Skull', '195259', '206246', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'is', 'ismisc', 'IS Miscellaneous', 'Unhallowed Chalice', '37929', '206016', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                // Jack &#39;Legchopper&#39; Menendez
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'Ambidextrous Plasteel Gloves', '21979', '151686', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'Bloodstained Dog-Tag (Soldier)', '149944', '165466', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'Disposal Unit Electrical Toolset', '12710', '164552', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'DNA Sample (Doctor)', '11750', '165470', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'Glowing Amygdaloid Nucleus (MP and NT)', '144711', '165471', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'Important Looking Briefcase (Crat)', '99668', '165472', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'Jack&#39;s Head (Adv, Fixer and Trader)', '144703', '165469', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'Jack&#39;s Reinforced Gloves (Enf)', '21979', '165468', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'Proof of what happened to Joslyn (Agent)', '144709', '165474', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'Remains of a long lost pet (Engi)', '144707', '165473', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'Severed leg of the Grand Master (MA)', '144709', '165467', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'Silken Legchopper Gloves', '37105', '152544', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'jack', 'legchopper', 'Jack &#39;Legchopper&#39; Menendez', 'Supporting Carbonan Holster', '13227', '151715', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
				// Pyramid of Home				
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'poh', 'poh', 'Common Loot', 'Crying Spirit Capsule', '231189', '302918', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'poh', 'poh', 'Common Loot', 'Laughing Spirit Capsule', '231187', '302916', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'poh', 'poh', 'Common Loot', 'Screaming Spirit Capsule', '231185', '302917', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'poh', 'poh', 'Common Loot', 'Inert Sigil of Alighieri', '235313', '302926', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'poh', 'poh', 'Boss Loot', 'Inert Sigil of Machiavelli', '235317', '302928', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'poh', 'poh', 'Boss Loot', 'Ancient Protective Drone', '244361', '302923', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'poh', 'poh', 'Boss Loot', 'Dense Nanite Aegis', '160725', '302930', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'poh', 'poh', 'Boss Loot', 'Ancient Aggressive Webbing', '244359', '302924', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'poh', 'poh', 'Boss Loot', 'Sluggish Notum Lens', '160723', '302931', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }							
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'poh', 'poh', 'Boss Loot', 'Ancient Resorative Fungus', '244360', '302925', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }	
                        $query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'poh', 'poh', 'Common Loot', 'Portable Notum Infusion Device', '302888', '302932', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }	
				// HL Subway		
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Boots of Gridspace Distortion', '13267', '305995', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Condemned Bulwark', '22395', '306003', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Counterfeit fr00b T-shirt', '85945', '305963', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Eumenidess Omni-Pol Forest Body Armor', '13254', '305983', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Fetid Vagabond Cloak', '22898', '305962', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Fortified Construction Sleeves', '13221', '305964', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Jester\'s Gift', '287079', '305029', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Mark of the Bloodless', '96116', '306026', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Notum-Infused Wool Balaclava Mask', '160562', '305979', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Reinforced Bau Cyber Armor Boots', '22878', '305973', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Reinforced Bau Cyber Armor Gloves', '22939', '305971', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Reinforced Bau Cyber Armor Helmet', '31738', '305972', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Reinforced Bau Cyber Armor Pants', '22928', '305970', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Reinforced Bau Cyber Armor Sleeves', '22889', '305969', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Reinforced Bau Cyber Body Armor', '22981', '305967', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Reinforced Bau Cyber Female Body Armor', '22942', '305968', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Superior Ring of the Nucleus Basalis', '301035', '305028', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Supreme Office Worker Suit', '156355', '305982', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Survivalist Leather Armor Legwear', '245961', '305987', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Symbiotic Nanite Gloves', '21871', '305990', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubarmors', 'Common Loot', 'Vergils Black Trenchcoat', '18849', '305997', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubmiscs', 'Common Loot', 'Amalgamated Research Attunement Device', '161873', '305986', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubmiscs', 'Common Loot', 'Aspect of Paralyzing Fear', '20406', '305984', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubmiscs', 'Common Loot', 'Belt of Great Justice', '119145', '306001', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubmiscs', 'Common Loot', 'Combat Assist Wen-Wen', '268035', '305975', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubmiscs', 'Common Loot', 'Eye of The Psion', '149936', '305989', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubmiscs', 'Common Loot', 'Patchwork Defensive Drone', '159123', '306002', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubmiscs', 'Common Loot', 'Purification Stim', '11715', '305978', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubmiscs', 'Common Loot', 'Technical Guidance Personal Terminal', '218777', '305981', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubmiscs', 'Common Loot', 'Unstable Damage Augmentation Device', '218769', '305974', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubweapons', 'Common Loot', 'BBI Faithful 1000', '113994', '305980', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubweapons', 'Common Loot', 'Distraction Rifle', '21146', '305966', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubweapons', 'Common Loot', 'Vicious Support Beam of Malice', '33143', '305965', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubweapons', 'Common Loot', 'Illegally Augmented Ofab Mongoose', '264818', '305985', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubweapons', 'Common Loot', 'Illegally Modified Dreadloch Modified Shark', '264839', '306004', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubweapons', 'Common Loot', 'Illegally Modified Dreadloch Obliterator', '19800', '306005', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubweapons', 'Common Loot', 'Illegally Modified Dreadloch Panther', '264815', '305991', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubweapons', 'Common Loot', 'Illegally Modified Dreadloch Remodulator', '264837', '305999', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubweapons', 'Common Loot', 'Illegally Modified Dreadloch Thrasher', '210185', '305998', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubweapons', 'Common Loot', 'Illegally-Modified Dreadloch Tigress', '264843', '305988', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hlsubway', 'hlsubweapons', 'Common Loot', 'Lost Blade of Elder Tsunayoshi', '13326', '305993', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}						
				//HL Temple of 3 Winds
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Acolyte Purified Robe' ,'159573' ,'305466', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}					
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Desecrated Flesh' ,'144704' ,'305476', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}			
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Boss Loot', 'Exarch Purified Robe' ,'159569' ,'305469', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Boss Loot', 'Fist of Heavens' ,'13281' ,'305480', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Boss Loot', 'Guardian Heavy Tank Armor' ,'22395' ,'305033', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Boss Loot', 'Gartua&#39;s Second Coat' ,'300622' ,'305470', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Boss Loot', 'Mountain Razing Gauntlets' ,'13286' ,'305487', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Purified Robe of the Faithful' ,'305667' ,'305465', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Purified Acolyte Hood' ,'305666' ,'305472', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Purified Exarch Hood' ,'305670' ,'305475', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Purified Hood of the Faithful' ,'305667' ,'305471', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Purified Reverend Hood' ,'305669' ,'305473', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Purified Windcaller Hood' ,'305668' ,'305474', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Reverend Purified Robe' ,'159571' ,'305467', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Boss Loot', 'Strength of the Immortal' ,'290523' ,'305478', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Twilight Entreatment Armor (Body)' ,'13249' ,'305483', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Twilight Entreatment Armor (Boots)' ,'13265' ,'305481', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Twilight Entreatment Armor (Gloves)' ,'13279' ,'305484', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Twilight Entreatment Armor (Helmet)' ,'22269' ,'305485', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Twilight Entreatment Armor (Pants)' ,'13294' ,'305486', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwarmors', 'Common Loot', 'Twilight Entreatment Armor (Sleeves)' ,'13227' ,'305482', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwrings', 'Boss Loot', 'Bloodthrall Ring' ,'84066' ,'305495', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwrings', 'Common Loot', 'Notum Ring of the Three (green)' ,'286792' ,'305488', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwrings', 'Common Loot', 'Notum Ring of the Three (red)' ,'286793' ,'305489', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwrings', 'Common Loot', 'Notum Ring of the Three (yellow)' ,'286794' ,'305490', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwrings', 'Boss Loot', 'Ring of Blighted Flesh' ,'151927' ,'305491', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwrings', 'Boss Loot', 'Ring of the Coiled Serpent' ,'84065' ,'305496', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwrings', 'Boss Loot', 'Ring of Purifying Flame' ,'84060' ,'305493', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwsymbs', 'Boss Loot', 'Blessing of the Gripper' ,'12671' ,'305511', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwsymbs', 'Boss Loot', 'Ethereal Embrace' ,'231002' ,'305506', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwsymbs', 'Boss Loot', 'Grasp of the Immortal' ,'12666' ,'305505', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwsymbs', 'Boss Loot', 'Inner Peace' ,'12691' ,'305509', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwsymbs', 'Boss Loot', 'Keeper&#39;s Vigor' ,'12699' ,'305508', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwsymbs', 'Boss Loot', 'Nematet&#39;s Third Eye' ,'12706' ,'305510', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwsymbs', 'Boss Loot', 'Notum Graft' ,'12665' ,'305513', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwsymbs', 'Boss Loot', 'Vision of the Heretic' ,'230980' ,'305507', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwsymbs', 'Boss Loot', 'Wit of the Immortal' ,'195259' ,'305504', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwweaps', 'Common Loot', 'Bone Staff of The Immortal Summoner' ,'296406' ,'305524', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwweaps', 'Boss Loot', 'Ceremonial Blade' ,'233353' ,'305520', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwweaps', 'Boss Loot', 'Corrupted Edge' ,'218704' ,'305521', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwweaps', 'Boss Loot', 'Envoy to Chaos' ,'218702' ,'305522', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwweaps', 'Boss Loot', 'Obsidian Desecrator' ,'305049' ,'204755', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwweaps', 'Boss Loot', 'Sacred Chalice' ,'245993' ,'305523', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwweaps', 'Boss Loot', 'Summoner&#39;s Staff of Dismissal' ,'154366' ,'305526', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwweaps', 'Boss Loot', 'Uklesh&#39;s Talon' ,'210190' ,'305525', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Boss Loot', 'Knowledge of the Immortal One' ,'37930' ,'305527', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Boss Loot', 'Sacred Text of the Immortal One' ,'136329' ,'305514', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Common Loot', 'Tattered book: Stampede of the Boar (section 1)' ,'269476' ,'305543', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Common Loot', 'Tattered book: Stampede of the Boar (section 2)' ,'269476' ,'305544', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Common Loot', 'Tattered book: Stampede of the Boar (section 3)' ,'269476' ,'305545', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Common Loot', 'Tattered book: Stampede of the Boar (section 4)' ,'269476' ,'305546', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Common Loot', 'Tattered book: Stampede of the Boar (section 5)' ,'269476' ,'305547', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Common Loot', 'Tattered book: Stampede of the Boar (section 6)' ,'269476' ,'305548', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Common Loot', 'Tattered book: Sting of the Viper (section 1)' ,'136332' ,'305531', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Common Loot', 'Tattered book: Sting of the Viper (section 2)' ,'136332' ,'305532', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Common Loot', 'Tattered book: Sting of the Viper (section 3)' ,'136332' ,'305533', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Common Loot', 'Tattered book: Sting of the Viper (section 4)' ,'136332' ,'305534', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Common Loot', 'Tattered book: Sting of the Viper (section 5)' ,'136332' ,'305535', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwbooks', 'Common Loot', 'Tattered book: Sting of the Viper (section 6)' ,'136332' ,'305536', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwmiscs', 'Boss Loot', 'Aegis Circuit Board' ,'149938' ,'305519', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwmiscs', 'Common Loot', 'Corrupted Bloodmark' ,'25796' ,'305499', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwmiscs', 'Common Loot', 'Corrupted Soulmark' ,'25794' ,'305498', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwmiscs', 'Boss Loot', 'Lucid Nightmares' ,'276919' ,'305517', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwmiscs', 'Boss Loot', 'Memory of Future Events' ,'119139' ,'305516', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwmiscs', 'Boss Loot', 'Mnemonic Shard' ,'269806' ,'305518', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwmiscs', 'Boss Loot', 'Ornate Funeral Urn' ,'154195' ,'305556', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwmiscs', 'Common Loot', 'Rod of Dismissal' ,'100306' ,'206017', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'hltotw', 'hltotwmiscs', 'Common Loot', 'Sanguine Vambrace' ,'84050' ,'305500', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}	
				//Collector Chests
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'collector', 'collechest', 'Common Loot', 'Bronze Chest of the Collector (common)' ,'286445' ,'286561', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'collector', 'collechest', 'Worthy Loot', 'Silver Chest of the Collector (worthy)' ,'286443' ,'286438', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'collector', 'collechest', 'Rare Loot', 'Golden Chest of the Collector (rare)' ,'286444' ,'286441', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}	
				//Tarasque Castle
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taracommon', 'Common Loot', 'Wood Torch (several QL)', '85172' ,'125427', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taracommon', 'Common Loot', 'Corroded Blade' ,'114003' ,'158322', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taracommon', 'Common Loot', 'Well-Worn Antiquated Sword' ,'113987' ,'158296', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taracommon', 'Common Loot', 'Gutting Hook (several QL)' ,'158269' ,'157902', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taracommon', 'Common Loot', 'Torturing Tool (several QL)' ,'85160' ,'157899', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taracommon', 'Common Loot', 'Corroded Ring' ,'289772' ,'200818', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taracommon', 'Common Loot', 'Pattern of Inevitable Death' ,'231182' ,'255552', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taracommon', 'Common Loot', 'Pattern of Imminent Death' ,'231182' ,'255553', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taracommon', 'Common Loot', 'Nanobot Infusion Device' ,'11707' ,'275382', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Globe of Clarity' ,'290364' ,'158797', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Globe of Sufferance' ,'25800' ,'158796', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Signet Ring of the Green Knight' ,'84046' ,'158801', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Living Dragon Claws' ,'236566' ,'301127', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Chunk of Living Dragon Flesh' ,'144709' ,'158892', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Lump of Living Dragon Marrow' ,'144701' ,'158895', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Patch of Living Dragon Skin' ,'156555' ,'158893', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Piece of Living Dragon Wing' ,'144707' ,'158894', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Shard of Living Dragon Skull' ,'136596' ,'158896', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Dragon Tooth Poker (several QL)' ,'131261' ,'158843', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Robust Backpack' ,'99666' ,'158790', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Edge of the Tarasque (several QL)' ,'158280' ,'157855', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Heavy-Headed Staff (several QL)' ,'136738' ,'158886', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Gaily Painted Hood' ,'82924' ,'158795', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Cloak of the Wandering Knight' ,'22891' ,'158788', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Aura Magnifier' ,'12663' ,'158798', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Heavily Padded Overcoat' ,'18849' ,'158800', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Smart Hood of the Wanderer' ,'22999' ,'158789', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Heart of Tarasque' ,'12701' ,'158787', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Sinew of Tarasque' ,'12676' ,'158764', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'tarasque', 'taraboss', 'Boss Loot', 'Smelly Butcher Gloves' ,'159134' ,'158844', '0')";
						if(!$this -> bot -> db -> query($query))
						{
								echo "\nError running query: ".$query."\n"; sleep(5);
						}
				//Gauntlet loots
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntarmor', 'Common Loot', 'SSC &#34;Bastion&#34; Left Shoulder', '293723', '291932', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntarmor', 'Common Loot', 'SSC &#34;Bastion&#34; Right Shoulder Armor', '291371', '291931', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntarmor', 'Common Loot', 'SSC &#34;Bastion&#34; Back Armor', '291379', '292194', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Inert Bacteriophage', '292793', '292507', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Bacteriophage M73', '292775', '292509', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Red Data Crystal', '292764', '292514', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Bacteriophage Phi X 3957', '292776', '292508', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Blue Data Crystal', '292780', '292515', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Bacteriophage F9', '292774', '292510', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Green Data Crystal', '292792', '292516', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Pattern Conversion Device', '292762', '292517', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Nickel-Cobalt Ferrous Alloy', '292760', '292532', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Dacite Fiber', '292788', '292533', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Sealed Packet of Bilayer Graphene Sheets', '292779', '292529', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Mu-Negative Novictum Enriched Metamaterial', '292759', '292530', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Synchotronic Recombinator (Empty)', '292772', '292538', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Compressed Silane', '292784', '292524', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'Potassium Nitrate', '292763', '292525', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntitem', 'Common Loot', 'VLS Synthesis Catalyst', '292777', '292526', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntrare', 'Common Loot', 'A Single Strand of Glowing Dark Energy', '293810','293809', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntrare', 'Common Loot', 'Fatou Upgrade Plate', '292790', '292535', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntrare', 'Common Loot', 'Mandelbrot Upgrade Plate', '292758', '292536', '0')";
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
						$query = "INSERT INTO #___RaidLoot (table_version, raid, area, boss, name, img, ref, multiloot) VALUES('$version', 'gauntlet', 'gauntrare', 'Common Loot', 'Collatz Upgrade Plate', '292782', '292537', '0')";						
                        if(!$this -> bot -> db -> query($query))
                        {
                                echo "\nError running query: ".$query."\n"; sleep(5);
                        }						
                } //end test_db

        }

}
?>