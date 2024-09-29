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

delimeter ;;
create trigger guide_added after insert on guides for each row
begin
	if new.status='published' then
		insert into contributions set
			srctbl='guides',
			id=new.id,
			conttype='guide',
			posted=new.updated,
			url=concat('/guides/', new.url),
			author=1,
			title=new.title,
			preview=new.summary,
			hasmore=1;
	end if;
end;;

drop trigger if exists guide_changed;
delimiter ;;
create trigger guide_changed after update on guides for each row
begin
	if new.status='published' then
		if not old.status='published' then
			insert into contributions set
				srctbl='guides',
				id=new.id,
				conttype='guide',
				posted=new.updated,
				url=concat('/guides/', new.url),
				author=1,
				title=new.title,
				preview=new.summary,
				hasmore=1;
		else
			update contributions set
				title=new.title,
				preview=new.summary
			where srctbl='guides' and id=new.id;
			update contributions set
				title=new.title
			where srctbl='guide_comments' and id in (select * from (select c.id from guide_comments as c where c.guide=new.id) as cl);
		end if;
	end if;
end;;

create trigger guide_deleted after delete on guides for each row
delete from contributions where srctbl='guides' and id=old.id;

drop trigger if exists guide_comment_added;
create trigger guide_comment_added after insert on guide_comments for each row
insert into contributions set
	srctbl='guide_comments',
	id=new.id,
	conttype='comment',
	posted=new.posted,
	url=concat('/guides/', (select url from guides where id=new.guide), '#comments'),
	author=new.user,
	authorname=new.name,
	authorurl=new.contacturl,
	title=(select title from guides where id=new.guide),
	preview=left(new.html, locate('</p>', new.html) + 3),
	hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4;

create trigger guide_comment_changed after update on guide_comments for each row
update contributions set
	preview=left(new.html, locate('</p>', new.html) + 3),
	hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4
where srctbl='guide_comments' and id=new.id;

create trigger guide_comment_deleted after delete on guide_comments for each row
delete from contributions where srctbl='guide_comments' and id=old.id;
