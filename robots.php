<?
  header('Content-Type: text/plain');
  if(strpos($_SERVER['HTTP_HOST'], 'track7.org') === false || strpos($_SERVER['HTTP_HOST'], 'm.track7.org') !== false) {
    header('Last-Modified: ' . gmdate('D, j M Y H:i:s \G\M\T', filemtime($_SERVER['DOCUMENT_ROOT'] . '/' . $_SERVER['PHP_SELF'])));
?>
User-Agent: *
Disallow: /
<?
  } else {
    header('Last-Modified: ' . gmdate('D, j M Y H:i:s \G\M\T', filemtime('robots.txt')));
    include 'robots.txt';
  }
?>
