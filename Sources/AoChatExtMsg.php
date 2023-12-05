<?php
/* New "extended" messages, parser and abstraction.
* These were introduced in 16.1.  The messages use postscript
* base85 encoding (not ipv6 / rfc 1924 base85).  They also use
* some custom encoding and references to further confuse things.
*
* Messages start with the magic marker ~& and end with ~
* Messages begin with two base85 encoded numbers that define
* the category and instance of the message.  After that there
* are an category/instance defined amount of variables which
* are prefixed by the variable type.  A base85 encoded number
* takes 5 bytes.  Variable types:
*
* s: string, first byte is the length of the string
* i: signed integer (b85)
* u: unsigned integer (b85)
* f: float (b85)
* R: reference, b85 category and instance
* F: recursive encoding
* ~: end of message
*
* Message categories:
*  501 : More org messages
*        0xad0ae9b : Organization leave because of alignment change
*                    s(Char)
*  506 : NW messages
*        0x0c299d4 : Tower attack
*                    R(Faction), s(Org), s(Char),
*                    R(Faction), s(Org),
*                    s(Zone), i(Zone-X), i(Zone-Y)
*        0x8cac524 : Area abandoned
*                    R(Faction), s(Org), s(Zone)
*  508 : Org messages
*        0x04e87e7 : Character joined the organization
*                    s(Char)
*        0x2360067 : Character was kicked
*                    s(Kicker), s(Kicked)
*        0x2bd9377 : Character has left
*                    s(Char)
*        0x8487156 : Change of governing form
*                    s(Char), s(Form)
*        0x88cc2e7 : Organization disbanded
*                    s(Char)
*        0xc477095 : Vote begins
*                    s(Vote text), u(Minutes), s(Choices)
* 1001 : AI messages
*        0x01 : Cloak
*               s(Char), s(Cloak status)
*        0x02 : Radar alert
*        0x03 : Alien attack
*               s(Zone)
*        0x04 : Org HQ removed
*               s(Char), s(Zone)
*        0x05 : Building removal initiated
*               s(Char), R(House type), s(Zone)
*        0x06 : Building removed
*               s(Char), R(House type), s(Zone)
*        0x07 : Org HQ remove initiated
*               s(Char), s(Zone)
*
* Reference categories:
*  509 : House types (?)
*        0x00 : Normal House
* 2005 : Faction
*        0x00 : Neutral
*        0x01 : Clan
*        0x02 : Omni
*
*/

$GLOBALS["msg_cat"] = array(
    501 => array(
        0xad0ae9b => array(
            'AOEM_ORG_LEAVE',
            "{NAME} kicked from organization (alignment changed).",
            "s{NAME}"
        ),
    ),
    506 => array(
        0x0c299d4 => array(
            'AOEM_NW_ATTACK',
            "The {ATT_SIDE} organization {ATT_ORG} just entered a state of war! {ATT_NAME} attacked the {DEF_SIDE} organization {DEF_ORG}'s tower in {ZONE} at location ({X}, {Y}).",
            "R{ATT_SIDE}/s{ATT_ORG}/s{ATT_NAME}/R{DEF_SIDE}/s{DEF_ORG}/s{ZONE}/i{X}/i{Y}"
        ),
        0x8cac524 => array(
            'AOEM_NW_ABANDON',
            "Notum Wars Update: The {SIDE} organization {ORG} lost their base in {ZONE}.",
            "R{SIDE}/s{ORG}/s{ZONE}"
        ),
        0x70de9b2 => array(
            'AOEM_NW_OPENING',
            "(PLAYER) just initiated an attack on playfield (PF) at location ((X),(Y)). That area is controlled by (DEF_ORG). All districts controlled by your organization are open to attack! You are in a state of war. Leader chat informed.",
            "s(PLAYER)/i(PF)/i(X)/i(Y)/s(DEF_ORG)"
        ),
        0x5a1d609 => array(
            'AOEM_NW_TOWER_ATT_ORG',
            "The tower (TOWER) in (ZONE) was just reduced to (HEALTH) % health by (ATT_NAME) from the (ATT_ORG) organization!",
            "s(TOWER)/s(ZONE)/i(HEALTH)/s(ATT_NAME)/s(ATT_ORG)"
        ),
        0xd5a1d68 => array(
            'AOEM_NW_TOWER_ATT',
            "The tower (TOWER) in (ZONE) was just reduced to (HEALTH) % health by (ATT_NAME)!",
            "s(TOWER)/s(ZONE)/i(HEALTH)/s(ATT_NAME)"
        ),
        0xfd5a1d4 => array(
            'AOEM_NW_TOWER',
            "The tower (TOWER) in (ZONE) was just reduced to (HEALTH) % health!",
            "s(TOWER)/s(ZONE)/i(HEALTH)"
        ),
    ),
    508 => array(
        0xa5849e7 => array(
            'AOEM_ORG_JOIN',
            "{INVITER} invited {NAME} to your organization.",
            "s{INVITER}/s{NAME}"
        ),
        0x2360067 => array(
            'AOEM_ORG_KICK',
            "{KICKER} kicked {NAME} from the organization.",
            "s{KICKER}/s{NAME}"
        ),
        0x2bd9377 => array(
            'AOEM_ORG_LEAVE',
            "{NAME} has left the organization.",
            "s{NAME}"
        ),
        0x8487156 => array(
            'AOEM_ORG_FORM',
            "{NAME} changed the organization governing form to {FORM}.",
            "s{NAME}/s{FORM}"
        ),
        0x88cc2e7 => array(
            'AOEM_ORG_DISBAND',
            "{NAME} has disbanded the organization.",
            "s{NAME}"
        ),
        0xc477095 => array(
            'AOEM_ORG_VOTE',
            "Voting notice: {SUBJECT}\nCandidates: {CHOICES}\nDuration: {DURATION} minutes",
            "s{SUBJECT}/u{MINUTES}/s{CHOICES}"
        ),
        0x9f2cb84 => array(
            'AOEM_ORG_ENDVOTE',
            "Organization leader has stopped the voting with message : \"{MSG}\"",
            "s{MSG}"
        ),		
        0xa8241d4 => array(
            'AOEM_ORG_STRIKE',
            "Blammo! {NAME} has launched an orbital attack!",
            "s{NAME}"
        ),
        0x5517b44 => array(
            'AOEM_ORG_TAX',
            "Your leader, {NAME}, just changed the organizational tax. The new tax is {NEW} credits (the old value was {OLD}).",
            "s{NAME}/u{NEW}/u{OLD}"
        ),	
        0xe5e16f8 => array(
            'AOEM_ORG_LEAD',
            "Leadership has been given to {NAME}.",
            "s{NAME}"
        ),			
    ),
    1001 => array(
        0x01 => array(
            'AOEM_AI_CLOAK',
            "{NAME} turned the cloaking device in your city {STATUS}.",
            "s{NAME}/s{STATUS}"
        ),
        0x02 => array(
            'AOEM_AI_RADAR',
            "Your radar station is picking up alien activity in the area surrounding your city.",
            ""
        ),
        0x03 => array(
            'AOEM_AI_ATTACK',
            "Your city in {ZONE} has been targeted by hostile forces.",
            "s{ZONE}"
        ),
        0x04 => array(
            'AOEM_AI_HQ_REMOVE',
            "{NAME} removed the organization headquarters in {ZONE}.",
            "s{NAME}/s{ZONE}"
        ),
        0x05 => array(
            'AOEM_AI_REMOVE_INIT',
            "{NAME} initiated removal of a {TYPE} in {ZONE}.",
            "s{NAME}/R{TYPE}/s{ZONE}"
        ),
        0x06 => array(
            'AOEM_AI_REMOVE',
            "{NAME} removed a {TYPE} in {ZONE}.",
            "s{NAME}/R{TYPE}/s{ZONE}"
        ),
        0x07 => array(
            'AOEM_AI_HQ_REMOVE_INIT',
            "{NAME} initiated removal of the organization headquarters in {ZONE}.",
            "s{NAME}/s{ZONE}"
        ),
    ),
);

$GLOBALS["ref_cat"] = array(
    509 => array(0x00 => "Normal House"),
    2005 => array(
        0x00 => "Neutral",
        0x01 => "Clan",
        0x02 => "Omni"
    ),
);

class AOExtMsg
{
	var $type, $args, $text; // PHP8.2/3
	function __construct($str = null)
    {
        $this->type = 'AOEM_UNKNOWN';
        if (!empty($str)) {
            $this->read($str);
        }
    }


    function arg($n)
    {
        $key = "{" . strtoupper($n) . "}";
        if (isset($this->args[$key])) {
            return $this->args[$key];
        }
        return null;
    }


    function read($msg)
    {
        if (substr($msg, 0, 2) !== "~&") {
            return false;
        }
        $msg = substr($msg, 2);
        $category = $this->b85g($msg);
        $instance = $this->b85g($msg);
        if (!isset($GLOBALS["msg_cat"][$category]) || !isset($GLOBALS["msg_cat"][$category][$instance])) {
            echo "\nAOChat ExtMsg Debug: Unknown Cat: $category Instance: $instance\n\n";
            return false;
        }
        $typ = $GLOBALS["msg_cat"][$category][$instance][0];
        $fmt = $GLOBALS["msg_cat"][$category][$instance][1];
        $enc = $GLOBALS["msg_cat"][$category][$instance][2];
        $args = array();
        foreach (explode("/", $enc) as $eone) {
            $ename = substr($eone, 1);
            $msg = substr($msg, 1); // skip the data type id
            switch ($eone[0]) {
                case "s":
                    $len = ord($msg[0]) - 1;
                    $str = substr($msg, 1, $len);
                    $msg = substr($msg, $len + 1);
                    $args[$ename] = $str;
                    break;
                case "i":
                case "u":
                    $num = $this->b85g($msg);
                    $args[$ename] = $num;
                    break;
                case "R":
                    $cat = $this->b85g($msg);
                    $ins = $this->b85g($msg);
                    if (!isset($GLOBALS["ref_cat"][$cat]) || !isset($GLOBALS["ref_cat"][$cat][$ins])) {
                        $str = "Unknown ($cat, $ins)";
                    } else {
                        $str = $GLOBALS["ref_cat"][$cat][$ins];
                    }
                    $args[$ename] = $str;
                    break;
            }
        }
        $str = strtr($fmt, $args);
        $this->type = $typ;
        $this->text = $str;
        $this->args = $args;
    }


    function b85g(&$str)
    {
        $n = 0;
        for ($i = 0; $i < 5; $i++) {
            $n = $n * 85 + ord($str[$i]) - 33;
        }
        $str = substr($str, 5);
        return $n;
    }
}

?>
