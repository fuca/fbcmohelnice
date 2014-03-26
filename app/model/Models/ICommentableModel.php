<?php
namespace florbalMohelnice\Models;

use florbalMohelnice\Entities\Comment;
/**
 * Models interface for sending comments
 */
interface ICommentableModel {

	/**
	 *
	 */
	public function createComment(Comment $c);
	
	/**
	 *
	 */
	public function updateComment(Comment $c);
	
	/**
	 *
	 */
	public function deleteComment(Comment $c);
	
	/**
	 *
	 */
	public function getCommentsFluent($id);
}

