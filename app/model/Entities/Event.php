<?php
namespace florbalMohelnice\Entities;
/**
 * Description of Event
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class Event extends \DibiRow {
	const PARTIC_NOMINATION = 'nom';
	const PARTIC_VOLUNTARY 	= 'vol';
	const PARTIC_COMPULSORY = 'com';

	const VISIBILITY_GROUP = 'grp';
	const VISIBILITY_OTHER = 'oth';
	
	const COMMENTS_OFF = 'off';
	const COMMENTS_LOGGED = 'log';
	const COMMENTS_GROUP = 'grp';
	
	public function __construct($arr) {
		parent::__construct($arr);
	}
	
	/**
	 *
	 */
	public static function getParticipationModes() {
		return array(
				self::PARTIC_NOMINATION => 'Nominace',
				self::PARTIC_VOLUNTARY 	=> 'Dobrovolné',
				self::PARTIC_COMPULSORY => 'Povinné');
	}
	
	/**
	 *
	 */
	public static function getVisibilityModes() {
		return array(
				self::VISIBILITY_GROUP => 'Skupina',
				self::VISIBILITY_OTHER => 'Všichni');
	}
	
	/**
	 *
	 */
	 public static function getCommModes() {
	 	return array(self::COMMENTS_LOGGED 	=> 'Přihlášení',
	 				 self::COMMENTS_OFF 	=> 'Zakázány',
					 self::COMMENTS_GROUP 	=> 'Skupina');	
	 }
}

