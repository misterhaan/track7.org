create table gameworld (
	id varchar(32) not null primary key comment 'unique portion of the download url for this game world',
	post int unsigned not null,
	foreign key(post) references post(id) on delete cascade on update cascade,
	engine enum('zzt', 'megazeux') not null default 'megazeux',
	markdown text not null comment 'markdown version of the game world description, for editing',
	description text not null comment 'html version of the game world description, generated from markdown, for display',
	dmzx int unsigned not null default 0 comment 'id of this gameworld in the digitalmzx.com vault'
);
