<?php
require_once 'environment.php';

/**
 * collection of static functions for posting to twitter using oauth2
 */
class Twitter extends KeyMaster {
	private const AuthorizeURL = 'https://x.com/i/oauth2/authorize';
	private const TokenURL = 'https://api.x.com/2/oauth2/token';
	private const TweetURL = 'https://api.x.com/2/tweets';
	private const TweetLength = 280;

	/**
	 * checks the status of twitter auth tokens.
	 * @param mysqli $db database connection
	 * @return RefreshableTokenStatus status of twitter auth tokens
	 * @throws DetailedException for database errors
	 */
	public static function AuthStatus(mysqli $db): RefreshableTokenStatus {
		try {
			$select = $db->prepare('select type, unix_timestamp(expires) from token where service=\'twitter\' and type in (\'access\', \'refresh\')');
			$select->execute();
			$select->bind_result($type, $expires);
			$status = new RefreshableTokenStatus();
			while ($select->fetch()) {
				switch ($type) {
					case 'access':
						$status->Access = TokenStatus::WithExpiration($expires);
						break;
					case 'refresh':
						$status->Refresh = TokenStatus::WithExpiration($expires);
						break;
				}
			}
			return $status;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error checking twitter auth status', $mse);
		}
	}

	/**
	 * returns the url to authorize the application with twitter
	 * @param string $redirect url to redirect to after authorization -- must be a relative root url that will match twitter application settings
	 * @return string url to authorize the application with twitter
	 * @throws DetailedException if twitter client id is not set
	 */
	public static function AuthorizeURL(string $redirect) {
		self::RequireServiceKeys('KeysTwitter', 'ClientID');
		require_once 'auth.php';
		$csrf = Auth::GetCSRF();
		$verifier = CodeVerifier::GenerateHash();
		require_once 'formatUrl.php';

		return self::AuthorizeURL . '?' . http_build_query([
			'response_type' => 'code',
			'client_id' => KeysTwitter::ClientID,
			'redirect_uri' => FormatURL::FullUrl($redirect),
			'scope' => 'tweet.write tweet.read users.read offline.access',
			'state' => $csrf,
			'code_challenge' => $verifier,
			'code_challenge_method' => 'S256'
		]);
	}

	/**
	 * updates the twitter auth tokens based on an authorization request code.
	 * @param mysqli $db database connection
	 * @param string $csrf csrf token to prevent cross-site request forgery
	 * @param string $code code returned from twitter after authorization
	 * @return RefreshableTokenStatus status of the updated twitter auth tokens
	 * @throws DetailedException for database, csrf, or code verification errors
	 */
	public static function UpdateAuth(mysqli $db, string $csrf, string $code): RefreshableTokenStatus {
		require_once 'auth.php';
		if (!Auth::CheckCSRF($csrf))
			throw new DetailedException('csrf token missing or invalid');
		$verifier = CodeVerifier::Pop();
		$refreshableToken = self::GetRefreshableAuthToken($code, $verifier);
		self::SaveAuth($db, $refreshableToken);
		return RefreshableTokenStatus::FromResult($refreshableToken);
	}

	private static function GetRefreshableAuthToken(string $code, string $verifier): object {
		require_once 'formatUrl.php';
		return self::AuthToken([
			'code' => $code,
			'grant_type' => 'authorization_code',
			'redirect_uri' => FormatURL::FullUrl('/tools/tweet.php'),
			'code_verifier' => $verifier
		], 'twitter authentication failed');
	}

	private static function RefreshAuthToken(string $refreshToken): object {
		return self::AuthToken([
			'refresh_token' => $refreshToken,
			'grant_type' => 'refresh_token'
		], 'refreshing twitter authentication failed');
	}

	private static function AuthToken(array $fields, string $failMessage): object {
		self::RequireServiceKeys('KeysTwitter', 'ClientID', 'ClientSecret');
		$c = curl_init();
		curl_setopt_array($c, [
			CURLOPT_URL => self::TokenURL,
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTPHEADER => ['Authorization: Basic ' . base64_encode(KeysTwitter::ClientID . ':' . KeysTwitter::ClientSecret)],
			CURLOPT_POSTFIELDS => http_build_query($fields)
		]);
		$response = curl_exec($c);
		curl_close($c);
		$tokens = json_decode($response);
		if (isset($tokens->error))
			throw new DetailedException($failMessage, $tokens->error . ' - ' . $tokens->error_description);
		return $tokens;
	}

	private static function SaveAuth(mysqli $db, object $refreshableToken): void {
		try {
			$replace = $db->prepare('replace into token (service, type, token, scope, expires) values (\'twitter\', \'access\', ?, ?, date_add(now(), interval ? second)), (\'twitter\', \'refresh\', ?, ?, date_add(now(), interval 6 month))');
			$replace->bind_param('ssiss', $refreshableToken->access_token, $refreshableToken->scope, $refreshableToken->expires_in, $refreshableToken->refresh_token, $refreshableToken->scope);
			$replace->execute();
		} catch (mysqli_sql_exception $mse) {
			$db->rollback();
			throw DetailedException::FromMysqliException('error saving twitter auth tokens', $mse);
		}
	}

	private static function GetAccessToken(mysqli $db): string {
		try {
			$select = $db->prepare('select token, unix_timestamp(expires) from token where service=\'twitter\' and type=\'access\'');
			$select->execute();
			$select->bind_result($token, $expiration);
			if ($select->fetch() && $expiration > time()) {
				$select->close();
				return $token;
			}
			$select->close();

			$select = $db->prepare('select token, unix_timestamp(expires) from token where service=\'twitter\' and type=\'refresh\'');
			$select->execute();
			$select->bind_result($token, $expiration);
			if ($select->fetch() && $expiration > time()) {
				$refreshableToken = self::RefreshAuthToken($token);
				self::SaveAuth($db, $refreshableToken);
				$select->close();
				return $refreshableToken->access_token;
			}
			$select->close();

			throw new DetailedException('could not obtain twitter access token.  the administrator needs to authorize the application with twitter before tweets can be sent.');
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error getting twitter access token', $mse);
		}
	}

	/**
	 * Sends a message to Twitter to be posted as a tweet.
	 * @param mysqli $db database connection
	 * @param string $message message to post to twitter as a tweet
	 * @param string $url url to include with tweet (optional, will be shortened)
	 * @return object response from twitter with code and text fields
	 */
	public static function Tweet(mysqli $db, $message, $url = ''): object {
		// fix up the message and add / shorten the url if present
		if ($url) {
			if (substr($url, 0, 13) != 'https://bit.ly') {
				require_once 'formatUrl.php';
				$url = FormatURL::Shorten($url);
			}
			if (mb_strlen($message) + strlen($url) + 1 > self::TweetLength)
				$message = mb_substr($message, 0, self::TweetLength - strlen($url) - 2) . '… ' . $url;
			else
				$message .= ' ' . $url;
		} elseif (mb_strlen($message) > self::TweetLength)
			$message = mb_substr($message, 0, self::TweetLength);

		$token = self::GetAccessToken($db);
		$c = curl_init();
		curl_setopt_array($c, [
			CURLOPT_URL => self::TweetURL,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode(['text' => $message]),
			CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json;charset=UTF-8'],
		]);
		$response = new stdClass();
		$response->text = curl_exec($c);
		$response->code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		curl_close($c);
		return $response;
	}
}

class RefreshableTokenStatus {
	public TokenStatus $Access;
	public TokenStatus $Refresh;

	public function __construct() {
		$this->Access = TokenStatus::Missing();
		$this->Refresh = TokenStatus::Missing();
	}

	public static function FromResult(object $result): self {
		$status = new self();
		if (isset($result->access_token) && isset($result->expires_in))
			$status->Access = TokenStatus::WithExpiration(time() + $result->expires_in);
		if (isset($result->refresh_token))
			$status->Refresh = TokenStatus::WithExpiration(time() + 15778463); // 6 months, which isn't part of the response
		return $status;
	}
}

class TokenStatus {
	public bool $Exists;
	public bool $Expired;
	public string $ExpiresIn;

	private function __construct(bool $exists, bool $expired, string $expiresIn) {
		$this->Exists = $exists;
		$this->Expired = $expired;
		$this->ExpiresIn = $expiresIn;
	}

	public static function WithExpiration(?int $expiration): self {
		require_once 'formatDate.php';
		$expired = $expiration ? $expiration <= time() : false;
		$expiresIn = FormatDate::HowLongAgo($expiration);
		return new self(true, $expired, $expiresIn);
	}

	public static function Missing(): self {
		return new self(false, false, '');
	}
}
