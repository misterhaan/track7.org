<?php
require_once dirname(__DIR__) . '/environment.php';
require_once 'auth.php';

class DeviantartAuth extends Auth {
	private const RequestURL = 'https://www.deviantart.com/oauth2/authorize';
	private const Scope = 'user';
	private const VerifyURL = 'https://www.deviantart.com/oauth2/token';
	private const UserInfoURL = 'https://www.deviantart.com/api/v1/oauth2/user/whoami?expand=user.profile';

	public function __construct() {
		$this->Name = 'deviantart';
	}

	public function Begin(bool $remember, ?string $return): string {
		$this->RequireServiceKeys('t7keysDeviantart', ['CLIENT_ID']);
		$return = $this->GetReturnURL($return);
		$csrf = $this->GetCSRF();
		return self::RequestURL . '?' . http_build_query([
			'response_type' => 'code',
			'client_id' => t7keysDeviantart::CLIENT_ID,
			'redirect_uri' => $this->GetRedirectURL(),
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
			if ($token = $this->GetAccessToken($_GET['code']))
				if ($result->User = $this->GetUserInfo($token, $result))
					$result->LoginMatch = LoginProfile::Find($db, $this->Name, $result->User->ID);
		}
		return $result;
	}

	/**
	 * pass the code from deviantart login back to deviantart over a trusted connection to
	 * retrieve the access token.
	 * @param string $code value returned by deviantart login.
	 */
	private function GetAccessToken(string $code): string {
		$this->RequireServiceKeys('t7keysDeviantart', ['CLIENT_ID', 'CLIENT_SECRET']);
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::VerifyURL . '?' . http_build_query([
			'code' => $code,
			'client_id' => t7keysDeviantart::CLIENT_ID,
			'client_secret' => t7keysDeviantart::CLIENT_SECRET,
			'grant_type' => 'authorization_code',
			'redirect_uri' => $this->GetRedirectURL()
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
		return $response->access_token;
	}

	private function GetUserInfo($access): ?AuthUser {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::UserInfoURL . '&access_token=' . $access);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['SERVER_NAME']);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, false);
		$response = curl_exec($c);
		curl_close($c);
		$response = json_decode($response);
		if (isset($response->userid)) {
			$user = new AuthUser($response->userid);
			$user->Username = $response->username;
			require_once 'contact.php';
			$user->ProfileURL = ContactLink::ExpandURL($this->Name, $response->username);
			$user->Avatar = $response->usericon;  // 50px
			if (isset($response->profile)) {
				$user->DisplayName = $response->profile->real_name;
				$user->Website = $response->profile->website;
			}
			return $user;
		}
		return null;
	}
}
