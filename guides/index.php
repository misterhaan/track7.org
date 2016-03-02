<?
  define('NUM_GUIDES', 10);  // how many guides per "page"
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'guides':
        if(isset($_GET['tagid']) && +$_GET['tagid']) {
          $guides = 'select g.id, g.url, g.posted, g.updated, g.title, g.summary, g.level, g.rating, g.votes, g.views, count(c.guide) as comments from guide_taglinks as tl left join guides as g on g.id=tl.guide left join guide_comments as c on c.guide=g.id where tl.tag=\'' . +$_GET['tagid'] . '\' and g.status=\'published\'';
          if(isset($_GET['before']) && +$_GET['before'])
            $guides .= ' and g.posted<\'' . +$_GET['before'] . '\'';
            $guides .= ' group by g.id order by g.updated desc limit ' . NUM_GUIDES;
        } else {
          $guides = 'select g.id, g.url, g.posted, g.updated, g.title, g.summary, g.level, g.rating, g.votes, g.views, count(c.guide) as comments from guides as g left join guide_comments as c on c.guide=g.id where status=\'published\'';
          if(isset($_GET['before']) && +$_GET['before'])
            $guides .= ' and g.posted<\'' . +$_GET['before'] . '\'';
            $guides .= ' group by g.id order by g.updated desc limit ' . NUM_GUIDES;
        }
        $ajax->Data->query = $guides;
        if($guides = $db->query($guides)) {
          $idmap = [];
          $ids = [];
          $ajax->Data->guides = [];
          $lastdate = 0;
          while($guide = $guides->fetch_object()) {
            $ids[] = $guide->id;
            $idmap[$guide->id] = count($ajax->Data->guides);
            if($guide->views > 9999)
              $guide->views = number_format($guide->views);
            $posted = new stdClass();
            $posted->timestamp = $guide->posted;
            //$posted->datetime = gmdate('c', $guide->posted);
            //$posted->display = t7format::SmartDate($guide->posted);
            $posted->title = strtolower(date('g:i a \o\n l F jS Y', $guide->posted));
            $guide->posted = $posted;
            $lastdate = $guide->updated;
            $updated = new stdClass();
            $updated->timestamp = $guide->updated;
            $updated->datetime = gmdate('c', $guide->updated);
            $updated->display = t7format::SmartDate($guide->updated);
            $updated->title = strtolower(date('g:i a \o\n l F jS Y', $guide->updated));
            $guide->updated = $updated;
            $guide->tags = [];
            unset($guide->id);
            $ajax->Data->guides[] = $guide;
          }
          if(isset($_GET['tagid']) && +$_GET['tagid']) {
            if($more = $db->query('select 1 from guide_taglinks as tl left join guides as g on g.id=tl.guide where tl.tag=\'' . +$_GET['tagid'] . '\' and g.status=\'published\' and g.updated<\'' . +$lastdate . '\''))
              $ajax->Data->hasMore = $more->num_rows > 0;
          } else {
            if($more = $db->query('select 1 from guides where status=\'published\' and updated<\'' . +$lastdate . '\''))
              $ajax->Data->hasMore = $more->num_rows > 0;
          }
          if(count($ids))
            if($tags = $db->query('select tl.guide, t.name from guide_taglinks as tl left join guide_tags as t on tl.tag=t.id where tl.guide in (' . implode(', ', $ids) . ')'))
              while($tag = $tags->fetch_object())
                if(isset($idmap[$tag->guide]))
                  $ajax->Data->guides[$idmap[$tag->guide]]->tags[] = $tag->name;
        } else
          $ajax->Fail('error looking up latest guides');
        break;
      default:
        $ajax->Fail('unknown function name.  supported function names are:  guides.');
    }
    $ajax->Send();
    die;
  }

  $html = false;

  $tag = false;
  if(isset($_GET['tag']) && $tag = $db->query('select id, name, description from guide_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
    $tag = $tag->fetch_object();
  if($tag) {
    $html = new t7html(['ko' => true, 'rss' => ['title' => $tag->name . ' guides', 'url' => dirname($_SERVER['PHP_SELF']) . '/feed.rss?tags=' . $tag->name]]);
    $html->Open($tag->name . ' - guides');
?>
      <h1>
        latest guides — <?php echo $tag->name; ?>
        <a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss?tags=<?php echo $tag->name; ?>" title="rss feed of <?php echo $tag->name; ?> guides"><img alt=feed src="/images/feed.png"></a>
      </h1>

<?php
    ShowActions($tag->id);
?>
      <p id=taginfo data-tagid=<?php echo $tag->id; ?>>
        showing guides dealing with <?php echo $tag->description; ?>  go back to <a href="/guides/">all guides</a>.
      </p>
<?php
  } else {
    $html = new t7html(['ko' => true, 'rss' => ['title' => 'guides', 'url' => dirname($_SERVER['PHP_SELF']) . '/feed.rss']]);
    $html->Open('guides');
?>
      <h1>
        latest guides
        <a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of all guides"><img alt=feed src="/images/feed.png"></a>
      </h1>

      <nav class=tagcloud data-bind="visible: tags().length">
        <header>tags</header>
        <!-- ko foreach: tags -->
        <a data-bind="text: name, attr: { href: name + '/', title: 'guides tagged ' + name, 'data-count': count }"></a>
        <!-- /ko -->
      </nav>

<?php
    ShowActions();

    if($user->IsAdmin() && $drafts = $db->query('select url, title from guides where status=\'draft\' order by posted desc'))
      if($drafts->num_rows) {
?>
      <h2>draft entries</h2>
      <ul>
<?php
        while($draft = $drafts->fetch_object()) {
?>
        <li><a href="<?php echo $draft->url; ?>/1"><?php echo $draft->title; ?></a></li>
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

      <p data-bind="visible: !guides().length && !loadingGuides()">
        no guides!  how will we know what to do?
      </p>

      <!-- ko foreach: guides -->
      <article>
        <header>
          <h2><a data-bind="text: title, attr: {href: url + '/1'}" title="read this guide"></a></h2>
          <p class=guidemeta>
            <span class=guidelevel data-bind="text: level, attr: {title: level + ' level'}"></span>
            <span class=tags data-bind="visible: tags.length, attr: {title: tags.length == 1 ? '1 tag' : tags.length + ' tags'}, foreach: tags"><!-- ko if: $index() > 0 -->, <!-- /ko --><a class=tag data-bind="text: $data, attr: {href: ($root.tagid ? '../' : '') + $data + '/', title: 'guides tagged ' + $data}"></a></span>
            <span class=views data-bind="text: views, attr: {title: 'viewed ' + views + ' times'}"></span>
            <span class=rating data-bind="attr: {'data-stars': Math.round(rating*2)/2, title: 'rated ' + rating + ' stars by ' + (votes == 0 ? 'nobody' : (votes == 1 ? '1 person' : votes + ' people'))}"></span>
            <time class=posted data-bind="html: updated.display, attr: {datetime: updated.datetime, title: 'posted ' + (updated.datetime == posted.datetime ? updated.title : updated.title + ' (originally ' + posted.title + ')')}"></time>
            <span class=author title="written by misterhaan"><a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a></span>
          </p>
        </header>
        <div class=summary data-bind="html: summary">
        </div>
        <footer><p class=readmore>
          <a class=page1 data-bind="attr: {href: url + '/1'}">start with page 1</a>
          <a class=allpages data-bind="attr: {href: url + '/all'}">read all pages together</a>
        </p></footer>
      </article>

      <!-- /ko -->

      <p class=loading data-bind="visible: loadingGuides">loading more guides . . .</p>
      <p class=more data-bind="visible: hasMoreGuides"><a href=#nextpage data-bind="click: LoadGuides">load more guides</a></p>
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
        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/new" class=new>start a new guide</a>
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
?>
