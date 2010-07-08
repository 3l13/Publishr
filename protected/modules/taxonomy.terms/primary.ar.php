<?php

class taxonomy_terms_WdActiveRecord extends WdActiveRecord
{
	const VTID = 'vtid';
	const VID = 'vid';
	const TERM = 'term';
	const TERMSLUG = 'termslug';
	const WEIGHT = 'weight';

	protected function model($name='taxonomy.terms')
	{
		return parent::model($name);
	}

	public function __toString()
	{
		return $this->term;
	}
}