<?php

namespace florbalMohelnice\Models;
use florbalMohelnice\Entities\Comment,
        florbalMohelnice\Entities\StaticPage;

/**
 * Description of StaticPagesModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class StaticPageModel extends BaseModel implements ICommentableModel {

    /**
     * Returns raw fluent of all static pages
     * @param type $identifier
     * @return type
     */
    public function getFluent($identifier = NULL) {
	$res = $this->connection->select("*")->from("StaticPages");
        return $res;
    }
    
    /**
     * Returns static page of passed id_page
     * @param type $id_page
     * @return type
     * @throws \Nette\InvalidArgumentException
     * @throws \Nette\IOException
     */
    public function getPageById($id_page) {
        if (!is_numeric($id_page))
            throw new \Nette\InvalidArgumentException("Argument id_page has to be type of numeric");
        try {
        $res = $this->connection->select("*")
                ->from("StaticPages")
                ->where("id_page = %i", (integer) $id_page)
                ->execute()->setRowClass("florbalMohelnice\Entities\StaticPage")
                ->fetch();
        } catch (DibiException $ex) {
            throw new \Nette\IOException($ex->getMessage(), $ex);
        }
	if ($res->children_count == -1)
	    $res->offsetSet("link", 1);
        return $res;
    }
    
    /**
     * Returns one single page acc to passed abbr or FALSE
     * @param type $abbr
     * @return florbalMohelnice\Entities\StaticPage
     * @throws \Nette\InvalidArgumentException
     * @throws \Nette\IOException
     */
    public function getPageByAbbr($abbr) {
        if (!is_string($abbr))
            throw new \Nette\InvalidArgumentException("Argument abbr has to be type of string");
        try {
        $res = $this->connection->select("*")
                ->from("StaticPages")
                ->where("abbr = %s", $abbr)
                ->execute()->setRowClass("florbalMohelnice\Entities\StaticPage")
                ->fetch();
        } catch (DibiException $ex) {
            throw new \Nette\IOException($ex->getMessage(), $ex);
        }
        return $res;
    }
    
    
    
    /**
     * Removes static page of given id from database
     * @param \florbalMohelnice\Entities\StaticPage $spa
     * @throws \Nette\IOException
     */
    public function deletePage($spaId) {
        if (!is_numeric($spaId) || $spaId == NULL)
	    throw new \Nette\InvalidArgumentException('Argument $spa cannot be null');
        try {
            $this->connection->delete("StaticPages")
                    ->where("id_page = %i", (integer) $spaId)
                    ->execute();
        } catch(DibiException $ex) {
            throw new \Nette\IOException($ex->getMessage(), $ex);
        }
    }
    
    /**
     * Persists static page into database
     * @param \florbalMohelnice\Entities\StaticPage $spa
     * @return type
     * @throws \Nette\IOException
     */
    public function createPage(StaticPage $spa) {
        if ($spa->offsetExists('id_page'))
            $spa->offsetUnset('id_page');
	if ($spa->offsetGet("link")) 
	    $spa->offsetSet ("children_count", -1);
	$spa->offsetUnset('link');
	// abbr preparation
	$lowerTitle = \Nette\Utils\Strings::webalize($spa->offsetGet('title'), NULL, TRUE);
	$parent = $spa->offsetGet("parent_page");
	$parentId = $parent === NULL ? 0 : $parent;
	$abbr = $parentId."-".\Nette\Utils\Strings::truncate($lowerTitle, 7)
		. "-" . \Nette\Utils\Strings::random(5);
	$spa->offsetSet('abbr', $abbr);
        try {
            $this->connection->insert('StaticPages', $spa)->execute();
            $spaId = $this->connection->insertId();
	    if ($parent !== NULL)
		$this->incChildrenCount($parent);
        } catch (DibiException $ex) {
            throw new \Nette\IOException($ex->getMessage(), $ex);
	} catch (\Nette\InvalidArgumentException $ex) {
	    throw new \Nette\IOException($ex->getMessage(), $ex);
	}
        return $spaId;
    }
    
    protected function incChildrenCount($id) {
	return $this->changeChildrenCount($id, function($count){return ++$count;});
    }
    
    protected function decChildrenCount($id) {
	return $this->changeChildrenCount($id, function($count){return --$count;});
    }
    
    protected function changeChildrenCount($id, \Closure $fn) {
	if(!is_numeric($id))
	    throw new \Nette\InvalidArgumentException("Argument id has to be type of numeric");
	
	try {
	    $count = $this->connection->select('children_count')
		    ->from("StaticPages")->where("id_page = %i", $id)
		    ->execute()->fetchSingle();
	    $this->connection->update("StaticPages", array("children_count"=> $fn($count)))
		    ->where("id_page = %i", $id)
		    ->execute();
	} catch(DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage(), $ex);
	}
	return $count;
    }


    /**
     * Update existing StaticPage within database
     * @param \florbalMohelnice\Entities\StaticPage $spa
     * @throws \Nette\IOException
     */
    public function updatePage(StaticPage $spa) {
        $spaId = $spa->offsetGet("id_page");
        $spa->offsetUnset("id_page");
	$newParent = $spa->offsetGet("parent_page");
        if ($spa->offsetGet("link"))
	    $spa->offsetSet ("children_count", -1);
	$spa->offsetUnset('link');
        try {
	    $oldParent = $this->connection->select("parent_page")
		    ->from("StaticPages")->where("id_page = %i", $spaId)
		    ->execute()->fetchSingle();
	    
	    if ($oldParent !== NULL && $oldParent != $newParent)
		$this->decChildrenCount($oldParent);
	    
	    if ($newParent !== NULL)
		$this->incChildrenCount($newParent);
	    
            $this->connection->update('StaticPages', $spa)
                    ->where("id_page = %i", $spaId)
                    ->execute();
        } catch (DibiException $ex) {
            throw new \Nette\IOException($ex->getMessage(), $ex);
        }
        return $spa;
    }
    
    /**
     * Returns select list array feed of Static pages stored in database
     * @return type
     * @throws \Nette\IOException
     */
    public function getSelectPages($without = NULL, $withoutLinks = TRUE) {
	if ($without != NULL && !is_numeric($without))
	    throw new \Nette\InvalidArgumentException("Argument \$without has to be type of numeric");
	    
	try {
	    $result = $this->connection->select('id_page,CONCAT(StaticPages.title, \' \',\'(\',id_page,\')\') as title')
		    ->from('StaticPages');
	    if ($without != NULL)
		$result->where('id_page != %i', $without);
	    if ($withoutLinks)
		$result->where('children_count != %i', -1);
	    $result = $result->execute()->fetchPairs();
	} catch(DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage, $ex);
	}
	return $result;
    }
    
    
    // ----------------- ICommentableModel -------------------

    /**
     * Creates comment for implementing entity
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
     * Updates comment for implementing entity
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
     * Deletes comment for implementing entity - NOT IMPLEMENTED YET
     */
    public function deleteComment(Comment $c) {
	//
    }
    
    public function getCommentsFluent($id) {
        $res = $this->connection->select('
					Comments.*, 
					CONCAT(surname, \' \', Users.name, \' (\', kid, \')\') AS author')
		    ->from('Comments')
		    ->leftJoin('Users')->using('(kid)')
		    ->where('relate_post = %i AND relation_mode = %s', (integer) $id, \BasePresenter::C_STATIC_TYPE);
    }
}