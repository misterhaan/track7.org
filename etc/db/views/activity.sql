create view activity as
	select
		'comment' as type,
		c.instant,
		p.title,
		concat(p.url, '#comments') as url,
		u.id as author,
		coalesce(nullif(u.displayname, ''), u.username, c.name) as name,
		if(u.username is not null and u.username != '', concat('/user/', u.username, '/'), c.contact) as contact,
		'commented on' as verb,
		left(c.html, locate('</p>', c.html) + 3) as preview,
		length(c.html) - length(replace(c.html, '</p>', '')) > 4 as hasmore
		from comment as c
			left join post as p on p.id=c.post
			left join user as u on u.id=c.user
	union select
		s.type,
		p.instant,
		p.title,
		p.url,
		p.author,
		coalesce(nullif(u.displayname, ''), u.username) as name,
		concat('/user/', u.username, '/') as contact,
		s.verb,
		p.preview,
		p.hasmore
		from post as p
			left join subsite as s on s.id=p.subsite
			left join user as u on u.id=p.author
		where p.published=true and nullif(p.preview, '') is not null
	order by instant desc;
