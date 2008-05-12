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
            if($post->frienduid) {
?>
          <img src="/style/friend.png" alt="friend" title="<?=$post->login; ?> is your friend" />
<?
            }
            if($post->avatar) {
?>
          <a href="/user/<?=$post->login; ?>/"><img class="avatar" alt="" src="/user/avatar/<?=$post->login; ?>.<?=$post->avatar; ?>" /></a>
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
          <?=$post->post; ?>

          <?=HB::ShowHistory($user, $post->history); ?>

<?
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
              <a href="/user/sendmessage.php?to=<?=$post->login; ?>" title="send <?=$post->login; ?> a private message"><img src="/style/pm.png" alt="" />pm</a>
<?
	          if($post->showemail)
	            echo '              <a href="mailto:' . auText::SafeEmail($post->email) . '" title="send ' . $post->login . ' an e-mail"><img src="/style/email.png" alt="" />e-mail</a>' . "\n";
	          if($post->website)
	            echo '              <a href="' . $post->website . '" title="visit ' . $post->login . '\'s website"><img src="/style/www.png" alt="" />www</a>' . "\n";
	          if($user->Valid && !$post->frienduid)
	            echo '              <a href="/user/friends.php?add=' . $post->login . '" title="add ' . $post->login . ' to your friend list"><img src="/style/friend-add.png" alt="" />add friend</a>' . "\n";
?>
            </div>
<?
          }
          if($user->Valid && $user->ID == $post->uid || $user->GodMode) {
?>
            <a href="/hb/thread<?=$thread->id; ?>/edit=<?=$post->id; ?>" title="edit the above post"><img src="/style/edit.png" alt="" />edit</a>
            <a href="/hb/thread<?=$thread->id; ?>/delete=<?=$post->id; ?>" title="delete the above post"><img src="/style/del.png" alt="" />delete</a>
<?
          }
?>
            <a href="/hb/thread<?=$thread->id; ?>/reply<?=$post->id; ?>" title="quote the above post in a new reply" class="quote"><img src="/style/reply-quote.png" alt="" />quote</a>
          </div>
        </td>
      </tr></table>

<?
        }
        $page->SplitLinks('', '/hb/thread' . $thread->id . '/');
        // DO:  show add reply link for all but last page; form on last page
        if(isLastPage($db)) {
          $page->Heading("add a reply", "reply");
          $form = HB::GetPostForm($db, $user, 'reply', null, $thread, null, '/hb/thread' . $thread->id . '/reply');
          $form->WriteHTML($user->Valid);
        } else {
          // DO:  go to last page reply form
?>
      <p><a href="/hb/thread<?=$thread->id; ?>/skip=<?=getLastPageSkip($db); ?>#reply">add a reply</a></p>
<?
        }
      }
	  	$page->End();
	  	die;
	  }
  }
  $page->Show404();

  function isLastPage(&$db) {
    $skip = is_numeric($_GET[$db->split_skip]) ? $_GET[$db->split_skip] : $db->split_dskip;
    $show = is_numeric($_GET[$db->split_show]) ? $_GET[$db->split_show] : $db->split_dshow;
    return $db->split_count <= $skip + $show;
  }

  function getLastPageSkip(&$db) {
    $show = is_numeric($_GET[$db->split_show]) ? $_GET[$db->split_show] : $db->split_dshow;
    $count = $db->split_count - 1;
    return $count - $count % $show;
  }
?>
