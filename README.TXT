THIS IS AN ALPHA RELEASE PLUGIN -> USE AT YOUR OWN RISK AND DO NOT USE ON LIVE SITES

*** JFusion Installation Help ***


* What can JFusion do?

- JFusion allows you to integrate your Joomla users with many other softwares, therefore allowing your users to register only once and then login with a single username/password across different online softwares.

- Additional modules are provided to show avatars from your integrated software on the login module and to shoe forum posts in Joomla itself.


* What can JFusion not do?

- JFusion does not make any changes to the integrated software, therefore if you login through your integrated software, you will not be logged into Joomla.

- JFusion is an alpha release, intended to get feedback from the Joomla community. JFusion should not be used on production sites at this stage.

* How does JFusion work?

JFusion uses the Joomla 1.5 framework to provide user integration without any core hacks. Jfusion only works with Joomla 1.5.x and not with Joomla 1.0.x. You can select which software is used as the master of authentication. All user registration and password changes will need to be made through the master software. Jfusion will update the software slaves with the user details from the master user table. In addition Jfusion can handle multiple types of password encryption and can even create a session in the slave softwares to automatically logon the user on all softwares.


* How to install and configure JFusion:

We spent quite a bit of time making the installation process as easy as possible. There is a setup wizard and extensive error checking during all steps of installation to make it easy for yourself. Please installation and configure Jfusion using the steps below:

1. Install the Jfusion component
2. Configure the Jfusion plugins and select your master/slaves
3. Run the usersync in the Jfusion component to synchronise the user tables
4. Run the login checker to confirm the login behaviour is correct
5. Install and enable the Jfusion authentication and user plugins
6. Disable the Joomla default autentication and user plugins
7. Install and configure the Jfusion login and activity module (optional)


* Visual Integration

At the moment you have two options for visual integration of your external software into Jfusion. Full visual integration without a wrapper is currenlty not supported.

- Modify your external softwares template to match Joomla and select the “direct link” option in the Jfusion login / activity module.

- Show the external software in a Joomla iframe wrapper and select the “wrapper” option in the Jfusion login / activity module. You can create a menu link to your software by creating a new Joomla menu item for the Jfusion component.


* More help and support:

Please visit the website http://www.jfusion.org for detailed instructions on Jfusion configurations and for getting help on our support forums.


* Reporting bugs

JFusion is an alpha release and we would love to hear from you on how we can make Jfusion work better. If you find any bugs or issues please report them on the following website: http://code.google.com/p/jfusion/

