<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'application.php';

/**
 * Handler for application API requests.
 */
class ApplicationApi extends Api {
	private const IconSize = 64;

	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves applications with most recently released first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of applications to skip. usually the number of applications currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'edit', 'retrieves an application for editing.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'specify the id of the application to edit.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'idAvailable', 'checks if an application id is available.  this means not in use or already used by the specified application.', 'plain text', 'send the proposed new id for the applicaiton as the request body.');
		$endpoint->PathParameters[] = new ParameterDocumentation('oldId', 'string', 'current id of the application that might be changing its id.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'save', 'saves edits to an existing application or adds a new application.  must be logged in as the administrator.', 'multipart', 'fields from the form.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'id of the application to update.  if not specified, adds a new application.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('id', 'string', 'new application id (unique part of the url to the application).', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('name', 'string', 'name of the application.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('markdown', 'string', 'description of the application in markdown format.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('icon', 'file (png)', 'the icon image for this application as a png file upload.  required for a new application.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('github', 'string', 'unique part of the github url if the application is on github.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('wiki', 'string', 'unique part of the auwiki url if the application is on auwiki.');

		return $endpoints;
	}

	/**
	 * Get applications.
	 * @param array $params May contain number of applications to skip
	 */
	protected static function GET_list(array $params): void {
		$skip = +array_shift($params);
		self::Success(LatestApplication::List(self::RequireDatabase(), self::RequireUser(), $skip));
	}

	/**
	 * Get all information on an application for purposes of editing.
	 * @param array $params ID of application to edit
	 */
	protected static function GET_edit(array $params): void {
		$id = trim(array_shift($params));
		if (!$id)
			self::NotFound('application id must be specified.');
		if ($entry = EditApplication::FromID(self::RequireDatabase(), $id))
			self::Success($entry);
		else
			self::NotFound('could not find application with the specified id.');
	}

	/**
	 * Check if an application ID is available.
	 * @param array $params Current ID, if any.
	 */
	protected static function POST_idAvailable($params): void {
		$oldID = trim(array_shift($params));
		$newID = self::ReadRequestText();
		self::Success(EditApplication::IdAvailable(self::RequireDatabase(), $oldID, $newID));
	}

	/**
	 * Saves changes to an application.
	 * @param array $params Current ID of the application being changed, or blank if it's new.
	 */
	protected static function POST_save(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can save applications.  you might need to log in again.');

		$id = array_shift($params);
		$application = EditApplication::FromPOST();
		$result = EditApplication::IdAvailable(self::$db, $id, $application->ID);
		if ($result->State == 'invalid')
			self::DetailedError($result->Message);

		require_once 'image.php';
		$icon = Image::FromUpload('icon');
		if (!$id && !$icon)
			self::NotFound('new application must include an icon file.');
		if ($icon) {
			$ext = $icon->GetExtension();
			if ($ext != 'png')
				self::DetailedError("icon must be png format.  found $ext.");
			$name = $_SERVER['DOCUMENT_ROOT'] . '/code/vs/files/' . $application->ID . '.' . $ext;
			$icon->SaveResized($ext, [$name => self::IconSize], true);
			$icon->Delete();
		}

		if ($id)
			$application->Update(self::$db, $id);
		else
			$application->SaveNew(self::$db);

		if ($id && $id != $application->ID) {
			$path = $_SERVER['DOCUMENT_ROOT'] . '/code/vs/files/';
			if ($icon)
				unlink($path . $id . '.png');
			else
				rename($path . $id . '.png', $path . $application->ID . '.png');
		}
		self::Success('/code/vs/' . $application->ID);
	}
}
ApplicationApi::Respond();
