<?php
/**
* @package JFusion
* @subpackage Modules
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<?php if($type == 'logout') : ?>
<form action="index.php" method="post" name="login" id="form-login">
<?php if ($params->get('avatar') && $avatar) :
	$size = getimagesize($avatar);
	$w = $size[0];
	$h = $size[1];
	if($size[0]>60) {
		$scale = min(60/$w, 80/$h);
		$w = floor($scale*$w);
		$h = floor($scale*$h);
	}
?>
	<div align="center"><img src="<?php echo $avatar; ?>" height="<?php echo $h; ?>" width="<?php echo $w; ?>" alt="<?php echo $user->get('name'); ?>" /></div>
<?php endif; ?>
<?php if ($params->get('greeting')) : ?>
	<div align="center">
	    <?php echo JText::sprintf( 'HINAME', $user->get('name') ); ?>
	</div>
<?php endif; ?>

	<div align="center">
		<input type="submit" name="Submit" class="button" value="<?php echo JText::_('BUTTON_LOGOUT'); ?>" />
	</div>

	<input type="hidden" name="option" value="com_user" />
	<input type="hidden" name="task" value="logout" />
	<input type="hidden" name="silent" value="true" />
	<input type="hidden" name="return" value="<?php echo $return; ?>" />
</form>
<?php else : ?>
<?php if(JPluginHelper::isEnabled('authentication', 'openid')) : ?>
	<?php JHTML::_('script', 'openid.js'); ?>
<?php endif; ?>
<form action="<?php echo JRoute::_(JURI::Base().'index.php', true, $params->get('usesecure')); ?>" method="post" name="login" id="form-login" >
	<?php echo $params->get('pretext'); ?>
	<p id="form-login-username">
		<label for="modlgn_username"><?php echo JText::_('USERNAME') ?></label><br />
		<input id="modlgn_username" type="text" name="username" class="inputbox" alt="username" size="18" />
	</p>
	<p id="form-login-password">
		<label for="modlgn_passwd"><?php echo JText::_('PASSWORD') ?></label><br />
		<input id="modlgn_passwd" type="password" name="passwd" class="inputbox" size="18" alt="password" />
	</p>
	<?php if($params->get('show_rememberme')) : ?>
	<p id="form-login-remember">
		<label for="modlgn_remember"><?php echo JText::_('REMEMBER_ME') ?></label>
		<input id="modlgn_remember" type="checkbox" name="remember" value="yes" alt="Remember Me" />
	</p>
	<?php endif; ?>
	<input type="submit" name="Submit" class="button" value="<?php echo JText::_('BUTTON_LOGIN') ?>" />
	<ul>
        <?php if($params->get('lostpassword_show')) : ?>
		<li>
			<a href="<?php echo $lostpassword_url; ?>">
			<?php echo JText::_('FORGOT_YOUR_PASSWORD'); ?>
			</a>
		</li>
		<?php endif; ?>
        <?php if($params->get('lostusername_show')) : ?>
		<li>
			<a href="<?php echo $lostusername_url; ?>">
			<?php echo JText::_('FORGOT_YOUR_USERNAME'); ?></a>
		</li>
		<?php endif; ?>
		<?php
		$usersConfig = &JComponentHelper::getParams( 'com_users' );
		if ($params->get('register_show')) : ?>
		<li>
			<a href="<?php echo $register_url ?>">
				<?php echo JText::_('REGISTER'); ?>
			</a>
		</li>
		<?php endif; ?>
	</ul>
	<?php echo $params->get('posttext'); ?>

	<input type="hidden" name="option" value="com_user" />
	<input type="hidden" name="task" value="login" />
	<input type="hidden" name="silent" value="true" />
	<input type="hidden" name="return" value="<?php echo $return; ?>" />
    <?php echo JHTML::_( 'form.token' ); ?>
</form>
<?php endif; ?>