<?php
namespace florbalMohelnice\Entities;

/**
 * Description of WallPost
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class WallPost extends \DibiRow {

	const STATUS_PUBLISHED = 'pub';
	const STATUS_CONCEPT = 'con';
	
	const COMMENTS_OFF = 'off';
	const COMMENTS_LOGGED = 'log';
	const COMMENTS_PUBLIC = 'pub';
	
	public function __construct($arr) {
		parent::__construct($arr);
	}
	
	public function getId() {
		if (!$this->offsetGet('id_wallpost')) 
			throw new InvalidStateException('Argument id has to be set');
		return $this->offsetGet('id_wallpost');
	}
	
	/**
	 *
	 */
	 public static function getSelectCommModes() {
	 	return array(self::COMMENTS_LOGGED 	=> 'Přihlášení',
	 				 self::COMMENTS_OFF 	=> 'Zakázány',
					 self::COMMENTS_PUBLIC 	=> 'Veřejné');	
	 }
}
