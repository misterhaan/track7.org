<?
  define('MAX_SUGGEST', 8);

  $getvars = array('showall');
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if(isset($_GET['return']) && $_GET['return'] == 'suggest') {
    header('Content-Type: text/plain; charset=utf-8');
    $count = 0;
    $us = 'select login from users where login like \'' . addslashes($_GET['match']) . '%\' order by login';
    if($us = $db->Get($us, '', '', true))
      while($u = $us->NextRecord()) {
        echo "\n" . $u->login;
        if(++$count >= MAX_SUGGEST)
          die("\n<more>");
      }
    $us = 'select login from users where not login like \'' . addslashes($_GET['match']) . '%\' and login like \'%' . addslashes($_GET['match']) . '%\' order by login';
    if($us = $db->Get($us, '', '', true))
      while($u = $us->NextRecord()) {
        echo "\n" . $u->login;
        if(++$count >= MAX_SUGGEST)
          die("\n<more>");
      }
    if(!$count)
      die('<no matches>');
    die;
  }

  $page->Start('user list', 'track7 users');
?>
      <p>
        users’ names are linked to their profiles.
      </p>
<?
  if(!isset($_GET['showall'])) {
?>
      <p>
        only users who have posted something to track7 are shown.
      </p>
<?
    if($user->Valid) {
?>
        <ul><li><a href="?showall">show all users</a></li></ul>
<?
    }
    $us = ' and s.fans+s.signings+s.comments+s.posts+s.discs+s.rounds>0';
  }
  $us = 'select u.login, s.since, s.lastlogin, s.pageload, s.rank, s.fans, s.posts, s.comments from users as u, userstats as s where u.uid=s.uid' . $us . ' order by lastlogin desc';
  if($us = $db->GetSplit($us, 20, 0, '', '', 'error reading user information', 'there are currently no registered users')) {
?>
      <table class="text" cellspacing="0">
        <thead class="minor"><tr><th>user</th><th>on?</th><th>frequency</th><th>last login</th><th>registered</th><th>fans</th><th>posts</th><th>comments</th></tr></thead>
        <tbody>
<?
    while($u = $us->NextRecord()) {
?>
          <tr><td><a href="<?=$u->login; ?>/" title="view <?=$u->login; ?>'s profile"><?=$u->login; ?></a></td><td><?=onlineIcon($u->pageload); ?></td><td><?=$u->rank; ?></td><td><?=($u->lastlogin == null ? '' : auText::HowLongAgo($u->lastlogin) . ' ago'); ?></td><td><?=($u->since == null ? '' : auText::SmartTime($u->since, $user)); ?></td><td class="number"><?=$u->fans; ?></td><td class="number"><?=$u->posts; ?></td><td class="number"><?=$u->comments; ?></td></tr>
<?
    }
?>
        </tbody>
      </table>

<?
    $page->SplitLinks();
  }
  $page->End();

  function onlineIcon($pageload) {
    $on = $pageload > time() -900;
    return '<img src="/images/' . ($on ? 'online' : 'offline') . '.png" alt='. ($on ? 'online' : 'offline') . ' title="' . ($on ? 'was here in the past 15 minutes' : 'hasn’t been here in the past 15 minutes') . '">';
  }
?>
