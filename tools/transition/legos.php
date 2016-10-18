<?php
  define('TR_LEGOS', 7);
  define('STEP_COPYLEGOS', 1);
  define('STEP_COPYVOTES', 2);
  define('STEP_COPYFILES', 3);

  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
  $html = new t7html([]);
  $html->Open('lego migration');
?>
      <h1>lego migration</h1>
<?php
  if(isset($_GET['dostep']))
    switch($_GET['dostep']) {
      case 'copylegos':
        if($db->real_query('insert into lego_models (url, title, posted, deschtml, pieces, mans) select id, name, adddate, concat(\'<p>\',replace(notes,\'&nbsp;\',\' \'),\'</p>\'), pieces, minifigs from track7_t7data.legos'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYLEGOS . ', status=\'lego models migrated\' where id=' . TR_LEGOS . ' and stepnum<' . STEP_COPYLEGOS);
          else
            echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'copyvotes':
        if($db->real_query('insert into lego_votes (lego, voter, ip, vote, posted) select l.id, u.id, inet_aton(v.ip), case v.vote when -3 then 1 when 3 then 5 else v.vote+3 end as vote, v.time from track7_t7data.votes as v left join track7_t7data.ratings as r on r.id=v.ratingid left join lego_models as l on l.url=r.selector left join transition_users as u on u.olduid=v.uid where r.type=\'lego\' order by v.time'))
          if($db->real_query('update lego_models as l, (select lego, round((sum(vote)+3)/(count(1)+1),2) as rating, count(1) as votes from lego_votes group by lego) as s set l.rating=s.rating, l.votes=s.votes where l.id=s.lego'))
            $db->real_query('update transition_status set stepnum=' . STEP_COPYVOTES . ', status=\'lego votes migrated\' where id=\'' . TR_LEGOS . '\' and stepnum<' . STEP_COPYVOTES);
          else
            echo '<pre><code>' . $db->error . '</code></pre>';
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'copyfiles':
        exec('cp ' . $_SERVER['DOCUMENT_ROOT'] . '/art/lego/*.png ' . $_SERVER['DOCUMENT_ROOT'] . '/lego/data/');
        exec('cp ' . $_SERVER['DOCUMENT_ROOT'] . '/art/lego/*.zip ' . $_SERVER['DOCUMENT_ROOT'] . '/lego/data/');
        $db->real_query('update transition_status set stepnum=' . STEP_COPYFILES . ', status=\'lego completed\' where id=' . TR_LEGOS . ' and stepnum<' . STEP_COPYFILES);
        break;
    }

  if($status = $db->query('select stepnum, status from transition_status where id=' . TR_LEGOS))
    $status = $status->fetch_object();
?>
      <h2>lego models</h2>
<?php
  if($status->stepnum < STEP_COPYLEGOS) {
?>
      <p>
        the lego migration begins with the table of lego models.  make sure the
        lego_models table, and triggers to update contributions (along with
        contribution enum entries) have been created, then copy those legos!
      </p>
      <nav class=calltoaction><a class="copy action" href="?dostep=copylegos">copy those legos!</a></nav>
<?php
  } else {
?>
      <p>lego models have been migrated successfully.</p>

      <h2>lego votes</h2>
<?php
    if($status->stepnum < STEP_COPYVOTES) {
?>
      <p>
        lego models have some votes, so bring forward their votes and
        recalculate their ratings.
      </p>
      <nav class=calltoaction><a class="okay action" href="?dostep=copyvotes">copy those votes!</a></nav>
<?php
    } else {
?>
      <p>votes have been migrated successfully.</p>

      <h2>lego files</h2>
<?php
      if($status->stepnum < STEP_COPYFILES) {
?>
      <p>
        there are 2 images and 2 archive files for each model, so let’s copy
        them all to the new directory.
      </p>
      <nav class=calltoaction><a class="okay action" href="?dostep=copyfiles">copy those files!</a></nav>
<?php
      } else {
?>
      <p>files have been migrated successfully.</p>
<?php
      }
    }
  }
  $html->Close();
?>
