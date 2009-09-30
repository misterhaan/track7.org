<?
  require_once dirname(dirname($_SERVER['DOCUMENT_ROOT'])) . '/lib/track7.php';

  if(isset($_GET['course']) && is_numeric($_GET['course'])) {
    $course = 'select id, name, teelist, holes, parlist from dgcourses where id=\'' . addslashes($_GET['course']) . '\'';
    if($course = $db->GetRecord($course, 'error looking up course', 'course not found')) {
      if(isset($_POST['return']) && $_POST['return'] == 'xml') {
        header('Content-Type: text/xml; charset=utf-8');
        header('Cache-Control: no-cache');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<response>
<?
        if(!$user->Valid) {
?>
<errors>
<error>must be logged in to add scores.  log in using a new page, then come back and try again.</error>
</errors>
</response>
<?
          die;
        }
        // validate and handle form data
        $date = $_POST['date'] ? strtotime($_POST['date']) : time();
        $type = addslashes($_POST['type']);
        if(!$type)
          $type = 'null';
        $tees = addslashes($_POST['tees']);
        if(!$tees)
          $tees = 'null';
        $notes = addslashes(auText::BB2HTML($_POST['notes']));
        require_once '../../geek/discgolf/util.php';
        for($p = 0; $p < count($_POST['player']); $p++) {
          // find player uid
          if(!$_POST['player'][$p] || $user->Valid && $_POST['player'][$p] == $user->Name)
            $playerid = $user->ID;
          else {
            $playerid = 'select uid from users where login=\'' . addslashes($_POST['player'][$p]) . '\'';
            $playerid = $db->GetValue($playerid, 'error looking up player', 'player ' . htmlspecialchars($_POST['player'][$p]) . ' not found', true);
            // DO:  support anonymous rounds
          }
          if($playerid) {
            // save player scores
            $roundid = 'insert into dgrounds (uid, courseid, roundtype, tees, entryuid, instant, scorelist, score, comments) values (\'' . $playerid . '\', \'' . $course->id . '\', ' . ($type == 'null' ? 'null' : '\'' . addslashes($type) . '\'') . ', ' . ($tees == 'null' ? 'null' : '\'' . addslashes($tees) . '\'') . ', ' . ($playerid == $user->ID ? 'null' : '\'' . addslashes($user->ID) . '\'') . ', \'' . $date . '\', \'' . addslashes($_POST['scores'][$p]) . '\', \'' . array_sum(explode('|', $_POST['scores'][$p])) . '\', \'' . ($playerid == $user->ID ? $notes : 'from ' . $user->Name . ':&nbsp; ' . $notes) . '\')';
            if(false !== $roundid = $db->Put($roundid, 'error saving round')) {
              // update player stats
              calcPlayerStats($db, $playerid);
              // this row successful
              $round[$p]['success'] = true;
              $round[$p]['id'] = $roundid;
            } else {
              $round[$p]['success'] = false;
              $round[$p]['msg'] = 'error saving round';
            }
          } else {
            $round[$p]['success'] = false;
            $round[$p]['msg'] = 'player ' . htmlspecialchars($_POST['player'][$p]) . ' not found';
          }
        }
        // update course stats
        $success['count'] = countRounds($db, $course->id);
        $success['avg'] = calcAvgScores($db, $course->id, $type == 'null' ? null : $type, $tees && $tees != 'null' ? $tees : null);
?>
<rounds count="<?=$p; ?>">
<?
        foreach($round as $i => $r)
          if($r['success']) {
?>
<round index="<?=$i; ?>">
<result>success</result>
<id><?=$r['id']; ?></id>
</round>
<?
          } else {
?>
<round index="<?=$i; ?>">
<result>error</result>
<message><?=$r['msg']; ?></message>
</round>
<?
          }
?>
</rounds>
<course>
<count><?=$success['count'] ? 'success' : 'error'; ?></count>
<avg><?=$success['avg'] ? 'success' : 'error'; ?></avg>
</course>
<errors>
<?
        $page->SendXmlErrors(false);
?>
</errors>
</response>
<?
        die;
      }
      $page->Start($course->name . ' - disc golf scores', 'disc golf scores', $course->name);
      if($user->Valid)
        $page->Info('logged in as <span id="loggedinuser">' . $user->Name . '</span>.&nbsp; if this is not you, <a href="?userlogout=' . urlencode($_SERVER['REQUEST_URI']) . '">log out</a>.');
      else
        $page->Error('must be logged in to add disc golf scores — <a id="loginlink" href="/user/login.php">log in</a> or <a href="http://www.track7.org/user/register.php">register</a>');
?>
      <noscript><p class="info">this page does not function without javascript.&nbsp; either enable javascript or <a href="http://www.track7.org/geek/discgolf/rounds.php?id=new&amp;course=<?=$course->id; ?>">add your round</a> using the full track7 site.</p></noscript>
      <form id="addroundform" method="post" action="?course=<?=$course->id; ?>">
        <fieldset id="sharedinfo">
          <table class="columns" cellspacing="0">
            <tr><th><label for="flddate">date</label></th><td><input id="flddate" name="date" /></td></tr>
            <tr><th><label for="fldtype">type</label></th><td><select id="fldtype" name="type">
              <option value="null">(unknown)</option>
<?
      $types = 'show columns from dgrounds like \'roundtype\'';
      if($types = $db->Get($types, 'error looking up round types', '')) {
        $types = $types->NextRecord();
        $types = explode('\',\'', substr($types->Type, 6, -2));
        foreach($types as $type) {
?>
              <option><?=$type; ?></option>
<?
        }
      }
?>
            </select></td></tr>
<?
      if($course->teelist) {
?>
            <tr><th><label for="fldtees">tees</label></th><td><select id="fldtees" name="tees">
              <option value="null">(unknown)</option>
<?
        foreach(explode(',', $course->teelist) as $tee) {
?>
              <option><?=$tee; ?></option>
<?
        }
?>
            </select></td></tr>
<?
      }
?>
            <tr><th><label for="fldplayers">players</label></th><td><select id="fldplayers" name="players"><option>1</option><option>2</option><option>3</option><option>4</option></select></td></tr>
            <tr><td></td><td><input id="sharedinfosubmit" type="submit" name="submit" value="next" /></td></tr>
          </table>
        </fieldset>

        <fieldset id="scorelist">
          <table class="data" cellspacing="0">
            <thead>
              <tr><td></td><? for($hole = 1; $hole <= $course->holes; $hole++) echo '<th>' . $hole . '</th>'; ?><th>total</th></tr>
              <tr class="par"><th>par</th><td><?=implode('</td><td>', explode('|', $course->parlist)); ?></td><th class="total"><?=array_sum(explode('|', $course->parlist)); ?></th></tr>
            </thead>
          </table>
        </fieldset>
      </form>
<?
      $page->End();
      die;
    }
  }
  $page->Start('disc golf scores');

  // show nearest courses
  if($_GET['lat'] && $_GET['lon']) {
    $coslat = cos(deg2rad($_GET['lat']));
    if($courses = $db->Get('select name, id, 69.0934137*sqrt(pow(' . $_GET['lat'] . '-latitude,2)+pow(' . $coslat . '*(' . $_GET['lon'] . '-longitude),2)) as distance from dgcourses order by distance', 'error looking up nearest courses')) {
?>
      <ul>
<?
      while($course = $courses->NextRecord()) {
?>
      <li><a href="?course=<?=$course->id; ?>"><?=$course->name; ?></a> (<?=round($course->distance, 1); ?> miles)</li>
<?
      }
?>
      </ul>
<?
    }
  // show all courses in alpha order
  } elseif($courses = $db->Get('select name, id from dgcourses order by name', 'error looking up courses')) {
?>
      <ul>
<?
      while($course = $courses->NextRecord()) {
?>
      <li><a href="?course=<?=$course->id; ?>"><?=$course->name; ?></a></li>
<?
      }
?>
      </ul>
<?
  }
  $page->End();
?>
