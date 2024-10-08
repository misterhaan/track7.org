create table application (
	id varchar(32) primary key comment 'unique portion of the url to this application',
	name varchar(32) not null,
	post int unsigned not null,
	foreign key(post) references post(id) on delete cascade on update cascade,
	github varchar(16) not null default '' comment 'github repository name for this application',
	wiki varchar(32) not null default '' comment 'main auwiki article for this application',
	markdown text comment 'markdown version of the application description, for editing',
	description text comment 'html version of the application description, generated from markdown, for display'
);
