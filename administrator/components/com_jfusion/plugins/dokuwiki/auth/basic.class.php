<?php
/**
 * auth/basic.class.php
 *
 * foundation authorisation class
 * all auth classes should inherit from this class
 *
 * This class is modifyed by Morten Hundevad <fannoj@gmail.com>
 * for use in joomla / jfusion dokuwiki plugin
 *
 * @author    Chris Smith <chris@jalakai.co.uk>
 */
defined('_JEXEC' ) or die('Restricted access' );
class doku_auth_basic {

  var $success = true;


  /**
   * Posible things an auth backend module may be able to
   * do. The things a backend can do need to be set to true
   * in the constructor.
   */
  var $cando = array (
    'addUser'     => false, // can Users be created?
    'delUser'     => false, // can Users be deleted?
    'modLogin'    => false, // can login names be changed?
    'modPass'     => false, // can passwords be changed?
    'modName'     => false, // can real names be changed?
    'modMail'     => false, // can emails be changed?
    'modGroups'   => false, // can groups be changed?
    'getUsers'    => false, // can a (filtered) list of users be retrieved?
    'getUserCount'=> false, // can the number of users be retrieved?
    'getGroups'   => false, // can a list of available groups be retrieved?
    'external'    => false, // does the module do external auth checking?
    'logoff'      => false, // has the module some special logoff method?
  );

  /**
   * Encrypts a password using the given method and salt
   *
   * If the selected method needs a salt and none was given, a random one
   * is chosen.
   *
   * The following methods are understood:
   *
   *   smd5  - Salted MD5 hashing
   *   md5   - Simple MD5 hashing
   *   sha1  - SHA1 hashing
   *   ssha  - Salted SHA1 hashing
   *   crypt - Unix crypt
   *   mysql - MySQL password (old method)
   *   my411 - MySQL 4.1.1 password
   *
   * @author  Andreas Gohr <andi@splitbrain.org>
   * @return  string  The crypted password
   */
  function cryptPassword($clear,$method='',$salt=''){
    $share = Dokuwiki::getInstance();
    $conf = $share->getConf();

    if(empty($method)) $method = $conf['passcrypt'];

    //prepare a salt
    if(empty($salt)) $salt = md5(uniqid(rand(), true));

    switch(strtolower($method)){
      case 'smd5':
          return crypt($clear,'$1$'.substr($salt,0,8).'$');
      case 'md5':
        return md5($clear);
      case 'sha1':
        return sha1($clear);
      case 'ssha':
        $salt=substr($salt,0,4);
        return '{SSHA}'.base64_encode(pack("H*", sha1($clear.$salt)).$salt);
      case 'crypt':
        return crypt($clear,substr($salt,0,2));
      case 'mysql':
        //from http://www.php.net/mysql comment by <soren at byu dot edu>
        $nr=0x50305735;
        $nr2=0x12345671;
        $add=7;
        $charArr = preg_split("//", $clear);
        foreach ($charArr as $char) {
          if (($char == '') || ($char == ' ') || ($char == '\t')) continue;
          $charVal = ord($char);
          $nr ^= ((($nr & 63) + $add) * $charVal) + ($nr << 8);
          $nr2 += ($nr2 << 8) ^ $nr;
          $add += $charVal;
        }
        return sprintf("%08x%08x", ($nr & 0x7fffffff), ($nr2 & 0x7fffffff));
      case 'my411':
        return '*'.sha1(pack("H*", sha1($clear)));
      default:
        JError::raiseWarning(500,"Unsupported crypt method $method");
    }
  }


  /**
   * Constructor.
   *
   * Carry out sanity checks to ensure the object is
   * able to operate. Set capabilities in $this->cando
   * array here
   *
   * Set $this->success to false if checks fail
   *
   * @author  Christopher Smith <chris@jalakai.co.uk>
   */
  function auth_basic() {
     // the base class constructor does nothing, derived class
    // constructors do the real work
  }

  /**
   * Capability check. [ DO NOT OVERRIDE ]
   *
   * Checks the capabilities set in the $this->cando array and
   * some pseudo capabilities (shortcutting access to multiple
   * ones)
   *
   * ususal capabilities start with lowercase letter
   * shortcut capabilities start with uppercase letter
   *
   * @author  Andreas Gohr <andi@splitbrain.org>
   * @return  bool
   */
  function canDo($cap) {
    switch($cap){
      case 'Profile':
        // can at least one of the user's properties be changed?
        return ( $this->cando['modPass']  ||
                 $this->cando['modName']  ||
                 $this->cando['modMail'] );
        break;
      case 'UserMod':
        // can at least anything be changed?
        return ( $this->cando['modPass']   ||
                 $this->cando['modName']   ||
                 $this->cando['modMail']   ||
                 $this->cando['modLogin']  ||
                 $this->cando['modGroups'] ||
                 $this->cando['modMail'] );
        break;
      default:
        // print a helping message for developers
        if(!isset($this->cando[$cap])){
            JError::raiseWarning(500,"Check for unknown capability '$cap' - Do you use an outdated Plugin?");
            return false;
        }
        return $this->cando[$cap];
    }
  }

  /**
   * Log off the current user [ OPTIONAL ]
   *
   * Is run in addition to the ususal logoff method. Should
   * only be needed when trustExternal is implemented.
   *
   * @see     auth_logoff()
   * @author  Andreas Gohr
   */
  function logOff(){
  }

  /**
   * Do all authentication [ OPTIONAL ]
   *
   * Set $this->cando['external'] = true when implemented
   *
   * If this function is implemented it will be used to
   * authenticate a user - all other DokuWiki internals
   * will not be used for authenticating, thus
   * implementing the checkPass() function is not needed
   * anymore.
   *
   * The function can be used to authenticate against third
   * party cookies or Apache auth mechanisms and replaces
   * the auth_login() function
   *
   * The function will be called with or without a set
   * username. If the Username is given it was called
   * from the login form and the given credentials might
   * need to be checked. If no username was given it
   * the function needs to check if the user is logged in
   * by other means (cookie, environment).
   *
   * The function needs to set some globals needed by
   * DokuWiki like auth_login() does.
   *
   * @see auth_login()
   * @author  Andreas Gohr <andi@splitbrain.org>
   *
   * @param   string  $user    Username
   * @param   string  $pass    Cleartext Password
   * @param   bool    $sticky  Cookie should not expire
   * @return  bool             true on successful auth
   */
  function trustExternal($user,$pass,$sticky=false){
#    // some example:
#
#    global $USERINFO;
#    global $conf;
#    $sticky ? $sticky = true : $sticky = false; //sanity check
#
#    // do the checking here
#
#    // set the globals if authed
#    $USERINFO['name'] = 'FIXME';
#    $USERINFO['mail'] = 'FIXME';
#    $USERINFO['grps'] = array('FIXME');
#    $_SERVER['REMOTE_USER'] = $user;
#    $_SESSION[DOKU_COOKIE]['auth']['user'] = $user;
#    $_SESSION[DOKU_COOKIE]['auth']['pass'] = $pass;
#    $_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;
#    return true;
  }

  /**
   * Check user+password [ MUST BE OVERRIDDEN ]
   *
   * Checks if the given user exists and the given
   * plaintext password is correct
   *
   * May be ommited if trustExternal is used.
   *
   * @author  Andreas Gohr <andi@splitbrain.org>
   * @return  bool
   */
  function checkPass($user,$pass){
    JError::raiseWarning(500,"no valid authorisation system in use");
    return false;
  }

  /**
   * Return user info [ MUST BE OVERRIDDEN ]
   *
   * Returns info about the given user needs to contain
   * at least these fields:
   *
   * name string  full name of the user
   * mail string  email addres of the user
   * grps array   list of groups the user is in
   *
   * @author  Andreas Gohr <andi@splitbrain.org>
   * @return  array containing user data or false
   */
  function getUserData($user) {
    if(!$this->cando['external']) JError::raiseWarning(500,"no valid authorisation system in use");
    return false;
  }

  /**
   * Create a new User [implement only where required/possible]
   *
   * Returns false if the user already exists, null when an error
   * occurred and true if everything went well.
   *
   * The new user HAS TO be added to the default group by this
   * function!
   *
   * Set addUser capability when implemented
   *
   * @author  Andreas Gohr <andi@splitbrain.org>
   */
  function createUser($user,$pass,$name,$mail,$grps=null){
    JError::raiseWarning(500,"authorisation method does not allow creation of new users");
    return null;
  }

  /**
   * Modify user data [implement only where required/possible]
   *
   * Set the mod* capabilities according to the implemented features
   *
   * @author  Chris Smith <chris@jalakai.co.uk>
   * @param   $user      nick of the user to be changed
   * @param   $changes   array of field/value pairs to be changed (password will be clear text)
   * @return  bool
   */
  function modifyUser($user, $changes) {
    JError::raiseWarning(500,"authorisation method does not allow modifying of user data");
    return false;
  }

  /**
   * Delete one or more users [implement only where required/possible]
   *
   * Set delUser capability when implemented
   *
   * @author  Chris Smith <chris@jalakai.co.uk>
   * @param   array  $users
   * @return  int    number of users deleted
   */
  function deleteUsers($users) {
    JError::raiseWarning(500,"authorisation method does not allow deleting of users");
    return false;
  }

  /**
   * Return a count of the number of user which meet $filter criteria
   * [should be implemented whenever retrieveUsers is implemented]
   *
   * Set getUserCount capability when implemented
   *
   * @author  Chris Smith <chris@jalakai.co.uk>
   */
  function getUserCount($filter=array()) {
    JError::raiseWarning(500,"authorisation method does not provide user counts");
    return 0;
  }

  /**
   * Bulk retrieval of user data [implement only where required/possible]
   *
   * Set getUsers capability when implemented
   *
   * @author  Chris Smith <chris@jalakai.co.uk>
   * @param   start     index of first user to be returned
   * @param   limit     max number of users to be returned
   * @param   filter    array of field/pattern pairs, null for no filter
   * @return  array of userinfo (refer getUserData for internal userinfo details)
   */
  function retrieveUsers($start=0,$limit=-1,$filter=null) {
    JError::raiseWarning(500,"authorisation method does not provide user counts");
    return array();
  }

  /**
   * Define a group [implement only where required/possible]
   *
   * Set addGroup capability when implemented
   *
   * @author  Chris Smith <chris@jalakai.co.uk>
   * @return  bool
   */
  function addGroup($group) {
    JError::raiseWarning(500,"authorisation method does not support independent group creation");
    return false;
  }

  /**
   * Retrieve groups [implement only where required/possible]
   *
   * Set getGroups capability when implemented
   *
   * @author  Chris Smith <chris@jalakai.co.uk>
   * @return  array
   */
  function retrieveGroups($start=0,$limit=0) {
    JError::raiseWarning(500,"authorisation method does not support group list retrieval");
    return array();
  }

  /**
   * Check Session Cache validity [implement only where required/possible]
   *
   * DokuWiki caches user info in the user's session for the timespan defined
   * in $conf['securitytimeout'].
   *
   * This makes sure slow authentication backends do not slow down DokuWiki.
   * This also means that changes to the user database will not be reflected
   * on currently logged in users.
   *
   * To accommodate for this, the user manager plugin will touch a reference
   * file whenever a change is submitted. This function compares the filetime
   * of this reference file with the time stored in the session.
   *
   * This reference file mechanism does not reflect changes done directly in
   * the backend's database through other means than the user manager plugin.
   *
   * Fast backends might want to return always false, to force rechecks on
   * each page load. Others might want to use their own checking here. If
   * unsure, do not override.
   *
   * @param  string $user - The username
   * @author Andreas Gohr <andi@splitbrain.org>
   * @return bool
   */
  function useSessionCache($user){
    global $conf;
    return ($_SESSION[DOKU_COOKIE]['auth']['time'] >= @filemtime($conf['cachedir'].'/sessionpurge'));
  }

}
//Setup VIM: ex: et ts=2 enc=utf-8 :
