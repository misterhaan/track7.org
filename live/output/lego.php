<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';
  require_once 'auText.php';
  require_once 'auFile.php';

  $page->Start('original lego models');
?>
      <p>
        the lego models below are all my own original creations.&nbsp; each is
        available for download as an <a href="http://www.ldraw.org/">ldraw</a>-format
        model file and as a collection of instruction images.
      </p>
<?
  if($_POST['submit'] == 'vote' && is_numeric($_POST['vote']) && $_POST['vote'] >= -3 && $_POST['vote'] <= 3) {
    $ratingid = 'select id from ratings where type=\'lego\' and selector=\'' . addslashes($_POST['model']) . '\'';
    if($ratingid = $db->Get($ratingid, 'error looking up rating id')) {
      if($ratingid = $ratingid->NextRecord())
        $ratingid = $ratingid->id;
      else {
        $ins = 'insert into ratings (type, selector) values (\'lego\', \'' . addslashes(htmlspecialchars($_POST['model'])) . '\')';
        $ratingid = $db->Put($ins, 'error initializing rating');
      }
      if($ratingid) {
        $vote = 'replace into votes (ratingid, vote, uid, ip, time) values (' . $ratingid . ', ' . $_POST['vote'] . ', ' . $user->ID . ', \'' . ($user->Valid ? '' : $_SERVER['REMOTE_ADDR']) . '\', ' . time() . ')';
        if(false !== $db->Change($vote, 'error adding vote')) {
          $rating = 'select sum(vote) ratesum, count(1) ratecnt from votes where ratingid=' . $ratingid;
          if($rating = $db->GetRecord($rating, 'error calculating new rating', 'no votes found')) {
            $rating = 'update ratings set rating=' . ($rating->ratesum / $rating->ratecnt) . ', votes=' . $rating->ratecnt . ' where id=' . $ratingid;
            if(false !== $db->Change($rating, 'error updating new rating'))
              $page->Info('vote sucessfully added or updated');
          }
        }
      }
    }
  }
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
  $legos = 'select l.id, l.name, l.notes, l.pieces, l.minifigs, l.adddate, r.rating, r.votes from legos as l left join ratings as r on r.type=\'lego\' and r.selector=l.id order by rating desc, adddate desc';
  if($legos = $db->GetSplit($legos, 10, 0, '', '', 'error looking up listing of lego models', 'no lego models found', false, true)) {
    while($lego = $legos->NextRecord()) {
      $page->Heading($lego->name);
?>
      <div class="thumb">
        <a class="img" href="lego/<?=$lego->id; ?>.png" title="click to view full-size image"><img src="lego/<?=$lego->id; ?>-thumb.png" alt="full-size image" /></a>
        <div><?=auFile::ImageSize('lego/' . $lego->id . '.png'); ?></div>
      </div>
      <div class="thumbed">
        <table cellspacing="0" class="columns">
          <tr class="firstchild"><th>pieces</th><td><?=$lego->pieces; ?></td></tr>
          <tr><th>minifigs</th><td><?=$lego->minifigs; ?></td></tr>
          <tr><th>downloads</th><td><a href="/files/output/lego/<?=$lego->id; ?>-img.zip"><?=$lego->id; ?> instruction images</a> <em>(<?=auFile::Size('lego/' . $lego->id . '-img.zip'); ?>)</em><br /><a href="/files/output/lego/<?=$lego->id; ?>-ldr.zip"><?=$lego->id; ?> ldraw data file</a> <em>(<?=auFile::Size('lego/' . $lego->id . '-ldr.zip'); ?>)</em></td></tr>
          <tr><th>notes</th><td><?=$lego->notes; ?></td></tr>
          <tr><th>added</th><td><?=auText::HowLongAgo($lego->adddate); ?> ago</td></tr>
          <tr><th>rating</th><td><?=+$lego->rating; ?> (from <?=+$lego->votes; ?> vote<?=$lego->votes == 1 ? '' : 's'; ?>)<?=$_GET['vote'] == $lego->id ? '' : ' <a href="?vote=' . $lego->id . ($_GET['skip'] ? '&amp;skip=' . $_GET['skip'] : '') . '#frmvote">cast vote</a>'; ?></td></tr>
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
