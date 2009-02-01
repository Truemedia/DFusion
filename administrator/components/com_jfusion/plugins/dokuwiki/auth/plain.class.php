<?php
/**
 * Plaintext authentication backend
 *
 * This class is modifyed by Morten Hundevad <fannoj@gmail.com>
 * for use in joomla / jfusion dokuwiki plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 * @author     Chris Smith <chris@jalakai.co.uk>
 */

define('DOKU_AUTH', dirname(__FILE__));
require_once(DOKU_AUTH.'/basic.class.php');
require_once(DOKU_AUTH.'/io.class.php');
class doku_auth_plain extends doku_auth_basic {

    var $users = null;
    var $io = null;
    var $_pattern = array();

    /**
     * Constructor
     *
     * Carry out sanity checks to ensure the object is
     * able to operate. Set capabilities.
     *
     * @author  Christopher Smith <chris@jalakai.co.uk>
     */
    function auth_plain() {
      $this->io = new JFusionDokuwiki_Io();
    }

    function auth_file() {
      $share = Dokuwiki::getInstance();
      $params = JFusionFactory::getParams($share->getJname());

      $sorce_path = $params->get('source_path');
      if (substr($sorce_path, -1) == DS) {
        $sorce_path = $sorce_path . 'conf/users.auth.php';
      } else {
        $sorce_path = $sorce_path . DS . 'conf/users.auth.php';
      }
      return $sorce_path ;
    }

    /**
     * Check user+password [required auth function]
     *
     * Checks if the given user exists and the given
     * plaintext password is correct
     *
     * @author  Andreas Gohr <andi@splitbrain.org>
     * @return  bool
     */
    function checkPass($user,$pass) {
      $userinfo = $this->getUserData($user);
      if ($userinfo === false) return false;

      return auth_verifyPassword($pass,$this->users[$user]['pass']);
    }

    /**
     * Return user info
     *
     * Returns info about the given user needs to contain
     * at least these fields:
     *
     * name string  full name of the user
     * mail string  email addres of the user
     * grps array   list of groups the user is in
     *
     * @author  Andreas Gohr <andi@splitbrain.org>
     */
    function getUserData($user){
      return $this->_loadUserData();
    }

    /**
     * Create a new User
     *
     * Returns false if the user already exists, null when an error
     * occurred and true if everything went well.
     *
     * The new user will be added to the default group by this
     * function if grps are not specified (default behaviour).
     *
     * @author  Andreas Gohr <andi@splitbrain.org>
     * @author  Chris Smith <chris@jalakai.co.uk>
     */
    function createUser($user,$pwd,$name,$mail) {
      $share = Dokuwiki::getInstance();
      $users = $share->getUserList(false,true);

      if ($users[$user]) return false;

      $authfile = $this->auth_file();

      $pass = $this->cryptPassword($pwd);
      // prepare user line
      $groups = join(',',array($share->getDefaultUsergroup()));
      $userline = join(':',array($user,$pass,$name,$mail,$groups))."\n";
      if (!$this->io ) $this->io = new JFusionDokuwiki_Io();
      if ($this->io->saveFile($authfile,$userline,true)) {
        $users[$user] = compact('pass','name','mail','grps');
        return $pwd;
      }
      JError::raiseWarning(500,' file is not writable. Please inform the Wiki-Admin');
      return false;
    }

    /**
     * Modify user data
     *
     * @author  Chris Smith <chris@jalakai.co.uk>
     * @param   $user      nick of the user to be changed
     * @param   $changes   array of field/value pairs to be changed (password will be clear text)
     * @return  bool
     */
    function modifyUser($user, $changes) {
      if (!is_array($changes) || !count($changes)) return true;

      $share = Dokuwiki::getInstance();
      $users = $share->getUserList(false,true);

      // sanity checks, user must already exist and there must be something to change
      if (!is_array($users[$user])) return false;
      $userinfo = $users[$user];

      // update userinfo with new data, remembering to encrypt any password
      $newuser = $user;
      foreach ($changes as $field => $value) {
        if ($field == 'user') {
          $newuser = $value;
          continue;
        }
        if ($field == 'pass') $value = $this->cryptPassword($value);
        $userinfo[$field] = $value;
      }

      $groups = join(',',$userinfo['grps']);
      $userline = join(':',array($newuser, $userinfo['pass'], $userinfo['name'], $userinfo['mail'], $groups))."\n";
      if (!$this->io ) $this->io = new JFusionDokuwiki_Io();
      if (!$this->deleteUsers(array($user))) {
          JError::raiseWarning(500,'Unable to modify user data. Please inform the Wiki-Admin');
          return false;
      }

      $file = $this->auth_file();
      if (!$this->io ) $this->io = new JFusionDokuwiki_Io();
      if (!$this->io->saveFile($file,$userline,true)) {
          JError::raiseWarning(500,'There was an error modifying your user data. You should register again.');
          return false;
        // FIXME, user has been deleted but not recreated, should force a logout and redirect to login page
      }

      $users[$newuser] = $userinfo;
      return true;
    }

    /**
     *  Remove one or more users from the list of registered users
     *
     *  @author  Christopher Smith <chris@jalakai.co.uk>
     *  @param   array  $users   array of users to be deleted
     *  @return  int             the number of users deleted
     */
    function deleteUsers($users) {
      if (!is_array($users) || empty($users)) return 0;

      $share = Dokuwiki::getInstance();
      $the_users = $share->getUserList(false,true);

      $deleted = array();
      foreach ($users as $user) {
        if (isset($the_users[$user])) $deleted[] = preg_quote($user,'/');
      }
      if (empty($deleted)) return 0;

      $pattern = '/^('.join('|',$deleted).'):/';

      $file = $this->auth_file();
      if (!$this->io ) $this->io = new JFusionDokuwiki_Io();
      if ($this->io->deleteFromFile($file,$pattern,true)) {
        foreach ($deleted as $user) unset($the_users[$user]);
        return count($deleted);
      }

      // problem deleting, reload the user list and count the difference
      $count = count($the_users);
      $the_users = $share->getUserList(false,true);
      $count -= count($the_users);
      return $count;
    }

    /**
     * Return a count of the number of user which meet $filter criteria
     *
     * @author  Chris Smith <chris@jalakai.co.uk>
     */
    function getUserCount($filter=array()) {
      $users = $this->_loadUserData();
      if (!count($filter)) return count($users);

      $count = 0;
      $this->_constructPattern($filter);

      foreach ($users as $user => $info) {
          $count += $this->_filter($user, $info);
      }

      return $count;
    }

    /**
     * Bulk retrieval of user data
     *
     * @author  Chris Smith <chris@jalakai.co.uk>
     * @param   start     index of first user to be returned
     * @param   limit     max number of users to be returned
     * @param   filter    array of field/pattern pairs
     * @return  array of userinfo (refer getUserData for internal userinfo details)
     */
    function retrieveUsers($start=0,$limit=0,$filter=array()) {

      if ($this->users === null) $this->_loadUserData();

      ksort($this->users);

      $i = 0;
      $count = 0;
      $out = array();
      $this->_constructPattern($filter);

      foreach ($this->users as $user => $info) {
        if ($this->_filter($user, $info)) {
          if ($i >= $start) {
            $out[$user] = $info;
            $count++;
            if (($limit > 0) && ($count >= $limit)) break;
          }
          $i++;
        }
      }

      return $out;
    }

    /**
     * Load all user data
     *
     * loads the user file into a datastructure
     *
     * @author  Andreas Gohr <andi@splitbrain.org>
     */
    function _loadUserData(){
      $users = array();

      $file = $this->auth_file();
      if(!@file_exists($file)) return;

      $lines = file($file);
      foreach($lines as $line){
        $line = preg_replace('/#.*$/','',$line); //ignore comments
        $line = trim($line);
        if(empty($line)) continue;

        $row    = split(":",$line,5);
        $groups = split(",",$row[4]);

        $users[$row[0]]['username'] = $row[0];
        $users[$row[0]]['pass'] = $row[1];
        $users[$row[0]]['name'] = urldecode($row[2]);
        $users[$row[0]]['mail'] = $row[3];
        $users[$row[0]]['grps'] = $groups;
      }
      return $users;
    }

    /**
     * return 1 if $user + $info match $filter criteria, 0 otherwise
     *
     * @author   Chris Smith <chris@jalakai.co.uk>
     */
    function _filter($user, $info) {
        // FIXME
        foreach ($this->_pattern as $item => $pattern) {
            if ($item == 'user') {
                if (!preg_match($pattern, $user)) return 0;
            } else if ($item == 'grps') {
                if (!count(preg_grep($pattern, $info['grps']))) return 0;
            } else {
                if (!preg_match($pattern, $info[$item])) return 0;
            }
        }
        return 1;
    }

    function _constructPattern($filter) {
      $this->_pattern = array();
      foreach ($filter as $item => $pattern) {
//        $this->_pattern[$item] = '/'.preg_quote($pattern,"/").'/i';          // don't allow regex characters
        $this->_pattern[$item] = '/'.str_replace('/','\/',$pattern).'/i';    // allow regex characters
      }
    }
}

//Setup VIM: ex: et ts=2 enc=utf-8 :