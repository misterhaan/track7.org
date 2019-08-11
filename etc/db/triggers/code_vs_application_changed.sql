delimiter ;;
create trigger code_vs_application_changed after update on code_vs_applications for each row
begin
	update contributions as c set
		c.url=concat('/code/vs/', new.url),
		c.title=concat(new.name, ' v', (select concat(major, '.', minor, '.', revision) from code_vs_releases where id=c.id)),
		c.preview=left(new.deschtml, locate('</p>', new.deschtml) + 3)
	where srctbl='code_vs_releases' and id in (select * from (select r.id from code_vs_releases as r where r.application=new.id) as rels);
	update contributions set
		url=concat('/code/vs/', new.url, '#comments'),
		title=new.name
	where srctbl='code_vs_comments' and id in (select * from (select id from code_vs_comments where application=new.id) as c1);
end;;
