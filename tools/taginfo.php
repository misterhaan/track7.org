<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(!$user->GodMode) {
    header('HTTP/1.0 403 Forbidden');
    @include $_SERVER['DOCUMENT_ROOT'] . '/403.php';
    die;
  }
  if(strlen($_GET['name']) && strlen($_GET['type'])) {
    $desc = 'select description from taginfo where type=\'' . addslashes($_GET['type']) . '\' and name=\'' . addslashes($_GET['name']) . '\'';
    if($desc = $db->GetValue($desc, 'error looking up tag information', 'tag not found', true)) {
      require_once 'auForm.php';
      require_once 'auText.php';
      $tagedit = new auForm('tagedit', '?type=' . $_GET['type'] . '&amp;name=' . $_GET['name']);
      $tagedit->AddField('desc', 'description', 'enter a description for this tag', false, auText::HTML2BB($desc), _AU_FORM_FIELD_BBCODE);
      $tagedit->AddButtons('save', 'save tag description');
      if($tagedit->CheckInput(true)) {
        $update = 'update taginfo set description=\'' . addslashes(auText::BB2HTML($_POST['desc'])) . '\' where type=\'' . addslashes($_GET['type']) . '\' and name=\'' . addslashes($_GET['name']) . '\'';
        if(false !== $db->Change($update, 'error updating tag description'))
          $page->Info('tag description successfully updated');
      }
      $tagedit->WriteHTML(true);
    } else
      unset($desc);
  }
  if(!$desc){
    $page->Start('tag information editor', 'tag information editor');
    $tags = 'select name, type, count from taginfo order by name, type';
    if($tags = $db->GetSplit($tags, 20, 0, '', '', 'error looking up tags', 'no tag information found')) {
?>
      <ul>
<?
      while($tag = $tags->NextRecord()) {
?>
        <li><a href="?type=<?=$tag->type; ?>&amp;name=<?=$tag->name; ?>"><?=$tag->type; ?>/<?=$tag->name; ?> (<?=$tag->count; ?>)</a></li>
<?
      }
?>
      </ul>
  
<?
      $page->SplitLinks();
    }
  }
  $page->End();
?>
