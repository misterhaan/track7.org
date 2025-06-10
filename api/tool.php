<?php
require_once dirname(__DIR__) . '/etc/class/api.php';

/**
 * Handler for tool API requests.
 */
class ToolApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'gitpull', 'updates track7 to the latest code on github.  must be logged in as the administrator.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'tweetAuthStatus', 'checks the status of the twitter authorization for the site.  must be logged in as the administrator.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'tweetAuthURL', 'gets the url to authenticate with twitter for tweeting site activity.  must be logged in as the administrator.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'tweetAuth', 'completes the twitter authorization process.  must be logged in as the administrator.', 'post');
		$endpoint->BodyParameters[] = new ParameterDocumentation('code', 'string', 'the code returned from twitter after authorization.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('csrf', 'string', 'csrf token to prevent cross-site request forgery.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'tweet', 'sends a tweet with the specified message and url.  must be logged in as the administrator.', 'post');
		$endpoint->BodyParameters[] = new ParameterDocumentation('message', 'string', 'message to tweet.  must be less than 280 characters.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('url', 'string', 'url to include with the tweet.  will be shortened if not already a bit.ly url.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'regexmatch', 'tests a regular expression against a string and returns the matches.', 'post', 'pattern and subject from test form.');
		$endpoint->PathParameters[] = new ParameterDocumentation('all', 'string', 'specify all to return all matches.  otherwise, only the first match is returned.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('pattern', 'string', 'regular expression pattern to match.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('subject', 'string', 'subject to match against.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'regexreplace', 'replaces matches of a regular expression in a string.', 'post', 'pattern, replacement, and subject from test form.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('pattern', 'string', 'regular expression pattern to match.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('replacement', 'string', 'replacement for anything matching the pattern.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('subject', 'string', 'subject to match against.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'timestamp', 'converts a timestamp to various formats.', 'querystring');
		$endpoint->PathParameters[] = new ParameterDocumentation('zone', 'string', 'time zone to use (local or utc).', true);
		$endpoint->PathParameters[] = new ParameterDocumentation('formatted', 'string', 'specify formatted to interpret the value as a formatted date string instead of a timestamp.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('value', 'string', 'timestamp or formatted date string to convert.', true);

		return $endpoints;
	}

	public static function POST_gitpull(): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('git pull is only available to the administrator.');
		chdir($_SERVER['DOCUMENT_ROOT']);
		exec('git pull', $output, $retcode);
		self::Success(new GitPullResult(self::RequireUser(), $retcode, $output));
	}

	public static function GET_tweetAuthStatus(): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('tweet authorization status is only available to the administrator.');
		require_once 'twitter.php';
		self::Success(Twitter::AuthStatus(self::RequireDatabase()));
	}

	public static function GET_tweetAuthURL(): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('tweet authorization url is only available to the administrator.');
		require_once 'twitter.php';
		self::Success(Twitter::AuthorizeURL('/tools/tweet.php'));
	}

	public static function POST_tweetAuth(): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('tweet authorization is only available to the administrator.');
		if (!isset($_POST['code']))
			self::NotFound('code must be specified.');
		$csrf = $_POST['csrf'];
		require_once 'twitter.php';
		self::Success(Twitter::UpdateAuth(self::RequireDatabase(), $csrf, $_POST['code']));
	}

	public static function POST_tweet(): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('tweeting is only available to the administrator.');
		if (!isset($_POST['message']))
			self::NotFound('message must be specified.');
		$message = trim($_POST['message']);
		$url = isset($_POST['url']) ? trim($_POST['url']) : '';
		require_once 'twitter.php';
		self::Success(Twitter::Tweet(self::RequireDatabase(), $message, $url));
	}

	public static function POST_regexmatch(array $params): void {
		$all = array_shift($params) == 'all';
		if (!isset($_POST['pattern'], $_POST['subject']))
			self::NotFound('pattern and subject must be specified.');
		$pattern = trim($_POST['pattern']);
		$subject = trim($_POST['subject']);
		$matches = [];
		if ($all)
			preg_match_all($pattern, $subject, $matches);
		else
			preg_match($pattern, $subject, $matches);
		self::Success($matches);
	}

	public static function POST_regexreplace(): void {
		if (!isset($_POST['pattern'], $_POST['subject']))
			self::NotFound('pattern, and subject must be specified.');
		$pattern = trim($_POST['pattern']);
		$replacement = isset($_POST['replacement']) ? trim($_POST['replacement']) : '';
		$subject = trim($_POST['subject']);
		self::Success(preg_replace($pattern, $replacement, $subject));
	}

	public static function GET_timestamp(array $params): void {
		$zone = 'local';
		$formatted = false;
		$value = isset($_GET['value']) ? $_GET['value'] : null;
		while ($param = array_shift($params)) {
			if ($param == 'local' || $param == 'utc')
				$zone = $param;
			else if ($param == 'formatted')
				$formatted = true;
		}
		if (!$value)
			self::NotFound('value must be specified.');

		require_once 'formatDate.php';
		$user = self::RequireUser();
		if ($formatted) {
			if ($zone == 'local')
				$timestamp = FormatDate::LocalToTimestamp($value, $user);
			else {
				$dt = new DateTime($value, new DateTimeZone('UTC'));
				$timestamp = $dt->getTimestamp();
			}
		} else
			$timestamp = +$value;
		if ($timestamp === false)
			self::NotFound('invalid timestamp or formatted date string.');

		self::Success([
			'timestamp' => $timestamp,
			'smart' => FormatDate::SmartDate($user, $timestamp),
			'ago' => FormatDate::HowLongAgo($timestamp),
			'year' => FormatDate::Local('Y', $timestamp, $user),
			'month' => strtolower(FormatDate::Local('F (n)', $timestamp, $user)),
			'day' => FormatDate::Local('jS', $timestamp, $user),
			'weekday' => strtolower(FormatDate::Local('l', $timestamp, $user)),
			'time' => FormatDate::Local('g:i:s a', $timestamp, $user)
		]);
	}
}

class GitPullResult extends KeyMaster {
	public TimeTagData $Instant;
	public int $ReturnCode;
	public string $Output;
	public array $CacheDelete;
	public ?CloudflareResult $Cloudflare = null;

	public function __construct(CurrentUser $user, int $returnCode, array $output) {
		require_once 'formatDate.php';
		$this->Instant = new TimeTagData($user, 'g:i a', time(), FormatDate::Long);
		$this->ReturnCode = $returnCode;
		$this->Output = implode("\n", $output);
		$this->CacheDelete = [];

		foreach ($output as $line) {
			$parts = explode('|', $line);
			if (count($parts) == 2) {
				$file = trim($parts[0]);
				if (substr($file, -3) == '.js' || in_array(substr($file, -4), ['.css', '.png', '.gif', '.jpg', '.xml', '.txt']) || substr($file, -5) == '.woff') {
					require_once 'formatUrl.php';
					$this->CacheDelete[] = FormatURL::FullUrl('/' . $file);
				}
			}
		}

		if (count($this->CacheDelete)) {
			self::RequireServiceKeys('t7keysCloudflare', 'ID', 'TOKEN');
			$data = new stdClass();
			$data->files = $this->CacheDelete;
			$c = curl_init();
			curl_setopt_array($c, [
				CURLOPT_URL => 'https://api.cloudflare.com/client/v4/zones/' . t7keysCloudflare::ID . '/purge_cache',
				CURLOPT_CUSTOMREQUEST => "DELETE",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_USERAGENT => 'track7.org git pull',
				CURLOPT_CONNECTTIMEOUT => 30,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . t7keysCloudflare::TOKEN, 'Content-Type: application/json'],
				CURLOPT_POSTFIELDS => json_encode($data)
			]);
			$text = curl_exec($c);
			$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
			curl_close($c);
			$this->Cloudflare = new CloudflareResult($code, $text);
		}
	}
}

class CloudflareResult {
	public $Code;
	public $Text;

	public function __construct($code, $text) {
		$this->Code = $code;
		$this->Text = $text;
	}
}

ToolApi::Respond();
