<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'release.php';

/**
 * Handler for application API requests.
 */
class ReleaseApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves releases for an application by most recent.');
		$endpoint->PathParameters[] = new ParameterDocumentation('application', 'string', 'id of application whose releases to look up.', true);
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of releases to skip. usually the number of releases currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'versionAvailable', 'checks if a version is available to release for an application.', 'plain text', 'send the version to check as the request body in x.x.x format.');
		$endpoint->PathParameters[] = new ParameterDocumentation('application', 'string', 'id of application to check version.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'add', 'saves a new release of an application.  must be logged in as the administrator.', 'post', 'fields from the form');
		$endpoint->PathParameters[] = new ParameterDocumentation('application', 'string', 'id of the application to release.', true);
		$endpoint->BodyDocumentation[] = new ParameterDocumentation('version', 'string', 'version to release.  should be formatted x.x.x but zeroes will be filled in for the second and third x if missing.', true);
		$endpoint->BodyDocumentation[] = new ParameterDocumentation('instant', 'string', 'when this release was released.  leave blank for now, or use human-redable date and time format understood by php strtotime.');
		$endpoint->BodyDocumentation[] = new ParameterDocumentation('language', 'string', 'primary language used in this release.  must be either c# or vb.', true);
		$endpoint->BodyDocumentation[] = new ParameterDocumentation('dotnet', 'float', '.net version used by this application.');
		$endpoint->BodyDocumentation[] = new ParameterDocumentation('visualstudio', 'integer', 'visual studio version this application was developed in.  just the numeric part of the name.', true);
		$endpoint->BodyDocumentation[] = new ParameterDocumentation('changelog', 'string', 'changes made in this release, usually as a list.  markdown format.');
		$endpoint->BodyDocumentation[] = new ParameterDocumentation('binurl', 'string', 'url to the main birany download for this release.  this might be a 64-bit installer or a zip file containing any-cpu dlls.', true);
		$endpoint->BodyDocumentation[] = new ParameterDocumentation('bin32url', 'string', 'url to the 32-bit binary download for this release.  if specified, binurl is assumed to be 64-bit.');
		$endpoint->BodyDocumentation[] = new ParameterDocumentation('srcurl', 'string', 'url to the source code for this release.');

		return $endpoints;
	}

	/**
	 * Get releases of an application.
	 * @param array $params ID of application whose releases to look up.  May also contain number of applications to skip
	 */
	protected static function GET_list(array $params): void {
		$application = trim(array_shift($params));
		$skip = +array_shift($params);
		self::Success(Release::List(self::RequireDatabase(), self::RequireUser(), $application, $skip));
	}

	protected static function POST_versionAvailable(array $params): void {
		$application = trim(array_shift($params));
		$version = self::ReadRequestText();
		if (!$application || !$version)
			self::NotFound('application and version must be specified.');

		self::Success(NewRelease::VersionAvailable(self::RequireDatabase(), self::RequireUser(), $application, $version));
	}

	protected static function POST_add(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can release applications.  you might need to log in again.');

		$application = array_shift($params);
		if (!$application)
			self::NotFound('application must be specified.');

		$release = NewRelease::FromPOST(self::RequireDatabase(), self::$user, $application);
		$release->Save(self::$db);
		if (time() - $release->Instant < 604800) {  // within the last week
			require_once 'formatUrl.php';
			self::Tweet($application . ' v' . $release->Version . ' released', FormatURL::FullUrl('/code/vs/' . $application));
		}
		self::Success('/code/vs/' . $application);
	}
}
ReleaseApi::Respond();
