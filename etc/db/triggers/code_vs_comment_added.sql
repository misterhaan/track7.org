create trigger code_vs_comment_added after insert on code_vs_comments for each row
insert into contributions set
	srctbl='code_vs_comments',
	id=new.id,
	conttype='comment',
	posted=new.posted,
	url=concat('/code/vs/', (select url from code_vs_applications where id=new.application), '#comments'),
	author=new.user,
	authorname=new.name,
	authorurl=new.contacturl,
	title=(select name from code_vs_applications where id=new.application),
	preview=left(new.html, locate('</p>', new.html) + 3),
	hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4;
