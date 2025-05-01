<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once 'formatDate.php';

/**
 * Handler for photo API requests.
 */
class DateApi extends Api {
	/**
	 * Provide documentation for this API when requested without an endpoint.
	 * @return EndpointDocumentation[] Array of documentation for each endpoint of this API
	 */
	public static function GetEndpointDocumentation(): array {
		$endpoints = [];

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'validatePast', 'checks whether the provided date string is valid and in the past.', 'plain text', 'send the date string to validate as the request body.');

		$endpoints[] = $endpoint = new EndpointDocumentation('POST', 'validateTime', 'checks whether the provided time string is valid.', 'plain text', 'send the time string to validate as the request body.');

		return $endpoints;
	}

	/**
	 * Validate that a date is in the past.
	 * @param array $params String representation of a date to validate.
	 */
	protected static function POST_validatePast(array $params) {
		$dateString = self::ReadRequestText();
		if (!$dateString)
			self::NotFound('date is required.');
		$timestamp = FormatDate::LocalToTimestamp($dateString, self::RequireUser());
		if ($timestamp === false)
			self::Success(new ValidationResult('invalid', 'can’t make sense of that as a date / time'));
		if ($timestamp > time())
			self::Success(new ValidationResult('invalid', 'future values are not allowed'));
		self::Success(new ValidationResult('valid', '', FormatDate::Local('Y-m-d g:i:s a', $timestamp, self::$user)));
	}

	protected static function POST_validateTime(array $params) {
		$timeString = self::ReadRequestText();
		if (!$timeString)
			self::NotFound('time is required.');
		$timestamp = strtotime($timeString);
		if ($timestamp === false)
			self::Success(new ValidationResult('invalid', 'can’t make sense of that as a date / time'));
		self::Success(new ValidationResult('valid', '', date('g:i a', $timestamp)));
	}
}
DateApi::Respond();
