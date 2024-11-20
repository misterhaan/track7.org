create table contributions (
	srctbl enum('guides', 'guide_comments', 'lego_models', 'lego_comments', 'stories', 'stories_comments', 'code_vs_releases', 'code_vs_comments', 'code_web_scripts', 'code_web_comments', 'update_messages', 'update_comments', 'forum_replies') not null comment 'name of the table this activity is fully stored in',
	id smallint unsigned not null comment 'id of this activity in srctbl',
	primary key(srctbl, id),
	conttype enum('comment', 'guide', 'post', 'lego', 'story', 'code', 'update', 'discuss'),
	posted int not null,
	key(posted),
	url varchar(32) not null default '' comment 'url to this contribution (blank for site updates)',
	author smallint unsigned comment 'user who posted this contribution, or null to use custom name and contacturl',
	foreign key (author) references users(id) on delete cascade on update cascade,
	authorname varchar(48) not null default '' comment 'name of anonymous contributor',
	authorurl varchar(255) not null default '' comment 'contact url for anonymous contributor',
	title varchar(128) not null default '',
	preview text not null comment 'beginning text of this contribution or the entire contribution if small enough',
	hasmore bool not null default 0
);
