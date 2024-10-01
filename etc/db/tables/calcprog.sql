create table calcprog (
	id varchar(32) primary key comment 'unique portion of the download url for this program',
	post int unsigned not null,
	foreign key(post) references post(id) on delete cascade on update cascade,
	subject enum ('math', 'science', 'art') not null,
	model enum('ti-85',	'ti-86') not null,
	ticalc int unsigned not null comment 'id of this program on ticalc.org',
	description text
);
