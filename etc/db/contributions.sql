create table contributions (
  srctbl enum('blog_comments', 'blog_entries', 'guides', 'guide_comments', 'photos', 'photos_comments', 'art', 'art_comments', 'lego_models', 'lego_comments', 'stories', 'stories_comments') not null comment 'name of the table this activity is fully stored in',
  id smallint unsigned not null comment 'id of this activity in srctbl',
  primary key(srctbl, id),
  conttype enum('comment', 'guide', 'post', 'photo', ' art', 'lego', 'story'),
  posted int not null,
  key(posted),
  url varchar(32) not null default '' comment 'url to this contribution (blank for site updates)',
  author smallint unsigned comment 'user who posted this contribution, or null to use custom name and contacturl',
  foreign key (author) references users(id) on delete cascade on update cascade,
  authorname varchar(48) not null default '' comment 'name of anonymous contributor',
  authorurl varchar(255) not null default '' comment 'contact url for anonymous contributor',
  title varchar(128) not null default'',
  preview text not null comment 'beginning text of this contribution or the entire contribution if small enough',
  hasmore bool not null default 0
);

create table contributions_srctbls (
  id tinyint unsigned not null auto_increment primary key,
  name varchar(32) not null,
  unique(name),
  conttype tinyint unsigned not null,
  foreign key (conttype) references contributions_types(id) on delete cascade on update cascade
);

create table contribution_types (
  id tinyint unsigned not null auto_increment primary key,
  name varchar(16) not null
);

create trigger blog_comment_added after insert on blog_comments for each row
insert into contributions set
  srctbl='blog_comments',
  id=new.id,
  conttype='comment',
  posted=new.posted,
  url=concat('/bln/', (select url from blog_entries where id=new.entry), '#comments'),
  author=new.user,
  authorname=new.name,
  authorurl=new.contacturl,
  title=(select title from blog_entries where id=new.entry),
  preview=left(new.html, locate('</p>', new.html) + 3),
  hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4;

create trigger blog_comment_changed after update on blog_comments for each row
update contributions set
  author=new.user,
  authorname=new.name,
  authorurl=new.contacturl,
  preview=left(new.html, locate('</p>', new.html) + 3),
  hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4
where srctbl='blog_comments' and id=new.id;

create trigger blog_comment_deleted after delete on blog_comments for each row
delete from contributions where srctbl='blog_comments' and id=old.id;

drop trigger if exists blog_entry_added;
delimiter ;;
create trigger blog_entry_added after insert on blog_entries for each row
begin
  if new.status='published' then
    insert into contributions set
      srctbl='blog_entries',
      id=new.id,
      conttype='post',
      posted=new.posted,
      url=concat('/bln/', new.url),
      author=1,
      title=new.title,
      preview=left(new.content, locate('</p>', new.content) + 3),
      hasmore=length(new.content)-length(replace(new.content, '</p>', ''))>4;
  end if;
end;;

drop trigger if exists blog_entry_changed;
delimiter ;;
create trigger blog_entry_changed after update on blog_entries for each row
begin
  if new.status='published' then
    if old.status='draft' then
      insert into contributions set
        srctbl='blog_entries',
        id=new.id,
        conttype='post',
        posted=new.posted,
        url=concat('/bln/', new.url),
        author=1,
        title=new.title,
        preview=left(new.content, locate('</p>', new.content) + 3),
        hasmore=length(new.content)-length(replace(new.content, '</p>', ''))>4;
    else
      update contributions set
        title=new.title,
        preview=left(new.content, locate('</p>', new.content) + 3),
        hasmore=length(new.content)-length(replace(new.content, '</p>', ''))>4
      where srctbl='blog_entries' and id=new.id;
    end if;
    update contributions set
      title=new.title
    where srctbl='blog_comments' and id in (select * from (select c.id from blog_comments as c left join blog_entries as e on e.id=c.entry where e.id=new.id) as c1);
  end if;
end;;

create trigger blog_entry_deleted after delete on blog_entries for each row
delete from contributions where srctbl='blog_entries' and id=old.id;

delimeter ;;
create trigger guide_added after insert on guides for each row
begin
  if new.status='published' then
    insert into contributions set
      srctbl='guides',
      id=new.id,
      conttype='guide',
      posted=new.updated,
      url=concat('/guides/', new.url, '/1'),
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
        url=concat('/guides/', new.url, '/1'),
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
  url=concat('/guides/', (select url from guides where id=new.guide), '/1#comments'),
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

create trigger photo_added after insert on photos for each row
insert into contributions set
  srctbl='photos',
  id=new.id,
  conttype='photo',
  posted=new.posted,
  url=concat('/album/', new.url, '/'),
  author=1,
  title=new.caption,
  preview=concat('<p><img class=photo src="/album/photos/', new.url, '.jpeg"></p>'),
  hasmore=1;

delimiter ;;
create trigger photo_changed after update on photos for each row
begin
  update contributions set
    title=new.caption
  where srctbl='photos' and id=new.id;
  update contributions set
    title=new.caption
  where srctbl='photos_comments' and id in (select * from (select c.id from photos_comments as c where c.photo=new.id) as cl);
end;;

drop trigger photo_comment_added;
create trigger photo_comment_added after insert on photos_comments for each row
insert into contributions set
  srctbl='photos_comments',
  id=new.id,
  conttype='comment',
  posted=new.posted,
  url=concat('/album/', (select url from photos where id=new.photo), '#comments'),
  author=new.user,
  authorname=new.name,
  authorurl=new.contacturl,
  title=(select caption from photos where id=new.photo),
  preview=left(new.html, locate('</p>', new.html) + 3),
  hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4;

create trigger photo_comment_changed after update on photos_comments for each row
update contributions set
  preview=left(new.html, locate('</p>', new.html) + 3),
  hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4
where srctbl='photos_comments' and id=new.id;

create trigger photo_comment_deleted after delete on photos_comments for each row
delete from contributions where srctbl='photos_comments' and id=old.id;

drop trigger art_added;
create trigger art_added after insert on art for each row
insert into contributions set
  srctbl='art',
  id=new.id,
  conttype='art',
  posted=new.posted,
  url=concat('/art/', new.url),
  author=1,
  title=new.title,
  preview=concat('<p><img class=art src="/art/img/', new.url, '.', (select ext from image_formats where id=new.format), '"></p>'),
  hasmore=1;

drop trigger art_changed;
delimeter ;;
create trigger art_changed after update on art for each row
begin
  update contributions set
    url=concat('/art/', new.url),
    title=new.title,
    preview=concat('<p><img class=art src="/art/img/', new.url, '.', (select ext from image_formats where id=new.format), '"></p>')
  where srctbl='art' and id=new.id;
  update contributions set
    url=concat('/art/', new.url, '#comments'),
    title=new.title
  where srctbl='art_comments' and id in (select * from (select c.id from art_comments as c where c.art=new.id) as cl);
end;;

create trigger art_comment_added after insert on art_comments for each row
insert into contributions set
  srctbl='art_comments',
  id=new.id,
  conttype='comment',
  posted=new.posted,
  url=concat('/art/', (select url from art where id=new.art), '#comments'),
  author=new.user,
  authorname=new.name,
  authorurl=new.contacturl,
  title=(select title from art where id=new.art),
  preview=left(new.html, locate('</p>', new.html) + 3),
  hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4;

create trigger art_comment_changed after update on art_comments for each row
update contributions set
  preview=left(new.html, locate('</p>', new.html) + 3),
  hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4
where srctbl='art_comments' and id=new.id;

create trigger art_comment_deleted after delete on art_comments for each row
delete from contributions where srctbl='art_comments' and id=old.id;
