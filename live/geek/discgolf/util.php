<?
  function calcAllAvgScores(&$db, $courseId) {
    $success = true;
    $del = 'delete from dgcoursestats where courseid=\'' . $courseId . '\'';
    if(false === $db->Change($del, 'error deleting existing stats for course ' . $courseId))
      return false;
    $roundTypes = 'select distinct roundtype, tees from dgrounds where courseid=\'' . $courseId . '\'';
    if($roundTypes = $db->Get($roundTypes, 'error looking up round types for course ' . $courseId, ''))
      while($roundType = $roundTypes->NextRecord())
        $success &= calcAvgScores($db, $courseId, $roundType->roundtype, $roundType->tees);
    return $success;
  }

  function calcAvgScores(&$db, $courseId, $roundType, $tees, $delempty = false) {
    $scores = 'select scorelist, score from dgrounds where courseid=\'' . $courseId . '\' and roundtype' . ($roundType ? '=\'' . $roundType . '\'' : ' is null') . ' and tees' . ($tees ? '=\'' . $tees . '\'' : ' is null');
    if($scores = $db->Get($scores, 'error looking up ' . $roundType . ', ' . $tees . '-tee rounds for course ' . $courseId)) {
      $rounds = $scores->NumRecords();
      if(!$rounds)
        if($delempty) {
          $del = 'delete from dgcoursestats where courseid=\'' . $courseId . '\' and roundtype=\'' . $roundType . '\' and tees=\'' . $tees . '\'';
          return false !== $db->Change($del, 'error deleting old course stats');
        } else
          return true;          
      while($score = $scores->NextRecord()) {
        $totalscore += $score->score;
        $scorelist = explode('|', $score->scorelist);
        for($i = count($scorelist) - 1; $i >= 0; $i--)
          $totallist[$i] += $scorelist[$i];
      }
      for($i = count($totallist) - 1; $i >= 0; $i--)
        $totallist[$i] = round($totallist[$i] / $rounds, 1); 
      $ins = 'replace into dgcoursestats (courseid, roundtype, tees, avglist, avgscore, rounds) values (\'' . $courseId . '\', \'' . $roundType . '\', \'' . $tees . '\', \'' . implode('|', $totallist) . '\', \'' . round($totalscore / $rounds, 1) . '\', \'' . $rounds . '\')';
      return false !== $db->Put($ins, 'error saving average scores of ' . $roundType . ' rounds for course ' . $courseId);
    }
    return false;
  }

  function calcPlayerStats(&$db, $uid) {
    $rounds = 'select scorelist from dgrounds where uid=\'' . $uid . '\'';
    $score = array(0, 0, 0, 0, 0, 0);
    if($rounds = $db->Get($rounds, 'error looking up scores for this player')) {
      while($round = $rounds->NextRecord()) {
        $scorelist = explode('|', $round->scorelist);
        foreach($scorelist as $s) {
          $score[0]++;
          $score[$s]++;
        }
      }
      $db->Put('replace into dgplayerstats (uid, aces, birds, pars, bogies, doubles, holes) values (\'' . $uid . '\', ' . $score[1] . ', ' . $score[2] . ', ' . $score[3] . ', ' . $score[4] . ', ' . $score[5] . ', ' . $score[0] . ')', 'error saving statistics');
      $db->Change('update userstats set rounds=\'' . $rounds->NumRecords() . '\' where uid=\'' . $uid . '\'');
    }
  }
?>
