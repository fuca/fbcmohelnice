<?php

namespace florbalMohelnice\Miscellaneous;

use Nette\Object,
    Nette\Security\Identity,
    Nette\Security\IAuthenticator,
    Nette\Security\AuthenticationException,
   Nette\Utils\Strings;

/**
 * @author Michal Fucik
 * @package florbalMohelnice
 */

final class Authenticator extends Object implements IAuthenticator {
	
	/** @var Models\UserModel */
	private $model;
	
	/** @var Salt */
	private $salt;
	
	public function __construct(\florbalMohelnice\Models\UserModel $userModel, $salt) {
		
		$this->model = $userModel;
		$this->salt = $salt;
	}
	
	/**
	 * @param Credentials  Prihlasovaci udaje.
	 * @throws AuthenticationException Chyba v overeni udaju.
	 * @return Identitu uzivatele.
	 */
	public function authenticate(array $credentials) {
		
		list($username, $password) = $credentials;
		$saltedHash = $this->calculateHash($password, $this->salt);
		
		try {
			$user = $this->model->getUserByKid($username);
		} catch (\Nette\ArgumentOutOfRangeException $ex) {
			throw new AuthenticationException($ex->getMessage(), self::IDENTITY_NOT_FOUND);
        }
	if ($user->activity == 'una') {
	    $name = "(" . $user->kid . ")";
	    throw new AuthenticationException("Uživatel $name již není aktivním členem sdružení. Kontaktujte prosím sekretáře klubu.");
	}
        if ($user->password != $saltedHash) 
            throw new AuthenticationException('Špatné heslo', self::INVALID_CREDENTIAL);
        
		
		$data = array(
					'nick'=>$user->nick,
					'name'=>$user->name,
					'surname'=>$user->surname,
					'year'=>$user->year,
					'activity'=>$user->activity,
					'email'=>$user->email,
					'cfbu_number'=>$user->cfbu_number,
					'profile_required'=>$user->profile_required,
					'password_status'=>$user->password_status,
					'password'=>$user->password,
					'last_logged'=>$user->last_logged);
		
		$this->model->setLastLogged($user->kid);
		$roles = $this->model->getUserRoles($user->kid);
		
		$identity = new Identity($user->kid, $roles, $data);
		
		return $identity;
	}
	
	/**
	 * Computes salted password hash.
	 * @param  string
	 * @return string
	 */
	public static function calculateHash($password, $salt = NULL) {
		$password = Strings::lower($password);
		return crypt($password, $salt != NULL ?: '$2a$07$' . Strings::random(22));
	}
	
}

