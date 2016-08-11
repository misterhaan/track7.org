<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  $tag = false;
  if(isset($_GET['tag']))
    if($tag = $db->query('select id, name from photos_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
      if($tag = $tag->fetch_object())
        ;  // got a tag
      else {  // tag not found, so try getting to the entry without the tag
        header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . $_GET['name']);
        die;
      }

  $photo = false;

  if(isset($_GET['photo']) && $photo = $db->query('select id, url, youtube, caption, posted, taken, year, story from photos where url=\'' . $db->escape_string($_GET['photo']) . '\' limit 1'))
    $photo = $photo->fetch_object();
  if(!$photo) {
    header('HTTP/1.0 404 Not Found');
    $html = new t7html([]);
    $html->Open($tag ? 'photo not found - ' . $tag->name . ' - photo album' : 'photo not found - photo album');
?>
      <h1>404 photo not found</h1>

      <p>
        sorry, we don’t seem to have a photo by that name.  try picking one from
        <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">the gallery</a>.
      </p>
<?php
    $html->Close();
    die;
  }

  $prev = $next = false;
  if($tag) {
    if($prev = $db->query('select p.url, p.caption from photos_taglinks as tl left join photos as p on p.id=tl.photo where tl.tag=\'' . +$tag->id . '\' and p.posted<\'' . +$photo->posted . '\' order by p.posted desc limit 1'))
      $prev = $prev->fetch_object();
    if($next = $db->query('select p.url, p.caption from photos_taglinks as tl left join photos as p on p.id=tl.photo where tl.tag=\'' . +$tag->id . '\' and p.posted>\'' . +$photo->posted . '\' order by p.posted limit 1'))
      $next = $next->fetch_object();
  } else {
    if($prev = $db->query('select url, caption from photos where posted<\'' . +$photo->posted . '\' order by posted desc limit 1'))
      $prev = $prev->fetch_object();
    if($next = $db->query('select url, caption from photos where posted>\'' . +$photo->posted . '\' order by posted limit 1'))
      $next = $next->fetch_object();
  }
  $photo->posted = t7format::TimeTag('smart', $photo->posted, 'g:i a \o\n l F jS Y');

  $html = new t7html(['ko' => true]);
  $html->Open(htmlspecialchars($photo->caption) . ($tag ? ' - ' . $tag->name . ' - photos' : ' - photos'));
?>
      <h1><?php echo htmlspecialchars($photo->caption); ?></h1>
<?php
  if($user->IsAdmin()) {
?>
      <nav class=actions><a class=edit href="<?php echo dirname($_SERVER['SCRIPT_NAME']) . '/edit.php?id=' . $photo->id; ?>">edit this photo</a></nav>
<?php
  }
  TagPrevNext($prev, $tag, $next);
  if($photo->youtube) {
?>
      <p><iframe class=photo width="640" height="385" src="https://www.youtube.com/embed/<?php echo $photo->youtube; ?>" allowfullscreen></iframe></p>
<?php
  } else {
?>
      <p><img class=photo src="<?php echo dirname($_SERVER['SCRIPT_NAME']) . '/photos/' . $photo->url; ?>.jpeg"></p>
<?php
  }
?>
      <p class=photometa>
<?php
  if($photo->taken) {
    $photo->taken = t7format::TimeTag('smart', $photo->taken, 'g:i a \o\n l F jS Y');
?>
        <time class=taken datetime="<?php echo $photo->taken->datetime; ?>" title="taken <?php echo $photo->taken->title; ?>"><?php echo $photo->taken->display; ?></time>
<?php
  } elseif($photo->year) {
?>
        <span class=taken title="taken in <?php echo $photo->year; ?>"><?php echo $photo->year; ?></span>
<?php
  }
?>
        <time class=posted datetime="<?php echo $photo->posted->datetime; ?>" title="posted <?php echo $photo->posted->title; ?>"><?php echo $photo->posted->display; ?></time>
<?php
  if($tags = $db->query('select t.name from photos_taglinks as tl left join photos_tags as t on t.id=tl.tag where tl.photo=\'' . +$photo->id . '\''))
    if($tagcount = $tags->num_rows) {
?>
        <span class=tags>
<?php
      while($t = $tags->fetch_object()) {
?>
          <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']) . '/' . $t->name; ?>/"><?php echo $t->name; ?></a><?php if(--$tagcount) echo ','; ?>
<?php
      }
?>
        </span>
<?php
    }
?>
      </p>
<?php
  echo $photo->story;
  TagPrevNext($prev, $tag, $next);
  $html->ShowComments('photo', 'photos', $photo->id);
  $html->Close();

  function TagPrevNext($prev, $tag, $next) {
?>
      <nav class=tagprevnext>
<?php
    if($next) {
?>
        <a class=prev title="see the photo posted after this<?php if($tag) echo ' in ' . $tag->name; ?>" href="<?php echo dirname($_SERVER['SCRIPT_NAME']) . ($tag ? '/' . $tag->name . '/' : '/') . $next->url; ?>"><?php echo htmlspecialchars($next->caption); ?></a>
<?php
    }
    if($tag) {
?>
        <a class=tag title="see all photos posted in <?php echo $tag->name; ?>" href="<?php echo dirname($_SERVER['SCRIPT_NAME']) . '/' . $tag->name . '/'; ?>"><?php echo $tag->name; ?></a>
<?php
    } else {
?>
        <a class=gallery title="see all photos" href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">everything</a>
<?php
    }
    if($prev) {
?>
        <a class=next title="see the photo posted before this<?php if($tag) echo ' in ' . $tag->name; ?>" href="<?php echo dirname($_SERVER['SCRIPT_NAME']) . ($tag ? '/' . $tag->name . '/' : '/') . $prev->url; ?>"><?php echo htmlspecialchars($prev->caption); ?></a>
<?php
    }
?>
      </nav>
<?php
  }
?>
