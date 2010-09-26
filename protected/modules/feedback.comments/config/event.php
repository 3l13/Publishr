<?php

return array
(
	'operation.save:before' => array('feedback_comments_WdEvents', 'before_operation_save'),
	'operation.delete' => array('feedback_comments_WdEvents', 'operation_delete'),
	'alter.block.edit' => array('feedback_comments_WdEvents', 'alter_block_edit')
);