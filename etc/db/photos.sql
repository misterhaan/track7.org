create table photos (
  id smallint unsigned auto_increment primary key,
  url varchar(32) not null comment 'unique portion of the url to this guide (usually a url-friendly version of the caption)', unique(url),
  youtube varchar(16) not null default '' comment 'youtube video id, or blank if this is a still photo',
  posted int comment 'unix timestamp when this photo was posted',
  key(posted),
  taken int comment 'unix timestamp when this photo was taken (typically read from exif data)',
  key(taken),
  year smallint unsigned not null default 0 comment 'year this photo was taken',
  key(year),
  caption varchar(32) not null comment 'title for this photo (should fit under the thumbnail)',
  story text not null default '' comment 'html description of this photo, generated from storymd',
  storymd text not null default '' comment 'description of this photo in markdown (for editing)'
);

create table photos_tags (
  id smallint unsigned primary key auto_increment,
  name varchar(16) not null comment 'used for both display and links',
  unique (name),
  count smallint not null default 0 comment 'how many photos use this tag',
  lastused int not null default 0 comment 'unix timestamp for the last time a photo was posted using this tag',
  key (lastused),
  description text not null default ''
);

create table photos_taglinks (
  tag smallint unsigned not null,
  photo smallint unsigned not null,
  primary key (tag, photo),
  key (photo),
  foreign key (tag) references photos_tags(id) on delete cascade on update cascade,
  foreign key (photo) references photos(id) on delete cascade on update cascade
);

create table photos_comments (
  id smallint unsigned primary key auto_increment,
  photo smallint unsigned not null comment 'photo this comment was posted to',
  foreign key (photo) references photos(id) on delete cascade on update cascade,
  posted int not null default 0 comment 'unix timestamp when the comment was posted',
  key (posted),
  user smallint unsigned comment 'user who posted this comment, or null to use custom name and contacturl',
  foreign key (user) references users(id) on delete cascade on update cascade,
  name varchar(48) not null default '' comment 'name of anonymous commenter',
  contacturl varchar(255) not null default '' comment 'contact url for anonymous commenter',
  html text not null default '' comment 'html format of comment text, generated from markdown',
  markdown text not null default '' comment 'editable version of comment text'
);
