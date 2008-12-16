<?php
    /**
* @package JFusion
* @subpackage Plugin_Discussbot
* @version 1.0.9
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

    // no direct access
    defined('_JEXEC' ) or die('Restricted access' );

    /**
* Load the JFusion framework
*/
    jimport('joomla.plugin.plugin');
    require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
    require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');

    class plgContentJfusion extends JPlugin
    {
        /**
		* Constructor
		*
		* For php4 compatability we must not use the __constructor as a constructor for
		* plugins because func_get_args ( void ) returns a copy of all passed arguments
		* NOT references. This causes problems with cross-referencing necessary for the
		* observer design pattern.
		*/
	    function plgContentJfusion(& $subject, $config)
	    {
	        parent::__construct($subject, $config);

	        //load the language
	        $this->loadLanguage('com_jfusion', JPATH_BASE);
	    }

	    function onPrepareContent(& $contentitem, $options)
	    {
			global $JFusionActive;
			$JFusionActive = true;

			//prevent any output by the plugins (this could prevent cookies from being passed to the header)
			ob_start();

	    	//retrieve slave software
	        $slaves = JFusionFunction::getSlaves();

	        foreach($slaves as $slave)
	        {
				$jname = $slave->name;
	            //get the params
    	        $params = JFusionFactory::getParams($jname);
	        	$discussBot = $params->get("enable_discussbot");
				$linkMode = $params->get("link_mode");
	        	$linkText = $params->get("link_text");
   	        	$auto = $params->get("auto_create");

    	        if($discussBot)
    	        {
    	            //now check to see if the plugin is configured
				    $jdb =& JFactory::getDBO();
				    $query = 'SELECT status from #__jfusion WHERE name = ' . $jdb->quote($jname);
				    $jdb->setQuery($query );

				    if ($jdb->loadResult() == 3)
				    {
        				$forum = JFusionFactory::getForum($jname);

        				//create the thread if set to auto generate but only if the content is published
	    	        	if($auto && $contentitem->state)
	    	        	{
	    	        		$generate = true;

	    					//check to see if there are sections/categories that are specifically included/excluded
	    					$sections = $params->get("include_sections");
							$includeSections = empty($sections) ? false : explode(",",$sections);

							$categories = $params->get("include_categories");
							$includeCategories = empty($categories) ? false : explode(",",$categories);

							$sections = $params->get("exclude_sections");
							$excludeSections = empty($sections) ? false : explode(",",$sections);

							$categories = $params->get("exclude_categories");
							$excludeCategories = empty($categories) ? false : explode(",",$categories);

							//section and category id of content
							$secid = $contentitem->sectionid;
							$catid = $contentitem->catid;

							//there are section stipulations on what articles to include
							if($includeSections)
							{
								if($includeCategories)
								{
									//there are both specific sections and categories to include
									//check to see if this article is not in the selected sections and categories
									if(!in_array($secid,$includeSections) && !in_array($catid,$includeCategories)) $generate = false;
								}
								elseif($excludeCategories)
								{
									//exclude this article if it is in one of the excluded categories
									if(in_array($catid,$excludeCategories)) $generate = false;
								}
								//there are only specific sections to include with no category stipulations
								elseif(in_array($secid,$includeSections)) $generate = true;
								//this article is not in one of the sections to include
								else $generate = false;
							}
							//there are category stipulations on what articles to include but no section stipulations
	    	        		elseif($includeCategories)
	    	        		{
	    	        			//check to see if this article is not in the selected categories
	    	        			if(!in_array($catid,$includeCategories)) $generate = false;
	    	        		}
	    	        		//there are section stipulations on what articles to exclude
	    	        		elseif($excludeSections)
	    	        		{
	    	        			//check to see if this article is in the excluded sections
	    	        			if(in_array($secid,$excludeSections)) $generate = false;
	    	        		}
	    	        		//there are category stipulations on what articles to exclude but no exclude stipulations on section
	    	        		elseif($excludeCategories)
	    	        		{
	    	        			//check to see if this article is in the excluded categories
	    	        			if(in_array($catid,$excludeCategories)) $generate = false;
	    	        		}

	    	        		//generate the thread/post if article meets criteria above
	    	        		if($generate)
	    	        		{
	      	      				$JFusionSlave = JFusionFactory::getForum($jname);
	            	    		$SlaveThread = $JFusionSlave->checkThreadExists($contentitem);

	                			if ($SlaveThread['error']) {
	                    			JFunctionFunction::raiseWarning($slave->name . ' ' .JText::_('FORUM') . ' ' .JText::_('UPDATE'), $SlaveThread['error'],1);
		                		}

		                		//get the ID of the thread
		                		$existingthread = $JFusionSlave->getThread($contentitem->id);
		                		$threadid = $existingthread->threadid;

								$urlstring_pre = JFusionFunction::createURL($forum->getThreadURL($threadid), $jname, $linkMode);
			                    $urlstring = '<div class="jfusiondiscusslink"><a href="'. $urlstring_pre . '" target="' . $new_window . '">' . $linkText . '</a></div>';

								//add link to content
			                    $contentitem->text = $contentitem->text . $urlstring;

			                    //add posts to content if enabled
			                    if($params->get("show_posts"))
			                    {
									$tableOfPosts = $JFusionSlave->createPostTable($existingthread);
									$contentitem->text = $contentitem->text . $tableOfPosts;
			                    }
	    	        		}

							//find any {jfusion_discuss...} to manually plug
		    	        	 preg_match_all('/\{jfusion_discuss (.*)\}/U',$contentitem->text,$matches);

		    	        	 //find each thread by the id
		    	        	 foreach($matches[1] AS $id)
		    	        	 {
								//create the url string
		                        $urlstring_pre = JFusionFunction::createURL($forum->getThreadURL($id), $jname, $linkMode);
		                        $urlstring = '<div class="jfusiondiscusslink"><a href="'. $urlstring_pre . '" target="' . $new_window . '">' . $linkText . '</a></div>';

		                        //replace plugin with link
		                        $contentitem->text = str_replace("{jfusion_discuss $id}",$urlstring,$contentitem->text);
		    	        	 }
	    	        	}
	    	        	else
	    	        	{
		    	        	 //find any {jfusion_discuss...}
		    	        	 preg_match_all('/\{jfusion_discuss (.*)\}/U',$contentitem->text,$matches);

		    	        	 //find each thread by the id
		    	        	 foreach($matches[1] AS $id)
		    	        	 {
								//create the url string
		                        $urlstring_pre = JFusionFunction::createURL($forum->getThreadURL($id), $jname, $linkMode);
		                        $urlstring = '<div class="jfusiondiscusslink"><a href="'. $urlstring_pre . '" target="' . $new_window . '">' . $linkText . '</a></div>';

		                        //replace plugin with link
		                        $contentitem->text = str_replace("{jfusion_discuss $id}",$urlstring,$contentitem->text);
		    	        	 }
	    	        	}
				    }
    	        }
			}

			ob_end_clean();
            $result = true;
            return $result;
	    }
    }
?>