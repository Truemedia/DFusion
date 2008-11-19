

DROP TABLE IF EXISTS #__jfusion_users;
CREATE TABLE #__jfusion_users (
	id int(11) NOT NULL,
	username varchar(50),
	PRIMARY KEY (username)
) DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS #__jfusion_users_plugin;
CREATE TABLE #__jfusion_users_plugin (
	autoid int(11) NOT NULL auto_increment,
	id int(11) NOT NULL,
	username varchar(50),
	userid int(11) NOT NULL,
    jname varchar(50) NOT NULL,
	PRIMARY KEY (autoid)
) DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS #__jfusion_sync;
CREATE TABLE #__jfusion_sync (
  syncid varchar(10),
  action varchar(255),
  syncdata text,
  time_start int(8),
  time_end int(8),
  PRIMARY KEY  (syncid)
);