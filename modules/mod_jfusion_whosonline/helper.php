<?php
class modjfusionWhosOnlineHelper {
	function renderPluginAuto($jname, $config, $params) {
		//now check to see if the plugin is configured
	    $jdb =& JFactory::getDBO();
	    $query = 'SELECT status from #__jfusion WHERE name = ' . $jdb->Quote($jname);
	    $jdb->setQuery($query );
	
	    if ($jdb->loadResult() == 1) {
	        $forum = JFusionFactory::getForum($jname);
	        $public = JFusionFactory::getPublic($jname);
	        $db = JFusionFactory::getDatabase($jname);
	
	        if (JError::isError($db)) {
	            return JText::_('NO_DATABASE');
	        } else {
				$output = '';
	        	                		
	            //show the number of people online if set to do so
				if($config["showmode"]==0 || $config["showmode"]==2){
					$numGuests = $public->getNumberOnlineGuests();
					$numMembers = $public->getNumberOnlineMembers();
                	$output .= JText::_('WE_HAVE').'&nbsp;';
                	if($numGuests==1) {
                		$output .= JText::sprintf('GUEST','1');
                	} elseif($numGuests==0 || $numGuests>1) {
                		$output .= JText::sprintf('GUESTS',$numGuests) ;
                	}
                	
    				$output .= '&nbsp;' . JText::_('AND') . '&nbsp;';
                	
                    if($numMembers==1) {
                		$output .= JText::sprintf('MEMBER','1');
                	} elseif($numMembers==0 || $numMembers>1) {
                		$output .= JText::sprintf('MEMBERS',$numMembers);
                	}
                	$output .= '&nbsp;'.JText::_('ONLINE') . '<br />';
				}
				
		        $query = $public->getOnlineUserQuery();
	            $db->setQuery($query);
	            $result = $db->loadRowList();
	          
                if (JError::isError($db)) {
                    return $db->stderr();
                } else if (!$result) {
                    $output .= JText::_('NO_USERS_ONLINE');
                } else { 
					$output .= "<ul>";
                    // process result
					for ($i=0; $i<count($result); $i++) {
						if($jname=='joomla_int' || $jname=='joomla_ext') {
							$userlookup = new stdClass();
							$userlookup->id  = $result[$i][0];
						} else {
							$userlookup = JFusionFunction::lookupUser($jname,$result[$i][0],false, $result[$i][1]);
						}

						$name = ($config["name"]==1) ? $result[$i][2] : $result[$i][1];
						if ($config['userlink']) {
							if($config['userlink_software']!='' && $config['userlink_software'] != 'jfusion' && $config["userlink_software"]!='custom') {
								$user_url = JFusionFunction::getAltProfileURL($config['userlink_software'],$result[$i][1]);
							} elseif ($config['userlink_software']=='custom' && !empty($config['userlink_custom'])) {
								$user_url = $config['userlink_custom'].$userlookup->id;
							} else {
								$user_url = false;
							}  
  
							if($user_url === false  && $jname!='joomla_int' && $jname!='joomla_ext') {
  								$user_url = JFusionFunction::routeURL($forum->getProfileURL($result[$i][0], $result[$i][1]), $config['itemid']);
  							}
							 
  							$user = '<a href="'. $user_url . '">'.$name.'</a>';
  						} else {
  							$user = $name;
  						}
						
						$avatar = '';
			            if ($config['avatar']) {
		    	            // retrieve avatar
		    	            $avatarSrc = $config['avatar_software'];
		    	            if($jname!='joomla_int' && $jname!='joomla_ext' && ($avatarSrc=='' || $avatarSrc=='jfusion')) {
								$avatar = $forum->getAvatar($userlookup->userid);
		    	            } elseif(!empty($avatarSrc) && $avatarSrc!='jfusion') {
		    	            	$avatar = JFusionFunction::getAltAvatar($avatarSrc, $userlookup->id);
		    	            }
		    	            
							if(empty($avatar)) {
								$avatar = JFusionFunction::getJoomlaURL()."administrator".DS."components".DS."com_jfusion".DS."images".DS."noavatar.png";
							}
								
							$size = @getimagesize($avatar);
							$w = $size[0];
							$h = $size[1];
							if($size[0]>40) {
								$scale = min(40/$w, 53/$h);
								$w = floor($scale*$w);
								$h = floor($scale*$h);
							} elseif (empty($size)) {
								$h = 53;
								$w = 40;
							}
							$avatar = "<img style='vertical-align:middle'; src='$avatar' height='$h' width='$w' alt='$name' />";
			            } 
                   
                        //put it all together for output
                        $output .= '<li>'. $avatar . ' <b>'.$user.'</b></li>';
		            }
		            $output .= "</ul>";
				}
				
				return $output;
	        }
	    } else {
	        return JText::_('NOT_CONFIGURED');
	    }
	}
	
	function renderPluginMode($jname, $config, $view, $pluginParam) {
		$forum = JFusionFactory::getForum($jname);
		if(method_exists($forum, "renderWhosOnlineModule")) {
			return $forum->renderWhosOnlineModule($config, $view, $pluginParam);
		}
		return JText::_('NOT IMPLEMENTED YET');
	}
}
?>