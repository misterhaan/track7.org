create table transition_login (
  id smallint unsigned primary key,
  login varchar(32) unique not null comment 'username from the deprecated local login',
  pass varchar(96) not null comment 'password from the deprecated local login',
  foreign key(id) references users(id)
);

create table login_remembered (
  series char(16) not null primary key comment 'used for lookup',
  tokenhash char(88) not null comment 'used for validation',
  expires int not null,
  key(expires),
  user smallint unsigned not null,
  foreign key(user) references users(id)
);

create table login_google (
  id smallint unsigned primary key auto_increment,
  user smallint unsigned not null,
  foreign key(user) references users(id),
  sub varchar(32) not null comment 'google subscriber id',
  unique(sub),
  profile varchar(64) not null default '' comment 'url to this google id profile'
);

create table login_twitter (
  id smallint unsigned primary key auto_increment,
  user smallint unsigned not null,
  foreign key(user) references users(id),
  user_id bigint unsigned not null comment 'twitter user id',
  unique(user_id),
  profile varchar(64) not null default '' comment 'url to this twitter profile'
);

create table login_facebook (
  id smallint unsigned primary key auto_increment,
  user smallint unsigned not null,
  foreign key(user) references users(id),
  extid bigint unsigned not null comment 'facebook user id',
  unique(extid),
  profile varchar(64) not null default '' comment 'url to this facebook profile'
);
