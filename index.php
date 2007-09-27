<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  $quotetext[] = 'dudes love it but chicks at best endure it';
  $quotetext[] = 'there&rsquo;s no i in team &mdash; there&rsquo;s a me though if you jumble it up';
  $quotetext[] = 'there&rsquo;s no pecans in this!';
  $quotetext[] = 'in wine there is wisdom; in beer there is freedom; in water there is bacteria';
  $quoteby[] = 'r. smuckles';
  $quoteby[] = 'g. house';
  $quoteby[] = 'crazy pecan lady';
  $quoteby[] = 'b. franklin';
  $quoteid = rand(0, count($quotetext) - 1);

  require_once 'auText.php';
  $page->AddFeed('track7', '/feeds/unifeed.rss');
  $page->AddFeed('track7 site updates', '/feeds/updates.rss');
  $page->AddFeed('track7 forum posts', '/feeds/posts.rss');
  $page->AddFeed('track7 page comments', '/feeds/comments.rss');
  $page->Start('track7 - ' . $quotetext[$quoteid], '');
?>
      <h1>
        <img src="/style/<?=$user->Style; ?>/track7.png" alt="track7" />
        <span class="sub" id="randomquote" title="- <?=$quoteby[$quoteid]; ?>"><?=$quotetext[$quoteid]; ?></span>
      </h1>

      <div class="twocolumn">
<?
  $page->Heading('welcome to track7');
?>
      <img src="/favicon.png" alt="" class="icon" />
      <p class="iconned">
        track7 is for me.&nbsp; it&rsquo;s here because i enjoy working on it
        and it&rsquo;s a good way for me to learn some useful things.&nbsp; if
        something here ends up being interesting or useful for someone else,
        then that&rsquo;s even better!  
      </p>

<?
  $sects = 'select name, tooltip, urlout from pages where parent=1 and flags&' . _FLAG_PAGES_ISSECTION . '>0 and flags&' . ($user->valid ? _FLAG_PAGES_HIDELOGIN : _FLAG_PAGES_HIDENOLOGIN) . '=0 order by sort';
  if($sects = $db->Get($sects, 'error looking up sections', '')) {
?>
        <ul id="sections">
<?
    while($sect = $sects->NextRecord()) {
      $sect->name = str_replace(array('analogu', 'pen', 'a/v', 'album', 'forums', 'shop'),
                                array('the analog underground', 'pen vs. sword', 'audio / video', 'photo album', 'oi (forums)', 'merchandise'), $sect->name);
?>
          <li><a href="<?=$sect->urlout; ?>" title="<?=$sect->tooltip; ?>">
            <img src="<?=$sect->urlout; ?>favicon.png" alt="" />
            <?=$sect->name; ?>

          </a></li>
<?
    }
?>
        </ul>

<?
  }
  $pages = 'select name, tooltip, urlout from pages where parent=1 and flags&' . ($user->valid ? $user->godmode ? _FLAG_PAGES_ISSECTION : _FLAG_PAGES_HIDELOGIN | _FLAG_PAGES_ISSECTION : _FLAG_PAGES_HIDENOLOGIN | _FLAG_PAGES_ISSECTION) . '=0 order by sort';
  if($pages = $db->Get($pages, 'error looking up general pages', '')) {
?>
        <ul>
<?
    while($p = $pages->NextRecord()) {
?>
          <li><a href="<?=$p->urlout; ?>" title="<?=$p->tooltip; ?>"><?=$p->name; ?></a></li>
<?
    }
?>
        </ul>
<?
  }
?>
      </div>
      <div class="twocolumn">
<?
  $page->Heading('recent updates<a class="feed" href="/feeds/updates.rss" title="rss feed of recent updates"><img src="/style/feed.png" alt="feed" /></a>');
  $updates = 'select instant, `change` from updates order by instant desc';
  if($updates = $db->GetLimit($updates, 0, 3, 'unable to read last 3 updates', 'no updates found')) {
?>
      <table class="columns" cellspacing="0" id="recentupdates">
<?
    $first = ' class="firstchild"';
    while($update = $updates->NextRecord()) {
?>
        <tr<?=$first; ?>><th><?=strtolower(auText::SmartDate($update->instant, $user)); ?></th><td><?=$update->change; ?></td></tr>
<?
        $first = '';
    }
?>
      </table>
      <p class="seemore"><a href="/new.php">see more updates...</a></p>

<?
  }
  $page->Heading('recent posts<a class="feed" href="/feeds/posts.rss" title="rss feed of recent posts"><img src="/style/feed.png" alt="feed" /></a>');
  $posts = 'select p.instant, p.id, p.subject, p.uid, u.login, p.number, p.tid, t.fid, f.title from oiposts as p left join users as u on p.uid=u.uid left join oithreads as t on p.tid=t.id left join oiforums as f on t.fid=f.id order by p.instant desc';
  if($posts = $db->GetLimit($posts, 0, 3, 'unable to read last 3 posts', 'no posts found')) {
?>
      <table class="columns" cellspacing="0" id="recentposts">
<?
    $first = ' class="firstchild"';
    while($post = $posts->NextRecord()) {
      $post->number = floor($post->number / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE;
      if($post->uid) {
?>
        <tr<?=$first; ?>><th><?=strtolower(auText::SmartTime($post->instant, $user)); ?></th><td><a href="/oi/f<?=$post->fid; ?>/t<?=$post->tid; ?>/<?=$post->number ? '&amp;skip=' . $post->number : ''; ?>#p<?=$post->id; ?>"><?=$post->subject; ?></a> by <a href="/user/<?=$post->login; ?>/"><?=$post->login; ?></a> in <?=$post->title; ?></td></tr>
<?
      } else {
?>
        <tr<?=$first; ?>><th><?=strtolower(auText::SmartTime($post->instant, $user)); ?></th><td><a href="/oi/f<?=$post->fid; ?>/t<?=$post->tid; ?>/<?=$post->number ? '&amp;skip=' . $post->number : ''; ?>#p<?=$post->id; ?>"><?=$post->subject; ?></a> by anonymous in <?=$post->title; ?></td></tr>
<?
      }
      $first = '';
    }
?>
      </table>
      <p class="seemore"><a href="/oi/recentposts.php">see more posts...</a></p>

<?
  }
  $page->Heading('recent comments<a class="feed" href="/feeds/comments.rss" title="rss feed of recent comments"><img src="/style/feed.png" alt="feed" /></a>');
  $comments = 'select c.page, c.instant, c.uid, u.login, c.name from comments as c left join users as u on c.uid=u.uid order by instant desc';
  if($comments = $db->GetLimit($comments, 0, 3, 'unable to read last 3 comments', 'no comments found')) {
?>
      <table class="columns" cellspacing="0" id="recentcomments">
<?
    $first = ' class="firstchild"';
    while($comment = $comments->NextRecord()) {
      $commentpage = explode('/', $comment->page);
      $commentpage = $commentpage[count($commentpage) - 1];
      if($comment->uid) {
?>
        <tr<?=$first; ?>><th><?=strtolower(auText::SmartTime($comment->instant, $user)); ?></th><td>on <a href="<?=$comment->page; ?>#comments"><?=$commentpage; ?></a> by <a href="/user/<?=$comment->login; ?>/"><?=$comment->login; ?></a></td></tr>
<?
      } else {
?>
        <tr<?=$first; ?>><th><?=strtolower(auText::SmartTime($comment->instant, $user)); ?></th><td>on <a href="<?=$comment->page; ?>#comments"><?=$commentpage; ?></a> by <?=$comment->name; ?></td></tr>
<?
      }
      $first = '';
    }
?>
      </table>
      <p class="seemore"><a href="/comments.php">see more comments...</a></p>

<?
  }
?>
      </div>

      <p id="shorturl">
        thanks to <a href="http://www.shorturl.com/ref/in.cgi?track7.vze.com">shorturl.com</a>&rsquo;s
        free service, this site can be accessed through track7.vze.com without
        popups or any sort of ads, and at no cost to me.&nbsp; you can continue
        to use track7.vze.com as long as this service remains free.
      </p>
<?
  $page->End();
?>
