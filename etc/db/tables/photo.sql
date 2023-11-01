create table photo (
	id varchar(32) not null primary key comment 'unique portion of the url to this photo (usually a url-friendly version of the title)',
	post int unsigned not null unique,
	foreign key(post) references post(id) on delete cascade on update cascade,
	youtube varchar(16) not null default '' comment 'youtube video id, or blank if this is a still photo',
	taken datetime comment 'when this photo was taken (typically read from exif data)',
	key(taken),
	year smallint unsigned not null default 0 comment 'year this photo was taken',
	key(year),
	story text not null comment 'html description of this photo, generated from storymd',
	storymd text not null comment 'description of this photo in markdown (for editing)'
);
