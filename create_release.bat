del *.zip
wzzip -rP jfusion_component.zip administrator components language/en-GB/en-GB.com_jfusion.ini
wzzip -a jfusion_component.zip administrator/components/com_jfusion/com_jfusion.xml

wzzip -rP jfusion_mod_activity.zip modules/mod_jfusion_activity language/en-GB/en-GB.mod_jfusion_activity.ini
wzzip -a jfusion_mod_activity.zip modules/mod_jfusion_activity/mod_jfusion_activity.xml

wzzip -rP jfusion_mod_login.zip modules/mod_jfusion_login language/en-GB/en-GB.mod_jfusion_login.ini
wzzip -a jfusion_mod_login.zip modules/mod_jfusion_login/mod_jfusion_login.xml

wzzip -rP jfusion_plugin_auth.zip plugins/authentication
wzzip -a jfusion_plugin_auth.zip plugins/authentication/jfusion.xml

wzzip -rP jfusion_plugin_user.zip plugins/user
wzzip -a jfusion_plugin_user.zip plugins/user/jfusion.xml

wzzip -a jfusion_package.zip *.zip *.txt
