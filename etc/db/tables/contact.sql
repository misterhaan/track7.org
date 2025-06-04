create table contact (
	user smallint unsigned not null,
	foreign key(user) references user(id) on delete cascade on update cascade,
	type enum('email', 'website', 'twitter', 'facebook', 'github', 'deviantart', 'steam', 'twitch') not null,
	primary key(user,type),
	contact varchar(64) not null comment 'unique part of contact url',
	unique(type,contact),
	visibility enum('none', 'friends', 'users', 'all') not null default 'friends' comment 'who can see this contact'
);
