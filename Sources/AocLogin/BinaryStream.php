<?php
/************************************************************************
 * Project     : BeBot Authentication 2010-2011
 * Author      : Chaoz ( Rayek Level 80 Priest Of Mitra @ Crom )
 * License     : This program is free software; you can redistribute it
 *               and/or modify it under the terms of the GNU General
 *               Public License as published by the Free Software
 *               Foundation; version 2 of the License only.
 *               This program is distributed without ANY WARRANTY.
 *
 * File        : BinaryStream
 * Description : Wrapper class to make it easier to read and write datatypes
 *               to and from a binary data blob. Also to make it more clear
 *               how many bytes we are reading and writing.
 *************************************************************************/

class BinaryStream
{
    private $m_Data = "";
    private $m_DataLen = 0;
    private $m_ReadPtr = 0;
    private $m_WritePtr = 0;


    public function __construct($data = "", $len = 0)
    {
        $this->m_Data = $data;
        $this->m_DataLen = $len;
        $this->m_ReadPtr = 0;
        $this->m_WritePtr = 0;
    }


    /// GetReadData
    /// Returns the data we have not read yet from the binarystream
    /// @return String - The binary data
    /// @author Chaoz
    public function GetReadData()
    {
        $str = substr($this->m_Data, $this->m_ReadPtr, $this->m_DataLen - $this->m_ReadPtr);
        return $str;
    }


    /// GetLength
    /// Returns max length of the used buffer
    /// @return int - Length of the used buffer
    /// @author Chaoz
    public function GetLength()
    {
        if ($this->m_DataLen > $this->m_WritePtr) {
            return $this->m_DataLen;
        } else {
            return $this->m_WritePtr;
        }
    }


    /// GetWriteData
    /// Returns the databuffer that we have written to.
    /// This function will convert the data from an array to a string before returning it
    /// @return String - The buffer we have written data into
    /// @author Chaoz
    public function GetWriteData()
    {	
		if(is_array($this->m_Data)) return implode($this->m_Data);
		else return $this->m_Data;
    }


    /// GetRawData
    /// Returns the databuffer as is
    /// @return Array - The data buffer
    /// @author Chaoz
    public function GetRawData()
    {
        return $this->m_Data;
    }


    /// GetReadLength
    /// Returns length of how much we have read so far
    /// @return int - The read pointer
    /// @author Chaoz
    public function GetReadLength()
    {
        return $this->m_ReadPtr;
    }


    /// GetWriteLength
    /// Returns length of how much we have written so far
    /// @return int - The write pointer
    /// @author Chaoz
    public function GetWriteLength()
    {
        return $this->m_WritePtr;
    }


    /// GetVecSize
    /// Returns the number of elements in the vector
    /// @return int - The number of elements
    /// @author Chaoz
    public function ReadVecSize()
    {
        $res = $this->ReadUInt32();
        $res = (($res / 1009) - 1);
        return $res;
    }


    /// Skip
    /// Skips n bytes in the read buffer
    /// @author Chaoz
    public function Skip($len)
    {
        $this->m_ReadPtr += $len;
    }


    /// ReadUInt16
    /// Reads two bytes from the buffer and returns the uint16 value
    /// @return uint16 - The uint16 value
    /// @author Chaoz
    public function ReadUInt16()
    {
        $tmp1 = $this->m_Data[$this->m_ReadPtr++];
        $tmp2 = $this->m_Data[$this->m_ReadPtr++];
        $tmp = $tmp1 . $tmp2;

        $data = unpack("n", $tmp);
        $res = array_pop($data);

        //echo("[BinaryStream][ReadUInt16] " . $res . " [pos:" . ($this->m_ReadPtr-2) . " -> " . ($this->m_ReadPtr ) . "]\n");

        return $res;
    }


    /// ReadUInt32
    /// Reads four bytes from the buffer and returns the uint32 value
    /// @return uint32 - The uint32 value
    /// @author Chaoz
    public function ReadUInt32()
    {
        $tmp1 = $this->m_Data[$this->m_ReadPtr++];
        $tmp2 = $this->m_Data[$this->m_ReadPtr++];
        $tmp3 = $this->m_Data[$this->m_ReadPtr++];
        $tmp4 = $this->m_Data[$this->m_ReadPtr++];
        $tmp = $tmp1 . $tmp2 . $tmp3 . $tmp4;

        $data = unpack("N", $tmp);
        $res = array_pop($data);

        // Make sure the value is unsigned
        if ($res < 0) {
            $res += 0x100000000;
        }

        //echo("[BinaryStream][ReadUInt32] " . $res . " [pos:" . ($this->m_ReadPtr-4) . " -> " . ($this->m_ReadPtr ) . "]\n");

        return $res;
    }


    /// ReadString
    /// Reads first the length of the string, and then the string from the buffer
    /// @return string - The string in the buffer
    /// @author Chaoz
    public function ReadString()
    {
        $len = $this->ReadUInt16();
        if ($this->m_ReadPtr + $len > $this->m_DataLen) {
            //echo("[BinaryStream][ReadString] " . $str . " [pos:" . ($this->m_ReadPtr) . "] invalid length :" . $len . " since it will read outside of buffer.\n");
            return null;
        }

        $str = substr($this->m_Data, $this->m_ReadPtr, $len);
        //echo("[BinaryStream][ReadString] " . $str . " [pos:" . ($this->m_ReadPtr) . " -> " . ($this->m_ReadPtr + $len) . "] [len:" . $len . "]\n");
        $this->m_ReadPtr += $len;

        return $str;
    }


    /// ReadRaw
    /// Reads n number of bytes from the read buffer
    /// @return string - The string in the buffer
    /// @author Chaoz
    public function ReadRaw($len)
    {
        if ($this->m_ReadPtr + $len > $this->m_DataLen) {
            //echo("[BinaryStream][ReadRaw] " . $str . " [pos:" . ($this->m_ReadPtr) . "] invalid length :" . $len . " since it will read outside of buffer.\n");
            return null;
        }

        $str = substr($this->m_Data, $this->m_ReadPtr, $len);
        $this->m_ReadPtr += $len;

        return $str;
    }


    /// WriteUInt8
    /// Writes 1 byte to the write buffer
    /// @param data [int] The data we want to write
    /// @author Chaoz
    public function WriteUInt8($data)
    {
        $packedData = pack("C", $data);
        $this->m_Data[$this->m_WritePtr++] = $packedData[0];
        //echo("[BinaryStream][WriteUInt8] " . $data . " [pos:" . ($this->m_WritePtr-1) . " -> " . ($this->m_WritePtr ) . "] \n");
    }


    /// WriteUInt16
    /// Writes 2 byte to the write buffer
    /// @param data [int] The data we want to write
    /// @author Chaoz
    public function WriteUInt16($data)
    {
        $packedData = pack("n", $data);
        $this->m_Data[$this->m_WritePtr++] = $packedData[0];
        $this->m_Data[$this->m_WritePtr++] = $packedData[1];
        //echo("[BinaryStream][WriteUInt16] " . $data . " [pos:" . ($this->m_WritePtr-2) . " -> " . ($this->m_WritePtr ) . "] \n");
    }


    /// WriteUInt32
    /// Writes 4 bytes to the write buffer
    /// @param data [int] The data we want to write
    /// @author Chaoz
    public function WriteUInt32($data)
    {
        $packedData = pack("N", $data);
        $this->m_Data[$this->m_WritePtr++] = $packedData[0];
        $this->m_Data[$this->m_WritePtr++] = $packedData[1];
        $this->m_Data[$this->m_WritePtr++] = $packedData[2];
        $this->m_Data[$this->m_WritePtr++] = $packedData[3];
        //echo("[BinaryStream][WriteUInt32] " . $data . " [pos:" . ($this->m_WritePtr-4) . " -> " . ($this->m_WritePtr) . "] \n");
    }


    /// WriteRaw
    /// Writes n byte to the write buffer
    /// @param str [String] The data we want to write
    /// @param len [int] Length of the data we want to write
    /// @author Chaoz
    public function WriteRaw($str, $len)
    {
		//echo("[BinaryStream][WriteRaw] " . $str . " [pos:" . ($this->m_WritePtr) . " to " . ($this->m_WritePtr + $len) . "] len:" . $len . "]\n");
        for ($i = 0; $i < $len; $i++) {
            if(isset($str[$i])) {
				$this->m_Data[$this->m_WritePtr++] = $str[$i]; }
        }
    }
	

    /// WriteString
    /// Writes a string to the write buffer
    /// @param str [String] The data we want to write
    /// @author Chaoz
    public function WriteString($str)
    {
        $len = strlen($str);
        $this->WriteUInt16($len);
        $this->WriteRaw($str, $len);
    }
}

?>
