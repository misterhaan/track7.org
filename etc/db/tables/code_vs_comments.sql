create table code_vs_comments (
	id smallint unsigned primary key auto_increment,
	application smallint unsigned not null comment 'application this comment was posted to',
	foreign key (application) references code_vs_applications(id) on update cascade on delete cascade,
	posted int not null default 0 comment 'unix timestamp when the comment was posted',
	key (posted),
	user smallint unsigned comment 'user who posted this comment, or null to use custom name and contacturl',
	foreign key (user) references users(id) on update cascade on delete cascade,
	name varchar(48) not null default '' comment 'name of anonymous commenter',
	contacturl varchar(255) not null default '' comment 'contact url for anonymous commenter',
	html text not null comment 'html format of comment text, generated from markdown',
	markdown text not null comment 'editable version of comment text'
);
