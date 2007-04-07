<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if(preg_match('/^[1-9][0-9]{3}-[0-2][0-9]-[0-3][0-9]$/', $_GET['day'])) {
    $page->Start($_GET['day'] . ' statistics', 'daily hit statistics', strtolower(date('l, F j<\s\u\p>S</\s\u\p>, Y', strtotime($_GET['day']))));
    $range = 'day';
    $date = $_GET['day'];
  } elseif(preg_match('/^[1-9][0-9]{3}w[0-5][0-9]$/', $_GET['week'])) {
    $page->Start($_GET['week'] . ' statistics', 'weekly hit statistics', substr($_GET['week'], 0, 4) . ' week ' . +substr($_GET['week'], 5));
    $range = 'week';
    $date = $_GET['week'];
  } elseif(preg_match('/^[1-9][0-9]{3}-[0-2][0-9]$/', $_GET['month'])) {
    $page->Start($_GET['month'] . ' statistics', 'monthly hit statistics', strtolower(date('F Y', strtotime($_GET['month'] . '-15'))));
    $range = 'month';
    $date = $_GET['month'];
  } elseif(preg_match('/^[1-9][0-9]{3}$/', $_GET['year'])) {
    $page->Start($_GET['year'] . ' statistics', 'yearly hit statistics', $_GET['year']);
    $range = 'year';
    $date = $_GET['year'];
  } else {
    $page->Start('overall statistics', 'overall hit statistics');
    $range = 'overall';
    $date = 'forever';
  }

  $requests = 'select value, hits from hitdetails where date=\'' . $date . '\' and type=\'request\' order by hits desc';
  if($requests = $db->GetLimit($requests, 0, 10, 'error looking up page requests', '')) {
    $page->Heading('page requests');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>request</th><th>hits</th></tr></thead>
        <tbody>
<?
    while($request = $requests->NextRecord()) {
?>
          <tr><td><?=$request->value; ?></td><td class="number"><?=$request->hits; ?></td><?=$user->GodMode ? '<td class="clear"><a class="img" href="hits.php?request=' . $request->value . '"><img src="/style/details.png" alt="details" /></a></td>' : ''; ?></tr>
<?
    }
?>
        </tbody>
      </table>

<?
  }

  if($user->GodMode) {
    $fails = 'select value, hits from hitdetails where date=\'' . $date . '\' and type=\'failure\' order by hits desc';
    if($fails = $db->GetLimit($fails, 0, 10, 'error looking up failures', '')) {
      $page->Heading('failed requests');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>request</th><th>hits</th></tr></thead>
        <tbody>
<?
      while($fail = $fails->NextRecord()) {
?>
          <tr><td><?=$fail->value; ?></td><td class="number"><?=$fail->hits; ?></td><td class="clear"><a class="img" href="hits.php?failure=<?=str_replace('%', '%25', $fail->value); ?>"><img src="/style/details.png" alt="details" /></a></td></tr>
<?
      }
?>
        </tbody>
      </table>

<?
    }
  }
  
  $referrers = 'select r.site, sum(d.hits) as totalhits from knownreferrers as r, hitdetails as d where r.referrer=d.value and d.date=\'' . $date . '\' and d.type=\'referrer\' and not (site=\'spam\') group by site order by totalhits desc';
  if($referrers = $db->GetLimit($referrers, 0, 10, 'error looking up referrers', '')) {
    $page->Heading('referring sites');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>site</th><th>hits</th></tr></thead>
        <tbody>
<?
    while($referrer = $referrers->NextRecord()) {
?>
          <tr><td><a href="<?=$referrer->site; ?>"><?=$referrer->site; ?></a></td><td class="number"><?=$referrer->totalhits; ?></td><?=$user->godmode ? '<td class="clear"><a class="img" href="hits.php?referrer=' . $referrer->site . '"><img src="/style/details.png" alt="details" /></a></td>' : ''; ?></tr>
<?
    }
?>
        </tbody>
      </table>

<?
  }

  $browsers = 'select value, hits from hitdetails where date=\'' . $date . '\' and type=\'browser\' and not (value=\'default browser\') order by hits desc';
  if($browsers = $db->GetLimit($browsers, 0, 10, 'error looking up browsers', '')) {
    $page->heading('browsers');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>browser</th><th>hits</th></tr></thead>
        <tbody>
<?
    while($browser = $browsers->NextRecord()) {
?>
          <tr><td><?=$browser->value; ?></td><td class="number"><?=$browser->hits; ?></td><?=$user->godmode ? '<td class="clear"><a class="img" href="hits.php?browser=' . $browser->value . '"><img src="/style/details.png" alt="details" /></a></td>' : ''; ?></tr>
<?
    }
?>
        </tbody>
      </table>

<?
  }

  $platforms = 'select value, hits from hitdetails where date=\'' . $date . '\' and type=\'platform\' order by hits desc';
  if($platforms = $db->GetLimit($platforms, 0, 10, 'error looking up platforms', '')) {
    $page->Heading('platforms');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>platform</th><th>hits</th></tr></thead>
        <tbody>
<?
    while($platform = $platforms->NextRecord()) {
?>
          <tr><td><?=$platform->value; ?></td><td class="number"><?=$platform->hits; ?></td><?=$user->GodMode ? '<td class="clear"><a class="img" href="hits.php?platform=' . $platform->value . '"><img src="/style/details.png" alt="details" /></a></td>' : ''; ?></tr>
<?
    }
?>
        </tbody>
      </table>

<?
  }

  if($user->GodMode) {
    $ips = 'select value, hits from hitdetails where date=\'' . $date . '\' and type=\'ip\' order by hits desc';
    if($ips = $db->GetLimit($ips, 0, 10, 'error looking up ip addresses', '')) {
      $page->Heading('ip addresses');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>request</th><th>hits</th></tr></thead>
        <tbody>
<?
      while($ip = $ips->NextRecord()) {
?>
          <tr><td><?=$ip->value; ?></td><td class="number"><?=$ip->hits; ?></td><td class="clear"><a class="img" href="hits.php?ip=<?=$ip->value; ?>"><img src="/style/details.png" alt="details" /></a></td></tr>
<?
      }
?>
        </tbody>
      </table>

<?
    }
  }
  $page->End();
?>
