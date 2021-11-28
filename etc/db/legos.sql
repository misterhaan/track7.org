create table lego_models (
	id smallint unsigned auto_increment primary key,
	url varchar(32) not null comment 'unique portion of the url to this lego model', unique(url),
	posted int not null default 0 comment 'unix timestamp when this lego model was posted', key(posted),
	title varchar(32) not null default '',
	deschtml text not null comment 'html description of this lego model, generated from descmd',
	descmd text not null comment 'description of this lego model in markdown (for editing)',
	pieces smallint unsigned not null default 0 comment 'number of pieces in this model', key(pieces),
	mans tinyint unsigned not null default 0 comment 'number of lego men this model can accomodate', key(mans),
	rating float unsigned not null default 3, key(rating),
	votes smallint unsigned not null default 0, key(votes)
);

create table lego_comments (
	id smallint unsigned primary key auto_increment,
	lego smallint unsigned not null comment 'lego model this comment was posted to',
	foreign key (lego) references lego_models(id) on delete cascade on update cascade,
	posted int not null default 0 comment 'unix timestamp when the comment was posted',
	key (posted),
	user smallint unsigned comment 'user who posted this comment, or null to use custom name and contacturl',
	foreign key (user) references users(id) on delete cascade on update cascade,
	name varchar(48) not null default '' comment 'name of anonymous commenter',
	contacturl varchar(255) not null default '' comment 'contact url for anonymous commenter',
	html text not null comment 'html format of comment text, generated from markdown',
	markdown text not null comment 'editable version of comment text'
);

create table lego_votes (
	id smallint unsigned primary key auto_increment,
	lego smallint unsigned not null comment 'lego model this comment was posted to',
	foreign key (lego) references lego_models(id) on delete cascade on update cascade,
	voter smallint unsigned not null default 0 comment 'user who voted.  points to id but can’t use foreign key due to needing a non-null unlinked option for unique index.',
	ip int unsigned not null default 0 comment 'ip address of anonymous voter.  use inet_aton() to store and inet_ntoa() to retrieve',
	unique(lego,voter,ip),
	vote tinyint unsigned not null default 3,
	posted int not null default 0 comment 'unix timestamp when the vote was cast', key(posted)
);

create trigger lego_model_added after insert on lego_models for each row
insert into contributions set
	srctbl='lego_models',
	id=new.id,
	conttype='lego',
	posted=new.posted,
	url=concat('/lego/', new.url),
	author=1,
	title=new.title,
	preview=concat('<p><img class=lego src="/lego/data/', new.url, '.png"></p>'),
	hasmore=1;

delimeter ;;
create trigger lego_model_changed after update on lego_models for each row
begin
	update contributions set
		url=concat('/lego/', new.url),
		title=new.title,
		preview=concat('<p><img class=lego src="/lego/data/', new.url, '.png"></p>')
	where srctbl='lego_models' and id=new.id;
	update contributions set
		url=concat('/lego/', new.url, '#comments'),
		title=new.title
	where srctbl='lego_comments' and id in (select * from (select c.id from lego_comments as c where c.lego=new.id) as cl);
end;;

create trigger lego_comment_added after insert on lego_comments for each row
insert into contributions set
	srctbl='lego_comments',
	id=new.id,
	conttype='comment',
	posted=new.posted,
	url=concat('/lego/', (select url from lego_models where id=new.lego), '#comments'),
	author=new.user,
	authorname=new.name,
	authorurl=new.contacturl,
	title=(select title from lego_models where id=new.lego),
	preview=left(new.html, locate('</p>', new.html) + 3),
	hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4;

create trigger lego_comment_changed after update on lego_comments for each row
update contributions set
	preview=left(new.html, locate('</p>', new.html) + 3),
	hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4
where srctbl='lego_comments' and id=new.id;

create trigger lego_comment_deleted after delete on lego_comments for each row
delete from contributions where srctbl='lego_comments' and id=old.id;
