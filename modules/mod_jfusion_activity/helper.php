<?php
class modjfusionActivityHelper {
	function renderPluginAuto($jname, $config, $params) {
		//now check to see if the plugin is configured
	    $jdb =& JFactory::getDBO();
	    $query = 'SELECT status from #__jfusion WHERE name = ' . $jdb->Quote($jname);
	    $jdb->setQuery($query );
	    $config['selected_forums'] = $params->get('selected_forums_'.$jname);
	    
	    if ($jdb->loadResult() == 1) {
	
	        $forum = JFusionFactory::getForum($jname);
	        $db = JFusionFactory::getDatabase($jname);
	
	        if (JError::isError($db)) {
	            return JText::_('NO_DATABASE');
	        } else {
	            if ($config['forum_mode'] == 0 || empty($config['selected_forums'])) {
	                $selectedforumssql = "";
	            } else if (is_array($config['selected_forums'])) {
	                $selectedforumssql = implode(",", $config['selected_forums']);
	            } else {
	                $selectedforumssql = $config['selected_forums'];
	            }
	
	            //define some other JFusion specific parameters
	            $query = $forum->getQuery($selectedforumssql, $config['result_order'], 
	                                      $config['result_limit'], $config['display_limit']);
	
	            // load
	            $db->setQuery($query[$config['mode']][$config['lxt_type']]);
	            $result = $db->loadRowList();
	
	            $forum->filterForumList($result);
	            //reorder the keys for the for loop
	            $result = array_values($result);
	            
	            if ($config['debug']) {
	                $debug = "Query mode:" . $mode . '<br><br>SQL Query:' . $query[$mode][$lxt_type] .'<br><br>Results:<br>' ;
	                for ($i=0; $i<count($result); $i++) {
	                    $debug .= 'id:'. $result[$i][0] . '   username:'. $result[$i][1] . '  subject:'. $result[$i][3] . '<br>';
	                }
	                die($debug);
	            } else {
	
	                // fire
	                if (JError::isError($db)) {
	                    return $db->stderr();
	                } else if (!$result) {
	                    return JText::_('NO_POSTS');
	                } else {
						$output = "<ul>";
	                    // process result
	                    for ($i=0; $i<count($result); $i++) {
	                        $user = "";
	
							//cleanup the strings for output
			                $safeHtmlFilter = & JFilterInput::getInstance(null, null, 1, 1);
			                foreach ($result[$i] as $key => $value){
				            	$result[$i][$key] =  $safeHtmlFilter->clean($value, gettype($value));
				            }
	
				            //process user info
	                        if ($config['showuser']) {
	                            if ($config['userlink']) {
	                            	if($config['userlink_software']!='' && $config['userlink_software'] != 'jfusion' && $config["userlink_software"]!='custom') {
	                            		$user_url = JFusionFunction::getAltProfileURL($config['userlink_software'],$result[$i][1]);
	                            	} elseif ($config['userlink_software']=='custom' && !empty($config['userlink_custom'])) {
										$userlookup = JFusionFunction::lookupUser($jname,$result[$i][2],false);
										$user_url = $config['userlink_custom'].$userlookup->id;
									} else {
	                            		$user_url = false;
	                            	}
	                            	
	                            	if($user_url === false) {
	                            		$user_url = JFusionFunction::routeURL($forum->getProfileURL($result[$i][2], $result[$i][1]), $config['itemid']);
	                            	}
	                            	
									$user = '<a href="'. $user_url . '" target="' . $config['new_window'] . '">'.$result[$i][1].'</a>';
	                            } else {
									$user = $result[$i][1];
	                            }
	                            $user = " - <b>".$user."</b>";
	                        }
	
	                        //process date info
	                        if($config['showdate']) {
	                        	jimport('joomla.utilities.date');
	                        	$JDate =  new JDate($result[$i][4]);
	                        	$JDate->setOffset($config['tz_offset']);
	                        	$date = $JDate->toFormat($config['date_format']);
	                        } else {
	                        	$date = '';
	                        }
	
	                        //process subject or body info
	                        $subject = ($config['display_body'] == 0 && empty($result[$i][3]) ||
	                        $config['display_body'] == 1 && $config['mode'] == LCP ||
	                        $config['display_body'] == 2) ? $result[$i][5] : $result[$i][3];
	
	                        //make sure that a message is always shown
							if (empty($subject)) {
								$subject = JText::_('NO_SUBJECT');
							} elseif (strlen($subject) > $config['display_limit_subject']) {
								//we need to shorten the subject
								$subject = substr($subject,0,$config['display_limit_subject']) . '...';
							}
	
	                        //combine all info into an urlstring
	                        if ($config['linktype'] == LINKPOST) {
								$urlstring_pre = JFusionFunction::routeURL($forum->getPostURL($result[$i][6], $result[$i][0]), $config['itemid']);
	    	                    $urlstring = '<a href="'. $urlstring_pre . '" target="' . $config['new_window'] . '">'. $subject.'</a>';
	                        } else {
	                        	$urlstring_pre = JFusionFunction::routeURL($forum->getThreadURL($result[$i][0]), $config['itemid']);
	                        	$urlstring = '<a href="'. $urlstring_pre . '" target="' . $config['new_window'] . '">' .$subject.'</a>';
	                        }
	
	                        //put it all together for output
	                        $output .= '<li>'. $urlstring . '<b>'.$user.'</b>'.$date.'</li>';
	
	                    }
	                $output .= "</ul>";
					return $output;
	                }
	            }
	        }
	    } else {
	        return JText::_('NOT_CONFIGURED');
	    }
	}
	
	function renderPluginMode($jname, $config, $view, $pluginParam) {
		$forum = JFusionFactory::getForum($jname);
		if(method_exists($forum, "renderActivityModule")) {
			return $forum->renderActivityModule($config, $view, $pluginParam);
		}
		return JText::_('NOT IMPLEMENTED YET');
	}
}
?>
