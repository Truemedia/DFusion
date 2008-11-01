<?php
//==============================================================
// class ForumOps - intended to be used with vBulletin 3.7.2
// by Alex Matulich, June 2008, Unicorn Research Corporation
//
// Setup:
// ------
//
// 1. First, make sure FORUMPATH is defined properly below.
//
// 2. Next, make sure the function userdata_convert correctly converts
// an array of your own user data to an array of vBulletin user data.
// Minimally, you need to convert your data to an array containing
// the keys 'username', 'password', and 'email'.  If your data array
// already contains these keys, simply have userdata_convert return
// the original array, otherwise translate your array to a vBulletin
// array having those keys.
//
// Usage:
// ------
//
// At the top of your php modules where you need to perform vBulletin
// user operations (creation, deletion, updating, login, and logout),
// put these two lines (where MY_PATH is the path to this file):
//
//     require_once(MY_PATH.'/class.forumops.php');
//     $forum = new ForumOps();
//
// Now get your user data array from $_POST or whatever.  Let's call
// this array $userdata.  Here's what you can do:
//
// CREATE AND REGISTER NEW USER:
//
//    $errmsg = $forum->register_newuser($userdata);
//
// UPDATE EXISTING USER DATA:
//
//    $errmsg = $forum->update_user($userdata);
//
// (In this case, $userdata need contain only the username and anything
// else you wish to update, such as password and/or email address.)
//
// $errmsg contains any error messages separated by <br> codes.
// If no errors occured then NULL is returned.
//
// DELETE USER:
//
//    $forum->delete_user($username); // $username = user name
//
// LOGIN USER TO THE FORUM:
//
//    $forum->login(  // requires array to be passed
//       array('username' => $username, 'password' => $pw);
//
// LOG OFF USER FROM THE FORUM:
//
//    $forum->logout();
//
// WARNING: It is common for you to have TABLE_PREFIX defined for
// your own database.  You must call it something else (for example,
// remove the underscore) or it will conflict with the vBulletin
// definition of the same name.
//===============================================================

//===============================================================
// Change this definition and the userdata_convert() function
// to suit your needs:

define('FORUMPATH', $_SERVER['DOCUMENT_ROOT'].'/forum'); // path to your forum

function userdata_convert(&$userdata) // internal function
{
   // $userdata is our array that contains user data from our own
   // user database, which we must convert to the vBulletin values.
   // Minimally, it must contain the username, email and/or password.

   // required fields
   $vbuser = array( 'username' => $userdata['username'] );
   if (isset($userdata['email']))
      $vbuser['email'] = $userdata['email'];
   if (isset($userdata['password']))
      $vbuser['password'] = $userdata['password'];

   // extra stuff, expand as desired
   if ($userdata['birthdate'])
      $vbuser['birthday_search'] = date('Y-m-d', $userdata['birthdate']);
   return $vbuser;
}
// end of configuration stuff
//===============================================================

define('REGISTERED_USERGROUP', 2); // typical default for registered users
define('PERMANENT_COOKIE', false); // false=session cookies (recommended)

define('THIS_SCRIPT', __FILE__);
$cwd = getcwd();
chdir(FORUMPATH);
require_once('./global.php');
require_once('./includes/init.php'); // includes class_core.php
require_once('./includes/class_dm.php'); // for class_dm_user.php
require_once('./includes/class_dm_user.php'); // for user functions
require_once('./includes/functions.php'); // vbsetcookie etc.
require_once('./includes/functions_login.php'); // process login/logout


//---------------------------------------------------------------------
// This function duplicates the functionality of fetch_userinfo(),
// using the user name instead of numeric ID as the argument.
// See comments in includes/functions.php for documentation.
//---------------------------------------------------------------------
function fetch_userinfo_from_username($username, $option=0, $languageid=0)
{
   global $vbulletin, $db;
   $result = $db->query("SELECT * FROM "
      . TABLE_PREFIX . "user WHERE username = '".$username."'");
    $useridq = $db->fetch_array($result);
   if (!$useridq) return $useridq;
   $userid = $useridq['userid'];
   return fetch_userinfo($userid, $option, $languageid);
}


//---------------------------------------------------------------------
// CLASS ForumOps
//---------------------------------------------------------------------
class ForumOps extends vB_DataManager_User {
   var $userdm;

   function ForumOps() // constructor
   {
      global $vbulletin;
      $this->userdm =& datamanager_init('User', $vbulletin, ERRTYPE_ARRAY);
   }


   //======== USER REGISTRATION / UPDATE / DELETE ========

   function register_newuser(&$userdata, $login = false)
   {
      global $vbulletin;
      $vbuser = userdata_convert($userdata);
      foreach($vbuser as $key => $value)
         $this->userdm->set($key, $value);
      $this->userdm->set('usergroupid', REGISTERED_USERGROUP);

      // Bitfields; set to desired defaults.
      // Comment out those you have set as defaults
      // in the vBuleltin admin control panel
      $this->userdm->set_bitfield('options', 'adminemail', 1);
      $this->userdm->set_bitfield('options', 'showsignatures', 1);
      $this->userdm->set_bitfield('options', 'showavatars', 1);
      $this->userdm->set_bitfield('options', 'showimages', 1);
      $this->userdm->set_bitfield('options', 'showemail', 0);

      if ($login) $this->login($vbuser);

      //$this->userdm->errors contains error messages
      if (empty($this->userdm->errors))
         $vbulletin->userinfo['userid'] = $this->userdm->save();
      else
         return implode('<br>', $this->userdm->errors);
      return NULL;
   }


   function update_user(&$userdata)
   {
      global $vbulletin;
      $vbuser = userdata_convert($userdata);
      if (!($existing_user = fetch_userinfo_from_username($vbuser['username'])))
         return 'fetch_userinfo_from_username() failed.';

      $this->userdm->set_existing($existing_user);
      foreach($vbuser as $key => $value)
         $this->userdm->set($key, $value);

      // reset password cookie in case password changed
      if (isset($vbuser['password']))
         vbsetcookie('password',
            md5($vbulletin->userinfo['password'].COOKIE_SALT),
            PERMANENT_COOKIE, true, true);

      if (count($this->userdm->errors))
         return implode('<br>', $this->userdm->errors);
      $vbulletin->userinfo['userid'] = $this->userdm->save();
      return NULL;
   }


   function delete_user(&$username)
   {
   // The vBulletin documentation suggests using userdm->delete()
   // to delete a user, but upon examining the code, this doesn't
   // delete everything associated with the user.  The following
   // is adapted from admincp/user.php instead.
   // NOTE: THIS MAY REQUIRE MAINTENANCE WITH NEW VBULLETIN UPDATES.

      global $vbulletin;
      $db = &$vbulletin->db;
      $userdata = $db->query_first_slave("SELECT userid FROM "
         . TABLE_PREFIX . "user WHERE username='{$username}'");
      $userid = $userdata['userid'];
      if ($userid) {

      // from admincp/user.php 'do prune users (step 1)'

         // delete subscribed forums
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "subscribeforum WHERE userid={$userid}");
         // delete subscribed threads
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "subscribethread WHERE userid={$userid}");
         // delete events
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "event WHERE userid={$userid}");
         // delete event reminders
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "subscribeevent WHERE userid={$userid}");
         // delete custom avatars
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "customavatar WHERE userid={$userid}");
         $customavatars = $db->query_read("SELECT userid, avatarrevision FROM "
          . TABLE_PREFIX . "user WHERE userid={$userid}");
         while ($customavatar = $db->fetch_array($customavatars)) {
            @unlink($vbulletin->options['avatarpath'] . "/avatar{$customavatar['userid']}_{$customavatar['avatarrevision']}.gif");
         }
         // delete custom profile pics
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "customprofilepic WHERE userid={$userid}");
         $customprofilepics = $db->query_read(
            "SELECT userid, profilepicrevision FROM "
            . TABLE_PREFIX . "user WHERE userid={$userid}");
         while ($customprofilepic = $db->fetch_array($customprofilepics)) {
            @unlink($vbulletin->options['profilepicpath'] . "/profilepic$customprofilepic[userid]_$customprofilepic[profilepicrevision].gif");
         }
         // delete user forum access
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "access WHERE userid={$userid}");
         // delete moderator
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "moderator WHERE userid={$userid}");
         // delete private messages
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "pm WHERE userid={$userid}");
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "pmreceipt WHERE userid={$userid}");
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "session WHERE userid={$userid}");
         // delete user group join requests
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "usergrouprequest WHERE userid={$userid}");
         // delete bans
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "userban WHERE userid={$userid}");
         // delete user notes
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "usernote WHERE userid={$userid}");

      // from admincp/users.php 'do prune users (step 2)'

         // update deleted user's posts with userid=0
         $db->query_write("UPDATE " . TABLE_PREFIX
            . "thread SET postuserid = 0, postusername = '"
            . $db->escape_string($username)
            . "' WHERE postuserid = $userid");
         $db->query_write("UPDATE " . TABLE_PREFIX
            . "post SET userid = 0, username = '"
            . $db->escape_string($username)
            . "' WHERE userid = $userid");

         // finally, delete the user
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "usertextfield WHERE userid={$userid}");
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "userfield WHERE userid={$userid}");
         $db->query_write("DELETE FROM " . TABLE_PREFIX
            . "user WHERE userid={$userid}");
      }
   /*
      the following is suggested in the documentation but doesn't work:

      $existing_user = fetch_userinfo_from_username($username);
      $this->userdm->set_existing($existing_user);
      return $this->userdm->delete();
   */
   }


   // ======== USER LOGIN / LOGOUT ========

   function login($vbuser)
   {
      global $vbulletin;
      $vbulletin->userinfo = fetch_userinfo_from_username($vbuser['username']);

      // update password expire time to
      // to prevent vBulletin from expiring the password

      $this->userdm->set_existing($vbulletin->userinfo);
      $this->userdm->set('passworddate', 'FROM_UNIXTIME('.TIMENOW.')', false);
      $this->userdm->save();

      // set cookies
      vbsetcookie('userid', $vbulletin->userinfo['userid'],
         PERMANENT_COOKIE, true, true);
      vbsetcookie('password',
         md5($vbulletin->userinfo['password'].COOKIE_SALT),
         PERMANENT_COOKIE, true, true);

      // create session stuff
      process_new_login('', 1, '');
   }


   function logout()
   {
      process_logout(); // unsets all cookies and session data
   }

} // end class ForumOps
chdir($cwd);
?>