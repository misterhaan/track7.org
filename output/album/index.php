<?
  if(isset($_GET['page'])) {
    if(!isset($_GET['photo']) || !is_numeric($_GET['photo'])) {
      header('HTTP/1.x Moved Permanently');
      header('Location: http://' . $_SERVER['HTTP_HOST'] . '/album/' . $_GET['page'] . '/');
      die;
    } else {
      require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
      $photo = 'select url from albumphotos where id=' . $_GET['photo'];
      if($photo = $db->GetValue($photo, '', '')) {
        header('HTTP/1.x Moved Permanently');
        header('Location: http://' . $_SERVER['HTTP_HOST'] . '/album/' . $_GET['page'] . '/photo' . substr($photo, strlen($_GET['page'])) . '.jpeg');
        die;
      } else
        $page->Show404();
    }
  }
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';

  if($user->GodMode)
    $page->Start('photo album', 'photo album &nbsp; <a class="img" href="/output/album/?addpage" title="add a new page"><img src="/style/new.png" alt="new" /></a>');
  else
    $page->Start('photo album');

  if(isset($_POST['submit']) && $user->GodMode) {
    // save the new page
    if(!is_numeric($_POST['sort']))
      $_POST['sort'] = 0;
    if($_POST['sort'] < 1) {
      $_POST['sort'] = 'select max(sort) from albumpages';
      $_POST['sort'] = $db->GetValue($_POST['sort'], '', '');
      $_POST['sort']++;
    } else {
      $shift = 'update albumpages set sort=sort+1 where sort>=' . $_POST['sort'];
      $db->Change($shift, 'error updating sort order of existing pages');
    }
    $ins = 'insert into albumpages (name, image, tooltip, sort) values (\'' . addslashes($_POST['name']) . '\', \'' . addslashes($_POST['image']) . '\', \'' . addslashes($_POST['tooltip']) . '\', ' . $_POST['sort'] . ')';
    if(false !== $db->Put($ins, 'error saving new page'))
      $page->Info('new page added successfully');
  }

  $pages = 'select name, image, tooltip from albumpages order by sort';
  if($pages = $db->Get($pages, 'error reading pages', 'no pages found')) {
?>
      <p>
        click a photo to turn to a page of pictures.
      </p>

      <div class="photogroup">
<?
    while($pg = $pages->NextRecord()) {
?>
        <div class="photo">
          <a class="img" title="<?=$pg->tooltip; ?>" href="<?=$pg->name; ?>/"><img src="<?=$pg->image; ?>.jpg" alt="" /><?=$pg->name; ?></a>
        </div>
<?
    }
?>
        <br class="clear" />
      </div>

<?
  }
  if(isset($_GET['addpage']) && $user->GodMode) {
    $form = new auForm('newpage', '/album/');
    $formset = new auFormFieldSet('add a new page');
    $formset->AddField('name', 'name', 'name of this page (will be used as a directory name)', true, '', _AU_FORM_FIELD_NORMAL, 10, 16);
    $formset->AddField('image', 'image', 'image (without path or extension) that should be used to represent this page', true, $pg->image, _AU_FORM_FIELD_NORMAL, 16, 32);
    $formset->AddField('tooltip', 'tooltip', 'tooltip to display with link to this page', false, '', _AU_FORM_FIELD_NORMAL, 50, 255);
    $formset->AddField('sort', 'sort', 'number used to determine the order the pages display in', false, '', _AU_FORM_FIELD_NORMAL, 1, 3);
    $formset->AddButtons('save', 'add this page');
    $form->AddFieldSet($formset);
    $form->WriteHTML(true);
  }
  $page->End();
?>
