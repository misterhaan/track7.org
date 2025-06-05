<?php
require_once 'environment.php';

/**
 * Interactions with the bit.ly web service.
 */
class Bitly extends KeyMaster {
	private const BitlyShortenURL = 'https://api-ssl.bit.ly/v3/shorten';

	/**
	 * Shortens a URL using the bit.ly web service.  To use a bit.ly account,
	 * make sure the constants t7keysBitly::LOGIN and t7keysBitly::KEY are set
	 * to the login and API key for the account.
	 * @param string $url URL to shorten.
	 * @return string Shortened URL.
	 */
	public static function Shorten($url) {
		self::RequireServiceKeys('t7keysBitly', 'LOGIN', 'KEY');
		$c = curl_init();
		curl_setopt_array($c, [
			CURLOPT_URL => self::BitlyShortenURL . '?login=' . t7keysBitly::LOGIN . '&apiKey=' . t7keysBitly::KEY . '&uri=' . urlencode($url) . '&format=txt',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => $_SERVER['SERVER_NAME'],
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT => 30
		]);
		$short = curl_exec($c);
		curl_close($c);
		return $short;
	}
}
