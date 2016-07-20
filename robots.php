<?
  header('Content-Type: text/plain');
  if($_SERVER['SERVER_PORT'] > 8000) {
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
