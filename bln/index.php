<?php
  define('NUM_ENTRIES', 10);  // how many entries per "page"
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'entries':
        if(isset($_GET['tagid']) && +$_GET['tagid']) {
          $entries = 'select e.id, e.url, e.posted, e.title, e.content, count(c.entry) as comments from blog_entrytags as et left join blog_entries as e on e.id=et.entry left join blog_comments as c on c.entry=e.id where et.tag=\'' . +$_GET['tagid'] . '\' and e.status=\'published\'';
          if(isset($_GET['before']) && +$_GET['before'])
            $entries .= ' and e.posted<\'' . +$_GET['before'] . '\'';
          $entries .= ' group by e.id order by e.posted desc limit ' . NUM_ENTRIES;
        } else {
          $entries = 'select e.id, e.url, e.posted, e.title, e.content, count(c.entry) as comments from blog_entries as e left join blog_comments as c on c.entry=e.id where status=\'published\'';
          if(isset($_GET['before']) && +$_GET['before'])
            $entries .= ' and e.posted<\'' . +$_GET['before'] . '\'';
          $entries .= ' group by e.id order by e.posted desc limit ' . NUM_ENTRIES;
        }
        $idmap = [];
        $ids = [];
        $urlmap = [];
        $urls = [];
        $ajax->Data->entries = [];
        if($entries = $db->query($entries)) {
          $lastdate = 0;
          while($entry = $entries->fetch_object()) {
            $ids[] = $entry->id;
            $idmap[$entry->id] = count($ajax->Data->entries);
            $posted = new stdClass();
            $lastdate = $entry->posted;
            $posted->timestamp = $entry->posted;
            $posted->datetime = gmdate('c', $entry->posted);
            $posted->display = strtolower(date('M j, Y', $entry->posted));
            $posted->title = strtolower(date('g:i a \o\n l F jS Y', $entry->posted));
            $entry->posted = $posted;
            $entry->tags = [];
            unset($entry->id);
            $ajax->Data->entries[] = $entry;
          }
          if(isset($_GET['tagid']) && +$_GET['tagid']) {
            if($more = $db->query('select 1 from blog_entrytags as et left join blog_entries as e on e.id=et.entry where et.tag=\'' . +$_GET['tagid'] . '\' and e.status=\'published\' and e.posted<\'' . +$lastdate . '\''))
              $ajax->Data->hasMore = $more->num_rows > 0;
          } else {
            if($more = $db->query('select 1 from blog_entries where status=\'published\' and posted<\'' . +$lastdate . '\''))
              $ajax->Data->hasMore = $more->num_rows > 0;
          }
          if(count($ids))
            if($tags = $db->query('select et.entry, t.name from blog_entrytags as et left join blog_tags as t on et.tag=t.id where et.entry in (' . implode(', ', $ids) . ')'))
              while($tag = $tags->fetch_object())
                if(isset($idmap[$tag->entry]))
                  $ajax->Data->entries[$idmap[$tag->entry]]->tags[] = $tag->name;
        } else
          $ajax->Fail('error getting latest blog entries.');
        break;
      default:
        $ajax->Data->fail = true;
        $ajax->Data->message = 'unknown function name.  supported function names are: entries.';
    }
    $ajax->Send();
    die;
  }

  $html = false;

  $tag = false;
  if(isset($_GET['tag']) && $tag = $db->query('select id, name, description from blog_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
    $tag = $tag->fetch_object();
  if($tag) {
    $html = new t7html(['ko' => true, 'rss' => ['title' => $tag->name . ' blog entries', 'url' => dirname($_SERVER['PHP_SELF']) . '/feed.rss?tags=' . $tag->name]]);
    $html->Open($tag->name . ' - blog');
?>
      <h1>
        latest blog entries — <?php echo $tag->name; ?>
        <a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss?tags=<?php echo $tag->name; ?>" title="rss feed of <?php echo $tag->name; ?> entries"><img alt=feed src="/images/feed.png"></a>
      </h1>

<?php
    ShowActions($tag->id);
?>
      <p id=taginfo data-tagid=<?php echo $tag->id; ?>>
        showing blog entries <?php echo $tag->description; ?>  go back to <a href="/bln/">all entries</a>.
      </p>
<?php
  } else {
    $html = new t7html(['ko' => true, 'rss' => ['title' => 'blog entries', 'url' => dirname($_SERVER['PHP_SELF']) . '/feed.rss']]);
    $html->Open('blog');
?>
      <h1>
        latest blog entries
        <a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of all entries"><img alt=feed src="/images/feed.png"></a>
      </h1>

      <nav class=tagcloud data-bind="visible: tags().length">
        <header>tags</header>
        <!-- ko foreach: tags -->
        <a data-bind="text: name, attr: { href: name + '/', title: 'entries tagged ' + name, 'data-count': count }"></a>
        <!-- /ko -->
      </nav>

<?php
    ShowActions();

    if($user->IsAdmin() && $drafts = $db->query('select url, title from blog_entries where status=\'draft\' order by posted desc'))
      if($drafts->num_rows) {
?>
      <h2>draft entries</h2>
      <ul>
<?php
        while($draft = $drafts->fetch_object()) {
?>
        <li><a href="<?php echo $draft->url; ?>"><?php echo $draft->title; ?></a></li>
<?php
        }
?>
      </ul>
<?php
      }
  }
?>
      <ul class=errors data-bind="visible: errors().length, foreach: errors">
        <li data-bind="text: $data"></li>
      </ul>

      <p data-bind="visible: !entries().length && !loadingEntries()">
        this blog is empty!
      </p>

      <!-- ko foreach: entries -->
      <article>
        <header>
          <h2><a data-bind="text: title, attr: {href: url}" title="view this post with its comments"></a></h2>
          <p class=postmeta>
            posted by <a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a>
            <!-- ko if: tags.length -->in <!-- ko foreach: tags --><!-- ko if: $index() > 0 -->, <!-- /ko --><a class=tag data-bind="text: $data, attr: {href: ($root.tagid ? '../' : '') + $data + '/', title: 'entries tagged ' + $data}"></a><!-- /ko --><!-- /ko -->
            <!-- ko if: posted.datetime != "1970-01-01T00:00:00+00:00" -->on <time data-bind="text: posted.display, attr: {datetime: posted.datetime, title: posted.title}"></time><!-- /ko -->
          </p>
        </header>
        <div class=entrycontent data-bind="html: content">
        </div>
        <footer>
          <p class=comments>
            <a data-bind="text: comments + ' comments', attr: {href: url + '#comments', title: (comments ? 'join' : 'start') + ' the discussion on this entry'}"></a>
          </p>
        </footer>
      </article>

      <!-- /ko -->

      <p class=loading data-bind="visible: loadingEntries">loading more entries . . .</p>
      <p class=more data-bind="visible: hasMoreEntries"><a href=#nextpage data-bind="click: LoadEntries">load more entries</a></p>
<?php
  $html->Close();
  die;
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(isset($_GET['tag'])) {
    $tag = 'select name, description from taginfo where type=\'entries\' and name=\'' . addslashes($_GET['tag']) . '\'';
    if($tag = $db->GetRecord($tag, 'error looking up tag information', 'tag not found')) {
      $page->AddFeed($tag->name . ' entries', '/feeds/entries.rss?tags=' . $tag->name);
      $page->Start('bln/' . $tag->name, 'bln [' . $tag->name . ']<a class="feed" href="/feeds/entries.rss?tags=' . $tag->name . '" title="rss feed of ' . $tag->name . ' entries"><img src="/style/feed.png" alt="feed" /></a>');
?>
      <p>
        <?=$tag->description; ?>
      </p>
<?
      if($user->GodMode) {
?>
      <ul><li><a href="/tools/taginfo.php?type=entries&amp;name=<?=$tag->name; ?>">add/edit tag description</a></li></ul>
<?
      }
    }
  }
  if(!$tag) {
    $page->AddFeed('entries', '/feeds/entries.rss');
    $page->Start('bln', 'bln<a class="feed" href="/feeds/entries.rss" title="rss feed of all entries"><img src="/style/feed.png" alt="feed" /></a>', 'a natural blog');
    $page->TagCloud('entries', 'tag=', 2, 4, 8, 16);  // keep in sync with ../index.php
  }
  if($user->GodMode) {
    $page->Info('<a href="&amp;edit">add a new entry</a>');
    $entries = 'select name, title from bln where status=\'draft\' ' . ($tag ? 'and (tags=\'' . $tag->name . '\' or tags like \'' . $tag->name . ',%\' or tags like \'%,' . $tag->name . '\' or tags like \'%,' . $tag->name . ',%\') ' : '') . 'order by instant desc';
    if($entries = $db->Get($entries, 'error looking up draft entries', '')) {
      $page->Heading('draft entries');
?>
      <ul>
<?
      while($entry = $entries->NextRecord()) {
        $links[] = '<a href="' . $entry->name . '">' . $entry->title . '</a>';
?>
        <li><a href="<?=$entry->name; ?>"><?=$entry->title; ?></a></li>
<?
      }
?>
      </ul>
<?
    }
  }
  $entries = 'select name, instant, tags, title, post from bln where not status=\'draft\' ' . ($tag ? 'and (tags=\'' . $tag->name . '\' or tags like \'' . $tag->name . ',%\' or tags like \'%,' . $tag->name . '\' or tags like \'%,' . $tag->name . ',%\') ' : '') . 'order by instant desc';
  if($entries = $db->GetSplit($entries, 10, 0, '', '', 'error looking up entries', 'no entries have been made yet')) {
    while($entry = $entries->NextRecord()) {
      if($entry->instant)
        $page->Heading('<span class="when">posted in ' . TagLinks($entry->tags) . ', ' . strtolower($user->tzdate('M j, Y', $entry->instant)) . '</span><a href="' . $entry->name . '">' . $entry->title . '</a>');
      else
        $page->Heading('<span class="when">posted in ' . TagLinks($entry->tags) . '</span><a href="' . $entry->name . '">' . $entry->title . '</a>');
?>
        <?=$entry->post; ?>

<?
      $comments = 'select count(1) from comments where page=\'/output/pen/bln/' . $entry->name . '\'';
      if(false !== $comments = $db->GetValue($comments, 'error finding number of comments on this entry', '')) {
?>
      <p><a href="<?=$entry->name; ?>#comments"><?=$comments; ?> comment<?=$comments == 1 ? '' : 's'; ?> on this entry</a></p>

<?
      }
    }
    $page->SplitLinks();
  }
  $page->End();

  /**
   * create the menu of actions.
   * @param integer $tagid id of the tag to edit from this page, if any
   */
  function ShowActions($tagid = false) {
    global $user;
    if($user->IsAdmin()) {
?>
      <nav class=actions>
        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>start a new entry</a>
<?php
      if($tagid) {  // TODO:  make tag description edit go somewhere that works
?>
        <a href="#tagedit" class=edit>edit tag description</a>
<?php
      }
?>
      </nav>

<?php
    }
  }

  function TagLinks($tags) {
    $tags = explode(',', $tags);
    foreach($tags as $tag) {
      $links[] = '<a href="tag=' . $tag . '">' . $tag . '</a>';
    }
    return implode(', ', $links);
  }
?>
