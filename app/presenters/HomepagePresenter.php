<?php

use Nette\Diagnostics\Debugger;

/**
 * Homepage presenter.
 *
 * @author  Michal Fučík
 * @package fbcmoh
 */
class HomepagePresenter extends BasePresenter {
    
    public function startup() {
	parent::startup();
	$this->setLayoutStyle("homepage");
    }

    public function beforeRender() {
	parent::beforeRender();
	$this->template->layoutStyle = $this->getLayoutStyle();
    }
    public function renderDefault() {

	try {
	    $arts = $this->getArticlesModel()
			    ->getFluent()->where('highlight = 1')
			    ->orderBy('updated_time')->desc()->limit(6)
			    ->execute()->fetchAll();
	} catch (DibiException $ex) {
	    $this->flashMessage('Vyskytly se neočekávané potíže', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    return;
	}
	
	if ($arts) {
	    foreach ($arts as $a) {
		try {
		    $groups = $this->getArticlesModel()
			->getArticleGroups($a->id_article);
		    $a->offsetSet("categories", $groups);
		} catch (\Nette\IOException $ex) {
		    Debugger::log($ex,  Debugger::ERROR);
		    $this->flashMessage("Kategorie nezjištěny","error");
		} catch(Nette\InvalidArgumentException $ex) {
		    Debugger::log($ex,  Debugger::ERROR);
		    $this->flashMessage("Chyba argumentu","error");
		}
	    
	    }
	    $this->template->articles = $arts;
	} else
	    $this->flashMessage('Vyskytly se neočekávané potíže', 'error');
    }

    public function renderClub() {
	$this->template->message = 'A tady zase nejaky klubovy kecy.';
    }

    public function renderMedia() {
	$this->template->message = 'Jo na facebooku jsme taky.';
    }

    public function renderContact() {
	$this->template->message = 'Muzete nam i napsat.';
    }

    /**
     * Static page which describes copyright rules.
     */
    public function renderCopyright() {

	$res = $this->getStaticPage('copyright');
	$this->template->title = $res['title'];
	$this->template->content = $res['content'];
    }

    /**
     * Render public member profile
     * @param Standard string 
     */
    public function renderMemberProfile($string) {
	$ids = explode('-', $string);
	$kid = $ids[0];
	try {
	    $userData = $this->getUserModel()->getUserByKid((int) $kid);
	    $profileData = $this->getUserModel()->getWebProfilesFluent((int) $kid)->execute()->fetch();
	} catch (Exception $ex) {
	    $this->flashMessage('Omlouváme se, ale požadovaná data nelze získat. Zkuste to prosím znovu nebo později.', 'error');
	    Debugger::log($ex->getMessage(), Debugger::ERROR);
	    $this->redirect('Homepage:default');
	}

	dump("PICTURE");

	$this->template->profile_req = $userData->profile_required;


	$this->template->publicData = array('name' => $userData->name,
	    'surname' => $userData->surname,
	    'year' => $userData->year,
	    'nick' => $userData->nick,
	    'signature' => $userData->signature,
	    'city' => $userData->city);
	$profileData->offsetUnset('kid');
	$profileData->offsetUnset('city');
	$profileData->offsetUnset('job');
	$profileData->offsetUnset('last_updated');
	$profileData->offsetUnset('contact');
	$this->template->profileData = $profileData;

	// TODO rights 0 1 2
	$this->template->levelOneData = array('job' => $userData->job,
	    'phone' => $userData->phone);

	// TODO rights 3 4
	$this->template->levelTwoData = array('address' => $userData->address,
	    'postalCode' => $userData->postal_code,
	    'contName' => $userData->contperson_name,
	    'contPhone' => $userData->contperson_phone,
	    'contEmail' => $userData->contperson_email);
    }

    public function renderSitemap() {
	// vypsat data ze SitemapPresenter:show, ci tak neco
    }

    public function renderAccessibility() {

	// prohlaseni o pristupnosti
	$res = $this->getStaticPage('accessibility');
	$this->template->title = $res['title'];
	$this->template->content = $res['content'];
    }

    public function actionShowArticle($id) {
	try {
	    $article = $this->getArticlesModel()->getArticle($id);
	} catch (\Nette\IOException $ex) {
	    Debugger::log($ex, Debugger::ERROR);
	    $this->flashMessage("Omlouváme se, článek se nepodařilo načíst", "error");
	} catch (Nette\InvalidArgumentException $ex) {
	    Debugger::log($ex, Debugger::DETECT);
	    $this->flashMessage("Chyba v argumentu", "error");
	}
	$this->template->article = $article;
    }
}
