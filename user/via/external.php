<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'auth.php';

class ExternalSignIn extends Page {
	protected static ?Auth $auth;
	protected static ?AuthResult $result;
	protected static ?DetailedException $error = null;

	public function __construct() {
		if (!isset($_GET['source']))
			self::NotFound();
		try {
			self::$auth = Auth::Provider(trim($_GET['source']));
		} catch (DetailedException) {
			self::NotFound();
		}

		try {
			self::$result = self::$auth->Process(self::RequireDatabase());
			self::HandleRedirect();
		} catch (DetailedException $e) {
			self::$unknownError = $e;
		}

		parent::__construct(self::$auth->Name . ' sign-in');  // to show either registration form or a problem
	}

	protected static function MainContent(): void {
?>
		<h1><?= self::$auth->Name; ?> sign-in results</h1>
	<?php
		if (self::$error)
			self::ShowError();
		if (!self::$result)
			self::ShowNoAuthResult();
		if (!self::$result->IsValid)
			self::ShowInvalidResult();

		if (self::IsUserLoggedIn())
			if (self::$result->LoginMatch->UserID == self::$user->ID)
				self::ShowAlreadyLinkedError();
			else
				self::ShowLinkedToSomeoneElseError();
		else
			self::ShowRegistrationForm();
	}

	private static function HandleRedirect(): void {
		if (self::$result && self::$result->IsValid && self::$result->User) {
			if (!self::IsUserLoggedIn() && self::$result->LoginMatch)
				self::LoginAndRedirect();  // successful login
			if (self::IsUserLoggedIn() && !self::$result->LoginMatch)
				self::AddLoginAndRedirect();  // successful addition of login method
		}
		if (self::$result && self::$result->IsValid && !self::$result->User) {
			print_r(self::$result);
			die;
			self::Redirect(self::$result->Continue);  // canceled login
		}
	}

	private static function LoginAndRedirect(): void {
		require_once 'user.php';
		$user = CurrentUser::Login(self::RequireDatabase(), self::$auth->Name, self::$result->LoginMatch->UserID, self::$result->Remember);
		if ($user->IsLoggedIn()) {  // should be true after previous line
			if (self::$result->LoginMatch->Name != self::$result->User->DisplayName || self::$result->LoginMatch->URL != self::$result->User->ProfileURL || $avatarChanged = self::$result->LoginMatch->Avatar != self::$result->User->Avatar) {
				$user->UpdateLogin(self::RequireDatabase(), self::$auth->Name, self::$result->LoginMatch->ID, self::$result->User->DisplayName, self::$result->User->ProfileURL, self::$result->User->Avatar);
				if (self::$result->LoginMatch->LinkAvatar && $avatarChanged && self::$result->User->Avatar)
					$user->UpdateAvatar(self::RequireDatabase(), self::$result->User->Avatar);
			}
			self::Redirect(self::$result->Continue);
		}
	}

	private static function AddLoginAndRedirect(): void {
		self::$user->AddLogin(self::RequireDatabase(), self::$auth->Name, self::$result->User->ID, self::$result->User->DisplayName, self::$result->User->ProfileURL, self::$result->User->Avatar);
		if (self::$result->User->ProfileURL) {
			require_once 'contact.php';
			ContactLink::Add(self::RequireDatabase(), self::$user->ID, self::$auth->Name, self::$result->User->ProfileURL);
		}
		self::Redirect(self::$result->Continue);
	}

	private static function ShowError(): void {
	?>
		<p>
			oops, <?= self::$auth->Name; ?> told us something, but either we couldn’t
			understand it or we ran into an error trying to figure out if you’ve been
			here before. it might work if you wait a while and try again (if <?= self::$auth->Name; ?>
			goes back to making sense), or if you’ve linked a login account from a
			different site that might be working. if none of that works, you should
			tell <a href="/user/misterhaan/" title="go to misterhaan’s profile for contact information">misterhaan</a>.
		</p>
		<p class=error><?= self::$error->Message; ?></p>
	<?php
	}

	private static function ShowNoAuthResult(): void {
	?>
		<p class=error>
			no authentication data found! that’s not supposed to happen!
		</p>
	<?php
	}

	private static function ShowInvalidResult(): void {
	?>
		<p>
			oops, there’s something wrong with your authentication data. sometimes
			that happens if you wait too long on the <?= self::$auth->Name; ?>
			sign in page. if that sounds like you, just try again.
		</p>
	<?php
	}

	private static function ShowAlreadyLinkedError(): void {
	?>
		<p>
			this <?= self::$auth->Name; ?> account is already linked to your
			track7 account. maybe you meant to
			<a href="/user/settings.php#linkedaccounts">link a different <?= self::$auth->Name; ?> account</a>?
		</p>
	<?php
	}

	private static function ShowLinkedToSomeoneElseError(): void {
	?>
		<p>
			this <?= self::$auth->Name; ?> account is linked to track7, but not
			for who you’re currently signed in as. if you want to link this <?= self::$auth->Name; ?>
			account to <?= htmlspecialchars(self::$user->DisplayName); ?> then
			things are a bit complicated — you probably want to ask <a href="/user/misterhaan/" title="go to misterhaan’s profile for contact information">misterhaan</a>
			to merge things on the track7 side. if you’re trying to sign in with
			this <?= self::$auth->Name; ?> account but not as
			<?= htmlspecialchars(self::$user->DisplayName); ?> then you’ll need to
			sign out first (from the menu in the upper right).
		</p>
	<?php
	}

	private static function ShowRegistrationForm(): void {
		$_SESSION['registering'] = [
			'provider' => self::$auth->Name,
			'id' => self::$result->User->ID,
			'username' => self::$result->User->Username,
			'email' => self::$result->User->Email,
			'website' => self::$result->User->Website,
			'displayname' => self::$result->User->DisplayName,
			'avatar' => self::$result->User->Avatar,
			'profile' => self::$result->User->ProfileURL,
			'remember' => self::$result->Remember,
			'continue' => self::$result->Continue
		];
	?>
		<p>
			welcome to track7! according to our records, you haven’t signed in with
			this <?= self::$auth->Name; ?> account before. if you <em>have</em>
			signed in to track7 before, maybe you used a different account — you can
			try signing in again with that account and then add this <?= self::$auth->Name; ?>
			account as another sign-in option. if you are new, we’ve filled in some
			information based on your <?= self::$auth->Name; ?> profile. change
			it if you like, then enjoy track7 as a signed-in actual person!
		</p>

		<h2>profile information</h2>
		<div id=newuser></div>
<?php
	}
}
new ExternalSignIn();
