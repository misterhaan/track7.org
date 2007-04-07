<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  $access[0] = 0;
  $access[1] = 'user';
  $access[2] = 'directory';
  $access[3] = 3;
  $access[4] = 'system';
  $access[5] = 5;
  $access[6] = 'directory or system';
  $access[7] = 'all';

  $page->Start('php.ini');

  $ini= ini_get_all();
  foreach($ini as $key => $values) {
?>
      <h2><?=$key; ?></h2>
      <p>
        <strong>global:</strong>&nbsp; <?=htmlspecialchars($values['global_value']); ?><br />
        <strong>local:</strong>&nbsp; <?=htmlspecialchars($values['local_value']); ?><br />
        <strong>access:</strong>&nbsp; <?=$access[$values['access']]; ?>
      </p>

<?
  }

  $page->End();
?> 
