create table update_messages (
	id smallint unsigned primary key auto_increment,
	posted int unsigned not null,
	html text not null
);

create table update_comments (
	id smallint unsigned primary key auto_increment,
	message smallint unsigned not null comment 'update message this comment was posted to',
	foreign key (message) references update_messages(id) on delete cascade on update cascade,
	posted int not null default 0 comment 'unix timestamp when the comment was posted',
	key (posted),
	user smallint unsigned comment 'user who posted this comment, or null to use custom name and contacturl',
	foreign key (user) references users(id) on delete cascade on update cascade,
	name varchar(48) not null default '' comment 'name of anonymous commenter',
	contacturl varchar(255) not null default '' comment 'contact url for anonymous commenter',
	html text not null comment 'html format of comment text, generated from markdown',
	markdown text not null comment 'editable version of comment text'
);

create trigger update_message_added after insert on update_messages for each row
insert into contributions set
	srctbl='update_messages',
	id=new.id,
	conttype='update',
	posted=new.posted,
	url=concat('/updates/', new.id),
	author=1,
	title='track7 update',
	preview=new.html,
	hasmore=0;

create trigger update_message_changed after update on update_messages for each row
update contributions set
	preview=new.html
where srctbl='update_messages' and id=new.id;

create trigger update_comment_added after insert on update_comments for each row
insert into contributions set
	srctbl='update_comments',
	id=new.id,
	conttype='comment',
	posted=new.posted,
	url=concat('/updates/', new.message, '#comments'),
	author=new.user,
	authorname=new.name,
	authorurl=new.contacturl,
	title='track7 update',
	preview=left(new.html, locate('</p>', new.html) + 3),
	hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4;

create trigger update_comment_changed after update on update_comments for each row
update contributions set
	preview=left(new.html, locate('</p>', new.html) + 3),
	hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4
where srctbl='update_comments' and id=new.id;

create trigger update_comment_deleted after delete on update_comments for each row
delete from contributions where srctbl='update_comments' and id=old.id;
