create table diablo2chars (
  id smallint unsigned auto_increment primary key,
  owner smallint unsigned,
  name varchar(32),
  class enum(
    'necromancer',
    'druid',
    'barbarian',
    'paladin',
    'sorceress',
    'assassin',
    'amazon'
  ),
  `level` tinyint unsigned,
  difficulty enum(
    'normal',
    'nightmare',
    'hell'
  ),
  act tinyint unsigned,
  flags tinyint unsigned,
  quests int unsigned
);

create table fanmissions (
  number tinyint auto_increment primary key,
  `type` varchar(3),
  title varchar(100),
  `file` varchar(150),
  review text,
  date varchar(8)
);

create table civ3civs (
  id tinyint unsigned auto_increment primary key,
  formal varchar(11),
  noun varchar(13),
  adjective varchar(12),
  title varchar(16),
  leader varchar(14),
  description text,
  strength1 tinyint unsigned, key(strength1),
  strength2 tinyint unsigned, key(strength2),
  tech1 tinyint unsigned,
  tech2 tinyint unsigned,
  unitname varchar(18),
  unitbase tinyint unsigned, key(unitbase),
  color varchar(6)
);

create table civ3civstrengths (
  id tinyint unsigned auto_increment primary key,
  name varchar(12),
  description varchar(255)
);
