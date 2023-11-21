create table vote (
	post int unsigned not null,
	foreign key(post) references post(id) on delete cascade on update cascade,
	user smallint unsigned not null default 0 comment 'user who voted, or zero with ip for anonymous votes.  unenforced foreign key to user(id)',
	ip int unsigned not null default 0 comment 'ip address of anonymous voter or 0 if user vote.  use inet_aton() to store and inet_ntoa() to retrieve',
	primary key (post, user, ip),
	instant datetime not null comment 'when this was cast',
	key(instant),
	vote tinyint unsigned not null default 3
);
