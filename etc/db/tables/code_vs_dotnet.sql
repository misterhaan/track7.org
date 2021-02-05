create table code_vs_dotnet (
	id tinyint unsigned primary key auto_increment,
	version varchar(16) not null comment '.net version such as 4.5.1'
);

insert into code_vs_dotnet (version) values
	('1.1'),
	('2.0'),
	('4.0'),
	('4.5'),
	('5.0');
