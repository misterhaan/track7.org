<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(isset($_GET['entry'])) {
    $entry = 'select name, instant, cat, title, post from bln where name=\'' . addslashes($_GET['entry']) . '\'';
    if($entry = $db->GetRecord($entry, 'error looking up entry', '', true)) {
      if($_GET['cat'] != $entry->cat) {  // wrong category, so fix url
        header('Location: http://' . $_SERVER['HTTP_HOST'] . str_replace(array('/' . $_GET['cat'] . '/', 'cat=' . $_GET['cat']), array('/' . $entry->cat . '/', 'cat=' . $_GET['cat']), $_SERVER['REQUEST_URI']));
        die;
      }
      $page->Start($entry->title . ' - ' . $_GET['cat'] . ' - bln', $entry->title, $_GET['cat'] . ' - bln');

      if($entry->instant)
        $page->Heading('<span class="when">' . strtolower($user->tzdate('M j, Y', $entry->instant)) . '</span>' . $entry->title);
      else
        $page->Heading($entry->title);
?>
      <p>
        <?=$entry->post; ?>

      </p>
<?
      $page->SetFlag(_FLAG_PAGES_COMMENTS);  // show comments
    } else {
      $page->Show404();
      die;
    }
  } elseif(isset($_GET['cat'])) {
    $cats = 'show columns from bln like \'cat\'';
    if($cats = $db->Get($cats, 'error reading possible category values', 'category column missing', true)) {
      $cats = $cats->NextRecord();
      if(strpos($cats->Type, '\'' . $_GET['cat'] . '\'') === false)
        $engine->Show404();
    }
    $page->Start($_GET['cat'] . ' - bln', $_GET['cat'], 'bln - a natural blog');
    if($user->GodMode) {
?>
      <ul><li><a href="/pen/bln/admin/newentry=<?=$_GET['cat']; ?>">add a new entry</a></li></ul>

<?
    }
    $entries = 'select name, instant, title, post from bln where cat=\'' . $_GET['cat'] . '\' order by instant desc';
    if($entries = $db->GetSplit($entries, 10, 0, '', '', 'error looking up most recent entries', 'no entries have been made in this category yet')) {
      while($entry = $entries->NextRecord()) {
        if($entry->instant)
          $page->Heading('<span class="when">' . strtolower($user->tzdate('M j, Y', $entry->instant)) . '</span>' . $entry->title);
        else
          $page->Heading($entry->title);
?>
      <p>
        <?=$entry->post; ?>

      </p>
<?
        $comments = 'select count(1) as c from comments where page=\'/output/pen/bln/' . $_GET['cat'] . '/' . $entry->name . '\'';
        if(false !== $comments = $db->GetValue($comments, 'error finding number of comments on this entry', '')) {
?>
      <p><a href="/pen/bln/<?=$_GET['cat']; ?>/<?=$entry->name; ?>#comments"><?=$comments; ?> comment<?=$comments == 1 ? '' : 's'; ?> on this entry</a></p>

<?
        }
      }
      $page->SplitLinks();
    }
  } else {
    $page->Start('bln', 'bln', 'a natural blog');
?>
      <p>
        i decided to replace my old 'thoughts' pages with this thing.&nbsp;
        everything that used to be there has been gone for a while, and i expect
        that i will bring back some of them, but certainly not all.&nbsp;
        another thing to note is the name 'bln.'&nbsp; as you probably can tell,
        this is a blog.&nbsp; i didn't want to simply call it 'blog' (how
        boring!), so i started thinking about it.&nbsp; really it's just the
        letter b in front of the word log, which is also an abbreviation for
        logarithm (yes i realize i am exposing some geekiness here--it gets
        worse).&nbsp; what's better than a common log?&nbsp; a natural log!&nbsp;
        so the log changed to ln and thus i arrived at the name bln, which is of
        course a natural blog.&nbsp; note that i have not decided what 'natural
        blog' should mean, so at this point it means absolutely nothing.
      </p>
<?
    $cats = 'select cat, count(1) as num from bln group by cat order by concat(cat)';
    if($cats = $db->Get($cats, 'error looking up categories', 'no categories found')) {
?>
      <p>
        choose a category below to only show entries in that category
      </p>
      <ul>
<?
      while($cat = $cats->NextRecord()) {
        $catarr[] = $cat->cat;
?>
        <li><a href="/pen/bln/<?=$cat->cat; ?>/"><?=$cat->cat; ?></a> (<?=$cat->num; ?> entr<?=$cat->num > 1 ? 'ies' : 'y'; ?>)</li>
<?
      }
      if($user->GodMode) {
        $cats = 'show columns from bln like \'cat\'';
        if($cats = $db->GetRecord($cats, '', '')) {
          $cats = explode(',', str_replace(array('enum(', ')', '\''), '', $cats->Type));
          foreach($cats as $cat) {
            if(!in_array($cat, $catarr)) {
?>
        <li><a href="/pen/bln/<?=$cat; ?>/"><?=$cat; ?></a> (0 entries)</li>
<?
            }
          }
        }
?>
        <li><a href="/pen/bln/admin/newcat">add a new category</a></li>
<?
      }
?>
      </ul>
<?
    }
    $entries = 'select name, instant, cat, title, post from bln order by instant desc';
    if($entries = $db->GetSplit($entries, 10, 0, '', '', 'error looking up most recent entries', 'no entries have been made yet')) {
?>

      <hr class="minor" />

<?
      while($entry = $entries->NextRecord()) {
        if($entry->instant)
          $page->Heading('<span class="when">posted in ' . $entry->cat . ', ' . strtolower($user->tzdate('M j, Y', $entry->instant)) . '</span>' . $entry->title);
        else
          $page->heading('<span class="when">posted in ' . $entry->cat . '</span>' . $entry->title);
?>
      <p>
        <?=$entry->post; ?>

      </p>
<?
        $comments = 'select count(1) from comments where page=\'/output/pen/bln/' . $entry->cat . '/' . $entry->name . '\'';
        if(false !== $comments = $db->GetValue($comments, 'error finding number of comments on this entry', '')) {
?>
      <p><a href="/pen/bln/<?=$entry->cat; ?>/<?=$entry->name; ?>#comments"><?=$comments; ?> comment<?=$comments == 1 ? '' : 's'; ?> on this entry</a></p>

<?
        }
      }
      $page->SplitLinks();
    }
  }
  $page->End();
?>
