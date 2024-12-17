create table series (
	id varchar(32) not null primary key comment 'unique part of the url to this series',
	title varchar(128) not null,
	markdown text not null,
	html text not null
);
