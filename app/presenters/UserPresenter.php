<?php

use Nette\Application\UI\Form,
    \florbalMohelnice\Entities\User,
    florbalMohelnice\Forms\PaymentForm,
    Nette\Diagnostics\Logger,
    Grido\Grid,
    Grido\Components\Actions\Action,
    Grido\Components\Filters\Filter,
    Grido\Components\Columns\Column,
    Grido\Components\Columns\Date,
    florbalMohelnice\Entities\WebProfile,
    florbalMohelnice\Entities\PrivateMessage,
    florbalMohelnice\Forms\PasswordForm,
    florbalMohelnice\Entities\Order,
    florbalMohelnice\Forms\OrderForm,
    Nette\Diagnostics\Debugger;

/**
 * Desc. UserPresenter
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package fbcmoh
 */
final class UserPresenter extends SecuredPresenter {

    const LAYOUT_STYLE = "user";
    const INBOX = 'inbox';
    const OUTBOX = 'outbox';
    const DELETED = 'deleted';

    public function startup() {
	parent::startup();
	$this->setLayoutStyle(self::LAYOUT_STYLE);
    }
    
    public function beforeRender() {
	parent::beforeRender();
	$this->template->layoutStyle = $this->getLayoutStyle();
    }

    public function actionDefault() {
	
    }

    public function renderDefault() {
	
    }

// ------------------------------- MESSAGES --------------------------------

    public function actionMessageBox() {
	if (!$this->user->isAllowed("User:messageBox", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	$this->redirect('User:inbox');
    }

    public function actionInbox() {
	if (!$this->user->isAllowed("User:messageBox", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function actionOutbox() {
	if (!$this->user->isAllowed("User:messageBox", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function actionDeleted() {
	if (!$this->user->isAllowed("User:messageBox", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function actionShowPrivateMessage($id) {
	if (!$this->user->isAllowed("User:messageBox", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	$model = $this->getPrivateMessagesModel();
	try {
	    $message = $model->getMessage($id);
	    $model->markAs($message);
	} catch (IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Zpráva nemohla být načtena', 'error');
	    $this->redirect('User:messageBox');
	}
	$this->template->message = $message;
    }

    public function _starCustomRender($item) {
	$id = $item->offsetGet('id');
	$messageId = $item->offsetGet('id_message');
	$starred = $item->offsetGet('starred');
	$link = $this->link('starMessages!', array('ids' => array($id)));
	$messagesSection = $this->getSessionManager()->getSection('Messages');
	$messagesSection[$id] = array(
	    PrivateMessage::IDENTIFIER => $item->offsetGet('id_message'),
	    'mailbox_kid' => $item->offsetGet('mailbox_kid'),
	    'starred' => $item->offsetGet('starred'),
	    'id_entry' => $item->offsetGet('id'),
	    'status' => $item->offsetGet('status'),
	    );
	return "<a href=" . $link . ">" . ($starred == 1 ? "&#9733;" : "&#9734;");
    }
    
    public function _subjectCustomRender($item) {
	$link = $this->link('showPrivateMessage', array("id" => $item->id));
	return "<a href=" . $link . ">" . $item->subject . "</a>";
    }

    public function handleStarMessages(array $ids) {
	$kid = $this->getUserId();
	$messagesSection = $this->getSessionManager()->getSection('Messages');
	$model = $this->getPrivateMessagesModel();
	foreach ($ids as $i) {
	    $message = $messagesSection[$i];
	    if ($message != NULL) {
		try {
		    $model->starMessage(new PrivateMessage($message));
		} catch (IOException $ex) {
		    Debugger::log($ex->getMessage(), Debugger::ERROR);
		    $this->flashMessage('Akce nemohla být provedena', 'error');
		    return;
		}
	    }
	    else
		$this->flashMessage('Nemáte dostatečné oprávnění na tuto akci', 'error');
	}
	$this->redirect('this');
    }
    
    /**
     * grid handle
     * @param array $ids
     * @return type
     */
    public function handleMarkAsUnread(array $ids) {
	$kid = $this->getUserId();
	$messagesSection = $this->getSessionManager()->getSection('Messages');
	$model = $this->getPrivateMessagesModel();

	foreach ($ids as $i) {
	    $message = $messagesSection[$i];
	    if ($message != NULL) {
		try {
		    $model->markAsUnread(new PrivateMessage($message));
		} catch (IOException $ex) {
		    Debugger::log($ex->getMessage(), Debugger::ERROR);
		    $this->flashMessage('Akce nemohla být provedena', 'error');
		    return;
		}
	    }
	    else
		$this->flashMessage('Nemáte dostatečné oprávnění na tuto akci', 'error');
	}
    }
    
    public function handleDeleteMessage($msgId) {
	$this->deleteMessage($msgId);
    }

    public function deleteMessage($idEntry) {
	if (!$this->user->isAllowed("User:messageBox", "delete")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	try {
	    $this->getPrivateMessagesModel()->deleteMessage($idEntry);
	} catch (IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Operace nemohla být provedena');
	    $this->redirect('this');
	    break;
	}
	$this->flashMessage('Zpráva byla přesunuta do smazaných', 'success');
	$this->redirect('User:messageBox');
    }
    
    public function actionReplyMessage($msgId) {
	if (!$this->user->isAllowed("User:messageBox", "add")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	$this->setView("createMessage");
	try {
	    $message = $this->getPrivateMessagesModel()->getMessage($msgId);
	} catch (IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Operace nemohla být provedena');
	    $this->redirect('this');
	    break;
	}
	$comp = $this->getComponent('messageForm');
	$message->offsetSet('content', '"'.$message->offsetGet('content').'"');
	$message->offsetSet('recipients', $message->offsetGet('sender_kid'));
	$message->offsetSet('subject', "Re: ".$message->offsetGet('subject'));
	$comp->setDefaults($message);
    }

    private function getXBoxGrid($name, $x) {
	$states = array('' => '',
	    PrivateMessage::STATUS_READ => 'Přečteno',
	    PrivateMessage::STATUS_UNREAD => 'Nepřečteno');
	$users = array('' => '') + $this->getUserModel()->getSelectUsers();
	$starred = array('' => '', 0 => '-', 1 => '*');

	$uid = $this->getUserId();

	$filterRenderType = Filter::RENDER_INNER;

	$grid = $grid = new Grid($this, $name);

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id');

	$grid->addColumn('starred', '*')
		->setSortable()
		->setReplacement($starred)
		->setCustomRender(callback($this, '_starCustomRender'))
		->setFilter(Filter::TYPE_SELECT, $starred);

	switch ($x) {
	    case self::INBOX:
		$grid->setModel($this->getPrivateMessagesModel()
				->getInboxFluent($uid));

		$sentLabel = 'Odesláno';
		$addressCol = 'sender_kid';
		$addressLabel = 'Od';

		$grid->addColumn('status', 'Stav')
			->setSortable()
			->setReplacement($states)
			->setFilter(Filter::TYPE_SELECT, $states);
		break;
	    case self::OUTBOX:
		$grid->setModel($this->getPrivateMessagesModel()
				->getOutboxFluent($uid));

		$sentLabel = 'Doručeno';
		$addressCol = 'recipient_kid';
		$addressLabel = 'Komu';
		break;
	    case self::DELETED:
		$grid->setModel($this->getPrivateMessagesModel()
				->getDeletedFluent($uid));

		$addressCol = 'recipient_kid';
		$addressLabel = 'Komu';
		$sentLabel = 'Datum';
		$grid->addColumn('sender_kid', 'Od')
			->setSortable()
			->setReplacement($users)
			->setFilter(Filter::TYPE_SELECT, $users);
		break;
	}

	$grid->addColumn($addressCol, $addressLabel)
		->setSortable()
		->setReplacement($users)
		->setFilter(Filter::TYPE_SELECT, $users);

	$grid->addColumn('subject', 'Předmět')
		->setSortable()
		->setTruncate(30)
		->setCustomRender(callback($this, '_subjectCustomRender'))
		->setFilter()
		->setSuggestion();
	
//	$grid->addColumn('content', 'Zpráva')
//		->setTruncate(20)
//		->setSortable()
//		->setFilter();

	$grid->addColumn('sent', $sentLabel, Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATETIME)
		->setFilter()
		->setSuggestion();

	$grid->setOperations(
		array('delete' => 'Smazat', 'star' => 'Od/Razítkovat', 'unread' => 'Označit za nepřečtené'), callback($this, 'inboxGridOperationsHandler'));
	$grid->addAction('showPrivateMessage', 'Zobrazit');

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();
    }

    public function inboxGridOperationsHandler($operation, $ids) {
	switch ($operation) {
	    case 'delete':
		foreach ($ids as $id)
		    $this->deleteMessage($id);
		break;
	    case 'star':
		    $this->handleStarMessages($ids);
		break;
	    case 'unread':
		    $this->handleMarkAsUnread($ids);
		break;
	}
    }

    public function createComponentInboxGrid($name) {
	return $this->getXBoxGrid($name, self::INBOX);
    }

    public function createComponentOutboxGrid($name) {
	return $this->getXBoxGrid($name, self::OUTBOX);
    }

    public function createComponentDeletedGrid($name) {
	return $this->getXBoxGrid($name, self::DELETED);
    }

    public function actionCreateMessage() {
	if (!$this->user->isAllowed("User:messageBox", "add")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function renderCreateMessage() {
	
    }

    public function sendMessageHandle(\florbalMohelnice\Entities\PrivateMessage $msg) {
	try {
	    $this->getPrivateMessagesModel()->createMessage($msg, $this->getUserId());
	} catch (DibiException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Zprávu se nepodařilo odeslat, zkuste to prosím později');
	    $this->redirect('User:inbox');
	    //  TODO logger
	}
	$this->flashMessage('Zpráva odeslána', 'success');
	$this->redirect('User:inbox');
    }

    public function createComponentMessageForm($name) {
	$selUsers = $this->getService('userModel')->getSelectUsers($this->getUserId());
	$f = new \florbalMohelnice\Forms\PrivateMessageForm($this, $name, $selUsers);
	return $f;
    }
    
    public function createComponentMessagesControl($name) {
	$c = new florbalMohelnice\Components\MessagesControl($this, $name);
	$c->setMessagesModel($this->getPrivateMessagesModel());
	$c->setUserId($this->getUser()->getIdentity()->getId());
	$c->setUsers($this->getService('userModel')->getSelectUsers($this->getUserId()));
	return $c;
    }

// -------------------------- PAYMENTS -------------------------------------

    public function actionPayments() {
	if (!$this->user->isAllowed("User:payments", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function renderPayments() {

	$grid = $this->getComponent('userPaymentsGrid');
    }

    protected function createComponentUserPaymentsGrid($name) {
	$filterRenderType = Filter::RENDER_INNER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getService('paymentModel')->getUsersFluent($this->getUserId()));

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id_payment');

	$grid->addColumn('subject', 'Předmět')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('season', 'Sezóna')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('amount', 'Částka')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('due_date', 'Splatnost', Column::TYPE_DATE)
		->setSortable()
		->setDateFormat(Date::FORMAT_DATE)
		->setFilter();

	$grid->addColumn('status', 'Zaplaceno')
		->setReplacement(array('unp' => 'Ne', 'pac' => 'Hotově', 'paa' => 'Účet'))
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();
    }

// ---------------------------- PENALTIES ----------------------------------

    public function actionPenalties() {
	if (!$this->user->isAllowed("User:microPayments", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function renderPenalties() {
	
    }

    public function createComponentMicroPaymentsGrid($name) {
	$filterRenderType = Filter::RENDER_INNER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getService('microPaymentModel')->getFluent($this->getUserId()));

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id_micropayment');

	$grid->addColumn('ordered_time', 'Zadáno', Column::TYPE_DATE)
		->setSortable();

	$grid->addColumn('micropayment_type', "Typ")
		->setSortable();

	$grid->addColumn('subject', 'Předmět')
		->setSortable()
		->setFilter();

	$grid->addColumn('amount', 'Částka')
		->setSortable()
		->setFilter();

	$grid->addColumn('season', 'Sezóna')
		->setSortable()
		->setFilter();

	$grid->addColumn('comment', 'Komentář')
		->setSortable();

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();
    }

// -------------------------------- CREDITS --------------------------------
    public function actionCredit() {
	if (!$this->user->isAllowed("User:credit", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function renderCredit() {
	
    }

    public function createComponentUserCredits($name) {
	$c = new \florbalMohelnice\Components\CreditsControl($this, $name);
	$kid = $this->getUser()->getIdentity()->getId();
	$c->setId($kid);
	return $c;
    }

// -------------------------------- PERSONAL DATA --------------------------
    public function actionData() {
	if (!$this->user->isAllowed("User:data", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	$result = NULL;
	try {
	    $result = $this->getUserModel()->getFluentContacts($this->getUserId())
			    ->execute()->fetch();
	} catch (Nette\IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Chyba při získávání dat.');
	    return;
	}
	$this->template->data = $result;
    }

    public function renderData() {
	
    }

    public function actionChangeData() {
	if (!$this->user->isAllowed("User:data", "edit")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	try {
	    $data = $this->getUserModel()->getFluentContacts($this->getUserId())
			    ->execute()->fetch();
	} catch (Nette\IOException $ex) {
	    $this->flashMessage('Nepodařilo se načíst data');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    return;
	}
	$form = $this->getComponent('editUserDataForm');
	$form->setDefaults($data);
    }

    public function editUserData(User $usr) {
	if (!$this->user->isAllowed("User:data", "edit")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	try {
	    $this->getUserModel()->updateUserAndContact($usr);
	} catch (Nette\IOException $ex) {
	    switch($ex->getCode()) {
		case 1062 : 
		    $this->flashMessage("Duplicitní záznam (ukládané rodné číslo nebo čfbu id už má jiný uživatel).");
		    break;
		default :
		    $this->flashMessage("Data nebyla uložena", 'error');
	    }
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    return;
	}
	$this->flashMessage('Data byla uložena');
	$this->redirect('User:data');
    }

    public function renderChangeData() {
	
    }

    public function createComponentEditUserDataForm($name) {
	$form = new florbalMohelnice\Forms\UserDataForm($this, $name);
	return $form;
    }

// ------------------------------ USER PROFILE -----------------------------

    public function actionProfile() {
	if (!$this->user->isAllowed("User:profile", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	$data = NULL;
	try {
	    $data = $this->getUserModel()->getWebProfilesFluent($this->getUserId())
			    ->execute()->setRowClass('florbalMohelnice\Entities\WebProfile')->fetch();
	} catch (Nette\IOException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Data nebyla načtena. Zkuste to prosím později.', 'error');
	}
	$this->template->data = $data;
    }

    public function renderProfile() {
	
    }

    /*
      public function createWebProfile(WebProfile $wp) {
      try {
      $this->getUserModel()->createProfile($wp);
      } catch (Exception $ex) {
      $this->flashMessage('Chyba, záznam nebyl vytvořen, zkuste to prosím později.','success');
      // TODO logger
      }
      $this->flashMessage('Záznam byl vytvořen', 'success');
      $this->redirect('User:profile'); // TODO where to redirect?
      } */

    public function updateWebProfile(WebProfile $wp) {
	if (!$this->user->isAllowed("User:profile", "edit")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	try {
	    $this->getUserModel()->updateWebProfile($wp);
	} catch (Exception $ex) {
	    $this->flashMessage('Chyba, záznam nebyl uložen, zkuste to prosím později.', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    return;
	}
	$this->flashMessage('Záznam byl uložen.');
	$this->getUser()->getIdentity()->profile_required = WebProfile::USER_PROFILE_EDITED;
	$this->redirect('User:profile');
    }

    public function actionEditProfile() {
	if (!$this->user->isAllowed("User:profile", "edit")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	$comp = $this->getComponent('webProfileForm');
	try {
	    $profile = $this->getUserModel()->getWebProfilesFluent($this->getUserId())
			    ->execute()->setRowClass('florbalMohelnice\Entities\WebProfile')->fetch();
	} catch (\Nette\ArgumentOutOfRangeException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage($ex->getMessage(), 'error');
	    $this->redirect('User:profile');
	}
	$comp->setDefaults($profile->toArray());
    }

    public function createComponentWebProfileForm($name) {
	$f = new florbalMohelnice\Forms\UserWebProfileForm($this, $name);
	return $f;
    }

// ------------------------------- EVENTS ----------------------------------

    public function actionEvents() {
	if (!$this->user->isAllowed("User:events", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function renderEvents() {
	
    }

    public function createComponentUserEvents($name) {
	$c = new florbalMohelnice\Components\UserEventsControl($this, $name);
	$c->setModel($this->getEventsModel());
	$c->setKid($this->getUserId());
	$c->setGroupsModel($this->getGroupsModel());
	return $c;
    }

    public function createComponentChangePwForm($name) {
	$pms = $this->context->getParameters();
	$salt = $pms['models']['salt'];
	$data = $this->getUser()->getIdentity()->getData();
	$pass = $data['password'];
	$c = new PassWordForm($this, $name, $pass, $salt);
	return $c;
    }

    public function changePassword($newPass) {
	$identity = $this->getUser()->getIdentity();
	$kid = $identity->getId();
	$data = $identity->getData();
	$email = $data['email'];
	$user = new User(array(
	    'password' => $newPass,
	    'password_status' => 'ok',
	    'kid' => $kid,
	    'email' => $email));
	try {
	    $this->getUserModel()->updatePassword(array($user));
	} catch (Nette\IOException $ex) {
	    $this->flashMessage('Nastala neočekávaná chyba, zkuste to prosím později.', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    return;
	} catch (\Nette\InvalidStateException $ex) {
	    $this->flashMessage('Mail se nepodařilo odeslat', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	$this->user->getIdentity()->password_status = 'ok';
	$this->redirect('User:data');
    }

// ---------------------------- ORDERS -------------------------------------

    public function addOrder(Order $order) {
	if (!$this->user->isAllowed("User:orders", "add")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	$i = $order;
	$i->offsetSet('kid', $this->getUserId());
	try {
	    $this->getOrdersModel()->createOrder($i);
	} catch (Nette\IOException $ex) {
	    $this->flashMessage('Objednávku se nepodařilo zadat. Chybu napravíme co nevidět. Zkuste to prosím zítra.', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}
	$this->flashMessage('Objednávka byla zadána', 'success');
	$this->redirect('User:orders');
    }
    
        public function createComponentOrderForm($name) {
	try {
	    $orderTypes = $this->getOrdersModel()->getSelectTypes();
	} catch (Nette\IOException $ex) {
	    $this->flashMessage('Nastala neočekávaná chyba, formulář nemohl být vytvořen. Zkuste to prosím později', 'error');
	    Debugger::log($ex, Debugger::ERROR);
	    return new OrderForm($this, $name);
	}
	
	$c = new \florbalMohelnice\Forms\OrderForm($this, $name, $orderTypes, $this->getUserModel()->getSelectUsers());
	return $c;
    }

    public function actionShowOrder($id) {
	$order = $this->getOrdersModel()->getOrder($id);
	$adminEditAllowed = false;
	
	if ($this->user->isAllowed("Admin:orders", "edit"))
		$adminEditAllowed = true;
	
	$this->template->editAllowed = $adminEditAllowed;
	$this->template->order = $order;
    }

    public function renderOrders() {
	if (!$this->user->isAllowed("User:orders", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function createComponentMyOrdersGrid($name) {
	$filterRenderType = Filter::RENDER_INNER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getOrdersModel()->getFluent($this->getUserId()));

	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id');
	$grid->addColumn('label', 'Typ')
		->setSortable();

	$grid->addColumn('ordered_time', 'Zadáno', Column::TYPE_DATE)
		->setSortable();

	$grid->addColumn('handler', 'Vyřizuje')
		->setSortable();

	$grid->addColumn('state', 'Stav')
		->setSortable();

	$grid->addColumn('last_edit', 'Poslední změna', Column::TYPE_DATE)
		->setSortable();

	$grid->addColumn('comment', 'Komentář')
		->setTruncate(10)
		->setSortable();

	$grid->addAction('showOrder', 'Detail');

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();
    }

}