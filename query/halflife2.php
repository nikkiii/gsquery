<?php
/**
 * A GSQuery subclass which implements support for Halflife 2 servers
 * Supported:
 * - Team Fortress 2
 * - Left 4 Dead 2
 * - Counter Strike: Source (UNTESTED)
 * - HL2:DM (UNTESTED)
 * 
 * @author Nikki
 *
 */
class Halflife2 extends GSQuery_Parent {
	
	private $queryproto;
	private $rconproto;
	
	public function __construct($settings) {
		$settings += array(
			'host' => 'localhost',
			'port' => 27015
		);
		$this->queryproto = $this->initializeProtocol('sourcequery', $settings);
		if(!empty($settings['rcon'])) {
			$this->copySettings($settings, $settings['rcon'], array('host', 'port'));
			$this->rconproto = $this->initializeProtocol('sourcercon', $settings['rcon']);
		}
	}
	
	public function queryInfo() {
		if(!$this->queryproto) {
			throw new Exception('Query protocol not initialized!');
		}
		return $this->queryproto->queryInfo();
	}
	
	public function queryPlayers() {
		if(!$this->queryproto) {
			throw new Exception('Query protocol not initialized!');
		}
		return $this->queryproto->queryPlayers();
	}
	
	public function queryRules() {
		$this->queryproto->queryRules();
	}
	
	public function queryRcon($command) {
		if(!$this->rconproto) {
			throw new Exception('RCon protocol not initialized!');
		}
		return $this->rconproto->command($command);
	}
}