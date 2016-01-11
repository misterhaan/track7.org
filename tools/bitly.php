<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(!$user->IsAdmin()) {
    header('HTTP/1.0 404 Not Found');
    require_once $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    die;
  }

  $html = new t7html([]);
  $html->Open('bitly test');
?>
      <h1>bitly test</h1>
      <p>
        anything entered into this form gets sent to bit.ly and shortened using
        the track7 account.
      </p>

      <form method=post>
        <label title="enter a url to shorten">
          <span class=label>url:</span>
          <span class=field><input name=url id=url></span>
        </label>
        <button title="shorten this url with bit.ly">shorten</button>
      </form>
<?php
  if(isset($_POST['url'])) {
    $url = t7send::Bitly(trim($_POST['url']));
?>
      <pre><code><?php echo $url; ?></code></pre>
<?php
  }
?>
