<?php
/**
 * A simple buffer for reading standard data types in PHP
 * 
 * @author Nikki
 *
 */
class Buffer {
	
	/**
	 * The data object (String in this case)
	 */
	private $data;
	
	public function __construct($data) {
		$this->data = $data;
	}
	
	public function getByte() {
		$byte = substr($this->data, 0, 1);
		$this->skip(1);
		return ord($byte);
	}
	
	public function getChar() {
		return sprintf("%c", $this->getByte());
	}
	
	public function getShort() {
		$lo = $this->getByte();
		$hi = $this->getByte();
		$short = ($hi << 8) | $lo;
		return $short;
	}
	
	public function getInteger() {
		$lo = $this->getShort();
		$hi = $this->getShort();
		$long = ($hi << 16) | $lo;
		return $long;
	}
	
	public function getFloat() {
		$f = @unpack("f1float", $this->data);
		$this->skip(4);
		return $f['float'];
	}
	
	public function getString() {
		$end = strpos($this->data, "\0");
		$str = substr($this->data, 0, $end);
		$this->skip($end + 1);
		return $str;
	}
	
	public function skip($size) {
		$this->data = substr($this->data, $size);
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function close() {
		unset($this->data);
	}
}