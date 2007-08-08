create table compcds (
  id varchar(16) not null primary key,
  title varchar(32),
  coverart text,
  music text,
  `time` varchar(5),
  sort tinyint unsigned
);
create table comptracks (
  id smallint unsigned auto_increment null primary key,
  cd varchar(16) not null, index(cd),
  track tinyint unsigned,
  artist varchar(32),
  title varchar(32),
  `time` varchar(5)
);

create table legos (
  id varchar(32) not null primary key,
  name varchar(32),
  notes text,
  pieces tinyint unsigned not null default 0,
  minifigs tinyint unsigned not null default 0,
  adddate int
);

create table art (
  id varchar(32) not null primary key,
  type enum(
    'sketch',
    'digital'
  ), index(type),
  name varchar(32),
  description text,
  adddate int
);
