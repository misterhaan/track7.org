<?php

/**
 * collection of static functions for sending messages using various protocols
 * and services.
 */
class t7send {
	const TWEET_URL = 'https://api.twitter.com/1.1/statuses/update.json';
	const TWEET_LENGTH = 140;


	/**
	 * Sends a message to Twitter to be posted as a tweet.  The following
	 * constants must be defined correctly for the Twitter account the message
	 * should be posted to:
	 * t7keysTweet::CONSUMER_KEY
	 * t7keysTweet::CONSUMER_SECRET
	 * t7keysTweet::OAUTH_TOKEN
	 * t7keysTweet::OAUTH_TOKEN_SECRET
	 * @param string $message Message to post to Twitter as a tweet.
	 * @param string $url URL to include with tweet (optional, will be shortened).
	 * @return object Response from Twitter with code and text fields.
	 */
	public static function Tweet($message, $url = false) {
		if (+$_SERVER['SERVER_PORT'] > 8000)
			return false;
		// fix up the message and add / shorten the url if present
		if ($url) {
			if (substr($url, 0, 13) != 'https://bit.ly') {
				require_once 'formatUrl.php';
				$url = FormatURL::Shorten($url);
			}
			if (mb_strlen($message) + strlen($url) + 1 > self::TWEET_LENGTH)
				$message = mb_substr($message, 0, self::TWEET_LENGTH - strlen($url) - 2) . '… ' . $url;
			else
				$message .= ' ' . $url;
		} elseif (mb_strlen($message) > self::TWEET_LENGTH)
			$message = mb_substr($message, 0, self::TWEET_LENGTH);

		// collect and sign oauth data
		$oauth = [
			'oauth_nonce' => md5(microtime() . mt_rand()),
			'oauth_timestamp' => time(),
			'oauth_version' => '1.0',
			'oauth_consumer_key' => t7keysTweet::CONSUMER_KEY,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_token' => t7keysTweet::OAUTH_TOKEN
		];
		ksort($oauth);
		$sig = 'POST&' . rawurlencode(self::TWEET_URL) . '&' . rawurlencode(http_build_query($oauth, '', '&', PHP_QUERY_RFC3986));
		$oauth['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $sig, t7keysTweet::CONSUMER_SECRET . '&' . t7keysTweet::OAUTH_TOKEN_SECRET, true)));
		ksort($oauth);

		// quote all oauth variables for the authorization header
		$header = array();
		foreach ($oauth as $var => $val)
			$header[] = $var . '="' . $val . '"';

		// send the request
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, self::TWEET_URL);
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, 't7send');
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . implode(', ', $header)]);
		curl_setopt($c, CURLOPT_POSTFIELDS, ['status' => $message]);
		$response = new stdClass();
		$response->text = curl_exec($c);
		$response->code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		curl_close($c);
		return $response;
	}
}
