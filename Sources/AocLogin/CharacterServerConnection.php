<?php

include_once "ServerConnection.php";
include_once "Endpoints.php";

define('RPC_TERRITORY_INIT', 0x9CB2CB03);
define('RPC_TERRITORY_INITACK', 0x5DC18991);
define('RPC_TERRITORY_STARTUP', 0x6A546D41);
define('RPC_TERRITORY_CHARACTERLIST', 0xC414C5EF);
define('RPC_TERRITORY_LOGINCHARACTER', 0xEF616EB6);
define('RPC_TERRITORY_RECEIVED_CHATSERVER', 0x23A632FA);
define('RPC_TERRITORY_RECEIVED_GUILDSERVER', 0x5AED2A60);
define('RPC_TERRITORY_ERROR', 0xD4063CA0);
define('RPC_TERRITORY_DIMENSIONLIST', 0xF899B14C);
define('RPC_TERRITORY_SETUPCOMPLETE', 0x4F91A58C);
define('RPC_TERRITORY_CSREADY', 0x5AED2A60);
define('RPC_TERRITORY_CHECKSUMMAP', 0x0C09CA25);
define('RPC_TERRITORY_SENDCHECKSUMMAP', 0xDFD8518E);
define('RPC_TERRITORY_RECEIVEDCHARSETTINGS', 0x233605B9);
define('RPC_TERRITORY_SENDCHARSETTINGS', 0x3C7C926C);

class CharacterServerConnection extends ServerConnection
{
    /// Reference to the parent (AOChat class)
    private $m_Parent;
    /// Name of the bot/character we want to log in
    private $m_CharacterName;
    /// The key we use to log in with
    private $m_LoginCookie;

    // Chat server info
    private $m_ChatServerAddress;
    private $m_ChatServerPort;
    private $m_ChatServerCookie;

    // Guild server info
    private $m_GuildServerAddress;
    private $m_GuildServerPort;
    private $m_GuildServerCookie;


    public function __construct(
        $parent,
        $accountID,
        $characterName,
        $loginCookie,
        $serverAddress,
        $serverPort,
        $loginType
    ) {
        $clientEndpoint = new Endpoints(0x7D155738, "PlayerInterface", $accountID, 0);
        $serverEndpoint = new Endpoints(0x82F20484, "PlayerAgent", 0, 0);

        parent::SConnection(
            "Conan Char Server ",
            $serverAddress,
            $serverPort,
            $clientEndpoint,
            $serverEndpoint,
            $loginType
        );

        $this->m_Parent = $parent;
        $this->m_AccountID = $accountID;
        $this->m_CharacterName = $characterName;
        $this->m_LoginCookie = $loginCookie;
    }


    public function GetChatServerAddress()
    {
        return $this->m_ChatServerAddress;
    }


    public function GetChatServerPort()
    {
        return $this->m_ChatServerPort;
    }


    public function GetChatServerCookie()
    {
        return $this->m_ChatServerCookie;
    }


    public function Connect()
    {
        if (parent::Connect()) {
            $this->SendAuthentication();
            return true;
        }
        return false;
    }


    public function SendAuthentication()
    {
        $stream = parent::CreateBinaryStream(RPC_TERRITORY_INIT);

        $stream->WriteUInt32($this->m_AccountID);
        $stream->WriteUInt32($this->m_LoginCookie);
        $stream->WriteUInt32(1);

        parent::EncryptAndSend($stream, RPC_TERRITORY_INIT);
    }


    public function LoginCharacter($characterID, $language)
    {
        $this->m_CharacterID = $characterID;
        $this->m_CharacterLanguage = $language;

        $stream = parent::CreateBinaryStream(RPC_TERRITORY_LOGINCHARACTER);

        $stream->WriteUInt32($characterID);
        $stream->WriteUInt32(1009);
        $stream->WriteString($language);
        $stream->WriteUInt16(0);
        $stream->WriteUInt16(0);
        $stream->WriteUInt16(0);
        $stream->WriteUInt32(0);
        $stream->WriteUInt32(0);
        $stream->WriteUInt32(0);

        parent::EncryptAndSend($stream, RPC_TERRITORY_LOGINCHARACTER);
    }


    public function HandlePackets()
    {
        do {
            $stream = parent::HandlePackets();
            if ($stream == null) {
                continue;
            }
            $rpcID = parent::GetRpcID($stream);

            switch ($rpcID) {
                case RPC_TERRITORY_INITACK:
                {
                    $status = $stream->ReadUInt32();

                    $outStream = parent::CreateBinaryStream(RPC_TERRITORY_STARTUP);
                    $outStream->WriteString("");
                    parent::EncryptAndSend($outStream, RPC_TERRITORY_STARTUP);
                }
                    break;

                case RPC_TERRITORY_CHECKSUMMAP:
                {
                    $outStream = parent::CreateBinaryStream(RPC_TERRITORY_SENDCHECKSUMMAP);
                    $outStream->WriteUInt32(1009);
                    parent::EncryptAndSend($outStream, RPC_TERRITORY_SENDCHECKSUMMAP);
                }
                    break;

                case RPC_TERRITORY_RECEIVEDCHARSETTINGS:
                {
                    $outStream = parent::CreateBinaryStream(RPC_TERRITORY_SENDCHARSETTINGS);
                    $outStream->WriteUInt32(1009);
                    parent::EncryptAndSend($outStream, RPC_TERRITORY_SENDCHARSETTINGS);
                }
                    break;

                case RPC_TERRITORY_CHARACTERLIST:
                {
                    $login = 0;
                    $playerid = $stream->ReadUInt32();
                    $characters = $stream->ReadVecSize();

                    // Prepare an array of all characters returned
                    for ($i = 0; $i < $characters; $i++) {
                        $stream->ReadUInt32(); // characterID
                        $stream->ReadUInt32(); // playerID

                        $characterID = $stream->ReadUInt32();
                        $characterName = $stream->ReadString();
                        $dimensionID = $stream->ReadUInt32();
                        $loginState = $stream->ReadUInt32();
                        $date = $stream->ReadString();
                        $stream->ReadUInt32(); // PlayTime
                        $stream->ReadUInt32(); // Playfield
                        $level = $stream->ReadUInt32(); // Level
                        $stream->ReadUInt32(); // Class
                        $stream->ReadUInt32(); // ?? State
                        $stream->ReadUInt32(); // ?? Login1
                        $stream->ReadUInt32(); // ?? Login2
                        $stream->ReadUInt32(); // Gender
                        $stream->ReadUInt32(); // Race
                        $language = $stream->ReadString();
                        $blocked = $stream->ReadUInt32();
                        $stream->ReadUInt32(); // ?? Offline levels
                        $stream->ReadString(); // ?? Blob MD5

                        $this->m_Parent->chars[] = array(
                            "id" => $characterID,
                            "name" => $characterName,
                            "level" => $level,
                            "online" => $loginState,
                            "language" => $language
                        );

                        // If we find the matching character, log him in.
                        // We don't care if we only fill the array up with characters up til this point
                        if ($characterName == $this->m_CharacterName) {
                            $login = $characterID;
                            $this->LoginCharacter($characterID, $language);
                            break;
                        }
                    }
                    // We have no character to log in with
                    // this is a fatal error. Dump all characters so that we can see if this is fubar
                    if ($login == 0) {
                        echo "Did not find the bot '$this->m_CharacterName' from the $characters characters on account\n";
                        if ($characters > 0) {
                            foreach ($this->chars as $e) {
                                echo "[" . $this->m_LogName . "] Character : ID = " . $e["id"] . ", Name = '" . $e["name"] . "'\n";
                            }
                        }
                        return false;
                    }
                    break;
                }

                case RPC_TERRITORY_ERROR:
                {
                    trigger_error(
                        "RPC_UNIVERSE_ERROR: Error while authenticating to territory [Err:" . $this->displayConanError(
                            $packet->args[0]
                        ) . "]",
                        E_USER_WARNING
                    );
                    return false;
                }

                case RPC_TERRITORY_RECEIVED_CHATSERVER:
                {
                    $chatserverIP = $stream->ReadUInt32();
                    $this->m_ChatServerPort = $stream->ReadUInt16();
                    $this->m_ChatServerCookie = $stream->ReadUInt32();
                    $characterType = $stream->ReadUInt32();
                    $characterID = $stream->ReadUInt32();

                    $this->m_ChatServerAddress = long2ip($chatserverIP);
                    echo("[" . $this->m_LogName . "] Received chat server address [ $this->m_ChatServerAddress:$this->m_ChatServerPort ]\n");
                    return true;
                }
                    break;

                case RPC_TERRITORY_RECEIVED_GUILDSERVER:
                {
                    $guildserverIP = $stream->ReadUInt32();
                    $this->m_GuildServerPort = $stream->ReadUInt16();
                    $this->m_GuildServerCookie = $stream->ReadUInt32();
                    $characterType = $stream->ReadUInt32();
                    $characterID = $stream->ReadUInt32();

                    $this->m_GuildServerAddress = long2ip($guildserverIP);
                    echo("[" . $this->m_LogName . "] Received guild server address [ $this->m_GuildServerAddress:$this->m_GuildServerPort ]\n");
                }
                    break;

                // Ignore these messages
                case RPC_TERRITORY_SETUPCOMPLETE:
                case RPC_TERRITORY_DIMENSIONLIST:
                case 0x206B6EE5:
                case 0x15F7AF22:
                    break;

                default:
                    echo("[" . $this->m_LogName . "] Unhandled RPC : $rpcID\n");
                    break;
            }

        } while (true);

        return true;
    }
}

?>
