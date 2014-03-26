<?php

use florbalMohelnice\Forms\LogInForm;
/**
 * Desc. AuthPresenter
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class AuthPresenter extends BasePresenter {
    
	/** @persitent */
	public $backlink = '';
	
	public function startup() {
	    parent::startup();
	    //$this->setLayoutStyle();
	    
	}
	
	public function beforeRender() {
	    parent::beforeRender();
	    $this->template->layoutStyle = $this->getLayoutStyle();
	}
	
	/** Vytvori komponentu loginForm a ta se v sablone vykresluje */
	public function actionLogIn($backlink) {
		$form = new LogInForm($this, 'loginForm');
		$form->setBacklink($backlink);
		return $form;
	}
	
	public function actionDefault() {
	    
	    $this->redirect('Club:default');
	}
	
	public function actionLogOut() {
		
		$this->user->logout(TRUE);
		$this->flashMessage('Uživatel byl odhlášen.','information');
		$this->redirect('Homepage:default');
  }
}

