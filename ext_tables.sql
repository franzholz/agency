
# THESE create statements will NOT work if this file is piped into MySQL.
# Rather they will be detected by the TYPO3 Install Tool and through that
# you should upgrade the tables to content these fields.

CREATE TABLE fe_users (
	static_info_country char(3) DEFAULT '' NOT NULL,
	zone varchar(45) DEFAULT '' NOT NULL,
	language char(2) DEFAULT '' NOT NULL,
	gender int(11) unsigned DEFAULT '99' NOT NULL,
	cnum varchar(50) DEFAULT '' NOT NULL,
	name varchar(100) DEFAULT '' NOT NULL,
	first_name varchar(50) DEFAULT '' NOT NULL,
	last_name varchar(50) DEFAULT '' NOT NULL,
	status int(11) unsigned DEFAULT '0' NOT NULL,
	city varchar(40) DEFAULT '' NOT NULL,
	country varchar(60) DEFAULT '' NOT NULL,
	house_no varchar(20) DEFAULT '' NOT NULL,
	zip varchar(20) DEFAULT '' NOT NULL,
	telephone varchar(25) DEFAULT '' NOT NULL,
	fax varchar(25) DEFAULT '' NOT NULL,
	email varchar(255) DEFAULT '' NOT NULL,
	company varchar(50) DEFAULT '' NOT NULL,
	date_of_birth int(11) DEFAULT '0' NOT NULL,
	comments text,
	by_invitation tinyint(4) unsigned DEFAULT '0' NOT NULL,
	module_sys_dmail_html tinyint(3) unsigned DEFAULT '0' NOT NULL,
	terms_acknowledged tinyint(4) unsigned DEFAULT '0' NOT NULL,
	token varchar(32) DEFAULT '' NOT NULL,
	tx_agency_password blob,
	lost_password tinyint(4) unsigned DEFAULT '0' NOT NULL,
);


CREATE TABLE fe_groups_language_overlay (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	sorting int(10) unsigned DEFAULT '0' NOT NULL,
	fe_group int(11) unsigned DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	title varchar(50) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


CREATE TABLE sys_agency_fe_users_limit_fe_groups (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	codes tinytext,
	fe_users_uid int(11) DEFAULT '0' NOT NULL,
	fe_groups_uid int(11) DEFAULT '0' NOT NULL,
	status int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY fe_user (fe_users_uid),
	KEY relation (fe_users_uid,fe_groups_uid)
);

