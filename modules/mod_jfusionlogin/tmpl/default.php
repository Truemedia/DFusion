<?php
/**
* @package JFusion
* @subpackage Views
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access'); ?>

<?php if($type == 'logout') : ?>
<form action="index.php" method="post" name="login" id="form-login">
<?php if ($params->get('avatar') && $avatar) : ?>
	<div align="center"><img src="<?php echo $avatar; ?>" alt="<?php echo $user->get('name'); ?>" /></div>
<?php endif; ?>
<?php if ($params->get('greeting')) : ?>
	<div align="center">
	    <?php echo JText::sprintf( 'HINAME', $user->get('name') ); ?>
		<?php if ($params->get('pmcount')) : ?>
			<br />
			<?php
			    echo JText::_('PM_START');
			    echo ' <a href="'.$url_pm.'">'.JText::sprintf('PM_LINK', $pmcount["total"])."</a>";
			    echo JText::sprintf('PM_END', $pmcount["unread"]);
			?>
		<?php endif; ?>
	</div>
<?php elseif ($params->get('pmcount')) : ?>
    <div align="center">
	<?php
	    echo JText::_('PM_START');
	    echo ' <a href="'.$url_pm.'">'.JText::sprintf('PM_LINK', $pmcount["total"]).'</a>';
	    echo JText::sprintf('PM_END', $pmcount["unread"]);
	?>
	</div>
<?php endif; ?>
<?php if ($params->get('viewnewmessages')) : ?>
	<div align="center"><a href="<?php echo $url_viewnewmessages; ?>"><?php echo JText::_('VIEW_NEW_TOPICS'); ?></a></div>
<?php endif; ?>
	<div align="center">
		<input type="submit" name="Submit" class="button" value="<?php echo JText::_('BUTTON_LOGOUT'); ?>" />
	</div>

	<input type="hidden" name="option" value="com_user" />
	<input type="hidden" name="task" value="logout" />
	<input type="hidden" name="return" value="<?php echo $return; ?>" />
</form>
<?php else : ?>
<?php if(JPluginHelper::isEnabled('authentication', 'openid')) : ?>
	<?php JHTML::_('script', 'openid.js'); ?>
<?php endif; ?>
<form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>" method="post" name="login" id="form-login" >
	<?php echo $params->get('pretext'); ?>
	<fieldset class="input">
	<p id="form-login-username">
		<label for="modlgn_username"><?php echo JText::_('USERNAME') ?></label><br />
		<input id="modlgn_username" type="text" name="username" class="inputbox" alt="username" size="18" />
	</p>
	<p id="form-login-password">
		<label for="modlgn_passwd"><?php echo JText::_('PASSWORD') ?></label><br />
		<input id="modlgn_passwd" type="password" name="passwd" class="inputbox" size="18" alt="password" />
	</p>
	<?php if(JPluginHelper::isEnabled('system', 'remember')) : ?>
	<p id="form-login-remember">
		<label for="modlgn_remember"><?php echo JText::_('REMEMBER ME') ?></label>
		<input id="modlgn_remember" type="checkbox" name="remember" class="inputbox" value="yes" alt="Remember Me" />
	</p>
	<?php endif; ?>
	<input type="submit" name="Submit" class="button" value="<?php echo JText::_('BUTTON_LOGIN') ?>" />
	</fieldset>
	<ul>
        <?php if($params->get('show_lostpass')) : ?>
		<li>
			<a href="<?php echo $url_lostpass; ?>">
			<?php echo JText::_('FORGOT_YOUR_PASSWORD'); ?>
			</a>
		</li>
		<?php endif; ?>
        <?php if($params->get('show_lostusername')) : ?>
		<li>
			<a href="<?php echo $url_lostuser; ?>">
			<?php echo JText::_('FORGOT_YOUR_USERNAME'); ?></a>
		</li>
		<?php endif; ?>
		<?php
		$usersConfig = &JComponentHelper::getParams( 'com_users' );
		if ($usersConfig->get('allowUserRegistration') && $params->get('show_newaccount')) : ?>
		<li>
			<a href="<?php echo $url_register ?>">
				<?php echo JText::_('REGISTER'); ?>
			</a>
		</li>
		<?php endif; ?>
	</ul>
	<?php echo $params->get('posttext'); ?>

	<input type="hidden" name="option" value="com_user" />
	<input type="hidden" name="task" value="login" />
	<input type="hidden" name="return" value="<?php echo $return; ?>" />
    <?php echo JHTML::_( 'form.token' ); ?>
</form>
<?php endif; ?>