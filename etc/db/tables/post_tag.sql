create table post_tag (
	post int unsigned not null,
	tag varchar(16) not null,
	primary key(post,tag),
	foreign key(post) references post(id) on update cascade on delete cascade,
	foreign key(tag) references tag(name) on update cascade on delete cascade
);
