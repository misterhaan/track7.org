<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(isset($_GET['cd'])) {
    $cd = 'select id, title, coverart, music, time from compcds where id=\'' . addslashes($_GET['cd']) . '\'';
    if($cd = $db->GetRecord($cd, 'error looking up cd information', 'cd not found')) {
      $page->Start($cd->title, '<img class="cd" src="' . dirname($_SERVER['PHP_SELF']) . '/' . $cd->id . '.jpg" alt="' . $cd->title . '" />');
?>
      <h2>cover art</h2>
      <p>
        <?=$cd->coverart; ?>

      </p>

      <h2>music</h2>
      <p>
        <?=$cd->music; ?>

      </p>

<?
      $tracks = 'select track, artist, title, time from comptracks where cd=\'' . $cd->id . '\' order by track';
      if($tracks = $db->Get($tracks, 'error looking up track listing', 'no tracks found')) {
?>
      <table class="text" id="tracklist" cellspacing="0">
        <thead><tr><th>#</th><th class="artist">artist</th><th class="title">title</th><th>time</th></tr></thead>
        <tbody>
<?
        while($track = $tracks->NextRecord()) {
?>
          <tr><td><?=str_pad($track->track, 2, '0', STR_PAD_LEFT); ?></td><td class="artist"><?=$track->artist; ?></td><td class="title"><?=$track->title; ?></td><td class="number"><?=$track->time; ?></td></tr>
<?
        }
?>
        </tbody>
        <tfoot><tr><td colspan="3"></td><td class="total"><?=$cd->time; ?></td></tr></tfoot>
      </table>
<?
      }
      //$page->p->flags |= _FLAG_PAGES_COMMENTS;
    } else {
      $page->Show404();
      die;
    }
  } else {
    $page->Start('compilation cds');

    $cds = 'select id, title from compcds order by sort desc';
    if($cds = $db->Get($cds, 'error getting compilation cd information', 'no compilation cds found')) {
?>
      <p>click a cd cover for more information</p>

      <div id="covers" cellspacing="0">
<?
      while($cd = $cds->NextRecord()) {
?>
        <a title="<?=$cd->title; ?>" href="cd/<?=$cd->id; ?>.php"><img src="<?=$cd->id; ?>.jpg" alt="<?=$cd->title; ?>" /></a>
<?
      }
?>
        <br class="clear" />
      </div>

<?
    }
  }
  $page->End();
?>
