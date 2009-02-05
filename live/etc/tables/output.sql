create table photos (
  id varchar(30) not null primary key,
  youtubeid varchar(16),
  caption varchar(32),
  description text,
  added int not null default 0, index(added),
  taken int,
  tags varchar(255), index(tags)
);
