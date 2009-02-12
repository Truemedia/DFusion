<?php

/**
 * @package JFusion
 * @subpackage Models
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Abstract interface for all JFusion functions that are accessed through the Joomla front-end
* @package JFusion
*/
class JFusionPublic{

    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */
    function getJname()
    {
        return '';
    }

    /**
     * gets the visual html output from the plugin
     */
    function & getBuffer($jPluginParam)
    {
    	return 0;
    }

    /**
     * function that parses the HTML body and fixes up URLs and form actions
     * @param string the HTML body output generated by getBuffer()
     * @param string $baseURL the base joomla URL to add variables to
     * @param string $fullURL the full URL to the current page
     * @param string $integratedURL the URL to the integrated software
     * @return object userinfo Object containing the user information
     */
    function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
    {
    }

    /**
     * function that parses the HTML header and fixes up URLs
     * @param string $buffer the output generated by getBuffer()
     * @param string $baseURL the base joomla URL to add variables to
     * @param string $fullURL the full URL to the current page
     * @param string $integratedURL the URL to the integrated software
     * @return object userinfo Object containing the user information
     */
    function parseHeader(&$buffer, $baseURL, $fullURL, $integratedURL)
    {
    }

     /**
     * Returns the registration URL for the integrated software
     * @return string registration URL
     */
    function getRegistrationURL()
    {
        return '';
    }

     /**
     * Returns the lost password URL for the integrated software
     * @return string lost password URL
     */
    function getLostPasswordURL()
    {
        return '';
    }

     /**
     * Returns the lost username URL for the integrated software
     * @return string lost username URL
     */
    function getLostUsernameURL()
    {
        return '';
    }

	/************************************************
	 * Functions For JFusion Search Plugin
	 ***********************************************/
    
    /**
     * Retrieves the search results to be displayed.  Placed here so that plugins that do not use the database can retrieve and return results
     * @param $text string text to be searched
     * @param $phrase string how the search should be performed exact, all, or any
     * @param $pluginParam custom plugin parameters in search.xml
     * @return array of results as objects
     * Each result should include:
     * $result->title = title of the post/article
     * $result->section = section of  the post/article (shows underneath the title; example is Forum Name / Thread Name)
     * $result->text = text body of the post/article
     * $result->?? = whatever else you need to create the link in getSearchResultLink()
     */
	function getSearchResults(&$text, &$phrase, &$pluginParam)
	{
		//initialize plugin database
		$db = & JFusionFactory::getDatabase($this->getJname());
		//get the query used to search
		$query = $this->getSearchQuery();

		//assign specific table colums to title and text
		$columns = $this->getSearchQueryColumns();

		//build the query
		if($phrase == 'exact') {
			$where = "(LOWER({$columns->title}) LIKE '%$text%') OR (LOWER({$columns->text}) like '%$text%')";
		} else {
			$words = explode (' ', $text);
			$wheres = array();
			foreach($words as $word) {
				$wheres[] = "(LOWER({$columns->title}) LIKE '%$word%') OR (LOWER({$columns->text}) like '%$word%')";
			}

			if($phrase == 'all') $separator = "AND";
			else $separator = "OR";

			$where = '(' . implode ( ") $separator (", $wheres) . ')';
		}
		
		//pass the where clause into the plugin in case it wants to add something
		$this->getSearchCriteria($where);
		
		$query .= " WHERE $where";
		
		$db->setQuery($query);
		$results = $db->loadObjectList();
		
		//pass results back to the plugin in case they need to be filtered
		$this->filterSearchResults($results);
		return $results;
	}   
    
     /**
     * Assigns specific db columns to title and text of content retrieved
     * @return object Db columns assigned to title and text of content retrieved
     */
	function getSearchQueryColumns()
	{
		$columns = new stdClass();
		$columns->title = '';
		$columns->text = '';
		return $columns;
	}

	/**
	 * Generates SQL query for the search plugin that does not include where, limit, or order by
	 * @return string Returns query string
	 */
	function getSearchQuery()
	{
		return '';
	}

	/**
	 * Add on a plugin specific clause; 
	 * @param $where reference to where clause already generated by search bot; add on plugin specific criteria
	 */
	function getSearchCriteria(&$where)
	{
		
	}
	
	/**
	 * Filter out results from the search ie forums that a user does not have permission to
	 * @param $results object list of search query results
	 */
	function filterSearchResults(&$results)
	{
		
	}
	
	/**
	 * Returns the URL for a post
	 * @param $vars mixed 
	 * @return string with URL
	 */
	function getSearchResultLink($vars)
	{
		return '';
	}
	
	/**
	 * Cleans up the text before presented to user; useful for removing BB code, etc
	 * @param $text Text to be cleaned up
	 * @return Cleaned up text
	 */
	function cleanUpSearchText($text)
	{
		return $text;
	}
}