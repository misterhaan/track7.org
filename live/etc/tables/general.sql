# t7pages flags:
# 0x01 - hide from not logged-in users
# 0x02 - hide from logged-in users
create table t7pages (
  id smallint unsigned primary key auto_increment,
  parent smallint unsigned,
  sort tinyint unsigned,
  url varchar(255) not null,
  key (url),
  name varchar(55),
  tooltip varchar(255),
  flags tinyint unsigned default 0
);
# pages flags:
# 0x01 - hide from not logged-in users
# 0x02 - hide from logged-in users
# 0x04 - don't log hits for this page
# 0x08 - enabled for comments
# 0x10 - has children
create table pages (
  id smallint unsigned primary key auto_increment,
  parent smallint unsigned,
  sort tinyint unsigned,
  urlin varchar(255) not null, key (urlin),
  urlout varchar(255) not null,
  name varchar(55),
  tooltip varchar(255),
  description text,
  keywords text,
  flags tinyint unsigned default 0
);

create table ratings (
  id smallint unsigned primary key auto_increment,
  type enum(
    'lego',
    'sketch',
    'digital',
    'task',
    'guide'
  ),
  selector varchar(50),
  unique (type, selector),
  rating float not null default 0,
  votes tinyint unsigned not null default 0
);

create table votes (
  id int unsigned primary key auto_increment,
  ratingid smallint unsigned not null,
  vote tinyint not null default 0,
  uid smallint unsigned,
  ip varchar(15),
  unique(ratingid, uid, ip),
  time int
);

create table updates (
  instant int,
  `change` varchar(255)
);

create table comments (
  page varchar(255),
  instant int,
  uid smallint unsigned not null default 0, index(uid),
  name varchar(45),
  url varchar(100),
  comments text
);

create table tasks (
  id smallint unsigned auto_increment primary key,
  instant int,
  project enum (
    'track7',
    'meat'
  ) not null default 'track7', index(project),
  status enum (
    'new',
    'started',
    'done',
    'cancelled'
  ) not null default 'new', index(status),
  area tinyint unsigned, index(area),
  parentarea tinyint unsigned, index(parentarea),
  title varchar(255)
);
create table t7taskarea (
  id tinyint unsigned auto_increment primary key,
  parent smallint unsigned,
  name varchar(50)
);

create table ready (
  id tinyint unsigned auto_increment primary key,
  heading varchar(127),
  img varchar(63),
  caption text
);

create table bln (
  name varchar(32) not null primary key,
  instant int,
  cat enum(
    'question',
    'answer',
    'complaint'
  ), index (cat),
  title varchar(128),
  post text
);

create table browserengines (
  browser varchar(15) primary key,
  engine varchar(8)
);

create table browserissues (
  browser varchar(16),
  issue text
);
