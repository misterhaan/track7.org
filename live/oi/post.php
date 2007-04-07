<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auText.php';

  // give a 404 error for missing / non-numeric forum id
  if(!isset($_GET['f']) || !is_numeric($_GET['f']))
    $page->Show404();
  
  $forum = 'select title from oiforums where id=' . $_GET['f'];
  if($forum = $db->GetRecord($forum, 'error looking up forum title', 'unable to find a forum with id ' . $_GET['f'], true)) {
    if(isset($_GET['t']) && is_numeric($_GET['t'])) {
      $thread = 'select title from oithreads where id=' . $_GET['t'];
      if($thread = $db->GetValue($thread, 'error looking up thread title', 'unable to find a thread with id ' . $_GET['t'], true)) {
        $reply = 're: ' . $thread;
        if(isset($_GET['p']) && is_numeric($_GET['p'])) {
          $post = 'select p.subject, p.post, u.login from oiposts as p left join users as u on p.uid=u.uid where p.id=' . $_GET['p'];
          if($post = $db->GetRecord($post, 'error looking up post to reply to', 'unable to find a post with id ' . $_GET['p'], true)) {
            $reply = $post->subject;
            $post = '[q=' . $post->login . ']' . auText::HTML2BB($post->post) . '[/q]';
            if(strtolower(substr($reply, 0, 4)) != 're: ')
              $reply = 're: ' . $reply;
          }
        } elseif(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
          $post = 'select subject, post, uid from oiposts where id=' . $_GET['edit'];
          if($post = $db->GetRecord($post, 'error looking up post to edit', 'unable to find a post with id ' . $_GET['edit'], true)) {
            if(($post->uid == 0 || $user->id != $post->uid) && !$user->GodMode) {
              $page->Start('edit post - oi', 'edit post (not allowed)');
              $page->Error('unable to edit post - you are not allowed to edit this post');
              $page->End();
              die;
            }
            $edit = true;
            $reply = $post->subject;
            $post = auText::HTML2BB($post->post);
          } else {
            $page->Start('edit post - oi', 'edit post (unable to find post)');
            $page->End();
            die;
          }
        }
      } else {
        $page->Start('reply - oi', 'reply to (unable to find thread)');
        $page->End();
        die;
      }
    }

    require_once 'auForm.php';
    $form = new auForm('forumpost', '/oi/f' . $_GET['f'] . (isset($thread) ? '/t' . $_GET['t'] . ($edit ? '/edit=' . $_GET['edit'] : '/reply') : '/post'));
    if($edit)
      $form->AddData('edit', $_GET['edit']);
    if($user->Valid)
      $form->AddText('posting as', $user->Name);
    else
      $form->AddHTML('posting as', 'anonymous (<a href="/user/login.php">log in</a> or <a href="/user/register.php">register</a>)');
    if(!isset($thread))
      $form->AddField('title', 'thread title', 'enter a title for this thread', true, '', _AU_FORM_FIELD_NORMAL, 40, 255);
    $form->AddField('subject', 'post subject', 'enter a subject for this post', false, $reply, _AU_FORM_FIELD_NORMAL, 40, 255);
    $form->AddField('post', 'message', 'enter your post (you may use t7code)', true, $post, _AU_FORM_FIELD_BBCODE);
    $form->AddButtons(array('preview', 'post ' . ($user->Valid ? 'as ' . $user->Name : 'anonymously')), array('preview your message before actually posting it', 'add your message'));

    if($form->CheckInput($user->Valid) && substr($form->Submitted(), 0, 6) == 'post a') {
      if(isset($_POST['title'])) {
        // we are starting a new thread
        if(strlen($_POST['title']) < 1)
          $_POST['title'] = '(untitled)';
        if(strlen($_POST['subject']) < 1)
          $_POST['subject'] = $_POST['title'];
        $ins = 'insert into oithreads (fid, title, instant, uid) values (' . $_GET['f'] . ', \'' . addslashes(htmlspecialchars($_POST['title'])) . '\', ' . time() . ', ' . $user->ID . ')';
        if(false !== ($_GET['t'] = $db->Put($ins, 'error opening new thread')))
          $db->Change('update oiforums set threads=threads+1 where id=' . $_GET['f'], '');
        if($page->HasQueuedMessages()) {
          $page->Start('new thread - ' . $forum->title . ' - oi', 'post new thread in ' . $forum->title);
          $page->End();
          die;
        }
      }
      if(strlen($_POST['subject']) < 1)
        $_POST['subject'] = '(untitled)';
      if(isset($_POST['edit'])) {
        if(!$user->Valid) {
          $page->Start('cannot edit post');
          $page->Error('only logged-in users may edit posts');
          $page->End();
          die;
        }
        $update = 'update oiposts set subject=\'' . addslashes(htmlspecialchars($_POST['subject'])) . '\', post=\'' . addslashes(auText::BB2HTML(trim($_POST['post']))) . '\', history=concat(\'          edited by ' . $user->Name . ', ' . date('g:i:s a, M d, Y') . "<br />\n" . '\', history) where id=' . $_POST['edit'] . ($user->GodMode ? '' : ' and uid=' . $user->ID);
        if($update = $db->Change($update, 'error updating post', 'post not found' . ($user->GodMode ? '' : ' or post does not belong to you'))) {
          header('Location: http://' . $_SERVER['HTTP_HOST'] . '/oi/f' . $_GET['f'] . '/t' . $_GET['t'] . '/#p' . $_POST['edit']);
          die;
        } else {
          $page->Start('failed to edit post');
          $page->End();
          die;
        }
      } else {
        $ins = 'insert into oiposts (tid, subject, post, instant, uid) values (' . $_GET['t'] . ', \'' . addslashes(htmlspecialchars($_POST['subject'])) . '\', \'' . addslashes(auText::BB2HTML($_POST['post'])) . '\', ' . time() . ', ' . $user->ID . ')';
        if(false !== ($id = $db->Put($ins, 'error saving post'))) {
          $db->Change('update oithreads set posts=posts+1, lastpost=' . $id . ' where id=' . $_GET['t']);
          if($count = $db->GetValue('select posts from oithreads where id=' . $_GET['t'], '', ''))
            $db->Change('update oiposts set number=' . $count . ' where id=' . $id);
          $db->Change('update oiforums set lastpost=' . $id . ', posts=posts+1 where id=' . $_GET['f']);
          if($user->Valid) {
            $db->Change('update userstats set posts=posts+1 where uid=' . $user->ID);
            $user->UpdateRank();
          }
          if($user->ID != 1)
            @mail('misterhaan@' . _HOST, 'new forum ' . (isset($_POST['title']) ? 'thread' : 'post'), 'http://' . $_SERVER['HTTP_HOST'] . '/oi/f' . $_GET['f'] . '/t' . $_GET['t'] . (isset($_POST['title']) ? '/' : ($count > _FORUM_POSTS_PER_PAGE ? '/&skip=' . (floor($count / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE) : '/') . '#p' . $id), 'From: track7 forums <oi@' . _HOST . '>');
          // using header to avoid any problem with refresh or back buttons
          $count /= _FORUM_POSTS_PER_PAGE;
          $count = floor($count) * _FORUM_POSTS_PER_PAGE;
          header('Location: http://' . $_SERVER['HTTP_HOST'] . '/oi/f' . $_GET['f'] . '/t' . $_GET['t'] . (isset($_POST['title']) ? '/' : ($count ? '/&skip=' . $count : '/') . '#p' . $id));
          die;
        }
      }
    } else {
      // no form was submitted
      if(isset($thread))
        if($edit)
          $page->Start('edit post - ' . $reply . ' - ' . $thread . ' - ' . $forum->title . ' - oi', 'edit post');
        else
          $page->Start('reply - ' . $thread . ' - ' . $forum->title . ' - oi', 'reply to ' . $thread, $forum->title);
      else
        $page->Start('new thread - ' . $forum->title . ' - oi', 'post new thread in ' . $forum->title);
      if($form->CheckInput($user->Valid) && $form->Submitted() == 'preview') {
        $page->Heading('preview');
?>
      <table class="post"><tr>
        <td class="userinfo">
          <?=$user->Valid ? '<a href="/user/' . $user->Name . '/">' . $user->Name . '</a>' : 'anonymous'; ?>

        </td>
        <td>
          <div class="head">
            <div class="subject">subject:&nbsp; <span class="response"><?=htmlspecialchars($_POST['subject']); ?></span></div>
            <div class="time">posted:&nbsp; <span class="response"><?=strtolower($user->tzdate('g:i:s a, M d, Y')); ?></span></div>
          </div>
          <p>
            <?=auText::BB2HTML($_POST['post']); ?>

          </p>
        </td>
      </tr></table>

<?
      }
      $form->WriteHTML($user->Valid);

      if(isset($thread) && !$edit) {
        // show the thread's last 3 posts
        $posts = 'select u.login, p.uid, p.post from users as u, oiposts as p where u.uid=p.uid and p.tid=' . $_GET['t'] . ' order by instant desc';
        if($posts = $db->GetLimit($posts, 0, 3, 'error looking up last three posts', 'no posts found for this thread', true)) {
          $page->heading('last three posts');
          while($post = $posts->NextRecord()) {
?>
      <table class="post"><tr>
        <td class="userinfo">
          <?=$post->uid > 0 ? '<a href="/user/' . $post->login . '/">' . $post->login . '</a>' : $post->login; ?>

        </td>
        <td>
          <p>
            <?=$post->post; ?>

          </p>
        </td>
      </tr></table>

<?
          }
        }
      }
    }
  } else
    $page->Start('new thread - oi', 'new thread', '(unable to find forum)');
    
  $page->End();
?>
