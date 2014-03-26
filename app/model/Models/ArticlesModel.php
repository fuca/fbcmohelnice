<?php

namespace florbalMohelnice\Models;

use florbalMohelnice\Entities\Article,
    florbalMohelnice\Models\ICommentableModel,
    florbalMohelnice\Entities\Comment;

/**
 * Description of articles Model
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class ArticlesModel extends BaseModel implements ICommentableModel {

    /**
     *
     */
    public function getFluent() {
	return $this->connection
			->select('*')
			->from('Articles');
    }

    /**
     * 
     */
    public function getArticlesWithinGroup($abbr = NULL) {
	$res = $this->connection->select('*')
			->from('Articles')
			->innerJoin('relArticleGroup')->using('(id_article)')
			->innerJoin('Groups')->using('(id_group)');
	if ($abbr != NULL)
	    if ($abbr != 'fbc')	
		$res = $res->where('abbr = %s', $abbr);
	
	return $res->execute()->fetchAll();
    }

    /**
     *
     */
    public function getArticle($id) {
	if (!is_numeric($id))
	    throw new \Nette\InvalidArgumentException('Argument has to be type of numeric');
	$aMod = new GroupsModel($this->connection);
	try {
	    $res = $this->connection->select('*')
			    ->from('Articles')
			    ->where('id_article = %i', $id)
			    ->execute()
			    ->setRowClass('florbalMohelnice\Entities\Article')->fetch();
	    if ($res != FALSE) {
		$res->offsetSet('categories', $aMod->getArticleGroups($res));
	    }
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
	$res->offsetSet("groups", $this->getArticleGroups($id));
	return $res;
    }

    /**
     *
     */
    public function updateArticle(Article $art) {
	$grps = $art->offsetGet('categories');
	$art->offsetUnset('categories');
	$aId = $art->offsetGet('id_article');
	$art->offsetUnset('id_article');
	$aMod = new GroupsModel($this->connection);

	try {

	    $this->connection->update('Articles', $art)
		    ->where('id_article = %i', $aId)
		    ->execute();

	    $art->offsetSet('id_article', $aId);
	    $art->offsetSet('categories', $grps);
	    $aMod->removeArticleGroups($art);
	    $aMod->addArticleGroups($art);
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
    }

    /**
     *
     */
    public function deleteArticle($id) {
	if (!is_numeric($id))
	    throw new \Nette\InvalidArgumentException('Argument has to be type of numeric');
	try {
	    $this->connection->delete('Articles')
		    ->where('id_article = %i', $id)
		    ->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
    }

    /**
     *
     */
    public function createArticle(Article $art) {
	$grps = $art->offsetGet('categories');
	$art->offsetUnset('categories');
	$aMod = new GroupsModel($this->connection);
	try {
	    $this->connection->insert('Articles', $art)->execute();
	    $aId = $this->connection->insertId();
	    $art->offsetSet('id_article', $aId);
	    $art->offsetSet('categories', $grps);
	    $aMod->addArticleGroups($art);
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
        return $aId;
    }

    /**
     *
     */
    public function incArticleCounter(Article $art) {
	$aId = $art->offsetGet('id_article');
	try {
	    $counter = $this->connection->select('counter')
			    ->from('Articles')
			    ->where('id_article = %i', $aId)
			    ->execute()->fetchSingle();
	    $this->connection
		    ->update('Articles', array('counter' => ((integer) $counter) + 1))
		    ->where('id_article = %i', $aId)
		    ->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage);
	}
    }
    
    /**
     * @param type $id
     * @return array group_abbr, group_name
     * @throws \Nette\InvalidArgumentException
     * @throws \Nette\IOException
     */
    public function getArticleGroups($id) {
	if (!is_numeric($id))
	    throw new \Nette\InvalidArgumentException("Argument has to be a type of numeric, $id given");
	try {
	    $res = $this->connection->select('abbr,Groups.name')
			    ->from('relArticleGroup')
			    ->innerJoin('Groups')
			    ->using('(id_group)')
			    ->where('id_article = %i', $id)
			    ->execute()->fetchPairs();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex);
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
		    ->leftJoin('Users')->using('(kid)')
		    ->where('relate_post = %i AND relation_mode = %s', (integer) $id, \BasePresenter::C_ARTICLE_TYPE);
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	    return;
	}
	return $res;
    }

}
