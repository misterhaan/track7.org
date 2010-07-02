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
        bln is a &lsquo;natural blog&rsquo; because i thought that was a clever
        name.
      </p>
      <dl>
        <dt><a href="bln/">a natural blog</a></dt>
        <dd>

<?
    $page->TagCloud('entries', 'bln/tag=', 2, 4, 8, 16);  // keep in sync with bln/index.php
?>
        </dd>
      </dl>

<?

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
