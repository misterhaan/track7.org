<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if(isset($_GET['p'])) {
    $player = 'select u.uid, u.login, s.discs, s.rounds from users as u left join userstats as s on s.uid=u.uid where u.login=\'' . addslashes($_GET['p']) . '\'';
    if($player = $db->GetRecord($player, 'error looking up user id', 'user not found')) {
      $page->Start($player->login . '&rsquo;s player page - disc golf', $player->login, 'disc golf player');
?>
      <p>
        this page shows disc golf related information for <?=$player->login; ?>.&nbsp;
        for contact and other information about this player, view
        <a href="/user/<?=$player->login; ?>/"><?=$player->login; ?>&rsquo;s user profile</a>.
      </p>

<?
      $page->Heading('statistics');
      $stats = 'select skill, aces, birds, pars, bogies, doubles, holes from dgplayerstats where uid=' . $player->uid;
      if($stats = $db->GetRecord($stats, 'error reading statistics for this player')) {
        if(!$stats) {
          // calculate stats for this user since the row was missing
          $page->Info('statistics missing for this player--calculating now');
          $rounds = 'select scorelist from dgrounds where uid=' . $player->uid;
          $score = array(0, 0, 0, 0, 0, 0);
          if($rounds = $db->Get($rounds, 'error looking up scores for this player')) {
            while($round = $rounds->NextRecord()) {
              $scorelist = explode('|', $round->scorelist);
              foreach($scorelist as $s) {
                $score[0]++;
                $score[$s]++;
              }
            }
            $db->Put('insert into dgplayerstats (uid, aces, birds, pars, bogies, doubles, holes) values (' . $player->uid . ', ' . $score[1] . ', ' . $score[2] . ', ' . $score[3] . ', ' . $score[4] . ', ' . $score[5] . ', ' . $score[0] . ')', 'error saving statistics');
            $stats->aces = $score[1];
            $stats->birds = $score[2];
            $stats->pars = $score[3];
            $stats->bogies = $score[4];
            $stats->doubles = $score[5];
            $stats->holes = $score[0];
          }
        }
      }
      // eventually, i'd like to have a pie chart image script to include here
?>
      <table class="columns" cellspacing="0">
        <tr><th>discs</th><td><?=$player->discs; ?></td></tr>
        <tr><th>rounds</th><td><?=$player->rounds; ?></td></tr>
<?
      if($stats->holes) {
?>
        <tr><th>aces</th><td><?=$stats->aces; ?> (<?=percent($stats->aces, $stats->holes); ?>)</td></tr>
        <tr><th>birdies</th><td><?=$stats->birds; ?> (<?=percent($stats->birds, $stats->holes); ?>)</td></tr>
        <tr><th>pars</th><td><?=$stats->pars; ?> (<?=percent($stats->pars, $stats->holes); ?>)</td></tr>
        <tr><th>bogies</th><td><?=$stats->bogies; ?> (<?=percent($stats->bogies, $stats->holes); ?>)</td></tr>
        <tr><th>doubles</th><td><?=$stats->doubles; ?> (<?=percent($stats->doubles, $stats->holes); ?>)</td></tr>
<?
      }
?>
      </table>

<?
      $page->Heading('courses played');
      $courses = 'select c.id, c.name, count(1) as rounds from dgcourses as c, dgrounds as r where r.uid=' . $player->uid . ' and r.courseid=c.id group by c.id order by rounds desc';
      if($courses = $db->Get($courses, 'error looking up courses played', 'no courses played')) {
?>
      <ul>
<?
        while($course = $courses->NextRecord()) {
?>
        <li><a href="courses.php?id=<?=$course->id; ?>"><?=$course->name; ?></a> (<?=$course->rounds; ?> round<?=$course->rounds > 1 ? 's' : ''; ?>)</li>
<?
        }
?>
      </ul>

<?
      }

      $page->Heading('recent rounds');
      $rounds = 'select r.id, r.instant, r.courseid, c.name, r.roundtype, r.tees, r.score, r.comments from dgrounds as r left join dgcourses as c on c.id=r.courseid where r.uid=' . $player->uid . ' and r.entryuid is null order by r.instant desc';
      if($rounds = $db->GetLimit($rounds, 0, 5, 'error looking up recent rounds', 'no rounds found')) {
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>date</th><th>course</th><th>type</th><th>tees</th><th>score</th><th>comments</th></tr></thead>
<?
        $roundcount = 'select count(1) from dgrounds where uid=' . $player->uid;
        if($roundcount = $db->GetValue($roundcount, '', '')) {
?>
        <tfoot class="seemore"><tr><td colspan="6"><a href="rounds.php?player=<?=$player->login; ?>">view more of <?=$player->login; ?>&rsquo;s rounds (<?=$roundcount; ?> total)</a></td></tr></tfoot>
<?
        }
?>
        <tbody>
<?
        while($round = $rounds->NextRecord()) {
          $round->comments = trim(html_entity_decode(strip_tags($round->comments), ENT_COMPAT, _CHARSET));
          if(strlen($round->comments) > 17)
            $round->comments = mb_substr($round->comments, 0, 15, _CHARSET) . '...';
?>
          <tr><td><a href="rounds.php?id=<?=$round->id; ?>"><?=strtolower(auText::SmartDate($round->instant)); ?></a></td><td><a href="courses.php?id=<?=$round->courseid; ?>"><?=$round->name; ?></a></td><td><?=$round->roundtype; ?></td><td><?=$round->tees; ?></td><td class="number"><?=$round->score; ?></td><td><?=$round->comments; ?></td></tr>
<?
        }
?>
        </tbody>
      </table>

<?
      }

      $page->Heading('discs owned');
      $discs = 'select c.id, c.discid, d.name, c.mass, c.color, c.status, c.comments from dgcaddy as c left join dgdiscs as d on d.id=c.discid where c.uid=' . $player->uid . ' and (c.status=\'bag\' or c.status=\'reserve\') order by +c.status';
      if($discs = $db->Get($discs, 'error looking up discs', 'no discs found')) {
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>color</th><th>mass</th><th>disc</th><th>status</th><th>comments</th></tr></thead>
        <tfoot class="seemore"><tr><td colspan="5"><a href="caddy.php?player=<?=$player->login; ?>">view all of <?=$player->login; ?>&rsquo;s discs (including lost or sold)</a></td></tr></tfoot>
        <tbody>
<?
        while($disc = $discs->NextRecord()) {
          if(!strlen($disc->color))
            $disc->color = '<em>unknown</em>';
          $disc->comments = html_entity_decode(str_replace(array('\r', '\n'), '', strip_tags($disc->comments)), ENT_COMPAT, _CHARSET);
          if(strlen($disc->comments) > 22)
            $disc->comments = mb_substr($disc->comments, 0, 20, _CHARSET) . '...';
?>
          <tr><td><a href="caddy.php?id=<?=$disc->id; ?>"><?=$disc->color; ?></a></td><td><?=$disc->mass; ?> g</td><td><a href="discs.php?id=<?=$disc->discid; ?>"><?=$disc->name; ?></a></td><td><?=$disc->status; ?></td><td><?=$disc->comments; ?></td></tr>
<?
        }
?>
        </tbody>
      </table>

<?
      }

      $page->End();
      die;
    }
  }
  $page->Start('players - disc golf', 'disc golf players');
?>
      <p>
        the following track7 users have either posted their scores for at least
        one round of disc golf or have posted which discs they own.&nbsp; choose
        one of them to view their disc golf player profile.
      </p>

<?
  $players = 'select u.login, s.rounds, s.discs from users as u, userstats as s where u.uid=s.uid and (s.discs>0 or s.rounds>0) order by (2*s.rounds+s.discs) desc';
  if($players = $db->Get($players, 'error looking up disc golf players', 'no players found')) {
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
  $page->End();

  function percent($value, $total) {
    return round(100 * $value / $total) . '%';
  }
?>
