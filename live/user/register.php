<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';
  $reg = $user->GetRegistrationForm();

  $page->Start('register', 'track7 registration');

  if($reg->Submitted) {
    if($user->RegisterMessage == _AU_USER_REGISTER_SUCCESS) {
?>
      <p>
        you have successfully registered '<?=$user->Name; ?>' and are now
        logged in!&nbsp; if you entered an e-mail address, an e-mail has been
        sent with your login details.&nbsp; you may want to go
        <a href="editprofile.php">fill out your profile</a>.&nbsp; next time you
        visit track7 you will need to <a href="login.php">log in</a>, at which
        point you can have a cookie sent to your browser so that you will be
        logged in automatically.
      </p>
<?
      $page->End();
      die;
    } else
      $page->Error('user registration failed', strtolower($user->RegisterMessage));
  }
  if($user->Valid)
    $page->Info('you are currently logged in as ' . $user->Name);
?>
      <p>
        register for an account at track7 to enjoy certain <a href="/user/">benefits</a>
      </p>

<?
  unset($_POST['pass1'], $_POST['pass2']);  // clear the passwords so they don't get written out in the form
  $reg->WriteHTML();

  $page->End();
?>
