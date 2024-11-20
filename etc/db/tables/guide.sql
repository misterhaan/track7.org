create table guide (
	id varchar(32) not null primary key comment 'unique portion of the url to this guide (usually a url-friendly version of the title)',
	post int unsigned not null,
	foreign key(post) references post(id) on delete cascade on update cascade,
	summary text not null comment 'one-paragraph summary of this guide, in markdown',
	updated datetime comment 'unix timestamp when the guide was last published or updated',
	key(updated),
	level enum('beginner','intermediate','advanced') not null default 'intermediate',
	views smallint unsigned not null default 0,
	key(views)
);
