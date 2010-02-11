<?
/*----------------------------------------------------------------------------*\
 | purpose:  display the profile of the user passed through $_GET['login'].   |
 |                                                                            |
\*----------------------------------------------------------------------------*/

  // -----------------------------------------------[ display a profile ]-- //
  if(isset($_GET['login'])) {
    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

    $page->Start(htmlspecialchars($_GET['login'], ENT_QUOTES, _CHARSET) . '’s profile', 'profile for ' . htmlspecialchars($_GET['login'], ENT_QUOTES, _CHARSET));

    $u = 'select uid, login from users where login=\'' . addslashes($_GET['login']) . '\'';
    if($u = $db->GetRecord($u, 'error looking up basic profile information', 'could not find a user named \'' . htmlspecialchars($_GET['login'], ENT_QUOTES, _CHARSET) .'\'', true)) {
      if($user->Valid) {
        $isfriend = 'select 1 from userfriends where fanuid=\'' . $user->ID . '\' and frienduid=\'' . $u->uid . '\'';
        $isfriend = $db->Get($isfriend, 'error checking if you are already friends', '');
        if($u->uid == $user->ID) {
          if($isfriend)
            $page->info('you are your friend');
          else {
?>
      <p><a href="/user/friends.php?add=<?=$_GET['login']; ?>&amp;from=<?=$_SERVER['REQUEST_URI']; ?>">add yourself to your friends list</a> (won't increase your fan count)</p>
<?
          }
?>
      <p><a href="/user/editprofile.php">edit profile</a></p>

<?
        } else {
          if($isfriend)
            $page->info($_GET['login'] . ' is your friend');
          else {
?>
      <p><a href="/user/friends.php?add=<?=$_GET['login']; ?>&amp;from=<?=$_SERVER['REQUEST_URI']; ?>">add <?=$_GET['login']; ?> to your friends list</a></p>

<?
          }
          if($user->GodMode) {
?>
      <p><a href="/user/editprofile.php?user=<?=$_GET['login']; ?>">edit this user's profile</a></p>
      <p><a href="/user/delete.php?user=<?=$_GET['login']; ?>">delete this user</a></p>

<?
          }
        }
      }
      $song = 'select instant, title, artist, length, filename from usersongs where uid=' . $u->uid . ' order by instant desc';
      $song = $db->GetRecord($song, 'error looking up last song played', '');
      $profile = 'select avatar, signature, location, geekcode, hackerkey from userprofiles where uid=' . $u->uid;
      $profile = $db->GetRecord($profile, 'error looking up profile information', 'profile information not found');

      if($profile->signature || $profile->avatar || $profile->location || $profile->geekcode || $profile->hackerkey || $song->length || $u->uid == $user->ID) {
        $page->Heading('profile');
?>
      <table id="userprofile" class="columns" cellspacing="0">
<?
        if($profile->avatar) {
?>
        <tr><th>avatar</th><td><img src="/user/avatar/<?=$_GET['login']; ?>.<?=$profile->avatar; ?>" alt="" /></td></tr>
<?
        }
        if($profile->signature) {
?>
        <tr><th>signature</th><td><?=$profile->signature; ?></td></tr>
<?
        }
        if($profile->location) {
?>
        <tr><th>location</th><td><?=$profile->location; ?></td></tr>
<?
        }
        if($profile->geekcode) {
          $lines = explode('<br />', $profile->geekcode);
          if(strtoupper($lines[count($lines) - 1]) == '------END GEEK CODE BLOCK------')
            unset($lines[count($lines) - 1]);
          if(strtoupper($lines[0]) == '-----BEGIN GEEK CODE BLOCK-----')
            unset($lines[0]);
          $profile->geekcode = implode('<br />', $lines);
?>
        <tr><th>geek code</th><td><?=$profile->geekcode; ?></td></tr>
<?
        }
        if($profile->hackerkey) {
?>
        <tr><th>hacker key</th><td><?=$profile->hackerkey; ?></td></tr>
<?
        }
        if($song) {
          $time = $song->length;
          if($time) {
            $time = explode(':', $time);
            $time = $song->instant + $time[0] * 60 + $time[1];
            if($time < time()) {
              if($u->uid == $user->ID)
                echo '        <tr><th>current track</th><td>none <span class="detail">(<a href="/user/songtrack.php">music tracking instructions</a>)</span></td></tr>' . "\n";
            } else {
              if($song->title) {
                $song->title = '“' . $song->title . '”';
                if($song->artist)
                  $song->title .= ' by ' . $song->artist;
                $song = $song->title;
              } else
                $song = $song->filename;
              echo '        <tr><th>current track</th><td>' . $song . ($u->uid == $user->ID ? ' <span class="detail">(<a href="/user/songtrack.php">music tracking instructions</a>)</span>' : '') . '</td></tr>' . "\n";
            }
          }
        } elseif($u->uid == $user->ID)
          echo '        <tr><th>current track</th><td><a href="/user/songtrack.php">start tracking your music</a></td></tr>' . "\n";
?>
      </table>

<?
      }
      $contact = 'select email, website, jabber, icq, aim, steam, flags from usercontact where uid=' . $u->uid;
      if($contact = $db->GetRecord($contact, 'error looking up contact information', 'contact information not found')) {
        $page->heading('contact information');
?>
      <table class="columns" cellspacing="0">
        <tr><th>track7</th><td><a href="/user/sendmessage.php?to=<?=htmlspecialchars($_GET['login']); ?>"><?=htmlspecialchars($_GET['login']); ?></a></td></tr>
<?
        if(strlen($contact->email) > 0) {
          echo '        <tr><th>e-mail</th><td>';
          if($contact->flags & _FLAG_USERCONTACT_SHOWEMAIL) {
            $safemail = auText::SafeEmail($contact->email);
            echo '<a href="mailto:' . $safemail . '" title="send ' . $_GET['login'] . ' an e-mail">' . $safemail . '</a>';
          } else
            echo '<em>[not shown]</em>';
          echo '</td></tr>' . "\n";
        }
        if(strlen($contact->website) > 0)
          echo '        <tr><th>website</th><td><a href="' . $contact->website . '" title="visit ' . $_GET['login'] . '\'s website">' . $contact->website . '</a></td></tr>' . "\n";
        if(strlen($contact->jabber) > 0) {
          echo '        <tr><th>jabber</th><td>' . $contact->jabber . '</td></tr>' . "\n";
        }
        if(strlen($contact->icq) > 0)
          echo '        <tr><th>icq</th><td><a href="http://web.icq.com/whitepages/message_me?uin=' . $contact->icq . '&amp;action=message" title="contact ' . $_GET['login'] . ' via icq">' . $contact->icq . '</a></td></tr>' . "\n";
        if(strlen($contact->aim) > 0)
          echo '        <tr><th>aim</th><td><a href="aim:goim?screenname=' . $contact->aim . '" title="contact ' . $_GET['login'] . ' via aim">' . $contact->aim . '</a></td></tr>' . "\n";
        if(strlen($contact->steam) > 0)
          echo '        <tr><th>steam</th><td><a href="http://steamcommunity.com/id/' . $contact->steam . '" title="view ' . $_GET['login'] . '’s steam community profile">' . $contact->steam . '</a></td></tr>' . "\n";
?>
      </table>

<?
      }
      $stats = 'select since, lastlogin, signings, rank, posts, comments, discs, rounds, fans, rpgchars from userstats where uid=' . $u->uid;
      if($stats = $db->GetRecord($stats, 'error looking up statistics for user')) {
        $page->Heading('statistics');
?>
      <table class="columns" cellspacing="0">
        <tr><th>user id</th><td><?=$u->uid; ?></td></tr>
        <tr><th>registered</th><td><?=($stats->since == null ? '' : strtolower($user->tzdate('M d, Y \a\t g:i:s a', $stats->since))); ?></td></tr>
        <tr><th>last login</th><td><?=($stats->lastlogin == null ? '' : strtolower($user->tzdate('M d, Y \a\t g:i:s a', $stats->lastlogin))); ?></td></tr>
        <tr><th>frequency</th><td><?=$stats->rank; ?></td></tr>
        <tr><th>fans</th><td><?=$stats->fans; ?></td></tr>
        <tr><th>posts</th><td><?=$stats->posts; ?></td></tr>
        <tr><th>comments</th><td><?=$stats->comments; ?></td></tr>
<?
      if($stats->discs > 0)
        echo '        <tr><th>discs</th><td>' . $stats->discs . '</td></tr>' . "\n";
      if($stats->rounds > 0)
        echo '        <tr><th>rounds</th><td>' . $stats->rounds . '</td></tr>' . "\n";
      if($stats->rpgchars > 0)
        echo '        <tr><th>characters</th><td>' . $stats->rpgchars . '</td></tr>' . "\n";
?>
      </table>

<?
        if($user->ID == $u->uid || $user->GodMode) {
          $page->Heading('friends (only visible to you)', 'friends');
          $friends = 'select u.login, p.avatar from userfriends as f left join users as u on u.uid=f.frienduid left join userprofiles as p on p.uid=u.uid where fanuid=\'' . $u->uid . '\' order by login';
          if($friends = $db->Get($friends, 'error looking up friends', 'you don’t currently have any <a href="/user/friends.php">friends</a>.&nbsp; visit <a href="/user/list.php">other users’</a> profiles to add them to your list.')) {
?>
      <ul id="friends">
<?
            while($friend = $friends->NextRecord()) {
              if($friend->avatar)
                $friend->avatar = '/user/avatar/' . $friend->login . '.' . $friend->avatar;
              else
                $friend->avatar = '/style/noavatar.jpg';
?>
        <li><div class="friend">
          <a class="profile" href="/user/<?=$friend->login; ?>/" title="view <?=$friend->login; ?>’s profile">
            <img alt="" src="<?=$friend->avatar; ?>" />
            <?=$friend->login; ?>

          </a>
          <div class="actions">
            <a href="/user/sendmessage.php?to=<?=$friend->login; ?>" title="send <?=$friend->login; ?> a message"><img src="/style/pm.png" alt="pm" /></a>
            <a href="/user/friends.php?remove=<?=$friend->login; ?>&amp;from=/user/<?=$u->login; ?>/%23friends" title="remove <?=$friend->login; ?> from your friends list"><img src="/style/del.png" alt="remove" /></a>
          </div>
        </div></li>
<?
            }
?>
      </ul>
<?
          }
        }
        if($stats->posts) {
          $posts = 'select p.instant, p.id, t.tags, p.subject, p.thread, t.title from hbposts as p left join hbthreads as t on p.thread=t.id where p.uid=' . $u->uid . ' order by instant desc';
          if($posts = $db->GetLimit($posts, 0, 5, 'error getting list of posts by this user')) {
            $page->Heading('recent forum posts');
            require_once '../hb/hb.inc';
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>date</th><th>subject</th><th>thread</th><th>tags</th></tr></thead>
        <tbody>
<?
            while($post = $posts->NextRecord()) {
?>
          <tr><td><?=strtolower(auText::SmartTime($post->instant, $user)); ?></td><td><a href="/hb/thread<?=$post->thread; ?>/#p<?=$post->id; ?>"><?=$post->subject; ?></a></td><td><a href="/hb/thread<?=$post->thread; ?>/"><?=$post->title; ?></a></td><td><?=HB::TagLinks($post->tags); ?></td></tr>
<?
            }
?>
        </tbody>
        <tfoot class="seemore"><tr><td colspan="4"><a href="/hb/recentposts.php?author=<?=$_GET['login']; ?>">view more of <?=$_GET['login']; ?>'s posts</a></td></tr></tfoot>
      </table>

<?
          }
        }
        if($stats->comments > 0) {
          $comments = 'select page, instant from comments where uid=' . $u->uid . ' order by instant desc';
          if($comments = $db->GetLimit($comments, 0, 5, 'error getting list of comments posted by this user')) {
            $page->Heading('recent comments');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>date</th><th>page url</th></tr></thead>
        <tbody>
<?
            while($comment = $comments->NextRecord()) {
?>
          <tr><td><?=strtolower($user->tzdate('M d, Y \a\t g:i:s a', $comment->instant)); ?></td><td><a href="<?=htmlspecialchars($comment->page); ?>#comments"><?=htmlspecialchars($comment->page); ?></a></td></tr>
<?
            }
?>
        </tbody>
        <tfoot class="seemore"><tr><td colspan="4"><a href="/comments.php?user=<?=$_GET['login']; ?>">view more of <?=$_GET['login']; ?>'s comments</a></td></tr></tfoot>
      </table>

<?
          }
        }
        if($stats->discs > 0 || $stats->rounds > 0 || $stats->rpgchars > 0) {
          $page->Heading('more information');
?>
      <ul>
<?
          if($stats->discs > 0 || $stats->rounds > 0) {
?>
        <li><a href="/geek/discgolf/players.php?p=<?=$_GET['login']; ?>">disc golf player profile</a></li>
<?
          }
          if($stats->rpgchars > 0) {
?>
        <li><a href="/geek/rpg/?player=<?=$_GET['login']; ?>">rpg characters</a></li>
<?
          }
?>
      </ul>
<?
        }
      }
    }
    $page->End();
  } else  // display user list
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/list.php');
?>
