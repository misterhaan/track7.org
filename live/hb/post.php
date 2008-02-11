<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'hb.inc';

  if(is_numeric($_GET['thread'])) {
    $thread = 'select id, title, posts, tags from hbthreads where id=\'' . addslashes($_GET['thread']) . '\'';
    if($thread = $db->GetRecord($thread, 'error looking up thread', 'thread not found')) {
      if(is_numeric($_GET['id'])) {
        $post = 'select p.id, p.number, p.subject, p.post, p.history, p.instant, p.uid, u.login from hbposts as p left join users as u on u.uid=p.uid where p.id=\'' . addslashes($_GET['id']) . '\'';
        if($post = $db->GetRecord($post, 'error looking up post to edit', 'post not found'))
          if($user->Valid && ($user->ID == $post->uid || $user->GodMode)) {
            $edit = HB::GetPostForm($db, $user, 'edit', $post, $thread);
            if($edit->CheckInput(true)) {
              if($edit->Submitted() == 'preview') {
                $page->Start('edit post &ldquo;' . $post->subject . '&rdquo;');
                $page->Heading('preview');
?>
      <table class="post"><tr>
        <td class="userinfo">
          <?=$post->login ? '<a href="/user/' . $post->login . '/">' . $post->login . '</a>' : 'anonymous'; ?>

        </td>
        <td>
          <div class="head">
            <div class="subject">subject:&nbsp; <span class="response"><?=htmlentities($_POST['subject'], ENT_COMPAT, _CHARSET); ?></span></div>
            <div class="time">posted:&nbsp; <span class="response"><?=strtolower($user->tzdate('g:i:s a, M d, Y', $post->instant)); ?></span></div>
          </div>
          <?=auText::BB2HTML($_POST['post'], false, false); ?>

          <?=HB::ShowHistory($user, $post->history . '/' . $user->Name . '|' . time()); ?>

        </td>
      </tr></table>

<?
              } else {
                if($post->number == 1) {
                  $tags = makeTagList();
                  $update = 'update hbthreads set title=\'' . addslashes(htmlentities($_POST['title'])) . '\', tags=\'' . $tags . '\' where id=\'' . $thread->id . '\'';
                  $db->Change($update, 'error updating thread');
                  $newtags = explode(',', $tags);
                  $oldtags = explode(',', $thread->tags);
                  // ignore tags that were there before and are still there
                  for($i = count($oldtags) - 1; $i >= 0; $i--)
                    if(in_array($oldtags[$i], $newtags))
                      unset($oldtags[$i], $newtags[array_search($oldtags[$i], $newtags)]);
                  if(count($oldtags)) {  // tags were removed
                    $update = 'update taginfo set count=count-1 where type=\'threads\' and (name=\'' . implode('\' or name=\'', $oldtags) . '\')';
                    $db->Change($update);
                  }
                  if(count($newtags)) {  // tags were added
                    $ins = 'insert into taginfo (type, name, count) values (\'threads\', \'' . implode('\', 1), (\'threads\', \'', $newtags) . '\', 1) on duplicate key update count=count+1';
                    $db->Put($ins);
                  }
                }
                $update = 'update hbposts set subject=\'' . addslashes(htmlentities($_POST['subject'], ENT_COMPAT, _CHARSET)) . '\', post=\'' . addslashes(auText::BB2HTML($_POST['post'], false, false)) . '\', history=\'' . $post->history . '/' . $user->Name . '|' . time() . '\' where id=\'' . $post->id . '\'';
                if(false !== $db->Change($update, 'error updating post')) {
                  header('Location: http://' . $_SERVER['HTTP_HOST'] . '/hb/thread' . $thread->id . ($post->number > _FORUM_POST_PER_PAGE ? '/skip=' . (floor(($post->number - 1) / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE) : '/') . '#p' . $post->id);
                  die;
                }
              }
            }
            $page->Start('edit post &ldquo;' . $post->subject . '&rdquo;');
            $edit->WriteHTML(true);
            $page->End();
            die;
          } else
            $page->Error('you may only edit your own posts.&nbsp; if this is your post, you may need to log in.');
        $page->Start('edit post');
        $page->End();
        die;
      } else {
        if(is_numeric($_GET['quote'])) {
          $quote = 'select p.subject, p.post, u.login from hbposts as p left join users as u on u.uid=p.uid where id=\'' . addslashes($_GET['quote']) . '\'';
          if(false !== $quote = $db->GetRecord($quote, 'error looking up post to quote', 'post to quote not found'))
            if(!$quote->login)
              $quote->login = 'anonymous';
        } else
          $quote = false;
        $reply = HB::GetPostForm($db, $user, 'reply', null, $thread, $quote);
        if($reply->CheckInput($user->Valid)) {
          if(!$_POST['subject'])
            $_POST['subject'] = $quote ? (strtolower(substr($quote->subject, 0, 3)) != 're:' ? 're: ' . $quote->subject : $quote->subject) : (strtolower(substr($thread->title, 0, 3)) != 're:' ? 're: ' . $thread->title : $thread->title);
          if($reply->Submitted() == 'preview') {
            $page->Start('reply to ' . ($quote ? $quote->subject : $thread->title));
            $page->Heading('preview');
?>
      <table class="post"><tr>
        <td class="userinfo">
          <?=$user->Valid ? '<a href="/user/' . $user->Name . '/">' . $user->Name . '</a>' : 'anonymous'; ?>

        </td>
        <td>
          <div class="head">
            <div class="subject">subject:&nbsp; <span class="response"><?=htmlentities($_POST['subject'], ENT_COMPAT, _CHARSET); ?></span></div>
            <div class="time">posted:&nbsp; <span class="response"><?=strtolower($user->tzdate('g:i:s a, M d, Y')); ?></span></div>
          </div>
          <?=auText::BB2HTML($_POST['post'], false, false); ?>

        </td>
      </tr></table>

<?
          } else {
            $post = 'insert into hbposts (thread, number, subject, post, instant, uid) values (\'' . $thread->id . '\', ' . ($thread->posts + 1) . ', \'' . addslashes(htmlentities($_POST['subject'], ENT_COMPAT, _CHARSET)) . '\', \'' . addslashes(auText::BB2HTML($_POST['post'], false, false)) . '\', ' . time() . ', \'' . $user->ID . '\')';
            if(false !== $post = $db->Put($post, 'error saving new post')) {
              $update = 'update hbthreads set posts=posts+1, lastpost=\'' . $post . '\' where id=\'' . $thread->id . '\'';
              if(false !== $db->Change($update, 'error linking thread to post')) {
                $update = 'update userstats set posts=posts+1 where uid=\'' . $user->ID . '\'';
                $db->Change($update);
                header('Location: http://' . $_SERVER['HTTP_HOST'] . '/hb/thread' . $thread->id . ($thread->posts + 1 > _FORUM_POSTS_PER_PAGE ? '/skip=' . (floor($thread->posts / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE) . '#p' : '/#p') . $post);
                die;
              }
            }
          }
        }
        $page->Start('reply to ' . ($quote ? $quote->subject : $thread->title));
        $reply->WriteHTML($user->Valid);
        $page->End();
      }
    }
  } else {
    // new thread
    $newthread = HB::GetPostForm($db, $user, 'newthread');
    if($newthread->CheckInput($user->Valid)) {
      if($newthread->Submitted() == 'preview') {
        $page->Start('post new thread');
        $page->Heading(htmlentities($_POST['title'], ENT_COMPAT, _CHARSET) . ' (preview)');
?>
      <p>tags:&nbsp; <?=HB::TagLinks(makeTagList()); ?></p>
      <table class="post"><tr>
        <td class="userinfo">
          <?=$user->Valid ? '<a href="/user/' . $user->Name . '/">' . $user->Name . '</a>' : 'anonymous'; ?>

        </td>
        <td>
          <div class="head">
            <div class="subject">subject:&nbsp; <span class="response"><?=htmlentities($_POST[$_POST['subject'] ? 'subject' : 'title'], ENT_COMPAT, _CHARSET); ?></span></div>
            <div class="time">posted:&nbsp; <span class="response"><?=strtolower($user->tzdate('g:i:s a, M d, Y')); ?></span></div>
          </div>
          <?=auText::BB2HTML($_POST['post'], false, false); ?>

        </td>
      </tr></table>

<?
      } else {
        $tags = makeTagList();
        $thread = 'insert into hbthreads (tags, title, instant, uid) values (\'' . addslashes($tags) . '\', \'' . addslashes(htmlentities($_POST['title'], ENT_COMPAT, _CHARSET)) . '\', ' . time() . ', \'' . $user->ID . '\')';
        if(false !== $thread = $db->Put($thread, 'error saving new thread')) {
          $post = 'insert into hbposts (thread, number, subject, post, instant, uid) values (\'' . $thread . '\', 1, \'' . addslashes(htmlentities($_POST[$_POST['subject'] ? 'subject' : 'title'], ENT_COMPAT, _CHARSET)) . '\', \'' . addslashes(auText::BB2HTML($_POST['post'], false, false)) . '\', ' . time() . ', \'' . $user->ID . '\')';
          if(false !== $post = $db->Put($post, 'error saving new post')) {
            $update = 'update hbthreads set posts=1, lastpost=\'' . $post . '\' where id=\'' . $thread . '\'';
            if(false !== $db->Change($update, 'error linking thread to post')) {
              $update = 'update userstats set posts=posts+1 where uid=\'' . $user->ID . '\'';
              $db->Change($update);
              $ins = 'insert into taginfo (type, name, count) values (\'threads\', \'' . implode('\', 1), (\'threads\', \'', explode(',', $tags)) . '\', 1) on duplicate key update count=count+1';
              $db->Put($ins);
              header('Location: http://' . $_SERVER['HTTP_HOST'] . '/hb/thread' . $thread);
              die;
            } else {
              $db->Change('delete from hbposts where id=\'' . $post . '\'', 'error deleting post');
              $db->Change('delete from hbthreads where id=\'' . $thread . '\'', 'error deleting thread');
            }
          } else
            $db->Change('delete from hbthreads where id=\'' . $thread . '\'', 'error deleting thread');
        }
      }
    }
    $page->Start('post new thread');
    $newthread->WriteHTML($user->Valid);
    $page->End();
    die;
  }
  $page->Start('add / edit post');
  $page->End();
  die;
  
  function makeTagList() {
    if($_POST['taglist'])
      return $_POST['taglist'];
    foreach($_POST as $name => $val)
      if(substr($name, 0, 5) == 'tags_')
        $tags[] = $val;
    return implode(',', $tags);
  } 
?>
