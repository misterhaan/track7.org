<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'script.php';

/**
 * Handler for web script API requests.
 */
class ScriptApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves web scripts with most recently released first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of web scripts to skip. usually the number of web scripts currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'edit', 'retrieves a web script for editing.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'specify the id of the web script to edit.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'idAvailable', 'checks if a web script id is available.  this means not in use or already used by the specified web script.');
		$endpoint->PathParameters[] = new ParameterDocumentation('oldId=newId', 'string', 'oldId is the id of the web script that might be changing its id, or just start with the equal sign for a new script.  newId is the proposed new id for the script.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'save', 'saves edits to an existing web script or adds a new web script.  must be logged in as the administrator.', 'multipart', 'fields from the form.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'id of the web script to update.  if not specified, adds a new web script.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('id', 'string', 'new web script id (unique part of the url to the web script).', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('title', 'string', 'name of the web script.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('type', 'string', 'type of the web script.  must be one of:  api, snippet, userscript, web application, website.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('description', 'string', 'description of the web script in markdown format.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('upload', 'file (zip)', 'the package of this web script as a zip file upload.  either this or a download link is required for a new web script.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('download', 'string', 'url to where this web script can be downloaded.  either this or a zip file upload is required for a new web script.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('github', 'string', 'unique part of the github url if the web script is on github.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('wiki', 'string', 'unique part of the auwiki url if the web script is on auwiki.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('instant', 'string', 'when this web script was released.  will use current time if blank, otherwise needs to make sense to php date interpretation.');

		return $endpoints;
	}

	/**
	 * Get web scripts.
	 * @param array $params May contain number of web scripts to skip
	 */
	protected static function GET_list(array $params): void {
		$skip = +array_shift($params);
		self::Success(IndexScript::List(self::RequireDatabase(), self::RequireUser(), $skip));
	}

	/**
	 * Get all information on a web script for purposes of editing.
	 * @param array $params ID of web script to edit
	 */
	protected static function GET_edit(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can edit web scripts.  you might need to log in again.');

		$id = trim(array_shift($params));
		if (!$id)
			self::NotFound('web script id must be specified.');
		if ($script = EditScript::FromID(self::RequireDatabase(), $id)) {
			require_once 'formatDate.php';
			$script->FormattedInstant = FormatDate::Local('Y-m-d g:i:s a', $script->Instant, self::RequireUser());
			self::Success($script);
		} else
			self::NotFound('could not find web script with the specified id.');
	}

	/**
	 * Check if a web script ID is available.
	 * @param array $params Current ID followed by an equal sign followed by the new ID to check.  Current ID may be blank.
	 */
	protected static function GET_idAvailable($params): void {
		$oldNewID = array_shift($params);
		if (!$oldNewID)
			self::NotFound('id must be specified.');
		$oldNewID = explode('=', $oldNewID);
		if (count($oldNewID) == 1) {
			$oldID = '';
			$newID = $oldNewID[0];
		} else {
			$oldID = array_shift($oldNewID);
			$newID = implode('=', $oldNewID);
		}
		self::Success(EditScript::IdAvailable(self::RequireDatabase(), $oldID, $newID));
	}

	/**
	 * Saves changes to an application.
	 * @param array $params Current ID of the application being changed, or blank if it's new.
	 */
	protected static function POST_save(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can save web scripts.  you might need to log in again.');

		$id = array_shift($params);
		$script = EditScript::FromPOST(self::RequireUser());
		$path = $_SERVER['DOCUMENT_ROOT'] . '/code/web/files/';
		$hasFile = isset($_FILES['upload']) && $_FILES['upload']['size'];
		if (!$script->Download && !$hasFile && !file_exists($path . $id . '.zip'))
			self::NotFound('web script needs to either include a download link or a zip file.');
		$result = EditScript::IdAvailable(self::$db, $id, $script->ID);
		if ($result->State == 'invalid')
			self::DetailedError($result->Message);

		if ($hasFile)
			move_uploaded_file($_FILES['upload']['tmp_name'], $path . $script->ID . '.zip');

		if ($id)
			$script->Update(self::$db, $id);
		else
			$script->SaveNew(self::$db);

		if ($id && $id != $script->ID) {
			if ($hasFile)
				unlink($path . $id . '.zip');
			else
				rename($path . $id . '.zip', $path . $script->ID . '.zip');
		}
		self::Success('/code/web/' . $script->ID);
	}
}
ScriptApi::Respond();
