<?php
namespace florbalMohelnice\Models;
/**
 * Description of BaseModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
abstract class BaseModel extends \Nette\Object {
	
	/** @var DibiConnection */
	private $connection;
	
	public function __construct(\DibiConnection $conn) {
		$this->connection = $conn;
	}
	
	public function getConnection() {
		return $this->connection;
	}
	
	abstract protected function getFluent();
	
	protected function begin() {
		return $this->getConnection()->begin();
	}
	
	protected function commit() {
		return $this->getConnection()->commit();
	}
	
	protected function rollback() {
		return $this->getConnection()->rollback();
	}	
}

