<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'user.php';

/**
 * Handler for user API requests.
 */
class UserApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves all site users.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'suggest', 'retrieves a list of users that match the supplied search text.', 'plain text', 'send the search text as the request body.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'info', 'retrieves information about a user by username.');
		$endpoint->PathParameters[] = new ParameterDocumentation('username', 'string', 'username to look up.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'registration', 'retrieves registration information validated by an external authentication provider.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'register', 'registers a new user.  requires previous authentication through an external login provider.', 'form data', 'send the registration information as form data in the request body.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('csrf', 'string', 'cross-site request forgery token to validate the request.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('username', 'string', 'username for the new user.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('displayname', 'string', 'display name for the new user.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('email', 'string', 'e-mail address for the new user.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('website', 'string', 'website for the new user.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('linkprofile', 'boolean', 'whether the external profile should be linked from the track7 profile.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('useavatar', 'boolean', 'whether to use the avatar image from the external profile.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'idAvailable', 'checks if a username is valid and available.  this means not in use or already used by the specified user.', 'plain text', 'send the proposed new username as the request body.');
		$endpoint->PathParameters[] = new ParameterDocumentation('oldId', 'string', 'curent username of the user that might be changing its username.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'nameAvailable', 'checks if a display name is valid and available.  this means not in use or already used by the specified user.', 'plain text', 'send the proposed new display name as the request body.');
		$endpoint->PathParameters[] = new ParameterDocumentation('oldName', 'string', 'curent display name of the user that might be changing its name.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'login', 'logs in a user with a username and password.');
		$endpoint->PathParameters[] = new ParameterDocumentation('remember', 'boolean', 'whether the user wants the login remembered.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'auth', 'retrieves the URL to authenticate with an external login provider.');
		$endpoint->PathParameters[] = new ParameterDocumentation('provider', 'string', 'name of the external login provider to authenticate with.', true);
		$endpoint->PathParameters[] = new ParameterDocumentation('remember', 'boolean', 'whether the user wants the login remembered.');

		$endpoints[] = $endpoint = new EndpointDocumentation('PUT', 'friend', 'adds a user as a friend for the logged-in user.  requires authentication.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'user id of the friend to add.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('DELETE', 'friend', 'removes a user from friends list of the logged-in user.  requires authentication.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'user id of the friend to remove.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'logout', 'logs out the current user.');

		return $endpoints;
	}

	/**
	 * get latest site activity.
	 */
	protected static function GET_list(): void {
		self::Success(DetailedUser::List(self::RequireDatabase(), self::RequireUser()));
	}

	protected static function POST_suggest(): void {
		$match = trim(self::ReadRequestText());
		if (strlen($match) < 3)
			self::NotFound('at least 3 characters are required to suggest users.');
		self::Success(MatchingUser::Suggest(self::RequireDatabase(), self::RequireUser(), $match));
	}

	protected static function GET_info(array $params): void {
		$username = trim(array_shift($params));
		if (!$username)
			self::NotFound('username must be specified.');
		self::Success(new User(self::RequireDatabase(), $username));
	}

	protected static function GET_registration(): void {
		require_once 'auth.php';
		if (!isset($_SESSION['registering']))
			self::NotFound('no registration in progress.');
		require_once 'auth.php';
		self::Success([
			'Provider' => $_SESSION['registering']['provider'],
			'Username' => $_SESSION['registering']['username'],
			'DisplayName' => $_SESSION['registering']['displayname'],
			'Email' => $_SESSION['registering']['email'],
			'Website' => $_SESSION['registering']['website'],
			'Avatar' => $_SESSION['registering']['avatar'],
			'ProfileURL' => $_SESSION['registering']['profile'],
			'CSRF' => Auth::GetCSRF()
		]);
	}

	protected static function POST_register(): void {
		if (!isset($_SESSION['registering']))
			self::NotFound('no authentication information found.');
		require_once 'auth.php';
		self::Success(Auth::Register(self::RequireDatabase(), self::RequireUser()));
	}

	/**
	 * Check if a username is available.
	 * @param array $params Current username, if any.
	 */
	protected static function POST_idAvailable(array $params): void {
		$oldID = trim(array_shift($params));
		$newID = self::ReadRequestText();
		if ($oldID == $newID)
			self::Success(new ValidationResult('valid'));
		self::Success(User::IdAvailable(self::RequireDatabase(), self::RequireUser(), $newID));
	}

	protected static function POST_nameAvailable(array $params): void {
		$oldName = trim(array_shift($params));
		$newName = self::ReadRequestText();
		if ($oldName == $newName)
			self::Success(new ValidationResult('valid'));
		self::Success(User::NameAvailable(self::RequireDatabase(), self::RequireUser(), $newName));
	}

	protected static function POST_login(array $params): void {
		$remember = trim(array_shift($params));
		$remember = boolval($remember) && strtolower($remember) != 'false';
		$user = CurrentUser::PasswordLogin(self::RequireDatabase(), $remember);
		if (!$user->IsLoggedIn())
			self::Forbidden('invalid username and / or password.');
		self::Success();
	}

	protected static function GET_auth(array $params): void {
		$site = trim(array_shift($params));
		if (!$site)
			self::NotFound('login provider must be specified.');
		$remember = trim(array_shift($params));
		$remember = boolval($remember) && strtolower($remember) != 'false';
		require_once 'auth.php';
		self::Success(Auth::Provider($site)->Begin($remember));
	}

	protected static function PUT_friend(array $params): void {
		$friend = +array_shift($params);
		if (!$friend)
			self::NotFound('friend id must be specified.');
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to add a friend.');
		if (self::$user->ID == $friend)
			self::Forbidden('you cannot add yourself as a friend.');
		self::Success(Friend::Add(self::RequireDatabase(), self::RequireUser(), $friend));
	}

	protected static function DELETE_friend(array $params): void {
		$friend = +array_shift($params);
		if (!$friend)
			self::NotFound('friend id must be specified.');
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to remove a friend.');
		self::Success(Friend::Remove(self::RequireDatabase(), self::RequireUser(), $friend));
	}

	protected static function POST_logout(): void {
		if (!self::IsUserLoggedIn())
			self::Success();  // no errors for logging out of stale login session
		self::$user->Logout(self::RequireDatabase());
		self::Success();
	}
}
UserApi::Respond();
