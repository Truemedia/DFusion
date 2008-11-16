REM delete any old zip files
del *.zip
del administrator/components/com_jfusion/packages/*.zip

REM Create the new packages for the plugins and module
wzzip -rP administrator/components/com_jfusion/packages/jfusion_mod_activity.zip modules/mod_jfusion_activity language/en-GB/en-GB.mod_jfusion_activity.ini
wzzip -a administrator/components/com_jfusion/packages/jfusion_mod_activity.zip modules/mod_jfusion_activity/mod_jfusion_activity.xml

wzzip -rP administrator/components/com_jfusion/packages/jfusion_mod_login.zip modules/mod_jfusion_login language/en-GB/en-GB.mod_jfusion_login.ini
wzzip -a administrator/components/com_jfusion/packages/jfusion_mod_login.zip modules/mod_jfusion_login/mod_jfusion_login.xml

wzzip -rP administrator/components/com_jfusion/packages/jfusion_plugin_auth.zip plugins/authentication
wzzip -a administrator/components/com_jfusion/packages/jfusion_plugin_auth.zip plugins/authentication/jfusion.xml

wzzip -rP administrator/components/com_jfusion/packages/jfusion_plugin_user.zip plugins/user
wzzip -a administrator/components/com_jfusion/packages/jfusion_plugin_user.zip plugins/user/jfusion.xml

REM create the new master package
wzzip -rP jfusion_package.zip administrator components language/en-GB/en-GB.com_jfusion.ini
wzzip -a jfusion_package.zip administrator/components/com_jfusion/com_jfusion.xml

REM create a ZIP containing all files to allow for easy updates
wzzip -rP jfusion_files.zip administrator components language modules plugins