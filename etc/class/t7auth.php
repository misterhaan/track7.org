<?php
class t7auth {
	/**
	 * gets a cross-site request forgery token.  will be created the first time.
	 * @return string random 32-character hexadecimal
	 */
	public static function GetCSRF() {
		if(!isset($_SESSION['CSRF']))
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
		if(!isset($_SESSION['CSRF']))
			return false;
		$stored = $_SESSION['CSRF'];
		unset($_SESSION['CSRF']);
		return $csrf == $stored;
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
		$links['facebook'] = t7authFacebook::GetAuthUrl($continue, $csrf);
		// TODO:  add links to other methods here
		if(!$adding)
			$links['track7'] = t7authTrack7::GetAuthURL($continue, $csrf);
		return $links;
	}

	public static function LoginRegister(t7authRegisterable $auth) {
		global $db, $html, $user;
		if($auth->HasData)
			if($auth->IsValid)
				if($auth->ID)
					if($finduser = $db->query('select user from login_' . $auth::SOURCE . ' where ' . $auth::FIELD . '=\'' . $db->escape_string($auth->ID) . '\' limit 1'))
						if($finduser = $finduser->fetch_object())
							if($user->IsLoggedIn()) // adding a known login
								if($user->ID == $finduser->user) { // adding a login that was already added
									self::OpenPage($auth::SOURCE);
?>
			<p>
				this <?php echo $auth::SOURCE; ?> account is already linked to your
				track7 account.  maybe you meant to <a href="/user/settings.php#linkedaccounts">link
				a different <?php echo $auth::SOURCE; ?> account</a>?
			</p>
<?php
								} else { // adding a login that's already linked to a different account
									self::OpenPage($auth::SOURCE);
?>
			<p>
				this <?php echo $auth::SOURCE; ?> account is linked to track7, but not
				for who you’re currently signed in as.  if you want to link this <?php echo $auth::SOURCE; ?>
				account to <?php echo htmlspecialchars($user->DisplayName); ?> then
				things are a bit complicated — you probably want to ask <a href="/user/misterhaan/" title="go to misterhaan’s profile for contact information">misterhaan</a>
				to merge things on the track7 side.  if you’re trying to sign in with
				this <?php echo $auth::SOURCE; ?> account not as <?php echo htmlspecialchars($user->DisplayName); ?>
				then you’ll need to sign out first (from the menu in the upper right).
			</p>
<?php
								}
							else { // logging in
								$user->Login($auth::SOURCE, $auth);
								die;
							}
						else // account not linked to track7
							if($auth->GetUserInfo())
								if($user->IsLoggedIn()) { // link new account to user
									$db->autocommit(false);  // don't create external profile or login unless both get created
									if($db->real_query('insert into external_profiles (name, url, avatar) values (\'' . $db->escape_string($auth->DisplayName) . '\', \'' . $db->escape_string($auth->ProfileFull) . '\', \'' . $db->escape_string($auth->Avatar) . '\')')) {
										$pid = $db->insert_id;
										if($db->real_query('insert into login_' . $auth::SOURCE . ' (user, ' . $auth::FIELD . ', profile) values (\'' . +$user->ID . '\', \'' . $db->escape_string($auth->ID) . '\', \'' . +$pid . '\')')) {
											if($auth->ProfileShort)
												$db->real_query('update users_profiles set ' . $auth::SOURCE . '=\'' . $db->escape_string($auth->ProfileShort) . '\' where id=\'' . +$user->ID . '\' and ' . $auth::SOURCE . '=\'\'');
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
				oops, we couldn’t link your <?php echo $auth::SOURCE; ?> account for
				signing into track7.  generally if you see this you should tell
				<a href="/user/misterhaan" title="go to misterhaan’s profile for contact information">misterhaan</a>.
			</p>
<?php
								} else { // show registration form
									// TODO: check if e-mail is already linked (might be handled by javascript later instead)
									$_SESSION['registering'] = $auth::SOURCE;
									$_SESSION[$auth::SOURCE] = [$auth::FIELD => $auth->ID, 'name' => $auth->DisplayName, 'avatar' => $auth->Avatar, 'profile' => $auth->ProfileFull, 'remember' => $auth->Remember, 'continue' => $auth->Continue];
									self::OpenPage($auth::SOURCE);
?>
			<p>
				welcome to track7!  according to our records, you haven’t signed in with
				this <?php echo $auth::SOURCE; ?> account before.  if you <em>have</em>
				signed in to track7 before, maybe you used a different account — you can
				try signing in again with that account and then add this <?php echo $auth::SOURCE; ?>
				account as another sign-in option.  if you are new, we’ve filled in some
				information based on your <?php echo $auth::SOURCE; ?> profile.  change
				it if you like, then enjoy track7 as a signed-in actual person!
			</p>

			<h2>profile information</h2>
			<form id=newuser>
				<input type=hidden id=csrf value="<?php echo t7auth::GetCSRF(); ?>">
				<label>
					<span class=label>username:</span>
					<span class=field><input id=username maxlength=32 required value="<?php echo htmlspecialchars($auth->Username); ?>"></span>
					<span class=validation></span>
				</label>
				<label>
					<span class=label>display name:</span>
					<span class=field><input id=displayname maxlength=32 value="<?php echo htmlspecialchars($auth->DisplayName); ?>"></span>
					<span class=validation></span>
				</label>
				<label>
					<span class=label>e-mail:</span>
					<span class=field><input id=email maxlength=64 value="<?php echo htmlspecialchars($auth->Email); ?>"></span>
					<span class=validation></span>
				</label>
				<label>
					<span class=label>website:</span>
					<span class=field><input id=website maxlength=64 value="<?php echo htmlspecialchars($auth->Website); ?>"></span>
					<span class=validation></span>
				</label>
<?php
									if($auth->ProfileShort) {
?>
				<label>
					<span class=checkbox><input type=checkbox checked id=linkprofile> link <a href="<?php echo htmlspecialchars($auth->ProfileFull); ?>">this profile</a> as your <?php echo $auth::SOURCE; ?> profile</span>
				</label>
<?php
									}
?>
				<label>
					<span class=checkbox><input type=checkbox checked id=useavatar> use this profile picture: <img class=avatar src="<?php echo htmlspecialchars($auth->Avatar); ?>"></span>
				</label>
				<button>confirm</button>
			</form>
<?php
								}
							else { // couldn't get user info from account
								self::OpenPage($auth::SOURCE);
?>
			<p>
				oops, we couldn’t get any information about that <?php echo $auth::SOURCE; ?>
				account.  this generally shouldn’t happen unless <?php echo $auth::SOURCE; ?>
				goes down between logging in and looking up account information.
				generally if you see this you should tell <a href="/user/misterhaan/" title="go to misterhaan’s profile for contact information">misterhaan</a>.
			</p>
<?php
							}
					else { // error checking if login is known
						self::OpenPage($auth::SOURCE);
?>
			<p>
				hey, so <?php echo $auth::SOURCE; ?> told us who you are, but when we
				tried to check if you’d been here before something went wrong.
				generally if you see this you should tell <a href="/user/misterhaan/" title="go to misterhaan’s profile for contact information">misterhaan</a>.
			</p>
<?php
					}
				else { // didn't get a subscriber ID, so they probably changed their mind.  go back to the previous page
					header('Location: ' . t7format::FullUrl($auth->Continue));
					die();
				}
			else { // continuity data didn't match up
				self::OpenPage($auth::SOURCE);
?>
			<p>
				oops, there's something wrong with your authentication data.  sometimes
				that happens if you leave track7 open for a while without clicking any
				links and then try to sign in, or if you wait too long on the <?php echo $auth::SOURCE; ?>
				sign in page.  if that sounds like you, just try again.
			</p>
<?php
			}
		else { // state data missing
			self::OpenPage($auth::SOURCE);
?>
			<p>
				no authentication data found!&nbsp; maybe you need to
				<a href="<?php echo $auth::GetAuthURL('/', self::GetCSRF()); ?>">sign in with <?php echo $auth::SOURCE; ?></a>?
			</p>
<?php
		}
		$html->Close();
	}

	private static function OpenPage($source) {
		global $html;
		if(isset($html))
			return;
		$html = new t7html([]);
		$html->Open($source . ' sign-in');
?>
			<h1><?php echo $source; ?> sign-in results</h1>
<?php
	}
}

abstract class t7authRegisterable {
	public $HasData = false;
	public $IsValid = false;
	public $ID = false; // id on the provider side

	public $Continue = false; // local url to redirect to on success
	public $Remember = false; // whether this login should be remembered for future sessions

	// any of this group will remain false if the auth doesn't support them
	public $ProfileFull = false; // full url to the profile of this account
	public $ProfileShort = false; // unique portion of the profile of this account
	public $Avatar = false; // url to the avatar of this account
	public $Username = false; // suggested username from this account
	public $DisplayName = false; // suggested display name from this account
	public $Email = false; // e-mail address associated with this account
	public $Website = false; // website associated with this account

	public static abstract function GetAuthUrl($continue, $csrf);
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
	const REQUEST = 'https://accounts.google.com/o/oauth2/auth';
	const SCOPE = 'openid email';
	const VERIFY = 'https://www.googleapis.com/oauth2/v3/token';
	const INFO = 'https://www.googleapis.com/plus/v1/people/me/openIdConnect';

	private $access;

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
	public function t7authGoogle() {
		if($this->HasData = isset($_GET['state'])) {
			parse_str($_GET['state'], $state);
			if(isset($state['continue']))
				$this->Continue = $state['continue'];
			if($this->IsValid = (isset($state['csrf']) && t7auth::CheckCSRF($state['csrf']))) {
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
		if(isset($response->access_token) && isset($response->id_token)) {
			$this->access = $response->access_token;
			$id = explode('.', $response->id_token);
			$id = json_decode(base64_decode($id[1]));
			$this->ID = $id->sub;
		}
	}

	/**
	 * get more user info to register this user here.
	 * @return mixed|boolean user info object, or false if unable to retrieve.
	 */
	public function GetUserInfo() {
		if($this->access) {
			$c = curl_init();
			curl_setopt($c, CURLOPT_URL, self::INFO);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['SERVER_NAME']);
			curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($c, CURLOPT_TIMEOUT, 30);
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($c, CURLOPT_HEADER, false);
			curl_setopt($c, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . $this->access));
			$response = curl_exec($c);
			curl_close($c);
			$response = json_decode($response);
			if(isset($response->sub) && $response->sub == $this->ID) {
				$this->ProfileFull = $response->profile;
				$this->ProfileShort = t7user::CollapseProfileLink($response->profile, self::SOURCE);
				$this->Avatar = $response->picture;
				$this->Username = explode('@', $response->email)[0];
				$this->DisplayName = $response->name;
				$this->Email = $response->email;
				// unused:  gender
				return $response;
			}
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
	public function t7authTwitter() {
		if(isset($_GET['startauth'])) {
			if(isset($_GET['continue']))
				$_SESSION['twitter_continue'] = $_GET['continue'];
			$_SESSION['twitter_remember'] = isset($_GET['remember']);
			$this->Authenticate();
		}
		if($this->HasData = (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier'])))
			if($this->IsValid = (isset($_SESSION['twitter_pre_token']) && $_SESSION['twitter_pre_token'] == $_GET['oauth_token'])) {
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
		$oauth = ['oauth_consumer_key' => t7keysTwitter::CONSUMER_KEY,
				'oauth_nonce' => md5(microtime() . mt_rand()),
				'oauth_signature_method' => 'HMAC-SHA1',
				'oauth_timestamp' => time(),
				'oauth_version' => '1.0'];
		ksort($oauth);
		$sig = 'POST&' . rawurlencode(self::REQUEST) . '&' . rawurlencode(http_build_query($oauth, null, '&', PHP_QUERY_RFC3986));
		$oauth['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $sig, t7keysTwitter::CONSUMER_SECRET . '&', true)));
		ksort($oauth);

		// quote all oauth variables for the authorization header
		$header = array();
		foreach($oauth as $var => $val)
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
		if(isset($tokens['oauth_callback_confirmed']) && $tokens['oauth_callback_confirmed'] == 'true') {
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
		$oauth = ['oauth_consumer_key' => t7keysTwitter::CONSUMER_KEY,
				'oauth_nonce' => md5(microtime() . mt_rand()),
				'oauth_signature_method' => 'HMAC-SHA1',
				'oauth_timestamp' => time(),
				'oauth_token' => $_SESSION['twitter_pre_token'],
				'oauth_version' => '1.0'];
		$post = ['oauth_verifier' => $verifier];
		$sig = array_merge($oauth, $post);
		ksort($sig);
		$sig = 'POST&' . rawurlencode(self::ACCESS) . '&' . rawurlencode(http_build_query($sig, null, '&', PHP_QUERY_RFC3986));
		$oauth['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $sig, t7keysTwitter::CONSUMER_SECRET . '&' . $_SESSION['twitter_pre_token_secret'], true)));
		ksort($oauth);

		// quote all oauth variables for the authorization header
		$header = array();
		foreach($oauth as $var => $val)
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
	 * @return mixed|boolean user info object, or false if unable to retrieve.
	 */
	public function GetUserInfo() {
		if($this->access) {
			// collect and sign oauth data
			$oauth = ['oauth_consumer_key' => t7keysTwitter::CONSUMER_KEY,
					'oauth_nonce' => md5(microtime() . mt_rand()),
					'oauth_signature_method' => 'HMAC-SHA1',
					'oauth_timestamp' => time(),
					'oauth_token' => $this->access['oauth_token'],
					'oauth_version' => '1.0'];
			$get = ['skip_status' => true];
			$sig = array_merge($oauth, $get);
			ksort($sig);
			$sig = 'GET&' . rawurlencode(self::VERIFY) . '&' . rawurlencode(http_build_query($sig, null, '&', PHP_QUERY_RFC3986));
			$oauth['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $sig, t7keysTwitter::CONSUMER_SECRET . '&' . $this->access['oauth_token_secret'], true)));
			ksort($oauth);

			// quote all oauth variables for the authorization header
			$header = array();
			foreach($oauth as $var => $val)
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
			if(isset($response->id) && $response->id == $this->ID) {
				$this->ProfileFull = t7user::ExpandProfileLink($response->screen_name, self::SOURCE);
				$this->ProfileShort = $response->screen_name;
				$this->Avatar = $response->profile_image_url;
				$this->Username = $response->screen_name;
				$this->DisplayName = $response->name;
				if(isset($response->entities) && isset($response->entities->url) && isset($response->entities->url->urls) && isset($response->entities->url->urls[0]) && isset($response->entities->url->urls[0]->expanded_url))
					$this->Website = $response->entities->url->urls[0]->expanded_url;
				else
					$this->Website = '';
				return $response;
			}
		}
		return false;
	}
}

/**
 * authorization using facebook oauth
 * @author misterhaan
 */
class t7authFacebook extends t7authRegisterable {
	const SOURCE = 'facebook';
	const FIELD = 'extid';
	const REDIRECT = '/user/via/facebook.php';
	const REQUEST = 'https://www.facebook.com/dialog/oauth';
	const SCOPE = 'public_profile,email';
	const VERIFY = 'https://graph.facebook.com/v2.3/oauth/access_token';
	const ID = 'https://graph.facebook.com/me';
	const INFO = 'https://graph.facebook.com/v2.5/me';
	const PICURL = 'http://graph.facebook.com/v2.10/{ID}/picture';

	private $access = false;

	public static function GetAuthUrl($continue, $csrf) {
		return self::REQUEST . '?' . http_build_query([
				'client_id' => t7keysFacebook::ID,
				'redirect_uri' => t7format::FullUrl(self::REDIRECT),
				'response_type' => 'code',
				'scope' => self::SCOPE,
				'state' => 'remember&' . http_build_query(array('continue' => $continue, 'csrf' => $csrf))
		]);
	}

	/**
	 * handle authentication from facebook.  this class should only be
	 * instantiated by the page specified in self::REDIRECT.  the querystring is
	 * expected to be set by facebook after a login attempt.
	 */
	public function t7authFacebook() {
		if($this->HasData = isset($_GET['state'])) {
			parse_str($_GET['state'], $state);
			if(isset($state['continue']))
				$this->Continue = $state['continue'];
			if($this->IsValid = (isset($state['csrf']) && t7auth::CheckCSRF($state['csrf']))) {
				$this->Remember = isset($state['remember']);
				$this->GetToken($_GET['code']);
			}
		}
	}

	/**
	 * pass the code from facebook login back to facebook over a trusted
	 * connection to retrieve the access and id tokens.
	 * @param string $code value returned by facebook login
	 */
	private function GetToken($code) {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::VERIFY . '?' . http_build_query([
				'code' => $code,
				'client_id' => t7keysFacebook::ID,
				'client_secret' => t7keysFacebook::SECRET,
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
		if(isset($response->access_token)) {
			$this->access = $response->access_token;
			$this->GetID();
		}
	}

	/**
	 * get the user id the access token is for
	 */
	private function GetID() {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::ID . '?' . http_build_query(['access_token' => $this->access, 'fields' => 'id']));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['SERVER_NAME']);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, false);
		$response = curl_exec($c);
		curl_close($c);
		$response = json_decode($response);
		if(isset($response->id))
			$this->ID = $response->id;
	}

	/**
	 * get more user info to register this user here.
	 * @return mixed|boolean user info object, or false if unable to retrieve.
	 */
	public function GetUserInfo() {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::INFO . '?' . http_build_query([
				'access_token' => $this->access,
				'fields' => 'id,email,link,name,website'
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
		if(isset($response->id) && $response->id == $this->ID) {
			$this->ProfileFull = $response->link;
			$this->Avatar = str_replace('{ID}', $this->ID, self::PICURL);
			$this->Username = explode('@', $response->email)[0];
			$this->DisplayName = $response->name;
			$this->Email = $response->email;
			if(isset($response->website))
				$this->Website = $response->website;
			return $response;
		}
		return false;
	}

	/**
	 * request an obfuscated url in order to find the url it ends up at.
	 * @param string $url obfuscated url to request
	 * @return mixed deobfuscated url
	 */
	private static function GetLastRedirect($url) {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['SERVER_NAME']);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, false);
		$response = curl_exec($c);
		$redirect = curl_getinfo($c, CURLINFO_EFFECTIVE_URL);
		curl_close($c);
		return $redirect;
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
	public function t7authTrack7() {
		if($this->HasData = (isset($_POST['username']) && isset($_POST['password']))) {
			if(isset($_GET['continue']))
				$this->Continue = $_GET['continue'];
			if($this->IsValid = (isset($_GET['csrf']) && t7auth::CheckCSRF($_GET['csrf']))) {
				$this->Remember = isset($_POST['remember']);
				$this->DBError = false;
				global $db;
				if($chk = $db->query('select id, pass from transition_login where login=\'' . $db->real_escape_string(trim($_POST['username'])) . '\' limit 1')) {
					if($chk = $chk->fetch_object())
						if($this->CheckPassword(trim($_POST['password']), $chk->pass))
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
		if($len == 96)  // currently auUser uses base64 SHA512 with 8-character base64 salt for 96 characters total
			return base64_encode(hash('sha512', $saltpass, true)) == substr($hash, 8);
		if($len == 48)  // until version 0.4.0, auUser used hexadecimal SHA1 with 8-character hexadecimal salt for 48 characters total
			return sha1($saltpass) == substr($hash, 8);
		if($len == 32)  // this should not happen except for old track7 users, since auUser never used MD5
			return md5($pass) == $hash;
		return false;
	}
}
