<?php

class WdCloudElement extends WdElement
{
	const T_LEVELS = '#cloud-levels';

	protected function getInnerHTML()
	{
		$options = $this->getTag(self::T_OPTIONS);

		if (!$options)
		{
			return;
		}

		$min = min($options);
    	$max = max($options);

    	$range = ($min == $max) ? 1 : $max - $min;
    	$levels = $this->getTag(self::T_LEVELS, 8);

		$markup = $this->type == 'ul' ? 'li' : 'span';

		$rc = '';

		foreach ($options as $name => $usage)
		{
			$popularity = ($usage - $min) / $range;
			$level = 1 + ceil($popularity * ($levels - 1));

			$rc .= '<' . $markup . ' class="tag' . $level . '">' . $name . '</' . $markup . '>' . PHP_EOL;
		}

		return $rc;
	}
}