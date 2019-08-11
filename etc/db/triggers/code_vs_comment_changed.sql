create trigger code_vs_comment_changed after update on code_vs_comments for each row
update contributions set
	author=new.user,
	authorname=new.name,
	authorurl=new.contacturl,
	preview=left(new.html, locate('</p>', new.html) + 3),
	hasmore=length(new.html)-length(replace(new.html, '</p>', ''))>4
where srctbl='code_vs_comments' and id=old.id;
