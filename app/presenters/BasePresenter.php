<?php

use \Nette\Application\UI\Form,
    \florbalMohelnice\Components\MenuControl,
    Nette\Diagnostics\Debugger;

/**
 * Base class for all application presenters.
 *
 * @author     Michal Fučík
 * @package    florbalMohelnice
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {

    const C_WALLPOST_TYPE = 'wpo';
    const C_STATIC_TYPE = 'spa';
    const C_EVENT_TYPE = 'eve';
    const C_ARTICLE_TYPE = 'art';
    const C_USER_TYPE = 'use';
    const C_FORUM_TYPE = 'for';
    const C_PARTY_TYPE = 'par';

    protected $masterContactMess = "Došlo k neočekávané chybě!. Kontaktujte prosím administrátora.";

    /** @var year identifier of actual season */
    private $actualSeason;

    /** @var user model */
    private $userModel;

    /** @var orders model */
    private $ordersModel;

    /** @var groups model */
    private $groupsModel;

    /** @var categories model */
    private $categoriesModel;

    /** @var privateMessages model */
    private $privateMessagesModel;

    /** @var walls model */
    private $wallsModel;

    /** @var articles model */
    private $articlesModel;

    /** @var events model */
    private $eventsModel;

    /** @var forum model */
    private $forumModel;

    /** @var forum model */
    private $paymentModel;

    /** @var page style */
    private $layoutStyle;

    /** @var universal viewed entity name */    
    private $entityName;
    
    /** @var universal viewed entity name */
    private $entity;
    
    /** @var file model */
    private $fileModel;
    
    /** @var staticPage model */
    private $staticPageModel;
    
    /** @var participation model */
    private $participationModel;
    
    /** @var season applications model */
    private $seasonApplicationsModel;
    
    /** @var seasons model */
    private $seasonsModel;
    
    /** @var userMainGroupAbbr */
    private $userMainGroupAbbr;
    
    public function getUserMainGroupAbbr() {
        if (!isset($this->userMainGroupAbbr)) {
        try {
            $groupAbbr = $this->getGroupsModel()->getUsersHomeGroupId(new florbalMohelnice\Entities\User(array('kid'=>$this->getUserId())), FALSE, \florbalMohelnice\Entities\Group::COLUMN_ABBR);
            
        } catch (\Nette\Exception $ex) {
            Debugger::log($ex->getMessage(), $ex->getCode(), $ex);
        } catch (\Nette\InvalidArgumentException $ex) {
            Debugger::log($ex->getMessage(), $ex->getCode(), $ex);
        }
            $this->userMainGroupAbbr = $groupAbbr;
        }
        return $this->userMainGroupAbbr;
    }
   
    public function getEntity() {
	return $this->entity;
    }

    public function setEntity($entity) {
	$this->entity = $entity;
    }
    
    public function getSeasonsModel() {
        if (!isset($this->seasonsModel))
	    $this->seasonsModel = $this->getService('seasonsModel');
	return $this->seasonsModel;
    }
    
    public function getSeasonApplicationsModel() {
        if (!isset($this->seasonApplicationsModel))
	    $this->seasonApplicationsModel = $this->getService('seasonApplicationsModel');
	return $this->seasonApplicationsModel;
    }
    
    public function getParticipationModel() {
        if (!isset($this->participationModel))
	    $this->participationModel = $this->getService('participationModel');
	return $this->participationModel;
    }
    
    public function getStaticPageModel() {
        if (!isset($this->staticPageModel))
	    $this->staticPageModel = $this->getService('staticPageModel');
	return $this->staticPageModel;
    }
    
    public function getFileModel() {
        if (!isset($this->fileModel))
	    $this->fileModel = $this->getService('fileModel');
	return $this->fileModel;
    }
    
    public function setEntityName($name) {
	$this->session->getSection('entity')->name = $name;
    }
    
    public function getEntityName() {
	return $this->session->getSection('entity')->name;
    }
    
    public function getLayoutStyle() {
	return $this->layoutStyle;
    }

    public function setLayoutStyle($col) {
	$this->layoutStyle = $col;
    }

    public function getPaymentModel() {
	if (!isset($this->paymentModel))
	    $this->paymentModel = $this->getService('paymentModel');
	return $this->paymentModel;
    }

    public function getForumModel() {
	if (!isset($this->forumModel))
	    $this->forumModel = $this->getService('forumModel');
	return $this->forumModel;
    }

    public function getEventsModel() {
	if (!isset($this->eventsModel))
	    $this->eventsModel = $this->getService('eventsModel');
	return $this->eventsModel;
    }

    public function getArticlesModel() {
	if (!isset($this->articlesModel))
	    $this->articlesModel = $this->getService('articlesModel');
	return $this->articlesModel;
    }

    public function getWallsModel() {
	if (!isset($this->wallsModel))
	    $this->wallsModel = $this->getService('wallsModel');
	return $this->wallsModel;
    }

    public function getPrivateMessagesModel() {
	if (!isset($this->privateMessagesModel))
	    $this->privateMessagesModel = $this->getService('privateMessagesModel');
	return $this->privateMessagesModel;
    }

    public function getCategoriesModel() {
	if (!isset($this->categoriesModel))
	    $this->categoriesModel = $this->getService('categoriesModel');
	return $this->categoriesModel;
    }

    public function getGroupsModel() {
	if (!isset($this->groupsModel))
	    $this->groupsModel = $this->getService('groupsModel');
	return $this->groupsModel;
    }

    public function getUserModel() {
	if (!isset($this->userModel))
	    $this->userModel = $this->getService('userModel');
	return $this->userModel;
    }

    public function getOrdersModel() {
	if (!isset($this->ordersModel))
	    $this->ordersModel = $this->getService('ordersModel');
	return $this->ordersModel;
    }

    public function getSessionManager($section = NULL) {
	$res = $this->context->session;
	return $section === NULL ? $res : $res->getSection($section);
    }

    public function getActualSeasonId() {
	if (!isset($this->actualSeason)) {
	    $params = $this->context->getParameters();
	    $monthSeasStart = $params['season']['seasonStarts'];

	    $actDate = date('Y-m-d', time());
	    $arrDate = date_parse($actDate);
	    $arrDateMonth = &$arrDate['month'];
	    $arrDateYear = &$arrDate['year'];

	    if ($arrDateMonth >= $monthSeasStart && $arrDateMonth <= 12)
		$this->actualSeason = $arrDateYear;
	    else
		$this->actualSeason = $arrDateYear - 1;
	}
	return $this->actualSeason;
    }

    public function getUserId() {
	return -1;
    }

    public function beforeRender() {
	parent::beforeRender();
	// DEFAULT
	
	$this->template->ls = "homepage";
	$this->template->webProfilePrompt = "";
	$this->template->passwordResetPrompt = "";
    }

    public function startup() {
        parent::startup();
        $this->setEntityName("");
    }
    /** LoginControl factory.
     * @param component name $name
     * @return \Components\LoginControl 
     */
    public function createComponentLoginControl($name) {

	$res = new florbalMohelnice\Components\LoginControl($this, $name);

	$res->setInLabel('Přihlásit se');
	$res->setInText('Přihlásit se');
	$res->setInTarget('Auth:logIn');
	$res->setOutLabel('Odhlásit se');
	$res->setOutText("#");
	$res->setOutTarget('Auth:logOut');
	$res->setPrivateMessagesCallback(callback($this, 'getUnreadPMsCount'));
	$res->setTemplateFile(__DIR__ . '/../components/LoginControl/default.latte');
	//$res->setTemplateStatus(__DIR__ . '/../components/LoginControl/status.latte');
	return $res;
    }

    public function getUnreadPMsCount($kid) {

	try {
	    return $this->getPrivateMessagesModel()->getUnreadCount($kid);
	} catch (\Nette\IOException $ex) {
	    Debugger::log($ex, Debugger::ERROR);
	    $this->flashMessage("Nove zpravy se nepodarilo spocitat", "error");
	    return 0;
	} catch (\Nette\InvalidArgumentException $ex) {
	    $this->flashMessage("Nove zpravy se nepodarilo spocitat", "error");
	    Debugger::log("Get messages count with argument $kid", Debugger::DETECT);
	    return "?";
	}
    }

    /** Search text form factory
     * @param string
     * @return \Nette\Ápplication\UI\Form

      protected function createComponentSearchForm($name) {
      $form = new Form($this, $name);
      $form->addText('searchText')
      ->setDefaultValue('Zadejte hledaný výraz')
      ->setAttribute('id',FALSE)
      ->setAttribute('class','searchtext')
      ->setAttribute('onfocus',"if(this.value==this.defaultValue){this.value=''}")
      ->setAttribute('onblur',"if(this.value==''){this.value=this.defaultValue}");
      $form->getElementPrototype()->id = FALSE;

      $form->addSubmit('search',' ')
      ->setAttribute('class','searchsubmit');
      $form->onSuccess[] = callback($this, 'searchFormSubmitted');
      $form->getElementPrototype()->id = '';
      } */

    /**
     * Upper menu factory. 
     * @var string
     * @return MenuControl
     */
    public function createComponentClubMenu($name) {
	$user = $this->user;
	$action = $this->presenter->getAction();
	
	$comp = new MenuControl($this, $name);
	if (!$user->isLoggedIn())
	    return $comp;
	$comp->setLabel('Klubová sekce webu');
	
	if ($user->isAllowed('Club:walls', 'view')) {
	    $node = $comp->addNode('Nástěnky', 'Club:walls', TRUE, array('param'=>$this->getUserMainGroupAbbr(), "img" => "ico.png", "desc" => "neco proste tady muzes delat no .."));
	    if($action == 'walls')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Club:forum', 'view')) {
            $abbr = $this->getUserMainGroupAbbr();
	    $node = $comp->addNode('Fórum', "Club:forum", TRUE, array('param'=>$this->getUserMainGroupAbbr())); 
	    if($action == 'forum')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Club:members', 'view')) {
	    $node = $comp->addNode('Adresář členů', 'Club:members');
	    if ($action == 'members')
	    $comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Club:calendar', 'view')) {
	    $node = $comp->addNode('Kalendář akcí', 'Club:calendar', TRUE, array('param'=>$this->getUserMainGroupAbbr()));
	    if ($action == 'calendar')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Club:trainingParticipation', 'view')) {
	    $node = $comp->addNode('Docházka', 'Club:trainingParticipation');
	    if ($action == 'trainingParticipation')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Club:advantages', 'view')) {
	    $node = $comp->addNode('Výhody pro členy', 'Club:advantages');
	    if ($action == 'advantages')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Club:documents', 'view')) {
	    $node = $comp->addNode('Dokumenty', 'Club:documents');
	    if ($action == 'documents')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Club:mail', 'view')) {
	    $node = $comp->addNode('Klubový mail', 'http://mail.fbcmohelnice.cz', FALSE);
	    if ($action == 'mail')
		$comp->setCurrentNode($node);
	}
	
	return $comp;
    }

    // 			if ($i->menu_item_link === $this->getName()) 
    // 			$nav->setCurrentNode ($sec);
    /**
     * User menu factory. 
     * @var string
     * @return MenuControl
     */
    public function createComponentUserMenu($name) {
	$user = $this->user;
	$action = $this->presenter->getAction();
	
	$comp = new MenuControl($this, $name);
	
	if (!$user->isLoggedIn())
	    return $comp;
	$comp->setLabel('Uživatelská sekce');
	
	if ($user->isAllowed('User:messageBox', 'view')) {
	    $node = $comp->addNode('Zprávy', 'User:messageBox');
	    if ($action == 'messageBox')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('User:events', 'view')) {
	    $node = $comp->addNode('Události', 'User:events');
	    if ($action == 'events')
		$comp->setCurrentNode($node);
	}   
	    
	if ($user->isAllowed('User:payments', 'view')) {
	    $node = $comp->addNode('Platby', 'User:payments');
	    if ($action == 'payments')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('User:orders', 'view')) {
	    $node = $comp->addNode('Objednávky', 'User:orders');
            if ($action == 'orders')
		$comp->setCurrentNode($node);
	}
	if ($user->isAllowed('User:microPayments', 'view')) {
	    $node = $comp->addNode('Pokuty', 'User:microPayments');
	    if ($action == 'microPayments')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('User:credit', 'view')) {
	    $node = $comp->addNode('Kredity', 'User:credit');
	    if ($action == 'credit')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('User:data', 'view')) {
	    $node = $comp->addNode('Moje údaje', 'User:data');
	    if ($action == 'data')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('User:profile', 'view')) {
	    $node = $comp->addNode('Profil', 'User:profile');
	    if ($action == 'profile')
		$comp->setCurrentNode($node);
	}
	    $node = $comp->addNode('Odhlásit', 'Auth:logOut');
	return $comp;
    }

   /**
     * Mail menu factory. 
     * @var string
     * @return MenuControl
     */
    public function createComponentMailMenu($name) {
	$user = $this->user;
	$action = $this->presenter->getAction();
	
	$comp = new MenuControl($this, $name);
	
	if (!$user->isLoggedIn())
	    return $comp;
	$comp->setLabel('Zprávy');
	
	if ($user->isAllowed('User:messageBox', 'create')) {
	    $node = $comp->addNode('Nová zpráva', 'User:createMessage');
	    if ($action == 'createMessage')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('User:messageBox', 'view')) {
	    $node = $comp->addNode('Příchozí', 'User:inbox');
	    if ($action == 'inbox')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('User:messageBox', 'view')) {
	    $node = $comp->addNode('Odchozí', 'User:outbox');
	    if ($action == 'outbox')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('User:messageBox', 'view')) {
	    $node = $comp->addNode('Smazané', 'User:deleted');
	    if ($action == 'deleted')
		$comp->setCurrentNode($node);
	}
	return $comp;
    }


    /**
     * Admin menu factory. 
     * @var string
     * @return MenuControl
     */
    public function createComponentAdminMenu($name) {
	$user = $this->user;
	$action = $this->presenter->getAction();
	
	$comp = new MenuControl($this, $name);
	
	if (!$user->isLoggedIn())
	    return $comp;
	$comp->setLabel('Administrační sekce');
	
	if ($user->isAllowed('Admin:season', 'view')) {
	    $node = $comp->addNode('Sezóna', 'Admin:season');
	    if ($action == 'season')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:fileAssign', 'view')) {
	    $node = $comp->addNode('Přiřazení souborů', 'Admin:fileAssign');
	    if ($action == 'fileAssign')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:microPayments', 'view')) {
	    $node = $comp->addNode('Pokuty', 'Admin:microPayments');
	    if ($action == 'microPayments')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:payments', 'view')) {
	    $node = $comp->addNode('Platby', 'Admin:payments');
	    if ($action == 'payments')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:credit', 'view')) {
	    $node = $comp->addNode('Kredity', 'Admin:credit');
	    if ($action == 'credit')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:events', 'view')) {
	    $node = $comp->addNode('Akce', 'Admin:events');
	    if ($action == 'events')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:participation', 'view')) {
	    $node = $comp->addNode('Docházka', 'Admin:participation');
	    if ($action == 'participation')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:articles', 'view')) {
	    $node = $comp->addNode('Články', 'Admin:articles');
	    if ($action == 'articles')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:permit', 'view')) {
	    $node = $comp->addNode('Ke schválení', 'Admin:permit');
	    if ($action == 'permit')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:orders', 'view')) {
	    $node = $comp->addNode('Objednávky', 'Admin:orders');
	    if ($action == 'orders')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:gallery', 'view')) {
	    $node = $comp->addNode('Galerie', 'Admin:gallery');
	    if ($action == 'gallery')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:users', 'view')) {
	    $node = $comp->addNode('Uživatelé', 'Admin:users');
	    if ($action == 'users')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:walls', 'view')) {
	    $node = $comp->addNode('Nástěnky', 'Admin:walls');
	    if ($action == 'walls')
		$comp->setCurrentNode($node);
	}
	    
	if ($user->isAllowed('Admin:forums', 'view')) {
	    $node = $comp->addNode('Fóra', 'Admin:forums');
	    if ($action == 'forums')
		$comp->setCurrentNode($node);
	}
	
	if ($user->isAllowed('Admin:staticPages', 'view')) {
	    $node = $comp->addNode('Statická část', 'Admin:staticPages');
	    if ($action == 'staticPages')
		$comp->setCurrentNode($node);
	}
	
	return $comp;
    }
    
    public function createComponentCategoriesMenu($name) {
	try {
		$data = $this->getGroupsModel()->getGroups();
	} catch (\Nette\IOException $ex) {
		$this->flashMessage('Obsah skupin nemohl být načten', 'error');
		Debugger::log($ex->getMessage(), Debugger::ERROR);
		return FALSE;
	}
	$c = new MenuControl($this, $name);
	$action = $this->getAction();
	foreach ($data as $d) {
		$abbr = $d->abbr;
		$name = $d->name;
		$current = FALSE;
		if ($this->getParam('abbr') == $abbr) {
		    $current = TRUE;
		    $this->setEntityName($name);
		}
		
		$c->setLabel("Kategorie");
		$node = $c->addNode($name, $this->link($action, $abbr), FALSE, array(), $abbr);
		if ($current)
		    $c->setCurrentNode($node);
	}
	return $c;
    }
    
    /**
     * Comment form factory.
     * @param string
     */
    public function createComponentCommentForm($name) {

	$params = $this->getParameter();

	$relateType = $this->actionToType($params['action']);

	$form = new Form($this, $name);
	$form->addHidden('relate_post')->setValue((int) $params['id']);
	$form->addHidden('relation_mode')->setValue($relateType);
	$form->addTextArea('content', NULL, 60, 5, 1000)
		->addRule(Form::FILLED, 'Komentář musí mít text.');
	$form->addSubmit('sendComment', 'Odeslat komentář');
	$form->onSuccess[] = callback($this, 'sendCommentHandle');

	return $form;
    }

    /**
     * Convert action string to elemnt type string.
     * @param string
     */
    protected function actionToType($action) {

	$relateType = NULL;
	if (!is_string($action) || $action === '')
	    throw new \Nette\ÍnvalidArgumentException('Argument action has to be non-empty string.');

	switch ($action) {
	    case 'showPost':
		$relateType = 'wpo';
		break;
	    case 'showArticle':
		$relateType = 'art';
		break;
	    case 'showEvent':
		$relateType = 'eve';
		break;
	    case 'showUser':
		$relateType = 'use';
		break;
	    case 'showForum':
		$relateType = 'pho';
		break;
	    case 'showPage':
		$relateType = 'spa';
		break;
	    case 'showParticipation':
		$relateType = 'par';
		break;
	}

	return $relateType;
    }

    /**
     * @param string
     */
    public function createComponentCommentsControl($name) {

	$con = new \Components\CommentsControl($this, $name);

	$con->setTemplateFile('comments.latte');
	$param = $this->getParam();
	$con->setId((int) $param['id']);
	$con->setType($this->actionToType($param['action']));

	return $con;
    }

    /**
     * Send comment handle.
     * @param \Nette\Application\UI\Form
     */
    public function sendCommentHandle(Form $form) {

	$data = $form->getValues();
	$uid = $this->presenter->user->identity->id;
	$now = date('Y-m-d G:i:s');
	$data['kid'] = $uid;
	$data['inserted_time'] = $now;

	$this->context->models->comment->saveComment($data);
	$this->flashMessage('Komentář byl úspěšně přidán.', 'information');
	$this->redirect('this');
    }

    /** Search text form onSuccess handler 
     * @param \Nette\Ápplication\UI\Form
     */
    public function searchFormSubmitted(Form $form) {

	$values = $form->getValues();
	$phrase = $values['searchText'];
	if ($phrase != '' && $phrase != 'Zadejte hledaný výraz')
	    $this->redirect('Search:searchResult', $phrase);
	$this->redirect('this');
    }

    /** Not implemented yet.
     * @param type $path 
     * @return array('title', 'content', 'url') 
     */
    final public function getStaticPage($path) {

	// page model getPage($path)
	throw new \Nette\NotImplementedException("Not implemented yet.");
	return array('title' => NULL,
	    'content' => 'nic tu neni, musis dopsat metodu getStatic, aby neco natahla z DB',
	    'url' => NULL);
    }

    public function createComponentVisualPaginator($name) {
	return new VisualPaginator($this, $name);
    }
    
//    public function createComponentFileManager($name) {
//        $config = array(
//		    "dataDir"=>WWW_DIR, 
//		    "cacheDir"=>CACHE_DIR,
//		    "thumbsDir"=>FM_IMAGES_DIR,
//		    "resUrl"=>WWW_DIR."../libs/file-manager-1.0/src/resources",
//		    "cache"=>TRUE,
//		    "readonly"=>FALSE);
//        $fm = new \Ixtrum\FileManager(
//            $this->context->httpRequest,
//            $this->context->session,
//            $config);
//	return $fm;
//    }
    
    public function createComponentPublicMenu($name) {
	$c = new florbalMohelnice\Components\PublicMenuControl($name);
	$c->setModel($this->getStaticPageModel());
	return $c;
    }

}
