<?php

namespace florbalMohelnice\Models;

use florbalMohelnice\Entities\WallPost,
    florbalMohelnice\Entities\Article,
    florbalMohelnice\Entities\Event,
    florbalMohelnice\Entities\User;

/**
 * Description of GroupsModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class GroupsModel extends BaseModel {

    /**
     *
     */
    public function getGroups() {
	try {
	    return $this->getFluent()->execute()->fetchAll();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
    }

    /**
     *
     */
    public function getFluent() {
	try {
	    return $this->connection->select('*')->from('Groups');
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
    }

    /**
     *
     */
    public function getPostsGroups(WallPost $wp) {
	try {
	    $res = $this->connection->select('id_wallpost, id_group')
			    ->from('relWallpostGroup')
			    ->where('id_wallpost = %i', $wp->offsetGet('id_wallpost'))
			    ->execute()->fetchAssoc('id_group');
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
	return array_keys($res);
    }

    /**
     * Returns array of users which belongs into group specified by passed $id
     * @param type $id
     * @throws \Nette\InvalidArgumentException
     */
    public function getSelectGroupUsers($id) {
	if (!is_numeric($id))
	    throw new \Nette\InvalidArgumentException("Argument has to be a type of numeric");
	$result = $this->connection
			->select('[kid], CONCAT(surname, \' \', name, \' (\', kid, \')\') AS author')
			->from('[Users]')->join('Positions')->using('(kid)')
			->where('id_group = %i', $id)
			->orderBy('surname')
			->execute()->fetchPairs();
	return $result;
    }

    /**
     *
     */
    public function getArticleGroups(Article $art) {
	try {
	    //$res = $this->connection->select('id_article, id_group')
	    $res = $this->connection->select('id_article, id_group')
			    ->from('relArticleGroup')
			    ->where('id_article = %i', $art->offsetGet('id_article'))
			    ->execute()->fetchAssoc('id_group');
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
	return array_keys($res);
    }

    /**
     * Returns array of groups which passed Event belongs to
     * @param \florbalMohelnice\Entities\Event $ev
     * @return type
     * @throws IOException
     */
    public function getEventGroups(Event $ev) {
	try {
	    $res = $this->connection->select('id_event, id_group')
			    ->from('relEventGroup')
			    ->where('id_event = %i', $ev->offsetGet('id_event'))
			    ->execute()->fetchAssoc('id_group');
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
	return array_keys($res);
    }

    /**
     * Returns <id_group, group name> array groups which passed User belongs into
     * @param \florbalMohelnice\Models\User $u
     * @return type
     * @throws IOException
     */
    public function getUserGroups(User $u) {
	try {
	    $res = $this->connection->select('id_group, Groups.name')
			    ->from('Positions')
			    ->join('Groups')->using('(id_group)')
			    ->where('kid = %i', $u->offsetGet('kid'))
			    ->execute()->fetchPairs();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
	return $res;
    }

    public function getForumGroups(\florbalMohelnice\Entities\Forum $u) {
	try {
	    $res = $this->connection->select('id_group, Groups.name')
			    ->from('relForumGroup')
			    ->join('Groups')->using('(id_group)')
			    ->where('id_forum = %i', $u->offsetGet('id_forum'))
			    ->execute()->fetchPairs();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
	return $res;
    }

    /**
     * Returns array of available groups for use within select list
     * @param type $root If TRUE is passed, root group (FBC Mohelnice) will be included
     * @return type
     * @throws \Nette\IOException
     */
    public function getSelectGroups($root = FALSE) {
	try {
	    $result = $this->getFluent()->removeClause('select')
		    ->select('id_group,name');
	    if (!$root)
		$result->where('id_group != %i', 1);

	    $result = $result->execute()->fetchPairs();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
	return $result;
    }

    /**
     *
     */
    public function removePostsGroups(WallPost $wp) {
	try {
	    $this->connection->delete('relWallpostGroup')
		    ->where('id_wallpost = %i', $wp->offsetGet('id_wallpost'))
		    ->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
    }

    /**
     *
     */
    public function removeArticleGroups(Article $art) {
	try {
	    $this->connection->delete('relArticleGroup')
		    ->where('id_article = %i', $art->offsetGet('id_article'))
		    ->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
    }

    /**
     * Removes all entries from relForumGroup table specified by Forum's id
     * @param \florbalMohelnice\Entities\Forum $forum
     * @throws IOException
     */
    public function removeForumGroups(\florbalMohelnice\Entities\Forum $forum) {
	try {
	    $this->connection->delete('relForumGroup')
		    ->where('id_forum = %i', $forum->offsetGet('id_forum'))
		    ->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
    }

    /**
     * @param \florbalMohelnice\Entities\Article $art
     * @throws \Nette\IOException
     */
    public function addArticleGroups(Article $art) {
	$groups = $art->offsetGet('categories');
	$idWp = $art->offsetGet('id_article');
	try {
	    foreach ($groups as $g) {
		$data = array('id_article' => $idWp, 'id_group' => $g);
		$this->connection->insert('relArticleGroup', $data)
			->execute();
		if ($g == 1)
		    break;
	    }
	} catch (\Nette\DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage(), $ex);
	}
    }

    /**
     * @param \florbalMohelnice\Entities\User $u
     * @throws \Nette\IOException
     */
    public function addUserGroups(User $u) {
	$categories = $u->offsetGet('categories');
	try {
	    foreach ($categories as $cat) {
		$data = array('kid' => $u->offsetGet('kid'), 'id_group' => $cat);
		$this->connection
			->insert('Positions', $data)
			->execute();
		if ($cat == 1)
		    break;
	    }
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage(), $ex);
	}
    }

    public function addForumGroups(\florbalMohelnice\Entities\Forum $f) {
	$categories = $f->offsetGet('categories');
	try {
	    foreach ($categories as $cat) {
		$data = array('id_forum' => $f->offsetGet('id_forum'), 'id_group' => $cat);
		$this->connection
			->insert('relForumGroup', $data)
			->execute();
		if ($cat == 1)
		    break;
	    }
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage(), $ex);
	}
    }

    /**
     * @throws DibiException
     */
    public function addWallPostsGroups(WallPost $wp) {
	$groups = $wp->offsetGet('categories');
	$idWp = $wp->offsetGet('id_wallpost');
	try {
	    foreach ($groups as $g) {
		$data = array('id_wallpost' => $idWp, 'id_group' => $g);
		$this->connection->insert('relWallpostGroup', $data)
			->execute();
	    }
	} catch (\Nette\DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage(), $ex);
	}
    }

    /**
     * @throws DibiException
     */
    public function addEventGroups(Event $ev) {
	$groups = $ev->offsetGet('categories');
	$idEv = $ev->offsetGet('id_event');
	try {
	    foreach ($groups as $g) {
		$data = array('id_event' => $idEv, 'id_group' => $g);
		$this->connection->insert('relEventGroup', $data)
			->execute();
	    }
	} catch (\Nette\DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage(), $ex);
	}
    }

    /**
     *
     */
    public function removeEventGroups(Event $ev) {
	try {
	    $this->connection->delete('relEventGroup')
		    ->where('id_event = %i', $ev->offsetGet('id_event'))
		    ->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
    }

    /**
     * Removes all records from Positions table having kid passed within User parameter
     * @param \florbalMohelnice\Models\User $u
     * @throws IOException
     */
    public function removeUserGroups(User $u) {
	try {
	    $this->connection->delete('Positions')
		    ->where('kid = %i', $u->offsetGet('kid'))
		    ->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
    }

    public function getUsersHomeGroupId(User $usr, $fluent = FALSE, $column = \florbalMohelnice\Entities\Group::COLUMN_ID) {
        switch($column) {
            case \florbalMohelnice\Entities\Group::COLUMN_ID:    break;
            case \florbalMohelnice\Entities\Group::COLUMN_ABBR:        break;
            default: throw new \Nette\InvalidArgumentException("Illegal column name passed > $column");
        }
        
	$kid = $usr->offsetGet('kid');
	try {
	    $res = $this->connection->select($column)
		    ->from('Positions')
		    ->join('Groups')->using('(id_group)')
		    ->where('kid = %i AND priority = %i', $kid, $this->connection->select('MAX(priority)')
			->from('Positions')->join('Groups')->using('(id_group)')->where('kid = %i', $kid)->execute());
	    if (!$fluent) {
		$res = $res->execute()->fetchSingle();
            }
	} catch (DibiException $ex) {
	    throw new \Nette\Exception($ex->getMessage(), $ex->getCode(), $ex);
	}
	return $res;
    }

    public function getUsersHomeGroupCreditTax(User $usr, $idSeason) {
	$kid = $usr->offsetGet('kid');
	try {
	    $res = $this->connection->select('credit')
			    ->from('SeasonTaxes')
			    ->join('Groups')->using('(id_group)')
			    ->join('Positions')->using('(id_group)')
			    ->where('id_season = %i AND id_group = %i AND kid = %i', $idSeason, $this->getUsersHomeGroupId($usr), $kid)
			    ->execute()->fetchSingle();
	} catch (DibiException $ex) {
	    throw new Exception($ex->getMessage(), $ex->getCode(), $ex);
	}
	return $res;
    }

}