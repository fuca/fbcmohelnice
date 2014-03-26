<?php

namespace florbalMohelnice\Models;

use florbalMohelnice\Entities\WallPost,
    florbalMohelnice\Models\GroupsModel,
    florbalMohelnice\Entities\Comment;

/**
 * Description of WallsModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class WallsModel extends BaseModel implements ICommentableModel {

    /**
     *
     */
    public function getFluent($abbr = NULL) {

	$res = $this->connection->select('
							WallPosts.*, 
                                                        CONCAT(show_from,\' \',show_to) AS short_show,
							id_group,
							Groups.abbr, 
							Groups.name AS group_name,
							kid AS author_kid,
							CONCAT(surname, \' \', Users.name, \' (\', kid, \')\') AS author')
			->from('WallPosts')
			->leftJoin('relWallpostGroup')->using('(id_wallpost)')
                        ->leftJoin('Groups')->using('(id_group)')
			->leftJoin('Users')->on('posted_kid = kid');
			
	return $abbr != NULL && $abbr != 'fbc' ? $res->where('abbr = %s', $abbr) : $res;
    }

    public function countFromFluent(\DibiFluent $fluent) {
	try {
	    $fluent->removeClause('select');
	    $fluent->select(count('*'));
	    return $fluent->execute()->fetchSingle();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex);
	}
    }

    /**
     *
     */
    public function getAdminGridPosts($kid = FALSE) {
	if (!is_numeric($kid) && $kid)
	    throw new \Nette\InvalidArgumentException('Argument has to be type of numeric');
	$inner = $this->getFluent()->groupBy('id_wallpost');
	if ($kid)
	    $inner->where('kid = %i', $kid);
	return $this->connection->select('*')->from($inner)->as('innerOne');
    }

    /**
     * Returns wallpost associated with given id
     * @param numeric
     */
    public function getWallPost($id) {
	$gModel = new GroupsModel($this->connection);
	try {
	    $res = $this->getFluent()
			    ->where('id_wallpost = %i', $id)
			    ->execute()->setRowClass('florbalMohelnice\Entities\WallPost')->fetch();
	    if ($res != FALSE)
		$res->offsetSet('categories', $gModel->getPostsGroups($res));
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
	return $res;
    }

    /**
     *
     */
    public function updateWallPost(WallPost $wp) {
	$cats = $wp->offsetGet('categories');
	$wp->offsetUnset('categories');
	$gModel = new GroupsModel($this->connection);

	try {
	    $this->connection->begin();
	    $gModel->removePostsGroups($wp);
	    $id = $wp->offsetGet('id_wallpost');
	    $wp->offsetUnset('id_wallpost');
	    $this->connection->update('WallPosts', $wp->toArray())
		    ->where('id_wallpost = %i', $id)
		    ->execute();
	    $wp->offsetSet('id_wallpost', $id);
	    $wp->offsetSet('categories', $cats);
	    $gModel->addWallPostsGroups($wp);
	    $this->connection->commit();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
    }

    /**
     *
     */
    public function createWallPost(WallPost $wp) {
	$gModel = new GroupsModel($this->connection);
	try {
	    $cats = $wp->offsetGet('categories');
	    $wp->offsetUnset('categories');
	    $this->connection->insert('WallPosts', $wp)->execute();
	    $idWp = $this->connection->getInsertId();
	    $wp->offsetSet('categories', $cats);
	    $wp->offsetSet('id_wallpost', $idWp);
	    $gModel->addWallPostsGroups($wp);
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage);
	}
	return $idWp;
    }

    /**
     *
     */
    public function deletePost(WallPost $wp) {
	$gpM = new GroupsModel($this->connection);
	try {
	    $this->connection->delete('WallPosts')
		    ->where('id_wallpost = %i', $wp->getId())
		    ->execute();
	    $gpM->removePostsGroups($wp);
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
    }

    // ----------------- ICommentableModel -------------------

    /**
     *
     */
    public function createComment(Comment $c) {
	$com = $c;
	$com->offsetUnset('id_comment');
	try {
	    $this->connection->insert('Comments', $c)->execute();
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
		    ->where('relate_post = %i AND relation_mode = %s', (integer) $id, \BasePresenter::C_WALLPOST_TYPE);
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	    return;
	}
	return $res;
    }

}
