<?php

class taxonomy_vocabulary_WdActiveRecord extends WdActiveRecord
{
	const VID = 'vid';
	const SITEID = 'siteid';
	const VOCABULARY = 'vocabulary';
	const VOCABULARYSLUG = 'vocabularyslug';
	const IS_TAGS = 'is_tags';
	const IS_MULTIPLE = 'is_multiple';
	const IS_REQUIRED = 'is_required';
	const SCOPE = 'scope';
	const WEIGHT = 'weight';

	public $vid;
	public $siteid;
	public $vocabulary;
	public $vocabularyslug;
	public $is_tags;
	public $is_multiple;
	public $is_required;
	public $scope;
	public $weight;
}