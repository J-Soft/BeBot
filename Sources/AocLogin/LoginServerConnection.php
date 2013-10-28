<?php

include_once "ServerConnection.php";
include_once "Endpoints.php";

define('RPC_UNIVERSE_INIT', 0);
define('RPC_UNIVERSE_CHALLENGE', 0);
define('RPC_UNIVERSE_ANSWERCHALLENGE', 1);
define('RPC_UNIVERSE_AUTHENTICATED', 1);
define('RPC_UNIVERSE_ERROR', 2);
define('RPC_UNIVERSE_INTERNAL_ERROR', 4);
define('RPC_UNIVERSE_SETREGION', 5);

class LoginServerConnection extends ServerConnection
{
    private $m_Parent;
    private $m_Username;
    private $m_Password;

    private $m_LoginCookie;
    private $m_AccountID;

    private $m_CharacterServerAddress;
    private $m_CharacterServerPort;


    public function LoginServerConnection(
        $parent,
        $username,
        $password,
        $serverAddress,
        $serverPort,
        $loginType
    ) {
        $clientEndpoint = new Endpoints(0x0C384054, "UniverseInterface", 1, 0);
        $serverEndpoint = new Endpoints(0x604DDBA0, "UniverseAgent", 0, 0);

        parent::ServerConnection(
            "Conan Login Server",
            $serverAddress,
            $serverPort,
            $clientEndpoint,
            $serverEndpoint,
            $loginType
        );

        $this->m_LoginCookie = 0;
        $this->m_AccountID = 0;

        $this->m_Parent = $parent;
        $this->m_Username = $username;
        $this->m_Password = $password;
    }


    /// GetCharacterServerAddress
    /// Returns the address of the character server
    /// @return String [return] Name of the server ( like server.ageofconan.com )
    /// @author Chaoz
    public function GetCharacterServerAddress()
    {
        return $this->m_CharacterServerAddress;
    }


    /// GetCharacterServerPort
    /// Returns the port of the character server
    /// @return uint16 [return] Port to the server ( like 7036 )
    /// @author Chaoz
    public function GetCharacterServerPort()
    {
        return $this->m_CharacterServerPort;
    }


    /// GetSeed
    /// Returns the cookie used to log in to all the other servers
    /// @return uint32 [return] Returns the cookie
    /// @author Chaoz
    public function GetLoginCookie()
    {
        return $this->m_LoginCookie;
    }


    /// GetPlayerID
    /// Return the ID of the account that we are logging in with
    /// @return uint32 [return] Returns the playerID
    /// @author Chaoz
    public function GetAccountID()
    {
        return $this->m_AccountID;
    }


    /// Connect
    /// Connects to the login server and sends the initial login packet
    /// @author Chaoz
    public function Connect()
    {
        if (parent::Connect()) {
            $this->SendAuthentication();
            return true;
        }
        return false;
    }


    /// SendAuthentication
    /// Sends the username to the login server
    /// @author Chaoz
    public function SendAuthentication()
    {
        $stream = parent::CreateBinaryStream(RPC_UNIVERSE_INIT);
        if ($this->m_EndpointType == LOGIN_TYPE_ENDPOINTS) {
            $key = $this->m_Username . ":2";
            $stream->WriteString("");
            $stream->WriteString($key);
            $stream->WriteUInt32(1);
        } else {
            if ($this->m_EndpointType == LOGIN_TYPE_PROTOBUF) {
                $stream->WriteString($this->m_Username);
                $stream->WriteUInt32(1);
            }
        }

        parent::EncryptAndSend($stream, RPC_UNIVERSE_INIT);
    }


    /// AnswerChallenge
    /// Generates an encrypted login key based of the seed, username and password
    /// we get from the server and then sends it back to the server to log in with
    /// @arg uint32 [in] Login seed
    /// @author Chaoz
    public function AnswerChallenge($seed)
    {
        $response = $this->m_Parent->generate_login_key($seed, $this->m_Username, $this->m_Password);

        $stream = parent::CreateBinaryStream(RPC_UNIVERSE_ANSWERCHALLENGE);
        $stream->WriteString($response);
        parent::EncryptAndSend($stream, RPC_UNIVERSE_ANSWERCHALLENGE);
    }


    /// HandlePackets
    /// Handles all incoming messages from the loginserver.
    /// @author Chaoz
    public function HandlePackets()
    {
        do {
            $stream = parent::HandlePackets();
            if ($stream == null) {
                continue;
            }
            $rpcID = parent::GetRpcID($stream);

            switch ($rpcID) {
                case RPC_UNIVERSE_CHALLENGE:
                {
                    $seed = $stream->ReadString();
                    $this->AnswerChallenge($seed);
                }
                    break;

                case RPC_UNIVERSE_AUTHENTICATED:
                {
                    $authStatus = $stream->ReadUInt32();
                    $playerType = $stream->ReadUInt32();
                    $this->m_AccountID = $stream->ReadUInt32();
                    $tmAddress = $stream->ReadString();
                    $this->m_LoginCookie = $stream->ReadUInt32();
                    $loginStatus = $stream->ReadUInt32();

                    if ($authStatus != 1) {
                        echo("[" . $this->m_LogName . "] Failed to authenticate [auth:$authStatus] \n");
                        return false;
                    }

                    if ($loginStatus != 0) {
                        echo("[" . $this->m_LogName . "] Failed to log in [login error:$loginStatus] : " . parent::displayConanError(
                                $loginStatus
                            ) . "\n");
                        return false;
                    }

                    // Split the server address up from address:port
                    if (strlen($tmAddress) != 0) {
                        list($this->m_CharacterServerAddress, $this->m_CharacterServerPort) = split(":", $tmAddress);
                        echo "[" . $this->m_LogName . "] Received character server address [ " . $this->m_CharacterServerAddress . ":" . $this->m_CharacterServerPort . " ]\n";
                        return true;
                    }
                    echo("[" . $this->m_LogName . "] Failed to receive the character server address\n");
                    return false;
                }

                case RPC_UNIVERSE_INTERNAL_ERROR:
                {
                    trigger_error("RPC_UNIVERSE_INTERNAL_ERROR: Internal error", E_USER_WARNING);
                    $this->Disconnect("Internal error");
                }
                    return false;

                case RPC_UNIVERSE_ERROR:
                {
                    //trigger_error("RPC_UNIVERSE_ERROR: Error while authenticating to universe [Err:".$this->displayConanError($packet->args[0])."]",E_USER_WARNING);
                    $this->Disconnect("Universe Error");
                }
                    return false;

                case RPC_UNIVERSE_SETREGION:
                    break;

                case -1:
                    trigger_error(
                        "RPC_LOGINSERVER_ERROR: Error while reading message header [Err:Unknown RPC ID]",
                        E_USER_WARNING
                    );
                    return false;

                default:
                    echo("[" . $this->m_LogName . "] Unhandled RPC : $rpcID\n");
                    break;
            }

        } while (true);

        return true;
    }
}

?>
