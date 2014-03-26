<?php
namespace florbalMohelnice\Entities;

/**
 * Description of Comment
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class Comment extends \DibiRow {
	
	const WALLPOST_TYPE = 'wpo' ;
	const STATIC_TYPE = 'spa';
	const EVENT_TYPE = 'eve';
	const ARTICLE_TYPE = 'art';
	const USER_TYPE = 'use';
	const PHORUM_TYPE = 'pho';
	const PARTY_TYPE = 'par';

	public function __construct($arr) {
		parent::__construct($arr);
	}
}
