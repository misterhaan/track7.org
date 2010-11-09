<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(!$user->GodMode)
    $page->Show404();

  $page->Start('auSend::Bitly tester');

  //$page->Info('anything entered into this form gets sent to <a href="http://twitter.com/track7feed">twitter</a>, so remember to delete test tweets.');

  $frm = new auForm('bitlytest');
  $frm->Add(new auFormString('url', 'url', 'enter a url to shorten'));
  $frm->Add(new auFormButtons('shorten', 'shorten this url with bit.ly'));
  $frm->WriteHTML(true);
  if($frm->Submitted()) {
    $url = auSend::Bitly($_POST['url']);
    $page->Heading('Shortened URL');
    $hurl = htmlspecialchars($url);
?>
      <p><a href="<?=$hurl; ?>"><?=$hurl; ?></a></p>
<?
  }

  $page->End();
?>
