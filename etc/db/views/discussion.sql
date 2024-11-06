create view discussion as
	select
		p.id,
		p.title,
		group_concat(pt.tag order by pt.tag) as tags,
		fc.instant as startinstant,
		coalesce(nullif(fcu.displayname, ''), fcu.username, fc.name) as startername,
		if(fcu.id is null, fc.contact, concat('/user/', fcu.username)) as startercontact,
		(select count(1) from comment where post=p.id)-1 as replies,
		lc.instant as latestinstant,
		coalesce(nullif(lcu.displayname, ''), lcu.username, lc.name) as latestname,
		if(lcu.id is null, lc.contact, concat('/user/', lcu.username)) as latestcontact
	from post as p
	left join post_tag as pt on pt.post=p.id
	join comment as fc on fc.post=p.id
	left join comment as ffc on ffc.post=p.id and ffc.instant<fc.instant
	left join user as fcu on fcu.id=fc.user
	left join comment as lc on lc.post=p.id
	left join comment as llc on llc.post=p.id and llc.instant>lc.instant
	left join user as lcu on lcu.id=lc.user
	where p.subsite='forum' and ffc.id is null and llc.id is null
	group by p.id, fc.id, lc.id
	order by lc.instant desc
;
