create table remember (
	series char(16) not null primary key comment 'for lookup',
	tokenhash char(88) not null comment 'for validation',
	expires datetime not null,
	key(expires),
	user smallint unsigned not null,
	foreign key(user) references user(id)
);
