<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';
  require_once 'auText.php';

  $page->start('regular expression testing');
?>
      <h2>t7code / t7uncode</h2>
<?
  $form = new auForm('t7code', null, 'post', true);
  $form->AddField('t7c', 't7code format', 'enter t7code to translate to html', false, strlen($_POST['t7c']) > 0 ? $_POST['t7c'] : auText::HTML2BB($_POST['t7u']), _AU_FORM_FIELD_BBCODE);
  $form->AddField('t7u', 'html format', 'enter html to translate to t7code', false, strlen($_POST['t7u']) > 0 ? $_POST['t7u'] : auText::BB2HTML($_POST['t7c']), _AU_FORM_FIELD_MULTILINE);
  $form->AddButtons('translate', 'fill in the blank field by translating the non-blank field');
  $form->WriteHTML(true);
?>
      <h2><a href="http://php.net/preg_replace">preg_replace()</a></h2>
<?
  $form = new auForm('preg_replace');
  if($_POST['submit'] == 'replace')
    $form->AddHTML('result', preg_replace($_POST['replacepattern'], $_POST['replacement'], $_POST['replacesubject']));
  $form->AddField('replacepattern', 'pattern', 'enter a regular expression to replace', true, $_POST['replacepattern']);
  $form->AddField('replacement', 'replacement', 'enter some text to replace the pattern with', true, $_POST['replacement']);
  $form->AddField('replacesubject', 'subject', 'enter a block of text to perform the replacement on', true, $_POST['replacesubject'], _AU_FORM_FIELD_MULTILINE);
  $form->AddButtons('replace', 'test the preg_replace() function');
  $form->WriteHTML(true);
?>
      <h2><a href="http://php.net/preg_match">preg_match()</a></h2>
<?
  if($_POST['submit'] == 'match') {
    if(preg_match($_POST['matchpattern'], $_POST['matchsubject'], $matches)) {
      echo '<p><pre>';
      print_r($matches);
      echo '</pre></p>';
    } else
      $page->Info('pattern not found.');
  }
  $form = new auForm('preg_match');
  $form->AddField('matchpattern', 'pattern', 'enter a regular expression to match', true, $_POST['matchpattern']);
  $form->AddField('matchsubject', 'subject', 'enter a block of text to perform the match on', true, $_POST['matchsubject'], _AU_FORM_FIELD_MULTILINE);
  $form->AddButtons('match', 'test the preg_match() function');
  $form->WriteHTML(true);

  $page->End();
?>
