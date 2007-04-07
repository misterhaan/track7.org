<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('$_SERVER');
?>
      <table class="columns" cellspacing="0">
<?
  foreach($_SERVER as $tag => $data)
    echo "        <tr><th>$tag</th><td>$data</td></tr>\n";
?>
      </table>
<?
  $page->End();
?>