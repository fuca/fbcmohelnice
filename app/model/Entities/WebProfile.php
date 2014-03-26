<?php
namespace florbalMohelnice\Entities;
/**
 * Description of WebProfile
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class WebProfile extends \DibiRow {
    
	const USER_PROFILE_CONFIRMED = 'con';
	
	const USER_PROFILE_EDITED = "eee";
	const USER_PROMPT_EDITED = "Váš profil čeká na schválení";
	
	const USER_PROFILE_REQUIRED = "req";
	const USER_PROMPT_REQUIRED = "Vyplň si svůj profil, prosím";
	
	const USER_PASS_CHANGE_REQUIRED = 'res';
	const USER_PASS_PROMPT = "Změňte si prosím své heslo";
	
	public function __construct($arr) {
		parent::__construct($arr);
	}
}

