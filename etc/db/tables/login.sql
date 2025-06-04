create table login (
	site enum ('google', 'twitter', 'github', 'deviantart', 'steam', 'twitter') not null,
	id varchar(64) not null comment 'id of account on external site',
	primary key(site, id),
	user smallint unsigned not null,
	foreign key(user) references user(id) on delete cascade on update cascade,
	name varchar(64) comment 'display name of this profile',
	url varchar(128) comment 'url to this profile on the external site',
	avatar varchar(255) comment 'url to the avatar for this profile',
	linkavatar bool not null default false comment 'whether the user chose the avatar from this profile'
);
