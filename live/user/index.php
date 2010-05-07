<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->start('user functions', 'track7 user functions');

  if($user->Valid) {
?>
      <p>
        you are currently logged in as <?=$user->Name; ?>.
      </p>

      <ul>
        <li><a href="message.php">check your messages</a></li>
        <li><a href="<?=$user->Name; ?>/">view your profile</a></li>
        <li><a href="profile.php">edit your profile</a></li>
        <li><a href="logout.php">log out</a></li>
        <li><a href="list.php">user list</a></li>
      </ul>

<?
  } else {
?>
      <p>
        you are not currently logged in.
      </p>
      <ul>
        <li><a id="messageloginlink" href="login.php">log in</a></li>
        <li><a href="register.php">register</a></li>
        <li><a href="list.php">user list</a></li>
      </ul>

<?
  }
?>
      <hr class="minor" />

      <p>
        currently a user account will do the following for you (provided you are
        logged in):
      </p>
      <ul>
        <li>show a profile page which can show any of the following contact information<ul>
          <li>your e-mail address (doesn't have to be shown even if you provide one)</li>
          <li>your website url</li>
          <li>your icq uin</li>
          <li>your aim screen name</li>
          <li>your jabber id</li>
        </ul></li>
        <li>allow you to edit your posts and comments</li>
        <li>allow users and other visitors to send you messages through track7</li>
        <li>automatically enter your name and link to your profile when you post comments</li>
        <li>allow you to enter disc golf scores to be displayed on track7</li>
      </ul>

<?
  $page->End();
?>
