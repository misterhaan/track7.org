<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if($user->GodMode) {
    if(isset($_GET['new'])) {
      $new = getSketchForm();
      if($new->Submitted(true)) {
        $ins = 'insert into art (id, type, description, adddate) values (\'' . addslashes($_POST['id']) . '\', \'sketch\', \'' . addslashes(auText::BB2HTML($_POST['description'], false, false)) . '\', \'' . time() . '\')';
        if(false !== $db->Put($ins, 'error adding sketch'))
          header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
      }
      $page->ResetFlag(_FLAG_PAGES_COMMENTS);
      $page->Start('add pen / pencil sketch');
      $new->WriteHTML(true);
      $page->End();
      die;
    }
    if($_GET['edit']) {
      $sketch = 'select id, description from art where id=\'' . addslashes($_GET['edit']) . '\' and type=\'sketch\'';
      if(false !== $sketch = $db->GetRecord($sketch, 'error looking up sketch to edit', 'unable to find sketch for editing', true)) {
        $edit = getSketchForm($sketch);
        if($edit->Submitted(true)) {
          $update = 'update art set id=\'' . addslashes($_POST['id']) . '\', description=\'' . addslashes(auText::BB2HTML($_POST['description'], false, false)) . '\' where id=\'' . $sketch->id . '\'';
          if(false !== $db->Change($update, 'error updating sketch'))
            header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
        }
        $page->ResetFlag(_FLAG_PAGES_COMMENTS);
        $page->Start('edit pen / pencil sketch');
        $edit->WriteHTML(true);
        $page->End();
        die;
      }
    }
    $page->info('<a href="?new">add a sketch</a>');
  }
  $page->AddFeed('track7 art', '/feeds/art.rss');
  $page->Start('pen / pencil sketch gallery', 'pen / pencil sketch gallery<a class="feed" href="/feeds/art.rss" title="rss feed of art"><img src="/style/feed.png" alt="feed" /></a>');
?>
      <p>
        the following are most of the pen and pencil sketches i’ve done
        throughout my years (and haven’t thrown out or lost).&nbsp; the previews
        are cropped and don’t show the whole sketch — click on them to bring up
        a large version.
      </p>

<?
  $sketches = 'select a.id, a.description, a.adddate, ifnull(r.rating,0) as rating, ifnull(r.votes,0) as votes, v.vote from art as a left join ratings as r on r.selector=a.id and r.type=\'sketch\' left join votes as v on v.ratingid=r.id and ' . ($user->Valid ? 'v.uid=' . $user->ID : 'v.ip=\'' . addslashes($_SERVER['REMOTE_ADDR']) . '\'') . ' where a.type=\'sketch\' order by rating desc, votes desc, a.adddate desc';
  if($sketches = $db->Get($sketches, 'error looking up sketches', 'no sketches found'))
    while($sketch = $sketches->NextRecord()) {
      $page->Heading('', $sketch->id);
?>
      <div class="thumb">
        <a class="img" href="<?=$sketch->id; ?>.png" title="click to view full-size image"><img src="<?=$sketch->id; ?>-prev.png" alt="" /></a>
        <div><?=auFile::ImageSize($sketch->id . '.png'); ?></div>
        <? auRating::Show('sketch', $sketch->id, $sketch->rating, $sketch->votes, $sketch->vote); ?>
      </div>
      <div class="thumbed">
        <?=$sketch->description; ?>

<?
      if($user->GodMode) {
?>
        <p class="info"><a href="?edit=<?=$sketch->id; ?>">edit this sketch</a></p>
<?
      }
?>
      </div>
<?
    }

  $page->End();

  function getSketchForm($sketch = false) {
    $frm = new auForm('sketch', $sketch ? '?edit=' . $sketch->id : '?new');
    $frm->Add(new auFormString('id', 'id', 'unique identifier (also filename) for this sketch', true, $sketch->id, 20, 32));
    $frm->Add(new auFormMultiString('description', 'description', 'description of this sketch', true, auText::HTML2BB($sketch->description), true));
    $frm->Add(new auFormButtons('save', 'save this sketch'));
    return $frm;
  }
?>
