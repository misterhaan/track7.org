create table friend (
	fan smallint unsigned not null comment 'user who marked a friend',
	friend smallint unsigned not null comment 'user chosen as a friend',
	primary key(fan, friend),
	foreign key(fan) references user(id) on delete cascade on update cascade,
	foreign key(friend) references user(id) on delete cascade on update cascade
);
