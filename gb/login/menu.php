<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';

  if(isset($_SESSION['bookid'])) {
    $page->Start('guestbook management');
    if(isset($_GET['action'])) {
      switch($_GET['action']) {
        case 'chgpass':
          if(isset($_POST['submit'])) {
            $book = 'select pass from gbbooks where id=' . $_SESSION['bookid'];
            if($book = $db->GetRecord($book, 'error reading guestbook information', 'guestbook not found')) {
              $_POST['oldpass'] = md5($_POST['oldpass']);
              if($_POST['oldpass'] != $book->pass)
                $page->Error('incorrect password entered');
              elseif($_POST['newpass1'] != $_POST['newpass2'])
                $page->Error('new passwords don\'t match');
              else {
                $book = 'update gbbooks set pass=\'' . md5($_POST['newpass1']) . '\' where id=' . $_SESSION['bookid'];
                if(false !== $db->Change($book, 'error updating password'))
                  $page->Info('password changed successfully!');
              }
            }
          } else {
            $page->Heading('change password');
            $chgpass = new auForm('chgpass', '?action=chgpass');
            $chgpass->AddField('oldpass', 'old password', 'enter your current password', true, '', _AU_FORM_FIELD_PASSWORD, 15);
            $chgpass->AddField('newpass1', 'new password', 'enter your new password', true, '', _AU_FORM_FIELD_PASSWORD, 15);
            $chgpass->AddField('newpass2', 'confirm new password', 'enter your new password again to confirm', true, '', _AU_FORM_FIELD_PASSWORD, 15);
            $chgpass->AddButtons('submit', 'change your password');
            $chgpass->WriteHTML(true);
?>
      <hr />
<?
          }
          break;
        case 'chgemail':
          $book = 'select notify from gbbooks where id=' . $_SESSION['bookid'];
          if($book = $db->GetRecord($book, 'error reading guestbook information', 'guestbook not found')) {
            if(isset($_POST['submit'])) {
              switch($_POST['submit']) {
                case 'delete':
                  $email = explode(',', $book->notify);
                  $_POST['email'] = $_POST['email'];
                  $key = array_search($_POST['email'], $email);
                  if($key === false)
                    $page->Error('\'' . htmlspecialchars($_POST['email']) . '\' could not be deleted because it was not in the database.');
                  else {
                    unset($email[$key]);
                    $update = 'update gbbooks set notify=\'' . addslashes(implode(',', $email)) . '\' where id=' . $_SESSION['bookid'];
                    if(false !== $db->Change($update, 'error updating notification e-mail(s)'))
                      $page->Info('notification email(s) updated successfully:&nbsp; removed ' . $_POST['email']);
                  }
                  break;
                case 'add':
                  $_POST['email'] = strtolower($_POST['email']);
                  if(strpos($book->notify, $_POST['email']) !== false)
                    $page->Error('\'' . htmlspecialchars($_POST['email']) . '\' could not be added because it is already in the database.');
                  else {
                    $update = 'update gbbooks set notify=\'' . (strlen($book->notify) > 0 ? $book->notify . ',' : '') . addslashes($_POST['email']) . '\' where id=' . $_SESSION['bookid'];
                    if(false !== $db->Change($update, 'error updating notification e-mail(s)'))
                      $page->Info('notification email(s) updated successfully:&nbsp; added ' . $_POST['email']);
                  }
                  break;
              }
            } else {
              $page->Heading('change notification e-mail(s)');
              if(strlen($book->notify)) {
                $notify = new auForm('delemail', '?action=chgemail');
                $notify->AddSelect('email', 'e-mail address', 'choose an e-mail address to delete', auFormSelect::ArrayIndex(explode(',', $book->notify)));
                $notify->AddButtons('delete', 'remobe the selected e-mail from the notification list');
                $notify->WriteHTML(true);
              }
              $notify = new auForm('addemail', '?action=chgemail');
              $notify->AddField('email', 'new e-mail', 'enter an e-mail address to add to the notification list', true, '', _AU_FORM_FIELD_NORMAL, 25);
              $notify->AddButtons('add', 'add this e-mail address to the notification list');
              $notify->WriteHTML(true);
?>
      <hr />
<?
            }
          }
          break;
        case 'edithead':
          if(isset($_POST['submit'])) {
            $book = 'update gbbooks set header=\'' . addslashes($_POST['newhead']) . '\' where id=' . $_SESSION['bookid'];
            if(false !== $db->Change($book, 'unable to update guestbook header'))
              $page->Info('guestbook header updated successfully!');
          } else {
            $book = 'select header from gbbooks where id=' . $_SESSION['bookid'];
            if($book = $db->GetRecord($book, 'unable to read guestbook header')) {
              $page->Heading('update header');
              $head = new auForm('head', '?action=edithead');
              $head->AddField('newhead', 'heading', 'edit the beginning html for viewing your guestbook', true, $book->header, _AU_FORM_FIELLD_MULTILINE);
              $head->AddButtons('submit', 'save the beginning html');
              $head->WriteHTML(true);
?>
      <hr />
<?
            }
          }
          break;
        case 'editfoot':
          if(isset($_POST['submit'])) {
            $book = 'update gbbooks set footer=\'' . addslashes($_POST['newfoot']) . '\' where id=' . $_SESSION['bookid'];
            if(false !== $db->Change($book, 'unable to update guestbook footer'))
              $page->Info('guestbook footer updated successfully!');
          } else {
            $book = 'select footer from gbbooks where id=' . $_SESSION['bookid'];
            if($book = $db->GetRecord($book, 'unable to read guestbook footer', 'guestbook not found')) {
              $page->Heading('update footer');
              $foot = new auForm('foot', '?action=editfoot');
              $foot->AddField('newfoot', 'footer', 'edit the ending html for viewing your guestbook', true, $book->footer, _AU_FORM_FIELD_MULTILINE);
              $foot->AddButtons('submit', 'save the ending html');
              $foot->WriteHTML(true);
?>
      <hr />
<?
            }
          }
          break;
        case 'editbody':
          if(isset($_POST['submit'])) {
            $book = 'update gbbooks set entry=\'' . addslashes($_POST['newbody']) . '\' where id=' . $_SESSION['bookid'];
            if(false !== $db->Change($book, 'unable to update guestbook information'))
              $page->Info('guestbook entry format updated successfully!' . "\n      </p>\n      <p>\n        note that this change will only affect future entries.&nbsp; old entries\n        will need to be updated manually, or left as is.");
          } else {
            $book = 'select entry from gbbooks where id=' . $_SESSION['bookid'];
            if($book = $db->GetRecord($book, 'unable to read guestbook information', 'guestbook not found')) {
              $page->Heading('update information');
              $body = new auForm('body', '?action=editbody');
              $body->AddField('newbody', 'entry', 'enter the html to be used as a guestbook entry', true, $book->entry, _AU_FORM_FIELD_MULTILINE);
              $body->AddButtons('submit', 'save the entry html');
              $body->WriteHTML(true);
?>
      <hr />
<?
            }
          }
          break;
      }
    }
    showMenu();
    $page->End();
  } else
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/');

  function showMenu() {
?>
      <h2>account options</h2>
      <ul>
        <li><a href="<?=$_SERVER['PHP_SELF']; ?>?action=chgpass">change password</a></li>
        <li><a href="<?=$_SERVER['PHP_SELF']; ?>?action=chgemail">change notify e-mail(s)</a></li>
      </ul>
    
      <h2>display options</h2>
      <ul>
        <li><a href="<?=$_SERVER['PHP_SELF']; ?>?action=edithead">edit header</a></li>
        <li><a href="<?=$_SERVER['PHP_SELF']; ?>?action=editfoot">edit footer</a></li>
        <li><a href="<?=$_SERVER['PHP_SELF']; ?>?action=editbody">edit body</a></li>
        <!--li><a href="<?=$_SERVER['PHP_SELF']; ?>?action=resetbody">reset body</a></li-->
        <!--li><a href="<?=$_SERVER['PHP_SELF']; ?>?action=signform">get sign form</a></li-->
      </ul>
<?
  }
?>
