<?php

namespace florbalMohelnice\Models;

use florbalMohelnice\Entities\User,
    florbalMohelnice\Entities\WebProfile,
    \Nette\Mail\Message;

/**
 * Description of UserModel;
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class UserModel extends BaseModel {

    /**
     *
     */
    public function getSelectUsers($kid = NULL, $self = TRUE) {
	if ($kid != NULL && !is_numeric($kid))
	    throw new \Nette\InvalidArgumentException("Argument has to be a type of numeric");

	$result = $this->connection
			->select('[kid], CONCAT(surname, \' \', name, \' (\', kid, \')\') AS author')
			->from('[Users]')->orderBy('surname')
			->execute()->fetchPairs();

	if ($kid != NULL && $result && $self)
	    unset($result[$kid]);
	return $result;
    }

    /**
     * Returns record of one user.
     * @param integer $kid
     * @throws \InvalidArgumentException
     * @return \Nette\Security\Identity
     */
    public function getUserByKid($kid) {
	if (!is_integer($kid))
	    throw new \InvalidArgumentException("Argument has to be an integer KID $kid given.");
	try {
	    $groupsModel = new GroupsModel($this->connection);
	    $result = $this->connection->select("[*]")->from("[Users]")
		    ->leftJoin('Contacts')->using('(kid)')
		    ->where('kid = %i', $kid)
		    ->execute()->setRowClass('florbalMohelnice\Entities\User')
		    ->fetch();
	    if ($result === FALSE)
		throw new \Nette\Security\AuthenticationException("User with kid '$kid' not found");
	    $result->roles = $this->getUserRoles($kid);
	    $result->categories = $groupsModel->getUserGroups($result);
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage(), $ex);
	}
	return $result;
    }

    public function getFluent() {
	return $this->connection->select('*')->from('Users');
    }

    public function getFluentContacts($kid = NULL) {
	if (!is_numeric($kid) && $kid != NULL)
	    throw new \Nette\InvalidArgumentException('Argument has to be a type of numeric');
	try {
	    $result = $this->connection->select('*')
		    ->from('Users')->leftJoin('Contacts')
		    ->using('(kid)');
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	    return;
	}
	return $kid != NULL ? $result->where('Users.kid = %i', $kid) : $result;
    }

    /**
     * Update info about last login.
     * @param integer $kid
     */
    public function setLastLogged($kid) {
	if (!is_numeric($kid))
	    throw new \InvalidArgumentException("Argument has to be numeric KID $kid given.");
	return $this->connection->update("Users", array('last_logged' => date('Y-m-d G:i:s')))
			->where('[kid] = %i', $kid)->execute();
    }

    public function getUserRoles($kid) {

//		$result = $this->connection->select("id_role, [role_name]")->from("[Users]")
//				->innerJoin("[relUserInRole]")->using("(kid)")
//				->innerJoin("[Roles]")->using("(id_role)")
//				->where("kid = %i", $kid)
//				->execute()->fetchAssoc('id_role');

	$result = $this->connection->select("[id_role],[role_name]")->from("[relUserInRole]")
			->innerJoin('[Roles]')->using("(id_role)")
			->where("kid = %i", $kid)->execute()->fetchPairs();
	return $result;
    }

    public function getAllRoles() {
	return $this->connection->select('*')->from('Roles')->execute()->fetchPairs();
    }

    /** Add user data from UserForm to database */
    public function createUser(User $u) {

	$groupsModel = new GroupsModel($this->connection);

	$usersTable = array(
	    'name' => $u->name,
	    'surname' => $u->surname,
	    'birth_number' => $u->birth_number,
	    'cfbu_number' => $u->cfbu_number,
	    'nick' => $u->nick,
	    'email' => $u->email,
	    'password' => $u->password,
	    'last_logged' => date("Y-m-d H:i:s", 0),
	    'year' => $this->birthNumToYear($u->birth_number));
	
	if ($u->offsetExists('activity'))
	    $usersTable['activity'] = $u->offsetGet('activity');
	if ($u->offsetExists('profile_required'))
	    $usersTable['profile_required'] = $u->offsetGet('profile_required');
	if ($u->offsetExists('password_status'))
	    $usersTable['password_status'] = $u->offsetGet('password_status');

	$roles = $u->offsetGet('roles');
	$categories = $u->offsetGet('categories');

	$contactTable = array(
	    'address' => $u->address,
	    'city' => $u->city,
	    'postal_code' => $u->postal_code,
	    'phone' => $u->phone,
	    'job' => $u->job,
	    'contperson_name' => $u->contperson_name,
	    'contperson_phone' => $u->contperson_phone,
	    'contperson_email' => $u->contperson_email,
	    'last_updated' => date("Y-m-d H:i:s", 0),);

	if ($this->userExistp($u)) {
	    throw new \Nette\InvalidArgumentException("Uživatel s rodným číslem $u->birth_number už existuje.");
	}

	try {
	    $this->connection->insert('Users', $usersTable)->execute();
	    $kid = $this->connection->insertId();
	    $this->connection->insert('Contacts', array_merge($contactTable, array('kid' => $kid)))
		    ->execute();
	    $this->connection->insert('WebProfiles', array('kid' => $kid))->execute();
	    // $this->connection->insert('Contacts', array('kid' => $kid))->execute(); pro to tady bylo????

	    foreach ($roles as $r) {
		$this->connection->insert('relUserInRole', array('kid' => $kid, 'id_role' => $r))
			->execute();
	    }
	    $groupsModel->addUserGroups($u);
	} catch (DibiException $ex) {
	    throw new Nette\IOException('Something went wrong during creating new user. -- ' . $ex->getMessage());
	}
    }

    /** User exist predicate acc birth_number */
    public function userExistp(User $u) {
	return $this->connection->select('kid')->from('Users')
			->WHERE('birth_number = %s', $u->birth_number)
			->execute()->fetchSingle() === FALSE ? FALSE : TRUE;
    }

    private function birthNumToYear($bn) {
	$d = substr($bn, 0, 1);
	$y = substr($bn, 1, 1);
	if ($d == 0) {
	    return "200" . $y;
	} else {
	    return "19" . $d . $y;
	}
    }
    
    private function extractUserAndContact(User $iUser) {
	$oUser = array(
	    'name' => $iUser->name,
	    'surname' => $iUser->surname,
	    'birth_number' => $iUser->birth_number,
	    'cfbu_number' => $iUser->cfbu_number,
	    'nick' => $iUser->nick,
	    'email' => $iUser->email,
	    'last_logged' => date("Y-m-d H:i:s", 0),
	    'year' => $this->birthNumToYear($iUser->birth_number));
	
	    if($iUser->offsetExists('profile_required'))
		$oUser['profile_required'] = $iUser->offsetGet('profile_required');
	    if($iUser->offsetExists('activity'))
		$oUser['activity'] = $iUser->offsetGet('activity');
	    if($iUser->offsetExists('password_status'))
		$oUser['password_status'] = $iUser->offsetGet('password_status');
	    
	$oKid = $iUser->kid;

	$oContact = array(
	    'address' => $iUser->address,
	    'city' => $iUser->city,
	    'postal_code' => $iUser->postal_code,
	    'phone' => $iUser->phone,
	    'job' => $iUser->job,
	    'contperson_name' => $iUser->contperson_name,
	    'contperson_phone' => $iUser->contperson_phone,
	    'contperson_email' => $iUser->contperson_email,
	    'last_updated' => date("Y-m-d H:i:s", time()));
	
	return array($oKid, $oUser, $oContact);
    }

    public function updateUserAndContact(User $u) {
	
	$resultX = $this->extractUserAndContact($u);
	$usersTable = $resultX[1];
	$kid = $resultX[0];
	$contactTable = $resultX[2];
	
	try {
	    $this->connection->update('Users', $usersTable)
		    ->where('kid = %i', $kid)->execute();

	    $this->connection->update('Contacts', $contactTable)
		    ->where('kid = %i', $kid)->execute();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage(), $ex->getCode(), $ex);
	} catch (DibiDriverException $ex) {
	    throw new \Nette\IOException($ex->getMessage(), $ex->getCode(), $ex);
	}
    }

    public function updateUser(User $u, $admin = FALSE) {
	$groupsModel = new GroupsModel(($this->connection));

	$resultX = $this->extractUserAndContact($u);
	$usersTable = $resultX[1];
	$kid = $resultX[0];
	$contactTable = $resultX[2];
	
	try {

	    $this->connection->update('Users', $usersTable)
		    ->where('kid = %i', $kid)->execute();

	    $this->connection->delete('relUserInRole')->where('kid = %i', $kid)
		    ->execute();
	    if ($u->offsetExists('roles')) {
		$newRoles = $u->offsetGet('roles');
		foreach ($newRoles as $r) {
		    $this->connection->insert('relUserInRole', array('kid' => $kid, 'id_role' => $r))
			    ->execute();
		}
	    }
	    $this->connection->update('Contacts', $contactTable)
		    ->where('kid = %i', $kid)->execute();
	    if ($admin) {
		$groupsModel->removeUserGroups($u);
		$groupsModel->addUserGroups($u);
	    }
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage(), $ex);
	}
    }

    public function toggleActivity($kid) {
	$act = $this->connection->select('activity')->from('Users')->where('kid = %i', $kid)->execute()->fetchSingle();
	switch ($act) {
	    case 'act':
		$this->setActivityTo($kid, 'una');
		break;
	    case 'una':
		$this->setActivityTo($kid, 'act');
		break;
	    default:
		throw new \Nette\InvalidStateException("Database is inconsistent, check activity column in Users table.");
	}
    }

    private function setActivityTo($kid, $to) {
	return $this->connection->update('Users', array('activity' => $to))
			->where('kid = %i', $kid)->execute();
    }

    /* ======= Web profiles =================================================== */

    public function getWebProfilesFluent($kid = NULL) {
	if ($kid != NULL && !is_numeric($kid))
	    throw new \Nette\InvalidArgumentException('Argument has to be a type of numeric');
	$result = $this->connection->select('*')->from('WebProfiles');
	if ($kid != NULL)
	    $result = $result->where('kid = %i', $kid);
	return $result;
    }

    public function updateWebProfile(WebProfile $wp, $admin = FALSE) {
	$kid = $wp->offsetGet('kid');
	$wp->offsetUnset('kid');
	try {
	    $this->connection->update('WebProfiles', $wp)->where('kid = %i', $kid)->execute();
	    if (!$admin)
		$this->connection->update('Users', array('profile_required' => WebProfile::USER_PROFILE_EDITED))->where('kid = %i', $kid)->execute();
	} catch (\DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
    }

    public function webProfilesForCheckFluent() {

	$res = $this->connection->select('*')->from('WebProfiles')
		->innerJoin($this->connection->select('kid, name, surname, nick, profile_required')->from('Users'))->as('users')->using('(kid)')
		->where('profile_required = %s', 'eee');
	return $res;
    }

    public function permitWebProfile($kid) {
	try {
	    $this->connection->update('Users', array('profile_required' => WebProfile::USER_PROFILE_CONFIRMED))->where('kid = %i', $kid)->execute();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
    }
    
    public function rejectWebProfile($kid) {
	try {
	    $this->connection->update('Users', array('profile_required' => WebProfile::USER_PROFILE_REQUIRED))->where('kid = %i', $kid)->execute();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
    }

    public function updatePassword(array $users) {

	foreach ($users as $u) {
	    if (!($u instanceof User))
		throw new \Nette\InvalidArgumentException('One of array elements is not type of User');

	    $pw = $u->offsetGet('password');
	    $email = $u->offsetGet('email');
	    $kid = $u->offsetGet('kid');
	    try {
		$this->connection->update('Users', array('password' => $pw, 'password_status'=> 'ok'))->where('kid = %i', $kid)->execute();
	    } catch (DibiException $ex) {
		throw new \Nette\IOException($ex->getMessage());
		return;
	    }

	    $messageBody = "Dobrý den, \n\n
							změna hesla proběhla úspěšně. Vaše nové heslo pro vstup do ISU FBC Mohelnice (http://fbcmohelnice.cz/login) je $pw. \n
							Přihlašovací jméno je stále Vaše klubové identifikační číslo ($kid). \n\n\n\n
							----------------------------------------------------- \n
							Na tento mail prosím neodpovídejte.";

	    $msg = new Message('no-reply <webmaster@fbcmohelnice.cz>');
	    $msg->addTo($email);
	    $msg->setSubject('Změna hesla');
	    $msg->setBody($messageBody);
	    $msg->send();
	}
    }

}

