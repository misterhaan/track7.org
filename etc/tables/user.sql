# users flags:
# 0x01 - has unread mail
# 0x80 - godmode

create table users (
  uid smallint unsigned primary key auto_increment,
  login varchar(32) unique not null,
  pass varchar(32) not null,
  allowedip varchar(255) not null,
  style tinyint unsigned not null default 1,
  flags tinyint unsigned not null default 0
);

create table userprofiles (
  uid smallint unsigned primary key,
  signature text,
  avatar varchar(50)
);
insert into userprofiles (uid) select uid from users;

create table userstats (
  uid smallint unsigned primary key auto_increment,
  since int,
  lastlogin int,
  pageload int,
  signings tinyint unsigned not null default 0,
  comments smallint unsigned not null default 0,
  posts smallint unsigned not null default 0,
  discs tinyint unsigned not null default 0,
  rounds tinyint unsigned not null default 0,
  customrank varchar(16) default null,
  rank enum(
    'am radio',
    'shortwave',
    'fm radio',
    'television',
    'radar',
    'infrared',
    'visible',
    'ultraviolet',
    'x-ray',
    'gamma ray'
  ) not null default 'am radio',
  fans smallint unsigned not null default 0
);

# usercontact flags:
# 0x01 - show e-mail
# 0x02 - notify of new content
# 0x04 - notify of minor updates
# 0x08 - weekly message e-mail
# 0x10 - daily message e-mail

create table usercontact (
  uid smallint unsigned primary key auto_increment,
  email varchar(64),
  website varchar(64),
  icq varchar(10),
  aim varchar(32),
  steam varchar(32),
  flags tinyint unsigned not null default 0
);

# usermessages flags:
# 0x01 - read
# 0x02 - replied

create table usermessages (
  id int unsigned primary key auto_increment,
  instant int,
  touid smallint unsigned, index(touid),
  fromuid smallint unsigned not null default 0, index(fromuid),
  name varchar(45),
  contact varchar(100),
  subject varchar(100),
  message text,
  flags tinyint unsigned not null default 0
);

create table userguestsonline (
  session char(32) primary key,
  lastpage int
);

create table usersongs (
  uid smallint unsigned,
  instant int,
  title varchar(255),
  artist varchar(255),
  album varchar(255),
  length varchar(6),
  filename varchar(255)
);

create table userfriends (
  fanuid smallint unsigned,
  frienduid smallint unsigned,
  primary key(fanuid, frienduid)
);
