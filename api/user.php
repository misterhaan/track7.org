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

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'logout', 'logs out the current user.');

		return $endpoints;
	}

	protected static function POST_logout(array $params): void {
		if (!self::IsUserLoggedIn())
			self::Success();  // no errors for logging out of stale login session
		self::$user->Logout(self::RequireDatabase());
		self::Success();
	}
}
UserApi::Respond();
