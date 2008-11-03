<?php

/**
 * @package JFusion
 * @subpackage Models
 * @version 1.0.8.004
 * @author JFusion development team -- Henk Wevers
 * @copyright Copyright (C) 2008 JFusion -- Henk Wevers. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * load the JFusion framework
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');

/**
* HTML Form Parser
* This will extract all forms and his elements in an
* big assoc Array.
*
* @package HtmlFormParser
* @version $Id 1.0 -- mod HJW-4
* @author Peter Valicek <Sonny2@gmx.DE>
* @copyright 2004 Peter Valicek Peter Valicek <Sonny2@gmx.DE>: GPL-2
*
*
* Many modifications and bug repairs by Henk Wevers
*/

class JFusionCurlHtmlFormParser {

    var $html_data = '';
    var $_return = array();
    var $_counter = '';
    var $button_counter = '';
    var $_unique_id = '';

    function JFusionCurlHtmlFormParser( $html_data ) {
        if ( is_array($html_data) ) {
            $this->html_data = join('', $html_data);
        } else {
            $this->html_data = $html_data;
        }
        $this->_return = array();
        $this->_counter = 0;
        $this->button_counter = 0;
        $this->_unique_id = md5(time());
    }

    function parseForms() {
        if ( preg_match_all("/<form.*>.+<\/form>/isU", $this->html_data, $forms) ) {

            foreach ( $forms[0] as $form ) {

                $this->button_counter = 0;

                #form details
                preg_match("/<form.*name=[\"']?([\w\s-]*)[\"']?[\s>]/i", $form, $form_name);
                $this->_return[$this->_counter]['form_data']['name'] = preg_replace("/[\"'<>]/", "", $form_name[1]);
                preg_match("/<form.*action=(\"([^\"]*)\"|'([^']*)'|[^>\s]*)([^>]*)?>/is", $form, $action);
                $this->_return[$this->_counter]['form_data']['action'] = preg_replace("/[\"'<>]/", "", $action[1]);
                preg_match("/<form.*method=[\"']?([\w\s]*)[\"']?[\s>]/i", $form, $method);
                $this->_return[$this->_counter]['form_data']['method'] = preg_replace("/[\"'<>]/", "", $method[1]);
                preg_match("/<form.*enctype=(\"([^\"]*)\"|'([^']*)'|[^>\s]*)([^>]*)?>/is", $form, $enctype);
                $this->_return[$this->_counter]['form_data']['enctype'] = preg_replace("/[\"'<>]/", "", $enctype[1]);
                preg_match("/<form.*id=[\"']?([\w\s-]*)[\"']?[\s>]/i", $form, $id);
                $this->_return[$this->_counter]['form_data']['id'] = preg_replace("/[\"'<>]/", "", $id[1]);

                # form elements: input type = hidden
                if ( preg_match_all("/<input.*type=[\"']?hidden[\"']?.*>/iU", $form, $hiddens) ) {                       //MODS!

                    foreach ( $hiddens[0] as $hidden ) {
                        $this->_return[$this->_counter]['form_elements'][$this->_getName($hidden)] = array(
                                                                                            'type'    => 'hidden',
                                                                                            'value'    => $this->_getValue($hidden)
                                                                                            );
                    }
                }

                # form elements: input type = text
                if ( preg_match_all("/<input.*type=[\"']?text[\"']?.*>/iU", $form, $texts) ) {
                    foreach ( $texts[0] as $text ) {
                        $this->_return[$this->_counter]['form_elements'][$this->_getName($text)] = array(
                                                                                            'type'    => 'text',
                                                                                            'value'   => $this->_getValue($text),
                                                                                            'id'      => $this->_getId($text),
                                                                                            'class'   => $this->_getClass($text)
                                                                                            );
                    }
                }

                # form elements: input type = password
                if ( preg_match_all("/<input.*type=[\"']?password[\"']?.*>/iU", $form, $passwords) ) {
                    foreach ( $passwords[0] as $password ) {
                        $this->_return[$this->_counter]['form_elements'][$this->_getName($password)] = array(
                                                                                            'type'    => 'password',
                                                                                            'value'    => $this->_getValue($password)
                                                                                            );
                    }
                }

                # form elements: textarea
                if ( preg_match_all("/<textarea.*>.*<\/textarea>/isU", $form, $textareas) ) {
                    foreach ( $textareas[0] as $textarea ) {
                        preg_match("/<textarea.*>(.*)<\/textarea>/isU", $textarea, $textarea_value);
                        $this->_return[$this->_counter]['form_elements'][$this->_getName($textarea)] = array(
                                                                                            'type'    => 'textarea',
                                                                                            'value'    => $textarea_value[1]
                                                                                            );
                    }
                }

                # form elements: input type = checkbox
                if ( preg_match_all("/<input.*type=[\"']?checkbox[\"']?.*>/iU", $form, $checkboxes) ) {
                    foreach ( $checkboxes[0] as $checkbox ) {
                        if ( preg_match("/checked/i", $checkbox) ) {
                            $this->_return[$this->_counter]['form_elements'][$this->_getName($checkbox)] = array(
                                                                                            'type'    => 'checkbox',
                                                                                            'value'    => 'on'
                                                                                            );
                        } else {
                            $this->_return[$this->_counter]['form_elements'][$this->_getName($checkbox)] = array(
                                                                                            'type'    => 'checkbox',
                                                                                            'value'    => ''
                                                                                            );
                        }
                    }
                }

                # form elements: input type = radio
                if ( preg_match_all("/<input.*type=[\"']?radio[\"']?.*>/iU", $form, $radios) ) {
                    foreach ( $radios[0] as $radio ) {
                        if ( preg_match("/checked/i", $radio) ) {
                            $this->_return[$this->_counter]['form_elements'][$this->_getName($radio)] = array(
                                                                                            'type'    => 'radio',
                                                                                            'value'    => $this->_getValue($radio)
                                                                                            );
                        }
                    }
                }

                 # form elements: input type = submit
                if ( preg_match_all("/<input.*type=[\"']?submit[\"']?.*>/iU", $form, $submits) ) {
                    foreach ( $submits[0] as $submit ) {
                        $this->_return[$this->_counter]['buttons'][$this->button_counter] = array(
                                                                                            'type'    => 'submit',
                                                                                            'name'    => $this->_getName($submit),
                                                                                            'value'    => $this->_getValue($submit)
                                                                                            );
                        $this->button_counter++;
                    }
                }

                # form elements: input type = button
                if ( preg_match_all("/<input.*type=[\"']?button[\"']?.*>/iU", $form, $buttons) ) {
                    foreach ( $buttons[0] as $button ) {
                        $this->_return[$this->_counter]['buttons'][$this->button_counter] = array(
                                                                                            'type'    => 'button',
                                                                                            'name'    => $this->_getName($button),
                                                                                            'value'    => $this->_getValue($button)
                                                                                            );
                        $this->button_counter++;
                    }
                }

                # form elements: input type = reset
                if ( preg_match_all("/<input.*type=[\"']?reset[\"']?.*>/iU", $form, $resets) ) {
                    foreach ( $resets[0] as $reset ) {
                        $this->_return[$this->_counter]['buttons'][$this->button_counter] = array(
                                                                                            'type'    => 'reset',
                                                                                            'name'    => $this->_getName($reset),
                                                                                            'value'    => $this->_getValue($reset)
                                                                                            );
                        $this->button_counter++;
                    }
                }

                # form elements: input type = image
                if ( preg_match_all("/<input.*type=[\"']?image[\"']?.*>/iU", $form, $images) ) {
                    foreach ( $images[0] as $image ) {
                        $this->_return[$this->_counter]['buttons'][$this->button_counter] = array(
                                                                                            'type'    => 'reset',
                                                                                            'name'    => $this->_getName($image),
                                                                                            'value'    => $this->_getValue($image)
                                                                                            );
                        $this->button_counter++;
                    }
                }

                # input type=select entries
                # Here I have to go on step around to grep at first all select names and then
                # the content. Seems not to work in an other way
                if ( preg_match_all("/<select.*>.+<\/select>/isU", $form, $selects) ) {
                    foreach ( $selects[0] as $select ) {
                        if ( preg_match_all("/<option.*>.+<\/option>/isU", $select, $all_options) ) {
                            foreach ( $all_options[0] as $option ) {
                                if ( preg_match("/selected/i", $option) ) {
                                    if ( preg_match("/value=[\"'](.*)[\"']\s/iU", $option, $option_value) ) {
                                        $option_value = $option_value[1];
                                        $found_selected = 1;
                                    } else {
                                        preg_match("/<option.*>(.*)<\/option>/isU", $option, $option_value);
                                        $option_value = $option_value[1];
                                        $found_selected = 1;
                                    }
                                }
                            }
                            if ( !isset($found_selected) ) {
                                if ( preg_match("/value=[\"'](.*)[\"']/iU", $all_options[0][0], $option_value) ) {
                                    $option_value = $option_value[1];
                                } else {
                                    preg_match("/<option>(.*)<\/option>/iU", $all_options[0][0], $option_value);
                                    $option_value = $option_value[1];
                                }
                            } else {
                                unset($found_selected);
                            }
                            $this->_return[$this->_counter]['form_elements'][$this->_getName($select)] = array(
                                                                                                    'type'    => 'select',
                                                                                                    'value'    => trim($option_value)
                                                                                                    );
                        }
                    }
                }

                # Update the form counter if we have more then 1 form in the HTML table
                $this->_counter++;
            }
        }
        return $this->_return;
    }

    function _getName( $string ) {
        if (preg_match("/name=(\"([^\"]*)\"|'([^']*)'|[^>\s]*)([^>]*)?>/is", $string, $match) ) {
           #preg_match("/name=[\"']?([\w\s]*)[\"']?[\s>]/i", $string, $match) ) { -- did not work as expected
            $val_match = preg_replace("/\"'/", "", trim($match[1]));
            unset($string);
            return trim($val_match,'"');
        }
    }

    function _getValue( $string ) {
        if ( preg_match("/value=(\"([^\"]*)\"|'([^']*)'|[^>\s]*)([^>]*)?>/is", $string, $match) ) {
            $val_match = trim($match[1]);

            if ( strstr($val_match, '"') ) {
                $val_match = str_replace('"', '', $val_match);
            }
            unset($string);
            return $val_match;
        }
    }

    function _getId( $string ) {
        if (preg_match("/id=(\"([^\"]*)\"|'([^']*)'|[^>\s]*)([^>]*)?>/is", $string, $match) ) {
           #preg_match("/name=[\"']?([\w\s]*)[\"']?[\s>]/i", $string, $match) ) { -- did not work as expected
            $val_match = preg_replace("/\"'/", "", trim($match[1]));
            unset($string);
            return $val_match;
        }
    }

    function _getClass( $string ) {
        if (preg_match("/class=(\"([^\"]*)\"|'([^']*)'|[^>\s]*)([^>]*)?>/is", $string, $match) ) {
           #preg_match("/name=[\"']?([\w\s]*)[\"']?[\s>]/i", $string, $match) ) { -- did not work as expected
            $val_match = preg_replace("/\"'/", "", trim($match[1]));
            unset($string);
            return $val_match;
        }
    }

}




/**
* Singleton static only class that creates instances for each specific JFusion plugin.
* @package JFusion
*/

class JFusionCurl{

   /*
    * function read_header
    * Basic  code was found on Svetlozar Petrovs website http://svetlozar.net/page/free-code.html.
    * The code is free to use and similar code can be found on other places on the net.
    */

  function read_header($ch, $string){
        global $location;
        global $cookiearr;
        global $ch;
        global $cookies_to_set;
        global $cookies_to_set_index;

        $length = strlen($string);
        if(!strncmp($string, "Location:", 9)){
          $location = trim(substr($string, 9, -1));
        }
        if(!strncmp($string, "Set-Cookie:", 11)){
            header($string,false);
            $cookiestr = trim(substr($string, 11, -1));
            $cookie = explode(';', $cookiestr);
            $cookies_to_set[$cookies_to_set_index] = $cookie;
            $cookies_to_set_index++;
            $cookie = explode('=', $cookie[0]);
            $cookiename = trim(array_shift($cookie));
            $cookiearr[$cookiename] = trim(implode('=', $cookie));
        }

        $cookie = "";
        if(trim($string) == ""){                                                            // MODS!
          foreach ($cookiearr as $key=>$value){
                $cookie .= "$key=$value ";
            }
          curl_setopt($ch, CURLOPT_COOKIE, $cookie);    //echo $cookie."<br>";
        }
        return $length;
    }
    /*
    * function parseURL
    * out[0] = full url
    * out[1] = scheme or '' if no scheme was found
    * out[2] = username or '' if no auth username was found
    * out[3] = password or '' if no auth password was found
    * out[4] = domain name or '' if no domain name was found
    * out[5] = port number or '' if no port number was found
    * out[6] = path or '' if no path was found
    * out[7] = query or '' if no query was found
    * out[8] = fragment or '' if no fragment was found
    */

    function parseUrl ($url){
      $r = '!(?:(\w+)://)?(?:(\w+)\:(\w+)@)?([^/:]+)?';
      $r .= '(?:\:(\d*))?([^#?]+)?(?:\?([^#]+))?(?:#(.+$))?!i';
        preg_match ( $r, $url, $out );
        return $out;
    }

    function parsecookies($cookielines){
      $line=array();
        $cookies=array();
        foreach ($cookielines as $line){
            $cdata = array();
            $data = array();
            foreach( $line as $data ) {
                $cinfo = explode( '=', $data );
                $cinfo[0] = trim( $cinfo[0] );
                if( $cinfo[0] == 'expires' ) $cinfo[1] = strtotime( $cinfo[1] );
                if( $cinfo[0] == 'secure' ) $cinfo[1] = "true";
                if( in_array( $cinfo[0], array( 'domain', 'expires', 'path', 'secure', 'comment' ) ) ) {
                    $cdata[trim( $cinfo[0] )] = $cinfo[1];
                }
                else {
                    $cdata['value']['key'] = $cinfo[0];
                    $cdata['value']['value'] = $cinfo[1];
                }
            }
            $cookies[] = $cdata;
        }
        return $cookies;
    }


    function setmycookies($mycookies_to_set){
        $cookies=array();
        $cookies=JFusionCurl::parsecookies($mycookies_to_set);
        foreach ($cookies as $cookie){
            global $hnd;
            $name="";
            $value="";
            $expires_time=time()+30*60;
            $cookiepath="";
            $cookiedomain="";
            $httponly="true";
            if (isset($cookie[value][key]))     {$name= $cookie[value][key];}
            if (isset($cookie[value][value]))   {$value=$cookie[value][value];}
            if (isset($cookie[expires]))        {$expires_time=$cookie[expires];}
            if (isset($cookie[path]))           {$cookiepath=$cookie[path];}
            if (isset($cookie[domain]))         {$cookiedomain=$cookie[domain];}

            setcookie($name, urldecode($value),$expires_time,$cookiepath,$cookiedomain,0,1);
            // fwrite($hnd, "$name--$value--$expires_time--$cookiepath--$cookiedomain\n");
        }
    }

  /*
   * function RemoteLogin
   * Smart function to programatically login to an JFusion integration
   * Will determine what to post (including, optionally, hidden form inputs) and what cookies to set.
   * Will then login.
   * In addition to username and password the function only needs an URL to a page with a loginform
   * and the ID of the loginform.
   * Including button information and hidden input posts is optionally
   */

    function RemoteLogin($post_url,$formid,$username,$password,$integrationtype,$relpath=false,$hidden=false,$buttons=false,$override=NULL){
        $status=array();
        $status['debug']='';
        global $ch;
        global $cookiearr;
        global $cookies_to_set;
        global $cookies_to_set_index;
        $tmpurl = array();
        $cookies_to_set=array();
        $cookies_to_set_index=0;

        # prevent usererror by not supplying trailing backslash
        if (!(substr($post_url,-1) == "/")) {
            $post_url = $post_url."/";
        }

        # read the login page
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$post_url);
        curl_setopt($ch, CURLOPT_REFERER, "");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'JFusionCurl::read_header');
        $remotedata = curl_exec($ch);

        if ($integrationtype ==1){
            curl_close($ch);
        }
        $cookies_to_set_index=0;
        JFusionCurl::setmycookies($cookies_to_set);

        #find out if we have the form with the name/id specified
        $parser = new JFusionCurlHtmlFormParser( $remotedata);
        $result = $parser->parseForms();

        $frmcount = count($result);
        $myfrm = -1;
        $i = 0;
        do {
          if (isset($result[$i]['form_data']['name'])){
             if ($result[$i]['form_data']['name']==$formid){
               $myfrm = $i;
               break;
             }
          }
          if (isset($result[$i]['form_data']['id'])){
             if ($result[$i]['form_data']['id']==$formid){
               $myfrm = $i;
               break;
             }
          }
          $i +=1;
        } while ($i<$frmcount);

        if ($myfrm == -1) {
            $i = 0;
            $helpthem = 'I found';
            do {
              $helpthem = $helpthem.' -- Name='.$result[$i]['form_data']['name'].'&ID='.$result[$i]['form_data']['id'];
              $i +=1;
            } while ($i<$frmcount);
            $status['error']= 'Could not find a valid loginform, check plugin parameters! '.$helpthem;              //MODS!!
            return $status;
        }

        // by now we have the specified  login form, lets get the data needed to login
        // we went to all this trouble to get to the hidden input entries.
        // The stuff is there to enhance security and is, yes, hidden
        $form_action= $result[$myfrm]['form_data']['action'];
        $form_method= $result[$myfrm]['form_data']['method'];
        $elements_keys  = array_keys($result[$myfrm]['form_elements']);
        $elements_values= array_values($result[$myfrm]['form_elements']);
        $elements_count  = count($result[$myfrm]['form_elements']);

      // override keys/values from hidden inputs
      // the $override is an array of keys/values that override existing keys/values

      if ($override){
           $overridearr = array();
           $newhidden = array();
           $lines = array();
           $line=array();
           $lines = explode(',',$override);
           foreach ($lines as $line) {
              $cinfo = explode ('=',$line);
              $overridearr[$cinfo[0]][value] = $cinfo[1];
              $overridearr[$cinfo[0]][type] = 'hidden';
           }
           $newhidden= array_merge($result[$myfrm]['form_elements'],$overridearr);
           $elements_keys  = array_keys($newhidden);
           $elements_values= array_values($newhidden);
           $elements_count  = count($newhidden);
      }

        // now construct the action parameter
        // first make it not use ssl (TO DO, cURL ssl support)

        $form_action = str_replace ('https://','http://',$form_action);

        // websites provide a full url (easy), or a relative url to the post directory. We have to add in the
        // domain if not present. We can extract the domain from the post_url passed to this function

        if ((strpos($form_action,'http://') === false) && !$relpath) {
           $tmpurl = $this->parseUrl($post_url);                       // MODS!
           $form_action = 'http://'.$tmpurl[4].$form_action;
          //$form_action = $post_url.$form_action;
        }
        if ($relpath){$form_action = $post_url.$form_action;}
        if ($username != '') { //if the username is empty we have a logout form, so just post the hidden parameters        // MODS!

           // now try to find the fieldnames for the username and password entries
           // as far as I have seen, matching user or name will do together with pass:
             $input_username_name="";
          for ($i = 0; $i <= $elements_count-1; $i++) {
               if (strpos(strtolower($elements_keys[$i]),'user')!==false){$input_username_name=$elements_keys[$i];break;}
                if (strpos(strtolower($elements_keys[$i]),'name')!==false){$input_username_name=$elements_keys[$i];break;}
             }
            if ($input_username_name==""){
                $status['error']='Could not find a valid namefield in the login form, contact the pluginauthor, or adjust your loginform';
                return $status;
            }

            $input_password_name="";
            for ($i = 0; $i <= $elements_count-1; $i++) {
                if (strpos(strtolower($elements_keys[$i]),'pass')!==false){$input_password_name=$elements_keys[$i];break;}
            }

            if ($input_password_name==""){
                $status['error']='Could not find a valid passwordfield in the login form, contact pluginauthor, or adjust your loginform';
                return $status;
            }
       }
        // we now set the submit parameters. These are:
        // all form_elements name=value combinations with value != '' and type hidden
        $strParameters="";
        if ($hidden) {
             for ($i = 0; $i <= $elements_count-1; $i++) {
              if (($elements_values[$i] ['value'] != '')&& ($elements_values[$i] ['type'] == 'hidden')){
                  $strParameters .= '&'.$elements_keys[$i].'='.urlencode($elements_values[$i] ['value']);
                }
           }
        }

        // have to figure out how to handle buttons exactly, seems not important anyway. leave it for now
        if ($buttons){
             if (isset($result[$myfrm] [buttons][0]['type'])) {
                 if ($result[$myfrm] [buttons][0]['type'] =='submit'){
                   $strParameters .= '&'.'submit'.'='.urlencode($result[$myfrm] [buttons][0]['value']);
                 }
             }
        }

        $post_params = $input_username_name."=".urlencode($username)."&".$input_password_name."=".urlencode($password);

        // finally submit the login form:
        if ($integrationtype == 1){
             $ch = curl_init();
             curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
             curl_setopt($ch, CURLOPT_REFERER, "");
             curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
             curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'JFusionCurl::read_header');
        }
        curl_setopt($ch, CURLOPT_URL,$form_action);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params.$strParameters);
        $remotedata = curl_exec($ch);
        curl_close($ch);

        #we have to set the cookies now
        JFusionCurl::setmycookies($cookies_to_set);
        $cookies_to_set_index=0;
        return $status;
     }

    /*
      *   @function RemoteLogout
      *   @returns $status
      */

  function RemoteLogout($post_url) {
      $status=array();
        $status['debug']='';
        global $ch;
        global $cookiearr;
        global $cookies_to_set;
        global $cookies_to_set_index;
        $tmpurl = array();
        $cookies_to_set=array();
        $cookies_to_set_index=0;

        # prevent usererror by not supplying trailing backslash
        if (!(substr($post_url,-1) == "/")) {
            $post_url = $post_url."/";
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_REFERER, "");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'JFusionCurl::read_header');
        curl_setopt($ch, CURLOPT_URL,$post_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $remotedata = curl_exec($ch);
        curl_close($ch);
        #we have to set the cookies now
        JFusionCurl::setmycookies($cookies_to_set);
        $cookies_to_set_index=0;
        return $status;
     }
  }