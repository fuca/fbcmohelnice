<?php

namespace florbalMohelnice\Forms;

use \Nette\Application\UI\Form,
    Nette\Diagnostics\Logger,
    florbalMohelnice\Entities\StaticPage,
    Vodacek\Forms\Controls\DateInput,
    Nette\DateTime;

/**
 * Description of StaticPageForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class StaticPageForm extends Form {
    
    const CREATE_MODE = 'create';
    const UPDATE_MODE = 'update';

    /** @var users list */
    private $users;

    /** @var form mode */ // ENUM create/update
    private $mode;

    /** @var parent pages array */
    private $pages;

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

    public function getPages() {
	if (!isset($this->pages) || !is_array($this->pages)) {
	    $msg = 'StaticPageForm::getPages - Attribute pages is not set';
	    Logger::log($msg, Logger::ERROR);
	    throw new \Nette\InvalidStateException($msg);
	}
	return $this->pages;
    }

    public function setPages(array $pgs) {
	$this->pages = $pgs;
    }

    public function getStatusSelect() {
	$resArray = StaticPage::getStatusModes();
	return $resArray;
    }

    public function getCommentModes() {
	$commentModes = StaticPage::getSelectCommModes();
	return $commentModes;
    }

    public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, array $selUsers, array $pagesSelect, $mode = self::CREATE_MODE) {
	parent::__construct();
	$this->setMode($mode);
	$this->setUsers($selUsers);
	$this->setPages($pagesSelect);
	$width = 70;
	$height = 40;

	$this->addHidden('id_page');

	$this->addSelect('parent_page', "Rodič", $this->getPages())
		->setPrompt('');
	$this->addText('title', 'Titulek', $width)
		->addRule(\Nette\Forms\Form::FILLED, "Titulek musí být zadán");
	$this->addTextArea('content', '', $width, $height);
	
	$this->addCheckbox('link', 'Odkaz');
	//$this->addSelect('status', 'Status', $this->getStatusSelect());
	//$this->addSelect('comment_mode', 'Komentáře', $this->getCommentModes());
	$this->addSubmit('sendButton', 'Uložit');
	$this->onSuccess[] = callback($this, "staticPageFormSubmitted");
    }

    public function staticPageFormSubmitted(Form $form) {
	$presenter = $form->getPresenter();
	$values = $form->getValues();
	$values['updated_time'] = new DateTime();
	$sp = new \florbalMohelnice\Entities\StaticPage($values);
	$sp->offsetSet('updated_kid', $presenter->getUser()->getIdentity()->id);
	switch ($form->getMode()) {
	    case self::CREATE_MODE:
		$presenter->createStaticPage($sp);
		break;
	    case self::UPDATE_MODE:
		$sp->offsetSet('id_page', $values['id_page']);
		$presenter->updateStaticPage($sp);
		break;
	    default:
		$msg = 'Invalid Static page form mode.';
		Logger::log($msg, Logger::ERROR);
		throw new \Nette\InvalidStateException($msg);
	}
    }

}

