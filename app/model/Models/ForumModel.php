<?php

namespace florbalMohelnice\Models;

use florbalMohelnice\Entities\ForumCategory,
    florbalMohelnice\Entities\Comment,
    florbalMohelnice\Entities\Forum;

/**
 * Description of ForumModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class ForumModel extends BaseModel implements ICommentableModel {

    /**
     *
     */
    public function getAdminFluent() {
	$res = $this->connection
			->select('
							Forum.*')
			->from('Forum')
			->join('Users')->on('kid = update_kid');
	return $res;
    }

    /**
     *
     */
    public function getFluent() {
	$res = $this->connection
			->select('
								Forum.*,
								CONCAT(surname, \' \', Users.name, \' (\', kid, \')\') AS author')
			->from('Forum')->join('Users')->on('kid = update_kid');

	return $res;
    }

    /**
     * Fetches ResultSet of all existing forums
     * @param integer identifier of parent forum
     * @return DibiResultSet
     */
    public function getAll($parent = NULL) {
	$res = $this->getFluent();
	if ($parent != NULL && is_numeric($parent))
	    $res = $res->where('parent_forum = %i', $parent);

	return $res->execute()
			->setRowClass('florbalMohelnice\Entities\Forum')
			->fetchAll();
    }
    
    public function getForumsWithinGroup($abbr = NULL) {
	$res = $this->connection->select('Forum.*')->setFlag('distinct')
		->from('Forum')
		->leftJoin('relForumGroup')->using('(id_forum)')
		->leftJoin('Groups')->using('(id_group)');
		// foreach arrayAbbrs
	if ($abbr !== NULL && $abbr != 'fbc') {
//	    $count = count($arrayAbbrs);
//	    if ($count != 0) {
//		for($i = 0; $i < $count; $i++) {
//		    $res->where('abbr LIKE %s', $arrayAbbrs[$i]);
//		    if ($i != $count){}
//
//		}
//	    }
	    $res->where('abbr LIKE %s', $abbr);
	}
	return $res->execute()->fetchAll();
    }

    /**
     * Creates new Forum record within database
     * @param \florbalMohelnice\Entities\Forum $f
     * @return type
     * @throws IOException
     */
    public function createForum(Forum $f) {
	if ($f->offsetGet('parent_forum') == 0)
	    $f->offsetSet('parent_forum', 1);
	$groupsModel = new GroupsModel($this->connection);
	$cats = $f->offsetGet('categories');
	$f->offsetUnset('categories');
	try {
	    $this->connection->insert('Forum', $f)->execute();
	    $fId = $this->connection->insertId();
	    $f->offsetSet('id_forum', $fId);
	    $f->offsetSet('categories', $cats);
	    $groupsModel->addForumGroups($f);
	} catch (IOException $ex) {
	    throw new IOException($ex->getMessage());
	}
	return $fId;
    }

    /**
     * Updates state of passed Forum entity within database
     * @param \florbalMohelnice\Entities\Forum $f
     * @throws IOException
     */
    public function updateForum(Forum $f) {
	$id = $f->offsetGet('id_forum');
	$groupsModel = new GroupsModel($this->connection);
	try {
	    $groupsModel->removeForumGroups($f);
	    $groupsModel->addForumGroups($f);
	    $f->offsetUnset('categories');
	    $f->offsetUnset('id_forum');
	    $this->connection->update('Forum', $f)
		    ->where('id_forum = %i', $id)
		    ->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
    }

    /**
     * Returns Forum entity specified by passed id
     * @param type $id
     * @return type
     * @throws \Nette\InvalidArgumentException
     * @throws IOException
     */
    public function getForum($id) {
	if (!is_numeric($id))
	    throw new \Nette\InvalidArgumentException('Argument id has to be type of numeric');
	$groupsModel = new GroupsModel($this->connection);
	try {
	    $r = $this->getFluent()->where('id_forum = %i', $id)
		    ->execute()->setRowClass('florbalMohelnice\Entities\Forum')
		    ->fetch();
	    if ($r)
		$r->offsetSet('categories', $groupsModel->getForumGroups($r));
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
	return $r;
    }

    /**
     *
     */
    public function removeForum($id) {
	if (!is_numeric($id))
	    throw new \Nette\InvalidArgumentException('Argument $id has to be a type of numeric');
	try {
	    $res = $this->connection->delete('Forum')
		    ->where('id_forum = %i', $id)
		    ->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
	return $res;
    }

    /**
     *
     */
    public function getSelectForums() {
	try {
	    $res = $this->connection->select('id_forum,title')->from('Forum')->execute()->fetchPairs();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
	return $res;
    }

    // ----------------- ICommentableModel -------------------

    /**
     *
     */
    public function createComment(Comment $c) {
	$com = $c;
	$com->offsetUnset('id_comment');
	$c_date = $c->offsetGet('updated_time');
	$c_rel = $c->offsetGet('relate_post');
	$c_kid = $c->offsetGet('kid');
	try {
	    $this->connection->insert('Comments', $c)->execute();
	    $this->connection->update('Forum', array('update_time' => $c_date,
			'update_kid' => $c_kid))
		    ->where('id_forum = %i', $c_rel)
		    ->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	    return;
	}
    }

    /**
     *
     */
    public function updateComment(Comment $c) {
	$c_type = $c->offsetGet('relation_mode');
	$c->offsetUnset('relation_mode');
	$c_id = $c->offsetGet('relate_post');
	$c->offsetUnset('relate_post');
	try {
	    $this->connection
		    ->update('Comments', $c)
		    ->where('id_comment = %i', $c_id)
		    ->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	    return;
	}
    }

    /**
     *
     */
    public function deleteComment(Comment $c) {
	//
    }

    /**
     *
     */
    public function getCommentsFluent($id) {
	try {
	    $res = $this->connection->select('
									Comments.*, 
									CONCAT(surname, \' \', Users.name, \' (\', kid, \')\') AS author')
		    ->from('Comments')
		    ->join('Users')->using('(kid)')
		    ->where('relate_post = %i AND relation_mode = %s', (integer) $id, \BasePresenter::C_FORUM_TYPE);
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	    return;
	}
	return $res;
    }

}
