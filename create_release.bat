REM delete any old zip files
del *.zip
del administrator/components/com_jfusion/packages/*.zip

REM Create the new packages for the plugins and module
wzzip -rP administrator/components/com_jfusion/packages/jfusion_mod_activity.zip modules/mod_jfusion_activity language/en-GB/en-GB.mod_jfusion_activity.ini
wzzip -a administrator/components/com_jfusion/packages/jfusion_mod_activity.zip modules/mod_jfusion_activity/mod_jfusion_activity.xml

wzzip -rP administrator/components/com_jfusion/packages/jfusion_mod_login.zip modules/mod_jfusion_login language/en-GB/en-GB.mod_jfusion_login.ini
wzzip -a administrator/components/com_jfusion/packages/jfusion_mod_login.zip modules/mod_jfusion_login/mod_jfusion_login.xml

wzzip -rP administrator/components/com_jfusion/packages/jfusion_mod_whosonline.zip modules/mod_jfusion_whosonline language/en-GB/en-GB.mod_jfusion_whosonline.ini
wzzip -a administrator/components/com_jfusion/packages/jfusion_mod_whosonline.zip modules/mod_jfusion_whosonline/mod_jfusion_whosonline.xml

wzzip -rP administrator/components/com_jfusion/packages/jfusion_plugin_auth.zip plugins/authentication
wzzip -a administrator/components/com_jfusion/packages/jfusion_plugin_auth.zip plugins/authentication/jfusion.xml

wzzip -rP administrator/components/com_jfusion/packages/jfusion_plugin_user.zip plugins/user
wzzip -a administrator/components/com_jfusion/packages/jfusion_plugin_user.zip plugins/user/jfusion.xml

wzzip -rP administrator/components/com_jfusion/packages/jfusion_plugin_search.zip plugins/search
wzzip -a administrator/components/com_jfusion/packages/jfusion_plugin_search.zip plugins/search/jfusion.xml
wzzip -a administrator/components/com_jfusion/packages/jfusion_plugin_search.zip administrator/language/en-GB/en-GB.plg_search_jfusion.ini

wzzip -rP administrator/components/com_jfusion/packages/jfusion_plugin_content.zip plugins/content
wzzip -a administrator/components/com_jfusion/packages/jfusion_plugin_content.zip plugins/content/jfusion.xml
wzzip -a administrator/components/com_jfusion/packages/jfusion_plugin_content.zip administrator/language/en-GB/en-GB.plg_content_jfusion.ini

REM create the new master package
wzzip -rP jfusion_package.zip administrator components language/en-GB/en-GB.com_jfusion.ini README.htm
wzzip -a jfusion_package.zip administrator/components/com_jfusion/com_jfusion.xml

REM create a ZIP containing all files to allow for easy updates
wzzip -rP jfusion_files.zip administrator components language modules plugins