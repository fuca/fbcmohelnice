<?php

namespace florbalMohelnice\Components;

use florbalMohelnice\Forms\ParticipationForm;

/**
 * Description of EventParticipationControl
 *
 * @author Michal Fucik
 */
class EventParticipationControl extends \Nette\Application\UI\Control {

    /** @var participated kid */
    private $kid;

    /** @var participated event */
    private $idEvent;

    /** @var event participation model */
    private $model;

    /** @var template file name */
    private $countTemplate;

    /** @var template file name */
    private $historyTemplate;

    /** @var template file name */
    private $templateFile;

    /** @var template file name */
    private $controlsTemplate;

    /** @var template file name */
    private $adminTemplate;
    
    /** @var template file name */
    private $participatedTemplate;

    /** @var done predicate */
    private $done;

    /** @var data */
    private $data;

    /** @var confirm date */
    private $confirmDate;

    /** @var predicate of admin mode */
    private $adminMode;

    public function setAdminMode($adminMode) {
	if ($adminMode != 1 && $adminMode != 0)
	    throw new \Nette\InvalidArgumentException("Argument adminMode has to be a  type of boolean, $adminMode given");
	$this->adminMode = $adminMode;
    }

    public function getConfirmDate() {
	return $this->confirmDate;
    }

    public function confirmAblep() {
	return (new \Nette\DateTime() < $this->getConfirmDate() ? $this->getConfirmDate() : FALSE);
    }

    public function setConfirmDate($confirmDate) {
	$this->confirmDate = new \Nette\DateTime($confirmDate);
    }

    public function getDone() {
	if (!isset($this->done)) {
	    $data = $this->getData();
	    $elm = @$data[$this->kid];
	    if ($elm !== NULL) {
		$donep = $elm->participation;
	    } else {
		$donep = $elm;
	    }

	    $this->done = $donep;
	}
	return $this->done;
    }

    public function setIdEvent($idEvent) {
	$this->idEvent = $idEvent;
    }

    public function setKid($k) {
	if (!is_numeric($k))
	    throw new Nette\InvalidArgumentException("Argument has to be type of numeric");
	$this->kid = $k;
    }

    public function setModel(\florbalMohelnice\Models\ParticipationModel $model) {
	if ($model == NULL)
	    throw new Nette\InvalidArgumentException("Argument can't be NULL");
	$this->model = $model;
    }

    public function setTemplateFile($path) {
	if (!file_exists($path))
	    throw new Nette\FileNotFoundException("Given template file does not exist!");
	$this->templateFile = $path;
    }
    
    public function setParticipatedTemplate($path) {
	if (!file_exists($path))
	    throw new Nette\FileNotFoundException("Given template file does not exist!");
	$this->participatedTemplate = $path;
    }

    public function __construct($parent = NULL, $name = NULL) {
	parent::__construct($parent, $name);
	$this->setTemplateFile(__DIR__ . "/default.latte");
	$this->setCountTemplate(__DIR__ . "/count.latte");
	$this->setHistoryTemplate(__DIR__ . "/history.latte");
	$this->setControlsTemplate(__DIR__ . "/controls.latte");
	$this->setAdminTemplate(__DIR__ . "/defAdmin.latte");
	$this->setParticipatedTemplate(__DIR__ . "/participated.latte");
    }

    private function integrityCheck() {
	if (!isset($this->model))
	    throw new \Nette\InvalidStateException('Model property has to be set. Use setModel() method');
	if (!isset($this->kid))
	    throw new \Nette\InvalidStateException('Kid property has to be set. Use setKid() method');
	if (!isset($this->idEvent))
	    throw new \Nette\InvalidStateException('IdEvent property has to be set. Use setIdEvent() method');
	if (!isset($this->confirmDate))
	    throw new \Nette\InvalidStateException('ConfirmDate property has to be set. Use setConfirmDate() method');
    }

    public function setCountTemplate($countTemplate) {
	if (!file_exists($countTemplate))
	    throw new Nette\FileNotFoundException("Given template file does not exist!");
	$this->countTemplate = $countTemplate;
    }

    public function setHistoryTemplate($historyTemplate) {
	if (!file_exists($historyTemplate))
	    throw new Nette\FileNotFoundException("Given template file does not exist!");
	$this->historyTemplate = $historyTemplate;
    }

    public function setControlsTemplate($controlsTemplate) {
	if (!file_exists($controlsTemplate))
	    throw new Nette\FileNotFoundException("Given template file does not exist!");
	$this->controlsTemplate = $controlsTemplate;
    }

    public function setAdminTemplate($adminTemplate) {
	if (!file_exists($adminTemplate))
	    throw new Nette\FileNotFoundException("Given template file does not exist!");
	$this->adminTemplate = $adminTemplate;
    }

    public function getData() {
	if (!isset($this->data)) {
	    try {
		$data = $this->model->getAll($this->idEvent);
	    } catch (\Nette\IOException $e) {
		$this->parent->flashMessage('Potvrzené členy se nepodařilo spočítat');
		\Nette\Diagnostics\Debugger::log($e, \Nette\Diagnostics\Debugger::ERROR);
	    }
	    $this->data = $data;
	}
	return $this->data;
    }

    public function render() {
	$this->integrityCheck();
	$this->template->setFile($this->templateFile);
	$this->template->done = $this->getDone();
	$this->template->allowed = $this->getConfirmDate();
	$this->template->render();
    }

    public function renderControls() {
	$this->integrityCheck();
	$this->template->setFile($this->controlsTemplate);
	$this->template->allowed = $this->getConfirmDate();
	$this->template->done = $this->getDone();
	$this->template->render();
    }

    public function renderConfirmed() {
	$this->integrityCheck();
	if ($this->getDone()) {
	    $this->renderParticipated ();
	    return;
	}
	$this->template->setFile($this->countTemplate);
	$confirmed = array();
	$count = 0;
	foreach ($this->getData() as $d) {
	    if ($d->participation == \florbalMohelnice\Entities\Participation::YES_GOING_OWN) {
		array_push($confirmed, $d);
		$count++;
	    }
	}
	$this->template->count = $count;
	$this->template->going = $confirmed;
	$this->template->admin = $this->adminMode;
	$this->template->idEvent = $this->idEvent;
	$this->template->render();
    }

    public function renderParticipated() {
	$this->integrityCheck();
	$this->template->setFile($this->participatedTemplate);
	$confirmed = array();
	$count = 0;
	foreach ($this->getData() as $d) {
	    if ($d->participation == \florbalMohelnice\Entities\Participation::YES_GOING_OWN ||
		    $d->participation == \florbalMohelnice\Entities\Participation::YES_GOING_ADM) {
		array_push($confirmed, $d);
		$count++;
	    }
	}
	$this->template->count = $count;
	$this->template->going = $confirmed;
	$this->template->admin = $this->adminMode;
	$this->template->idEvent = $this->idEvent;
	$this->template->render();
    }

    public function handleCancelParticipation() {
	try {
	    $this->model->resetParticipation($this->kid, $this->idEvent);
	} catch (\Nette\IOException $x) {
	    
	} catch (\Nette\InvalidArgumentException $x) {
	    
	}
	$this->redirectEvent();
    }

    public function renderHistory() {
	$this->integrityCheck();
	$this->template->setFile($this->historyTemplate);
	// zobrazit historii aktivit vcetne komentaru
	$this->template->data = $this->getData();
	$this->template->render();
    }

    public function renderAdmin() {
	if (!$this->adminMode)
	    throw new \Nette\InvalidStateException("This EventParticipationControl is not in admin mode");
	$this->template->setFile($this->adminTemplate);
	$this->template->render();
    }

    public function handleRemoveParticipation($id_event, $kid) {
	if (!is_numeric($id_event))
	    throw new \Nette\InvalidArgumentException("Argument id_event has to be type of numeric, $id_event given");
	if (!is_numeric($kid))
	    throw new \Nette\InvalidArgumentException("Argument kid has to be type of numeric, $kid given");

	$part = new \florbalMohelnice\Entities\Participation(array("id_event" => $id_event, "kid" => $kid));
	try {
	    $this->model->deleteParticipation($part);
	} catch (Nette\IOException $x) {
	    $this->presenter->flashMessage('Nastala chyba, nebyl jste příhlášen', 'error');
	    \Nette\Diagnostics\Debugger::log($x . 'KID = ' . $this->kid . ', EVENT ID = ' . $this->idEvent . ' ;', \Nette\Diagnostics\Debugger::ERROR);
	}
	$this->parent->redirect('this');
    }

    public function addParticipationsByAdmin($idEvent, array $array) {

	foreach ($array as $key => $e) {
	    $part = new \florbalMohelnice\Entities\Participation(
		    array(
		'kid' => $key,
		'id_event' => $idEvent,
		'participation' => \florbalMohelnice\Entities\Participation::YES_GOING_ADM,
		'comment' => $e));
	    try {
		$this->model->createParticipation($part);
	    } catch (\Nette\IOException $x) {
		\Nette\Diagnostics\Debugger::log($x->getMessage(), \Nette\Diagnostics\Debugger::ERROR);
		$this->parent->flashMessage("Účast se nepodařilo uložit");
	    }
	}
	$this->parent->redirect('this');
    }

    public function addParticipation(\florbalMohelnice\Entities\Participation $part) {
	try {
	    $this->model->createParticipation($part);
	} catch (\Nette\IOException $x) {
	    if ($x->getCode() == 1) {
		$this->presenter->flashMessage('K účasti na této akci jste se už vyjádřil/a', 'information');
	    } else {
		$this->presenter->flashMessage('Nastala chyba, nebyl jste příhlášen', 'error');
		\Nette\Diagnostics\Debugger::log($x . 'KID = ' . $this->kid . ', EVENT ID = ' . $this->idEvent . ' ;', \Nette\Diagnostics\Debugger::ERROR);
	    }
	}
	$this->redirectEvent();
    }

    public function editParticipation(\florbalMohelnice\Entities\Participation $part) {
	// snad nejaky overeni prav ?
	try {
	    $this->model->updateParticipation($part);
	} catch (\Nette\IOException $x) {
	    $this->presenter->flashMessage('Nastala chyba, záznam nebyl upraven', 'error');
	    \Nette\Diagnostics\Debugger::log($x . 'KID = ' . $this->kid . ', EVENT ID = ' . $this->idEvent . ' ;', \Nette\Diagnostics\Debugger::ERROR);
	}
	$this->redirectEvent();
    }

    public function redirectEvent() {
	$this->presenter->redirect("Club:showEvent", array("id" => $this->idEvent));
    }

    public function createComponentYesForm($name) {
	$c = new ParticipationForm($this, $name, \florbalMohelnice\Entities\Participation::YES_GOING_OWN, $this->kid, $this->idEvent);
	return $c;
    }

    public function createComponentNoForm($name) {
	$c = new ParticipationForm($this, $name, \florbalMohelnice\Entities\Participation::NO_NOT_GOING_OWN, $this->kid, $this->idEvent);
	return $c;
    }

    public function createComponentEventParticipationAdminForm($name) {
	$usrs = $this->parent->getUserModel()->getSelectUsers();
	$c = new \florbalMohelnice\Forms\EventParticipationAdminForm($this, $name, $this->idEvent, $usrs);

	return $c;
    }

}
