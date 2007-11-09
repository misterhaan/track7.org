create table pensections (
  id varchar(8) primary key not null,
  name varchar(32) not null default '',
  description text,
  sort tinyint unsigned not null
);

create table penstories (
  id varchar(16) primary key not null,
  section varchar(8) not null, foreign key (section) references pensections (id),
  name varchar(64) not null default '',
  title varchar(128) not null default '',
  subtitle varchar(64) not null default '',
  pretitle varchar(64) not null default '',
  description text,
  posted varchar(10),
  sort tinyint unsigned not null
);

create table bln (
  name varchar(32) primary key not null,
  instant int, index(instant),
  tags varchar(255), index(tags),
  title varchar(128),
  post text
);
