<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if($_GET['name']) {
    $entry = 'select name, instant, tags, title, post from bln where name=\'' . addslashes($_GET['name']) . '\'';
    if($entry = $db->GetRecord($entry, 'error looking up entry', 'entry not found')) {
      if($user->GodMode && isset($_GET['edit'])) {
        $frm = GetEntryForm($entry);
        if($frm->CheckInput(true)) {
          require_once 'auFile.php';
          $update = 'update bln set name=\'' . auFile::NiceName($_POST['name']) . '\', title=\'' . addslashes(htmlentities($_POST['title'], ENT_COMPAT, _CHARSET)) . '\', tags=\'' . addslashes(htmlentities($_POST['tags'], ENT_COMPAT, _CHARSET)) . '\', post=\'' . addslashes(auText::BB2HTML($_POST['post'])) . '\' where name=\'' . $entry->name . '\'';
          if(false !== $db->Change($update, 'error updating entry')) {
            $oldtags = explode(',', $entry->tags);
            $newtags = explode(',', $_POST['tags']);
            if(count($oldtags) && count($newtags))
              for($i = 0; $i < count($oldtags); $i++)
                if(false !== $pos = array_search($oldtags[$i], $newtags))
                  unset($oldtags[$i], $newtags[$pos]);
            if(count($oldtags))
              $db->Change('update taginfo set count=count-1 where type=\'entries\' and (name=\'' . implode('\' or name=\'', $oldtags) . '\')');
            if(count($newtags))
              $db->Put('insert into taginfo (type, name, count) values (\'entries\', \'' . implode('\', 1), (\'entries\', \'', $newtags) . '\', 1) on duplicate key update count=count+1');
            header('Location: http://' . $_SERVER['HTTP_HOST'] . '/output/pen/bln/' . auFile::NiceName($_POST['name']));
            die;
          }
        }
        $page->Start('edit entry');
        $frm->WriteHTML(true);
        $page->End();
        die;
      }
      if($entry->instant)
        $page->Start($entry->title . ' - bln', $entry->title, 'posted in ' . TagLinks($entry->tags) . ', ' . strtolower($user->tzdate('M j, Y', $entry->instant)));
      else
        $page->Start($entry->title . ' - bln', $entry->title, 'posted in ' . TagLinks($entry->tags));
      if($user->GodMode) {
?>
      <ul><li><a href="<?=$_GET['name']; ?>&amp;edit">edit this entry</a></li></ul>
<?
        }
?>
      <p>
        <?=$entry->post; ?>

      </p>
<?
      $page->SetFlag(_FLAG_PAGES_COMMENTS);  // show comments
      $page->End();
      die;
    }
  } elseif($user->GodMode && isset($_GET['edit'])) {
    $frm = GetEntryForm();
    if($frm->CheckInput(true)) {
      require_once 'auFile.php';
      $ins = 'insert into bln (name, instant, tags, title, post) values (\'' . auFile::NiceName($_POST['name']) . '\', ' . time() . ', \'' . addslashes(htmlspecialchars($_POST['tags'])) . '\', \'' . addslashes(htmlentities($_POST['title'])) . '\', \'' . addslashes(auText::BB2HTML($_POST['post'])) . '\')';
      if(false !== $db->Put($ins, 'error saving new entry')) {
        $db->Put('insert into taginfo (type, name, count) values (\'entries\', \'' . str_replace(',', '\', 1), (\'entries\', \'', $_POST['tags']) . '\', 1) on duplicate key update count=count+1');
        header('Location: http://' . $_SERVER['HTTP_HOST'] . '/output/pen/bln/' . auFile::NiceName($_POST['name']));
        die;
      }
    }
    $page->Start('add entry - bln', 'add entry');
    $frm->WriteHTML(true);
    $page->End();
    die;
  }
  $page->Show404();

  function TagLinks($tags) {
    $tags = explode(',', $tags);
    foreach($tags as $tag) {
      $links[] = '<a href="tag=' . $tag . '">' . $tag . '</a>';
    }
    return implode(', ', $links);
  }

  function GetEntryForm($entry = false) {
    require_once 'auForm.php';
    require_once 'auText.php';
    $frm = new auForm('entry', $entry->name . '&edit');
    $frm->AddField('name', 'name', 'name to use in url; must be unique', true, $entry->name, _AU_FORM_FIELD_NORMAL, 20, 32);
    $frm->AddField('title', 'title', 'title to display on the page', true, $entry->title, _AU_FORM_FIELD_NORMAL, 50, 128);
    $frm->AddField('tags', 'tags', 'tags for this entry (comma-separated)', true, $entry->tags, _AU_FORM_FIELD_NORMAL, 50, 255);
    $frm->AddField('post', 'entry', 'the text of this entry (t7code)', true, auText::HTML2BB($entry->post), _AU_FORM_FIELD_BBCODE, 10);
    $frm->AddButtons('save', 'save this entry');
    return $frm;
  }
?>