create table token (
	service varchar(32) not null comment 'name of the oauth2 service',
	type varchar(16) not null comment 'type of token, usually access or refresh',
	primary key (service, type),
	token varchar(128) not null,
	scope varchar(64) not null comment 'permissions in scope for this token',
	expires datetime comment 'when this token expires, null if it does not expire'
);
