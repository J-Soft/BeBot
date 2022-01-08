<?php
/*	WebSocket клиент/сервер без зависимостей.

	Соответствует протоколу, описанному в RFC 6455.
	Поддерживает хендшейки, маскирование, фрагментацию, control-фреймы (пинги, close'ы), TLS.
	Не накладывает каких-либо ограничений на маскировку фреймов.
	Поддерживаются практически все возможности протокола.
	Не поддерживаются расширения и субпротоколы.
	
	Клиент WebSockets.
	
		При ошибках соединения выбрасывает исключение.
		У клиента имеется встроенный механизм обнаружения повисших соединений.
		Если длительное время данных не приходит, то выбрасывается исключение и соединение закрывается.
		Это позволяет обнаружить и обработать (штатную для TCP) ситуацию зависания подключения, когда удаленный сервер внезапно пропал без ответа.
		Поэтому когда долгое время (stall_time/2) нет данных, то делается поддержание активности в канале. По-умолчанию это пинги в виде control-frame.
		Сервер может не поддерживать ping control-frames (хотя обязан). В таких случаях вы можете пронаследовать класс и перекрыть функцию make_activity(), выполнив вместо пинга что-то другое. 
		
		Пример:
		
			$ws = new WebSocketClient();
			$ws->connect("ws://localhost:1122/some/url");
			$ws->send('some shit');
			$arr = $ws->recv();
			foreach ($arr as $a)
			{echo $a['payload'].'==========='."\n";}
		
	Сервер WebSockets.
	
		При ошибках клиентов исключения не выбрасываются - вместо этого вызывается соответствующий колбек.
		Все клиенты обрабатываются в одном потоке (любой "медленный" клиент способен заставить ждать весь сервер!), поэтому имеется уязвимость типа "DoS".
		В глобальную сеть, конечно, не повесишь, а вот локально - вполне применимый.
		Неявно склеивает фрагментированные фреймы. При получении гигантских фрагментированных пакетов (несколько сотен Мб) может просто исчерпаться память, и это, опять же, DoS.
		Сервер не поддерживает TLS соединения.
		
	
		Пример:
		
			$s = new WebSocketServer();
			$s->on_receive = function($cl,$f)use($s){
			  echo 'client #'.$cl['id'].' says:';
			  var_dump($f);
			  echo "\n";
			  $s->send($cl, 'Thank you!', 'text', false);
			};
			$s->on_connect = function($cl){
			  echo 'client #'.$cl['id'].' connected to "'.$cl['url'].'"'."\n";
			};
			$s->on_error = function($cl,$err){
			  echo 'client #'.$cl['id'].' error: '.$err."\n";
			};
			$s->start('localhost', 36000);
			
			// цикл не обязателен - можно проверять довольно редко, с любыми интервалами
			while (true)
			{
				$s->check_messages();
				usleep(50000);
			}
				
		Потестить сервер можно через плагины Chrome или Firefox: 
		
			Simple Web Socket Client
			Smart Websocket Client
			и др.
	
	Ссылки:
	
		https://tools.ietf.org/html/rfc6455
		https://developer.mozilla.org/en-US/docs/Web/API/WebSockets_API/Writing_WebSocket_client_applications
		https://developer.mozilla.org/en-US/docs/Web/API/WebSockets_API/Writing_WebSocket_servers	
	
	2021 :  Forked & optimized for Bebot context by Bitnykk (RK5)
			This means to seek best balance of compatibility vs stability while max availability
			Result might be dropping few packets when the read is too slow to respond WS side ...
			... but it achieves intended without sacrificing responsivity for all other tasks !
*/



class WebSocketException extends Exception {}

class WebSocketClient extends WebSocketBase 
{
	// время в секундах, после которого соединение будет считаться повисшим, при условии что за это время не было входящих данных.
	// после прошествия половины будет вызвана функция make_activity().
	public $stall_time = 28;
	// заголовки, полученные от сервера при рукопожатии (будет заполнено при вызове connect())
	public $handshake = '';
	// функция, периодически вызываемая пока recv() ждёт результатов. 
	// Может использоваться, чтобы выполнять некоторые действия во время ожидания, т.к. оно блокирующее.
	// вызывается довольно часто (зависит от таймаута приема).
	public $on_idle;
	public $framesize = 4;	
	protected $socket, $session;
	protected $last_activity = 0;
	protected $last_pinged = 0;
	protected $timeout = 0.05;
	
	/*	Соединиться с заданным URL.
			$url - адрес, например 'ws://some-shit/to/hell' или 'wss://secure-shit/path'
		Вернет true при успехе.
	*/
	public function connect($url)
	{
		extract(parse_url($url)); // $ scheme host port path query fragment user pass
		$scheme = ((strtolower($scheme)=='wss')?'tls':'tcp');
		$this->socket = @stream_socket_client($scheme.'://'.$host.':'.$port, $this->errno, $this->errstr, $this->stall_time);
		if (!$this->socket) {
			//throw new WebSocketException('connect error ('.$this->errstr.')');
			$this->close();
			$this->opened = false;
			echo 'WebSocketException : connect error
			';
		}
		$this->set_timeout(0.5);
		
		$key = "";
		for ($i=1;$i<=16;$i++) $key .= chr(mt_rand(0,255));
		$key = base64_encode($key);
		$get = ($path ?? "/").
			(isset($query) ? "?" . $query : "").
			(isset($fragment) ? "#" . $fragment : "");		
		$head = "GET ".$get." HTTP/1.1"."\r\n".
				"Host: ".$host.":".$port."\r\n".
				"User-Agent: Bebot\r\n".
				"Connection: Upgrade"."\r\n".
				"Upgrade: webSocket"."\r\n".
				"Sec-WebSocket-Key: ".$key."\r\n".
				"Sec-WebSocket-Version: 13"."\r\n";
		if(isset($user) && isset($pass)) $head .= "authorization: Basic ".base64_encode($user.":".$pass)."\r\n";
		$head.= "\r\n";

		$x = fwrite($this->socket, $head);
		if (!$x)
		{
			$this->close();
			//throw new WebSocketException('handshake error (can\'t write to socket)');
			echo 'WebSocketException : handshake error
			';			
			$this->opened = false;
		}
		$this->handshake = '';
		do {
			$x = @fgets($this->socket);
			if ($x===FALSE)
			{
				$this->close();
				//throw new WebSocketException('handshake error (can\'t read from socket)');
				echo 'WebSocketException : read error
				';
				$this->opened = false;
			}
			$this->handshake .= $x;
		} while (!in_array($x, ["\r\n", "\n"]));

		if (strpos($this->handshake, $this->server_key($key))===FALSE)
		{
			if (preg_match('#content-length:\s*(\d+)#i', $this->handshake, $m))
			{$this->handshake .= fread($this->socket, $m[1]);}
			//throw new WebSocketException('handshake error (invalid server key: '.substr($this->handshake,0,1200).'...)');
			echo 'WebSocketException : key error
			';
			$this->close();
			$this->opened = false;
			return;
		}
		$this->set_timeout($this->timeout);
		$this->opened = true;
		$this->last_activity = time();
		return true;
	}
	
	public function __destruct()
    {$this->close();}
	
	// получить интервал проверок сетевого буфера
	public function get_timeout()
	{return $this->timeout;}
	
	/*	Задать интервал между проверками сетевого буфера входящих данных, в секундах.
		Чем он выше, тем больше вероятность что входящие данные будут периодически слегка запаздывать.
	*/
	public function set_timeout($timeout)
	{
		$this->timeout = $timeout;
		@stream_set_timeout($this->socket, floor($timeout), floor(1000000*($timeout-floor($timeout))));
	}

	/*	Отправить фрейм.
			$data - полезные данные (строка)
			$opcode - тип фрейма ("опкод"). Возможные значения: 'text', 'binary', 'continue', 'ping', 'pong', 'close'
				Фреймы типов 'ping', 'pong' и 'close' не могут содержать больше 125 байт данных внутри $data.
			$is_final - обычно true, но если требуется отправить цепочку фреймов (т.н. "фрагментированный фрейм"), то им всем ставьте $is_final==false, а последнему true. При этом всем фреймам в цепочке кроме первого нужно поставить $opcode=='continue'. Отправлять фреймы цепочками имеет смысл когда общий размер отправляемых данных заранее не известен, либо он слишком большой. Протокол разрешает фрагментацию для опкодов 'text' и 'binary'. Для остальных опкодов $is_final игнорируется. Другие типы пакетов могут "вклиниваться" при передаче фрагментированного пакета. Но нельзя слать одновременно несколько фрагментированных "потоков" - можно один (например типа 'text'), а второй целостный (например 'binary'), или наоборот.
	*/
    public function send($data, $opcode = 'text', $is_final = true)
	{
		$z = null;
		if (!$this->opened) {
			//throw new WebSocketException('tried to send/recv on closed socket');
			$this->close();
			echo 'WebSocketException : send error
			';			
			$this->opened = false;
		}
		if (in_array($opcode, ['ping', 'pong', 'close'])) 
		{
			$data = substr($data,0,125);
			$is_final = true;
		}
		$data = $this->hybi_encode($data, $opcode, true, $is_final);
		for ($wr=0;$wr<strlen($data);$wr+=$x)
		{
			$x = @fwrite($this->socket, substr($data, $wr));
			if (!$x)
			{$z = @stream_get_meta_data($this->socket);}
			// соединение рипнулось
			/*if ($x===FALSE || (isset($z['eof'])&&((int)$z['eof'])==1) || !$this->opened)
			{
				$op = $this->opened;
				$this->close();
				throw new WebSocketException('error when sending data (z = '.$z.', res='.((int)$x).', eof='.((int)$z['eof']).', opened='.((int)$op).')');
			}*/
		}
		@fflush($this->socket);
    }
	
	/*	Получить свежие фреймы.
			$blocking - (bool) получить как минимум один. Если фреймов нет - будет ждать неограниченное время пока хотя бы один не появится.
		Вернет массив фреймов, возможно пустой (при $blocking==false).
		Фрейм это ассоциативный массив, имеет вид:
			'payload' - полезные данные (строка)
			'opcode' - тип фрейма ("опкод"). Возможные значения: 
				'text' - фрейм содержит текстовые данные
				'binary' - фрейм содержит бинарные данные
				'ping' - т.н. "пинг", собеседник должен в ответ послать pong-фрейм
				'pong' - ответ на ping-фрейм
				'close' - закрывающий соединение сигнал
			'masked' - (bool) был ли фрейм маскирован
			'fragmented' - (bool) был ли фрейм собран из нескольких фрагментов ("фрагментирован")
		Функция неявно склеивает фрагментированные фреймы и возвращает всегда целостные данные.
	*/
	public function recv($blocking = true)
	{
		if (!$this->opened) {
			//throw new WebSocketException('tried to send/recv on closed socket');
			$this->close();
			echo 'WebSocketException : recv error
			';		
			$this->opened = false;			
		}
		$res = []; $z = null;
		$read[] = $this->socket; $write  = null; $except = null;
		if(stream_select($read, $write, $except, 0)) {
		while (!feof($this->socket))
		{
			// ждёт данных время, равное таймауту. задать можно через set_timeout().
			$x = @fread($this->socket, $this->framesize*1024);
			$now = microtime(true);
			if ($this->on_idle) ($this->on_idle)();
			if (strlen($x))
			{
				$this->last_activity = $now;
				$this->last_pinged = 0;
			}
			if ($x==='')
			{
				$z = @stream_get_meta_data($this->socket);
				if ($now-$this->last_activity > $this->stall_time/2)
				{
					if (!$this->last_pinged)
					{
						$this->make_activity();
						$this->last_pinged = $now;
					}
					elseif ($now-$this->last_pinged > $this->stall_time)
					{
						// длительное время нет данных == соединение повисло
						$this->close();
						//throw new WebSocketException('connection stalled');
						echo 'WebSocketException : stall error
						';
						$this->opened = false;
					}
				}
			}
			/*if ($x===FALSE || (isset($z['eof'])&&((int)$z['eof'])==1) || !$this->opened)
			{			
				// соединение рипнулось
				$op = $this->opened;
				$this->close();
				throw new WebSocketException('error when receiving data (z = '.$z.', res='.((int)$x).', eof='.((int)$z['eof']).', opened='.((int)$op).')');
			}*/
			// цикл нужен чтобы забрать все готовые фреймы из буфера
			while ($data = $this->hybi_decode($x, $this->session))
			{
				$x = '';
				if ($data['opcode']=='ping')			
				{
					$this->send(substr($data['payload'],0,125), 'pong');
				}
				elseif ($data['opcode']=='pong') {					
					$this->last_pinged = 0;
				}
				elseif ($data['opcode']=='close' && $this->opened)
				{
					$this->send('', 'close');
					$this->close();
				}
				else
				{
					$res[] = $data;
				}
			}
			// есть один или несколько целых фреймов (и возможно один неполный, его данные были сохранены - их впитала функция hybi_decode()),
			// либо целых фреймов нет, но есть один и он пока неполный.
			if ($res || !$blocking) return $res;
			// если не было получено ни одного целого фрейма и включен блокирующий режим, то отправляем дальше ждать неявно через fread()
		}}
	}
	
	/*	Закрыть соединение.
		Не порождает ошибок и исключений.
	*/
    public function close()
	{
		$this->opened = false;
		$this->session = [];
		// try {
			// $this->send('', 'close');
		// } catch (Throwable $e) {};
		if(isset($this->socket)) @fclose($this->socket);
		if(isset($this->socket)) unset($this->socket);
	}
	
	/*	Функция, которая должна вынудить сервер прислать любой фрейм как можно скорее.
		Вы можете перекрыть её своей, если сервер не поддерживает WebSocket-пинги.
	*/
	public function make_activity()
	{
		$this->send('', 'ping');	
	}

}

class WebSocketBase
{
	// посчитать серверный ключ на основе клиентского ключа
	protected function server_key($key)
	{
		$magic_string = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
		return base64_encode(sha1($key.$magic_string, true));
	}

	/*	Закодировать фрейм.
			$data - данные (строка)
			$opcode - тип фрейма. Возможные значения:
				'text' - стандартный тип фрейма
				'continue' - используется в связке с $fin, см. ниже
				'binary' - бинарный (используется по согласованию!)
				'close' - закрывающий соединение фрейм. Получив такой фрейм сторона должна закрыть соединение.
				'ping' - пингующий фрейм. Может посылаться как сервером, так и клиентом. Согласно протоколу противоположная сторона должна ответить на него как можно быстрее фреймом с опкодом 'pong' и с теми же данными, но размер данных при этом не может быть больше 125.
				'pong' - см. выше
			$masked - маскировать ли фрейм. Обычно true.
			$fin - последний ли это фрейм. Обычно true. Используется когда размер данных неизвестен и тогда засылается серия фреймов, первый из которых имеет опкод 'text' либо 'binary', а остальные "досылаются" с опкодом 'continue'. Всем фреймам в этой цепочке (кроме последнего) ставится $fin == false. Но опять же, это используется только для различного рода стриминга, т.е. случай нестандартный.
	*/
	protected function hybi_encode($data, $opcode = 'text', $masked = true, $fin = true)
	{
		$opcodes = ['continue' => 0x00, 'text' => 0x01, 'binary' => 0x02, 'close' => 0x08, 'ping' => 0x09, 'pong' => 0x0A];
		$sz = strlen($data);
		$mask = $extended_sz = '';
		if ($sz > 0xFFFF)
		{
			$extended_sz = pack('J', $sz);
			$sz = 127;
		}
		elseif ($sz > 125)
		{
			$extended_sz = pack('n', $sz);
			$sz = 126;
		}
		if ($masked)
		{
			$mask = pack('N', mt_rand(0, 0xffffffff));
			$x = strlen($data);
			for ($i=0;$i<$x;$i++)
			{$data[$i] = $data[$i] ^ $mask[$i % 4];}
			$sz = (0x80 | $sz);
		}
		$opcode = $opcodes[$opcode];
		if ($fin) $opcode = (0x80 | $opcode);
		$frame = 
			chr($opcode).
			chr($sz).
			$extended_sz.
			$mask.
			$data
		;
		return $frame;
	}
	
	/*	Декодировать фрейм.
		Обрезает из переданных данных ровно один фрейм, декодирует его и возвращает.
		Если данных не хватает, то вернет false.
		Необработанная часть неявно запоминается и будет добавлена перед декодированием в начало следующих данных. 
		Т.е. внутри $data данных может быть на несколько фреймов - будет возвращен лишь первый, а остальные можно получить при дальнейших вызовах.
		Функция неявно склеивает фрагментированные фреймы, возвращая всегда целостный.
	*/
	protected function hybi_decode($data, &$session)
	{	
		$prev = "";
		if(isset($session['prev_data'])) $prev = $session['prev_data'];
		$data = $prev.$data;
		// данных недостаточно
		if (strlen($data) < 2)
		{
			$session['prev_data'] = $data;
			return false;
		}
		$opcodes = [0x00 => 'continue', 0x01 => 'text', 0x02 => 'binary', 0x08 => 'close', 0x09 => 'ping', 0x0A => 'pong'];
		$opcode = ord($data[0]) & 0x0F;
		$is_fin = ord($data[0]) & 0x80;
		$res = [];
		$res['opcode'] = $opcodes[$opcode];
		$res['masked'] = (bool)(ord($data[1]) & 0x80);
		$res['fragmented'] = false;
		$len = ord($data[1]) & 0x7F;
		if ($len==126) $fmt = [8, 'n'];
		elseif ($len<=125) $fmt = [6];
		elseif ($len==127) $fmt = [14, 'J'];
		if(isset($fmt[0])) $offset = $fmt[0];
		else $offset = 0;
		if(isset($fmt[1])) $char = $fmt[1];
		else $char = "";
		if ($char)
		{
			// данных недостаточно
			if (strlen($data) < $offset-4)
			{
				$session['prev_data'] = $data;
				return false;
			}
			$len = max(0, unpack('nshit/'.$char.'pay', $data)['pay']);
		}
		if (!$res['masked']) $offset -= 4;
		// данных недостаточно
		if (strlen($data) < $offset + $len)
		{
			$session['prev_data'] = $data;
			return false;
		}
		if ($res['masked'])
		{
			$mask = substr($data, $offset-4, 4);
			$j = 0;
			$x = $offset + $len;
			for ($i=$offset;$i<$x;$i++)
			{$data[$i] = $data[$i] ^ $mask[$j++ % 4];}
		}
		$res['payload'] = substr($data, $offset, $len);
		$session['prev_data'] = substr($data, $offset + $len);
		if ($res['opcode']=='continue')
		{
			if (in_array($session['buf_opcode'], ['text', 'binary']))
			{
				$session['buf'][$session['buf_opcode']] .= $res['payload'];
				if (!$is_fin) return false;
				$res['opcode'] = $session['buf_opcode'];
				$res['fragmented'] = true;
				$res['payload'] = (string)$session['buf'][$session['buf_opcode']];
				$session['buf_opcode'] = '';
			}
		}
			else
		{
			if (in_array($res['opcode'], ['text', 'binary']))
			{
				$session['buf'][$res['opcode']] = '';
				$session['buf_opcode'] = $res['opcode'];
			}
		}
		return $res;
	}
}


class WebSocketServer extends WebSocketBase
{
	protected $socket, $last_activity, $last_pinged;
	protected $client_cnt;
	protected $timeout = 0.05;
	
	/*	Список подключенных клиентов.
		Содержит массивы клиентов, каждый вида:
			'id' - ID клиента
			'url' - относительный WebSocket-URL, к которому обратился клиент, например "/some/addr"
			'sock' - сокет
			'handshake' - заголовки, полученные от клиента при подключении
			'session' - внутренние данные, используемые при приеме фреймов
	*/
	public $clients = [];
	
	/*	Колбек, вызывающийся при подключении нового клиента.
		функция-замыкание вида: 
			function($client){ ... }
			, где:
				$client - массив клиента
	*/
	public $on_connect;
	
	/*	Колбек, вызывающийся при получении очередного фрейма.
		функция-замыкание вида: 
			function($client, $frame){ ... }
			, где:
				$client - массив клиента
				$frame - массив фрейма. Содержит поля:
					'payload' - полезные данные (строка)
					'opcode' - тип фрейма ("опкод"). Возможные значения: 
						'text' - фрейм содержит текстовые данные
						'binary' - фрейм содержит бинарные данные
						'ping' - т.н. "пинг", собеседник должен в ответ послать pong-фрейм
						'pong' - ответ на ping-фрейм
						'close' - закрывающий соединение сигнал
					'masked' - (bool) был ли фрейм маскирован
					'fragmented' - (bool) был ли фрейм собран из нескольких фрагментов ("фрагментирован")
	*/
	public $on_receive;
	
	/*	Колбек, вызывающийся при ошибке или отключении клиента.
		При отключении $error == 'disconnected'.
		функция-замыкание вида: 
			function($client, $error){ ... }
			, где:
				$client - массив клиента
				$error - строковое описание ошибки
	*/
	public $on_error;
	
	// запустить сервер
	public function start($host = '127.0.0.1', $port = 6666)
	{
		if (($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false)
		{throw new WebSocketException("socket_create() failed: ".socket_strerror(socket_last_error()));}
		// это позволяет заюзать порт сразу же после завершения сервера. Иначе еще 60 секунд ОС никому не будет давать открыть его.
		// должна вызываться именно здесь.
		socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
		if (socket_bind($this->socket, $host, $port) === false)
		{throw new WebSocketException("socket_bind() failed: ".socket_strerror(socket_last_error($this->socket)));}
		if (socket_listen($this->socket) === false)
		{throw new WebSocketException("socket_listen() failed: ".socket_strerror(socket_last_error($this->socket)));}
		socket_set_nonblock($this->socket);
	}
	
	// аккуратно отключить всех клиентов и завершить сервер.
	// (gracefully)
	public function stop()
	{
		foreach ($this->clients as $c)
		{
			@socket_shutdown($c['sock']);
			@socket_close($c['sock']);
		}
		@socket_shutdown($this->socket);
		@socket_close($this->socket);
		$this->clients = [];
		$this->socket = NULL;
	}
	
	/*	Отправить данные клиенту.
			$client - массив клиента
			$data - строка
			$opcode - опкод. Возможные значения: 'text', 'binary', 'continue', 'ping', 'pong', 'close'
			$masked - маскировать ли фрейм (обычно true)
			$fin - последний ли это фрейм в цепочке (обычно true)
	*/
	public function send($client, $data, $opcode = 'text', $masked = true, $fin = true)
	{
		$data = $this->hybi_encode($data, $opcode, $masked, $fin);
		if (!$this->cl_send($client['sock'], $data))
		{
			$this->close($client);
			return false;
		}
		return true;
	}
	
	/*	Отключить клиента.
			$client - массив клиента
	*/
	public function close($client)
	{
		unset($this->clients[$client['id']]);
		@socket_shutdown($client['sock']);
		@socket_close($client['sock']);
	}
	
	/*	Получить входящие сообщения, а также принять новые соединения.
		Эту функцию нужно периодически вызывать.
	*/
	public function check_messages()
	{
		do {
			$was_data = false;
			if ($sock = @socket_accept($this->socket))
			{
				$new = ['sock' => $sock, 'id'=> (++$this->client_cnt)];
				$hdr = '';
				do {
					$line = @socket_read($sock, 64*1024, PHP_NORMAL_READ);
					$hdr .= $line;
					if (preg_match('#(\n\n|\r\n\r\n)$#', $hdr)) break;
				} while ($line!==FALSE);
				preg_match('#\nSec-WebSocket-Key:([^\r\n]*)#i', $hdr, $m);
				preg_match('#^get\s+(\S+)#i', $hdr, $m2);
				$new['handshake'] = $hdr;
				$new['url'] = $m2[1];
				$server_key = $this->server_key(trim($m[1]));
				$data = 'HTTP/1.1 101 Switching Protocols'."\r\n".
					'Upgrade: websocket'."\r\n".
					'Connection: Upgrade'."\r\n".
					'Sec-WebSocket-Accept: '.$server_key."\r\n".
					"\r\n";
				$this->cl_send($sock, $data);
				$this->clients[$new['id']] = $new;
				if ($this->on_connect)
				{($this->on_connect)($new);}
				$was_data = true;
				@socket_set_nonblock($sock);
			}
			foreach ($this->clients as $k=>&$c)
			{
				$chunk = @socket_read($c['sock'], 64*1024);
				if (!$chunk)
				{
					$error = socket_last_error($c['sock']);
					// async-режим: когда данных нет, то $chunk===FALSE и ошибка одна из перечисленных ниже, а когда соединение оборвалось, то $chunk==='' (при этом ошибка та же).
					$is_wait = in_array($error, [SOCKET_EAGAIN, SOCKET_EINPROGRESS]);
					$is_disconnected = ($chunk==='' && $is_wait);
					if (($error && !$is_wait) || $is_disconnected)
					{
						// соединение с клиентом завершилось с ошибкой, либо он сам закрыл соединение
						$error = ($is_disconnected?'disconnected':socket_strerror($error));
						if ($this->on_error)
						{($this->on_error)($this->clients[$k], $error);}
						unset($this->clients[$k]);
						continue;
					}
				}
				if ($frame = $this->hybi_decode($chunk, $c['session']))
				{
					$was_data = true;
					if ($this->on_receive)
					{($this->on_receive)($c, $frame);}
					if ($frame['opcode']=='close')
					{
						if ($this->on_error)
						{($this->on_error)($this->clients[$k], 'disconnected');}
						$this->close($this->clients[$k]);
						continue;
					}
					elseif ($frame['opcode']=='ping')
					{$this->send($this->clients[$k], $frame['payload'], 'pong');}
				}
			}
			unset($c);
		} while ($was_data);
	}
	
	protected function cl_send($sock, $data)
	{
		$off = 0;
		do {
			$wr = socket_write($sock, substr($data, $off), strlen($data));
			if ($wr===FALSE)
			{
				$error = socket_last_error($c['sock']);
				if ($error != 11 && $error != 115)
				{
					return false;
				}
			}
			$off += $wr;
		} while ($off<strlen($data));
		return true;
	}
}
