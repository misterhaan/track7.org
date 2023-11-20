<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'comment.php';

/**
 * Handler for comment API requests.
 */
class CommentApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'bypost', 'get the latest comments for the specified post with most recent first.');
		$endpoint->PathParameters[] = new ParameterDocumentation('postID', 'integer', 'specify the post by its id.', true);
		$endpoint->PathParameters[] = new ParameterDocumentation('skip', 'integer', 'specify a number of comments to skip, which is usually the number of comments currently loaded.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'new', 'post a new comment.', 'POST data', 'fields from the comment form.  for signed-in users, the name and contact fields do not appear.');
		$endpoint->PathParameters[] = new ParameterDocumentation('postID', 'integer', 'specify the post the comment applies to by its id.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('markdown', 'string', 'comment text in markdown format, as entered into the comment form.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('name', 'string', 'name of commenter (required if not signed in).');
		$endpoint->BodyParameters[] = new ParameterDocumentation('contact', 'string', 'contact email or url of commenter (ignored if signed in)');

		$endpoints[] = $endpoint = new EndpointDocumentation('PATCH', 'id', 'update an existing comment.  must be logged in as the user who originally posted the comment or the administrator.', 'markdown', 'it will be saved as the new comment.');
		$endpoint->PathParameters[] = new ParameterDocumentation('commentID', 'integer', 'id of the comment to update.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('DELETE', 'id', 'delete an existing comment.  must be logged in as the user who originally posed the comment or the administrator.');
		$endpoint->PathParameters[] = new ParameterDocumentation('commentID', 'integer', 'id of the comment to delete.', true);

		return $endpoints;
	}

	protected static function GET_bypost(array $params): void {
		$post = +array_shift($params);
		if (!$post)
			self::NotFound('post must be specified.');
		$skip = +array_shift($params);
		self::RequireUser();
		self::Success(Comment::ListByPost(self::$db, self::$user, $post, $skip));
	}

	protected static function POST_new(array $params): void {
		$post = +array_shift($params);
		if (!$post)
			self::NotFound('post must be specified.');
		$name = isset($_POST['name']) ? trim($_POST['name']) : '';
		$contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
		$markdown = isset($_POST['markdown']) ? trim($_POST['markdown']) : '';

		if (!$markdown)
			self::DetailedError('cannot save a blank comment');
		if (!$name && !self::IsUserLoggedIn())
			self::DetailedError('either sign in or provide a name to post a comment');

		self::Success(Comment::Create(self::$db, self::$user, $post, $markdown, $name, $contact));
	}

	protected static function PATCH_id(array $params): void {
		$id = +array_shift($params);
		if (!$id)
			self::NotFound('comment id must be specified.');
		$markdown = self::ReadRequestText();
		if (!$markdown)
			self::DetailedError('cannot save empty comment.  if you meant to remove it, use “delete” instead.');
		if (!self::IsUserLoggedIn())
			self::Forbidden('must be signed in to update comment.  if you’ve had this page open a while you might need to open a new tab to sign back in and then try saving again.');
		self::Success(Comment::Update(self::$db, self::$user, $id, $markdown));
	}

	protected static function DELETE_id(array $params): void {
		$id = +array_shift($params);
		if (!$id)
			self::NotFound('comment id must be specified.');
		if (!self::IsUserLoggedIn())
			self::Forbidden('must be signed in to delete comment.  if you’ve had this page open a while you might need to open a new tab to sign back in and then try saving again.');
		self::Success(Comment::Delete(self::$db, self::$user, $id));
	}
}
CommentApi::Respond();
