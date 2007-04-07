<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';
  require_once 'auText.php';

  if(!$user->GodMode) {
    $page->Show404();
    die;
  }
  if(isset($_GET['newcat'])) {
    if(isset($_POST['submit'])) {
      $cats = 'show columns from bln like \'cat\'';
      if($cats = $db->Get($cats, 'error reading possible category values', 'category column missing', true)) {
        $cats = $cats->NextRow();
        $cats = $cats->Type;
        $cats = str_replace(')', ',\'' . addslashes($_POST['cat']) . '\')', $cats);
        $alter = 'alter table bln change cat cat ' . $cats;
        if(false !== $db->Change($alter, 'error adding new category')) {
          header('Location: http://' . $_SERVER['HTTP_HOST'] . '/pen/bln/');
          die;
        }
      }
    }
    $page->Start('add a category - bln', 'add a category', 'bln (a natural blog)');
    $form = new auForm('addcat', '?newcat');
    $form->AddField('cat', 'category name', 'enter a name for the new category', true, '', _AU_FORM_FIELD_NORMAL, 15);
    $form->AddButtons('add', 'add this category to bln');
    $form->WriteHTML(true);
    $page->End();
  } elseif(isset($_GET['newentry'])) {
    if(isset($_POST['submit'])) {
      $chk = 'select 1 from bln where name=\'' . addslashes($_POST['name']) . '\'';
      if($chk = $db->Get($chk, 'error checking to see if name already in use', '')) {
        $page->Error('name already in use');
      } else {
        $ins = 'insert into bln (name, instant, cat, title, post) values (\'' . addslashes($_POST['name']) . '\', ' . time() . ', \'' . addslashes($_POST['cat']) . '\', \'' . addslashes($_POST['title']) . '\', \'' . addslashes(auText::BB2HTML($_POST['post'])) . '\')';
        if(false !== $db->Put($ins, 'error saving entry')) {
          header('Location: http://' . $_SERVER['HTTP_HOST'] . '/pen/bln/' . $_POST['cat'] . '/' . $_POST['name']);
          die;
        }
      }
    }
    $page->Start('add an entry - bln', 'add an entry', 'bln (a natural blog)');
    $cats = 'show columns from bln like \'cat\'';
    if($cats = $db->Get($cats, 'error reading possible category values', 'category column missing', true)) {
      $cats = $cats->NextRecord();
      $cats = explode('\',\'', substr($cats->Type, 6, -2));
      sort($cats);
      $form = new auForm('newentry', '?newentry');
      $form->AddField('name', 'name', 'name to use in url; must be unique', true, '', _AU_FORM_FIELD_NORMAL, 20, 32);
      $form->AddField('title', 'title', 'title to display on the page', true, '', _AU_FORM_FIELD_NORMAL, 50, 128);
      $form->AddSelect('cat', 'category', 'the category this entry belongs to', auFormSelect::ArrayIndex($cats), $_GET['newentry']);
      $form->AddField('post', 'entry', 'the text of this entry (t7code)', true, '', _AU_FORM_FIELD_BBCODE, 10);
      $form->AddButtons('add', 'add this entry to bln');
      $form->WriteHTML(true);
    }
    $page->End();
  }
?>
