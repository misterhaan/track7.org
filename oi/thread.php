<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  // give a 404 error for missing / non-numeric thread id
  if(!isset($_GET['tid']) || !is_numeric($_GET['tid']))
    $page->Show404();
  $thread = 'select fid, title from oithreads where id=' . $_GET['tid'];
  if($thread = $db->GetRecord($thread, 'error looking up thread title', 'no thread found with id ' . $_GET['tid'], true)) {
    $ftitle = 'select title from oiforums where id=' . $thread->fid;
    if($ftitle = $db->Get($ftitle, '')) {
      if($ftitle = $ftitle->NextRecord())
        $ftitle = $ftitle->title;
      else
        $ftitle = '(missing)';
    } else
      $ftitle = '(error looking up forum title)';
    $page->Start($thread->title . ' - ' . $ftitle . ' - oi', $thread->title);
    $posts = 'select u.login, s.rank, p.id, p.uid, p.instant, p.subject, p.post, p.history, r.signature, r.avatar, c.flags&' . _FLAG_USERCONTACT_SHOWEMAIL . ' as showemail, c.email, c.website from oiposts as p left join users as u on p.uid=u.uid left join usercontact as c on u.uid=c.uid left join userstats as s on u.uid=s.uid left join userprofiles as r on u.uid=r.uid where p.tid=' . $_GET['tid'] . ' order by instant';
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
?>
            </div>
<?
        }
        if($user->Valid && $user->id == $post->uid || $user->GodMode) {
?>
            <a href="/oi/f<?=$thread->fid; ?>/t<?=$_GET['tid']; ?>/edit=<?=$post->id; ?>" title="edit the above post">edit</a> |
            <a href="/oi/f<?=$thread->fid; ?>/t<?=$_GET['tid']; ?>/delete=<?=$post->id; ?>" title="delete the above post">delete</a> |
<?
        }
?>
            <a href="/oi/f<?=$thread->fid; ?>/t<?=$_GET['tid']; ?>/reply=<?=$post->id; ?>" title="quote the above post in a new reply">reply</a>
          </div>
        </td>
      </tr></table>

<?
      }
?>
      <p><a href="/oi/f<?=$thread->fid; ?>/t<?=$_GET['tid']; ?>/reply">add a reply</a></p>
<?
      $page->SplitLinks();
    }
  } else {
    $page->Start('unknown thread - oi', 'unknown thread');
  }
  $page->End();
?>
