<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'user.php';

/**
 * Handler for setting API requests.
 */
class SettingsApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'profile', 'retrieves profile settings for the current user.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'profile', 'saves profile settings for the current user.', 'multipart');
		$endpoint->BodyParameters[] = new ParameterDocumentation('username', 'string', 'username for the user.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('displayname', 'string', 'display name for the user.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('avatarsource', 'string', 'avatar source for the user.  can be current, none, gravatar, or one of the linked login accounts.  defaults to current.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'time', 'retrieves time settings for the current user.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'time', 'saves time settings for the current user.', 'multipart');
		$endpoint->BodyParameters[] = new ParameterDocumentation('currenttime', 'string', 'current time for the user.  should be formatted such as 3:27 pm.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('dst', 'string', 'whether the user observes daylight saving time.  should be true if the clocks change, regardless of whether daylight saving time is active.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'contacts', 'retrieves contact methods for the current user.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'contacts', 'saves contact methods for the current user.', 'json', 'send a json array of contact methods, each with the following properties (or an empty array to remove all).');
		$endpoint->BodyParameters[] = new ParameterDocumentation('type', 'string', 'type of contact method.  must be one of the supported types.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('value', 'string', 'value of the contact method.  must be appropriate for the type; could be an e-mail address, url, username, or user id.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('visibility', 'string', 'visibility of the contact method.  must be one of none, friends, users, or all.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'notification', 'retrieves notification settings for the current user.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'notification', 'saves notification settings for the current user.', 'multipart');
		$endpoint->BodyParameters[] = new ParameterDocumentation('emailnewmessage', 'boolean', 'whether to send an e-mail when a message is received.  defaults to false.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'logins', 'retrieves notification settings for the current user.');

		$endpoints[] = $endpoint = new EndpointDocumentation('DELETE', 'login', 'remove a login account from the current user.  must have a password or at least one remaining login account.');
		$endpoint->PathParameters[] = new ParameterDocumentation('provider', 'string', 'provider of the login account to remove.  should be one of the supported login providers.', true);
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'id of the login account to remove.  should match one of the user’s accounts with the specified provider.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('DELETE', 'password', 'remove password from the current user.  must have at least one login account.');

		return $endpoints;
	}

	protected static function GET_profile(): void {
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to get your profile settings.');
		self::Success(self::$user->GetProfileSettings(self::RequireDatabase()));
	}

	protected static function POST_profile(): void {
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to change your profile settings.');
		if (!isset($_POST['username']))
			self::NotFound('username is required.');
		$username = trim($_POST['username']);
		$displayname = isset($_POST['displayname']) ? trim($_POST['displayname']) : '';
		$avatarsource = isset($_POST['avatarsource']) ? trim($_POST['avatarsource']) : 'current';
		self::Success(self::$user->SaveProfileSettings(self::RequireDatabase(), $username, $displayname, $avatarsource));
	}

	protected static function GET_time(): void {
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to get your time settings.');
		self::Success(self::$user->GetTimeSettings());
	}

	protected static function POST_time(): void {
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to change your time settings.');
		if (!isset($_POST['currenttime']))
			self::NotFound('current time is required.');
		$currenttime = trim($_POST['currenttime']);
		$dst = isset($_POST['dst']) && boolval(trim($_POST['dst'])) && strtolower(trim($_POST['dst'])) != 'false';
		self::Success(self::$user->SaveTimeSettings(self::RequireDatabase(), $currenttime, $dst));
	}

	protected static function GET_contacts(): void {
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to get your contact methods.');
		self::Success(self::$user->GetContactMethods(self::RequireDatabase()));
	}

	protected static function POST_contacts(): void {
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to change your contact methods.');
		$contacts = self::ReadRequestJsonObject();
		self::Success(self::$user->SaveContactMethods(self::RequireDatabase(), $contacts));
	}

	protected static function GET_notification(): void {
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to get your notification settings.');
		self::Success(self::$user->GetNotificationSettings(self::RequireDatabase()));
	}

	protected static function POST_notification(): void {
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to change your notification settings.');
		$sendemail = isset($_POST['emailnewmessage']) ? boolval(trim($_POST['emailnewmessage'])) && strtolower(trim($_POST['emailnewmessage'])) != 'false' : false;
		self::Success(self::$user->SaveNotificationSettings(self::RequireDatabase(), $sendemail));
	}

	protected static function GET_logins(): void {
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to get your login settings.');
		self::Success(self::$user->GetLoginSettings(self::RequireDatabase()));
	}

	protected static function DELETE_login(array $params): void {
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to remove a login account.');
		$provider = trim(array_shift($params));
		$id = trim(array_shift($params));
		if (!$provider || !$id)
			self::NotFound('provider and id are required.');
		self::Success(self::$user->RemoveLogin(self::RequireDatabase(), $provider, $id));
	}

	protected static function DELETE_password(): void {
		if (!self::IsUserLoggedIn())
			self::Forbidden('you must be signed in to remove your password.');
		self::Success(self::$user->RemovePassword(self::RequireDatabase()));
	}
}
SettingsApi::Respond();
