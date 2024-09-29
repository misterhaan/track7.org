create table blog (
	id varchar(32) not null primary key comment 'unique portion of the url to this blog (usually a url-friendly version of the title)',
	post int unsigned,
	foreign key(post) references post(id) on delete cascade on update cascade,
	html text not null,
	markdown text not null
);
