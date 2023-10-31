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
	public static function Respond() {
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
			require_once 't7.php';
			$html = new t7html();
			$name = substr($_SERVER['SCRIPT_NAME'], 5, -4);  // five for '/api/' and -4 for '.php'
			$html->Open($name . ' api');
?>
			<h1><?= $name; ?> api</h1>
<?php
			static::ShowDocumentation($html);
			$html->Close();
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
	 * Send a successful response.
	 * @param mixed $data Response data (optional)
	 */
	protected static function Success($data = true) {
		header('Content-Type: application/json');
		die(json_encode($data));
	}

	/**
	 * Write out the documentation for the API controller.  The page is already
	 * opened with an h1 header, and will be closed after the call completes.
	 */
	protected abstract static function ShowDocumentation();

	/**
	 * End the request with a detailed error.  Details are only showed to administrators.
	 * @param DetailedException|string $error Exception with details or non-detailed error message
	 * @param ?string $detail Extra detail for administrators.  Not used when $error is a DetailedException
	 */
	protected static function DetailedError(mixed $error, string $detail = null) {
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
	protected static function NotFound(string $message = '') {
		http_response_code(404);
		header('Content-Type: text/plain');
		die($message);
	}
}
