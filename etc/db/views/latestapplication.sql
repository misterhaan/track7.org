create view latestapplication as
	select
		a.id,
		p.id as post,
		p.instant,
		a.name,
		concat(r.major, '.', r.minor, '.', r.revision) as version,
		a.description,
		r.binurl,
		r.bin32url
	from application as a
	left join post as p on p.id=a.post
	left join `release` as r on r.application=a.id
	left join `release` as latest on latest.application=a.id and latest.instant>r.instant
	where latest.application is null
	order by p.instant
;
