create table blog_entries (
  id smallint unsigned primary key auto_increment,
  url varchar(32) not null comment 'unique portion of the url to this entry',
  unique(url),
  status enum(
    'draft',
    'published'
  ) not null default 'draft',
  key (status),
  posted int not null comment 'unix timestamp when the entry was published or last time the draft was saved',
  key (posted),
  title varchar(128) not null comment 'display title',
  content text not null
);

create table blog_tags (
  id smallint unsigned primary key auto_increment,
  name varchar(16) not null comment 'used for both display and links',
  unique (name),
  count smallint not null default 0 comment 'how many published entries use this tag',
  lastused int not null default 0 comment 'unix timestamp for the last time an entry was published using this tag',
  key (lastused),
  description text not null default ''
);

create table blog_entrytags (
  tag smallint unsigned not null,
  entry smallint unsigned not null,
  primary key (tag, entry),
  key (entry),
  foreign key (tag) references blog_tags(id) on delete cascade,
  foreign key (entry) references blog_entries(id) on delete cascade
);

create table blog_comments (
  id smallint unsigned primary key auto_increment,
  entry smallint unsigned not null comment 'entry this comment was posted to',
  foreign key (entry) references blog_entries(id) on delete cascade,
  posted int not null default 0 comment 'unix timestamp when the comment was posted',
  key (posted),
  user smallint unsigned comment 'user who posted this comment, or null to use custom name and contacturl',
  foreign key (user) references users(id) on delete cascade,
  name varchar(48) not null default '' comment 'name of anonymous commenter',
  contacturl varchar(255) not null default '' comment 'contact url for anonymous commenter',
  html text not null default '' comment 'html format of comment text, generated from markdown',
  markdown text not null default '' comment 'editable version of comment text'
);
