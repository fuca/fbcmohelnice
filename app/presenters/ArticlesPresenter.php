<?php

use florbalMohelnice\Entities\Article,
    florbalMohelnice\Components\CommentsControl,
    Nette\Diagnostics\Debugger;

/**
 * Desc. ArticlesPresenter
 *
 * @author Michal Fučík michal.fuca.fucik@gmail.com
 * @package fbcmoh
 */
final class ArticlesPresenter extends BasePresenter {

    /** @var current article id */
    private $articleId;

    /**
     *
     */
    public function getId() {
	return $this->articleId;
    }

    /**
     *
     */
    public function setId($id) {
	$this->articleId = $id;
    }

    public function startup() {
	parent::startup();
	//$this->setLayoutStyle("whatever u want");
    }

    public function beforeRender() {
	parent::beforeRender();
	$this->template->layoutStyle = $this->getLayoutStyle();
    }

    /*     * */

    public function renderDefault($abbr = NULL) {
	if (!$this->user->isAllowed("Articles:default", "view")) {
	    $this->flashMessage("Nedostatečné oprávnění", "error");
	    $this->redirect("Homepage:default");
	}
	try {
	    $arts = $this->getArticlesModel()
		    ->getArticlesWithinGroup($abbr);
	} catch (DibiException $ex) {
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->flashMessage('Vyskytly se neočekávané potíže, omlouváme se', 'error');
	    return;
	}
	$this->template->articles = $arts;
    }

    /*     * */

    public function renderShowArticle($id = 0) {

	$this->setId($id);
	$user = $this->presenter->user;
	$aid = $id;
	$commentsp = FALSE;
	try {
	    $article = $this->getArticlesModel()->getArticle((int) $id, TRUE);

	    $this->getArticlesModel()->incArticleCounter($article);
	} catch (IOException $ex) {
	    $this->flashMessage('Článek se nepodařilo načíst');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	}

	$this->template->data = $article;

	switch ($article->comments_mode) {
	    case 'pbc':
		$commentsp = TRUE;
		break;
	    case 'off':
		$commentsp = FALSE;
		break;
	    case 'log':
		if ($user->isLoggedIn())
		    $commentsp = TRUE;
		break;
	    case 'gro':
		break;
	}
	$this->template->comments = $commentsp;
	//$this->template->loggedIn = $user->

	$this->template->id = $aid;
    }

    /**
     *
     */
    public function createComponentArticleComments($name) {
	$c = new CommentsControl($this, $name);
	$c->setModel($this->getArticlesModel());
	$c->setId(dump($this->getId()));
	$c->setType(self::C_ARTICLE_TYPE);
	$c->setUserId($this->getUserId());
	return $c;
    }

}

