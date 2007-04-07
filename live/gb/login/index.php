<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';

  if(isset($_POST['book'])) {
    $_POST['pass'] = md5($_POST['pass']);
    $chk = 'select id, pass from gbbooks where name=\'' . addslashes($_POST['book']) . '\'';
    if($chk = $db->GetRecord($chk, 'error trying to look up guestbook', 'guestbook not found')) {
      if($_POST['pass'] != $chk->pass) {
        $page->Error('sorry, the password you entered is incorrect.');
        $_GET['book'] = $_POST['book'];
      } else {
        $_SESSION['bookid'] = $chk->id;
        header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/menu.php');
        die;
      }
    }
  }

  $page->Start('guestbook login', 'log in to manage guestbook');
  $login = new auForm('gblogin');
  $login->AddField('book', 'name', 'enter the name used to register your guestbook', true, $_GET['book'], _AU_FORM_FIELD_NORMAL, 10, 15);
  $login->AddField('pass', 'password', 'enter the password for your guestbook', true, '', _AU_FORM_FIELD_PASSWORD, 10);
  $login->AddButtons('login', 'log in to get the guestbook management menu');
  $login->WriteHTML(true);

  $page->End();
?>
