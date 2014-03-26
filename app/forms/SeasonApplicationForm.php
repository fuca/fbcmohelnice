<?php

namespace florbalMohelnice\Forms;

use Nette\Application\UI\Form,
    Nette\DateTime,
    Vodacek\Forms\Controls\DateInput;

/**
 * Description of SeasonApplicationForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class SeasonApplicationForm extends Form {

    const UPDATE_MODE = 'update';
    const CREATE_MODE = 'create';

    /** @var users list */
    private $users;

    /** @var form mode */ // ENUM create/update
    private $mode;
    
    /** @var seasons */
    private $seasons;
    
    public function getSeasons() {
	return $this->seasons;
    }

    public function setSeasons($seasons) {
	$this->seasons = $seasons;
    }
        
    public function getMode() {
	return $this->mode;
    }

    public function setMode($m) {
	if (($m != self::CREATE_MODE) && ($m != self::UPDATE_MODE)) {
	    throw new \InvalidArgumentException(
	    "Mode has to be set on " . self::CREATE_MODE . " or " . self::UPDATE_MODE . ", '$m' given.");
	}
	$this->mode = $m;
    }

    public function setUsers(array $uss) {
	$this->users = $uss;
    }

    public function getUsers() {
	if (!isset($this->users) || !is_array($this->users)) {
	    throw new \Nette\InvalidStateException('Attribute users is not set');
	}
	return $this->users;
    }
    
    public function setHiddenKid($kid) {
	if ($kid == NULL)
	    throw new \Nette\InvalidArgumentException("Argument kid was null");
	if (!is_numeric($kid))
	    throw new \Nette\InvalidArgumentException("Argument kid has to be type of numeric");
	$this->setDefaults(array('kid'=>$kid));
    }
    
    public function isCreate() {
	if ($this->getMode() == self::CREATE_MODE)
	    return TRUE;
	else return FALSE;
    }
    
    public function isUpdate() {
	if ($this->getMode() == self::UPDATE_MODE) 
	    return TRUE;
	else return FALSE;
    }

    public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name, array $seasons, array $selUsers, $mode = self::CREATE_MODE) {
	parent::__construct();

	$this->setMode($mode);
	$this->setUsers($selUsers);
	$this->setSeasons($seasons);
	$paymentStates = \florbalMohelnice\Entities\Payment::getStates();
	$contactStatus = \florbalMohelnice\Entities\Contact::getSelectStatus();
	
	
	$this->addHidden('kid');
	
	$this->addSelect('id_season','Sezóna', $this->getSeasons())
		->addRule(Form::FILLED, 'Sezóna musí být vybrána');
	
	$this->addSelect('clp_status', 'Stav ČLP', $paymentStates)
		->addRule(Form::FILLED, 'Stav ČLP musí být vybrán');
	
	if ($this->isUpdate())    
	    $this->addDate('enrolled_time', 'Zadáno', DateInput::TYPE_DATETIME_LOCAL)
		    ->addRule(Form::FILLED, 'Datum zadání přihlášky musí být vyplněno');
	
	if ($this->isUpdate()) {
	    $this->addSelect('clp_kid', 'ČLP zadal', $this->getUsers())
		    ->addRule(Form::FILLED, 'Zadavatel informací o platbě musí být vybrán');
	
	    $this->addDate('clp_time', 'ČLP z. čas', DateInput::TYPE_DATETIME_LOCAL)
		    ->addRule(Form::FILLED, 'Datum zadání informace o platbě člp musí být vyplněno');
	}
	if ($this->isUpdate())
	    $this->addText('credits', 'Kredity', 5)
		 ->addRule(Form::FILLED, 'Mmnožství kreditů musí být zadáno')
		 ->addRule(Form::NUMERIC, 'Mmnožství kreditů musí být číslo');
    
	
	$this->addSelect('contacts_status', 'Stav kontaktu', $contactStatus)
		->addRule(Form::FILLED, 'Stav kontaktu musí být vybrán');
	
	if ($this->isUpdate())
	    $this->addDate('contacts_time', 'Změna kont.', DateInput::TYPE_DATETIME_LOCAL)
	    	->setDisabled(TRUE);
	
	$this->addSubmit('save', 'Ulož');
	$this->onSuccess[] = callback($this, 'saveOperationHandler');
    }
    
    
    public function saveOperationHandler(SeasonApplicationForm $form) {
	$values = $form->getValues();
	$app = new \florbalMohelnice\Entities\SeasonApplication($values);
	$app->offsetSet('kid', $values['kid']);
	switch($this->getMode()) {
		case self::CREATE_MODE:			
			$app->offsetSet('enrolled_time', new DateTime());
			$app->offsetSet('clp_kid', $this->presenter->getUserId());
			$app->offsetSet('clp_time', new DateTime());
			$app->offsetSet('contacts_time', new DateTime());
			$this->presenter->addApplication($app);
			break;
		case self::UPDATE_MODE:
			$this->presenter->updateApplication($app);
			break;
	}
    }
}
