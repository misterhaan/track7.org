<?
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
?>
      <ul><li><a href="&amp;edit">add a new entry</a></li></ul>

<?
  }
  $entries = 'select name, instant, tags, title, post from bln ' . ($tag ? 'where tags=\'' . $tag->name . '\' or tags like \'' . $tag->name . ',%\' or tags like \'%,' . $tag->name . '\' or tags like \'%,' . $tag->name . ',%\' ' : '') . 'order by instant desc';
  if($entries = $db->GetSplit($entries, 10, 0, '', '', 'error looking up entries', 'no entries have been made yet')) {
    while($entry = $entries->NextRecord()) {
      if($entry->instant)
        $page->Heading('<span class="when">posted in ' . TagLinks($entry->tags) . ', ' . strtolower($user->tzdate('M j, Y', $entry->instant)) . '</span>' . $entry->title);
      else
        $page->heading('<span class="when">posted in ' . TagLinks($entry->tags) . '</span>' . $entry->title);
?>
      <p>
        <?=$entry->post; ?>

      </p>
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

  function TagLinks($tags) {
    $tags = explode(',', $tags);
    foreach($tags as $tag) {
      $links[] = '<a href="tag=' . $tag . '">' . $tag . '</a>';
    }
    return implode(', ', $links);
  }
?>
