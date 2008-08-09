<?php

class frameless
{
	/**
	 * The base URL of this application
	 *
	 * @var		string
	 * @access	protected
	 * @since	1.0
	 */
	protected	$_baseURL			= NULL;


	/**
	 * The full URL of this application
	 *
	 * @var		string
	 * @access	protected
	 * @since	1.0
	 */
	protected	$_fullURL			= NULL;

	/**
	 * A list of headers used by SMF
	 *
	 * @var		array
	 * @access	protected
	 * @since	1.0
	 */
	protected	$_headers			= array();


	/**
	 * Get an instance of the brdige
	 *
	 * @param	array $config Configuration values
	 * @return	object j2smfBridge
	 * @access	public
	 * @since	1.0
	 */
	function getInstance($config = array())
	{
		static $frameless;

		if ( ! $frameless ) $frameless = new frameless();

		return $frameless;
	}


	/**
	 * Add a header that will later be given to Joomla
	 *
	 * @param	string $header
	 * @access	public
	 * @since	1.0
	 */
	function addHeader($header)
	{
		array_push($this->_headers, $header);
	}

	/**
	 * Get all the headers used by SMF
	 *
	 * @return	array HTML Headers
	 * @access	public
	 * @since	1.0
	 */
	function getAllHeaders()
	{
		return $this->_headers;
	}

	/**
	 * Get the base URL for the application
	 *
	 * @return	string
	 * @access	public
	 * @since	1.0
	 */
	function getBaseURL()
	{
		return $this->_baseURL;
	}

	/**
	 * Get the full URL for the application
	 *
	 * @return	string
	 * @access	public
	 * @since	1.0
	 */
	function getFullURL()
	{
		return $this->_fullURL;
	}


	/**
	 * Set the base URL for the application
	 *
	 * @param	string $url
	 * @access	public
	 * @since	1.0
	 */
	function setBaseURL($url)
	{
		$this->_baseURL = $url;
	}


	/**
	 * Set the full URL for the application
	 *
	 * @param	string $url
	 * @access	public
	 * @since	1.0
	 */
	function setFullURL($url)
	{
		$this->_fullURL = $url;
	}

	function parseBuffer(&$buffer)
	{
		global $boardurl, $scripturl;

		$base		= $this->_baseURL;
		$full		= $this->_fullURL;

		static $regex, $replace;

		if ( ! $regex || ! $replace )
		{
			// Define our preg arrays
			$regex		= array();
			$replace	= array();

			// Add the appropriate url termination
			if ( strpos($base, '?') === false )
			{
				$base .= '?';
			} else
			if ( strpos($base, '&') )
			{
				$base .= '&';
			}

			// Internal links
			$regex[]	= '#href="\#(.*?)"#';
			$replace[]	= 'href="'.$full.'#$1"';

			// Site URLs
			$regex[]	= "#$scripturl\??#mS";
			$replace[]	= $base;

		}

		$buffer = preg_replace($regex, $replace, $buffer);
	}
}



