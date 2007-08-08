create table dgdiscs (
  id tinyint unsigned auto_increment primary key,
  mfgr varchar(32),
  name varchar(32),
  `type` enum(
    'distance driver',
    'fairway driver',
    'multi-purpose',
    'putt / approach',
    'specialty'
  ),
  speed tinyint unsigned,
  glide tinyint unsigned,
  turn tinyint,
  fade tinyint,
  popularity tinyint unsigned default 0
);

create table dgcaddy (
  id smallint unsigned auto_increment primary key,
  uid smallint unsigned not null, index (uid),
  discid tinyint unsigned, index (discid),
  `status` enum ('use', 'lost', 'sold') not null default 'use',
  mass tinyint unsigned,
  color varchar(16),
  comments varchar(255)
);

create table dgcourses (
  id tinyint unsigned auto_increment primary key,
  name varchar(64),
  location varchar(64),
  holes tinyint unsigned not null default 18,
  parlist varchar(53) not null default '3|3|3|3|3|3|3|3|3|3|3|3|3|3|3|3|3|3',
  par tinyint unsigned not null default 54,
  rounds tinyint unsigned not null default 0,
  comments text
);

create table dgrounds (
  id smallint unsigned not null auto_increment primary key,
  uid smallint unsigned not null, index (uid),
  courseid tinyint unsigned not null, index (courseid),
  roundtype enum(
    'single',
    'doubles - best disc'
  ), index (roundtype),
  instant int,
  scorelist varchar(53),
  score tinyint unsigned,
  bestdisc smallint unsigned,
  worstdisc smallint unsigned,
  comments text
);

create table dgplayerstats (
  uid smallint unsigned not null primary key,
  skill smallint,
  aces smallint not null default 0,
  birds smallint not null default 0,
  pars smallint not null default 0,
  bogies smallint not null default 0,
  doubles smallint not null default 0,
  holes smallint not null default 0
);

create table dgcoursestats (
  id tinyint unsigned auto_increment primary key,
  courseid tinyint unsigned not null, index (courseid),
  roundtype enum(
    'single',
    'doubles - best disc'
  ), index (roundtype),
  avglist varchar(71),
  avgscore smallint unsigned
);
