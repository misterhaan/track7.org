create table users (
  id smallint unsigned primary key auto_increment,
  level tinyint unsigned not null default 1 comment 'access level (higher number is more access)',
  username varchar(32) not null comment 'limited to certain characters since it will be used in a url',
  unique(username),
  displayname varchar(32) not null default '' comment 'display name in case people want something like John Smith',
  avatar varchar(255) not null default '' comment 'url to avatar (may be offsite)'
);

create table users_settings (
  id smallint unsigned primary key,
  timebase enum('server', 'gmt') not null default 'server' comment 'whether times should be based off server time (dst) or gmt',
  timeoffset smallint not null default 0 comment 'seconds to add to the timebase to get to local time for the user',
  foreign key(id) references users(id)
);

create table users_email (
  id smallint unsigned primary key,
  email varchar(64) not null default '',
  vis_email enum('none', 'friends', 'users', 'all') not null default 'none' comment 'who can see the email address',
  key(email),
  foreign key(id) references users(id)
);

create table users_profiles (
  id smallint unsigned primary key,
  website varchar(64) not null default '' comment 'url to personal website',
  vis_website enum('friends', 'all') not null default 'all',
  twitter varchar(16) not null default '',
  vis_twitter enum('friends', 'all') not null default 'friends',
  google varchar(64) not null default '',
  vis_google enum('friends', 'all') not null default 'friends',
  facebook varchar(32) not null default '',
  vis_facebook enum('friends', 'all') not null default 'friends',
  steam varchar(32) not null default '',
  vis_steam enum('friends', 'all') not null default 'friends',
  foreign key(id) references users(id)
);

create table users_friends (
  fan smallint unsigned not null,
  friend smallint unsigned not null,
  primary key(fan, friend),
  foreign key(fan) references users(id),
  foreign key(friend) references users(id)
);

create table users_stats (
  id smallint unsigned primary key,
  registered int not null,
  key(registered),
  lastlogin int not null,
  key(lastlogin),
  fans smallint unsigned not null default 0,
  key(fans),
  comments smallint unsigned not null default 0,
  key(comments),
  posts smallint unsigned not null default 0,
  key(posts),
  foreign key(id) references users(id)
);

create table transition_users (
  id smallint unsigned primary key,
  olduid smallint unsigned not null,
  foreign key(id) references users(id)
);
