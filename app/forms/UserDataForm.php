<?php
namespace florbalMohelnice\Forms;
use Nette,
	Nette\Application\UI\Form,
	florbalMohelnice\Entities\User;
/**
 * Description of UserForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class UserDataForm extends \Nette\Application\UI\Form {	
	
	const PHONE_NO_LENGTH = 9;
	const EMAIL_LENGTH = 32;
	const NAME_LENGTH = 32;
	const SURNAME_LENGTH = 32;
	
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL) {
		parent::__construct($parent, $name);
		
		$this->addHidden('kid');
		$this->addSubmit('submitButton','Uložit');
		$this->addGroup('Úprava dat');
		
		$this->addText('name', 'Jméno', self::NAME_LENGTH, self::NAME_LENGTH) // maxlength 40
				->addRule(Form::FILLED, 'Není zadáno jméno')
				->setRequired(TRUE);
		
		$this->addText('surname', 'Příjmení', self::SURNAME_LENGTH, self::SURNAME_LENGTH)
				->addRule(Form::FILLED, 'Není zadáno příjmení')
				->setRequired(TRUE);
		
		$this->addText('birth_number', 'Rodné číslo', 10, 10)
				->addRule(Form::FILLED, 'Není zadáno rodné číslo')
				->addRule(Form::NUMERIC, 'Rodné číslo musí obsahovat pouze čísla')
				->addRule(Form::LENGTH, 'Rodné číslo musí být dlouhé 10 znaků', 10)
				->setRequired(TRUE);
		
		$this->addText('nick','Přezdívka', 16, 16)
				->addRule(Form::FILLED, 'Není zadáno příjmení')
				->setRequired(TRUE);
		
		$this->addText('email','E-mail', self::EMAIL_LENGTH, self::EMAIL_LENGTH)
				->addRule(Form::FILLED, 'Není zadáno příjmení')
				->addRule(Form::EMAIL, 'Špatný formát emailu')
				->setRequired(TRUE);

		$this->addText('address', 'Adresa')
				->addRule(Form::FILLED,'Pole "Adresa" je povinné')
				->setRequired(TRUE);

		$this->addText('city', 'Město/Obec')
				->addRule(Form::FILLED, 'Pole "Město" je povinné')
				->setRequired(TRUE);
		
		$this->addText('postal_code', 'PSČ',5,5)
				->addRule(Form::FILLED, 'Pole "PSČ" je povinné')
				->addRule(Form::NUMERIC, 'PSČ musí obsahovat pouze čísla')
				->addRule(Form::LENGTH, 'PSČ musí být dlouhé 5 znaků', 5)
				->setRequired(TRUE);
		
		$this->addText('phone', 'Telefon', self::PHONE_NO_LENGTH, self::PHONE_NO_LENGTH)
				->addRule(Form::FILLED, 'Není zadáno telefonní číslo')
				->addRule(Form::NUMERIC,'Telefonní číslo musí obsahovat pouze čísla')
				->addRule(Form::LENGTH, "Telefon musí obsahovat ". self::PHONE_NO_LENGTH ." znaků", self::PHONE_NO_LENGTH)
				->setRequired(TRUE);
		
		$this->addText('job','Zaměstnání')
				->addRule(Form::FILLED, 'Není zadáné zaměstnání')
				->setRequired(TRUE);
		
		$this->addGroup('Kontaktní osoba');
		$this->addText('contperson_name', 'Jméno', self::SURNAME_LENGTH, self::SURNAME_LENGTH);
			
		$this->addText('contperson_phone','Telefon', self::PHONE_NO_LENGTH, self::PHONE_NO_LENGTH)
				->addCondition(Form::FILLED)
				->addRule(Form::LENGTH, "Telefonní číslo musí mít max". self::PHONE_NO_LENGTH ."znaků", self::PHONE_NO_LENGTH)
				->addRule(Form::NUMERIC,'Telefonní číslo musí obsahovat pouze čísla');
		$this->addText('contperson_email','E-mail', self::EMAIL_LENGTH, self::EMAIL_LENGTH)
				->addCondition(Form::FILLED)
				->addRule(Form::EMAIL, 'Špatný formát emailu');
		
		$this->onSuccess[] = callback($this, 'userDataFormSubmitted');
	}
	
	/**
	 * ContactsForm
	 */
	public function userDataFormSubmitted(Form $form) {
		$values = $form->getValues();
		$configParams = $this->presenter->context->getParameters();
		//$salt = $configParams['models']['salt'];
		
		//$values->offsetSet('password', 
		//					\florbalMohelnice\Miscellaneous\Authenticator::calculateHash(
		//						$values->birth_number,
		//						$salt));
		$user = new User($values);
		$user->offsetSet('cfbu_number', '');
		$this->presenter->editUserData($user);
	}
}

