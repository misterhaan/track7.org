<?
/*----------------------------------------------------------------------------*\
 | show higlighted source code for a php file.  the filename is expected in   |
 | $_GET['file'], in the form of path/to/script.php                           |
\*----------------------------------------------------------------------------*/

  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  // get rid of any leading slashes
  while(substr($_GET['file'], 0, 1) == '/')
    $_GET['file'] = substr($_GET['file'], 1);

  // make sure they've asked for a file, it's a php file, it's not a hidden file, and it exists
  if(!isset($_GET['file']) ||
     substr($_GET['file'], -4) != '.php' ||
     substr($_GET['file'], 0, 1) == '.' ||
     !file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $_GET['file']) ||
     strpos($_GET['file'], '/.') !== false) {
    $page->Show404();  // show a file not found error
  } else {
    // get all of the higlighting colors from php.ini
    $highlight['bg'] = ini_get('highlight.bg');
    $highlight['comment'] = ini_get('highlight.comment');
    $highlight['default'] = ini_get('highlight.default');
    $highlight['html'] = ini_get('highlight.html');
    $highlight['keyword'] = ini_get('highlight.keyword');
    $highlight['string'] = ini_get('highlight.string');
    // set up arrays to use with str_replace later
    foreach($highlight as $type => $color) {
      $font[] = '<font color="' . $color . '">';  // php4 uses font tags
      $span[] = '<span style="color: ' . $color . '">';  // php5 uses span tags with css
      $class[] = '<span class="' . $type . '">';  // we will be replacing specific color settings with a classname that can have the color controlled by an external css file
    }
    $page->Start('source of /' . $_GET['file'], 'php source', '/' . $_GET['file']);
?>
      <div class="source"><ol>
<?
    $file = fopen($_SERVER['DOCUMENT_ROOT'] . '/' . $_GET['file'], 'r');  // open the file for reading
    $php = 0;  // should be either 1 or 0:  1 when we opened a php and didn't close it, 0 otherwise
    $comment = 0;  // should be either 1 or 0:  1 when we opened a comment and didn't close it, 0 otherwise

    // loop through each line of the file
    while(!feof($file)) {
      $line = fgets($file);
      // if the line starts with one space, save it as a non-breaking space and trim it off
      if(substr($line, 0, 1) == ' ' && substr($line, 1, 1) != ' ') {
        $space = '&nbsp;';
        $line = substr($line, 1);
      } else
        $space = '';
      // add a comment start to the beginning of the line if we are in a comment
      if($comment)
        $line = '/*' . $line;
      // add a php start to the beginning of the line if we are in php code
      if($php)
        $line = '<?' . $line;
      // remember to remove the php start and comment start if we added them
      $rmphp = $php;
      $rmcmt = $comment;
      // figure out if we start the next line in php code
      $php = substr_count($line, '<?') - substr_count($line, '?>');
      // figure out if we start the next line in a comment
      $comment = substr_count($line, '/*') - substr_count($line, '*/');
      // end the comment on this line to avoid unended comment errors
      if($comment)
        $line .= '*/';
      // highlight the line and get rid of unneccessary code tags, line breaks, and br tags
      $line = str_replace(array('<code>', '</code>', "\n", "\r", '<br />'), '', highlight_string($line, true));
      // change font color tags into span class tags (php4)
      $line = str_replace($font, $class, $line);
      // change span color tags into span class tags (php5)
      $line = str_replace($span, $class, $line);
      // change font closing tags into span closing tags (php4)
      $line = str_replace('</font>', '</span>', $line);

      // remove php start if we added it
      if($rmphp) {
        $pos = strpos($line, '&lt;?');
        if($pos !== false)
          $line = substr($line, 0, $pos) . substr($line, $pos + 5);
      }
      // remove comment start if we added it
      if($rmcmt) {
        $pos = strpos($line, '/*');
        if($pos !== false)
          $line = substr($line, 0, $pos) . substr($line, $pos + 2);
      }
      // remove comment end if we added it
      if($comment) {
        $pos = strrpos($line, '*/');  //strrpos searches backward for '*' here, but */ should be the last thing on the line anyway
        if($pos !== false)
          $line = substr($line, 0, $pos) . substr($line, $pos + 2);
      }
      
      // get rid of any empty span tags, 2 levels deep
      $line = preg_replace('/<span class="(bg|comment|default|html|keyword|string)"><\/span>/', '', $line);
      $line = preg_replace('/<span class="(bg|comment|default|html|keyword|string)"><\/span>/', '', $line);

      // put a non-breaking space on any blank lines so that firefox/mozilla/netscape don't overlap the line numbers
      if(strlen($line) < 1)
        $line = '&nbsp;';
?>
        <li><?=$space; ?><?=$line; ?></li>
<?
    }
?>
      </ol></div>

<?
    $page->End();
  }
?>
