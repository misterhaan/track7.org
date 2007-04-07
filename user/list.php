<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auText.php';

  $page->Start('user list', 'track7 users');
?>
      <p>
        click a username to view the user's profile, which (as you may expect)
        has more information about the user, including ways (at least one) to
        contact them.
      </p>
<?
  if($_GET['show'] != 'all') {
?>
      <p>
        only users who have posted something to track7 are shown.
      </p>
<?
    if($user->Valid) {
?>
        <ul><li><a href="?show=all">show all users</a></li></ul>
<?
    }
    $us = ' and s.signings+s.comments+s.posts+s.discs+s.rounds>0';
  }
  $us = 'select u.login, s.since, s.lastlogin, s.pageload, s.rank, s.posts, s.comments from users as u, userstats as s where u.uid=s.uid' . $us . ' order by lastlogin desc';
  if($us = $db->GetSplit($us, 20, 0, '', '', 'error reading user information', 'there are currently no registered users')) {
?>
      <table class="text" cellspacing="0">
        <thead class="minor"><tr><th>user</th><th>status</th><th>frequency</th><th>last login</th><th>registered</th><th>posts</th><th>comments</th></tr></thead>
        <tbody>
<?
    while($u = $us->NextRecord()) {
?>
          <tr><td><a href="<?=$u->login; ?>/" title="view <?=$u->login; ?>'s profile"><?=$u->login; ?></a></td><td><?=$u->pageload > time() -900 ? 'online' : 'offline'; ?></td><td><?=$u->rank; ?></td><td><?=($u->lastlogin == null ? '' : auText::HowLongAgo($u->lastlogin) . ' ago'); ?></td><td><?=($u->since == null ? '' : auText::SmartTime($u->since, $user)); ?></td><td class="number"><?=$u->posts; ?></td><td class="number"><?=$u->comments; ?></td></tr>
<?
    }
?>
        </tbody>
      </table>

<?
    $page->SplitLinks();
  }
  $page->End();
?>
