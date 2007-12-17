create table taginfo (
  type enum('guides', 'photos', 'entries') not null default 'guides',
  name varchar(63) not null, primary(type, name),
  count smallint unsigned not null default 0, index(count),
  description text
);
