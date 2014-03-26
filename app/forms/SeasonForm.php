<?php
namespace florbalMohelnice\Forms;

use \Nette\Application\UI\Form,
	Nette\Diagnostics\Logger,
	Vodacek\Forms\Controls\DateInput,
	Nette\DateTime;

/**
 * Description of SeasonForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class SeasonForm extends Form {
    
	const UPDATE_MODE = 'update';
	const CREATE_MODE = 'create';
	
	/** @var users list */
	private $users;
	
	/** @var form mode*/ // ENUM create/update
	private $mode;
	
	/** @var groups */
	private $groups;
	
	public function getCategories() {
	    return $this->groups;
	}

	public function setCategories(array $groups) {
	    $this->groups = $groups;
	}
	
	public function getMode() {
	    if (!isset($this->mode))
		$this->mode = self::CREATE_MODE;
	    return $this->mode;
	}
	
	public function setMode($m) {
		if (($m != self::CREATE_MODE) && ($m != self::UPDATE_MODE)) {
			$msg = "CreditForm::SetMode - Mode has to be set on 'create' or 'update', '$m' given.";
			Logger::log($msg, Logger::ERROR);
			throw new \InvalidArgumentException($msg);
		}
		$this->mode = $m;
	}
	
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name) {
		parent::__construct($parent, $name);
		
		$this->addHidden('id_season');
		$this->addSubmit('save','Uložit sezónu');
		$this->onSuccess[] = callback($this, 'formProccessHandler');
	}

	
	public function init($mode = self::CREATE_MODE) {
	    $this->setMode($mode);
	    switch($mode) {
		case self::CREATE_MODE:
		    $this->initializeCreate();
		    break;
		case self::UPDATE_MODE:
		    $this->initializeCreate();
		    break;
		default:
		    throw new \Nette\InvalidStateException("Invalid form mode passed to init method");
	    }
	}
	
	private function initializeCreate() {
	    if (!isset($this->groups))
		throw new \Nette\InvalidStateException("Attribute groups is not set");
	    
	    $this->addDate('date_from', 'Začíná', DateInput::TYPE_DATE)
		    ->addRule(Form::FILLED, "Datum začátku sezóny musí být zadáno");
	    $this->addDate('date_to', 'Končí', DateInput::TYPE_DATE)
		    ->addRule(Form::FILLED, "Datum konce sezóny musí být zadáno");
	    
	    $rows = $this->addContainer('common');
	    
	    foreach($this->getCategories() as $key=>$group) {
		$row  = new \Nette\Forms\Container($rows, $key);	
		$row->addText('credit', $group , 5)
			->addRule(Form::FILLED, "Počet kreditů pro kategorii $group musí být zadán")
			->addRule(Form::NUMERIC, "Počet kreditů pro kategorii $group musí být číslo");
		
		$row->addText('clp', '')
			->addRule(Form::FILLED, "ČLP pro kategorii $group musí být zadáno")
			->addRule(Form::NUMERIC, "ČLP pro kategorii $group musí být číslo");
	    }
	    $this->addCheckbox('active', 'Nastavit jako aktuální sezónu');
	    $this->addTextArea('comment', 'Komentář', 55, 10);
	}
	
	public function formProccessHandler(SeasonForm $form) {
	    
	    $values = $form->getValues();
	    $idSeason = $values['id_season'];
	    $now = new DateTime();
	    
	    $season = new \florbalMohelnice\Entities\Season($values);
	    $season->offsetSet('last_change', $now);
	    switch($this->getMode()) {
		case self::CREATE_MODE:
		    $this->presenter->createSeason($season);
		    break;
		case self::UPDATE_MODE:
		    $season->offsetSet('id_season', $idSeason);
		    $this->presenter->updateSeason($season);
		    break;
	    }
	}
}