<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  // redirect logged-in users to profile edit page
  if($user->Valid) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . '/user/editprofile.php?tab=display');
    die;
  }

  if(isset($_GET['scheme']) && ($_GET['scheme'] == 'water' || $_GET['scheme'] == 'fire' || $_GET['scheme'] == 'earth' || $_GET['scheme'] == 'air')) {
    setcookie('style', $_GET['scheme'], strtotime('2038-01-18') , '/', '.' . _HOST);
    $page->Start('color scheme chosen');
?>
      <p>
        your browser has been sent a cookie telling it to use the <?=$_GET['scheme']; ?>
        color scheme for track7.&nbsp; if you accepted this cookie, the next
        page you view at track7 will use the <?=$_GET['scheme']; ?> color scheme.
      </p>
<?
  } else {
    $page->Start('choose color scheme');
?>
      <p>
        click a color scheme below and your browser will be sent a cookie
        telling it which color scheme should be used for track7.
      </p>
      <ul id="colorchoice">
        <li><a href="?scheme=water"><img src="/style/water/thumb.png" alt="" />water (default)</a></li>
        <li><a href="?scheme=fire"><img src="/style/fire/thumb.png" alt="" />fire</a></li>
        <li><a href="?scheme=earth"><img src="/style/earth/thumb.png" alt="" />earth</a></li>
        <li><a href="?scheme=air"><img src="/style/air/thumb.png" alt="" />air</a></li>
      </ul>
      <br class="clear" />
<?
  }
  $page->End();
?>
