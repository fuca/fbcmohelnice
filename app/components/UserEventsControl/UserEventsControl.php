<?php
namespace florbalMohelnice\Components;

use Grido\Grid,
    Grido\Components\Actions\Action,
    Grido\Components\Filters\Filter,
    Grido\Components\Columns\Column,
    Grido\Components\Columns\Date,
    \florbalMohelnice\Entities\User;
/**
 * Description of UserEventsControl
 *
 * @author fuca
 */
class UserEventsControl extends \Nette\Application\UI\Control {
    
    /** var user id */
    private $kid;
    
    /** template file path */
    private $confirmedTemplate;
    
    /** template file path */
    private $dismissedTemplate;
    
    /** template file path */
    private $groupTemplate;
    
    /** responsible data model */
    private $model;
    
    /** model for fetching set of user's groups */ // callback would be better
    private $groupsModel;
    
    /** model for fetching users */
    private $usersModel;
    
    public function getUsersModel() {
	return $this->usersModel;
    }

    public function setUsersModel(\florbalMohelnice\Models\UserModel $usersModel) {
	$this->usersModel = $usersModel;
    }
    
    public function getConfirmedTemplate() {
	return $this->confirmedTemplate;
    }

    public function setConfirmedTemplate($confirmedTemplate) {
	if (!file_exists($confirmedTemplate))
	    throw new Nette\FileNotFoundException("Given template file does not exist!");
	$this->confirmedTemplate = $confirmedTemplate;
    }

    public function getDismissedTemplate() {
	return $this->dismissedTemplate;
    }

    public function setDismissedTemplate($dismissedTemplate) {
	if (!file_exists($dismissedTemplate))
	    throw new Nette\FileNotFoundException("Given template file does not exist!");
	$this->dismissedTemplate = $dismissedTemplate;
    }

    public function getGroupTemplate() {
	return $this->groupTemplate;
    }

    public function setGroupTemplate($groupTemplate) {
	if (!file_exists($groupTemplate))
	    throw new Nette\FileNotFoundException("Given template file does not exist!");
	$this->groupTemplate = $groupTemplate;
    }

    public function getGroupsModel() {
	return $this->groupsModel;
    }

    public function setGroupsModel(\florbalMohelnice\Models\GroupsModel $groupsModel) {
	$this->groupsModel = $groupsModel;
    }

    public function setKid($k) {
	if (!is_numeric($k))
	    throw new Nette\InvalidArgumentException("Argument has to be type of numeric");
	$this->kid = $k;
    }
    
    public function setModel(\florbalMohelnice\Models\EventsModel $model) {
	if ($model == NULL)
	    throw new Nette\InvalidArgumentException("Argument can't be NULL");
	$this->model = $model;
    }

    public function __construct($parent = NULL, $name = NULL) {
	parent::__construct($parent, $name);
	$this->setConfirmedTemplate(__DIR__ . "/defConfirmed.latte");
	$this->setDismissedTemplate(__DIR__ . "/defDismissed.latte");
	$this->setGroupTemplate(__DIR__ . "/defGroup.latte");
    }
    
    public function getKid() {
	return $this->kid;
    }

    public function getModel() {
	return $this->model;
    }

    public function getTemplateFile() {
	return $this->templateFile;
    }
    
    public function render() {
	$this->renderConfirmed();
    }
    
    public function renderConfirmed() {
	$this->template->setFile($this->getConfirmedTemplate());
	$this->template->render();
    }
    
    public function renderDismissed() {
	$this->template->setFile($this->getDismissedTemplate());
	$this->template->render();
    }
    
    public function renderGroup() {
	$this->template->setFile($this->getGroupTemplate());
	$this->template->render();
    }
    
    public function createComponentAdminForm($name) {
	$frm = new EventParticipationAdminForm($this, $name);
	return $frm;
    }
    
    private function prepareGrid($name) {
	$filterRenderType = Filter::RENDER_INNER;
	$grid = new Grid($this, $name);
	
	$grid->setDefaultPerPage(15);
	$grid->setPrimaryKey('id_event');
	$grid->addColumn('title', 'Titulek')
		->setSortable();

	$grid->addColumn('take_place_from', 'Kdy', Column::TYPE_DATE)
		->setSortable();
	$grid->addAction('eventRedirect', 'Přejít', Action::TYPE_HREF,
		'Club:showEvent');
	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();
	return $grid;
    }
    

    
    public function createComponentConfirmedGrid($name) {
	$c = $this->prepareGrid($name);
	$c->setModel($this->getModel()
		->getUserParticipatedEventsFluent($this->getKid(), 1));
	return $c;
    }
    
    public function createComponentDismissedGrid($name) {
	$c = $this->prepareGrid($name);
	$c->setModel($this->getModel()
		->getUserParticipatedEventsFluent($this->getKid(), 0));
	return $c;
    }
    
    public function createComponentGroupAvailableGrid($name) {
	$c = $this->prepareGrid($name);
	$groups = $this->getGroupsModel()->getUserGroups(
		    new User(array('kid' => $this->getKid())));
	$c->setModel($this->getModel()
		->getAllEventsByCategory($groups, TRUE, TRUE));
	return $c;
    }
}
