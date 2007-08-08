create table t7hits (
  instant int not null, index (instant),
  ip varchar(15) not null,
  page varchar(255) not null,
  referrer varchar(255) not null,
  useragent varchar(255) not null
);

create table statdate (
  `date` char(10) primary key,
  uhits int unsigned not null default 0,
  rhits int unsigned not null default 0
);

create table statmonth (
  month char(7) primary key,
  uhits int unsigned not null default 0,
  rhits int unsigned not null default 0,
  days tinyint unsigned not null default 0
);

create table statip (
  ip varchar(15) primary key,
  hits int unsigned not null default 0
);

create table statpage (
  page varchar(255) primary key,
  hits int unsigned not null default 0,
  section varchar(16)
);

create table statreferrer (
  referrer varchar(255) primary key,
  hits int unsigned not null default 0,
  site varchar(255)
);

create table statuseragent (
  useragent varchar(255) primary key,
  hits int unsigned not null default 0,
  browser varchar(16),
  os varchar(16)
);

create table stat404 (
  page varchar(255) primary key,
  hits int unsigned not null default 0,
  notes varchar(255)
);
