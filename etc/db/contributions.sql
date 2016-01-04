create table contributions (
  srctbl enum('blog_comments', 'blog_entries') not null comment 'name of the table this activity is fully stored in',
  id smallint unsigned not null comment 'id of this activity in srctbl',
  primary key(srctbl, id),
  conttype enum('comment', 'post'),
  posted int not null,
  key(posted),
  url varchar(32) not null default '' comment 'url to this contribution (blank for site updates)',
  author smallint unsigned comment 'user who posted this contribution, or null to use custom name and contacturl',
  foreign key (author) references users(id),
  authorname varchar(48) not null default '' comment 'name of anonymous contributor',
  authorurl varchar(255) not null default '' comment 'contact url for anonymous contributor',
  title varchar(128) not null default'',
  preview text not null comment 'beginning text of this contribution or the entire contribution if small enough',
  hasmore bool not null default 0
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
