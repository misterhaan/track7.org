create table code_vs_lang (
  id tinyint unsigned primary key auto_increment,
  abbr varchar(4) not null,
  name varchar(16) not null
);
insert into code_vs_lang (abbr, name) values
  ('vb', 'visual basic'),
  ('c#', 'c#');

create table code_vs_dotnet (
  id tinyint unsigned primary key auto_increment,
  version varchar(16) not null comment '.net version such as 4.5.1'
);
insert into code_vs_dotnet (version) values
  ('1.1'),
  ('2.0'),
  ('4.0');

create table code_vs_studio (
  version decimal(4,1) unsigned primary key comment 'internal type version, as in visual studio 2015 is version 14',
  abbr varchar(6) not null default '' comment 'short display name such as 2015 or vb6',
  name varchar(32) not null default '' comment 'display name such as visual studio 2015 or visual basic 6'
);
insert into code_vs_studio (version, abbr, name) values
  (6.0, 'vb6', 'visual studio 6.0'),
  (7.1, '2003', 'visual studio .net 2003'),
  (8.0, '2005', 'visual studio 2005'),
  (9.0, '2008', 'visual studio 2008'),
  (10.0, '2010', 'visual studio 2010'),
  (12.0, '2013', 'visual studio 2013'),
  (14.0, '2015', 'visual studio 2015'),
  (15.0, '2017', 'visual studio 2017');

create table code_vs_applications (
  id smallint unsigned primary key auto_increment,
  url varchar(32) not null comment 'unique portion of the url to this application',
  unique(url),
  name varchar(32) not null,
  github varchar(16) not null default '' comment 'github repository name for this application',
  wiki varchar(32) not null default '' comment 'main auwiki article for this application',
  descmd text comment 'markdown version of the application description, for editing',
  deschtml text comment 'html version of the application description, generated from descmd, for display'
);

create table code_vs_comments (
  id smallint unsigned primary key auto_increment,
  application smallint unsigned not null comment 'application this comment was posted to',
  foreign key (application) references code_vs_applications(id) on update cascade on delete cascade,
  posted int not null default 0 comment 'unix timestamp when the comment was posted',
  key (posted),
  user smallint unsigned comment 'user who posted this comment, or null to use custom name and contacturl',
  foreign key (user) references users(id) on update cascade on delete cascade,
  name varchar(48) not null default '' comment 'name of anonymous commenter',
  contacturl varchar(255) not null default '' comment 'contact url for anonymous commenter',
  html text not null default '' comment 'html format of comment text, generated from markdown',
  markdown text not null default '' comment 'editable version of comment text'
);

create table code_vs_releases (
  id smallint unsigned primary key auto_increment,
  application smallint unsigned not null,
  foreign key (application) references code_vs_applications(id) on update cascade on delete cascade,
  released int not null default 0 comment 'date this release was released',
  key(released),
  major tinyint unsigned not null default 0,
  minor tinyint unsigned not null default 0,
  revision tinyint unsigned not null default 0,
  unique(application, major, minor, revision),
  lang tinyint unsigned not null,
  foreign key (lang) references code_vs_lang(id) on update cascade on delete cascade,
  dotnet tinyint unsigned,
  foreign key (dotnet) references code_vs_dotnet(id) on update cascade on delete cascade,
  studio decimal(4,1) unsigned,
  foreign key (studio) references code_vs_studio(version) on update cascade on delete cascade,
  binurl varchar(128),
  bin32url varchar(128) comment 'url to the 32-bit binary if binurl is a 64-bit binary',
  srcurl varchar(128),
  changelog text not null default '' comment 'html change log which is usually a bulleted list.  generated from markdown which is not saved.'
);

delimiter ;;
create trigger code_vs_application_changed after update on code_vs_applications for each row
begin
  update contributions as c set
    c.url=concat('/code/vs/', new.url),
    c.title=concat(new.name, ' v', (select concat(major, '.', minor, '.', revision) from code_vs_releases where id=c.id)),
    c.preview=left(new.deschtml, locate('</p>', new.deschtml) + 3)
  where srctbl='code_vs_releases' and id in (select * from (select r.id from code_vs_releases as r where r.application=new.id) as rels);
  update contributions set
    url=concat('/code/vs/', new.url, '#comments'),
    title=new.name
  where srctbl='code_vs_comments' and id in (select * from (select id from code_vs_comments where application=new.id) as c1);
end;;

create trigger code_vs_release_added after insert on code_vs_releases for each row
insert into contributions set
  srctbl='code_vs_releases',
  id=new.id,
  conttype='code',
  posted=new.released,
  url=concat('/code/vs/', (select url from code_vs_applications where id=new.application)),
  author=1,
  title=concat((select name from code_vs_applications where id=new.application), ' v', new.major, '.', new.minor, '.', new.revision),
  preview=left((select deschtml from code_vs_applications where id=new.application), locate('</p>', (select deschtml from code_vs_applications where id=new.application)) + 3),
  hasmore=1;

create trigger code_vs_comment_added after insert on code_vs_comments for each row
insert into contributions set
  srctbl='code_vs_comments',
  id=new.id,
  conttype='comment',
  posted=new.posted,
  url=concat('/code/vs/', (select url from code_vs_applications where id=new.application), '#comments'),
  author=new.user,
  authorname=new.name,
  authorurl=new.contacturl,
  title=(select name from code_vs_applications where id=new.application),
  preview=left(new.html, locate('</p>', new.html) + 3),
  hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4;

create trigger code_vs_comment_changed after update on code_vs_comments for each row
update contributions set
  author=new.user,
  authorname=new.name,
  authorurl=new.contacturl,
  preview=left(new.html, locate('</p>', new.html) + 3),
  hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4
where srctbl='code_vs_comments' and id=old.id;

create trigger code_vs_comment_deleted after delete on code_vs_comments for each row
delete from contributions where srctbl='code_vs_comments' and id=old.id;
