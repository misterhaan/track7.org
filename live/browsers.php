<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('known browser issues with track7');
?>
      <p>
        track7 uses cascading style sheets (css) to present its layout and
        style.&nbsp; this works great in browsers such as mozilla firefox and 
        recent versions of netscape, but internet explorer and other older
        browsers tend to have problems with parts of it.&nbsp; often all this
        means is that people visiting track7 with internet explorer will miss
        out on some of the more advanced features.&nbsp; track7 should look
        &quot;good enough&quot; in any recent browser or internet explorer -- if
        you have problems, try upgrading to one of the following; each of which
        track7 is tested in, and any issues found are listed below:
      </p>
      <table class="text" cellspacing="0">
        <thead class="minor"><tr><th>browser</th><th>version</th><th title="the engine actually displays web pages, and may be used by other browsers as well">engine</th></tr></thead>
        <tbody>
          <tr class="firstchild"><td><a href="http://www.mozilla.org/products/firefox/">mozilla firefox</a></td><td>2.0.0.1</td><td>gecko</td></tr>
          <tr><td><a href="http://www.opera.com/download/">opera</a></td><td>9.10</td><td>opera</td></tr>
          <tr><td><a href="http://www.microsoft.com/windows/ie/default.mspx?mg_ID=10010">microsoft internet explorer</a></td><td>7.0</td><td>mshtml</td></tr>
          <tr><td><a href="http://www.konqueror.org/">konqueror</a></td><td>3.5</td><td>khtml</td></tr>
        </tbody>
      </table>

<?
  $browser = 'select browsername from hits where useragent=\'' . addslashes($_SERVER['HTTP_USER_AGENT']) . '\' order by instant';
  if($browser = $db->GetLimit($browser, 0, 1, 'error looking up browser based on useragent')) {
    if($browser->NumRecords() > 0) {
      $browser = $browser->NextRecord();
      $browser = $browser->browsername;
      if(strlen($browser) < 1) {
        $engine = $browser = '<em>unknown</em>';
      } else {
        $engine = 'select engine from browserengines where browser=\'' . addslashes($browser). '\'';
        if(false === $engine = $db->GetValue($engine, 'error looking up engine based on browser', ''))
          $engine = '<em>unknown</em>';
      }
    } else
      $engine = $browser = '<em>unknown</em>';
?>
      <p>
        based on the user agent sent by your browser, you appear to be using the
        <abbr title="<?=htmlspecialchars($_SERVER['HTTP_USER_AGENT']); ?>"><?=$browser; ?> browser, based on the <?=$engine; ?> engine</abbr>.&nbsp;
        note that this value is not reliable, and if it's not entirely correct,
        parts of track7 will not look right.
      </p>

<?
    $issues = 'select issue from browserissues where browser=\'' . $engine . '\'';
    if($issues = $db->Get($issues, 'error getting list of issues for your browser', '')) {
      if(file_exists('style/' . $engine . '-alpha.png'))
        $browserimg = ' class="browser"><img src="style/' . $engine . '-alpha.png" alt="" /';
      else
        $browserimg = '';
?>
      <h2<?=$browserimg; ?>><?=$engine; ?> engine</h2>
      <ul>
<?
      while($issue = $issues->NextRecord()) {
?>
        <li>
          <?=$issue->issue; ?>

        </li>
<?
      }
?>
      </ul>

<?
    }
  }
  $issues = 'select issue, browser from browserissues where not (browser=\'' . $engine .'\') order by browser';
  if($issues = $db->Get($issues, 'error getting list of issues for other browsers', '')) {
    while($issue = $issues->NextRecord()) {
      if($issue->browser != $browser) {
        echo $close;
        $close = '      </ul>' . "\n\n";
        $browser = $issue->browser;
        if(file_exists('style/' . $browser . '-alpha.png'))
          $browserimg = ' class="browser"><img src="style/' . $browser . '-alpha.png" alt="" /';
        else
          $browserimg = '';
?>
      <h2<?=$browserimg; ?>><?=$browser; ?> engine</h2>
      <ul>
<?
      }
?>
        <li>
          <?=$issue->issue; ?>

        </li>
<?
    }
    echo $close;
  }
  $page->End();
?>