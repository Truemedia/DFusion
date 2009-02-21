#!/bin/sh

FULLPATH="$PWD"

case $1 in
	clear_packages)
		echo "delete old package zip files"
		cd $FULLPATH
		rm administrator/components/com_jfusion/packages/*.zip
		
		;;
	clear_main)
		echo "delete old main zip files"
		cd $FULLPATH
		rm *.zip

		;;
	clear)
		$0 clear_main
		$0 clear_packages
		
		;;
	create_packages)
		$0 clear_packages

		echo "create the new packages for the plugins and module"
		cd $FULLPATH
		zip -r $FULLPATH/administrator/components/com_jfusion/packages/jfusion_mod_activity.zip modules/mod_jfusion_activity -x *.svn*  > /dev/null
		zip -gr $FULLPATH/administrator/components/com_jfusion/packages/jfusion_mod_activity.zip language/en-GB/en-GB.mod_jfusion_activity.ini -x *.svn* > /dev/null
		cd $FULLPATH/modules/mod_jfusion_activity/
		zip -gr $FULLPATH/administrator/components/com_jfusion/packages/jfusion_mod_activity.zip mod_jfusion_activity.xml -x *.svn* > /dev/null

		cd $FULLPATH
		zip -r $FULLPATH/administrator/components/com_jfusion/packages/jfusion_mod_login.zip modules/mod_jfusion_login -x *.svn*  > /dev/null
		zip -gr $FULLPATH/administrator/components/com_jfusion/packages/jfusion_mod_login.zip language/en-GB/en-GB.mod_jfusion_login.ini -x *.svn* > /dev/null
		cd $FULLPATH/modules/mod_jfusion_login/
		zip -gr $FULLPATH/administrator/components/com_jfusion/packages/jfusion_mod_login.zip mod_jfusion_login.xml -x *.svn* > /dev/null

		cd $FULLPATH
		zip -r $FULLPATH/administrator/components/com_jfusion/packages/jfusion_mod_whosonline.zip modules/mod_jfusion_whosonline -x *.svn*  > /dev/null
		zip -gr $FULLPATH/administrator/components/com_jfusion/packages/jfusion_mod_whosonline.zip language/en-GB/en-GB.mod_jfusion_whosonline.ini -x *.svn* > /dev/null
		cd $FULLPATH/modules/mod_jfusion_whosonline/
		zip -gr $FULLPATH/administrator/components/com_jfusion/packages/jfusion_mod_whosonline.zip mod_jfusion_whosonline.xml -x *.svn* > /dev/null

		cd $FULLPATH
		zip -r $FULLPATH/administrator/components/com_jfusion/packages/jfusion_plugin_auth.zip plugins/authentication -x *.svn* > /dev/null
		cd $FULLPATH/plugins/authentication/
		zip -gr $FULLPATH/administrator/components/com_jfusion/packages/jfusion_plugin_auth.zip jfusion.xml -x *.svn* > /dev/null

		cd $FULLPATH
		zip -r $FULLPATH/administrator/components/com_jfusion/packages/jfusion_plugin_user.zip plugins/user -x *.svn* > /dev/null
		cd $FULLPATH/plugins/user/
		zip -gr $FULLPATH/administrator/components/com_jfusion/packages/jfusion_plugin_user.zip jfusion.xml -x *.svn* > /dev/null

		cd $FULLPATH
		zip -r $FULLPATH/administrator/components/com_jfusion/packages/jfusion_plugin_search.zip plugins/search -x *.svn* > /dev/null
		zip -gr $FULLPATH/administrator/components/com_jfusion/packages/jfusion_plugin_search.zip administrator/language/en-GB/en-GB.plg_search_jfusion.ini -x *.svn* > /dev/null
		cd $FULLPATH/plugins/search/
		zip -gr $FULLPATH/administrator/components/com_jfusion/packages/jfusion_plugin_search.zip jfusion.xml -x *.svn* > /dev/null

		cd $FULLPATH
		zip -r $FULLPATH/administrator/components/com_jfusion/packages/jfusion_plugin_content.zip plugins/content -x *.svn* > /dev/null
		zip -gr $FULLPATH/administrator/components/com_jfusion/packages/jfusion_plugin_content.zip administrator/language/en-GB/en-GB.plg_content_jfusion.ini -x *.svn* > /dev/null		
		cd $FULLPATH/plugins/content/
		zip -gr $FULLPATH/administrator/components/com_jfusion/packages/jfusion_plugin_content.zip jfusion.xml -x *.svn* > /dev/null

		;;
	create_main)
		$0 clear_main

		echo "create the new master package"
		cd $FULLPATH
		zip -r $FULLPATH/jfusion_package.zip administrator components language/en-GB/en-GB.com_jfusion.ini README.htm -x *.svn* > /dev/null
		cd $FULLPATH/administrator/components/com_jfusion/
		zip -gr $FULLPATH/jfusion_package.zip com_jfusion.xml -x *.svn* > /dev/null
	
		echo "create a ZIP containing all files to allow for easy updates"
		cd $FULLPATH
		zip -r jfusion_files.zip administrator components language modules plugins -x *.svn* > /dev/null

		;;
	create)
		$0 create_packages
		$0 create_main

		;;
	create_vb)
		cd $FULLPATH
		rm side_projects/vbulletin/*.zip
		cd $FULLPATH/side_projects/vbulletin
		zip -r $FULLPATH/side_projects/vbulletin/plg_auth_jfusionvbulletin.zip plugins/authentication/ -x *.svn* > /dev/null
		cd $FULLPATH/side_projects/vbulletin/plugins/authentication
		zip -gr $FULLPATH/side_projects/vbulletin/plg_auth_jfusionvbulletin.zip jfusionvbulletin.xml -x *.svn* > /dev/null
		
		;;
	*)
		echo "Usage $FULLPATH/create_package.sh {clear_packages|clear_main|clear|create_main|create_packages|create}"
		;;
esac

exit 0
