create table user (
	id smallint unsigned primary key auto_increment,
	level tinyint unsigned not null default 1 comment 'access level (higher number is more access)',
	username varchar(32) not null comment 'limited to certain characters since it will be used in a url',
	unique(username),
	displayname varchar(32) not null default '' comment 'display name in case people want something like John Smith',
	avatar varchar(255) not null default '' comment 'url to avatar (may be offsite)'
);
