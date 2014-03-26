<?php

use Nette\Diagnostics\Debugger;
/**
 * Description of ClubInfoPresenter
 *
 * @author Michal Fučík
 * @package fbcmoh
 */
class ClubInfoPresenter extends BasePresenter {
    
	const LAYOUT_STYLE = "homepage";
	
	    public function startup() {
	parent::startup();
	$this->setLayoutStyle(self::LAYOUT_STYLE);
    }

    public function beforeRender() {
	parent::beforeRender();
	$this->template->layoutStyle = $this->getLayoutStyle();
    }
	
	public function actionDefault() {}
	public function renderDefault() {}
	
	public function actionShowStatic($abbr) {
	    $page = null;
	    $model = $this->getStaticPageModel();
	    try {
		$page = $model->getPageByAbbr($abbr);
	    } catch(Nette\IOException $ex) {
		Debugger::log($ex->getMessage(), Debugger::ERROR);
		$this->flashMessage('Stránku se nepodařilo načíst', 'error');
	    } catch(\Nette\InvalidArgumentException $ex) {
		Debugger::log($ex->getMessage(), Debugger::ERROR);
		$this->flashMessage('Špatný nebo žádný parameter', 'error');
	    }
	    $this->template->page = $page;
	}
	
	public function renderShowStatic() {}
	
	public function actionShowMenuPage($id) {
	    if (!is_numeric($id)) {
		$this->flashMessage("Špatný nebo žádný argument", "error");
		$this->redirect("Homepage:default");
	    }
	    try {
		$data = $this->getStaticPageModel()->getFluent()
			->where("parent_page = %i", $id)->orderBy("title")
			->execute()->fetchAll();
	    } catch (Nette\IOException $ex) {
		$this->flashMessage("Stránku nelze zobrazit", "error");
		\Nette\Diagnostics\Debugger::log($ex->getMessage());
	    }
	    // tady bych asi vytvoril nejaky menu nebo neco na cem se domluvime
	}
	
	
}
