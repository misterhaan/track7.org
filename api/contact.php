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
		$endpoint->PathParameters[] = new ParameterDocumentation('username', 'string', 'username whose contact links to look up.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'validate', 'validates a contact link.', 'plain text', 'send the value to validate as the request body.');
		$endpoint->PathParameters[] = new ParameterDocumentation('type', 'string', 'type of contact link to validate.', true);

		return $endpoints;
	}

	/**
	 * get contact links for a user.
	 * @param array $params username whose contacts to look up
	 */
	protected static function GET_list(array $params): void {
		$username = trim(array_shift($params));
		if (!$username)
			self::NotFound('username must be specified.');
		self::Success(ContactLink::List(self::RequireDatabase(), self::RequireUser(), $username));
	}

	/**
	 * validate a contact link.
	 * @param array $params type of contact link to validate
	 */
	protected static function POST_validate(array $params): void {
		$type = trim(array_shift($params));
		if (!$type)
			self::NotFound('contact type must be specified.');
		$value = self::ReadRequestText();

		self::Success(ContactLink::Validate($type, $value));
	}
}
ContactApi::Respond();
