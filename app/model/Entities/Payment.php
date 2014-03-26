<?php
namespace florbalMohelnice\Entities;
/**
 * Description of Payment
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class Payment extends \DibiRow {
	
	const STATUS_UNPAYED	= 'unp';
	const STATUS_PAY_CASH	= 'pac';
	const STATUS_PAY_ACC	= 'paa';
    
	public function __construct($arr) {
		parent::__construct($arr);
	}
	
	public static function getStates() {
	    return array(self::STATUS_UNPAYED => 'Ne', self::STATUS_PAY_CASH => 'Hotově', self::STATUS_PAY_ACC => 'Účet');
	}
}

