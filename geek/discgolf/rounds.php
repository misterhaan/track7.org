<?
  $getvars = array('player', 'id');
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if($user->Valid && $_GET['id'] == 'new' && is_numeric($_GET['course'])) {
    $course = 'select id, holes, name, location, teelist, parlist from dgcourses where id=\'' . addslashes($_GET['course']) . '\'';
    if($course = $db->GetRecord($course, 'error looking up course', 'course not found', true)) {
      $page->ResetFlag(_FLAG_PAGES_COMMENTS);
      $rf = getRoundForm($page, $db, $user, $course);
      $okscores = true;
      if($rf->CheckInput(true)) {
        $page->Start('save round(s) - ' . $course->name . ' - disc golf', 'saving scores');
        $partner = $_POST['type'] == 'doubles - best disc';
        for($row = 0; isset($_POST['player'][$row]); $row++) {
          $scores = checkScoreRow($row, $db, $course->holes, $partner);
          if(!$scores['okscores'])
            $page->Error('cannot save invalid scores.&nbsp; every hole must have a number between 1 and 9.&nbsp; scores entered were:&nbsp; ' . implode(', ', $scores['score']) . '.');
          else {
            if($scores['player'] === false)
              $page->Error('cannot save scores for nonexistant user ' . htmlspecialchars($_POST['player'][$row]) . ':&nbsp; ' . implode(', ', $scores['score']) . '.');
            else if($scores['player'])
              saveScores($course->id, $scores['score'], $scores['player'], htmlspecialchars($_POST['player'][$row]), $db, $user, $page);

            if($partner)
              if($scores['partner'] === false)
                $page->Error('cannot save scores for nonexistant user ' . htmlspecialchars($_POST['partner'][$row]) . ':&nbsp; ' . implode(', ', $scores['score']) . '.');
              else if($scores['partner'])
                saveScores($course->id, $scores['score'], $scores['partner'], htmlspecialchars($_POST['partner'][$row]), $db, $user, $page);
          }
        }
        $page->End();
        die;
      }
      $page->Start('new round - ' . $course->name . ' - disc golf', 'add a round', $course->name);
?>
      <p>
        use the form below to enter your scores or your friends’ scores.&nbsp;
        for your own scores, leave the player field blank.&nbsp; for friends’
        scores, fill in their user name.&nbsp; note that the best disc, worst
        disc, and comments fields are ignored unless you are entering your own
        scores.
      </p>
<?
      if(!$okscores)
        $page->Error('scores for holes 1-' . +$course->holes . ' must be a number between 1 and 9');
      $rf->WriteHTML(true);
      $page->End();
      die;
    }
  }

  if(is_numeric($_GET['id'])) {
    $round = 'select u.login, r.uid, r.courseid, c.name, c.location, c.teelist, r.roundtype, r.tees, r.instant, c.holes, c.parlist, c.par, r.scorelist, r.score, r.bestdisc, r.worstdisc, r.comments, r.entryuid, eu.login as entryuser from dgrounds as r left join dgcourses as c on c.id=r.courseid left join users as u on u.uid=r.uid left join users as eu on eu.uid=r.entryuid where r.id=\'' . +$_GET['id'] . '\'';
    if($round = $db->GetRecord($round, 'error looking up round', 'round not found', true)) {
      $page->Start(date('Y-m-d', $round->instant) . ' - ' . $round->name, $round->login . '’s ' . strtolower(auText::SmartDate($round->instant, $user)) . ' round', $round->name);
      if($round->uid == $user->ID) {
        if(isset($_GET['edit'])) {
          $rf = getRoundForm($page, $db, $user, $round, $round);
          $okscores = true;
          $edited = false;
          if($rf->CheckInput(true)) {
            $scores = checkScoreFields(0, $round->holes);
            if($okscores = $scores['okscores']) {
              $scores = $scores['score'];
              $update = 'update dgrounds set instant=\'' . $user->tzstrtotime($_POST['date']) . '\', roundtype=' . ($_POST['type'] == 'null' ? 'null' : '\'' . addslashes($_POST['type']) . '\'') . ', tees=' . ($_POST['tees'] && $_POST['tees'] != 'null' ? '\'' . addslashes($_POST['tees']) . '\'' : 'null') . ', scorelist=\'' . implode('|', $scores) . '\', score=\'' . array_sum($scores) . '\', bestdisc=' . $_POST['bestdisc'] . ', worstdisc=' . $_POST['worstdisc'] . ', comments=\'' . addslashes(auText::BB2HTML($_POST['comments'])) . '\' where id=\'' . addslashes($_GET['id']) . '\'';
              if(false !== $db->Change($update, 'error updating round')) {
                $edited = true;
                require_once 'util.php';
                if(calcAvgScores($db, $round->courseid, $round->roundtype, $round->tees, true))
                  if(calcAvgScores($db, $round->courseid, $_POST['type'] == 'null' ? null : addslashes($_POST['type']), $_POST['tees'] && $_POST['tees'] != 'null' ? addslashes($_POST['tees']) : null)) {
                    calcPlayerStats($db, $round->uid);
                    $page->Info('round updated');
                    $round->instant = $user->tzstrtotime($_POST['date']);
                    $round->roundtype = $_POST['type'] == 'null' ? null : htmlentities($_POST['type'], ENT_COMPAT, _CHARSET);
                    $round->tees = $_POST['tees'] && $_POST['tees'] != 'null' ? htmlentities($_POST['tees'], ENT_COMPAT, _CHARSET) : null;
                    $round->scorelist = implode('|', $scores);
                    $round->score = array_sum($scores);
                    $round->bestdisc = $_POST['bestdisc'] == 'null' ? null : $_POST['bestdisc'];
                    $round->worstdisc = $_POST['worstdisc'] == 'null' ? null : $_POST['worstdisc'];
                    $round->comments = auText::BB2HTML($_POST['comments']);
                  }
              }
            }
          }
          if(!$edited) {
            if(!$okscores)
              $page->Error('scores for holes 1-' . +$round->holes . ' must be a number between 1 and 9');
            $rf->WriteHTML(true);
            $page->ResetFlag(_FLAG_PAGES_COMMENTS);
            $page->End();
            die;
          }
        }
        if(isset($_GET['approve'])) {
          $update = 'update dgrounds set entryuid=null where id=\'' . addslashes($_GET['id']) . '\'';
          if($db->Change($update, 'error marking round approved', 'round not updated')) {
            $page->Info('round approved');
            $round->entryuid = null;
          }
        }
        if(isset($_GET['delete'])) {
          $del = 'delete from dgrounds where id=\'' . addslashes($_GET['id']) . '\'';
          if(false !== $db->Change($del, 'error deleting round')){
            require_once 'util.php';
            // need to recalculate average scores now that this round is no longer included
            if(calcAvgScores($db, $round->courseid, $round->roundtype, $round->tees, true)) {
              $page->Info('round deleted');
              calcPlayerStats($db, $round->uid);
?>
      <ul>
        <li><a href="?player=<?=$user->Name; ?>">back to your rounds</a></li>
        <li><a href="courses.php?id=<?=$round->courseid; ?>">back to <?=$round->name; ?></a></li>
        <li><a href="rounds.php">back to all rounds</a></li>
      </ul>
<?
            }
          }
        }
?>
      <ul>
        <li><a href="?id=<?=$_GET['id']; ?>&amp;edit">edit this round</a></li>
<?
        if($round->entryuid) {
?>
        <li><a href="?id=<?=$_GET['id']; ?>&amp;approve">approve</a> or <a href="?id=<?=$_GET['id']; ?>&amp;delete">delete</a> this round (entered by <a href="/user/<?=$round->entryuser; ?>"><?=$round->entryuser; ?></a>)</li>
<?
        }
?>
      </ul>
<?
      } elseif($round->entryuid)
        $page->Info('this round has not yet been confirmed by ' . $round->login);
?>
      <p>
        on <?=strtolower(date('l, F j<\s\u\p>S</\s\u\p>, Y', $round->instant)); ?>,
        <a href="players.php?p=<?=$round->login; ?>" title="more information on this player"><?=$round->login; ?></a>
        played this <?=$round->roundtype; ?> round <?=$round->tees ? 'from the ' . $round->tees . ' tees ' : ''; ?>
        at <a href="courses.php?id=<?=$round->courseid; ?>" title="more information on this course"><?=$round->name; ?></a>.
      </p>

<?
      if($round->comments || $round->bestdisc || $round->worstdisc) {
        $page->Heading($round->login . '’s comments');
?>
      <p>
        <?=$round->comments; ?>

      </p>
<?
        if($round->bestdisc || $round->worstdisc) {
?>

      <table class="columns" cellspacing="0">
<?
          if($round->bestdisc) {
            $best = 'select c.discid, d.name, c.color, c.mass from dgcaddy as c left join dgdiscs as d on d.id=c.discid where c.id=\'' . $round->bestdisc . '\'';
            if($best = $db->GetRecord($best, 'error looking up best disc information', 'best disc not found', true)) {
?>
        <tr><th>best disc</th><td><a href="discs.php?id=<?=$best->discid; ?>" title="more information on this disc"><?=$best->name; ?></a> (<?=$best->color; ?> <?=$best->mass; ?>g)</td></tr>
<?
            }
          }
          if($round->worstdisc) {
            $worst = 'select c.discid, d.name, c.color, c.mass from dgcaddy as c left join dgdiscs as d on d.id=c.discid where c.id=\'' . $round->worstdisc . '\'';
            if($worst = $db->GetRecord($worst, 'error looking up worst disc information', 'worst disc not found', true)) {
?>
        <tr><th>worst disc</th><td><a href="discs.php?id=<?=$worst->discid; ?>" title="more information on this disc"><?=$worst->name; ?></a> (<?=$worst->color; ?> <?=$worst->mass; ?>g)</td></tr>
<?
            }
          }
?>
      </table>
<?
        }
      }
      $page->Heading('score');
      $avg = 'select avglist, avgscore from dgcoursestats where courseid=\'' . $round->courseid . '\' and roundtype=\'' . $round->roundtype . '\' and tees=\'' . $round->tees . '\'';
      if($avg = $db->GetRecord($avg, 'error looking up average scores for similar rounds', ''))
        $avg->avglist = explode('|', $avg->avglist);
?>
      <table class="columns" cellspacing="0">
        <tr><th>total</th><td class="number"><?=$round->score; ?></td></tr>
        <tr><th>course</th><td class="number"><?=diffScore($round->score, $round->par); ?></td></tr>
        <tr><th>par 3</th><td class="number"><?=diffScore($round->score, 3 * $round->holes); ?></td></tr>
        <tr><th>average</th><td class="number"><?=diffScore($round->score, $avg->avgscore); ?></td></tr>
      </table>
<?
      $round->scorelist = explode('|', $round->scorelist);
      if(!is_array($round->parlist))
        $round->parlist = explode('|', $round->parlist);
      showRoundHoles($round->holes, $round->scorelist, $round->parlist, $avg->avglist);
?>
      <ul>
        <li><a href="?player=<?=$round->login; ?>"><?=$round->login; ?>’s rounds</a></li>
        <li><a href="players.php?p=<?=$round->login; ?>"><?=$round->login; ?>’s player profile</a></li>
        <li><a href="courses.php?id=<?=$round->courseid; ?>"><?=$round->name; ?> information</a></li>
      </ul>
<?
      $page->End();
      die;
    }
  }

  $page->ResetFlag(_FLAG_PAGES_COMMENTS);
  if(strlen($_GET['player'])) {
    $page->Start(htmlentities($_GET['player'], ENT_COMPAT, _CHARSET) . '’s rounds - disc golf', htmlentities($_GET['player'], ENT_COMPAT, _CHARSET) . '’s rounds');
    $rounds = ' and u.login=\'' . addslashes($_GET['player']) . '\'';
  } else {
    $page->Start('rounds - disc golf', 'rounds');
    $rounds = '';
  }
  $rounds = 'select r.id, u.login, r.courseid, c.name, r.roundtype, r.tees, r.instant, r.score, r.comments from dgrounds as r left join dgcourses as c on c.id=r.courseid left join users as u on u.uid=r.uid where (r.entryuid is null or r.uid=\'' . $user->ID . '\')' . $rounds . ' order by instant desc';
  if($rounds = $db->GetSplit($rounds, 30, 0, '', '', 'error looking up rounds', 'no rounds entered')) {
?>
      <table class="text" cellspacing="0">
        <thead><tr><th>date</th><th>course</th><?=strlen($_GET['player']) ? '' : '<th>player</th>'; ?><th>type</th><th>tees</th><th>score</th></tr></thead>
        <tbody>
<?
    while($round = $rounds->NextRecord()) {
      $round->comments = trim(html_entity_decode(strip_tags($round->comments), ENT_COMPAT, _CHARSET));
      if(strlen($round->comments) > 71)
        $round->comments = substr($round->comments, 0, 69) . '...';
?>
          <tr><td><a href="?id=<?=$round->id; ?>" title="more information on this round"><?=strtolower(auText::SmartDate($round->instant, $user)); ?></a></td><td><a href="courses.php?id=<?=$round->courseid; ?>" title="more information on this course"><?=$round->name; ?></a></td><?=strlen($_GET['player']) ? '' : '<td><a href="players.php?p=' . $round->login . '" title="more information on this player">' . $round->login . '</a></td>'; ?><td><?=$round->roundtype; ?></td><td><?=$round->tees; ?></td><td><?=$round->score; ?></td></tr><tr class="comments"><td class="minor" colspan="6"><?=$round->comments; ?></td></tr>
<?
    }
?>
        </tbody>
      </table>
<?
    $page->SplitLinks();
    if(strlen($_GET['player'])) {
?>
      <ul><li><a href="players.php?p=<?=htmlentities($_GET['player'], ENT_COMPAT, _CHARSET); ?>"><?=htmlentities($_GET['player'], ENT_COMPAT, _CHARSET); ?>’s player profile</a></li></ul>
<?
    }
  }
  $page->End();

  // ------------------------------------------------------------[ diffScore ]--
  function diffScore($score, $par) {
    $diff = $score - $par;
    if($diff == 0)
      return 'even';
    if($diff > 0)
      return '+' . $diff;
    return $diff;
  }

  // -------------------------------------------------------[ showRoundHoles ]--
  function showRoundHoles($holes, $score, $par, $avg) {
?>
      <div id="parlist">
<?
    for($h = 0; $h < $holes; $h += 9)
      show9RoundHoles($h, $h + 9, $holes, $score, $par, $avg);
?>
      </div>

<?
  }

  function show9RoundHoles($shole, $ehole, $holes, $score, $par, $avg) {
?>
        <table class="data" cellspacing="0">
<?
    echo '          <thead><tr><td></td>';
    for($i = $shole + 1; $i <= $ehole; $i++)
      echo '<th>' . $i . '</th>';
    if($shole > 0 || $ehole < $holes)
      echo '<th>nine</th>';
    if($last = $ehole == $holes)
      echo '<th>total</th>';
    echo "</tr></thead>\n          <tbody>\n";
    echo '            <tr><th>score</th>';
    $sum = 0;
    for($i = $shole; $i < $ehole; $i++) {
      echo '<td>' . $score[$i] . '</td>';
      $sum += $score[$i];
    }
    echo '<th>' . $sum . '</th>';
    if($last)
      echo '<th>' . array_sum($score) . '</th>';
    echo "</tr>\n            <tr><th>par</th>";
    $sum = 0;
    for($i = $shole; $i < $ehole; $i++) {
      echo '<td>' . $par[$i] . '</td>';
      $sum += $par[$i];
    }
    echo '<th>' . $sum . '</th>';
    if($last)
      echo '<th>' . array_sum($par) . '</th>';
    echo "</tr>\n";
    if(is_array($avg)) {
      echo '            <tr><th>average</th>';
      $sum = 0;
      for($i = $shole; $i < $ehole; $i++) {
        echo '<td>' . number_format($avg[$i], 1) . '</td>';
        $sum += $avg[$i];
      }
      echo '<th>' . $sum . '</th>';
      if($last)
        echo '<th>' . number_format(array_sum($avg), 1) . '</th>';
      echo "</tr>\n";
    }
?>
          </tbody>
        </table>

<?
  }

  /**
   * Creates a form to create or edit a round.
   * @param auPageTrack7 Page layout object (for mobile layout flag)
   * @param auBD $db Database connection
   * @param auUserTrack7 $user Logged in user
   * @param object $course Course record
   * @param object $round Round record if editing, or false if new
   * @return auForm Round create/edit form object
   */
  function getRoundForm(&$page, &$db, $user, $course, $round = false) {
    if($round)
      $form = new auForm('editround', '?id=' . $_GET['id'] . '&edit');
    else
      $form = new auForm('newround', '?id=new&course=' . $course->id);
    $form->Add(new auFormHTML('course', $course->name . ' (' . $course->location . ')' . ($round ? '' : ' <a id="changecourse" href="?id=new">change</a>')));
    $form->Add(new auFormInstant('date', 'date', 'when the round was played', false, $round ? $user->tzdate('Y-m-d', $round->instant) : '', 10, 20));
    $form->Add(new auFormSelect('type', 'type', 'type of round played', false, getRoundTypes($db), $round->roundtype));
    if($course->teelist)
      $form->Add(new auFormSelect('tees', 'tees', 'which set of tees round was played from', false, array('null' => '(unknown)') + auFormSelect::ArrayIndex(explode(',', $course->teelist)), $round->tees));
    $form->Add(new auFormFieldSet('scores', 'scoreset', '', getScoreTable($page, $user, $course, $round)));
    $disclist = getDiscList($db, $user, $round->uid);
    $form->AddSelect('bestdisc', 'best disc', 'most valuable disc (this round)', $disclist, $round->bestdisc);
    $form->AddSelect('worstdisc', 'worst disc', 'least valuable disc (this round)', $disclist, $round->worstdisc);
    $form->AddField('comments', 'comments', 'your comments on this round', false, auText::HTML2BB($round->comments), _AU_FORM_FIELD_BBCODE);
    $form->AddButtons('save', $round ? 'save changes to this round' : 'add this round');
    return $form;
  }

  /**
   * Look up courses from the database and build a list in the format needed by
   * auFormSelect.  A blank entry is also included.
   * @param auDB $db Database connection object
   * @return array List of courses ready to be used as auFormSelect options
   */
  function getCourseList(&$db) {
    $courselist[''] = '';
    if($courses = $db->Get('select id, name, location from dgcourses order by name', 'error looking up courses', 'no courses found'))
      while($course = $courses->NextRecord())
        $courselist[$course->id] = html_entity_decode($course->name, ENT_COMPAT, _CHARSET) . ' (' . $course->location . ')';
    return $courselist;
  }

  /**
   * Look up round types from the database and build a list in the format needed
   * by auFormSelect.  An unknown entry is also included.
   * @param auDB $db Database connection object
   * @return array List of round types ready to be used as auFormSelect options
   */
  function getRoundTypes(&$db) {
    $values['null'] = '(unknown)';
    $types = 'show columns from dgrounds like \'roundtype\'';
    if($types = $db->Get($types, 'error looking up round types', '')) {
      $types = $types->NextRecord();
      $types = auFormSelect::ArrayIndex(explode('\',\'', substr($types->Type, 6, -2)));
      return $values + $types;
    }
    return $values;
  }

  /**
   * Generate the score table for a round form.
   * @param auPageTrack7 Page layout object (for mobile layout flag)
   * @param auUserTrack7 $user Logged-in user
   * @param object $course Course to generate score table for, or false for generic score table
   * @param object $round Round to show scores from, or false if adding scores
   * @return string HTML score table for a round
   */
  function getScoreTable(&$page, $user, $course, $round) {
    $tbl = '';
    if($course)
    $course->parlist = explode('|', $course->parlist);
    if($round)
      $round->scorelist = explode('|', $round->scorelist);
    for($start = 0; $start < ($course ? $course->holes : 27); $start += 9)
      $tbl .= get9ScoreTable($start, $start + 9, $page->Mobile, $user, $course, $round);
    return $tbl;
  }

  /**
   * Generate a 9-hole score table for a round form.
   * @param integer $shole First hole to show (0-based)
   * @param integer $ehole Last hole to show (1-based)
   * @param bool $mobile Page layout object (for mobile layout flag)
   * @param auUserTrack7 $user Logged-in user
   * @param object $course Course to generate score table for, or false for generic score table
   * @param object $round Round to show scores from, or false if adding scores
   * @return string HTML 9-hole score table for a round
   */
  function get9ScoreTable($shole, $ehole, $mobile, $user, $course, $round) {
    $tbl = '<table class="scores' . $ehole . '" cellspacing="0"><thead><tr><td></td>';
    for($h = $shole + 1; $h <= $ehole; $h++)
      $tbl .= '<th>' . $h . '</th>';
    if($multi = !$course || $course->holes > 9)
      $tbl .= '<th>nine</th>';
    if($last = $ehole >= ($course ? $course->holes : 27))
      $tbl .= '<th>total</th>';
    $tbl .= '</tr></thead><tbody><tr class="par"><th class="player">par</th>';
    $sum = 0;
    for($h = $shole; $h < $ehole; $h++) {
      $tbl .= '<th>' . $course->parlist[$h] . '</th>';
      $sum += $course->parlist[$h];
    }
    if($multi)
      $tbl .= '<th>' . $sum . '</th>';
    if($last)
      $tbl .= '<th>' . array_sum($course->parlist) . '</th>';
    $tbl .= '</tr><tr><th class="player">';
    if($round)
      $tbl .= $round->login;
    elseif($shole == 0)
      $tbl .= '<input class="string" type="text" id="fldplayer0" name="player[0]" size="12" maxlength="32" value="' . ($round ? $round->login : $user->Name) . '" />';
    $tbl .= '</th>';
    $sum = 0;
    for($h = $shole; $h < $ehole; $h++) {
      if($mobile  && !$round)
        $tbl .= '<td><select ' . ($h == 0 ? 'id="score0_0" ' : '') . 'name="score[0][' . $h . ']">' . getScoreOptions(3) . '</select></td>';
      else
        $tbl .= '<td><input ' . ($h == 0 ? 'id="score0_0" ' : '') . 'class="integer" type="text" name="score[0][' . $h . ']" size="1" maxlength="1" value="' . ($round ? $round->scorelist[$h] : 3) . '" /></td>';
      $sum += $round->scorelist[$h];
    }
    if($multi)
      $tbl .= '<th>' . ($sum ? $sum : '') . '</th>';
    if($last)
      $tbl .= '<th>' . ($round ? array_sum($round->scorelist) : '') . '</th>';
    $tbl .= '</tr></tbody></table>';
    return $tbl;
  }

  /**
   * Generate option elements for score select elements.
   * @param integer $default Score that should be selected by default
   */
  function getScoreOptions($default) {
    $opts = '';
    for($s = 1; $s <= 9; $s++)
      $opts .= '<option' . ($s == $default ? ' selected="selected"' : '') . '>' . $s . '</option>';
    return $opts;
  }

  // ----------------------------------------------------------[ getDiscList ]--
  function getDiscList(&$db, $user, $uid = false) {
    if(!$uid)
      $uid = $user->ID;
    $values['null'] = '(none)';
    $discs = 'select c.id, d.name, c.mass, c.color, c.status from dgcaddy as c left join dgdiscs as d on c.discid=d.id where c.uid=\'' . $uid . '\' order by +c.status, d.name';
    if($discs = $db->Get($discs, 'error reading list of discs'))
      while($disc = $discs->NextRecord())
        $values[$disc->id] = $disc->name . ' (' . $disc->color . ' ' . $disc->mass . 'g, ' . $disc->status . ')';
    return $values;
  }

  /**
   * Check player and score fields since they're not part of the form object.
   * @param integer $row row of scores to check (0-based)
   * @param auDB $db database connection object
   * @param integer $holes number of holes this course has
   * @param boolean $partner whether to check for a partner
   * @return array data found in the row:  score (array), player (string), partner (string only present if $partner), okscores (bool)
   */
  function checkScoreRow($row, &$db, $holes, $partner) {
    // if partner round with partner but no player, make partner the player and delete the partner
    if($partner && !$_POST['player'][$row] && $_POST['partner'][$row]) {
      $_POST['player'][$row] = $_POST['partner'][$row];
      $_POST['partner'][$row] = '';
    }
    // verify player and partner are actual users
    if($_POST['player'][$row])
      $ret['player'] = checkPlayer($db, $_POST['player'][$row]);
    if($partner && $_POST['partner'][$row])
      $ret['partner'] = checkPlayer($db, $_POST['partner'][$row]);
    return array_merge($ret, checkScoreFields($row, $holes));
  }

  /**
   * Check score fields since they're not part of the form object.
   * @param integer $row row of scores to check (0-based)
   * @param integer $holes number of holes this course has
   * @return array data found in the row:  score (array), okscores (bool)
   */
  function checkScoreFields($row, $holes) {
    $ret['okscores'] = true;  // assume scores are good
    for($h = 0; $h < $holes; $h++) {
      $ret['score'][$h] = +$_POST['score'][$row][$h];
      if(!is_numeric($ret['score'][$h]) || strlen($ret['score'][$h]) != 1 || $ret['score'][$h] < 1 || $ret['score'][$h] > 9)
        $ret['okscores'] = false;
    }
    return $ret;
  }

  /**
   * Check if a player is a user and return the user ID if so.
   * @param auDB $db database connection object
   * @param string $login username to check
   * @return string user ID, or false if $login is not a user
   */
  function checkPlayer(&$db, $login) {
    $chk = 'select uid from users where login=\'' . addslashes($login) . '\'';
    return $db->GetValue($chk, 'error checking whether ' . htmlspecialchars($login, ENT_COMPAT, _CHARSET) . ' is a valid user', 'could not find a user named ' . htmlspecialchars($login, ENT_COMPAT, _CHARSET), true);
  }

  /**
   * Save new scores.
   * @param string $courseid ID of the course these scores are from
   * @param array $scores the score for each hole
   * @param string $puid ID of the player
   * @param string $pname name of the player
   * @param auBD $db database connection object
   * @param auUserTrack7 $user user information object for the user submitting the scores
   * @param auPage $page page layout object for message output
   */
  function saveScores($courseid, $scores, $puid, $pname, &$db, &$user, &$page) {
    $owner = $puid == $user->ID;
    $ins = 'insert into dgrounds (uid, courseid, roundtype, tees, instant, scorelist, score, ' . ($owner ? 'bestdisc, worstdisc, comments' : 'entryuid') . ') values (\'' . addslashes($puid) . '\', \'' . $courseid . '\', ' . ($_POST['type'] == 'null' ? 'null' : '\'' . addslashes($_POST['type']) . '\'') . ', ' . (!$_POST['tees'] || $_POST['tees'] == 'null' ? 'null' : '\'' . addslashes($_POST['tees']) . '\'') . ', \'' . $user->tzstrtotime($_POST['date']) . '\', \'' . implode('|', $scores) . '\', \'' . array_sum($scores) . '\'' . ($owner ? ', ' . $_POST['bestdisc'] . ', ' . $_POST['worstdisc'] . ', \'' . addslashes(auText::BB2HTML($_POST['comments'])) . '\'' : ', \'' . $user->ID . '\'') . ')';
    if(false !== $roundid = $db->Put($ins, 'error saving scores for ' . $pname)) {
      require_once 'util.php';
      countRounds($db, $courseid);
      if(calcAvgScores($db, $courseid, $_POST['roundtype'] == 'null' ? null : $_POST['roundtype'], $_POST['tees'] && $_POST['tees'] != 'null' ? $_POST['tees'] : null)) {
        calcPlayerStats($db, $puid);
        $page->info('saved <a href="?id=' . $roundid . '">' . $pname . '’s round</a>');
      }
    }
  }
?>
