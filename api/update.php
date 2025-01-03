<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'update.php';

/**
 * Handler for update API requests.
 */
class UpdateApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves the lastest updates with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of updates to skip. usually the number of updates currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'add', 'adds a new update.', 'multipart');
		$endpoint->BodyParameters[] = new ParameterDocumentation('markdown', 'string', 'content of the update in markdown format.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('posted', 'string', 'when the update was made. uses current date and time if left blank.');

		return $endpoints;
	}

	/**
	 * Get latest updates.
	 * @param array $params May contain number of updates to skip (empty array will skip 0)
	 */
	protected static function GET_list(array $params): void {
		$skip = +array_shift($params);
		self::Success(Update::List(self::RequireDatabase(), self::RequireUser(), $skip));
	}

	protected static function POST_add(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can post a site update.');

		$update = Update::FromPOST(self::RequireUser());
		$update->Save(self::RequireDatabase());
		self::Success($update->ID);
	}
}
UpdateApi::Respond();
