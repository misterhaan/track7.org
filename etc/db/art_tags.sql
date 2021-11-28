create table art_tags (
	id smallint unsigned primary key auto_increment,
	name varchar(16) not null comment 'used for both display and links',
	unique (name),
	count smallint not null default 0 comment 'how many art use this tag',
	lastused int not null default 0 comment 'unix timestamp for the last time art was posted using this tag',
	key (lastused),
	description text
);
insert into art_tags (name, description) values
	('sketch', '<p>i try to get my sketches into the scanner before i lose the paper they’re on.  i started out sketching in pencil but got more comfortable in pen while working a summer job where i had pens but not pencils available.  my sketches are posted with very little editing after scanning.</p>'),
	('digital', '<p>my digital art is either entirely created within the gimp (jasc paint shop pro for the older ones) or i start with a picture or image from the internet.</p>'),
	('cover', '<p>i used to make mix tapes and then compilation cds.  i’d start with a theme and title, select songs, sometimes blend them together using audacity (cool edit at first), and then design cover art.  track listings are included with the description of each cover art.</p>');
