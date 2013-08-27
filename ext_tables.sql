#
# Table structure for table 'tx_newsfeedimport_feeds'
#
CREATE TABLE tx_newsfeedimport_feeds (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	url tinytext NOT NULL,
	targetpid int(11) DEFAULT '0' NOT NULL,
	overrideedited tinyint(4) unsigned DEFAULT '0' NOT NULL,
	importimages tinyint(4) unsigned DEFAULT '0' NOT NULL,
	default_hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	default_type tinyint(4) DEFAULT '0' NOT NULL,
	default_extension tinyint(4) DEFAULT '0' NOT NULL,
	default_categories blob NOT NULL,
	default_author tinytext NOT NULL,
	default_authoremail tinytext NOT NULL,
	emailnotification tinyint(4) unsigned DEFAULT '0' NOT NULL,
	notificationreceivers tinytext NOT NULL,
	notificationmailsubject tinytext NOT NULL,
	notificationmailtext text NOT NULL

	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tt_news'
#
CREATE TABLE tt_news (
	tx_newsfeedimport_feed int(11) unsigned DEFAULT '0' NOT NULL,
	tx_newsfeedimport_guid tinytext NOT NULL,
	tx_newsfeedimport_edited tinyint(4) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'tx_news_domain_model_news'
#
CREATE TABLE tx_news_domain_model_news (
	tx_newsfeedimport_feed int(11) unsigned DEFAULT '0' NOT NULL,
	tx_newsfeedimport_guid tinytext NOT NULL,
	tx_newsfeedimport_edited tinyint(4) DEFAULT '0' NOT NULL
);
