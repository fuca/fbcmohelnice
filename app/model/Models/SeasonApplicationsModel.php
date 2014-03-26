<?php

namespace florbalMohelnice\Models;
use \florbalMohelnice\Entities\SeasonApplication;

/**
 * Description of SeasonApplicationsModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class SeasonApplicationsModel extends BaseModel {

    /**
     *
     */
    public function getFluent($kid = NULL) {
	if ($kid == NULL)
	    throw new \Nette\InvalidArgumentException("Argument kid was null");
	if (!is_numeric($kid))
	    throw new \Nette\InvalidArgumentException("Argument kid has to be type of numeric");
	
	return $this->connection->select('*')
				->from('SeasonApplications')
				->innerJoin('EligibleSeasons')->using('(id_season)')
				->where('kid = %i', $kid);
    }
    
    /**
     * 
     * @param \florbalMohelnice\Entities\SeasonApplication $app
     * @return type
     * @throws \Nette\InvalidArgumentException
     * @throws \Nette\IOException
     */
    public function createApplication(SeasonApplication $app) {
	if ($app == NULL)
	    throw new \Nette\InvalidArgumentException('Argument app was null');
	try {
	    $this->connection->insert('SeasonApplications', $app)->execute();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
	return TRUE;
    }
    
    public function getApplications($kid, $season = NULL) {
	if ($kid == NULL)
	    throw new \Nette\InvalidArgumentException("Argument kid was null");
	if (!is_numeric($kid))
	    throw new \Nette\InvalidArgumentException("Argument kid has to be type of numeric");
	if ($season !== NULL && !is_numeric($season))
	    throw new \Nette\InvalidArgumentException("Argument season has to be type of numeric");
	
	try {
	    $res = $this->connection->select("SeasonApplications.*")
		    ->from("SeasonApplications")
		    ->join("EligibleSeasons")->using("(id_season)");
	    if ($season === NULL) {
		$res->where("kid = %i", $kid);
	    } else {
		$res->where("kid = %i AND id_season = %i", $kid, $season);
	    }
	    $res = $res->execute()->setRowClass("florbalMohelnice\Entities\SeasonApplication");
	    if ($season === NULL) {
		$res = $res->fetchAll();
	    } else {
		$res = $res->fetch();
		
	    }
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
	return $res;
    }
    
    /**
     * 
     * @param \florbalMohelnice\Entities\SeasonApplication $app
     * @throws \Nette\InvalidArgumentException
     * @throws \Nette\IOException
     */
    public function updateApplication(SeasonApplication $app) {
	if ($app == NULL)
	    throw new \Nette\InvalidArgumentException("Argument app was null");
	$idSeason = $app->offsetGet("id_season");
	$kid = $app->offsetGet("kid");
	$app->offsetUnset("id_season");
	$app->offsetUnset("kid");
	try {
	    $this->connection->update("SeasonApplications", $app)
		    ->where("kid = %i AND id_season = %i", $kid, $idSeason)
		    ->execute();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
    }
    
    /**
     * 
     * @param \florbalMohelnice\Models\Application $app
     * @throws \Nette\InvalidArgumentException
     * @throws \Nette\IOException
     */
    public function deleteApplication(Application $app) {
	if ($app == NULL)
	    throw new \Nette\InvalidArgumentException("Argument app was null");
	$idSeason = $app->offsetGet("id_season");
	$kid = $app->offsetGet("kid");
	try {
	    $this->connection->delete("SeasonApplications")
		    ->where("kid = %i AND id_season = %i", $kid, $idSeason)
		    ->execute();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
    }
}