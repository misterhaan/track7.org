<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  $tag = false;
  if(isset($_GET['tag']))
    if($tag = $db->query('select name from guide_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
      if($tag = $tag->fetch_object())
        $tag = $tag->name;
    else {  // tag not found, so try getting to the guide without the tag
      header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . $_GET['url'] . '/' . $_GET['page']);
      die;
    }

  $guide = false;

  if(isset($_GET['url']) && $guide = $db->query('select g.id, g.url, g.title, g.status, g.posted, g.updated, g.summary, g.level, g.rating, g.votes, g.views, g.author, v.vote from guides as g left join guide_votes as v on g.id=v.guide and v.' . ($user->IsLoggedIn() ? 'voter=' . +$user->ID . ' and v.ip=0' : 'voter=0 and v.ip=inet_aton(\'' . $_SERVER['REMOTE_ADDR'] . '\')') . ' where g.url=\'' . $db->escape_string($_GET['url']) . '\' limit 1'))
    $guide = $guide->fetch_object();
  if(!$guide || $guide->status != 'published' && !$user->IsAdmin()) {
    header('HTTP/1.0 404 Not Found');
    $html = new t7html([]);
    $html->Open($tag ? 'guide not found - ' . $tag . ' - guides' : 'guide not found - guides');
?>
      <h1>404 guide not found</h1>

      <p>
        sorry, we don’t seem to have a guide by that name.  try the list of
        <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all guides</a>.
      </p>
<?php
    $html->Close();
    die;
  }
  $pages = [];
  if($pageinfo = $db->query('select id, number, heading, html from guide_pages where guide=\'' . +$guide->id . '\' order by number')) {
    while($p = $pageinfo->fetch_object())
      $pages[$p->number] = $p;
    if($_GET['page'] != 'all' && !isset($pages[$_GET['page']]))
      if($_GET['page'] != 1) {
        // invalid page, so go to page 1 (if page 1 is invalid it will get handled later)
        header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . ($tag ? $tag . '/' : '') . $_GET['url'] . '/1');
        die;
      }
  }

  $html = new t7html(['ko' => true]);
  $html->Open(htmlspecialchars($guide->title) . ($tag ? ' - ' . $tag . ' - guides' : ' - guides'));
  if($guide->status == 'published')
    $db->real_query('update guides set views=views+1 where id=' . +$guide->id);
  $tags = [];
  if($ts = $db->query('select t.name from guide_taglinks as l left join guide_tags as t on t.id=l.tag where l.guide=' . +$guide->id))
    while($t = $ts->fetch_object())
      $tags[] = '<a href="' . ($tag ? '../../' : '../') . $t->name . '/" title="guides tagged ' . $t->name . '">' . $t->name . '</a>';
?>
      <h1><?php echo htmlspecialchars($guide->title); ?></h1>
      <p class=guidemeta>
        <span class=guidelevel title="<?php echo $guide->level; ?> level"><?php echo $guide->level; ?></span>
        <span class=tags><?php echo implode(', ', $tags); ?></span>
        <span class=views title="viewed <?php echo $guide->views; ?> times"><?php echo $guide->views; ?></span>
        <span class=rating data-stars=<?php echo round($guide->rating*2)/2; ?> title="rated <?php echo $guide->rating; ?> stars by <?php echo $guide->votes == 0 ? 'nobody' : ($guide->votes == 1 ? '1 person' : $guide->votes . ' people'); ?>"></span>
        <time class=posted datetime="<?php echo gmdate('c', $guide->updated); ?>" title="posted <?php echo $guide->updated == $guide->posted ? strtolower(date('g:i a \o\n l F jS Y', $guide->updated)) : strtolower(date('g:i a \o\n l F jS Y', $guide->updated)) . ' (originally ' . strtolower(date('g:i a \o\n l F jS Y', $guide->posted)) . ')'; ?>"><?php echo t7format::SmartDate($guide->updated) ; ?></time>
        <span class=author title="written by misterhaan"><a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a></span>
      </p>
<?php
  if($user->IsAdmin() || $guide->author == $user->ID) {
?>
      <nav class=actions data-id=<?php echo $guide->id; ?>>
        <a class=edit href=edit>edit this guide</a>
<?php
    if($user->IsAdmin() && $guide->status == 'draft') {
?>
        <a class=publish href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php?ajax=publish">publish this guide</a>
        <a class=del href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php?ajax=delete">delete this guide</a>
<?php
    }
?>
      </nav>
<?php
  }
  echo $guide->summary;
  echo $guide->toc = MakeTOC($pages);
  if($_GET['page'] == 'all')
    foreach($pages as $page) {
?>
      <h2><?php echo $page->heading; ?></h2>
<?php
      echo $page->html;
    }
  elseif(isset($pages[$_GET['page']])) {
?>
      <h2><?php echo $pages[$_GET['page']]->heading; ?></h2>
<?php
    echo $pages[$_GET['page']]->html;
  } else {
?>
      <p class=error>oops, this guide doesn’t have a first page.</p>
<?php
  }
  echo $guide->toc;

  if($guide->status == 'published') {
?>
      <p>
        how was it?  <?php $html->ShowVote('guide', $guide->id, $guide->vote); ?>
      </p>
<?php
    $html->ShowComments('guide', 'guide', $guide->id);
  }
  $html->Close();
  
  function MakeTOC($pages) {
    $ret = '<ol class=toc>';
    foreach($pages as $page)
      if(+$page->number == +$_GET['page'])
        $ret .= '<li class=selected>' . $page->heading . '</li>';
      else
        $ret .= '<li><a href="' . $page->number . '">' . $page->heading . '</a></li>';
    if($_GET['page'] == 'all')
      $ret .= '<li class="selected allpages">all ' . count($pages) . ' pages</li>';
    else
      $ret .= '<li class=allpages><a href=all>all ' . count($pages) . ' pages</a></li>';
    return $ret . '</ol>';
  }
?>
