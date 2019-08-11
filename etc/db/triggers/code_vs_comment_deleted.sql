create trigger code_vs_comment_deleted after delete on code_vs_comments for each row
delete from contributions where srctbl='code_vs_comments' and id=old.id;
