<?
  define('MAX_SUGGEST', 8);

  $getvars = array('sort', 'id');
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if($_GET['return'] == 'suggest') {
    $count = 0;
    $mfgrs = 'select distinct mfgr from dgdiscs where mfgr like \'' . addslashes($_GET['match']) . '%\' order by mfgr';
    if($mfgrs = $db->Get($mfgrs, '', '', true))
      while($mfgr = $mfgrs->NextRecord()) {
        echo "\n" . $mfgr->mfgr;
        if(++$count >= MAX_SUGGEST)
          die("\n<more>");
      }
    $mfgrs = 'select distinct mfgr from dgdiscs where not mfgr like \'' . addslashes($_GET['match']) . '%\' and mfgr like \'%' . addslashes($_GET['match']) . '%\' order by mfgr';
    if($mfgrs = $db->Get($mfgrs, '', '', true))
      while($mfgr = $mfgrs->NextRecord()) {
        echo "\n" . $mfgr->mfgr;
        if(++$count >= MAX_SUGGEST)
          die("\n<more>");
      }
    if(!$count)
      die('<no matches>');
    die;
  }

  if($_GET['id'] == 'new') {
    $adddisc = getDiscForm($db);
    if($adddisc->CheckInput(true)) {
      $ins = 'insert into dgdiscs (name, mfgr, type, speed, glide, turn, fade) values (\'' . addslashes(htmlentities($_POST['name'], ENT_COMPAT, _CHARSET)) . '\', \'' . addslashes(htmlentities($_POST['manufacturer'], ENT_COMPAT, _CHARSET)) . '\', \'' . addslashes(htmlentities($_POST['type'], ENT_COMPAT, _CHARSET)) . '\', ' . intOrNull($_POST['speed']) . ', ' . intOrNull($_POST['glide']) . ', ' . intOrNull($_POST['turn']) . ', ' . intOrNull($_POST['fade']) . ')';
      if(false !== $discid = $db->Put($ins, 'error saving disc')) {
        header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?id=' . $discid);
        die;
      }
    }
    $page->Start('add disc - disc golf', 'add disc');
    showFormInstructions();
    $adddisc->WriteHTML(true);
    $page->ResetFlag(_FLAG_PAGES_COMMENTS);
    $page->End();
    die;
  }

  if(is_numeric($_GET['id'])) {
    $disc = 'select id, approved, mfgr, name, type, speed, glide, turn, fade, popularity from dgdiscs where id=\'' . addslashes($_GET['id']) . '\'';
    if($disc = $db->GetRecord($disc, 'error looking up disc information', 'disc not found', true)) {
      if($user->GodMode) {
        if(isset($_GET['approve'])) {
          $update = 'update dgdiscs set approved=\'yes\' where id=\'' . $_GET['id'] . '\'';
          if(false !== $db->Change($update, 'error approving disc')) {
            $page->Info('disc successfully marked approved');
            $disc->approved = 'yes';
          }
        }
        if(isset($_GET['edit'])) {
          $editdisc = getDiscForm($db, $disc);
          if($editdisc->CheckInput(true)) {
            $update = 'update dgdiscs set name=\'' . addslashes(htmlentities($_POST['name'], ENT_COMPAT, _CHARSET)) . '\', mfgr=\'' . addslashes(htmlentities($_POST['manufacturer'], ENT_COMPAT, _CHARSET)) . '\', type=\'' . addslashes(htmlentities($_POST['type'], ENT_COMPAT, _CHARSET)) . '\', speed=' . intOrNull($_POST['speed']) . ', glide=' . intOrNull($_POST['glide']) . ', turn=' . intOrNull($_POST['turn']) . ', fade=' . intOrNull($_POST['fade']) . ' where id=\'' . addslashes($_GET['id']) . '\'';
            if(false !== $db->Change($update, 'error updating disc')) {
              header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?id=' . $_GET['id']);
              die;
            }
          }
          $page->Start('edit ' . $disc->name . ' - disc golf', 'edit ' . $disc->name);
          showFormInstructions();
          $editdisc->WriteHTML(true);
          $page->ResetFlag(_FLAG_PAGES_COMMENTS);
          $page->End();
          die;
        }
        if(isset($_GET['delete'])) {
          $del = 'delete from dgdiscs where id=\'' . $_GET['id'] . '\'';
          if(false !== $db->Change($del, 'error deleting disc')) {
            $shift = 'update dgdiscs set id=id-1 where id>' . +$_GET['id'];
            if(false !== $db->Change($shift, 'error shifting disc ids down')) {
              $shift = 'alter table dgdiscs auto_increment=';
              $lastid = 'select max(id)+1 from dgdiscs';
              if($lastid = $db->GetValue($lastid, 'error looking up last disc id', ''))
                $db->Change($shift . +$lastid, 'error updating disc auto_increment');
              $shift = 'update dgcaddy set discid=discid-1 where discid>' . +$_GET['id'];
              if(false !==  $db->Change($shift, 'error shifting caddy disc ids down')) {
                $shift = 'update dgrounds set bestdisc=bestdisc-1 where bestdisc>' . +$_GET['id'];
                if(false !==  $db->Change($shift, 'error shifting round best disc ids down')) {
                  $shift = 'update dgrounds set worstdisc=worstdisc-1 where worstdisc>' . +$_GET['id'];
                  if(false !==  $db->Change($shift, 'error shifting round worst disc ids down')) {
                    header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
                    die;
                  }
                }
              }
            }
          }
        }
      }
      $page->Start($disc->name . ' - discs - disc golf', $disc->name, $disc->type . ' from ' . $disc->mfgr);
?>
      <table class="columns" cellspacing="0">
        <tr><th>disc name</th><td><?=$disc->name; ?></td></tr>
        <tr><th>disc type</th><td><?=$disc->type; ?></td></tr>
        <tr><th>manufacturer</th><td><?=$disc->mfgr; ?></td></tr>
<?
      if($disc->speed) {
?>
        <tr><th>speed rating</th><td><?=$disc->speed; ?> of 12</td></tr>
<?
      }
      if($disc->glide) {
?>
        <tr><th>glide rating</th><td><?=$disc->glide; ?> of 7</td></tr>
<?
      }
      if(is_numeric($disc->turn)) {
?>
        <tr><th>high-speed turn rating</th><td><?=stability($disc->turn); ?></td></tr>
<?
      }
      if(is_numeric($disc->fade)) {
?>
        <tr><th>low-speed fade rating</th><td><?=stability($disc->fade); ?></td></tr>
<?
      }
?>
      </table>

      <ul>
<?
      if($user->GodMode) {
?>
        <li><a href="?id=<?=$_GET['id']; ?>&amp;edit">edit this disc</a></li>
<?
      }
      if($user->Valid) {
        if($disc->approved == 'yes') {
?>
        <li><a href="caddy.php?id=new&amp;disc=<?=$_GET['id']; ?>">add a <?=$disc->name; ?> to your collection</a></li>
<?
        } elseif($user->GodMode) {
?>
        <li>this disc is not approved &mdash; <a href="?id=<?=$_GET['id']; ?>&amp;approve">approve</a> or <a href="?id=<?=$_GET['id']; ?>&amp;delete">delete</a></li>
<?
        } else {
?>
        <li>this disc has not yet been approved and may be spam or a duplicate &mdash; please try again later</li>
<?
        }
      } else {
?>
        <li><a href="/user/login.php">log in</a> or <a href="/user/register.php">register</a> to add this disc to your bag</li>
<?
      }
?>
      </ul>
<?
      $discs = 'select u.login, c.id, c.status, c.mass, c.color, c.comments from dgcaddy as c left join users as u on u.uid=c.uid where c.discid=\'' . addslashes($_GET['id']) . '\' order by c.status';
      if($discs = $db->Get($discs, 'error looking up players who have this disc', '')) {
        $page->Heading($disc->name . 's');
?>
      <table class="text" cellspacing="0">
        <thead><tr><th>color</th><th>mass</th><th>player</th><th>status</th><th>comments</th></tr></thead>
        <tbody>
<?
        while($disc = $discs->NextRecord()) {
          if(!strlen($disc->color))
            $disc->color = '<em>unknown</em>';
          $disc->comments = html_entity_decode(str_replace(array('\r', '\n'), '', strip_tags($disc->comments)), ENT_COMPAT, _CHARSET);
          if(strlen($disc->comments) > 22)
            $disc->comments = substr($disc->comments, 0, 20) . '...';
?>
          <tr><td><a href="caddy.php?id=<?=$disc->id; ?>"><?=$disc->color; ?></a></td><td><?=$disc->mass; ?> g</td><td><a href="players.php?p=<?=$disc->login; ?>"><?=$disc->login; ?></a></td><td><?=$disc->status; ?></td><td><?=htmlentities($disc->comments, ENT_COMPAT, _CHARSET); ?></td></tr>
<?
        }
?>
        </tbody>
      </table>
<?
      }
      $page->End();
      die;
    }
  }

  $page->ResetFlag(_FLAG_PAGES_COMMENTS);
  $page->Start('discs');
?>
      <p>
        below is a listing of the golf discs that have been entered into track7.&nbsp;
        please note that discs by manufacturers other than innova do not have
        official numbers for speed, glide, turn, or fade &mdash; if anything is
        listed here it is likely a guess.
      </p>

<?
  if($user->Valid) {
?>
      <ul><li><a href="?id=new">add a disc</a></li></ul>
<?
  } else {
?>
      <ul><li><a href="/user/login.php">log in</a> or <a href="/user/register.php">register</a> to add a disc</li></ul>
<?
  }
  switch($_GET['sort']) {
    case 'name':
      $discs = 'name';
      $sort = 'name';
      $sortopt = array('speed', 'glide', 'turn', 'fade');
      break;
    case 'speed':
      $discs = 'speed desc, name';
      $sort = 'speed';
      $sortopt = array('name', 'glide', 'turn', 'fade');
      break;
    case 'glide':
      $discs = 'glide desc, name';
      $sort = 'glide';
      $sortopt = array('name', 'speed', 'turn', 'fade');
      break;
    case 'turn':
      $discs = 'turn desc, name';
      $sort = 'turn';
      $sortopt = array('name', 'speed', 'glide', 'fade');
      break;
    case 'fade':
      $discs = 'fade desc, name';
      $sort = 'fade';
      $sortopt = array('name', 'speed', 'glide', 'turn');
      break;
    default:
      $discs = 'popularity desc, name';
      $sort = 'popularity';
      $sortopt = array('name', 'speed', 'glide', 'turn', 'fade');
  }
  $discs = 'select id, approved, mfgr, name, type, speed, glide, turn, fade, popularity from dgdiscs' . ($user->GodMode ? '' : ' where approved=\'yes\'') . ' order by ' . $discs;
  if($discs = $db->GetSplit($discs, 20, 0, '', '', 'error looking up list of discs', 'no discs found')) {
    $opt = ' <span class="options">[ ';
    if($sort != 'popularity')
      $opt .= '<a href="' . $_SERVER['PHP_SELF'] . '">popularity</a> | ';
    $first = true;
    foreach($sortopt as $val) {
      if(!$first)
        $opt .= ' | ';
      $opt .= '<a href="?sort=' . $val . '">' . $val . '</a>';
      $first = false;
    }
    $opt .= ' ]</span>';
    $page->Heading('discs by ' . $sort . $opt);
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>name</th><th>brand</th><th>type</th><th>speed</th><th>glide</th><th>turn</th><th>fade</th><th>in use</th><?=$user->GodMode ? '<th>new</th>' : ''; ?></tr></thead>
        <tbody>
<?
    while($disc = $discs->NextRecord()) {
?>
          <tr><td><a href="?id=<?=$disc->id; ?>"><?=$disc->name; ?></a></td><td><?=$disc->mfgr; ?></td><td><?=$disc->type; ?></td><td class="number"><?=$disc->speed; ?></td><td class="number"><?=$disc->glide; ?></td><td class="number"><?=($disc->turn > 0 ? '+' : '') . $disc->turn; ?></td><td class="number"><?=($disc->fade > 0 ? '+' : '') . $disc->fade; ?></td><td class="number"><?=$disc->popularity; ?></td><?=$user->GodMode ? '<td>' . ($disc->approved != 'yes' ? 'yes' : '') . '</td>' : ''; ?></tr>
<?
    }
?>
        </tbody>
      </table>
<?
    $page->SplitLinks();
  }
  $page->End();

  function stability($stable) {
    if(!is_numeric($stable))
      return '(unknown)';
    if($stable > 0)
      $ret = '+' . $stable . ' - ';
    else
      $ret = $stable . ' - ';
    switch(abs($stable)) {
      case 1: $ret .= 'least '; break;
      case 2: $ret .= 'slightly '; break;
      case 4: $ret .= 'very '; break;
      case 5: $ret .= 'most '; break;
    }
    if($stable > 0)
      $ret .= 'over';
    elseif($stable < 0)
      $ret .= 'under';
    return $ret . 'stable';
  }

  function getDiscForm(&$db, $disc = false) {
    require_once 'auForm.php';
    if($disc)
      $form = new auForm('editdisc', '?id=' . $disc->id . '&edit');
    else
      $form = new auForm('adddisc', '?id=new');
    $form->AddField('name', 'name', 'enter the name of this disc', true, $disc->name, _AU_FORM_FIELD_NORMAL, 16, 32);
    $form->AddField('manufacturer', 'manufacturer', 'enter who makes this disc', false, $disc->mfgr, _AU_FORM_FIELD_NORMAL, 16, 32);
    $form->AddSelect('type', 'type', 'choose the type of this disc', getDiscTypes($db), $disc->type);
    $form->AddSelect('speed', 'speed', 'choose the speed of this disc', array('' => '(unknown)', 1 => '1 - slowest', 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => '12 - fastest'), $disc->speed);
    $form->AddSelect('glide', 'glide', 'choose the glide of this disc', array('' => '(unknown)', 1 => '1 - least', 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => '7 - most'), $disc->glide);
    $form->AddSelect('turn', '(high-speed) turn', 'choose the turn (high speed) of this disc', getStableOptions(-5, 1), $disc->turn);
    $form->AddSelect('fade', '(low-speed) fade', 'choose the fade (low speed) of this disc', getStableOptions(0, 5), $disc->fade);
    if($disc)
      $form->AddButtons('save', 'save changes to this disc');
    else
      $form->AddButtons('add', 'request that this disc get added');
    return $form;
  }

  function getDiscTypes(&$db) {
    $types = 'show columns from dgdiscs like \'type\'';
    if($types = $db->Get($types, 'error reading disc types')) {
      $types = $types->NextRecord();
      $types = substr($types->Type, 6, -2);
      $types = explode('\',\'', $types);
    }
    return auFormSelect::ArrayIndex($types);
  }

  function getStableOptions($low, $high, $unknown = true) {
    if($unknown)
      $ret[''] = '(unknown)';
    for($i = $low; $i <= $high; $i++)
      $ret[$i] = stability($i);
    return $ret;
  }

  function showFormInstructions() {
?>
      <p>
        name is required and manufacturer is extremely helpful, and anything
        else you can add is also useful.&nbsp; speed, glide, turn, and fade
        are all numeric (the following definitions come from
        <a href="http://www.innovadiscs.com/">innovadiscs.com</a>):&nbsp;
        speed (1-10) is how quickly a disc cuts through the air, where 12 is
        the fastest.&nbsp; glide (1-7) is how much carry or float a disc has
        where 7 is the most.&nbsp; high speed turn is rated from -5 (very
        understable) to +1 (slightly overstable).&nbsp; low speed fade is
        rated from 0 (stable) to +5 (very overstable).
      </p>
<?
  }

  function intOrNull($val) {
    if(is_numeric($val))
      return $val;
    return 'null';
  }

  die;  //--------------------------------------------------- old script follows

  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auText.php';
  require_once 'auForm.php';

  if(isset($_GET['id']) && is_numeric($_GET['id']) && strpos($_GET['id'], '.') === false) {
    $disc = 'select name, mfgr, type, speed, glide, turn, fade from dgdiscs where id=' . $_GET['id'];
    if($disc = $db->GetRecord($disc, 'error reading disc information from the database', 'could not find disc ' . $_GET['id'] . ' -- please find the disc below and click on its name.&nbsp; if it isn\'t in the list, you can request that it be added.', true)) {
      $page->Start($disc->name . ' - discs', $disc->name, $disc->mfgr);
      if(isset($_GET['caddy']) && is_numeric($_GET['caddy']) && strpos($_GET['caddy'], '.') === false) {
        $caddy = 'select users.login, dgcaddy.status, dgcaddy.mass, dgcaddy.color, dgcaddy.comments, dgcaddy.uid from users, dgcaddy where users.uid=dgcaddy.uid and dgcaddy.id=' . $_GET['caddy'] . ' and dgcaddy.discid=' . $_GET['id'];
        if($caddy = $db->GetRecord($caddy, 'error reading specific disc information', 'could not find a ' . $disc->name . ' with id ' . $_GET['caddy'], true)) {
          if(isset($_GET['edit']) && ($user->Valid && $user->ID == $caddy->uid || $user->GodMode)) {
            if(isset($_POST['submit'])) {
              unset($_POST['submit']);  // don't add another disc to the bag accidentally
              if(strlen($_POST['mass']) > 0 && (!is_numeric($_POST['mass']) || strpos($_POST['mass'], '.') !== false || $_POST['mass'] < 100 || $_POST['mass'] > 200))
                $page->Error('mass must be a number between 100 and 200 (no decimal places)');
              elseif($_POST['status'] != 'bag' && $_POST['status'] != 'lost' && $_POST['status'] != 'sold')
                $page->Error('status must be either bag, lost, or sold -- please use the official track7 form');
              else {
                $update = 'update dgcaddy set comments=\'' . addslashes(auText::BB2HTML($_POST['comments'])) . '\', color=\'' . addslashes(htmlspecialchars($_POST['color'])) . '\', mass=' . $_POST['mass'] . ', status=\'' . $_POST['status'] . '\' where id=' . $_GET['caddy'];
                if(false !== $db->Change($update, 'error updating your disc')) {
                  $page->Info('disc updated successfully');
                  if($caddy->status == 'bag' && $_POST['status'] != 'bag') {
                    $db->Change('update dgdiscs set popularity=popularity-1 where id=' . $_GET['id']);
                    $db->Change('update userstats set discs=discs-1 where uid=' . $user->ID);
                  }
                  // update the caddy object since the row was just updated
                  $caddy->comments = auText::BB2HTML($_POST['comments']);
                  $caddy->color = $_POST['color'];
                  $caddy->mass = $_POST['mass'];
                  $caddy->status = $_POST['status'];
                }
              }
            }
?>
      <h2>your comments</h2>
<?
            $editcaddy = new auForm('editcaddy', '?id=' . $_GET['id'] . '&caddy=' . $_GET['caddy'] . '&edit');
            $editcaddy->AddField('comments', 'comments', 'enter any comments you have on this disc', false, auText::HTML2BB($caddy->comments), _AU_FORM_FIELD_BBCODE);
            $editcaddy->AddField('color', 'color', 'enter the color of your disc', false, $caddy->color, _AU_FORM_FIELD_NORMAL, 8, 16);
            $editcaddy->AddField('mass', 'mass', 'enter the mass of your disc in grams', false, $caddy->mass, _AU_FORM_FIELD_INTEGER, 3, 3);
            $editcaddy->AddSelect('status', 'status', 'choose the status of your disc', auFormSelect::ArrayIndex(array('bag', 'lost', 'sold')), $caddy->status);
            $editcaddy->AddButtons('update', 'update this disc');
            $editcaddy->WriteHTML(true);
          } else {
?>
      <h2>comments from <?=$caddy->login; ?></h2>
      <p>
        <?=$caddy->comments; ?>
      </p>
      <p>
        color:&nbsp; <?=$caddy->color; ?><br />
        mass:&nbsp; <?=$caddy->mass; ?> g<br />
        status:&nbsp; <?=$caddy->status; ?>
      </p>

<?
            if($user->Valid && $user->ID == $caddy->uid || $user->GodMode) {
?>
      <p><a href="?id=<?=$_GET['id']; ?>&amp;caddy=<?=$_GET['caddy']; ?>&amp;edit">edit this disc</a></p>

<?
            }
          }
        }
      }
      if(isset($_POST['submit']) && $user->Valid) {
        if(strlen($_POST['mass']) > 0 && (!is_numeric($_POST['mass']) || strpos($_POST['mass'], '.') !== false || $_POST['mass'] < 100 || $_POST['mass'] > 200))
          $page->Error('mass must be a number between 100 and 200 (no decimal places)');
        else {
          $ins = 'insert into dgcaddy (uid, discid, mass, color, comments) values (' . $user->ID. ', ' . $_GET['id'] . ', ' . $_POST['mass'] . ', \'' . addslashes(htmlspecialchars($_POST['color'])) . '\', \'' . addslashes(auText::BB2HTML($_POST['comments'])) . '\')';
          if(false !== $db->Put($ins, 'error adding disc to your bag')) {
            $update = 'update dgdiscs set popularity=popularity+1 where id=' . $_GET['id'];
            $db->Change($update);
            $update = 'update userstats set discs=discs+1 where uid=' . $user->ID;
            $db->Change($update);
            $page->Info('disc successfully added to your bag');
          }
        }
      }
?>
      <ul>
        <li><?=$disc->type; ?></li>
        <li>speed rating:&nbsp; <?=$disc->speed; ?> of 10</li>
        <li>glide rating:&nbsp; <?=$disc->glide; ?> of 7</li>
        <li>high speed turn rating:&nbsp; <?=stability($disc->turn); ?></li>
        <li>low speed fade rating:&nbsp; <?=stability($disc->fade); ?></li>
      </ul>

<?
      $discs = 'select dgcaddy.id, users.login as player, dgcaddy.status, dgcaddy.mass, dgcaddy.color, dgcaddy.comments from dgcaddy, users where dgcaddy.uid=users.uid and dgcaddy.discid=' . $_GET['id'] . ' order by +status';
      if($discs = $db->Get($discs, 'error finding out who is carrying this disc', 'nobody has this disc yet' . ($user->Valid ? '.&nbsp; if you have one of these, fill out the form below' : ''))) {
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>player</th><th>status</th><th>mass</th><th>color</th><th>comments</th></tr></thead>
        <tbody>
<?
        while($d = $discs->NextRecord()) {
          $d->comments = str_replace(array('<br />', "\n", '&nbsp;', '</p><p>'), ' ', $d->comments);
          if(strlen($d->comments) < 1)
            $d->comments = '<em>[none]</em>';
?>
          <tr><td><a href="/user/<?=$d->player; ?>/" title="view <?=$d->player; ?>'s profile"><?=$d->player; ?></a></td><td><?=$d->status; ?></td><td><?=$d->mass; ?> g</td><td><?=$d->color; ?></td><td><a href="?id=<?=$_GET['id']; ?>&amp;caddy=<?=$d->id; ?>" title="view <?=$d->player; ?>'s comments on this disc"><?=(strlen($d->comments) > 27 ? substr($d->comments, 0, 25) . '...' : $d->comments); ?></a></td></tr>
<?
        }
?>
        </tbody>
      </table>

<?
      }
      if($user->Valid) {
        if(isset($caddy))
          echo '      <ul><li><a href="?id=' . $_GET['id'] . '">back to the ' . $disc->name . ' page</a></li></ul>' . "\n";
        else {
?>

      <h2>add this disc to your bag</h2>
      <p class="instructor">
        use this form to add a <?=$disc->name; ?> to your bag.&nbsp; you may
        add a <?=$disc->name; ?> even if you already have one.<br />
        mass is usually somewhere between 150 g and 175 g, though larger discs
        are allowed to be heavier.<br />
        you may enter anything you want (up to 16 characters) for the color.&nbsp;
        generally values will be something like 'red' or 'yellow.'<br />
        use the comments for whatever you want to remember about this disc.&nbsp;
        some examples include what you use the disc for, when you got it, or
        where you found it.
      </p>
<?
          $adddisc = new auForm('adddisc', '?id=' . $_GET['id']);
          $adddisc->AddField('mass', 'mass', 'enter the mass of your disc in grams', false, '', _AU_FORM_FIELD_INTEGER, 3, 3);
          $adddisc->AddField('color', 'color', 'enter the color of your disc', false, '', _AU_FORM_FIELD_NORMAL, 8, 16);
          $adddisc->AddField('comments', 'comments', 'enter any comments you have on this disc', false, '', _AU_FORM_FIELD_BBCODE);
          $adddisc->AddButtons('add', 'add this disc to your bag');
          $adddisc->WriteHTML(true);
        }
      } else {
?>
      <p><a href="/user/login.php">log in</a> to add this disc to your bag</p>

<?
      }
      $page->End();
      die;
    }
  }
  if(isset($_POST['submit'])) {
    if(strlen($_POST['name']) < 1)
      $page->Error('please enter the name of this disc--i can\'t do much without a name!');
    elseif($_POST['type'] != 'distance driver' && $_POST['type'] != 'fairway driver' && $_POST['type'] != 'multi-purpose' && $_POST['type'] != 'putt / approach' && $_POST['type'] != 'specialty')
      $page->Error('invalid disc type -- please use the official track7 form');
    elseif(strlen($_POST['speed']) > 0 && (!is_numeric($_POST['speed']) || strpos($_POST['speed'], '.') !== false || $_POST['speed'] < 1 || $_POST['speed'] > 10))
      $page->Error('speed must be left blank or be a number between 1 and 10 (no decimal places) -- please use the official track7 form');
    elseif(strlen($_POST['glide']) > 0 && (!is_numeric($_POST['glide']) || strpos($_POST['glide'], '.') !== false || $_POST['glide'] < 1 || $_POST['glide'] > 7))
      $page->Error('glide must be left blank or be a number between 1 and 7 (no decimal places) -- please use the official track7 form');
    elseif(strlen($_POST['turn']) > 0 && (!is_numeric($_POST['turn']) || strpos($_POST['turn'], '.') !== false || $_POST['turn'] < -3 || $_POST['turn'] > 1))
      $page->Error('turn must be left blank or be a number between -3 and 1 (no decimal places) -- please use the official track7 form');
    elseif(strlen($_POST['fade']) > 0 && (!is_numeric($_POST['fade']) || strpos($_POST['fade'], '.') !== false || $_POST['fade'] < 0 || $_POST['fade'] > 3))
      $page->Error('fade must be left blank or be a number between 0 and 3 (no decimal places) -- please use the official track7 form');
    elseif($user->GodMode) {
      $ins = 'insert into dgdiscs (mfgr, name, type, speed, glide, turn, fade) values (\'' . addslashes($_POST['mfgr']) . '\', \'' . addslashes($_POST['name']) . '\', \'' . $_POST['type'] . '\', ' . (strlen($_POST['speed']) > 0 ? $_POST['speed'] : 'null') . ', ' . (strlen($_POST['glide']) > 0 ? $_POST['glide'] : 'null') . ', ' . (strlen($_POST['turn']) > 0 ? $_POST['turn'] : 'null') . ', ' . (strlen($_POST['fade']) > 0 ? $_POST['fade'] : 'null') . ')';
      if(false !== $db->Put($ins, 'error inserting new disc into database'))
        $page->Info('new disc successfully added to database');
    } else {
      $subject = 'add disc - ';
      if($user->Valid)
        $subject .= $user->Name;
      else
        $subject .= 'somebody';
      $message = 'name:  ' . $_POST['name'] . "\n"
               . 'mfgr:  ' . $_POST['mfgr'] . "\n"
               . 'type:  ' . $_POST['type'] . "\n"
               . 'speed: ' . $_POST['speed'] . "\n"
               . 'glide: ' . $_POST['glide'] . "\n"
               . 'turn: ' . $_POST['turn'] . "\n"
               . 'fade: ' . $_POST['fade'];
      @mail('misterhaan@' . _HOST, $subject, $message, 'From: disc golf <dgolf@' . _HOST . ">\r\nX-Mailer: PHP/" . phpversion() . "\r\n");
      $page->Info('request to add new disc sent successfully.&nbsp; if approved, it will probably show up here in a few days.');
    }
  }
  $page->Start('discs');
?>
      <p>
        below is a listing of golf discs that are currently in the system.&nbsp;
        please note that discs by manufacturers other than innova do not have
        official numbers for speed, glide, turn, or fade -- if anything is
        listed here it is either my guess or the guess of the person who
        requested that i add the disc.&nbsp; if you use a disc that is not on
        this list, use the form at the bottom of this page to request that it be
        added.<br />
        to add a disc to your bag, click on the disc name and use the form on
        that page.
      </p>

<?
  $discs = 'select id, mfgr, name, `type`, speed, glide, turn, fade, popularity from dgdiscs order by popularity desc, name';
  if($discs = $db->GetSplit($discs, 20, '', '', '', 'error reading discs', 'there are currently no discs in the database')) {
?>
      <h2>discs</h2>
      <table class="data" id="golfdiscs" cellspacing="0">
        <thead><tr><th>name</th><th>brand</th><th>type</th><th>speed</th><th>glide</th><th>turn</th><th>fade</th><th>in use</th></tr></thead>
        <tbody>
<?
    while($disc = $discs->NextRecord()) {
?>
          <tr><td><a href="?id=<?=$disc->id; ?>" title="view details for this disc"><?=$disc->name; ?></a></td><td><?=$disc->mfgr; ?></td><td><?=$disc->type; ?></td><td><?=$disc->speed; ?></td><td><?=$disc->glide; ?></td><td><?=($disc->turn === null ? '' : str_pad($disc->turn, 2, '+', STR_PAD_LEFT)); ?></td><td><?=($disc->fade === null ? '' : str_pad($disc->fade, 2, '+', STR_PAD_LEFT)); ?></td><td><?=$disc->popularity; ?></td></tr>
<?
    }
?>
        </tbody>
      </table>

<?
    $page->SplitLinks();
  }
?>

      <h2>add a disc</h2>
      <p>
        use this form to request that a disc be added to track7's database.&nbsp;
        name is required and manufacturer is extremely helpful, and anything
        else you can add is also useful.&nbsp; speed, glide, turn, and fade
        are all numeric (the following definitions come from
        <a href="http://www.innovadiscs.com/">innovadiscs.com</a>):&nbsp;
        speed (1-10) is how quickly a disc cuts through the air, where 10 is
        the fastest.&nbsp; glide (1-7) is how much carry or float a disc has
        where 7 is the most.&nbsp; high speed turn is rated from -3 (very
        understable) to +1 (slightly overstable).&nbsp; low speed fade is
        rated from 0 (stable) to +3 (very overstable).
      </p>
<?
  $newdisc = new auForm('newdisc');
  $newdisc->AddField('name', 'name', 'enter the name of this disc', true, '', _AU_FORM_FIELD_NORMAL, 20, 32);
  $newdisc->AddField('mfgr', 'manufacturer', 'enter who makes this disc', false, '', _AU_FORM_FIELD_NORMAL, 20, 32);
  $newdisc->AddSelect('type', 'type', 'choose which type of disc this is', auFormSelect::ArrayIndex(array('distance driver', 'fairway driver', 'multi-purpose', 'putt / approach', 'specialty')));
  $newdisc->AddSelect('speed', 'speed', 'choose the speed for this disc', array('' => '(unknown)', 1 => '1 - slowest', 2 => null, 3 => null, 4 => null, 5 => null, 6 => null, 7 => null, 8 => null, 9 => null, 10 => '10 - fastest'));
  $newdisc->AddSelect('glide', 'glide', 'choose the glide for this disc', array('(unknown)' => null, 1 => '1 - least', 2 => null, 3 => null, 4 => null, 5 => null, 6 => null, 7 => '7 - most'));
  $newdisc->AddSelect('turn', '(high speed) turn', 'choose the turn (high speed) for this disc', array('' => '(unknown)', -3 => '-3 - very understable', -2 => '-2 - understable', -1 => '-1 - slightly understable', 0 => ' 0 - stable', 1 => '+1 - slightly overstable'));
  $newdisc->AddSelect('fade', '(low speed) fade', 'choose the fade (low speed) for this disc', array('' => '(unknown)', 0 => ' 0 - stable', 1 => '+1 - slightly overstable', 2 => '+2 - overstable', 3 => '+3 - very overstable'));
  $newdisc->AddButtons('add', 'add this disc');
  $newdisc->WriteHTML($user->Valid);

  $page->End();
?>
