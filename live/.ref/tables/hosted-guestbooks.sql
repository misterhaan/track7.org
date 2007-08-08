create table gbbooks (
  id smallint unsigned auto_increment primary key,
  name varchar(15) unique,
  pass varchar(32),
  notify varchar(255),
  header text,
  entry text,
  footer text,
);
create table gbentries (
  id smallint unsigned auto_increment primary key,
  bookid smallint unsigned,
  entry text
);
