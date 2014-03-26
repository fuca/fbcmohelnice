<?php
namespace florbalMohelnice\Components;

use Nette\Diagnostics\Logger,
	florbalMohelnice\Models\ICommentableModel,
	florbalMohelnice\Entities\Comment,
	florbalMohelnice\Forms\CommentForm;

/**
 * Description of CommentsControl
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class CommentsControl extends \Nette\Application\UI\Control {
	
	/** @var entity id */
	private $entityId;
	
	/** @var entity type */
	private $entityType;
	
	/** @var user id */
	private $userId;
	
	/** @var Model for certain type of commented subject */
	private $model;
	
	/** @var Mode of form */
	private $formMode;
	
	/** @var Name of template for render comments*/
	private $commTemplate;
	
	/** @var Name of template file to render comment's form */
	private $formTemplate;
	
	/**
	 *
	 */
	public function getUserId() {
		if(!isset($this->userId))
			throw new \Nette\InvalidStateException('Identifier of commenting user has to be set');
		return $this->userId;
	}
	
	/**
	 *
	 */
	public function setUserId($id) {
		$this->userId = $id;
	}
	
	/**
	 *
	 */
	public function getType() {
		if(!isset($this->entityType))
			throw new \Nette\InvalidStateException('Type of commented entity has to be set');
		return $this->entityType;
	}
	
	/**
	 *
	 */
	public function setType($type) {
		$this->entityType = $type;
	}
	
	/**
	 *
	 */
	public function getId() {
		if(!isset($this->entityId)) {
			$id = $this->parent->getSessionManager('CommentsControl')->id;
			if (!isset($id)) 
				throw new \Nette\InvalidStateException('Identifier of commented entity has to be set');
			else 
				$this->entityId = $id;
		}
		return $this->entityId;
	}
	
	/**
	 *
	 */
	public function setId($id) {
		$this->parent->getSessionManager('CommentsControl')->id = $id;
		$this->entityId = $id;
	}
	
	/**
	 *
	 */
	public function getFormMode() {
		if(!isset($this->formMode))
			$this->formMode = CommentForm::CREATE_MODE;
		return $this->formMode;
	}
	
	/**
	 *
	 */
	public function setFormMode($mode) {
		if ($mode != CommentForm::CREATE_MODE && $mode != CommentForm::UPDATE_MODE)
			throw new \Nette\InvalidArgumentException('Invalid argument type, use one of CommentForm modes');
		$this->formMode = $mode;	
	}
	
	/**
	 *
	 */
	public function setModel(ICommentableModel $m) {
		$this->model = $m;	
	}
	
	/**
	 *
	 */	
	public function getModel() {
		if (!isset($this->model))
			throw new \Nette\InvalidStateException('Attribute model has to be set');
		return $this->model;
	}
	
	/**
	 *
	 */
	public function getCommentsTemplate() {
		if(!isset($this->commTemplate))
			$this->commTemplate = __DIR__ . '/defComments.latte';
		return $this->commTemplate;
	}
	
	/**
	 *
	 
	public function setCommentsTemplate($path) {
		if(!file_exists($path))
			throw new \Nette\InvalidArgumentException("Messages template file doesn't exist");
		$this->commTemplate = $path;
	}	 */
	
	/**
	 *
	 */
	public function getFormTemplate() {
		if(!isset($this->formTemplate))
			$this->formTemplate = __DIR__ . '/defForm.latte';
		return $this->formTemplate;		
	}

	/**
	 *

	public function setFormTemplate($path) {
		if(!file_exists($path))
			throw new \Nette\InvalidArgumentException("Form template file doesn't exist");
		$this->formTemplate = $path;
	}
		 */
		
	/**
	 *
	 */
	public function render() {
		$this->renderComments();
	}
	
	/**
	 *
	 */
	public function __construct($parent, $name) {
		parent::__construct($parent, $name);
	}
	
	/**
	 *
	 */
	private function checkState() {
		if(!isset($this->model)) 
			throw new \Nette\InvalidStateException("Please use setModel(...) method");
		/*if(!isset($this->messTemplate)) 
			throw new \Nette\InvalidStateException("Please use setMessagesTemplate(...) method");
		if(!isset($this->formTemplate)) 
			throw new \Nette\InvalidStateException("Please use setFormTemplate(...) method");*/
	}

	/**
	 *
	 */
	public function renderComments() {
		$this->checkState();
		$this->template->setFile($this->getCommentsTemplate());
		$comments = FALSE;
		try {
			$comments = $this->model->getCommentsFluent($this->getId(), $this->getType())
							->orderBy('inserted_time')->desc()
							->execute()->fetchAssoc('id_comment');
		} catch(DibiException $ex) {
			$this->parent->flashMessage('Komentáře nejsou dostupné');
		}
		
		$this->template->comments = $comments;
		$this->template->render();
	}
	
	/**
	 *
	 */
	public function renderForm() {
		$this->checkState();
	 	$this->template->setFile($this->getFormTemplate());
	 	$this->getComponent('commentForm')
	 		 ->setDefaults(array('relate_post'=>$this->getId()));
	 	$this->template->render();
	}
	
	/**
	 *
	 */
	public function addComment(Comment $c) {
		$com 				= $c;
//		$com->relate_post 	= $this->getId();
		$com->relation_mode = $this->getType();
		$com->kid 			= $this->getUserId();
		try {
			$this->getModel()->createComment($com);
		} catch(IOException $ex ) {
			$this->parent->flashMessage('Komentář nebyl přidán');
			// TODO LOGGER
		}
		$this->presenter->redirect('this');
	}
	
	/**
	 *
	 */
	public function editComment(Comment $c) {
		
	} 
	
	/**
	 *
	 */
	public function createComponentCommentForm($name) {
		$c = new CommentForm($this, $name, $this->getFormMode());
		return $c;
	}
	
		/**
	 *
	 */
	public function createComponentEditCommentForm($name) {
		$c = new CommentForm($this, $name, $this->getFormMode());
		return $c;
	}
}
