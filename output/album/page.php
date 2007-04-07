<?
  define('MAXWIDTH', 570);
  define('THUMBWIDTH', 150);
  define('THUMBHEIGHT', 100);

  // go back to the regular album page if they didn't specify a page
  if(!isset($_GET['name'])) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . '/album/');
    die;
  }
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';
  
  // force comments to off.  the page should actually have it set on for the sake of the photos themselves.
  $page->ResetFlag(_FLAG_PAGES_COMMENTS);

  // go back to the regular album page if the specified page doesn't exist
  if(!$db->Get('select 1 from albumpages where name=\'' . addslashes($_GET['name']) . '\'', '', '')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . '/album/');
    die;
  }

  if(isset($_GET['delpage']) && $user->GodMode) {
    $del = 'delete from albumpages where name=\'' . $_GET['name'] . '\'';
    if(false !== $db->Change($del, 'unable to delete page'))
      $page->Info('page deleted successfully');
    $page->Start('delete page');
    $page->End();
    die;
  }

  $page->Start($_GET['name'] . ' - photo album', $_GET['name'] . ($user->GodMode ? ' &nbsp; <a class="img" href="/output/album/' . $_GET['name'] . '/newgroup" title="add a new group to this page"><img src="/style/new.png" alt="add" /></a> <a class="img" href="/output/album/' . $_GET['name'] . '/editpage" title="edit this page"><img src="/style/edit.png" alt="edit" /></a> <a class="img" href="/output/album/' . $_GET['name'] . '/delpage" title="delete this page"><img src="/style/del.png" alt="del" /></a>' : ''), 'photo album');

  if(isset($_POST['formid']) && $user->GodMode)
    switch($_POST['formid']) {
      case 'newgroup':
        // add a new group to the page
        if(!is_numeric($_POST['sort']))
          $_POST['sort'] = 0;
        if($_POST['sort'] < 1) {
          $_POST['sort'] = 'select max(sort) from albumgroups where page=\'' . addslashes($_GET['name']) . '\'';
          $_POST['sort'] = $db->GetValue($_POST['sort'], '', '');
          $_POST['sort']++;
        } else {
          $shift = 'update albumgroups set sort=sort+1 where sort>=' . $_POST['sort'] . ' and page=\'' . $_GET['name'] . '\'';
          $db->Change($shift, 'error shifting sort order of existing photos');
        }
        $ins = 'insert into albumgroups (page, title, sort) values (\'' . addslashes($_GET['name']) . '\', \'' . addslashes($_POST['title']) . '\', ' . $_POST['sort'] . ')';
        if(false !== $db->Put($ins, 'error adding group'))
          $page->Info('group added successfully');
        break;
      case 'newphoto':
        // add a new photo to a group
        if(!is_numeric($_POST['groupid']))
          $_POST['groupid'] = 0;
        if(!is_numeric($_POST['sort']))
          $_POST['sort'] = 0;
        if($_POST['sort'] < 1) {
          $_POST['sort'] = 'select max(sort) from albumphotos where `group`=' . addslashes($_POST['groupid']);
          $_POST['sort'] = $db->GetValue($_POST['sort'], '', '');
          $_POST['sort']++;
        } else {
          $shift = 'update albumphotos set sort=sort+1 where sort>=' . $_POST['sort'] . ' and `group`=' . $_POST['groupid'];
          $db->Change($shift, 'error shifting sort order of existing photos');
        }
        // resize the photo
        if(is_uploaded_file($_FILES['photo']['tmp_name'])) {
          $img = getimagesize($_FILES['photo']['tmp_name']);
          if($img[2] == 2) {
            $upload = @imagecreatefromjpeg($_FILES['photo']['tmp_name']);
            if($upload) {
              $thumb = imagecreatetruecolor(THUMBWIDTH, THUMBHEIGHT);
              if(imagecopyresampled($thumb, $upload, 0, 0, 0, 0, THUMBWIDTH, THUMBHEIGHT, $img[0], $img[1])) {
                $thumbfile = explode('.', $_FILES['photo']['name']);
                $thumbfile[count($thumbfile) - 1] = 'jpg';
                $thumbfile = implode('.', $thumbfile);
                $thumbfile = _ROOT . '/album/' . $_GET['name'] . '/' . $thumbfile;
                if(imagejpeg($thumb, $thumbfile)) {
                  $pheight = MAXWIDTH * $img[1] / $img[0];
                  $photo = imagecreatetruecolor(MAXWIDTH, $pheight);
                  if(imagecopyresampled($photo, $upload, 0, 0, 0, 0, MAXWIDTH, $pheight, $img[0], $img[1])) {
                    $photofile = substr($thumbfile, 0, -1) . 'eg';
                    if(imagejpeg($photo, $photofile)) {
                      $file = explode('.', $_FILES['photo']['name']);
                      unset($file[count($file) - 1]);
                      $file = implode('.', $file);
                      $ins = 'insert into albumphotos (`group`, caption, url, story, sort) values (' . $_POST['groupid'] . ', \'' . addslashes($_POST['caption']) . '\', \'' . $_GET['name'] . '/' . $file . '\', \'' . addslashes($_POST['story']) . '\', ' . $_POST['sort'] . ')';
                      if(false !== $db->Put($ins, 'error saving photo'))
                        $page->Info('photo added successfully');
                    } else
                      $page->Error('unable to save photo');
                  } else
                    $page->Error('unable to resize photo for display');
                } else
                  $page->Error('unable to save thumbnail image');
              } else
                $page->Error('unable to resize photo to thumbnail size');
            } else
              $page->Error('unable to open uploaded photo');
          } else
            $page->Error('photo must be jpeg format');
        } else
          $page->Error('no photo uploaded!');

        break;
      case 'editpage':
        $pg = 'select image, tooltip, sort from albumpages where name=\'' . addslashes($_GET['name']) . '\'';
        if($pg = $db->GetRecord($pg, 'unable to get page information', 'unable to find page', true)) {
          if($_POST['name'] != $_GET['name']) {
            $update = 'update albumgroups set page=\'' . addslashes($_POST['name']) . '\' where page=\'' . addslashes($_GET['name']) . '\'';
            $db->Change($update, 'unable to update groups with new page name');
          }
          if(!is_numeric($_POST['sort']) || $_POST['sort'] < 1) {
            $_POST['sort'] = 'select max(sort) from albumpages';
            if(false === $_POST['sort'] = $db->GetValue($_POST['sort'], '', ''))
              $_POST['sort'] = 1;
          }
          if($_POST['sort'] < $pg->sort) {
            $shift = 'update albumpages set sort=sort+1 where sort>=' . $_POST['sort'] . ' and sort<' . $pg->sort;
            $db->Change($shift, 'unable to update sort order');
          } elseif($_POST['sort'] > $pg->sort) {
            $shift = 'update albumpages set sort=sort-1 where sort>' . $pg->sort . ' and sort<=' . $_POST['sort'];
            $db->Change($shift, 'unable to update sort order');
          }
          $update = 'update albumpages set name=\'' . addslashes($_POST['name']) . '\', image=\'' . addslashes($_POST['image']) . '\', tooltip=\'' . addslashes($_POST['tooltip']) . '\', sort=' . $_POST['sort'] . ' where name=\'' . $_GET['name'] . '\'';
          $_GET['name'] = $_POST['name'];
          if($db->Change($update, 'unable to update page'))
            $page->Info('page updated successfully');
        }
        break;
      case 'editgroup':
        $group = 'select sort from albumgroups where id=' . $_POST['id'];
        if($group = $db->GetRecord($group, 'error looking up current sort value for group', 'unable to find group', true)) {
          if($_POST['sort'] < 1 || !is_numeric($_POST['sort'])) {
            $_POST['sort'] = 'select max(sort) from albumgroups where page=\'' . $_GET['name'] . '\'';
            if(false === $_POST['sort'] = $db->GetValue($_POST['sort'], '', ''))
              $_POST['sort'] = 1;
          }
          if($_POST['sort'] < $group->sort) {
            $shift = 'update albumgroups set sort=sort+1 where sort>=' . $_POST['sort'] . ' and sort<' . $group->sort . ' and page=\'' . $_GET['name'] . '\'';
            $db->Change($shift, 'error updating sort order');
          } elseif($_POST['sort'] > $group->sort) {
            $shift = 'update albumgroups set sort=sort-1 where sort<=' . $_POST['sort'] . ' and sort>' . $group->sort . ' and page=\'' . $_GET['name'] . '\'';
            $db->Change($shift, 'error updating sort order');
          }
          $update = 'update albumgroups set title=\'' . addslashes($_POST['title']) . '\', sort=' . $_POST['sort'] . ' where id=' . $_POST['id'];
          if($db->Change($update, 'error saving changes to group'))
            $page->Info('group updated successfully');
        }
        break;
      default:
        $page->Error('unknown form submitted');
        break;
    }

  if(isset($_GET['editpage']) && $user->GodMode) {
    $pg = 'select image, tooltip, sort from albumpages where name=\'' . $_GET['name'] . '\'';
    if($pg = $db->GetRecord($pg, 'unable to get page information', 'unable to find page', true)) {
      $form = new auForm('editpage', '/album/' . $_GET['name'] . '/');
      $formset = auFormFieldSet('edit this page');
      $formset->AddField('name', 'name', 'name of this page (will be used as a directory name)', true, $_GET['name'], _AU_FORM_FIELD_NORMAL, 10, 16);
      $formset->AddField('image', 'image', 'image (without path or extension) that should be used to represent this page', true, $pg->image, _AU_FORM_FIELD_NORMAL, 16, 32);
      $formset->AddField('tooltip', 'tooltip', 'tooltip to display with link to this page', false, $pg->tooltip, _AU_FORM_FIELD_NORMAL, 50, 255);
      $formset->AddField('sort', 'sort', 'number used to determine the order the pages display in', false, $pg->sort, _AU_FORM_FIELD_NORMAL, 1, 3);
      $formset->AddButtons('save', 'save changes to this page');
      $form->AddFieldSet($formset);
      $form->WriteHTML(true);
    }
  }
  if(isset($_GET['newgroup']) && $user->GodMode) {
    $form = new auForm('newgroup', '/album/' . $_GET['name'] . '/');
    $formset = auFormFieldSet('add new group');
    $formset->AddField('title', 'title', 'title of this group to be displayed as a heading on the page', false, '', _AU_FORM_FIELD_NORMAL, 50, 128);
    $formset->AddField('sort', 'sort', 'number used to determine the order the groups display in', false, '', _AU_FORM_FIELD_NORMAL, 1, 3);
    $formset->AddButtons('save', 'add this group');
    $form->AddFieldSet($formset);
    $form->WriteHTML(true);
  }

  $groups = 'select id, title, sort from albumgroups where page=\'' . addslashes($_GET['name']) . '\' order by sort';
  if($groups = $db->Get($groups, 'error reading groups of photos for this page', 'there are currently no photos on this page')) {
?>
      <p>click a photo to see a larger version with a longer description.</p>

<?
    while($group = $groups->NextRecord()) {
      if(isset($_GET['editgroup']) && $user->GodMode && $_GET['editgroup'] == $group->id) {
        $form = new auForm('editgroup', '/album/' . $_GET['name'] . '/');
        $formset = ('edit \'' . $group->title . '\' group');
        $formset->AddData('id', $group->id);
        $formset->AddField('title', 'title', 'title of this group to be displayed as a heading on the page', false, $group->title, _AU_FORM_FIELD_NORMAL, 50, 128);
        $formset->AddField('sort', 'sort', 'number used to determine the order the groups display in', false, $group->sort, _AU_FORM_FIELD_NORMAL, 1, 3);
        $formset->AddButtons('save', 'save changes to this group');
        $form->AddFieldSet($formset);
        $form->WriteHTML(true);
      } elseif(isset($_GET['delgroup']) && $user->GodMode && $_GET['delgroup'] == $group->id) {
        $del = 'delete from albumgroups where id=' . $group->id;
        if(false !== $db->Change($del, 'unable to delete group')) {
          $page->Info('group deleted successfully');
          $group->title = '(deleted)';
          $group->id = 0;
        }
      }
      if(strlen($group->title) > 0 || $user->GodMode)
        $page->Heading($group->title . ($user->GodMode ? ' &nbsp; <a class="img" href="/output/album/' . $_GET['name'] . '/newphoto=' . $group->id . '" title="add a photo to this group"><img src="/style/new.png" alt="add" /></a> <a class="img" href="/output/album/' . $_GET['name'] . '/editgroup=' . $group->id . '" title="edit this group"><img src="/style/edit.png" alt="edit" /></a> <a class="img" href="/output/album/' . $_GET['name'] . '/delgroup=' . $group->id . '" title="delete this group"><img src="/style/del.png" alt="del" /></a>' : ''));
      echo '      <div class="photogroup">' . "\n";
      $photos = 'select id, caption, url from albumphotos where `group`=' . $group->id . ' order by sort';
      if($photos = $db->Get($photos, 'error reading photos for this group', '')) {
        while ($photo = $photos->NextRecord()) {
?>
        <div class="photo">
          <a class="img" href="/output/album/<?=$_GET['name']; ?>/photo<?=substr($photo->url, strlen($_GET['name'])); ?>.jpeg" title="click to enlarge"><img src="/album/<?=$photo->url; ?>.jpg" alt="" /><?=$photo->caption; ?></a>
        </div>
<?
        }
      }
?>
        <br class="clear" />
      </div>

<?
      if(isset($_GET['newphoto']) && $user->GodMode && $_GET['newphoto'] == $group->id) {
        $form = new auForm('newphoto', '/album/' . $_GET['name'] . '/');
        $form->AddData('groupid', $group->id);
        $formset = new auFormFieldSet('add new photo to \'' . $group->title . '\'');
        $formset->AddField('caption', 'caption', 'the caption will be used as the name for this photo', true, '', _AU_FORM_FIELD_NORMAL, 15, 20);
        //$formset->AddField('url', 'url', 'url to the image, in the form dirname/imagename (like me/pins)', true, '', _AU_FORM_FIELD_NORMAL, 15, 32);
        $formset->AddField('photo', 'photo', 'upload a photo, which will automatically get resized', true, '', _AU_FORM_FIELD_FILE);
        $formset->AddField('story', 'description', 'a longer description of the photo to display on the photo page only', true, '', _AU_FORM_FIELD_BBCODE);
        $formset->AddField('sort', 'sort', 'number used to determine what order photos are displayed in', false, '', _AU_FORM_FIELD_NORMAL, 2, 3);
        $formset->AddButtons('save', 'add this photo');
        $form->AddFieldSet($formset);
        $form->WriteHTML(true);
      }
    }
  }
  $page->End();
?>
