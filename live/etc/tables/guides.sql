create table guides (
  id varchar(32) not null primary key,
  status enum(
    'new',
    'pending',
    'approved',
    'rejected'
  ) not null default 'new', index(status),
  tags varchar(255) not null,
  title varchar(128) not null,
  description text not null,
  skill enum(
    'beginner',
    'intermediate',
    'advanced'
  ),
  dateadded int,
  dateupdated int,
  pages tinyint unsigned not null default 0,
  author tinyint unsigned
)

create table guidepages (
  guideid varchar(32) not null,
  pagenum tinyint unsigned not null default 0,
  version tinyint not null default -1, index(guideid, version),
  entrytype enum(
    'bbcode',
    'html'
  ) not null default bbcode,
  heading varchar(128) not null,
  content text not null,
  primary key (guideid, pagenum, version)
);
