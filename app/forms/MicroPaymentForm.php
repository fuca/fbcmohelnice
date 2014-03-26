<?php

namespace florbalMohelnice\Forms;
use \Nette\Application\UI\Form,
	\Nette\DateTime,
	Vodacek\Forms\Controls\DateInput;
/**
 * Description of MicroPaymentForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class MicroPaymentForm extends Form {
	
	/** @var form mode*/ // ENUM create/update
	private $mode;
	
	/** @var users to select list */
	private $users;
	
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
		if (($m != 'create') && ($m != 'update')) {
			throw new \InvalidArgumentException("Mode has to be set on 'create' or 'update', '$m' given.");
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
	
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, array $selectUsers, array $selectSeasons, $mode = 'create') {
		parent::__construct();

		$this->setMode($mode);
		$this->setSeasons($selectSeasons);
		$this->setUsers($selectUsers);
	
		$this->addHidden('id_micropayment');
		$this->addSelect('kid','Člen', $this->getUsers())
				->setPrompt('-')
				->addRule(Form::FILLED, 'Člen není vybrán');
		
		$this->addText('subject','Předmět')
				->addRule(Form::FILLED, 'Předmět není zadán')
				->setRequired("Predmet vole - required test");
		
//		$this->addSelect('id_micropayment_type', 'Typ mikroplatby', array())
//			->addRule(Form::FILLED, 'Typ platby není vybrán.')
//			->setRequired();
		
		$this->addText('amount', 'Částka', 10, 10)
			->addRule(Form::FILLED, 'Částka chybí.')
			->addRule(Form::NUMERIC, 'Částka musí být číslo.');
		
		$this->addText('micropayment_type', 'Typ', 30)
			->addRule(Form::FILLED, 'Typ mikroplatby musí být zadán.');
		
		$ord = $this->addSelect('season', 'Sezóna', $this->getSeasons())
				->addRule(Form::FILLED, 'Sezóna není vybrána')
				->setRequired();
		
		$this->addTextArea('comment', 'Komentář', 20, 5);
		
		$ord = $this->addSelect('ordered_kid', 'Zadavatel', $this->getUsers())
				->addRule(Form::FILLED, 'Zadavatel není vybrán')
				->setRequired();
		
		$sel = $this->addDate('ordered_time', 'Zadáno', DateInput::TYPE_DATE)
				->setDefaultValue(new DateTime())
				->addRule(Form::RANGE, NULL, array(new DateTime('-5 year'), new DateTime('+2 month')));
		
		if ($this->getMode() == 'create') {
			$ord->setDisabled();
			$sel->setDisabled();
		}
		
		$this->addSubmit('sendButton', 'Uložit');
		$this->onSuccess[] = callback($this, 'microPaymentFormSubmitted');
	}	
	
	public function microPaymentFormSubmitted(MicroPaymentForm $form) {
		$presenter = $this->getPresenter();
		$values = $form->getValues();
		switch($form->getMode()) {
			case 'create':
				$values->offsetUnset('id_micropayment');
				$values->offsetSet('ordered_kid', $presenter->getUser()->getIdentity()->id);
				$values->offsetSet('ordered_time', new \Nette\DateTime());
				$presenter->createMicroPayment(new \florbalMohelnice\Entities\MicroPayment($values));
				break;
			case 'update':
				$presenter->updateMicroPayment(new \florbalMohelnice\Entities\MicroPayment($values));
				break;
			default: 
				$msg = 'Invalid form mode.';
				throw new \Nette\InvalidStateException($msg);
		}
	}
	
	public function attached($presenter) {
		parent::attached($presenter);		
		$this->getComponent('ordered_kid')->setDefaultValue($presenter->getUser()->getIdentity()->id);
	}
	
	
}

