create table settings (
	user smallint unsigned primary key,
	foreign key(user) references users(id) on delete cascade on update cascade,
	timebase enum('server', 'gmt') not null default 'server' comment 'whether times should be based off server time (dst) or gmt',
	timeoffset smallint not null default 0 comment 'seconds to add to the timebase to get to local time for the user',
	emailnewmessage bool not null default true comment 'whether the user should be e-mailed when sent a message'
);
