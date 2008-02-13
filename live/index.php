<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  define('MAXITEMS', 7);

  $quotetext[] = 'dudes love it but chicks at best endure it';
  $quotetext[] = 'there&rsquo;s no i in team &mdash; there&rsquo;s a me though if you jumble it up';
  $quotetext[] = 'there&rsquo;s no pecans in this!';
  $quotetext[] = 'in wine there is wisdom; in beer there is freedom; in water there is bacteria';
  $quotetext[] = 'always put salt in your eyes';
  $quoteby[] = 'r. smuckles';
  $quoteby[] = 'g. house';
  $quoteby[] = 'crazy pecan lady';
  $quoteby[] = 'b. franklin';
  $quoteby[] = 'mother';
  $quoteid = rand(0, count($quotetext) - 1);

  require_once 'auText.php';
  $page->AddFeed('track7', '/feeds/unifeed.rss');
  $page->AddFeed('track7 site updates', '/feeds/updates.rss');
  $page->AddFeed('track7 forum posts', '/feeds/posts.rss');
  $page->AddFeed('track7 page comments', '/feeds/comments.rss');
  $page->AddFeed('track7 bln entries', '/feeds/entries.rss');
  $page->AddFeed('track7 album photos', '/feeds/photos.rss');
  $page->Start('track7', '');
?>
      <div id="welcomeabout">
        <h1>
          <img src="/style/<?=$user->Style; ?>/track7.png" alt="track7" />
        </h1>
        <p id="randomquote" title="- <?=$quoteby[$quoteid]; ?>"><?=$quotetext[$quoteid]; ?></p>

<?
  $page->Heading('welcome to track7');
?>
        <p>
          i&rsquo;m <a href="/user/misterhaan/">misterhaan</a>, and track7 is my
          personal site to host whatever i feel like putting out there and so i
          can play around with apache, php, and mysql.
        </p><p>
          if you find something here useful or interesting, i&rsquo;d like to
          hear about it!&nbsp; consider commenting on the page if it&rsquo;s
          enabled for comments, posting about it on the forums, sending me a
          message.
        </p><p>
          i also like helping people.&nbsp; if you come across a guide that
          doesn&rsquo;t have as much information as you&rsquo;d like, or you
          just have a question you think i might have an answer for, i&rsquo;ll
          do my best to help.&nbsp; posting your question in the forums is the
          best approach since other people can also help you, but if you prefer
          to keep your question private you can also send it to me in a message. 
        </p>
      </div>
      <div id="features">
<?
  $page->Heading('features');
?>
        <dl>
          <dt><a href="/analogu/"><img class="icon" src="/analogu/favicon.png" alt="" />the analog underground</a></dt>
          <dd>
            download free software with source code. 
          </dd>
          <dt><a href="/output/pen/"><img class="icon" src="/output/pen/favicon.png" alt="" />pen vs. sword</a></dt>
          <dd>
            read short stories and theories.
          </dd>
          <dt><a href="/output/pen/bln/"><img class="icon" src="/output/pen/favicon.png" alt="" />bln (natural blog)</a></dt>
          <dd>
            find out what i think.
          </dd>
          <dt><a href="/output/lego.php"><img class="icon" src="/output/favicon.png" alt="" />lego models</a></dt>
          <dd>
            download instructions to build lego models.
          </dd>
          <dt><a href="/output/gfx/"><img class="icon" src="/output/favicon.png" alt="" />graphics</a></dt>
          <dd>
            see pencil sketches and digital artwork.
          </dd>
          <dt><a href="/output/gfx/album/"><img class="icon" src="/output/gfx/album/favicon.png" alt="" />photo album</a></dt>
          <dd>
            view my collection of photos.
          </dd>
          <dt><a href="/geek/guides/"><img class="icon" src="/geek/favicon.png" alt="" />guides</a></dt>
          <dd>
            learn something.
          </dd>
          <dt><a href="/geek/discgolf/"><img class="icon" src="/geek/favicon.png" alt="" />disc golf</a></dt>
          <dd>
            track disc golf scores.
          </dd>
          <dt><a href="/hb/"><img class="icon" src="/hb/favicon.png" alt="" />forums</a></dt>
          <dd>
            speak your mind and see what others think.
          </dd>
        </dl>

<?
  $page->Heading('recent additions<a class="feed" href="/feeds/unifeed.rss" title="track7 unifeed"><img src="/style/feed.png" alt="feed" /></a>');
  // get last MAXITEMS items from updates, posts, comments, entries, and photos
  $updates = 'select instant, `change` from updates order by instant desc';
  if($updates = $db->GetLimit($updates, 0, MAXITEMS, 'error looking up updates', ''))
    $update = $updates->NextRecord();
  else
    $update = false;
  $posts = 'select p.id, p.number, p.thread, p.instant, p.subject, p.uid, u.login from hbposts as p left join users as u on u.uid=p.uid order by instant desc';
  if($posts = $db->GetLimit($posts, 0, MAXITEMS, 'error looking up forum posts', ''))
    $post = $posts->NextRecord();
  else
    $post = false;
  $comments = 'select c.instant, c.page, c.uid, u.login, c.name, c.url from comments as c left join users as u on u.uid=c.uid order by instant desc';
  if($comments = $db->GetLimit($comments, 0, MAXITEMS, 'error looking up comments', ''))
    $comment = $comments->NextRecord();
  else
    $comment = false;
  $entries = 'select instant, name, title from bln order by instant desc';
  if($entries = $db->GetLimit($entries, 0, MAXITEMS, 'error looking up bln entries', ''))
    $entry = $entries->NextRecord();
  else
    $entry = false;
  $photos = 'select added, id, caption from photos order by added desc';
  if($photos = $db->GetLimit($photos, 0, MAXITEMS, 'error looking up album photos', ''))
    $photo = $photos->NextRecord();
  else
    $photo = false;
  $guides = 'select id, dateadded, title from guides order by dateadded desc'; 
  if($guides = $db->GetLimit($photos, 0, MAXITEMS, 'error looking up guides and tips', ''))
    $guide = $guides->NextRecord();
  else
    $guide = false;
?>
        <table id="updates" class="columns" cellspacing="0">
<?
  $items = 0;
  while($items < MAXITEMS && ($update || $post || $comment || $entry || $photo || $guide)) {
    if($update && (!$post || $update->instant >= $post->instant) && (!$comment || $update->instant >= $comment->instant) && (!$entry || $update->instant >= $entry->instant) && (!$photo || $update->instant >= $photo->added) && (!$guide || $update->instant >= $guide->dateadded)) {
      echo '          <tr' . ($items ? ' class="first"' : '') . '><th>' . strtolower(auText::SmartTime($update->instant, $user)) . '</th><td class="type"><img src="/style/misterhaan-16.png" alt="update" title="site update" /></td><td>' . $update->change . "</td></tr>\n";
      $update = $updates->NextRecord();
    } elseif($post && (!$update || $post->instant >= $update->instant) && (!$comment || $post->instant >= $comment->instant) && (!$entry || $post->instant >= $entry->instant) && (!$photo || $post->instant >= $photo->added) && (!$guide || $post->instant >= $guide->dateadded)) {
      echo '          <tr' . ($items ? ' class="first"' : '') . '><th>' . strtolower(auText::SmartTime($post->instant, $user)) . '</th><td class="type"><img src="/hb/favicon-16.png" alt="post" title="forum post" /></td><td><a href="/hb/thread' . $post->thread . ($post->number - 1 > _FORUM_POSTS_PER_PAGE ? '/skip=' . (floor(($post->number - 1) / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE) . '#p' : '/#p') . $post->id . '">' . $post->subject . '</a> by ' . ($post->uid ? '<a href="/user/' . $post->login . '/">' . $post->login . '</a>' : 'anonymous') . "</td></tr>\n";
      $post = $posts->NextRecord();
    } elseif($comment && (!$update || $comment->instant >= $update->instant) && (!$post || $comment->instant >= $post->instant) && (!$entry || $comment->instant >= $entry->instant) && (!$photo || $comment->instant >= $photo->added) && (!$guide || $comment->instant >= $guide->dateadded)) {
      $pagename = explode('/', $comment->page);
      $pagename = $pagename[count($pagename) - 1];
      echo '          <tr' . ($items ? ' class="first"' : '') . '><th>' . strtolower(auText::SmartTime($comment->instant, $user)) . '</th><td class="type"><img src="/favicon-16.png" alt="comment" title="page comment" /></td><td>comment on <a href="' . $comment->page . '">' . $pagename . '</a> by ' . ($comment->uid ? '<a href="/user/' . $comment->login . '/">' . $comment->login . '</a>' : ($comment->url ? '<a href="' . $comment->url . '">' . $comment->name . '</a>' : $comment->name)) . "</td></tr>\n";
      $comment = $comments->NextRecord();
    } elseif($entry && (!$update || $entry->instant >= $update->instant) && (!$post || $entry->instant >= $post->instant) && (!$comment || $entry->instant >= $comment->instant) && (!$photo || $entry->instant >= $photo->added) && (!$guide || $entry->instant >= $guide->dateadded)) {
      echo '          <tr' . ($items ? ' class="first"' : '') . '><th>' . strtolower(auText::SmartTime($entry->instant, $user)) . '</th><td class="type"><img src="/output/pen/favicon-16.png" alt="entry" title="bln entry" /></td><td><a href="/output/pen/bln/' . $entry->name . '">' . $entry->title . "</a></td></tr>\n";
      $entry = $entries->NextRecord();
    } elseif($photo && (!$update || $photo->added >= $update->instant) && (!$post || $photo->added >= $post->instant) && (!$comment || $photo->added >= $comment->instant) && (!$entry || $photo->added >= $entry->instant) && (!$guide || $photo->added >= $guide->dateadded)) {
      echo '          <tr' . ($items ? ' class="first"' : '') . '><th>' . strtolower(auText::SmartTime($photo->added, $user)) . '</th><td class="type"><img src="/output/gfx/album/favicon-16.png" alt="photo" title="album photo" /></td><td><a href="/output/gfx/album/photo/' . $photo->id . '">' . $photo->caption . "</a></td></tr>\n";
      $photo = $photos->NextRecord();
    } elseif($guide && (!$update || $guide->dateadded >= $update->instant) && (!$post || $guide->dateadded >= $post->instant) && (!$comment || $guide->dateadded >= $comment->instant) && (!$entry || $guide->dateadded >= $entry->instant) && (!$photo || $guide->dateadded >= $photo->added)) {
      echo '          <tr' . ($items ? ' class="first"' : '') . '><th>' . strtolower(auText::SmartTime($guide->dateadded, $user)) . '</th><td class="type"><img src="/geek/favicon-16.png" alt="guide" title="guide" /></td><td><a href="/geek/guides/' . $guide->id . '">' . $guide->title . "</a></td></tr>\n";
      $guide = $guides->NextRecord();
    }
    $items++;
  }
?>
        </table>
        <p class="links">[
          <a href="new.php">updates</a> |
          <a href="hb/recentposts.php">posts</a> |
          <a href="comments.php">comments</a>
        ]</p>
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
