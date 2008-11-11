<?
/*----------------------------------------------------------------------------*\
 | creates an image which is a chart of daily hits.  used by the monthly      |
 | stats page.                                                                |
 |                                                                            |
 | 2008.11.11:                                                                |
 |  - fixed maxy to include oldest day                                        |
 | 2007.01.23:                                                                |
 |  - changed to new layout classes                                           |
 | 2006.04.21:                                                                |
 |  - changed to new hits tables                                              |
 |  - increased default width to 450 (from 400)                               |
 | 2005.06.23:                                                                |
 |  - changed to new layout classes                                           |
 |  - increased default width to 400 (from 275)                               |
 |  - don't do transparent bars, set bar color from style in querystring      |
 | 2004.06.25:                                                                |
 |  - added comments                                                          |
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
  // default / max number of days is the number of available pixels
  if(!isset($_GET['d']) || $_GET['d'] + 2 > $_GET['w'])
    $_GET['d'] = $_GET['w'] - 2;

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

  if(isset($_GET['month'])) {
    $where = date('Y-m', strtotime($_GET['month'] . '-01') + 2764800);  // 2764800 seconds == 32 days
    $where = ' where `date`<\'' . $where . '-31\'';
  }
  $stats = 'select `date` from hitcounts' . $where . ' and date like \'%-%-%\' order by `date` desc';
  $stats = $db->GetLimit($stats, 0, $_GET['d']);
  if($stats->IsError())
    imageString($png, 2, 3, 3, 'error reading day statistics', $grey);
  else {
    $days = $stats->NumRecords();

    // write the first and last day on the graph
    $day = $stats->NextRecord();
    imageString($png, 2, $_GET['w'] - 60, $_GET['h'] - 15, $day->date, $grey);
    while($nextday = $stats->NextRecord())
      $day = $nextday;
    imageString($png, 2, 1, $_GET['h'] - 15, $day->date, $grey);

    // get highest number of hits
    $maxy = 'select max(`unique`) as maxhits from hitcounts where date like \'%-%-%\' and `date`>=\'' . $day->date . '\'';
    $maxy = $db->Get($maxy);
    if($maxy->IsError())
      imageString($png, 2, 3, 3, 'error trying to find max number of hits', $grey);
    else {
      $maxy = $maxy->NextRecord();
      $maxy = $maxy->maxhits;

      $h = $_GET['h'] - 17;
      $w = ($_GET['w'] - 2) / $days;
      $l = $_GET['w'] - 1 - $w;

      $stats = 'select `date`, `unique` as uhits from hitcounts' . $where . ' and date like \'%-%-%\' order by `date` desc';
      $stats = $db->GetLimit($stats, 0, $_GET['d']);
      if($stats->IsError())
        imageString($png, 2, 3, 3, 'error reading day statistics', $grey);
      else {
        while($day = $stats->NextRecord()) {
          imageFilledRectangle($png, $l, 1 + $h * (1 - $day->uhits / $maxy), $l + $w - 1, $h, substr($day->date, 0, 7) == $_GET['month'] ? $bars : $greybars);
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
  imageInterlace($png,1);
  imagePNG($png);
?>
