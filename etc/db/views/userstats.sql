create view userstats as
	select user, sum(posts) as posts, sum(comments) as comments, sum(fans) as fans, sum(friends) as friends, sum(votes) as votes
		from (
			select author as user, count(1) as posts, 0 as comments, 0 as fans, 0 as friends, 0 as votes
				from post
				where subsite!='forum'
				group by author
			union all
			select fc.user, count(1) as posts, -count(1) as comments, 0 as fans, 0 as friends, 0 as votes
				from post as p
					left join comment as fc on fc.post=p.id
					left join comment as ffc on ffc.post=p.id and fc.instant<ffc.instant
				where p.subsite='forum' and fc.user is not null and ffc.id is null
				group by fc.user
			union all
			select user, 0 as posts, count(1) as comments, 0 as fans, 0 as friends, 0 as votes
				from comment
				where user is not null
				group by user
			union all
			select friend as user, 0 as posts, 0 as comments, count(1) as fans, 0 as friends, 0 as votes
				from friend
				group by friend
			union all
			select fan as user, 0 as posts, 0 as comments, 0 as fans, count(1) as friends, 0 as votes
				from friend
				group by fan
			union all
			select user, 0 as posts, 0 as comments, 0 as fans, 0 as friends, count(1) as votes
				from vote
				where user>0
				group by user
		) as partialstats
	group by user
;
