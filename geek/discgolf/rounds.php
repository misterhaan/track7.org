<?
  $getvars = array('player', 'id');
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if($user->Valid && $_GET['id'] == 'new' && is_numeric($_GET['course'])) {
    $course = 'select id, holes, name, teelist from dgcourses where id=\'' . addslashes($_GET['course']) . '\'';
    if($course = $db->GetRecord($course, 'error looking up course', 'course not found', true)) {
      $rf = getRoundForm($db, $user, $course);
      $okscores = true;
      if($rf->CheckInput(true) && $okscores = checkScoreFields($course->holes)) {
        if($_POST['player']) {
          $playerid = 'select uid from users where login=\'' . addslashes($_POST['player']) . '\'';
          $playerid = $db->GetValue($playerid, 'error looking up player&rsquo;s user id', 'player ' . htmlspecialchars($_POST['player']) . ' not found', true);
        } else
          $playerid = $user->ID;
        if($playerid) {
          $ins = 'insert into dgrounds (uid, courseid, roundtype, tees, entryuid, instant, scorelist, score' . ($playerid == $user->ID ? ', bestdisc, worstdisc, comments' : '') . ') values (\'' . $playerid . '\', \'' . addslashes($_GET['course']) . '\', ' . ($_POST['type'] == 'null' ? 'null' : '\'' . addslashes($_POST['type']) . '\'') . ', ' . ($_POST['tees'] || $_POST['tees'] == 'null' ? 'null' : '\'' . addslashes($_POST['tees']) . '\'') . ', ' . ($playerid == $user->ID ? 'null' : '\'' . $user->ID . '\'') . ', \'' . $user->tzstrtotime($_POST['date']) . '\', \'' . implode('|', $_POST['score']) . '\', \'' . array_sum($_POST['score']) . '\'' . ($playerid == $user->ID ? ', ' . $_POST['bestdisc'] . ', ' . $_POST['worstdisc'] . ', \'' . auText::BB2HTML($_POST['comments']) . '\'' : '') . ')';
          if(false !== $roundid = $db->Put($ins, 'error saving round')) {
            require_once 'util.php';
            if(calcAvgScores($db, $_GET['course'], $_POST['roundtype'] == 'null' ? null : $_POST['roundtype'], $_POST['tees'] && $_POST['tees'] != 'null' ? $_POST['tees'] : null)) {
              calcPlayerStats($db, $playerid);
              header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?id=' . $roundid);
              die;
            }
          }
        }
      }
      $page->ResetFlag(_FLAG_PAGES_COMMENTS);
      $page->Start('new round - ' . $course->name . ' - disc golf', 'add a round', $course->name);
?>
      <p>
        use the form below to enter your scores or your friends&rsquo; scores.&nbsp;
        for your own scores, leave the player field blank.&nbsp; for friends&rsquo;
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
    $round = 'select u.login, r.uid, r.courseid, c.name, c.teelist, r.roundtype, r.tees, r.instant, c.holes, c.parlist, c.par, r.scorelist, r.score, r.bestdisc, r.worstdisc, r.comments, r.entryuid, eu.login as entryuser from dgrounds as r left join dgcourses as c on c.id=r.courseid left join users as u on u.uid=r.uid left join users as eu on eu.uid=r.entryuid where r.id=\'' . +$_GET['id'] . '\'';
    if($round = $db->GetRecord($round, 'error looking up round', 'round not found', true)) {
      require_once 'auText.php';
      $page->Start(date('Y-m-d', $round->instant) . ' - ' . $round->name, $round->login . '&rsquo;s ' . strtolower(auText::SmartDate($round->instant, $user)) . ' round', $round->name);
      if($round->uid == $user->ID) {
        if(isset($_GET['edit'])) {
          $rf = getRoundForm($db, $user, $round, $round);
          $okscores = true;
          $edited = false;
          if($rf->CheckInput(true) && $okscores = checkScoreFields($round->holes)) {
            $update = 'update dgrounds set instant=\'' . $user->tzstrtotime($_POST['date']) . '\', roundtype=' . ($_POST['type'] == 'null' ? 'null' : '\'' . addslashes($_POST['type']) . '\'') . ', tees=' . ($_POST['tees'] && $_POST['tees'] != 'null' ? '\'' . addslashes($_POST['tees']) . '\'' : 'null') . ', scorelist=\'' . implode('|', $_POST['score']) . '\', score=\'' . array_sum($_POST['score']) . '\', bestdisc=' . $_POST['bestdisc'] . ', worstdisc=' . $_POST['worstdisc'] . ', comments=\'' . addslashes(auText::BB2HTML($_POST['comments'])) . '\' where id=\'' . addslashes($_GET['id']) . '\'';
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
                  $round->scorelist = implode('|', $_POST['score']);
                  $round->score = array_sum($_POST['score']);
                  $round->bestdisc = $_POST['bestdisc'] == 'null' ? null : $_POST['bestdisc'];
                  $round->worstdisc = $_POST['worstdisc'] == 'null' ? null : $_POST['worstdisc'];
                  $round->comments = auText::BB2HTML($_POST['comments']);
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
        $page->Heading($round->login . '&rsquo;s comments');
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
      $round->parlist = explode('|', $round->parlist);
      showRoundHoles($round->holes, $round->scorelist, $round->parlist, $avg->avglist);
?>
      <ul>
        <li><a href="?player=<?=$round->login; ?>"><?=$round->login; ?>&rsquo;s rounds</a></li>
        <li><a href="players.php?p=<?=$round->login; ?>"><?=$round->login; ?>&rsquo;s player profile</a></li>
        <li><a href="courses.php?id=<?=$round->courseid; ?>"><?=$round->name; ?> information</a></li>
      </ul>
<?
      $page->End();
      die;
    }
  }

  $page->ResetFlag(_FLAG_PAGES_COMMENTS);
  if(strlen($_GET['player'])) {
    $page->Start(htmlentities($_GET['player'], ENT_COMPAT, _CHARSET) . '&rsquo;s rounds - disc golf', htmlentities($_GET['player'], ENT_COMPAT, _CHARSET) . '&rsquo;s rounds');
    $rounds = ' and u.login=\'' . addslashes($_GET['player']) . '\'';
  } else {
    $page->Start('rounds - disc golf', 'rounds');
    $rounds = '';
  }
  $rounds = 'select r.id, u.login, r.courseid, c.name, r.roundtype, r.tees, r.instant, r.score, r.comments from dgrounds as r left join dgcourses as c on c.id=r.courseid left join users as u on u.uid=r.uid where (r.entryuid is null or r.uid=\'' . $user->ID . '\')' . $rounds . ' order by instant desc';
  if($rounds = $db->GetSplit($rounds, 30, 0, '', '', 'error looking up rounds', 'no rounds entered')) {
    require_once 'auText.php';
?>
      <table class="text" cellspacing="0">
        <thead><tr><th>date</th><th>course</th><?=strlen($_GET['player']) ? '' : '<th>player</th>'; ?><th>type</th><th>tees</th><th>score</th><th>comments</th></tr></thead>
        <tbody>
<?
    while($round = $rounds->NextRecord()) {
      $round->comments = trim(html_entity_decode(strip_tags($round->comments), ENT_COMPAT, _CHARSET));
      if(strlen($round->comments) > 17)
        $round->comments = substr($round->comments, 0, 15) . '...';
?>
          <tr><td><a href="?id=<?=$round->id; ?>" title="more information on this round"><?=strtolower(auText::SmartDate($round->instant, $user)); ?></a></td><td><a href="courses.php?id=<?=$round->courseid; ?>" title="more information on this course"><?=$round->name; ?></a></td><?=strlen($_GET['player']) ? '' : '<td><a href="players.php?p=' . $round->login . '" title="more information on this player">' . $round->login . '</a></td>'; ?><td><?=$round->roundtype; ?></td><td><?=$round->tees; ?></td><td><?=$round->score; ?></td><td class="minor"><?=$round->comments; ?></td></tr>
<?
    }
?>
        </tbody>
      </table>
<?
    $page->SplitLinks();
    if(strlen($_GET['player'])) {
?>
      <ul><li><a href="players.php?p=<?=htmlentities($_GET['player'], ENT_COMPAT, _CHARSET); ?>"><?=htmlentities($_GET['player'], ENT_COMPAT, _CHARSET); ?>&rsquo;s player profile</a></li></ul>
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
    for($start = 0; $start < $holes; $start += 9)
      showNineHoles($start, $score, $par, $avg);
?>
      </div>

<?
  }

  // --------------------------------------------------------[ showNineHoles ]--
  function showNineHoles($start, $score, $par, $avg) {
?>
        <table class="data" cellspacing="0">
<?
    $end = $start + 9;
    echo '          <thead><tr><td></td>';
    for($i = $start + 1; $i <= $end; $i++)
      echo '<th>' . $i . '</th>';
    echo "</tr></thead>\n          <tbody>\n";
    echo '            <tr><th>score</th>';
    for($i = $start; $i < $end; $i++)
      echo '<td>' . $score[$i] . '</td>';
    echo "</tr>\n            <tr><th>par</th>";
    for($i = $start; $i < $end; $i++)
      echo '<td>' . $par[$i] . '</td>';
    echo "</tr>\n";
    if(is_array($avg)) {
      echo '            <tr><th>avgerage</th>';
      for($i = $start; $i < $end; $i++)
        echo '<td>' . number_format($avg[$i], 1) . '</td>';
      echo "</tr>\n";
    }
?>
          </tbody>
        </table>
<?
  }

  // ---------------------------------------------------------[ getRoundForm ]--
  function getRoundForm(&$db, $user, $course, $round = false) {
    require_once 'auForm.php';
    require_once 'auText.php';
    if($round)
      $form = new auForm('editround', '?id=' . $_GET['id'] . '&edit');
    else
      $form = new auForm('newround', '?id=new&course=' . $course->id);
    $form->AddText('course', $course->name);
    if($round)
      $form->AddText('player', $round->login);
    else
      $form->AddField('player', 'player', 'leave blank if entering your own scores, or enter the name of the player whose scores you are entering', false, '', _AU_FORM_FIELD_NORMAL, 20, 32);
    $form->AddField('date', 'date', 'when the round was played', true, $user->tzdate('Y-m-d', $round ? $round->instant : time()), _AU_FORM_FIELD_NORMAL, 10, 20);
    $form->AddSelect('type', 'type', 'type of round played', getRoundTypes($db), $round->roundtype);
    if($course->teelist)
      $form->AddSelect('tees', 'tees', 'which set of tees round was played from', array('null' => '(unknown)') + auFormSelect::ArrayIndex(explode(',', $course->teelist)), $round->tees);
    $form->AddHTML('scores', getScoreFields($course->holes, $round));
    $disclist = getDiscList($db, $user, $round->uid);
    $form->AddSelect('bestdisc', 'best disc', 'most valuable disc (this round)', $disclist, $round->bestdisc);
    $form->AddSelect('worstdisc', 'worst disc', 'least valuable disc (this round)', $disclist, $round->worstdisc);
    $form->AddField('comments', 'comments', 'your comments on this round', false, auText::HTML2BB($round->comments), _AU_FORM_FIELD_BBCODE);
    $form->AddButtons('save', $round ? 'save changes to this round' : 'add this round');
    return $form;
  }

  // --------------------------------------------------------[ getRoundTypes ]--
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

  // -------------------------------------------------------[ getScoreFields ]--
  function getScoreFields($holes, $round) {
    $ret = "\n" . '            <table id="parfields" cellspacing="0">' . "\n";
    if($round)
      $scorelist = explode('|', $round->scorelist);
    for($start = 0; $start < $holes; $start += 9) {
      $end = $start + 9;
      $ret .= '              <tr>';
      for($i = $start + 1; $i <= $end; $i++)
        $ret .= '<th>' . $i . '</th>';
      $ret .= "</tr>\n";
      $ret .= '              <tr>';
      for($i = $start + 1; $i <= $end; $i++)
        $ret .= '<td><input type="text" name="score[' . $i . ']" value="' . (is_numeric($_POST['score'][$i]) ? +$_POST['score'][$i] : ($scorelist ? $scorelist[$i - 1] : 3)) .'" size="1" maxlength="1" /></td>';
      $ret .= "</tr>\n";
    }
    return $ret . "            </table>\n          ";
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

  // -----------------------------------------------------[ checkScoreFields ]--
  function checkScoreFields($holes) {
    for($i = 1; $i <= $holes; $i++)
      if(!is_numeric($_POST['score'][$i]) || strlen($_POST['score'][$i]) != 1 || $_POST['score'][$i] < 1 || $_POST['score'][$i] > 9)
        return false;
    return true;
  }
?>
