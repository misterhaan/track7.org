create table post (
	id int unsigned primary key auto_increment,
	instant datetime comment 'when this was posted',
	key(instant),
	title varchar(128) not null default '',
	subsite varchar(16) not null,
	foreign key(subsite) references subsite(id) on delete cascade on update cascade,
	url varchar(128) not null comment 'absolute url path to this post',
	unique(url),
	author smallint unsigned not null,
	foreign key(author) references user(id) on delete cascade on update cascade,
	preview text not null,
	hasmore boolean not null default false
);
