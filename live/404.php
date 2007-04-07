<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  header('HTTP/1.0 404 Not Found');
  $page->Start('404 not found', '404 bad guess');
?>
      <p>
        i can't find the file you asked for, so you will have to either get it
        right or find it yourself (or have google help you):
      </p>

<?
  require_once 'auForm.php';
  $google = new auForm('', 'http://www.google.com/custom', 'get');
  $google->AddData('cof', 'L:http://www.track7.org/t7logo.png;S:http://www.track7.org/;');
  $google->AddData('sitesearch', 'www.track7.org');
  $google->AddData('filter', 0);
  $google->AddField('q', 'google', 'text to search for', true, '', _AU_FORM_FIELD_NORMAL, 70);
  $google->AddButtons('search', 'search track7 using google');
  $google->WriteHTML($user->valid);

  $page->End();
?>
