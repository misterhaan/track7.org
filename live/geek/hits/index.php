<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->start('track7 hit statistics');

  // last 7 days
  $daily = 'select date, raw, `unique` from hitcounts where date like \'%-%-%\' order by date desc';
  if($daily = $db->GetLimit($daily, 0, 7, 'error reading daily statistics', '')) {
    $page->Heading('daily statistics');
?>
      <img class="hitchart" src="graph-days.png?d=93&amp;month=<?=date('Y-m'); ?>&amp;style=<?=$user->Style; ?>" alt="" />
      <table class="data" id="datestats" cellspacing="0">
        <thead><tr><th>date</th><th title="total hits">raw</th><th title="total hits excluding hits from the same ip within 6 hours">unique</th><th title="average number of pages viewed per visit (raw / unique)">pages</th></tr></thead>
        <tbody>
<?
    if($user->GodMode) {
      while($day = $daily->NextRecord()) {
?>
          <tr><td class="date"><a href="detail.php?day=<?=$day->date; ?>"><?=date('m·d·Y', strtotime($day->date)); ?></a></td><td class="number"><?=$day->raw; ?></td><td class="number"><?=$day->unique; ?></td><td class="number"><?=$day->unique > 0 ? number_format($day->raw / $day->unique, 1) : ''; ?></td><td class="clear"><a href="hits.php?date=<?=$day->date; ?>"><img src="/style/details.png" alt="details" /></a></td></tr>
<?
      }
    } else {
      while($day = $daily->NextRecord()) {
?>
          <tr><td class="date"><a href="detail.php?day=<?=$day->date; ?>"><?=date('m·d·Y', strtotime($day->date)); ?></a></td><td class="number"><?=$day->raw; ?></td><td class="number"><?=$day->unique; ?></td><td class="number"><?=$day->unique > 0 ? number_format($day->raw / $day->unique, 1) : ''; ?></td></tr>
<?
      }
    }
?>
        </tbody>
      </table>

<?
  }

  // last 4 weeks
  $weekly = 'select date, raw/days as raw, `unique`/days as `unique` from hitcounts where date like \'%w%\' and days>0 order by date desc';
  if($weekly = $db->GetLimit($weekly, 0, 4, 'error reading weekly statistics', '')) {
    $page->Heading('weekly statistics');
?>
      <img class="hitchart" src="graph-weeks.png?k=52&amp;style=<?=$user->Style; ?>" alt="" />
      <table class="data" id="datestats" cellspacing="0">
        <thead><tr><th>week</th><th title="average hits per day">raw</th><th title="average hits per day excluding hits from the same ip within 6 hours">unique</th><th title="average number of pages viewed per visit (raw / unique)">pages</th></tr></thead>
        <tbody>
<?
    while($week = $weekly->NextRecord()) {
?>
          <tr><td class="date"><a href="detail.php?week=<?=$week->date; ?>">week <?=+substr($week->date, 5); ?></a></td><td class="number"><?=number_format($week->raw, 1); ?></td><td class="number"><?=number_format($week->unique, 1); ?></td><td class="number"><?=$week->unique > 0 ? number_format($week->raw / $week->unique, 1) : ''; ?></td></tr>
<?
    }
?>
        </tbody>
      </table>

<?
  }

  // last 6 months
  $monthly = 'select date, raw/days as raw, `unique`/days as `unique` from hitcounts where date like \'%-%\' and not (date like \'%-%-%\') and days>0 order by date desc';
  if($monthly = $db->GetLimit($monthly, 0, 6, 'error reading monthly statistics', '')) {
    $page->Heading('monthly statistics');
?>
      <img class="hitchart" src="graph-months.png?year=<?=date('Y'); ?>&amp;style=<?=$user->Style; ?>" alt="" />
      <table class="data" id="datestats" cellspacing="0">
        <thead><tr><th>month</th><th title="average hits per day">raw</th><th title="average hits per day excluding hits from the same ip within 6 hours">unique</th><th title="average number of pages viewed per visit (raw / unique)">pages</th></tr></thead>
        <tbody>
<?
    while($month = $monthly->NextRecord()) {
?>
          <tr><td class="date"><a href="detail.php?month=<?=$month->date; ?>"><?=strtolower(date('M Y', strtotime($month->date . '-15'))); ?></a></td><td class="number"><?=number_format($month->raw, 1); ?></td><td class="number"><?=number_format($month->unique, 1); ?></td><td class="number"><?=$month->unique > 0 ? number_format($month->raw / $month->unique, 1) : ''; ?></td></tr>
<?
    }
?>
        </tbody>
      </table>

<?
  }

  // all years
  $yearly = 'select date, raw/days as raw, `unique`/days as `unique` from hitcounts where not(date like \'%-%\' or date like \'%w%\') and days>0 order by date desc';
  if($yearly = $db->Get($yearly, 'error reading yearly statistics', '')) {
    $page->Heading('yearly statistics');
?>
      <img class="hitchart" src="graph-years.png?style=<?=$user->Style; ?>" alt=""/>
      <table class="data" id="datestats" cellspacing="0">
        <thead><tr><th>year</th><th title="average hits per day">raw</th><th title="average hits per day excluding hits from the same ip within 6 hours">unique</th><th title="average number of pages viewed per visit (raw / unique)">pages</th></tr></thead>
        <tbody>
<?
    while($year = $yearly->NextRecord()) {
?>
          <tr><td class="date"><a href="detail.php?year=<?=$year->date; ?>"><?=$year->date; ?></a></td><td class="number"><?=number_format($year->raw, 1); ?></td><td class="number"><?=number_format($year->unique, 1); ?></td><td class="number"><?=$year->unique > 0 ? number_format($year->raw / $year->unique, 1) : ''; ?></td></tr>
<?
    }
?>
        </tbody>
      </table>

<?
  }
  
  // forever, from totalling all years
  $forever = 'select sum(raw)/sum(days) as raw, sum(`unique`)/sum(days) as `unique` from hitcounts where not (date like \'%-%\' or date like \'%w%\')';
  if($forever = $db->GetRecord($forever, 'error reading forever statistics', '')) {
    $page->Heading('overall');
?>
      <table class="data" id="datestats" cellspacing="0">
        <thead><tr><th></th><th title="average hits per day">raw</th><th title="average hits per day excluding hits from the same ip within 6 hours">unique</th><th title="average number of pages viewed per visit (raw / unique)">pages</th></tr></thead>
        <tbody><tr><td class="date"><a href="detail.php">overall</a></td><td class="number"><?=number_format($forever->raw, 1); ?></td><td class="number"><?=number_format($forever->unique, 1); ?></td><td class="number"><?=$forever->unique > 0 ? number_format($forever->raw / $forever->unique, 1) : ''; ?></td></tr></tbody>
      </table>

<?
  }

  $page->End();
?>
