<?php

namespace florbalMohelnice\Components;

/**
 * Description of ForumNode
 *
 * @author fuca
 */
class ForumNode {

    private $id;
    private $updateKid;
    private $updateTime;
    private $parentForum;
    private $title;
    private $description;
    private $viewPersmission;
    private $commentMode;
    
    function __construct($id, $updateKid, $updateTime, $parentForum, $title, $description, $viewPersmission, $commentMode) {
	parent::__construct();

	$this->id = $id;
	$this->updateKid = $updateKid;
	$this->updateTime = $updateTime;
	$this->parentForum = $parentForum;
	$this->title = $title;
	$this->description = $description;
	$this->viewPersmission = $viewPersmission;
	$this->commentMode = $commentMode;
    }

    
    public function add(\florbalMohelnice\Components\ForumNode $n) {
	$this->addComponent($n);
    }
    
    public function removeNode($id) {
	$this->removeComponent();
    }
}

