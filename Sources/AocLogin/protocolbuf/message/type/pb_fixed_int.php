<?php
/**
 * @author Thor Richard Hansen
 */
class PBFixedInt extends PBScalar
{
  var $wired_type = PBMessage::WIRED_32BIT;

  /**
   * Parses the message for this type
   *
   * @param array
   */
  public function ParseFromArray()
  {
    $b1 = $this->reader->next(true);
    $b2 = $this->reader->next(true);
    $b3 = $this->reader->next(true);
    $b4 = $this->reader->next(true);

    // Todo, unpack the value in case someone wants to read it
  }

  /**
   * Serializes type
   */
  public function SerializeToString($rec = -1)
  {
    // first byte is length byte
    $string = '';

    if ($rec > -1) {
      $val = $rec << 3 | $this->wired_type;
      $string .= $this->base128->set_value($val);
    }

    // Write a UInt32
    $packedData = pack("N", $this->value);
    $tmp1 = $packedData[0];
    $tmp2 = $packedData[1];
    $tmp3 = $packedData[2];
    $tmp4 = $packedData[3];
    $string .= $tmp4 . $tmp3 . $tmp2 . $tmp1;

    //$value = $this->base128->set_value($this->value);
    //parent::hexdump("PBFixedInt", $string, strlen($string) );

    return $string;
  }
}

?>
