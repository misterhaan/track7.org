create table hbthreads (
  id smallint unsigned auto_increment primary key,
  tags varchar(255), index(tags),
  title varchar(255),
  instant int,
  uid smallint unsigned, index (uid),
  posts smallint unsigned,
  lastpost smallint unsigned
);

create table hbposts (
  id smallint unsigned auto_increment primary key,
  thread smallint unsigned, index (thread),
  number smallint unsigned not null default 0,
  subject varchar(255),
  post text,
  history text not null default '',
  instant int,
  uid smallint unsigned, index (uid)
);
