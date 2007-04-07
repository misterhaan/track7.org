<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFile.php';
  require_once 'auForm.php';

  $page->Start('pen / pencil sketch gallery');
?>
      <p>
        the following are most of the pen and pencil sketches i've done
        throughout my years (and haven't thrown out or lost).&nbsp; the newest
        ones are at the top, as best as i can remember when i drew them.&nbsp;
        the previews don't show the whole sketch -- click on them to bring up a
        large version.
      </p>

<?
  if($_POST['submit'] == 'vote' && is_numeric($_POST['vote']) && $_POST['vote'] >= -3 && $_POST['vote'] <= 3) {
    $ratingid = 'select id from ratings where type=\'sketch\' and selector=\'' . addslashes($_POST['sketch']) . '\'';
    if($ratingid = $db->GetRecord($ratingid, 'error looking up rating id')) {
      if($ratingid)
        $ratingid = $ratingid->id;
      else  // insert new row into ratings table
        $ratingid = $db->Put($ins, 'error initializing rating');
      if($ratingid) {
        $vote = 'replace into votes (ratingid, vote, uid, ip, time) values (' . $ratingid . ', ' . $_POST['vote'] . ', ' . $user->ID . ', \'' . ($user->Valid ? '' : $_SERVER['REMOTE_ADDR']) . '\', ' . time() . ')';
        if($db->Change($vote, 'error adding vote')) {
          $rating = 'select sum(vote) ratesum, count(1) ratecnt from votes where ratingid=' . $ratingid;
          if($rating = $db->GetRecord($rating, 'error calculating new rating', 'no votes found')) {
            $rating = 'update ratings set rating=' . ($rating->ratesum / $rating->ratecnt) . ', votes=' . $rating->ratecnt . ' where id=' . $ratingid;
            if(false !== $db->Change($rating, 'error updating new rating'))
              $page->Info('vote sucessfully added or updated');
          }
        }
      }
    }
  }

  $sketches = 'select a.id, a.description, a.adddate, r.rating, r.votes from art as a left join ratings as r on r.selector=a.id and r.type=\'sketch\' where a.type=\'sketch\' order by r.rating desc, a.adddate desc';
  if($sketches = $db->Get($sketches, 'error looking up sketches', 'no sketches found'))
    while($sketch = $sketches->NextRecord()) {
      $page->Heading('');
?>
      <div class="thumb">
        <a class="img" href="<?=$sketch->id; ?>.png" title="click to view full-size image"><img src="<?=$sketch->id; ?>-prev.png" alt="" /></a>
        <div><?=auFile::ImageSize($sketch->id . '.png'); ?></div>
        <div>rated <?=+$sketch->rating; ?> (from <?=+$sketch->votes; ?> votes)<?=$_GET['vote'] == $sketch->id ? '' : '</div>' . "\n" . '        <div><a href="?vote=' . $sketch->id . '#frmvote">cast vote</a>'; ?></div>
      </div>
      <div class="thumbed">
        <p>
          <?=$sketch->description; ?>

        </p>
<?
      if($_GET['vote'] == $sketch->id) {
        $rating = 'select v.vote from votes as v, ratings as r where r.id=v.ratingid and r.type=\'sketch\' and r.selector=\'' . $sketch->id . '\' and (v.uid=' . $user->id . ' or v.ip=\'' . $_SERVER['REMOTE_ADDR'] . '\') order by v.ip';
        $rating = $db->GetValue($rating, 'error checking to see if you have already voted', '');
        $vote = new auForm('vote');
        $vote->AddData('sketch', $sketch->id);
        $voteset = new auFormFieldSet('rate sketch');
        $voteset->AddSelect('vote', 'rating', 'choose your rating of this sketch', array(-3 => '-3 (worst)', -2 => '-2', -1 => '-1', 0 => '0 (average)', 1 => '1', 2 => '2', 3 => '3 (best)'), +$rating);
        $voteset->AddButtons('vote', 'cast your vote for this sketch');
        $vote->AddFieldSet($voteset);
        $vote->WriteHTML($user->Valid);
      }
?>
      </div>
<?
    }

  $page->End();
?>
