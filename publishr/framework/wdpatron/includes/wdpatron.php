<?php

/**
 * This file is part of the WdPatron software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.weirdog.com/wdpatron/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.weirdog.com/wdpatron/license/
 */

require_once WDCORE_ROOT . 'wddate.php';

define('WDPATRON_DELIMIT_MACROS', false);

class WdPatron extends WdTextHole
{
	protected $trace_templates = false;
	protected $htmlparser;

	public function __construct()
	{
		$this->htmlparser = new WdHTMLParser
		(
			array
			(
				WdHTMLParser::T_ERROR_HANDLER => array($this, 'error')
			)
		);

		#
		# create context
		#

		$this->contextInit();

		#
		# add functions
		#

		$this->addFunction('toString', array(__CLASS__, '_function_toString'));
		$this->addFunction('add', create_function('$a,$b', 'return ($a + $b);'));
		$this->addFunction('try', array($this, '_get_try'));

		#
		# some operations
		#

		//FIXME: add more operators

		$this->addFunction('if', create_function('$a,$b,$c=null', 'return $a ? $b : $c;'));
		$this->addFunction('or', create_function('$a,$b', 'return $a ? $a : $b;'));
		$this->addFunction('not', create_function('$a', 'return !$a;'));
		$this->addFunction('mod', create_function('$a,$b', 'return $a % $b;'));
		$this->addFunction('bit', create_function('$a,$b', 'return (int) $a & (1 << $b);'));

		$this->addFunction('greater', create_function('$a,$b', 'return ($a > $b);'));
		$this->addFunction('smaller', create_function('$a,$b', 'return ($a < $b);'));
		$this->addFunction('equals', create_function('$a,$b', 'return ($a == $b);'));
		$this->addFunction('different', create_function('$a,$b', 'return ($a != $b);'));

		#
		#
		#

		$this->addFunction('split', create_function('$a,$b=","', 'return explode($b,$a);'));
		$this->addFunction('join', create_function('$a,$b=","', 'return implode($b,$a);'));
		$this->addFunction('index', create_function('', '$a = func_get_args(); $i = array_shift($a); return $a[$i];'));

		$this->addFunction('replace', create_function('$a,$b,$c=""', 'return str_replace($b, $c, $a);'));

		#
		# array (mostly from ruby)
		#

		/**
		 * Returns the first element, or the first n elements, of the array. If the array is empty,
		 * the first form returns nil, and the second form returns an empty array.
		 *
		 * a = [ "q", "r", "s", "t" ]
		 * a.first    // "q"
		 * a.first(1) // ["q"]
		 * a.first(3) // ["q", "r", "s"]
		 *
		 */

		$this->addFunction('first', create_function('$a,$n=null', '$rc = array_slice($a, 0, $n ? $n : 1);  return $n === null ? array_shift($rc) : $rc;'));

		// TODO-20100507: add the 'last' method

		#
		# string (mostly form ruby)
		#

		$this->addFunction('capitalize', create_function('$str', 'return mb_convert_case($str, MB_CASE_TITLE);'));
		$this->addFunction('downcase', 'mb_strtolower');
		$this->addFunction('tr', create_function('$str,$from,$to', 'return strtr($str, $from, $to);'));
		$this->addFunction('upcase', 'mb_strtoupper');
	}

	static private $singleton;

	static public function getSingleton($class='WdPatron')
	{
		if (!self::$singleton)
		{
			self::$singleton = new $class();
		}

		return self::$singleton;
	}

	public function _get_try($from, $which, $default=null)
	{
		$form = (array) $from;

		return isset($from[$which]) ? $from[$which] : $default;
	}

	static public function _function_toString($a)
	{
		if (is_array($a) || (is_object($a) && !method_exists($a, '__toString')))
		{
			return wd_dump($a);
		}

		return (string) $a;
	}

	/*
	**

	SYSTEM

	**
	*/

	protected $trace = array();
	protected $errors = array();

	public function error($message, array $args=array())
	{
		if ($message instanceof Exception)
		{
			$message = $message->getMessage() . '<br /><br />in ' . wd_strip_root($message->getFile()) . ':' . $message->getLine();
		}
		else
		{
			$message = t($message, $args);
		}

		#
		#
		#

		$rc = PHP_EOL . '<strong>wdpatron error:</strong> ' . $message . '<br />';

		if ($this->trace)
		{
			$i = count($this->trace);

			$rc .= '<br />';

			foreach ($this->trace as $trace)
			{
				list($which, $message) = $trace;

				if ($which == 'file')
				{
					$root = $_SERVER['DOCUMENT_ROOT'];
					$root_length = strlen($root);

					if (substr($message, 0, $root_length) == $root)
					{
						$message = substr($message, $root_length);
					}
				}

				$rc .= sprintf('#%02d: in %s "%s"', $i--, $which, $message) . '<br />';
			}
		}

		if (0)
		{
			#
			# PHP stack
			#

			$stack = debug_backtrace();

			$rc .= implode('<br />', WdDebug::formatTrace($stack));
		}

		#
		#
		#

		$this->errors[] = '<code class="wdp-error">' . $rc . '</code>';
	}

	public function fetchErrors()
	{
		$rc = implode(PHP_EOL, $this->errors);

		$this->errors = array();

		return $rc;
	}

	protected function resolve_callback($matches)
	{
		$expression = $matches[1];
		$do_entities = true;

		$modifier = substr($expression, -1, 1);

		if ($modifier == '=')
		{
			$do_entities = false;

			$expression = substr($expression, 0, -1);
		}
		else if ($modifier == '!')
		{
			$this->error('all expressions are automatically escaped now: %expression', array('%expression' => $expression));

			$expression = substr($expression, 0, -1);
		}

		if (strlen($expression) > 2 && $expression[1] == ':' && $expression[0] == 't')
		{
			$rc = t(substr($expression, 2));
		}
		else
		{
			$rc = $this->evaluate($expression);

			if (is_object($rc))
			{
				if (!method_exists($rc, '__toString'))
				{
					$this->error('%expression was resolved to an object of the class %class', array('%expression' => $expression, '%class' => get_class($rc)));
				}

				$rc = (string) $rc;
			}
			else if (is_array($rc))
			{
				$this->error('%expression was resolved to an array with the following keys: :keys', array('%expression' => $expression, ':keys' => implode(', ', array_keys($rc))));
			}
		}

		if ($do_entities)
		{
			$rc = htmlspecialchars($rc, ENT_COMPAT, WDCORE_CHARSET);
		}

		return $rc;
	}

	public function get_file()
	{
		foreach ($this->trace as $trace)
		{
			list($which, $data) = $trace;

			if ($which == 'file')
			{
				return $data;
			}
		}
	}

	public function get_template_dir()
	{
		return dirname($this->get_file());
	}

	/*
	**

	TEMPLATES

	**
	*/

	protected $templates = array();
	protected $templates_searched = false;

	protected function search_templates()
	{
		global $core;

		if ($this->templates_searched)
		{
			return;
		}

		$root = $_SERVER['DOCUMENT_ROOT'];
		$path = '/protected/templates/partials/';

		if (file_exists($root . $path))
		{
			$dh = opendir($root . $path);

			while (($file = readdir($dh)) !== false)
			{
				if ($file{0} == '.')
				{
					continue;
				}

				$name = $file;
				$pos = strrpos($file, '.');

				if ($pos)
				{
					$name = substr($file, 0, $pos);
				}

				$this->addTemplate($name, '!f:' . $root . $path . $file);
			}
		}

		$this->templates_searched = true;
	}

	public function addTemplate($name, $template)
	{
		if (isset($this->templates[$name]))
		{
			$this->error
			(
				'The template %name is already defined ! !template', array
				(
					'%name' => $name, '!template' => $template
				)
			);

			return;
		}

		$this->templates[$name] = $template;
	}

	protected function get_template($name)
	{
		#
		# if the template is not defined, and we haven't searched templates
		# defined by modules, now is the time
		#

//		wd_log("get template $name form " . $this->get_template_dir());

		if (empty($this->templates[$name]))
		{
			$this->search_templates();
		}

		if (isset($this->templates[$name]))
		{
			$template = $this->templates[$name];

			#
			# we convert the template into a tree of nodes to speed up following parsings
			#

			if (is_string($template))
			{
				$file = null;

				if ($template{0} == '!' && $template{1} == 'f' && $template{2} == ':')
				{
					$file = substr($template, 3);
					$template = file_get_contents($file);
					$file = substr($file, strlen($_SERVER['DOCUMENT_ROOT']));
				}

				$template = $this->htmlparser->parse($template, 'wdp:');

				if ($file)
				{
					$template['file'] = $file;
				}

				$this->templates[$name] = $template;
			}

			return $template;
		}
	}

	public function callTemplate($name, array $args=array())
	{
		$template = $this->get_template($name);

		if (!$template)
		{
			$er = 'Unknown template %name';
			$params = array('%name' => $name);

			if ($this->templates)
			{
				$er .= ', available templates: :list';
				$params[':list'] = implode(', ', array_keys($this->templates));
			}

			$this->error($er, $params);

			return;
		}

		array_unshift($this->trace, array('template', $name));

		$this->context['self']['arguments'] = $args;

//		echo l('\1', $this->context['self']['arguments']);

		$rc = $this->publish($template);

		array_shift($this->trace);

		return $rc;
	}

	/*
	**

	CONTEXT

	**
	*/

	protected $context_depth = 0;
	protected $context_pushed = array();
	protected $context_shared = array();

	protected function contextInit()
	{
		foreach ($_SERVER as $key => &$value)
		{
			if (substr($key, 0, 5) == 'HTTP_')
			{
				$_SERVER['http'][strtolower(substr($key, 5))] = &$value;
			}
			else if (substr($key, 0, 7) == 'REMOTE_')
			{
				$_SERVER['remote'][strtolower(substr($key, 7))] = &$value;
			}
			else if (substr($key, 0, 8) == 'REQUEST_')
			{
				$_SERVER['request'][strtolower(substr($key, 8))] = &$value;
			}
		}

		$this->context = array
		(
			'self' => null,
			'this' => null
		);
	}

	protected function contextPush()
	{
		$this->context_depth++;
		array_push($this->context_pushed, $this->context);
	}

	protected function contextPop()
	{
		$this->context = array_pop($this->context_pushed);
		$this->context_depth--;
	}

	/*
	**

	PUBLISH

	**
	*/

	public function publish($template, $bind=null, array $options=array())
	{
		if (is_array($bind) && (isset($bind['bind']) || isset($bind['variables'])))
		{
			throw new WdException('Bind is now an argument sweetheart !');
		}

		if (!$template)
		{
			return;
		}

		if (!is_array($template))
		{
			$template = $this->htmlparser->parse($template, 'wdp:');

			if ($this->errors)
			{
				$rc = $this->fetchErrors();

				return $rc;
			}
		}

		if ($bind !== null)
		{
			$this->context['this'] = $bind;
		}

		$file = null;

		foreach ($options as $option => $value)
		{
			switch ((string) $option)
			{
				case 'variables':
				{
					$this->context = array_merge($this->context, $value);
				}
				break;

				case 'file':
				{
					$file = $value;
				}
				break;

				default:
				{
					WdDebug::trigger('Suspicious option: %option :value', array('%option' => $option, ':value' => $value));
				}
				break;
			}
		}

		if (isset($template['file']))
		{
			$file = $template['file'];

			unset($template['file']);
		}

		if ($file)
		{
			array_unshift($this->trace, array('file', $file));
		}

		$rc = '';

		foreach ($template as $node)
		{
			if (is_string($node))
			{
				#
				# we don't resolve comments, unless they are Internet Explorer comments e.g. <!--[
				#

				$parts = preg_split('#(<!--(?!\[).+-->)#sU', $node, -1, PREG_SPLIT_DELIM_CAPTURE);

				if (count($parts) == 1)
				{
					$rc .= $this->resolve($node);
				}
				else
				{
					#
					# The comments, which are on odd position, are kept intact. The text, which is
					# on even position is resolved.
					#

					foreach ($parts as $i => $part)
					{
						$rc .= ($i % 2) ? $part : $this->resolve($part);
					}
				}

				continue;
			}

			#
			# append errors that might have happened while resolving
			#

			$rc .= $this->fetchErrors();

			#
			# call the markup
			#

			try
			{
				$rc .= $this->call_markup
				(
					$node['name'], $node['args'], isset($node['children']) ? $node['children'] : null
				);
			}
			catch (WdHTTPException $e)
			{
				throw $e;
			}
			catch (Exception $e)
			{
				$this->error($e->getMessage());
			}

			#
			# append errors that might have happened during markup call
			#

			$rc .= $this->fetchErrors();
		}

		#
		#
		#

		if ($file)
		{
			array_shift($this->trace);
		}

		return $rc;
	}

	/*

	#
	# $context_markup is used to keep track of two variables associated with each markup :
	# self and this.
	#
	# 'self' is a reference to the markup itsef, holding its name and the arguments with which
	# it was called, it is also used to store special markup data as for the foreach markup
	#
	# 'this' is a reference to the object of the markup, that being an array, an object or a value
	#
	#

	<wdp:articles>

		self.range.start
		self.range.limit
		self.rnage.count

		this = array of Articles

		<wdp:foreach>

			self.name = foreach
			self.arguments = array()
			self.position
			self.key
			self.left

			this = an Article object

		</wdp:foreach>
	</wdp:articles>

	*/

	protected $context_markup = array();

	protected function call_markup($name, $args, $template)
	{
		try
		{
			$hook = WdHook::find('patron.markups', $name);
		}
		catch (Exception $e)
		{
			$this->error('Unknown markup %name', array('%name' => $name));

			return;
		}

		// TODO-20100425: remove the following compatibility code

		foreach ($args as $param => $value)
		{
			if (strpos($value, '#{') === false)
			{
				continue;
			}

			$this->error
			(
				'COMPAT: Evaluation of the %param parameter for the %markup markup', array
				(
					'%param' => $param,
					'%markup' => $name
				)
			);

			$args[$param] = $this->resolve($value);
		}

		// /20100425

		$missing = array();
		$binding = empty($hook->tags['no-binding']);

		foreach ($hook->params as $param => $options)
		{
			/* DIRTY
			if ($param == 'no-binding')
			{
				$binding = !$options;

				throw new WdException('<em>no-binding</em> should be a hook tag an not a parameter');
			}
			*/

			if (is_array($options))
			{
				/* DIRTY
				if (array_key_exists('silent', $options))
				{
					throw new WdException('<em>silent</em> should be an option of <em>expression</em> and not a parameter option');
				}
				*/

				#
				# default value
				#

				if (isset($options['default']) && !array_key_exists($param, $args))
				{
					$args[$param] = $options['default'];
				}

				if (array_key_exists($param, $args))
				{
					$value = $args[$param];

					if (isset($options['evaluate']))
					{
						wd_log('\4:: evaluate "\3" with value: \5, params \1 and args \2', array($hook->params, $args, $param, $name, $value));

						$args[$param] = $this->evaluate($value);
					}

					if (isset($options['expression']))
					{
						$silent = !empty($options['expression']['silent']);

						//wd_log('\4:: evaluate expression "\3" with value: \5, params \1 and args \2', array($hook->params, $args, $param, $name, $value));

						if ($value{0} == ':')
						{
							$args[$param] = substr($value, 1);
						}
						else
						{
							$args[$param] = $this->evaluate($value, $silent);
						}
					}
				}
				else if (isset($options['required']))
				{
					$missing[$param] = true;
				}
			}
			else
			{
				//wd_log('options is a value: \1', array($options));

				if (!array_key_exists($param, $args))
				{
					$args[$param] = $options;
				}
			}

			if (!isset($args[$param]))
			{
				$args[$param] = null;
			}
		}

		#
		# handle 'with-param' special markups
		#

		if ($template)
		{
			foreach ($template as $k => $child)
			{
				if (!is_array($child) || $child['name'] != 'with-param')
				{
					continue;
				}

				$child_args = $child['args'];
				$param = $child_args['name'];

				if (isset($child_args['select']))
				{
					if (isset($child['children']))
					{
						throw new WdException('Ambiguous selection for with-param %name', array('%name' => $param));
					}

					$args[$param] = $this->evaluate($child_args['select']);
				}
				else if (isset($child['children']))
				{
					$args[$param] = $this->publish($child['children']);
				}

				#
				# remove the parameter for the missing paremets list
				#

				unset($missing[$param]);

				#
				# remove the 'with-param' markup from the template
				#

				unset($template[$k]);
			}
		}

		if ($missing)
		{
			throw new WdException
			(
				'The %param parameter is required for the %markup markup, given %args', array
				(
					'%param' => implode(', ', array_keys($missing)),
					'%markup' => $name,
					'%args' => json_encode($args)
				)
			);
		}

		#
		# call hook
		#

		array_unshift($this->trace, array('markup', $name));

		if ($binding)
		{
			array_push($this->context_markup, array($this->context['self'], $this->context['this']));

			$this->context['self'] = array
			(
				'name' => $name,
				'arguments' => $args
			);
		}

		$rc = null;

		try
		{
			$rc = $hook->__invoke($args, $this, $template);
		}
		catch (WdHTTPException $e)
		{
			throw $e;
		}
		catch (Exception $e)
		{
			$this->error((string) $e->getMessage());
		}

		if ($binding)
		{
			$context = array_pop($this->context_markup);

			$this->context['self'] = $context[0];
			$this->context['this'] = $context[1];
		}

		array_shift($this->trace);

		return $rc;
	}
}