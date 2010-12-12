<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_comments_WdActiveRecord extends WdActiveRecord
{
	const COMMENTID = 'commentid';
	const NID = 'nid';
	const PARENTID = 'parentid';
	const UID = 'uid';
	const AUTHOR = 'author';
	const AUTHOR_EMAIL = 'author_email';
	const AUTHOR_URL = 'author_url';
	const AUTHOR_IP = 'author_ip';
	const CONTENTS = 'contents';
	const STATUS = 'status';
	const NOTIFY = 'notify';
	const CREATED = 'created';

	public $commentid;
	public $nid;
	public $parentid;
	public $uid;
	public $author;
	public $author_email;
	public $author_url;
	public $author_ip;
	public $contents;
	public $status;
	public $notify;
	public $created;

	protected function model($name='feedback.comments')
	{
		return parent::model($name);
	}

	protected function __get_node()
	{
		global $core;

		return $core->models['system.nodes'][$this->nid];
	}

	protected function __get_url()
	{
		$node = $this->node;

		return ($node ? $this->node->url : '#unknown-node-' . $this->nid) . '#comment-' . $this->commentid;
	}

	protected function __get_absolute_url()
	{
		$node = $this->node;

		return ($node ? $this->node->absolute_url : '#unknown-node-' . $this->nid) . '#comment-' . $this->commentid;
	}

	protected function __get_author_icon()
	{
		$hash = md5(strtolower(trim($this->author_email)));

		return 'http://www.gravatar.com/avatar/' . $hash . '.jpg?' . http_build_query
		(
			array
			(
				'd' => 'identicon'
			)
		);
	}

	protected function __get_excerpt()
	{
		return $this->excerpt();
	}

	protected function __get_is_author()
	{
		return $this->node->uid == $this->uid;
	}

	public function excerpt($limit=55)
	{
		return wd_excerpt((string) $this, $limit);
	}

	public function __toString()
	{
		$str = Textmark_Parser::parse($this->contents);

		return WdKses::sanitizeComment($str);
	}
}