<?
/*----------------------------------------------------------------------------*\
 | creates an image which is a chart of yearly hits.  used by the overall     |
 | stats page.                                                                |
 |                                                                            |
 | 2008.11.11:                                                                |
 |  - fixed max height to include oldest year                                 |
 | 2007.01.23:                                                                |
 |  - changed to new layout classes                                           |
 | 2006.04.21:                                                                |
 |  - changed to show years                                                   |
 |  - copied from graph-months                                                |
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
  // default / max number of years is the number of available pixels
  if(!isset($_GET['y']) || $_GET['y'] + 2 > $_GET['w'])
    $_GET['y'] = $_GET['w'] - 2;

  $png = imageCreate($_GET['w'], $_GET['h']);

  $white = imageColorAllocate($png, 255, 255, 255);
  $grey = imageColorAllocate($png, 102, 102, 102);
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

  $stats = 'select date from hitcounts where not (date like \'%-%\' or date like \'%-%-%\' or date like \'%w%\') order by date desc';
  $stats = $db->GetLimit($stats, 0, $_GET['y']);
  if($stats->IsError())
    imageString($png, 2, 3, 3, 'error reading year statistics', $grey);
  else {
    $years = $stats->NumRecords();

    // write the first and last year on the graph
    $year = $stats->NextRecord();
    imageString($png, 2, $_GET['w'] - 24, $_GET['h'] - 15, $year->date, $grey);
    while($nextyear = $stats->NextRecord())
      $year = $nextyear;
    imageString($png, 2, 1, $_GET['h'] - 15, $year->date, $grey);

    // get highest number of average hits
    $maxy = 'select max(`unique`/days) as hits from hitcounts where not (date like \'%-%\' or date like \'%-%-%\' or date like \'%w%\') and date>=\'' . $year->year . '\'';
    $maxy = $db->Get($maxy);
    if($maxy->IsError())
      imageString($png, 2, 3, 3, 'error trying to find max number of hits', $grey);
    else {
      $maxy = $maxy->NextRecord();
      $maxy = $maxy->hits;
      if($maxy == 0)
        $maxy = 1;

      $h = $_GET['h'] - 17;
      $w = ($_GET['w'] - 2) / $years;
      $l = $_GET['w'] - 1 - $w;

      $stats = 'select (`unique`/days) as hits from hitcounts where not (date like \'%-%\' or date like \'%-%-%\' or date like \'%w%\') order by date desc';
      $stats = $db->GetLimit($stats, 0, $_GET['y']);
      if($stats->IsError())
        imageString($png, 2, 3, 3, 'error reading year statistics', $grey);
      else {
        while($year = $stats->NextRecord()) {
          imageFilledRectangle($png, $l, 1 + $h * (1 - $year->hits / $maxy), $l + $w - 1, $h, $bars);
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
  header('Pragma: ');
  imageInterlace($png, 1);
  imagePNG($png);
?>
