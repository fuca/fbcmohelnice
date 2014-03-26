<?php
namespace florbalMohelnice\Entities;

/**
 * Description of rder
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class Order extends \DibiRow {
	
	const REQUEST_STATE = 'req';
	const INPROGRESS_STATE = 'inp';
	const SOLVED_STATE = 'sol';
	const CANCELED_STATE = 'cnl';
	
	public function __construct($arr) {
		parent::__construct($arr);
	}
	
	public static function getStates() {
		return array('req'=>'Čeká', 'inp'=>'Vyřizuje se', 'sol'=>'Hotovo','cls'=>'Zrušeno');
	} 
}

