<?php
namespace florbalMohelnice\Entities;

/**
 * Description of Group
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class Group extends \DibiRow {

	const GENERAL_ABBR = 'fbc';
        const COLUMN_ABBR = 'abbr';
        const COLUMN_ID = 'id_group';
	
	public function __construct($arr) {
		parent::__construct($arr);
	}
}
