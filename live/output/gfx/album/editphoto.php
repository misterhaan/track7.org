<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  define('MAXWIDTH', 570);
  define('PREVIEW', 150);

  if(!$user->GodMode) {
    header('HTTP/1.0 403 Forbidden');
    @include $_SERVER['DOCUMENT_ROOT'] . '/403.php';
    die;
  }

  if(strlen($_GET['id'])) {
    $photo = 'select id, caption, description, tags from photos where id=\'' . addslashes($_GET['id']) . '\'';
    if(false === $photo = $db->GetRecord($photo, 'error looking up photo information', 'photo not found'))
      unset($photo);
  }
  require_once 'auForm.php';
  require_once 'auText.php';
  $photoedit = new auForm('photoedit', '?id=' . $_GET['id']);
  $photoedit->AddField('id', 'id', 'enter an id for this photo (only filename characters allowed)', true, $photo->id, _AU_FORM_FIELD_NORMAL, 10, 30);
  if($photo)
    $photoedit->AddField('photo', 'photo', 'choose a jpeg image to replace this photo with', false, '', _AU_FORM_FIELD_FILE);
  else
    $photoedit->AddField('photo', 'photo', 'choose a jpeg image to upload', true, '', _AU_FORM_FIELD_FILE);
  $photoedit->AddField('caption', 'caption', 'enter a caption for this photo', false, $photo->caption, _AU_FORM_FIELD_NORMAL, 15, 30);
  $photoedit->AddField('desc', 'description', 'enter a description of this photo', false, auText::HTML2BB($photo->description), _AU_FORM_FIELD_BBCODE);
  $photoedit->AddField('tags', 'tags', 'enter tags for this photo, separated by commas', false, $photo->tags, _AU_FORM_FIELD_NORMAL, 20, 255);
  if($photo)
    $photoedit->AddButtons(array('edit', 'delete'), array('save changes to this photo', 'delete this photo'));
  else
    $photoedit->AddButtons('add', 'add this photo to the album');
  if($photoedit->Submitted() == 'delete') {
    $del = 'delete from photos where id=\'' . addslashes($photo->id) . '\'';
    if($db->Remove($del, 'error removing photo')) {
      if($photo->tags) {
        $tags = explode(',', $photo->tags);
        $update = 'update taginfo set count=count-1 where type=\'photos\' and (name=\'' . implode('\' or name=\'', $tags) . '\')';
        $db->Change($update);
      }
      header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/');
      die;
    }
  } elseif($photoedit->CheckInput(true)) {
    require_once 'auFile.php';
    $id = auFile::NiceName($_POST['id']);
    if($id != $_GET['id']) {
      $chk = 'select id from photos where id=\'' . addslashes($id) . '\'';
      $chk = $db->Get($chk, 'error checking if photo id is already in use');
      if(!$chk)
        $error = true;
      elseif($chk->NumRecords()) {
        $page->Error('photo id already in use &mdash; please choose a different id');
        $error = true;
      }
    }
    if(!$error) {
      $dirname = _ROOT . dirname($_SERVER['PHP_SELF']) . '/photos/';
      $upload = auFile::SaveUploadImage('photo', $dirname, _AU_FILE_IMAGE_JPEG, $id . '.jpeg', MAXWIDTH, MAXWIDTH, true);
      if(!$upload['found'] && $photoedit->Submitted() == 'add')
        $page->Error('please choose a photo to upload');
      elseif($upload['found'] && !$upload['saved'])
        $page->Error('error saving uploaded photo:&nbsp; ' . $upload['message']);
      else {
        if($photoedit->Submitted() == 'edit' && $id != $_GET['id'])
          if($upload['saved']) {
            @unlink($dirname . $_GET['id'] . '.jpg');
            @unlink($dirname . $_GET['id'] . '.jpeg');
          } else {
            @rename($dirname . $_GET['id'] . '.jpg', $dirname . $id . '.jpg');
            @rename($dirname . $_GET['id'] . '.jpeg', $dirname . $id . '.jpeg');
          }
        if($upload['saved']) {
          $photo = imagecreatefromjpeg($upload['path'] . $upload['file']);
          if(!$photo) {
            $page->Error('unable to read photo for thumbnail generation');
            $error = true;
          } else {
            if(!@rename($upload['path'] . $upload['file'], $dirname . $id . '.jpeg')) {
              $page->Error('unable to rename uploaded photo');
              $error = true;
            } else {
              $w = $h = PREVIEW;
              if($upload['height'] > $upload['width'])
                $w = round(PREVIEW * $upload['width'] / $upload['height']);
              else
                $h = round(PREVIEW * $upload['height'] / $upload['width']);
              $thumb = imagecreatetruecolor($w, $h);
              if(!imagecopyresampled($thumb, $photo, 0, 0, 0, 0, $w, $h, $upload['width'], $upload['height'])) {
                $page->Error('unable to resize uploaded photo to thumbnail size');
                $error = true;
              } elseif(!@imagejpeg($thumb, $dirname . $id . '.jpg')) {
                $page->Error('unable to save thumbnail image');
                $error = true;
              }
            }
          }
        }
        if(!$error) {
          if($photoedit->Submitted() == 'edit') {
            $update = 'update photos set id=\'' . addslashes($id) . '\', caption=\'' . addslashes(htmlentities($_POST['caption'])) . '\', description=\'' . addslashes(auText::BB2HTML($_POST['desc'])) . '\', tags=\'' . addslashes(htmlentities($_POST['tags'])) . '\' where id=\'' . $photo->id . '\'';
            if(false === $db->Change($update, 'error updating photo information'))
              $error = true;
            elseif($_POST['tags'] != $photo->tags) {
              $newtags = explode(',', $_POST['tags']);
              $oldtags = explode(',', $photo->tags);
              foreach($oldtags as $tag)
                if(in_array($tag, $newtags)) {
                  unset($oldtags[array_search($tag, $oldtags)]);
                  unset($newtags[array_search($tag, $newtags)]);
                }
              if(is_array($oldtags) && count($oldtags)) {
                $update = 'update taginfo set count=count-1 where type=\'photos\' and (name=\'' . implode('\' or name=\'', $oldtags) . '\')';
                $db->Put($update, 'error derceasing tag counts');
              }
              if(is_array($newtags) && count($newtags)) {
                $ins = 'insert into taginfo (type, name, count) values (\'photos\', \'' . implode('\', 1), (\'photos\', \'', $newtags) . '\', 1) on duplicate key update count=count+1';
                $db->Put($ins, 'error updating tag information');
              }
            }
          } else {
            $ins = 'insert into photos (id, caption, description, tags, added) values (\'' . addslashes($id) . '\', \'' . addslashes(htmlentities($_POST['caption'])) . '\', \'' . addslashes(auText::BB2HTML($_POST['desc'])) . '\', \'' . addslashes(htmlentities($_POST['tags'])) . '\', \'' . time() . '\')';
            if(false === $db->Put($ins, 'error adding photo information'))
              $error = true;
            else {
              $tags = explode(',', $_POST['tags']);
              $ins = 'insert into taginfo (type, name, count) values (\'photos\', \'' . implode('\', 1), (\'photos\', \'', $tags) . '\', 1) on duplicate key update count=count+1';
              $db->Put($ins, 'error updating tag information');
            }
          }
          if(!$error) {
            header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/photo/' . $id);
            die;
          }
        }
      }
    }
  }
  if($photo)
    $page->Start('edit photo - photo album', 'edit photo');
  else
    $page->Start('add photo - photo album', 'add photo');
  $photoedit->WriteHTML(true);
  if($photo)
    echo '      <img id="photo" src="' . dirname($_SERVER['PHP_SELF']) . '/photos/' . $photo->id . '.jpeg" />' . "\n";
  $page->End();
?>
