CREATE TABLE IF NOT EXISTS #__jfusion (
  id int(11) NOT NULL auto_increment,
  name varchar(50) NOT NULL,
  description varchar(150) NOT NULL,
  version varchar(50),
  date varchar(50),
  author varchar(50),
  support varchar(50),
  params text,
  master tinyint(4) NOT NULL,
  slave tinyint(4) NOT NULL,
  status tinyint(4) NOT NULL,
  dual_login tinyint(4) NOT NULL,
  check_encryption tinyint(4) NOT NULL,
  activity tinyint(4) NOT NULL,
  PRIMARY KEY  (id)
);

INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status, check_encryption, activity)
VALUES ('joomla_int', 'Current Joomla Installation', '1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/phpbb3/',  0, 0,  0, 3,  0, 0);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('joomla_ext', 'External Joomla Installation', '1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/phpbb3/', 0, 3, 3, 0,  0, 0);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('vbulletin', 'vBulletin 3.6.8', '1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/phpbb3/', 0,  3, 0, 0,  0, 1);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('phpbb3', 'phpBB3','1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/phpbb3/', 0, 0, 0, 0, 0, 1);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('smf', 'SMF 1.1.4', '1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/phpbb3/', 0, 3, 3, 0,  0, 1);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('mybb', 'myBB 1.2.12','1.00','25th May 2008',  'JFusion development team', 'www.jfusion.org/phpbb3/',  0,  3, 0, 0,  0, 1);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('ipb', 'ipb','1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/phpbb3/',  0,  3, 0, 0, 0, 1);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('magento', 'magento 1.0','1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/phpbb3/',  0,  3, 0, 0, 0, 0);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('punbb', 'punbb 1.2.17','1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/phpbb3/',  0,  3, 0, 0, 0, 1);


CREATE TABLE IF NOT EXISTS #__jfusion_users (
	id int(11) NOT NULL,
	username varchar(50),
	PRIMARY KEY (username)
) DEFAULT CHARACTER SET utf8;

CREATE TABLE IF NOT EXISTS #__jfusion_users_plugin (
	autoid int(11) NOT NULL auto_increment,
	id int(11) NOT NULL,
	username varchar(50),
	userid int(11) NOT NULL,
    jname varchar(50) NOT NULL,
	PRIMARY KEY (autoid)
) DEFAULT CHARACTER SET utf8;


CREATE TABLE IF NOT EXISTS #__jfusion_sync (
  syncid varchar(10),
  action varchar(255),
  syncdata text,
  time_start int(8),
  time_end int(8),
  PRIMARY KEY  (syncid)
);