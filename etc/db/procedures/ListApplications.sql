create procedure ListApplications()
deterministic
reads sql data
select a.id, a.url, a.name, a.deschtml, concat(r.major, '.', r.minor, '.', r.revision) as version, r.released, r.binurl, r.bin32url
	from code_vs_applications as a
	join code_vs_releases as r on r.application=a.id and not exists (
		select * from code_vs_releases as nr where nr.application=a.id and nr.released>r.released)
	order by released desc;
