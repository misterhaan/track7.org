<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('disc golf');
  
  if($golfer = $user->Valid) {
    $golfer = 'select discs+rounds as golfer from userstats where uid=' . $user->ID;
    $golfer = $db->GetValue($golfer, 'error looking up user statistics', 'user statistics not found', true);
  }
  if($golfer) {
?>
      <p>
        welcome back to the disc golf section!&nbsp; use the links below to
        enter more information, or visit
        <a href="players.php?p=<?=$user->Name; ?>">your player profile</a> to
        see what you&rsquo;ve already entered.
      </p>

<?
  } else {
?>
      <p>
        the disc golf section is inspired by
        <a href="http://www.discgolfstats.com/" title="disc golf course statistics">folfscores.com</a>,
        which can track scores for you but doesn&rsquo;t do everything i was
        hoping for.&nbsp; beyond just scores, here you can also enter which
        discs you have in your arsenal.&nbsp; you will need a
        <a href="/user/">user account</a> before you can save any information.&nbsp;
        below are previews of some of the disc golf data currently on track7.
      </p>

<?
  }
  $players = 'select u.login, s.discs, s.rounds from users as u left join userstats as s on u.uid=s.uid where discs>0 or rounds>0 order by rounds desc, discs desc';
  if($players = $db->GetLimit($players, 0, 5, 'error reading disc golf players', '')) {
    $page->Heading('players');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>player</th><th>discs</th><th>rounds</th></tr></thead>
        <tbody>
<?
    while($player = $players->NextRecord()) {
?>
          <tr><td><a href="players.php?p=<?=$player->login; ?>"><?=$player->login; ?></a></td><td class="number"><?=$player->discs; ?></td><td class="number"><?=$player->rounds; ?></td></tr>
<?
    }
?>
        </tbody>
      </table>

<?
  }

  $courses = 'select id, name, location, holes, par, rounds from dgcourses order by rounds desc, name';
  if($courses = $db->GetLimit($courses, 0, 5, 'error reading disc golf courses', 'there are currently no disc golf courses in the database')) {
    $page->Heading('courses');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>course</th><th>location</th><th>holes</th><th>par</th><th>rounds</th></tr></thead>
        <tbody>
<?
    while($course = $courses->NextRecord()) {
?>
          <tr><td><a href="courses.php?id=<?=$course->id; ?>" title="view details for this course"><?=$course->name; ?></a></td><td><?=$course->location; ?></td><td class="number"><?=$course->holes; ?></td><td class="number"><?=$course->par; ?></td><td class="number"><?=$course->rounds; ?></td></tr>
<?
    }
    $courses = 'select count(1) as c from dgcourses';
    if($courses = $db->Get($courses, '')) {
      if($courses = $courses->NextRecord())
        $courses = $courses->c;
      else
        $courses = 0;
    } else
      $courses = '<em>error</em>';
?>
        </tbody>
        <tfoot class="seemore"><tr><td colspan="5"><a href="courses.php">view more courses (<?=$courses; ?> total)</a></td></tr></tfoot>
      </table>

<?
  }

  $discs = 'select id, mfgr, name, `type`, speed, glide, turn, fade, popularity from dgdiscs order by popularity desc, name';
  if($discs = $db->GetLimit($discs, 0, 5, 'error reading discs', 'there are currently no discs in the database')) {
    $page->Heading('discs');
?>
      <table class="data" id="golfdiscs" cellspacing="0">
        <thead><tr><th>disc</th><th>brand</th><th>type</th><th>speed</th><th>glide</th><th>turn</th><th>fade</th><th>in use</th></tr></thead>
        <tbody>
<?
    while($disc = $discs->NextRecord()) {
?>
          <tr><td><a href="discs.php?id=<?=$disc->id; ?>" title="view details for this disc"><?=$disc->name; ?></a></td><td><?=$disc->mfgr; ?></td><td><?=$disc->type; ?></td><td><?=$disc->speed; ?></td><td><?=$disc->glide; ?></td><td><?=str_pad($disc->turn, 2, '+', STR_PAD_LEFT); ?></td><td><?=str_pad($disc->fade, 2, '+', STR_PAD_LEFT); ?></td><td><?=$disc->popularity; ?></td></tr>
<?
    }
    $discs = 'select count(1) as c from dgdiscs';
    if($discs = $db->Get($discs, '')) {
      if($discs = $discs->NextRecord())
        $discs = $discs->c;
      else
        $discs = 0;
    } else
      $discs = '<em>error</em>';
?>
        </tbody>
        <tfoot class="seemore">
          <tr><td colspan="8"><a href="discs.php">view more discs (<?=$discs; ?> total)</a></td></tr>
        </tfoot>
      </table>

<?
  }

  $page->End();
?>
