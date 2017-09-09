<?php
/**
 * process local login (local registration is discontinued)
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$auth = new t7authTrack7();

// if ID isn't false, the user was successfully logged in and the login command will redirect and end the script.
if($auth->ID)
	$user->Login('transition', $auth->ID, $auth->Remember, $auth->Continue);

$params = [];
if(isset($_GET['continue']))
	$params['continue'] = $_GET['continue'];
$html = new t7html($params);
$html->Open('local sign in failed');
?>
			<h1>local sign in failed</h1>
<?php
if(!$auth->HasData) {
?>
			<p>
				no username and password found.  maybe you found this page by accident?
				either that or the login form is broken, which would be bad.
			</p>
<?php
} elseif(!$auth->IsValid) {
?>
			<p>
				antiforgery information incorrect or missing.  it can expire if you
				leave the page open for a long time before logging in, so if maybe you
				did that, go ahead and just try it again.
			</p>
<?php
} elseif($auth->DBError) {
?>
			<p>
				we ran into some sort of problem reading the database. that doesn’t
				happen often, so it might work if you try again later.
			</p>
<?php
} else {
?>
			<p>
				most likely the reason we couldn’t sign you in is we don’t have the
				username and password you entered on file.  if you usually sign in with
				an account from another site you will need to select its icon and sign
				in through that site instead of using the track7 icon and entering your
				username and password here.
			</p>
<?php
}
$html->Close();
