<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  
  if(is_numeric($_GET['id'])) {
    $char = 'select c.id, c.uid, c.name, c.game, c.class, c.level, g.name as gamename, b.name as gamebase, cl.name as classname from rpgchars as c left join rpgames as g on g.id=c.game left join rpgames as b on b.id=g.expansionbase left join rpgclasses as cl on cl.id=c.class where c.id=\'' . addslashes($_GET['id']) . '\'';
    if($char = $db->GetRecord($char, 'error looking up character', 'character not found', true)) {
      if(isset($_GET['edit']) && $user->ID == $char->uid) {
        $charentry = getCharForm($db, $char);
        if($charentry->CheckInput(true)) {
          if(verifyClass($db, $page, $_POST['class'], $_POST['game'])) {
            $update = 'update rpgchars set name=\'' . addslashes(htmlspecialchars($_POST['name'])) . '\', class=\'' . +$_POST['class'] . '\', game=\'' . +$_POST['game'] . '\', level=\'' . +$_POST['level'] . '\' where id=\'' . $char->id . '\'';
            if(false !== $db->Change($update, 'error saving rpg character')) {
              $db->Put('insert into rpghistory (`char`, instant, level) values (\'' . $char->id . '\', \'' . time() . '\', \'' . +$_POST['level'] . '\')');
              header('Location: http://' . $_SERVER['HTTP_HOST'] . '/geek/rpg/character.php?id=' . $char->id);
              die;
            }
          }
        }
        $page->Start($char->name . ' - edit rpg character', 'edit rpg character');
        $charentry->WriteHTML(true);
        $page->End();
        die;
      }
      $page->Start($char->name . ' - rpg character', $char->name, 'level ' . $char->level . ' ' . $char->classname);
      $page->Heading('vitals');
?>
      <table class="columns" cellspacing="0">
        <tr><th>name</th><td><?=$char->name; ?></td></tr>
        <tr><th>level</th><td><?=$char->level; ?></td></tr>
        <tr><th>class</th><td><?=$char->classname; ?></td></tr>
        <tr><th>game</th><td><?=formatGameName($char->gamename, $char->gamebase); ?></td></tr>
      </table>
<?
      if($user->ID == $char->uid)
        $page->Info('since this is your character, you may <a href="?id=' . $char->id . '&amp;edit">edit the vitals</a>');
      $page->Heading('history');
      $history = 'select instant, level from rpghistory where `char`=\'' . $char->id . '\' order by instant desc';
      if($history = $db->Get($history, 'error looking up history', 'no history found')) {
?>
      <table class="text" cellspacing="0">
        <thead><tr><th>level</th><th>as of</th></tr></thead>
        <tbody>
<?
        while($update = $history->NextRecord()) {
?>
          <tr><td class="number"><?=$update->level; ?></td><td><?=strtolower(auText::SmartTime($update->instant, $user)); ?></td></tr>
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
    header('Location: http://' . $_SERVER['HTTP_HOST'] . '/geek/rpg/');
    die;
  }
  if($user->Valid) {
    $charentry = getCharForm($db);
    if($charentry->CheckInput(true)) {
      if(verifyClass($db, $page, $_POST['class'], $_POST['game'])) {
        $char = 'insert into rpgchars (uid, name, game, class, level) values (\'' . $user->ID . '\', \'' . addslashes(htmlspecialchars($_POST['name'], ENT_COMPAT, _CHARSET)) . '\', ' . +$_POST['game'] . ', ' . +$_POST['class'] . ', ' . +$_POST['level'] . ')';
        if(false !== $char = $db->Put($char, 'error saving character')) {
          $db->Put('insert into rpghistory (`char`, instant, level) values (\'' . addslashes($char) . '\', ' . time() . ', ' . +$_POST['level'] . ')');
          header('Location: http://' . $_SERVER['HTTP_HOST'] . '/geek/rpg/character.php?id=' . $char);
          die;
        }
      }
    }
    $page->Start('add new rpg character');
    $charentry->WriteHTML(true);
    $page->End();
    die;
  }

  /**
   * Gets the RPG character entry form.
   *
   * @param auDBBase $db Database connection.
   * @param object $char Character object from the database, or false if creating a new character.
   * @return auForm RPG character entry form.
   */
  function getCharForm(&$db, $char = false) {
    $form = new auForm('charentry', $char ? '?id=' . $char->id . '&edit' : '');
    $form->Add(new auFormString('name', 'name', 'the character’s name in the game', true, $char->name, 10, 57));
    // game dropdown
    $gamelist[""] = "";
    $games = 'select g.id, g.name, b.name as base from rpgames as g left join rpgames as b on b.id=g.expansionbase';
    if($games = $db->Get($games, 'error looking up games', 'no games defined'))
      while($game = $games->NextRecord())
        $gamelist[$game->id] = formatGameName($game->name, $game->base);
        else
          $gamelist[$game->id] = $game->name;
    $form->Add(new auFormSelect('game', 'game', 'which game this character is in', true, $gamelist, $char->game));
    // class dropdown
    $classes = 'select c.id, c.name, g.name as game, b.name as base from rpgclasses as c left join rpgames as g on g.id=c.game left join rpgames as b on b.id=g.expansionbase order by c.game';
    if($classes = $db->Get($classes, 'error looking up classes', 'no classes defined'))
      while($class = $classes->NextRecord())
        $classlist[$class->id] = $class->name . ' — ' . formatGameName($class->game, $class->base);
    $form->Add(new auFormSelect('class', 'class', 'character’s class', true, $classlist, $char->class));

    $form->Add(new auFormInteger('level', 'level', 'character’s level', true, $char->level, 3, 3));
    $form->Add(new auFormButtons('save', 'save this character'));
    return $form;
  }

  /**
   * Formats a game name by putting the name of an expansion's base game in
   * parenthesis if the game is an expansion.
   *
   * @param string $name Name of the game or expansion pack.
   * @param string $base Name of the game the expansion is for, or blank/null if not an expansion.
   * @return string Formatted game name.
   */
  function formatGameName($name, $base) {
    if($base)
      return $name . ' (' . $base . ')';
    return $name;
  }

  /**
   * Verifies that a class is allowed for a game.
   *
   * @param auDBBase $db Database connection object.
   * @param auPage $page Page layout object.
   * @param integer $class ID of character class to check.
   * @param integer $game ID of game to check against.
   * @return bool Whether the class is allowed.
   */
  function verifyClass(&$db, &$page, $class, $game) {
    $class = 'select c.game, e.id as expansion from rpgclasses as c left join rpgames as g on g.id=c.game left join rpgames as e on e.expansionbase=g.id where c.id=\'' . addslashes($class) . '\'';
    if($class = $db->GetRecord($class, 'error checking if class is correct for game', 'class not found', true)) {
      if($class->game != $game && $class->expansion != $game) {
        $page->Error('choose a class appropriate for the selected game');
        return false;
      }
      return true;
    }
    return false;
  }
?>
