<?php
namespace florbalMohelnice\Entities;

/**
 * Description of Article
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class Article extends \DibiRow {

	const STATUS_PUBLISHED = 'pub';
	const STATUS_CONCEPT = 'con';
	const STATUS_PENDING = 'pen';
	const STATUS_CORRECTION = 'cor';
	
	const COMMENTS_OFF = 'off';
	const COMMENTS_LOGGED = 'log';
	const COMMENTS_PUBLIC = 'pub';
	const COMMENTS_GROUP = 'grp';
	
	public function __construct($arr) {
		parent::__construct($arr);
	}
	
	public function getId() {
		if (!$this->offsetGet('id_article')) 
			throw new InvalidStateException('Argument id has to be set');
		return $this->offsetGet('id_article');
	}
	
	/**
	 *
	 */
	 public static function getSelectCommModes() {
	 	return array(self::COMMENTS_LOGGED 	=> 'Přihlášení',
	 				 self::COMMENTS_OFF 	=> 'Zakázány',
					 self::COMMENTS_PUBLIC 	=> 'Veřejné',
					 self::COMMENTS_GROUP 	=> 'Skupina');	
	 }
	 
	 /**
	  *
	  */
	 public static function getStatusModes() {
	 	return array (self::STATUS_CONCEPT 		=> 'Rozepsaný',
	 				  self::STATUS_PUBLISHED 	=> 'Zveřejněno',
					  self::STATUS_PENDING 		=> 'Čeká na schválení',
					  self::STATUS_CORRECTION 	=> 'Opravit');
	 }
}
