create table user (
	id smallint unsigned primary key auto_increment,
	level tinyint unsigned not null default 1 comment 'access level (higher number is more access)',
	username varchar(32) not null comment 'limited to certain characters since it will be used in a url',
	unique(username),
	displayname varchar(32) comment 'display name in case people want something like John Smith',
	avatar varchar(255) comment 'url to avatar (may be offsite)',
	registered datetime not null default now() comment 'when the user first regisetered',
	key(registered),
	lastlogin datetime not null default now() comment 'when the user last logged in',
	key(lastlogin),
	passwordhash varchar(96) comment 'optional if user has external login'
);
