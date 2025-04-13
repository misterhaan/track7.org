<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'contact.php';

/**
 * Handler for contact API requests.
 */
class ContactApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves contact links for the specified user.  results can vary based on who’s asking.');
		$endpoint->PathParameters[] = new ParameterDocumentation('username', 'string', 'username whose contact links to look up.');

		return $endpoints;
	}

	/**
	 * get contact links for a user.
	 * @param array $params may contain number of activities to skip
	 */
	protected static function GET_list(array $params): void {
		$username = trim(array_shift($params));
		if (!$username)
			self::NotFound('username must be specified.');
		self::Success(ContactLink::List(self::RequireDatabase(), self::RequireUser(), $username));
	}
}
ContactApi::Respond();
