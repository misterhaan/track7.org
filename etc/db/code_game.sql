create table code_game_engines (
	id tinyint unsigned primary key auto_increment,
	name varchar(16) not null
);
insert into code_game_engines (name) values
	('zzt'),
	('megazeux');

create table code_game_worlds (
	id smallint unsigned primary key auto_increment,
	url varchar(32) not null comment 'unique portion of the download url for this game world',
	unique(url),
	name varchar(64) not null,
	released int not null default 0 comment 'date this game world was released',
	key(released),
	engine tinyint unsigned not null,
	foreign key(engine) references code_game_engines(id) on update cascade on delete cascade,
	descmd text comment 'markdown version of the game world description, for editing',
	deschtml text comment 'html version of the game world description, generated from descmd, for display',
	dmzx int unsigned comment 'id of this gameworld in the digitalmzx.com vault'
);
