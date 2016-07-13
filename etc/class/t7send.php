<?php
  /**
   * collection of static functions for sending messages using various protocols
   * and services.
   * @author misterhaan
   *
   */
  class t7send {
    const BITLY_URL = 'http://api.bit.ly/v3/shorten';

    const TWEET_URL = 'https://api.twitter.com/1.1/statuses/update.json';
    const TWEET_LENGTH = 140;

    /**
     * Shortens a URL using the bit.ly web service.  To use a bit.ly account,
     * make sure the constants t7keysBitly::LOGIN and t7keysBitly::KEY are set
     * to the login and API key for the account.
     * @param string $url URL to shorten.
     * @return string Shortened URL.
     */
    public static function Bitly($url) {
      $c = curl_init();
      curl_setopt($c, CURLOPT_URL, self::BITLY_URL . '?login=' . t7keysBitly::LOGIN . '&apiKey=' . t7keysBitly::KEY . '&uri=' . urlencode($url) . '&format=txt');
      curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($c, CURLOPT_USERAGENT, 't7send');
      curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
      curl_setopt($c, CURLOPT_TIMEOUT, 30);
      $url = curl_exec($c);
      curl_close($c);
      return $url;
    }

    /**
     * Sends an e-mail.  Only $body may contain line breaks.
     * @param string $subject Subject of the e-mail.
     * @param string $body Body of the e-mail.
     * @param string $from e-mail address of the sender.
     * @param string $to e-mail address of the recipient.
     * @param string $fromname Display name of the sender (optional).
     * @param string $toname Display name of the recipient (optional).
     * @param string $cc e-mail addresses to carbon copy (optional).
     * @param string $bcc e-mail addresses to blind carbon copy (optional).
     * @param string $reply e-mail address for replies, or true to use $from (optional).
     * @return boolean Whether an e-mail was sent.
     */
    public static function Email($subject, $body, $from, $to, $fromname = false, $toname = false, $cc = false, $bcc = false, $reply = false) {
      if(+$_SERVER['SERVER_PORT'] > 8000)
        return false;
      // subject may not contain line breaks
      if(strpos($subject, "\r") !== false || strpos($subject, "\n") !== false)
        return false;
      if($fromname)
        $from = $fromname . ' <' . $from . '>';
      // sender may not contain line breaks
      if(strpos($from, "\r") !== false || strpos($from, "\n") !== false)
        return false;
      if($toname)
        $to = $toname . '<' . $to . '>';
      // recipient may not contain line breaks
      if(strpos($to, "\r") !== false || strpos($to, "\n") !== false)
        return false;
      $headers = ['X-Mailer: t7send/php' . phpversion(), 'From: ' . $from];
      if($cc) {
        // cc may not contain line breaks
        if(strpos($cc, "\r") !== false || strpos($cc, "\n") !== false)
          return false;
        $headers[] = 'Cc: ' . $cc;
      }
      if($bcc) {
        // bcc may not contain line breaks
        if(strpos($bcc, "\r") !== false || strpos($bcc, "\n") !== false)
          return false;
        $headers[] = 'Bcc: ' . $bcc;
      }
      if($reply)
        if($reply === true)
          $headers[] = 'Reply-To: ' . $from;
        else {
          // reply-to may not contain line breaks
          if(strpos($reply, "\r") !== false || strpos($reply, "\n") !== false)
            return false;
          $headers[] = 'Reply-To: ' . $reply;
        }
      return @mail($to, $subject, $body, implode("\r\n", $headers));
    }

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
      if(+$_SERVER['SERVER_PORT'] > 8000)
        return false;
      // fix up the message and add / shorten the url if present
      if($url) {
        if(substr($url, 0, 13) != 'http://bit.ly')
          $url = self::Bitly($url);
        if(mb_strlen($message) + strlen($url) + 1 > self::TWEET_LENGTH)
          $message = mb_substr($message, 0, self::TWEET_LENGTH - strlen($url) - 2) . '… ' . $url;
        else
          $message .= ' ' . $url;
      } elseif(mb_strlen($message) > self::TWEET_LENGTH)
        $message = mb_substr($message, 0, self::TWEET_LENGTH);

      // collect and sign oauth data
      $oauth = ['oauth_nonce' => md5(microtime() . mt_rand()),
          'oauth_timestamp' => time(),
          'oauth_version' => '1.0',
          'oauth_consumer_key' => t7keysTweet::CONSUMER_KEY,
          'oauth_signature_method' => 'HMAC-SHA1',
          'oauth_token' => t7keysTweet::OAUTH_TOKEN];
      ksort($oauth);
      $sig = 'POST&' . rawurlencode(self::TWEET_URL) . '&' . rawurlencode(http_build_query($oauth, null, '&', PHP_QUERY_RFC3986));
      $oauth['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $sig, t7keysTweet::CONSUMER_SECRET . '&' . t7keysTweet::OAUTH_TOKEN_SECRET, true)));
      ksort($oauth);

      // quote all oauth variables for the authorization header
      $header = array();
      foreach($oauth as $var => $val)
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
?>
