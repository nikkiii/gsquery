<?php
/**
 * Gamespy 4 protocol, specifically for Minecraft
 * Note: Some code was referenced from xPaw's implementation, so most of the credit goes ot him.
 * 
 * @author Nikki
 * @author xPaw
 *
 */
class Gamespy4 extends QueryProtocol {
	
	const STATISTIC = 0x00;
	const HANDSHAKE = 0x09;
	
	private $socket;
	
	private $challenge;
	
	private $settings;
	
	public function __construct($settings) {
		$settings += array(
			'host' => 'localhost',
			'port' => 25565,
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
		
		try {
	   		$this->challenge = $this->getChallenge();
		} catch(Exception $e) {
			echo $e->getMessage();
			
			$this->disconnect();
		}
	}
	
	public function disconnect() {
		if($this->socket) {
			fclose($this->socket);
			$this->socket = false;
		}
	}
	
	private function getChallenge() {
		$data = $this->writeData(self::HANDSHAKE);
		
		if ($data === false) {
			throw new Exception("Failed to receive challenge.");
		}
		
		return pack('N', $data);
	}
	
	public function queryInfo() {
		if(!$this->socket || feof($this->socket)) {
			$this->connect();
		}
		$data = $this->writeData(self::STATISTIC, $this->challenge . pack('c*', 0x00, 0x00, 0x00, 0x00));
		
		if (!$data) {
			throw new Exception("Failed to receive status.");
		}
		
		$last = "";
		$info = new stdClass;
		
		$data	= substr($data, 11); // splitnum + 2 int
		$data	= explode("\x00\x00\x01player_\x00\x00", $data);
		$players = substr($data[1], 0, -2);
		$data	= explode("\x00", $data[0]);
		
		foreach ($data as $key => $value) {
			if (~$key & 1) {
				$last = $value;
				$info->$value = "";
			} else if ($last != false) {
				$info->$last = $value;
			}
		}
		
		$info->players = $players ? explode("\x00", $players) : array();
		
		return $info;
	}
	
	private function writeData($command, $append = "") {		
		$command = pack('c*', 0xFE, 0xFD, $command, 0x01, 0x02, 0x03, 0x04) . $append;
		$length  = strlen($command);
		
		if ($length !== fwrite($this->socket, $command, $length)) {
			throw new Exception("Failed to write on socket.");
		}
		
		$data = fread($this->socket, 2048);
		
		if ($data === false) {
			throw new Exception("Failed to read from socket.");
		}
		
		if (strlen($data) < 5 || $data[0] != $command[2]) {
			return false;
		}
		
		return substr($data, 5);
	}
}