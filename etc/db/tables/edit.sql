create table edit (
	id int unsigned primary key auto_increment,
	comment int unsigned not null comment 'comment that was edited',
	foreign key(comment) references comment(id) on update cascade on delete cascade,
	instant datetime comment 'when the comment was edited',
	key (comment,instant),
	user smallint unsigned not null comment 'user who made this edit',
	foreign key(user) references user(id) on update cascade on delete cascade
);
