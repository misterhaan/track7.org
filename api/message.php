<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'message.php';

/**
 * Handler for message API requests.
 */
class MessageApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'list', 'retrieves conversations the logged-in user is involved in.  requires authentication.');

		$endpoints[] = $endpoint = new EndpointDocumentation('GET', 'conversation', 'retrieves all messages in the conversation between the logged-in user and the specified user.  requires authentication.');
		$endpoint->PathParameters[] = new ParameterDocumentation('withUserID', 'integer', 'id of the other user the conversation is with, or 0 for messages from anonymous users.', true);

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'send', 'sends a message to a track7 user.', 'multipart');
		$endpoint->PathParameters[] = new ParameterDocumentation('toUserID', 'integer', 'id of the user the message is being sent to.', true);
		$endpoint->BodyParameters[] = new ParameterDocumentation('message', 'string', 'message body in markdown format.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('fromname', 'string', 'name of person sending the message.  ignored if logged in.');
		$endpoint->BodyParameters[] = new ParameterDocumentation('fromcontact', 'string', 'contact url or email address of person sending the message.  ignored if logged in.');

		return $endpoints;
	}

	protected static function GET_list() {
		if (!self::IsUserLoggedIn())
			self::Forbidden('must be signed in to view conversations');
		self::Success(Conversation::List(self::RequireDatabase(), self::RequireUser()));
	}

	protected static function GET_conversation(array $params) {
		if (!self::IsUserLoggedIn())
			self::Forbidden('must be signed in to view messages');
		$withUserID = +array_shift($params);
		$skip = +array_shift($params);
		self::Success(Message::List(self::RequireDatabase(), self::RequireUser(), $withUserID, $skip));
	}

	protected static function POST_send(array $params) {
		$toUserID = +array_shift($params);
		if (!$toUserID)
			self::NotFound('toUserID is required');
		if (!self::IsUserLoggedIn() && !isset($_POST['fromname']))
			self::NotFound('nobody’s logged in and we didn’t ask your name.  this can happen if the messages page has been left open a long time, and should probably be fixed my signing back in using a new tab so you don’t lose the message you just wrote.');
		require_once 'user.php';
		$toUser = new User(self::RequireDatabase(), $toUserID);
		if ($toUser->ID != $toUserID)
			self::NotFound('could not find user with id ' . $toUserID);
		$message = isset($_POST['message']) ? trim($_POST['message']) : '';
		if (!$message || strlen($message) < 2)
			self::NotFound('message text missing or blank.  we can’t send it if you didn’t write it.');
		$fromname = isset($_POST['fromname']) ? trim($_POST['fromname']) : '';
		if (strpos($fromname, "\r") !== false || strpos($fromname, "\n") !== false)
			self::DetailedError('fromname cannot contain line breaks');
		$fromcontact = isset($_POST['fromcontact']) ? trim($_POST['fromcontact']) : '';
		self::Success(Message::Send(self::RequireDatabase(), self::RequireUser(), $toUser, $message, $fromname, $fromcontact));
	}
}
MessageApi::Respond();
