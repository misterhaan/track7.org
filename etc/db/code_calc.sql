create table code_calc_subject (
	id tinyint unsigned primary key auto_increment,
	name varchar(16) not null
);
insert into code_calc_subject (name) values
	('math'),
	('science'),
	('art');

create table code_calc_model (
	id tinyint unsigned primary key auto_increment,
	name varchar(8) not null
);
insert into code_calc_model (name) values
	('ti-85'),
	('ti-86');

create table code_calc_progs (
	id smallint unsigned primary key auto_increment,
	url varchar(32) not null comment 'unique portion of the download url for this program',
	unique(url),
	name varchar(32) not null,
	released int not null default 0 comment 'date this program was released',
	key(released),
	subject tinyint unsigned not null,
	foreign key(subject) references code_calc_subject(id) on update cascade on delete cascade,
	model tinyint unsigned not null,
	foreign key(model) references code_calc_model(id) on update cascade on delete cascade,
	descmd text comment 'markdown version of the program description, for editing',
	deschtml text comment 'html version of the program description, generated from descmd, for display'
);
