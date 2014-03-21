<?php

namespace ServerProvisioning;

use iMSCP_Exception as Exception;
use Zend\Http\PhpEnvironment\Request as Request;

/**
 * Class Router
 *
 * @package ServerProvisioning
 */
class Router
{
	/**
	 * @var array
	 */
	protected $routes = array();

	/**
	 * @var array
	 */
	protected $namedRoutes = array();

	/**
	 * @var string
	 */
	protected $basePath = '';

	/**
	 * @var Request;
	 */
	protected $request;

	/**
	 * Constructor
	 *
	 * @param Request $request
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * Set the base path
	 *
	 * @return Router
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $basePath;

		return $this;
	}

	/**
	 * Add several route at once
	 *
	 * @see map()
	 * @params array $routes
	 * @return Router
	 */
	public function addRoutes(array $routes = array())
	{
		foreach ($routes as $route) {
			$this->addRoute(
				$route['method'], $route['route'], $route['target'], isset($route['name']) ? $route['name'] : null
			);
		}

		return $this;
	}

	/**
	 * Map a route to a target
	 *
	 * @throws \Exception
	 * @param string $method Http method
	 * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
	 * @param mixed $target The target where this route should point to. Can be anything.
	 * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
	 * @return Router
	 */
	public function addRoute($method, $route, $target, $name = null)
	{
		if ($route != '*') {
			$route = $this->basePath . $route;
		}

		$this->routes[] = array($method, $route, $target, $name);

		if ($name) {
			if (isset($this->namedRoutes[$name])) {
				throw new Exception("Cannot redeclare route '{$name}'");
			} else {
				$this->namedRoutes[$name] = $route;
			}
		}

		return $this;
	}

	/**
	 * Reversed routing
	 *
	 * Generate the URL for a named route. Replace regexes with supplied parameters
	 *
	 * @throws Exception
	 * @param string $routeName Route name
	 * @param array $params @params Associative array containing placeholders replacements.
	 * @return string URL.
	 */
	public function generate($routeName, array $params = array())
	{
		// Check if named route exists
		if (!isset($this->namedRoutes[$routeName])) {
			throw new Exception("Route '{$routeName}' does not exist.");
		}

		// Replace named parameters
		$route = $this->namedRoutes[$routeName];
		$url = $route;

		if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				list($block, $pre, , $param, $optional) = $match;

				if ($pre) {
					$block = substr($block, 1);
				}

				if (isset($params[$param])) {
					$url = str_replace($block, $params[$param], $url);
				} elseif ($optional) {
					$url = str_replace($pre . $block, '', $url);
				}
			}
		}

		return $url;
	}

	/**
	 * Match a given URI path against stored routes
	 *
	 * @param string $path URI path
	 * @param string $httpMethod HTTP method
	 * @return array|boolean Array with route information on success, false on failure (no match).
	 */
	public function match($path = null, $httpMethod = null)
	{
		$params = array();

		// set URI path if it isn't passed as parameter
		if ($path === null) {
			$path = $this->request->getUri()->getPath();
		}

		// set HTTP method if it isn't passed as a parameter
		if ($httpMethod === null) {
			$httpMethod = $this->request->getMethod();
		}

		foreach ($this->routes as $handler) {
			list($method, $pRoute, $target, $name) = $handler;

			$methods = explode('|', $method);
			$methodMatch = false;

			// Check if request method matches. If not, abandon early. (CHEAP)
			foreach ($methods as $method) {
				if (strcasecmp($httpMethod, $method) === 0) {
					$methodMatch = true;
					break;
				}
			}

			// Method did not match, continue to next route.
			if (!$methodMatch) continue;

			// Check for a wildcard (matches all)
			if ($pRoute === '*') {
				$match = true;
			} elseif (isset($pRoute[0]) && $pRoute[0] === '@') {
				$match = preg_match('`' . substr($pRoute, 1) . '`', $path, $params);
			} else {
				$route = null;
				$regex = false;
				$j = 0;
				$n = isset($pRoute[0]) ? $pRoute[0] : null;
				$i = 0;

				// Find the longest non-regex substring and match it against the URI Path
				while (true) {
					if (!isset($pRoute[$i])) {
						break;
					} elseif (false === $regex) {
						$c = $n;
						$regex = $c === '[' || $c === '(' || $c === '.';

						if (false === $regex && false !== isset($pRoute[$i + 1])) {
							$n = $pRoute[$i + 1];
							$regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
						}

						if (false === $regex && $c !== '/' && (!isset($path[$j]) || $c !== $path[$j])) {
							continue 2;
						}

						$j++;
					}

					$route .= $pRoute[$i++];
				}

				$regex = $this->compileRoute($route);
				$match = preg_match($regex, $path, $params);
			}

			if (($match == true || $match > 0)) {
				if ($params) {
					foreach ($params as $key => $value) {
						if (is_numeric($key)) unset($params[$key]);
					}
				}

				return array('target' => $target, 'params' => $params, 'name' => $name);
			}
		}

		return false;
	}

	/**
	 * Compile the regex for a given route (EXPENSIVE)
	 *
	 * @param string $route Route
	 * @return string
	 */
	private function compileRoute($route)
	{
		if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
			$matchTypes = array(
				'i' => '[0-9]++',
				'a' => '[0-9A-Za-z]++',
				'h' => '[0-9A-Fa-f]++',
				'*' => '.+?',
				'**' => '.++',
				'' => '[^/\.]++'
			);

			foreach ($matches as $match) {
				list($block, $pre, $type, $param, $optional) = $match;

				if (isset($matchTypes[$type])) {
					$type = $matchTypes[$type];
				}

				if ($pre === '.') {
					$pre = '\.';
				}

				// Older versions of PCRE require the 'P' in (?P<named>)
				$pattern = '(?:'
					. ($pre !== '' ? $pre : null)
					. '('
					. ($param !== '' ? "?P<$param>" : null)
					. $type
					. '))'
					. ($optional !== '' ? '?' : null);

				$route = str_replace($block, $pattern, $route);
			}

		}

		return "`^$route$`";
	}
}
