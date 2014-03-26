<?php

use Grido\Grid,
    Grido\Components\Actions\Action,
    Grido\Components\Filters\Filter,
    Grido\Components\Columns\Column,
    Grido\Components\Columns\Date,
    florbalMohelnice\Components\WallControl,
    florbalMohelnice\Entities\Group,
    florbalMohelnice\Components\CommentsControl,
    Nette\Diagnostics\Debugger;

/**
 * Desc. ClubPresenter
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package fbcmoh
 */
final class ClubPresenter extends SecuredPresenter {

    const LAYOUT_STYLE = "club";

    /** @var links for wall per group */
    private $groupLinks;

    /** @var current entity id */
    private $entityId;
    
    /**
     *
     */
    public function startup() {
	parent::startup();
	$this->entityId = NULL;
	$this->setLayoutStyle(self::LAYOUT_STYLE);
    }

    public function beforeRender() {
	parent::beforeRender();
	$this->template->layoutStyle = $this->getLayoutStyle();
    }

    public function getEntity() {
	return $this->entity;
    }

    public function setEntity($entity) {
	$this->entity = $entity;
    }
        
    /**
     *
     */
    public function getId() {
	if (!isset($this->entityId))
	    throw new \Nette\InvalidStateException("Entity id is not properly set");
	return $this->entityId;
    }

    /**
     *
     */
    public function setId($id) {
	$this->entityId = $id;
    }
    
    /**
     *
     */
    public function setWallName($name) {
	$this->setEntityName($name);
    }

    /**
     *
     */
    public function getWallName() {
	return $this->getEntityName();
    }

    public function __construct(Nette\DI\Container $context) {
	parent::__construct($context);
    }

    public function actionDefault() {
	if (!$this->user->isAllowed("Club:default", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function renderDefault() {
	
    }

// ---------------------------- WALLS --------------------------------------	

    public function actionWalls($abbr) {
	if (!$this->user->isAllowed("Club:walls", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
//	$vp = new VisualPaginator($this, 'vp');
	$vp = $this->getComponent('visualPaginator');
	$paginator = $vp->getPaginator();
	$paginator->setItemsPerPage(10);

        $now = new DateTime();
        
	$wallPostModel = $this->getWallsModel();
	$fluent = $wallPostModel->getFluent($abbr);

	try {
	    $validData = $fluent->where("show_from <= %t AND show_to >= %t AND status = %s", 
                    $now, $now, \florbalMohelnice\Entities\WallPost::STATUS_PUBLISHED)
			    ->execute()->fetchAssoc('id_wallpost'); 
	    $invalidDataCount = $wallPostModel->countFromFluent(
                    $fluent->clause('where')->and("show_to < %t AND status = %s", 
                            $now, \florbalMohelnice\Entities\WallPost::STATUS_PUBLISHED));

	    $paginator->setItemCount($invalidDataCount);
            
	    $invalidData = $wallPostModel->getFluent($abbr)->where("show_to < %t AND status = %s", 
                    $now, \florbalMohelnice\Entities\WallPost::STATUS_PUBLISHED)
			    ->offset($paginator->getOffset())->limit($paginator->getLength())
			    ->execute()->fetchAssoc('id_wallpost');
	} catch (\Nette\IOException $ex) {
	    $this->flashMessage('Obsah nemohl být načten', 'error');
	    Debugger::log($ex->getMessage()->getMessage(), Debugger::ERROR);
	}

	$this->template->actualData = $validData;
	$this->template->oldData = $invalidData;
	$this->getComponent("categoriesMenu"); // potreba vedlejsi efekt v teto chvili
	$this->template->title = $this->getWallName();
        $this->template->abbr = $abbr;
        
    }

    public function renderWalls() {
	
    }

    public function actionShowWallPost($id, $abbr) {
	if (!$this->user->isAllowed("Club:walls", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	$this->setId($id);
	$editAllowed = false;

	try {
	    $res = $this->getWallsModel()->getWallPost((integer) $id);
	} catch (IOException $ex) {
	    $this->flashMessage('Zprávu nebylo možné zobrazit', 'error');
	    Debugger::log($ex->getMessage()->getMessage(), Debugger::ERROR);
	    $this->redirect('Club:walls');
	}
	if ($this->user->isAllowed("Admin:walls", "edit"))
	    $editAllowed = true;
        
        $this->getComponent("categoriesMenu"); // potreba vedlejsi efekt v teto chvili
	$this->template->title = $this->getWallName();
	$this->template->editAllowed = $editAllowed;
	$this->template->wallPost = $res;
        $this->template->abbr = $abbr;
    }

    public function createComponentWall($name) {
	$result = new WallControl($this, $name);
	$result->setFluent($this->getWallsModel()->getFluent());
	return $result;
    }

// ------------------------------ MEMBERS ----------------------------------

    public function actionMembers() {
	if (!$this->user->isAllowed("Club:members", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function renderMembers() {
	
    }

    protected function createComponentMembersConctactGrid($name) {
	$filterRenderType = Filter::RENDER_OUTER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getService('userModel')->getFluentContacts());

	$grid->setDefaultPerPage(50);
	$grid->setPrimaryKey('kid');

	$grid->addColumn('surname', 'Příjmení')
		->setSortable()
		->setFilter()
		->setSuggestion();

	$grid->addColumn('name', 'Jméno')
		->setFilter()
		->setSuggestion();

	$grid->addColumn('nick', 'Přezdívka');

	$grid->addColumn('email', 'Email')
		->setFilter()
		->setSuggestion();

	$grid->addColumn('phone', 'Telefon')
		->setFilter()
		->setSuggestion();

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();
    }

// ------------------------------ CALENDAR ---------------------------------

    public function actionCalendar($abbr = 'fbc') {
	if (!$this->user->isAllowed("Club:calendar", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	try {
	    $events = $this->getEventsModel()->getAllEventsByCategory(array($abbr));
	} catch (IOException $ex) {
	    $this->flashMessage('Události se nezdařilo načíst', 'error');
	    Debugger::log($ex->getMessage()->getMessage(), Debugger::ERROR);
	}
	// tady muzu ve foreachi vyfiltrovat, opravneni na jednotlive akce..
	$this->getComponent('categoriesMenu');
	$this->template->title = $this->getWallName();
	$this->template->events = $events;
        $this->template->abbr = $abbr;
    }

    public function renderCalendar() {}

    /**
     *
     */
    public function actionShowEvent($id_event, $abbr) {
	if (!$this->user->isAllowed("Club:calendar", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
        
	$this->setId($id_event);
	
	try {
	    $ev = $this->getEventsModel()->getEvent($id_event);
	} catch (IOException $ex) {
	    $this->flashMessage('Událost se nepodařilo načíst', 'error');
	    Debugger::log($ex->getMessage()->getMessage(), Debugger::ERROR);
	}
	$this->setEntity($ev);
        $this->getComponent('categoriesMenu');
        $this->template->title = $this->getWallName();
	$this->template->event = $ev;
        $this->template->abbr = $abbr;
    }

    public function createComponentEventParticipation($name) {
	$c = new florbalMohelnice\Components\EventParticipationControl($this, $name);
	$c->setKid($this->user->getId());
	try {
	    $c->setIdEvent($this->getId());
	} catch(\Nette\InvalidStateException $x) {
	    $this->flashMessage ('Chyba inicializace modulu EventParticipation', 'error');
	    Debugger::log("ERROR > ClubPresenter:371 > Pokus o inicializaci komponenty za stavu neplatneho id_event", $x);
	    $this->redirect("Club:default");
	}
	$c->setConfirmDate($this->getEntity()->confirm_until);
	$c->setModel($this->getParticipationModel());
	return $c;
    }
    
// ------------------------------ ADVANTAGES -------------------------------

    public function actionAdvantages() {
	if (!$this->user->isAllowed("Club:advantages", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function renderAdvantages() {
	
    }

// ----------------------------- DOCUMENTS ---------------------------------

    public function actionDocuments() {
	if (!$this->user->isAllowed("Club:documents", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function renderDocuments() {
	
    }

// ----------------------------- CLUB MAIL ---------------------------------

    public function actionClubMail() {
	if (!$this->user->isAllowed("Club:mail", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
    }

    public function renderClubMail() {
	
    }

// ---------------------------- COMMENTS -----------------------------------

    /**
     *
     */
    public function createComponentWallPostComments($name) {
	$c = new CommentsControl($this, $name);
	$c->setModel($this->getWallsModel());
	$c->setId($this->getId());
	$c->setType(self::C_WALLPOST_TYPE);
	$c->setUserId($this->getUserId());
	return $c;
    }

    /**
     *
     */
    public function createComponentEventComments($name) {
	$c = new CommentsControl($this, $name);
	$c->setModel($this->getEventsModel());
	$c->setId($this->getId());
	$c->setType(self::C_EVENT_TYPE);
	$c->setUserId($this->getUserId());
	return $c;
    }

    /**
     *
     */
    public function createComponentForumComments($name) {
	$c = new CommentsControl($this, $name);
	$c->setModel($this->getForumModel());
	$c->setId($this->getId());
	$c->setType(self::C_FORUM_TYPE);
	$c->setUserId($this->getUserId());
	return $c;
    }

// -------------------------- FORUM ----------------------------------------

    /**
     * Entry point of public forum page
     * @param type $id_forum
     * @throws \Nette\InvalidArgumentException
     */
    public function actionForum($abbr = 'fbc') {
	if (!$this->user->isAllowed("Club:forum", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
//	if (!is_numeric($id_forum) && $id_forum != NULL)
//	    throw new \Nette\InvalidArgumentException("Argument has to be type of numeric or NULL");
	try {
            
	    $fs = $this->getForumModel()->getForumsWithinGroup($abbr);
//	    if ($id_forum != NULL) {
//		$parent = $this->getForumModel()->getForum($id_forum);
//		$this->template->parent = $parent;
//	    }
	} catch (IOException $ex) {
	    $this->flashMessage('Kategorie fóra se nepodařilo zobrazit');
	    Debugger::log($ex->getMessage()->getMessage(), Debugger::ERROR);
	}
	foreach ($fs as $f) {
	    if (!$this->hasEqualOrGreater(array_keys($this->user->roles), $f->view_permission))
		unset($f);
	}
	$this->getComponent("categoriesMenu"); // vedlejsi efekt nastaveni nazvu wallName
	$this->template->title = $this->getWallName();
	$this->template->forums = $fs;
        $this->template->abbr = $abbr;
    }

    private function hasEqualOrGreater(array $search, $key) {
	foreach ($search as $item) {
	    if ($item >= $key)
		return TRUE;
	}
	return FALSE;
    }
    
    /**
     *
     */
    public function renderForum() {
	
    }

//    public function createComponentForumsMenu($name) {
//
//	$c = new \florbalMohelnice\Components\MenuControl($this, $name);
//	$c->setLabel("Fóra");
//	
//	$root = new florbalMohelnice\Components\MenuControl\MenuNode($c, 0);
//	$root->setUrl("Club:forum");
//	$root->setLabel("FBC Mohelnice");
//    	$root->mode = TRUE;
//	$root->data = NULL;
//	
//	$par = FALSE;
//	$c->setRootNode($root);
//	$fList = $this->getForumModel()->getAll();
//	
//	foreach ($fList as $f) {
//	    try {
//		$par = $c->getComponent($f->parent_forum);
//	    } catch(Nette\InvalidArgumentException $ex) {}
//	    if ($par) {
//		$par->addNode($f->title, "Club:forum", TRUE, array("parameter"=>$f->id_forum), $f->id_forum);
//	    } else {
//		$c->addNode($f->title, "Club:forum", TRUE, array("parameter"=>$f->id_forum), $f->id_forum);
//	    }
//	}
//	return $c;
//    }

    /**
     * Shows details of entire forum thread
     * @param type $id_forum
     */
    public function actionForumThread($id_forum, $abbr) {
	if (!$this->user->isAllowed("Club:forum", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("User:default");
	}
	$this->setId($id_forum);
	try {
	    $forum = $this->getForumModel()->getForum($id_forum);
	} catch (IOException $ex) {
	    $this->flashMessage('Forum nemohlo být načteno');
	    Debugger::log($ex->getMessage()->getMessage(), Debugger::ERROR);
	    $this->redirect('Club:forum');
	}
	if (!$forum) {
	    $this->flashMessage('Dané forum neexistuje');
	    $this->redirect('Club:forum');
	}
        
        $this->getComponent("categoriesMenu"); // vedlejsi efekt nastaveni nazvu wallName
	$this->template->title = $this->getWallName();
	$this->template->forum = $forum;
        $this->template->abbr = $abbr;
    }

    /**
     * 
     */
//	public function createComponentMemberSearchForm($name) {
//		
//		$form = new \Nette\Application\UI\Form($this, $name);
//		$form->addGroup('Hledat');
//		$form->addText('expression', 'Výraz', '30', '50')
//				->setDefaultValue('Zadejte hledaný výraz')
//				->setAttribute('onfocus',"if(this.value==this.defaultValue){this.value=''}")
//				->setAttribute('onblur',"if(this.value==''){this.value=this.defaultValue}");
//		//$form->addRadioList('modeRadio', '', array('name'=>'Jméno', 'kid'=>'KID'))->setDefaultValue('kid');
//		$form->addSubmit('searchSubmit', 'Hledej');
//		$form->onSuccess[] = callback($this, 'searchMembersHandle');
//	}

    /**
     * Search members submit handler.
     * @param \Nette\Application\UI\Form
     */
//	public function searchMembersHandle(\Nette\Application\UI\Form $form) {
//		
//		$data = $form->getValues();
//		
//		$data = $this->context->models->search->getMembers($data);
//		// mozna by se hodilo ulozit expr do sessny a plnit jim form v actionMembers
//		
//		$this->redirect('Members', $data);
//	}

    /*     * */
//	public function createComponentMemberGrid($name) {
//		
//		$grid = new Tabella($this->context->models->users->usersGrid(), $this->context, array());
//		$grid->addColumn('KID', 'kid', array('width'=>8));
//		$grid->addColumn('Příjmení', 'surname', array('width'=>20));
//		$grid->addColumn('Jméno', 'name', array('width'=>20));
//		$grid->addColumn('E-mail', 'email', array('width'=>30));
//		$grid->addColumn('Year', 'year', array('width'=>10));
//		return $grid;	
//	}


    /*     * */
//	public function createComponentMemberDataGrid($name) {
//		
//		$grid = new DataGrid($this, $name);
//		$model = $this->context->models->users;
//		
//		$grid->bindDataTable($model->getPublicData('Users'));
//		$grid->addColumn('kid','KID');
//		$grid->addColumn('name','Jméno');
//		$grid->addColumn('surname','Příjmení');
//		$grid->addColumn('year','Ročník');
//		
//		
//		return $grid;
//	}
}

