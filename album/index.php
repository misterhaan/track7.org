<?php
  define('NUM_PHOTOS', 24);
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'photos':
        if(isset($_GET['tagid']) && +$_GET['tagid']) {
          $photosq = 'select p.url, p.posted, p.caption, count(c.photo) as comments from photos_taglinks as t left join photos as p on p.id=t.photo left join photos_comments as c on c.photo=p.id where t.tag=\'' . +$_GET['tagid'] . '\'';
          if(isset($_GET['before']) && +$_GET['before'])
            $photosq .= ' and p.posted<\'' . +$_GET['before'] . '\'';
          $photosq .= ' group by p.id order by p.posted desc limit ' . NUM_PHOTOS;
        } else {
          $photosq = 'select p.url, p.posted, p.caption, count(c.photo) as comments from photos as p left join photos_comments as c on c.photo=p.id';
          if(isset($_GET['before']) && +$_GET['before'])
            $photosq .= ' where p.posted<\'' . +$_GET['before'] . '\'';
            $photosq .= ' group by p.id order by p.posted desc limit ' . NUM_PHOTOS;
        }
        $ajax->Data->photosq = $photosq;
        $ajax->Data->photos = [];
        if($photos = $db->query($photosq))
          while($photo = $photos->fetch_object()) {
            $posted = t7format::TimeTag('M j, Y', $photo->posted, 'g:i a \o\n l F jS Y');
            $posted->timestamp = $photo->posted;
            $photo->posted = $posted;
            $ajax->Data->photos[] = $photo;
          }
        $ajax->Data->hasMore = false;
        if($more = $db->query($photosq . ', 1'))
          $ajax->Data->hasMore = $more->num_rows > 0;
        break;
      default:
        $ajax->Fail('unknown function name.  supported function names are: photos.');
        break;
    }
    $ajax->Send();
    die;
  }

  $html = false;

  $tag = false;
  if(isset($_GET['tag']) && $tag = $db->query('select id, name, description from photos_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
    $tag = $tag->fetch_object();
  if($tag) {
    $html = new t7html(['ko' => true, 'bodytype' => 'gallery', 'rss' => ['title' => $tag->name . ' photos', 'url' => dirname($_SERVER['PHP_SELF']) . '/feed.rss?tags=' . $tag->name]]);
    $html->Open($tag->name . ' - photo album');
?>
      <h1>
        photo album — <?php echo $tag->name; ?>
        <a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss?tags=<?php echo $tag->name; ?>" title="rss feed of <?php echo $tag->name; ?> photos"><img alt=feed src="/images/feed.png"></a>
      </h1>
<?php
    ShowActions($tag->id);
?>
      <div id=taginfo data-tagid=<?php echo $tag->id; ?>>
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
        <div class=editable><?php echo $tag->description; ?></div>
      </div>
      <p>go back to <a href="/album/">all photos</a>.</p>
<?php
  } else {
    $html = new t7html(['ko' => true, 'bodytype' => 'gallery', 'rss' => ['title' => 'photo album', 'url' => dirname($_SERVER['PHP_SELF']) . '/feed.rss']]);
    $html->Open('photo album');
?>
      <h1>
        photo album
        <a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of all photos"><img alt=feed src="/images/feed.png"></a>
      </h1>

      <nav class=tagcloud data-bind="visible: tags().length">
        <header>tags</header>
        <!-- ko foreach: tags -->
        <a data-bind="text: name, attr: { href: name + '/', title: 'photos tagged ' + name, 'data-count': count }"></a>
        <!-- /ko -->
      </nav>

<?php
    ShowActions();
  }
?>
      <ul class=errors data-bind="visible: errors().length, foreach: errors">
        <li data-bind="text: $data"></li>
      </ul>

      <p data-bind="visible: !photos().length && !loadingPhotos()">
        this album is empty!
      </p>

      <ol id=photogallery class=gallery data-bind="foreach: photos">
        <li>
          <a class=photothumb data-bind="attr: {href: url}">
            <img data-bind="attr: {src: '/album/photos/' + url + '.jpg'}">
            <span class=caption data-bind="text: caption"></span>
          </a>
        </li>
      </ol>

      <p class=loading data-bind="visible: loadingPhotos">loading more photos . . .</p>
      <p class="more calltoaction" data-bind="visible: hasMorePhotos"><a class="action get" href=#nextpage data-bind="click: LoadPhotos">load more photos</a></p>
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
        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>add a photo or video</a>
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

  die;  // TODO:  remove old code
  require_once  dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $url = dirname($_SERVER['PHP_SELF']) . '/';
  $phurl = $url;
  if($user->GodMode) {
    $page->Info('<a href="' . dirname($_SERVER['PHP_SELF']) . '/editphoto.php">add a new photo</a>');
    $page->Info('<a href="' . dirname($_SERVER['PHP_SELF']) . '/editvideo.php">add a new video</a>');
  }
  if(isset($_GET['tag'])) {
    $tag = htmlspecialchars($_GET['tag'], ENT_COMPAT, _CHARSET);
    $phurl .= 'tag=' . $tag . '/';
    $page->Start($tag . ' - photo album', 'photo album [' . $tag . ']<a class="feed" href="/feeds/photos.rss?tags=' . $tag . '" title="rss feed of album photos tagged with ' . $tag . '"><img src="/style/feed.png" alt="feed" /></a>');
    $photos = addslashes($_GET['tag']);
    $photos = ' where tags=\'' . $photos . '\' or tags like \'' . $photos . ',%\' or tags like \'%,' . $photos . '\' or tags like \'%,' . $photos . ',%\'';
    $page->heading($tag);
    $page->ShowTagDescription('photos', $tag);
    if($user->GodMode)
      $page->Info('<a href="/tools/taginfo.php?type=photos&amp;name=' . $tag . '">add/edit tag description</a>');
  } else {
    $tag = false;
    $page->Start('photo album', 'photo album<a class="feed" href="/feeds/photos.rss" title="rss feed of album photos"><img src="/style/feed.png" alt="feed" /></a>');
    $page->TagCloud('photos', $url . 'tag=', 4, 7, 17, 40, 3);
  }
  switch($_GET['sort']) {
    case 'oldest':
      $sortadj = 'oldest';
      $sortsql = 'added';
      $phurl .= 'sort=oldest/';
      break;
    default:
      $sortadj = 'newest';
      $sortsql = 'added desc';
      break;
  }
  $photos = 'select id, caption, added from photos' . $photos . ' order by ' . $sortsql;
  if($photos = $db->GetSplit($photos, 24, 0, '', '', 'error looking up photos', isset($_GET['tag']) ? 'no photos tagged with ' . $tag : 'no photos found')) {
?>
      <p>showing <?=$db->split_count; ?> photos (<?=$_GET['show']; ?> at a time)</p>
      <?=getSortLinks($db, $url, $tag); ?>


      <ul id="photos">
        <li>
<?
    while($photo = $photos->NextRecord()) {
      if($started) {
?>
        </li><li>
<?
      }
?>
          <a href="<?=$phurl; ?>photo=<?=$photo->id; ?>">
            <span class="photopreview">
              <img src="<?=$url; ?>photos/<?=$photo->id; ?>.jpg" alt="" <?=auFile::ImageSizeCSS(_ROOT . '/output/gfx/album/photos/' . $photo->id . '.jpg'); ?>/>
            </span>
            <span class="caption"><?=$photo->caption; ?></span>
          </a>
<?
      $started = true;
    }
?>
        </li>
      </ul>
<?
    $q = '';
    if($_GET['tag']) {
      $url .= 'tag=' . $_GET['tag'];
      $q = '/';
    }
    if($_GET['sort']) {
      $url .= $q . 'sort=' . $_GET['sort'];
      $q = '/';
    }
    $page->SplitLinks(htmlspecialchars($q), $url);
  }
  $page->End();

  /**
   * gets HTML with linked options for how to sort thumbnails.
   */
  function getSortLinks(&$db, $url, $tag) {
    if($tag)
      $url .= 'tag=' . $tag;
    $ret = '<div id="sortoptions">sort by:&nbsp; ';
    if(!$_GET['sort'])
      $ret .= 'newest';
    else
      $ret .= '<a href="' . $url . '" title="show newest photos first">newest</a>';
    if($_GET['sort'] == 'oldest')
      $ret .= ' | oldest';
    else {
      if(substr($url, -1) != '/')
        $url .= '/';
      $ret .= ' | <a href="' . $url . 'sort=oldest" title="show oldest photos first">oldest</a>';
    }
    return $ret . '</div>';
  }
?>
