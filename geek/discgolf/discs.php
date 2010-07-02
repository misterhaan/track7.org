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
        <li>this disc is not approved — <a href="?id=<?=$_GET['id']; ?>&amp;approve">approve</a> or <a href="?id=<?=$_GET['id']; ?>&amp;delete">delete</a></li>
<?
        } else {
?>
        <li>this disc has not yet been approved and may be spam or a duplicate — please try again later</li>
<?
        }
      } else {
?>
        <li><a id="messageloginlink" href="/user/login.php">log in</a> or <a href="/user/register.php">register</a> to add this disc to your bag</li>
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
        official numbers for speed, glide, turn, or fade — if anything is
        listed here it is likely a guess.
      </p>

<?
  if($user->Valid) {
?>
      <ul><li><a href="?id=new">add a disc</a></li></ul>
<?
  } else {
?>
      <ul><li><a id="messageloginlink" href="/user/login.php">log in</a> or <a href="/user/register.php">register</a> to add a disc</li></ul>
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
    $opt = ' <ul class="elements">';
    if($sort != 'popularity')
      $opt .= '<li><a href="' . $_SERVER['PHP_SELF'] . '">popularity</a></li>';
    foreach($sortopt as $val)
      $opt .= '<li><a href="?sort=' . $val . '">' . $val . '</a></li>';
    $opt .= '</ul>';
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
      $ret = '+' . $stable . ' — ';
    else
      $ret = $stable . ' — ';
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
?>
