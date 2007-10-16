<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';
  require_once 'auText.php';
  $cmtform = new auForm('pagecomments', '/comments.php');
  $cmtform->AddData('page', '');
  $cmtform->AddData('ref', '');
  if($user->Valid) {  
    $cmtform->AddData('uid', '');
    //$cmtform->AddText('name', '');
  } else {
    $cmtform->AddField('name', 'name', 'enter your name so i will know who you are', false);
    $cmtform->AddField('contact', 'contact', 'enter an e-mail address or a url', false);
  }
  $cmtform->AddField('pagecomments', 'comments', 'enter your comments (you may use t7code)', true, '', _AU_FORM_FIELD_BBCODE, array('cols' => 40));
  $cmtform->AddButtons(array('save', 'update'), 'post your comment on this page');
  switch($cmtform->Submitted()) {
    case 'save':
      if($cmtform->CheckInput($user->Valid)) {
        if(isset($_POST['uid']) && is_numeric($_POST['uid'])) {
          if($user->Valid && $_POST['uid'] == $user->ID) {
            if(false !== $db->Put('insert into comments (page, instant, uid, comments) values (\'' . addslashes($_POST['page']) . '\', ' . time() . ', ' . +$_POST['uid'] . ', \'' . addslashes(auText::BB2HTML($_POST['pagecomments'])) . '\')', 'error saving comment')) {
              $db->Change('update userstats set comments=comments+1 where uid=' . +$_POST['uid']);
              $user->UpdateRank();
              if($_POST['uid'] != 1)
                @mail('misterhaan@' . _HOST, $user->Name . ' has commented on ' . $_POST['page'], 'http://' . $_SERVER['HTTP_HOST'] . $_POST['page'] . '#comments' . "\n\n" . $_POST['pagecomments'],
                  'From: track7 comments <whatyousay@' . _HOST . ">\r\n" .
                  'X-Mailer: PHP/' . phpversion() . "\r\n");
              if($_POST['ref'])
                $_POST['page'] = $_POST['ref'];
              header('Location: http://' . $_SERVER['HTTP_HOST'] . $_POST['page'] . '#comments');
              die;
            }
          } else
            $page->Error('not logged in as user you are trying to post as!&nbsp; either something has gone wrong or you are trying to do things you shouldn\'t.');
        } else {
          if(preg_match('/&lt;a .*href=(&quot;)?.+(&quot;)?&gt;/is', $_POST['pagecomments']) || strpos($_POST['pagecomments'], '^)') !== false || strpos($_POST['pagecomments'], 'Hello world') !== false) {
            $page->Start('spam somewhere else, fool', 'no coming in, please');
?>
      <p>
        you appear to have attempted to use an html link in your comment.&nbsp;
        if you are a real person (meaning not a bot or a spammer) and were
        trying to post a link, please see my post on
        <a href="/oi/f3/t1/">how to use t7code</a> for instructions and try
        again.&nbsp; if you are a bot or a spammer, please go away and cease
        your nonsense immediately.
      </p>
<?
            $page->End();
            die;
          } elseif(substr($_POST['page'], 0, 1) != '/') {
            $page->Start('spam somewhere else, fool', 'no coming in, please!');
?>
      <p>
        you appear to be trying to post a comment without using the official
        form, possibly even trying to spam a link to some crappy site nobody
        wants to see ever.&nbsp; if you actually did use the official comment
        form and got this error, please note the page you came from and
        <a href="/user/sendmessage.php?to=misterhaan">contact me about it</a>.&nbsp;
        if you are a bot or a spammer, please go away and cease your nonsense
        immediately.
      </p>
<?
            $page->End();
            die;
          } elseif(strpos($_POST['contact'], '[url') !== false) {
            $page->Start('spam somewhere else, fool', 'no coming in, please!');
?>
      <p>
        you appear to be trying to post a nasty nasty spam comment with links to
        websites that nobody will ever want to go to, ever.&nbsp; if this is not
        what you are trying to do, please
        <a href="/user/sendmessage.php?to=misterhaan">contact me about it</a>.&nbsp;
        if you are a bot or a spammer, please go away and cease your nonsense
        immediately.
      </p>
<?
            $page->End();
            die;
          }
          if(strlen($_POST['name']) <= 0)
            $_POST['name'] = 'anonymous';
          if(false !== $db->Put('insert into comments (page, instant, name, url, comments) values (\'' . addslashes(htmlspecialchars($_POST['page'])) . '\', ' . time() . ', \'' . addslashes(htmlspecialchars($_POST['name'])) . '\', \'' . addslashes(auText::FixLink($_POST['contact'])) . '\', \'' . addslashes(auText::BB2HTML($_POST['pagecomments'])) . '\')', 'error saving comments')) {
            @mail('misterhaan@' . _HOST, $_POST['name'] . ' has commented on ' . $_POST['page'], 'http://' . $_SERVER['HTTP_HOST'] . $_POST['page'] . '#comments' . "\n\n" . $_POST['pagecomments'],
                    'From: track7 comments <whatyousay@' . _HOST . ">\r\n" .
                    'X-Mailer: PHP/' . phpversion() . "\r\n");
            if($_POST['ref'])
              $_POST['page'] = $_POST['ref'];
            header('Location: http://' . $_SERVER['HTTP_HOST'] . $_POST['page'] . "#comments\r\n");
            die;
          }
        }
      } else
        $page->Error('looks like you messed something up!&nbsp; make sure to enter some comments, and don\'t fill in the spam trapper fields (which you shouldn\'t see anyway) unless you\'re spamming.');
      $page->Start('error saving comment');
      $page->End();
      die;
      break;
    case 'update':
      if(is_numeric($_POST['id'])) {
        if($user->GodMode) {
          $olduser = 'select uid from comments where id=' . +$_POST['id'];
          if(false !== $olduser = $db->GetValue($olduser, 'error looking up current user id for this comment', 'cannot find comment', true)) {
            if($olduser != $_POST['uid']) {
              if($olduser > 0)
                $db->Change('update userstats set comments=comments-1 where uid=' . +$olduser);
              if($_POST['uid'] > 0)
                $db->Change('update userstats set comments=comments+1 where uid=\'' . $_POST['uid'] . '\'');
            }
            $update = 'update comments set page=\'' . addslashes($_POST['page']) . '\', uid=\'' . addslashes($_POST['uid']) . '\', name=\'' . addslashes(htmlspecialchars($_POST['name'])) . '\', url=\'' . addslashes(htmlspecialchars($_POST['url'])) . '\', comments=\'' . addslashes(auText::BB2HTML($_POST['pagecomments'])) . '\' where id=' . $_POST['id'];
            if(false !== $db->Change($update, 'error updating comment')) {
              header('Location: http://' . $_SERVER['HTTP_HOST'] . $_POST['goback']);
              die;
            }
          }
        } else {
          $update = 'update comments set comments=\'' . addslashes(auText::BB2HTML($_POST['pagecomments'])) . '\' where id=' . +$_POST['id'] . ' and uid=\'' . $user->ID . '\'';
          if(false !== $db->Change($update, 'error updating comment', 'unable to update comment:&nbsp; either you are not logged in or this comment is not yours', true)) {
            header('Location: http://' . $_SERVER['HTTP_HOST'] . $_POST['goback']);
            die;
          }
        }
      }
      $page->Start('error editing comment');
      $page->End();
      die;
      break;
  }
  if(is_numeric($_GET['edit'])) {
    $comment = 'select * from comments where id=' . $_GET['edit'];
    if($comment = $db->GetRecord($comment, '', '')) {
      if($user->GodMode) {
        $page->Start('edit comment');
        $cmtfrm = new auForm('pagecomments');
        $cmtfrm->AddData('id', $_GET['edit']);
        $cmtfrm->AddData('goback', $_GET['goback']);
        $cmtfrm->AddField('page', 'page', 'change which page this comment should display on', true, $comment->page, _AU_FORM_FIELD_NORMAL, 40, 255);
        $cmtfrm->AddField('uid', 'user', 'id of the user who posted this comment', false, $comment->uid, _AU_FORM_FIELD_NORMAL, 3, 4);
        $cmtfrm->AddField('name', 'name', 'name of poster if not from a user', false, $comment->name, _AU_FORM_FIELD_NORMAL, 30, 45);
        $cmtfrm->AddField('url', 'url', 'contact url of poseter if not from a user', false, $comment->url, _AU_FORM_FIELD_NORMAL, 60, 100);
        $cmtfrm->AddField('pagecomments', 'comments', 'the actual comments posted', true, auText::HTML2BB($comment->comments), _AU_FORM_FIELD_BBCODE);
        $cmtfrm->AddButtons('update', 'save changes to this comment');
        $cmtfrm->WriteHTML(true);
        $page->End();
        die;
      } elseif($user->Valid && $user->ID == $comment->uid) {
        $page->Start('edit comment');
        $cmtfrm = new auForm('pagecomments');
        $cmtfrm->AddData('id', $_GET['edit']);
        $cmtfrm->AddData('goback', $_GET['goback']);
        $cmtfrm->AddField('pagecomments', 'comments', 'the actual comments posted', true, auText::HTML2BB($comment->comments), _AU_FORM_FIELD_BBCODE);
        $cmtfrm->AddButtons('update', 'save changes to this comment');
        $cmtfrm->WriteHTML(true);
        $page->End();
        die;
      }
    }
  } elseif(is_numeric($_GET['delete'])) {
    $comment = 'select uid from comments where id=' . $_GET['delete'];
    if($comment = $db->GetRecord($comment, 'error checking which user posted this comment', 'unable to find comment', true)) {
      if($user->godmode || $user->valid && $comment->uid == $user->id) {
        $del = 'delete from comments where id=' . $_GET['delete'];
        if(false !== $db->Change($del, 'error deleting comment')) {
          $db->Change('update userstats set comments=comments-1 where uid=' . $comment->uid);
          header('Location: http://' . $_SERVER['HTTP_HOST'] . $_GET['goback']);
          die;
        }
      }
    }
    $page->Start('error deleting comment');
    $page->End();
    die;
  } else {
    // did not submit a form, so let's show the most recent comments
    if(isset($_GET['user']) && $u = $db->GetRecord('select uid, login from users where login=\'' . addslashes($_GET['user']) . '\'', '', '')) {
      $page->Start($u->login . '\'s comment history');
      $comments = 'select c.id, c.page, c.instant, c.uid, u.login, c.name, c.url, c.comments, p.avatar, s.rank, f.frienduid from comments as c left join users as u on c.uid=u.uid left join userprofiles as p on c.uid=p.uid left join userstats as s on c.uid=s.uid left join userfriends as f on c.uid=f.frienduid and f.fanuid=\'' . $user->ID . '\' where c.uid=' . $u->uid . ' order by instant desc';
    } else {
      $page->AddFeed('track7 page comments', '/feeds/comments.rss');
      $page->Start('comment history<a class="feed" href="/feeds/comments.rss" title="rss feed of comment history"><img src="/style/feed.png" alt="feed" /></a>');
      $comments = 'select c.id, c.page, c.instant, c.uid, u.login, c.name, c.url, c.comments, p.avatar, s.rank, f.frienduid from comments as c left join users as u on c.uid=u.uid left join userprofiles as p on c.uid=p.uid left join userstats as s on c.uid=s.uid left join userfriends as f on c.uid=f.frienduid and f.fanuid=\'' . $user->ID . '\' order by instant desc';
    }
    if($comments = $db->GetSplit($comments, 10, 0, '', '', 'error looking up comments', 'nobody has commented on anything')) {
?>
      <p>
        this page lists all comments that have been posted on the pages of
        track7, with the most recent first.&nbsp; it's largely to help me find
        comments, but might be interesting / useful to other people as well.
      </p>

      <div id="usercomments">
<?
      while($comment = $comments->NextRecord()) {
?>
        <table class="post" cellspacing="0" id="c<?=$comment->id; ?>"><tr>
          <td class="userinfo">
<?
        if($comment->uid > 0) {
?>
            <a href="/user/<?=$comment->login; ?>/"><?=$comment->login; ?></a>
<?
          if($comment->avatar) {
?>
            <img class="avatar" alt="" src="/user/avatar/<?=$comment->login; ?>.<?=$comment->avatar; ?>" />
<?
          }
?>
            <div class="frequency" title="frequency"><?=$comment->rank; ?></div>
<?
        } elseif(strlen($comment->url) > 0) {
?>
            <a href="<?=$comment->url; ?>"><?=$comment->name; ?></a>
<?
        } else {
?>
            <?=$comment->name; ?>

<?
        }
?>
          </td>
          <td>
            <div class="head">
              <div class="time">posted:&nbsp; <span class="response"><?=strtolower($user->tzdate('g:i:s a, M d, Y', $comment->instant)); ?></span></div>
            </div>
            <span class="detail">page:&nbsp; <a href="<?=htmlspecialchars($comment->page); ?>"><?=htmlspecialchars($comment->page); ?></a></span>
            <p>
              <?=$comment->comments; ?>

            </p>
            <div class="foot">
<?
        if($comment->uid) {
?>
              <div class="userlinks">
                <a href="/user/sendmessage.php?to=<?=$comment->login; ?>" title="send <?=$comment->login; ?> a private message">pm</a>
<?
          if($comment->website) {
?>
                | <a href="<?=$comment->website; ?>" title="visit <?=$comment->login; ?>'s website">www</a>
<?
          }
          if($user->Valid && $comment->frienduid === null) { 
?>
                | <a href="/user/friends.php?add=<?=$comment->login; ?>" title="add <?=$comment->login; ?> to your friend list">+friend</a>
<?
          }
?>
              </div>
<?
        }
        if($user->GodMode || $user->Valid && $user->ID == $comment->uid) {
          $url = $page->GetRequestURL();
          if(strpos($url, '?') !== false)
            $url .= '&_commentskip=' . $_GET['_commentskip'] . '#comments';
          else
            $url .= '?_commentskip=' . $_GET['_commentskip'] . '#comments';
          $url = urlencode($url);
?>
              <a href="/comments.php?edit=<?=$comment->id; ?>&amp;goback=<?=$url; ?>" title="edit this comment">edit</a> |
              <a href="/comments.php?delete=<?=$comment->id; ?>&amp;goback=<?=$url; ?>" title="delete this comment">delete</a>
<?
        }
?>
            </div>
          </td>
        </tr></table>

<?
      }
      $page->SplitLinks();
?>
      </div>

<?
    }
    $page->End();
  }
?>
