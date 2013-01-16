<?php
require_once 'protocol.php';

class GSQuery {
	function create($type, $info) {
		$className = ucfirst($type);
		$file = dirname(__FILE__) . '/query/' . $type . '.php';
		if(file_exists($file)) {
			require_once $file;
			$instance = new $className($info);
			return $instance;
		}
		return false;
	}
}

/**
 * Subclass parent (Which handles the calls going to the protocol class)
 * 
 * @author Nikki
 *
 */
class GSQuery_Parent {
	
	public function queryInfo() {
		error_log('GSQuery subtype does not support queryInfo()');
	}
	
	public function queryPlayers() {
		error_log('GSQuery subtype does not support queryPlayers()');
	}
	
	public function queryRcon($command) {
		error_log('GSQuery subtype does not support queryRcon()');
	}
	
	/**
	 * Initialize a protocol
	 * @param string $name The protocol name (File name)
	 * @param array $data The protocol settings
	 */
	protected function initializeProtocol($name, $data) {
		$className = ucfirst($name);
		$file = dirname(__FILE__) . '/protocol/' . $name . '.php';
		if(file_exists($file)) {
			require_once $file;
			$instance = new $className($data);
			return $instance;
		}
		throw new Exception("Invalid protocol : " . $name);
	}
	
	/**
	 * Copy settings from one array to another
	 * @param array $arr The source
	 * @param array $target The target
	 * @param array $keys The keys to copy
	 */
	protected function copySettings($arr, &$target, $keys) {
		foreach($keys as $key) {
			if(isset($arr[$key]) && empty($target[$key])) {
				$target[$key] = $arr[$key];
			}
		}
	}
}
?>