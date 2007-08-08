create table albumpages (
  name varchar(16) primary key,
  image varchar(32),
  tooltip varchar(255),
  sort tinyint unsigned
);
create table albumgroups (
  id tinyint unsigned auto_increment primary key,
  page varchar(16), index (page),
  title varchar(128),
  sort tinyint unsigned
);
create table albumphotos (
  id smallint unsigned auto_increment primary key,
  `group` tinyint unsigned, index (`group`),
  caption varchar(20),
  url varchar(32),
  story text,
  sort tinyint unsigned
);
