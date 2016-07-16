<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  // handle old tag= format for tags
  if(isset($_GET['name']) && substr($_GET['name'], 0, 4) == 'tag=') {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . substr($_GET['name'], 4) . '/');
    die;
  }

  $tag = false;
  if(isset($_GET['tag']))
    if($tag = $db->query('select name from blog_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
      if($tag = $tag->fetch_object())
        $tag = $tag->name;
    else {  // tag not found, so try getting to the entry without the tag
      header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . $_GET['name']);
      die;
    }

  $entry = false;

  if(isset($_GET['name']) && $entry = $db->query('select id, url, title, status, posted, content from blog_entries where url=\'' . $db->escape_string($_GET['name']) . '\' limit 1'))
    $entry = $entry->fetch_object();
  if(!$entry || $entry->status != 'published' && !$user->IsAdmin()) {
    header('HTTP/1.0 404 Not Found');
    $html = new t7html([]);
    $html->Open($tag ? 'entry not found - ' . $tag . ' - blog' : 'entry not found - blog');
?>
      <h1>404 blog entry not found</h1>

      <p>
        sorry, we don’t seem to have a blog entry by that name.  try the list of
        <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all blog entries</a>.
      </p>
<?php
    $html->Close();
    die;
  }

  $html = new t7html(['ko' => true]);
  $html->Open(htmlspecialchars($entry->title) . ($tag ? ' - ' . $tag . ' - blog' : ' - blog'));
?>
      <h1><?php echo htmlspecialchars($entry->title); ?></h1>
<?php
  if($tags = $db->query('select t.name from blog_entrytags as et left join blog_tags as t on t.id=et.tag where et.entry=\'' . $entry->id . '\'')) {
    $entry->tags = [];
    while($t = $tags->fetch_object())
      $entry->tags[] = '<a href="' . ($tag ? '../' : '') . $t->name . '/" title="entries tagged ' . $t->name . '">' . $t->name . '</a>';
  }
?>
      <p class=postmeta>
        posted by <a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a>
<?php
  if(count($entry->tags)) {
?>
        in <?php echo join(', ', $entry->tags); ?>
<?php
  }
  if($entry->posted) {
?>
        on <time datetime="<?php echo date('c', $entry->posted); ?>" title="<?php echo t7format::LocalDate('g:i a \o\n l F jS Y', $entry->posted); ?>"><?php echo strtolower(t7format::LocalDate('M j, Y', $entry->posted)); ?></time>
<?php
  }
?>
      </p>
<?php
  if($user->IsAdmin()) {
?>
      <nav class=actions data-id="<?php echo $entry->id; ?>">
        <a class=edit href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php?id=<?php echo $entry->id; ?>">edit this entry</a>
<?php
    if($entry->status == 'draft') {
?>
        <a id=publishentry class=publish href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php?ajax=publish">publish this entry</a>
        <a id=delentry class=del href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php?ajax=delete">delete this entry</a>
<?php
    }
?>
      </nav>
<?php
  }
  echo $entry->content;
  // TODO:  show previous / next entry (tag or all), link back to list

  if($entry->status == 'published')
    $html->ShowComments('entry', 'blog', $entry->id);
  $html->Close();
?>
