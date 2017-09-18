<?php
/**
 * Format class translates various values for display.  All functions are
 * static.
 * @author misterhaan
 *
 */
class t7format {
	private static $parsedown = false;  // markdown parsing object

	/**
	 * Get the beginning of a URL for this website, such as https://www.track7.org
	 * @return string URL scheme and host
	 */
	private static function UrlStart() {
		return self::Scheme() . '://' . self::Host();
	}
	/**
	 * Find the scheme used to access this website.
	 * @return string URL scheme:  either http or https
	 */
	private static function Scheme() {
		if(!self::$scheme) {
			if(isset($_SERVER['REQUEST_SCHEME']))
				self::$scheme = $_SERVER['REQUEST_SCHEME'];
			else {
				self::$scheme = 'http';
				if(isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on')
					self::$scheme .= 's';
			}
		}
		return self::$scheme;
	}
	private static $scheme = false;

	/**
	 * Find the host portion for URL on this website.  Will be similar to what
	 * $_SERVER['HTTP_HOST'] would provide if it was reliable.
	 * @return string Web server hostname, with port if nonstandard
	 */
	private static function Host() {
		if(!self::$host) {
			self::$host = $_SERVER['SERVER_NAME'];
			// don't include standard ports.  assumes we won't have swapped the standard ports for http and https
			if($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443)
				self::$host .= ':' . $_SERVER['SERVER_PORT'];
		}
		return self::$host;
	}
	private static $host = false;

	/**
	 * Make a root URL into a fully-qualified URL.  Detects http or https, server
	 * name, and port.  Does not support username or password.
	 * @param string $rootUrl A root URL (starts with a forward slash) for this website
	 */
	public static function FullUrl($rootUrl) {
		return self::UrlStart() . $rootUrl;
	}

	/**
	 * Format a contact address (website / web page / e-mail) and forms it into
	 * a valid URL as best as possible.  Nothing is flat-out rejected.
	 * @param string $link User-entered contact address
	 * @return string Probably valid URL from $link
	 */
	public static function Link($link) {
		$link = trim($link);
		if($link == '' || substr($link, 0, 1) == '#' || substr($link, 0, 1) == '/' || substr($link, 0, 7) == 'mailto:')
			return $link;
		if(strpos($link, '://') === false)
			if(strpos($link, '@') && strpos($link, '.', strpos($link, '@')))  // has an @ with a dot later on, so probably an e-mail address
				return 'mailto:' . $link;
			else
				return 'http://' . $link;
		// unqualify fully-qualified links to this site
		if(substr($link, 0, strlen(self::UrlStart()) + 1) == self::UrlStart() . '/')
			return substr($link, strlen(self::UrlStart()));
		return $link;
	}

	/**
	 * Attempts to turn input into a valid URL to a webserver.
	 * @param string $value URL or domain name.  May be modified into a proper URL.
	 * @return boolean Whether value is (or has become) a valid URL.
	 */
	public static function CheckUrl(&$value) {
		if(!stripos($value, '://'))
			$value = 'http://' . $value;
		if(preg_match('/^https?:\/\/[^\.\/]+(\.[^\.\/]+)+$/i', $value))
			$value .= '/';
		if(substr($value, 0, 7) != 'http://' && substr($value, 0, 8) != 'https://')
			return false;
		stream_context_set_default(array('http' => array('method' => 'HEAD')));
		$headers = @get_headers($value, 1);
		return $headers && +substr($headers[0], 9, 3) < 400;
	}

	/**
	 * Format a timestamp two or three ways for use in an html time tag.  Format
	 * parameters will eventually be used in the php date function.
	 * @param string $format Display format, or 'ago' to show how long ago, or 'smart' to format according to age
	 * @param integer $timestamp Unix timestamp to format
	 * @param string $tooltipformat Optional format for tooltip.
	 * @return object with ->datetime for datetime attribute, display for content, and optionally title for title attribute
	 */
	public static function TimeTag($format, $timestamp, $tooltipformat = false) {
		$datetime = new stdClass();
		$datetime->datetime = gmdate('c', $timestamp);
		if($format == 'ago' || $format == 'since')
			$datetime->display = self::HowLongAgo($timestamp);
		elseif($format == 'smart')
			$datetime->display = self::SmartDate($timestamp);
		else
			$datetime->display = strtolower(self::LocalDate($format, $timestamp));
		if($tooltipformat)
			$datetime->title = strtolower(self::LocalDate($tooltipformat, $timestamp));
		return $datetime;
	}

	/**
	 * Format a timestamp for a small space based on how long ago it was.
	 * @param integer $timestamp Timestamp to format
	 * @return string Formatted timestamp
	 */
	public static function SmartDate($timestamp) {
		$diff = time() - $timestamp;
		if($diff < 86400 && date('Y-m-d') == date('Y-m-d', $timestamp))  // 86400 s == 1 day
			return strtolower(self::LocalDate('g:i a', $timestamp));
		if($diff < 518400)  // 518400 s == 6 days
			return strtolower(self::LocalDate('l', $timestamp));
		if(date('Y') == date('Y', $timestamp) || $diff < 15768000)  // 15768000 s == 6 months
			return strtolower(self::LocalDate('M j<\s\u\p>S</\s\u\p>', $timestamp));
		return strtolower(self::LocalDate('M Y', $timestamp));
	}

	/**
	 * Translate a time in the past into how long before now it is, using the
	 * best-fit time unit.
	 * @param integer $timestamp Timestamp from the past to translate
	 */
	public static function HowLongAgo($timestamp) {
		return self::TimeSpan(time() - $timestamp);
	}

	/**
	 * Translate a number of seconds into the best-fit time unit.
	 * @param integer $seconds Number of seconds to translate
	 * @return string Translated time with unit
	 */
	public static function TimeSpan($seconds) {
		if($seconds < 120)  // 2 minutes
			return $seconds . ' seconds';
		if($seconds < 7200)  // 2 hours
			return round($seconds / 60, 0) . ' minutes';
		if($seconds < 172800)  // 2 days
			return round($seconds / 3600, 0) . ' hours';
		if($seconds < 1209600)  // 2 weeks
			return round($seconds / 86400, 0) . ' days';
		if($seconds < 5259488)  // 2 months
			return round($seconds / 604800, 0) . ' weeks';
		if($seconds < 63113818)  // 2 years
			return round($seconds / 2629739.52) . ' months';
		if($seconds < 631138176)  // 20 years
			return round($seconds / 31556908.8) . ' years';
		return round($seconds / 315569088) . ' decades';
	}

	/**
	 * Format a timestamp as specified after converting to the user's timezone.
	 * @param string $format Date format string (see php.net/date)
	 * @param integer $timestamp Timestamp to format
	 * @return string
	 */
	public static function LocalDate($format, $timestamp) {
		global $user;
		if($user->DST)
			return date($format, $timestamp + $user->tzOffset);
		return gmdate($format, $timestamp + $user->tzOffset);
	}

	/**
	 * Get a timestamp from a formatted string that's in the user's timezone.
	 * @param string $timestring Formatted date / time string (see php.net/strtotime)
	 * @return integer
	 */
	public static function LocalStrtotime($timestring) {
		global $user;
		if($user->DST)
			return strtotime($timestring) - $user->tzOffset;
		return strtotime(strtotime($timestring) . ' seconds GMT') - $user->tzOffset;
	}

	/**
	 * Parse markdown into HTML, ignoring headers and encoding HTML characters.
	 * @param string $md Markdown to parse
	 * @return string HTML parsing results
	 */
	public static function Markdown($md) {
		if(!self::$parsedown) {
			self::$parsedown = new HeaderlessParsedown();
			self::$parsedown->setMarkupEscaped(true);
		}
		return self::$parsedown->parse($md);
	}
}

/**
 * Parsedown class with headers disabled.
 * @author misterhaan
 */
class HeaderlessParsedown extends Parsedown {
	protected function blockHeader($Line) { return; }
	protected function blockSetextHeader($Line, array $Block = NULL) { return; }
}
