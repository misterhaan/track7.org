<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFile.php';
  require_once 'auForm.php';

  $page->Start('digital art gallery');

  if($_POST['submit'] == 'vote' && is_numeric($_POST['vote']) && $_POST['vote'] >= -3 && $_POST['vote'] <= 3) {
    $ratingid = 'select id from ratings where type=\'digital\' and selector=\'' . addslashes($_POST['digital']) . '\'';
    if($ratingid = $db->Get($ratingid, 'error looking up rating id')) {
      $ratingid = $ratingid->NextRecord();
      if($ratingid)
        $ratingid = $ratingid->id;
      else {
        // insert new row into ratings table
        $ins = 'insert into ratings (type, selector) values (\'digital\', \'' . addslashes($_POST['digital']) . '\')';
        $ratingid = $db->Put($ins, 'error initializing rating');
      }
      if($ratingid) {
        $vote = 'replace into votes (ratingid, vote, uid, ip, time) values (' . $ratingid . ', ' . $_POST['vote'] . ', ' . $user->ID . ', \'' . ($user->Valid ? '' : $_SERVER['REMOTE_ADDR']) . '\', ' . time() . ')';
        if($db->Change($vote, 'error adding vote')) {
          $rating = 'select sum(vote) as ratesum, count(1) ratecnt from votes where ratingid=' . $ratingid;
          if($rating = $db->GetRecord($rating, 'error calculating new rating', 'no votes found')) {
            $rating = 'update ratings set rating=' . ($rating->ratesum / $rating->ratecnt) . ', votes=' . $rating->ratecnt . ' where id=' . $ratingid;
            if(false !== $db->Change($rating, 'error updating new rating'))
              $page->Info('vote sucessfully added or updated');
          }
        }
      }
    }
  }

  $digitals = 'select a.id, a.description, a.adddate, r.rating, r.votes from art as a left join ratings as r on r.selector=a.id and r.type=\'digital\' where a.type=\'digital\' order by r.rating desc, a.adddate desc';
  if($digitals = $db->Get($digitals, 'error looking up digital art', 'no digital art found'))
    while($digital = $digitals->NextRecord()) {
      $page->Heading('', $digital->id);
?>
      <div class="thumb">
        <a class="img" href="<?=$digital->id; ?>.png" title="click to view full-size image"><img src="<?=$digital->id; ?>-prev.png" alt="" /></a>
        <div><?=auFile::ImageSize($digital->id . '.png'); ?></div>
        <div>rated <?=+$digital->rating; ?> (from <?=+$digital->votes; ?> votes)<?=$_GET['vote'] == $digital->id ? '' : '</div>' . "\n" . '        <div><a href="?vote=' . $digital->id . '#frmvote">cast vote</a>'; ?></div>
      </div>
      <div class="thumbed">
        <p>
          <?=$digital->description; ?>

        </p>
<?
      if($_GET['vote'] == $digital->id) {
        $rating = 'select v.vote from votes as v, ratings as r where r.id=v.ratingid and r.type=\'digital\' and r.selector=\'' . $digital->id . '\' and (v.uid=' . $user->id . ' or v.ip=\'' . $_SERVER['REMOTE_ADDR'] . '\') order by v.ip';
        $rating = $db->GetValue($rating, 'error checking to see if you have already voted', '');
        $vote = new auForm('vote');
        $vote->AddData('digital', $digital->id);
        $voteset = new auFormFieldSet('rate image');
        $voteset->AddSelect('vote', 'rating', 'choose your rating of this image', array(-3 => '-3 (worst)', -2 => '-2', -1 => '-1', 0 => '0 (average)', 1 => '1', 2 => '2', 3 => '3 (best)'), +$rating);
        $voteset->AddButtons('vote', 'cast your vote for this image');
        $vote->AddFieldSet($voteset);
        $vote->WriteHTML($user->Valid);
      }
?>
      </div>
<?
    }

  $page->End();
?>
