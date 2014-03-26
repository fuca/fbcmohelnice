<?php
namespace florbalMohelnice\Components;

/**
 * Description of MessagesControl
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class MessagesControl extends \Nette\Application\UI\Control {
	
	/** @var user id */
	private $userId;
	
	/** @var private messages model*/
	private $messagesModel;
	
	/** @var select users*/
	private $users;
	
	/** @var general templatefile*/
	private $templateFile;
	
	/** @var deleted template file*/
	private $deletedTemplate;
	
	/** @var create message template file*/
	private $createTemplate;
	
	public function getUserId() {
		if (!isset($this->userId)) throw new \Nette\InvalidStateException('Used ID is not set up yet');
		return $this->userId;
	}
	
	public function setUserId($id) {
		if (!is_numeric($id)) throw new \Nette\InvalidArgumentException('Argument user id has to be of numeric type');
			$this->userId = $id;
	}
	
	public function getMessagesModel() {
		return $this->messagesModel;
	}
	
	public function setMessagesModel(\florbalMohelnice\Models\PrivateMessagesModel $model) {
		$this->messagesModel = $model;
	}
	
	public function getUsers() {
		return $this->users;
	}
	
	public function setUsers(array $users) {
		if (sizeof($users) == 0) throw new \Nette\InvalidArgumentException("Entry array cannot be empty");
		$this->users = $users;
	}
	
	public function getTemplateFile() {
		if (!isset($this->templateFile)) $this->templateFile = __DIR__."/general.latte";
		return $this->templateFile;
	}
	
	public function setTemplateFile($file) {
		if (!file_exists($file)) throw new \Nette\InvalidArgumentException("Passed file doesn't exist");
		$this->templateFile = $file;
	}
	
	public function getInboxTemplateFile() {
		return $this->getTemplateFile();
	}
	
	public function getOutboxTemplateFile() {
		return $this->getTemplateFile();
	}
	
	public function getDeletedTemplateFile() {
		if (!isset($this->deletedTemplate)) $this->deletedFile = __DIR__."/deletedMessages.latte";
		return $this->deletedTemplate;
	}
	
	public function setDeletedTemplateFile($file) {
		if (!file_exists($file)) throw new \Nette\InvalidArgumentException("Passed file doesn't exist");
		$this->deletedTemplate = $file;
	}
	
	public function getCreateTemplate() {
		if (!isset($this->createTemplate)) $this->createTemplate = __DIR__."/createMessage.latte";
		return $this->createTemplate;
	}
	
	public function setCreateTemplate($file) {
		if (!file_exists($file)) throw new \Nette\InvalidArgumentException("Passed file doesn't exist");
		$this->createTemplate = $file;
	}


	public function __construct($parent, $name) {
		parent::__construct($parent, $name);
	}
	
	public function renderInbox() {
		$incoming = $this->getMessagesModel()->getIOMsgs(TRUE, $this->getUserId());
		$this->template->setFile($this->getTemplateFile());
		$this->template->messages = $incoming;
		$this->template->render();
	}
	
	public function renderOutbox() {
		$outgoing = $this->getMessagesModel()->getIOMsgs(FALSE, $this->getUserId());
		$this->template->setFile($this->getTemplateFile());
		$this->template->messages = $outgoing;
		$this->template->render();
	}
	
	public function renderDeleted() {
		$deleted = $this->getMessagesModel()->getDeleted($this->getUserId());
		$this->template->setFile($this->getDeletedTemplateFile());
		$this->template->messages = $deleted;
		$this->template->render();
	}
	
	public function render() {
		$this->renderInbox();
	}
	
	public function renderPanel() {
		
	}
	
	public function renderCreateMessage() {
		$this->template->setFile($this->getCreateTemplate());
		$this->template->render();
	}
	
	public function handleCreateMessage($args) {
		$this->renderCreateMessage();
	}

	
}

