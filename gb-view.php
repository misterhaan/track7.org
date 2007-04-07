<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->start('track7 guestbook');
?>
      <br />

<?
  $entries = 'select * from guestbook where site=\'track7\' order by instant desc';
  if($entries = $db->GetSplit($entries, 20, 0, '', '', 'error reading guestbook entries', 'no entries in this guestbook yet')) {
    $row = $db->split_count - $_GET['skip'];
    while($entry = $entries->NextRecord()) {
?>
      <div class="gbintro">
        <div class="gbtime"><?=strtolower($user->tzdate("F d, Y \a\\t\ g:i:s a", $entry->instant)); ?></div>
        <div class="gbnum"><?=$row--; ?></div>
<?=$entry->comments; ?>
      <hr class="minor" />
<?
    }
    $page->SplitLinks();
  }
  $page->End();
?>
