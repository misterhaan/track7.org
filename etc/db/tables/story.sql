create table story (
	id varchar(32) not null primary key,
	post int unsigned not null unique,
	foreign key(post) references post(id) on delete cascade on update cascade,
	series varchar(32) comment 'which series this story is part of, or null if it stands alone',
	foreign key(series) references series(id) on delete cascade on update cascade,
	number tinyint unsigned not null default 0 comment 'if part of a series, number this story is in the series',
	key(series,number),
	description text not null,
	markdown text not null,
	html text not null
);
