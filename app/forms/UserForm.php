<?php

namespace florbalMohelnice\Forms;

use Nette\Application\UI\Form,
    \florbalMohelnice\Entities\User;

/**
 * Description of UserForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class UserForm extends \Nette\Application\UI\Form {

    const CREATE_MODE = 'create';
    const UPDATE_MODE = 'update';

    /** @var form mode */ // ENUM create/update
    private $mode;

    /** @var available roles */
    private $roles;

    /* @var available categories */
    private $categories;

    public function getMode() {
	return $this->mode;
    }

    public function setMode($m) {
	if ($m != 'create' && $m != 'update')
	    throw new \InvalidArgumentException("Mode has to be set on 'create' or 'update', '$m' given.");
	$this->mode = $m;
    }

    public function setRoles(array $r) {
	if (sizeof($r) == 0)
	    throw new \Nette\InvalidStateException('There are none roles for select');
	$this->roles = $r;
    }

    protected function getRoles() {
	return $this->roles;
    }

    protected function getCategories() {
	return $this->categories;
    }

    public function setCategories(array $cats) {
	if (sizeof($cats) == 0)
	    throw new \Nette\InvalidStateException('There are none categories for select');
	$this->categories = $cats;
    }

    public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, array $roles, array $cats, $mode = self::CREATE_MODE) {
	parent::__construct($parent, $name);

	$this->setMode($mode);
	$this->setRoles($roles);
	$this->setCategories($cats);

	$phoneNoLength = 9;
	$emailLength = 32;
	$nameLength = 32;
	$surnameLength = 32;

	$this->addHidden('kid');
	$this->addSubmit('submitButton', 'Uložit');
	if ($this->getMode() == self::CREATE_MODE)
	    $this->addGroup('Nový uživatel');
	else
	    $this->addGroup('Editace uživatele');


	$this->addText('name', 'Jméno', $nameLength, $nameLength) // maxlength 40
		->addRule(Form::FILLED, 'Není zadáno jméno')
		->setRequired(TRUE);

	$this->addText('surname', 'Příjmení', $surnameLength, $surnameLength)
		->addRule(Form::FILLED, 'Není zadáno příjmení')
		->setRequired(TRUE);

	$this->addText('birth_number', 'Rodné číslo', 10, 10)
		->addRule(Form::FILLED, 'Není zadáno rodné číslo')
		->addRule(Form::NUMERIC, 'Rodné číslo musí obsahovat pouze čísla')
		->addRule(Form::LENGTH, 'Rodné číslo musí být dlouhé 10 znaků', 10)
		->setRequired(TRUE);

	$this->addText('cfbu_number', 'ČFbU id', 10, 10)
                ->addCondition(Form::FILLED)
		->addRule(Form::NUMERIC, 'ČFbU identifikátor musí obsahovat pouze čísla')
		->addRule(Form::LENGTH, 'ČFbU identifikátor musí být dlouhý 10 znaků', 10);

	$this->addText('nick', 'Přezdívka', 16, 16)
		->addRule(Form::FILLED, 'Není zadáno příjmení')
		->setRequired(TRUE);

	$this->addText('email', 'E-mail', $emailLength, $emailLength)
		->addRule(Form::FILLED, 'Není zadáno příjmení')
		->addRule(Form::EMAIL, 'Špatný formát emailu')
		->setRequired(TRUE);

	$this->addMultiSelect('roles', 'Role', $this->getRoles(), 6)
		->addRule(Form::FILLED, 'Role musí být vybrána')
		->setRequired(TRUE);

	$this->addMultiSelect('categories', 'Kategorie', $this->getCategories(), 6)
		->addRule(Form::FILLED, 'Kategorie musí být vybrána')
		->setRequired(TRUE);

	$this->addText('address', 'Adresa')
		->addRule(Form::FILLED, 'Pole "Adresa" je povinné')
		->setRequired(TRUE);

	$this->addText('city', 'Město/Obec')
		->addRule(Form::FILLED, 'Pole "Město" je povinné')
		->setRequired(TRUE);

	$this->addText('postal_code', 'PSČ', 5, 5)
		->addRule(Form::FILLED, 'Pole "PSČ" je povinné')
		->addRule(Form::NUMERIC, 'PSČ musí obsahovat pouze čísla')
		->addRule(Form::LENGTH, 'PSČ musí být dlouhé 5 znaků', 5)
		->setRequired(TRUE);

	$this->addText('phone', 'Telefon', $phoneNoLength, $phoneNoLength)
		->addRule(Form::FILLED, 'Není zadáno telefonní číslo')
		->addRule(Form::NUMERIC, 'Telefonní číslo musí obsahovat pouze čísla')
		->addRule(Form::LENGTH, "Telefon musí obsahovat $phoneNoLength znaků", $phoneNoLength)
		->setRequired(TRUE);

	$this->addText('job', 'Zaměstnání')
		->addRule(Form::FILLED, 'Není zadáné zaměstnání')
		->setRequired(TRUE);

	$this->addGroup('Kontaktní osoba');
	$this->addText('contperson_name', 'Jméno', $surnameLength, $surnameLength);

	$this->addText('contperson_phone', 'Telefon', $phoneNoLength, $phoneNoLength)
		->addCondition(Form::FILLED)
		->addRule(Form::LENGTH, "Telefonní číslo musí mít max $phoneNoLength znaků", $phoneNoLength)
		->addRule(Form::NUMERIC, 'Telefonní číslo musí obsahovat pouze čísla');
	$this->addText('contperson_email', 'E-mail', $emailLength, $emailLength)
		->addCondition(Form::FILLED)
		->addRule(Form::EMAIL, 'Špatný formát emailu');

	$this->onSuccess[] = callback($this, 'userFormSubmitted');
    }

    /**
     * ContactsForm
     */
    public function userFormSubmitted(Form $form) {
	$values = $form->getValues();
	$configParams = $this->presenter->context->getParameters();
	$salt = $configParams['models']['salt'];

	$values->offsetSet('password', \florbalMohelnice\Miscellaneous\Authenticator::calculateHash(
			$values->birth_number, $salt));
	switch ($this->getMode()) {
	    case self::CREATE_MODE:
		$this->presenter->createUser(new User($values));
		break;
	    case self::UPDATE_MODE:
		$this->presenter->updateUser(new User($values));
		break;
	}
	$this->presenter->redirect('Admin:users');
    }

}

