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
        $ajax->Fail('unknown function name.  supported function names are: entries.');
        break;
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
        showing blog entries
<?php
    if($user->IsAdmin()) {
?>
        <label class=multiline id=editdesc>
          <span class=field><textarea></textarea></span>
          <span>
            <a href="#save" title="save tag description" class="action okay"></a>
            <a href="#cancel" title="cancel editing" class="action cancel"></a>
          </span>
        </label>
<?php
    }
?>
        <span class=editable><?php echo $tag->description; ?></span>
        go back to <a href="/bln/">all entries</a>.
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
      <p class="more calltoaction" data-bind="visible: hasMoreEntries"><a class="action get" href=#nextpage data-bind="click: LoadEntries">load more entries</a></p>
<?php
  $html->Close();

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
      if($tagid) {
?>
        <a href="#tagedit" class=edit>edit tag description</a>
<?php
      }
?>
      </nav>

<?php
    }
  }
?>
