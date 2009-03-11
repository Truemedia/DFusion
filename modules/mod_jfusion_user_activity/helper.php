<?php
class modjfusionUserActivityHelper {
	function renderPluginAuto($jname, $config, $params) {
		//now check to see if the plugin is configured
	    $jdb =& JFactory::getDBO();
	    $query = 'SELECT status from #__jfusion WHERE name = ' . $jdb->Quote($jname);
	    $jdb->setQuery($query );
	
	    if ($jdb->loadResult() == 1) {
	        $forum =& JFusionFactory::getForum($jname);
	        $db =& JFusionFactory::getDatabase($jname);
			$joomlaUser =& JFactory::getUser();
			
	        if (JError::isError($db)) {
	            return JText::_('NO_DATABASE');
	        } else {
				if(!$joomlaUser->guest) {
				
	       			$PluginUser =& JFusionFactory::getUser($jname);
	       			$userinfo = $PluginUser->getUser($joomlaUser->username);  

	       			//get the avatar of the logged in user
		            if ($config['avatar']) {
		            	$output = ($config["avatar_location"]=='left') ? "<div style='height:{$config["avatar_height"]}px; text-align:{$config['alignment']};'>\n<div style='float:left; margin-right:5px;'>" : "<div style='text-{$config['alignment']};'>\n<div>";

		       			// retrieve avatar
	    	            $avatarSrc =& $config['avatar_software'];
	    	            if($jname!='joomla_int' && $jname!='joomla_ext' && ($avatarSrc=='' || $avatarSrc=='jfusion')) {   	
	    	            	$avatar = $forum->getAvatar($userinfo->userid);
	    	            } elseif(!empty($avatarSrc) && $avatarSrc!='jfusion') {
	    	            	$avatar = JFusionFunction::getAltAvatar($avatarSrc, $joomlaUser->id);
	    	            }
	    	            
						if(empty($avatar)) {
							$avatar = JFusionFunction::getJoomlaURL()."administrator".DS."components".DS."com_jfusion".DS."images".DS."noavatar.png";
						}
							
						$avatar = "<img style='vertical-align:middle'; src='$avatar' height='{$config['avatar_height']}' width='{$config['avatar_width']}' alt='$name' />";
						
						$output .= $avatar."</div>\n";
		            } else {
		            	$output = "<div style='text-align:{$config['alignment']};'>\n";
		            }
		            
		            //get the PM count of the logged in user
		            if($config["pmcount"]) {
		            	$output .= "<div>\n";
		            	
		            	$url_pm = JFusionFunction::routeURL($forum->getPrivateMessageURL(),$config['itemid']);
		            	$pmcount = $forum->getPrivateMessageCounts($userinfo->userid);
						$pm  .= JText::_('PM_START');
						$pm .= ' <a href="'.$url_pm.'">'.JText::sprintf('PM_LINK', $pmcount["total"]).'</a>';
	    				$pm .= JText::sprintf('PM_END', $pmcount["unread"]);
	    				
	    				$output .= $pm . "</div>\n";
		            }
		            
		            //get the new message url
		            if($config['viewnewmessages']) {
		            	$output .= "<div>";
						$url_viewnewmessages = JFusionFunction::routeURL($forum->getViewNewMessagesURL(), $config['itemid']);

						$output .= "<a href='$url_viewnewmessages' target='{$config['new_window']}'>" . JText::_('VIEW_NEW_TOPICS') . "</a></div>\n"; 
		            }
		            
				} else {
					$output .= $config['login_msg'];
				}
				$output .= "</div>\n";
				return $output;
	        }
	    } else {
	        return JText::_('NOT_CONFIGURED');
	    }
	}
	
	function renderPluginMode($jname, $config, $view, $pluginParam) {
		$forum = JFusionFactory::getForum($jname);
		if(method_exists($forum, "renderUserActivityModule")) {
			return $forum->renderUserActivityModule($config, $view, $pluginParam);
		}
		return JText::_('NOT_IMPLEMENTED_YET');
	}
}
?>