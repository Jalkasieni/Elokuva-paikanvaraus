<?php
/**
*
* @package apexnet
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * Web response implementation for requests that have an associated template
 */
class web_response_template extends web_response
{
	// Twig environment
	protected $twig;

	protected $tpl_vars = array();
	protected $tpl_file = false;

	protected function __construct(web_request $request, $status_code, $charset)
	{
		parent::__construct($request, $status_code, $charset);

		$this->twig = new Twig_Environment(new Twig_Loader_Filesystem(array(APEXNET_APP_VIEWS, APEXNET_APP_TEMPLATES, APEXNET_TEMPLATE_PATH)), array(
			'charset'		=> $this->charset,
			'debug'			=> defined('DEBUG'),
			'cache'			=> APEXNET_APP_CACHE . 'templates/',
			'autoescape'	=> true
		));

		$this->tpl_vars = array(
			'content_encoding'	=> $this->charset,
			'base_url'			=> $this->request->base_url(),
			'page_url'			=> $this->request->request_url(),
			'request'			=> $this->request->route()
		);

		// add template functions
		$this->twig->addFunction(new Twig_SimpleFunction('page_time', '\ApexNet\Foundation\Util::time'));
		$this->twig->addFunction(new Twig_SimpleFunction('page_memory', '\ApexNet\Foundation\Util::memory'));

		// add session id control functions
		$this->twig->addFunction(new Twig_SimpleFunction('append_sid', array($this->request, 'append_sid')));
		$this->twig->addFunction(new Twig_SimpleFunction('remove_sid', 'web_request::remove_sid'));

		// add content filters
		$this->twig->addFilter(new Twig_SimpleFilter('linkify', '\ApexNet\BBCode\BBCParser::linkifyText', array('is_safe' => array('html'))));
	}

	public function template_var($name, $value)
	{
		$this->tpl_vars[$name] = $value;
	}

	public function template_row($name, array $vars)
	{
		$this->tpl_vars[$name][] =& $vars;
	}

	public function paginate($per_page, $total, $name = false)
	{
		// Check per_page to avoid division by zero
		$per_page = ($per_page <= 0) ? 1 : $per_page;

		// Do not do anything if there is nothing to do
		if ($total < $per_page)
			return 0;

		// Some simple math
		$start_var = "{$name}_start";
		$offset = max($this->request->variable($start_var, 0), 0);
		if ($offset < $per_page)
		{
			$offset = 0;
		}
		else if ($offset > $total)
		{
			$offset = floor(($total - 1) / $per_page) * $per_page;
		}

		if (!$name)
			return $offset;

		$total_pages = ceil($total / $per_page);
		if ($total_pages > 1 && $total)
		{
			// Build the url of the current page
			$url = preg_replace("#(?:&|\\?)?$start_var=[0-9]*#i", '', $this->request->request_url());
			$url = $this->request->append_sid($url);

			$delim = (strpos($url, '?') === false) ? '?' : '&';
			$on_page = floor($offset / $per_page) + 1;

			$start = $on_page - 2;
			$end = $on_page + 2;

			while ($start < 1 && ($end + 1) <= $total_pages)
			{
				++$end;
				++$start;
			}

			while ($end > $total_pages && ($start - 1) >= 1)
			{
				--$start;
				--$end;
			}

			$start = max($start, 1);
			$end = min($end, $total_pages);

			$pages = array();
			for (; $start <= $end; ++$start)
			{
				$page_url = ($start != $on_page) ? $url :  false;
				$page_offset = (($start - 1) * $per_page);
				if ($page_url && $page_offset > 0)
					$page_url .= "$delim$start_var=$page_offset";

				$pages['pages'][$start] = $page_url;
			}

			if (!empty($pages))
			{
				if ($on_page != 1)
					$pages['previous'] = $pages['pages'][$on_page - 1];
				if ($on_page != $total_pages)
					$pages['next'] = $pages['pages'][$on_page + 1];

				$this->tpl_vars["{$name}_pagination"] =& $pages;
			}
		}

		return $offset;
	}

	public function body($name, array $page_vars = array())
	{
		$this->tpl_file = "$name.twig.html";
		if (!empty($page_vars))
			$this->tpl_vars = array_merge($this->tpl_vars, $page_vars);

		return $this;
	}

	public function output()
	{
		return $this->twig->render($this->tpl_file, $this->tpl_vars);
	}

	public function send()
	{
		$this->send_headers();

		// if invoked from error handler, disable cache
		if (defined('APEXNET_FATAL_ERROR'))
			$this->twig->setCache(false);

		$this->twig->display($this->tpl_file, $this->tpl_vars);
		exit();
	}
}
