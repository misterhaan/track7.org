create table oigroups (
  id tinyint unsigned auto_increment primary key,
  sort tinyint unsigned,
  title varchar(255)
);

create table oiforums (
  id tinyint unsigned auto_increment primary key,
  gid tinyint unsigned,
  sort tinyint unsigned,
  title varchar(255),
  description varchar(255),
  threads smallint unsigned default 0,
  posts int unsigned default 0,
  lastpost smallint unsigned
);

create table oithreads (
  id smallint unsigned auto_increment primary key,
  fid tinyint unsigned,
  title varchar(255),
  instant int,
  uid smallint unsigned, index (uid),
  posts smallint unsigned,
  lastpost smallint unsigned
);

create table oiposts (
  id smallint unsigned auto_increment primary key,
  tid smallint unsigned, index (tid),
  number smallint unsigned not null default 0,
  subject varchar(255),
  post text,
  history text not null default '',
  instant int,
  uid smallint unsigned, index (uid)
);
