<?php

class j2smfBridge
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
	 * An array of checks to perform for integration
	 * 
	 * @var		arra
	 * @access	protected
	 * @since	1.0
	 */
	//protected	$_checks			= array();
	
	/**
	 * A flag that indicates if debugging has been enabled
	 *
	 * @var		boolean
	 * @access	protected
	 * @since	1.0
	 */
	protected	$_debug				= 0;
	
	/**
	 * A list of debug messages
	 *
	 * @var		array
	 * @access	protected
	 * @since	1.0
	 */
	protected	$_debugMessages		= array();
	
	/**
	 * A list of error messages
	 *
	 * @var		array
	 * @access	protected
	 * @since	1.0
	 */
	protected	$_errorMessages		= array();
	
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
	 * The path to the SMF Index file
	 *
	 * @var		string
	 * @access	protected
	 * @since	1.0
	 */
	//protected	$_smfPath			= 'smf1';
	
	/**
	 * Integrate SMF users into the Joomla database
	 *
	 * @var		string
	 * @access	protected
	 * @since	1.0
	 */
	//protected 	$_userIntegration	= false;
	
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
		static $bridge;
		
		if ( ! $bridge ) $bridge = new j2smfBridge();
		
		return $bridge;
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
	//function getBaseURL()
	//{
		//return $this->_baseURL;
	//}
	
	/**
	 * Get the debug level for the bridge
	 *
	 * @return	boolean True if debugging has been enabled
	 * @access	public
	 * @since	1.0
	 */
	function getDebug()
	{
		return $this->_debug;
	}
	
	/**
	 * Get the list of debug messages
	 *
	 * @return	array
	 * @access	public
	 * @since	1.0
	 */
	function getDebugMessages()
	{
		return $this->_debugMessages;
	}
	
	/**
	 * Get the list of error messages
	 *
	 * @return	array
	 * @access	public
	 * @since	1.0
	 */
	function getErrorMessages()
	{
		return $this->_errorMessages;
	}
	
	/**
	 * Get the full URL for the application
	 *
	 * @return	string
	 * @access	public
	 * @since	1.0
	 */
	//function getFullURL()
	//{
		//return $this->_fullURL;
	//}
	
	/**
	 * Get a flag for a type of integration check
	 *
	 * @param	strng $type
	 * @return	mixed
	 * @access	public
	 * @since	1.0
	 */
	//function getIntegrationCheck($type)
	//{
		//if ( ! isset($this->_checks[$type]) ) return null;
		
		//return $this->_checks[$type];
	//}
	
	/**
	 * Execute the SMF script and return the contents
	 *
	 * @return	string SMF Output
	 * @access	public
	 * @access	1.0
	 */
	function & getSMFBuffer()
	{
		// We're going to want a few globals... these are all set later.
		global $time_start, $maintenance, $msubject, $mmessage, $mbname, $language;
		global $boardurl, $boarddir, $sourcedir, $webmaster_email, $cookiename;
		global $db_server, $db_name, $db_user, $db_prefix, $db_persist, $db_error_send, $db_last_error;
		global $db_connection, $modSettings, $context, $sc, $user_info, $topic, $board, $txt;
		global $scripturl;
		
		// Required to avoid a warning about a license violation even though this is not the case
		global $forum_version;
		
		// Get the path
		$path	= $this->getSMFPath().DS.'index.php';
		
		if ( ! is_file($path) ) {
			$this->logError('The path to the SMF index file set in the component preferences does not exist');
			return null;
		}
		
		// Get the output
		ob_start();
		$rs = include_once($path);
		$buffer = ob_get_contents();
		ob_end_clean();
		
		// Log an error if we could not include the file
		if ( ! $rs ) $this->logDebug('Could not find SMF in the specified directory');
		
		return $buffer;
	}
	
	/**
	 * Get the path to the SMF index file
	 *
	 * @return	string File Path
	 * @access	public
	 * @since	1.0
	 */
	//function getSMFPath()
	//{
		//if ( ! $this->_smfPath && $this->getDebug() )
		//{
			//throw new j2smfException('PATH_TO_SMF_NOT_SET');
			//return false;
		//}
		
		//return $this->_smfPath;
	//}
		
	/**
	 * Get an SMF Member Emal given a Member Name
	 *
	 * @param	string $name Member Name
	 * @return	string Member Emails, null if a username cannot be found
	 * @access	public
	 */
	//function getSMFMemberEmail($name)
	//{
		//global $db_prefix;
		
		//$query	= "SELECT emailAddress FROM {$db_prefix}members WHERE memberName = '$name' LIMIT 1";
		//$cur	= db_query( $query, __FILE__, __LINE__);
		//$row	= mysql_fetch_row( $cur );
		
		//if ( ! $row ) return null;
		
		//return	$row[0];
	//}
	
	/**
	 * Get an SMF Member ID given a Member Name
	 *
	 * @param	string $name Member Name
	 * @return	integer Member ID, null if a username cannot be found
	 * @access	public
	 */
	//function getSMFMemberID($name)
	//{
		//global $db_prefix;
		
		//$query	= "SELECT ID_MEMBER FROM {$db_prefix}members WHERE memberName = '$name' LIMIT 1";
		//$cur	= db_query( $query, __FILE__, __LINE__);
		//$row	= mysql_fetch_row( $cur );
		
		//if ( ! $row ) return null;
		
		//return	$row[0];
	//}

	/**
	 * Get an SMF Member Name given a Member ID
	 *
	 * @param	integer $id Member ID
	 * @return	string Member Name, null if a username cannot be found
	 * @access	public
	 */
	//function getSMFMemberName($id)
	//{
		//global $db_prefix;
		
		//$query	= "SELECT memberName FROM {$db_prefix}members WHERE ID_MEMBER = $id LIMIT 1";
		//$cur	= db_query( $query, __FILE__, __LINE__);
		//$row	= mysql_fetch_row( $cur );
		
		//if ( ! $row ) return null;
		
		//return	$row[0];
	//}
	
	/**
	 * Allow SMF users to integrate into the Joomla system
	 *
	 * @return	boolean True if user integration should be allowed
	 * @access	public
	 * @since	1.0
	 */
	//function getUserIntegration()
	//{
		//return $this->_userIntegration;
	//}
	
	/**
	 * Load the integration file that will integrate many aspects of SMF with Joomla via hooks
	 *
	 * @param	string $file Name of the integration file, Optional
	 * @return	boolean True
	 * @access	public
	 * @since	1.0
	 */
	//function loadIntegration( $path = null )
	//{
		// Allow for an override
		//if ( ! $path )	$path	= $this->_integrationScript;
		
		//if ( ! file_exists($path) ) {
			//$this->logError('CANNOT_LOAD_INTEGRATION_FILE');
			//return false;
		//}
		
		//return include_once($path);
	//}
	
	/**
	 * Log a debug message
	 *
	 * @param	string $message
	 * @access	public
	 * @since 	1.0
	 */
	function logDebug($message)
	{
		$j2smf		= j2smfBridge::getInstance();
		$j2smf->_logDebug($message);
	}
	
	function _logDebug($message)
	{
		array_push($this->_debugMessages, $message);
	}
	
	/**
	 * Log an error
	 *
	 * @param	string $message Error Message
	 * @access	public
	 * @since	1.0
	 */
	function logError($message)
	{
		array_push($this->_errorMessages, $message);
	}
	
	/**
	 * Set the base URL for the application
	 *
	 * @param	string $url
	 * @access	public
	 * @since	1.0
	 */
	//function setBaseURL($url)
	//{
		//$this->_baseURL = $url;
	//}
	
	/**
	 * Set the debug level for the bridge
	 *
	 * @param	boolean $flag
	 * @access	public
	 * @since	1.0
	 */
	function setDebug($flag)
	{
		$this->_debug	= $flag;
	}
	
	/**
	 * Set the full URL for the application
	 *
	 * @param	string $url
	 * @access	public
	 * @since	1.0
	 */
	//function setFullURL($url)
	//{
		//$this->_fullURL = $url;
	//}
	
	/**
	 * Set a flag for a type of integration check
	 *
	 * @param	strng $type
	 * @param	mixed $value
	 * @access	public
	 * @since	1.0
	 */
	//function setIntegrationCheck($type, $value)
	//{
		//$this->_checks[$type]	= $value;
	//}
	
	/**
	 * Set the filename to the integration script
	 *
	 * @param	string $path
	 * @access	public
	 * @since	1.0
	 */
	//function setIntegrationScript($path)
	//{
		//$this->_integrationScript	= $path;
	//}
	
	/**
	 * Set the path to the SMF index file
	 *
	 * @param	string $path File Path
	 * @access	public
	 * @since	1.0
	 */
	//function setSMFPath($path)
	//{
		//$this->_smfPath	= $path;
	//}
	
	/**
	 * Allow SMF users to integrate into the Joomla system
	 *
	 * @param	boolean $flag
	 * @access	public
	 * @since	1.0
	 */
	//function setUserIntegration($flag)
	//{
		//$this->_userIntegration	= $flag;
	//}
	
	/**
	 * Indicates if the Joomla user should be logged out if a user is not logged into SMF
	 *
	 * @return	boolean True if the user should be logged out
	 * @access	public
	 * @since	1.0
	 */	
	//function shouldEnforceIntegratedLogin()
	//{
		//return (boolean) $this->_checks['ENFORCE_INTEGRATION'];
	//}
	
	/**
	 * Indicates if email verification should happen
	 *
	 * @return	boolean True if e-mail verification should happen
	 * @access	public
	 * @since	1.0
	 */
	//function shouldVerifyEmail()
	//{
		//return (boolean) $this->_checks['MATCH_EMAIL'];
	//}
}

class j2smfException extends Exception 
{
	
}
