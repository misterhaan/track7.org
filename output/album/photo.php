<?
  // go back to the index page if they didn't specify a photo
  if(!isset($_GET['url'])) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . '/album/');
    die;
  }
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';
  require_once 'auFile.php';

  // go back to the index page if the specified photo doesn't exist
  if(!($photo = $db->GetRecord('select `group`, caption, story, sort from albumphotos where url=\'' . addslashes($_GET['url']) . '\'', '', ''))) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . '/album/');
    die;
  }
  $pg = 'select page as name from albumgroups where id=' . $photo->group;
  if(!($pg = $db->Get($pg, 'error trying to figure out which page this photo belongs to', 'this photo does not seem to belong to a page', true)))
    $page->start('photo album - ' . $photo->caption);
  else {
    if(isset($_GET['del']) && $user->GodMode) {
      // delete this photo
      $del = 'delete from albumphotos where url=\'' . addslashes($_GET['url']) . '\'';
      if(false !== $db->Change($del, 'unable to delete photo'))
        $page->Info('photo deleted successfully');
      $page->Start('delete photo');
      $page->End();
      die;
    } elseif(isset($_POST['submit']) && $user->GodMode) {
      // edit this photo
      $photo->caption = $_POST['caption'];
      $photo->story = $_POST['story'];
      if(!is_numeric($_POST['sort']))
        $_POST['sort'] = 0;
      if($_POST['sort'] < 1) {
        $_POST['sort'] = 'select max(sort) from albumphotos where `group`=' . $photo->group;
        if($_POST['sort'] = $db->GetValue($_POST['sort'], '', '')) {
          if($_POST['sort'] < 1)
            $_POST['sort'] = 1;
        } else {
          $_POST['sort'] = 1;
        }
      } 
      if($_POST['sort'] < $photo->sort) {
        $shift = 'update albumphotos set sort=sort+1 where sort>=' . $_POST['sort'] . ' and sort<' . $photo->sort . ' and `group`=' . $photo->group;
        $db->Update($shift, 'error shifting sort order of existing photos');
      } elseif($_POST['sort'] > $photo->sort) {
        $shift = 'update albumphotos set sort=sort-1 where sort>' . $photo->sort . ' and sort<=' . $_POST['sort'] . ' and `group`=' . $photo->group;
        $db->Update($shift, 'error shifting sort order of existing photos');
      }
      $photo->sort = $_POST['sort'];
      $update = 'update albumphotos set caption=\'' . addslashes($photo->caption) . '\', story=\'' . addslashes($photo->story) . '\', sort=' . $photo->sort . ' where url=\'' . addslashes($_GET['url']) . '\'';
      if(false !== $db->Change($update, 'error updating photo'))
        $page->Info('photo updated successfully');
    }
    $pg = $pg->NextRecord();
    $page->Start($photo->caption . ' - ' . $pg->name . ' - photo album', $photo->caption . ($user->GodMode ? ' &nbsp; <a class="img" href="/output/album/' . $pg->name . '/photo' . substr($_GET['url'], strlen($pg->name)) . '.jpeg/edit" title="edit this photo"><img src="/style/edit.png" alt="edit" /></a> <a class="img" href="/output/album/' . $pg->name . '/photo' . substr($_GET['url'], strlen($pg->name)) . '.jpeg/del" title="delete this photo"><img src="/style/del.png" alt="del" /></a>' : ''), 'photo album:&nbsp; ' . $pg->name);
  }
?>
      <img class="photo" src="/album/<?=$_GET['url']; ?>.jpeg" alt="" <?=auFile::ImageSizeCSS('/album/' . $_GET['url'] . '.jpeg'); ?>/>
<?
  if(isset($_GET['edit']) && $user->GodMode) {
    $form = new auForm('editphoto', '/album/' . $pg->name . '/photo' . substr($_GET['url'], strlen($pg->name)) . '.jpeg');
    $formset = new auFormFieldSet('edit photo');
    $formset->AddField('caption', 'caption', 'the caption will be used as the name for this photo', true, $photo->caption,  _AU_FORM_FIELD_NORMAL, 15, 20);
    $formset->AddField('story', 'description', 'a longer description of the photo to display on the photo page only', true, $photo->story, _AU_FORM_FIELD_MULTILINE);
    $formset->AddField('sort', 'sort', 'number used to determine what order photos are displayed in', false, $photo->sort,  _AU_FORM_FIELD_NORMAL, 2, 3);
    $formset->AddButtons('save', 'save changes to this photo');
    $form->AddFieldSet($formset);
    $form->WriteHTML(true);
  } else {
?>
      <p class="photostory">
        <?=$photo->story; ?>

      </p>
<?
  }
  $page->End();
?>
