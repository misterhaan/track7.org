<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('feeds');
?>
      <p>
        track7 content is being made available through rss feeds.&nbsp; if you
        use an rss news reader, you can add any of these feeds to be notified of
        new content on track7 without having to visit track7.
      </p>
      
      <ul class="feeds">
        <li><a href="posts.rss">recent posts</a></li>
      </ul>
<?
  $page->End();
?>
