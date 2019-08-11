create table code_vs_applications (
	id smallint unsigned primary key auto_increment,
	url varchar(32) not null comment 'unique portion of the url to this application',
	unique(url),
	name varchar(32) not null,
	github varchar(16) not null default '' comment 'github repository name for this application',
	wiki varchar(32) not null default '' comment 'main auwiki article for this application',
	descmd text comment 'markdown version of the application description, for editing',
	deschtml text comment 'html version of the application description, generated from descmd, for display'
);
