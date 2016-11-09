create table stories_series (
  id tinyint unsigned primary key auto_increment,
  url varchar(32) not null comment 'unique part of the url to this series',
  key(url),
  lastposted int unsigned not null default 0 comment 'latest value of stories.posted for this series',
  key(lastposted),
  numstories tinyint not null default 0,
  title varchar(128) not null,
  descmd text not null default '',
  deschtml text not null default ''
);

create table stories (
  id smallint unsigned primary key auto_increment,
  published bool not null default 0 comment 'whether the story is published',
  key(published),
  posted int not null default 0 comment 'unix timestamp when the story was posted, or the order the story was written in if date posted wasn’t recorded',
  key(posted),
  series tinyint unsigned comment 'which series this story is part of, or null if it stands alone',
  foreign key(series) references stories_series(id) on delete cascade on update cascade,
  number tinyint unsigned not null default 0 comment 'if part of a series, number this story is in the series',
  key(series,number),
  url varchar(32) not null,
  key(url),
  title varchar(128) not null,
  descmd text not null default '',
  deschtml text not null default '',
  storymd text not null default '',
  storyhtml text not null default ''
);

create table stories_comments (
  id smallint unsigned primary key auto_increment,
  story smallint unsigned not null comment 'story this comment was posted to',
  foreign key (story) references stories(id) on delete cascade on update cascade,
  posted int not null default 0 comment 'unix timestamp when the comment was posted',
  key (posted),
  user smallint unsigned comment 'user who posted this comment, or null to use custom name and contacturl',
  foreign key (user) references users(id) on delete cascade on update cascade,
  name varchar(48) not null default '' comment 'name of anonymous commenter',
  contacturl varchar(255) not null default '' comment 'contact url for anonymous commenter',
  html text not null default '' comment 'html format of comment text, generated from markdown',
  markdown text not null default '' comment 'editable version of comment text'
);

delimiter ;;
create trigger story_added after insert on stories for each row
begin
  if new.published=1 then
    insert into contributions set
      srctbl='stories',
      id=new.id,
      conttype='story',
      posted=new.posted,
      url=concat('/pen/', new.url),
      author=1,
      title=new.title,
      preview=new.deschtml,
      hasmore=1;
  end if;
end;;

delimiter ;;
create trigger story_changed after update on stories for each row
begin
  if new.published=1 then
    if old.published=0 then
      insert into contributions set
        srctbl='stories',
        id=new.id,
        conttype='story',
        posted=new.posted,
        url=concat('/pen/', new.url),
        author=1,
        title=new.title,
        preview=new.deschtml,
        hasmore=1;
    else
      update contributions set
        title=new.title,
        preview=new.deschtml
      where srctbl='stories' and id=new.id;
    end if;
    update contributions set
      title=new.title
    where srctbl='stories_comments' and id in (select * from (select c.id from stories_comments as c left join stories as s on s.id=c.story where s.id=new.id) as c1);
  end if;
end;;

create trigger story_deleted after delete on stories for each row
delete from contributions where srctbl='stories' and id=old.id;

create trigger story_comment_added after insert on stories_comments for each row
insert into contributions set
  srctbl='stories_comments',
  id=new.id,
  conttype='comment',
  posted=new.posted,
  url=concat('/pen/', (select url from stories where id=new.story), '#comments'),
  author=new.user,
  authorname=new.name,
  authorurl=new.contacturl,
  title=(select title from stories where id=new.story),
  preview=left(new.html, locate('</p>', new.html) + 3),
  hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4;

create trigger story_comment_changed after update on stories_comments for each row
update contributions set
  author=new.user,
  authorname=new.name,
  authorurl=new.contacturl,
  preview=left(new.html, locate('</p>', new.html) + 3),
  hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4
where srctbl='stories_comments' and id=new.id;

create trigger story_comment_deleted after delete on stories_comments for each row
delete from contributions where srctbl='stories_comments' and id=old.id;
