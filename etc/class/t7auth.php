<?php
class t7auth {
	/**
	 * gets a cross-site request forgery token.  will be created the first time.
	 * @return string random 32-character hexadecimal
	 */
	public static function GetCSRF() {
		if (!isset($_SESSION['CSRF']))
			$_SESSION['CSRF'] = bin2hex(openssl_random_pseudo_bytes(16));
		return $_SESSION['CSRF'];
	}

	/**
	 * check a returned value against the stored cross-site request forgery
	 * token.  the stored token will be deleted as part of the check.
	 * @param string $csrf returned value from the cross-site request.
	 * @return boolean whether the returned value matches the stored value
	 */
	public static function CheckCSRF($csrf) {
		if (!isset($_SESSION['CSRF']))
			return false;
		$stored = $_SESSION['CSRF'];
		unset($_SESSION['CSRF']);
		return $csrf == $stored;
	}

	/**
	 * Get the list of supported external authenticators.
	 * @return string[] array of authenticator source names.
	 */
	public static function GetAuthList() {
		return [
			t7authGoogle::SOURCE,
			t7authTwitter::SOURCE,
			t7authGithub::SOURCE,
			t7authDeviantart::SOURCE,
			t7authSteam::SOURCE
		];
	}

	/**
	 * Check if the specified authentication source is supported.
	 * @param string $source authenticator source name (must match SOURCE constant of a t7authRegisterable.
	 * @return boolean true if source is supported.
	 */
	public static function IsKnown($source) {
		return in_array($source, self::GetAuthList());
	}

	/**
	 * get links for external authentication.
	 * @param string $continue track7 url to continue to after visiting the external site
	 * @param boolean $adding whether the authentication is being added to an existing account
	 * @return array external authentication links, in a named-index array
	 */
	public static function GetAuthLinks($continue, $adding = false) {
		$csrf = self::GetCSRF();
		$links = [];
		$links['google'] = t7authGoogle::GetAuthURL($continue, $csrf);
		$links['twitter'] = t7authTwitter::GetAuthURL($continue);
		$links['github'] = t7authGithub::GetAuthUrl($continue, $csrf);
		$links['deviantart'] = t7authDeviantart::GetAuthUrl($continue, $csrf);
		$links['steam'] = t7authSteam::GetAuthUrl($continue, $csrf);
		if (!$adding)
			$links['track7'] = t7authTrack7::GetAuthURL($continue, $csrf);
		return $links;
	}

	/**
	 * get the name of the external id field for the specified login source.
	 * @param string $source SOURCE constant value matching a t7auth class.
	 * @return string name of the external id field of the login database for the specified source.
	 */
	public static function GetField($source) {
		return [
			t7authGoogle::SOURCE => t7authGoogle::FIELD,
			t7authTwitter::SOURCE => t7authTwitter::FIELD,
			t7authGithub::SOURCE => t7authGithub::FIELD,
			t7authDeviantart::SOURCE => t7authDeviantart::FIELD,
			t7authSteam::SOURCE => t7authSteam::FIELD
		][$source];
	}

	/**
	 * create the authorization object for the specified source.
	 * @param string $source SOURCE constant value matching a t7auth class.
	 */
	public static function MakeExternalAuth($source) {
		switch ($source) {
			case t7authGoogle::SOURCE:
				return new t7authGoogle();
			case t7authTwitter::SOURCE:
				return new t7authTwitter();
			case t7authGithub::SOURCE:
				return new t7authGithub();
			case t7authDeviantart::SOURCE:
				return new t7authDeviantart();
			case t7authSteam::SOURCE:
				return new t7authSteam();
		}
		return false;
	}

	/**
	 * login or register to track7 based on authentication from an external site.
	 * @param t7authRegisterable $auth account information from an external site.
	 */
	public static function LoginRegister(t7authRegisterable $auth) {
		global $db, $html, $user;
		if ($auth->HasData)
			if ($auth->IsValid)
				if ($auth->ID)
					if ($finduser = $db->query('select user from login_' . $auth::SOURCE . ' where ' . $auth::FIELD . '=\'' . $db->escape_string($auth->ID) . '\' limit 1'))
						if ($finduser = $finduser->fetch_object())
							if ($user->IsLoggedIn()) // adding a known login
								if ($user->ID == $finduser->user) { // adding a login that was already added
									self::OpenPage($auth::SOURCE);
?>
			<p>
				this <?= $auth::SOURCE; ?> account is already linked to your
				track7 account. maybe you meant to <a href="/user/settings.php#linkedaccounts">link
					a different <?= $auth::SOURCE; ?> account</a>?
			</p>
		<?php
								} else { // adding a login that's already linked to a different account
									self::OpenPage($auth::SOURCE);
		?>
			<p>
				this <?= $auth::SOURCE; ?> account is linked to track7, but not
				for who you’re currently signed in as. if you want to link this <?= $auth::SOURCE; ?>
				account to <?= htmlspecialchars($user->DisplayName); ?> then
				things are a bit complicated — you probably want to ask <a href="/user/misterhaan/" title="go to misterhaan’s profile for contact information">misterhaan</a>
				to merge things on the track7 side. if you’re trying to sign in with
				this <?= $auth::SOURCE; ?> account not as <?= htmlspecialchars($user->DisplayName); ?>
				then you’ll need to sign out first (from the menu in the upper right).
			</p>
		<?php
								}
							else { // logging in
								$user->Login($auth::SOURCE, $auth);
								die;
							}
						else // account not linked to track7
							if ($auth->GetUserInfo())
								if ($user->IsLoggedIn()) { // link new account to user
									$db->autocommit(false);  // don't create external profile or login unless both get created
									if ($db->real_query('insert into external_profiles (name, url, avatar) values (\'' . $db->escape_string($auth->DisplayName) . '\', \'' . $db->escape_string($auth->ProfileFull) . '\', \'' . $db->escape_string($auth->Avatar) . '\')')) {
										$pid = $db->insert_id;
										if ($db->real_query('insert into login_' . $auth::SOURCE . ' (user, ' . $auth::FIELD . ', profile) values (\'' . +$user->ID . '\', \'' . $db->escape_string($auth->ID) . '\', \'' . +$pid . '\')')) {
											if ($auth->ProfileShort)
												$db->real_query('replace into contact (user, type, contact) values (\'' . +$user->ID . '\', \'' . $auth::SOURCE . '\', \'' . $db->escape_string($auth->ProfileShort) . '\')');
											$db->commit();
											header('Location: ' . t7format::FullUrl($auth->Continue));
											die;
										}
									}
									$db->autocommit(true);
									// error linking account
									self::OpenPage($auth::SOURCE);
		?>
			<p>
				oops, we couldn’t link your <?= $auth::SOURCE; ?> account for
				signing into track7. generally if you see this you should tell
				<a href="/user/misterhaan" title="go to misterhaan’s profile for contact information">misterhaan</a>.
			</p>
		<?php
								} else { // show registration form
									// TODO:  check if e-mail is already linked (might be handled by javascript later instead)
									$_SESSION['registering'] = $auth::SOURCE;
									$_SESSION[$auth::SOURCE] = [$auth::FIELD => $auth->ID, 'name' => $auth->DisplayName, 'avatar' => $auth->Avatar, 'profile' => $auth->ProfileFull, 'remember' => $auth->Remember, 'continue' => $auth->Continue];
									self::OpenPage($auth::SOURCE);
		?>
			<p>
				welcome to track7! according to our records, you haven’t signed in with
				this <?= $auth::SOURCE; ?> account before. if you <em>have</em>
				signed in to track7 before, maybe you used a different account — you can
				try signing in again with that account and then add this <?= $auth::SOURCE; ?>
				account as another sign-in option. if you are new, we’ve filled in some
				information based on your <?= $auth::SOURCE; ?> profile. change
				it if you like, then enjoy track7 as a signed-in actual person!
			</p>

			<h2>profile information</h2>
			<form id=newuser>
				<input type=hidden id=csrf value="<?= t7auth::GetCSRF(); ?>">
				<label>
					<span class=label>username:</span>
					<span class=field><input id=username maxlength=32 required value="<?= htmlspecialchars($auth->Username); ?>"></span>
					<span class=validation></span>
				</label>
				<label>
					<span class=label>display name:</span>
					<span class=field><input id=displayname maxlength=32 value="<?= htmlspecialchars($auth->DisplayName); ?>"></span>
					<span class=validation></span>
				</label>
				<label>
					<span class=label>e-mail:</span>
					<span class=field><input id=email maxlength=64 value="<?= htmlspecialchars($auth->Email); ?>"></span>
					<span class=validation></span>
				</label>
				<label>
					<span class=label>website:</span>
					<span class=field><input id=website maxlength=64 value="<?= htmlspecialchars($auth->Website); ?>"></span>
					<span class=validation></span>
				</label>
				<?php
									if ($auth->ProfileShort) {
				?>
					<label>
						<span class=checkbox><input type=checkbox checked id=linkprofile> link <a href="<?= htmlspecialchars($auth->ProfileFull); ?>">this profile</a> as your <?= $auth::SOURCE; ?> profile</span>
					</label>
				<?php
									}
				?>
				<label>
					<span class=checkbox><input type=checkbox checked id=useavatar> use this profile picture: <img class=avatar src="<?= htmlspecialchars($auth->Avatar); ?>"></span>
				</label>
				<button>confirm</button>
			</form>
		<?php
								}
							else { // couldn't get user info from account
								self::OpenPage($auth::SOURCE);
		?>
			<p>
				oops, we couldn’t get any information about that <?= $auth::SOURCE; ?>
				account. this generally shouldn’t happen unless <?= $auth::SOURCE; ?>
				goes down between logging in and looking up account information.
				generally if you see this you should tell <a href="/user/misterhaan/" title="go to misterhaan’s profile for contact information">misterhaan</a>.
			</p>
		<?php
							}
					else { // error checking if login is known
						self::OpenPage($auth::SOURCE);
		?>
			<p>
				hey, so <?= $auth::SOURCE; ?> told us who you are, but when we
				tried to check if you’d been here before something went wrong.
				generally if you see this you should tell <a href="/user/misterhaan/" title="go to misterhaan’s profile for contact information">misterhaan</a>.
			</p>
		<?php
					}
				else { // didn't get a subscriber ID, so they probably changed their mind.  go back to the previous page
					header('Location: ' . t7format::FullUrl($auth->Continue));
					die;
				}
			else { // continuity data didn't match up
				self::OpenPage($auth::SOURCE);
		?>
			<p>
				oops, there's something wrong with your authentication data. sometimes
				that happens if you leave track7 open for a while without clicking any
				links and then try to sign in, or if you wait too long on the <?= $auth::SOURCE; ?>
				sign in page. if that sounds like you, just try again.
			</p>
		<?php
			}
		else { // state data missing
			self::OpenPage($auth::SOURCE);
		?>
			<p>
				no authentication data found!&nbsp; maybe you need to
				<a href="<?= $auth::GetAuthURL('/', self::GetCSRF()); ?>">sign in with <?= $auth::SOURCE; ?></a>?
			</p>
		<?php
		}
		$html->Close();
	}

	private static function OpenPage($source) {
		global $html;
		if (isset($html))
			return;
		$html = new t7html(['vue' => true]);
		$html->Open($source . ' sign-in');
		?>
		<h1><?= $source; ?> sign-in results</h1>
<?php
	}
}

/**
 * base class for authorizations that can be registered to track7
 * @author misterhaan
 */
abstract class t7authRegisterable {
	/**
	 * @var boolean whether authorization data has been provided.
	 */
	public $HasData = false;
	/**
	 * @var boolean whether provided authorization data has been validated.
	 */
	public $IsValid = false;
	/**
	 * @var mixed id on the provider side.
	 */
	public $ID = false;

	/**
	 * @var string local url to redirect to on success (not from provider site).
	 */
	public $Continue = false;
	/**
	 * @var bool whether this login should be remembered for future sessions (originates from local login form).
	 */
	public $Remember = false;

	// any of this group will remain false if the auth doesn't support them
	/**
	 * @var string full url to the profile of this account.
	 */
	public $ProfileFull = false;
	/**
	 * @var string unique portion of the profile of this account.
	 */
	public $ProfileShort = false;
	/**
	 * @var string url to the avatar of this account.
	 */
	public $Avatar = false;
	/**
	 * @var string suggested username from this account.
	 */
	public $Username = false;
	/**
	 * @var string suggested display name from this account.
	 */
	public $DisplayName = false;
	/**
	 * @var string e-mail address associated with this account.
	 */
	public $Email = false;
	/**
	 * @var string website associated with this account.
	 */
	public $Website = false;

	/**
	 * generate an authorization url for this provider.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash).
	 * @param string $csrf random string for antiforgery (should be saved for comparison against response).
	 * @return string url for logging in with this provider.
	 */
	public static abstract function GetAuthUrl($continue, $csrf);
	/**
	 * get more user info to register this user here.
	 * @return boolean true if able to retrieve.
	 */
	public abstract function GetUserInfo();
}

/**
 * authorization using google openid connect
 * @author misterhaan
 */
class t7authGoogle extends t7authRegisterable {
	const SOURCE = 'google';
	const FIELD = 'sub';
	const REDIRECT = '/user/via/google.php';
	const REQUEST = 'https://accounts.google.com/o/oauth2/v2/auth';
	const SCOPE = 'openid email profile';
	const VERIFY = 'https://oauth2.googleapis.com/token';
	const INFO = 'https://openidconnect.googleapis.com/v1/userinfo';  // not used since that data is already in the id token

	private $id_token;

	/**
	 * build the url for logging in with google, with forgery protection and
	 * which page to return to built in.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash)
	 * @param string $csrf random string for antiforgery (should be saved for comparison against response)
	 * @return string url for logging in with google
	 */
	public static function GetAuthURL($continue, $csrf) {
		return self::REQUEST . '?' . http_build_query(array(
			'client_id' => t7keysGoogle::ID,
			'redirect_uri' => t7format::FullUrl(self::REDIRECT),
			'response_type' => 'code',
			'scope' => self::SCOPE,
			'state' => 'remember&' . http_build_query(array('continue' => $continue, 'csrf' => $csrf))
		));
	}

	/**
	 * handle authentication from google.  this class should only be
	 * instantiated by the page specified in self::REDIRECT.  the querystring is
	 * expected to be set by google after a login attempt.
	 */
	public function __construct() {
		if ($this->HasData = isset($_GET['state'])) {
			parse_str($_GET['state'], $state);
			if (isset($state['continue']))
				$this->Continue = $state['continue'];
			if ($this->IsValid = (isset($state['csrf']) && t7auth::CheckCSRF($state['csrf']))) {
				$this->Remember = isset($state['remember']);
				$this->GetTokens($_GET['code']);
			}
		}
	}

	/**
	 * pass the code from google login back to google over a trusted connection
	 * to retrieve the access and id tokens.
	 * @param string $code value returned by google login
	 */
	private function GetTokens($code) {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::VERIFY);
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['SERVER_NAME']);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query(array(
			'code' => $code,
			'client_id' => t7keysGoogle::ID,
			'client_secret' => t7keysGoogle::SECRET,
			'redirect_uri' => t7format::FullUrl(self::REDIRECT),
			'grant_type' => 'authorization_code'
		)));
		$response = curl_exec($c);
		curl_close($c);
		$response = json_decode($response);
		if (isset($response->access_token) && isset($response->id_token)) {
			$this->access = $response->access_token;
			$id = explode('.', $response->id_token);
			$id = json_decode(base64_decode($id[1]));
			$this->ID = $id->sub;
			$this->id_token = $id;
		}
	}

	/**
	 * get more user info to register this user here.
	 * @return boolean true if able to retrieve.
	 */
	public function GetUserInfo() {
		if ($this->id_token) {
			if (isset($this->id_token->profile)) {
				$this->ProfileFull = $this->id_token->profile;
				$this->ProfileShort = t7user::CollapseProfileLink($this->id_token->profile, self::SOURCE);
			}
			if (isset($this->id_token->picture))
				$this->Avatar = $this->id_token->picture . '?sz=64';
			if (isset($this->id_token->email)) {
				$this->Username = explode('@', $this->id_token->email)[0];
				$this->Email = $this->id_token->email;
			}
			if (isset($this->id_token->name))
				$this->DisplayName = $this->id_token->name;
			// unused:  gender
			return true;
		}
		return false;
	}
}

/**
 * authorization using twitter openid connect
 * @author misterhaan
 */
class t7authTwitter extends t7authRegisterable {
	const SOURCE = 'twitter';
	const FIELD = 'user_id';
	const REDIRECT = '/user/via/twitter.php';
	const REQUEST = 'https://api.twitter.com/oauth/request_token';
	const AUTHENTICATE = 'https://api.twitter.com/oauth/authenticate';
	const ACCESS = 'https://api.twitter.com/oauth/access_token';
	const VERIFY = 'https://api.twitter.com/1.1/account/verify_credentials.json';

	private $access = false;

	/**
	 * get the url for logging in with twitter, with the page to return to built
	 * in.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash)
	 * @return string url for logging in with twitter
	 */
	public static function GetAuthURL($continue, $csrf = null) {
		return self::REDIRECT . '?startauth&remember&continue=' . urlencode($continue);
	}

	/**
	 * handle authentication from twitter.  this class should only be
	 * instantiated by the page specified in self::REDIRECT.  the querystring is
	 * expected to be set by twitter after a login attempt or from a login / add
	 * account link.
	 */
	public function __construct() {
		if (isset($_GET['startauth'])) {
			if (isset($_GET['continue']))
				$_SESSION['twitter_continue'] = $_GET['continue'];
			$_SESSION['twitter_remember'] = isset($_GET['remember']);
			$this->Authenticate();
		}
		if ($this->HasData = (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier'])))
			if ($this->IsValid = (isset($_SESSION['twitter_pre_token']) && $_SESSION['twitter_pre_token'] == $_GET['oauth_token'])) {
				$this->Continue = $_SESSION['twitter_continue'];
				unset($_SESSION['twitter_continue']);
				$this->Remember = $_SESSION['twitter_remember'];
				unset($_SESSION['twitter_remember']);
				$this->GetTokens($_GET['oauth_verifier']);
			}
	}

	/**
	 * obtain a token and use it to request authentication through twitter.com.
	 */
	private function Authenticate() {
		// collect and sign oauth data
		// twitter documentation says i need to include oauth_callback set to %-encoded fully-qualified callback url, but i get an error if i include it
		$oauth = [
			'oauth_consumer_key' => t7keysTwitter::CONSUMER_KEY,
			'oauth_nonce' => md5(microtime() . mt_rand()),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_version' => '1.0'
		];
		ksort($oauth);
		$sig = 'POST&' . rawurlencode(self::REQUEST) . '&' . rawurlencode(http_build_query($oauth, null, '&', PHP_QUERY_RFC3986));
		$oauth['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $sig, t7keysTwitter::CONSUMER_SECRET . '&', true)));
		ksort($oauth);

		// quote all oauth variables for the authorization header
		$header = array();
		foreach ($oauth as $var => $val)
			$header[] = $var . '="' . $val . '"';

		// send the request
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::REQUEST);
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['SERVER_NAME']);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . implode(', ', $header), 'Content-length: 0']);
		$response = curl_exec($c);
		curl_close($c);

		parse_str($response, $tokens);  // should set oauth_token, oauth_token_secret, and oauth_callback_confirmed
		if (isset($tokens['oauth_callback_confirmed']) && $tokens['oauth_callback_confirmed'] == 'true') {
			$_SESSION['twitter_pre_token'] = $tokens['oauth_token'];
			$_SESSION['twitter_pre_token_secret'] = $tokens['oauth_token_secret'];
			header('Location: ' . self::AUTHENTICATE . '?oauth_token=' . $tokens['oauth_token']);
			die;
		}
	}

	/**
	 * pass the code from google login back to google over a trusted connection
	 * to retrieve the access and id tokens.
	 * @param string $code value returned by google login
	 */
	private function GetTokens($verifier) {
		// collect and sign oauth data
		$oauth = [
			'oauth_consumer_key' => t7keysTwitter::CONSUMER_KEY,
			'oauth_nonce' => md5(microtime() . mt_rand()),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_token' => $_SESSION['twitter_pre_token'],
			'oauth_version' => '1.0'
		];
		$post = ['oauth_verifier' => $verifier];
		$sig = array_merge($oauth, $post);
		ksort($sig);
		$sig = 'POST&' . rawurlencode(self::ACCESS) . '&' . rawurlencode(http_build_query($sig, null, '&', PHP_QUERY_RFC3986));
		$oauth['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $sig, t7keysTwitter::CONSUMER_SECRET . '&' . $_SESSION['twitter_pre_token_secret'], true)));
		ksort($oauth);

		// quote all oauth variables for the authorization header
		$header = array();
		foreach ($oauth as $var => $val)
			$header[] = $var . '="' . $val . '"';

		// send the request
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::ACCESS);
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['SERVER_NAME']);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . implode(', ', $header)]);
		curl_setopt($c, CURLOPT_POSTFIELDS, $post);
		$response = curl_exec($c);
		curl_close($c);
		parse_str($response, $this->access);  // should contain oauth_token, oauth_token_secret, user_id, screen_name, and x_auth_expires
		$this->ID = isset($this->access['user_id']) ? $this->access['user_id'] : explode('-', $this->access['oauth_token'])[0];
	}

	/**
	 * get more user info to register this user here.
	 * @return boolean true if able to retrieve.
	 */
	public function GetUserInfo() {
		if ($this->access) {
			// collect and sign oauth data
			$oauth = [
				'oauth_consumer_key' => t7keysTwitter::CONSUMER_KEY,
				'oauth_nonce' => md5(microtime() . mt_rand()),
				'oauth_signature_method' => 'HMAC-SHA1',
				'oauth_timestamp' => time(),
				'oauth_token' => $this->access['oauth_token'],
				'oauth_version' => '1.0'
			];
			$get = ['skip_status' => true];
			$sig = array_merge($oauth, $get);
			ksort($sig);
			$sig = 'GET&' . rawurlencode(self::VERIFY) . '&' . rawurlencode(http_build_query($sig, null, '&', PHP_QUERY_RFC3986));
			$oauth['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $sig, t7keysTwitter::CONSUMER_SECRET . '&' . $this->access['oauth_token_secret'], true)));
			ksort($oauth);

			// quote all oauth variables for the authorization header
			$header = array();
			foreach ($oauth as $var => $val)
				$header[] = $var . '="' . $val . '"';

			// send the request
			$c = curl_init();
			curl_setopt($c, CURLOPT_URL, self::VERIFY . '?' . http_build_query($get));
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['SERVER_NAME']);
			curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($c, CURLOPT_TIMEOUT, 30);
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($c, CURLOPT_HEADER, false);
			curl_setopt($c, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . implode(', ', $header)]);
			$response = curl_exec($c);
			curl_close($c);
			$response = json_decode($response);
			if (isset($response->id) && $response->id == $this->ID) {
				$this->ProfileFull = t7user::ExpandProfileLink($response->screen_name, self::SOURCE);
				$this->ProfileShort = $response->screen_name;
				$this->Avatar = $response->profile_image_url_https;
				$this->Username = $response->screen_name;
				$this->DisplayName = $response->name;
				if (isset($response->entities) && isset($response->entities->url) && isset($response->entities->url->urls) && isset($response->entities->url->urls[0]) && isset($response->entities->url->urls[0]->expanded_url))
					$this->Website = $response->entities->url->urls[0]->expanded_url;
				else
					$this->Website = '';
				return true;
			}
		}
		return false;
	}
}

/**
 * authorization using github oauth
 * @author misterhaan
 */
class t7authGithub extends t7authRegisterable {
	const SOURCE = 'github';
	const FIELD = 'extid';
	const REDIRECT = '/user/via/github.php';
	const REQUEST = 'https://github.com/login/oauth/authorize';
	const SCOPE = 'user:email';
	const VERIFY = 'https://github.com/login/oauth/access_token';
	const USER = 'https://api.github.com/user';

	private $access = false;
	private $gotinfo = false;  // avoid looking up user info twice

	/**
	 * build the url for logging in with github, with forgery protection and which
	 * page to return to built in.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash)
	 * @param string $csrf random string for antiforgery (should be saved for comparison against response)
	 * @return string url for logging in with github
	 */
	public static function GetAuthUrl($continue, $csrf) {
		return self::REQUEST . '?' . http_build_query([
			'client_id' => t7keysGithub::CLIENT_ID,
			// without this it uses the one in the application defined in github
			//'redirect_uri' => t7format::FullUrl(self::REDIRECT),
			'scope' => self::SCOPE,
			'state' => 'remember&' . http_build_query(['continue' => $continue, 'csrf' => $csrf])
		]);
	}

	/**
	 * handle authentication from github.  this class should only be instantiated
	 * by the page specified in self::REDIRECT.  the querystring is expected to be
	 * set by github after a login attempt.
	 */
	public function __construct() {
		if ($this->HasData = isset($_GET['state'])) {
			parse_str($_GET['state'], $state);
			if (isset($state['continue']))
				$this->Continue = $state['continue'];
			if ($this->IsValid = (isset($state['csrf']) && t7auth::CheckCSRF($state['csrf']))) {
				$this->Remember = isset($state['remember']);
				$this->GetToken($_GET['code'], $_GET['state']);
			}
		}
	}

	/**
	 * pass the code from github login back to github over a trusted connection to
	 * retrieve the access token.
	 * @param string $code value returned by github login.
	 * @param string $state value sent to and returned by github login.
	 */
	private function GetToken($code, $state) {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::VERIFY . '?' . http_build_query([
			'code' => $code,
			'client_id' => t7keysGithub::CLIENT_ID,
			'client_secret' => t7keysGithub::CLIENT_SECRET,
			// without this it uses the one in the application defined in github
			//'redirect_uri' => t7format::FullUrl(self::REDIRECT),
			'state' => $state
		]));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['SERVER_NAME']);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, false);
		$response = curl_exec($c);
		curl_close($c);
		parse_str($response, $tokens);
		if (isset($tokens['access_token'])) {
			$this->access = $tokens['access_token'];
			$this->GetUserInfo();  // need to get all the info now because that's the only way to find the id
		}
	}

	/**
	 * get the user id the access token is for
	 */
	public function GetUserInfo() {
		if ($this->gotinfo)
			return true;
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::USER);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, 't7auth');
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_HTTPHEADER, array('Authorization: token ' . $this->access));
		$response = curl_exec($c);
		curl_close($c);
		$response = json_decode($response);
		if (isset($response->id)) {
			$this->ID = $response->id;
			if (isset($response->login)) {
				$this->Username = $response->login;
				$this->ProfileShort = $response->login;
			}
			if (isset($response->name))
				$this->DisplayName = $response->name;
			if (isset($response->avatar_url))
				$this->Avatar = $response->avatar_url;
			if (isset($response->html_url))
				$this->ProfileFull = $response->html_url;
			if (isset($response->email))
				$this->Email = $response->email;
			if (isset($response->blog))
				$this->Website = $response->blog;
			$this->gotinfo = true;
			return true;
		}
		return false;
	}
}

/**
 * authorization using deviantart oauth
 * @author misterhaan
 */
class t7authDeviantart extends t7authRegisterable {
	const SOURCE = 'deviantart';
	const FIELD = 'uuid';
	const REDIRECT = '/user/via/deviantart.php';
	const REQUEST = 'https://www.deviantart.com/oauth2/authorize';
	const SCOPE = 'user';
	const VERIFY = 'https://www.deviantart.com/oauth2/token';
	const USER = 'https://www.deviantart.com/api/v1/oauth2/user/whoami?expand=user.profile';

	/**
	 * build the url for logging in with deviantart, with forgery protection and
	 * which page to return to built in.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash)
	 * @param string $csrf random string for antiforgery (should be saved for comparison against response)
	 * @return string url for logging in with deviantart
	 */
	public static function GetAuthUrl($continue, $csrf) {
		return self::REQUEST . '?' . http_build_query([
			'response_type' => 'code',
			'client_id' => t7keysDeviantart::CLIENT_ID,
			'redirect_uri' => t7format::FullUrl(self::REDIRECT),
			'scope' => self::SCOPE,
			'state' => 'remember&' . http_build_query(['continue' => $continue, 'csrf' => $csrf])
		]);
	}

	public function __construct() {
		if ($this->HasData = isset($_GET['code'])) {
			parse_str($_GET['state'], $state);
			if (isset($state['continue']))
				$this->Continue = $state['continue'];
			if ($this->IsValid = (isset($state['csrf']) && t7auth::CheckCSRF($state['csrf']))) {
				$this->Remember = isset($state['remember']);
				$this->GetToken($_GET['code']);
			}
		}
	}

	private function GetToken($code) {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::VERIFY . '?' . http_build_query([
			'code' => $code,
			'client_id' => t7keysDeviantart::CLIENT_ID,
			'client_secret' => t7keysDeviantart::CLIENT_SECRET,
			'grant_type' => 'authorization_code',
			'redirect_uri' => t7format::FullUrl(self::REDIRECT)
		]));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['SERVER_NAME']);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, false);
		$response = curl_exec($c);
		curl_close($c);
		$response = json_decode($response);
		if (isset($response->access_token))
			$this->GetUserInfoFromToken($response->access_token);
	}

	private function GetUserInfoFromToken($access) {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::USER . '&access_token=' . $access);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, 't7auth');
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, false);
		$response = curl_exec($c);
		curl_close($c);
		$response = json_decode($response);
		if (isset($response->userid)) {
			$this->ID = $response->userid;
			$this->Username = $response->username;
			$this->ProfileShort = $response->username;
			$this->ProfileFull = t7user::ExpandProfileLink($response->username, self::SOURCE);
			$this->Avatar = $response->usericon;  // 50px
			if (isset($response->profile)) {
				$this->DisplayName = $response->profile->real_name;
				$this->Website = $response->profile->website;
			}
			return true;
		}
		return false;
	}

	public function GetUserInfo() {
		return true;
	}
}

/**
 * authorization using steam community openid
 * @author misterhaan
 */
class t7authSteam extends t7authRegisterable {
	const SOURCE = 'steam';
	const FIELD = 'steamID64';
	const REDIRECT = '/user/via/steam.php';
	const REQUEST = 'https://steamcommunity.com/openid/login';
	const OPENID_NS = 'http://specs.openid.net/auth/2.0';
	const OPENID_IDENTITY = 'http://specs.openid.net/auth/2.0/identifier_select';
	const PROFILE = 'https://steamcommunity.com/profiles/';  // append the steam id and a forward slash
	const STEAM_PROFILE_URL_CUSTOM = 'https://steamcommunity.com/id/';  // append the steam custom id and a forward slash
	const AVATAR_PREFIX_HTTPS = 'https://steamcdn-a.akamaihd.net/';

	/**
	 * build the url for logging in with steam, with forgery protection and which
	 * page to return to built in.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash)
	 * @param string $csrf random string for antiforgery (should be saved for comparison against response)
	 * @return string url for logging in with steam
	 */
	public static function GetAuthUrl($continue, $csrf) {
		return self::REQUEST . '?' . http_build_query([
			'openid.ns' => self::OPENID_NS,
			'openid.mode' => 'checkid_setup',
			'openid.return_to' => t7format::FullUrl(self::REDIRECT) . '?remember&' . http_build_query(['continue' => $continue, 'csrf' => $csrf]),
			'openid.realm' => t7format::FullUrl('/'),
			'openid.identity' => self::OPENID_IDENTITY,
			'openid.claimed_id' => self::OPENID_IDENTITY
		]);
	}

	/**
	 * handle authentication from steam.  this class should only be instantiated
	 * by the page specified in self::REDIRECT.  the querystring is expected to be
	 * set by steam after a login attempt.
	 */
	public function __construct() {
		if ($this->HasData = isset($_GET['openid_claimed_id'])) {
			if (isset($_GET['continue']))
				$this->Continue = $_GET['continue'];
			if ($this->IsValid = (isset($_GET['csrf']) && t7auth::CheckCSRF($_GET['csrf']) && $this->Validate())) {
				$this->ID = explode('/', $_GET['openid_claimed_id']);
				$this->ID = $this->ID[count($this->ID) - 1];
				$this->Remember = isset($_GET['remember']);
			}
		}
	}

	/**
	 * validate that authentication information actually came from steam.
	 */
	private function Validate() {
		$data = [
			'openid.assoc_handle' => $_GET['openid_assoc_handle'],
			'openid.signed' => $_GET['openid_signed'],
			'openid.sig' => $_GET['openid_sig'],
			'openid.ns' => self::OPENID_NS,
			'openid.mode' => 'check_authentication'
		];
		foreach (explode(',', $_GET['openid_signed']) as $var)
			$data['openid.' . $var] = $_GET['openid_' . str_replace('.', '_', $var)];
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::REQUEST);
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, 't7auth');
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($data));
		$response = curl_exec($c);
		curl_close($c);
		$resarr = [];
		foreach (explode("\n", $response) as $line) {
			$varval = explode(':', $line, 2);
			if (count($varval) == 2)
				$resarr[trim($varval[0])] = trim($varval[1]);
		}
		// verification result should contain the same NS we sent, plus an is_valid value which should be true
		return isset($resarr['ns']) && $resarr['ns'] == self::OPENID_NS && isset($resarr['is_valid']) && $resarr['is_valid'] == 'true';
	}

	/**
	 * get more user info to register this user here.
	 * @return boolean true if able to retrieve.
	 */
	public function GetUserInfo() {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::PROFILE . $this->ID . '/?xml=1');
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, 't7auth');
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_MAXREDIRS, 5);
		$response = curl_exec($c);
		$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		curl_close($c);
		if ($code == 200 && $xml = simplexml_load_string($response))
			if (!isset($xml->error)) {
				$this->DisplayName = html_entity_decode((string)$xml->steamID);  // should use UTF-8
				if (isset($xml->customURL)) {
					$this->Username = (string)$xml->customURL;
					$this->ProfileShort = (string)$xml->customURL;
				} else
					$this->ProfileShort = (string)$xml->steamID64;
				$this->ProfileFull = t7user::ExpandProfileLink($this->ProfileShort, self::SOURCE);
				$this->Avatar = preg_replace('/^http:\/\/[^\/]+\/(.+)$/', self::AVATAR_PREFIX_HTTPS . '$1', (string)$xml->avatarMedium);  // 64px
				return true;
			}
		return false;
	}
}

/**
 * authorization using local track7 username and password (deprecated)
 * @author misterhaan
 */
class t7authTrack7 {
	const VERIFY = '/user/via/track7.php';

	public $HasData = false;
	public $IsValid = false;
	public $DBError = false;
	public $ID = false;

	public $Continue = false;
	public $Remember = false;

	/**
	 * build the url for logging in locally, with forgery protection and which
	 * page to return to built in.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash)
	 * @param string $csrf random string for antiforgery (should be saved for comparison against response)
	 * @return string url for logging in locally
	 */
	public static function GetAuthURL($continue, $csrf) {
		return self::VERIFY . '?' . http_build_query(array(
			'continue' => $continue,
			'csrf' => $csrf
		));
	}

	/**
	 * handle local authentication.  this class should only be instantiated by
	 * the page specified in self::VERIFY.  username, password, and optionally
	 * remember should be posted, with csrf and optionally continue provided
	 * through the querystring.
	 */
	public function __construct() {
		if ($this->HasData = (isset($_POST['username']) && isset($_POST['password']))) {
			if (isset($_GET['continue']))
				$this->Continue = $_GET['continue'];
			if ($this->IsValid = (isset($_GET['csrf']) && t7auth::CheckCSRF($_GET['csrf']))) {
				$this->Remember = isset($_POST['remember']);
				$this->DBError = false;
				global $db;
				if ($chk = $db->query('select id, pass from transition_login where login=\'' . $db->real_escape_string(trim($_POST['username'])) . '\' limit 1')) {
					if ($chk = $chk->fetch_object())
						if ($this->CheckPassword(trim($_POST['password']), $chk->pass))
							$this->ID = $chk->id;
				} else
					$this->DBError = true;
			}
		}
	}

	/**
	 * Checks a plain-text password against an encrypted password.
	 *
	 * @param string $password Plain-text password.
	 * @param string $hash Encrypted password.
	 * @return bool True if passwords match.
	 */
	private function CheckPassword($password, $hash) {
		$len = strlen($hash);
		$saltpass = $password . substr($hash, 0, 8);
		if ($len == 96)  // currently auUser uses base64 SHA512 with 8-character base64 salt for 96 characters total
			return base64_encode(hash('sha512', $saltpass, true)) == substr($hash, 8);
		if ($len == 48)  // until version 0.4.0, auUser used hexadecimal SHA1 with 8-character hexadecimal salt for 48 characters total
			return sha1($saltpass) == substr($hash, 8);
		if ($len == 32)  // this should not happen except for old track7 users, since auUser never used MD5
			return md5($pass) == $hash;
		return false;
	}
}
