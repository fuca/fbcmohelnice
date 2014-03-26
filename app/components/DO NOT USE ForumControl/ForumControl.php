<?php

namespace florbalMohelnice\Components;

/**
 * Description of ForumControl
 *
 * @author fuca
 */
final class ForumControl extends \Nette\Application\UI\Control {

    private $rootNode;

    private $current;
    
    private $label;
    
    private $templateFile;
    
    private $menuTemplate;
    
    public function getMenuTemplate() {
	return $this->menuTemplate;
    }

    public function setMenuTemplate($menuTemplate) {
	$this->menuTemplate = $menuTemplate;
    }

        
    public function getCurrent() {
	return $this->current;
    }

    public function setCurrent($current) {
	$this->current = $current;
    }

    public function getLabel() {
	return $this->label;
    }

    public function setLabel($label) {
	$this->label = $label;
    }

    public function getTemplateFile() {
	return $this->templateFile;
    }

    public function setTemplateFile($templateFile) {
	if (!file_exists($templateFile))
	    throw new \Nette\FileNotFoundException("Given templatefile does not exist");
	$this->templateFile = $templateFile;
    }

        const rootNodeName = 'FBC Mohelnice';

    public function setRootNode(\florbalMohelnice\Components\ForumNode $fn) {
	$this->rootNode = $fn;
    }

    public function getControls() {
	return $this->controls;
    }

    public function addNode(e $fn) {
	$this->addComponent($fn, $fn->getId());
    }

    public function removeNode($name) {
	$c = $this->getComponent($name);
	$c->getParent()->removeComponent($c);
	if ($c->getName() == rootNodeName) {
	    $root = $this->createComponent(rootNodeName);
	    $this->setRootNode($root);
	}
    }

    public function __construct($parent, $name) {
	
    }

}
