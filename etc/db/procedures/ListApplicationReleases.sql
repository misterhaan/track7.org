create procedure ListApplicationReleases(application_id smallint unsigned)
select
	r.released,
	concat(r.major, '.', r.minor, '.', r.revision) as version,
	r.binurl,
	r.bin32url,
	r.srcurl,
	r.changelog,
	l.abbr as lang,
	if(n.version is not null, concat('.net ', n.version), '') as dotnet,
	s.name as studio
from code_vs_releases as r
left join code_vs_lang as l on l.id=r.lang
left join code_vs_dotnet as n on n.id=r.dotnet
left join code_vs_studio as s on s.version=r.studio
where r.application=application_id
	and not exists (select 1 from code_vs_releases as nr where nr.application=application_id and nr.major=r.major and nr.minor=r.minor and nr.revision>r.revision)
order by r.major desc, r.minor desc;
