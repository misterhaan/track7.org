create table auscripts (
  name varchar(32) not null primary key,
  language enum(
    'php',
    'js'
  ),
  title varchar(128),
  description text,
  flags tinyint unsigned
);
