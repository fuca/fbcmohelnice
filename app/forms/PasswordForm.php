<?php
namespace florbalMohelnice\Forms;;
use \Nette\Application\UI\Form,
	\florbalMohelnice\Miscellaneous\Authenticator;

/**
 * Description of PasswordForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class PasswordForm extends Form {
	
	/** @var old password hash */
	private $oldPass;
	/** @var password salt key */
	private $salt;
	
	public function setOldPassword($pass) {
		$this->oldPass = $pass;
	}
	
	public function setSalt($salt) {
		$this->salt = $salt;
	}
	
	public function getOldPassword() {
		if (!isset($this->oldPass)) throw new \Nette\InvalidStateException('Old password has to be set');
		return $this->oldPass;
	}
	
	public function getSalt() {
		if (!isset($this->salt)) throw new \Nette\InvalidStateException('Salt hash has to be set');
		return $this->salt;
	}
	
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, $oldPass, $salt) {
		parent::__construct($parent, $name);
		
		$this->setOldPassword($oldPass);
		$this->setSalt($salt);
		
		$this->addText('old_password', 'Staré heslo')
			->addRule(Form::FILLED, 'Zadejte prosím staré heslo');
			
		$this->addText('new_password', 'Nové heslo')
			->addRule(Form::FILLED, 'Zadejte prosím nové heslo');
		
		$this->addText('new_password2', 'Nové znovu')
			->addRule(Form::FILLED, 'Zadejte prosím nové heslo znovu');
		
		$this->addSubmit('submitButton', 'Změnit');
		$this->onSuccess[] = callback($this, 'passwordFormSubmitted');
	}
	
	public function passwordFormSubmitted(Form $f) {
		$values = $f->getValues();
		$enteredOldPass = $values['old_password'];
		$new1 = $values['new_password'];
		$new2 = $values['new_password2'];
		try {
			$enteredOldHashed = Authenticator::calculateHash($enteredOldPass, $this->getSalt());
		
			if ($this->getOldPassword() != $enteredOldHashed) {
				$this->addError ('Staré heslo bylo zadáno chybně');
				return;
			}
			if ($new1 != $new2) {
				$this->addError ('Nová hesla se neshodují');
				return;
			}		
		} catch (\Nette\InvalidStateException $ex) {
			$this->presenter->flashMessage('Chyba, heslo nelze změnit. Zkuste to prosím později', 'error');
			\Nette\Diagnostics\Debugger::log($ex->getMessage(), \Nette\Diagnostics\Debugger::ERROR);
			$this->presenter->redirect('User:data');
		}
		$this->presenter->changePassword(Authenticator::calculateHash($new1, $this->getSalt()));
	}
}

