<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auText.php';
  require_once 'auForm.php';

  if(is_numeric($_GET['id']) && strpos($_GET['id'], '.') === false) {
    $course = 'select name, location, parlist, par, comments from dgcourses where id=' . $_GET['id'];
    if($course = $db->GetRecord($course, 'error reading course information', 'could not find course id ' . $_GET['id'] . ' -- please find your course below and click on its name.&nbsp; if it isn\'t in the list, you can request that it be added.')) {
      $course->parlist = explode('|', $course->parlist);
      $page->Start($course->name . ' - disc golf', $course->name, $course->location);
      if(is_numeric($_GET['round']) && strpos($_GET['round'], '.') === false) {
?>
      <p>
        <?=$course->comments; ?>
      </p>

<?
        $round = 'select u.login, r.uid, r.instant, r.scorelist, r.score, r.bestdisc, r.worstdisc, r.comments from users as u, dgrounds as r where u.uid=r.uid and r.courseid=' . $_GET['id'] . ' and r.id=' . $_GET['round'];
        if($round = $db->GetRecord($round, 'error reading round information', 'could not find a round at ' . $course->name . ' with id ' . $_GET['round'], true)) {
          if(isset($_GET['del']) && $user->Valid && ($user->Name == $round->login || $user->GodMode)) {
            $del = 'delete from dgrounds where id=' . $_GET['round'];
            if(false !== $db->Change($del, 'error deleting round')) {
              $page->Info('round deleted successfully');
              $db->Change('update userstats set rounds=rounds-1 where uid=' . $round->uid, 'error updating number of rounds for ' . $round->login);
              $db->Change('update dgcourses set rounds=rounds-1 where id=' . $_GET['id'], 'error updating number of rounds for ' . $course->name);
?>
      <p><a href="?id=<?=$_GET['id']; ?>">back to the <?=$course->name; ?> page</a></p>
<?
              $page->End();
              die;
            }
          }
          $round->scorelist = explode('|', $round->scorelist);
          if(isset($_GET['edit']) && $user->Valid && ($user->Name == $round->login || $user->GodMode)) {
            if(isset($_POST['submit'])) {
              unset($_POST['submit'], $_GET['edit']);  //don't add another round instead of editing.  also don't show the edit form again
              for($hole = 1; $hole <= 18 && !isset($error); $hole++)
                if(!is_numeric($_POST['score'][$hole]) || strpos($_POST['score'][$hole], '.') !== false || $_POST['score'][$hole] < 1 || $_POST['score'][$hole] > 9)
                  $page->Error($error = 'score for hole ' . $hole . ' must be between 1 and 9');
              if(!isset($error)) {
                if(!is_numeric($_POST['mvd']) && $_POST['mvd'] !== 'null')
                  $page->Error = 'most valueable disc must be a number or null -- please use the official track7 form';
                elseif(!is_numeric($_POST['lvd']) && $_POST['lvd'] !== 'null')
                  $page->Error = 'least valueable disc must be a number or null -- please use the official track7 form';
                else {
                  $update = 'update dgrounds set instant=' . $user->tzstrtotime($_POST['date']) . ', scorelist=\'' . implode('|', $_POST['score']) . '\', score=' . array_sum($_POST['score']) . ', bestdisc=' . $_POST['mvd'] . ', worstdisc=' . $_POST['lvd'] . ', comments=\'' . addslashes(auText::BB2HTML($_POST['comments'])) . '\' where id=' . $_GET['round'];
                  if(false !== $db->Change($update, 'error updating round')) {
                    $page->Info('round successfully updated!');
                    // we updated the round, so fix the round object
                    $round->scorelist = $_POST['score'];
                    $round->score = array_sum($_POST['score']);
                    $round->bestdisc = $_POST['mvd'];
                    $round->worstdisc = $_POST['lvd'];
                    $round->comments = auText::BB2HTML($_POST['comments']);
                  }
                }
              }
            }
?>
      <h2>your round - <?=strtolower($user->tzdate('M d, Y', $round->instant)); ?></h2>
<?
            $newround = new auForm('newround', '?id=' . $_GET['id'] . '&amp;round=' . $_GET['round'] . '&amp;edit');
            $newround->AddField('date', 'date', 'the date this round was played in YYYY-MM-DD format', true, $user->tzdate('Y-m-d', $round->instant), _AU_FORM_FIELD_NORMAL, 10, 20);
            $newround->AddField('comments', 'comments', 'your comments on this round (may use t7code)', false, auText::HTML2BB($round->comments), _AU_FORM_FIELD_BBCODE);
            for($hole = 1; $hole <=18; $hole++)
              $newround->AddField('score[' . $hole . ']', 'hole ' . $hole, 'your score for hole ' . $hole . ' this round (1-9)', true, $round->scorelist[$hole - 1], _AU_FORM_FIELD_INTEGER, 1, 1);
            $values['null'] = '(none)';
            $discs = 'select dgcaddy.id, dgdiscs.name, dgcaddy.mass, dgcaddy.color from dgcaddy, dgdiscs where dgcaddy.discid=dgdiscs.id and dgcaddy.status=\'bag\' and dgcaddy.uid=' . $user->ID;
            if($discs = $db->Get($discs, 'error reading list of discs'))
              while($disc = $discs->NextRecord())
                $values[$disc->id] = $disc->name . ' (' . $disc->color . ' ' . $disc->mass . 'g)';
            $newround->AddSelect('mvd', 'best disc', 'most valuable disc (this round)', $values, $round->bestdisc);
            $newround->AddSelect('lvd', 'worst disc', 'least valuable disc (this round)', $values, $round->worstdisc);
            $newround->AddButtons('update', 'update this round');
            $newround->WriteHTML(true);
          } else {
            if($round->bestdisc !== null) {
              $round->bestdisc = 'select dgdiscs.name, dgcaddy.id, dgcaddy.discid, dgcaddy.mass, dgcaddy.color from dgdiscs, dgcaddy where dgdiscs.id=dgcaddy.discid and dgcaddy.id=' . $round->bestdisc;
              if($round->bestdisc = $db->GetRecord($round->bestdisc, '', ''))
                $round->bestdisc = '<a href="discs.php?id=' . $round->bestdisc->discid . '&amp;caddy=' . $round->bestdisc->id . '" title="view more information on this disc">' . $round->bestdisc->name . ' (' . $round->bestdisc->color . ' ' . $round->bestdisc->mass . 'g)</a>';
              else
                $round->bestdisc = '(error)';
            } else
              $round->bestdisc = '(none)';
  
            if($round->worstdisc !== null) {
              $round->worstdisc = 'select dgdiscs.name, dgcaddy.id, dgcaddy.discid, dgcaddy.mass, dgcaddy.color from dgdiscs, dgcaddy where dgdiscs.id=dgcaddy.discid and dgcaddy.id=' . $round->worstdisc;
              if($round->worstdisc = $db->GetRecord($round->worstdisc, '', ''))
                $round->worstdisc = '<a href="discs.php?id=' . $round->worstdisc->discid . '&amp;caddy=' . $round->worstdisc->id . '" title="view more information on this disc">' . $round->worstdisc->name . ' (' . $round->worstdisc->color . ' ' . $round->worstdisc->mass . 'g)</a>';
              else
                $round->worstdisc = '(error)';
            } else
              $round->worstdisc = '(none)';
  
?>
      <h2><?=$round->login; ?>'s round - <?=strtolower($user->tzdate('M d, Y', $round->instant)); ?></h2>
      <p>
        <?=$round->comments; ?>
      </p>
      <table class="text" id="coursepar" cellspacing="0">
<?
            echo '        <thead><tr><th>hole</th>';
            for($hole = 1; $hole <= 18; $hole++)
              echo '<th>' . $hole . '</th>';
            echo '</tr></thead>' . "\n" . '        <tbody>' . "\n" . '          <tr><td>par</td>';
            for($hole = 0; $hole < 18; $hole++)
              echo '<td>' . $course->parlist[$hole] . '</td>';
            echo '</tr>' . "\n" . '          <tr><td>score</td>';
            for($hole = 0; $hole < 18; $hole++)
              echo '<td>' . $round->scorelist[$hole] . '</td>';
            echo '</tr>' . "\n";
?>
        </tbody>
      </table>
      <table class="columns" cellspacing="0">
        <tr><th>best disc</th><td><?=$round->bestdisc; ?></td></tr>
        <tr><th>worst disc</th><td><?=$round->worstdisc; ?></td></tr>
      </table>

<?
            if($user->Valid && ($user->Name == $round->login || $user->GodMode)) {
?>
      <p><a href="<?=$_SERVER['PHP_SELF']; ?>?id=<?=$_GET['id']; ?>&amp;round=<?=$_GET['round']; ?>&amp;edit">edit this round</a> | <a href="<?=$_SERVER['PHP_SELF']; ?>?id=<?=$_GET['id']; ?>&amp;round=<?=$_GET['round']; ?>&amp;del">delete this round</a></p>

<?
            }
          }
        }
      }
      if(isset($_POST['submit']) && $user->Valid) {
        for($hole = 1; $hole <= 18 && !isset($error); $hole++)
          if(!is_numeric($_POST['score'][$hole]) || strpos($_POST['score'][$hole], '.') !== false || $_POST['score'][$hole] < 1 || $_POST['score'][$hole] > 9)
            $page->Error($error = 'score for hole ' . $hole . ' must be between 1 and 9');
        if(!isset($error)) {
          if(!is_numeric($_POST['mvd']) && $_POST['mvd'] !== 'null')
            $page->Error('most valueable disc must be a number or null -- please use the official track7 form');
          elseif(!is_numeric($_POST['lvd']) && $_POST['lvd'] !== 'null')
            $page->Error('least valueable disc must be a number or null -- please use the official track7 form');
          else {
            $ins = 'insert into dgrounds (uid, courseid, instant, scorelist, score, bestdisc, worstdisc, comments) values (' . $user->ID . ', ' . $_GET['id'] . ', ' . $user->tzstrtotime($_POST['date']) . ', \'' . implode('|', $_POST['score']) . '\', ' . array_sum($_POST['score']) . ', ' . $_POST['mvd'] . ', ' . $_POST['lvd'] . ', \'' . addslashes(auText::BB2HTML($_POST['comments'])) . '\')';
            if($false !== $db->Put($ins, 'error saving round')) {
              $update = 'update dgcourses set rounds=rounds+1 where id=' . $_GET['id'];
              $db->Change($update);
              $update = 'update userstats set rounds=rounds+1 where uid=' . $user->ID;
              $user->UpdateRank();
              $db->Change($update);
              calcplayerstats();
              $page->Info = 'round added successfully';
            }
          }
        }
      }
      if(!isset($round)) {
?>
      <p>
        <?=$course->comments; ?>
      </p>

      <table class="text" id="coursepar" cellspacing="0">
<?
        echo '        <thead><tr><th>hole</th>';
        for($hole = 1; $hole <= 18; $hole++)
          echo '<th>' . $hole . '</th>';
        echo '</tr></thead>' . "\n" . '        <tbody><tr><td>par</td>';
        for($hole = 1; $hole <= 18; $hole++)
          echo '<td>' . $course->parlist[$hole - 1] . '</td>';
        echo '</tr></tbody>' . "\n";
?>
      </table>

      <p><a href="scoresheet.rtf?course=<?=$_GET['id']; ?>" title="rtf score sheet for you to print, take with you, and fill in">printable scoresheet</a></p>

<?
      }
      $rounds = 'select users.login as player, dgrounds.instant, dgrounds.score, dgrounds.comments, dgrounds.id from users, dgrounds where dgrounds.uid=users.uid and dgrounds.courseid=' . $_GET['id'] . ' order by score, instant desc';
      if($rounds = $db->Get($rounds, 'error reading rounds for this course', 'no rounds have been entered for this course yet')) {
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>date</th><th>player</th><th>raw</th><th>course</th><th>par&nbsp;3's</th><th>comments</th></tr></thead>
        <tbody>
<?
        while($round = $rounds->NextRecord()) {
          $round->comments = str_replace(array('<br />', "\n", '&nbsp;'), ' ', $round->comments);
          if(strlen($round->comments) < 1)
            $round->comments = '<em>[none]</em>';
?>
          <tr><td><?=$user->tzdate('Y-m-d', $round->instant); ?></td><td><a href="players.php?p=<?=$round->player; ?>"><?=$round->player; ?></a></td><td class="number"><?=$round->score; ?></td><td class="number"><?=($round->score == $course->par ? 'even' : ($round->score > $course->par ? '+' : '') . ($round->score - $course->par)); ?></td><td class="number"><?=($round->score == 54 ? 'even' : ($round->score > 54 ? '+' : '') . ($round->score - 54)); ?></td><td><a href="<?=$_SERVER['PHP_SELF']; ?>?id=<?=$_GET['id']; ?>&amp;round=<?=$round->id; ?>" title="view comments on this round"><?=(strlen($round->comments) > 17 ? substr($round->comments, 0, 15) . '...' : $round->comments); ?></a></td></tr>
<?
        }
?>
        </tbody>
      </table>

<?
      }
      if($user->Valid) {
        if(isset($_GET['round'])) {
?>
      <p><a href="?id=<?=$_GET['id']; ?>">back to the <?=$course->name; ?> page</a></p>

<?
        } else {
          $page->Heading('add a round');
          $newround = new auForm('newround', '?id=' . $_GET['id']);
/*?>
        <p class="instructor">
          use this form to enter a round you played at <?=$course->name; ?>.<br />
          date and scores are required.<br />
          date formats other than YYYY-MM-DD may have unexpected results, so it
          is best to enter the date in that format.&nbsp; today's date will show
          by default.<br />
          scores must be between 1 and 9.<br />
          you should see a list of your discs for most and least valueable disc
          -- if not then you have not entered any discs.<br />
          comments are optional and may be used to save any information about
          the round you want to remember.&nbsp; some examples are who all was
          playing or what the weather was like.
        </p>
<?*/
          $newround->AddText('player', $user->Name);
          $newround->AddField('date', 'date', 'the date this round was played in YYYY-MM-DD format', true, $user->tzdate('Y-m-d'), _AU_FORM_FIELD_NORMAL, 10, 20);
          for($hole = 1; $hole <=18; $hole++)
            $newround->AddField('score[' . $hole . ']', 'hole ' . $hole, 'your score for hole ' . $hole . ' this round (1-9)', true, 3, _AU_FORM_FIELD_INTEGER, 1, 1);
          $values['null'] = '(none)';
          $discs = 'select dgcaddy.id, dgdiscs.name, dgcaddy.mass, dgcaddy.color from dgcaddy, dgdiscs where dgcaddy.discid=dgdiscs.id and dgcaddy.status=\'bag\' and dgcaddy.uid=' . $user->ID;
          if($discs = $db->Get($discs, 'error reading list of discs'))
            while($disc = $discs->NextRecord())
              $values[$disc->id] = $disc->name . ' (' . $disc->color . ' ' . $disc->mass . 'g)';
          $newround->AddSelect('mvd', 'best disc', 'most valuable disc (this round)', $values);
          $newround->AddSelect('lvd', 'worst disc', 'least valuable disc (this round)', $values);
          $newround->AddField('comments', 'comments', 'your comments on this round (may use t7code)', false, '', _AU_FORM_FIELD_BBCODE);
          $newround->AddButtons('add', 'add this round');
          $newround->WriteHTML(true);
        }
      } else {
?>
      <p><a href="/user/login.php">log in</a> to enter a round</p>

<?
      }
    }

    $page->End();
    die;
  }
  if(isset($_POST['submit'])) {
    if(strlen($_POST['name']) < 1)
      $page->Error('please enter the name of this course--i can\'t do much without a name!');
    elseif(strlen($_POST['location'])  < 1)
      $page->Error('please enter the location of this course so that other players will have a better chance of finding it');
    else {
      for($hole = 1; $hole <= 18 && !isset($error); $hole++)
        if(!is_numeric($_POST['par'][$hole]) || strpos($_POST['par'][$hole], '.') !== false || $_POST['par'][$hole] < 3 || $_POST['par'][$hole] > 5)
          $page->Error($error = 'par for hole ' . $hole . ' must be between 3 and 5 -- please use the official track7 form');
    }
    if(!isset($error))
      if($user->GodMode) {
        $ins = 'insert into dgcourses (name, location, parlist, par, comments) values (\'' . addslashes($_POST['name']) . '\', \'' . addslashes($_POST['location']) . '\', \'' . implode($_POST['par'], '|') . '\', ' . array_sum($_POST['par']) . ', \'' . addslashes(auText::BB2HTML($_POST['comments'])) . '\')';
        if(false !== $db->Put($ins, 'error saving new course'))
          $page->Info('new course successfully added');
      } else {
        $subject = 'add course - ';
        if(isset($user))
          $subject .= $user->Name;
        else
          $subject .= 'somebody';
        $message = 'name:  ' . $_POST['name'] . "\n"
                 . 'location:  ' . $_POST['location'] . "\n"
                 . 'par:' . "\n";
        for($hole = 1; $hole <= 18; $hole++)
          $message .= '  ' . str_pad($hole, 2, ' ', STR_PAD_LEFT) . ':  ' . $_POST['par'][$hole] . "\n";
        $message .= 'comments:' . "\n" . $_POST['comments'];
        unset($_POST['name'], $_POST['location'], $_POST['par'], $_POST['comments']);
        @mail('misterhaan@' . _HOST, $subject, $message, 'From: disc golf <dgolf@' . _HOST . ">\r\nX-Mailer: PHP/" . phpversion() . "\r\n");
        $page->Info('request to add new course sent successfully.&nbsp; if approved, it will probably show up here in a few days.');
      }
  }
  $page->Start('courses - disc golf', 'disc golf courses');
?>
      <p>
        below is a listing of disc golf courses that are currently in the
        system.&nbsp; if you play a course that is not on this list, use the
        form at the bottom of this page to request that it be added.
      </p>
      <p>
        when viewing a course, there will be a &quot;printable scoresheet&quot;
        link.&nbsp; click on this link and open it with a program that
        understands rtf (such as openoffice, word, or wordpad), then print it
        from there.&nbsp; if you want to save it, you will probably want to put
        the name of the course in there somewhere in case you save more than one
        of them.
      </p>

<?
  $courses = 'select id, name, location, par, rounds from dgcourses order by rounds desc';
  if($courses = $db->GetSplit($courses, 20, '', '', '', 'error reading courses', 'there are currently no courses in the database')) {
    $page->Heading('courses');
?>
      <table class="data" id="golfcourses" cellspacing="0">
        <thead><tr><th>name</th><th>location</th><th>par</th><th>rounds</th></tr></thead>
        <tbody>
<?
    while($course = $courses->NextRecord()) {
?>
          <tr><td><a href="?id=<?=$course->id; ?>" title="view details for this course"><?=$course->name; ?></a></td><td><?=$course->location; ?></td><td class="number"><?=$course->par; ?></td><td class="number"><?=$course->rounds; ?></td></tr>
<?
    }
?>
        </tbody>
      </table>

<?
  }
?>
      <hr class="minor" />

<?
  $page->Heading('add a course');
?>
      <p>
        use this form to request that a disc golf course be added to track7.
      </p>
<?
  $newcourse = new auForm('newcourse');
/*?>
        <p class="instructor">
          use this form to request that a disc golf course be added to track7's
          database.<br />
          name and location are required.<br />
          if you don't know the course par just leave everything at 3.<br />
          comments are for describing the course to other disc golfers who may
          be interested in checking it out, so use your judgement on what might
          be helpful there.&nbsp; comments are not required but are a good idea.
        </p>
<?*/
  $newcourse->AddField('name', 'name', 'the name of this course (i.e. plamann park)', true, '', _AU_FORM_FIELD_NORMAL, 30, 64);
  $newcourse->AddField('location', 'location', 'the location of this course (i.e. appleton, wi)', true, '', _AU_FORM_FIELD_NORMAL, 30, 64);
  for($hole = 1; $hole <=18; $hole++)
    $newcourse->AddField('par[' . $hole . ']', 'hole ' . $hole, 'par for hole ' . $hole . ' (1-9)', true, 3, _AU_FORM_FIELD_INTEGER, 1, 1);
  $newcourse->AddField('comments', 'comments', 'a short description of the course', false, '', _AU_FORM_FIELD_BBCODE);
  $newcourse->AddButtons('add', 'submit a request to add this course');
  $newcourse->WriteHTML($user->Valid);
  $page->End();

  function calcplayerstats() {
    global $db;
    global $user;
    $rounds = 'select scorelist from dgrounds where uid=' . $user->ID;
    $score = array(0, 0, 0, 0, 0, 0);
    if($rounds = $db->Get($rounds, 'error looking up scores for this player')) {
      while($round = $rounds->NextRecord()) {
        $scorelist = explode('|', $round->scorelist);
        foreach($scorelist as $s) {
          $score[0]++;
          $score[$s]++;
        }
      }
      $db->Put('replace into dgplayerstats (uid, aces, birds, pars, bogies, doubles, holes) values (' . $user->ID . ', ' . $score[1] . ', ' . $score[2] . ', ' . $score[3] . ', ' . $score[4] . ', ' . $score[5] . ', ' . $score[0] . ')', 'error saving statistics');
    }
  }
?>
