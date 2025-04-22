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
		// twitter skipped because it needs an extra step that doesn't fit in this structure anymore
		$links['github'] = t7authGithub::GetAuthUrl($continue, $csrf);
		$links['deviantart'] = t7authDeviantart::GetAuthUrl($continue, $csrf);
		$links['steam'] = t7authSteam::GetAuthUrl($continue, $csrf);
		return $links;
	}
}

/**
 * base class for authorizations that can be registered to track7
 * @author misterhaan
 */
abstract class t7authRegisterable {
	/**
	 * generate an authorization url for this provider.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash).
	 * @param string $csrf random string for antiforgery (should be saved for comparison against response).
	 * @return string url for logging in with this provider.
	 */
	public static abstract function GetAuthUrl($continue, $csrf);
}

/**
 * authorization using google openid connect
 * @author misterhaan
 */
class t7authGoogle extends t7authRegisterable {
	const SOURCE = 'google';

	/**
	 * build the url for logging in with google, with forgery protection and
	 * which page to return to built in.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash)
	 * @param string $csrf random string for antiforgery (should be saved for comparison against response)
	 * @return string url for logging in with google
	 */
	public static function GetAuthURL($continue, $csrf) {
		require_once 'auth.php';
		return Auth::Provider('google')->Begin(true, $continue);
	}
}

/**
 * authorization using twitter openid connect
 * @author misterhaan
 */
class t7authTwitter extends t7authRegisterable {
	const SOURCE = 'twitter';
	const REDIRECT = '/user/via/twitter.php';

	/**
	 * get the url for logging in with twitter, with the page to return to built
	 * in.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash)
	 * @return string url for logging in with twitter
	 */
	public static function GetAuthURL($continue, $csrf = null) {
		// this doesn't work, but it only matters on the couple pages that aren't migrated yet
		return self::REDIRECT . '?startauth&remember&continue=' . urlencode($continue);
	}
}

/**
 * authorization using github oauth
 * @author misterhaan
 */
class t7authGithub extends t7authRegisterable {
	const SOURCE = 'github';

	/**
	 * build the url for logging in with github, with forgery protection and which
	 * page to return to built in.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash)
	 * @param string $csrf random string for antiforgery (should be saved for comparison against response)
	 * @return string url for logging in with github
	 */
	public static function GetAuthUrl($continue, $csrf) {
		require_once 'auth.php';
		return Auth::Provider('github')->Begin(true, $continue);
	}
}

/**
 * authorization using deviantart oauth
 * @author misterhaan
 */
class t7authDeviantart extends t7authRegisterable {
	const SOURCE = 'deviantart';

	/**
	 * build the url for logging in with deviantart, with forgery protection and
	 * which page to return to built in.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash)
	 * @param string $csrf random string for antiforgery (should be saved for comparison against response)
	 * @return string url for logging in with deviantart
	 */
	public static function GetAuthUrl($continue, $csrf) {
		require_once 'auth.php';
		return Auth::Provider('deviantart')->Begin(true, $continue);
	}
}

/**
 * authorization using steam community openid
 * @author misterhaan
 */
class t7authSteam extends t7authRegisterable {
	const SOURCE = 'steam';

	/**
	 * build the url for logging in with steam, with forgery protection and which
	 * page to return to built in.
	 * @param string $continue local url to return to after login is complete (should begin with a forward slash)
	 * @param string $csrf random string for antiforgery (should be saved for comparison against response)
	 * @return string url for logging in with steam
	 */
	public static function GetAuthUrl($continue, $csrf) {
		require_once 'auth.php';
		return Auth::Provider('steam')->Begin(true, $continue);
	}
}
