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
                if($_POST['return'] != 'xml')
                  header('Location: http://' . $_SERVER['HTTP_HOST'] . '/hb/thread' . $thread->id . ($thread->posts + 1 > _FORUM_POSTS_PER_PAGE ? '/skip=' . (floor($thread->posts / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE) . '#p' : '/#p') . $post);
                else {
                  header('Content-Type: text/xml; charset=utf-8');
                  header('Cache-Control: no-cache');
                  echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<response result="success">
<post id="<?=$post; ?>">
<subject><?=htmlspecialchars($_POST['subject'], ENT_NOQUOTES); ?></subject>
<message><![CDATA[<?=auText::BB2HTML($_POST['post'], false, false); ?>]]></message>
<time><?=strtolower($user->tzdate('g:i:s a, M d, Y', time())); ?></time>
<user id="<?=$user->ID; ?>">
<name><?=$user->Name; ?></name>
<?
                  if($user->Valid) {
                    $userinfo = 'select s.rank, r.signature, r.avatar, c.flags&' . _FLAG_USERCONTACT_SHOWEMAIL . ' as showemail, c.email, c.website, f.frienduid from users as u left join userstats as s on s.uid=u.uid left join userprofiles as r on r.uid=u.uid left join usercontact as c on c.uid=u.uid left join userfriends as f on f.fanuid=u.uid and f.frienduid=u.uid where u.uid=\'' . $user->ID . '\'';
                    if($userinfo = $db->GetRecord($userinfo, '', '')) {
                      if($userinfo->frienduid)
                        echo "<friend/>\n";
                      if($userinfo->avatar)
                        echo '<avatar>' . $userinfo->avatar . "</avatar>\n";
                      echo '<rank>' . $userinfo->rank . "</rank>\n";  // rank is always defined
                      if($userinfo->signature)
                        echo '<signature>' . htmlspecialchars(auText::pbr2EOL(str_replace('&nbsp;', chr(0xc2) . chr(0xa0), $userinfo->signature)), ENT_NOQUOTES) . "</signature>\n";
                      if($userinfo->showemail)
                        echo '<email>' . auText::SafeEmail($userinfo->email) . "</email>\n";
                      if($userinfo->website)
                        echo '<website>' . $userinfo->website . "</website>\n";
                    }
                  }
?>
</user>
</post>
</response>
<?
                }
                die;
              }
            }
            if($_POST['return'] == 'xml')
              $page->SendXmlErrors();
          }
        }
        if($reply->Submitted() != 'preview' && $_POST['return'] == 'xml') {
          foreach($reply->GetErrors() as $e);
          $page->Error(html_entity_decode($e, ENT_COMPAT, _CHARSET));
          $page->SendXmlErrors();
        }
        $page->Start('reply to ' . ($quote ? $quote->subject : $thread->title));
        $reply->WriteHTML($user->Valid);
        $page->End();
      }
    }
  } elseif(is_numeric($_GET['quote'])) {
    // ajax request for quoted post
    $quote = 'select p.subject, p.post, u.login from hbposts as p left join users as u on u.uid=p.uid where id=\'' . addslashes($_GET['quote']) . '\'';
    if(false !== $quote = $db->GetRecord($quote, 'error looking up post to quote', 'post to quote not found')) {
      if(!$quote->login)
        $quote->login = 'anonymous';
      header('Content-Type: text/xml; charset=utf-8');
      header('Cache-Control: no-cache');
      echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
      require_once 'auText.php';
?>
<response result="success">
<subject><?=htmlspecialchars(html_entity_decode(HB::AddRe($quote->subject), ENT_COMPAT, _CHARSET), ENT_NOQUOTES); ?></subject>
<quote><?=htmlspecialchars('[q=' . $quote->login . ']' . auText::HTML2BB($quote->post) . '[/q]', ENT_NOQUOTES) . "\n"; ?></quote>
</response>
<?
      die;
    }
    $page->SendXmlErrors();
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
