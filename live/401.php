<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('401 authorization required', '401 you are not me');
?>
      <p>
        if you are seeing this page, it means that you were unable to prove that
        you are me, or you are using a browser which didn't ask you if you were
        me.
      </p>
      <p>
        the page you requested is only for me, and since you did not prove that
        you are me, i am not going to let you see it.&nbsp; if you ever become
        me, then by all means come back -- otherwise quit poking around in my
        site's private parts.
      </p>

<?
  $page->End();
?>
