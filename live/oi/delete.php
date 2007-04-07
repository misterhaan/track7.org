<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';

  if(!is_numeric($_GET['p']))
    $page->Show404();
  $p = 'select p.tid, p.number, p.subject, p.post, p.history, p.instant, p.uid, u.login, t.fid, t.title as thread, t.lastpost as threadlastpost, f.title as forum, f.lastpost as forumlastpost from oiposts as p left join users as u on u.uid=p.uid left join oithreads as t on t.id=p.tid left join oiforums as f on f.id=t.fid where p.id=' . $_GET['p'];
  if($p = $db->GetRecord($p, 'error looking up post information', 'post not found')) {
    if($user->GodMode) {
      if($_POST['submit'] == 'confirm') {
        if(false !== ($db->Change('delete from oiposts where id=' . $_GET['p'], 'error deleting post'))) {
          $ok = (false !== $db->Change('update oithreads set posts=posts-1 where id=' . $p->tid, 'error updating post count on thread ' . $p->tid));
          $ok = $ok && (false !== $db->Change('update oiforums set posts=posts-1 where id=' . $p->fid, 'error updating post count on forum ' . $p->fid));
          if($_GET['p'] == $p->threadlastpost) {
            $newlastpost = 'select id, instant from oiposts where tid=' . $p->tid . ' order by instant desc';
            if($newlastpost = $db->GetRecord($newlastpost, 'error looking up last post to update last post on thread ' . $p->tid, '')) {
              $ok = $ok && (false !== $db->Change('update oithreads set lastpost=' . $newlastpost->id . ', instant=' . $newlastpost->instant . ' where id=' . $p->tid, 'error updating last post on thread ' . $p->tid));
            }
            else {
              // no other posts in the thread, so delete the thread
              $del = 'delete from oithreads where id=\'' . $p->tid . '\'';
              $ok = $ok && (false !== $db->Change($del, 'Error removing empty thread'));
              $p->tid = false;
            }
            if($_GET['p'] == $p->forumlastpost) {
              $newlastpost = 'select lastpost from oithreads where fid=' . $p->fid . ' order by instant desc';
              if($newlastpost = $db->GetValue($newlastpost, 'error looking up last post to update last post on forum ' . $p->fid, 'Could not find last post for forum'))
                $ok = $ok && (false !== $db->Change('update oiforums set lastpost=' . $newlastpost . ' where id=' . $p->fid, 'error updating last post on forum ' . $p->fid));
              else
                $ok = false;
            }
          }
          if($p->uid)
            $ok = $ok && (false !== $db->Change('update userstats set posts=posts-1 where uid=' . $p->uid, 'error updating post count for user ' . $p->uid));
          if($ok) {
            $header = 'Location: http://' . $_SERVER['HTTP_HOST'] . '/oi/f' . $p->fid . '/';
            if($p->tid)
              $header .= 't' . $p->tid . '/';
            header($header);
            die;
          }
        }
      }
      $page->Start('delete ' . $p->subject . ' - oi', 'delete post');
?>
      <p>deleting &quot;<?=$p->subject; ?>&quot; posted in <?=$p->thread; ?> (<?=$p->forum; ?>) . . .</p>
      
<?
      $conf = new auForm('confdel', '/oi/f' . $p->fid . '/t' . $p->tid . '/delete=' . $_GET['p']);
      $conf->AddButtons('confirm', 'actually go ahead and get rid of this thing');
      $conf->WriteHTML(true);
?>
      <table class="post" cellspacing="0"><tr>
        <td class="userinfo">
          <?=$p->login ? $p->login : 'anonymous'; ?>
        </td>
        <td>
          <div class="head">
            <div class="subject">subject:&nbsp; <span class="response"><?=$p->subject; ?></span></div>
            <div class="time">posted:&nbsp; <span class="response"><?=strtolower($user->tzdate('g:i:s a, M d, Y', $p->instant)); ?></span></div>
          </div>
          <p>
            <?=$p->post; ?>

          </p>
<?
        if($p->history) {
?>
          <p class="history">
<?=substr($p->history, 0, strpos($p->history, "\r") ? -8 : -7); ?>

          </p>
<?
        }
?>
        </td>
      </tr></table>

<?
    } else {
      $page->Start('delete post');
      $page->Error('sorry, you can\'t delete your own posts just yet');
    }
  } else
    $page->Start('delete post');
  $page->End();
?>
