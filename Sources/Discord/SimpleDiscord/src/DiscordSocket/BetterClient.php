<?php

namespace SimpleDiscord\DiscordSocket;

class BetterClient extends \WebSocket\Client {
	
	var $socket, $is_connected, $last_opcode, $close_status, $is_closing, $huge_payload;
	
	public function getSocket() {
		return $this->socket;
	}

	public function connectIfNotConnected() {
		if (!isset($this->is_connected)||!$this->is_connected) {
			$this->connect();
		}
	}

	protected function read(string $length): string {
		$data = '';
		while (strlen($data) < $length) {
			$buffer = fread($this->socket, $length - strlen($data));
			if ($buffer === false) {
				$metadata = stream_get_meta_data($this->socket);
				throw new \WebSocket\ConnectionException(
					'Broken frame, read ' . strlen($data) . ' of stated '
					. $length . ' bytes.  Stream state: '
					. json_encode($metadata)
				);
			}
			if ($buffer === '') {
				return null;
			}
			$data .= $buffer;
		}
		return $data;
	}

	protected function receive_fragment() {
		// Just read the main fragment information first.
		$data = $this->read(2);

		if (is_null($data)) {
			return "TIMED OUT"; // cascade
		}

		// Is this the final fragment?  // Bit 0 in byte 0
		/// @todo Handle huge payloads with multiple fragments.
		$final = (boolean) (ord($data[0]) & 1 << 7);

		// Should be unused, and must be false…  // Bits 1, 2, & 3
		$rsv1 = (boolean) (ord($data[0]) & 1 << 6);
		$rsv2 = (boolean) (ord($data[0]) & 1 << 5);
		$rsv3 = (boolean) (ord($data[0]) & 1 << 4);

		// Parse opcode
		$opcode_int  = ord($data[0]) & 31; // Bits 4-7
		$opcode_ints = array_flip(self::$opcodes);
		if (!array_key_exists($opcode_int, $opcode_ints)) {
			throw new \WebSocket\ConnectionException("Bad opcode in websocket frame: $opcode_int");
		}
		$opcode = $opcode_ints[$opcode_int];

		// record the opcode if we are not receiving a continutation fragment
		if ($opcode !== 'continuation') {
			$this->last_opcode = $opcode;
		}

		// Masking?
		$mask = (boolean) (ord($data[1]) >> 7); // Bit 0 in byte 1

		$payload = '';

		// Payload length
		$payload_length = (integer) ord($data[1]) & 127; // Bits 1-7 in byte 1
		if ($payload_length > 125) {
			if ($payload_length === 126) {
				$data = $this->read(2);
			}
			// 126: Payload is a 16-bit unsigned int
			else {
				$data = $this->read(8);
			}
			// 127: Payload is a 64-bit unsigned int
			$payload_length = bindec(self::sprintB($data));
		}

		// Get masking key.
		if ($mask) {
			$masking_key = $this->read(4);
		}

		// Get the actual payload, if any (might not be for e.g. close frames.
		if ($payload_length > 0) {
			$data = $this->read($payload_length);

			if ($mask) {
				// Unmask payload.
				for ($i = 0; $i < $payload_length; $i++) {
					$payload .= ($data[$i] ^ $masking_key[$i % 4]);
				}

			} else {
				$payload = $data;
			}

		}

		if ($opcode === 'close') {
			// Get the close status.
			if ($payload_length >= 2) {
				$status_bin		 = $payload[0] . $payload[1];
				$status			 = bindec(sprintf("%08b%08b", ord($payload[0]), ord($payload[1])));
				$this->close_status = $status;
				$payload			= substr($payload, 2);

				if (!$this->is_closing) {
					$this->send($status_bin . 'Close acknowledged: ' . $status, 'close', true);
				}
				// Respond.
			}

			if ($this->is_closing) {
				$this->is_closing = false;
			}
			// A close response, all done.

			// And close the socket.
			fclose($this->socket);
			$this->is_connected = false;
		}

		// if this is not the last fragment, then we need to save the payload
		if (!$final) {
			$this->huge_payload .= $payload;
			return null;
		}
		// this is the last fragment, and we are processing a huge_payload
		else if ($this->huge_payload) {
			// sp we need to retreive the whole payload
			$payload			= $this->huge_payload .= $payload;
			$this->huge_payload = null;
		}

		return $payload;
	}

	public function receive() {
		if (!isset($this->is_connected)||!$this->is_connected) {
			$this->connect();
		}
		/// @todo This is a client function, fixme!

		$this->huge_payload = '';

		$response = null;
		while (is_null($response)) {
			$response = $this->receive_fragment();
			if (isset(error_get_last()["message"]) && strpos(error_get_last()["message"], "fread") === 0) {
				throw new \WebSocket\ConnectionException(serialize(error_get_last()));
			}
		}
		return $response;
	}
}
