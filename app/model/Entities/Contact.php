<?php
namespace florbalMohelnice\Entities;

/**
 * Description of Contact
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class Contact extends \DibiRow {

	const STATUS_PERMITTED	= 'ok';
	const STATUS_REJECTED	= 'nok';
	const STATUS_PENDING	= 'noy';
	
	public function __construct($arr) {
		parent::__construct($arr);
	}
	
	public static function getSelectStatus() {
	    return array(
		self::STATUS_PERMITTED	=> 'Schválen', 
		self::STATUS_REJECTED	=> 'Opravit', 
		self::STATUS_PENDING	=> 'Čeká');
	}
}
