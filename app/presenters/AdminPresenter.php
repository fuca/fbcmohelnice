<?php

use Nette\Application\UI\Form,
    florbalMohelnice\Entities\User,
    florbalMohelnice\Entities\Payment,
    florbalMohelnice\Entities\CreditEntry,
    florbalMohelnice\Entities\MicroPayment,
    florbalMohelnice\Entities\Order,
    florbalMohelnice\Entities\WallPost,
    florbalMohelnice\Entities\Article,
    florbalMohelnice\Entities\Event,
    florbalMohelnice\Entities\Forum,
    florbalMohelnice\Entities\Season,	
    florbalMohelnice\Components\ProfilePermitControl,
    florbalMohelnice\Forms\OrderForm,
    florbalMohelnice\Forms\ArticleForm,
    florbalMohelnice\Forms\MicroPaymentForm,
    florbalMohelnice\Forms\PaymentForm,
    florbalMohelnice\Forms\WallPostForm,
    florbalMohelnice\Forms\EventForm,
    florbalMohelnice\Forms\ForumForm,
    florbalMohelnice\Forms\UserForm,
    Nette\Diagnostics\Logger,
    Grido\Grid,
    Grido\Components\Actions\Action,
    Grido\Components\Filters\Filter,
    Grido\Components\Columns\Column,
    Grido\Components\Columns\Date,
    Nette\Image,
    Nette\Diagnostics\Debugger;

/**
 * Administration presenter of fbcmoh IS.
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class AdminPresenter extends SecuredPresenter {

    const LAYOUT_STYLE = "admin";
    
    public function startup() {
	parent::startup();
	$this->setLayoutStyle(self::LAYOUT_STYLE);
    }

    public function beforeRender() {
	parent::beforeRender();
	$this->template->layoutStyle = $this->getLayoutStyle();
    }

    /** in use */
    public function actionDefault() {
	
    }

    /** in use */
    public function renderDefault() {
	
    }

    public function actionFileAssign() {
	
    }

    public function renderFileAssign() {
	
    }
    
    // ----------------------------- SEASONS -----------------------------------

    public function actionSeason() {
	if (!$this->user->isAllowed('Admin:season', 'view')) {
	    $this->flashMessage("Nemáte dostatečné oprávnění", "error");
	    $this->redirect("Admin:default");
	}
	$addPermit = FALSE;
	if ($this->user->isAllowed('Admin:season', 'create'))
		$addPermit = TRUE;
	$this->template->addPermit = $addPermit;
    }
    
    public function actionAddSeason() {
	if (!$this->user->isAllowed('Admin:season', 'create')) {
	    $this->flashMessage("Na provedení této akce nemáte dostatečné oprávnění", "error");
	    $this->redirect("Admin:season");
	}
    }

    
    public function createComponentSeasonsGrid($name) {
	$filterRenderType = Filter::RENDER_INNER;
	
	$grid = new Grid($this, $name);
	$grid->setModel($this->getSeasonsModel()->getFluent());

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id_season');

	$grid->addColumn('id_season', 'ID')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('label', 'Název')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('date_from', 'Začíná', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATE)
		->setFilter();
	
	$grid->addColumn('date_to', 'Končí', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATE)
		->setFilter();

	$grid->addColumn('comment', 'Komentář')
		->setSortable()
		->setFilter()
		->setSuggestion();
	
	if ($this->user->isAllowed('Admin:season', 'delete'))
	    $grid->setOperations(array('delete' => 'Smazat'), callback($this, 'seasonsGridOperationsHandler'));	
	
	if ($this->user->isAllowed('Admin:season', 'update'))
	    $grid->addAction('edit', '[Edit]', Action::TYPE_HREF, 'editSeason');

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();
	
    }
    
    public function createComponentCreateSeasonForm($name) {
	$c = new \florbalMohelnice\Forms\SeasonForm($this, $name);
	try {
	    $groups = $this->getGroupsModel()->getSelectGroups();    
	} catch (\Nette\IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Chyba čtení skupin z databáze', 'error');
	    $this->redirect('season');
	}
	$c->setCategories($groups);
	$c->init();
	return $c;
    }
    
    public function createComponentUpdateSeasonForm($name) {
	$c = new \florbalMohelnice\Forms\SeasonForm($this, $name);
	try {
	    $groups = $this->getGroupsModel()->getSelectGroups();    
	} catch (\Nette\IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Chyba čtení skupin z databáze', 'error');
	    $this->redirect('season');
	}
	$c->setCategories($groups);
	$c->init(\florbalMohelnice\Forms\SeasonForm::UPDATE_MODE);
	return $c;
    }
    
    public function actionEditSeason($id_season) {
	try {
	    $season = $this->getSeasonsModel()->getSeason($id_season);
	} catch (\Nette\IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Chyba čtení z databáze', 'error');
	    $this->redirect('season');
	}
	$form = $this->getComponent('updateSeasonForm');
	$form->setDefaults($season);
	$this->template->label = $season->label;
    }
    
    public function createSeason(Season $season) {
	if (!$this->user->isAllowed('Admin:season', 'create')) {
	    $this->flashMessage("Na provedení této akce nemáte dostatečné oprávnění", "error");
	    $this->redirect("Admin:season");
	}
	try {
	    $season->offsetSet('updated_kid', $this->getUserId());
	    $this->getSeasonsModel()->createSeason($season);
	} catch(\Nette\IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    if ($ex->getCode() == 1062) {
		$this->flashMessage('Sezóna pro tento rok už existuje, prosím upravte stávající záznam', 'error');
	    } else {
		$this->flashMessage('Sezóna nebyla uložena', 'error');
		$this->redirect('season');
	    }
	} 
	
	$this->redirect('Admin:season');
    }
    
    public function updateSeason(Season $season) {
	if (!$this->user->isAllowed('Admin:season', 'update')) {
	    $this->flashMessage("Na provedení této akce nemáte dostatečné oprávnění", "error");
	    $this->redirect("Admin:season");
	}
	try {
	    $season->offsetSet('updated_kid', $this->getUserId());
	    $this->getSeasonsModel()->updateSeason($season);
	    $this->flashMessage('Změny byly úspěšně uloženy', 'success');
	} catch(\Nette\IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Sezóna nebyla upravena', 'error');
	    $this->redirect('season');
	}
	$this->redirect('Admin:season');
    }
    
    public function seasonsGridOperationsHandler($op, $ids) {
	switch ($op) {
	    case 'delete':
		    $this->deleteSeasons($ids);
		break;
	}
    }
    
    public function deleteSeasons(array $ids) {
	
	foreach ($ids as $i) {
	    try {
		$this->getSeasonsModel()->deleteSeason(
			new Season(array(
					'id_season' => $i)));
	    } catch(\Nette\IOException $ex) {
		Debugger::log($ex->getMessage(), Debugger::ERROR);
		$this->flashMessage("Sezóna '$i' nebyla smazána", 'error');
		$this->redirect('season');
	    }
	}
	$this->redirect('this');
    }
    

// ----------------------------- PAYMENTS ----------------------------------

    /** in use */
    public function renderPayments() {
	if (!$this->user->isAllowed("Admin:payments", "view")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění", "error");
	    $this->redirect("Admin:default");
	}
	$editPermit = FALSE;
	if ($this->user->isAllowed("Admin:payments", "update"))
	    $editPermit = TRUE;
	$this->template->editPermit = $editPermit;
    }

    /** in use */
    public function actionAddPayment() {
	if (!$this->user->isAllowed("Admin:payments", "create")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
    }

    /** in use */
    public function renderAddPayment() {
	
    }

    /** in use */
    public function actionEditPayment($id_payment) {
	if (!$this->user->isAllowed("Admin:payments", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	if (!is_numeric($id_payment)) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Kriticka chyba, špatný formát argumentu', 'error');
	    $this->redirect('Admin:Payments');
	}
	$form = $this->getComponent('editPaymentForm');
	try {
	    $py = $this->getService('paymentModel')->getPayment((integer) $id_payment);
	} catch (\Nette\ArgumentOutOfRangeException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage($ex->getMessage(), 'error');
	    $this->redirect('payments');
	}
	$form->setDefaults($py);
    }

    /** in use */
    public function renderEditPayment() {
	
    }

    /** in use */
    public function createPayment(florbalMohelnice\Entities\Payment $py) {
	$this->getService('paymentModel')->createPayment($py);
	$this->flashMessage('Platba zadána', 'success');
	//$this->redirect('payments');
    }

    /** in use */
    public function updatePayment(florbalMohelnice\Entities\Payment $py) {

	$this->getService('paymentModel')->updatePayment($py);
	$this->flashMessage('Změny uloženy', 'success');
	$this->redirect('payments');
    }

    /** in use */
    public function deletePayments(array $ids) {
	if (!$this->user->isAllowed("Admin:payments", "delete")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$model = $this->getService('paymentModel');
	foreach ($ids as $id) {
	    try {
		$model->removePayment((integer) $id);
	    } catch (\Nette\OutOfRangeException $ex) {
		Debugger::log($ex->getMessage(), Debugger::ERROR);
		$this->flashMessage('Platba nebyla smazána', 'error');
	    }

	    $this->flashMessage("Platba s id $id byla smazána");
	}
	$this->redirect('Admin:payments');
    }

    /** in use */
    protected function createComponentPaymentsGrid($name) {
	$filterRenderType = Filter::RENDER_INNER;
	
	$status = Payment::getStates();
	$grid = new Grid($this, $name);
	$grid->setModel($this->getService('paymentModel')->getFluent());

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id_payment');

	$grid->addColumn('kid', 'KID')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('surname', 'Příjmení')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('name', 'Jméno')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('season', 'Sezóna')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('subject', 'Předmět')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('amount', 'Částka')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('pay_day', 'Splatnost', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATE)
		->setFilter();

	$grid->addColumn('status', 'Zaplaceno')
		->setReplacement($status)
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addAction('edit', 'Upravit', Action::TYPE_HREF, 'editPayment');
	$grid->setOperations(array('delete' => 'Smazat'), callback($this, 'paymentsGridOperationsHandler'));

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();
    }

    /** in use */
    public function paymentsGridOperationsHandler($operation, $ids) {
	switch ($operation) {
	    case 'delete':
		$this->deletePayments($ids);
		break;
	}
    }

    /** in use */
    public function createComponentAddPaymentForm($name) {
	$msg = 'Komponenta je nedostupna, omlouvame se za zpusobene potize';
	try {
	    $eseasons = $this->getSeasonsModel()->getSelectSeasons();
	    $users = $this->getService('userModel')->getSelectUsers();
	} catch(\Nette\IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage($msg, 'error');
	    $this->redirect('Admin:Payments');
	}
	try {
	    $c = new PaymentForm($this, $name, $users, $eseasons, 'create');
	} catch (Nette\InvalidStateException $ex) {
	    $this->flashMessage($msg, 'error');
	} catch (InvalidArgumentException $ex) {
	    $this->flashMessage($msg, 'error');
	}
	return $c;
    }

    /** in use */
    protected function createComponentEditPaymentForm($name) {
	$msg = 'Komponenta je nedostupna, omlouvame se za zpusobene potize';
	try {
	    $eseasons = $this->getSeasonsModel()->getSelectSeasons();
	    $c = new PaymentForm($this, $name, $this->getService('userModel')->getSelectUsers($this->getUserId(), FALSE), $eseasons, 'update');
	} catch (Nette\InvalidStateException $ex) {
	    $this->flashMessage($msg, 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	} catch (InvalidArgumentException $ex) {
	    $this->flashMessage($msg, 'error');
	}
	return $c;
    }

// -------------------------------- CREDITS --------------------------------
    /** in use */
    public function actionCredit() {
	if (!$this->user->isAllowed("Admin:credit", "view")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$addPermit = FALSE;
	if ($this->user->isAllowed("Admin:credit", "create"))
		$addPermit = TRUE;
	$this->template->addPermit = $addPermit;
    }

    /** in use */
    public function renderCredit() {
	
    }

    public function actionEditCredit($id_credit) {
	if (!$this->user->isAllowed("Admin:credit", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	// overeni prav
	if (!is_numeric($id_credit)) {
	    $this->log("ActionEditCredit - Invalid argument exception. Argument is not numeric type.", Logger::CRITICAL);
	    $this->flashMessage('Špatný formát argumentu', 'error');
	    $this->redirect("Admin:Credit");
	    return;
	}
	$form = $this->getComponent('editCreditEntryForm');
	try {
	    $values = $this->getService('creditModel')->getEntry((integer) $id_credit);
	} catch (Nette\ArgumentOutOfRangeException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage($ex, 'error');
	    $this->redirect('Admin:Credit');
	}
	$form->setDefaults($values);
    }

    public function createComponentAdminUserCreditsGrid($name) {
	$c = new \florbalMohelnice\Components\CreditsControl($this, $name);
	$kid = $this->getUser()->getIdentity()->getId();
	$c->setEditMode(TRUE);
	$c->setId($kid);
	return $c;
    }

    /** in use */
    public function createComponentCreditsGrid($name) {
	$filterRenderType = Filter::RENDER_OUTER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getService('creditModel')->getFluent());

	$grid->setDefaultPerPage(50);
	$grid->setPrimaryKey('kid');

	$grid->addColumn('kid', 'KID')
		->setFilter()
		->setSuggestion();

	$grid->addColumn('name', 'Jméno')
		->setFilter()
		->setSuggestion();

	$grid->addColumn('surname', 'Příjmení')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('credit_count', 'Počet kreditů')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('season', 'Sezóna')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('subject', 'Předmět')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addAction('edit', 'Detail', Action::TYPE_HREF, 'creditsDetail');
	$grid->setFilterRenderType($filterRenderType);
    }

    public function actionCreditsDetail($kid) {
	if (!$this->user->isAllowed("Admin:creditDetail", "view")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	if (!is_numeric($kid))
	    throw new InvalidArgumentException('Argument has to be a numeric');
	$this->template->data = $this->getService('creditModel')->getUserCredit((int) $kid);
	$this->template->user = $this->getService('userModel')->getUserByKid((int) $kid);
    }

    /** in use */
    public function createComponentAddCreditEntryForm($name) {
	$msg = 'Komponenta je nedostupna, omlouvame se za zpusobene potize';
	try {
	    $users = $this->getUserModel()->getSelectUsers();
	    $rewards = $this->getService('creditModel')->getRewardsSelect();
	    $seasons = $this->getSeasonsModel()->getSelectSeasons();
	    $c = new florbalMohelnice\Forms\CreditForm($this, $name, $users, $rewards, $seasons, 'create');
	} catch (Nette\InvalidStateException $ex) {
	    $this->flashMessage($msg, 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	} catch (InvalidArgumentException $ex) {
	    $this->flashMessage($msg, 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	return $c;
    }

    /** in use */
    public function createComponentEditCreditEntryForm($name) {
	$msg = 'Komponenta je nedostupna, omlouvame se za zpusobene potize';
	try {
	    $users = $this->getUserModel()->getSelectUsers();
	    $rewards = $this->getService('creditModel')->getRewardsSelect();
	    $seasons = $this->getSeasonsModel()->getSelectSeasons();
	    $c = new florbalMohelnice\Forms\CreditForm($this, $name, $users, $rewards, $seasons, 'update');
	} catch (Nette\InvalidStateException $ex) {
	    $this->flashMessage($msg, 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	} catch (InvalidArgumentException $ex) {
	    $this->flashMessage($msg, 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	return $c;
    }

    /**
     * 
     * @param CreditEntry $ce
     */
    public function createCreditEntry(CreditEntry $ce) {
	if (!$this->user->isAllowed("Admin:credit", "create")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$this->getService('creditModel')->createCreditEntry($ce);
	$this->flashMessage('Záznam byl přidán', 'success');
	$this->redirect('Admin:credit');
    }

    /**
     * 
     * @param CreditEntry $ce
     */
    public function updateCreditEntry(CreditEntry $ce) {
	if (!$this->user->isAllowed("Admin:credit", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$this->getService('creditModel')->updateCreditEntry($ce);
	$this->flashMessage('Záznam byl upraven', 'success');
	$this->redirect('Admin:credit');
    }

    public function actionAddCredit() {
	if (!$this->user->isAllowed("Admin:credit", "create")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
    }

// ----------------------------- EVENTS ------------------------------------

    public function actionEvents() {
	if (!$this->user->isAllowed("Admin:events", "view")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$addPermit = FALSE;
	if ($this->user->isAllowed("Admin:events", "create"))
		$addPermit = TRUE;
	$this->template->addPermit = $addPermit;
    }

    public function renderEvents() {
	
    }

    /**
     *
     */
    public function createComponentEditEventForm($name) {
	$groups = $this->getGroupsModel()->getSelectGroups();
	$types = $this->getEventsModel()->getSelectTypes();
	$users = $this->getUserModel()->getSelectUsers();
	$c = new EventForm($this, $name, $groups, $types, $users, EventForm::UPDATE_MODE);
	return $c;
    }

    /**
     *
     */
    public function createComponentAddEventForm($name) {
	$groups = $this->getGroupsModel()->getSelectGroups();
	$types = $this->getEventsModel()->getSelectTypes();
	$users = $this->getUserModel()->getSelectUsers();
	$c = new EventForm($this, $name, $groups, $types, $users);
	return $c;
    }

    /**
     *
     */
    public function createComponentAdminEventsGrid($name) {
	$eventTypes = array('' => '') + $this->getEventsModel()->getSelectTypes();
	$participation = array('' => '') + Event::getParticipationModes();
	$users = array('' => '') + $this->getUserModel()->getSelectUsers();
	$visModes = array('' => '') + Event::getVisibilityModes();
	$comments = array('' => '') + Event::getCommModes();
	$filterRenderType = Filter::RENDER_OUTER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getEventsModel()->getFluent());

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id_event');

	$grid->addColumn('id_event', 'Typ')
		->setReplacement($eventTypes)
		->setSortable()
		->setFilter(Filter::TYPE_SELECT, $eventTypes);

	$grid->addColumn('title', 'Titulek')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('take_place_from', 'Od', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATETIME)
		->setFilter();


	$grid->addColumn('take_place_to', 'Do', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATETIME)
		->setFilter();

	$grid->addColumn('participation_mode', 'Přítomnost')
		->setReplacement($participation)
		->setSortable()
		->setFilter(Filter::TYPE_SELECT, $participation);


	$grid->addColumn('visibility', 'Viditelnost')
		->setReplacement($visModes)
		->setSortable()
		->setFilter(Filter::TYPE_SELECT, $visModes);

	$grid->addColumn('ordered_kid', 'Přidal')
		->setReplacement($users)
		->setSortable()
		->setFilter(Filter::TYPE_SELECT, $users);

	$grid->addColumn('ordered_time', 'Přidáno', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATETIME)
		->setFilter();

	$grid->addColumn('comment_mode', 'Komentáře')
		->setReplacement($comments)
		->setSortable()
		->setFilter(Filter::TYPE_SELECT, $comments);

	$grid->setOperations(array('delete' => 'Smazat'), callback($this, 'eventsGridOperationsHandler'))
		->setConfirm('delete', 'Are you sure you want to delete %i items?');

	$grid->addAction('editEvent', 'Upravit')
		->setIcon('pencil');

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting("events" . " " . date("Y-m-d H:i:s", time()));
    }
    
    public function eventsGridOperationsHandler($op, $ids) {
	switch($op) {
	    case 'delete':
		$this->removeEvent($ids);
	    break;
	}
    }

    /**
     *
     */
    public function actionEditEvent($id_event) {
	if (!$this->user->isAllowed("Admin:events", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	if (!is_numeric($id_event)) {
	    $this->flashMessage('Identifikator události musí být číslo', 'error');
	    return;
	}
	try {
	    $ev = $this->getEventsModel()->getEvent($id_event);
	} catch (IOException $ex) {
	    $this->flashMessage('Událost se nepodařilo načíst', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('Admin:events', 'error');
	}
	if ($ev === FALSE) {
	    $this->flashMessage('Událost se nepodařilo načíst', 'error');
	    $this->redirect('Admin:events', 'error');
	}
	$f = $this->getComponent('editEventForm');
	$f->setDefaults($ev);
    }

    public function actionAddEvent() {
	if (!$this->user->isAllowed("Admin:events", "create")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
    }

    /**
     *
     */
    public function addEvent(Event $ev) {

	$ev->offsetSet('ordered_kid', $this->getUserId());
	try {
	    $this->getEventsModel()->createEvent($ev);
	} catch (IOException $ex) {
	    $this->flashMessage('Událost se nepodařilo uložit', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('Admin:events');
	}
	$this->flashMessage('Událost byla uložena', 'success');
	$this->redirect('Admin:events');
    }

    /**
     *
     */
    public function removeEvent(array $ids) {

	if (!$this->user->isAllowed("Admin:events", "delete")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	foreach ($ids as $i) {
	    try {
		$this->getEventsModel()->deleteEvent($i);
	    } catch (IOException $ex) {
		$this->flashMessage("Událost #$i nemohla být smazána", 'error');
		Debugger::log($ex->getMessage(), Debugger::ERROR);
	    }
	    $this->flashMessage("Událost #$i byla smazána", 'success');
	}
	$this->redirect('Admin:events');
    }

    /**
     *
     */
    public function editEvent(Event $ev) {
	if (!$this->user->isAllowed("Admin:events", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	try {
	    $this->getEventsModel()->updateEvent($ev);
	} catch (IOException $ex) {
	    $this->flashMessage('Změny se nepodařilo uložit', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    return;
	}
	$this->flashMessage('Změny byly uloženy', 'success');
	$this->redirect('Admin:events');
    }
    
// --------------------------- PARTICIPATION -----------------------------------
    
    public function actionParticipation() {
    }
    
    public function actionEditParticipation($id_event) {
	if (!$this->user->isAllowed("Admin:participation", "update")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("Admin:participation");
	}
	$this->setEntityName($id_event);
	
	try {
	    $ev = $this->getEventsModel()->getEvent($id_event);
	} catch (IOException $ex) {
	    $this->flashMessage('Událost se nepodařilo načíst', 'error');
	    Debugger::log($ex->getMessage()->getMessage(), Debugger::ERROR);
	}
	$this->setEntity($ev);
	$this->template->event = $ev;
    }
    
    public function createComponentEventParticipation($name) {
	$c = new florbalMohelnice\Components\EventParticipationControl($this, $name);
	$c->setKid($this->user->getId());
	$c->setAdminMode(TRUE);
	try {
	    $c->setIdEvent($this->getEntityName());
	} catch(\Nette\InvalidStateException $x) {
	    $this->flashMessage ('Chyba inicializace modulu EventParticipation', 'error');
	    Debugger::log("ERROR > ClubPresenter:371 > Pokus o inicializaci komponenty za stavu neplatneho id_event", $x);
	    $this->redirect("Club:default");
	}
	$c->setConfirmDate($this->getEntity()->confirm_until);
	$c->setModel($this->getParticipationModel());
	return $c;
    }
    
    public function createComponentParticipationGrid($name) {
	$eventTypes = array('' => '') + $this->getEventsModel()->getSelectTypes();
	$comments = array('' => '') + Event::getCommModes();
	$filterRenderType = Filter::RENDER_OUTER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getEventsModel()->getFluent());

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id_event');

	$grid->addColumn('id_event', 'Typ')
		->setReplacement($eventTypes)
		->setSortable()
		->setFilter(Filter::TYPE_SELECT, $eventTypes);

	$grid->addColumn('title', 'Titulek')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('take_place_from', 'Od', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATETIME)
		->setFilter();

	$grid->addColumn('ordered_time', 'Přidáno', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATETIME)
		->setFilter();

	$grid->addColumn('comment_mode', 'Komentáře')
		->setReplacement($comments)
		->setSortable()
		->setFilter(Filter::TYPE_SELECT, $comments);

	$grid->addAction('editParticipation', 'Upravit')
		->setIcon('pencil');

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting("adminParticipation" . " " . date("Y-m-d H:i:s", time()));
    }
    

// ----------------------------- USERS -------------------------------------

    /** in use */
    public function actionAddUser() {
	if (!$this->user->isAllowed("Admin:users", "create")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
    }

    public function actionUsers() {
	$addPermit = FALSE;
	if ($this->user->isAllowed("Admin:users", "update"))
	    $addPermit = TRUE;
	$this->template->addPermit = $addPermit;
    }

    /** in use */
    public function renderAddUser() {
	
    }

    /** in use */
    public function createUser(User $user) {
	if (!$this->user->isAllowed("Admin:users", "create")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	try {
	    $this->getService('userModel')->createUser($user);
	    $this->flashMessage('Uživatel přidán', 'information');
	} catch (\Nette\InvalidArgumentException $e) {
	    $this->flashMessage($e->getMessage(), 'error');
	    Debugger::log($e->getMessage(), Debugger::ERROR);
	}
    }

    /* in use */
    public function actionEditUser($kid) {
	if (!$this->user->isAllowed("Admin:users", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$comp = $this->getComponent('editUserForm');
	$pays = $this->getComponent('userPaymentsControl');
	$orders = $this->getComponent('userOrdersControl');
	$credits = $this->getComponent('userCredits');
	$events = $this->getComponent('userEvents');
	$pays->setKid($kid);
	$orders->setKid($kid);
	$credits->setId($kid);
	$events->setKid($kid);
	try {
	    $user = $this->getService('userModel')->getUserByKid((integer) $kid);
	} catch (\Nette\ArgumentOutOfRangeException $ex) {
	    $this->flashMessage($ex->getMessage(), 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('users');
	}
	$user->roles = array_keys($user->roles);
	$user->categories = array_keys($user->categories);
	$comp->setDefaults($user->toArray());
    }

    /* in use */
    public function renderEditUser() {}
    
    /* in use */
    public function renderShowUser() {}
    
    /* in use */
    public function actionShowUser($kid) {
	if (!$this->user->isAllowed("Admin:users", "view")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$comp = $this->getComponent('editUserForm');
	$pays = $this->getComponent('userPaymentsControl');
	$orders = $this->getComponent('userOrdersControl');
	$credits = $this->getComponent('userCredits');
	$events = $this->getComponent('userEvents');
	$pays->setKid($kid);
	$orders->setKid($kid);
	$credits->setId($kid);
	$events->setKid($kid);
	
	try {
	    $user = $this->getUserModel()->getUserByKid((integer) $kid);
	} catch (\Nette\ArgumentOutOfRangeException $ex) {
	    $this->flashMessage($ex->getMessage(), 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('users');
	}
	$user->roles = array_keys($user->roles);
	$user->categories = array_keys($user->categories);
	$comp->setDefaults($user->toArray());
	foreach ($comp->getControls() as $c) {
	    $c->setDisabled(TRUE);
	}
	$this->setView('editUser');
    }

    /** in use */
    public function updateUser(User $user) {
	if (!$this->user->isAllowed("Admin:users", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$this->getService('userModel')->updateUser($user, TRUE);
	$this->flashMessage('Uloženo', 'information');
    }

    /** in use */
    public function createComponentAddUserForm($name) {
	return new UserForm($this, $name, $this->getUserModel()->getAllRoles(), $this->getGroupsModel()->getSelectGroups());
    }

    /** in use */
    public function createComponentEditUserForm($name) {
	return new UserForm($this, $name, $this->getUserModel()->getAllRoles(), $this->getGroupsModel()->getSelectGroups(), UserForm::UPDATE_MODE);
    }

    public function createComponentUserPaymentsControl($name) {
	$c = new \UserPaymentsControl($this, $name);
	$c->setModel($this->getPaymentModel());
	return $c;
    }

    public function createComponentUserOrdersControl($name) {
	$c = new \UserOrdersControl($this, $name);
	$c->setModel($this->getOrdersModel());
	return $c;
    }

    public function createComponentUserCredits($name) {
	$c = new \florbalMohelnice\Components\CreditsControl($this, $name);
	return $c;
    }

    public function createComponentUserEvents($name) {
	$c = new \florbalMohelnice\Components\UserEventsControl($this, $name);
	$c->setGroupsModel($this->getGroupsModel());
	$c->setModel($this->getEventsModel());
	return $c;
    }

    /* in use */
    private function toggleActivity($kid) {
	if (!$this->user->isAllowed("Admin:users", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	try {
	    $this->getService('userModel')->toggleActivity($kid);
	} catch (Nette\InvalidStateException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage($ex->getMessage(), 'error');
	}
    }

    public function actionShowWebProfile($kid) {
	if (!$this->user->isAllowed("Admin:permit", "view")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$permitPermit = FALSE;
	if (!is_numeric($kid))
	    throw new \Nette\InvalidArgumentException('Špatný formát argumentu');
	try {
	    $data = $this->getUserModel()->getWebProfilesFluent($kid)->execute()->fetch();
	} catch (\Nette\IOException $ex) {
	    $this->flashMessage('Chyba v získání dat', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    return;
	}
	if (!$this->user->isAllowed("Admin:permit", array("update", "create")))
	    $permitPermit = TRUE;
	$this->template->permitPermit = $permitPermit;
	$this->template->data = $data;
    }

    public function createComponentPermitGrid($name) {
	$filterRenderType = Filter::RENDER_INNER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getUserModel()->webProfilesForCheckFluent());

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('kid');

	$grid->addColumn('kid', 'KID')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('surname', 'Příjmení')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('name', 'Jméno')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addAction('show', 'Zobrazit', Action::TYPE_HREF, 'showWebProfile');
	
	$grid->setOperations(array('permit' => 'Schválit'), callback($this, 'editWebProfileOperationsHandler'));

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();
	
	return $grid;
    }

    public function handlePermitProfile(array $kids) {
	if (!$this->user->isAllowed("Admin:permit", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	foreach ($kids as $kid) {
	    try {

		$this->getUserModel()->permitWebProfile($kid);
	    } catch (\Nette\IOException $ex) {
		$this->flashMessage('Schváení profilu \"' . $kid . '\" selhalo, zkuste to prosím později -- ' . $ex->getMessage(), 'error');
		Debugger::log($ex->getMessage(), Debugger::ERROR);
		return;
	    }

	    $user = $this->getUser();
	    $i = $user->getIdentity();
	    $uid = $i->getId();
	    if ($uid == $kid) {
		$data = $i->getData();
		$data['profile_required'] = 'con';
	    }
	}
	$this->redirect('Admin:permit');
    }

    public function editWebProfileOperationsHandler($operation, $ids) {
	switch ($operation) {
	    case 'permit':
		$this->handlePermitProfile($ids);
		break;
	}
    }

    /**
     * Component factory for users grid.
     * @param $name
     */
    public function createComponentUsersGrid($name) {

	$filterRenderType = Filter::RENDER_OUTER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getUserModel()->getFluent());

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('kid');

	$grid->addColumn('kid', 'KID')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('surname', 'Příjmení')
		->setSortable()
		->setFilter()
		->setSuggestion();

		$grid->getColumn('surname')->getCellPrototype()->class('textleft');

	$grid->addColumn('name', 'Jméno');

		$grid->getColumn('name')->getCellPrototype()->class('textleft');

	$grid->addColumn('year', 'Ročník')
		->setSortable()
		->setFilter()
		->setSuggestion();

	/*$grid->addColumn('email', 'E-mail', Column::TYPE_MAIL)
		->setSortable()
		->setFilter()
		->setSuggestion();*/

	$grid->addColumn('activity', 'Aktivní')
		->setReplacement(array('act' => 'A', 'una' => 'N'))
		->setFilter(Filter::TYPE_SELECT, array('' => '', 'act' => 'A', 'una' => 'N'));

	$grid->addColumn('last_logged', 'Poslední přihlášení', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATETIME);

		$grid->getColumn('last_logged')->getCellPrototype()->class('textsmall');
	
	$seas = $this->getActualSeasonId();
	try {
	    $seas = $this->getSeasonsModel()->getSeason($seas);
	} catch (\Nette\IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	
	if ($seas != FALSE)
	    $seas = $seas->label;

	if ($this->user->isAllowed("Admin:users", "update"))
	    $grid->setOperations(array('deactivate' => 'De/aktivovat', 'application' => 'Přihlásit na sezónu '. $seas), callback($this, 'usersGridOperationsHandler'))
		 ->setConfirm('deactivate', 'Určitě chcete vybráné členy de/aktivovat?')
		 ->setConfirm('application', 'Určitě chcete vybráné členy přihlásit na nejbližší sezónu?');
		
	
	if ($this->user->isAllowed("Admin:users", "update")) {
	    $grid->addAction('edit', '[Edit]', Action::TYPE_HREF, 'editUser');
	} else {
	    if ($this->user->isAllowed("Admin:users", "view"))
	    $grid->addAction('show', '[Zobraz] ', Action::TYPE_HREF, 'showUser');
	}
	$grid->addAction('application', '[Prihl]', Action::TYPE_HREF, 'userApplications');	

	$grid->setFilterRenderType($filterRenderType);
	//$grid->setDefaultFilter(array('activity' => 'act'));
	$grid->setExporting("users" . " " . date("Y-m-d H:i:s", time()));
	
	return $grid;
    }

    /* in use */

    public function usersGridOperationsHandler($operation, $id) {
	switch ($operation) {
	    case 'deactivate':
		foreach ($id as $i) {
		    $this->toggleActivity($i);
		}
		break;
	    case 'application':
		foreach ($id as $i) {
		    $this->_semiAutomaticApplication($i);
		}
		$this->redirect('this');
		break;
	}
    }
    
    private function _semiAutomaticApplication($kid) {
	if (!is_numeric($kid))
	    throw new \Nette\InvalidArgumentException("Argument kid has to be type of numeric, $kid given");
	$sesId = $this->getActualSeasonId();
	try {
	    $season = $this->getSeasonsModel()->getSeason($sesId);
	    if (!$season)
		throw new \Nette\IOException("Nekonzistence databáze, chybí záznam pro sezónu $sesId", 999);
	    
	    $app = new \florbalMohelnice\Entities\SeasonApplication(array());
	    $app->offsetSet('id_season', $sesId);
	    $app->offsetSet('kid', $kid);
	    $app->offsetSet('clp_status', florbalMohelnice\Entities\Payment::STATUS_UNPAYED);
	    $app->offsetSet('enrolled_time', new Nette\DateTime());
	    $app->offsetSet('clp_kid', $this->getUserId());
	    $app->offsetSet('clp_time', new Nette\DateTime());
	    $usersGroupCreditTax = $this->getGroupsModel()->getUsersHomeGroupCreditTax(new User(array('kid'=>$kid)), $this->getActualSeasonId());
	    $app->offsetSet('credits', $usersGroupCreditTax);
	    $app->offsetSet('contacts_status', florbalMohelnice\Entities\Contact::STATUS_PENDING);
	    $app->offsetSet('contacts_time', new Nette\DateTime());
	    $this->getSeasonApplicationsModel()->createApplication($app);
	} catch (\Nette\IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    switch($ex->getCode()) {
		case 999:
		    $this->flashMessage($ex->getMessage(), 'error');
		    break;
		default:
		    $this->flashMessage("Přihlášku pro člena $kid se nepodařilo vytvořit", 'error');
	    }
	}
    }
    
    /**
     * 
     * @param type $kid
     */
    public function actionUserApplications($kid) {
	if (!$this->user->isAllowed("Admin:users", "view")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
            $this->redirect("Admin:default");
        }
	$user = $this->getDBUser($kid);
	
	$permit = FALSE;
	if ($this->user->isAllowed("Admin:users", "update"))
	    $permit = TRUE;
	$this->template->editPermit = $permit;
	$this->template->user = $user;
    }
    
    public function createComponentUserApplicationsGrid($name) {
	$filterRenderType   = Filter::RENDER_OUTER;
	$users		    = array('','') + $this->getUserModel()->getSelectUsers();
	$contactStatus	    = array('','') + \florbalMohelnice\Entities\Contact::getSelectStatus();
	$clpStatus	    = array('','') + florbalMohelnice\Entities\Payment::getStates();
	$seasons	    = array('','') + $this->getSeasonsModel()->getSelectSeasons();

	$grid = new Grid($this, $name);
	$grid->setModel($this->getSeasonApplicationsModel()->getFluent($this->getParam('kid')));

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id_season');

	$grid->addColumn('id_season', 'Sezóna')
		->setReplacement($seasons)
		->setSortable()
		->setFilter()
		->setSuggestion();
	
	$grid->addColumn('enrolled_time', 'Zadáno', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATETIME);
	
	$grid->addColumn('clp_status', 'Stav ČLP')
		->setReplacement($clpStatus)
		->setSortable()
		->setFilter();
	
	$grid->addColumn('clp_kid', 'ČLP zadal' )
		->setReplacement($users)
		->setSortable()
		->setFilter();
	
	$grid->addColumn('contacts_status', 'Stav kontaktu')
		->setReplacement($contactStatus)
		->setSortable()
		->setFilter();
	
	$grid->addColumn('clp_time', 'ČLP', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATETIME);
	
	$grid->addColumn('contacts_time', 'Kontakty', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATETIME);
	
	$grid->addAction('edit', '[Edit]', Action::TYPE_HREF, 'editApplication');
	
	$grid->setFilterRenderType($filterRenderType);
        $grid->setExporting("seasonApplications" . " " . date("Y-m-d H:i:s", time()));
	
	return $grid;
    }
    
    
    public function getDBUser($kid) {
	if (!is_numeric($kid)) {
	    $this->flashMessage("Spatny format argumentu", "error");
	    //$this->redirect("Admin:users");
	}
	$this->setEntityName($kid);
	$user = NULL;
	try {
	    $user = $this->getUserModel()->getUserByKid((integer) $kid);
	} catch (\Nette\IOException $ex) {
	    $this->flashMessage("Nepodařilo se získat data o uživateli s kid ($kid)", 'error');
	}
	return $user;
    }
    
    public function actionAddApplication($kid) {
	$user = $this->getDBUser($kid);
	$this->template->user = $user;
    }
    
    public function addApplication(florbalMohelnice\Entities\SeasonApplication $app) {
	$kid = $this->getEntityName();
	if(!$this->user->isAllowed('Admin:applications','create')) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    
            $this->redirect("Admin:userApplications", $kid);
	}
	
	try {
	    $usersGroupCreditTax = $this->getGroupsModel()->getUsersHomeGroupCreditTax(new User(array('kid'=>$kid)), $this->getActualSeasonId());
	    $app->offsetSet('credit', $usersGroupCreditTax);
	    $id = $this->getSeasonApplicationsModel()->createApplication($app);
	} catch (\Nette\IOException $ex) {
	    $this->flashMessage('Přihlášku se nepodařilo vytvořit', 'error');
            Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	$this->redirect('Admin:userApplications', $app->kid);
    }
    
    public function updateApplication(florbalMohelnice\Entities\SeasonApplication $app) {
	$kid = $app->offsetGet('kid');
	if(!$this->user->isAllowed('Admin:applications','update')) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $kid = $this->getEntityName();
            $this->redirect("Admin:userApplications", $kid);
	}
	try {
	    $this->getSeasonApplicationsModel()->updateApplication($app);
	} catch (\Nette\IOException $ex) {
	    $this->flashMessage('Přihlášku se nepodařilo upravit', 'error');
            Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	$this->redirect('Admin:userApplications', $kid);
    }
    
    public function actionEditApplication($id_season) {
	$kid = $this->getEntityName();
	$user = $this->getDBUser($kid);
	try {
	    $app = $this->getSeasonApplicationsModel()->getApplications($kid, $id_season);
	} catch (\Nette\IOException $ex) {
	    $this->flashMessage('Přihlášku se nepodařilo načíst', 'error');
            Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('Admin:userApplications', $kid);
	}
	$form = $this->getComponent('updateApplicationForm');
	dump($app);
	$form->setDefaults($app);
	$this->template->user = $user;
    }
    
    public function createComponentCreateApplicationForm($name) {
	$users = $this->getUserModel()->getSelectUsers();
	$seasons = $this->getSeasonsModel()->getSelectSeasonsWithoutApplications($this->getUserId());
	$form = new \florbalMohelnice\Forms\SeasonApplicationForm($this, $name, $seasons, $users);
	$form->setHiddenKid($this->getUserId());
	return $form;
    }
    
        public function createComponentUpdateApplicationForm($name) {
	$users = $this->getUserModel()->getSelectUsers();
	$seasons = $this->getSeasonsModel()->getSelectSeasons();
	$form = new \florbalMohelnice\Forms\SeasonApplicationForm($this, $name, $seasons, $users, \florbalMohelnice\Forms\SeasonApplicationForm::UPDATE_MODE);
	$form->setHiddenKid($this->getUserId());
	return $form;
    }
    
// -------------------------------- ARTICLES -------------------------------


    public function actionArticles() {
        if (!$this->user->isAllowed("Admin:articles", "view")) {
            $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
            $this->redirect("Admin:default");
        }
    }

    public function renderArticles() {
        
    }

    public function actionEditArticle($id_article) {
        if (!$this->user->isAllowed("Admin:articles", "update")) {
            $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
            $this->redirect("Admin:default");
        }
        if (!is_numeric($id_article)) {
            $this->flashMessage('Špatný formát argumentu','error');
            $this->redirect('Admin:articles');
        }
        try {
            $art = $this->getArticlesModel()->getArticle($id_article);
        } catch (IOException $ex) {
            $this->flashMessage('Článek se nepodařilo načíst', 'error');
            Debugger::log($ex->getMessage(), Debugger::ERROR);
        }
        if (!$art) { // tohle dodělat snad všude kde to jde
            $this->flashMessage('Článek sse nepodařilo načíst', 'error');
            $this->redirect('Admin:articles');
        }
        $c = $this->getComponent('editArticleForm');	

	$c->setDefaults($art);
    }

    public function renderEditArticle() {
        
    }

    public function actionAddArticle() {
        if (!$this->user->isAllowed("Admin:articles", "create")) {
            $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
            $this->redirect("Admin:default");
        }
    }

    public function renderAddArticle() {
        
    }

    public function updateArticle(Article $art) {
	    $fupl = null;
	    
            $fsel = $art->offsetGet('selectPicture');
            $art->offsetUnset('selectPicture');
	    
            if ($art->offsetExists('picture')) {
		$fupl = $art->offsetGet('picture');
		$art->offsetUnset('picture');
	    }
            $imgName = 'empty.jpg';
        try {
            if ($fsel != NULL) {
                $art->offsetSet('picture', $fsel);
            } else {
                if ( $fupl != null && $fupl->isOk())
                    $imgName = $fupl->name;
                $art->offsetSet('picture', $imgName);
                
                
                if ($fupl != null && $fupl->isOk() && $fupl->isImage()) { // is there any error?
                    $imgPath = ARTICLES_IDIR . "/" . $imgName;

                    // smazu stary obrazek
                    if (file_exists($imgPath))
                        unlink(ARTICLES_IDIR . "/" . $imgName);

                    // ulozim obrazek
                    $fupl->move($imgPath);
                }
                $this->getArticlesModel()->updateArticle($art);
            }
        } catch (IOException $ex) {
            $this->flashMessage('Změna nemohla být provedena', 'error');
            Debugger::log($ex->getMessage(), Debugger::ERROR);
            return;
        }
        $this->redirect('Admin:articles');
    }

    public function createArticle(Article $art) {
        $fupl = null;
        
        $fsel = $art->offsetGet('selectPicture');
        $art->offsetUnset('selectPicture');
	
	if ($art->offsetExists('picture')) {
		$fupl = $art->offsetGet('picture');
		$art->offsetUnset('picture');
	}
        $imgName = "empty.jpg";

        try {
            $art->offsetSet('kid', $this->getUserId());
            if ($fsel != NULL) {
                $art->offsetSet('picture', $fsel);
            } else {
            
                if ($fupl != null && $fupl->isOk())
                    $imgName = $fupl->name;
                $art->offsetSet('picture', $imgName);
                $id = $this->getArticlesModel()->createArticle($art);

                if ($fupl != null && $fupl->isOk() && $fupl->isImage()) { // is there any error?
                    $imgPath = ARTICLES_IDIR . "/" . $imgName;

                    // smazu stary obrazek
                    if (file_exists($imgPath))
                        unlink(ARTICLES_IDIR . "/" . $imgName);

                    // ulozim obrazek
                    $fupl->move($imgPath);
                }
            }
        } catch (IOException $ex) {
            $this->flashMessage('Článek se nepodařilo uložit', 'error');
            Debugger::log($ex->getMessage(), Debugger::ERROR);
        }
        $this->redirect('Admin:articles');
    }

    public function deleteArticle($ids) {
        if (!$this->user->isAllowed("Admin:articles", "delete")) {
            $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
            $this->redirect("Admin:default");
        }
        foreach ($ids as $id) {
            try {
                $this->getArticlesModel()->deleteArticle($id);
            } catch (IOException $ex) {
                $this->flashMessage("Záznam #$id nešel smazat", 'error');
                Debugger::log($ex->getMessage(), Debugger::ERROR);
            }
        }
    }

    /**
     *
     */
    public function createComponentAddArticleForm($name) {
        $usrs = $this->getUserModel()->getSelectUsers();
        $grps = $this->getGroupsModel()->getSelectGroups();
        $images = $this->getFileModel()->getArticlesIdirContent();
        $c = new ArticleForm($this, $name, $usrs, $grps, $images);
        return $c;
    }

    /**
     *
     */
    public function createComponentEditArticleForm($name) {
        $usrs = $this->getUserModel()->getSelectUsers();
        $grps = $this->getGroupsModel()->getSelectGroups();
        $images = $this->getFileModel()->getArticlesIdirContent();        
        $c = new ArticleForm($this, $name, $usrs, $grps, $images, ArticleForm::UPDATE_MODE);
        return $c;
    }

    public function createComponentAdminArticlesGrid($name) {

        $users = array('' => '') + $this->getUserModel()->getSelectUsers();
        $status = array('' => '') + Article::getStatusModes();
        //$highlight = array('' => '', 0 => 'Ne', 1 => 'Ano');
        $comments = array('' => '') + Article::getSelectCommModes();
        $filterRenderType = Filter::RENDER_OUTER;

        $grid = new Grid($this, $name);
        $grid->setModel($this->getArticlesModel()->getFluent());

        $grid->setDefaultPerPage(30);
        $grid->setPrimaryKey('id_article');


        $grid->addColumn('id_article', 'Id')
                ->setSortable()
                ->setFilter()
                ->setSuggestion();

        $grid->addColumn('kid', 'Autor')
                ->setReplacement($users)
                ->setSortable()
                ->setFilter(Filter::TYPE_SELECT, $users);

		$grid->getColumn('kid')->getCellPrototype()->class('textsmall');

        $grid->addColumn('title', 'Titulek')
                ->setSortable()
                ->setFilter()
                ->setSuggestion();

		$grid->getColumn('title')->getCellPrototype()->class('textsmall');

        $grid->addColumn('status', 'Status')
                ->setReplacement($status)
                ->setSortable()
                ->setFilter(Filter::TYPE_SELECT, $status);

        $grid->addColumn('updated_time', 'Poslední změna', Column::TYPE_DATE)
                ->setSortable()
                ->setDateFormat(Date::FORMAT_DATETIME)
                ->setFilter()
                ->setSuggestion();

		$grid->getColumn('updated_time')->getCellPrototype()->class('textsmall');

        $grid->addColumn('counter', 'Čítač')
                ->setSortable()
                ->setFilter()
                ->setSuggestion();

        $grid->addColumn('comments_mode', 'Komentáře')
                ->setReplacement($comments)
                ->setSortable()
                ->setFilter(Filter::TYPE_SELECT, $comments);

        $grid->setOperations(array('delete' => 'Smazat'), callback($this, 'articlesGridOperationsHandler'))
                ->setConfirm('delete', 'Are you sure you want to delete %i items?');

        $grid->addAction('editArticle', 'Upravit')
                ->setIcon('pencil');

        $grid->setFilterRenderType($filterRenderType);
        $grid->setExporting("articles" . " " . date("Y-m-d H:i:s", time()));
    }

    public function articlesGridOperationsHandler($op, $ids) {
        switch ($op) {
            default:
                $this->deleteArticle($ids);
                break;
        }
    }


// ------------------------------ GALLERY ----------------------------------

    public function actionGallery() {
	if (!$this->user->isAllowed("Admin:gallery", "view")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
    }

    public function renderGallery() {
	
    }

// ----------------------------- MICROPAYMENTS -----------------------------

    
    public function actionMicroPayments() {
	$addPermit = FALSE;
	if ($this->user->isAllowed("Admin:microPayments", "create"))
		$addPermit = TRUE;
	$this->template->addPermit = $addPermit;
    }
    
    /** in use */
    public function actionEditMicroPayment($id_micropayment) {
	if (!$this->user->isAllowed("Admin:microPayments", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	if (!is_numeric($id_micropayment)) {
	    //\Nette\Diagnostics\Logger::log("AdminPresenter::actionEditPayment, argument is not numeric type", Logger::CRITICAL);
	    $this->flashMessage('Argument musí být číslo', 'error');
	    $this->redirect('Admin:microPayments');
	    return;
	}
	$form = $this->getComponent('editMicroPaymentForm');
	try {
	    $py = $this->getService('microPaymentModel')->getMicroPayment((integer) $id_micropayment);
	} catch (\Nette\ArgumentOutOfRangeException $ex) {
	    $this->flashMessage($ex->getMessage(), 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('Admin:microPayments');
	}
	$form->setDefaults($py);
    }

    /** in use */
    public function renderEditMicroPayments() {
	
    }

    /** in use */
    public function createMicroPayment(florbalMohelnice\Entities\MicroPayment $py) {
	if (!$this->user->isAllowed("Admin:microPayments", "create")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$this->getService('microPaymentModel')->createMicroPayment($py);
	$this->redirect('microPayments');
    }

    /** in use */
    public function updateMicroPayment(florbalMohelnice\Entities\MicroPayment $py) {
	if (!$this->user->isAllowed("Admin:microPayments", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$this->getService('microPaymentModel')->updateMicroPayment($py);
	$this->redirect('microPayments');
    }

    /** in use */
    public function deleteMicroPayment($id) {
	if (!$this->user->isAllowed("Admin:microPayments", "delete")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	try {
	    $this->getService('microPaymentModel')->removeMicroPayment((integer) $id);
	} catch (\Nette\OutOfRangeException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Mikroplatba neexistuje', 'error');
	}
	$this->redirect('microPayments');
    }

    /** in use */
    protected function createComponentMicroPaymentsGrid($name) {
	$filterRenderType = Filter::RENDER_OUTER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getService('microPaymentModel')->getFluent());

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id_micropayment');

	$grid->addColumn('kid', 'KID')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('surname', 'Příjmení')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('name', 'Jméno')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('season', 'Sezóna')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('subject', 'Předmět')
		->setSortable()
		->setFilter()
		->setSuggestion();

		$grid->getColumn('subject')->getCellPrototype()->class('textsmall');

	$grid->addColumn('micropayment_type', 'Typ')
		->setSortable()
		->setFilter()
		->setSuggestion();

		$grid->getColumn('micropayment_type')->getCellPrototype()->class('textsmall');

	$grid->addColumn('amount', 'Částka')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addAction('edit', 'Upravit', Action::TYPE_HREF, 'editMicroPayment');
	$grid->setOperations(array('delete' => 'Smazat'), callback($this, 'microPaymentsGridOperationsHandler'));

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();
    }

    /** in use */
    public function microPaymentsGridOperationsHandler($operation, $id) {
	switch ($operation) {
	    case 'delete':
		foreach ($id as $i) {
		    $this->deleteMicroPayment($i);
		}
		break;
	}
    }

    /** in use */
    public function createComponentAddMicroPaymentForm($name) {
	$msg = 'Komponenta je nedostupna, omlouvame se za zpusobene potize'; // WTF?
	try {
	    $eseasons = $this->getSeasonsModel()->getSelectSeasons();
	    $users = $this->getService('userModel')->getSelectUsers();
	    $c = new MicroPaymentForm($this, $name, $users, $eseasons, 'create');
	} catch (Nette\InvalidStateException $ex) {
	    $this->flashMessage($msg, 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	} catch (InvalidArgumentException $ex) {
	    $this->flashMessage($msg, 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	return $c;
    }

    /** in use */
    protected function createComponentEditMicroPaymentForm($name) {
	$msg = 'Komponenta je nedostupna, omlouvame se za zpusobene potize'; // WTF?
	try {
	    $eseasons = $this->getSeasonsModel()->getSelectSeasons();

	    $users = $this->getService('userModel')->getSelectUsers();
	    $c = new MicroPaymentForm($this, $name, $users, $eseasons, 'update');
	} catch (Nette\InvalidStateException $ex) {
	    $this->flashMessage($msg, 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	} catch (InvalidArgumentException $ex) {
	    $this->flashMessage($msg, 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	return $c;
    }

// ----------------------------- ORDERS ------------------------------------

    public function actionOrders() {
	$editPermit = FALSE;
	$addPermit = FALSE;
	if ($this->user->isAllowed("User:orders", "update"))
	    $editPermit = TRUE;
	if ($this->user->isAllowed("Admin:orders", "create"))
	    $addPermit = TRUE;
	$this->template->editPermit = $editPermit;
	$this->template->addPermit = $addPermit;
    }

    public function actionShowOrder($id) {
	if (!$this->user->isAllowed("Admin:orders", "view")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$editPermit = FALSE;
	try {
	    $order = $this->getOrdersModel()->getOrder($id);
	} catch (DibiException $ex) {
	    $this->flashMessage('Objednávka s uvedeným ID pravděpodobně neexistuje', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('Admin:orders');
	    return;
	} catch (Nette\InvalidArgumentException $ex) {
	    Debugger::log($ex, Debugger::DETECT);
	    $this->flashMessage("Chyba v argumentu", "error");
	    $this->redirect("Admin:orders");
	}
	$c = $this->getComponent('editOrderForm');
	//$order->offsetSet('identifier', $id);
	//$order->offsetSet('author_kid');
	$c->setDefaults($order->toArray());

	if ($this->user->isAllowed("Admin:orders", "update"))
	    $editPermit = TRUE;

	$this->template->editPermit = $editPermit;
	$this->template->order = $order;
    }

    public function handleSetOrderState($state, $id) {
	if (!is_numeric($id)) {
	    $this->flashMessage('Chyba v argumentu', "error");
	    return;
	}
	switch ($state) {
	    case 'req':
	    case 'inp':
	    case 'sol':
	    case 'cls':
		try {
		    $obj = new Order(array('id' => $id, 'state' => $state));
		    $this->getOrdersModel()->updateOrder($obj);
		} catch (\Nette\IOException $ex) {
		    $this->flashMessage('Operace nemohla být provedena, zkuste to prosím později', 'error');
		    Debugger::log($ex, "ERROR");
		} catch (Nette\InvalidArgumentException $ex) {
		    $this->flashMessage('Chyba v argumentu', "error");
		    Debugger::log($ex, "DETECT");
		    return;
		}
		break;
	}
        $this->redirect('Admin:orders');
    }

    public function createComponentAdminOrdersGrid($name) {

	$orderTypes = array('' => '') + $this->getOrdersModel()->getSelectTypes();
	$users = array('' => '') + $this->getUserModel()->getSelectUsers();
	$handlers = array('' => '') + $this->getOrdersModel()->getHandlersSelect();
	$states = array('' => '') + Order::getStates();

	$filterRenderType = Filter::RENDER_OUTER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getOrdersModel()->getAdminFluent());

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id');

	$grid->addColumn('id', 'ID');
	$grid->addColumn('order_type_id', 'Typ')
		->setSortable()
		->setReplacement($orderTypes)
		->setFilter(Filter::TYPE_SELECT, $orderTypes);

	$grid->addColumn('kid', 'Zájemce')
		->setSortable()
		->setReplacement($users)
		->setFilter(Filter::TYPE_SELECT, $users);

		$grid->getColumn('kid')->getCellPrototype()->class('textsmall');

	$grid->addColumn('ordered_time', 'Zadáno', Column::TYPE_DATE)
		->setSortable()
		->setFilter(Filter::TYPE_DATE);

	$grid->addColumn('handler_kid', 'Vyřizuje')
		->setSortable()
		->setReplacement($users)
		->setFilter(Filter::TYPE_SELECT, $handlers);

		$grid->getColumn('handler_kid')->getCellPrototype()->class('textsmall');

	$grid->addColumn('state', 'Stav')
		->setSortable()
		->setReplacement($states)
		->setFilter(Filter::TYPE_SELECT, $states);
//				->setDefaultValue('req');

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();

	$grid->addAction('show', 'Detail', Action::TYPE_HREF, 'showOrder');
	$grid->setOperations(array('delete' => 'Smazat'), callback($this, 'orderGridOperationsHandler'));
    }

    public function orderGridOperationsHandler($operation, $ids) {
	switch ($operation) {
	    case 'delete':
		$this->handleDeleteOrder($ids);
		break;
	}
    }

    public function handleDeleteOrder($ids) {
	if (!$this->user->isAllowed("Admin:orders", "delete")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	foreach ($ids as $id) {
	    $obj = new Order(array('id' => $id));
	    $this->getOrdersModel()->deleteOrder($obj);
	}
    }

    public function addOrder(Order $order) {
	if (!$this->user->isAllowed("Admin:orders", "create")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("Admin:orders");
	}
	$i = $order;
	try {
	    $this->getOrdersModel()->createOrder($i);
	} catch (Nette\IOException $ex) {
	    $this->flashMessage('Objednávku se nepodařilo zadat. Chybu napravíme co nevidět. Zkuste to prosím zítra.', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	//$this->flashMessage('Objednávka byla zadána', 'success');
	$this->redirect('Admin:orders');
    }

    public function actionAddOrder() {
	if (!$this->user->isAllowed("Admin:orders", 'create')) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("Admin:orders");
	}
    }

    public function createComponentEditOrderForm($name) {
	try {
	    $orderTypes = $this->getOrdersModel()->getSelectTypes();
	} catch (Nette\IOException $ex) {
	    $this->flashMessage('Nastala neočekávaná chyba, formulář nemohl být vytvořen. Zkuste to prosím později', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    return new OrderForm($this, $name);
	}
	$c = new OrderForm($this, $name, $orderTypes, $this->getSelectUsers(), OrderForm::CONTEXT_ADMIN, 'update');
	return $c;
    }

    public function createComponentOrderForm($name) {
	try {
	    $orderTypes = $this->getOrdersModel()->getSelectTypes();
	} catch (Nette\IOException $ex) {
	    $this->flashMessage('Nastala neočekávaná chyba, formulář nemohl být vytvořen. Zkuste to prosím později', 'error');
	    Debugger::log($ex, Debugger::ERROR);
	    return new OrderForm($this, $name);
	}

	$c = new \florbalMohelnice\Forms\OrderForm($this, $name, $orderTypes, $this->getUserModel()->getSelectUsers(), \florbalMohelnice\Forms\OrderForm::CONTEXT_ADMIN);
	return $c;
    }

    public function actionEditOrder($id) {
	if (!$this->user->isAllowed("Admin:orders", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:orders");
	}
	try {
	    $order = $this->getOrdersModel()->getOrder($id);
	} catch (\Nette\IOException $ex) {
	    Debugger::log($ex, "ERROR");
	    $this->flashMessage('Objednávka s uvedeným ID pravděpodobně neexistuje', 'error');
	    $this->redirect("Admin:orders");
	} catch (\Nette\InvalidArgumentException $ex) {
	    Debugger::log($ex, "DETECT");
	    $this->flashMessage("Špatný formát parametru", "error");
	    $this->redirect("Admin:orders");
	}

	$form = $this->getComponent("editOrderForm");
	$form->setDefaults($order);
    }

    public function editOrder(Order $obj) {
	if (!$this->user->isAllowed("Admin:orders", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$obj;
	//$i->offsetSet('kid',$this->getUserId());
	try {
	    $this->getOrdersModel()->updateOrder($obj);
	} catch (\Nette\IOException $ex) {
	    $this->flashMessage($ex->getMessage(), 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('Admin:orders');
	    return;
	}
	//$this->flashMessage('Změny byly uloženy', 'success');
	$this->redirect('Admin:orders');
    }

// ------------------------------- WALLPOSTS -------------------------------
    public function actionWalls() {
	$addPermit = FALSE;
	if ($this->user->isAllowed("Admin:walls", "update"))
	    $addPermit = TRUE;
	$this->template->addPermit = $addPermit;
    }

    public function createComponentWallPostForm($name) {
	$groupsSelect = $this->getGroupsModel()->getSelectGroups();
	$c = new WallPostForm($this, $name, $groupsSelect, WallPostForm::CREATE_MODE);
	return $c;
    }

    public function createComponentEditWallPostForm($name) {
	$groupsSelect = $this->getGroupsModel()->getSelectGroups();
	$c = new WallPostForm($this, $name, $groupsSelect, WallPostForm::UPDATE_MODE);
	return $c;
    }

    public function addWallPost(WallPost $wp) {
	if (!$this->user->isAllowed("Admin:walls", "create")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$wp->offsetSet('posted_kid', $this->getUserId());
	try {
	    $this->getWallsModel()->createWallPost($wp);
	} catch (IOException $ex) {
	    $this->flashMessage('Příspěvek se nezdařilo přidat', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('Admin:walls');
	}
	//$this->flashMessage('Příspěvek byl úspěšně přidán', 'success');
	$this->redirect('Admin:walls');
    }

    /**
     *
     */
    public function editWallPost(WallPost $wp) {
	if (!$this->user->isAllowed("Admin:walls", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	try {
	    $this->getWallsModel()->updateWallPost($wp);
	} catch (IOException $ex) {
	    $this->flashMessage('Změny nemohly být uloženy', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	//$this->flashMessage('Změny byly uloženy', 'success');
	$this->redirect('Admin:walls');
    }

    /**
     *
     */
    public function createComponentWallPostsGrid($name) {
	$onlyOwn = FALSE; // ACL
	$users = array('' => '') + $this->getUserModel()->getSelectUsers();
	$groups = array('' => '') + $this->getGroupsModel()->getSelectGroups();
	$status = array('' => '',
	    WallPost::STATUS_PUBLISHED => 'Publikováno',
	    WallPost::STATUS_CONCEPT => 'Koncept');
	$comment = array('' => '',
	    WallPost::COMMENTS_LOGGED => 'Přihlášení',
	    WallPost::COMMENTS_OFF => 'Zakázány',
	    WallPost::COMMENTS_PUBLIC => 'Veřejné');

	$filterRenderType = Filter::RENDER_OUTER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getWallsModel()->getAdminGridPosts($onlyOwn));

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id_wallpost');
	$grid->addColumn('id_wallpost', 'Id');


	$grid->addColumn('title', 'Titulek')
		->setTruncate(20);

	$grid->addColumn('author_kid', 'Autor')
		->setSortable()
		->setReplacement($users)
		->setFilter(Filter::TYPE_SELECT, $users);

	$grid->addColumn('id_group', 'Skupina')
		->setSortable()
		->setReplacement($groups)
		->setFilter(Filter::TYPE_SELECT, $groups);

	$grid->addColumn('short_show', 'Zobrazit')
		->setSortable()
		->setFilter(Filter::TYPE_DATE);

	/*$grid->addColumn('show_to', 'Zobrazit do', Column::TYPE_DATE)
		->setSortable()
		->setFilter(Filter::TYPE_DATE);*/

	$grid->addColumn('status', 'Stav')
		->setSortable()
		->setReplacement($status)
		->setFilter(Filter::TYPE_SELECT, $status);

	$grid->addColumn('updated_time', 'Poslední změna', Column::TYPE_DATE)
		->setSortable()
		->setFilter(Filter::TYPE_DATE);

	$grid->addColumn('comment_mode', 'Komentáře')
		->setSortable()
		->setReplacement($comment)
		->setFilter(Filter::TYPE_SELECT, $comment);

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();

	$grid->addAction('show', 'Upravit', Action::TYPE_HREF, 'editWallPost');
	$grid->setOperations(array('delete' => 'Smazat'), callback($this, 'wallPostGridOperationsHandler'));
	return $grid;
    }

    /**
     *
     */
    public function wallPostGridOperationsHandler($op, $ids) {
	switch ($op) {
	    case 'delete':
		$this->deletePosts($ids);
		break;
	}
    }

    /**
     *
     */
    protected function deletePosts(array $ids) {
	if (!$this->user->isAllowed("Admin:walls", "delete")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$wp = new WallPost(array());
	try {
	    foreach ($ids as $id) {
		$wp->offsetSet('id_wallpost', $id);
		$this->getWallsModel()->deletePost($wp);
	    }
	} catch (IOException $ex) {
	    $this->flashMessage("Příspěvek #$id se nezdařilo smazat", 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('Admin:walls');
	}
	//$this->flashMessage('Smazáno', 'success');
	$this->redirect('Admin:walls');
    }

    public function actionEditWallPost($id_wallpost) {
	if (!$this->user->isAllowed("Admin:walls", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	try {
	    $post = $this->getWallsModel()->getWallPost($id_wallpost);
	} catch (IOException $ex) {
	    $this->flashMessage('Příspěvek se nepodařilo načíst', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	if (!$post) { // tohle dodělat snad všude kde to jde
	    $this->flashMessage('Příspěvek se nepodařilo načíst', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('Admin:walls');
	}
	$c = $this->getComponent('editWallPostForm');
	// TODO groups of post....
	$c->setDefaults($post);
    }

// -------------------------------- FORUMS ---------------------------------

    /**
     *
     */
    public function actionForums() {
	if (!$this->user->isAllowed("Admin:forums", "view")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$addPermit = FALSE;
	if ($this->user->isAllowed("Admin:forums", "create"))
		$addPermit = TRUE;
	try {
	    $res = $this->getForumModel()->getAll();
	} catch (DibiException $ex) {
	    $this->flashMessage('Fora se nepodařilo načíst', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	$this->template->addPermit = $addPermit;
	$this->template->forums = $res;
    }

    /**
     *
     */
    public function actionAddForum() {
	if (!$this->user->isAllowed("Admin:forums", "create")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
    }
    
    /**
     *
     */
    public function actionEditForum($id_forum) {
	if (!$this->user->isAllowed("Admin:forums", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	
	try {
	    $forum = $this->getForumModel()->getForum($id_forum);
	} catch(\Nette\IOException $ex) {
	    $this->flashMessage('Fórum se nepodařio načíst', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	$forum->categories = array_keys($forum->categories);
	$form = $this->getComponent('editForumForm');
	$form->setDefaults($forum->toArray());
    }
    
    public function actionShowForum($id_forum) {
	if (!$this->user->isAllowed("Admin:forums", "view")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	
	try {
	    $forum = $this->getForumModel()->getForum($id_forum);
	} catch(\Nette\IOException $ex) {
	    $this->flashMessage('Fórum se nepodařio načíst', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	$form = $this->getComponent('editForumForm');
	$forum->categories = array_keys($forum->categories);
	$form->setDefaults($forum->toArray());
	foreach ($form->getComponents() as $comp) {
	    $comp->setDisabled(TRUE);
	}
	$this->setView('editForum');
    }
    

    /**
     * Sends CreateForum message to ForumModel
     * @param \florbalMohelnice\Entities\Forum $f
     */
    public function addForum(Forum $f) {
	if (!$this->user->isAllowed("Admin:forums", "create")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$f->offsetSet('update_kid', $this->getUserId());
	try {
	    $this->getForumModel()->createForum($f);
	} catch (IOException $ex) {
	    $this->flashMessage('Fórum se nepodařio vytvořit', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	//$this->flashMessage('Fórum bylo vytvořeno', 'success');
	$this->redirect('Admin:forums');
    }
    
    /**
     * Sends UpdateForum message to ForumModel
     * @param \florbalMohelnice\Entities\Forum $f
     */
    public function updateForum(Forum $f) {
	if (!$this->user->isAllowed("Admin:forums", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	$f->offsetSet('update_kid', $this->getUserId());
	try {
	    $this->getForumModel()->updateForum($f);
	} catch (IOException $ex) {
	    $this->flashMessage('Fórum se nepodařio vytvořit', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	//$this->flashMessage('Fórum bylo upraveno', 'success');
	$this->redirect('Admin:forums');
    }

    /**
     * AddForumForm factory
     * @param type $name
     * @return \florbalMohelnice\Forms\ForumForm
     */
    public function createComponentAddForumForm($name) {
	$groups = $this->getGroupsModel()->getSelectGroups();
	$parents = $this->getForumModel()->getSelectForums();
	$rights = $this->getUserModel()->getAllRoles();
	$c = new ForumForm($this, $name, $groups, $parents, $rights);
	return $c;
    }
    
    /**
     * EditForumForm factory
     * @param type $name
     * @return \florbalMohelnice\Forms\ForumForm
     */
    public function createComponentEditForumForm($name) {
	$groups = $this->getGroupsModel()->getSelectGroups();
	$parents = $this->getForumModel()->getSelectForums();
	$rights = $this->getUserModel()->getAllRoles();
	$c = new ForumForm($this, $name, $groups, $parents, $rights, ForumForm::UPDATE_MODE);
	return $c;
    }

    /**
     * ForumsGrid factory
     * @param type $name
     */
    public function createComponentForumsGrid($name) {

	$filterRenderType = Filter::RENDER_OUTER;
	//$users = array('' => '') + $this->getUserModel()->getSelectUsers();
	$parents = array('' => '') + $this->getForumModel()->getSelectForums();
	$rights = array('' => '') + $this->getUserModel()->getAllRoles();

	$grid = new Grid($this, $name);
	$grid->setModel($this->getForumModel()->getAdminFluent());

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id_forum');

	$grid->addColumn('id_forum', 'Id');

	$grid->addColumn('title', 'Titulek')
		->setSortable()
		->setTruncate(20)
		->setFilter();

	$grid->addColumn('description', 'Popis')
		->setTruncate(30);

		$grid->getColumn('description')->getCellPrototype()->class('textsmall');

	$grid->addColumn('parent_forum', 'Rodič')
		->setSortable()
		->setReplacement($parents)
		->setFilter(Filter::TYPE_SELECT, $parents);

	$grid->addColumn('view_permission', 'Min. práva')
		->setSortable()
		->setReplacement($rights)
		->setFilter(Filter::TYPE_SELECT, $rights);

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();
	if ($this->user->isAllowed('Admin:forums', "update"))
	    $grid->addAction('edit', '[Uprav] ', Action::TYPE_HREF, 'editForum');
	elseif ($this->user->isAllowed('Admin:forums', "view"))
	    $grid->addAction('show', '[Zobraz] ', Action::TYPE_HREF, 'showForum');

//		$grid->addAction('show', 'Zobrazit', Action::TYPE_HREF, 'showOrder');
	$grid->setOperations(array('delete' => 'Smazat'), callback($this, 'forumsGridOperationsHandler'));
    }

    /**
     * Forum's grid operations handler
     * @param type $op
     * @param type $ids
     */
    public function forumsGridOperationsHandler($op, $ids) {
	switch ($op) {
	    case 'delete':
		$this->deleteForums($ids);
		break;
	}
    }

    /**
     *
     */
    public function deleteForums(array $ids) {
	if (!$this->user->isAllowed("Admin:forums", "delete")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
	foreach ($ids as $i) {
	    try {
		$this->getForumModel()->removeForum($i);
	    } catch (IOException $ex) {
		$this->flashMessage('Fórum se nepodařilo smazat', 'error');
		Debugger::log($ex->getMessage(), Debugger::ERROR);
	    }
	}
	$x = sizeof($ids);
	//$this->flashMessage("Smazání $x položek proběhlo úspěšně", 'success');
	$this->redirect('this');
    }
    
    // ------------------------ STATIC PAGES -----------------------------------
    
    public function actionStaticPages() {
	if (!$this->user->isAllowed("Admin:staticPages", "view")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:default");
	}
    }
    
    public function renderStaticPages() {
	$addPermit = FALSE;
	if ($this->user->isAllowed("Admin:forums", "create"))
		$addPermit = TRUE;
	$this->template->addPermit = $addPermit;
    }
    
    public function createStaticPage(florbalMohelnice\Entities\StaticPage $sp) {
	if (!$this->user->isAllowed("Admin:staticPages", "create")) {
	    $this->flashMessage("Nemáte dostatečná odprávnění na tuto akci", "error");
	    $this->redirect("Admin:staticPages");
	}
	$model = $this->getStaticPageModel();
	try {
	    $id = $model->createPage($sp);
	} catch (\Nette\IOException $ex) {
		$this->flashMessage('Statickou stránku se nepodařilo vytvořit', 'error');
		Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	//$this->flashMessage("Statická stránka s id $id byla vytvořena", 'success');
	$this->redirect('Admin:staticPages');
    }
    
    public function actionEditStaticPage($id_page) {
	if (!$this->user->isAllowed("Admin:staticPages", "update")) {
	    $this->flashMessage("Nemáte dostatečné oprávnění na provedení této akce", "error");
	    $this->redirect("Admin:staticPages");
	}
	try {
	    $element = $this->getStaticPageModel()->getPageById($id_page);
	} catch (IOException $ex) {
	    $this->flashMessage('Stránku se nepodařilo načíst', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	if (!$element) { 
	    $this->flashMessage('Stránku se nepodařilo načíst', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('Admin:staticPages');
	}
	$c = $this->getComponent('editPageForm');
	$c->setDefaults($element);
    }

    public function updateStaticPage(florbalMohelnice\Entities\StaticPage $sp) {
	if (!$this->user->isAllowed("Admin:staticPages", "update")) {
	    $this->flashMessage("Nemáte dostatečná odprávnění na tuto akci", "error");
	    $this->redirect("Admin:staticPages");
	}
	$model = $this->getStaticPageModel();
	$id = $sp->offsetGet("id_page");
	
	try {
	    $model->updatePage($sp);
	} catch(\Nette\IOException $ex) {
	    $this->flashMessage('Statickou stránku se nepodařilo upravit', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	//$this->flashMessage("Statická stránka s id $id byla vytvořena", 'success');
	$this->redirect('Admin:staticPages');
    }
	
    public function deleteStaticPages(array $ids) {
	$counter = 0;
	$model = $this->getStaticPageModel();
	foreach ($ids as $id) {
	    try {
		$model->deletePage((integer) $id);
	    } catch(\Nette\IOException $ex) {
		$this->flashMessage("Stránku s id $id se nepodařilo smazat", "error");
		Debugger::log($ex->getMessage(), Debugger::ERROR);
	    } catch(\Nette\InvalidArgumentException $ex) {
		$this->flashMessage("Stránku s id $id se nepodařilo smazat - argument nebyl ve správném tvaru", "error");
		Debugger::log($ex->getMessage(), Debugger::ERROR);
	    }
	    //$this->flashMessage("Stránka $id byla úspěšně smazána", "success");
	    
	}
	$this->redirect('this');
    }
    
    public function createComponentAddPageForm($name) {
	$users = $this->getUserModel()->getSelectUsers();
	$id_page = $this->getParam("id_page");
	$pages = $this->getStaticPageModel()->getSelectPages($id_page);
	$comp = new florbalMohelnice\Forms\StaticPageForm($this, $name, $users, $pages);
	return $comp;
    }
    
    public function createComponentEditPageForm($name) {
	$users = $this->getUserModel()->getSelectUsers();
	$id_page = $this->getParam("id_page");
	$pages = $this->getStaticPageModel()->getSelectPages($id_page);
	$comp = new florbalMohelnice\Forms\StaticPageForm($this, $name, $users, $pages, florbalMohelnice\Forms\StaticPageForm::UPDATE_MODE);
	return $comp;
    }
    
    public function createComponentStaticPagesGrid($name) {
	
	$filterRenderType = Filter::RENDER_OUTER;
	$users = array('' => '') + $this->getUserModel()->getSelectUsers();
	$parents = array('' => '') + $this->getStaticPageModel()->getSelectPages();
	$comments = array('' => '') + florbalMohelnice\Entities\StaticPage::getSelectCommModes();
	$status = array('' => '') + florbalMohelnice\Entities\StaticPage::getStatusModes();

	$grid = new Grid($this, $name);
	$grid->setModel($this->getStaticPageModel()->getFluent());

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id_page');

	$grid->addColumn('id_page', 'ID');

	$grid->addColumn('title', 'Titulek')
		->setSortable()
		->setTruncate(20)
		->setFilter();

	$grid->addColumn('parent_page', 'Rodič')
		->setSortable()
		->setReplacement($parents)
		->setFilter(Filter::TYPE_SELECT, $parents);

	$grid->addColumn('updated_kid', 'Upravil')
		->setSortable()
		->setReplacement($users)
		->setFilter(Filter::TYPE_SELECT, $users);
	
	$grid->addColumn('status', 'Stav')
		->setSortable()
		->setReplacement($status)
		->setFilter(Filter::TYPE_SELECT, $status);
	
	$grid->addColumn('comment_mode', 'Komentáře')
		->setSortable()
		->setReplacement($comments)
		->setFilter(Filter::TYPE_SELECT, $comments);

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();

	$grid->addAction('edit', 'Upravit', Action::TYPE_HREF, 'editStaticPage');
	//$grid->addAction('show', 'Zobrazit', Action::TYPE_HREF, 'showPage');
	//$grid->addAction('delete', 'Zobrazit', Action::TYPE_HREF, 'deletePage');
	$grid->setOperations(array('delete' => 'Smazat'), callback($this, 'staticPagesGridOperationsHandler'));
    }
    
    public function staticPagesGridOperationsHandler($operation, array $ids) {
	switch ($operation) {
	    case 'delete':
		$this->deleteStaticPages($ids);
		break;
	    default: 
		$this->flashMessage("Operation not permitted.", "error");
		$this->redirect("Admin:staticPages");
	}
    }
    
    
}

