create table guides (
  id smallint unsigned auto_increment primary key,
  url varchar(32) not null comment 'unique portion of the url to this guide (usually a url-friendly version of the title)', unique(url),
  status enum('draft', 'submitted', 'published', 'rejected') not null default 'draft', key(status),
  title varchar(128) not null default '' comment 'display title (may contain characters that aren’t safe for html)',
  summary text not null default '' comment 'one-paragraph summary of this guide, in html (generated from summary_markdown)',
  summary_markdown text not null default '' comment 'one-paragraph summary of this guide, in markdown',
  posted int comment 'unix timestamp when the guide was published or last time the draft was saved',
  updated int comment 'unix timestamp when the guide was last published or updated', key(updated),
  author smallint unsigned comment 'user who posted this guide',
  foreign key (author) references users(id) on delete cascade,
  level enum('beginner','intermediate','advanced') not null default 'intermediate',
  rating float unsigned not null default 3, key(rating),
  votes smallint unsigned not null default 0, key(votes),
  views smallint unsigned not null default 0, key(views)
);

create table guide_pages (
  id smallint unsigned auto_increment primary key,
  guide smallint unsigned not null, foreign key (guide) references guides(id) on delete cascade,
  number tinyint unsigned not null comment 'page number, used for showing pages in the correct order', key(number),
  key(guide, number),
  heading varchar(128) not null comment 'display title for this page (may contain characters that aren’t safe for html)',
  html text not null comment 'html format of page text, generated from markdown',
  markdown text not null default '' comment 'editable version of page text'
);

create table guide_tags (
  id smallint unsigned primary key auto_increment,
  name varchar(16) not null comment 'used for both display and links',
  unique (name),
  count smallint not null default 0 comment 'how many published guides use this tag',
  lastused int not null default 0 comment 'unix timestamp for the last time a guide was published or updated using this tag',
  key (lastused),
  description text not null default ''
);

create table guide_taglinks (
  tag smallint unsigned not null,
  guide smallint unsigned not null,
  primary key (tag, guide),
  key (guide),
  foreign key (tag) references guide_tags(id) on delete cascade,
  foreign key (guide) references guides(id) on delete cascade
);

create table guide_comments (
  id smallint unsigned primary key auto_increment,
  guide smallint unsigned not null comment 'guide this comment was posted to',
  foreign key (guide) references guides(id) on delete cascade,
  posted int not null default 0 comment 'unix timestamp when the comment was posted',
  key (posted),
  user smallint unsigned comment 'user who posted this comment, or null to use custom name and contacturl',
  foreign key (user) references users(id) on delete cascade,
  name varchar(48) not null default '' comment 'name of anonymous commenter',
  contacturl varchar(255) not null default '' comment 'contact url for anonymous commenter',
  html text not null default '' comment 'html format of comment text, generated from markdown',
  markdown text not null default '' comment 'editable version of comment text'
);

create table guide_votes (
  id smallint unsigned primary key auto_increment,
  guide smallint unsigned not null comment 'guide this vote is for',
  foreign key (guide) references guides(id) on delete cascade,
  voter smallint unsigned not null default 0 comment 'user who voted.  points to id but can’t use foreign key due to needing a non-null unlinked option for unique index.',
  ip int unsigned not null default 0 comment 'ip address of anonymous voter.  use inet_aton() to store and inet_ntoa() to retrieve',
  unique(guide,voter,ip),
  vote tinyint unsigned not null default 3,
  posted int not null default 0 comment 'unix timestam when the vote was cast', key(posted)
);
