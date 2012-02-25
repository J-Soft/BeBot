<?php
/*
* FunFilters.php - Fun Text Filters based on http://kitenet.net/~joey/code/filters/
*
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
/*
* Provides fun text filters:
	- rot13 (rot13 encodes text)
	- chef (Sweedish Chef)
	- pirate (Talk like a Pirate)
	- eleet (l33t Filter)
	- fudd (Elmer Fudd)
* Also provides some useful filters:
	- nofont (removes font tags)
*/
$funfilters = new FunFilters($bot);
/*
The Class itself...
*/
class FunFilters extends BasePassiveModule
{

  /*
  Constructor:
  Hands over a reference to the "Bot" class.
  Defines access control for the commands
  Creates settings for the module
  Defines help for the commands
  */
  function __construct(&$bot)
  {
    parent::__construct($bot, get_class($this));
    $this->register_module("funfilters");
  }

  /*
  Rot13 something
  */
  function rot13($text)
  { // Start function rot13()
    return str_rot13($text);
  } // End function rot13()

  /*
  No Font Filter - Strips font tags
  For BeBot, this should remove all color formatting.
  I don't think font tags are used to change any other properties...
  */
  function nofont($text)
  { // Start function nocolor()
    $text = preg_replace("/<font.*?>/", "", $text);
    $text = preg_replace("/</font>/", "", $text);
    return $text;
  } // End function nocolor()

  /*
  Swedish Chef filter. Bork Bork Bork!
  Based on chef filter by Joey Hess: http://kitenet.net/~joey/code/filters/
      Copyright 1999 by Joey Hess under the terms of the GNU GPL.
      Note that the order of the commands in this function is very important!

  For the most part, this is Joey's code translated into PHP. I even perserved his comments.
  The main exception is handling of the. I was unable to translate Joey's the regexp into a
  form that worked in PHP, so I modified things a little bit. The output is the same, but I'm
  sure there is a much better way of achieving it.
  */
  function chef($text)
  { // Start function chef()
    // Save "The" for later
    // the: 116, 104, 101
    // THE: 84, 72, 69
    $the = "116, 104, 101";
    $The = "84, 72, 69";
    $text = str_replace("THE", $The, $text);
    $text = str_replace("The", $The, $text);
    $text = str_replace("the", $the, $text);
    // Change 'e' at the end of a word to 'e-a' (Excluding the)
    $text = preg_replace("/e\b/", "e-a", $text);
    // Stuff that happens at the end of a word.
    $text = preg_replace("/en\b/", "ee", $text);
    $text = preg_replace("/th\b/", "t", $text);
    // Stuff that happens if not the first letter of a word.
    $text = preg_replace("/\Bf/", "ff", $text);
    // Change 'o' to 'u' and at the same time, change 'u' to 'oo'. But only
    // if it's not the first letter of the word.
    // First change o characters that are not the first letter to 111
    $text = preg_replace("/\Bo/i", "111", $text);
    // Now change u to oo
    $text = preg_replace("/\Bu/i", "oo", $text);
    // And change 111 to u.
    $text = str_replace("111", "u", $text);
    // If a word starts with o|O, change to oo|Oo
    $text = preg_replace("/\bo/", "oo", $text);
    $text = preg_replace("/\bO/", "Oo", $text);
    // Fix the word "bork", which has been mangled to burk.
    $text = preg_replace("/\b[Bb]urk/", "bork", $text);
    // Stuff to do to letters that are the first letter of any word.
    $text = preg_replace("/\be/", "i", $text);
    $text = preg_replace("/\bE/", "I", $text);
    // Stuff that always happens.
    $text = str_replace("tiun", "shun", $text);
    $text = str_replace($the, "zee", $text);
    $text = str_replace($The, "Zee", $text);
    $text = str_replace("v", "f", $text);
    $text = str_replace("V", "F", $text);
    $text = str_replace("w", "v", $text);
    $text = str_replace("W", "V", $text);
    // Stuff to do to letters that are not the last letter of a word.
    // change a to e and A to E
    $text = preg_replace("/a(?!\b)/", "e", $text);
    $text = preg_replace("/A(?!\b)/", "E", $text);
    $text = str_replace("en", "un", $text); // this actually has the effect of changing "an" to "un".
    $text = str_replace("En", "Un", $text); // this actually has the effect of changing "An" to "Un".
    $text = str_replace("eoo", "oo", $text); // this actually has the effect of changing "au" to "oo".
    $text = str_replace("Eoo", "Oo", $text); // this actually has the effect of changing "Au" to "Oo".
    $text = str_replace("uv", "oo", $text); // Change "ow" to "oo".
    // Change 'i' to 'ee', but not at the beginning of a word,
    // and only affect the first 'i' in each word.
    $text = preg_replace("/\B[^a-hj-zA-HJ-Z]*i/", "ee", $text);
    // Special punctuation of the end of sentances but only at end of lines.
    $text = preg_replace("/([.!?])/", "$1\nBork Bork Bork!", $text);
    return $text;
  } // End function chef()

  /*
  Talk like a Pirate!
  */
  function pirate($text)
  { // Start function pirate.
    $text = trim($text);
    $trans_table = array('\bmy\b' => 'me',
                         '\bboss\b' => 'admiral',
                         '\bmanager\b' => 'admiral',
                         '\b[Cc]aptain\b' => "Cap'n",
                         '\bmyself\b' => 'meself',
                         '\byour\b' => 'yer',
                         '\byou\b' => 'ye',
                         '\bfriend\b' => 'matey',
                         '\bfriends\b' => 'maties',
                         '\bco[-]?worker\b' => 'shipmate',
                         '\bco[-]?workers\b' => 'shipmates',
                         '\bearlier\b' => 'afore',
                         '\bold\b' => 'auld',
                         '\bthe\b' => "th'",
                         '\bof\b' => "o'",
                         '\bdon\'t\b' => "dern't",
                         '\bdo not\b' => "dern't",
                         '\bnever\b' => "ne'er",
                         '\bever\b' => "e'er",
                         '\bover\b' => "o'er",
                         '\bYes\b' => 'Aye',
                         '\bNo\b' => 'Nay',
                         '\bdon\'t know\b' => "dinna",
                         '\bhadn\'t\b' => "ha'nae",
                         '\bdidn\'t\b' => "di'nae",
                         '\bwasn\'t\b' => "weren't",
                         '\bhaven\'t\b' => "ha'nae",
                         '\bfor\b' => 'fer',
                         '\bbetween\b' => 'betwixt',
                         '\baround\b' => "aroun'",
                         '\bto\b' => "t'",
                         '\bit\'s\b' => "'tis",
                         '\bwoman\b' => 'wench',
                         '\blady\b' => 'wench',
                         '\bwife\b' => 'lady',
                         '\bgirl\b' => 'lass',
                         '\bgirls\b' => 'lassies',
                         '\bguy\b' => 'lubber',
                         '\bman\b' => 'lubber',
                         '\bfellow\b' => 'lubber',
                         '\bdude\b' => 'lubber',
                         '\bboy\b' => 'lad',
                         '\bboys\b' => 'laddies',
                         '\bchildren\b' => 'minnows',
                         '\bkids\b' => 'minnows',
                         '\bhim\b' => 'that scurvey dog',
                         '\bher\b' => 'that comely wench',
                         '\bhim\.\b' => 'that drunken sailor',
                         '\bHe\b' => 'The ornery cuss',
                         '\bShe\b' => 'The winsome lass',
                         '\bhe\'s\b' => 'he be',
                         '\bshe\'s\b' => 'she be',
                         '\bwas\b' => "were bein'",
                         '\bHey\b' => 'Avast',
                         '\bher\.\b' => 'that lovely lass',
                         '\bfood\b' => 'chow',
                         '\broad\b' => 'sea',
                         '\broads\b' => 'seas',
                         '\bstreet\b' => 'river',
                         '\bstreets\b' => 'rivers',
                         '\bhighway\b' => 'ocean',
                         '\bhighways\b' => 'oceans',
                         '\bcar\b' => 'boat',
                         '\bcars\b' => 'boats',
                         '\btruck\b' => 'schooner',
                         '\btrucks\b' => 'schooners',
                         '\bSUV\b' => 'ship',
                         '\bmachine\b' => 'contraption',
                         '\bairplane\b' => 'flying machine',
                         '\bjet\b' => 'flying machine',
                         '\yalm\b' => 'flying machine',
                         '\yalmaha\b' => 'flying machine',
                         '\Yalmaha\b' => 'flying machine',
                         '\Yalm\b' => 'flying machine',
                         '\bdriving\b' => 'sailing',
                         '\bdrive\b' => 'sail',
                         '\bloot\b' => 'booty',
                         '\blooting\b' => 'plunderin');
    foreach ($trans_table as $search => $replace)
    {
      // PHP4 way of doing case insensitive replacments.
      $text = preg_replace("/" . $search . "/i", $replace, $text);
      // PHP5 gives str_ireplace, which should be better...
      // $text = str_ireplace($search, $new, $text); // str_ireplace is php5+
    }
    // Change ing to in
    $text = preg_replace("/ing\b/i", "in'", $text);
    $text = preg_replace("/ings\b/i", "in's", $text);
    if (preg_match("/(\.( |\t|$))/", $text, $info)) {
      $win = $this->winner(2);
      $stub = $info[1];
    }
    else if (preg_match("/([!\?]( \t|$))/", $text, $info)) {
      $win = $this->winner(3);
      $stub = $info[1];
    }
    if ($win) {
      $shouts = array(", avast$stub",
                      "$stub Ahoy!",
                      ", and a bottle of rum!",
                      ", by Blackbeard's sword$stub",
                      ", by Davy Jones' locker$stub",
                      "$stub Walk the plank!",
                      "$stub Aarrr!",
                      "$stub Yaaarrrrr!",
                      ", pass the grog!",
                      ", and dinna spare the whip!",
                      ", with a chest full of booty$stub",
                      ", and a bucket o' chum$stub",
                      ", we'll keel-haul ye!",
                      "$stub Shiver me timbers!",
                      "$stub And hoist the mainsail!",
                      "$stub And swab the deck!",
                      ", ye scurvey dog$stub",
                      "$stub Fire the cannons!",
                      ", to be sure$stub",
                      ", I'll warrant ye$stub");
      $text = rtrim($text, $stub);
      $text = $text . $shouts[array_rand($shouts, 1)];
    }
    return $text;
  } // End function pirate()

  function eleet($text)
  { // Start function eleet()
    $text = strtolower($text);
    $norm = array("porn",
                  "elite",
                  "eleet",
                  "your",
                  "you're",
                  "are",
                  "fool",
                  "you",
                  "newbie",
                  "noobie",
                  "hoot",
                  "loot",
                  "hacker",
                  "fear",
                  "skill",
                  "skills",
                  "dude",
                  "sucks",
                  "suck",
                  "a",
                  "e",
                  "i",
                  "o",
                  "s",
                  "t",
                  "for");
    $trans = array("pr0n",
                   "l33t",
                   "l33t",
                   "l33t",
                   "ur",
                   "r",
                   "f00",
                   "j00",
                   "n00b",
                   "n00b",
                   "w00t",
                   "13wt",
                   "h4x0r",
                   "ph33r",
                   "sk1llz",
                   "sk1llz",
                   "d00d",
                   "sux0r",
                   "sux0r",
                   "4",
                   "3",
                   "1",
                   "0",
                   "5",
                   "7",
                   "4");
    // Translate most of it.
    $text = str_replace($norm, $trans, $text);
    // replace f with ph, only at start of word.
    $text = preg_replace("/\f/", "ph", $text);
    // Fix some excessive wierdness.
    $text = str_replace("ph00", "f00", $text);
    $text = str_replace("1337", "l33t", $text);
    // Add some other wierdness.
    $text = str_replace("h07", "h4Wt", $text);
    return $text;
  } // End function eleet()

  function fudd($text)
  { // Start function fudd()
    $text = preg_replace("/[rl]/", "w", $text);
    $text = preg_replace("/qu/", "qw", $text);
    $text = preg_replace("/th\b/", "f", $text);
    $text = preg_replace("/th\B/", "d", $text);
    $text = preg_replace("/n\./", "n, uh-hah-hah-hah.", $text);
    $text = preg_replace("/[RL]/", "W", $text);
    $text = preg_replace("/Qu/", "Qw", $text);
    $text = preg_replace("/QU/", "QW", $text);
    $text = preg_replace("/TH\b/", "F", $text);
    $text = preg_replace("/TH\B/", "D", $text);
    $text = preg_replace("/Th/", "D", $text);
    $text = preg_replace("/N\./", "N, uh-hah-hah-hah.", $text);
    return $text;
  } // End function fudd()

  /*
  Censors text.
  function censor($text)
  */
  /*
  // Build your own word list.
  { // Start function censor()
      // rot13 encoded "bad" words.
      $strings = array(
          "ahqr", "enaql", "gjng", "pbpx", "t-fcbg",
          "anxrq", "encr", "grng", "pbzrvat", "t\f+fcbg",
          "avccyr", "erne", "guebng", "pbzvat", "tebva",
          "bcravat", "fangpu", "gvg", "pebgpu", "ubbgre",
          "beny", "fchax", "gvggl", "penc", "ubeal",
          "betl", "fcrez", "gvggvr", "penpx", "ubyr",
          "betnfz", "fcuvapgre", "hgrehf", "pernz", "uhzc",
          "certanag", "fghq", "ihyin", "phag", "unaqwbo",
          "cevpx", "fgnss", "intvan", "phz", "urnq",
          "chff", "fhpx", "ivetva", "phzzvat", "wvfz",
          "chffl", "fjnyybj", "ivoengbe", "pnzr", "xabo",
          "chffvrf", "fperj", "jbzo", "preivk", "xvff",
          "chovp", "frk", "jrg", "pureel", "ybir",
          "chqraqhz", "frkhny", "juber", "pyvg", "ybire",
          "chzc", "frrq", "kkk", "pyvgbevf", "ybirq",
          "cnagvrf", "frzra", "nany", "pyvggl", "ybnq",
          "crargengr", "funsg", "nerbyn", "pyvznk", "ynovn",
          "crargengrq", "funt", "nff", "qevyyrq", "ynvq",
          "cravf", "funttvat", "nffubyr", "qrsybjre", "yrfovna",
          "crgre", "fuvg", "obbo", "qvpx", "yvcf",
          "crpxre", "fvrt", "furvy", "oernfg", "qvyqb", "znfgheong",
          "cunyyhf", "fyhg", "ohgg", "rebgvp", "znfgheongr",
          "cvff", "fyhggvfu", "ohggbpx", "rerpgvba", "znfgheongvat",
          "ebfrohq", "fyvg", "onyy", "rkcbfrq", "znzznel",
          "ebq", "gbathr", "ovgpu", "shpx", "znzznevrf",
          "ehg", "gbby", "oybj", "snpvny",
      );
      $text = strtolower($text);
      $text = str_rot13($text);
      $text = str_replace($strings, "PRAFBERQ", $text);
      $text = str_rot13($text);
      $text = ucfirst($text);
      return $text;
  } // End function censor()
  */
  /*
  Determining a winner (or in this case, if we will be adding more text to the message:
  The higher the $chance value is, the better the odds of "winning" are.
  The highlow value determines if the high range of numbers of the low range of numbers is the winning range.
  Split determines where the split between the high and low ranges are. In this case, split favors the player, not the house.

  To make the example easier, assume that mt_getrandmax() returns 100.
  This means the range of random numbers is between 0-100. (In reality, the maximum range should be the largest 32 bit number suppored by the system)
  So the following code should work like this:
  If $chance is 3 and it was determined that low was the winning range:
      split would be 66, so every number below 66 would be considered low and every number above 66 would be high.
  if $chance is and it was determend that high was the winning range:
      spilt would be 33 so that a high number would be 34-100.
  */
  function winner($chance)
  { // Start function winner()
    $win = FALSE;
    $rand_max = mt_getrandmax();
    $rand_int = mt_rand();
    $highlow = mt_rand(0, 1); // Pick high or low for the winning range. 0 = low, 1 = high.
    if ($highlow) {
      $split = round(0 + $rand_max / $chance);
      if ($rand_int >= $split) {
        $win = TRUE;
      }
    }
    else
    {
      $split = round($rand_max - $rand_max / $chance);
      if ($rand_int <= $split) {
        $win = TRUE;
      }
    }
    return $win;
  } // End function winner()
}

?>