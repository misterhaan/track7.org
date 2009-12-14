<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

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
  $sketches = 'select a.id, a.description, a.adddate, ifnull(r.rating,0) as rating, ifnull(r.votes,0) as votes, v.vote from art as a left join ratings as r on r.selector=a.id and r.type=\'sketch\' left join votes as v on v.ratingid=r.id and (v.uid=' . $user->ID . ' or v.ip=\'' . addslashes($_SERVER['REMOTE_ADDR']) . '\') where a.type=\'sketch\' order by rating desc, votes desc, a.adddate desc';
  if($sketches = $db->Get($sketches, 'error looking up sketches', 'no sketches found'))
    while($sketch = $sketches->NextRecord()) {
      $page->Heading('', $sketch->id);
?>
      <div class="thumb">
        <a class="img" href="<?=$sketch->id; ?>.png" title="click to view full-size image"><img src="<?=$sketch->id; ?>-prev.png" alt="" /></a>
        <div><?=auFile::ImageSize($sketch->id . '.png'); ?></div>
        <? auRating::Show('sketch', $sketch->id, $sketch->rating, $sketch->votes, $sketch->vote); ?>
      </div>
      <div class="thumbed">
        <p>
          <?=$sketch->description; ?>

        </p>
      </div>
<?
    }

  $page->End();
?>
