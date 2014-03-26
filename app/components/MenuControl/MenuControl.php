<?php

namespace florbalMohelnice\Components;

/**
 * Description of AdminMenu
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package MenuControl
 */
class MenuControl extends \Nette\Application\UI\Control {

    /** @var string Menu label */
    private $label;

    /** @var MenuNode Menu root node */
    private $rootNode;

    /** @var MenuNode Menu current node */
    private $current;

    /** @var string head menu Template file */
    private $headMenuTemplate;

    /** @var grid template file */
    private $gridTemplate;

    /** @var content menu template */
    private $contentMenuTemplate;

    private $rootNodeName = 'rootNode';
    
    public function getRootNodeName() {
	return $this->rootNodeName;
    }

    public function setRootNodeName($rootNodeName) {
	$this->rootNodeName = $rootNodeName;
    }
    
    public function getRootNode() {
	return $this->rootNode;
    }

    public function __construct($parent, $name) {
	$this->rootNode = new MenuControl\MenuNode($this, $this->rootNodeName);
	$this->label = $name;
	$this->headMenuTemplate = __DIR__ . '/headMenu.latte';
	$this->contentMenuTemplate = __DIR__ . '/contentMenu.latte';
	$this->gridTemplate = __DIR__ . '/gridMenu.latte';
    }

    /**
     * Label setter.
     * @param string
     */
    public function setLabel($label) {
	if (!is_string($label))
	    throw new \Nette\InvalidArgumentException('Argument has to be string.');

	$this->label = $label;
    }

    /**
     * Label getter.
     * @return string
     */
    public function getLabel() {

	if (!isset($this->label))
	    $this->label = '';

	return $this->label;
    }

    /**
     * Set menu template file.
     * @param string
     * @return MenuControl
     */
    public function setHeadMenuTemplate($template) {
	if (!is_string($template) || $template == '')
	    throw new \Nette\InvalidArgumentException('Argument has to be non-empty string, and the file has to exist.');

	$this->headMenuTemplate = $template;

	return $this;
    }

    public function setGridTemplate($template) {
	if (!is_string($template) || $template == '')
	    throw new \Nette\InvalidArgumentException('Argument has to be non-empty string, and the file has to exist.');

	$this->gridTemplate = $template;
	return $this;
    }

    public function setContentMenuTemplate($template) {
	if (!is_string($template) || $template == '')
	    throw new \Nette\InvalidArgumentException('Argument has to be non-empty string, and the file has to exist.');

	$this->contentMenuTemplate = $template;
	return $this;
    }

    /**
     * Add MenuNode to hierarchy.
     * @param string
     * @param string
     * @return MenuNode
     */
    public function addNode($label, $url, $mode = TRUE, $data = NULL, $name = NULL) {

	return $this->getComponent($this->rootNodeName)->addNode($label, $url, $mode, $data, $name);
    }

    /**
     * Render head menu method.
     */
    public function render() {
	$this->template->setFile($this->headMenuTemplate);
	$this->template->menuLabel = $this->label;
	$this->template->nodes = $this->rootNode->getComponents();
	$this->template->render();
    }

    /**
     * Render grid menu method
     */
    public function renderGrid() {
	$this->template->setFile($this->gridTemplate);
	$this->template->menuLabel = $this->label;
	$this->template->nodes = $this->rootNode->getComponents();
	$this->template->render();
    }

    /**
     * Render content menu method
     */
    public function renderContentMenu($class = 'menu') {
	$this->template->setFile($this->contentMenuTemplate);
	$this->template->menuLabel = $this->label;
	$this->template->nodes = $this->rootNode->getComponents();
	$this->template->layoutStyle = $this->presenter->getLayoutStyle();
	$this->template->class = $class;
	$this->template->render();
    }

    /**
     * Removes node from hierarchy.
     * @param string
     * @return MenuControl
     */
    public function removeNode($name) {

	$component = $this->getComponent($name);
	$component->getParent()->removeComponent($component);

	if ($name == rootNodeName)
	    $root = $this->createComponent(rootNodeName);
	$this->setRootNode($root);
	return $this;
    }

    /**
     * Set node as current.
     * @param MenuNode
     * @return MenuNode
     */
    public function setCurrentNode(\florbalMohelnice\Components\MenuControl\MenuNode $node) {

	if (isset($this->current))
	    $this->current->setCurrent(FALSE);
	
	if ($this->current == $node)
		return $node;
	
	$node->setCurrent(TRUE);
	$this->current = $node;
	
	return $node;
    }

    /**
     * Set node as root.
     * @param MenuNode
     * @return MenuNode
     */
    public function setRootNode(\florbalMohelnice\Components\MenuControl\MenuNode $node) {

	$this->rootNode = $node;
	$this->setRootNodeName($node->getName());
	return $node;
    }

}