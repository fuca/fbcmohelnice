<?php
namespace florbalMohelnice\Entities;

/**
 * Description of User
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class User extends \DibiRow {
	
	public function __construct($arr) {
		parent::__construct($arr);
	}
}

