<?php
/**
 * A class which implements the Source RCON Protocol
 * 
 * @author Nikki
 *
 */
class Sourcercon extends RconProtocol {
	// Sending
	const SERVERDATA_EXECCOMMAND	= 2;
	const SERVERDATA_AUTH		   = 3;
	
	// Receiving
	const SERVERDATA_RESPONSE_VALUE = 0;
	const SERVERDATA_AUTH_RESPONSE  = 2;
	
	private $requestid = 0;
	
	private $settings;
	
	private $socket = false;
	
	public function __construct($settings) {
		$this->settings = $settings;
	}
	
	public function connect() {
		$this->requestid = 0;
		if($this->socket = @fsockopen($this->settings['host'], (int) $this->settings['port'])) {
			socket_set_timeout($this->socket, isset($this->settings['timeout']) ? (int) $this->settings['timeout'] : 3);
			if(!$this->auth($this->settings['password'])) {
				$this->disconnect();
				
				throw new Exception("Authorization failed.");
			}
		} else {
			throw new Exception("Can't open socket.");
		}
	}
	
	public function disconnect() {
		if($this->socket)
		{
			fclose($this->socket);
			
			$this->socket = null;
		}
	}
	
	public function command($string) {
		if(!$this->socket) {
			$this->connect();
		}
		if(!$this->writeData(self::SERVERDATA_EXECCOMMAND, $string)) {
			return false;
		}
		
		$data = $this->readData();
		
		if($data['requestid'] < 1 || $data['response'] != self::SERVERDATA_RESPONSE_VALUE)
		{
			return false;
		}
		
		return $data['string'];
	}
	
	private function auth($password) {
		if(!$this->writeData(self::SERVERDATA_AUTH, $password)) {
			return false;
		}
		
		$data = $this->readData();
		
		if($data['response'] == self::SERVERDATA_RESPONSE_VALUE) {
			//First is junk, read a second one
			$data = $this->readData();
		}
		
		return $data['requestid'] > -1 && $data['response'] == self::SERVERDATA_AUTH_RESPONSE;
	}
	
	private function readData() {
		$resp = array('string' => '', 'string2' => '');
		
		$expected = false;
		do {
			$size = fread($this->socket, 4);
			$size = unpack('V1size', $size);
			$size = $size['size'];
			$packet = fread($this->socket, $size);
			$packet = unpack('V1requestid/V1response/a*string/a*string2', $packet);
			
			if(!isset($resp['requestid']) || !isset($resp['response'])) {
				$resp['requestid'] = $packet['requestid'];
				$resp['response'] = $packet['response'];
			}
			$resp['string'] .= $packet['string'];
			$resp['string2'] .= $packet['string2'];
			
			$expected = ($size >= 3096);
		} while($expected);
		
		return $resp;
	}
	
	private function writeData($command, $string = "") {
		// Pack the packet together
		$data = pack('VV', $this->requestid++, $command) . $string . "\x00\x00\x00"; 
		
		// Prepend packet length
		$data = pack('V', strlen($data)) . $data;
		
		$length = strlen($data);
		
		return $length === fwrite($this->socket, $data, $length);
	}
}