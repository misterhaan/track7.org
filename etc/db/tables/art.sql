create table art (
	id varchar(32) not null primary key comment 'unique portion of the url to this art (usually a url-friendly version of the title)',
	post int unsigned not null unique,
	foreign key(post) references post(id) on delete cascade on update cascade,
	ext varchar(4) not null comment 'file extension for the image file',
	deviation varchar(64) not null default '' comment 'portion of deviantart url after deviantart.com/art/',
	html text not null comment 'html description of this art, generated from markdown',
	markdown text not null comment 'description of this art in markdown (for editing)'
);
