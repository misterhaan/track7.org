<?php
require_once 'user.php';

class FormatDate {
	public const Long = 'g:i a \o\n l F jS Y';
	public const LongHTML = 'g:i a \o\n l F j<\s\u\p>S</\s\u\p> Y';

	public static function LocalToTimestamp(string $dateString, CurrentUser $user): int|false {
		$nonlocal = strtotime($dateString);
		if ($nonlocal === false)
			return false;  // date string not understood
		if (!$user->DST)
			$nonlocal = strtotime($nonlocal . ' seconds GMT');
		return $nonlocal - $user->TzOffset;
	}

	public static function Local(string $format, int $timestamp, CurrentUser $user): string {
		if ($user->DST)
			return date($format, $timestamp + $user->TzOffset);
		return gmdate($format, $timestamp + $user->TzOffset);
	}

	/**
	 * Format a timestamp for a small space based on how long ago it was.
	 * @param integer $timestamp Timestamp to format
	 * @return string Formatted timestamp
	 */
	public static function SmartDate(CurrentUser $user, $timestamp) {
		$diff = time() - $timestamp;
		$format = 'M Y';
		if ($diff < 86400 && date('Y-m-d') == date('Y-m-d', $timestamp))  // 86400 s == 1 day
			$format = 'g:i a';
		if ($diff < 518400)  // 518400 s == 6 days
			$format = 'l';
		if (date('Y') == date('Y', $timestamp) || $diff < 15768000)  // 15768000 s == 6 months
			$format = 'M j<\s\u\p>S</\s\u\p>';
		return strtolower(self::Local($format, $timestamp, $user));
	}

	public static function HowLongAgo(int $timestamp): string {
		return self::TimeSpan(abs(time() - $timestamp));
	}

	public static function TimeSpan(int $seconds): string {
		if ($seconds < 120)  // 2 minutes
			return $seconds . ' seconds';
		if ($seconds < 7200)  // 2 hours
			return round($seconds / 60, 0) . ' minutes';
		if ($seconds < 172800)  // 2 days
			return round($seconds / 3600, 0) . ' hours';
		if ($seconds < 1209600)  // 2 weeks
			return round($seconds / 86400, 0) . ' days';
		if ($seconds < 5259488)  // 2 months
			return round($seconds / 604800, 0) . ' weeks';
		if ($seconds < 63113818)  // 2 years
			return round($seconds / 2629739.52) . ' months';
		if ($seconds < 631138176)  // 20 years
			return round($seconds / 31556908.8) . ' years';
		return round($seconds / 315569088) . ' decades';
	}
}

class TimeTagData {
	public string $DateTime = '';
	public string $Display = '';
	public string $Tooltip = '';

	public function __construct(CurrentUser $user, string $format, string $timestamp, string $tooltipFormat = '') {
		$this->DateTime = gmdate('c', $timestamp);
		if ($format == 'ago' || $format == 'since')
			$this->Display = FormatDate::HowLongAgo($timestamp);
		elseif ($format == 'smart')
			$this->Display = FormatDate::SmartDate($user, $timestamp);
		else
			$this->Display = strtolower(FormatDate::Local($format, $timestamp, $user));
		if ($tooltipFormat)
			$this->Tooltip = strtolower(FormatDate::Local($tooltipFormat, $timestamp, $user));
	}
}
