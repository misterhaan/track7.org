<?php
require_once 'environment.php';

/**
 * Base class for API controllers.  Requests are formed as
 * [controller]/[endpoint] with any required parameters separated by / after
 * the endpoint, and served by a function named [method]_[endpoint] in the Api
 * class in [controller].php.
 * @author misterhaan
 */
abstract class Api extends Responder {
	/**
	 * Respond to an API request or show API documentation.
	 */
	public static function Respond(): void {
		if (isset($_SERVER['PATH_INFO']) && substr($_SERVER['PATH_INFO'], 0, 1) == '/') {
			$method = $_SERVER['REQUEST_METHOD'];
			if (in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
				$params = explode('/', substr($_SERVER['PATH_INFO'], 1));
				$method .= '_' . array_shift($params);  // turn the HTTP method and the endpoint into a php method name
				if (method_exists(static::class, $method))
					try {
						static::$method($params);
					} catch (DetailedException $de) {
						self::DetailedError($de);
					} catch (mysqli_sql_exception $mse) {
						self::DetailedError(DetailedException::FromMysqliException('database error', $mse));
					}
				else
					self::NotFound('requested endpoint does not exist on this controller or requires a different request method.');
			} else
				self::NotFound("method $method is not supported.");
		} else {
			require_once 'apiDocPage.php';
			new ApiDocPage(static::class);
		}
	}

	/**
	 * Read the request body as plain text.
	 */
	protected static function ReadRequestText(): string {
		$fp = fopen("php://input", "r");
		$text = '';
		while ($data = fread($fp, 1024))
			$text .= $data;
		return $text;
	}

	/**
	 * Sends a message to Twitter to be posted as a tweet.
	 * @param string $message Tweet message
	 * @param ?string $url URL to include with tweet (optional; sent through shortener)
	 */
	protected static function Tweet(string $message, ?string $url = ''): void {
		// TODO:  migrate t7send
		require_once 't7send.php';
		t7send::Tweet($message, $url);
	}

	/**
	 * Send a successful response.
	 * @param mixed $data Response data (optional)
	 */
	protected static function Success($data = true): void {
		header('Content-Type: application/json');
		die(json_encode($data));
	}

	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public abstract static function GetEndpointDocumentation(): array;

	/**
	 * End the request with a detailed error.  Details are only showed to administrators.
	 * @param DetailedException|string $error Exception with details or non-detailed error message
	 * @param ?string $detail Extra detail for administrators.  Not used when $error is a DetailedException
	 */
	protected static function DetailedError(mixed $error, string $detail = null): void {
		http_response_code(500);
		header('Content-Type: text/plain');
		if (self::HasAdminSecurity())
			if ($error instanceof DetailedException)
				die($error->getDetailedMessage());
			elseif ($detail)
				die("$error:  $detail");
			else
				die($error);
		elseif ($error instanceof DetailedException)
			die($error->getMessage());
		else
			die($error . '.');
	}

	/**
	 * Mark the request as not found.  This probably only makes sense for get
	 * requests that look up an item by a key.
	 * @param string $message short message describing what was not found
	 */
	protected static function NotFound(string $message = ''): void {
		http_response_code(404);
		header('Content-Type: text/plain');
		die($message);
	}

	/**
	 * Mark the request as forbidden.  This generally applies when the request requires administrator permissions.
	 * @param string $message short message describing what was forbidden
	 */
	protected static function Forbidden(string $message = ''): void {
		http_response_code(403);
		header('Content-Type: text/plain');
		die($message);
	}
}

class EndpointDocumentation {
	public string $Method;
	public string $Name;
	public string $Documentation;
	public array $PathParameters = [];
	public string $BodyFormat;
	public string $BodyDocumentation;
	public array $BodyParameters = [];

	public function __construct(string $method, string $name, string $documentation, string $bodyFormat = 'none', string $bodyDocumentation = '') {
		$this->Method = $method;
		$this->Name = $name;
		$this->Documentation = $documentation;
		$this->BodyFormat = $bodyFormat;
		$this->BodyDocumentation = $bodyDocumentation;
	}
}

class ParameterDocumentation {
	public string $Name;
	public string $Type;
	public string $Documentation;
	public bool $Required;

	public function __construct(string $name, string $type, string $documentation, bool $required = false) {
		$this->Name = $name;
		$this->Type = $type;
		$this->Documentation = $documentation;
		$this->Required = $required;
	}
}
