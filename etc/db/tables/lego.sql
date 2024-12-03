create table lego (
	id varchar(32) primary key comment 'unique portion of the url to this lego model',
	post int unsigned not null unique,
	foreign key(post) references post(id) on delete cascade on update cascade,
	html text not null comment 'html description of this lego model, generated from markdown',
	markdown text not null comment 'description of this lego model in markdown (for editing)',
	pieces smallint unsigned not null default 0 comment 'number of pieces in this model',
	key(pieces)
);
