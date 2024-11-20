create table chapter (
	guide varchar(32) not null,
	foreign key (guide) references guide(id) on delete cascade,
	number tinyint unsigned not null comment 'chapter number, used for showing chapters in the correct order',
	primary key(guide, number),
	title varchar(128) not null comment 'display title for this chapter (may contain characters that aren’t safe for html)',
	html text not null comment 'html format of chapter text, generated from markdown',
	markdown text not null comment 'editable version of chapter text'
);
