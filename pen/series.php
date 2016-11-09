<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'stories':
        if(isset($_GET['series']) && $_GET['series'] == +$_GET['series'])
          if($stories = $db->query('select posted, url, title, deschtml from stories where published=1 and series=\'' . +$_GET['series'] . '\' order by posted')) {
            $ajax->Data->stories = [];
            while($story = $stories->fetch_object()) {
              if($story->posted > 100)
                $story->posted = t7format::TimeTag('M j, Y', $story->posted, 'g:i a \o\n l F jS Y');
              else
                $story->posted = false;
              $ajax->Data->stories[] = $story;
            }
          } else
            $ajax->Fail('database error looking up stories.');
        else
          $ajax->Fail('series must be provided as a numeric id.');
        break;
    }
    $ajax->Send();
    die;
  }

  if($series = $db->query('select id, url, title, deschtml from stories_series where url=\'' . $db->escape_string($_GET['series']) . '\''))
    if($series = $series->fetch_object()) {
      $html = new t7html(['ko' => true]);
      $html->Open(htmlspecialchars($series->title) . ' - stories');
?>
      <h1 data-series-id=<?php echo +$series->id; ?>><?php echo htmlspecialchars($series->title); ?></h1>
<?php
      echo $series->deschtml;
?>
      <!-- ko foreach: stories -->
      <article>
        <h2><a data-bind="text: title, attr: {href: url}"></a></h2>
        <p class=postmeta data-bind="visible: posted">
          <span data-bind="visible: posted">posted <time data-bind="text: posted.display, attr: {datetime: posted.datetime, title: posted.title}"></time></span>
        </p>
        <div class=description data-bind="html: deschtml"></div>
      </article>
      <!-- /ko -->
<?php
      $html->Close();
    } else
      ;  // TODO:  series not found
  else
    ; // TODO:  error looking up series
?>
