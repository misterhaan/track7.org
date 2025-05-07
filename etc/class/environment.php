<?php
// PHP should treat strings as UTF8
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// other files will be included from the same directory as this file
set_include_path(dirname(__FILE__));

// active user is tracked in session
session_start();

/**
 * Base class for sending a response to an HTTP request.
 */
abstract class Responder extends KeyMaster {
	protected static ?mysqli $db = null;
	protected static ?CurrentUser $user = null;

	/**
	 * Show an error.  Details are only showed to administrators.
	 * @param DetailedException|string $error Exception with details or non-detailed error message
	 * @param ?string $detail Extra detail for administrators.  Not used when $error is a DetailedException
	 */
	protected abstract static function DetailedError(mixed $error, ?string $detail = null): void;

	/**
	 * Check if the current user has administrator security.
	 */
	protected static function HasAdminSecurity(): bool {
		self::RequireUser();
		return self::$user->IsAdmin();
	}

	/**
	 * Check if the current user has trusted (or better) security.
	 */
	protected static function IsUserTrusted(): bool {
		self::RequireUser();
		return self::$user->IsTrusted();
	}

	/**
	 * Check if the current user is logged in.
	 */
	protected static function IsUserLoggedIn(): bool {
		self::RequireUser();
		return self::$user->IsLoggedIn();
	}

	/**
	 * Look up the current user for logic that requires it.  User object available as self::$user after this call.
	 */
	protected static function RequireUser(): CurrentUser {
		if (!self::$user) {
			require_once 'user.php';
			self::RequireDatabase();
			try {
				self::$user = new CurrentUser(self::$db);
				return self::$user;
			} catch (DetailedException $de) {
				// if there's an error creating the user we're going to ignore it and just say nobody's logged in
			}
			self::$user = new CurrentUser();
		}
		return self::$user;
	}

	/**
	 * Gets the database connection object.
	 */
	protected static function RequireDatabase(): mysqli {
		if (!self::$db) {
			self::RequireDatabaseKeys();
			try {
				mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);  // default as of PHP 8.1
				self::$db = new mysqli(t7keysDB::HOST, t7keysDB::USER, t7keysDB::PASS, t7keysDB::NAME);
				if (!self::$db->connect_errno) {
					self::$db->real_query('set names \'utf8mb4\'');
					self::$db->set_charset('utf8mb4');
					return self::$db;
				} else
					self::DetailedError('error connecting to database', self::$db->errno . ' ' . self::$db->error);
			} catch (mysqli_sql_exception $mse) {
				self::DetailedError(DetailedException::FromMysqliException('error connectiing to database', $mse));
			}
		}
		return self::$db;
	}

	/**
	 * Ensures database connection information is available before continuing.
	 */
	protected static function RequireDatabaseKeys() {
		self::RequireServiceKeys('t7keysDB', 'HOST', 'NAME', 'USER', 'PASS');
	}
}

/**
 * Base class for classes that need access to a service protected by keys.
 */
class KeyMaster {
	/**
	 * Ensures the keys file exists and can be loaded.
	 **/
	private static function RequireKeys() {
		require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/.t7keys.php';
	}

	/**
	 * Ensures service connection information is available before continuing.
	 * @param string $keyClass Name of the class that should contain the required keys
	 * @param array ...$keys Names of the class constants that contain the required keys
	 */
	protected static function RequireServiceKeys(string $keyClass, string|array ...$keys): void {
		self::RequireKeys();
		if (!class_exists($keyClass))
			throw new DetailedException('keys not specified or incomplete', "class:  $keyClass");
		foreach ($keys as $key) {
			if (!defined("$keyClass::$key"))
				throw new DetailedException('keys not specified or incomplete', "key:  $key");
		}
	}
}

/**
 * An exception that can contain additional details.
 */
class DetailedException extends RuntimeException {
	/**
	 * Additional details about this exception.  Technically an exception with null or empty details isn't detailed, but it can be used that way.
	 */
	protected ?string $details;

	/**
	 * @param string $message The main (non-detailed) exception message
	 * @param ?string $details Additional details about the exception
	 * @param int $code The exception code
	 * @param ?Throwable $previous The previous throwable used for exception chaining
	 */
	public function __construct(string $message, ?string $details = '', int $code = 0, ?Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
		$this->details = $details;
	}

	/**
	 * Create a DetailedException from a MySQLi exception.
	 * @param string $message The main (non-detailed) exception message
	 * @param mysqli_sql_exception $mysqliException MySQLi exception which provides the exception details
	 * @return DetailedException Detailed exception
	 */
	public static function FromMysqliException(string $message, mysqli_sql_exception $mysqliException): DetailedException {
		return new DetailedException($message, $mysqliException->getCode() . ' ' . $mysqliException->getMessage(), $mysqliException->getCode(), $mysqliException);
	}

	/**
	 * Gets the exception message combined with the details (if any).
	 * @return string Exception message with details
	 */
	public function getDetailedMessage(): string {
		if ($this->details)
			return rtrim($this->message, '.') . ":  $this->details";
		return $this->getMessage();
	}
}

/** Result object returned by validation functions */
class ValidationResult {
	/** Validation state, valid or invalid */
	public string $State;
	/** Reason why the result is invalid, or maybe a message about being valid */
	public string $Message = '';
	/** Value that should replace the validated value in case the validator changed the formatting */
	public string $NewValue = '';

	public function __construct(string $state, string $message = '', string $newValue = '') {
		$this->State = $state;
		$this->Message = $message;
		$this->NewValue = $newValue;
	}
}

/**
 * Pairing of a title and a URL
 */
class TitledLink {
	/**
	 * Link title
	 */
	public string $Title = '';
	/**
	 * Link URL
	 */
	public string $URL = '';

	/**
	 * Default constructor
	 * @param $title Link title
	 * @param $url Link URL
	 */
	public function __construct(string $title, string $url) {
		$this->Title = $title;
		$this->URL = $url;
	}
}
