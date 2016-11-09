<?
  define('MAX_COMMENT_GET', 10);

  if(isset($_GET['ajax'])) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'get':
        if(VerifyCommonFields($ajax, $_GET))
          if($comments = $db->query('(select c.id, c.posted, c.user as canchange, u.username, u.displayname, u.avatar, case u.level when 1 then \'new\' when 2 then \'known\' when 3 then \'trusted\' when 4 then \'admin\' else null end as level, f.fan as friend, c.name, c.contacturl, c.markdown, c.html from ' . $_GET['type'] . '_comments as c left join users as u on u.id=c.user left join users_friends as f on f.friend=c.user and f.fan=\'' . +$user->ID . '\' where c.' . KeyName($_GET['type']) . '=\'' . $db->escape_string($_GET['key']) . '\' order by c.posted desc limit ' . MAX_COMMENT_GET . ') order by posted')) {
            $ajax->Data->comments = [];
            while($comment = $comments->fetch_object()) {
              $comment->id += 0;
              $comment->posted = t7format::TimeTag('g:i a \o\n l F jS Y', $comment->posted);
              if(!$user->IsLoggedIn() && substr($comment->contacturl, 0, 7) == 'mailto:')
                $comment->contacturl = '';
              $comment->canchange = $user->IsLoggedIn() && ($comment->canchange == $user->ID && $comment->markdown || $user->IsAdmin());
              unset($comment->markdown);
              $ajax->Data->comments[] = $comment;
            }
            // TODO: check if there are more comments (and support loading more)
          } else
            $ajax->Fail('error getting comments.');
        break;
      case 'add':
        if(VerifyCommonFields($ajax, $_POST))
          if($user->IsLoggedIn() || isset($_POST['name']))  // name can be blank but needs to have been sent if not logged in
            if(isset($_POST['md']) && !ctype_space($_POST['md']) && $_POST['md'] != '') {
              $ajax->Data->html = t7format::Markdown($_POST['md']);
              $ajax->Data->posted = +time();
              if(!$user->IsLoggedIn()) {
                $ajax->Data->name = trim($_POST['name']);
                if($ajax->Data->name == '')
                  $ajax->Data->name = $user->DisplayName;  // grab the default display name for non-logged-in users
                $ajax->Data->contacturl = t7format::Link($_POST['contact']);
                $ajax->Data->canchange = false;
                $ajax->Data->friend = false;
                $ajax->Data->username = $ajax->Data->displayname = $ajax->Data->avatar = $ajax->Data->level = null;
              }
              $ins = $user->IsLoggedIn()
                ? 'insert into ' . $_POST['type'] . '_comments (' . KeyName($_POST['type']) . ', posted, user, html, markdown) values (\'' . $db->escape_string($_POST['key']) . '\', \'' . $ajax->Data->posted . '\', \'' . $user->ID . '\', \'' . $db->escape_string($ajax->Data->html) . '\', \'' . $db->escape_string($_POST['md']) . '\')'
                : 'insert into ' . $_POST['type'] . '_comments (' . KeyName($_POST['type']) . ', posted, name, contacturl, html, markdown) values (\'' . $db->escape_string($_POST['key']) . '\', \'' . $ajax->Data->posted . '\', \'' . $db->escape_string($ajax->Data->name) . '\', \'' . $db->escape_string($ajax->Data->contacturl) . '\', \'' . $db->escape_string(t7format::Markdown($_POST['md'])) . '\', \'' . $db->escape_string($_POST['md']) . '\')';
              if($db->real_query($ins)) {
                $ajax->Data->id = $db->insert_id;
                if($act = $db->query('select title, url from contributions where srctbl=\'' . $_POST['type'] . '_comments\' and id=\'' . +$ajax->Data->id . '\''))
                  if($act = $act->fetch_object())
                    t7send::Tweet('comment on ' . $act->title, 'http://' . $_SERVER['HTTP_HOST'] . $act->url);
                $ajax->Data->posted = t7format::TimeTag('g:i a \o\n l F jS Y', $ajax->Data->posted);
                if($user->IsLoggedIn()) {
                  $db->real_query('update users_stats set comments=(select count(1) from contributions where conttype=\'comment\' and author=\'' . +$user->ID . '\' group by author) where id=\'' . +$user->ID . '\'');
                  $ajax->Data->canchange = true;
                  $ajax->Data->username = $user->Username;
                  $ajax->Data->displayname = $user->DisplayName;
                  $ajax->Data->friend = false;
                  $ajax->Data->avatar = $user->Avatar;
                  $ajax->Data->level = $user->GetLevelName();
                  $ajax->Data->name = '';
                  $ajax->Data->contacturl = '';
                }
              } else
                $ajax->Fail('error saving comment.');
            } else
              $ajax->Fail('comment missing or empty.');
        break;
      case 'edit':
        if(VerifyCommonFields($ajax, $_POST, 'id'))
          if($user->IsLoggedIn())
            if($comment = $db->query('select user, markdown, html from ' . $_POST['type'] . '_comments where id=\'' . +$_POST['id'] . '\' limit 1'))
              if($comment = $comment->fetch_object())
                if($user->ID == $comment->user || $user->IsAdmin())
                  if($comment->markdown)
                    $ajax->Data->markdown = $comment->markdown;
                  elseif($user->IsAdmin())
                    $ajax->Data->markdown = $comment->html;
                  else
                    $ajax->Fail('this comment does not have editable content.');
                else
                  $ajax->Fail('you can only edit comments you posted.');
              else
                $ajax->Fail('comment not found.');
            else
              $ajax->Fail('error looking up comment.');
          else
            $ajax->Fail('you must be signed in to edit your comment.  you were probably signed out for inactivity.');
        break;
      case 'save':
        if(VerifyCommonFields($ajax, $_POST, 'id'))
          if($user->IsLoggedIn())
            if(isset($_POST['markdown']) && trim($_POST['markdown']))
              if($comment = $db->query('select user from ' . $_POST['type'] . '_comments where id=\'' . +$_POST['id'] . '\' limit 1'))
                if($comment = $comment->fetch_object())
                  if($user->ID == $comment->user || $user->IsAdmin()) {
                    $ajax->Data->html = t7format::Markdown($_POST['markdown']);
                    if(!$db->real_query('update ' . $_POST['type'] . '_comments set markdown=\'' . $db->escape_string($_POST['markdown']) . '\', html=\'' . $db->escape_string($ajax->Data->html) . '\' where id=\'' . +$_POST['id'] . '\' limit 1'))
                      $ajax->Fail('error updating comment');
                  } else
                    $ajax->Fail('you can only edit comments you posted.');
                else
                  $ajax->Fail('comment not found.');
              else
                $ajax->Fail('error looking up comment.');
            else
              $ajax->Fail('comment was empty.  if you intend to delete your comment, cancel editing and use delete instead.');
          else
            $ajax->Fail('you must be signed in to edit your comment.  you were probably signed out for inactivity.');
        break;
      case 'delete':
        if(VerifyCommonFields($ajax, $_POST, 'id'))
          if($user->IsLoggedIn())
            if($comment = $db->query('select user from ' . $_POST['type'] . '_comments where id=\'' . +$_POST['id'] . '\' limit 1'))
              if($comment = $comment->fetch_object())
                if($user->ID == $comment->user || $user->IsAdmin())
                  if($db->real_query('delete from ' . $_POST['type'] . '_comments where id=\'' . +$_POST['id'] . '\' limit 1'))
                    $db->real_query('update users_stats set comments=(select count(1) from contributions where conttype=\'comment\' and author=\'' . +$user->ID . '\' group by author) where id=\'' . +$user->ID . '\'');
                  else
                    $ajax->Fail('error deleting comment.');
                else
                  $ajax->Fail('you can only delete comments you posted.');
              else
                $ajax->Fail('comment not found.');
            else
              $ajax->Fail('error looking up comment.');
          else
            $ajax->Fail('you must be signed in to delete your comment.  you were probably signed out for inactivity.');
        break;
      default:
        $ajax->Fail('unknown function name.  supported function names are: get, add, delete.');
        break;
    }
    $ajax->Send();
    die;
  }

  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $cmtform = new auForm('pagecomments', '/comments.php');
  $cmtform->Add(new auFormData('page', ''));
  $cmtform->Add(new auFormData('ref', ''));
  if($user->Valid) {
    $cmtform->Add(new auFormData('uid', ''));
    //$cmtform->AddText('name', '');
  } else {
    $cmtform->Add(new auFormString('name', 'name', 'enter your name so i will know who you are', false));
    $cmtform->Add(new auFormString('contact', 'contact', 'enter an e-mail address or a url', false));
  }
  $cmtform->Add(new auFormMultiString('pagecomments', 'comments', 'enter your comments (you may use t7code)', true, '', true, 40));
  $cmtform->Add(new auFormButtons(array('post ' . ($user->Valid ? 'as ' . $user->Name : 'anonymously'), 'update'), 'post your comment on this page'));
  switch($cmtform->Submitted()) {
    case 'post ' . ($user->Valid ? 'as ' . $user->Name : 'anonymously'):
      if($cmtform->CheckInput($user->Valid)) {
        if(isset($_POST['uid']) && is_numeric($_POST['uid'])) {
          if($user->Valid && $_POST['uid'] == $user->ID) {
            if(false !== $db->Put('insert into comments (page, instant, uid, comments) values (\'' . addslashes($_POST['page']) . '\', ' . time() . ', ' . +$_POST['uid'] . ', \'' . addslashes(auText::BB2HTML($_POST['pagecomments'], false, false)) . '\')', 'error saving comment')) {
              tweetComment($_POST['page'], $user->Name);
              $db->Change('update userstats set comments=comments+1 where uid=' . +$_POST['uid']);
              $user->UpdateRank();
              //if($_POST['uid'] != 1)
              //  auSend::EMail($user->Name . ' has commented on ' . $_POST['page'], 'http://' . $_SERVER['HTTP_HOST'] . $_POST['page'] . '#comments' . "\n\n" . $_POST['pagecomments'], 'whatyousay@' . _HOST, 'misterhaan@' . _HOST, 'track7 comments', 'misterhaan');
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
        <a href="/hb/thread1/">how to use t7code</a> for instructions and try
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
          if(false !== $db->Put('insert into comments (page, instant, name, url, comments) values (\'' . addslashes(htmlspecialchars($_POST['page'])) . '\', ' . time() . ', \'' . addslashes(htmlspecialchars($_POST['name'])) . '\', \'' . addslashes(auText::FixLink($_POST['contact'])) . '\', \'' . addslashes(auText::BB2HTML($_POST['pagecomments'], false, false)) . '\')', 'error saving comments')) {
            tweetComment($_POST['page'], $_POST['name']);
            //auSend::EMail($_POST['name'] . ' has commented on ' . $_POST['page'], 'http://' . $_SERVER['HTTP_HOST'] . $_POST['page'] . '#comments' . "\n\n" . $_POST['pagecomments'], 'whatyousay@' . _HOST, 'misterhaan@' . _HOST, 'track7 comments', 'misterhaan');
            if($_POST['ref'])
              $_POST['page'] = $_POST['ref'];
            header('Location: http://' . $_SERVER['HTTP_HOST'] . $_POST['page'] . '#comments');
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
            $update = 'update comments set page=\'' . addslashes($_POST['page']) . '\', uid=\'' . addslashes($_POST['uid']) . '\', name=\'' . addslashes(htmlspecialchars($_POST['name'])) . '\', url=\'' . addslashes(htmlspecialchars($_POST['url'])) . '\', comments=\'' . addslashes(auText::BB2HTML($_POST['pagecomments'], false, false)) . '\' where id=' . $_POST['id'];
            if(false !== $db->Change($update, 'error updating comment')) {
              header('Location: http://' . $_SERVER['HTTP_HOST'] . $_POST['goback']);
              die;
            }
          }
        } else {
          $update = 'update comments set comments=\'' . addslashes(auText::BB2HTML($_POST['pagecomments'], false, false)) . '\' where id=' . +$_POST['id'] . ' and uid=\'' . $user->ID . '\'';
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
        $cmtfrm->Add(new auFormData('id', $_GET['edit']));
        $cmtfrm->Add(new auFormData('goback', $_GET['goback']));
        $cmtfrm->Add(new auFormString('page', 'page', 'change which page this comment should display on', true, $comment->page, 40, 255));
        $cmtfrm->Add(new auFormInteger('uid', 'user', 'id of the user who posted this comment', false, $comment->uid, 3, 4));
        $cmtfrm->Add(new auFormString('name', 'name', 'name of poster if not from a user', false, $comment->name, 30, 45));
        $cmtfrm->Add(new auFormString('url', 'url', 'contact url of poseter if not from a user', false, $comment->url, 60, 100));
        $cmtfrm->Add(new auFormMultiString('pagecomments', 'comments', 'the actual comments posted', true, auText::HTML2BB($comment->comments), true));
        $cmtfrm->Add(new auFormButtons('update', 'save changes to this comment'));
        $cmtfrm->WriteHTML(true);
        $page->End();
        die;
      } elseif($user->Valid && $user->ID == $comment->uid) {
        $page->Start('edit comment');
        $cmtfrm = new auForm('pagecomments');
        $cmtfrm->Add(new auFormData('id', $_GET['edit']));
        $cmtfrm->Add(new auFormData('goback', $_GET['goback']));
        $cmtfrm->Add(new auFormMultiString('pagecomments', 'comments', 'the actual comments posted', true, auText::HTML2BB($comment->comments), true));
        $cmtfrm->Add(new auFormButtons('update', 'save changes to this comment'));
        $cmtfrm->WriteHTML(true);
        $page->End();
        die;
      }
    }
  } elseif(is_numeric($_GET['delete'])) {
    $comment = 'select uid from comments where id=' . $_GET['delete'];
    if($comment = $db->GetRecord($comment, 'error checking which user posted this comment', 'unable to find comment', true)) {
      if($user->GodMode || $user->valid && $comment->uid == $user->id) {
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
      $comments = 'select c.id, c.page, c.instant, c.uid, u.login, c.name, c.url, c.comments, p.signature, p.avatar, s.rank, f.frienduid, n.flags&' . _FLAG_USERCONTACT_SHOWEMAIL . ' as showemail, n.email, n.website from comments as c left join users as u on c.uid=u.uid left join userprofiles as p on c.uid=p.uid left join userstats as s on c.uid=s.uid left join usercontact as n on c.uid=n.uid left join userfriends as f on c.uid=f.frienduid and f.fanuid=\'' . $user->ID . '\' where c.uid=' . $u->uid . ' order by instant desc';
    } else {
      $page->AddFeed('track7 page comments', '/feeds/comments.rss');
      $page->Start('comment history<a class="feed" href="/feeds/comments.rss" title="rss feed of comment history"><img src="/style/feed.png" alt="feed" /></a>');
      $comments = 'select c.id, c.page, c.instant, c.uid, u.login, c.name, c.url, c.comments, p.signature, p.avatar, s.rank, f.frienduid, n.flags&' . _FLAG_USERCONTACT_SHOWEMAIL . ' as showemail, n.email, n.website from comments as c left join users as u on c.uid=u.uid left join userprofiles as p on c.uid=p.uid left join userstats as s on c.uid=s.uid left join usercontact as n on c.uid=n.uid left join userfriends as f on c.uid=f.frienduid and f.fanuid=\'' . $user->ID . '\' order by instant desc';
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
          if($comment->frienduid) {
?>
            <img src="/style/friend.png" alt="friend" title="<?=$comment->login; ?> is your friend" />
<?
          }
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
              <div class="page">page:&nbsp; <a href="<?=htmlspecialchars($comment->page); ?>"><?=htmlspecialchars($comment->page); ?></a></div>
              <div class="time">posted:&nbsp; <span class="response"><?=strtolower($user->tzdate('g:i:s a, M d, Y', $comment->instant)); ?></span></div>
            </div>
            <?=$comment->comments; ?>

<?
        if($comment->uid > 0 && $comment->signature) {
?>
            <p class="signature">
              <?=$comment->signature; ?>

            </p>
<?
        }
?>
            <div class="foot">
<?
        if($comment->uid) {
?>
              <div class="userlinks">
                <a href="/user/sendmessage.php?to=<?=$comment->login; ?>" title="send <?=$comment->login; ?> a private message"><img src="/style/pm.png" alt="" />pm</a>
<?
            if($comment->showemail)
              echo '              <a href="mailto:' . auText::SafeEmail($comment->email) . '" title="send ' . $comment->login . ' an e-mail"><img src="/style/email.png" alt="" />e-mail</a>' . "\n";
            if($comment->website)
              echo '              <a href="' . $comment->website . '" title="visit ' . $comment->login . '\'s website"><img src="/style/www.png" alt="" />www</a>' . "\n";
            if($user->Valid && !$comment->frienduid)
              echo '              <a href="/user/friends.php?add=' . $comment->login . '" title="add ' . $comment->login . ' to your friend list"><img src="/style/friend-add.png" alt="" />add friend</a>' . "\n";
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
              <a href="/comments.php?edit=<?=$comment->id; ?>&amp;goback=<?=$url; ?>" title="edit this comment"><img src="/style/edit.png" alt="" />edit</a>
              <a href="/comments.php?delete=<?=$comment->id; ?>&amp;goback=<?=$url; ?>" title="delete this comment"><img src="/style/del.png" alt="" />delete</a>
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

  /**
   * Tweet that a new comment was posted.
   * @param string $url URL to the comment.
   * @param string $page Page the comment was posted on.
   * @param string $name Name of comment author.
   */
  function tweetComment($page, $name) {
    $pagename = explode('/', rtrim($page, '/'));
    $pagename = $pagename[count($pagename) - 1];
    $twurl = auSend::Bitly('http://' . str_replace('m.', 'www.', $_SERVER['HTTP_HOST']) . $page . '#comments');
    $tweet = 'comment on ' . $pagename . ' by ' . $name . ': ' . $twurl;
    auSend::Tweet($tweet);
  }

  /**
   * Verify the type and key fields, which are needed to know what the comments
   * apply to.
   * @param t7ajax $ajax Ajax object for potential error message
   * @param array $req Request array to check for type and key; $_GET or $_POST
   * @param string $field2 Second field to require; key for generic or id for specific
   * @return boolean true if verified successfully
   */
  function VerifyCommonFields($ajax, $req, $field2 = 'key') {
    if(isset($req['type']))
      switch($req['type']) {
        case 'blog':
        case 'guide':
        case 'photos':
        case 'art':
        case 'lego':
        case 'stories':
          if(isset($req[$field2]))
            return true;
          else
            $ajax->Fail($field2 . ' is required');
          break;
        default:
          $ajax->Fail('invalid comment type specified.  valid types are:  blog, guide, photos, art, lego, stories.');
          break;
      }
    else
      $ajax->Fail('comment type is required');
    return false;
  }

  function KeyName($type) {
    switch($type) {
      case 'blog':
        return 'entry';
      case 'photos':
        return 'photo';
      case 'stories':
        return 'story';
      default:
        return $type;
    }
    return false;
  }
?>
