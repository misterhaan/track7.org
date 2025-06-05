<?php
require_once dirname(__DIR__) . '/environment.php';
require_once 'auth.php';

class TwitchAuth extends Auth {
	private const RequestURL = 'https://id.twitch.tv/oauth2/authorize';
	private const VerifyURL = 'https://id.twitch.tv/oauth2/token';
	private const Scope = 'openid user:read:email';

	public function __construct() {
		$this->Name = 'twitch';
	}

	public function Begin(bool $remember, ?string $return): string {
		self::RequireServiceKeys('KeysTwitch', 'ClientID');
		$return = $this->GetReturnURL($return);
		$csrf = $this->GetCSRF();
		return self::RequestURL . '?' . http_build_query([
			'claims' => json_encode(['id_token' => ['email' => null, 'picture' => null, 'preferred_username' => null]]),
			'client_id' => KeysTwitch::ClientID,
			'nonce' => $csrf,
			'redirect_uri' => $this->GetRedirectURL(),
			'response_type' => 'code',
			'scope' => self::Scope,
			'state' => ($remember ? 'remember&' : '') . http_build_query(['continue' => $return])
		]);
	}

	public function Process(mysqli $db): ?AuthResult {
		if (!isset($_GET['state'], $_GET['code']))
			return null;

		$result = new AuthResult();

		if ($tokens = $this->GetTokens($_GET['code'])) {
			$id = json_decode(base64_decode(explode('.', $tokens->id_token)[1]));
			$result->IsValid = isset($id->nonce) && $this->CheckCSRF($id->nonce);
			if ($result->User = $this->GetUserInfo($id))
				$result->LoginMatch = LoginProfile::Find($db, $this->Name, $result->User->ID);
		}
		parse_str($_GET['state'], $state);
		if (isset($state['continue']) && $state['continue'])
			$result->Continue = $this->GetReturnURL($state['continue']);
		$result->Remember = isset($state['remember']);

		return $result;
	}

	private function GetTokens(string $code): ?object {
		self::RequireServiceKeys('KeysTwitch', 'ClientID', 'ClientSecret');
		$data = [
			'client_id' => KeysTwitch::ClientID,
			'client_secret' => KeysTwitch::ClientSecret,
			'code' => $code,
			'grant_type' => 'authorization_code',
			'redirect_uri' => $this->GetRedirectUrl()
		];
		$c = curl_init();
		curl_setopt_array($c, [
			CURLOPT_URL => self::VerifyURL,
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => $_SERVER['SERVER_NAME'],
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HEADER => false,
			CURLOPT_POSTFIELDS => http_build_query($data)
		]);
		$response = curl_exec($c);
		curl_close($c);
		$tokens = json_decode($response);
		return $tokens;
	}

	/**
	 * get more user info to register this user here.
	 */
	public static function GetUserInfo(object $idToken): ?AuthUser {
		if ($idToken->sub) {
			$user = new AuthUser($idToken->sub);
			if ($idToken->email)
				$user->Email = $idToken->email;
			if ($idToken->preferred_username) {
				$user->Username = $idToken->preferred_username;
				$user->ProfileURL = 'https://www.twitch.tv/' . $idToken->preferred_username;
			}
			if ($idToken->picture)
				$user->Avatar = $idToken->picture;
			return $user;
		}
		return null;
	}
}
