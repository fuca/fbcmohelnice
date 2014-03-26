<?php

namespace florbalMohelnice\Components\MenuControl;

/**
 * Description of MenuNode
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package MenuControl
 */
class MenuNode extends \Nette\ComponentModel\Container {

	/** @var boolean */
	private $isCurrent = FALSE;
	
	/** @var string */
	private $label;
	
	/** @var string */
	private $url;
	
	/** @var bool */
	private $mode;
	
	/** @var mixed */
	private $data;
	
	public function setLabel($label) {
	    $this->label = $label;
	}

	public function setUrl($url) {
	    $this->url = $url;
	}

	public function setMode($mode) {
	    $this->mode = $mode;
	}

	public function setData($data) {
	    $this->data = $data;
	}

		
	/**
	 * Data getter
	 * @return mixed
	 */
	public function getData() {
	    return $this->data;
	}

	/** 
	 * Url getter.
	 * @return string
	 */
	public function getUrl() {
		
		if (!isset($this->url))
			$this->url = '#';
		
		return $this->url;
	}
	
	/** 
	 * Url getter.
	 * @return string
	 */
	public function getMode() {
		
		if (!isset($this->mode))
			$this->mode = TRUE;
		
		return $this->mode;
	}
	
	/**
	 * Label getter.
	 * @return string
	 */
	public function getLabel() {
		
		if (!isset($this->label))
			$this->label = 'ERROR';
		
		return $this->label;
	}
	
	/** 
	 * Adds new node as child.
	 * @param string
	 * @param string
	 * @param bool
	 * @param mixed
	 * @return MenuNode
	 */
	public function addNode($label, $url, $mode = TRUE, $data = NULL, $name = NULL) {
		
		$node = new self;
		$node->url = $url;
		$node->label = $label;
		$node->mode = $mode;
		$node->data = $data;
		
		static $counter = 0;
		$this->addComponent($node, $name != NULL? $name:++$counter);
		
		return $node;
	}
	
	/** 
	 * Set up current state.
	 * @param bool
	 * @return MenuNode
	 */
	public function setCurrent($bool) {
		
		$this->isCurrent = $bool;
		
//		if ($bool)
//			$this->lookup('\florbalMohelnice\Components\MenuControl')->setCurrentNode($this);
		
		return $this;
	}
	
	/**
	 * Current node predicate
	 * @return type
	 */
	public function isCurrent() {
	    return $this->isCurrent;
	}

}

