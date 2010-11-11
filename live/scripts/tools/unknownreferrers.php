<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(!$user->GodMode) {
    $page->Show404();
    die;
  }
  $referrers = 'select referrer, site from knownreferrers where referrer like \'%' . addslashes($_GET['ref']) . '%\'';
  if($referrers = $db->Get($referrers, '')) {
    if($referrers->NumRecords()) {
?>
        <table class="text" cellspacing="0">
          <thead><tr><th>site</th><th>referrer</th></tr></thead>
          <tbody>
<?
      while($referrer = $referrers->NextRecord()) {
?>
            <tr><td><?=$referrer->site; ?></td><td><?=$referrer->referrer; ?></td></tr>
<?
      }
?>
          </tbody>
        </table>
<?
    } else
      die('<p class="info">no known referrers match ' . htmlspecialchars($_GET['ref'], ENT_COMPAT, _CHARSET) . '</p>');
  } else
    die('<p class="error">error checking known referrers</p>');
?>