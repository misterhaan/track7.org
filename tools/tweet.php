<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(!$user->GodMode)
    $page->Show404();

  $page->Start('auSend::Tweet tester');

  $page->Info('anything entered into this form gets sent to <a href="http://twitter.com/track7feed">twitter</a>, so remember to delete test tweets.');

  $frm = new auForm('tweettest');
  $frm->Add(new auFormString('message', 'message', 'enter a message to tweet'));
  $frm->Add(new auFormButtons('tweet', 'send this message to twitter'));
  $frm->WriteHTML(true);
  if($frm->Submitted()) {
    $response = auSend::Tweet($_POST['message']);
    $page->Heading('Response Code ' . $response->code);
    echo auText::BB2HTML($response->text, false, false);
  }

  $page->End();
?>
