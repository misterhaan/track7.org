<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  $jump[] = 'bln';
  $sections = 'select id, name, description from pensections order by sort';
  if($sections = $db->Get($sections, 'Error looking up sections', 'No sections defined')) {
    while($section = $sections->NextRecord()) {
      $sects[] = $section;
      $jump[] = $section->id;
    }
    $page->Start('pen vs. sword', null, '<em>which will prove mightier?</em>', '', $jump);
    $page->Heading('bln', 'bln');
?>
      <p>
        bln is a 'natural blog' because i know too much about math and thought
        that was a clever name.&nbsp; it contains some stuff that used to be the
        thoughts section.&nbsp; it's basically stream-of-consciousness style
        writing (as is usually the case with blogs), so be aware that some
        (most?) of it is rather raw.
      </p>

<?
    echo "      <dl>\n";
    echo '        <dt><a href="bln/">a natural blog</a></dt>' . "\n";
    $cats = 'select cat, count(1) as count from bln group by cat order by concat(cat)';
    if($cats = $db->Get($cats, 'error looking up categories', 'no categories in use')) {
      echo "        <dd><ul>\n";
      while($cat = $cats->NextRecord()) {
?>
            <li><a href="bln/<?=$cat->cat; ?>/"><?=$cat->cat; ?></a> - <?=$cat->count == 1 ? '1 entry' : $cat->count . ' entries'; ?></li>
<?
      }
      echo "        </ul></dd>\n";
    }
    echo "      </dl>\n\n";

    foreach($sects as $sect) {
      $page->Heading($sect->name, $sect->id);
      echo $sect->description;
      $stories = 'select id, name, description, posted from penstories where section=\'' . $sect->id . '\' order by sort';
      if($stories = $db->Get($stories, 'Error looking up stories', '')) {
?>
      <dl>
<?
        while($story = $stories->NextRecord()) {
          echo '        <dt>';
          if($story->posted)
            echo '<span class="when">' . $story->posted . '</span>';
          echo '<a href="' . $sect->id . '/' . $story->id . '.php">' . $story->name . "</a></dt>\n";
          if($story->description)
            echo '        <dd>' . $story->description . "</dd>\n";
        }
?>
      </dl>
<?
      }
    }
  } else
    $page->Start('pen vs. sword', null, '<em>which will prove mightier?</em>');
  $page->End();
?>
