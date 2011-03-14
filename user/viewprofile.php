<?
/*----------------------------------------------------------------------------*\
 | purpose:  display the profile of the user passed through $_GET['login'].   |
 |                                                                            |
\*----------------------------------------------------------------------------*/

  define('ACTIVITY_LIMIT', 7);

  // -----------------------------------------------[ display a profile ]-- //
  if(isset($_GET['login'])) {
    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

    $u = 'select u.uid, u.login, s.pageload from users as u left join userstats as s on u.uid=s.uid where login=\'' . addslashes($_GET['login']) . '\'';
    if($u = $db->GetRecord($u, 'error looking up basic profile information', 'could not find a user named \'' . htmlspecialchars($_GET['login'], ENT_QUOTES, _CHARSET) .'\'', true)) {
      $login = htmlspecialchars($u->login, ENT_QUOTES, _CHARSET);

      $title = $login;

      if($u->pageload)
        if($u->pageload > time() -900)
          $title = '<img src="/images/online.png" alt=online title="' . $login . ' was here in the past 15 minutes"> ' . $title;
        else
          $title = '<img src="/images/offline.png" alt=offline title="' . $login . ' hasn’t been here in the past 15 minutes"> ' . $title;

      if($user->Valid) {
        $isfriend = 'select 1 from userfriends where fanuid=\'' . $user->ID . '\' and frienduid=\'' . $u->uid . '\'';
        if($isfriend = $db->Get($isfriend, 'error checking if you are already friends', ''))
          $title = '<img src="/style/friend.png" alt="*" title="' . ($u->uid == $user->ID ? 'you are' : $login . ' is') . ' your friend"> ' . $title;
      }

      $page->Start($login, $title);
?>
      <ul class=actions>
<?
      if($u->uid == $user->ID)
        echo '        <li class=edit><a title="edit your profile" href="/user/editprofile.php?user=' . $login . '">edit profile</a></li>' . "\n";
      elseif($user->GodMode) {
        echo '        <li class=edit><a title="edit ' . $login . '’s profile" href="/user/editprofile.php?user=' . $login . '">edit profile</a></li>' . "\n";
        echo '        <li class=del><a title="delete ' . $login . '" href="/user/delete.php?user=' . $login . '">delete user</a></li>' . "\n";
      }
?>
        <li class=pm><a title="send <?=$login; ?> a private message on track7" href="/user/sendmessage.php?to=<?=$login; ?>">send message</a></li>
<?
      if($isfriend)
        if($u->uid == $user->ID)
          echo '        <li class=delfriend><a title="remove yourself from your friends (won’t decrease your fan count)" href="/user/friends.php?remove=' . $login . '&amp;from=' . $_SERVER['REQUEST_URI'] . '">remove friend</a></li>';
        else
          echo '        <li class=delfriend><a title="remove ' . $login . ' from your friends" href="/user/friends.php?remove=' . $login . '&amp;from=' . $_SERVER['REQUEST_URI'] . '">remove friend</a></li>';
              elseif($user->Valid)
        if($u->uid == $user->ID)
          echo '        <li class=addfriend><a title="add yourself as a friend (won’t increase your fan count)" href="/user/friends.php?add=' . $login . '&amp;from=' . $_SERVER['REQUEST_URI'] . '">add friend</a></li>' . "\n";
        else
          echo '        <li class=addfriend><a title="add ' . $login . ' as a friend" href="/user/friends.php?add=' . $login . '&amp;from=' . $_SERVER['REQUEST_URI'] . '">add friend</a></li>' . "\n";
?>
      </ul>
<?
      $contact = 'select email, website, jabber, icq, aim, twitter, steam, spore, flags from usercontact where uid=' . $u->uid;
      $contact = $db->GetRecord($contact, 'error looking up contact information', 'contact information not found');
      if($contact->email || $contact->website || $contact->jabber || $contact->icq || $contact->aim || $contact->twitter || $contact->steam || $contact->spore) {
?>
      <div id=connect>
        <h2>connect with <?=$login; ?></h2>
        <ul class=actions id=contactlinks>
<?
        if($contact->email) {
          echo '          <li class=email>';
          if($contact->flags & _FLAG_USERCONTACT_SHOWEMAIL) {
            $safemail = auText::SafeEmail($contact->email);
            echo '<a href="mailto:' . $safemail . '" title=send ' . $login . ' an e-mail">' . $safemail . '</a>';
          } else
            echo '<em title="' . $login . ' has an e-mail address on file but doesn’t show it to avoid spam">[not shown]</em>';
          echo "</li>\n";
        }
        if($contact->website)
          echo '          <li class=www><a title="visit ' . $login . '’s website" href="' . $contact->website . '">' . $contact->website . "</a></li>\n";
        if($contact->jabber)
          echo '          <li class=xmpp><a title="contact ' . $login . ' on jabber / xmpp / gtalk" href="xmpp:' . $contact->jabber . '?message">' . $contact->jabber . "</a></li>\n";
        if($contact->icq)
          echo '          <li class=icq><a title="contact ' . $login . ' on icq" href="icq:send_message?uin=' . $contact->icq . '">' . $contact->icq . "</a></li>\n";
        if($contact->aim)
          echo '          <li class=aim><a title="contact ' . $login . ' on aim" href="aim:goim?screenname=' . $contact->aim . '">' . $contact->aim . "</a></li>\n";
        if($contact->twitter)
          echo '          <li class=twitter><a title="view ' . $login . '’s twitter profile" href="http://twitter.com/' . $contact->twitter . '">@' . $contact->twitter . "</a></li>\n";
        if($contact->steam)
          echo '          <li class=steam><a title="view ' . $login . '’s steam community profile" href="http://steamcommunity.com/id/' . $contact->steam . '">' . $contact->steam . "</a></li>\n";
        if($contact->spore)
          echo '          <li class=spore><a title="view ' . $login . '’s spore profile" href="http://www.spore.com/view/myspore/' . $contact->spore . '">' . $contact->spore . "</a></li>\n";
?>
        </ul>
      </div>

<?
      }

      $profile = 'select avatar, signature, location, geekcode, hackerkey from userprofiles where uid=' . $u->uid;
      $profile = $db->GetRecord($profile, 'error looking up profile information', 'profile information not found');

      echo '' . "\n";
?>
      <img id=profileavatar src="<?=$profile->avatar ? '/user/avatar/' . $login . '.' . $profile->avatar : '/style/noavatar.jpg'; ?>" alt="">
      <table class=list id=userinfo>
        <tr><th>username</th><td><?=$login; ?></td></tr>
<?

      $stats = 'select since, lastlogin, signings, rank, posts, comments, discs, rounds, fans, rpgchars from userstats where uid=' . $u->uid;
      if($stats = $db->GetRecord($stats, 'error looking up statistics for user')) {
?>
        <tr><th>frequency</th><td><?=$stats->rank; ?></td></tr>
        <tr><th>last login</th><td title="<?=strtolower($user->tzdate('M d, Y \a\t g:i:s a', $stats->lastlogin)); ?>"><?=auText::HowLongAgo($stats->lastlogin); ?> ago</td></tr>
        <tr><th>registered</th><td title="<?=strtolower($user->tzdate('M d, Y \a\t g:i:s a', $stats->since)); ?>"><?=auText::HowLongAgo($stats->since); ?> ago</td></tr>
<?
      }
?>
      </table>
<?
      $song = 'select instant, title, artist, length, filename from usersongs where uid=' . $u->uid . ' order by instant desc';
      $song = $db->GetRecord($song, 'error looking up last song played', '');
      if($profile->location || $profile->geekcode || $profile->hackerkey || $profile->signature || $song || $u->uid == $user->ID) {
?>
      <table class=list id=userprofile>
<?
        if($profile->location)
          echo '        <tr><th>location</th><td>' . $profile->location . "</td></tr>\n";
        if($profile->geekcode)
          echo '        <tr><th>geek code</th><td class=code>' . $profile->geekcode . "</td></tr>\n";
        if($profile->hackerkey)
          echo '        <tr><th>hacker key</th><td class=code>' . $profile->hackerkey . "</td></tr>\n";
        if($profile->signature)
          echo '        <tr><th>signature</th><td class=signature><p>' . $profile->signature . "</p></td></tr>\n";
        if($song) {
          $time = explode(':', $song->length);
          if($song->instant + $time[0] * 60 + $time[1] < time()) {
            if($u->uid == $user->ID)
              echo '        <tr><th>current track</th><td>none <span class=detail>(<a href="/user/songtrack.php">music tracking instructions</a>)</span>' . "</td></tr>\n";
          } else {
            if($song->title) {
              $song->title = '“' . $song->title . '”';
              if($song->artist)
                $song->title .= ' by ' . $song->artist;
              $song = $song->title;
            } else
              $song = $song->filename;
            echo '        <tr><th>current track</th><td>' . $song . ($u->uid == $user->ID ? ' <span class=details>(<a href="/user/songtrack.php">music tracking instructions</a>)</span>' : '') . "</td></tr>\n";
          }
        } elseif($u->uid == $user->ID)
          echo '        <tr><th>current track</th><td><a href="/user/songtrack.php">start tracking your music</a>' . "</td></tr>\n";
?>
      </table>
<?
      }

      if($stats->fans || $stats->posts || $stats->comments || $stats->rounds || $stats->discs || $stats->rpgchars) {
?>
      <div id=rank>
        <h2>rankings</h2>
        <ul>
<?
        if($stats->fans)
          echo '          <li>#' . getRank($db, 'fans', $stats->fans) . ' in fans with ' . $stats->fans . "</li>\n";
        if($stats->posts)
          echo '          <li>#' . getRank($db, 'posts', $stats->posts) . ' in posts with <a href="/hb/recentposts.php?author=' . $login . '">' . $stats->posts . "</a></li>\n";
        if($stats->comments)
          echo '          <li>#' . getRank($db, 'comments', $stats->comments) . ' in comments with <a href="/comments.php?user=' . $login . '">' . $stats->comments . "</a></li>\n";
        if($stats->rounds)
          echo '          <li>#' . getRank($db, 'rounds', $stats->rounds) . ' in rounds with <a href="/geek/discgolf/rounds.php?player=' . $login . '">' . $stats->rounds . "</a></li>\n";
        if($stats->discs)
          echo '          <li>#' . getRank($db, 'discs', $stats->discs) . ' in discs with <a href="/geek/discgolf/caddy.php?player=' . $login . '">' . $stats->discs . "</a></li>\n";
        if($stats->rpgchars)
          echo '          <li>#' . getRank($db, 'rpgchars', $stats->rpgchars) . ' in characters with <a href="/geek/rpg/?player=' . $login . '">' . $stats->rpgchars . "</a></li>\n";
?>
        </ul>
      </div>
<?
      }

      if($stats->posts || $stats->comments || $stats->rounds) {
?>
      <div id=useractivity<?=$stats->fans || $stats->posts || $stats->comments || $stats->rounds || $stats->discs || $stats->rpgchars ? ' class=hasrank' : ''; ?>>
        <h2><?=$login; ?>’s activity</h2>
        <ol>
<?
        $comment = $post = $round = false;
        $comments = 'select page, instant from comments where uid=\'' . addslashes($u->uid) . '\' order by instant desc';
        if($comments = $db->GetLimit($comments, 0, ACTIVITY_LIMIT, 'error looking up comments', ''))
          $comment = $comments->NextRecord();
        $posts = 'select id, thread, number, subject, instant from hbposts where uid=\'' . addslashes($u->uid) . '\' order by instant desc';
        if($posts = $db->GetLimit($posts, 0, ACTIVITY_LIMIT, 'error looking up forum activity', ''))
          $post = $posts->NextRecord();
        $rounds = 'select r.id, r.instant, r.score, r.courseid, c.name from dgrounds as r left join dgcourses as c on c.id=r.courseid where r.uid=\'' . addslashes($u->uid) . '\' order by r.instant desc';
        if($rounds = $db->GetLimit($rounds, 0, ACTIVITY_LIMIT, 'error looking up disc golf rounds', ''))
          $round = $rounds->NextRecord();
        $activity = 0;
        while($activity < ACTIVITY_LIMIT && ($comment || $post || $round)) {
          if($comment && (!$post || $post->instant < $comment->instant) && (!$round || $round->instant < $comment->instant)) {
            $comment->title = explode('/', $comment->page);
            $comment->title = $comment->title[count($comment->title) - 1];
            echo '         <li class=comment>commented on <a href="' . htmlspecialchars($comment->page) . '">' . htmlspecialchars($comment->title) . '</a> ' . auText::HowLongAgo($comment->instant) . " ago</li>\n";
            $comment = $comments->NextRecord();
          } elseif($post && (!$comment || $comment->instant < $post->instant) && (!$round || $round->instant < $post->instant)) {
            echo '          <li class=post>posted <a href="/hb/thread' . $post->thread . '/#p' . $post->id . '">' . $post->subject . '</a> ' . auText::HowLongAgo($post->instant) . " ago</li>\n";
            $post = $posts->NextRecord();
          } elseif($round && (!$comment || $comment->instant < $round->instant) && (!$post || $post->instant < $round->instant)) {
            echo '          <li class=round>scored <a href="/geek/discgolf/rounds.php?id=' . $round->id . '">' . $round->score . '</a> at <a href="/geek/discgolf/courses.php?id=' . $round->courseid . '">' . $round->name . '</a> ' . auText::HowLongAgo($round->instant) . " ago</li>\n";
            $round = $rounds->NextRecord();
          }
          $activity++;
        }
?>
        </ol>
      </div>
      <br class=clear>
<?
      }

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
<?
            if($user->ID == $u->uid) {
?>
          <div class="actions">
            <a href="/user/sendmessage.php?to=<?=$friend->login; ?>" title="send <?=$friend->login; ?> a message"><img src="/style/pm.png" alt="pm" /></a>
            <a href="/user/friends.php?remove=<?=$friend->login; ?>&amp;from=/user/<?=$u->login; ?>/%23friends" title="remove <?=$friend->login; ?> from your friends list"><img src="/style/friend-del.png" alt="remove" /></a>
          </div>
<?
            }
?>
        </div></li>
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

  /**
   * Count how many users have a higher value in a certain stat than this user
   * @param auDB $db Database object
   * @param string $name Field name of the stat to check
   * @param integer $value User's value for the stat to check
   */
  function getRank(&$db, $name, $value) {
    if(false !== $rank = $db->GetValue('select count(1) from userstats where ' . $name . '>=' . +$value))
      return $rank;
    return '<em title=unknown>?</em>';
  }
?>
