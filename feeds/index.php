<?
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->AddFeed('track7', '/feeds/unifeed.rss');
  $page->AddFeed('track7 site updates', '/feeds/updates.rss');
  $page->AddFeed('track7 forum posts', '/feeds/posts.rss');
  $page->AddFeed('track7 page comments', '/feeds/comments.rss');
  $page->AddFeed('track7 bln entries', '/feeds/entries.rss');
  $page->AddFeed('track7 album photos', '/feeds/photos.rss');
  $page->AddFeed('track7 guides', '/feeds/guides.rss');
  $page->AddFeed('track7 art', '/feeds/art.rss');
  $page->AddFeed('track7 disc golf rounds', '/feeds/rounds.rss');
  $page->Start('feeds');
?>
      <p>
        new track7 content is available through rss feeds and twitter.&nbsp; if
        you use an rss news reader or twitter, you can add any of these feeds to
        be notified of new content on track7 without having to visit track7.
      </p>

      <ul class="twitter"><li><a href="http://twitter.com/track7feed">@track7feed</a></li></ul>

      <ul class="feeds">
        <li><a href="unifeed.rss">unifeed</a> (contains everything listed below)</li>
        <li><a href="updates.rss">site updates</a></li>
        <li><a href="posts.rss">forum posts</a></li>
        <li><a href="comments.rss">page comments</a></li>
        <li><a href="entries.rss">bln entries</a></li>
        <li><a href="photos.rss">album photos</a></li>
        <li><a href="guides.rss">guides</a></li>
        <li><a href="art.rss">art</a></li>
        <li><a href="rounds.rss">disc golf rounds</a></li>
      </ul>
<?
  $page->End();
?>
