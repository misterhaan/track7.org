create table image_formats (
  id tinyint unsigned auto_increment primary key,
  ext varchar(4) not null, unique(ext)
);
insert into image_formats (ext) values ('png'), ('jpg');

create table art (
  id smallint unsigned auto_increment primary key,
  url varchar(32) not null comment 'unique portion of the url to this art', unique(url),
  format tinyint unsigned not null, foreign key (format) references image_formats(id) on delete cascade on update cascade,
  posted int not null default 0 comment 'unix timestamp when this art was posted, or 0 if not recorded', key(posted),
  title varchar(32) not null default '',
  deschtml text not null default '' comment 'html description of this art, generated from descmd',
  descmd text not null default '' comment 'description of this art in markdown (for editing)',
  rating float unsigned not null default 3, key(rating),
  votes smallint unsigned not null default 0, key(votes)
);

create table art_tags (
  id smallint unsigned primary key auto_increment,
  name varchar(16) not null comment 'used for both display and links',
  unique (name),
  count smallint not null default 0 comment 'how many art use this tag',
  lastused int not null default 0 comment 'unix timestamp for the last time art was posted using this tag',
  key (lastused),
  description text not null default ''
);
insert into art_tags (name, description) values
  ('sketch', '<p>i try to get my sketches into the scanner before i lose the paper they’re on.  i started out sketching in pencil but got more comfortable in pen while working a summer job where i had pens but not pencils available.  my sketches are posted with very little editing after scanning.</p>'),
  ('digital', '<p>my digital art is either entirely created within the gimp (jasc paint shop pro for the older ones) or i start with a picture or image from the internet.</p>'),
  ('cover', '<p>i used to make mix tapes and then compilation cds.  i’d start with a theme and title, select songs, sometimes blend them together using audacity (cool edit at first), and then design cover art.  track listings are included with the description of each cover art.</p>');

create table art_taglinks (
  tag smallint unsigned not null,
  art smallint unsigned not null,
  primary key (tag, art),
  key (art),
  foreign key (tag) references art_tags(id) on delete cascade on update cascade,
  foreign key (art) references art(id) on delete cascade on update cascade
);

create table art_comments (
  id smallint unsigned primary key auto_increment,
  art smallint unsigned not null comment 'art this comment was posted to',
  foreign key (art) references art(id) on delete cascade,
  posted int not null default 0 comment 'unix timestamp when the comment was posted',
  key (posted),
  user smallint unsigned comment 'user who posted this comment, or null to use custom name and contacturl',
  foreign key (user) references users(id) on delete cascade on update cascade,
  name varchar(48) not null default '' comment 'name of anonymous commenter',
  contacturl varchar(255) not null default '' comment 'contact url for anonymous commenter',
  html text not null default '' comment 'html format of comment text, generated from markdown',
  markdown text not null default '' comment 'editable version of comment text'
);

create table art_votes (
  id smallint unsigned primary key auto_increment,
  art smallint unsigned not null comment 'art this vote is for',
  foreign key (art) references art(id) on delete cascade on update cascade,
  voter smallint unsigned not null default 0 comment 'user who voted.  points to id but can’t use foreign key due to needing a non-null unlinked option for unique index.',
  ip int unsigned not null default 0 comment 'ip address of anonymous voter.  use inet_aton() to store and inet_ntoa() to retrieve',
  unique(art,voter,ip),
  vote tinyint unsigned not null default 3,
  posted int not null default 0 comment 'unix timestamp when the vote was cast', key(posted)
);
