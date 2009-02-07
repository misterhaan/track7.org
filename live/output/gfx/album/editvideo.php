<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  define('PREVIEW', 150);

  if(!$user->GodMode) {
    header('HTTP/1.0 403 Forbidden');
    @include $_SERVER['DOCUMENT_ROOT'] . '/403.php';
    die;
  }

  if(strlen($_GET['id'])) {
    $photo = 'select id, youtubeid, caption, description, taken, tags from photos where id=\'' . addslashes($_GET['id']) . '\'';
    if(false === $photo = $db->GetRecord($photo, 'error looking up video information', 'video not found'))
      unset($photo);
  }
  $videoedit = new auForm('videoedit', '?id=' . $_GET['id']);
  $videoedit->Add(new auFormString('id', 'id', 'enter an id for this video (only filename characters allowed)', true, $photo->id, 15, 30));
  $videoedit->Add(new auFormString('youtubeid', 'youtube id', 'enter the id that appears after v= at the end of the youtube url', true, $photo->youtubeid, 10, 16));
  if($photo)
    $videoedit->Add(new auFormFile('photo', 'thumbnail', 'choose a jpeg image to replace this thumbnail with', false));
  else
    $videoedit->Add(new auFormFile('photo', 'thumbnail', 'choose a jpeg image to upload as a thumbnail', true));
  $videoedit->Add(new auFormFile('video', 'video', 'choose an avi video file to upload, if 7 meg or less', false));
  $videoedit->Add(new auFormString('caption', 'caption', 'enter a caption for this video', false, $photo->caption, 15, 30));
  $videoedit->Add(new auFormMultiString('desc', 'description', 'enter a description of this video', false, auText::HTML2BB($photo->description), true));
  $videoedit->Add(new auFormString('taken', 'taken', 'enter the date (or year) this video was taken', false, $photo->taken < 2010 ? $photo->taken : date('Y-m-d g:i:s a', $photo->taken), 20));
  $videoedit->Add(new auFormString('tags', 'tags', 'enter tags for this video, separated by commas', false, $photo->tags, 20, 255));
  if($photo)
    $videoedit->Add(new auFormButtons(array('edit', 'delete'), array('save changes to this video', 'delete this video')));
  else
    $videoedit->Add(new auFormButtons('add', 'add this video to the album'));
  if($videoedit->Submitted() == 'delete') {
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
  } elseif($videoedit->CheckInput(true)) {
    $error = false;
    $id = auFile::NiceName($_POST['id']);
    if($id != $_GET['id']) {
      $chk = 'select id from photos where id=\'' . addslashes($id) . '\'';
      $chk = $db->Get($chk, 'error checking if photo id is already in use');
      if(!$chk)
        $error = true;
      elseif($chk->NumRecords()) {
        $page->Error('photo id already in use — please choose a different id');
        $error = true;
      }
    }
    if(!$error) {
      if(is_numeric($_POST['taken']) && +$_POST['taken'] < 2010)
        $taken = +$_POST['taken'];
      else
        $taken = $user->tzstrtotime($_POST['taken']);
      $dirname = _ROOT . dirname($_SERVER['PHP_SELF']) . '/photos/';
      $upload = auFile::SaveUploadImage('photo', $dirname, _AU_FILE_IMAGE_JPEG, $id . '.jpg', PREVIEW, PREVIEW, true);
      if(!$upload['found'] && $videoedit->Submitted() == 'add')
        $page->Error('please choose a thumbnail to upload');
      elseif($upload['found'] && !$upload['saved'])
        $page->Error('error saving uploaded thumbnail:&nbsp; ' . $upload['message']);
      else {
        if($videoedit->Submitted() == 'edit' && $id != $_GET['id'])
          if($upload['saved']) {
            @unlink($dirname . $_GET['id'] . '.jpg');
            @unlink($dirname . $_GET['id'] . '.avi');
          } else {
            @rename($dirname . $_GET['id'] . '.jpg', $dirname . $id . '.jpg');
            @rename($dirname . $_GET['id'] . '.avi', $dirname . $id . '.avi');
          }
        auFile::SaveUpload('video', $dirname, 'video/x-msvideo', $id . '.avi');
        if($videoedit->Submitted() == 'edit') {
          $update = 'update photos set id=\'' . addslashes($id) . '\', youtubeid=\'' . addslashes(htmlspecialchars($_POST['youtubeid'])) . '\', caption=\'' . addslashes(htmlspecialchars($_POST['caption'], ENT_COMPAT, _CHARSET)) . '\', description=\'' . addslashes(auText::BB2HTML($_POST['desc'])) . '\', taken=\'' . $taken . '\', tags=\'' . addslashes(htmlspecialchars($_POST['tags'], ENT_COMPAT, _CHARSET)) . '\' where id=\'' . $photo->id . '\'';
          if(false === $db->Change($update, 'error updating video information'))
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
          $ins = 'insert into photos (id, youtubeid, caption, description, taken, tags, added) values (\'' . addslashes($id) . '\', \'' . addslashes(htmlspecialchars($_POST['youtubeid'])) . '\', \'' . addslashes(htmlentities($_POST['caption'], ENT_COMPAT, _CHARSET)) . '\', \'' . addslashes(auText::BB2HTML($_POST['desc'])) . '\', \'' . $taken . '\', \'' . addslashes(htmlentities($_POST['tags'], ENT_COMPAT, _CHARSET)) . '\', \'' . time() . '\')';
          if(false === $db->Put($ins, 'error adding video information'))
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
  if($photo)
    $page->Start('edit video - photo album', 'edit video');
  else
    $page->Start('add video - photo album', 'add video');
  $videoedit->WriteHTML(true);
  $page->End();
?>
