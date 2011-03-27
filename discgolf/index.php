<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('disc golf');

  if($golfer = $user->Valid) {
    $golfer = 'select discs+rounds as golfer from userstats where uid=' . $user->ID;
    $golfer = $db->GetValue($golfer, 'error looking up user statistics', 'user statistics not found', true);
    if($golfer) {
?>
      <p>
        welcome back to the disc golf section!
<?
    } else {
?>
      <p>
        welcome to the disc golf section!&nbsp; we don’t have any disc golf
        information for you yet, so suggest you make sure your local courses are
        listed (add them if they’re not), post your disc collection, print score
        sheets for your favorite courses, or start entering your scores.
<?
    }
?>
      </p>

      <ul>
        <li><a id="addroundlink" href="courses.php?addround">enter a round</a></li>
        <li><a href="players.php?p=<?=$user->Name; ?>">your player profile</a></li>
        <li><a href="rounds.php?player=<?=$user->Name; ?>">your rounds</a></li>
        <li><a href="caddy.php?player=<?=$user->Name; ?>">your discs</a></li>
<?
  } else {
?>
      <p>
        welcome to the disc golf section!&nbsp; since you’re not logged in you
        can’t post your disc collection or enter your scores, but you can view
        courses, disc information, and disc collections and scores entered by
        others.  <a id="messageloginlink" href="/user/login.php">log in</a> or
        <a href="/user/register.php">register</a> for more options.
      </p>

      <ul>
<?
  }
?>
        <li><a href="courses.php">courses</a></li>
        <li><a href="discs.php">disc information</a></li>
        <li><a href="players.php">players</a></li>
      </ul>

<?
  $page->End();
?>
