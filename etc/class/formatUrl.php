<?php
require_once 'environment.php';

class FormatURL {
	private static string $scheme = '';
	private static string $host = '';

	/**
	 * Make a root URL into a fully-qualified URL.  Detects http or https, server
	 * name, and port.  Does not support username or password.
	 * @param string $rootUrl A root URL (starts with a forward slash) for this website
	 */
	public static function FullUrl(string $rootUrl): string {
		return self::UrlStart() . $rootUrl;
	}

	/**
	 * Make a URL relative to the root of this website.  URL may already be relative or may be fully-qualified.
	 */
	public static function RelativeRootUrl(?string $url): string {
		if (!$url)
			return '/';
		if ($url[0] == '/')
			return $url;
		if (substr($url, 0, strlen(self::UrlStart())) == self::UrlStart())
			return substr($url, strlen(self::UrlStart()));
		throw new DetailedException("url $url does not belong to this website.");
	}

	/**
	 * Format a contact address (website / web page / e-mail) and forms it into
	 * a valid URL as best as possible.  Nothing is flat-out rejected.
	 * @param string $link User-entered contact address
	 * @return string Probably valid URL from $link
	 */
	public static function ContactLink($link) {
		$link = trim($link);
		if ($link == '' || substr($link, 0, 1) == '#' || substr($link, 0, 1) == '/' || substr($link, 0, 7) == 'mailto:')
			return $link;
		if (strpos($link, '://') === false)
			if (strpos($link, '@') && strpos($link, '.', strpos($link, '@')))  // has an @ with a dot later on, so probably an e-mail address
				return 'mailto:' . $link;
			else
				return 'https://' . $link;
		// unqualify fully-qualified links to this site
		if (substr($link, 0, strlen(self::UrlStart()) + 1) == self::UrlStart() . '/')
			return substr($link, strlen(self::UrlStart()));
		return $link;
	}

	/**
	 * Shortens a URL using a web service.
	 * @param string $url URL to shorten.
	 * @return string Shortened URL.
	 */
	public static function Shorten($url) {
		require_once 'bitly.php';
		return Bitly::Shorten($url);
	}

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
	private static function Scheme(): string {
		if (!self::$scheme) {
			if (isset($_SERVER['REQUEST_SCHEME']))
				self::$scheme = $_SERVER['REQUEST_SCHEME'];
			else {
				self::$scheme = 'http';
				if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on')
					self::$scheme .= 's';
			}
		}
		return self::$scheme;
	}

	/**
	 * Find the host portion for URL on this website.  Will be similar to what
	 * $_SERVER['HTTP_HOST'] would provide if it was reliable.
	 * @return string Web server hostname, with port if nonstandard
	 */
	private static function Host(): string {
		if (!self::$host) {
			self::$host = $_SERVER['SERVER_NAME'];
			// don't include standard ports.  assumes we won't have swapped the standard ports for http and https
			if ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443)
				self::$host .= ':' . $_SERVER['SERVER_PORT'];
		}
		return self::$host;
	}
}
