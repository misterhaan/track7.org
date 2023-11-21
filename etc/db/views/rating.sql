create view rating as
	select post, round((sum(vote)+3)/(count(1)+1),2) as rating, count(1) as votecount
	from vote
	group by post
;
