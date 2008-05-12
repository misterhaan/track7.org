<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if($user->Valid)
    $page->Info('<a href="character.php">add a new character</a> or select one of your characters below to edit');
  else
    $page->Info('log in or register to post your own characters');

  $chars = '';
  if(isset($_GET['game'])) {
    $game = 'select g.name, b.name as base from rpgames as g left join rpgames as b on b.id=g.expansionbase where g.id=\'' . addslashes($_GET['game']) . '\'';
    $game = $db->GetRecord($game, 'error looking up game name', 'game not found', true);
    if(isset($_GET['player'])) {
      $chars = 'where g.id=\'' . addslashes($_GET['game']) . '\' and u.login=\'' . addslashes($_GET['player']) . '\' ';
      $page->Start(htmlspecialchars($_GET['player']) . '’s ' . htmlspecialchars(formatGameName($game->name, $game->base)) . ' characters');
    } else {
      $chars = 'where g.id=\'' . addslashes($_GET['game']) . '\' ';
      $page->Start(htmlspecialchars(formatGameName($game->name, $game->base)) . ' characters');
    }
  } elseif(isset($_GET['player'])) {
    $chars = 'where u.login=\'' . addslashes($_GET['player']) . '\' ';
    $page->Start(htmlspecialchars($_GET['player']) . '’s role-playing game characters');
  } else
    $page->Start('role-playing game characters');
  $chars = 'select c.id, u.login, c.name, c.game, g.name as gamename, gb.name as gamebase, cl.name as classname, c.level from rpgchars as c left join users as u on u.uid=c.uid left join rpgames as g on g.id=c.game left join rpgames as gb on gb.id=g.expansionbase left join rpgclasses as cl on cl.id=c.class ' . $chars . 'order by level desc';
  if($chars = $db->GetSplit($chars, 20, 0, '', '', 'error looking up characters', 'no characters found')) {
?>
      <table class="text" cellspacing="0">
        <thead><tr><th>name</th><th>level</th><th>class</th><th>game</th><th>player</th></tr></thead>
        <tbody>
<?
    while($char = $chars->NextRecord()) {
?>
          <tr><td><a href="character.php?id=<?=$char->id; ?>"><?=$char->name; ?></a></td><td class="number"><?=$char->level; ?></td><td><?=$char->classname; ?></td><td><a href="?game=<?=$char->game; ?>"><?=formatGameName($char->gamename, $char->gamebase); ?></a></td><td><a href="?player=<?=$char->login; ?>"><?=$char->login; ?></a></td></tr>
<?
    }
?>
        </tbody>
      </table>
<?
  }
  $page->End();

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
?>
