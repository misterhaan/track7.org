<?
  require_once dirname(dirname($_SERVER['DOCUMENT_ROOT'])) . '/lib/track7.php';

  if(isset($_POST['return']) && $_POST['return'] == 'xml' && isset($_POST['submit']) && $_POST['submit'] == 'login') {
    header('Content-Type: text/xml; charset=utf-8');
    header('Cache-Control: no-cache');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    if($user->Valid) {
?>
<response result="success" />
<?
    } else {
?>
<response result="failure">
<?
      echo '<error>';
      echo $user->LoginMessage;
      echo "</error>\n";
?>
</response>
<?
    }
    die;
  }
  if(isset($_POST['submit']) && $_POST['submit'] == 'reset password') {
    $page->Start('reset password - login', 'reset password', 'track7 login');
    if(isset($_POST['email']))
      $user->ResetPassword(trim($_POST['login']), trim($_POST['email']));
    else {
?>
      <p>
        to confirm that you want to change your password (and that you didn’t
        just accidentally hit the wrong button!), please enter the e-mail
        address stored with your account.&nbsp; if you have not specified an
        e-mail address, you will not be able to reset your password and will
        need to <a href="http://www.track7.org/user/sendmessage.php?to=misterhaan">contact me</a> so i can
        manually reset your password.
      </p>
<?
      $emailform = new auForm('passwordreset');
      if($_POST['login']) {
        $emailform->AddData('login', $_POST['login']);
        $emailform->AddText('username', $_POST['login']);
      } else
        $emailform->AddField('login', 'username', 'enter your username', true, '', _AU_FORM_FIELD_NORMAL, 20, 32);
      $emailform->AddField('email', 'e-mail address', 'enter the e-mail address associated with this username to verify', true, '', _AU_FORM_FIELD_NORMAL, 30, 64);
      $emailform->AddButtons('reset password', 'reset your password');
      $emailform->WriteHTML();

      $page->End();
      die;
    }
  }
  $page->Start('login', 'track7 login');
  if($user->Valid)
    $page->Info('you are currently logged in as ' . $user->Name);
?>
      <p>
        if you have registered for a user account at track7, you can sign in
        here.&nbsp; if you don’t have a user account, you can
        <a href="http://www.track7.org/user/register.php">register</a> for one.
      </p>
      <p>
        forget your password?&nbsp; if you have entered an e-mail address, just
        fill in your username and click the reset password button to have your
        password changed and e-mailed to you.
      </p>

<?
  $login = new auForm('userlogin');
  $login->AddField('login', 'username', 'enter your username to either log in or reset your password', false, '', _AU_FORM_FIELD_NORMAL, 20, 32);
  $login->AddField('password', 'password', 'required only for logging in', false, '', _AU_FORM_FIELD_PASSWORD, 20);
  $login->AddField('remember', '', 'remember this information (sends a cookie)', false, false, _AU_FORM_FIELD_CHECKBOX);
  $login->AddButtons(array('login', 'reset password'), array('log in to track7', 'have your password reset and e-mailed to you'));
  if(isset($_POST['goback']) && strpos($_POST['goback'], '/logout.php') === false)
    $login->AddData('goback', $_POST['goback']);
  elseif(strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false && strpos($_SERVER['HTTP_REFERER'], '/logout.php') === false)
    $login->AddData('goback', strstr(substr($_SERVER['HTTP_REFERER'], 9), '/'));
  unset($_POST['password']);  // make sure password doesn't get sent with html
  $login->WriteHTML();

  $page->End();
?>
