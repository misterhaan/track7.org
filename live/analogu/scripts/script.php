<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFile.php';

  define('_FLAG_AUSCRIPTS_SQL', 0x01);
  define('_FLAG_AUSCRIPTS_PEAR', 0x02);
  define('_FLAG_AUSCRIPTS_LAYOUT', 0x04);
  define('_FLAG_AUSCRIPTS_APACHE', 0x08);

  if(!isset($_GET['name']))
    $page->Show404();
  $script = 'select * from auscripts where name=\'' . addslashes($_GET['name']) . '\'';
  if($script = $db->Get($script, 'error looking up information for this script')) {
    if($script = $script->NextRecord()) {
      $page->Start($script->name . ' - scripts - the analog underground', $script->title);
      $filename = $script->language . '-' . $script->name . '.zip';
?>
      <h2>download</h2>
      <ul><li><a href="/files/analogu/scripts/<?=$filename; ?>"><?=$filename; ?></a> (<?=auFile::Size($filename); ?>)</li></ul>

      <h2>description</h2>
<?=$script->description; ?>

<?
      if($script->flags > 0 || $script->language == 'php') {
?>
      <h2>requirements</h2>
      <p>
        the following are required in order for this script package to work as
        intended.&nbsp; if a website does not meet all of the listed
        requirements, it is possible that the script can be modified to still
        work, though possibly with less functionality than intended.
      </p>
      <ul>
<?
        if($script->language == 'php') {
?>
        <li><a href="http://www.php.net/downloads.php">php</a></li>
<?
        }
        if($script->flags & _FLAG_AUSCRIPTS_SQL) {
?>
        <li><a href="http://www.mysql.com/downloads/mysql/">sql database</a></li>
<?
        }
        if($script->flags & _FLAG_AUSCRIPTS_PEAR) {
?>
        <li><a href="http://pear.php.net/package/DB/download">pear::db</a></li>
<?
        }
        if($script->flags & _FLAG_AUSCRIPTS_LAYOUT) {
?>
        <li><a href="layout">layout classes</a></li>
<?
        }
        if($script->flags & _FLAG_AUSCRIPTS_APACHE) {
?>
        <li><a href="http://httpd.apache.org/docs/howto/htaccess.html">.htaccess</a></li>
<?
        }
?>
      </ul>

<?
      }
    } else {
      $page->Show404();  // script doesn't exist, so give a 404
    }
  } else {
    $page->Start('error', 'scripts - the analog underground');
  }
  $page->End();
?>
