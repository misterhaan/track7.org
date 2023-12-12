create view tagusage as
	select p.subsite, pt.tag, count(1) as count, max(p.instant) as lastused
	from post_tag as pt join post as p on p.id=pt.post and p.published=true
	group by p.subsite, pt.tag order by lastused
;
