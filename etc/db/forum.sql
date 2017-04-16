create table forum_discussions (
	id smallint unsigned primary key auto_increment,
	threadid smallint unsigned comment 'thread id from the old database, used to look up when redirecting',
	key(threadid),
	title varchar(255) not null
);

create table forum_replies (
	id mediumint unsigned primary key auto_increment,
	postid smallint unsigned comment 'post id from the old database, used to look up when redirecting',
	key(postid),
	discussion smallint unsigned not null,
	foreign key (discussion) references forum_discussions(id) on update cascade on delete cascade,
	posted int not null default 0 comment 'unix timestamp when the reply was posted',
	key (posted),
	user smallint unsigned comment 'user who posted this reply, or null to use custom name and contacturl',
	foreign key (user) references users(id) on delete cascade on update cascade,
	name varchar(48) not null default '' comment 'name of anonymous poster',
	contacturl varchar(255) not null default '' comment 'contact url for anonymous poster',
	html text not null default '' comment 'html format of reply text, generated from markdown',
	markdown text not null default '' comment 'editable version of reply text'
);

create table forum_tags (
	id smallint unsigned primary key auto_increment,
	name varchar(24) not null comment 'used for both display and links',
	unique (name),
	count smallint not null default 0 comment 'how many discussions use this tag',
	lastused int not null default 0 comment 'unix timestamp for the last time a discussion was posted using this tag',
	key (lastused),
	description text not null default ''
);

create table forum_discussion_tags (
	tag smallint unsigned not null,
	discussion smallint unsigned not null,
	primary key (tag, discussion),
	key (discussion),
	foreign key (tag) references forum_tags(id) on delete cascade on update cascade,
	foreign key (discussion) references forum_discussions(id) on delete cascade on update cascade
);

create table forum_edits (
	id mediumint unsigned primary key auto_increment,
	reply mediumint unsigned not null,
	foreign key(reply) references forum_replies(id) on update cascade on delete cascade,
	editor smallint unsigned not null comment 'user who made this edit',
	foreign key(editor) references users(id) on update cascade on delete cascade,
	posted int not null default 0 comment 'unix timestamp when the edit was made'
);

create trigger forum_discussion_changed after update on forum_discussions for each row
update contributions set
	title=new.title
	where srctbl='forum_replies' and id in (select id from forum_replies where discussion=new.id);

create trigger forum_reply_added after insert on forum_replies for each row
insert into contributions set
	srctbl='forum_replies',
	id=new.id,
	conttype='discuss',
	posted=new.posted,
	url=concat('/forum/', new.discussion, '#r', new.id),
	author=new.user,
	authorname=new.name,
	authorurl=new.contacturl,
	title=(select title from forum_discussions where id=new.discussion),
	preview=left(new.html, locate('</p>', new.html) + 3),
	hasmore=length(new.html) - length(replace(new.html, '</p>', '')) > 4;

create trigger forum_reply_changed after update on forum_replies for each row
update contributions set
	url=concat('/forum/', new.discussion, '#r', new.id),
	author=new.user,
	authorname=new.name,
	authorurl=new.contacturl,
	preview=left(new.html, locate('</p>', new.html) + 3),
	hasmore=length(new.html) - length(replace(new.html, '</p>', '')) > 4
	where srctbl='forum_replies' and id=new.id;

create trigger forum_reply_deleted after delete on forum_replies for each row
delete from contributions where srctbl='forum_replies' and id=old.id;
