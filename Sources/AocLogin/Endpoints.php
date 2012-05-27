<?php

class Endpoints
{
    private $m_Name;
    private $m_Type;
    private $m_Instance;
    private $m_Token;


    public function Endpoints($type, $name, $instance, $token)
    {
        $this->m_Name = $name;
        $this->m_Type = $type;
        $this->m_Instance = $instance;
        $this->m_Token = $token;
    }


    /// GetName
    /// Returns the name of the endpoint. This is used in the normal
    /// ( non protobuf ) protocol only.
    /// @return string [return] name of the endpoint
    /// @author Chaoz
    public function GetName()
    {
        return $this->m_Name;
    }


    /// GetType
    /// Returns the type of the endpoint. This is used in the protobuf
    /// protocol only.
    /// @return uint32 [return] type of the endpoint
    /// @author Chaoz
    public function GetType()
    {
        return $this->m_Type;
    }


    /// GetInstance
    /// Returns the instance of the endpoint.
    /// @return uint32 [return] instance of the endpoint
    /// @author Chaoz
    public function GetInstance()
    {
        return $this->m_Instance;
    }


    /// GetToken
    /// Returns the token of the endpoint.
    /// @return uint32 [return] token of the endpoint
    /// @author Chaoz
    public function GetToken()
    {
        return $this->m_Token;
    }

}

?>
