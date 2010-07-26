<?
  $getvars = array('id', 'roundsort', 'roundfilter', 'tees');
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if(isset($_GET['nearest']) && is_numeric($_GET['lat']) && is_numeric($_GET['lon'])) {
    header('Content-Type: text/plain; charset=utf-8');
    $coslat = cos(deg2rad($_GET['lat']));
    $course = 'select id, 69.0934137*sqrt(pow(' . $_GET['lat'] . '-latitude,2)+pow(' . $coslat . '*(' . $_GET['lon'] . '-longitude),2)) as distance from dgcourses order by distance';
    if($course = $db->GetValue($course))
      echo $course;
    die;
  }

  if(is_numeric($_GET['id'])) {
    $course = 'select approved, name, location, latitude, longitude, holes, teelist, parlist, par, comments from dgcourses where id=' . $_GET['id'];
    if($course = $db->GetRecord($course, 'error reading course information', 'could not find course id ' . $_GET['id'] . ' -- please find your course below and click on its name.&nbsp; if it isn\'t in the list, you can request that it be added.')) {
      if(isset($_GET['roundparams'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $course->teelist;
        echo "\n";
        echo $course->parlist;
        die;
      }
      if($user->GodMode) {
        if(isset($_GET['delete'])) {
          $del = 'delete from dgcourses where id=\'' . $_GET['id'] . '\'';
          if(false !== $db->Change($del, 'error deleting course')) {
            $shift = 'update dgcourses set id=id-1 where id>' . +$_GET['id'];
            if(false !== $db->Change($shift, 'error shifting course ids down')) {
              $shift = 'alter table dgcourses auto_increment=';
              $lastid = 'select max(id)+1 from dgcourses';
              if($lastid = $db->GetValue($lastid, 'error looking up last course id', ''))
                $db->Change($shift . +$lastid, 'error updating course auto_increment');
              $shift = 'update dgrounds set courseid=courseid-1 where courseid>' . +$_GET['id'];
              if(false !==  $db->Change($shift, 'error shifting round course ids down')) {
                $shift = 'update dgcoursestats set courseid=courseid-1 where courseid>' . +$_GET['id'];
                if(false !== $db->Change($shift, 'error shifting stats course ids down')) {
                  header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
                  die;
                }
              }
            }
          }
        }
        if(isset($_GET['calcAverages'])) {
          require_once 'util.php';
          if(countRounds($db, $_GET['id']) && calcAllAvgScores($db, $_GET['id']))
            $page->Info('average scores calculated successfully');
        }
        if(isset($_GET['approve'])) {
          $update = 'update dgcourses set approved=\'yes\' where id=\'' . $_GET['id'] . '\'';
          if(false !== $db->Change($update, 'error approving course')) {
            $page->Info('course successfully marked approved');
            $course->approved = 'yes';
          }
        }
        if(isset($_GET['edit'])) {
          $editcourse = getCourseForm($course);
          $okpar = true;
          if($editcourse->CheckInput(true) && $okpar = checkParFields()) {
            $update = 'update dgcourses set name=\'' . addslashes(htmlentities($_POST['name'], ENT_COMPAT, _CHARSET)) . '\', location=\'' . addslashes(htmlentities($_POST['location'], ENT_COMPAT, _CHARSET)) . '\', latitude=' . +$_POST['latitude'] . ', longitude=' . +$_POST['longitude'] . ', holes=\'' . +$_POST['holes'] . '\', teelist=' . (isset($_POST['tees']) ? '\'am,pro\'' : 'null') . ', comments=\'' . addslashes(auText::BB2HTML($_POST['comments'])) . '\', parlist=\'' . buildParList('\', par=\'') . '\' where id=\'' . $_GET['id'] . '\'';
            if(false !== $db->Change($update, 'error updating course')) {
              header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?id=' . $_GET['id']);
              die;
            }
          }
          $page->Start('edit ' . $course->name . ' - disc golf', 'edit ' . $course->name);
          if(!$okpar)
            $page->Error('par for holes 1-' . +$_POST['holes'] . ' must be a number between 1 and 9');
          $editcourse->WriteHTML(true);
          $page->End();
          die;
        }
      }
      $page->Start($course->name . ' - disc golf', $course->name, $course->location);
?>
      <p>
        <?=$course->comments; ?>

      </p>

      <ul>
        <li><a href="scoresheet.rtf?course=<?=$_GET['id']; ?>" title="rtf score sheet to print, take with you, and fill in">print a score sheet</a></li>
<?
    if($user->Valid) {
      if($user->GodMode) {
?>
        <li><a href="?id=<?=$_GET['id']; ?>&amp;calcAverages" title="recalculate average scores for this course">calculate average scores</a></li>
        <li><a href="?id=<?=$_GET['id']; ?>&amp;edit" title="edit this course">edit</a></li>
<?
        if($course->approved != 'yes') {
?>
        <li><a href="?id=<?=$_GET['id']; ?>&amp;approve" title="mark this course approved so others can see it">approve</a></li>
        <li><a href="?id=<?=$_GET['id']; ?>&amp;delete" title="delete this course">delete</a></li>
<?
        }
      }
?>
        <li><a href="rounds.php?id=new&amp;course=<?=$_GET['id']; ?>" title="enter scores from a round played at this course">add your scores</a></li>
<?
    } else {
?>
        <li><a id="messageloginlink" href="/user/login.php">login</a> or <a href="/user/register.php">register</a> to add your scores</li>
<?
    }
?>
      </ul>
<?
      $course->parlist = explode('|', $course->parlist);
      $averages = 'select roundtype, tees, avglist, avgscore, rounds from dgcoursestats where courseid=\'' . $_GET['id'] . '\'';
      if($averages = $db->Get($averages, 'error looking up statistics for this course', '')) {
        $avgcount = $averages->NumRecords();
        while($average = $averages->NextRecord()) {
          if(!$average->roundtype)
            $average->roundtype = 'unknown';
          if(!$average->tees)
            $average->tees = 'unknown';
          $average->avglist = explode('|', $average->avglist);
          $avg[$average->roundtype][$average->tees] = $average;
        }
      }
      $page->Heading('par and averages');
?>
      <p>
        the <?=$course->name; ?> course has <?=$course->holes; ?> holes.&nbsp;
        course par is <?=$course->par; ?>, or <?=3 * $course->holes; ?> on par
        3s.<?=getAvgScoreSentence($avgcount, $avg, $course); ?>
      </p>
<?
      showCourseHoles($course->holes, $course->parlist, $avg, $course->teelist);

      $players = 'select r.uid, u.login, count(1) as rounds from dgrounds as r left join users as u on u.uid=r.uid where r.courseid=\'' . addslashes($_GET['id']) . '\' and r.entryuid is null group by r.uid order by rounds desc';
      if($players = $db->Get($players, 'error looking up players for this course', '')) {
        $page->Heading('players');
        // eventually, a chart should go here with the players' scores
?>
      <ul>
<?
        while($player = $players->NextRecord()) {
?>
        <li><a href="players.php?p=<?=$player->login; ?>"><?=$player->login; ?></a> (<?=$player->rounds; ?> round<?=$player->rounds > 1 ? 's' : ''; ?>)</li>
<?
        }
?>
      </ul>
<?
      }

      if($_GET['roundsort'] == 'best')
        $rounds = 'order by r.score';
      else
        $rounds = 'order by r.instant desc';
      if($_GET['roundfilter'])
        if($_GET['roundfilter'] == 'unknown')
          $rounds = 'and r.roundtype is null ' . $rounds;
        else
          $rounds = 'and r.roundtype=\'' . addslashes($_GET['roundfilter']) . '\' ' . $rounds;
      if($course->teelist && $_GET['tees'])
        if($_GET['tees'] == 'unknown')
          $rounds = 'and r.tees is null ' . $rounds;
        else
          $rounds = 'and r.tees=\'' . addslashes($_GET['tees']) . '\' ' . $rounds;
      $rounds = 'select r.id, u.login, r.uid, r.player, r.roundtype, r.tees, r.instant, r.score, r.comments from dgrounds as r left join users as u on u.uid=r.uid where courseid=\'' . $_GET['id'] . '\' and (r.entryuid is null or r.uid=\'' . $user->ID . '\' or r.uid=0)' . $rounds;
      if($rounds = $db->GetSplit($rounds, 10, 0, '', '', 'error looking up rounds for this course', '')) {
        if($_GET['roundsort'] == 'best') {
          $heading[] = 'best';
          $options[] = '<a href="courses.php?id=' . $_GET['id'] . (isset($_GET['roundfilter']) ? '&amp;roundfilter=' . htmlentities($_GET['roundfilter'], ENT_COMPAT, _CHARSET) : '') . ($course->teelist && $_GET['tees'] ? '&amp;tees=' . htmlentities($_GET['tees'], ENT_COMPAT, _CHARSET) : '') . '" title="show recent rounds">recent</a>';
        } else {
          $heading[] = 'recent';
          $options[] = '<a href="courses.php?id=' . $_GET['id'] . '&amp;roundsort=best' . (isset($_GET['roundfilter']) ? '&amp;roundfilter=' . htmlentities($_GET['roundfilter'], ENT_COMPAT, _CHARSET) : '') . ($course->teelist && $_GET['tees'] ? '&amp;tees=' . htmlentities($_GET['tees'], ENT_COMPAT, _CHARSET) : '') . '" title="show best rounds">best</a>';
        }
        $roundtypes = 'select roundtype from dgrounds where courseid=\'' . addslashes($_GET['id']) . '\' and entryuid is null group by roundtype';
        if($roundtypes = $db->Get($roundtypes, 'error getting list of round types for this course', ''))
          if($roundtypes->NumRecords() > 1) {
            while($type = $roundtypes->NextRecord()) {
              if($type->roundtype)
                if($type->roundtype == $_GET['roundfilter']) {
                  $heading[] = $type->roundtype;
                  $roundtypefiltered = true;
                } else
                  $options[] = '<a href="courses.php?id=' . $_GET['id'] . ($_GET['roundsort'] == 'best' ? '&amp;roundsort=best&amp;roundfilter=' : '&amp;roundfilter=') . $type->roundtype . ($course->teelist && $_GET['tees'] ? '&amp;tees=' . htmlentities($_GET['tees'], ENT_COMPAT, _CHARSET) : '') . '" title="only show ' . $type->roundtype . ' rounds">' . $type->roundtype . '</a>';
            }
            if($roundtypefiltered)
              $options[] = '<a href="courses.php?id=' . $_GET['id'] . ($_GET['roundsort'] == 'best' ? '&amp;roundsort=best' : '') . ($course->teelist && $_GET['tees'] ? '&amp;tees=' . htmlentities($_GET['tees'], ENT_COMPAT, _CHARSET) : '') . '" title="show any rounds">any</a>';
          }
        if($course->teelist) {
          $tees = 'select tees from dgrounds where courseid=\'' . addslashes($_GET['id']) . '\' and entryuid is null group by tees';
          if($tees = $db->Get($tees, 'error getting list of tees for this course', ''))
            if($tees->NumRecords() > 1) {
              while($tee = $tees->NextRecord()) {
                if($tee->tees)
                  if($tee->tees == $_GET['tees']) {
                    $heading[] = $tee->tees . '-tee';
                    $teesfiltered = true;
                  } else
                    $options[] = '<a href="courses.php?id=' . $_GET['id'] . ($_GET['roundsort'] == 'best' ? '&amp;roundsort=best' : '') . ($_GET['roundfilter'] ? '&amp;roundfilter=' . htmlentities($_GET['roundfilter'], ENT_COMPAT, _CHARSET) : '') . '&amp;tees=' . $tee->tees . '" title="only show rounds from the ' . $tee->tees . ' tees">' . $tee->tees . ' tee</a>';
              }
              if($teesfiltered)
                $options[] = '<a href="courses.php?id=' . $_GET['id'] . ($_GET['roundsort'] == 'best' ? '&amp;roundsort=best' : '') . ($_GET['roundfilter'] ? '&amp;roundfilter=' . htmlentities($_GET['roundfilter'], ENT_COMPAT, _CHARSET) : '') . '" title="show rounds from any tees">any tee</a>';
            }
        }
        $page->Heading(implode(' ', $heading) . ' rounds <ul class="elements"><li>' . implode('</li><li>', $options) . '</li></ul>');
?>
      <table class="text" cellspacing="0">
        <thead><tr><th>date</th><th>player</th><th>type</th><?=$course->teelist ? '<th>tees</th>' : '';?><th title="total throws">raw</th><th title="compared to average for this type of round on this course">avg</th><th title="compared to course par">par</th><th title="compared to all holes as par 3">3s</th></tr></thead>
        <tbody>
<?
        $par3 = 3 * $course->holes;
        while($round = $rounds->NextRecord()) {
          $round->comments = trim(html_entity_decode(strip_tags($round->comments), ENT_COMPAT, _CHARSET));
          if(strlen($round->comments) > 71)
            $round->comments = mb_substr($round->comments, 0, 69, _CHARSET) . '...';
          if(!$round->roundtype)
            $round->roundtype = '?';
          if(!$round->tees)
            $round->tees = '?';
?>
          <tr><td class="minor"><a href="rounds.php?id=<?=$round->id; ?>" title="more information on this round"><?=strtolower(auText::SmartDate($round->instant, $user)); ?></a></td><td class="minor"><?=$round->uid ? '<a href="players.php?p=' . $round->login . '" title="more information on this player">' . $round->login . '</a>' : $round->player; ?></td><td><?=$round->roundtype; ?></td><?=$course->teelist ? '<td>' . $round->tees . '</td>' : ''; ?><td class="number"><?=$round->score; ?></td><td class="number"><?=($round->score == $avg[$round->roundtype][$round->tees]->avgscore ? 'even' : ($round->score > $avg[$round->roundtype][$round->tees]->avgscore ? '+' : '') . ($round->score - $avg[$round->roundtype][$round->tees]->avgscore)); ?></td><td class="number"><?=($round->score == $course->par ? 'even' : ($round->score > $course->par ? '+' : '') . ($round->score - $course->par)); ?></td><td class="number"><?=($round->score == $par3 ? 'even' : ($round->score > $par3 ? '+' : '') . ($round->score - $par3)); ?></td></tr>
          <tr class="comments"><td class="minor" colspan="9"><?=$round->comments; ?></td></tr>
<?
        }
?>
        </tbody>
      </table>

<?
        $page->SplitLinks();
      }
      $page->End();
      die;
    }
  }
  if($user->Valid && isset($_GET['addCourse'])) {
    $courseform = getCourseForm();
    $okpar = true;
    if($courseform->CheckInput(true) && $okpar = checkParFields()) {
      $ins = 'insert into dgcourses (name, location, latitude, longitude, holes, teelist, parlist, par, comments) values (\'' . addslashes(htmlentities($_POST['name'], ENT_COMPAT, _CHARSET)) . '\', \'' . addslashes(htmlentities($_POST['location'], ENT_COMPAT, _CHARSET)) . '\', ' . +$_POST['latitude'] . ', ' . +$_POST['longitude'] . ', \'' . +$_POST['holes'] . '\', ' . (isset($_POST['tees']) ? '\'am,pro\'' : 'null') . ', \'' . buildParList() . '\', \'' . addslashes(auText::BB2HTML($_POST['comments'])) . '\')';
      if(false !== $courseid = $db->Put($ins, 'error saving course information')) {
        header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?id=' . $courseid);
        die;
      }
    }
    $page->Start('add course - disc golf', 'add a disc golf course');
    if(!$okpar)
      $page->Error('par for holes 1-' . +$_POST['holes'] . ' must be a number between 1 and 9.');
    $courseform->WriteHTML(true);
    $page->End();
    die;
  }

  // DO:  alter text to indicate course is being chosen for score entry
  $page->Start('courses - disc golf', 'disc golf courses');
  if($user->Valid && isset($_GET['addround'])) {
?>
      <p>
        choose the course where your round takes place.
      </p>
<?
  } else {
?>
      <p>
        below is a listing of disc golf courses that are currently in the
        system.&nbsp; select a course to view rounds that have been entered for
        that course or to add a round of your own.&nbsp; if you play a course
        that is not on this list, use the link below to add it.
      </p>
      <p>
        when viewing a course, there will be a <em>printable scoresheet</em>
        link.&nbsp; click on this link and open it with a program that
        understands rtf (such as openoffice, word, or wordpad), then print it
        from there.&nbsp; if you want to save it, you will probably want to put
        the name of the course in there somewhere in case you save more than one
        of them.
      </p>
<?
    if($user->Valid) {
?>
      <ul><li><a href="?addCourse">add a course</a></li></ul>
<?
    } else {
?>
      <ul><li><a id="messageloginlink" href="/user/login.php">login</a> or <a href="/user/register.php">register</a> to add a course</li></ul>
<?
    }
  }
  if($user->Valid) {
    $rounds = 'select rounds from userstats where uid=\'' . $user->ID . '\'';
    $rounds = $db->GetValue($rounds, 'error looking up number of rounds played', 'user statistics not found');
  }
  $coursesel = '';
  if($_GET['coursesort'] == 'distance') {
    $heading = 'courses by distance';
    $options[] = '<a href="courses.php' . (isset($_GET['addround']) ? '?addround' : '') . '" title="sort courses by popularity">popularity</a>';
    $options[] = '<a href="?' . (isset($_GET['addround']) ? 'addround&amp;' : '') . 'coursesort=name" title="sort courses by name">name</a>';
    $coslat = cos(deg2rad($_GET['lat']));
    $coursesel = ', 69.0934137*sqrt(pow(' . $_GET['lat'] . '-latitude,2)+pow(' . $coslat . '*(' . $_GET['lon'] . '-longitude),2)) as distance';
    $courses = 'distance';
  } elseif($_GET['coursesort'] == 'name') {
    $heading = 'courses by name';
    $options[] = '<a href="courses.php' . (isset($_GET['addround']) ? '?addround' : '') . '" title="sort courses by popularity">popularity</a>';
    $courses = 'name';
  } else {
    $heading = 'courses by popularity';
    $options[] = '<a href="?' . (isset($_GET['addround']) ? 'addround&amp;' : '') . 'coursesort=name" title="sort courses by name">name</a>';
    $courses = $rounds ? 'userrounds desc' : 'rounds desc';
  }
  if($rounds)
    $courses = 'select c.id, c.approved, c.name, c.location, c.holes, c.par, c.rounds, count(r.uid) as userrounds' . $coursesel . ' from dgcourses as c left join dgrounds as r on r.courseid=c.id and r.uid=\'' . $user->ID . '\' and r.entryuid is null' . ($user->GodMode ? '' : ' where approved=\'yes\'') . ' group by c.id order by ' . $courses;
  else
    $courses = 'select id, approved, name, location, holes, par, rounds' . $coursesel . ' from dgcourses' . ($user->GodMode ? '' : ' where approved=\'yes\'') . ' order by ' . $courses;
  if($courses = $db->GetSplit($courses, 20, 0, '', '', 'error looking up courses', 'no courses found', false, true)) {
    $page->Heading($heading . ' <ul id="coursesort" class="elements"><li>' . implode('</li><li>', $options) . '</li></ul>');
?>
      <dl class="courses">
<?
    while($course = $courses->NextRecord()) {
?>
        <dt><a href="<?=(isset($_GET['addround']) ? 'rounds.php?id=new&amp;course=' : '?id=') . $course->id; ?>"><?=$course->name; ?></a></dt>
        <dd>
          located <?=$_GET['coursesort'] == 'distance' ? round($course->distance, $course->distance < 20 ? 1 : 0) . ' miles away ' : ''; ?>in <?=$course->location; ?>.&nbsp;
          <?=$rounds ? $course->userrounds : $course->rounds; ?> rounds logged<?=$rounds ? ' by you (' . $course->rounds . ' total)': ''; ?>.&nbsp;
          par <?=$course->par; ?> over <?=$course->holes; ?> holes.
        </dd>
<?
    }
?>
      </dl>
<?
  }
  $page->End();

  // --------------------------------------------------[ getAvgScoreSentence ]--
  function getAvgScoreSentence($num, $avg, $course) {
    if(!$num)
      return '';
    if($num == 1)
      foreach($avg as $type => $avs)
        foreach($avs as $tee => $av)
          return '&nbsp; average score is ' . number_format($av->avgscore, 1) . ' from ' . $av->rounds . ' ' . $type . ($course->teelist ? ', ' . $tee . '-tee' : '') . ' rounds.';
    else {
      $avgsentence = '&nbsp; average scores are ';
      $avgs = 0;
      if($num == 2)
        foreach($avg as $type => $average)
          foreach($average as $tee => $av) {
            $avgsentence .= number_format($av->avgscore, 1) . ' from ' . $av->rounds . ' ' . $type . ($course->teelist ? ', ' . $tee . '-tee' : '') . ' rounds';
            if($avgs++)
              $avgsentence .= '.';
            else
              $avgsentence .= '; and ';
          }
      else {
        foreach($avg as $type => $average)
          foreach($average as $tee => $av) {
            $avgsentence .= number_format($av->avgscore, 1) . ' from ' . $av->rounds . ' ' . $type . ($course->teelist ? ', ' . $tee . '-tee' : '') . ' rounds';
            $avgs++;
            if($avgs == $num)
              $avgsentence .= '.';
            elseif($avgs == $num - 1)
              $avgsentence .= '; and ';
            else
              $avgsentence .= '; ';
          }
      }
      return $avgsentence;
    }
  }

  // ------------------------------------------------------[ showCourseHoles ]--
  function showCourseHoles($holes, $par, $avg, $hastees) {
?>
      <div id="parlist">
<?
    for($s = 0; $s < $holes; $s+= 9)
      show9CourseHoles($s, $s + 9, $holes, $par, $avg, $hastees);
?>
      </div>

<?
  }

  function show9CourseHoles($shole, $ehole, $holes, $par, $avg, $hastees) {
?>
        <table class="data" cellspacing="0">
<?
    echo '          <thead><tr><td></td>';
    for($i = $shole + 1; $i <= $ehole; $i++)
      echo '<th>' . $i . '</th>';
    if($shole != 0 || $ehole != $holes)
      echo '<th>nine</th>';
    if($last = ($ehole == $holes))
      echo '<th>total</th>';
    echo "</tr></thead>\n          <tbody>\n";
    echo '            <tr><th>course par</th>';
    $parsum = 0;
    for($i = $shole; $i < $ehole; $i++) {
      echo '<td>' . $par[$i] . '</td>';
      $parsum += $par[$i];
    }
    echo '<th>' . $parsum . '</th>';
    if($last)
      echo '<th>' . (array_sum($par)) . '</th>';
    echo "</tr>\n";
    if(is_array($avg))
      foreach($avg as $type => $v)
        foreach($v as $tee => $values) {
          echo '            <tr><th>avg. ';
          echo $type;
          if($hastees) {
            echo ' ';
            echo $tee;
            echo '-tee';
          }
          echo '</th>';
          $avgsum = 0;
          for($i = $shole; $i < $ehole; $i++) {
            echo '<td>' . number_format($values->avglist[$i], 1) . '</td>';
            $avgsum += $values->avglist[$i];
          }
          echo '<th>' . number_format($avgsum, 1) . '</th>';
          if($last)
            echo '<th>' . number_format(array_sum($values->avglist), 1) . '</th>';
          echo "</tr>\n";
        }
?>
          </tbody>
        </table>
<?
  }

  // --------------------------------------------------------[ getCourseForm ]--
  function getCourseForm($course = false) {
    if($course)
      $courseform = new auForm('editcourse', '?id=' . $_GET['id'] . '&edit');
    else
      $courseform = new auForm('addcourse', '?addCourse');
    $courseform->Add(new auFormString('name', 'name', 'name of the course (i.e. grignon park)', true, html_entity_decode($course->name, ENT_COMPAT, _CHARSET), 40, 60));
    $courseform->Add(new auFormString('location', 'location', 'location of the course (i.e. appleton, wi)', true, $course->location, 40, 60));
    $courseform->Add(new auFormNumber('latitude', 'latitude', 'latitude portion of the geolocation of the first tee', false, $course->latitude));
    $courseform->Add(new auFormNumber('longitude', 'longitude', 'longitude portion of the geolocation of the first tee', false, $course->longitude));
    $courseform->Add(new auFormSelect('holes', 'holes', 'number of holes this course has', true, auFormSelect::ArrayIndex(array(9, 18, 27)), $course ? +$course->holes : 18));
    $courseform->Add(new auFormCheckbox('tees', 'tees', 'this course has both pro and amateur tees', $course->teelist));
    $courseform->Add(new auFormMultiString('comments', 'description', 'short description of this course', false, auText::HTML2BB($course->comments), true));
    $courseform->Add(new auFormHTML('par', getParFields($_POST['holes'], $course)));
    if($course)
      $courseform->Add(new auFormButtons('save', 'save changes to this course'));
    else
      $courseform->Add(new auFormButtons('add', 'request that this course get added'));
    return $courseform;
  }

  // ---------------------------------------------------------[ getParFields ]--
  function getParFields($holes = 18, $course) {
    if(!$holes)
      $holes = $course ? $course->holes : 18;
    $ret = "\n" . '            <table id="parfields" cellspacing="0">' . "\n";
    if($course)
      $parlist = explode('|', $course->parlist);
    $ret .= getNineParFields(0, 9, $parlist);
    $ret .= getNineParFields(9, 18, $parlist);
    $ret .= getNineParFields(18, 27, $parlist);
    return $ret . "            </table>\n          ";
  }

  // -----------------------------------------------------[ getNineParFields ]--
  function getNineParFields($start, $end, $parlist) {
    $ret = '              <tr class="holesto' . $end . '">';
    for($i = $start + 1; $i <= $end; $i++)
      $ret .= '<th>' . $i . '</th>';
    $ret .= "</tr>\n";
    $ret .= '              <tr class="holesto' . $end . '">';
    for($i = $start + 1; $i <= $end; $i++)
      $ret .= '<td><input type="text" name="par[' . $i . ']" value="' . (is_numeric($_POST['par'][$i]) ? +$_POST['par'][$i] : ($parlist ? $parlist[$i - 1] : 3)) .'" size="1" maxlength="1" /></td>';
    return $ret . "</tr>\n";
  }

  // -------------------------------------------------------[ checkParFields ]--
  function checkParFields() {
    for($i = 1; $i <= $_POST['holes']; $i++)
      if(!is_numeric($_POST['par'][$i]) || strlen($_POST['par'][$i]) != 1 || $_POST['par'][$i] < 1 || $_POST['par'][$i] > 9)
        return false;
    return true;
  }

  // ---------------------------------------------------------[ buildParList ]--
  function buildParList($sep = '\', \'') {
    for($i = 1; $i <= $_POST['holes']; $i++)
      $par[] = $_POST['par'][$i];
    return implode('|', $par) . $sep . array_sum($par);
  }
?>
