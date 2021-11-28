create table blog_tags (
	id smallint unsigned primary key auto_increment,
	name varchar(16) not null comment 'used for both display and links',
	unique (name),
	count smallint not null default 0 comment 'how many published entries use this tag',
	lastused int not null default 0 comment 'unix timestamp for the last time an entry was published using this tag',
	key (lastused),
	description text
