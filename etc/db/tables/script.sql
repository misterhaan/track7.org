create table script (
	id varchar(32) primary key comment 'unique portion of the url to this script',
	post int unsigned not null unique,
	foreign key(post) references post(id) on delete cascade on update cascade,
	type enum('website', 'web application', 'userscript', 'snippet', 'api') not null,
	download varchar(64) not null comment 'url to the download for this script on another site (blank for local zip)',
	github varchar(16) not null default '' comment 'github repository name for this script (blank for none)',
	wiki varchar(32) not null default '' comment 'main auwiki article for this script (blank for none)',
	mddescription text comment 'markdown version of the script description, for editing',
	description text comment 'html version of the script description, generated from mddescripction, for display',
	mdinstructions text comment 'markdown version of the script instructions, for editing',
	instructions text comment 'html version of the script instructions, generated from mdinstructions, for display'
);
