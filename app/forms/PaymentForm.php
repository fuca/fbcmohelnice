<?php

namespace florbalMohelnice\Forms;
use Nette\Application\UI\Form,
	Nette\DateTime,
	Vodacek\Forms\Controls\DateInput;
/**
 * Description of PaymentForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class PaymentForm extends Form {
	
        const CREATE_MODE = 'create';
	const UPDATE_MODE = 'update';
	
	/** @var users list */
	private $users;
	
	/** @var form mode*/ // ENUM create/update
	private $mode;
	
	/** @var eligible seasons */
	private $seasons;
	
	public function getSeasons() {
		return $this->seasons;
	}
	
	public function setSeasons(array $ses) {
		if (count($ses) == 0) 
		    throw new Nette\InvalidArgumentException("Wrong seasons array content");
		$this->seasons = $ses;
	}
	
	public function getMode() {
		return $this->mode;
	}
	
	public function setMode($m) {
		if (($m != self::CREATE_MODE) && ($m != self::UPDATE_MODE)) {
			throw new \InvalidArgumentException("Mode has to be set on self::CREATE_MODE or self::UPDATE_MODE, '$m' given.");
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
	
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, array $selUsers, array $selSeasons, $mode = self::CREATE_MODE) {
		parent::__construct();
		
		$this->setMode($mode);
		$this->setUsers($selUsers);
		$this->setSeasons($selSeasons);
		
		$group1Title = NULL;
		
		
		switch ($mode) {
			case self::CREATE_MODE:
				$group1Title = 'Nová platba';
				break;
			case self::UPDATE_MODE:
				$group1Title = 'Uprava platby';
				break;
			default:;
		}
		
		$this->addGroup($group1Title);
		$this->addHidden('id_payment');

		$this->addSelect('kid','Člen', $this->getUsers())
				->setPrompt('')
				->addRule(Form::FILLED, 'Člen není vybrán');
		
		$this->addText('subject','Předmět')
				->addRule(Form::FILLED, 'Předmět není zadán')
				->setRequired("Predmet vole - required test");
		
//		$this->addSelect('id_payment_type', 'Typ platby', array())
//			->addRule(Form::FILLED, 'Typ platby není vybrán.')
//			->setRequired();
		
		$this->addText('amount', 'Částka', 10, 10)
			->addRule(Form::FILLED, 'Částka chybí.')
			->addRule(Form::NUMERIC, 'Částka musí být číslo.');
		
		$this->addDate('pay_day', 'Datum splatnosti', DateInput::TYPE_DATE)
				->setDefaultValue(new DateTime('+1 month'));
				//->addRule(Form::RANGE, NULL, array(new DateTime(), new DateTime('+13 month')));
				
		$this->addSelect('season','Sezóna', $this->getSeasons(), 0)
				->setDefaultValue(2013);
		
		if ($this->getMode() == self::UPDATE_MODE)
		    $this->addDate('ordered_time', 'Zadáno', DateInput::TYPE_DATETIME_LOCAL)
		    		->addRule(Form::RANGE, NULL, array(new \Nette\DateTime('-20 year'), new DateTime('+10 year')));
//		$this->addDate('due_date', 'Datum splatnosti', DateInput::TYPE_DATE)
//				->setDefaultValue(new DateTime('+1 week'));
		$sel = $this->addSelect('status','Zaplaceno', array('unp'=>'Ne','pac'=>'Hotově','paa'=>'Účet'), 0)
				->setDefaultValue(0);
		
		$ord = $this->addSelect('ordered_kid', 'Zadavatel', $this->getUsers())
				->addRule(Form::FILLED, 'Zadavatel není vybrán')
				->setRequired();
		
		
		if ($this->getMode() == self::CREATE_MODE) {
			//$ord->setDisabled();
			//$sel->setDisabled();
		}
		$this->addTextArea('comment', 'Komentář', 20, 5);
		
		$this->addSubmit('sendButton', 'Uložit');
		$this->onSuccess[] = callback($this, 'paymentFormSubmitted');
	}
	
	public function paymentFormSubmitted(Form $form) {
		$presenter = $this->getPresenter();
		$values = $form->getValues();
		
		switch($form->getMode()) {
			case self::CREATE_MODE:
				$values['status'] = 'unp';
				$values['ordered_kid'] = $presenter->getUser()->getIdentity()->id;
				$values['ordered_time'] = new DateTime();
				$presenter->createPayment(new \florbalMohelnice\Entities\Payment($values));
				break;
			case self::UPDATE_MODE:
				$presenter->updatePayment(new \florbalMohelnice\Entities\Payment($values));
				break;
			default: 
				$msg = 'Invalid form mode.';
				throw new \Nette\InvalidStateException($msg);
		}
	}
	
	public function attached($presenter) {
		parent::attached($presenter);		
		$this->getComponent('ordered_kid')->setDefaultValue($presenter->getUserId());
	}
}

