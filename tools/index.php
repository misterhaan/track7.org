<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('track7 administrative tools');
?>
      <h2>manage data</h2>
      <ul>
        <li><a href="votes.php">votes</a></li>
        <li><a href="unknownreferrers.php">unknown referrers</a></li>
        <li><a href="guidereview.php">guide review</a></li>
        <li><a href="taginfo.php">tag info</a></li>
        <li><a href="links.php">related links</a></li>
<?
  if(strpos($_SERVER['HTTP_HOST'], 'track7.') !== false) {
?>
        <li><a href="/dh_phpmyadmin/data.track7.org/">phpmyadmin</a></li>
        <li><a href="https://panel.dreamhost.com/">panel</a></li>
<?
  } else {
?>
        <li><a href="/phpmyadmin/">phpmyadmin</a></li>
<?
  }
?>
      </ul>

      <h2>test php functions</h2>
      <ul>
        <li><a href="regex.php">regular expression tester</a></li>
        <li><a href="timestamps.php">timestamp converter</a></li>
      </ul>

      <h2>view server settings</h2>
      <ul>
        <li><a href="info.php">phpinfo</a></li>
        <li><a href="server.php">$_SERVER array contents</a></li>
        <li><a href="ini.php">php.ini settings</a></li>
      </ul>

<?
  $page->End();
?>
