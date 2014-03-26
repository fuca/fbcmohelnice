<?php
namespace florbalMohelnice\Entities;

/**
 * Description of Forum
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class Forum extends \DibiRow {

	const GENERAL_ABBR = 'fbc';
	
	const COMMENTS_OFF = 'off';
	const COMMENTS_LOGGED = 'log';
	const COMMENTS_PUBLIC = 'pub';
	
	public function __construct($arr) {
		parent::__construct($arr);
	}
	
	/**
	 *
	 */
	public static function getCommModes() {
		return array( 
				self::COMMENTS_OFF => 'Vypnuty',
				self::COMMENTS_LOGGED => 'Přihlášení',
				self::COMMENTS_PUBLIC => 'Veřejné');
	}
}
