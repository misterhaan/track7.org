create table subsite (
	id varchar(16) not null primary key comment 'also the directory name where this subsite can be found',
	name varchar(32) not null comment 'display name used in the main menu',
	calltoaction varchar(128) not null comment 'shown as a tooltip in the main menu',
	verb varchar(16) not null comment 'past-tense verb to use when content is added to this subsite'
);

-- create all subsites
-- insert into subsite (id, name, calltoaction, verb) values
-- ('pen', 'stories', 'read short fiction and a poem', 'storied'),
