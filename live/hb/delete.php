<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $post = 'select p.id, p.number, p.subject, p.post, p.thread, p.uid, t.posts, t.lastpost, t.tags from hbposts as p left join hbthreads as t on t.id=p.thread where p.id=\'' . addslashes($_GET['id']) . '\'';
  if($post = $db->GetRecord($post, 'error looking up post', 'post not found')) {
    if($user->Valid && $post->uid == $user->ID || $user->GodMode) {
      require_once 'auForm.php';
      $confirm = new auForm('confirm', $_SERVER['REQUEST_URI']);
      $confirm->AddText('confirmation', 'are you sure you want to delete “' . $post->subject . '?”');
      $confirm->AddButtons(array('yes', 'no'), array('delete this post', 'don&rsquo;t delete this post'));
      if($confirm->CheckInput(true))
        if($confirm->Submitted() == 'yes') {
          $del = 'delete from hbposts where id=\'' . $post->id . '\'';
          if(false !== $db->Change($del, 'error deleting post')) {
            if($post->uid)  // posted by a user -- update user's stats
              $db->Change('update userstats set posts=posts-1 where uid=\'' . $post->uid . '\'');
            if($post->posts > 1) {
              $db->Change('update hbposts set number=number-1 where number>' . $post->number);
              if($post->lastpost == $post->id) {
                $lastpost = 'select id from hbposts where thread=\'' . $post->thread . '\' order by instant desc';
                if(false !== $lastpost = $db->GetValue($lastpost, 'error looking up last post'))
                  $update = ', lastpost=\'' . $lastpost . '\'';
                else
                  $update = '';
              } else
                $update = '';
              $update = 'update hbthreads set posts=posts-1' . $update . ' where id=\'' . $post->thread . '\'';
              $db->Change($update, 'error updating thread');
              // go to the page that used to have the deleted post
              if($post->posts <= $post->number)
                $post->number--;
              header('Location: http://' . $_SERVER['HTTP_HOST'] . '/hb/thread' . $post->thread . ($post->number > _FORUM_POSTS_PER_PAGE ? '/skip=' . ((($post->number - 1) / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE) : '/'));
              die;
            } else {  // last post in thread -- delete thread and update taginfo
              if($db->Change('delete from hbthreads where id=\'' . $post->thread . '\'', 'error deleting thread'))
                $db->Change('update taginfo set count=count-1 where type=\'threads\' and (name=\'' . implode('\' or name=\'', explode(',', $post->tags)) . '\')', 'error updating taginfo');
              // go back to index since thread was deleted
              header('Location: http://' . $_SERVER['HTTP_HOST'] . '/hb/');
            }
          }
        } else {
          // delete canceled -- go back to thread
          header('Location: http://' . $_SERVER['HTTP_HOST'] . '/hb/thread' . $post->thread . ($post->number > _FORUM_POSTS_PER_PAGE ? '/skip=' . ((($post->number - 1) / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE) . '#p' : '/#p') . $post->id);
          die;
        }
      $page->Start('delete post &ldquo;' . $post->subject . '&rdquo;');
      $confirm->WriteHTML(true);
      $page->Heading($post->subject);
?>
      <?=$post->post; ?>

<?
    } else
      $page->Error('you may only delete your own posts');
  }
  $page->Start('delete post');
  $page->End();
?>
