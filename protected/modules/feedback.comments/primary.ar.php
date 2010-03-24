<?php

class feedback_comments_WdActiveRecord extends WdActiveRecord
{
	const NID = 'nid';
	const PARENTID = 'parentid';
	const UID = 'uid';
	const AUTHOR = 'author';
	const AUTHOR_EMAIL = 'author_email';
	const AUTHOR_URL = 'author_url';
	const CONTENTS = 'contents';
	const STATUS = 'status';
	const NOTIFY = 'notify';
	const CREATED = 'created';

	protected function model($name='feedback.comments')
	{
		return parent::model($name);
	}

	protected function __get_node()
	{
		return self::model('system.nodes')->load($this->nid);
	}

	protected function __get_url()
	{
		$node = $this->node;

		return ($node ? $this->node->url : 'unknown-node-' . $this->nid) . '#comment-' . $this->commentid;
	}

	protected function __get_absoluteUrl()
	{
		$node = $this->node;

		return ($node ? $this->node->absoluteUrl : 'unknown-node-' . $this->nid) . '#comment-' . $this->commentid;
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

	protected function __get_isAuthor()
	{
		return $this->node->uid == $this->uid;
	}

	public function excerpt($limit=55)
	{
		return wd_excerpt($this->contents, $limit);
	}
}