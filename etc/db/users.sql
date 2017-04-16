create table users (
	id smallint unsigned primary key auto_increment,
	level tinyint unsigned not null default 1 comment 'access level (higher number is more access)',
	username varchar(32) not null comment 'limited to certain characters since it will be used in a url',
	unique(username),
	displayname varchar(32) not null default '' comment 'display name in case people want something like John Smith',
	avatar varchar(255) not null default '' comment 'url to avatar (may be offsite)'
);

create table users_settings (
	id smallint unsigned primary key,
	timebase enum('server', 'gmt') not null default 'server' comment 'whether times should be based off server time (dst) or gmt',
	timeoffset smallint not null default 0 comment 'seconds to add to the timebase to get to local time for the user',
	emailnewmsg bool not null default 1 comment 'whether the user should be e-mailed when sent a message',
	unreadmsgs tinyint unsigned not null default 0 comment 'number of conversations with unread messages',
	foreign key(id) references users(id) on delete cascade on update cascade
);

create table users_email (
	id smallint unsigned primary key,
	email varchar(64) not null default '',
	vis_email enum('none', 'friends', 'users', 'all') not null default 'none' comment 'who can see the email address',
	key(email),
	foreign key(id) references users(id) on delete cascade on update cascade
);

create table users_profiles (
	id smallint unsigned primary key,
	website varchar(64) not null default '' comment 'url to personal website',
	vis_website enum('friends', 'all') not null default 'all',
	twitter varchar(16) not null default '',
	vis_twitter enum('friends', 'all') not null default 'friends',
	google varchar(64) not null default '',
	vis_google enum('friends', 'all') not null default 'friends',
	facebook varchar(32) not null default '',
	vis_facebook enum('friends', 'all') not null default 'friends',
	steam varchar(32) not null default '',
	vis_steam enum('friends', 'all') not null default 'friends',
	foreign key(id) references users(id) on delete cascade on update cascade
);

create table users_friends (
	fan smallint unsigned not null,
	friend smallint unsigned not null,
	primary key(fan, friend),
	foreign key(fan) references users(id) on delete cascade on update cascade,
	foreign key(friend) references users(id) on delete cascade on update cascade
);

create table users_stats (
	id smallint unsigned primary key,
	registered int not null,
	key(registered),
	lastlogin int not null,
	key(lastlogin),
	fans smallint unsigned not null default 0,
	key(fans),
	comments smallint unsigned not null default 0,
	key(comments),
	replies smallint unsigned not null default 0,
	key(replies),
	foreign key(id) references users(id) on delete cascade on update cascade
);

create table users_messages (
	id smallint unsigned primary key auto_increment,
	sent int not null comment 'timestamp when the message was sent',
	key(sent),
	conversation smallint unsigned not null default 0,
	key(conversation),
	author smallint unsigned comment 'user who sent this message',
	foreign key(author) references users(id) on delete cascade on update cascade,
	name varchar(48) not null default '' comment 'name of anonymous message sender',
	contacturl varchar(255) not null default '' comment 'contact url for anonymous message sender',
	subject varchar(128) not null default '' comment 'message subject',
	html text not null default '' comment 'html format of message text, generated from markdown',
	markdown text not null default '' comment 'editable version of message text',
	hasread bool not null default 0 comment 'whether this message has been read',
	key(hasread),
	hasreplied bool not null default 0 comment 'whether a reply to this message has been sent'
);

create table users_conversations (
	id smallint unsigned auto_increment,
	key(id),
	thisuser smallint unsigned not null comment 'one of the users in this conversation',
	foreign key(thisuser) references users(id) on delete cascade on update cascade,
	thatuser smallint unsigned not null comment 'the other user in this conversation, or 0 if anonymous',
	primary key(thisuser, thatuser),
	latestmessage smallint unsigned,
	foreign key(latestmessage) references users_messages(id) on delete set null on update cascade
);

delimiter ;;
create function GetConversationID(user1 smallint unsigned, user2 smallint unsigned)
returns smallint unsigned
begin
	if user2 is null
	then
		set user2 = 0;
	end if;
	if(select not exists(select id from users_conversations where thisuser=user1 and thatuser=user2))
	then
		insert into users_conversations (thisuser, thatuser) values (user1, user2);
		if user2 > 0 and user1 != user2
		then
			insert into users_conversations (id, thisuser, thatuser) values (last_insert_id(), user2, user1);
		end if;
	end if;
	return (select id from users_conversations where thisuser=user1 and thatuser=user2);
end;;
delimiter ;

create table transition_users (
	id smallint unsigned primary key,
	olduid smallint unsigned not null,
	foreign key(id) references users(id) on delete cascade on update cascade
);
