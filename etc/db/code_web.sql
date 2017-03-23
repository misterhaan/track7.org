create table code_web_usetype (
	id tinyint unsigned primary key auto_increment,
	name varchar(16) not null
);
insert into code_web_usetype (name) values
	('website'),
	('web application'),
	('userscript'),
	('snippet'),
	('api');

create table code_web_reqinfo (
	id tinyint unsigned primary key auto_increment,
	name varchar(16) not null,
	url varchar(64) not null default '' comment 'where to find more information on meeting this requirement'
);
insert into code_web_reqinfo (name, url) values
	('apache httpd', 'http://httpd.apache.org/'),
	('layout classes', '/code/web/layout'),
	('mysql', 'https://www.mysql.com/'),
	('pear::db', 'http://pear.php.net/package/DB/'),
	('php', 'http://php.net/'),
	('userscript', 'https://greasyfork.org/');

create table code_web_scripts (
	id smallint unsigned primary key auto_increment,
	url varchar(32) not null comment 'unique portion of the url to this script',
	unique(url),
	name varchar(32) not null,
	released int not null default 0 comment 'date this script was released',
	key(released),
	usetype tinyint unsigned not null,
	foreign key(usetype) references code_web_usetype(id) on update cascade on delete cascade,
	download varchar(64) not null comment 'url to the download for this script on another site (blank for local zip)',
	github varchar(16) not null default '' comment 'github repository name for this script (blank for none)',
	wiki varchar(32) not null default '' comment 'main auwiki article for this script (blank for none)',
	descmd text comment 'markdown version of the script description, for editing',
	deschtml text comment 'html version of the script description, generated from descmd, for display',
	instmd text comment 'markdown version of the script instructions, for editing',
	insthtml text comment 'html version of the script instructions, generated from instmd, for display'
);

create table code_web_requirements (
	script smallint unsigned not null,
	req tinyint unsigned not null,
	primary key(script, req),
	foreign key(script) references code_web_scripts(id) on update cascade on delete cascade,
	foreign key(req) references code_web_reqinfo(id) on update cascade on delete cascade
);

create table code_web_comments (
	id smallint unsigned primary key auto_increment,
	script smallint unsigned not null comment 'script this comment was posted to',
	foreign key (script) references code_web_scripts(id) on update cascade on delete cascade,
	posted int not null default 0 comment 'unix timestamp when the comment was posted',
	key (posted),
	user smallint unsigned comment 'user who posted this comment, or null to use custom name and contacturl',
	foreign key (user) references users(id) on update cascade on delete cascade,
	name varchar(48) not null default '' comment 'name of anonymous commenter',
	contacturl varchar(255) not null default '' comment 'contact url for anonymous commenter',
	html text not null default '' comment 'html format of comment text, generated from markdown',
	markdown text not null default '' comment 'editable version of comment text'
);

create trigger code_web_script_added after insert on code_web_scripts for each row
insert into contributions set
	srctbl='code_web_scripts',
	id=new.id,
	conttype='code',
	posted=new.released,
	url=concat('/code/web/', new.url),
	author=1,
	title=new.name,
	preview=left(new.deschtml, locate('</p>', new.deschtml) + 3),
	hasmore=1;

delimiter ;;
create trigger code_web_script_changed after update on code_web_scripts for each row
begin
	update contributions as c set
		c.url=concat('/code/web/', new.url),
		c.title=new.name,
		c.preview=left(new.deschtml, locate('</p>', new.deschtml) + 3)
	where srctbl='code_web_scripts' and id=new.id;
	update contributions set
		url=concat('/code/web/', new.url, '#comments'),
		title=new.name
	where srctbl='code_web_comments' and id in (select * from (select id from code_web_comments where script=new.id) as c1);
end;;

create trigger code_web_comment_added after insert on code_web_comments for each row
insert into contributions set
	srctbl='code_web_comments',
	id=new.id,
	conttype='comment',
	posted=new.posted,
	url=concat('/code/web/', (select url from code_web_scripts where id=new.script), '#comments'),
	author=new.user,
	authorname=new.name,
	authorurl=new.contacturl,
	title=(select name from code_web_scripts where id=new.script),
	preview=left(new.html, locate('</p>', new.html) + 3),
	hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4;

create trigger code_web_comment_changed after update on code_web_comments for each row
update contributions set
	author=new.user,
	authorname=new.name,
	authorurl=new.contacturl,
	preview=left(new.html, locate('</p>', new.html) + 3),
	hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4
where srctbl='code_web_comments' and id=old.id;

create trigger code_web_comment_deleted after delete on code_web_comments for each row
delete from contributions where srctbl='code_web_comments' and id=old.id;
