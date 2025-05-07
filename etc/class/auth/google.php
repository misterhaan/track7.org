<?php
require_once dirname(__DIR__) . '/environment.php';
require_once 'auth.php';

class GoogleAuth extends Auth {
	private const RequestURL = 'https://accounts.google.com/o/oauth2/v2/auth';
	private const VerifyURL = 'https://oauth2.googleapis.com/token';
	private const Scope = 'openid email profile';

	public function __construct() {
		$this->Name = 'google';
	}

	public function Begin(bool $remember, ?string $return): string {
		self::RequireServiceKeys('t7keysGoogle', 'ID');
		$return = $this->GetReturnURL($return);
		$csrf = $this->GetCSRF();
		return self::RequestURL . '?' . http_build_query([
			'client_id' => t7keysGoogle::ID,
			'redirect_uri' => $this->GetRedirectURL(),
			'response_type' => 'code',
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
			if ($token = $this->GetIdToken($_GET['code']))
				if ($result->User = $this->GetUserInfo($token, $result))
					$result->LoginMatch = LoginProfile::Find($db, $this->Name, $result->User->ID);
		}
		return $result;
	}

	/**
	 * pass the code from google login back to google over a trusted connection
	 * to retrieve the access and id tokens.
	 * @param string $code value returned by google login
	 */
	private function GetIdToken(string $code): ?object {
		self::RequireServiceKeys('t7keysGoogle', 'ID', 'SECRET');
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::VerifyURL);
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
			'redirect_uri' => $this->GetRedirectURL(),
			'grant_type' => 'authorization_code'
		)));
		$response = curl_exec($c);
		curl_close($c);
		$response = json_decode($response);
		// unused:  access_token
		if (isset($response->id_token)) {
			$id = explode('.', $response->id_token);
			return json_decode(base64_decode($id[1]));
		}
		return null;
	}

	/**
	 * get more user info to register this user here.
	 */
	public static function GetUserInfo(object $idToken): ?AuthUser {
		if ($idToken->sub) {
			$user = new AuthUser($idToken->sub);
			if (isset($idToken->profile))
				$user->ProfileURL = $idToken->profile;
			if (isset($idToken->picture))
				$user->Avatar = $idToken->picture . '?sz=64';
			if (isset($idToken->email)) {
				$user->Username = explode('@', $idToken->email)[0];
				$user->Email = $idToken->email;
			}
			if (isset($idToken->name))
				$user->DisplayName = $idToken->name;
			// unused:  gender
			return $user;
		}
		return null;
	}
}
