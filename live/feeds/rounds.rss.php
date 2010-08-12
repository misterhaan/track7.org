<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFeed.php';

  $rss = new auFeed('track7 disc golf rounds', '/geek/discgolf/rounds.php', 'disc golf rounds posted on track7', 'copyright 2010 track7');
  $rounds = 'select r.id, r.instant, r.courseid, c.name, r.uid, r.player, u.login, r.roundtype, r.tees, r.score, r.comments from dgrounds as r left join dgcourses as c on c.id=r.courseid left join users as u on u.uid=r.uid where entryuid is null or r.uid=0 order by instant desc';
  if($rounds = $db->GetLimit($rounds, 0, 15, '', ''))
    while($round = $rounds->NextRecord()) {
      $rss->AddItem('<p>' . ($round->uid ? '<a href="http://' . $_SERVER['HTTP_HOST'] . '/geek/discgolf/players.php?p=' . $round->login . '" title="more information on this player">' . $round->login . '</a>' : $round->player) . ' played a ' . $round->roundtype . ' round ' . ($round->tees ? 'from the ' . $round->tees . ' tees ' : '') . 'at <a href="http://' . $_SERVER['HTTP_HOST'] . '/geek/discgolf/courses.php?id=' . $round->courseid . '" title="more information on this course">' . $round->name . '</a>, scoring ' . $round->score . '.</p><p>' . $round->comments . '</p>', $round->name . ' round - ' . ($round->uid ? $round->login : $round->player), '/geek/discgolf/rounds.php?id=' . $round->id, $round->instant, '/geek/discgolf/rounds.php?id=' . $round->id, true);
    }
  $rss->End();
?>
