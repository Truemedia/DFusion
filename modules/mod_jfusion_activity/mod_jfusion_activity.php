<?php
/**
* @package JFusion
* @subpackage Modules
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

//check if the JFusion component is installed
$model_file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php';
$factory_file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php';
if (file_exists($model_file) && file_exists($model_file)) {

/**
* require the JFusion libraries
*/
require_once($model_file);
require_once($factory_file);

defined('_DATE_FORMAT_LC2') or define('_DATE_FORMAT_LC2','%A, %d %B %Y %H:%M');
define('LAT', 0);
define('LCT', 1);
define('LCP', 2);
define('LINKTHREAD', 0);
define('LINKPOST', 1);

// configuration
$mode = intval($params->get('mode'));
$linktype = intval($params->get('linktype'));
$display_body = intval($params->get('display_body'));
$lxt_type = intval($params->get('linktype'));
$forum_mode = $params->get('forum_mode', 0);
$selected_forums = $params->get('selected_forums');
$display_limit = intval($params->get('display_limit'));
$display_limit_subject = intval($params->get('display_limit_subject'));
$result_limit = intval($params->get('result_limit'));
$date_format = $params->get('custom_date', _DATE_FORMAT_LC2);
$tz_offset = intval($params->get('tz_offset'));
$result_order = (intval($params->get('result_order'))) ? "DESC" : "ASC";
$showdate = intval($params->get('showdate'));
$showuser = intval($params->get('showuser'));
$userlink = intval($params->get('userlink'));
$view= $params->get('link_mode');

if ($params->get('new_window')) {
	$new_window = '_blank';
} else {
    $new_window = '_self';
}


$jname = $params->get('JFusionPlugins');
if ($jname) {
    //now check to see if the plugin is configured
    $jdb =& JFactory::getDBO();
    $query = 'SELECT status from #__jfusion WHERE name = ' . $jdb->quote($jname);
    $jdb->setQuery($query );
    if ($jdb->loadResult() == 3) {

        $forum = JFusionFactory::getForum($jname);
        $db = JFusionFactory::getDatabase($jname);

        if (JError::isError($db)) {
            echo  JText::_('NO_DATABASE');
        } else {

            if ($forum_mode == 0 || empty($selected_forums)) {
                $selectedforumssql = "";
            } else if (is_array($selected_forums)) {
                $selectedforumssql = implode(",", $selected_forums);
            } else {
                $selectedforumssql = $selected_forums;
            }

            //define some other JFusion specific parameters
            $query = $forum->getQuery($selectedforumssql, $result_order, $result_limit, $display_limit);

            // load
            $db->setQuery($query[$mode][$lxt_type]);
            $result = $db->loadRowList();

            if ($params->get('debug')) {
                $debug = "Query mode:" . $mode . '<br><br>SQL Query:' . $query[$mode][$lxt_type] .'<br><br>Results:<br>' ;
                for ($i=0; $i<count($result); $i++) {
                    $debug .= 'id:'. $result[$i][0] . '   username:'. $result[$i][1] . '  subject:'. $result[$i][3] . '<br>';
                }


                die($debug);
            } else {

                // fire
                if (JError::isError($db)) {
                    echo $db->stderr();
                } else if (!$result) {
                    echo JText::_('NO_POSTS');
                } else {

                    echo "<ul>";

                    // process result
                    for ($i=0; $i<count($result); $i++) {
                        $user = "";

						//cleanup the strings for output
		                $safeHtmlFilter = & JFilterInput::getInstance(null, null, 1, 1);
		                foreach ($result[$i] as $key => $value){
			            	$result[$i][$key] =  $safeHtmlFilter->clean($value, gettype($value));
			            }

			            //process user info
                        if ($showuser) {
                            if ($userlink) {
								$user_url = JFusionfunction::createURL($forum->getProfileURL($result[$i][2], $result[$i][1]), $jname, $view);
								$user = '<a href="'. $user_url . '" target="' . $new_window . '">'.$result[$i][1].'</a>'; 								$user = '<a href="'. $user_url . '" target="' . $new_window . '">'.$result[$i][1].'</a>';
                            } else {
								$user = $result[$i][1];
                            }
                            $user = " - <b>".$user."</b>";
                        }

                        //process date info
                        $date = $showdate ? " - ".strftime($date_format, $tz_offset * 3600 + ($result[$i][4])) : "";

                        //process subject or body info
                        $subject = ($display_body == 0 && empty($result[$i][3]) ||
                        $display_body == 1 && $mode == LCP ||
                        $display_body == 2) ? $result[$i][5] : $result[$i][3];

                        //make sure that a message is always shown
						if (empty($subject)) {
							$subject = JText::_('NO_SUBJECT');
						} elseif (strlen($subject) > $display_limit_subject) {
							//we need to shorten the subject
							$subject = substr($subject,0,$display_limit_subject) . '...';
						}

                        //combine all info into an urlstring
                        if ($linktype == LINKPOST) {
							$urlstring_pre = JFusionfunction::createURL($forum->getPostURL($result[$i][5], $result[$i][0]), $jname, $view);
    	                    $urlstring = '<a href="'. $urlstring_pre . '" target="' . $new_window . '">'. $subject.'</a>';
                        } else {
                        	$urlstring_pre = JFusionfunction::createURL($forum->getThreadURL($result[$i][0]), $jname, $view);
                        	$urlstring = '<a href="'. $urlstring_pre . '" target="' . $new_window . '">' .$subject.'</a>';
                        }

                        //put it all together for output
                        echo '<li>'. $urlstring . '<b>'.$user.'</b>'.$date.'</li>';

                    }

                    echo "</ul>";
                }
            }
        }
    } else {
        echo JText::_('NOT_CONFIGURED');
    }
} else {
    echo JText::_('NO_PLUGIN');
}
} else {
    echo JText::_('NO_COMPONENT');
}

