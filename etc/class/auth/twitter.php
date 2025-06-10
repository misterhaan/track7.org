<?php
require_once dirname(__DIR__) . '/environment.php';
require_once 'auth.php';

class TwitterAuth extends Auth {
	private const RequestURL = 'https://api.x.com/oauth/request_token';
	private const AuthenticateURL = 'https://api.x.com/oauth/authenticate';
	private const AcessTokenURL = 'https://api.x.com/oauth/access_token';
	private const VerifyCredentialsURL = 'https://api.x.com/1.1/account/verify_credentials.json';

	public function __construct() {
		$this->Name = 'twitter';
	}

	public function Begin(bool $remember, ?string $return): string {
		$_SESSION['twitter_continue'] = $this->GetReturnURL($return);
		$_SESSION['twitter_remember'] = $remember;

		// twitter documentation says i need to include oauth_callback set to %-encoded fully-qualified callback url, but i get an error if i include it
		$response = $this->OauthRequest('POST', self::RequestURL);

		parse_str($response, $tokens);  // should set oauth_token, oauth_token_secret, and oauth_callback_confirmed
		if (isset($tokens['oauth_callback_confirmed']) && $tokens['oauth_callback_confirmed'] == 'true') {
			$_SESSION['twitter_pre_token'] = $tokens['oauth_token'];
			$_SESSION['twitter_pre_token_secret'] = $tokens['oauth_token_secret'];
			return self::AuthenticateURL . '?oauth_token=' . $tokens['oauth_token'];
		} else
			throw new DetailedException('twitter authentication failed', $response);
	}

	public function Process(mysqli $db): ?AuthResult {
		if (!isset($_GET['oauth_token'], $_GET['oauth_verifier']))
			return null;

		$result = new AuthResult();
		if ($result->IsValid = isset($_SESSION['twitter_pre_token']) && $_SESSION['twitter_pre_token'] == $_GET['oauth_token']) {
			$result->Continue = isset($_SESSION['twitter_continue']) ? $_SESSION['twitter_continue'] : '/';
			unset($_SESSION['twitter_continue']);
			$result->Remember = isset($_SESSION['twitter_remember']) ? $_SESSION['twitter_remember'] : false;
			unset($_SESSION['twitter_remember']);
			if ($access = $this->GetAccessToken($_GET['oauth_verifier'], $_GET['oauth_token']))
				if ($result->User = $this->GetUserInfo($access))
					$result->LoginMatch = LoginProfile::Find($db, $this->Name, $result->User->ID);
		}
		unset($_SESSION['twitter_pre_token']);
		return $result;
	}

	private function GetAccessToken(string $verifier, string $oauthToken): array {
		$response = $this->OauthRequest('POST', self::AcessTokenURL, $oauthToken, $_SESSION['twitter_pre_token_secret'], ['oauth_verifier' => $verifier]);
		parse_str($response, $access);  // should contain oauth_token, oauth_token_secret, user_id, screen_name, and x_auth_expires
		return $access;
	}

	private function GetUserInfo($access): ?AuthUser {
		$response = $this->OauthRequest('GET', self::VerifyCredentialsURL, $access['oauth_token'], $access['oauth_token_secret'], ['skip_status' => true]);
		$response = json_decode($response);
		$id = isset($access['user_id']) ? $access['user_id'] : explode('-', $access['oauth_token'])[0];
		if (isset($response->id) && $response->id == $id) {
			$user = new AuthUser($id);
			require_once 'contact.php';
			$user->ProfileURL = ContactLink::ExpandURL($this->Name, $response->screen_name);
			$user->Avatar = $response->profile_image_url_https;
			$user->Username = $response->screen_name;
			$user->DisplayName = $response->name;
			if (isset($response->entities, $response->entities->url, $response->entities->url->urls, $response->entities->url->urls[0], $response->entities->url->urls[0]->expanded_url))
				$user->Website = $response->entities->url->urls[0]->expanded_url;
			return $user;
		}
		return null;
	}

	private function OauthRequest(string $method, string $url, ?string $token = null, ?string $secret = '', ?array $data = null): string {
		$header = $this->OauthHeader($method, $url, $token, $secret, $data);

		if ($method == 'GET' && $data)
			$url .= '?' . http_build_query($data);

		// send the request
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		if ($method == 'POST')
			curl_setopt($c, CURLOPT_POST, true);
		curl_setopt_array($c, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => $_SERVER['SERVER_NAME'],
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HEADER => false,
			CURLOPT_HTTPHEADER => ["Authorization: OAuth $header"]
		]);
		if ($method == 'POST' && $data)
			curl_setopt($c, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($c);
		curl_close($c);

		return $response;
	}

	private function OauthHeader(string $method, string $url, ?string $token = null, ?string $secret = '', ?array $data = null): string {
		self::RequireServiceKeys('KeysTwitter', 'ConsumerKey', 'ConsumerSecret');
		// collect and sign oauth data
		$oauth = [
			'oauth_consumer_key' => KeysTwitter::ConsumerKey,
			'oauth_nonce' => md5(microtime() . mt_rand()),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_version' => '1.0'
		];
		if ($token)
			$oauth['oauth_token'] = $token;
		if ($method == 'GET' && $data)
			$oauth = array_merge($data, $oauth);
		ksort($oauth);

		$sig = $method . '&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($oauth, '', '&', PHP_QUERY_RFC3986));
		$oauth['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $sig, KeysTwitter::ConsumerSecret . '&' . $secret, true)));
		ksort($oauth);

		// quote all oauth variables for the authorization header
		$header = array();
		foreach ($oauth as $var => $val)
			$header[] = $var . '="' . $val . '"';
		return implode(', ', $header);
	}
}
