create table tag (
	name varchar(16) not null comment 'used for both display and links',
	subsite varchar(16) not null,
	primary key(name,subsite),
	foreign key(subsite) references subsite(id) on update cascade on delete cascade,
	description text not null
);
