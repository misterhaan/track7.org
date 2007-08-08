create table links (
  id smallint unsigned auto_increment primary key,
  catid tinyint unsigned, index(catid),
  url varchar(255),
  title varchar(64),
  tooltip varchar(255),
  description text
);

create table linkcats (
  id tinyint unsigned auto_increment primary key,
  pageid smallint unsigned, index(pageid),
  title varchar(64)
);
