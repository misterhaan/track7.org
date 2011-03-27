<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  $page->Start('original lego models');
?>
      <p>
        the lego models below are all my own original creations.&nbsp; each is
        available for download as an <a href="http://www.ldraw.org/">ldraw</a>-format
        model file and as a collection of instruction images.
      </p>
<?
  if($user->GodMode) {
    if(isset($_GET['add'])) {
      if($_POST['submit'] == 'save') {
        if(!$_POST['name'])
          $_POST['name'] = $_POST['id'];
        $ins = 'insert into legos (id, name, pieces, minifigs, notes, adddate) values (\'' . addslashes(htmlspecialchars($_POST['id'])) . '\', \'' . TEXT::slash(htmlspecialchars($_POST['name'])) . '\', ' . TEXT::slash($_POST['pieces']) . ', ' . TEXT::slash($_POST['minifigs']) . ', \'' . addslashes(auText::BB2HTML($_POST['notes'])) . '\', ' . time() . ')';
        if(false !== $db->Put($ins, 'error saving model')) {
          $page->Info('model added successfully');
        }
      } else {
        $page->Heading('add a new model');
        $legoform = new auForm('addlego', '?add');
        $legoform->AddField('id', 'id', 'enter a unique identifier for this model, which will also be used in filenames', true, '', _AU_FORM_FIELD_NORMAL, 12, 32);
        $legoform->AddField('name', 'name', 'enter the name of this model, or leave blank to use the id', false, '', _AU_FORM_FIELD_NORMAL, 16, 32);
        $legoform->AddField('pieces', 'pieces', 'enter the number of pieces needed to build this model', true, '', _AU_FORM_FIELD_NORMAL, 2, 3);
        $legoform->AddField('minifigs', 'minifigs', 'enter the number of minifigs this model can carry', true, '', _AU_FORM_FIELD_NORMAL, 1, 3);
        $legoform->AddField('notes', 'notes', 'enter any notes to display with this model', false, '', _AU_FORM_FIELD_BBCODE);
        $legoform->AddButtons('save', 'add this model');
        $legoform->WriteHTML(true);
      }
    } else {
?>
      <ul><li><a href="?add">add a new model</a></li></ul>
<?
    }
  }
  $legos = 'select l.id, l.name, l.notes, l.pieces, l.minifigs, l.adddate, ifnull(r.rating,0) as rating, ifnull(r.votes,0) as votes, v.vote from legos as l left join ratings as r on r.type=\'lego\' and r.selector=l.id left join votes as v on v.ratingid=r.id and (not (v.uid=0) and v.uid=' . $user->ID . ' or v.ip=\'' . addslashes($_SERVER['REMOTE_ADDR']) . '\') order by rating desc, votes desc, adddate desc';
  if($legos = $db->GetSplit($legos, 10, 0, '', '', 'error looking up listing of lego models', 'no lego models found', false, true)) {
    while($lego = $legos->NextRecord()) {
      $page->Heading($lego->name, $lego->id);
?>
      <div class="thumb">
        <a class="img" href="<?=$lego->id; ?>.png" title="click to view full-size image"><img src="<?=$lego->id; ?>-thumb.png" alt="full-size image" /></a>
        <div><?=auFile::ImageSize($lego->id . '.png'); ?></div>
        <? auRating::Show('lego', $lego->id, $lego->rating, $lego->votes, $lego->vote); ?>
      </div>
      <div class="thumbed">
<?
      if($lego->id == "stalker") {
?>
        <a href="http://naughtynestor.com/080415-stalker.php" class="award" title="see naughty nestor blow up this model"><img src="award-nestor-destruction.png" alt="worthy of destruction award" title="" /></a>
<?
      }
?>
        <table cellspacing="0" class="columns">
          <tr class="firstchild"><th>pieces</th><td><?=$lego->pieces; ?></td></tr>
          <tr><th>mans</th><td><?=$lego->minifigs; ?></td></tr>
          <tr><th>downloads</th><td><a href="/files/output/lego/<?=$lego->id; ?>-img.zip"><?=$lego->name; ?> instruction images</a> <em>(<?=auFile::Size($lego->id . '-img.zip'); ?>)</em><br /><a href="/files/output/lego/<?=$lego->id; ?>-ldr.zip"><?=$lego->name; ?> ldraw data file</a> <em>(<?=auFile::Size($lego->id . '-ldr.zip'); ?>)</em></td></tr>
          <tr><th>notes</th><td><?=$lego->notes; ?></td></tr>
          <tr><th>added</th><td><?=auText::HowLongAgo($lego->adddate); ?> ago</td></tr>
        </table>
<?
      if($_GET['vote'] == $lego->id) {
        $rating = 'select v.vote from votes as v, ratings as r where r.id=v.ratingid and r.type=\'lego\' and r.selector=\'' . $lego->id . '\' and (v.uid=' . $user->ID . ' or v.ip=\'' . $_SERVER['REMOTE_ADDR'] . '\') order by v.ip';
        $rating = $db->GetValue($rating, 'error checking to see if you have already voted', '');
        $vote = new auForm('vote');
        $vote->AddData('model', $lego->id);
        $voteset = new auFormFieldSet('rate ' . $lego->name);
        $voteset->AddSelect('vote', 'rating', 'choose your rating of this lego model', array(-3 => '-3 (worst)', -2 => '-2', -1 => '-1', 0 => '0 (average)', 1 => '1', 2 => '2', 3 => '3 (best)'), +$rating);
        $voteset->AddButtons('vote', 'cast your vote for this lego model');
        $vote->AddFieldSet($voteset);
        $vote->WriteHTML($user->Valid);
      }
?>
      </div>
<?
    }
    $page->SplitLinks();
  }

  $page->End();
?>
