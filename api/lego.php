<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'lego.php';

/**
 * Handler for lego API requests.
 */
class LegoApi extends Api {
	private const ThumbSize = 150;
	private const FullSize = 800;

	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves the lastest lego models with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of lego models to skip. usually the number of lego models currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'edit', 'retrieves details of a lego model for editing. only available to admin.');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'lego model id to load for editing', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'idAvailable', 'checks if a lego model id is available.  this means not in use or already used by the specified lego model.', 'plain text', 'send the proposed new id for the lego model as the request body.');
		$endpoint->PathParameters[] = new ParameterDocumentation('oldId', 'string', 'current id of the lego model that might be changing its id.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'save', 'save edits to a lego model or add a new lego model. only available to admin.', 'multipart/form-data');
		$endpoint->PathParameters[] = new ParameterDocumentation('id', 'string', 'id of the lego model to update.  if not specified, adds a new lego model.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('id', 'string', 'new lego model id (unique part of the url to the lego model).', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('title', 'string', 'title of the lego model.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('description', 'string', 'description of the lego model in markdown format.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('pieces', 'integer', 'number of pieces in this lego model.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('image', 'file (png)', 'the 3d rendered image of this lego model as a png file upload.  required for new lego models.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('ldraw', 'file (ldraw)', 'ldraw data file of this lego model.  required for new lego models.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('intstructions', 'file (pdf)', 'building instructions for this lego model.  required for new lego models.');

		return $endpoints;
	}

	/**
	 * Get latest lego models.
	 * @param array $params May contain number of lego models to skip (empty array will skip 0)
	 */
	protected static function GET_list(array $params): void {
		$skip = +array_shift($params);
		self::Success(IndexLego::List(self::RequireDatabase(), $skip));
	}

	/**
	 * Get all information on a lego model for purposes of editing.
	 * @param array $params ID of lego model to edit
	 */
	protected static function GET_edit(array $params): void {
		$id = trim(array_shift($params));
		if (!$id)
			self::NotFound('id must be specified.');
		if ($lego = EditLego::FromID(self::RequireDatabase(), $id))
			self::Success($lego);
		else
			self::NotFound("unable to find lego model “$id.”");
	}

	/**
	 * Check if a lego model ID is available.
	 * @param array $params Current ID, if any.
	 */
	protected static function POST_idAvailable($params): void {
		$oldID = trim(array_shift($params));
		$newID = self::ReadRequestText();
		self::Success(EditLego::IdAvailable(self::RequireDatabase(), $oldID, $newID));
	}

	/**
	 * Saves changes to a lego model.
	 * @param array $params Current ID of the lego model being changed, or blank if it's new.
	 */
	protected static function POST_save(array $params): void {
		if (!self::HasAdminSecurity())
			self::Forbidden('only the administrator can save lego models.  you might need to log in again.');

		$id = array_shift($params);
		$lego = EditLego::FromPOST();
		$result = EditLego::IdAvailable(self::$db, $id, $lego->ID);
		if ($result->State == 'invalid')
			self::DetailedError($result->Message);

		require_once 'image.php';
		$image = Image::FromUpload('image');
		if (!$id && !$image)
			self::NotFound('new lego model must include an image file.');
		$hasLdraw = isset($_FILES['ldraw']) && $_FILES['ldraw']['size'];
		if (!$id && !$hasLdraw)
			self::NotFound('new lego model must include an ldraw file.');
		$hasInstructions = isset($_FILES['instructions']) && $_FILES['instructions']['size'];
		if (!$id && !$hasInstructions)
			self::NotFound('new lego model must include instructions.');
		if ($image) {
			$name = $_SERVER['DOCUMENT_ROOT'] . '/lego/data/' . $lego->ID;
			$image->SaveResized('png', ["$name-thumb.png" => self::ThumbSize, "$name.png" => self::FullSize]);
			$image->Delete();
		}
		if ($hasLdraw)
			move_uploaded_file($_FILES['ldraw']['tmp_name'], '/lego/data/' . $lego->ID . '.ldr');
		if ($hasInstructions)
			move_uploaded_file($_FILES['instructions']['tmp_name'], '/lego/data/' . $lego->ID . '.pdf');

		if ($id)
			$lego->Update(self::$db, $id);
		else {
			$lego->SaveNew(self::$db);
			require_once 'formatUrl.php';
			self::Tweet('new lego: ' . $lego->Title, FormatURL::FullUrl('/lego/' . $lego->ID));
		}

		if ($id && $id != $lego->ID) {
			$path = $_SERVER['DOCUMENT_ROOT'] . '/lego/data/';
			if ($image) {
				unlink($path . $id . '.png');
				unlink($path . $id . '-thumb.png');
			} else {
				rename($path . $id . '.png', $path . $lego->ID . '.png');
				rename($path . $id . '-thumb.png', $path . $lego->ID . '-thumb.png');
			}
			if ($hasLdraw)
				unlink($path . $id . '.ldr');
			else
				rename($path . $id . '.ldr', $path . $lego->ID . '.ldr');
			if ($hasInstructions)
				unlink($path . $id . '.pdf');
			else
				rename($path . $id . '.pdf', $path . $lego->ID . '.pdf');
		}
		self::Success('/lego/' . $lego->ID);
	}
}
LegoApi::Respond();
