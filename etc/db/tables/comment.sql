create table comment (
	id int unsigned primary key auto_increment,
	instant datetime comment 'when the comment was posted',
	key (instant),
	post int unsigned not null comment 'posted item this comment applies to',
	foreign key (post) references post(id) on delete cascade on update cascade,
	user smallint unsigned comment 'user who posted this comment, or null to use custom name and contacturl',
	foreign key (user) references user(id) on delete cascade on update cascade,
	name varchar(48) not null default '' comment 'name of anonymous commenter',
	contact varchar(255) not null default '' comment 'contact url for anonymous commenter',
	html text not null comment 'html format of comment text, generated from markdown',
	markdown text not null comment 'editable version of comment text'
);
