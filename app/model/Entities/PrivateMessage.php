<?php
namespace florbalMohelnice\Entities;

/**
 * Description of PrivateMessage
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class PrivateMessage extends \DibiRow {
	const IDENTIFIER = 'id_message';
	const STATUS_READ = 'red';
	const STATUS_UNREAD = 'unr';
	const STATUS_DELETED = 'del';
	
	public function __construct($arr) {
		parent::__construct($arr);
	}
	
	public function getId() {
		if (!$this->offsetSet(self::IDENTIFIER)) 
			throw \Nette\InvalidStateException('Message id is not set');

		return $this->offsetGet(self::IDENTIFIER);
	}
}

