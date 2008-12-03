<?php

/**
* @package JFusion_magento
* @version 1.0.8-008
* @author Henk Wevers
* @copyright Copyright (C) 2008 JFusion.--Henk Wevers All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/


  /* reminder for jFusion abstract user definition
     * gets the userinfo from the JFusion integrated software. Definition of object:
     * $userinfo->userid
     * $userinfo->name
     * $userinfo->username
     * $userinfo->email
     * $userinfo->password (encrypted password)
     * $userinfo->password_salt (salt used to encrypt password)
     * $userinfo->block (0 if allowed to access site, 1 if user access is blocked)
     * $userinfo->registerdate
     * $userinfo->lastvisitdate
     * @param string $username username
     * @return object userinfo Object containing the user information
     */



// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* load the Abstract User Class
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.curl.php');

/**
* @package JFusion_myplugin
*/
class JFusionUser_magento extends JFusionUser{
    /* Magento does not have usernames. The user is identified by an 'identity_id' that is found through
     *  the users e-mail address.
     *  To make it even more difficult for us, there is no simple tablecontaining all userdata, but
     *  the userdata is arranged in tables for different variable types.
     *  User attributes are identified by fixed attribute ID's in these tables
      *
     *  The usertables are:
     *  customer_entity
     *  customer_address_entity
     *  customer_address_entity_datetime
     *  customer_address_entity_decimal
     *  customer_address_entity_int
     *  customer_address_entity_text
     *  customer_address_entity_varchar
     *  customer_entity_datetime
     *  customer_entity_decimal
     *  customer_entity_int
     *  customer_entity_text
     *  customer_entity_varchar
    *
     */


  /*
   * Returns an array of Magento entity types
   */
  function getMagentoEntityTypeID($eav_entity_code){
    static $eav_entity_types;
        if (!isset($eav_entity_types)){
            $db = JFusionFactory::getDatabase($this->getJname());
            $db->setQuery("SELECT entity_type_id,entity_type_code FROM #__eav_entity_type");
        if ($db->_errorNum != 0){
            return false;
        }
        $result =  $db->loadObjectList();
          for ($i=0;$i< count($result); $i++) {
               $eav_entity_types[$result[$i]->entity_type_code]=$result[$i]->entity_type_id;
            }
        }
        return $eav_entity_types[$eav_entity_code];
  }

     /*
      * Returns a Magento UserObject for the current installation
      * (see eav_entity_type)
      * please note, this is all my coding, so please report bugs to me, not to the Magento developers
      * henk wevers
    */
  function getMagentoDataObjectRaw($entity_type_code){
    static $eav_attributes;
    if (!isset($eav_attributes[$entity_type_code])){
         // first get the entity_type_id to access the attribute table
      $entity_type_id = $this->getMagentoEntityTypeID('customer');
         $db = JFusionFactory::getDatabase($this->getJname());

         // Get a database object
         $db->setQuery('SELECT attribute_id, attribute_code, backend_type FROM #__eav_attribute WHERE entity_type_id ='.$entity_type_id);
      if ($db->_errorNum != 0){
           return false;
      }
         //getting the results
         $result =  $db->loadObjectList();
         for ($i=0;$i< count($result); $i++) {
            $db->setQuery('SELECT attribute_id, attribute_code, backend_type FROM #__eav_attribute WHERE entity_type_id ='.$entity_type_id);
             $eav_attributes[$entity_type_code][$i]['attribute_code']=$result[$i]->attribute_code;
             $eav_attributes[$entity_type_code][$i]['attribute_id']=$result[$i]->attribute_id;
             $eav_attributes[$entity_type_code][$i]['backend_type']=$result[$i]->backend_type;
          if ($db->_errorNum != 0){
              return false;
          }
        }
    }
    return $eav_attributes[$entity_type_code];
  }

  function getMagentoDataObject($entity_type_code){
    $result = $this->getMagentoDataObjectRaw($entity_type_code);
    $dataObject = array();
    for ($i=0;$i< count($result); $i++) {
        $dataObject[$result[$i]['attribute_code']]['attribute_id']=$result[$i]['attribute_id'];
        $dataObject[$result[$i]['attribute_code']]['backend_type']=$result[$i]['backend_type'];
    }
    return $dataObject;
  }

  function fillMagentoDataObject($entity_type_code,$entity_id,$entity_type_id){
    $result = array();
    $result = $this->getMagentoDataObjectRaw($entity_type_code);
    if (!$result){
        return false;
    }

    // walk through the array and fill the object requested
    // TODO This can be smarter by reading types at once and put the data them in the right place
    // for now I'm trying to get this working. optimising comes next
    $filled_object = array();
    $db = JFusionFactory::getDatabase($this->getJname());
    for ($i=0;$i< count($result); $i++) {
        if ($result[$i]['backend_type'] == 'static'){
          $query = 'SELECT '. $result[$i]['attribute_code'].' FROM #__'.$entity_type_code.'_entity'.
            ' WHERE entity_type_id ='.$entity_type_id.
            ' AND entity_id ='. $entity_id;
          $db->setQuery($query);
          if ($db->_errorNum != 0){
              return false;
          }
      } else {
          $query = 'SELECT value FROM #__'.$entity_type_code.'_entity_'.$result[$i]['backend_type'].
            ' WHERE entity_type_id ='.$entity_type_id.
            ' AND attribute_id ='.$result[$i]['attribute_id'].
            ' AND entity_id ='. $entity_id;
          $db->setQuery($query);
          if ($db->_errorNum != 0){
              return false;
        }
        }
        $filled_object[$result[$i]['attribute_code']]['value']=$db->loadResult();
        $filled_object[$result[$i]['attribute_code']]['attribute_id']=$result[$i]['attribute_id'];
        $filled_object[$result[$i]['attribute_code']]['backend_type']=$result[$i]['backend_type'];
    }
    return $filled_object;
  }

    function &getUser($username){

       // get the user from Magento
        $db = JFusionFactory::getDatabase($this->getJname());
        $username = $this->filterUsername($username);

        // Get the user id
        $query = "SELECT entity_id FROM #__customer_entity WHERE email =" . $db->Quote($username );
        $db->setQuery($query);
        $entity = (int) $db->loadResult();

        // check if we have found the user, if not return failure
        if (!$entity) {
            return false;
        }
      // Return a Magento customer array
      $magento_user = $this->fillMagentoDataObject("customer",$entity,1);
      If (!$magento_user){
          return false;
      }
        $instance = array();
        $instance['userid'] = $entity;
        $instance['username'] = $magento_user['email']['value'];
        $name = $magento_user['firstname']['value'];
        if ($magento_user['middlename']['value']) {$name = $name . ' '.$magento_user['middlename']['value'];}
        if ($magento_user['lastname']['value']) {$name = $name . ' '.$magento_user['lastname']['value'];}
        $instance['name'] = $name;
        $instance['email'] = $magento_user['email']['value'];
        $password = $magento_user['password_hash']['value'];
        $hashArr = explode(':', $password);
        $instance['password'] = $hashArr[0];
        $instance['password_salt'] = $hashArr[1];
        $instance['activation'] = '';
        if ($magento_user['confirmation']['value']){$instance['activation'] = $magento_user['confirmation']['value']; }
        $instance['registerDate'] = $magento_user['created_at']['value'];
        $instance['lastvisitDate'] = $magento_user['updated_at']['value'];
        if ($instance['activation']) {
            $instance['block'] = 1;
        } else {
        	$instance['block'] = 0;
        }
        return (object) $instance;
    }

    function updateUser($userinfo, $overwrite) {
         // Initialise some variables
        $db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
        $update_activation = $params->get('update_activation');

        $status = array();
        $status['debug'] = array();

        //find out if the user already exists
        $existinguser = $this->getUser($userinfo->email);

        if (!empty($existinguser)) {
            //a matching user has been found

            if (!empty($userinfo->password_clear)) {
            	//we can update the password but first find out if we need to
              	if($existinguser->password_salt) {
                  	$existingpassword = md5($existinguser->password_salt.$userinfo->password_clear);
              	} else {
                  	$existingpassword = md5($userinfo->password_clear);
              	}
              	if ($existingpassword != $existinguser->password) {
	              	$this->updatePassword($userinfo, $existinguser, $status);
              	} else {
                  	$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ':' .  JText::_('PASSWORD_VALID');
              	}
              } else {
                	$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ': ' . JText::_('PASSWORD_UNAVAILABLE');
              }


            //check the activation status


            if ($existinguser->activation != $userinfo->activation) {
              if ($update_activation || $overwrite) {
                    if ($userinfo->activation) {
                      //inactiva the user
                      $this->inactivateUser($userinfo, $existinguser, $status);
                    } else {
                        //activate the user
                       $this->activateUser($userinfo, $existinguser, $status);
                    }
                } else {
                  //return a debug to inform we skiped this step
                  $status['debug'][] = JText::_('SKIPPED_ACTIVATION_UPDATE') . ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
                }
            }

            $status['userinfo'] = $existinguser;
            if (empty($status['error'])) {
                $status['action'] = 'updated';
            }
            return $status;

        } else {
            //we need to create a new user
            $this->createUser($userinfo, $status);
            if (empty($status['error'])) {
                $status['action'] = 'created';
            }
            return $status;
        }
    }

    function getJname(){
        return 'magento';
    }

    function destroySession($userinfo, $options){
        $status['error'] = '';
        $params = JFusionFactory::getParams($this->getJname());
        $source_url = $params->get('source_url');
        $cookiedomain = $params->get('cookie_domain');  // MB: added
        $cookiepath = $params->get('cookie_path');  // MB: added
        $post_url = $source_url.$params->get('logout_url');
        $status = JFusionCurl::RemoteLogout($post_url,$cookiedomain,$cookiepath);
        return $status;
    }

        function getRandomString($len, $chars=null){
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }

    function createSession($userinfo, $options){
        $status['error'] = '';
        $params = JFusionFactory::getParams($this->getJname());
        $source_url = $params->get('source_url');
        $cookiedomain = $params->get('cookie_domain');
        $cookiepath = $params->get('cookie_path');
        $cookieexpires = $params->get('cookie_expires');
        $post_url = $source_url.$params->get('login_url');
        $formid = $params->get('loginform_id');
        $override = $params->get('override');
    $hidden = true;
    $buttons = true;
    $integrationtype = 1;
    $relpath=false;
        $cookies = array();
        $cookie  = array();
        $status['error'] = '';
        global $ch;
        global $cookiearr;
        global $cookies_to_set;
        global $cookies_to_set_index;
        $cookiearr = array();
        $cookies_to_set = array();
        $cookies_to_set_index = 0;
        $status=JFusionCurl::RemoteLogin($post_url,$formid,$userinfo->email,$userinfo->password_clear,
              $integrationtype,$relpath,$hidden,$buttons,$override,$cookiedomain,$cookiepath,$cookieexpires);
       return $status;
    }

    function filterUsername($username){
      //no username filtering implemented yet
      return $username;
    }

  function update_create_Magentouser($user,$entity_id){
        $db = JFusionFactory::getDatabase($this->getJname());
        $sqlDateTime = date('Y-m-d H:i:s', time());

    // transactional handling of this update is a neccessity
    if (!$entity_id){ //create an (almost) empty user
      // first get the current increment
      $db->BeginTrans();
       $query = 'SELECT increment_last_id FROM #__eav_entity_store WHERE entity_type_id = '.$this->getMagentoEntityTypeID('customer').' AND store_id = 0';
           $db->Execute($query);
       if ($db->_errorNum != 0){
        $db->RollbackTrans();
        return $db->stderr();
      }
          $increment_last_id_int = (int) $db->loadresult();
      $increment_last_id = sprintf("%'09u",($increment_last_id_int+1));
            $query =  'UPDATE #__eav_entity_store SET increment_last_id = '.$db->quote($increment_last_id).
                      ' WHERE entity_type_id = '.$this->getMagentoEntityTypeID('customer').' AND store_id = 0';
           $db->Execute($query);
      if ($db->_errorNum != 0){
        $db->RollbackTrans();
        return $db->stderr();
      }

      // so far so good, now create an empty user, to be updates later
      $query = 'INSERT INTO #__customer_entity   (entity_type_id, increment_id, is_active, created_at, updated_at) VALUES ' .
                            '('.$this->getMagentoEntityTypeID('customer').','.
                            $db->quote($increment_last_id).',1,'.$db->quote($sqlDateTime).', '.$db->quote($sqlDateTime).')';
           $db->Execute($query);
      if ($db->_errorNum != 0){
        $db->RollbackTrans();
        return $db->stderr();
      }
          $entity_id = $db->insertid();
    } else { // we are updating
      $query= 'UPDATE #__customer_entity' .
          ' SET updated_at = '.$db->quote($sqlDateTime).
          ' WHERE entity_id = '.$entity_id;
           $db->Execute($query);
      if ($db->_errorNum != 0){
        $db->RollbackTrans();
        return $db->stderr();
      }
    }
    // the basic userrecord is created, now update/create the eav records
    for ($i=0;$i< count($user); $i++) {
      if ($user[$i][backend_type] == 'static'){
        if (isset($user[$i][value])){
          $query= 'UPDATE #__customer_entity' .
              ' SET '.$user[$i][attribute_code].'= '.$db->quote($user[$i][value]).
              ' WHERE entity_id = '.$entity_id;
              $db->Execute($query);
          if ($db->_errorNum != 0){
            $db->RollbackTrans();
            return $db->stderr();
          }
        }
      } else {
        if (isset($user[$i][value])){
           $query = 'SELECT value FROM #__customer_entity'.'_'.$user[$i][backend_type].
              ' WHERE entity_id = '.$entity_id.' AND entity_type_id = '.$this->getMagentoEntityTypeID('customer').
              ' AND attribute_id = '.$user[$i][attribute_id] ;
              $db->Execute($query);
              $result = $db->loadresult();
          if ($result){
            // we do not update an empty value, but remove the record instead
            if ($user[$i][value]==''){
              $query= 'DELETE FROM #__customer_entity'.'_'.$user[$i][backend_type].
                  ' WHERE entity_id = '.$entity_id.' AND entity_type_id = '.$this->getMagentoEntityTypeID('customer').
                  ' AND attribute_id = '.$user[$i][attribute_id] ;
            } else {
              $query= 'UPDATE #__customer_entity'.'_'.$user[$i][backend_type].
                  ' SET value = '.$db->quote($user[$i][value]).
                  ' WHERE entity_id = '.$entity_id.' AND entity_type_id = '.$this->getMagentoEntityTypeID('customer').
                  ' AND attribute_id = '.$user[$i][attribute_id] ;
            }
          } else  { // must create
            $query= 'INSERT INTO #__customer_entity'.'_'.$user[$i][backend_type].
              ' (value, attribute_id, entity_id, entity_type_id) VALUES ('.
            $db->quote($user[$i][value]).', '.$user[$i][attribute_id].', '.
            $entity_id.', '.$this->getMagentoEntityTypeID('customer').')';
          }
              $db->Execute($query);
          if ($db->_errorNum != 0){
            $db->RollbackTrans();
            return $db->stderr();
          }
        }
      }
    }
    $db->CommitTrans();
    return false; //NOTE FALSE is NO ERRORS!
  }

   function fillMagentouser(&$Magento_user, $attribute_code,$value){
    $result= array();
     for ($i=0;$i< count($Magento_user); $i++) {
       if($Magento_user[$i]['attribute_code']==$attribute_code) {$Magento_user[$i]['value']=$value;}
      }
   }

    function createUser($userinfo, &$status){
     //found out what usergroup should be used
        $db = JFusionFactory::getDatabase($this->getJname());

        //prepare the variables
      // first get some default stuff from Magento
        $db->setQuery("SELECT default_group_id FROM #__core_website WHERE is_default = 1");
        $default_group_id = (int) $db->loadResult();
        $db->setQuery("SELECT default_store_id FROM #__core_store_group WHERE group_id =".$default_group_id);
        $default_store_id = (int) $db->loadResult();
        $db->setQuery('SELECT name, website_id FROM #__core_store WHERE store_id ='.$default_store_id);
        $result = $db->loadObject();
        $default_website_id = (int) $result->website_id;
        $default_created_in_store = $result->name;
       $magento_user = $this->getMagentoDataObjectRaw('customer');

      if ($userinfo->activation){$this->fillMagentouser($magento_user,'confirmation',$userinfo->activation);}
       $this->fillMagentouser($magento_user,'created_in',$default_created_in_store);
       $this->fillMagentouser($magento_user,'email',$userinfo->email);
       $parts = explode(' ', $userinfo->name);
       $this->fillMagentouser($magento_user,'firstname',$parts[0]);
    	if (count($parts)>1){
         $this->fillMagentouser($magento_user,'lastname',$parts[(count($parts)-1)]);
    	} else {
    		 // Magento needs Firstname AND Lastname, so add a dot when lastname is empty
    		$this->fillMagentouser($magento_user,'lastname','.');
    	}
       $middlename='';
       for ($i=1;$i< (count($parts)-1); $i++) {
           $middlename= $middlename.' '.$parts[$i];
       }
       if ($middlename) {$this->fillMagentouser($magento_user,'middlename',$middlename);}

       if (isset($userinfo->password_clear)) {
              $password_salt = $this->getRandomString(2);
              $this->fillMagentouser($magento_user,'password_hash',md5($password_salt.$userinfo->password_clear).':'.$password_salt);
          } else {
              $this->fillMagentouser($magento_user,'password_hash',$userinfo->password);
       }
/*     $this->fillMagentouser($magento_user,'prefix','');
       $this->fillMagentouser($magento_user,'suffix','');
       $this->fillMagentouser($magento_user,'taxvat','');
*/     $this->fillMagentouser($magento_user,'group_id',$default_group_id);
       $this->fillMagentouser($magento_user,'store_id',$default_store_id);
       $this->fillMagentouser($magento_user,'website_id',$default_website_id);
        //now append the new user data


      $errors = $this->update_create_Magentouser($magento_user,0);
        if ($errors){
            $status['error'][] = JText::_('USER_CREATION_ERROR') . $errors;
            return;
        }
        //return the good news
        $status['debug'][] = JText::_('USER_CREATION');
        $status['userinfo'] = $this->getUser($userinfo->email);
   }

   function updatePassword($userinfo, $existinguser, &$status){
       $magento_user = $this->getMagentoDataObjectRaw('customer');
         $password_salt = $this->getRandomString(2);
        $this->fillMagentouser($magento_user,'password_hash',md5($password_salt.$userinfo->password_clear).':'.$password_salt);
        $errors = $this->update_create_Magentouser($magento_user,$existinguser->userid);
        if ($errors) {
            $status['error'][] = JText::_('PASSWORD_UPDATE_ERROR')  . $db->stderr();
        } else {
            $status['debug'][] = JText::_('PASSWORD_UPDATE') . $existinguser->password;
        }
    }

    //TODO update username code
    function updateUsername($userinfo, &$existinguser, &$status){
    }

    function activateUser($userinfo, &$existinguser, &$status){
       $magento_user = $this->getMagentoDataObjectRaw('customer');
       $password_salt = $this->getRandomString(2);
       $this->fillMagentouser($magento_user,'confirmation','');
        $errors = $this->update_create_Magentouser($magento_user,$existinguser->userid);
        if ($errors) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
          $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }

    function inactivateUser($userinfo, &$existinguser, &$status) {
        $magento_user = $this->getMagentoDataObjectRaw('customer');
         $password_salt = $this->getRandomString(2);
      $this->fillMagentouser($magento_user,'confirmation',$userinfo->activation);
        $errors = $this->update_create_Magentouser($magento_user,$existinguser->userid);
        if ($errors) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
          $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
     }

    //TODO delete username code
     function deleteUsername($username){
    }
}