<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'hb.inc';

  // look up thread
  if(is_numeric($_GET['id'])) {
    $thread = 'select id, tags, title from hbthreads where id=\'' . addslashes($_GET['id']) . '\'';
	  if($thread = $db->GetRecord($thread, 'error looking up thread', 'thread not found')) {
	  	$page->Start($thread->title, $thread->title, 'tags:&nbsp; ' . HB::TagLinks($thread->tags));
	  	$posts = 'select u.login, s.rank, p.id, p.uid, p.instant, p.subject, p.post, p.history, r.signature, r.avatar, c.flags&' . _FLAG_USERCONTACT_SHOWEMAIL . ' as showemail, c.email, c.website, f.frienduid from hbposts as p left join users as u on p.uid=u.uid left join usercontact as c on u.uid=c.uid left join userstats as s on u.uid=s.uid left join userprofiles as r on u.uid=r.uid left join userfriends as f on f.frienduid=u.uid and f.fanuid=\'' . $user->ID . '\' where p.thread=\'' . $thread->id . '\' order by instant';
	  	if($posts = $db->GetSplit($posts, _FORUM_POSTS_PER_PAGE, 0, '', '', 'error getting posts for this thread', 'this thread is empty!', true, true)) {
        while($post = $posts->NextRecord()) {
?>
      <table class="post" cellspacing="0"><tr>
        <td class="userinfo">
<?
          if($post->uid > 0) {
?>
          <a href="/user/<?=$post->login; ?>/"><?=$post->login; ?></a>
<?
            if($post->avatar) {
?>
          <img class="avatar" alt="" src="/user/avatar/<?=$post->login; ?>.<?=$post->avatar; ?>" />
<?
            }
?>
          <div class="frequency" title="frequency"><?=$post->rank; ?></div>
<?
          } else {
?>
          anonymous

<?
          }
?>
        </td>
        <td>
          <div class="head">
            <div class="subject"><a class="ref" id="p<?=$post->id; ?>" href="#p<?=$post->id; ?>">subject:</a>&nbsp; <span class="response"><?=$post->subject; ?></span></div>
            <div class="time">posted:&nbsp; <span class="response"><?=strtolower($user->tzdate('g:i:s a, M d, Y', $post->instant)); ?></span></div>
          </div>
          <p>
            <?=$post->post; ?>

          </p>
<?
          if($post->history) {
?>
          <p class="history">
<?=substr($post->history, 0, strpos($post->history, "\r") ? -8 : -7); ?>

          </p>
<?
	        }
	        if($post->uid > 0 && $post->signature) {
?>
          <p class="signature">
            <?=$post->signature; ?>

          </p>
<?
          }
?>
          <div class="foot">
<?
	        if($post->uid > 0) {
?>
            <div class="userlinks">
              <a href="/user/sendmessage.php?to=<?=$post->login; ?>" title="send <?=$post->login; ?> a private message">pm</a>
<?
	          if($post->showemail)
	            echo '              | <a href="mailto:' . TEXT::safemail($post->email) . '" title="send ' . $post->login . ' an e-mail">e-mail</a>' . "\n";
	          if($post->website)
	            echo '              | <a href="' . $post->website . '" title="visit ' . $post->login . '\'s website">www</a>' . "\n";
	          if($user->Valid && !$post->frienduid)
	            echo '              | <a href="/user/friends.php?add=' . $post->login . '" title="add ' . $post->login . ' to your friend list">+friend</a>' . "\n";
?>
            </div>
<?
          }
          if($user->Valid && $user->id == $post->uid || $user->GodMode) {
?>
            <a href="/hb/thread<?=$thread->id; ?>/edit=<?=$post->id; ?>" title="edit the above post">edit</a> |
            <a href="/hb/thread<?=$thread->id; ?>/delete=<?=$post->id; ?>" title="delete the above post">delete</a> |
<?
          }
?>
            <a href="/hb/thread<?=$thread->id; ?>/reply<?=$post->id; ?>" title="quote the above post in a new reply">reply</a>
          </div>
        </td>
      </tr></table>

<?
        }
?>
      <p><a href="/hb/thread<?=$thread->id; ?>/reply">add a reply</a></p>
<?
        $page->SplitLinks();
      }
	  	$page->End();
	  	die;
	  }
  }
  $page->Show404();
?>
