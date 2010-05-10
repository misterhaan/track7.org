<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';

  $page->Start('unknown referrers');
  if($_POST['submit'] == 'add') {
    $ins = 'insert into knownreferrers (referrer, site) values (\'' . addslashes(htmlspecialchars($_POST['referrer'])) . '\', \'' . addslashes(htmlspecialchars($_POST['site'])) . '\')';
    if(false !== $db->Put($ins, 'error adding referrer to known referrers list'))
      $page->Info('referrer &quot;' . htmlspecialchars($_POST['referrer']) . '&quot; added to known referrers list as site &quot;' . htmlspecialchars($_POST['site']) . '&quot;');
  }
  $referrers = 'select h.value, h.hits from hitdetails as h left join knownreferrers as r on h.value=r.referrer where h.type=\'referrer\' and h.date=\'forever\' and r.site is null order by h.hits desc';
  if($referrers = $db->Get($referrers, 'error looking up unknownreferrers', 'no unknown referrers')) {
    $count = $referrers->NumRecords();
?>
      <p><?=$count; ?> unknown referrer<?=$count == 1 ? '' : 's'; ?> found</p>
<?
    for($referrer = $referrers->NextRecord(); $referrer && preg_match('/http:\/\/(www\.)?(m\.)?' . _HOST . '(:[0-9]+)?\//', $referrer->value); $referrer = $referrers->NextRecord()) {
      $ins = 'insert into knownreferrers (referrer, site) values (\'' . addslashes($referrer->value) . '\', \'http://' . $_SERVER['HTTP_HOST'] . '/\')';
      if(false !== $db->Put($ins, 'error automatically adding track7 referrer'))
        $page->Info('track7 referrer ' . $referrer->value . ' added automatically');
    }

    if($referrer) {
      $page->Heading($referrer->value . ' (' . $referrer->hits . ' hits)', 'referrer');
      $rf = new auForm('addreferrer');
      $rf->AddData('referrer', $referrer->value);
      $rf->AddField('site', 'site', 'enter the url to the site this referrer represents, or &quot;spam&quot; if it\'s spam', true, '', _AU_FORM_FIELD_NORMAL, 60, 128);
      $rf->AddButtons('add', 'add this referrer to the known referrers list');
      $rf->WriteHTML(true);
?>
      <div id="refcheckresults"></div>
<?
    }
  }
  $page->End();
?>
