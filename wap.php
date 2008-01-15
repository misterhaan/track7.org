<?
  if(isset($_POST['loadurl'])) {
    header('Location: http://' . $_POST['loadurl']);
    die;
  }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>track7 wireless home</title>
  </head>
  <body>
    <form method="post" action="wap.php">
      <label for="loadurl">http://</label><input name="loadurl" id="loadurl" />
    </form>
    <ol>
      <li><a href="http://wap.google.com/" accesskey="1">google</a></li>
      <li><a href="http://m.wund.com/cgi-bin/findweather/getForecast?brand=mobile&query=53719" accesskey="2">weather 53719</a></li>
    </ol>
  </body>
</html>
