create table message (
	id int unsigned primary key auto_increment,
	instant datetime not null default now() comment 'when the message was sent',
	key(instant),
	recipient smallint unsigned not null comment 'user this message was sent to',
	foreign key(recipient) references user(id) on delete cascade on update cascade,
	unread bool not null default true comment 'whether this message has been read',
	key(recipient,unread),
	sender smallint unsigned comment 'user who sent this message, or null for anonymous',
	foreign key(sender) references user(id) on delete cascade on update cascade,
	name varchar(48) comment 'name of anonymous message sender',
	contact varchar(255) comment 'contact url for anonymous message sender',
	html text not null comment 'html format of message text, generated from markdown',
	markdown text not null comment 'editable version of message text'
);
