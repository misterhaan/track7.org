<?
  require_once 't7include.inc';
  if(isset($_GET['player'])) {
    $player = 'select login, uid from users where login=\'' . TEXT::slash($_GET['player']) . '\'';
    if($player = $db->query($player, 'error looking up player', 'player not found')) {
      $player = $player->nextrow();
      $page->start($player->login . '\'s rounds - disc golf', $player->login . '\'s disc golf rounds');
      $filter .= ' and r.uid=' . $player->uid;
    }
  }
  if(is_numeric($_GET['course'])) {
    $course = 'select name, id from dgcourses where id=' . $_GET['course'];
    if($course = $db->query($course, 'error looking up course', 'course not found')) {
      $course = $course->nextrow();
      $page->start($course->name . ' rounds - disc golf', $course->name . ' disc golf rounds');
      $filter .= ' and r.courseid=' . $course->id;
    }
  }
  $page->start('rounds - disc golf', 'disc golf rounds');
  $rounds = 'select r.id, u.login, r.courseid, c.name, c.location, r.instant, r.score, r.courseid, r.comments from dgrounds as r, users as u, dgcourses as c where r.uid=u.uid and r.courseid=c.id' . $filter . ' order by instant desc';
  if($rounds = $db->splitquery($rounds, 20, 0, '', '', 'error looking up rounds', 'no rounds have been entered')) {
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>player</th><th>date</th><th>score</th><th>course</th><th>location</th><th>comments</th></tr></thead>
        <tbody>
<?
    while($round = $rounds->nextrow()) {
      $round->comments = str_replace(array('<br />', "\n", '&nbsp;'), ' ', $round->comments);
      if(strlen($round->comments) < 1)
        $round->comments = '<em>[none]</em>';
      elseif(strlen($round->comments) > 17)
        $round->comments = substr($round->comments, 0, 15) . '...';
?>
          <tr><td><a href="players.php?p=<?=$round->login; ?>"><?=$round->login; ?></a></td><td><?=TEXT::smartdate($round->instant); ?></td><td class="number"><?=$round->score; ?></td><td><a href="courses.php?id=<?=$round->courseid; ?>"><?=$round->name; ?></a></td><td><?=$round->location; ?></td><td><a href="rounds.php?id=<?=$round->id; ?>"><?=$round->comments; ?></a></td></tr>
<?
    }
?>
        </tbody>
      </table>

<?
    $page->splitlinks();
  }
  $page->end();
?>
