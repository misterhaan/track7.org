<?php
require_once dirname(__DIR__) . '/environment.php';
require_once 'auth.php';

class GithubAuth extends Auth {
	private const RequestURL = 'https://github.com/login/oauth/authorize';
	private const Scope = 'user:email';
	private const VerifyURL = 'https://github.com/login/oauth/access_token';
	private const UserInfoURL = 'https://api.github.com/user';

	public function __construct() {
		$this->Name = 'github';
	}

	public function Begin(bool $remember): string {
		self::RequireServiceKeys('t7keysGithub', 'CLIENT_ID');
		$return = $this->GetReturnURL();
		$csrf = $this->GetCSRF();
		return self::RequestURL . '?' . http_build_query([
			'client_id' => t7keysGithub::CLIENT_ID,
			// without this it uses the one in the application defined in github
			//'redirect_uri' => $this->GetRedirectURL(),
			'scope' => self::Scope,
			'state' => ($remember ? 'remember&' : '') . http_build_query(['continue' => $return, 'csrf' => $csrf])
		]);
	}

	public function Process(mysqli $db): ?AuthResult {
		if (!isset($_GET['state'], $_GET['code']))
			return null;

		$result = new AuthResult();
		parse_str($_GET['state'], $state);
		if ($result->IsValid = isset($state['csrf']) && $this->CheckCSRF($state['csrf'])) {
			if (isset($state['continue']) && $state['continue'])
				$result->Continue = $this->GetReturnURL($state['continue']);
			$result->Remember = isset($state['remember']);
			if ($token = $this->GetAccessToken($_GET['code'], $_GET['state']))
				if ($result->User = $this->GetUserInfo($token))
					$result->LoginMatch = LoginProfile::Find($db, $this->Name, $result->User->ID);
		}
		return $result;
	}

	/**
	 * pass the code from github login back to github over a trusted connection
	 * to retrieve the access and id tokens.
	 * @param string $code value returned by github login
	 * @param string $state value returned by github login
	 */
	private function GetAccessToken(string $code, string $state): string {
		self::RequireServiceKeys('t7keysGithub', 'CLIENT_ID', 'CLIENT_SECRET');
		$c = curl_init();
		curl_setopt_array($c, [
			CURLOPT_URL => self::VerifyURL . '?' . http_build_query([
				'code' => $code,
				'client_id' => t7keysGithub::CLIENT_ID,
				'client_secret' => t7keysGithub::CLIENT_SECRET,
				// without this it uses the one in the application defined in github
				//'redirect_uri' => $this->GetRedirectURL(),
				'state' => $state
			]),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => $_SERVER['SERVER_NAME'],
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HEADER => false
		]);
		$response = curl_exec($c);
		curl_close($c);
		parse_str($response, $tokens);
		return $tokens['access_token'];
	}

	/**
	 * get more user info to register this user here.
	 */
	public static function GetUserInfo(string $accessToken): ?AuthUser {
		$c = curl_init();
		curl_setopt_array($c, [
			CURLOPT_URL => self::UserInfoURL,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => $_SERVER['SERVER_NAME'],
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HEADER => false,
			CURLOPT_HTTPHEADER => array('Authorization: token ' . $accessToken)
		]);
		$response = curl_exec($c);
		curl_close($c);
		$response = json_decode($response);
		if (isset($response->id)) {
			$user = new AuthUser($response->id);
			if (isset($response->login))
				$user->Username = $response->login;
			if (isset($response->name))
				$user->DisplayName = $response->name;
			if (isset($response->avatar_url))
				$user->Avatar = $response->avatar_url;
			if (isset($response->html_url))
				$user->ProfileURL = $response->html_url;
			if (isset($response->email))
				$user->Email = $response->email;
			if (isset($response->blog))
				$user->Website = $response->blog;
			return $user;
		}
		return null;
	}
}
