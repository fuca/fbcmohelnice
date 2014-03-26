<?php

namespace florbalMohelnice\Components;

/**
 * Description of PublicMenuControl
 * 
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * Public menu control makes and render menu of public content
 * @package florbalMohelnice
 */
final class PublicMenuControl extends \Nette\Application\UI\Control {
    
    /** @var string template file */
    private $templateFile;
    
    /** @var model */
    private $model;
    
    private $rootElement;
    
    
    public function getHierarchy() {
	return $this->rootElement;
    }
    
    public function getModel() {
	return $this->model;
    }

    public function setModel($model) {
	
	$this->model = $model;
    }
        
    public function getTemplateFile() {
	if (!isset($this->templateFile))
	    $this->templateFile = "default.latte";
	return $this->templateFile;
    }

    public function setTemplateFile($templateFile) {
	$this->templateFile = $templateFile;
    }
        
    public function __construct() {
	$this->rootElement = new MenuControl($this, "PublicMenuControl");
	//$this->rootElement->getCo
    }
    
    public function render() {
	try {
	    $data = $this->getModel()->getFluent()
		    ->where('parent_page IS NULL')->orderBy("title")->execute()->fetchAll();	    
	} catch(\DibiException $ex) {
	    $this->presenter->flashMessage("Veřejné menu nemohlo být korektně sestaveno", "error");
	    \Nette\Diagnostics\Debugger::log($ex->getMessage(), $ex);
	}

	foreach($data as $d)
	    $this->createChildren($this->rootElement, $d);
	
	$this->template->setFile(__DIR__ . "/" . $this->getTemplateFile());
	$this->template->rootNode = $this->rootElement->getRootNode();
	$this->template->render();
    }
    
    public function createChildren($parentNode, $d) {
	$res = null;
	switch($d->children_count) {
	    case 0:
		$res = $parentNode->addNode($d->title, 
			    $this->presenter->link("ClubInfo:showStatic", 
						   $d->abbr), "static");
		break;
	    case -1:
		$split = explode(',', $d->content);
		$destination = $split[0];
		$splitSize = count($split);
		if ($splitSize > 2) {
		    $params = array();
		    for($i = 1; $i < $splitSize; $i++) {
			array_push ($params, $split[i]);
		    }
		} else {
		    if ($splitSize == 2)
			$params = $split[1];
		    else $params = NULL;
		}
		    
		$res = $parentNode->addNode($d->title,
			    $this->presenter->link($destination, $params), "extern");
		break;
	    default:
		try {
		    $subData = $this->getModel()->getFluent()
			->where('parent_page = %i', $d->id_page)
			->execute()->fetchAll();
		} catch(DibiException $ex) {
		    \Nette\Diagnostics\Debugger::log($ex->getMessage(), $ex);
		}
		$res = $subNode = $parentNode->addNode($d->title, 
				    $this->presenter->link("ClubInfo:showMenuPage", 
							   $d->id_page), "psz");
		foreach ($subData as $sd)
		    $this->createChildren($subNode, $sd);
	    }
	    return $res;
    }
}