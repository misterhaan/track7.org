<?php
require_once dirname(__DIR__) . '/environment.php';
require_once 'auth.php';

class SteamAuth extends Auth {
	private const RequestURL = 'https://steamcommunity.com/openid/login';
	private const OpenIdNS = 'http://specs.openid.net/auth/2.0';
	private const OpenIdIdentity = 'http://specs.openid.net/auth/2.0/identifier_select';
	private const ProfileURL = 'https://steamcommunity.com/profiles/';  // append the steam id and a forward slash
	private const AvatarPrefixHTTPS = 'https://steamcdn-a.akamaihd.net/';


	public function __construct() {
		$this->Name = 'steam';
	}

	public function Begin(bool $remember, ?string $return): string {
		require_once 'formatUrl.php';
		$return = $this->GetReturnURL($return);
		$csrf = $this->GetCSRF();
		return self::RequestURL . '?' . http_build_query([
			'openid.ns' => self::OpenIdNS,
			'openid.mode' => 'checkid_setup',
			'openid.return_to' => $this->GetRedirectURL() . '?' . ($remember ? 'remember&' : '') . http_build_query(['continue' => $return, 'csrf' => $csrf]),
			'openid.realm' => FormatURL::FullUrl('/'),
			'openid.identity' => self::OpenIdIdentity,
			'openid.claimed_id' => self::OpenIdIdentity
		]);
	}

	public function Process(mysqli $db): ?AuthResult {
		if (!isset($_GET['openid_claimed_id']))
			return null;

		$result = new AuthResult();
		if ($result->IsValid = isset($_GET['csrf']) && $this->CheckCSRF($_GET['csrf']) && $this->Validate()) {
			if (isset($_GET['continue']) && $_GET['continue'])
				$result->Continue = $this->GetReturnURL($_GET['continue']);
			$this->Remember = isset($_GET['remember']);
			if ($result->User = $this->GetUserInfo())
				$result->LoginMatch = LoginProfile::Find($db, $this->Name, $result->User->ID);
		}
		return $result;
	}

	/**
	 * validate that authentication information actually came from steam.
	 */
	private function Validate() {
		$data = [
			'openid.assoc_handle' => $_GET['openid_assoc_handle'],
			'openid.signed' => $_GET['openid_signed'],
			'openid.sig' => $_GET['openid_sig'],
			'openid.ns' => self::OpenIdNS,
			'openid.mode' => 'check_authentication'
		];
		foreach (explode(',', $_GET['openid_signed']) as $var)
			$data['openid.' . $var] = $_GET['openid_' . str_replace('.', '_', $var)];
		$c = curl_init();
		curl_setopt_array($c, [
			CURLOPT_URL => self::RequestURL,
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
		$resarr = [];
		foreach (explode("\n", $response) as $line) {
			$varval = explode(':', $line, 2);
			if (count($varval) == 2)
				$resarr[trim($varval[0])] = trim($varval[1]);
		}
		// verification result should contain the same NS we sent, plus an is_valid value which should be true
		return isset($resarr['ns']) && $resarr['ns'] == self::OpenIdNS && isset($resarr['is_valid']) && $resarr['is_valid'] == 'true';
	}

	private function GetUserInfo(): ?AuthUser {
		$id = explode('/', $_GET['openid_claimed_id']);
		$id = $id[count($id) - 1];
		$c = curl_init();
		curl_setopt_array($c, [
			CURLOPT_URL => self::ProfileURL . $id . '/?xml=1',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => $_SERVER['SERVER_NAME'],
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HEADER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 5
		]);
		$response = curl_exec($c);
		$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		curl_close($c);
		if ($code == 200 && $xml = simplexml_load_string($response))
			if (!isset($xml->error)) {
				$user = new AuthUser($id);
				$user->DisplayName = html_entity_decode((string)$xml->steamID);  // should use UTF-8
				if (isset($xml->customURL))
					$user->Username = (string)$xml->customURL;
				require_once 'contact.php';
				$user->ProfileURL = ContactLink::ExpandURL($this->Name, isset($xml->customURL) ? (string)$xml->customURL : (string)$xml->steamID64);
				if ($xml->avatarMedium)
					$user->Avatar = (string)$xml->avatarMedium[0];  // 64px
				return $user;
			}
		return null;
	}
}
