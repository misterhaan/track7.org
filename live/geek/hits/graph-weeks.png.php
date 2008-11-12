<?
/*----------------------------------------------------------------------------*\
 | creates an image which is a chart of weekly hits.  used by the yearly      |
 | stats page.                                                                |
 |                                                                            |
 | 2008.11.12:                                                                |
 |  - highlight current year                                                  |
 | 2008.11.11:                                                                |
 |  - copied from graph-months file                                           |
 |                                                                            |
\*----------------------------------------------------------------------------*/

  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  // default style is water
  if(!isset($_GET['style']))
    $_GET['style'] = 'water';
  // default width is 450 pixels
  if(!isset($_GET['w']))
    $_GET['w'] = 450;
  // default height is 250 pixels
  if(!isset($_GET['h']))
    $_GET['h'] = 250;
  // default / max number of weeks is the number of available pixels
  if(!isset($_GET['k']) || $_GET['k'] + 2 > $_GET['w'])
    $_GET['k'] = $_GET['w'] - 2;

  $png = imageCreate($_GET['w'], $_GET['h']);

  $white = imageColorAllocate($png, 255, 255, 255);
  $grey = imageColorAllocate($png, 102, 102, 102);
  $greybars = imageColorAllocate($png, 204, 204, 204);
  switch($_GET['style']) {
    case 'air':
      $bars = imageColorAllocate($png, 104, 104, 104);
      break;
    case 'earth':
      $bars = imageColorAllocate($png, 153, 102, 85);
      break;
    case 'fire':
      $bars = imageColorAllocate($png, 204, 76, 60);
      break;
    default:
      $bars = imageColorAllocate($png, 51, 119, 170);
  }

  // draw the main graph area
  imageFilledRectangle($png, 0, 0, $_GET['w'] - 1, $_GET['h'] - 1, $white);
  imageRectangle($png, 0, 0, $_GET['w'] - 1, $_GET['h'] - 16, $grey);

  $stats = 'select date as week from hitcounts where date like \'%w%\' order by date desc';
  $stats = $db->GetLimit($stats, 0, $_GET['k']);
  if($stats->IsError())
    imageString($png, 2, 3, 3, 'error reading week statistics', $grey);
  else {
    $weeks = $stats->NumRecords();

    // write the first and last month on the graph
    $week = $stats->NextRecord();
    imageString($png, 2, $_GET['w'] - 42, $_GET['h'] - 15, $week->week, $grey);
    while($nextweek = $stats->NextRecord())
      $week = $nextweek;
    imageString($png, 2, 1, $_GET['h'] - 15, $week->week, $grey);

    // get highest number of average hits
    $maxy = 'select max(`unique`/days) as hits from hitcounts where date like \'%w%\' and date>=\'' . $week->week . '\'';
    $maxy = $db->Get($maxy);
    if($maxy->IsError())
      imageString($png, 2, 3, 3, 'error trying to find max number of hits', $grey);
    else {
      $maxy = $maxy->NextRecord();
      $maxy = $maxy->hits;
      header('x-debug-maxy: ' . $maxy);
      if($maxy == 0)
        $maxy = 1;

      $h = $_GET['h'] - 17;
      $w = ($_GET['w'] - 2) / $weeks;
      $l = $_GET['w'] - 1 - $w;

      $stats = 'select date, (`unique`/days) as hits from hitcounts where date like \'%w%\' order by date desc';
      $stats = $db->GetLimit($stats, 0, $_GET['k']);
      if($stats->IsError())
        imageString($png, 2, 3, 3, 'error reading week statistics', $grey);
      else {
        while($week = $stats->NextRecord()) {
          imageFilledRectangle($png, $l, 1 + $h * (1 - $week->hits / $maxy), $l + $w - 1, $h, substr($week->date, 0, 4) == $_GET['year'] ? $bars : $greybars);
          $l -= $w;
        }
      }
    }
  }

  $lastmod = strtotime(date('Y-m-d') . ' 03:30:00');
  if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $lastmod <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    header('HTTP/1.0 304 Not Modified');
    die;
  }
  header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastmod) . ' GMT');
  header('Content-type: image/png');
  header('Cache-Control: public');  // allow caching
  imageInterlace($png, 1);
  imagePNG($png);
?>
