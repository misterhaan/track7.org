<?php
require_once 'environment.php';

/**
 * Interactions with the bit.ly web service.
 */
class Bitly extends KeyMaster {
	private const BitlyShortenURL = 'https://api-ssl.bit.ly/v4/shorten';

	/**
	 * Shortens a URL using the bit.ly web service.
	 * @param string $url URL to shorten.
	 * @return string Shortened URL.
	 */
	public static function Shorten(string $url): string {
		self::RequireServiceKeys('KeysBitly', 'AccessToken');
		$data = new stdClass();
		$data->long_url = $url;
		$c = curl_init();
		curl_setopt_array($c, [
			CURLOPT_URL => self::BitlyShortenURL,
			CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . KeysBitly::AccessToken, 'Content-Type: application/json'],
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($data),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => $_SERVER['SERVER_NAME'],
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT => 30
		]);
		$responseText = curl_exec($c);
		curl_close($c);
		$response = json_decode($responseText);
		if (isset($response->link))
			return $response->link;
		throw new DetailedException('couldn’t get shortened url', $responseText);
	}
}
