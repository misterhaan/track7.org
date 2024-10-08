create table `release` (
	application varchar(32) not null,
	foreign key(application) references application(id) on delete cascade on update cascade,
	major tinyint unsigned not null default 0,
	minor tinyint unsigned not null default 0,
	revision tinyint unsigned not null default 0,
	primary key(application, major, minor, revision),
	instant datetime comment 'when this was posted',
	key(instant),
	language enum('vb', 'c#') not null default 'c#' comment 'primary project language',
	dotnet decimal(4,1) comment '.net version used by the application',
	visualstudio smallint unsigned comment 'visual studio version the application was developed in',
	changelog text not null comment 'html change log which is usually a bulleted list.  generated from markdown which is not saved.',
	binurl varchar(128) not null,
	bin32url varchar(128) not null default '' comment 'url to the 32-bit binary if binurl is a 64-bit binary',
	srcurl varchar(128) not null default ''
);
