<?php
  define('MAX_PHOTO_SIZE', 800);
  define('MAX_THUMB_SIZE', 150);
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(!$user->IsAdmin()) {
    if(isset($_GET['ajax'])) {
      $ajax = new t7ajax();
      $ajax->Fail('you don’t have the rights to do that.  you might need to log in again.');
      $ajax->Send();
      die;
    }
    header('HTTP/1.0 404 Not Found');
    $html = new t7html([]);
    $html->Open('photo not found - blog');
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

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'get':
        if(isset($_GET['id']) && $_GET['id'])
          if($photo = $db->query('select id, caption, url, youtube, coalesce(nullif(storymd,\'\'),story) as storymd, taken, year from photos where id=\'' . +$_GET['id'] . '\''))
            if($photo = $photo->fetch_object()) {
              if($photo->taken)
                $photo->taken = t7format::LocalDate('Y-m-d g:i:s a', $photo->taken);
              $photo->tags = [];
              if($tags = $db->query('select t.name from photos_taglinks as tl left join photos_tags as t on t.id=tl.tag where tl.photo=\'' . +$photo->id . '\''))
                while($tag = $tags->fetch_object())
                  $photo->tags[] = $tag->name;
              $ajax->Data = $photo;
            } else
              $ajax->Fail('cannot find photo.');
          else
            $ajax->Fail('error looking up photo details for editing.');
        else
          $ajax->Fail('get requires a url.');
        break;
      case 'save':
        $id = isset($_POST['id']) ? +$_POST['id'] : false;
        if($id || $_FILES['photo']['size'])
          if($_POST['caption']) {
            if(!$_POST['url'])
              $_POST['url'] = str_replace(' ', '-', $_POST['caption']);
            if($unique = $db->query('select url from photos where url=\'' . $db->escape_string($_POST['url']) . '\' and id!=\'' . +$id . '\' limit 1'))
              if($unique->num_rows < 1) {
                if($_FILES['photo']['size']) {
                  $size = getimagesize($_FILES['photo']['tmp_name']);
                  $image = imagecreatefromjpeg($_FILES['photo']['tmp_name']);
                  $exif = exif_read_data($_FILES['photo']['tmp_name'], 'EXIF', true);
                  if(isset($exif['IFD0']['Orientation']))
                    switch($exif['IFD0']['Orientation']) {
                      case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                      case 6:
                        $image = imagerotate($image, -90, 0);
                        $tmp = $size[0];
                        $size[0] = $size[1];
                        $size[1] = $tmp;
                        break;
                      case 8:
                        $image = imagerotate($image, 90, 0);
                        $tmp = $size[0];
                        $size[0] = $size[1];
                        $size[1] = $tmp;
                        break;
                    }
                  $aspect = $size[0] / $size[1];
                  if(!$_POST['youtube'])
                    if($size[0] > MAX_PHOTO_SIZE || $size[1] > MAX_PHOTO_SIZE) {
                      if($aspect > 1) {
                        $width = MAX_PHOTO_SIZE;
                        $height = round(MAX_PHOTO_SIZE / $aspect);
                      } else {
                        $height = MAX_PHOTO_SIZE;
                        $width = round(MAX_PHOTO_SIZE * $aspect);
                      }
                      $fullsize = imagecreatetruecolor($width, $height);
                      imagecopyresampled($fullsize, $image, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
                      imagejpeg($fullsize, dirname($_SERVER['SCRIPT_FILENAME']) . '/photos/' . $_POST['url'] . '.jpeg');
                      imagedestroy($fullsize);
                      unlink($_FILES['photo']['tmp_name']);
                    } else
                      move_uploaded_file($_FILES['photo']['tmp_name'], dirname($_SERVER['SCRIPT_FILENAME']) . '/photos/' . $_POST['url'] . '.jpeg');
                  if($aspect > 1) {
                    $w = MAX_THUMB_SIZE;
                    $h = round(MAX_THUMB_SIZE / $aspect);
                  } else {
                    $h = MAX_THUMB_SIZE;
                    $w = round(MAX_THUMB_SIZE * $aspect);
                  }
                  $thumb = imagecreatetruecolor($w, $h);
                  imagecopyresampled($thumb, $image, 0, 0, 0, 0, $w, $h, $size[0], $size[1]);
                  imagejpeg($thumb, dirname($_SERVER['SCRIPT_FILENAME']) . '/photos/' . $_POST['url'] . '.jpg');
                  imagedestroy($thumb);
                  imagedestroy($image);
                  if(!$_POST['taken'] && isset($exif['EXIF']) && isset($exif['EXIF']['DateTimeOriginal']))
                    $_POST['taken'] = $exif['EXIF']['DateTimeOriginal'];
                }
                $_POST['taken'] = $_POST['taken'] ? t7format::LocalStrtotime($_POST['taken']) : '';
                $_POST['year'] = $_POST['year'] ? $_POST['year'] : $_POST['taken'] ? t7format::LocalDate('Y', $_POST['taken']) : '';
                $q = 'photos set caption=\'' . $db->escape_string($_POST['caption']) . '\', url=\'' . $db->escape_string(trim($_POST['url'])) . '\', youtube=\'' . ($_POST['youtube'] ? $db->escape_string(trim($_POST['youtube'])) : '') . '\', storymd=\'' . $db->escape_string(trim($_POST['storymd'])) . '\', story=\'' . $db->escape_string(t7format::Markdown(trim($_POST['storymd']))) . '\', taken=' . ($_POST['taken'] ? '\'' . +$_POST['taken'] . '\'' : 'null') . ', year=' . +$_POST['year'];
                $q = $id ? 'update ' . $q . ' where id=\'' . +$id . '\' limit 1' : 'insert into ' . $q . ', posted=\'' . +time() . '\'';
                if($db->real_query($q)) {
                  if(!$id) {
                    $id = $db->insert_id;
                    t7send::Tweet(($_POST['youtube'] ? 'new video: ' : 'new photo: ') . $_POST['caption'], 'http://' . $_SERVER['HTTP_HOST'] . '/album/' . $_POST['url']);
                  }
                  $_POST['taglist'] = explode(',', $_POST['taglist']);
                  $_POST['originalTaglist'] = explode(',', $_POST['originalTaglist']);
                  $addtags = array_diff($_POST['taglist'], $_POST['originalTaglist']);
                  if(count($addtags)) {
                    $qat = $db->prepare('insert into photos_tags (name) values (?) on duplicate key update id=id');
                    $qat->bind_param('s', $name);
                    $qlt = $db->prepare('insert into photos_taglinks set photo=\'' . +$id . '\', tag=(select id from photos_tags where name=? limit 1)');
                    $qlt->bind_param('s', $name);
                    foreach($addtags as $name) {
                      if(!$qat->execute())
                        $ajax->Fail('error adding tag:  ' . $qat->error);
                      if(!$qlt->execute())
                        $ajax->Fail('error linking tag:  ' . $qlt->error);
                    }
                    $qat->close();
                    $qlt->close();
                  }
                  $deltags = array_diff($_POST['originalTaglist'], $_POST['taglist']);
                  if(count($deltags))
                    $db->real_query('delete from photos_taglinks where photo=\'' . +$id . '\' and tag in (select id from photos_tags where name in (\'' . implode('\', \'', $deltags) . '\'))');
                  $tags = array_merge($addtags, $deltags);
                  if(count($tags))
                    if(!$db->real_query('update photos_tags set count=(select count(1) as count from photos_taglinks as tl left join photos as p on p.id=tl.photo where tl.tag=photos_tags.id group by tl.tag), lastused=(select max(p.posted) as lastused from photos_taglinks as tl left join photos as p on p.id=tl.photo where tl.tag=photos_tags.id group by tl.tag) where name in (\'' . implode('\', \'', $tags) . '\')'))
                      $ajax->Fail('error updating tag stats:  ' . $db->error);
                  $ajax->Data->url = dirname($_SERVER['SCRIPT_NAME']) . '/' . $_POST['url'];
                } else
                  $ajax->Fail('database error saving photo data.' . "\n\n" . $q);
              } else
                $ajax->Fail('url “' . $_POST['url'] . '” already in use.');
            else
              $ajax->Fail('error checking uniqueness of photo url.');
          } else
            $ajax->Fail('caption is required.');
        else
          $ajax->Fail('image file must be included with new photos.');
        break;
    }
    $ajax->Send();
    die;
  }

  $id = isset($_GET['id']) ? +$_GET['id'] : false;
  $html = new t7html(['ko' => true]);
  $html->Open(($id ? 'edit' : 'add') . ' photo - album');
?>
      <h1><?php echo $id ? 'edit' : 'add'; ?> photo</h1>
      <form id=editphoto data-bind="submit: Save">
<?php
  if($id) {
?>
        <input type=hidden id=photoid name=id value="<?php echo $id; ?>">
<?php
  }
?>
        <label>
          <span class=label>caption:</span>
          <span class=field><input name=caption maxlength=32 required data-bind="textInput: caption"></span>
        </label>
        <label>
          <span class=label>url:</span>
          <span class=field><input id=url name=url maxlength=32 pattern="[a-z0-9\-_]+" data-bind="value: url"></span>
        </label>
        <label title="youtube video id if this photo is a video (unique part of the video url)">
          <span class=label>youtube:</span>
          <span class=field><input name=youtube maxlength=32 data-bind="value: youtube"></span>
        </label>
        <label title="upload the photo, or a thumbnail for a video" data-bind="visible: !photo()">
          <span class=label>photo:</span>
          <span class=field>
            <input type=file name=photo accept="image/jpeg, image/jpg" data-bind="event: {change: CachePhoto}">
          </span>
        </label>
        <label class=multiline title="the photo, or a thumbnail for a video" data-bind="visible: photo()">
          <span class=label>photo:</span>
          <span class=field>
            <img class="photo preview" data-bind="attr: {src: photo}">
          </span>
        </label>
        <label class=multiline>
          <span class=label>story:</span>
          <span class=field><textarea name=storymd data-bind="value: storymd"></textarea></span>
        </label>
        <label>
          <span class=label>taken:</span>
          <span class=field><input name=taken data-bind="value: taken"></span>
        </label>
        <label>
          <span class=label>year:</span>
          <span class=field><input name=year pattern="[0-9]{4}" maxlength=4 data-bind="value: year"></span>
        </label>
        <label>
          <span class=label>tags:</span>
          <span class=field><input name=taglist pattern="([a-z0-9\.]+( [a-z0-9\.]+)*(,[a-z0-9\.]+( [a-z0-9\.]+)*)*)?" data-bind="value: tags"></span>
        </label>
        <button id=save>save</button>
        <p data-bind="visible: id()"><img class=photo data-bind="attr: {src: url() ? 'photos/' + url() + '.jpeg' : ''}"></p>
      </form>
<?php
  $html->Close();
?>
