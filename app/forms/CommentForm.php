<?php
namespace florbalMohelnice\Forms;

use Nette\Application\UI\Form,
	Nette\DateTime,
	Vodacek\Forms\Controls\DateInput,
	florbalMohelnice\Entities\Comment;
/**
 * Description of CommentForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class CommentForm extends Form {

	const UPDATE_MODE = 'update';
	const CREATE_MODE = 'create';
	
	/** @var form mode*/ // ENUM create/update
	private $mode;
	
	public function getMode() {
		return $this->mode;
	}
	
	public function setMode($m) {
		if (($m != self::CREATE_MODE) && ($m != self::UPDATE_MODE)) {
			throw new \InvalidArgumentException(
			"Mode has to be set on ". self::CREATE_MODE ." or ". self::UPDATE_MODE . ", '$m' given.");
		}
		$this->mode = $m;
	}
		
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, $mode = self::CREATE_MODE) {
		parent::__construct();
		
		$this->setMode($mode);
		
		$this->addHidden('id_comment');
		$this->addHidden('relate_post');	
		$this->addHidden('relation_mode');
		
		$ta = $this->addTextArea('content', '', 40, 5)
			 ->addRule(Form::FILLED, 'Komentář musí mít nějaký obsah');
		//$ta->getControlPrototype()->id('elm1');
		$ta->setAttribute('id', 'elm1');
				
		$sbmt = $this->addSubmit('add','Odeslat');
		$sbmt->setAttribute('id', 'submit');
		$this->onSuccess[] = callback($this, 'commentFormSubmitted');
	}		
	
	/**
	 *
	 */
	public function commentFormSubmitted(Form $form) {
		$values  = $form->getValues();
		$comment = new Comment($values);
		$now 	 = new DateTime();
		switch($this->getMode()) {
			case self::CREATE_MODE:
				$comment->offsetSet('inserted_time', $now);				
				$comment->offsetSet('updated_time', $now);
				$this->parent->addComment($comment);
				break;
			case self::UPDATE_MODE:
				$comment->offsetSet('updated_time', $now);
				$this->parent->editComment($comment);
				break;
		}
	}
	
}
