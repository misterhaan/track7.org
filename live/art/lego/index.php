<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  $page->Start('original lego models');

  // DO:  move to model.php
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
      <ul class=actions><li class=add><a href="?add">add a new model</a></li></ul>
<?
    }
  }
?>
      <p>
        the lego models below are all my own original creations.&nbsp; each is
        available for download as an <a href="http://www.ldraw.org/">ldraw</a>-format
        model file and as a collection of instruction images.
      </p>
      <div id=sortoptions>
        sort by:&nbsp;
<?
  switch($_GET['sort']) {
    case 'newest':
?>
        newest |
        <a href="../lego/">highest rated</a> |
        <a href="?sort=pieces">most pieces</a>
<?
      $legos = 'adddate desc';
      break;
    case 'pieces':
?>
        pieces |
        <a href="../lego/">highest rated</a> |
        <a href="?sort=newest">newest</a>
<?
      $legos = 'pieces desc, ifnull(r.rating,0) desc, ifnull(r.votes,0) desc, adddate desc';
      break;
    default:
?>
        highest rated |
        <a href="?sort=newest">newest</a> |
        <a href="?sort=pieces">most pieces</a>
<?
      $legos = 'ifnull(r.rating,0) desc, ifnull(r.votes,0) desc, adddate desc';
  }
?>
      </div>
<?
  $legos = 'select l.id, l.name from legos as l left join ratings as r on r.type=\'lego\' and r.selector=l.id left join votes as v on v.ratingid=r.id and (not (v.uid=0) and v.uid=' . $user->ID . ' or v.ip=\'' . addslashes($_SERVER['REMOTE_ADDR']) . '\') order by ' . $legos;
  if($legos = $db->GetSplit($legos, 24, 0, '', '', 'error looking up listing of lego models', 'no lego models found', false, true)) {
?>
      <ol id=legothumbs>
<?
    while($lego = $legos->NextRecord()) {
?>
        <li><a href="<?=$lego->id; ?>"><img alt="" src="<?=$lego->id; ?>-thumb.png"><?=$lego->name; ?></a></li>
<?
    }
?>
      </ol>
<?
    $page->SplitLinks();
  }

  $page->End();
?>
