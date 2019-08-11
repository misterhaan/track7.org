create table code_vs_studio (
	version decimal(4,1) unsigned primary key comment 'internal type version, as in visual studio 2015 is version 14',
	abbr varchar(6) not null default '' comment 'short display name such as 2015 or vb6',
	name varchar(32) not null default '' comment 'display name such as visual studio 2015 or visual basic 6'
);

insert into code_vs_studio (version, abbr, name) values
	(6.0, 'vb6', 'visual studio 6.0'),
	(7.1, '2003', 'visual studio .net 2003'),
	(8.0, '2005', 'visual studio 2005'),
	(9.0, '2008', 'visual studio 2008'),
	(10.0, '2010', 'visual studio 2010'),
	(12.0, '2013', 'visual studio 2013'),
	(14.0, '2015', 'visual studio 2015'),
	(15.0, '2017', 'visual studio 2017'),
	(16.0, '2019', 'visual studio 2019');
