<?php
require_once dirname(dirname(__FILE__)) . '/buffer.php';

class Sourcequery extends QueryProtocol {
	private $socket;
	private $settings;
	
	private $challenge = false;
	
	public function __construct($settings) {
		$settings += array(
			'timeout' => 3
		);
		$this->settings = $settings;
	}
	
	public function connect() {
		$this->socket = @fsockopen('udp://' . $this->settings['host'], (int) $this->settings['port'], $errNo, $errStr, $this->settings['timeout']);
		
		if ($errNo || $this->socket === false) {
			throw new Exception('Could not create socket: ' . $errStr);
		}
		
		stream_set_timeout($this->socket, $this->settings['timeout']);
		stream_set_blocking($this->socket, true);
	}
	
	public function disconnect() {
		fclose($this->socket);
		$this->socket = false;
	}
	
	public function queryInfo() {
		
		$query = 'TSource Engine Query' . pack('x');
		
		$data = $this->sendQuery($query);
		
		$buffer = new Buffer($data);
		$buffer->skip(5);
		
		$out = new stdClass();
		$out->protocol = $buffer->getByte();
		$out->name = $buffer->getString();
		$out->map = $buffer->getString();
		$out->gamedirectory = $buffer->getString();
		$out->gamename = $buffer->getString();
		$out->appid = $buffer->getShort();
		$out->totalplayers = $buffer->getByte();
		$out->maxplayers = $buffer->getByte();
		$out->maxbots = $buffer->getByte();
		$out->servertype = $buffer->getChar();
		$out->serveros = $buffer->getChar();
		$out->serverlocked = $buffer->getByte() ? true : false;
		$out->serversecure = $buffer->getByte() ? true : false;
		
		if($out->gamedirectory == 'ship') {
			$out->gamemode = $buffer->getByte();
			$out->witnesscount = $buffer->getByte();
			$out->witnesstime = $buffer->getByte();
		}
		
		$out->gameversion = $buffer->getString();
		
		return $out;
	}
	
	public function queryPlayers() {
		$query = 'U' . pack('V', $this->getChallenge());
		
		//Send the query and return the data into the Buffer
		$buffer = new Buffer($this->sendQuery($query));
		$buffer->skip(5);
		
		//Parse the data
		$out = new stdClass();
		$slots = $buffer->getByte();
		$out->players = array();
		for ($i = 0; $i < $slots; $i++) {
			$id = $buffer->getByte();
			$name = $buffer->getString();
			if (empty($name)) continue;
			$out->players[] = array(
				'id' => $id == 0 ? $i+1 : $id,
				'name' => $name,
				'kills' => $buffer->getInteger(),
				'onlinetime' => (int) $buffer->getFloat()
			);
		}
		$out->activeplayers = count($out->players);
		
		return $out;
	}
	
	public function queryRules() {
		$query = 'i' . pack('V', $this->getChallenge());
		
		$buffer = new Buffer($this->sendQuery($query));
		$buffer->skip(5);
		
		$count = $buffer->getShort();
		for($i = 0; $i < $count; $i++) {
			$key = trim($buffer->getString());
			$value = trim($buffer->getString());
			if(!empty($key) && !empty($value))
			echo "$key: $value\n";
		}
	}
	
	private function getChallenge() {
		if(!$this->challenge) {
			$buffer = new Buffer($this->sendQuery('U' . pack('V', -1)));
			$buffer->skip(5);
			$this->challenge = $buffer->getInteger();
			$buffer->close();
		}
		return $this->challenge;
	}
	
	private function sendQuery($data) {
		$this->connect();
		
		if(!$this->writeData($data)) {
			return false;
		}
		
		$resp = $this->readData();
		
		$this->disconnect();
		
		return $resp;
	}
	
	private function readData() {
		$compressed = false;
		$packets = array();
		$expected = 0;
		do {
			$packet = fread($this->socket, 1500);
			
			$header = substr($packet, 0, 4);
			$ack = @unpack(PHP_INT_SIZE == 4 ? 'V' : 'i', $header);
			$split = $ack[1];
			if ($split == -2) {
				$packet = substr($packet, 4);
				$header = substr($packet, 0, 4);
				$packet = substr($packet, 4);

				$pnum = substr($packet, 0, 2);
				$packet = substr($packet, 2);
				$requestid = unpack('Vreqid', $header);
				$requestid = $requestid['reqid'];
				
				$compressed = false;
				
				$short = unpack("vshort", $pnum);
				$short = $short['short'];
				if (!$expected) $expected = $short & 0x00FF;
				$seq = $short >> 8;
				
				if (seq == 0) {
					$compressed = ($requestid >> 31 & 0x01 == 1);
					if ($compressed) {
						$header = substr($packet, 0, 8);
						$packet = substr($packet, 8);
						$info = @unpack("V1total/V1crc", $header);
						$uncompressed_total = $info['total'];
						$uncompressed_crc = $info['crc'];
					}
				}
				$packets[$seq] = $packet;
				$expected--;
			} elseif ($split == -1) {
				$packets[0] = $packet;
				$expected = 0;
			}
		} while ($expected);
		
		ksort($packets, SORT_NUMERIC);

		if ($compressed) {
			$raw = bzdecompress(implode('', $packets));
			$crc = crc32($raw);
			if ($crc != $uncompressed_crc) {
				return false;
			}
			return $raw;
		}
		return implode('', $packets);
	}
	
	private function writeData($command) {
		// Pack the packet together
		$data = pack('V', -1) . $command;
		
		$length = strlen($data);
		
		return $length === fwrite($this->socket, $data, $length);
	}
}