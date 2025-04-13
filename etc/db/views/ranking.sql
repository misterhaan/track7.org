create view ranking as
	select user, sum(posts) as posts, sum(postrank) as postrank, sum(comments) as comments, sum(commentrank) as commentrank, sum(fans) as fans, sum(fanrank) as fanrank, sum(friends) as friends, sum(friendrank) as friendrank, sum(votes) as votes, sum(voterank) as voterank from (
		select s.user, s.posts, count(1) as postrank, 0 as comments, 0 as commentrank, 0 as fans, 0 as fanrank, 0 as friends, 0 as friendrank, 0 as votes, 0 as voterank
		from userstats as s
		left join userstats as bs on bs.posts>s.posts or bs.user=s.user
		group by s.user
		union all
		select s.user, 0 as posts, 0 as postrank, s.comments, count(1) as commentrank, 0 as fans, 0 as fanrank, 0 as friends, 0 as friendrank, 0 as votes, 0 as voterank
		from userstats as s
		left join userstats as bs on bs.comments>s.comments or bs.user=s.user
		group by s.user
		union all
		select s.user, 0 as posts, 0 as postrank, 0 as comments, 0 as commentrank, s.fans, count(1) as fanrank, 0 as friends, 0 as friendrank, 0 as votes, 0 as voterank
		from userstats as s
		left join userstats as bs on bs.fans>s.fans or bs.user=s.user
		group by s.user
		union all
		select s.user, 0 as posts, 0 as postrank, 0 as comments, 0 as commentrank, 0 as fans, 0 as fanrank, s.friends, count(1) as friendrank, 0 as votes, 0 as voterank
		from userstats as s
		left join userstats as bs on bs.friends>s.friends or bs.user=s.user
		group by s.user
		union all
		select s.user, 0 as posts, 0 as postrank, 0 as comments, 0 as commentrank, 0 as fans, 0 as fanrank, 0 as friends, 0 as friendrank, s.votes, count(1) as voterank
		from userstats as s
		left join userstats as bs on bs.votes>s.votes or bs.user=s.user
		group by s.user
	) as partialrank
	group by user
;
