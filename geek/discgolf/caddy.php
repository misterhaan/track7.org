<?
  $getvars = array('player', 'sort', 'id');
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if(is_numeric($_GET['id'])) {
    $disc = 'select c.id, c.color, c.mass, c.discid, d.name, d.mfgr, d.type, c.uid, u.login, c.status, c.comments from dgcaddy as c left join users as u on u.uid=c.uid left join dgdiscs as d on d.id=c.discid where c.id=\'' . addslashes($_GET['id']) . '\'';
    if($disc = $db->GetRecord($disc, 'error looking up disc', 'disc not found', true)) {
      if(isset($_GET['edit']) && $user->ID == $disc->uid) {
        $editdisc = getCaddyForm($db, $disc, $disc);
        if($editdisc->CheckInput(true)) {
          $update = 'update dgcaddy set mass=\'' . +$_POST['mass'] . '\', color=\'' . addslashes(htmlentities($_POST['color'], ENT_COMPAT, _CHARSET)) . '\', status=\'' . addslashes(htmlentities($_POST['status'], ENT_COMPAT, _CHARSET)) . '\', comments=\'' . addslashes(auText::BB2HTML($_POST['comments'])) . '\' where id=\'' . $disc->id . '\'';
          if(false !== $db->Change($update, 'error updating disc')) {
            $ok = true;
            if(($disc->status == 'bag' || $disc->status == 'reserve') && ($_POST['status'] == 'lost' || $_POST['status'] == 'sold'))
              $ok = false !== $db->Change('update userstats set discs=discs-1 where uid=\'' . $user->ID . '\'', 'error updating disc count');
            if(($disc->status == 'lost' || $disc->status == 'sold') && ($_POST['status'] == 'bag' || $_POST['status'] == 'reserve'))
              $ok = false !== $db->Change('update userstats set discs=discs+1 where uid=\'' . $user->ID . '\'', 'error updating disc count');
            if($ok) {
              header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?id=' . $_GET['id']);
              die;
            }
          }
        }
        $page->Start('edit disc');
        $editdisc->WriteHTML(true);
        $page->End();
        die;
      }
      $page->Start($disc->login . '&rsquo;s ' . $disc->name . ' - disc golf', $disc->login . '&rsquo;s ' . $disc->name, $disc->type . ' from ' . $disc->mfgr);
?>
      <table class="columns" cellspacing="0">
        <tr><th>color</th><td><?=$disc->color; ?></td></tr>
        <tr><th>mass</th><td><?=$disc->mass; ?> g</td></tr>
        <tr><th>status</th><td><?=$disc->status; ?></td></tr>
      </table>

<?
      $page->Heading($disc->login . '&rsquo;s comments');
?>
      <p>
        <?=$disc->comments; ?>

      </p>

      <ul>
<?
      if($user->ID == $disc->uid) {
?>
        <li><a href="?id=<?=$_GET['id']; ?>&amp;edit">edit this disc</a></li>
<?
      }
?>
        <li><a href="?player=<?=$disc->login; ?>"><?=$disc->login; ?>&rsquo;s discs</a></li>
        <li><a href="players.php?p=<?=$disc->login; ?>"><?=$disc->login; ?>&rsquo;s player profile</a></li>
        <li><a href="discs.php?id=<?=$disc->discid; ?>"><?=$disc->name; ?> information</a></li>
      </ul>
<?
      $page->End();
      die;
    }
  }
  if($_GET['id'] == 'new' && is_numeric($_GET['disc']) && $user->Valid) {
    $disc = 'select id as discid, name, type, mfgr from dgdiscs where id=\'' . +$_GET['disc'] . '\'';
    if($disc = $db->GetRecord($disc, 'error looking up disc information', 'disc not found')) {
      $adddisc = getCaddyForm($db, $disc);
      if($adddisc->CheckInput(true)) {
        $ins = 'insert into dgcaddy (uid, discid, mass, color, comments, status) values (\'' . $user->ID . '\', \'' .  $disc->discid . '\', \'' . +$_POST['mass'] . '\', \'' . addslashes(htmlentities($_POST['color'], ENT_COMPAT, _CHARSET)) . '\', \'' . addslashes(auText::BB2HTML($_POST['comments'])) . '\', \'' . addslashes(htmlentities($_POST['status'], ENT_COMPAT, _CHARSET)) . '\')';
        if(false !== $caddyid = $db->Put($ins, 'error adding disc to collection')) {
          $ok = true;
          if($_POST['status'] == 'bag' || $_POST['status'] == 'reserve')
            $ok = false !== $db->Change('update userstats set discs=discs+1 where uid=\'' . $user->ID . '\'', 'error updating disc count');
          if($ok) {
            header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?id=' . $caddyid);
            die;
          }
        }
      }
      $page->ResetFlag(_FLAG_PAGES_COMMENTS);
      $page->Start('add ' . $disc->name);
?>
      <p>
        use this form to add a <?=$disc->name; ?> to your collection.&nbsp; you
        may add a <?=$disc->name; ?> even if you already have one.
      </p>
      <p>
        mass is usually somewhere between 150 g and 175 g, though larger discs
        are allowed to be heavier.&nbsp; you may enter anything you want (up to
        16 characters) for the color.&nbsp; generally values will be something
        like <em>red</em> or <em>yellow</em>.&nbsp; use the comments for
        whatever you want to remember about this disc.&nbsp; some examples
        include what you use the disc for, when you got it, and / or where you
        found it.
      </p>
<?
      $adddisc->WriteHTML(true);
      $page->End();
      die;
    }
  }

  $page->ResetFlag(_FLAG_PAGES_COMMENTS);
  if(strlen($_GET['player'])) {
    $page->Start(htmlentities($_GET['player'], ENT_COMPAT, _CHARSET) . '&rsquo;s discs - disc golf', htmlentities($_GET['player'], ENT_COMPAT, _CHARSET) . '&rsquo;s discs');
    $discs = ' where u.login=\'' . addslashes($_GET['player']) . '\'';
  } else {
  $page->Start('players&rsquo; discs - disc golf', 'players&rsquo; discs');
    $discs = '';
  }
  $discs = 'select c.id, c.color, c.mass, c.discid, d.name, u.login, c.status, c.comments from dgcaddy as c left join users as u on u.uid=c.uid left join dgdiscs as d on d.id=c.discid' . $discs . ' order by +c.status, d.name';
  if($discs = $db->GetSplit($discs, 20, 0, '', '', 'error looking up discs', 'no discs found')) {
    if(strlen($_GET['player']))
      $page->heading($_GET['player'] . '&rsquo;s discs');
    else
      $page->heading('players&rsquo; discs');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>color</th><th>mass</th><th>disc</th><?=strlen($_GET['player']) ? '' : '<th>player</th>'; ?><th>status</th><th>comments</th></tr></thead>
        <tbody>
<?
    while($disc = $discs->NextRecord()) {
      if(!strlen($disc->color))
        $disc->color = '<em>unknown</em>';
      $disc->comments = html_entity_decode(str_replace(array('\r', '\n'), '', strip_tags($disc->comments)), ENT_COMPAT, _CHARSET);
      if(strlen($disc->comments) > 22)
        $disc->comments = substr($disc->comments, 0, 20) . '...';
?>
          <tr><td><a href="?id=<?=$disc->id; ?>"><?=$disc->color; ?></a></td><td class="numeric"><?=$disc->mass; ?> g</td><td><a href="discs.php?id=<?=$disc->discid; ?>"><?=$disc->name; ?></a></td><?=strlen($_GET['player']) ? '' : '<td><a href="players.php?p=' . $disc->login . '">' . $disc->login . '</a></td>' ; ?><td><?=$disc->status; ?></td><td><?=$disc->comments; ?></td></tr>
<?
    }
?>
        </tbody>
      </table>
<?
    $page->SplitLinks();
    if(strlen($_GET['player'])) {
?>
      <ul><li><a href="players.php?p=<?=htmlentities($_GET['player'], ENT_COMPAT, _CHARSET); ?>"><?=htmlentities($_GET['player'], ENT_COMPAT, _CHARSET); ?>&rsquo;s player profile</a></li></ul>
<?
    }
  }
  $page->End();

  function getCaddyForm(&$db, $disc, $caddy = false) {
    require_once 'auForm.php';
    require_once 'auText.php';
    if($caddy)
      $f = new auForm('editdisc', '?id=' . $_GET['id'] . '&edit');
    else
      $f = new auForm('adddisc', '?id=new&disc=' . $disc->discid);
    $f->AddHTML('disc', '<a href="discs.php?id=' . $disc->discid . '">' . $disc->name . '</a>');
    $statuses = 'show columns from dgcaddy like \'status\'';
    if($statuses = $db->Get($statuses, 'error looking up status options', 'status options not found', true)) {
      $statuses = $statuses->NextRecord();
      $statuses = explode('\',\'', substr($statuses->Type, 6, -2));
    }
    $f->AddField('mass', 'mass (g)', 'enter the mass of your disc in grams', false, $disc->mass, _AU_FORM_FIELD_INTEGER, 3, 3);
    $f->AddField('color', 'color', 'enter the color of your disc', false, $disc->color, _AU_FORM_FIELD_NORMAL, 8, 16);
    $f->AddField('comments', 'comments', 'enter any comments you have on this disc', false, auText::HTML2BB($disc->comments), _AU_FORM_FIELD_BBCODE);
    $f->AddSelect('status', 'status', 'select the status of this disc', auFormSelect::ArrayIndex($statuses), $caddy ? $caddy->status : 'bag');
    $f->AddButtons('save', $caddy ? 'save changes to this disc' : 'add this disc');
    return $f;
  }
?>