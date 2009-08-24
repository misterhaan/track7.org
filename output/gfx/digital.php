<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  $page->Start('digital art gallery');

  $digitals = 'select a.id, a.description, a.adddate, ifnull(r.rating,0) as rating, ifnull(r.votes,0) as votes, v.vote from art as a left join ratings as r on r.selector=a.id and r.type=\'digital\' left join votes as v on v.ratingid=r.id and (v.uid=' . $user->ID . ' or v.ip=\'' . addslashes($_SERVER['REMOTE_ADDR']) . '\') where a.type=\'digital\' order by rating desc, votes desc, a.adddate desc';
  if($digitals = $db->Get($digitals, 'error looking up digital art', 'no digital art found'))
    while($digital = $digitals->NextRecord()) {
      $page->Heading('', $digital->id);
?>
      <div class="thumb">
        <a class="img" href="<?=$digital->id; ?>.png" title="click to view full-size image"><img src="<?=$digital->id; ?>-prev.png" alt="" /></a>
        <div><?=auFile::ImageSize($digital->id . '.png'); ?></div>
        <? auRating::Show('digital', $digital->id, $digital->rating, $digital->votes, $digital->vote); ?>
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
