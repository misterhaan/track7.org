<?
  //$getvars = array('date', 'failure', 'request', 'referrer', 'browser', 'platform', 'ip', 'uid');
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auText.php';

  if(!$user->GodMode) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/');
    die;
  }

  $from = '';
  $daymode = false;

  if(preg_match('/^[1-9][0-9]{3}-[0-2][0-9]-[0-3][0-9]$/', $_GET['date'])) {
    $where = 'h.instant>=' . strtotime($_GET['date']) . ' and h.instant<' . auText::Tomorrow(strtotime($_GET['date']));
    $page->Start($_GET['date'] . ' - hits', strtolower(date('l, F j<\s\u\p>S</\s\u\p>, Y', strtotime($_GET['date']))), 'daily hits');
    $daymode = true;
  } elseif(isset($_GET['failure'])) {
    $where = 'h.status=404 and (h.request=\'' . addslashes($_GET['failure']) . '\' or h.request like \'' . addslashes($_GET['failure']) . '?%\' or h.request like \'' . addslashes($_GET['failure']) . 'index.%\' or h.request like \'' . addslashes($_GET['failure']) . 'default.%\')';
    $page->Start($_GET['failure'] . ' - hits', $_GET['failure'], 'all requests');
  } elseif(isset($_GET['request'])) {
    $where = 'h.status<404 and (h.request=\'' . addslashes($_GET['request']) . '\' or h.request like \'' . addslashes($_GET['request']) . '?%\' or h.request like \'' . addslashes($_GET['request']) . 'index.%\' or h.request like \'' . addslashes($_GET['request']) . 'default.%\')';
    $page->Start($_GET['request'] . ' - hits', $_GET['request'], 'all requests');
  } elseif(isset($_GET['referrer'])) {
    $from = 'knownreferrers as r, ';
    $where = 'h.referrer=r.referrer and r.site=\'' . addslashes($_GET['referrer']) . '\'';
    $page->Start($_GET['referrer'] . ' - hits', $_GET['referrer'], 'all requests');
  } elseif(isset($_GET['browser'])) {
    $where = 'h.browsername=\'' . addslashes($_GET['browser']) . '\' or concat(concat(h.browsername, \' \'), h.browserversion)=\'' . addslashes($_GET['browser']). '\'';
    $page->Start($_GET['browser'] . ' - hits', $_GET['browser'], 'all requests');
  } elseif(isset($_GET['platform'])) {
    $where = 'h.platform=\'' . addslashes($_GET['platform']) . '\'';
    $page->Start($_GET['platform'] . ' - hits', $_GET['platform'], 'all requests');
  } elseif(isset($_GET['ip']) && $user->GodMode) {
    $where = 'h.ip=\'' . addslashes($_GET['ip']) . '\'';
    $page->Start($_GET['ip'] . ' - hits', $_GET['ip'], 'all requests');
  } elseif(is_numeric($_GET['uid']) && $user->GodMode) {
    $where = 'h.uid=' . $_GET['uid'];
    $u = 'select login from users where uid=' . $_GET['uid'];
    if($u = $db->GetValue($u, 'error looking up username', 'user not found'))
      $page->Start($u . ' - hits', $u, 'all requests');
    else
      $page->Start('unknown user - hits', 'unknown user', 'all requests');
  } else {  // prevent showing absolutely all dates
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/');
    die;
  }

  $hits = 'select h.instant, h.status, h.request, h.referrer, h.useragent, h.browsername, h.browserversion, h.platform, h.ip, h.uid, u.login from ' . $from . 'hits as h left join users as u on h.uid=u.uid where ' . $where . ' order by instant';
  if($daymode)
    $hits = $db->Get($hits, 'error looking up hits', 'no hits found');
  else
    $hits = $db->GetSplit($hits . ' desc', 25, 0, '', '', 'error looking up hits', 'no hits found');
  if($hits) {
?>
      <ol id="hits">
<?
    if($daymode)
      $raw = $hits->NumRecords();
    while($hit = $hits->NextRecord()) {
?>
        <li><table class="columns" cellspacing="0">
          <tr class="firstchild"><th>time</th><td><?=$user->tzdate('Y-m-d g:i:s a', $hit->instant); ?></td></tr>
          <tr><th>ip</th><td><?=$hit->ip; ?><?=$hit->uid ? ' (' . $hit->login . ')' : ''; ?></td></tr>
          <tr><th>request</th><td><?=$hit->request . ($hit->status == 200 ? '' : ' (' . $hit->status . ')'); ?></td></tr>
          <tr><th>referrer</th><td><?=$hit->referrer ? '<a href="' . str_replace('%', '%25', $hit->referrer) . '">' . $hit->referrer . '</a>' : ''; ?></td></tr>
          <tr><th>useragent</th><td title="<?=$hit->useragent; ?>"><?=$hit->browsername && $hit->browsername != 'default browser' ? $hit->browsername . ' ' . $hit->browserversion . ($hit->platform ? ' on ' . $hit->platform : '') : '(unknown)'; ?></td></tr>
        </table></li>
<?
      if($daymode) {
        if($hit->instant - $iptime[$hit->ip] > 21600)  // 6 hours
          $unique++;
        $iptime[$hit->ip] = $hit->instant;
      }
    }
?>
      </ol>
<?
    if($daymode) {
?>
      <p><?=$unique; ?> unique hits (<?=$raw; ?> raw)</p>
<?
    } else
      $page->SplitLinks();
?>

<?
  }
  $page->End();
?>
