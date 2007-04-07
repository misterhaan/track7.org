<?
/*----------------------------------------------------------------------------*\
 | purpose:  display the profile of the user passed through $_GET['login'].   |
 |                                                                            |
\*----------------------------------------------------------------------------*/

  // -----------------------------------------------[ display a profile ]-- //
  if(isset($_GET['login'])) {
    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
    require_once 'auText.php';

    $page->Start(htmlspecialchars($_GET['login']) . '\'s profile', 'profile for ' . htmlspecialchars($_GET['login']));

    $u = 'select uid from users where login=\'' . addslashes($_GET['login']) . '\'';
    if($u = $db->GetRecord($u, 'error looking up basic profile information', 'could not find a user named \'' . htmlspecialchars($_GET['login']) .'\'', true)) {
      if($user->Valid && $u->uid == $user->ID) {
?>
      <p><a href="/user/editprofile.php">edit profile</a></p>

<?
      } elseif($user->GodMode) {
?>
      <p><a href="/user/editprofile.php?user=<?=$_GET['login']; ?>">edit this user's profile</a></p>

<?
      }
      $song = 'select instant, title, artist, length, filename from usersongs where uid=' . $u->uid . ' order by instant desc';
      $song = $db->GetRecord($song, 'error looking up last song played', '');
      $profile = 'select avatar, signature from userprofiles where uid=' . $u->uid;
      $profile = $db->GetRecord($profile, 'error looking up profile information', 'profile information not found');
        
      if($profile->signature || $profile->avatar || $song->length || $u->uid == $user->ID) {
        $page->Heading('profile');
?>
      <table class="columns" cellspacing="0">
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
                $song->title = '&quot;' . $song->title . '&quot;';
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
      $contact = 'select email, website, jabber, icq, aim, flags from usercontact where uid=' . $u->uid;
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
?>
      </table>

<?
      }
      $stats = 'select since, lastlogin, signings, rank, posts, comments, discs, rounds from userstats where uid=' . $u->uid;
      if($stats = $db->GetRecord($stats, 'error looking up statistics for user')) {
        $page->Heading('statistics');
?>
      <table class="columns" cellspacing="0">
        <tr><th>user id</th><td><?=$u->uid; ?></td></tr>
        <tr><th>registered</th><td><?=($stats->since == null ? '' : strtolower($user->tzdate('M d, Y \a\t g:i:s a', $stats->since))); ?></td></tr>
        <tr><th>last login</th><td><?=($stats->lastlogin == null ? '' : strtolower($user->tzdate('M d, Y \a\t g:i:s a', $stats->lastlogin))); ?></td></tr>
        <tr><th>frequency</th><td><?=$stats->rank; ?></td></tr>
        <tr><th>posts</th><td><?=$stats->posts; ?></td></tr>
        <tr><th>comments</th><td><?=$stats->comments; ?></td></tr>
<?
      if($stats->discs > 0)
        echo '        <tr><th>discs</th><td>' . $stats->discs . '</td></tr>' . "\n";
      if($stats->rounds > 0)
        echo '        <tr><th>rounds</th><td>' . $stats->rounds . '</td></tr>' . "\n";
?>
      </table>

<?
        if($stats->posts) {
          $posts = 'select p.instant, p.id, p.tid, t.fid, p.subject, t.title as thread, f.title as forum from oiposts as p, oithreads as t, oiforums as f where p.tid=t.id and t.fid=f.id and p.uid=' . $u->uid . ' order by instant desc';
          if($posts = $db->GetLimit($posts, 0, 5, 'error getting list of posts by this user')) {
            $page->Heading('recent forum posts');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>date</th><th>subject</th><th>thread</th><th>forum</th></tr></thead>
        <tbody>
<?
            while($post = $posts->NextRecord()) {
?>
          <tr><td><?=auText::SmartTime($post->instant, $user); ?></td><td><a href="/oi/f<?=$post->fid; ?>/t<?=$post->tid; ?>/#p<?=$post->id; ?>"><?=$post->subject; ?></a></td><td><a href="/oi/f<?=$post->fid; ?>/t<?=$post->tid; ?>/"><?=$post->thread; ?></a></td><td><a href="/oi/f<?=$post->fid; ?>/"><?=$post->forum; ?></a></td></tr>
<?
            }
?>
        </tbody>
        <tfoot class="seemore"><tr><td colspan="4"><a href="/oi/recentposts.php?author=<?=$_GET['login']; ?>">view more of <?=$_GET['login']; ?>'s posts</a></td></tr></tfoot>
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
        if($stats->discs > 0 || $stats->rounds > 0) {
          $page->Heading('more information');
?>
      <ul>
        <li><a href="/geek/discgolf/players.php?p=<?=$_GET['login']; ?>">disc golf player profile</a></li>
      </ul>
<?
        }
      }
    }
    $page->End();
  } else  // display user list
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/list.php');
?>
