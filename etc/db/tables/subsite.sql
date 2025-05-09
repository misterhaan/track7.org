create table subsite (
	id varchar(16) not null primary key comment 'also the directory name where this subsite can be found',
	feature tinyint comment 'order in which this subsite is featured in the main menu',
	type varchar(16) not null comment 'type of content this subsite contains',
	name varchar(32) not null comment 'display name used in the main menu',
	calltoaction varchar(128) not null comment 'shown as a tooltip in the main menu',
	verb varchar(16) not null comment 'past-tense verb to use when content is added to this subsite'
);
