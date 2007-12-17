<html>
  <head>
    <title>subversion repositories - the analog underground - track7</title>
    <link rel="shortcut icon" href="/favicon.ico">
  </head>
  <body>
    <h1>subversion repositories</h1>
    <p>
      a list of the repositories
      <a href="http://www.track7.org/analogu/">the analog underground</a>
      currently has in subversion is below.&nbsp; clicking the links will allow
      you to browse and download the files, but it is probably more useful to
      check them out using an svn client.&nbsp; for windows, see
      <a href="http://tortoisesvn.net/">tortise svn</a>, which i may post some
      setup instructions for in the future.
    </p>

<?
  $svndir = dirname($_SERVER['DOCUMENT_ROOT']) . '/svn';
  if(is_dir($svndir)) {
    $svndir = opendir($svndir);
    if($svndir) {
?>
    <h2>repositories</h2>
    <ul>
<?
      while($repo = readdir($svndir))
        if(strpos($repo, '.') === false)
          echo '      <li><a href="' . $repo . '/">' . $repo . "</a></li>\n";
?>
    </ul>
<?
      closedir($svndir);
    }
  }
?>
  </body>
</html>
