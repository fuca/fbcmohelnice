<?php

use Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 * @author Michal Fucik  <michal.fuca.fucik@gmail.com>
 */
class RouterFactory {

	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter() {
	    
		$router = new RouteList();
		
		$router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY);
		$router[] = new Route('index.html', 'Homepage:default', Route::ONE_WAY);
		
//		$router[] = new Route('archiv[/<abbr>]', 'Articles:default');
//		$router[] = new Route('archiv/clanek/<id>', 'Articles:showArticle');
//		
//		$router[] = new Route('info', 'ClubInfo:default');
//		$router[] = new Route('info/<abbr>', 'ClubInfo:showStatic');
//		$router[] = new Route('info/menu/<id>', 'ClubInfo:showMenuPage');
//		
//		$router[] = new Route('is/osobni/zpravy', 'User:messageBox');
//		$router[] = new Route('is/osobni/zpravy/prichozi', 'User:inbox');
//		$router[] = new Route('is/osobni/zpravy/odchozi', 'User:outbox');
//		$router[] = new Route('is/osobni/zpravy/smazane', 'User:deleted');
//		$router[] = new Route('is/osobni/zpravy/vytvorit', 'User:createMessage');
//		$router[] = new Route('is/osobni/udalosti', 'User:events');
//		$router[] = new Route('is/osobni/udalosti/<id>', 'User:showEvent');
//		$router[] = new Route('is/osobni/platby', 'User:payments');
//		$router[] = new Route('is/osobni/objednavky', 'User:orders');
//		$router[] = new Route('is/osobni/objednavky/vytvorit', 'User:newOrder');
//		$router[] = new Route('is/osobni/objednavky/<id>', 'User:showOrder');
//		$router[] = new Route('is/osobni/pokuty', 'User:microPayments');
//		$router[] = new Route('is/osobni/kredity', 'User:credit');
//		$router[] = new Route('is/osobni/data/zmenit', 'User:changeData');
//		$router[] = new Route('is/osobni/data/zmenit-heslo', 'User:changePassword');
//		$router[] = new Route('is/osobni/profil', 'User:profile');
//		$router[] = new Route('is/osobni/profil/upravit', 'User:editProfile');
//		$router[] = new Route('is/odhlasit', 'Auth:logOut');
//		$router[] = new Route('prihlasit', 'Auth:logIn');
//		$router[] = new Route('is/osobni[/<action>[/<id>]]', 'User:default');
//		
//		$router[] = new Route('is/klub/nastenky', 'Club:walls');
//		$router[] = new Route('is/klub/nastenky/prispevek/<id>', 'Club:showWallPost');
//		$router[] = new Route('is/klub/forum', 'Club:forum');
//		$router[] = new Route('is/klub/forum/<id_forum>', 'Club:forumThread');
//		$router[] = new Route('is/klub/adresar', 'Club:members');
//		$router[] = new Route('is/klub/kalendar', 'Club:calendar');
//		$router[] = new Route('is/klub/dochazka', 'Club:trainingParticipation');
//		$router[] = new Route('is/klub/vyhody-pro-cleny', 'Club:advantages');
//		$router[] = new Route('is/klub/dokumenty', 'Club:documents');
//		$router[] = new Route('is/klub[/<action>[/<id>]]','Club:default');
//		
//		$router[] = new Route('is/admin/sezona', 'Admin:season');
//		$router[] = new Route('is/admin/sezona/vytvorit', 'Admin:addSeason');
//		$router[] = new Route('is/admin/sezona/upravit/<id_season>', 'Admin:editSeason');
//		$router[] = new Route('is/admin/prirazeni-souboru', 'Admin:fileAssign');
//		$router[] = new Route('is/admin/pokuty', 'Admin:microPayments');
//		$router[] = new Route('is/admin/pokuty/vytvorit', 'Admin:addMicroPayment');
//		$router[] = new Route('is/admin/pokuty/upravit/<id_micropayment>', 'Admin:editMicroPayment');
//		$router[] = new Route('is/admin/platby', 'Admin:payments');
//		$router[] = new Route('is/admin/platby/vytvorit', 'Admin:addPayment');
//		$router[] = new Route('is/admin/platby/upravit/<id_payment>', 'Admin:editPayment');
//		$router[] = new Route('is/admin/kredity', 'Admin:credit');
//		$router[] = new Route('is/admin/kredity/vytvorit', 'Admin:addCredit');
//		$router[] = new Route('is/admin/kredity/nahled/<kid>', 'Admin:creditsDetail');
//		$router[] = new Route('is/admin/kredity/upravit/<id_credit>', 'Admin:editCredit');
//		$router[] = new Route('is/admin/akce', 'Admin:events');
//		$router[] = new Route('is/admin/akce/vytvorit', 'Admin:addEvent');
//		$router[] = new Route('is/admin/akce/upravit/<id_event>', 'Admin:editEvent');
//		$router[] = new Route('is/admin/dochazka', 'Admin:participation');
//		$router[] = new Route('is/admin/dochazka/upravit/<id_event>', 'Admin:editParticipation');
//		$router[] = new Route('is/admin/clanky', 'Admin:articles');
//		$router[] = new Route('is/admin/clanky/vytvorit', 'Admin:addArticle');
//		$router[] = new Route('is/admin/clanky/upravit/<id_article>', 'Admin:editArticle');
//		$router[] = new Route('is/admin/schvaleni', 'Admin:permit');
//		$router[] = new Route('is/admin/schvaleni/upravit/<id_profile>', 'Admin:editPermit');
//		$router[] = new Route('is/admin/objednavky', 'Admin:orders');
//		$router[] = new Route('is/admin/objednavky/vytvorit', 'Admin:addOrder');
//		$router[] = new Route('is/admin/objednavky/nahled/<id>', 'Admin:showOrder');
//		$router[] = new Route('is/admin/galerie', 'Admin:gallery');
//		$router[] = new Route('is/admin/uzivatele', 'Admin:users');
//		$router[] = new Route('is/admin/uzivatele/vytvorit', 'Admin:addUser');
//		$router[] = new Route('is/admin/uzivatele/upravit/<kid>', 'Admin:editUser');
//		$router[] = new Route('is/admin/uzivatele/nahled/<kid>', 'Admin:showUser');
//		$router[] = new Route('is/admin/uzivatele/prihlasky/<kid>', 'Admin:userApplications');
//		$router[] = new Route('is/admin/uzivatele/prihlasky/vytvorit/<kid>', 'Admin:addApplication');
//		$router[] = new Route('is/admin/uzivatele/prihlasky/upravit/<id_season>', 'Admin:editApplication');
//		$router[] = new Route('is/admin/nastenky', 'Admin:walls');
//		$router[] = new Route('is/admin/nastenky/prispevky/pridat', 'Admin:addWallPost');
//		$router[] = new Route('is/admin/nastenky/prispevky/upravit/<id_wallpost>', 'Admin:editWallPost');
//		$router[] = new Route('is/admin/fora', 'Admin:forums');
//		$router[] = new Route('is/admin/fora/vytvorit', 'Admin:addForum');
//		$router[] = new Route('is/admin/fora/upravit/<id_forum>', 'Admin:editForum');
//		$router[] = new Route('is/admin/fora/nahled/<id_forum>', 'Admin:showForum');
//		$router[] = new Route('is/admin/staticke-stranky', 'Admin:staticPages');
//		$router[] = new Route('is/admin/staticke-stranky/vytvorit', 'Admin:addStaticPage');
//		$router[] = new Route('is/admin/staticke-stranky/upravit/<id_page>', 'Admin:editStaticPage');
//		$router[] = new Route('is/admin[/<action>[/<id>]]', 'Admin:default');
		
		$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
		
		return $router;
	}

}
