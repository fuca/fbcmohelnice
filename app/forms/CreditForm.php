<?php
namespace florbalMohelnice\Forms;

use \Nette\Application\UI\Form,
	Nette\Diagnostics\Logger,
	Vodacek\Forms\Controls\DateInput,
	Nette\DateTime;

/**
 * Description of CreditForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class CreditForm extends Form {
	
	/** @var users list */
	private $users;
	
	/** @var form mode*/ // ENUM create/update
	private $mode;
	
	/** @var credit reward select data */
	private $rewards;
	
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
			$msg = "CreditForm::SetMode - Mode has to be set on 'create' or 'update', '$m' given.";
			Logger::log($msg, Logger::ERROR);
			throw new \InvalidArgumentException($msg);
		}
		$this->mode = $m;
	}
	
	public function setUsers(array $uss) {
		$this->users = $uss;
	}
	
	public function getUsers() {
		if (!isset($this->users) || !is_array($this->users)) {
			$msg = 'CreditForm::getUsers - Attribute users is not set';
			Logger::log($msg, Logger::ERROR);
			throw new \Nette\InvalidStateException($msg);
		}
		return $this->users;
	}
	
	public function getRewards() {
		if (!isset($this->rewards) || !is_array($this->rewards)) {
			$msg = "CreditForm::getReward - Attribute users is not set";
			throw new \Nette\InvalidStateException($msg);
		}
		return $this->rewards;
	}
	
	public function setRewards (array $rews) {
		$this->rewards = $rews;
	}
	
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, array $selUsers, array $rewardsSelect, array $seasons, $mode = 'create') {
		parent::__construct();
		$this->setMode($mode);
		$this->setUsers($selUsers);
		$this->setRewards($rewardsSelect);
		$this->setSeasons($seasons);
		
		$this->addHidden('id_credit');
		$this->addSelect('kid', 'Člen', $this->getUsers())
				->addRule(Form::FILLED, "Člen musí být vybrán")
				->setPrompt('-');
		
		$this->addSelect('season','Sezóna', $this->getSeasons())
				->setDefaultValue(2013);
		
		$this->addSelect('subject','Předmět', $this->getRewards())
				->addRule(Form::FILLED, 'Předmět musí být vybrán.')
				->setPrompt('-');
		$this->addText('comment', 'Komentář');
		
		$ord = $this->addSelect('ordered_kid', 'Zadavatel', $this->getUsers())
				->addRule(Form::FILLED, 'Zadavatel není vybrán')
				->setRequired();
		
		$sel = $this->addDate('ordered_time', 'Zadáno', DateInput::TYPE_DATETIME_LOCAL)
				->setDefaultValue(new DateTime())
				->addRule(Form::RANGE, NULL, array(new DateTime('-3 month'), new DateTime('+1 month')));
		
		
		if ($this->getMode() == 'create') {
			$ord->setDisabled();
			//$sel->setDisabled();
		}
		
		$this->addSubmit('sendButton', 'Uložit');
		$this->onSuccess[] = callback($this, 'createCreditEntryHandle');	
		
	}
	
	public function createCreditEntryHandle(CreditForm $form) {
		$presenter = $form->getPresenter();
		$values = $form->getValues();
		switch($form->getMode()) {
			case 'create':
				$values['ordered_kid'] = $presenter->getUser()->getIdentity()->id;
				$presenter->createCreditEntry(new \florbalMohelnice\Entities\CreditEntry($values));
				break;
			case 'update':
				$presenter->updateCreditEntry(new \florbalMohelnice\Entities\CreditEntry($values));
				break;
			default: 
				$msg = 'Invalid form mode.';
				//Logger::log($msg, Logger::ERROR);
				throw new \Nette\InvalidStateException($msg);
		}
	}
	
	public function attached($presenter) {
		parent::attached($presenter);		
		$this->getComponent('ordered_kid')->setDefaultValue($presenter->getUser()->getIdentity()->id);
	}
}

