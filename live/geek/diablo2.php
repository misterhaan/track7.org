<?
/*
 1    expansion character
 2    harcore character
80    bastard sword clan memeber

      1    "den of evil",
      2    "sisters' burial grounds",
      4    "the search for cain",
      8    "the forgotten tower",
     10    "tools of the trade",
     20    "sisters to the slaughter",
0x3f = 63
     40    "radament's lair",
     80    "the horadric staff",
    100    "the tainted sun",
    200    "the arcane sanctuary",
    400    "the summoner",
    800    "the seven tombs",
0xfff = 4095
   1000    "the golden bird",
   2000    "blade of the old religion",
   4000    "khalim's will",
   8000    "lam esen's tome",
  10000    "the blackened temple",
  20000    "the guardian",
0x3ffff = 262143
  40000    "the fallen angel",
  80000    "the hellforge",
 100000    "terror's end",
0x1fffff = 2097151
 200000    "siege on harrogath",
 400000    "rescue on mount arreat",
 800000    "prison of ice",
1000000    "betrayal of harrogath",
2000000    "rite of passage",
4000000    "eve of destruction"
*/

  $questname = array(
    'den of evil',
    'sisters\' burial grounds',
    'the search for cain',
    'the forgotten tower',
    'tools of the trade',
    'sisters to the slaughter',
    'radament\'s lair',
    'the horadric staff',
    'the tainted sun',
    'the arcane sanctuary',
    'the summoner',
    'the seven tombs',
    'the golden bird',
    'blade of the old religion',
    'khalim\'s will',
    'lam esen\'s tome',
    'the blackened temple',
    'the guardian',
    'the fallen angel',
    'the hellforge',
    'terror\'s end',
    'siege on harrogath',
    'rescue on mount arreat',
    'prison of ice',
    'betrayal of harrogath',
    'rite of passage',
    'eve of destruction'
  );

  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';

  if(is_numeric($_GET['char']) || isset($_GET['new'])) {
    if($user->Valid && (isset($_GET['edit']) || isset($_GET['new']))) {
      if(isset($_GET['edit'])) {
        $char = 'select name, difficulty, class, quests, flags, level from diablo2chars where id=' . $_GET['char'];
        if(!$user->GodMode)
          $char .= ' and owner=' . $user->ID;
        if(false === $char = $db->GetRecord($char, 'error reading character information', 'character not found or does not belong to you', true)) {
          $page->Start('edit character');
          $page->End();
          die;
        }
      }
      if(isset($char))
        $page->Start($char->name . ' - diablo ii characters', title($char->difficulty, isMale($char->class), $char->quests, $char->flags) . $char->name, 'diablo ii character');
      else
        $page->Start('new character - diablo ii characters', 'new character', 'diablo ii character');
      $charform = new auForm('char');
      $charvitals = new auFormFieldSet('vitals');
      $charvitals->AddField('name', 'name', 'enter your character\'s name', true, $char->name, _AU_FORM_FIELD_NORMAL, 10, 32);
      $charvitals->AddSelect('class', 'class', 'choose your character\'s class', auFormSelect::ArrayIndex(array('necromancer', 'druid', 'barbarian', 'paladin', 'sorceress', 'assassin', 'amazon')), $char->class);
      $charvitals->AddField('expansion', 'expansion', 'this character is an expansion character', true, $char->flags & 1, _AU_FORM_FIELD_CHECKBOX);
      $charvitals->AddField('hardcore', 'hardcore', 'this character is a hardcore character', true, $char->flags & 2, _AU_FORM_FIELD_CHECKBOX);
      $charvitals->AddField('level', 'level', 'enter your character\'s current level', true, $char->level, _AU_FORM_FIELD_NORMAL, 2, 3);
      $charvitals->AddSelect('difficulty', 'difficulty', 'choose the difficulty this character is playing', auFormSelect::ArrayIndex(array('normal', 'nightmare', 'hell')), $char->difficulty);
      $charform->AddFieldSet($charvitals);
      $charquests = new auFormFieldSet('quests');
      $defaults = array();
      for($chk = 1; $chk <= 0x20; $chk << 1)
        if(+$char->quests & $chk)
          $defaults[] = $chk;
      $charquests->AddMultiSelect('quest1', 'act i', 'mark the quests your character has completed in act i', array(1 => $questname[0], 2 => $questname[1], 4 => $questname[2], 8 => $questname[3], 0x10 => $questname[4], 0x20 => $questname[5]), $defaults);
      $defaults = array();
      for($chk = 0x40; $chk <= 0x800; $chk << 1)
        if(+$char->quests & $chk)
          $defaults[] = $chk;
      $charquests->AddMultiSelect('quest2', 'act ii', 'mark the quests your character has completed in act ii', array(0x40 => $questname[6], 0x80 => $questname[7], 0x100 => $questname[8], 0x200 => $questname[9], 0x400 => $questname[10], 0x800 => $questname[11]), $defaults);
      $defaults = array();
      for($chk = 0x1000; $chk <= 0x20000; $chk << 1)
        if(+$char->quests & $chk)
          $defaults[] = $chk;
      $charquests->AddMultiSelect('quest3', 'act iii', 'mark the quests your character has completed in act iii', array(0x1000 => $questname[12], 0x2000 => $questname[13], 0x4000 => $questname[14], 0x8000 => $questname[15], 0x10000 => $questname[16], 0x20000 => $questname[17]), $defaults);
      $defaults = array();
      for($chk = 0x40000; $chk <= 0x100000; $chk << 1)
        if(+$char->quests & $chk)
          $defaults[] = $chk;
      $charquests->AddMultiSelect('quest4', 'act iv', 'mark the quests your character has completed in act iv', array(0x40000 => $questname[18], 0x80000 => $questname[19], 0x100000 => $questname[20]), $defaults);
      $defaults = array();
      for($chk = 0x200000; $chk <= 0x4000000; $chk << 1)
        if(+$char->quests & $chk)
          $defaults[] = $chk;
      $charquests->AddMultiSelect('quest5', 'act v', 'mark the quests your character has completed in act v', array(0x200000 => $questname[21], 0x400000 => $questname[22], 0x800000 => $questname[23], 0x1000000 => $questname[24], 0x2000000 => $questname[25], 0x4000000 => $questname[26]), $defaults);
      $charform->AddFieldSet($charquests);
      $charform->AddButtons('save', (isset($char) ? 'update this character' : 'add this character'));
      $charform->WriteHTML(true);

      $page->End();
      die;
    }
    $char = 'select c.name, c.difficulty, c.class, c.quests, c.flags, c.level, u.login as ownername from diablo2chars as c, users as u where c.id=' . $_GET['char'] . ' and c.owner=u.uid';
    if($char = $db->GetRecord($char, 'error reading character information', 'character not found', true)) {
      $page->Start($char->name . ' - diablo ii characters', title($char->difficulty, isMale($char->class), $char->quests, $char->flags) . $char->name, 'diablo ii character');
?>
      <h2>vitals</h2>
<?
      if(file_exists('diablo2/' . $char->ownername . '-' . $char->name . '.png'))
        echo '      <img id="d2charpic" src="diablo2/' . $char->ownername . '-' . $char->name . '.png" alt="" />' . "\n";
      else
        echo '      <img id="d2charpic" src="diablo2/' . $char->class . '.png" alt="" />' . "\n";
?>
      <p>
        played by:&nbsp; <a href="<?=$_SERVER['PHP_SELF']; ?>?player=<?=$char->ownername; ?>"><?=$char->ownername; ?></a><br />
        level <?=$char->level; ?> <?=$char->class; ?><br />
<?
      if($char->flags & 1)
        echo '        expansion character' . "\n";
?>
      </p>

      <h2>quests completed - <?=$char->difficulty; ?> difficulty</h2>
      <div class="d2quests">
<?
      if($char->quests == 0)
        echo '        <ul><li><em>(none)</em></li></ul>' . "\n";
      else {
        if($char->quests & 0x3f) {
          echo '        <h3>act i</h3>' . "\n"
             . '        <ul>' . "\n";
          for($q = 0; $q < 6; $q++)
            if($char->quests & (1 << $q))
              echo '          <li>' . $questname[$q] . '</li>' . "\n";
          echo '          </ul>' . "\n";
        }
        if($char->quests & 0xfc0) {
          echo '        <h3>act ii</h3>' . "\n"
             . '          <ul>' . "\n";
          for($q = 6; $q < 12; $q++)
            if($char->quests & (1 << $q))
              echo '          <li>' . $questname[$q] . '</li>' . "\n";
          echo '        </ul>' . "\n";
        }
        if($char->quests & 0x3f000) {
          echo '        <h3>act iii</h3>' . "\n"
             . '          <ul>' . "\n";
          for($q = 12; $q < 18; $q++)
            if($char->quests & (1 << $q))
              echo '          <li>' . $questname[$q] . '</li>' . "\n";
          echo '          </ul>' . "\n";
        }
        if($char->quests & 0x1c0000) {
          echo '        <h3>act iv</h3>' . "\n"
             . '          <ul>' . "\n";
          for($q = 18; $q < 21; $q++)
            if($char->quests & (1 << $q))
              echo '          <li>' . $questname[$q] . '</li>' . "\n";
          echo '          </ul>' . "\n";
        }
        if($char->quests & 0x7e00000) {
          echo '        <h3>act v</h3>' . "\n"
             . '          <ul>' . "\n";
          for($q = 21; $q < 27; $q++)
            if($char->quests & (1 << $q))
              echo '          <li>' . $questname[$q] . '</li>' . "\n";
          echo '          </ul>' . "\n";
        }
      }
?>
      </div>
<?
    }
  } elseif(isset($_GET['player'])) {
    $uid = 'select login, uid from users where login=\'' . addslashes($_GET['player']) . '\'';
    if($uid = $db->GetRecord($uid, 'error looking up user id for player ' . htmlspecialchars($_GET['player']), 'player ' . htmlspecialchars($_GET['player']) . ' does not seem to exist!', true)) {
      $_GET['player'] = $uid->login;
      $uid = $uid->uid;
      $page->Start($_GET['player'] . ' - diablo ii players', $_GET['player'], 'diablo ii player');
?>
      <p>
        <?=$_GET['player']; ?> has the following diablo ii characters.&nbsp;
        you may also want to check out
        <a href="/user/<?=$_GET['player']; ?>/"><?=$_GET['player']; ?>'s profile</a>
        for contact (and other) information.
      </p>

<?
      $chars = 'select id, difficulty, class, quests, flags, name, level, act from diablo2chars where owner=' . $uid . ' order by level desc';
      if($chars = $db->Get($chars, 'error reading characters', 'there are currently no diablo ii characters for this player')) {
?>
      <table class="text" cellspacing="0">
        <thead><tr><th>name</th><th>class</th><th>level</th><th>difficulty</th><th>act</th></tr></thead>
        <tbody>
<?
        while($char = $chars->NextRecord())
          echo '          <tr><td>'
             . '<a href="' . $_SERVER['PHP_SELF'] . '?char=' . $char->id . '">' . title($char->difficulty, isMale($char->class), $char->quests, $char->flags) . $char->name . '</a></td><td>'
             . $char->class . '</td><td class="number">'
             . $char->level . '</td><td>'
             . $char->difficulty . '</td><td class="number">'
             . $char->act . '</td></tr>' . "\n";
?>
        </tbody>
      </table>

<?
      }
    } else
      $page->Start('(unknown diablo ii player)');
  } else {
    $page->Start('diablo ii characters');
?>
      <p>
        this page exists partly to show my diablo ii characters and where they
        are in the game, and partly to let other people post and update their
        characters (eventually maybe).&nbsp; it's possible that this page could
        even be used to arrange multiplayer games between people who have posted
        their characters.&nbsp; for now it's just something fun, but if it gets
        hits i will probably add more to it!
      </p>

<?
    $chars = 'select c.id, c.difficulty, c.class, c.quests, c.flags, c.name, c.level, c.act, u.login as ownername from diablo2chars as c, users as u where c.owner=u.uid order by level desc';
    if($chars = $db->Get($chars, 'error reading characters', 'there are currently no diablo ii characters in the system')) {
?>
      <table class="text" cellspacing="0">
        <thead><tr><th>name</th><th>class</th><th>level</th><th>difficutly</th><th>act</th><th>player</th></tr></thead>
        <tbody>
<?
      while($char = $chars->NextRecord())
        echo '          <tr><td>'
           . '<a href="' . $_SERVER['PHP_SELF'] . '?char=' . $char->id . '">' . title($char->difficulty, isMale($char->class), $char->quests, $char->flags) . $char->name . '</a></td><td>'
           . $char->class . '</td><td class="number">'
           . $char->level . '</td><td>'
           . $char->difficulty . '</td><td class="number">'
           . $char->act . '</td><td>'
           . '<a href="' . $_SERVER['PHP_SELF'] . '?player=' . $char->ownername . '">' . $char->ownername . '</a></td></tr>' . "\n";
?>
        </tbody>
      </table>

<?
    }
  }
  $page->End();

  //-------------------------------------------------------------[functions]--//
  function isMale($class) {
    switch($class) {
      case 'necromancer':
      case 'druid':
      case 'barbarian':
      case 'paladin':
        return true;
        break;
      default:
        return false;
        break;
    }
  }
  
  function title($diff, $male, $quests, $flags) {
    if($flags & 1) { //expansion character
      if($flags & 2) { //hardcore character
        if($quests & 0x4000000) { //defeated baal
          return $diff == 'normal' ? 'destroyer ' : ($diff == 'nightmare' ? 'conqueror ' : 'guardian ');
        } else { //not defeated baal
          return $diff == 'normal' ? '' : ($diff = 'nightmare' ? 'destroyer ' : 'conqueror ');
        }
      } else { //not hardcore
        if($quests & 0x4000000) { //defeated baal
          return $diff == 'normal' ? 'slayer ' : ($diff == 'nightmare' ? 'champion ' : ($male ? 'patriarch ' : 'matriarch '));
        } else { //not defeated baal
          return $diff == 'normal' ? '' : ($diff == 'nightmare' ? 'slayer ' : 'champion ');
        }
      }
    } else { //not expansion
      if($male) {
        if($flags & 2) { //hardcore character
          if($quests & 0x100000) { //defeated diablo
            return $diff == 'normal' ? 'count ' : ($diff == 'nightmare' ? 'duke ' : 'king ');
          } else { //not defeated diablo
            return $diff == 'normal' ? '' : ($diff == 'nightmare' ? 'count ' : 'duke ');
          }
        } else { //not hardcore
          if($quests & 0x100000) { //defeated diablo
            return $diff == 'normal' ? 'sir ' : ($diff == 'nightmare' ? 'lord ' : 'baron ');
          } else { //not defeated diablo
            return $diff == 'normal' ? '' : ($diff == 'nightmare' ? 'sir ' : 'lord ');
          }
        }
      } else { //female
        if($flags & 2) { //hardcore character
          if($quests & 0x100000) { //defeated diablo
            return $diff == 'normal' ? 'countess ' : ($diff == 'nightmare' ? 'duchess ' : 'queen ');
          } else { //not defeated diablo
            return $diff == 'normal' ? '' : ($diff == 'nightmare' ? 'countess ' : 'duchess ');
          }
        } else { //not hardcore
          if($quests & 0x100000) { //defeated diablo
            return $diff == 'normal' ? 'dame ' : ($diff == 'nightmare' ? 'lady ' : 'baroness ');
          } else { //not defeated diablo
            return $diff == 'normal' ? '' : ($diff == 'nightmare' ? 'dame ':'lady ');
          }
        }
      }
    }
  }
?>
