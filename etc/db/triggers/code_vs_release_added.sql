create trigger code_vs_release_added after insert on code_vs_releases for each row
insert into contributions set
	srctbl='code_vs_releases',
	id=new.id,
	conttype='code',
	posted=new.released,
	url=concat('/code/vs/', (select url from code_vs_applications where id=new.application)),
	author=1,
	title=concat((select name from code_vs_applications where id=new.application), ' v', new.major, '.', new.minor, '.', new.revision),
	preview=left((select deschtml from code_vs_applications where id=new.application), locate('</p>', (select deschtml from code_vs_applications where id=new.application)) + 3),
	hasmore=1;
