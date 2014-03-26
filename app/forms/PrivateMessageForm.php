<?php
namespace florbalMohelnice\Forms;
use Nette\Application\UI\Form,
	florbalMohelnice\Entities\PrivateMessage;

/**
 * Description of PrivateMessageForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class PrivateMessageForm extends \Nette\Application\UI\Form {

	private $users;
	
	
	public function getUsers() {
		return $this->users;
	}
	
	public function setUsers(array $usrs) {
		$this->users = $usrs;
	}
	
	public function __construct($parent, $name, array $selUsers) {
		parent::__construct();
		
		$this->setUsers($selUsers);

		$this->addMultiSelect('recipients', 'Komu', $this->getUsers(), 10)
				->addRule(Form::FILLED, "Je třeba zadat alespoň jednoho příjemce");
		$this->addText('subject', 'Předmět', 50)
				->addRule(Form::FILLED, "Je třeba zadat předmět zprávy");
		$this->addTextArea('content', 'Text', 50, 15)
				->addRule(Form::FILLED, "Zpráva musí obsahovat nějaký text");
		
		$this->addSubmit('send', 'Odeslat zprávu');
		$this->onSuccess[] = callback($this,'sendMessageHandler');
				
	}
	
	public function sendMessageHandler(Form $form) {
		$values = $form->getValues();
		$values['sent'] = new \Nette\DateTime();
		$message = new PrivateMessage($values);
		$this->presenter->sendMessageHandle($message);
	}
}

