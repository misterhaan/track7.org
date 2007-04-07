<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('thief:&nbsp; the dark project fan missions', 'thief:&nbsp; the dark project', 'fan missions');

  $fms = 'select * from fanmissions where type=\'tdp\' order by number desc';
  if($fms = $db->Get($fms, 'error reading reviews')) {
    while($review = $fms->NextRecord()) {
?>
      <h2><span class="when"><?=$user->tzdate('m-d-Y', $review->date); ?></span><?=$review->title; ?></h2>
      <p>
        <?=$review->review . "\n"; ?>
      </p>

<?
    }
  }
  $page->End();
?>
