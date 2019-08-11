create table code_vs_lang (
	id tinyint unsigned primary key auto_increment,
	abbr varchar(4) not null,
	name varchar(16) not null
);

insert into code_vs_lang (abbr, name) values
	('vb', 'visual basic'),
	('c#', 'c#');
