<?php

class imscp_httpClient
{
	/**
	 * @var resource
	 */
	protected $curlSession;

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var int
	 */
	protected $port;

	/**
	 * @var string
	 */
	protected $userAgent;

	/**
	 * @var bool
	 */
	protected $headers;

	/**
	 * @var string cookieJar file path
	 */
	protected $cookieJar;

	public function __construct($url = null, $port = 80, $headers = false, $cookieJar = null, $userAgent = null)
	{
		if (function_exists('curl_init')) {
			$this->curlSesssion = curl_init();
			$this->url = $url;
			$this->port = $port;
			$this->headers = $headers;
			$this->cookie = $cookieJar;
			$this->userAgent = $userAgent;
		} else {
			throw new Exception('PHP cURL extension is not installed');
		}
	}

	/**
	 * Set object options
	 *
	 * @param string $option Option name
	 * @param string $value OPtion value
	 */
	public function setOption($option, $value)
	{
		$option = strtolower($option);
		$this->{$option} = $value;
	}

	/**
	 * Do a GET request
	 *
	 * @param string|null $url Url
	 * @param int|null $port Port
	 * @param bool $headers Whether or not response headers must be returned
	 * @param string|null $cookieJar
	 * @param string|null $userAgent
	 * @return array|string
	 */
	public function doGetRequest($url = null, $port = null, $headers = false, $cookieJar = null, $userAgent = null)
	{
		// Set parameters

		$this->_setParam($url, $this->url);
		$this->_setParam($port, $this->port);
		$this->_setParam($headers, $this->headers);
		$this->_setParam($cookieJar, $this->cookieJar);
		$this->_setParam($userAgent, $this->userAgent);

		// cURL Setup
		curl_setopt($this->curlSession, CURLOPT_PORT, $port);
		curl_setopt($this->curlSession, CURLOPT_URL, $url);
		curl_setopt($this->curlSession, CURLOPT_HEADER, $headers);
		curl_setopt($this->curlSession, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curlSession, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curlSession, CURLOPT_AUTOREFERER, true);

		if (is_readable($cookieJar)) {
			curl_setopt($this->curlSession, CURLOPT_COOKIE, $cookieJar);
			curl_setopt($this->curlSession, CURLOPT_COOKIEJAR, $cookieJar);
			curl_setopt($this->curlSession, CURLOPT_COOKIEFILE, $cookieJar);
		}

		if ($userAgent) {
			curl_setopt($this->curlSession, CURLOPT_USERAGENT, $this->userAgent);
		}

		// Execution
		if ($headers) {
			return array("headers" => curl_exec($this->curlSession), "content" => curl_exec($this->curlSession));
		} else {
			return curl_exec($this->curlSession);
		}
	}

	/**
	 * Do a POST request
	 *
	 * @param string|array $post POST parameters
	 * @param string|null $url Url
	 * @param int|null $port Port
	 * @param bool $headers Whether or not response headers must be returned
	 * @param string|null $cookieJar cookie file path
	 * @param string|null $userAgent OPTIONAL User agent
	 * @return array|string
	 */
	public function doPostRequest($post, $url = null, $port = null, $headers = false, $cookieJar = null, $userAgent = null)
	{
		// Set parameters
		$this->_setParam($url, $this->url);
		$this->_setParam($port, $this->port);
		$this->_setParam($headers, $this->headers);
		$this->_setParam($cookieJar, $this->cookieJar);
		$this->_setParam($userAgent, $this->userAgent);

		// cURL Setup
		curl_setopt($this->curlSession, CURLOPT_PORT, $port);
		curl_setopt($this->curlSession, CURLOPT_URL, $url);
		curl_setopt($this->curlSession, CURLOPT_HEADER, $headers);
		curl_setopt($this->curlSession, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curlSession, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curlSession, CURLOPT_AUTOREFERER, true);
		curl_setopt($this->curlSession, CURLOPT_POST, true);

		if (is_array($post)) {
			$string = '';

			foreach ($post as $key => $value) {
				$string .= "$key=$value&";
			}

			curl_setopt($this->curlSession, CURLOPT_POSTFIELDS, $string);
		} else {
			curl_setopt($this->curlSession, CURLOPT_POSTFIELDS, $post);
		}

		if ($userAgent !== false) {
			curl_setopt($this->curlSession, CURLOPT_USERAGENT, $this->userAgent);
		}

		if (is_readable($cookieJar)) {
			curl_setopt($this->curlSession, CURLOPT_COOKIE, $cookieJar);
			curl_setopt($this->curlSession, CURLOPT_COOKIEJAR, $cookieJar);
			curl_setopt($this->curlSession, CURLOPT_COOKIEFILE, $cookieJar);
		}

		// Execution
		if ($headers) {
			return array("headers" => curl_exec($this->curlSession), "content" => curl_exec($this->curlSession));
		} else {
			return curl_exec($this->curlSession);
		}
	}

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		if (is_resource($this->curlSession)) {
			curl_close($this->curlSession);
		}
	}

	/**
	 * Wake up method to enable unserialization
	 */
	public function __wakeup()
	{
		if (!is_resource($this->curlSession)) {
			$this->handler = curl_init();
		}
	}

	/**
	 * Set a variable to $default parameter if it's false
	 * @param mixed $param Param value
	 * @param mixed $default Default value set if $param is null
	 * @return void
	 */
	protected function _setParam(&$param, $default)
	{
		if ($param === null) {
			$param = $default;
		}
	}
}










function sendRequest() {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/cookieFileName");
	curl_setopt($ch, CURLOPT_URL,"http://raring.nuxwin.com/index.php");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "uname=admin&upass=skittles");

	ob_start();      // prevent any output
	curl_exec ($ch); // execute the curl command
	ob_end_clean();  // stop preventing output

	curl_close ($ch);
	unset($ch);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_COOKIEFILE, "/tmp/cookieFileName");
	curl_setopt($ch, CURLOPT_URL,"http://raring.nuxwin.com/admin/index.php");

	$buf2 = curl_exec ($ch);

	curl_close ($ch);

	echo "<PRE>".htmlentities($buf2);
}

sendRequest();
